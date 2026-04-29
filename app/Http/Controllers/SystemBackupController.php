<?php

namespace App\Http\Controllers;

use App\Models\SystemBackup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemBackupController extends Controller
{
    public function index(): View
    {
        $tab = request()->string('tab', 'backup')->toString();
        if (! in_array($tab, ['backup', 'restore', 'delete'], true)) {
            $tab = 'backup';
        }

        return view('backups.index', [
            'title' => 'Backup & Restore Sistem',
            'tab' => $tab,
            'tables' => $this->listTables(),
            'backups' => SystemBackup::with(['creator', 'restorer'])->latest()->paginate(20),
        ]);
    }

    public function backup(Request $request): RedirectResponse
    {
        $allowedTables = $this->listTables();
        $data = $request->validate([
            'scope' => ['required', 'in:single,all'],
            'table_name' => ['nullable', 'string'],
        ]);
        $scope = $data['scope'];
        $tableName = $data['table_name'] ?? '';

        if ($scope === 'single' && ! in_array($tableName, $allowedTables, true)) {
            return back()->with('error', 'Tabel yang dipilih tidak valid.');
        }

        $targetTables = $scope === 'all' ? $allowedTables : [$tableName];
        $sqlDump = $this->buildSqlDump($targetTables);
        $suffix = $scope === 'all' ? 'all_tables' : $tableName;
        $filename = 'backups/backup_'.$suffix.'_'.now()->format('Ymd_His').'.sql';
        Storage::disk('local')->put($filename, $sqlDump);

        SystemBackup::create([
            'name' => basename($filename),
            'file_path' => $filename,
            'type' => $scope === 'all' ? 'sql-all' : 'sql-'.$tableName,
            'created_by' => $request->user()?->id,
        ]);

        $targetLabel = $scope === 'all' ? 'seluruh tabel' : 'tabel '.$tableName;
        return back()->with('success', 'Backup '.$targetLabel.' berhasil dibuat.');
    }

    public function restore(Request $request, SystemBackup $backup): RedirectResponse
    {
        if (! Storage::disk('local')->exists($backup->file_path)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        $content = Storage::disk('local')->get($backup->file_path);
        $restored = $this->restoreFromContent($content, $backup->file_path);

        if (! $restored) {
            return back()->with('error', 'File backup tidak valid atau gagal direstore.');
        }

        $backup->update([
            'restored_at' => now(),
            'restored_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Restore database berhasil dijalankan.');
    }

    public function restoreUpload(Request $request): RedirectResponse
    {
        $allowedTables = $this->listTables();
        $data = $request->validate([
            'scope' => ['required', 'in:single,all'],
            'table_name' => ['nullable', 'string'],
            'sql_file' => ['required', 'file', 'mimes:sql,txt', 'max:20480'],
        ]);
        $scope = $data['scope'];
        $tableName = $data['table_name'] ?? '';

        if ($scope === 'single' && ! in_array($tableName, $allowedTables, true)) {
            return back()->with('error', 'Tabel yang dipilih tidak valid.');
        }

        $suffix = $scope === 'all' ? 'all_tables' : $tableName;
        $filename = 'backups/uploaded_restore_'.$suffix.'_'.now()->format('Ymd_His').'.sql';
        Storage::disk('local')->put($filename, file_get_contents($data['sql_file']->getRealPath()));
        $content = Storage::disk('local')->get($filename);

        if (! $this->restoreFromContent($content, $filename)) {
            return back()->with('error', 'Restore gagal. Pastikan file SQL valid.');
        }

        SystemBackup::create([
            'name' => basename($filename),
            'file_path' => $filename,
            'type' => $scope === 'all' ? 'sql-upload-all' : 'sql-upload-'.$tableName,
            'created_by' => $request->user()?->id,
            'restored_at' => now(),
            'restored_by' => $request->user()?->id,
        ]);

        $targetLabel = $scope === 'all' ? 'seluruh tabel' : 'tabel '.$tableName;
        return back()->with('success', 'Restore '.$targetLabel.' dari file SQL berhasil.');
    }

    public function download(SystemBackup $backup): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($backup->file_path), 404);
        return Storage::disk('local')->download($backup->file_path, $backup->name);
    }

    public function wipe(Request $request): RedirectResponse
    {
        $allowedTables = $this->listTables();
        $data = $request->validate([
            'scope' => ['required', 'in:single,all'],
            'table_name' => ['nullable', 'string'],
            'confirm_text' => ['required', 'string'],
        ]);
        $scope = $data['scope'];
        $tableName = $data['table_name'] ?? '';
        $confirmText = trim($data['confirm_text']);

        if ($scope === 'single' && ! in_array($tableName, $allowedTables, true)) {
            return back()->with('error', 'Tabel yang dipilih tidak valid.');
        }

        $singleConfirmations = ['HAPUS DATA TABEL', 'DELETE TABLE DATA'];
        $allConfirmations = ['HAPUS SEMUA DATA', 'DELETE ALL DATA'];

        if ($scope === 'single' && ! in_array($confirmText, $singleConfirmations, true)) {
            return back()->with('error', 'Konfirmasi tidak cocok. Ketik HAPUS DATA TABEL.');
        }

        if ($scope === 'all' && ! in_array($confirmText, $allConfirmations, true)) {
            return back()->with('error', 'Konfirmasi tidak cocok. Ketik HAPUS SEMUA DATA.');
        }

        $currentUserId = $request->user()?->id;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        if ($scope === 'all') {
            foreach ($allowedTables as $table) {
                if ($table === 'users') {
                    $this->truncateUsersPreservingCurrentUser($currentUserId);
                    continue;
                }

                $columns = $this->tableColumns($table);
                if ($columns !== []) {
                    DB::table($table)->truncate();
                }
            }
        } else {
            if ($tableName === 'users') {
                $this->truncateUsersPreservingCurrentUser($currentUserId);
            } else {
                $columns = $this->tableColumns($tableName);
                if ($columns !== []) {
                    DB::table($tableName)->truncate();
                }
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $targetLabel = $scope === 'all' ? 'seluruh tabel' : 'tabel '.$tableName;
        return redirect()->route('fitur.backup-restore', ['tab' => 'delete'])
            ->with('success', 'Isi '.$targetLabel.' berhasil dihapus.');
    }

    private function restoreFromContent(string $content, string $source): bool
    {
        try {
            if (str_ends_with($source, '.json')) {
                $json = json_decode($content, true);
                $tables = $json['tables'] ?? [];
                $this->restoreFromJson($tables);
                return true;
            }

            $statements = $this->splitSqlStatements($content);
            if ($statements === []) {
                return false;
            }

            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('Restore backup gagal', [
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    private function restoreFromJson(array $tables): void
    {
        DB::transaction(function () use ($tables): void {
            foreach ($tables as $table => $rows) {
                if (! is_array($rows) || $rows === []) {
                    continue;
                }

                $columns = array_keys($rows[0]);
                $updateColumns = array_values(array_filter($columns, fn ($column) => $column !== 'id'));

                DB::table($table)->upsert($rows, ['id'], $updateColumns);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $lines = explode("\n", $sql);
        $filteredLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (preg_match('/^DELIMITER\s+/i', $trimmed) === 1) {
                continue;
            }
            $filteredLines[] = $line;
        }

        $content = implode("\n", $filteredLines);
        $length = strlen($content);
        $statements = [];
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inBlockComment = false;
        $escape = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];
            $next = $i + 1 < $length ? $content[$i + 1] : '';

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (! $inSingle && ! $inDouble && ! $inBacktick && $char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }

            if ($char === '\\' && ($inSingle || $inDouble)) {
                $buffer .= $char;
                $escape = ! $escape;
                continue;
            }

            if ($char === "'" && ! $inDouble && ! $inBacktick && ! $escape) {
                $inSingle = ! $inSingle;
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && ! $inSingle && ! $inBacktick && ! $escape) {
                $inDouble = ! $inDouble;
                $buffer .= $char;
                continue;
            }

            if ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
                $buffer .= $char;
                continue;
            }

            if ($char === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                $escape = false;
                continue;
            }

            $buffer .= $char;
            $escape = false;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function listTables(): array
    {
        $tables = [];
        $rows = DB::select('SHOW TABLES');

        foreach ($rows as $row) {
            $tableName = array_values((array) $row)[0] ?? null;
            if ($tableName !== null) {
                $tables[] = $tableName;
            }
        }

        $excluded = ['migrations', 'failed_jobs', 'jobs', 'job_batches'];
        $tables = array_values(array_filter($tables, fn (string $table) => ! in_array($table, $excluded, true)));

        sort($tables);
        return $tables;
    }

    private function buildSqlDump(array $tables): string
    {
        $pdo = DB::connection()->getPdo();
        $sql = [];

        $sql[] = '-- PKL Monitor SQL Backup';
        $sql[] = '-- Generated at '.now()->toDateTimeString();
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($tables as $table) {
            try {
                $createRow = (array) DB::selectOne('SHOW CREATE TABLE '.$this->quoteIdentifier($table));
            } catch (QueryException) {
                $createRow = [];
            }

            $createStatement = $createRow['Create Table'] ?? array_values($createRow)[1] ?? null;
            if ($createStatement) {
                $sql[] = '';
                $sql[] = '-- Table: '.$table;
                $sql[] = 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table).';';
                $sql[] = $createStatement.';';

                $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
                if ($rows !== []) {
                    $columns = array_keys($rows[0]);
                    $columnSql = implode(', ', array_map(fn ($column) => $this->quoteIdentifier($column), $columns));

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($columns as $column) {
                            $values[] = $this->toSqlValue($pdo, $row[$column] ?? null);
                        }

                        $sql[] = 'INSERT INTO '.$this->quoteIdentifier($table).' ('.$columnSql.') VALUES ('.implode(', ', $values).');';
                    }
                }
            }
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        return implode("\n", $sql)."\n";
    }

    private function toSqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function tableColumns(string $table): array
    {
        try {
            return DB::getSchemaBuilder()->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function truncateUsersPreservingCurrentUser(?int $currentUserId): void
    {
        $columns = $this->tableColumns('users');
        if ($columns === []) {
            return;
        }

        $currentUser = $currentUserId !== null
            ? DB::table('users')->where('id', $currentUserId)->first()
            : null;
        $currentUserData = $currentUser ? (array) $currentUser : null;

        DB::table('users')->truncate();

        if ($currentUserData === null || ! in_array('id', $columns, true)) {
            return;
        }

        $payload = array_intersect_key($currentUserData, array_flip($columns));

        if (in_array('created_at', $columns, true) && empty($payload['created_at'])) {
            $payload['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $payload['updated_at'] = now();
        }

        DB::table('users')->insert($payload);
    }
}
