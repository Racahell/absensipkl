<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class); $k->bootstrap();
$role = Illuminate\Support\Facades\DB::table('roles')->where('id',1)->value('key');
var_dump($role);
