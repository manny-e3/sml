<?php

namespace App\Services;

use App\Models\SecurityMasterData;
use App\Models\SecurityMasterFieldValue;
use App\Models\SecurityManagement;
use App\Models\MarketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SecurityMasterDataService
{
    /**
     * Create a new security master record with field values
     */
    public function createSecurity(array $data)
    {
        // Validate category exists
        $category = MarketCategory::find($data['category_id']);
        if (!$category) {
            throw new \Exception('Market category not found');
        }

        // Get all field definitions for this category
        $fieldDefinitions = SecurityManagement::where('category_id', $data['category_id'])
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        // Validate required fields
        $this->validateRequiredFields($fieldDefinitions, $data['fields'] ?? []);

        // Validate field types
        $this->validateFieldTypes($fieldDefinitions, $data['fields'] ?? []);

        return DB::transaction(function () use ($data, $fieldDefinitions) {
            // Create security master record
            $security = SecurityMasterData::create([
                'category_id' => $data['category_id'],
                'security_name' => $data['security_name'],
                'status' => $data['status'] ?? 1,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Create field values
            foreach ($data['fields'] as $fieldData) {
                SecurityMasterFieldValue::create([
                    'security_master_id' => $security->id,
                    'field_id' => $fieldData['field_id'],
                    'field_value' => $fieldData['field_value'],
                ]);
            }

            return $security->load(['category', 'fieldValues.field']);
        });
    }

    /**
     * Update security master record
     */
    public function updateSecurity(SecurityMasterData $security, array $data)
    {
        // Get field definitions
        $fieldDefinitions = SecurityManagement::where('category_id', $security->category_id)
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        // Validate if fields are provided
        if (isset($data['fields'])) {
            $this->validateRequiredFields($fieldDefinitions, $data['fields']);
            $this->validateFieldTypes($fieldDefinitions, $data['fields']);
        }

        return DB::transaction(function () use ($security, $data) {
            // Update security master record
            $security->update([
                'security_name' => $data['security_name'] ?? $security->security_name,
                'status' => $data['status'] ?? $security->status,
                'updated_by' => $data['updated_by'] ?? null,
            ]);

            // Update field values if provided
            if (isset($data['fields'])) {
                foreach ($data['fields'] as $fieldData) {
                    SecurityMasterFieldValue::updateOrCreate(
                        [
                            'security_master_id' => $security->id,
                            'field_id' => $fieldData['field_id'],
                        ],
                        [
                            'field_value' => $fieldData['field_value'],
                        ]
                    );
                }
            }

            return $security->fresh()->load(['category', 'fieldValues.field']);
        });
    }

    /**
     * Get all securities with pagination
     */
    public function getAllSecurities($perPage = 15, $categoryId = null)
    {
        $query = SecurityMasterData::with(['category', 'fieldValues.field']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get single security with all field values
     */
    public function getSecurity($id)
    {
        return SecurityMasterData::with(['category', 'fieldValues.field'])->findOrFail($id);
    }

    /**
     * Delete security
     */
    public function deleteSecurity(SecurityMasterData $security)
    {
        return $security->delete();
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields($fieldDefinitions, $fields)
    {
        $providedFieldIds = collect($fields)->pluck('field_id')->toArray();
        $requiredFields = $fieldDefinitions->where('required', true);

        foreach ($requiredFields as $requiredField) {
            if (!in_array($requiredField->id, $providedFieldIds)) {
                throw ValidationException::withMessages([
                    'fields' => ["The field '{$requiredField->field_name}' is required."]
                ]);
            }
        }
    }

    /**
     * Validate field types
     */
    protected function validateFieldTypes($fieldDefinitions, $fields)
    {
        foreach ($fields as $fieldData) {
            $fieldDef = $fieldDefinitions->get($fieldData['field_id']);
            
            if (!$fieldDef) {
                throw ValidationException::withMessages([
                    'fields' => ["Field ID {$fieldData['field_id']} does not exist for this category."]
                ]);
            }

            $value = $fieldData['field_value'];

            // Skip validation for empty optional fields
            if (empty($value) && !$fieldDef->required) {
                continue;
            }

            // Validate based on field type
            switch ($fieldDef->field_type) {
                case 'Int':
                    if (!is_numeric($value) || !ctype_digit(strval($value))) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be an integer."]
                        ]);
                    }
                    break;

                case 'Float':
                case 'Decimal':
                    if (!is_numeric($value)) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be a number."]
                        ]);
                    }
                    break;

                case 'Text':
                    // Text can be anything
                    break;
            }
        }
    }
}
