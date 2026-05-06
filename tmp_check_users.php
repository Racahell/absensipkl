<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\User::query()
    ->latest('id')
    ->limit(12)
    ->get(['id','name','role_id','department_name','is_deleted','deleted_at','created_at']);

echo $rows->toJson(JSON_PRETTY_PRINT);
