<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class); $k->bootstrap();
$tables=['roles','menus','menu_permissions','users'];
foreach($tables as $t){
  $c=Illuminate\Support\Facades\DB::table($t)->count();
  echo $t.':'.$c."\n";
}
$u=Illuminate\Support\Facades\DB::table('users')->where('id',1)->first(['id','role_id','email']);
echo 'user1_role_id:'.($u->role_id ?? 'null')."\n";
