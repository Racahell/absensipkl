<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AuthSetupController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordResetEnforcementController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AttendanceValidationController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\AcademicMasterController;
use App\Http\Controllers\KajurStudentMonitoringController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveValidationController;
use App\Http\Controllers\MenuPermissionController;
use App\Http\Controllers\ReportChartController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentGuidanceNoteController;
use App\Http\Controllers\SystemBackupController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ValidationNoteHistoryController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyReportValidationController;
use App\Http\Controllers\DiscordSettingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\FeaturePageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PklLocationController;
use App\Http\Controllers\WeeklyValidationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/login/password', [LoginController::class, 'store'])->name('login.password');
    Route::get('/login/otp', [OtpLoginController::class, 'create'])->name('login.otp.form');
    Route::get('/login/otp/email', [OtpLoginController::class, 'create'])->defaults('channel', 'email')->name('login.otp.email');
    Route::post('/login/otp/send', [OtpLoginController::class, 'send'])->name('login.otp.send');
    Route::post('/login/otp/request', [OtpLoginController::class, 'send'])->name('login.otp.request');
    Route::post('/login/otp/verify', [OtpLoginController::class, 'verify'])->name('login.otp.verify');
    Route::get('/login/google', [GoogleAuthController::class, 'redirectForLogin'])->name('auth.google.redirect');

    Route::get('/register', fn () => redirect()->route('login')->with('error', 'Registrasi publik tidak tersedia. Akun hanya dibuat admin.'))->name('register');
    Route::post('/register', fn () => redirect()->route('login')->with('error', 'Registrasi publik tidak tersedia. Akun hanya dibuat admin.'))->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password/email', [ForgotPasswordController::class, 'sendEmailResetLink'])->name('password.email');
    Route::post('/forgot-password/whatsapp', [ForgotPasswordController::class, 'sendWhatsappCode'])->name('password.whatsapp.send');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
    Route::get('/reset-password-whatsapp', [ForgotPasswordController::class, 'whatsappForm'])->name('password.whatsapp.form');
    Route::post('/reset-password-whatsapp', [ForgotPasswordController::class, 'resetWithWhatsapp'])->name('password.whatsapp.update');
});

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/auth/reset-password-wajib', [PasswordResetEnforcementController::class, 'edit'])->name('password.reset.edit');
    Route::post('/auth/reset-password-wajib', [PasswordResetEnforcementController::class, 'update'])->name('password.reset.update');
    Route::post('/login/password/change-first', [PasswordResetEnforcementController::class, 'update'])->name('login.password.change-first');

    Route::get('/auth/setup', [AuthSetupController::class, 'show'])->name('auth.setup.show');
    Route::put('/auth/setup/contact', [AuthSetupController::class, 'updateContact'])->name('auth.setup.contact');
    Route::post('/auth/setup/email/send-otp', [AuthSetupController::class, 'sendEmailOtp'])->name('auth.setup.email.send');
    Route::post('/verify/email/send', [AuthSetupController::class, 'sendEmailOtp'])->name('verify.email.send');
    Route::post('/auth/setup/email/verify-otp', [AuthSetupController::class, 'verifyEmailOtp'])->name('auth.setup.email.verify');
    Route::post('/verify/email/confirm', [AuthSetupController::class, 'verifyEmailOtp'])->name('verify.email.confirm');
    Route::post('/auth/setup/phone/send-otp', [AuthSetupController::class, 'sendPhoneOtp'])->name('auth.setup.phone.send');
    Route::post('/verify/phone/send-otp', [AuthSetupController::class, 'sendPhoneOtp'])->name('verify.phone.send-otp');
    Route::post('/auth/setup/phone/verify-otp', [AuthSetupController::class, 'verifyPhoneOtp'])->name('auth.setup.phone.verify');
    Route::post('/verify/phone/confirm-otp', [AuthSetupController::class, 'verifyPhoneOtp'])->name('verify.phone.confirm-otp');
    Route::post('/auth/setup/otp/toggle', [AuthSetupController::class, 'toggleOtp'])->name('auth.setup.otp.toggle');
    Route::get('/auth/google/link', [GoogleAuthController::class, 'redirectForLink'])->name('auth.google.link');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectForLink'])->name('auth.google.redirect-link');
    Route::post('/auth/google/unlink', [GoogleAuthController::class, 'unlink'])->name('auth.google.unlink');

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('success', 'Email berhasil diverifikasi.');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link verifikasi email dikirim ulang.');
    })->middleware(['throttle:6,1'])->name('verification.send');

    Route::get('/email/verify-pending/{id}/{hash}', [ProfileController::class, 'verifyPendingEmail'])
        ->middleware(['signed'])
        ->name('verification.pending');
});

