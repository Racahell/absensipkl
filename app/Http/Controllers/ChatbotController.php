<?php

namespace App\Http\Controllers;

use App\Models\ChatbotMessage;
use App\Support\ChatbotMatcher;
use App\Support\RoleMenuResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        if (! $this->chatStorageReady()) {
            return response()->json([
                'session_token' => null,
                'items' => [],
            ]);
        }

        $validated = $request->validate([
            'session_token' => ['nullable', 'string', 'max:64'],
            'lang' => ['nullable', 'in:id,en'],
        ]);

        $sessionToken = (string) ($validated['session_token'] ?? '');
        if ($sessionToken === '') {
            $sessionToken = (string) ChatbotMessage::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('id')
                ->value('session_token');
        }

        if ($sessionToken === '') {
            return response()->json([
                'session_token' => null,
                'items' => [],
            ]);
        }

        $items = ChatbotMessage::query()
            ->where('user_id', $request->user()->id)
            ->where('session_token', $sessionToken)
            ->orderBy('id')
            ->limit(100)
            ->get(['is_bot', 'message', 'created_at'])
            ->map(fn (ChatbotMessage $item) => [
                'is_bot' => (bool) $item->is_bot,
                'message' => (string) $item->message,
                'created_at' => optional($item->created_at)?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json([
            'session_token' => $sessionToken,
            'items' => $items,
        ]);
    }

    public function message(Request $request, ChatbotMatcher $matcher, RoleMenuResolver $menuResolver): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'lang' => ['nullable', 'in:id,en'],
            'session_token' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $lang = (string) ($validated['lang'] ?? 'id');
        $question = trim((string) $validated['message']);
        $sessionToken = trim((string) ($validated['session_token'] ?? ''));
        if ($sessionToken === '') {
            $sessionToken = Str::uuid()->toString();
        }

        $normalizedRole = $this->normalizeRole((string) $user->role);
        $sessionContextKey = 'chatbot_context_'.$user->id;
        $roleMenus = $menuResolver->forRole($normalizedRole);
        $lastContext = (array) ($request->session()->get($sessionContextKey, []) ?: []);
        $menuFromQuestion = $menuResolver->findByQuestion($roleMenus, strtolower($question));
        if ($menuFromQuestion !== null) {
            $lastContext['last_menu_key'] = $menuFromQuestion['key'];
        }

        $result = $matcher->reply($question, $normalizedRole, $lang, [
            'last_intent_key' => $lastContext['last_intent_key'] ?? null,
            'last_menu_key' => $lastContext['last_menu_key'] ?? null,
            'role_menus' => $roleMenus,
        ]);

        if ($this->chatStorageReady()) {
            ChatbotMessage::query()->create([
                'user_id' => $user->id,
                'session_token' => $sessionToken,
                'role' => $normalizedRole,
                'lang' => $lang,
                'is_bot' => false,
                'message' => $question,
                'intent_key' => null,
                'confidence' => null,
            ]);

            ChatbotMessage::query()->create([
                'user_id' => $user->id,
                'session_token' => $sessionToken,
                'role' => $normalizedRole,
                'lang' => $lang,
                'is_bot' => true,
                'message' => (string) $result['reply'],
                'intent_key' => $result['intent_key'],
                'confidence' => $result['confidence'],
            ]);
        }

        $request->session()->put($sessionContextKey, [
            'last_intent_key' => $result['intent_key'] ?? null,
            'last_menu_key' => $result['menu_key'] ?? ($lastContext['last_menu_key'] ?? null),
        ]);

        return response()->json([
            'session_token' => $sessionToken,
            'reply' => (string) $result['reply'],
            'matched_intent' => $result['intent_key'],
            'confidence' => $result['confidence'],
        ]);
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            default => $role,
        };
    }

    private function chatStorageReady(): bool
    {
        return Schema::hasTable('chatbot_messages');
    }
}
