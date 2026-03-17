<?php

use Illuminate\Contracts\Console\Kernel;
use App\Models\MarketCategory;
use App\Models\ProductType;
use App\Imports\SecurityMasterImport;
use App\Services\SecurityMasterDataService;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// ... existing logic ...
try {
    $category = MarketCategory::first();
    if (!$category) {
        $category = MarketCategory::create([
            'name' => 'Test Cat', 
            'description' => 'Desc', 
            'status' => 1,
            'created_by' => 1
        ]);
    }
    
    // Ensure product exists
    $product = ProductType::where('market_category_id', $category->id)->first();
    if (!$product) {
        $product = ProductType::create([
             'market_category_id' => $category->id,
             'name' => 'Test Product',
             'code' => 'TP',
             'is_active' => 1,
             'created_by' => 1
        ]);
    }

    $service = app(SecurityMasterDataService::class);
    // Pass product_id in constructor (3rd arg)
    $import = new SecurityMasterImport($service, $category->id, $product->id, 1, 1);
    
    // Test processRow via reflection
    $reflection = new ReflectionClass($import);
    $method = $reflection->getMethod('processRow');
    $method->setAccessible(true);
    
    $row = [
        'security_name' => 'Imported Security Fallback',
        // 'product' => $product->name, // Intentionally removed to test fallback
        'status' => 1
    ];
    
    
    $method->invokeArgs($import, [$row, 2]);
    
    file_put_contents(__DIR__ . '/result.txt', "SUCCESS: Import processed row correctly.\n");
    
} catch (\Exception $e) {
    file_put_contents(__DIR__ . '/result.txt', "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
