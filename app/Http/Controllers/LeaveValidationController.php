<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Support\DiscordNotifier;
use App\Support\StudentMentorScopeResolver;
use App\Support\ValidationLogger;
use App\Support\WorkflowState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveValidationController extends Controller
{
    /**
     * @param string|null $status
     */
    private function isAwaitingStatus(?string $status): bool
    {
        $raw = strtolower(trim((string) $status));
        return $raw === 'awaiting' || str_starts_with($raw, 'pending');
    }

    public function index(Request $request): View
    {
        $query = LeaveRequest::with(['user', 'location'])
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        $this->applyActorScope($query, $request);

        $items = $query->get()
            ->unique(function (LeaveRequest $item): string {
                $date = (string) optional($item->request_date)->toDateString();
                return $item->user_id.'|'.$date.'|'.$item->type;
            })
            ->sortByDesc(function (LeaveRequest $item): string {
                return (string) optional($item->request_date)->toDateString().'|'.str_pad((string) $item->id, 10, '0', STR_PAD_LEFT);
            })
            ->values();
        foreach ($items as $item) {
            $this->syncEscalation($item);
        }

        return view('leave_requests.validation', [
            'items' => $items,
            'role' => $request->user()->role,
            'currentStatus' => 'awaiting, approved, rejected',
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_if(! $this->isAwaitingStatus((string) $leaveRequest->status), 422, 'Hanya pengajuan dengan status awaiting yang bisa diproses.');
        [$currentStatus, , $nextStatus, $noteField, $validatorField, $validatedAtField] = $this->flow($request->user()->role, $leaveRequest->status);
        $this->authorizeItem($request, $leaveRequest, $currentStatus);

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($request, $leaveRequest, $validated, $nextStatus, $noteField, $validatorField, $validatedAtField): void {
            $fromStatus = $leaveRequest->status;
            $finalStatus = $nextStatus;
            if ($nextStatus === 'approved_final') {
                $finalStatus = 'approved';
            }

            $leaveRequest->update([
                'status' => $finalStatus,
                'reject_reason_code' => null,
                $noteField => $validated['note'] ?? null,
                $validatorField => $request->user()->id,
                $validatedAtField => now(),
                'validation_sla_due_at' => in_array($finalStatus, WorkflowState::LEAVE_PENDING, true) ? WorkflowState::defaultSlaDueAt() : null,
                'validation_escalated_at' => null,
                'validation_escalation_level' => null,
            ]);

            ValidationLogger::log(
                $request->user(),
                'leave_request',
                (int) $leaveRequest->id,
                'approve_leave',
                $validated['note'] ?? null,
                [
                    'from_status' => $fromStatus,
                    'to_status' => $finalStatus,
                    'type' => $leaveRequest->type,
                ]
            );
        });

        return back()->with('success', 'Pengajuan berhasil di-approve.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_if(! $this->isAwaitingStatus((string) $leaveRequest->status), 422, 'Hanya pengajuan dengan status awaiting yang bisa diproses.');
        [$currentStatus, , , $noteField, $validatorField, $validatedAtField] = $this->flow($request->user()->role, $leaveRequest->status);
        $this->authorizeItem($request, $leaveRequest, $currentStatus);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
            'reject_reason_code' => ['nullable', 'in:reject_izin,reject_sakit'],
        ]);
        $rejectReasonCode = $validated['reject_reason_code']
            ?? ($leaveRequest->type === 'sakit' ? 'reject_sakit' : 'reject_izin');

        $fromStatus = $leaveRequest->status;
        $leaveRequest->update([
            'status' => 'rejected',
            'reject_reason_code' => $rejectReasonCode,
            $noteField => $validated['note'],
            $validatorField => $request->user()->id,
            $validatedAtField => now(),
            'validation_sla_due_at' => null,
            'validation_escalated_at' => null,
            'validation_escalation_level' => null,
        ]);

        Attendance::query()->updateOrCreate(
            [
                'user_id' => $leaveRequest->user_id,
                'attendance_date' => optional($leaveRequest->request_date)->toDateString(),
            ],
            [
                'pkl_location_id' => $leaveRequest->pkl_location_id,
                'status' => 'alpha',
                'validation_status' => 'alpha',
                'reject_reason_code' => $rejectReasonCode,
                'validation_sla_due_at' => null,
                'validation_escalated_at' => null,
                'validation_escalation_level' => null,
            ]
        );

        ValidationLogger::log(
            $request->user(),
            'leave_request',
            (int) $leaveRequest->id,
            'reject_leave',
            $validated['note'],
            [
                'from_status' => $fromStatus,
                'to_status' => 'rejected',
                'reject_reason_code' => $rejectReasonCode,
                'type' => $leaveRequest->type,
            ]
        );

        return back()->with('success', 'Pengajuan ditolak. Status berubah menjadi Absent.');
    }

    private function authorizeItem(Request $request, LeaveRequest $item, string|array $status): void
    {
        if (is_array($status)) {
            abort_if(! in_array($item->status, $status, true), 422, 'Status pengajuan tidak sesuai tahapan.');
        } else {
            abort_if($item->status !== $status, 422, 'Status pengajuan tidak sesuai tahapan.');
        }

        if (! $this->canAccessStudent($request, $item->user)) {
            abort(403, 'Tidak berwenang memvalidasi pengajuan ini.');
        }
    }

    /**
     * @return array{0:string|array<int, string>,1:string|null,2:string,3:string,4:string,5:string}
     */
    private function flow(string $role, ?string $currentStatus = null): array
    {
        [$noteField, $validatorField, $validatedAtField] = match ($role) {
            'pembimbing_pkl' => ['pembimbing_note', 'validated_by_pembimbing', 'validated_pembimbing_at'],
            'kajur' => ['kajur_note', 'validated_by_kajur', 'validated_kajur_at'],
            default => ['instruktur_note', 'validated_by_instruktur', 'validated_instruktur_at'],
        };

        return [
            ['awaiting', 'approved', 'rejected', 'pending_pembimbing', 'pending_instruktur', 'pending_kajur', 'pending'],
            null,
            'approved',
            $noteField,
            $validatorField,
            $validatedAtField,
        ];
    }

    private function syncEscalation(LeaveRequest $item): void
    {
        if (! in_array($item->status, WorkflowState::LEAVE_PENDING, true)) {
            return;
        }

        if (! $item->validation_sla_due_at) {
            $item->update(['validation_sla_due_at' => WorkflowState::defaultSlaDueAt()]);
            return;
        }

        $dueAt = Carbon::parse($item->validation_sla_due_at);
        $level = WorkflowState::escalationLevel($dueAt);
        if ($level && $item->validation_escalated_at === null) {
            $item->update([
                'validation_escalated_at' => now(),
                'validation_escalation_level' => $level,
            ]);

            DiscordNotifier::notifyEditDelete('SLA Escalation - Validasi Pengajuan', [
                'Leave ID' => $item->id,
                'Siswa' => $item->user?->name ?? '-',
                'Status' => $item->status,
                'Escalation Level' => $level,
                'Due At' => (string) $item->validation_sla_due_at,
            ]);
        }
    }

    private function applyActorScope($query, Request $request): void
    {
        $actor = $request->user();
        StudentMentorScopeResolver::applyStudentScope($query, $actor, 'user');
    }

    private function canAccessStudent(Request $request, $student): bool
    {
        return StudentMentorScopeResolver::canAccessStudent($request->user(), $student);
    }

}
