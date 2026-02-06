<?php

namespace App\Imports;

use App\Services\SecurityMasterDataService;
use App\Models\SecurityManagement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SecurityMasterImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $service;
    protected $categoryId;
    protected $createdBy;
    protected $authoriserId;
    protected $fieldMap;

    public function __construct(
        SecurityMasterDataService $service,
        int $categoryId,
        int $createdBy,
        int $authoriserId
    ) {
        $this->service = $service;
        $this->categoryId = $categoryId;
        $this->createdBy = $createdBy;
        $this->authoriserId = $authoriserId;
        
        $this->loadFieldMap();
    }

    /**
     * Load field definitions to map Excel headers to field IDs
     */
    protected function loadFieldMap()
    {
        $fields = SecurityManagement::where('category_id', $this->categoryId)
            ->where('status', 1)
            ->get();
            
        $this->fieldMap = $fields->mapWithKeys(function ($field) {
            // Normalize field name for matching (lowercase, underscores)
            $slug = \Illuminate\Support\Str::slug($field->field_name, '_');
            return [$slug => $field];
        });
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Start after header
        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            try {
                $this->processRow($row, $rowNumber);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Security Master Import Error Row {$rowNumber}: " . $e->getMessage());
            }
        }

        Log::info("Import completed: {$successCount} successful, {$errorCount} errors.");
    }

    protected function processRow($row, $rowNumber)
    {
        // 1. Extract core fields (security_name is mandatory)
        if (empty($row['security_name'])) {
            throw new \Exception("Security Name is required.");
        }

        $securityName = $row['security_name'];
        $status = isset($row['status']) ? (strtolower($row['status']) === 'active' ? 1 : 0) : 1;

        // 2. Map dynamic fields
        $fieldsData = [];
        
        foreach ($this->fieldMap as $headerSlug => $fieldDef) {
            // Check if this field exists in the row
            if (isset($row[$headerSlug])) {
                $value = $row[$headerSlug];
                
                // Basic type validation/casting could go here
                
                $fieldsData[] = [
                    'field_id' => $fieldDef->id,
                    'field_value' => (string) $value
                ];
            }
        }

        // 3. Construct payload for service
        $payload = [
            'category_id' => $this->categoryId,
            'security_name' => $securityName,
            'status' => $status,
            'created_by' => $this->createdBy,
            'authoriser_id' => $this->authoriserId,
            'fields' => $fieldsData
        ];

        // 4. Create request via service (handles validation)
        $this->service->createRequest($payload);
    }
}
