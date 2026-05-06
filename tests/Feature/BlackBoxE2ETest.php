<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Menu;
use App\Models\MenuPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlackBoxE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetMenuAccessCache();
    }

    public function test_password_login_with_offline_captcha_succeeds(): void
    {
        config()->set('captcha.mode', 'offline');

        $student = $this->makeUser('siswa', [
            'username' => 'siswa_login',
            'email' => 'siswa_login@example.test',
            'password' => Hash::make('secret12345'),
        ]);
        $this->grantMenuAccess('siswa', 'dashboard');

        $response = $this->withSession(['offline_captcha_answer' => 9])->post('/login', [
            'identifier' => 'siswa_login',
            'password' => 'secret12345',
            'offline_captcha_answer' => 9,
        ]);

        $response->assertRedirect('/dashboard/siswa');
        $this->assertAuthenticatedAs($student);
    }

    public function test_student_checkin_can_be_approved_by_pembimbing(): void
    {
        $student = $this->makeUser('siswa', [
            'username' => 'siswa_absen',
            'email' => 'siswa_absen@example.test',
        ]);
        $pembimbing = $this->makeUser('pembimbing_pkl', [
            'username' => 'mentor_absen',
            'email' => 'mentor_absen@example.test',
        ]);

        $student->update(['pembimbing_user_id' => $pembimbing->id]);

        $this->grantMenuAccess('siswa', 'absensi');
        $this->grantMenuAccess('pembimbing_pkl', 'validasi');

        $checkin = $this->actingAs($student)->post('/absensi/check-in', [
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'request_token' => 'checkin-token-1',
        ]);
        $checkin->assertSessionHasNoErrors();

        $attendance = Attendance::query()->where('user_id', $student->id)->latest('id')->first();
        $this->assertNotNull($attendance);
        $this->assertSame('pending', (string) $attendance->status);

        $approve = $this->actingAs($pembimbing)->post('/validasi/'.$attendance->id.'/approve', [
            'validation_stage' => 'checkin',
            'note' => 'Check-in valid.',
        ]);
        $approve->assertSessionHasNoErrors();

        $attendance->refresh();
        $this->assertSame('approved', (string) $attendance->checkin_validation_status);
    }

    public function test_student_leave_rejected_by_pembimbing_becomes_alpha_attendance(): void
    {
        Storage::fake('public');

        $student = $this->makeUser('siswa', [
            'username' => 'siswa_izin',
            'email' => 'siswa_izin@example.test',
        ]);
        $pembimbing = $this->makeUser('pembimbing_pkl', [
            'username' => 'mentor_izin',
            'email' => 'mentor_izin@example.test',
        ]);
        $student->update(['pembimbing_user_id' => $pembimbing->id]);

        $this->grantMenuAccess('siswa', 'pengajuan');
        $this->grantMenuAccess('pembimbing_pkl', 'validasi-pengajuan');

        $requestDate = now('Asia/Jakarta')->toDateString();

        $submit = $this->actingAs($student)->post('/pengajuan', [
            'request_date' => $requestDate,
            'type' => 'izin',
            'reason' => 'Ada urusan keluarga.',
            'evidence' => $this->fakePngUpload(),
        ]);
        $submit->assertSessionHasNoErrors();

        $leave = LeaveRequest::query()->where('user_id', $student->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertSame('awaiting', (string) $leave->status);

        Attendance::query()->create([
            'user_id' => $student->id,
            'pkl_location_id' => $student->pkl_location_id,
            'attendance_date' => $requestDate,
            'check_in_at' => now('Asia/Jakarta'),
            'check_in_latitude' => -6.2000000,
            'check_in_longitude' => 106.8166660,
            'check_in_ip' => '127.0.0.1',
            'check_in_device' => 'Test Device',
            'check_in_location_label' => 'Test Location',
            'check_in_location_address' => 'Test Address',
            'check_in_selfie_path' => 'no-photo',
            'check_in_request_token' => 'seed-attendance-token',
            'status' => 'pending',
            'validation_status' => 'pending',
            'checkin_validation_status' => 'pending',
            'checkout_validation_status' => 'not_submitted',
            'session_status' => 'open',
        ]);

        $reject = $this->actingAs($pembimbing)->post('/validasi-pengajuan/'.$leave->id.'/reject', [
            'note' => 'Bukti tidak sesuai.',
            'reject_reason_code' => 'reject_izin',
        ]);
        $reject->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertSame('rejected', (string) $leave->status);
        $this->assertSame('reject_izin', (string) $leave->reject_reason_code);

        $attendance = Attendance::query()
            ->where('user_id', $student->id)
            ->whereDate('attendance_date', $requestDate)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('alpha', (string) $attendance->status);
        $this->assertSame('reject_izin', (string) $attendance->reject_reason_code);
    }

    private function grantMenuAccess(string $role, string $menuKey): void
    {
        $menu = Menu::query()->firstOrCreate(
            ['key' => $menuKey],
            ['name' => ucfirst(str_replace(['-', '/'], ' ', $menuKey)), 'url' => '/'.$menuKey]
        );

        MenuPermission::query()->updateOrCreate(
            ['menu_id' => $menu->id, 'role' => $role],
            ['is_allowed' => true]
        );
    }

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'User '.strtoupper($role),
            'role' => $role,
            'nis' => (string) random_int(10000000, 99999999),
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'must_reset_password' => false,
            'must_change_password' => false,
            'is_deleted' => false,
            'department_name' => 'RPL',
            'class_name' => 'XII-RPL-1',
        ], $overrides));
    }

    private function fakePngUpload(): UploadedFile
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5WqMsAAAAASUVORK5CYII=';
        $path = tempnam(sys_get_temp_dir(), 'e2e_png_');
        file_put_contents($path, base64_decode($pngBase64));

        return new UploadedFile(
            $path,
            'evidence.png',
            'image/png',
            null,
            true
        );
    }

    private function resetMenuAccessCache(): void
    {
        $ref = new \ReflectionClass(\App\Support\MenuAccess::class);
        $prop = $ref->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }
}
