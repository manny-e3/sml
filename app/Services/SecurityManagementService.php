<?php

namespace App\Services;

use App\Models\SecurityManagement;
use App\Models\PendingSecurityManagement;
use App\Models\MarketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SecurityManagementService
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
        $pending = PendingSecurityManagement::with(['requester', 'category', 'productType', 'mainRecord'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Create a new security management creation request
     */
    public function createRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingSecurityManagement::create([
                'category_id' => $data['category_id'],
                'product_id' => $data['product_id'] ?? null,
                'field_name' => $data['field_name'],
                'field_type' => $data['field_type'],
                'required' => $data['required'] ?? false,
                'status' => $data['status'] ?? 1,
                'request_type' => 'create',
                'requested_by' => $data['created_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            // Notifications can be added here
            return $pending;
        });
    }

    /**
     * Create update request
     */
    public function updateRequest(SecurityManagement $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            // $field->update(['status' => 0]); // Should we enhance locking? Or just let it be.

            $pending = PendingSecurityManagement::create([
                'security_management_id' => $field->id,
                'category_id' => $field->category_id,
                'product_id' => $data['product_id'] ?? $field->product_id,
                'field_name' => $data['field_name'] ?? $field->field_name,
                'field_type' => $data['field_type'] ?? $field->field_type,
                'required' => $data['required'] ?? $field->required,
                'status' => $data['status'] ?? $field->status,
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            return $pending;
        });
    }

    /**
     * Create delete request
     */
    public function deleteRequest(SecurityManagement $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            $pending = PendingSecurityManagement::create([
                'security_management_id' => $field->id,
                'category_id' => $field->category_id,
                'product_id' => $field->product_id,
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'required' => $field->required,
                'status' => $field->status,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingSecurityManagement $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $field = SecurityManagement::create([
                        'category_id' => $pending->category_id,
                        'product_id' => $pending->product_id,
                        'field_name' => $pending->field_name,
                        'field_type' => $pending->field_type,
                        'required' => $pending->required,
                        'status' => $pending->status,
                        'created_by' => $pending->requested_by, // Store creator ID as string per original migration? Migration said string 'created_by'.
                    ]);
                    $result = $field;
                    break;

                case 'update':
                    $field = $pending->mainRecord;
                    if ($field) {
                        $field->update([
                            'product_id' => $pending->product_id,
                            'field_name' => $pending->field_name,
                            'field_type' => $pending->field_type,
                            'required' => $pending->required,
                            'status' => $pending->status,
                        ]);
                        $result = $field;
                    }
                    break;

                case 'delete':
                    $field = $pending->mainRecord;
                    if ($field) {
                        $field->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);
            
            // Notify Requester logic here

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurityManagement $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            $pending->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            // Notify Requester logic here

            return $pending;
        });
    }
}
