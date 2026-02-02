<?php

use App\Models\PendingProductType;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    if (!Schema::hasTable('pending_product_types')) {
        echo "Table 'pending_product_types' does not exist.\n";
        exit;
    }

    $count = PendingProductType::count();
    $all = PendingProductType::all();
    
    echo "PendingProductType Count: " . $count . "\n";
    if ($count > 0) {
        echo "IDs: " . $all->pluck('id')->implode(', ') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
