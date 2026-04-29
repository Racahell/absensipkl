<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Support\DiscordNotifier;
use App\Support\StudentMentorScopeResolver;
use App\Support\ValidationLogger;
use App\Support\WorkflowState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DailyReportValidationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRole($request);
        if ($request->user()->role === 'superadmin') {
            $status = 'pending_instruktur';
            $locationColumn = null;
        } else {
            [$status] = $this->flow($request->user()->role);
        }

        $query = DailyReport::query()
            ->with(['attendance.user', 'attendance.location'])
            ->orderByDesc('id');

        if (is_array($status)) {
            $query->whereIn('review_status', $status);
        } else {
            $query->where('review_status', $status);
        }

        $this->applyActorScope($query, $request);

        $items = $query->get()
            ->unique(function (DailyReport $item): string {
                $userId = (string) ($item->attendance?->user_id ?? '0');
                $date = (string) optional($item->attendance?->attendance_date)->toDateString();
                return $userId.'|'.$date;
            })
            ->values();
        foreach ($items as $item) {
            $this->syncEscalation($item);
        }

        return view('reports.validation', [
            'title' => 'Validasi Laporan Harian',
            'items' => $items,
            'role' => $request->user()->role,
        ]);
    }

    public function approve(Request $request, DailyReport $dailyReport): RedirectResponse
    {
        $this->authorizeRole($request);
        [$currentStatus, , $nextStatus, $noteField, $actorField, $validatedAtField] = $this->flow($request->user()->role, $dailyReport->review_status);
        $this->authorizeItem($request, $dailyReport, $currentStatus);
        $fromReviewStatus = $dailyReport->review_status;

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $dailyReport->update([
            'review_status' => $nextStatus,
            $noteField => $validated['note'] ?? null,
            'review_note_instruktur' => $request->user()->role === 'instruktur' ? ($validated['note'] ?? null) : $dailyReport->review_note_instruktur,
            $actorField => $request->user()->id,
            $validatedAtField => now(),
            'reject_reason_code' => null,
            'review_sla_due_at' => in_array($nextStatus, WorkflowState::REPORT_PENDING, true) ? WorkflowState::defaultSlaDueAt() : null,
            'review_escalated_at' => null,
            'review_escalation_level' => null,
        ]);

        ValidationLogger::log(
            $request->user(),
            'daily_report',
            (int) $dailyReport->id,
            $request->user()->role === 'instruktur' ? 'review_laporan_instruktur' : 'approve_laporan_pembimbing',
            $validated['note'] ?? null,
            [
                'from_review_status' => $fromReviewStatus,
                'to_review_status' => $nextStatus,
            ]
        );

        return back()->with('success', $request->user()->role === 'instruktur'
            ? 'Review laporan instruktur berhasil disimpan.'
            : 'Laporan harian berhasil divalidasi pembimbing.');
    }

    public function revise(Request $request, DailyReport $dailyReport): RedirectResponse
    {
        $this->authorizeRole($request);
        [$currentStatus, , , $noteField, $actorField, $validatedAtField] = $this->flow($request->user()->role, $dailyReport->review_status);
        $this->authorizeItem($request, $dailyReport, $currentStatus);
        $fromReviewStatus = $dailyReport->review_status;

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $dailyReport->update([
            'review_status' => 'revisi',
            $noteField => $validated['note'],
            'review_note_instruktur' => $request->user()->role === 'instruktur' ? $validated['note'] : $dailyReport->review_note_instruktur,
            $actorField => $request->user()->id,
            $validatedAtField => now(),
            'reject_reason_code' => 'report_revision_required',
            'review_sla_due_at' => null,
            'review_escalated_at' => null,
        ]);

        ValidationLogger::log(
            $request->user(),
            'daily_report',
            (int) $dailyReport->id,
            $request->user()->role === 'instruktur' ? 'review_revisi_instruktur' : 'revisi_laporan_pembimbing',
            $validated['note'],
            [
                'from_review_status' => $fromReviewStatus,
                'to_review_status' => 'revisi',
            ]
        );

        return back()->with('success', 'Laporan harian dikembalikan untuk revisi.');
    }

    private function authorizeItem(Request $request, DailyReport $item, string|array $status): void
    {
        if (is_array($status)) {
            abort_if(! in_array($item->review_status, $status, true), 422, 'Status laporan tidak sesuai tahapan.');
        } else {
            abort_if($item->review_status !== $status, 422, 'Status laporan tidak sesuai tahapan.');
        }

        if (! $this->canAccessStudent($request, $item->attendance?->user)) {
            abort(403, 'Tidak berwenang memvalidasi laporan ini.');
        }
    }

    /**
     * @return array{0:string|array<int, string>,1:string|null,2:string,3:string,4:string,5:string}
     */
    private function flow(string $role, ?string $currentStatus = null): array
    {
        if ($role === 'superadmin') {
            $status = $currentStatus ?? 'pending_instruktur';

            return match ($status) {
                'pending_instruktur' => ['pending_instruktur', null, 'reviewed_instruktur', 'instruktur_review_note', 'reviewed_by_instruktur', 'reviewed_instruktur_at'],
                default => abort(422, 'Status laporan ini tidak bisa diproses dari menu validasi laporan.'),
            };
        }

        return match ($role) {
            'pembimbing_pkl' => ['pending_pembimbing', null, 'pending_instruktur', 'pembimbing_review_note', 'reviewed_by_pembimbing', 'reviewed_pembimbing_at'],
            'instruktur' => ['pending_instruktur', null, 'reviewed_instruktur', 'instruktur_review_note', 'reviewed_by_instruktur', 'reviewed_instruktur_at'],
            default => match ($currentStatus ?? 'pending_instruktur') {
                // Role non-standar yang diberi akses via Menu Permission tetap bisa membuka
                // dan memproses sesuai status workflow laporan.
                'pending_pembimbing' => ['pending_pembimbing', null, 'pending_instruktur', 'pembimbing_review_note', 'reviewed_by_pembimbing', 'reviewed_pembimbing_at'],
                'pending_instruktur' => ['pending_instruktur', null, 'reviewed_instruktur', 'instruktur_review_note', 'reviewed_by_instruktur', 'reviewed_instruktur_at'],
                default => abort(422, 'Status laporan ini tidak bisa diproses dari menu validasi laporan.'),
            },
        };
    }

    private function authorizeRole(Request $request): void
    {
        // Access is controlled by menu.permission middleware + menu permissions matrix.
        // Do not block by fixed role here.
    }

    private function syncEscalation(DailyReport $item): void
    {
        if (! in_array($item->review_status, WorkflowState::REPORT_PENDING, true)) {
            return;
        }

        if (! $item->review_sla_due_at) {
            $item->update(['review_sla_due_at' => WorkflowState::defaultSlaDueAt()]);
            return;
        }

        $dueAt = Carbon::parse($item->review_sla_due_at);
        $level = WorkflowState::escalationLevel($dueAt);
        if ($level && $item->review_escalated_at === null) {
            $item->update([
                'review_escalated_at' => now(),
                'review_escalation_level' => $level,
            ]);

            DiscordNotifier::notifyEditDelete('SLA Escalation - Review Daily Report', [
                'Daily Report ID' => $item->id,
                'Siswa' => $item->attendance?->user?->name ?? '-',
                'Review Status' => $item->review_status,
                'Escalation Level' => $level,
                'Due At' => (string) $item->review_sla_due_at,
            ]);
        }
    }

    private function applyActorScope($query, Request $request): void
    {
        $actor = $request->user();
        StudentMentorScopeResolver::applyStudentScope($query, $actor, 'attendance.user');
    }

    private function canAccessStudent(Request $request, $student): bool
    {
        return StudentMentorScopeResolver::canAccessStudent($request->user(), $student);
    }
}
