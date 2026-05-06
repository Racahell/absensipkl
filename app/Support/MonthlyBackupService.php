<?php

namespace App\Support;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MonthlyBackupService
{
    /**
     * @return array{name:string,path:string}
     */
    public function run(?int $actorId = null): array
    {
        $tables = [
            'users',
            'attendances',
            'student_guidance_notes',
            'weekly_validations',
            'app_settings',
            'student_mentor_assignments',
        ];

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        $filename = 'backups/monthly_backup_'.now()->format('Ym').'_'.now()->format('Ymd_His').'.json';
        Storage::disk('local')->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        SystemBackup::query()->create([
            'name' => basename($filename),
            'file_path' => $filename,
            'type' => 'auto-monthly-json',
            'created_by' => $actorId,
        ]);

        return [
            'name' => basename($filename),
            'path' => $filename,
        ];
    }

    private function tableExists(string $table): bool
    {
        $result = DB::select('SHOW TABLES LIKE ?', [$table]);
        return $result !== [];
    }
}

