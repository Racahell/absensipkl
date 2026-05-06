<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\User::query()
    ->whereNull('role_id')
    ->orderByDesc('id')
    ->get(['id','name','email','department_name','created_at']);

echo $rows->toJson(JSON_PRETTY_PRINT);
