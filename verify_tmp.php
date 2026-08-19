<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (App\Models\TabelTenor::orderBy('nominal_min')->get() as $t) {
    echo (string)$t->nominal_min.' - '.(string)$t->nominal_max.' : '.$t->tenor_maksimal_bulan."\n";
}
