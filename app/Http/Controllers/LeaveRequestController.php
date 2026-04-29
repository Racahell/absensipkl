<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Support\WorkflowState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $items = LeaveRequest::where('user_id', $request->user()->id)
            ->latest('request_date')
            ->get();

        return view('leave_requests.student', ['items' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'request_date' => ['required', 'date'],
            'type' => ['required', 'in:izin,sakit'],
            'reason' => ['required', 'string', 'max:1000'],
            'evidence' => ['required', 'image', 'max:4096'],
        ]);

        $existingLeave = $user->leaveRequests()
            ->whereDate('request_date', $validated['request_date'])
            ->latest('id')
            ->first();

        if ($existingLeave && ! $this->canResubmitRejectedLeave($existingLeave)) {
            return back()->withErrors([
                'request_date' => 'Form pengajuan hanya bisa 1x per hari. Jika pengajuan ditolak, Anda bisa kirim ulang.',
            ]);
        }

        $existsAttendance = $user->attendances()
            ->whereDate('attendance_date', $validated['request_date'])
            ->exists();

        if ($existsAttendance) {
            return back()->withErrors(['request_date' => 'Tanggal ini sudah memiliki data absensi.']);
        }

        $path = $request->file('evidence')->store('leave-evidence', 'public');

        if ($existingLeave && $this->canResubmitRejectedLeave($existingLeave)) {
            $existingLeave->update([
                'pkl_location_id' => $user->pkl_location_id,
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'evidence_path' => $path,
                'status' => 'awaiting',
                'reject_reason_code' => null,
                'pembimbing_note' => null,
                'instruktur_note' => null,
                'kajur_note' => null,
                'validated_by_pembimbing' => null,
                'validated_by_instruktur' => null,
                'validated_by_kajur' => null,
                'validated_pembimbing_at' => null,
                'validated_instruktur_at' => null,
                'validated_kajur_at' => null,
                'validation_sla_due_at' => WorkflowState::defaultSlaDueAt(),
                'validation_escalated_at' => null,
                'validation_escalation_level' => null,
            ]);
        } else {
            LeaveRequest::create([
                'user_id' => $user->id,
                'request_date' => $validated['request_date'],
                'pkl_location_id' => $user->pkl_location_id,
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'evidence_path' => $path,
                'status' => 'awaiting',
                'validation_sla_due_at' => WorkflowState::defaultSlaDueAt(),
                'validation_escalated_at' => null,
                'validation_escalation_level' => null,
            ]);
        }

        return back()->with('success', 'Pengajuan berhasil dikirim dan menunggu validasi.');
    }

    private function canResubmitRejectedLeave(LeaveRequest $leave): bool
    {
        if ($leave->status === 'rejected' || in_array($leave->status, WorkflowState::LEAVE_REJECTS, true)) {
            return true;
        }

        return $leave->status === 'alpha'
            && filled($leave->reject_reason_code)
            && in_array((string) $leave->reject_reason_code, WorkflowState::LEAVE_REJECTS, true);
    }
}
