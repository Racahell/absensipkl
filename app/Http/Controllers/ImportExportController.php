<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\MenuAccess;
use App\Support\UsernameResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    private const IMPORT_JOB_DIR = 'import-jobs';
    private const IMPORT_BATCH_SIZE = 80;

    public function index(): View
    {
        $users = User::query();
        $roles = ['siswa', 'admin_sekolah', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'wakil_kepsek', 'kepsek'];
        $departments = User::query()->whereNotNull('department_name')->where('department_name', '!=', '')->distinct()->orderBy('department_name')->pluck('department_name')->values();
        $classes = User::query()->whereNotNull('class_name')->where('class_name', '!=', '')->distinct()->orderBy('class_name')->pluck('class_name')->values();
        $locations = User::query()->whereHas('pklLocation')->with('pklLocation:id,name')->get()->pluck('pklLocation.name')->filter()->unique()->values();

        return view('import-export.index', [
            'title' => 'Import & Export User',
            'usersCount' => [
                'siswa' => (clone $users)->where('role', 'siswa')->count(),
                'instruktur' => (clone $users)->where('role', 'instruktur')->count(),
                'kepsek' => (clone $users)->where('role', 'kepsek')->count(),
            ],
            'exportRoles' => $roles,
            'exportDepartments' => $departments,
            'exportClasses' => $classes,
            'exportLocations' => $locations,
        ]);
    }

    public function exportUsers(): StreamedResponse
    {
        $filename = 'template_import_user_'.now()->format('Ymd_His').'.csv';
        $headers = ['Content-Type' => 'text/csv'];

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            $this->writeCsvLineNoQuotes($out, ['nis_nuptk', 'nama', 'email', 'password', 'role', 'jurusan', 'kelas', 'tempat_pkl']);
            $this->writeCsvLineNoQuotes($out, ['10000003', 'Siswa PKL', 'siswa@example.com', '12345678', 'siswa', 'RPL', 'XII RPL 1', 'PT Maju']);
            $this->writeCsvLineNoQuotes($out, ['-', 'Admin Sekolah', 'admin@example.com', '12345678', 'admin_sekolah', '-', '-', '-']);
            $this->writeCsvLineNoQuotes($out, ['-', 'Instruktur PKL', 'instruktur@example.com', '12345678', 'instruktur', '-', '-', 'PT Maju']);

            fclose($out);
        }, $filename, $headers);
    }

    public function exportUsersData(Request $request): StreamedResponse|RedirectResponse
    {
        $this->ensureImportExportAccess($request);

        $filters = $request->validate([
            'role' => ['nullable', 'string', 'max:50'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:100'],
            'tempat_pkl' => ['nullable', 'string', 'max:255'],
        ]);

        $query = User::query()
            ->with('pklLocation:id,name')
            ->when(($filters['role'] ?? '') !== '', fn ($q) => $q->where('role', (string) $filters['role']))
            ->when(($filters['jurusan'] ?? '') !== '', fn ($q) => $q->where('department_name', (string) $filters['jurusan']))
            ->when(($filters['kelas'] ?? '') !== '', fn ($q) => $q->where('class_name', (string) $filters['kelas']))
            ->when(($filters['tempat_pkl'] ?? '') !== '', fn ($q) => $q->whereHas('pklLocation', fn ($loc) => $loc->where('name', (string) $filters['tempat_pkl'])));

        $rows = $query->orderBy('name')->get(['nis', 'nuptk', 'name', 'email', 'role_id', 'department_name', 'class_name', 'pkl_location_id']);
        $filename = 'export_user_'.now()->format('Ymd_His').'.csv';
        $headers = ['Content-Type' => 'text/csv'];

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            $this->writeCsvLineNoQuotes($out, ['nis_nuptk', 'nama', 'email', 'role', 'jurusan', 'kelas', 'tempat_pkl']);
            foreach ($rows as $row) {
                $this->writeCsvLineNoQuotes($out, [
                    (string) ($row->nis ?: $row->nuptk ?: '-'),
                    (string) ($row->name ?? '-'),
                    (string) ($row->email ?? '-'),
                    (string) ($row->role ?? '-'),
                    (string) ($row->department_name ?? '-'),
                    (string) ($row->class_name ?? '-'),
                    (string) ($row->pklLocation?->name ?? '-'),
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }

    /**
     * @param resource $out
     * @param array<int, scalar|null> $fields
     */
    private function writeCsvLineNoQuotes($out, array $fields): void
    {
        $clean = array_map(function ($value): string {
            $text = trim((string) ($value ?? ''));
            $text = str_replace(["\r", "\n", ';'], [' ', ' ', ','], $text);
            return $text;
        }, $fields);

        fwrite($out, implode(';', $clean)."\n");
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $this->ensureImportExportAccess($request);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $request->validate([
            'users_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('users_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'File CSV users gagal dibaca.');
        }

        $delimiter = $this->detectDelimiter($file->getRealPath());
        $processed = 0;
        $summary = $this->emptyRoleSummary();
        [$preparedRows, $skipped, $identifiers, $emails, $headerError] = $this->readStrictTemplateRowsFromHandle($handle, $delimiter);
        fclose($handle);

        if ($headerError !== null) {
            return back()->with('error', $headerError);
        }

        if ($preparedRows === []) {
            return back()->with(
                'success',
                "Import selesai: {$processed} baris diproses, {$skipped} baris dilewati. ".
                $this->formatSummary($summary)
            );
        }

        [$identityMap, $emailMap] = $this->loadExistingUserMaps($identifiers, $emails);

        foreach ($preparedRows as $row) {
            $role = $row['role'];
            $name = $row['name'];
            $identifier = $row['identifier'];
            $email = $row['email'];
            $phone = $row['phone'] ?? null;
            $className = $row['class_name'] ?? null;
            $departmentName = $row['department_name'] ?? null;
            $passwordRaw = (string) ($row['password'] ?? '');

            $existingByIdentity = $identityMap[$identifier] ?? null;
            $existingByEmail = $emailMap[$email] ?? null;

            if ($existingByIdentity && $existingByEmail && $existingByIdentity->id !== $existingByEmail->id) {
                $skipped++;
                continue;
            }

            $user = $existingByIdentity ?? $existingByEmail ?? new User();

            if ($user->exists && $user->role !== $role) {
                $managedRoles = $this->importableRoles();
                if (! in_array((string) $user->role, $managedRoles, true)) {
                    $skipped++;
                    continue;
                }
            }

            $user->name = $name;
            $user->username = app(UsernameResolver::class)->generateUnique(
                (string) ($user->username ?? ''),
                $role === 'siswa' ? $identifier : null,
                $role !== 'siswa' ? $identifier : null,
                $email,
                $user->exists ? (int) $user->id : null
            );
            $user->email = $email;
            $user->phone = $phone;
            $user->role = $role;
            $user->nis = $role === 'siswa' ? $identifier : null;
            $user->nuptk = $role !== 'siswa' && ! str_contains($identifier, '@') ? $identifier : null;
            $user->class_name = $className;
            $user->department_name = $departmentName ?? $this->extractDepartment((string) ($className ?? ''));
            if (! $this->isAcademicMasterValid($user->department_name, $user->class_name)) {
                $skipped++;
                continue;
            }
            $user->updated_by = $request->user()?->id;
            $user->is_deleted = false;
            $user->must_reset_password = true;
            $user->must_change_password = true;

            if (! $user->exists) {
                $user->created_by = $request->user()?->id;
                $user->email_verified_at = now();
                $user->phone_verified_at = $phone ? now() : null;
                $user->is_google_linked = false;
                $user->is_otp_active = false;
                $user->password = Hash::make($passwordRaw !== '' ? $passwordRaw : $identifier);
            } elseif ($passwordRaw !== '') {
                $user->password = Hash::make($passwordRaw);
            }

            try {
                if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }
                $user->save();
            } catch (\Throwable) {
                $skipped++;
                continue;
            }

            // Keep in-memory maps updated, so duplicate identity/email in same file won't create duplicates.
            $identityMap[$identifier] = $user;
            $emailMap[$email] = $user;
            if (! empty($user->nis)) {
                $identityMap[$user->nis] = $user;
            }
            if (! empty($user->nuptk)) {
                $identityMap[$user->nuptk] = $user;
            }

            $processed++;
            $summary[$role]++;
        }

        return back()->with(
            'success',
            "Import selesai: {$processed} baris diproses, {$skipped} baris dilewati. ".
            $this->formatSummary($summary)
        );
    }

    public function importUsersInit(Request $request): JsonResponse
    {
        $this->ensureImportExportAccess($request);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $request->validate([
            'users_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('users_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return response()->json([
                'ok' => false,
                'message' => 'File CSV users gagal dibaca.',
            ], 422);
        }

        $delimiter = $this->detectDelimiter($file->getRealPath());
        [$rows, $parseSkipped, $identifiers, $emails, $headerError] = $this->readStrictTemplateRowsFromHandle($handle, $delimiter);
        fclose($handle);

        if ($headerError !== null) {
            return response()->json([
                'ok' => false,
                'message' => $headerError,
            ], 422);
        }

        [$identityMapUsers, $emailMapUsers] = $this->loadExistingUserMaps($identifiers, $emails);
        $identityMapIds = [];
        foreach ($identityMapUsers as $key => $user) {
            $identityMapIds[$key] = (int) $user->id;
        }
        $emailMapIds = [];
        foreach ($emailMapUsers as $key => $user) {
            $emailMapIds[$key] = (int) $user->id;
        }

        $token = 'imp_'.Str::uuid()->toString();
        $state = [
            'owner_id' => (int) ($request->user()?->id ?? 0),
            'cursor' => 0,
            'processed' => 0,
            'runtime_skipped' => 0,
            'parse_skipped' => $parseSkipped,
            'summary' => $this->emptyRoleSummary(),
            'rows' => $rows,
            'total_rows' => count($rows) + $parseSkipped,
            'identity_map_ids' => $identityMapIds,
            'email_map_ids' => $emailMapIds,
        ];

        $this->writeImportJob($token, $state);

        return response()->json([
            'ok' => true,
            'token' => $token,
            'progress' => $this->buildProgressPayload($state),
        ]);
    }

    public function importUsersProcess(Request $request): JsonResponse
    {
        $this->ensureImportExportAccess($request);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = (string) $data['token'];
        $state = $this->readImportJob($token);
        if (! is_array($state)) {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi import tidak ditemukan atau sudah berakhir.',
            ], 404);
        }

        if ((int) ($state['owner_id'] ?? 0) !== (int) ($request->user()?->id ?? 0)) {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi import tidak valid untuk user ini.',
            ], 403);
        }

        $rows = $state['rows'] ?? [];
        $totalPreparedRows = count($rows);
        $cursor = (int) ($state['cursor'] ?? 0);
        if ($cursor >= $totalPreparedRows) {
            $progress = $this->buildProgressPayload($state);
            $this->deleteImportJob($token);
            return response()->json([
                'ok' => true,
                'completed' => true,
                'progress' => $progress,
                'message' => $this->buildImportSummaryMessage($progress),
            ]);
        }

        $slice = array_slice($rows, $cursor, self::IMPORT_BATCH_SIZE);
        $stateIdentityMap = is_array($state['identity_map_ids'] ?? null) ? $state['identity_map_ids'] : [];
        $stateEmailMap = is_array($state['email_map_ids'] ?? null) ? $state['email_map_ids'] : [];

        $neededIds = [];
        foreach ($slice as $row) {
            $identifier = (string) ($row['identifier'] ?? '');
            $email = strtolower((string) ($row['email'] ?? ''));
            if (isset($stateIdentityMap[$identifier])) {
                $neededIds[] = (int) $stateIdentityMap[$identifier];
            }
            if (isset($stateEmailMap[$email])) {
                $neededIds[] = (int) $stateEmailMap[$email];
            }
        }
        $neededIds = array_values(array_unique(array_filter($neededIds, fn ($id) => $id > 0)));

        $usersById = User::withTrashed()
            ->whereIn('id', $neededIds)
            ->get()
            ->keyBy('id');

        foreach ($slice as $row) {
            $role = (string) ($row['role'] ?? '');
            $name = trim((string) ($row['name'] ?? ''));
            $identifier = trim((string) ($row['identifier'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $phone = $row['phone'] ?? null;
            $className = $row['class_name'] ?? null;
            $departmentName = $row['department_name'] ?? null;
            $passwordRaw = (string) ($row['password'] ?? '');

            if (! in_array($role, $this->importableRoles(), true) || $name === '' || $email === '') {
                $state['runtime_skipped'] = (int) ($state['runtime_skipped'] ?? 0) + 1;
                $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                continue;
            }

            $existingIdentityId = isset($stateIdentityMap[$identifier]) ? (int) $stateIdentityMap[$identifier] : null;
            $existingEmailId = isset($stateEmailMap[$email]) ? (int) $stateEmailMap[$email] : null;

            if ($existingIdentityId && $existingEmailId && $existingIdentityId !== $existingEmailId) {
                $state['runtime_skipped'] = (int) ($state['runtime_skipped'] ?? 0) + 1;
                $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                continue;
            }

            $resolvedId = $existingIdentityId ?: $existingEmailId;
            $user = $resolvedId ? ($usersById->get($resolvedId) ?? null) : null;
            if (! $user) {
                $user = new User();
            }

            if ($user->exists && $user->role !== $role) {
                $managedRoles = $this->importableRoles();
                if (! in_array((string) $user->role, $managedRoles, true)) {
                    $state['runtime_skipped'] = (int) ($state['runtime_skipped'] ?? 0) + 1;
                    $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                    continue;
                }
            }

            $user->name = $name;
            $user->username = app(UsernameResolver::class)->generateUnique(
                (string) ($user->username ?? ''),
                $role === 'siswa' ? $identifier : null,
                $role !== 'siswa' ? $identifier : null,
                $email,
                $user->exists ? (int) $user->id : null
            );
            $user->email = $email;
            $user->phone = $phone;
            $user->role = $role;
            $user->nis = $role === 'siswa' ? $identifier : null;
            $user->nuptk = $role !== 'siswa' && ! str_contains($identifier, '@') ? $identifier : null;
            $user->class_name = $className;
            $user->department_name = $departmentName ?? $this->extractDepartment((string) ($className ?? ''));
            if (! $this->isAcademicMasterValid($user->department_name, $user->class_name)) {
                $state['runtime_skipped'] = (int) ($state['runtime_skipped'] ?? 0) + 1;
                $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                continue;
            }
            $user->updated_by = $request->user()?->id;
            $user->is_deleted = false;
            $user->must_reset_password = true;
            $user->must_change_password = true;

            if (! $user->exists) {
                $user->created_by = $request->user()?->id;
                $user->email_verified_at = now();
                $user->phone_verified_at = $phone ? now() : null;
                $user->is_google_linked = false;
                $user->is_otp_active = false;
                $user->password = Hash::make($passwordRaw !== '' ? $passwordRaw : $identifier);
            } elseif ($passwordRaw !== '') {
                $user->password = Hash::make($passwordRaw);
            }

            try {
                if ($user->exists && method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }
                $user->save();
                $usersById->put((int) $user->id, $user);
            } catch (\Throwable) {
                $state['runtime_skipped'] = (int) ($state['runtime_skipped'] ?? 0) + 1;
                $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                continue;
            }

            $stateIdentityMap[$identifier] = (int) $user->id;
            if (! empty($user->nis)) {
                $stateIdentityMap[(string) $user->nis] = (int) $user->id;
            }
            if (! empty($user->nuptk)) {
                $stateIdentityMap[(string) $user->nuptk] = (int) $user->id;
            }
            $stateEmailMap[$email] = (int) $user->id;

            $state['processed'] = (int) ($state['processed'] ?? 0) + 1;
            $state['summary'][$role] = (int) (($state['summary'][$role] ?? 0) + 1);
            $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
        }

        $state['identity_map_ids'] = $stateIdentityMap;
        $state['email_map_ids'] = $stateEmailMap;

        $progress = $this->buildProgressPayload($state);
        $completed = $progress['done_rows'] >= $progress['total_rows'];

        if ($completed) {
            $this->deleteImportJob($token);
        } else {
            $this->writeImportJob($token, $state);
        }

        return response()->json([
            'ok' => true,
            'completed' => $completed,
            'progress' => $progress,
            'message' => $completed ? $this->buildImportSummaryMessage($progress) : null,
        ]);
    }

    private function mapRow(array|false $header, array $row): array
    {
        if (! is_array($header)) {
            return [];
        }

        $mapped = [];
        foreach ($header as $index => $column) {
            $key = $this->normalizeHeaderKey((string) $column);
            $mapped[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        // Alias normalization for flexible CSV headers.
        if (! isset($mapped['firstname']) && isset($mapped['name'])) {
            $mapped['firstname'] = $mapped['name'];
        }
        if (! isset($mapped['name']) && isset($mapped['firstname'])) {
            $mapped['name'] = $mapped['firstname'];
        }
        if (! isset($mapped['username']) && isset($mapped['nis'])) {
            $mapped['username'] = $mapped['nis'];
        }
        if (! isset($mapped['username']) && isset($mapped['nuptk'])) {
            $mapped['username'] = $mapped['nuptk'];
        }
        if (! isset($mapped['class_name']) && isset($mapped['class'])) {
            $mapped['class_name'] = $mapped['class'];
        }

        // Repair shifted row case:
        // header: username;password;firstname;lastname;role;email
        // row:    username;password;firstname;lastname;email
        if (
            isset($mapped['role'], $mapped['email'])
            && trim((string) $mapped['email']) === ''
            && preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', (string) $mapped['role'])
        ) {
            $mapped['email'] = trim((string) $mapped['role']);
            $mapped['role'] = '';
        }

        // If CSV row has more columns than header, use the trailing value as role/class hint.
        $extraValues = array_slice($row, count($header));
        foreach ($extraValues as $extraRaw) {
            $extra = trim((string) $extraRaw);
            if ($extra === '') {
                continue;
            }

            $normalizedExtraRole = $this->normalizeRole($extra);
            if (! isset($mapped['role']) || trim((string) $mapped['role']) === '') {
                if (in_array($normalizedExtraRole, ['siswa', 'instruktur', 'kepsek'], true)) {
                    $mapped['role'] = $extra;
                    continue;
                }
            }

            if (! isset($mapped['class_name']) || trim((string) $mapped['class_name']) === '') {
                $mapped['class_name'] = $extra;
                continue;
            }

            if (! isset($mapped['lastname']) || trim((string) $mapped['lastname']) === '') {
                $mapped['lastname'] = $extra;
            }
        }

        return $mapped;
    }

    private function detectDelimiter(string $path): string
    {
        $firstLine = '';
        $handle = fopen($path, 'r');
        if ($handle) {
            $firstLine = (string) fgets($handle);
            fclose($handle);
        }

        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function normalizeRole(string $role): string
    {
        $value = strtolower(trim($role));

        return match ($value) {
            '' => '',
            'murid', 'siswa', 'student' => 'siswa',
            'guru', 'teacher', 'staff', 'pengajar' => 'pembimbing_pkl',
            'pembimbing', 'pembimbing_pkl' => 'pembimbing_pkl',
            'instruktur' => 'instruktur',
            'kajur' => 'kajur',
            'wali_kelas', 'wali kelas' => 'wali_kelas',
            'kesiswaan' => 'kesiswaan',
            'admin_sekolah', 'admin sekolah' => 'admin_sekolah',
            'kepsek', 'kepala sekolah', 'kepala_sekolah', 'principal', 'headmaster', 'head of school', 'ks' => 'kepsek',
            'wakil_kepsek', 'wakil kepsek', 'wakasek', 'wakil kepala sekolah' => 'wakil_kepsek',
            default => $value,
        };
    }

    private function inferRole(array $data): string
    {
        $explicitRole = $this->normalizeRole((string) ($data['role'] ?? ''));
        if (in_array($explicitRole, $this->importableRoles(), true)) {
            return $explicitRole;
        }

        $primaryMarker = strtolower(trim((string) ($data['class_name'] ?? $data['class'] ?? $data['lastname'] ?? '')));
        $detectedPrimary = $this->detectRoleFromText($primaryMarker);
        if ($detectedPrimary !== null) {
            return $detectedPrimary;
        }

        // Fallback: scan all cell values for role keywords (handles messy/shifted columns).
        foreach ($data as $value) {
            $detected = $this->detectRoleFromText((string) $value);
            if ($detected !== null) {
                return $detected;
            }
        }

        return 'siswa';
    }

    private function resolveClassName(array $data, string $role): ?string
    {
        $raw = trim((string) ($data['class_name'] ?? $data['class'] ?? $data['lastname'] ?? ''));
        if ($raw === '') {
            return null;
        }

        if ($role !== 'siswa') {
            $marker = strtolower($raw);
            if (
                str_contains($marker, 'guru')
                || str_contains($marker, 'teacher')
                || str_contains($marker, 'instruktur')
                || str_contains($marker, 'staff')
                || str_contains($marker, 'kepsek')
                || str_contains($marker, 'kepala sekolah')
                || str_contains($marker, 'principal')
                || str_contains($marker, 'headmaster')
                || str_contains($marker, 'head of school')
                || $marker === 'ks'
            ) {
                return null;
            }
        }

        return $raw;
    }

    private function normalizeEmail(string $email, string $identifier): string
    {
        $value = strtolower(trim($email));
        if ($value !== '') {
            return $value;
        }

        return strtolower($identifier).'@gmail.com';
    }

    private function extractDepartment(string $className): ?string
    {
        $value = strtoupper(trim($className));
        if ($value === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $value);

        return $parts[0] ?? null;
    }

    /**
     * @return array{column_order: array<int, string>, class_filters: array<int, string>, role_filters: array<int, string>}
     */
    private function extractImportOptions(Request $request): array
    {
        $rawClassFilter = (string) $request->input('class_filter', '');

        return [
            'column_order' => $this->parseColumnOrder((string) $request->input('column_order', '')),
            'class_filters' => $this->parseClassFilters($rawClassFilter),
            'role_filters' => $this->parseRoleFilters($rawClassFilter),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: array<int, string>, 3: array<int, string>}
     */
    private function readPreparedRowsFromHandle(mixed $handle, string $delimiter, array $options): array
    {
        $firstRow = fgetcsv($handle, 0, $delimiter);
        if (! is_array($firstRow)) {
            return [[], 0, [], []];
        }

        $customOrder = $options['column_order'] ?? [];
        $header = $customOrder !== [] ? $customOrder : $this->normalizeHeaderRow($firstRow);
        $knownHeaders = ['username', 'password', 'firstname', 'lastname', 'email', 'role', 'class_name', 'department_name', 'phone', 'nis', 'nuptk', 'class'];
        $headerHasKnown = count(array_intersect($knownHeaders, $header)) > 0;
        $useFirstRowAsData = $customOrder !== [] || ! $headerHasKnown;

        if (! $headerHasKnown && $customOrder === []) {
            $header = $this->defaultColumnOrder();
        }

        $rowsToRead = [];
        if ($useFirstRowAsData) {
            $rowsToRead[] = $firstRow;
        }
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowsToRead[] = $row;
        }

        $preparedRows = [];
        $skipped = 0;
        $identifiers = [];
        $emails = [];
        foreach ($rowsToRead as $row) {
            $mapped = $this->mapRow($header, $row);
            $prepared = $this->prepareImportRow($mapped, $options);
            if ($prepared === null) {
                $skipped++;
                continue;
            }

            $preparedRows[] = $prepared;
            $identifiers[] = (string) $prepared['identifier'];
            $emails[] = (string) $prepared['email'];
        }

        return [$preparedRows, $skipped, $identifiers, $emails];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: array<int, string>, 3: array<int, string>, 4: string|null}
     */
    private function readStrictTemplateRowsFromHandle(mixed $handle, string $delimiter): array
    {
        $firstRow = fgetcsv($handle, 0, $delimiter);
        if (! is_array($firstRow)) {
            return [[], 0, [], [], 'Template CSV kosong atau tidak valid.'];
        }

        $header = $this->normalizeHeaderRow($firstRow);
        $header = array_values(array_filter($header, fn ($value) => trim((string) $value) !== ''));

        $headerValid = count($header) >= 8
            && in_array($header[0] ?? '', ['nis/nuptk', 'nis_nuptk'], true)
            && in_array($header[1] ?? '', ['firstname', 'name', 'nama'], true)
            && in_array($header[2] ?? '', ['email'], true)
            && in_array($header[3] ?? '', ['password'], true)
            && in_array($header[4] ?? '', ['role'], true)
            && in_array($header[5] ?? '', ['department_name', 'jurusan'], true)
            && in_array($header[6] ?? '', ['class_name', 'kelas'], true)
            && in_array($header[7] ?? '', ['tempat_pkl', 'tempat pkl'], true);

        if (! $headerValid) {
            return [[], 0, [], [], 'Header CSV tidak sesuai template. Gunakan file template yang didownload dari sistem.'];
        }

        $preparedRows = [];
        $skipped = 0;
        $identifiers = [];
        $emails = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $cells = array_map(fn ($value) => trim((string) $value), $row);
            $isEmpty = collect($cells)->every(fn ($value) => $value === '');
            if ($isEmpty) {
                continue;
            }

            $mapped = [
                'nis_nuptk' => $cells[0] ?? '',
                'name' => $cells[1] ?? '',
                'email' => $cells[2] ?? '',
                'password' => $cells[3] ?? '',
                'role' => $cells[4] ?? '',
                'jurusan' => $cells[5] ?? '',
                'kelas' => $cells[6] ?? '',
                'tempat_pkl' => $cells[7] ?? '',
            ];

            $identifier = trim((string) ($mapped['nis_nuptk'] ?? ''));
            $name = trim((string) ($mapped['name'] ?? ''));
            $role = $this->normalizeRole((string) ($mapped['role'] ?? ''));
            $jurusan = $this->nullableToken((string) ($mapped['jurusan'] ?? ''));
            $kelas = $this->nullableToken((string) ($mapped['kelas'] ?? ''));
            $tempatPkl = $this->nullableToken((string) ($mapped['tempat_pkl'] ?? ''));
            if ($role === 'superadmin' || ! in_array($role, $this->importableRoles(), true)) {
                $skipped++;
                continue;
            }

            $email = $this->normalizeEmail((string) ($mapped['email'] ?? ''), $identifier);
            if (! $this->isImportRowValid($role, $identifier, $name, $email, (string) ($mapped['password'] ?? ''), $jurusan, $kelas, $tempatPkl)) {
                $skipped++;
                continue;
            }

            $preparedRows[] = [
                'role' => $role,
                'name' => $name,
                'identifier' => $identifier !== '' ? $identifier : $email,
                'email' => $email,
                'phone' => null,
                'class_name' => $kelas,
                'department_name' => $jurusan,
                'password' => trim((string) ($mapped['password'] ?? '')),
                'tempat_pkl' => $tempatPkl,
            ];

            if ($identifier !== '') {
                $identifiers[] = $identifier;
            }
            $emails[] = $email;
        }

        return [$preparedRows, $skipped, $identifiers, $emails, null];
    }

    /**
     * @param array<string, mixed> $mapped
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    private function prepareImportRow(array $mapped, array $options): ?array
    {
        $role = $this->inferRole($mapped);
        if (! in_array($role, ['siswa', 'instruktur', 'kepsek'], true)) {
            return null;
        }

        $className = $this->resolveClassName($mapped, $role);
        $roleFilters = is_array($options['role_filters'] ?? null) ? $options['role_filters'] : [];
        $classFilters = is_array($options['class_filters'] ?? null) ? $options['class_filters'] : [];

        // Union filter behavior:
        // - Non-student rows are filtered by role filter only.
        // - Student rows can pass by class filter (exact class match), or by role filter if 'siswa' is selected.
        if ($role !== 'siswa') {
            if ($roleFilters !== [] && ! in_array($role, $roleFilters, true)) {
                return null;
            }
        } else {
            $allowByRole = $roleFilters !== [] && in_array('siswa', $roleFilters, true);
            $allowByClass = $classFilters !== [] && $this->isClassAllowed($className, $classFilters);

            if ($roleFilters !== [] || $classFilters !== []) {
                if (! $allowByRole && ! $allowByClass) {
                    return null;
                }
            }
        }

        $name = trim((string) ($mapped['name'] ?? $mapped['firstname'] ?? ''));
        [$identifier, $passwordRaw] = $this->resolveIdentifierAndPassword($mapped);
        if ($name === '' || $identifier === '') {
            return null;
        }

        $email = $this->normalizeEmail((string) ($mapped['email'] ?? ''), $identifier);

        return [
            'role' => $role,
            'name' => $name,
            'identifier' => $identifier,
            'email' => $email,
            'phone' => $mapped['phone'] ?? null,
            'class_name' => $className,
            'department_name' => $mapped['department_name'] ?? null,
            'password' => $passwordRaw,
        ];
    }

    /**
     * @param array<string, mixed> $mapped
     * @return array{0: string, 1: string}
     */
    private function resolveIdentifierAndPassword(array $mapped): array
    {
        $nis = trim((string) ($mapped['nis'] ?? ''));
        $nuptk = trim((string) ($mapped['nuptk'] ?? ''));
        if ($nis !== '') {
            return [$nis, trim((string) ($mapped['password'] ?? ''))];
        }
        if ($nuptk !== '') {
            return [$nuptk, trim((string) ($mapped['password'] ?? ''))];
        }

        $username = trim((string) ($mapped['username'] ?? ''));
        $password = trim((string) ($mapped['password'] ?? ''));

        if ($username === '' && $password === '') {
            return ['', ''];
        }
        if ($username === '') {
            return [$password, $password];
        }
        if ($password === '') {
            return [$username, $username];
        }

        $usernameScore = $this->identifierScore($username);
        $passwordScore = $this->identifierScore($password);

        if ($passwordScore > $usernameScore) {
            return [$password, $username];
        }

        return [$username, $password];
    }

    private function identifierScore(string $value): int
    {
        $score = 0;
        if (preg_match('/^\d{5,}$/', $value)) {
            $score += 3;
        } elseif (preg_match('/^[A-Za-z0-9._-]{5,}$/', $value)) {
            $score += 2;
        }

        if (str_contains($value, '@')) {
            $score -= 2;
        }
        if (preg_match('/\s/', $value)) {
            $score -= 1;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeHeaderRow(array $header): array
    {
        return array_map(fn ($h) => $this->normalizeHeaderKey((string) $h), $header);
    }

    private function normalizeHeaderKey(string $key): string
    {
        $rawKey = trim($key);
        $rawKey = preg_replace('/^\x{FEFF}/u', '', $rawKey) ?? $rawKey;
        $rawKey = ltrim($rawKey, "\xEF\xBB\xBF");
        $normalized = strtolower($rawKey);
        $normalized = str_replace([' ', '-', '.'], '_', $normalized);

        return match ($normalized) {
            'user', 'user_name', 'userid', 'id_user', 'id_login', 'login', 'identifier' => 'username',
            'pass', 'passwd' => 'password',
            'nama', 'fullname', 'full_name', 'name' => 'firstname',
            'last_name', 'classroom' => 'lastname',
            'mail', 'e_mail' => 'email',
            'jabatan', 'position', 'status' => 'role',
            'class', 'class_name', 'nama_kelas', 'kelas' => 'class_name',
            'jurusan', 'major' => 'department_name',
            'no_hp', 'hp', 'no_wa', 'wa', 'telp', 'telephone', 'phone_number' => 'phone',
            default => $normalized,
        };
    }

    /**
     * @return array<int, string>
     */
    private function defaultColumnOrder(): array
    {
        return ['username', 'password', 'firstname', 'lastname', 'email', 'role'];
    }

    /**
     * @return array<int, string>
     */
    private function parseColumnOrder(string $raw): array
    {
        $value = trim($raw);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[;,|]/', $value) ?: [];
        $normalized = [];
        foreach ($parts as $part) {
            $key = $this->normalizeHeaderKey((string) $part);
            if (in_array($key, $this->knownColumnKeys(), true)) {
                $normalized[] = $key;
            }
        }

        $normalized = array_values(array_unique($normalized));

        // Ignore invalid custom header (e.g. user accidentally types "guru" here).
        if (count($normalized) < 3) {
            return [];
        }

        $hasIdentifier = in_array('username', $normalized, true)
            || in_array('nis', $normalized, true)
            || in_array('nuptk', $normalized, true);
        $hasName = in_array('firstname', $normalized, true) || in_array('name', $normalized, true);
        if (! $hasIdentifier || ! $hasName) {
            return [];
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function knownColumnKeys(): array
    {
        return ['username', 'password', 'firstname', 'name', 'lastname', 'email', 'role', 'class_name', 'department_name', 'phone', 'nis', 'nuptk', 'class'];
    }

    /**
     * @return array<int, string>
     */
    private function parseClassFilters(string $raw): array
    {
        $value = trim($raw);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[;,|]+/', $value) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $token = strtoupper(trim((string) preg_replace('/\s+/', ' ', (string) $part)));
            if ($token !== '') {
                // Skip known role keywords; they are parsed in parseRoleFilters().
                if (in_array($this->normalizeRole($token), ['siswa', 'instruktur', 'kepsek'], true)) {
                    continue;
                }
                $clean[] = $token;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * @return array<int, string>
     */
    private function parseRoleFilters(string $raw): array
    {
        $value = trim($raw);
        if ($value === '') {
            return [];
        }

        $text = strtolower($value);
        $roles = [];
        if (preg_match('/\b(siswa|murid|student)\b/u', $text)) {
            $roles[] = 'siswa';
        }
        if (preg_match('/\b(guru|teacher|instruktur|staff|pengajar)\b/u', $text)) {
            $roles[] = 'instruktur';
        }
        if (preg_match('/\b(kepsek|kepala sekolah|principal|headmaster|head of school|ks)\b/u', $text)) {
            $roles[] = 'kepsek';
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param array<int, string> $filters
     */
    private function isClassAllowed(?string $className, array $filters): bool
    {
        if ($filters === []) {
            return true;
        }
        if ($className === null || trim($className) === '') {
            return false;
        }

        $normalizedClass = $this->normalizeClassText($className);
        if ($normalizedClass === '') {
            return false;
        }

        foreach ($filters as $filter) {
            $normalizedFilter = $this->normalizeClassText((string) $filter);
            if ($normalizedFilter === '') {
                continue;
            }

            // Exact match only (case-insensitive, whitespace-normalized).
            if ($normalizedClass === $normalizedFilter) {
                return true;
            }
        }

        return false;
    }

    private function normalizeClassText(string $value): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]+/', ' ', $value));
        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }

    private function detectRoleFromText(string $value): ?string
    {
        $text = strtolower(trim($value));
        if ($text === '') {
            return null;
        }

        if (
            str_contains($text, 'kepsek')
            || str_contains($text, 'kepala sekolah')
            || str_contains($text, 'principal')
            || str_contains($text, 'headmaster')
            || str_contains($text, 'head of school')
            || preg_match('/\bks\b/u', $text)
        ) {
            return 'kepsek';
        }

        if (
            str_contains($text, 'guru')
            || str_contains($text, 'teacher')
            || str_contains($text, 'instruktur')
            || str_contains($text, 'staff')
            || str_contains($text, 'pengajar')
        ) {
            return 'instruktur';
        }

        return null;
    }

    private function inferRoleFromTemplateField(string $classOrPosition): string
    {
        $normalizedRole = $this->normalizeRole($classOrPosition);
        if (in_array($normalizedRole, $this->importableRoles(), true)) {
            return $normalizedRole;
        }

        // Anything else in template field is treated as student class marker.
        return 'siswa';
    }

    private function importableRoles(): array
    {
        return ['siswa', 'admin_sekolah', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'wakil_kepsek', 'kepsek'];
    }

    private function nullableToken(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        return $trimmed;
    }

    private function isImportRowValid(string $role, string $identifier, string $name, string $email, string $password, ?string $jurusan, ?string $kelas, ?string $tempatPkl): bool
    {
        if ($name === '' || $email === '' || $password === '') {
            return false;
        }

        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        if (! $emailValid && trim($identifier) === '') {
            return false;
        }

        return match ($role) {
            'siswa' => trim($identifier) !== '' && $jurusan !== null && $kelas !== null && $tempatPkl !== null,
            'admin_sekolah', 'kesiswaan', 'kepsek', 'wakil_kepsek' => true,
            'pembimbing_pkl' => $jurusan !== null && ($emailValid || trim($identifier) !== ''),
            'instruktur' => $tempatPkl !== null && ($emailValid || trim($identifier) !== ''),
            'kajur' => $jurusan !== null,
            'wali_kelas' => $jurusan !== null && $kelas !== null,
            default => false,
        };
    }

    private function normalizeTemplateClassName(string $classOrPosition): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $classOrPosition));
        return strtoupper($value);
    }

    /**
     * @param array<int, string> $identifiers
     * @param array<int, string> $emails
     * @return array{0: array<string, User>, 1: array<string, User>}
     */
    private function loadExistingUserMaps(array $identifiers, array $emails): array
    {
        $identifiers = array_values(array_unique(array_filter(array_map('trim', $identifiers), fn ($v) => $v !== '')));
        $emails = array_values(array_unique(array_filter(array_map(
            fn ($v) => strtolower(trim((string) $v)),
            $emails
        ), fn ($v) => $v !== '')));

        $query = User::withTrashed()->newQuery();
        $hasCondition = false;

        if ($identifiers !== []) {
            $hasCondition = true;
            $query->where(function ($q) use ($identifiers): void {
                $q->whereIn('nis', $identifiers)->orWhereIn('nuptk', $identifiers);
            });
        }

        if ($emails !== []) {
            if ($hasCondition) {
                $query->orWhereIn('email', $emails);
            } else {
                $query->whereIn('email', $emails);
                $hasCondition = true;
            }
        }

        if (! $hasCondition) {
            return [[], []];
        }

        /** @var Collection<int, User> $existingUsers */
        $existingUsers = $query->get();

        $identityMap = [];
        $emailMap = [];
        foreach ($existingUsers as $user) {
            if (! empty($user->nis) && ! isset($identityMap[$user->nis])) {
                $identityMap[$user->nis] = $user;
            }
            if (! empty($user->nuptk) && ! isset($identityMap[$user->nuptk])) {
                $identityMap[$user->nuptk] = $user;
            }
            $emailKey = strtolower((string) ($user->email ?? ''));
            if ($emailKey !== '' && ! isset($emailMap[$emailKey])) {
                $emailMap[$emailKey] = $user;
            }
        }

        return [$identityMap, $emailMap];
    }

    private function isAcademicMasterValid(?string $departmentName, ?string $className): bool
    {
        $departmentName = trim((string) ($departmentName ?? ''));
        $className = trim((string) ($className ?? ''));

        $department = null;
        if ($departmentName !== '') {
            $department = Department::query()->where('name', $departmentName)->first();
            if (! $department) {
                return false;
            }
        }

        if ($className !== '') {
            $class = SchoolClass::query()->where('name', $className)->first();
            if (! $class) {
                return false;
            }

            if ($department && (int) ($class->department_id ?? 0) > 0) {
                return (int) $class->department_id === (int) $department->id;
            }
        }

        return true;
    }

    private function importJobPath(string $token): string
    {
        return self::IMPORT_JOB_DIR.'/'.$token.'.json';
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeImportJob(string $token, array $state): void
    {
        Storage::disk('local')->put($this->importJobPath($token), json_encode($state, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readImportJob(string $token): ?array
    {
        $path = $this->importJobPath($token);
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function deleteImportJob(string $token): void
    {
        $path = $this->importJobPath($token);
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function buildProgressPayload(array $state): array
    {
        $cursor = (int) ($state['cursor'] ?? 0);
        $parseSkipped = (int) ($state['parse_skipped'] ?? 0);
        $processed = (int) ($state['processed'] ?? 0);
        $runtimeSkipped = (int) ($state['runtime_skipped'] ?? 0);
        $totalRows = max(0, (int) ($state['total_rows'] ?? 0));
        $doneRows = min($totalRows, $parseSkipped + $cursor);
        $percent = $totalRows > 0 ? (int) floor(($doneRows / $totalRows) * 100) : 100;

        $summary = is_array($state['summary'] ?? null) ? $state['summary'] : $this->emptyRoleSummary();
        foreach ($this->importableRoles() as $role) {
            $summary[$role] = (int) ($summary[$role] ?? 0);
        }

        return [
            'percent' => $percent,
            'done_rows' => $doneRows,
            'total_rows' => $totalRows,
            'processed' => $processed,
            'skipped' => $parseSkipped + $runtimeSkipped,
            'summary' => $summary,
        ];
    }

    /**
     * @param array<string, mixed> $progress
     */
    private function buildImportSummaryMessage(array $progress): string
    {
        return 'Import selesai: '.$progress['processed'].' baris diproses,'."\n".
            $progress['skipped'].' baris dilewati.'."\n".
            $this->formatSummary(is_array($progress['summary'] ?? null) ? $progress['summary'] : []);
    }

    private function emptyRoleSummary(): array
    {
        $summary = [];
        foreach ($this->importableRoles() as $role) {
            $summary[$role] = 0;
        }

        return $summary;
    }

    private function formatSummary(array $summary): string
    {
        $labels = [
            'siswa' => 'Siswa',
            'admin_sekolah' => 'Admin Sekolah',
            'pembimbing_pkl' => 'Instruktur PKL',
            'instruktur' => 'Pembimbing',
            'kajur' => 'Kajur',
            'wali_kelas' => 'Wali Kelas',
            'kesiswaan' => 'Kesiswaan',
            'kepsek' => 'Kepsek',
            'wakil_kepsek' => 'Wakil Kepsek',
        ];

        $parts = [];
        foreach ($labels as $role => $label) {
            $parts[] = $label.': '.(int) ($summary[$role] ?? 0);
        }

        return implode(",\n", $parts).'.';
    }

    private function ensureImportExportAccess(Request $request): void
    {
        $role = (string) ($request->user()?->role ?? '');
        abort_unless(MenuAccess::canAccess($role, 'fitur/import-export'), 403, 'Akses ditolak.');
    }
}
