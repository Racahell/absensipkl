<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$k = $app->make(Illuminate\Contracts\Console\Kernel::class); $k->bootstrap();

$roles = [
 ['id'=>1,'key'=>'superadmin','name'=>'Superadmin'],
 ['id'=>2,'key'=>'admin_sekolah','name'=>'Admin Sekolah'],
 ['id'=>3,'key'=>'siswa','name'=>'Siswa'],
 ['id'=>4,'key'=>'pembimbing_pkl','name'=>'Pembimbing PKL'],
 ['id'=>5,'key'=>'instruktur','name'=>'Instruktur'],
 ['id'=>6,'key'=>'kajur','name'=>'Kajur'],
 ['id'=>7,'key'=>'wali_kelas','name'=>'Wali Kelas'],
 ['id'=>8,'key'=>'kesiswaan','name'=>'Kesiswaan'],
 ['id'=>9,'key'=>'kepsek','name'=>'Kepsek'],
 ['id'=>10,'key'=>'wakil_kepsek','name'=>'Wakil Kepsek'],
];
foreach ($roles as $r) {
  Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(['id'=>$r['id']], $r + ['created_at'=>now(),'updated_at'=>now()]);
}
$cnt = Illuminate\Support\Facades\DB::table('roles')->count();
echo "roles_restored={$cnt}\n";
