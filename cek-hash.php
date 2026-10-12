<?php
foreach (['admin'=>'admin12345','manager'=>'manager12345','pengawas'=>'pengawas12345','kasir'=>'kasir12345'] as $u => $p) {
    $x = App\Models\User::where('username', $u)->first();
    echo $u . ': ' . (Illuminate\Support\Facades\Hash::check($p, $x->password) ? 'COCOK' : 'TIDAK COCOK') . PHP_EOL;
}
