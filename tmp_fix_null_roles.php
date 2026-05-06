<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$updated = Illuminate\Support\Facades\DB::table('users')
    ->whereIn('id', [1417, 1418])
    ->whereNull('role_id')
    ->update(['role_id' => 4, 'updated_at' => now()]);

echo "updated={$updated}";
