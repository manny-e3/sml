<?php

namespace App\Services;

use App\Models\SecurityMasterData;
use App\Models\PendingSecurityMasterData;
use App\Models\SecurityMasterFieldValue;
use App\Models\SecurityManagement;
use App\Models\MarketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Utils\Constants;

class SecurityMasterDataService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated list of pending requests.
     */
    public function getPendingRequests(int $perPage = 15)
    {
        $pending = PendingSecurityMasterData::with(['requester', 'category', 'mainRecord'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Create a new security master creation request
     */
    public function createRequest(array $data)
    {
        // Validate category exists
        $category = MarketCategory::find($data['category_id']);
        if (!$category) {
            throw new \Exception('Market category not found');
        }

        // Validate fields
        $this->validateFields($data['category_id'], $data['fields'] ?? []);

        return DB::transaction(function () use ($data) {
            $pending = PendingSecurityMasterData::create([
                'category_id' => $data['category_id'],
                'security_name' => $data['security_name'],
                'status' => $data['status'] ?? 1,
                'fields_data' => $data['fields'] ?? [],
                'request_type' => 'create',
                'requested_by' => $data['created_by'], // Assuming created_by passes user ID
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create update request
     */
    public function updateRequest(SecurityMasterData $security, array $data)
    {
        // Validate fields if provided
        if (isset($data['fields'])) {
            $this->validateFields($security->category_id, $data['fields']);
        }

        return DB::transaction(function () use ($security, $data) {
            // Set main record to pending_approval (optional, depending on requirement)
            // But usually we just lock it from other updates? 
            // For now, let's just mark it if we want, or just rely on pending request existence.
            $security->update(['approval_status' => 'pending_approval']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'security_name' => $data['security_name'] ?? $security->security_name,
                'status' => $data['status'] ?? $security->status,
                'fields_data' => $data['fields'] ?? null, // Only store if changing? Or store all?
                // If fields are passed, store them. 
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create delete request
     */
    public function deleteRequest(SecurityMasterData $security, array $data)
    {
        return DB::transaction(function () use ($security, $data) {
            $security->update(['approval_status' => 'pending_approval']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'security_name' => $security->security_name,
                'status' => $security->status,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'], // Assuming passed in same field or we need a param
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingSecurityMasterData $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $security = SecurityMasterData::create([
                        'category_id' => $pending->category_id,
                        'security_name' => $pending->security_name,
                        'status' => $pending->status,
                        'approval_status' => 'active',
                        'created_by' => $pending->requested_by,
                    ]);

                    // Create field values
                    if ($pending->fields_data) {
                        foreach ($pending->fields_data as $fieldData) {
                            SecurityMasterFieldValue::create([
                                'security_master_id' => $security->id,
                                'field_id' => $fieldData['field_id'],
                                'field_value' => $fieldData['field_value'],
                            ]);
                        }
                    }
                    $result = $security;
                    break;

                case 'update':
                    $security = $pending->mainRecord;
                    if ($security) {
                        $security->update([
                            'security_name' => $pending->security_name,
                            'status' => $pending->status,
                            'approval_status' => 'active',
                            'updated_by' => $pending->requested_by,
                        ]);

                        // Update fields
                        if ($pending->fields_data) {
                            foreach ($pending->fields_data as $fieldData) {
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
                        $result = $security;
                    }
                    break;

                case 'delete':
                    $security = $pending->mainRecord;
                    if ($security) {
                        $security->delete(); // Assuming soft delete or hard delete? 
                        // SecurityMasterData model doesn't use SoftDeletes in my previous view, 
                        // but MarketCategory did. I'll assume hard delete for now or standard delete.
                        // Wait, migration had 'onDelete cascade', so field values go too.
                        // If I want soft delete, I'd need to add it. For now, strict delete.
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurityMasterData $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            // Revert the main record approval_status
            if (in_array($pending->request_type, ['update', 'delete']) && $pending->mainRecord) {
                $pending->mainRecord->update(['approval_status' => 'active']);
            }

            $pending->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $this->notifyRequester($pending, 'rejected');

            return $pending;
        });
    }

    /**
     * Get all securities with pagination (Existing method preserved/adjusted)
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
     * Get single security (Existing method)
     */
    public function getSecurity($id)
    {
        return SecurityMasterData::with(['category', 'fieldValues.field'])->findOrFail($id);
    }

    // Helpers

    protected function validateFields($categoryId, $fields)
    {
        $fieldDefinitions = SecurityManagement::where('category_id', $categoryId)
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        $this->validateRequiredFields($fieldDefinitions, $fields ?? []);
        $this->validateFieldTypes($fieldDefinitions, $fields ?? []);
    }

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

    protected function validateFieldTypes($fieldDefinitions, $fields)
    {
        foreach ($fields as $fieldData) {
            $fieldDef = $fieldDefinitions->get($fieldData['field_id']);
            
            if (!$fieldDef) {
                 // Check if it's strictly required to fail if extra field sent, or just ignore?
                 // Original code threw exception.
                throw ValidationException::withMessages([
                    'fields' => ["Field ID {$fieldData['field_id']} does not exist for this category."]
                ]);
            }

            $value = $fieldData['field_value'];

            if (empty($value) && !$fieldDef->required) {
                continue;
            }

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
            }
        }
    }

    private function notifySelectedAuthoriser(PendingSecurityMasterData $pending)
    {
        if (!$pending->selected_authoriser_id) return;

        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        $requester = $this->externalUserService->getUserById($pending->requested_by);

        if ($authoriser && isset($authoriser['email'])) {
             Mail::to($authoriser['email'])->send(new \App\Mail\SecurityMasterRequestPending($pending, $requester));
        }
    }

    private function notifyRequester(PendingSecurityMasterData $pending, string $status)
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'])->send(new \App\Mail\SecurityMasterRequestApproved($pending, $requester));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'])->send(new \App\Mail\SecurityMasterRequestRejected($pending, $pending->rejection_reason, $requester));
        }
    }
}
