<?php

use Illuminate\Contracts\Console\Kernel;
use App\Models\SecurityManagement;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$ids = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,36];
$fields = SecurityManagement::whereIn('id', $ids)->get();

echo "ID,Field Name,Slug\n";
foreach ($fields as $field) {
    echo $field->id . ',' . $field->field_name . ',' . \Illuminate\Support\Str::slug($field->field_name, '_') . "\n";
}