Route::middleware(['auth', 'force.password.reset', 'menu.permission'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/siswa/calendar-data', [DashboardController::class, 'studentCalendarData'])->name('dashboard.siswa.calendar-data');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/reset-password-email', [ProfileController::class, 'sendResetPasswordEmail'])->name('profile.password.email');

    Route::get('/dashboard/superadmin', [DashboardController::class, 'legacyRedirect'])->middleware('role:superadmin');
    Route::get('/dashboard/admin-sekolah', [DashboardController::class, 'legacyRedirect'])->middleware('role:admin_sekolah');
    Route::get('/dashboard/siswa', [DashboardController::class, 'legacyRedirect'])->middleware('role:siswa');
    Route::get('/dashboard/pembimbing', [DashboardController::class, 'legacyRedirect'])->middleware('role:pembimbing_pkl');
    Route::get('/dashboard/instruktur', [DashboardController::class, 'legacyRedirect'])->middleware('role:instruktur');
    Route::get('/dashboard/kajur', [DashboardController::class, 'legacyRedirect'])->middleware('role:kajur');
    Route::get('/dashboard/wali-kelas', [DashboardController::class, 'legacyRedirect'])->middleware('role:wali_kelas');
    Route::get('/dashboard/kesiswaan', [DashboardController::class, 'legacyRedirect'])->middleware('role:kesiswaan');
    Route::get('/dashboard/kepsek', [DashboardController::class, 'legacyRedirect'])->middleware('role:kepsek');
    Route::get('/dashboard/wakil-kepsek', [DashboardController::class, 'legacyRedirect'])->middleware('role:wakil_kepsek');

    Route::get('/absensi', [StudentAttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/check-in', [StudentAttendanceController::class, 'checkInPage'])->name('absensi.checkin.page');
    Route::get('/absensi/check-out', [StudentAttendanceController::class, 'checkOutPage'])->name('absensi.checkout.page');
    Route::post('/absensi/check-in', [StudentAttendanceController::class, 'checkIn'])->name('absensi.checkin');
    Route::post('/absensi/check-out', [StudentAttendanceController::class, 'checkOut'])->name('absensi.checkout');
    Route::get('/pengajuan', [LeaveRequestController::class, 'index'])->name('pengajuan.index');
    Route::post('/pengajuan', [LeaveRequestController::class, 'store'])->name('pengajuan.store');
    Route::get('/riwayat-catatan', [ValidationNoteHistoryController::class, 'index'])->name('catatan.history');
    Route::get('/catatan-bimbingan', [StudentGuidanceNoteController::class, 'studentIndex'])->name('guidance.student.index');
    Route::post('/catatan-bimbingan', [StudentGuidanceNoteController::class, 'studentStore'])->name('guidance.student.store');

    Route::get('/validasi', [AttendanceValidationController::class, 'index'])->name('validasi.index');
    Route::post('/validasi/{attendance}/approve', [AttendanceValidationController::class, 'approve'])->name('validasi.approve');
    Route::post('/validasi/{attendance}/reject', [AttendanceValidationController::class, 'reject'])->name('validasi.reject');
    Route::post('/validasi/{attendance}/note', [AttendanceValidationController::class, 'saveNote'])->name('validasi.note');
    Route::post('/validasi/absensi/{attendance}/approve', [AttendanceValidationController::class, 'approve'])->name('validasi.absensi.approve');
    Route::post('/validasi/absensi/{attendance}/reject', [AttendanceValidationController::class, 'reject'])->name('validasi.absensi.reject');
    Route::get('/validasi/catatan-bimbingan', [StudentGuidanceNoteController::class, 'mentorIndex'])->name('guidance.mentor.index');
    Route::post('/validasi/catatan-bimbingan/{note}', [StudentGuidanceNoteController::class, 'mentorValidate'])->name('guidance.mentor.validate');

    Route::get('/validasi-pengajuan', [LeaveValidationController::class, 'index'])->name('validasi.pengajuan.index');
    Route::post('/validasi-pengajuan/{leaveRequest}/approve', [LeaveValidationController::class, 'approve'])->name('validasi.pengajuan.approve');
    Route::post('/validasi-pengajuan/{leaveRequest}/reject', [LeaveValidationController::class, 'reject'])->name('validasi.pengajuan.reject');

    Route::get('/validasi-laporan', [DailyReportValidationController::class, 'index'])->name('validasi.laporan.index');
    Route::post('/validasi-laporan/{dailyReport}/approve', [DailyReportValidationController::class, 'approve'])->name('validasi.laporan.approve');
    Route::post('/validasi-laporan/{dailyReport}/revisi', [DailyReportValidationController::class, 'revise'])->name('validasi.laporan.revisi');
    Route::post('/review/laporan/{dailyReport}', [DailyReportValidationController::class, 'approve'])->name('review.laporan');
    Route::get('/chatbot/history', [ChatbotController::class, 'history'])->name('chatbot.history');
    Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

    Route::get('/summary-report', [WeeklyValidationController::class, 'index'])->name('reports.weekly');
    Route::get('/summary-report/rekap', [WeeklyValidationController::class, 'recap'])->name('reports.weekly.recap');
    Route::get('/summary-report/analisis', [WeeklyValidationController::class, 'analysis'])->name('reports.weekly.analysis');
    Route::get('/summary/report', [WeeklyValidationController::class, 'index'])->name('summary.report');
    Route::post('/summary-report/approve', [WeeklyValidationController::class, 'approve'])->name('reports.weekly.approve');
    Route::post('/summary-report/revisi', [WeeklyValidationController::class, 'revise'])->name('reports.weekly.revise');
    Route::post('/summary-report/note', [WeeklyValidationController::class, 'saveNote'])->name('reports.weekly.note');
    Route::post('/summary-report/note/delete', [WeeklyValidationController::class, 'deleteNote'])->name('reports.weekly.note.delete');
    Route::post('/validasi-mingguan/{weeklyValidation}/approve', [WeeklyValidationController::class, 'approveById'])->name('validasi.mingguan.approve');
    Route::get('/kajur/catatan-bimbingan', [StudentGuidanceNoteController::class, 'kajurIndex'])->name('guidance.kajur.index');
    Route::post('/kajur/catatan-bimbingan/{note}/note', [StudentGuidanceNoteController::class, 'kajurNote'])->name('guidance.kajur.note');
    Route::get('/wakil-kepsek/validasi-kehadiran', [StudentGuidanceNoteController::class, 'wakilIndex'])->name('guidance.wakil.index');
    Route::post('/wakil-kepsek/validasi-kehadiran/{note}', [StudentGuidanceNoteController::class, 'wakilValidate'])->name('guidance.wakil.validate');

    Route::get('/laporan/export/excel', [ReportExportController::class, 'reportExcel'])->name('reports.export.excel');
    Route::get('/laporan/export/pdf', [ReportExportController::class, 'reportPdf'])->name('reports.export.pdf');
    Route::get('/laporan/print', [ReportExportController::class, 'reportPrint'])->name('reports.print');

    Route::get('/fitur-shared/lupa-password', [FeaturePageController::class, 'show'])
        ->defaults('slug', 'lupa-password')
        ->name('fitur-shared.lupa-password');

    Route::get('/fitur-shared/audit-log', [AuditLogController::class, 'index'])->name('fitur-shared.audit-log');

    Route::get('/fitur-shared/laporan-grafik', [ReportChartController::class, 'index'])->name('fitur-shared.laporan-grafik');

    Route::get('/fitur/manajemen-pengguna', [UserManagementController::class, 'index'])->name('fitur.manajemen-pengguna');
    Route::post('/fitur/manajemen-pengguna', [UserManagementController::class, 'store'])->name('users.store');
    Route::post('/fitur/manajemen-pengguna/bulk-action', [UserManagementController::class, 'bulkAction'])->name('users.bulk-action');
    Route::put('/fitur/manajemen-pengguna/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/fitur/manajemen-pengguna/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('/fitur/manajemen-pengguna/{id}/restore', [UserManagementController::class, 'restore'])->name('users.restore');
    Route::delete('/fitur/manajemen-pengguna/{id}/force-delete', [UserManagementController::class, 'forceDelete'])
        ->name('users.force-delete');

    Route::get('/fitur/setting-web', [AppSettingController::class, 'index'])->name('fitur.setting-web');
    Route::put('/fitur/setting-web', [AppSettingController::class, 'update'])->name('settings.update');
    Route::post('/fitur/setting-web/reminder-now', [AppSettingController::class, 'sendGuidanceReminderNow'])->name('settings.reminder-now');
    Route::get('/fitur/lokasi-pkl', [PklLocationController::class, 'index'])->name('fitur.lokasi-pkl');
    Route::get('/fitur/lokasi-pkl/{location}', [PklLocationController::class, 'show'])->name('locations.show');
    Route::post('/fitur/lokasi-pkl', [PklLocationController::class, 'store'])->name('locations.store');
    Route::post('/fitur/lokasi-pkl/bulk-action', [PklLocationController::class, 'bulkAction'])->name('locations.bulk-action');
    Route::put('/fitur/lokasi-pkl/{location}', [PklLocationController::class, 'update'])->name('locations.update');
    Route::delete('/fitur/lokasi-pkl/{location}', [PklLocationController::class, 'destroy'])->name('locations.destroy');

    Route::get('/fitur/backup-restore', [SystemBackupController::class, 'index'])->name('fitur.backup-restore');
    Route::post('/fitur/backup-restore', [SystemBackupController::class, 'backup'])->name('backups.create');
    Route::post('/fitur/backup-restore/{backup}/restore', [SystemBackupController::class, 'restore'])->name('backups.restore');
    Route::post('/fitur/backup-restore/restore-upload', [SystemBackupController::class, 'restoreUpload'])->name('backups.restore-upload');
    Route::post('/fitur/backup-restore/wipe', [SystemBackupController::class, 'wipe'])->name('backups.wipe');
    Route::get('/fitur/backup-restore/{backup}/download', [SystemBackupController::class, 'download'])->name('backups.download');

    Route::get('/fitur/import-export', [ImportExportController::class, 'index'])->name('fitur.import-export');
    Route::get('/fitur/import-export/users/export', [ImportExportController::class, 'exportUsers'])->name('import-export.users.export');
    Route::get('/fitur/import-export/users/export-data', [ImportExportController::class, 'exportUsersData'])->name('import-export.users.export-data');
    Route::post('/fitur/import-export/users/import', [ImportExportController::class, 'importUsers'])->name('import-export.users.import');
    Route::post('/fitur/import-export/users/import/init', [ImportExportController::class, 'importUsersInit'])->name('import-export.users.import.init');
    Route::post('/fitur/import-export/users/import/process', [ImportExportController::class, 'importUsersProcess'])->name('import-export.users.import.process');

    Route::get('/fitur/master-akademik', [AcademicMasterController::class, 'index'])->name('fitur.master-akademik');
    Route::post('/fitur/master-akademik/jurusan', [AcademicMasterController::class, 'storeDepartment'])->name('masters.department.store');
    Route::put('/fitur/master-akademik/jurusan/{department}', [AcademicMasterController::class, 'updateDepartment'])->name('masters.department.update');
    Route::delete('/fitur/master-akademik/jurusan/{department}', [AcademicMasterController::class, 'destroyDepartment'])->name('masters.department.destroy');
    Route::post('/fitur/master-akademik/jurusan/{id}/restore', [AcademicMasterController::class, 'restoreDepartment'])->name('masters.department.restore');
    Route::delete('/fitur/master-akademik/jurusan/{id}/force-delete', [AcademicMasterController::class, 'forceDeleteDepartment'])->name('masters.department.force-delete');
    Route::post('/fitur/master-akademik/kelas', [AcademicMasterController::class, 'storeClass'])->name('masters.class.store');
    Route::put('/fitur/master-akademik/kelas/{class}', [AcademicMasterController::class, 'updateClass'])->name('masters.class.update');
    Route::delete('/fitur/master-akademik/kelas/{class}', [AcademicMasterController::class, 'destroyClass'])->name('masters.class.destroy');
    Route::post('/fitur/master-akademik/kelas/{id}/restore', [AcademicMasterController::class, 'restoreClass'])->name('masters.class.restore');
    Route::delete('/fitur/master-akademik/kelas/{id}/force-delete', [AcademicMasterController::class, 'forceDeleteClass'])->name('masters.class.force-delete');


    Route::get('/kajur/siswa', [KajurStudentMonitoringController::class, 'index'])->name('kajur.students.index');
    Route::get('/kajur/siswa/{student}', [KajurStudentMonitoringController::class, 'show'])->name('kajur.students.show');
    Route::post('/kajur/siswa/{student}/assign-mentor', [KajurStudentMonitoringController::class, 'assignMentor'])->name('kajur.students.assign-mentor');
    Route::post('/kajur/siswa/assign-mentor-department', [KajurStudentMonitoringController::class, 'assignMentorForDepartment'])->name('kajur.students.assign-mentor-department');

    Route::middleware('role:superadmin')->group(function () {
        Route::get('/fitur/hak-akses-menu', [MenuPermissionController::class, 'index'])->name('fitur.hak-akses-menu');
        Route::put('/fitur/hak-akses-menu', [MenuPermissionController::class, 'update'])->name('menu-permissions.update');
        Route::get('/fitur/audit-log', [AuditLogController::class, 'index'])->name('fitur.audit-log');
        Route::get('/fitur/laporan-grafik', [ReportChartController::class, 'index'])->name('fitur.laporan-grafik');
        Route::get('/fitur/notif-discord', [DiscordSettingController::class, 'index'])->name('fitur.notif-discord');
        Route::put('/fitur/notif-discord', [DiscordSettingController::class, 'update'])->name('discord.update');
        Route::post('/fitur/notif-discord/test', [DiscordSettingController::class, 'test'])->name('discord.test');
        Route::get('/fitur/lupa-password', [FeaturePageController::class, 'show'])->defaults('slug', 'lupa-password')->name('fitur.lupa-password');
    });

    Route::middleware('role:admin_sekolah')->group(function () {
        Route::redirect('/fitur-admin/manajemen-pengguna', '/fitur/manajemen-pengguna')->name('fitur-admin.manajemen-pengguna');
        Route::redirect('/fitur-admin/hak-akses-menu', '/fitur/hak-akses-menu')->name('fitur-admin.hak-akses-menu');
        Route::redirect('/fitur-admin/setting-web', '/fitur/setting-web')->name('fitur-admin.setting-web');
        Route::redirect('/fitur-admin/lokasi-pkl', '/fitur/lokasi-pkl')->name('fitur-admin.lokasi-pkl');
        Route::redirect('/fitur-admin/laporan-grafik', '/fitur-shared/laporan-grafik')->name('fitur-admin.laporan-grafik');
        Route::redirect('/fitur-admin/backup-restore', '/fitur/backup-restore')->name('fitur-admin.backup-restore');
        Route::redirect('/fitur-admin/import-export', '/fitur/import-export')->name('fitur-admin.import-export');
        Route::get('/fitur-admin/audit-log', [AuditLogController::class, 'index'])->name('fitur-admin.audit-log');
    });
});
