<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = Illuminate\Support\Facades\DB::table('roles')->select('id','key','name')->orderBy('id')->get();
echo $roles->toJson(JSON_PRETTY_PRINT);
