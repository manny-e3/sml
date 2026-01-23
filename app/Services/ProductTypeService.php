<?php

namespace App\Services;

use App\Models\ProductType;
use App\Models\PendingProductType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductTypeService
{
    /**
     * Get paginated list of product types.
     */
    public function getAllProductTypes(int $perPage = 15): LengthAwarePaginator
    {
        return ProductType::with('marketCategory')
            ->withCount('securities')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get pending requests for the current user (if authoriser) or all if super admin?
     * logic: Authorisers should ideally see requests assigned to them.
     */
    public function getPendingRequests(int $perPage = 15): LengthAwarePaginator
    {
        $query = PendingProductType::where('approval_status', 'pending')
            ->with(['requester', 'selectedAuthoriser', 'productType', 'marketCategory'])
            ->latest();

        return $query->paginate($perPage);
    }

    /**
     * Create a request to create a new product type.
     */
    public function createRequest(array $data): PendingProductType
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingProductType::create([
                'market_category_id' => $data['market_category_id'],
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'request_type' => 'create',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a request to update an existing product type.
     */
    public function updateRequest(ProductType $productType, array $data): PendingProductType
    {
        return DB::transaction(function () use ($productType, $data) {
            $pending = PendingProductType::create([
                'product_type_id' => $productType->id,
                'market_category_id' => $data['market_category_id'] ?? $productType->market_category_id,
                'name' => $data['name'] ?? $productType->name,
                'code' => $data['code'] ?? $productType->code,
                'description' => $data['description'] ?? $productType->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $productType->is_active,
                'request_type' => 'update',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a request to delete a product type.
     */
    public function deleteRequest(ProductType $productType, array $data): PendingProductType
    {
        return DB::transaction(function () use ($productType, $data) {
            $pending = PendingProductType::create([
                'product_type_id' => $productType->id,
                'market_category_id' => $productType->market_category_id,
                'name' => $productType->name, // Snapshot
                'code' => $productType->code,
                'request_type' => 'delete',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingProductType $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = ProductType::create([
                        'market_category_id' => $pending->market_category_id,
                        'name' => $pending->name,
                        'code' => $pending->code,
                        'description' => $pending->description,
                        'is_active' => $pending->is_active,
                    ]);
                    break;

                case 'update':
                    $productType = $pending->productType;
                    if ($productType) {
                        $productType->update([
                            'market_category_id' => $pending->market_category_id,
                            'name' => $pending->name,
                            'code' => $pending->code,
                            'description' => $pending->description,
                            'is_active' => $pending->is_active,
                        ]);
                        $result = $productType;
                    }
                    break;

                case 'delete':
                    $productType = $pending->productType;
                    if ($productType) {
                        // Soft delete
                        $productType->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            // Notify Requester
            $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingProductType $pending, string $reason): PendingProductType
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        $pending->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Notify Requester
        $this->notifyRequester($pending, 'rejected');

        return $pending;
    }

    /**
     * Notify the selected authoriser.
     */
    private function notifySelectedAuthoriser(PendingProductType $pending): void
    {
        $authoriser = $pending->selectedAuthoriser;
        if ($authoriser) {
             Mail::to($authoriser->email)->send(new \App\Mail\ProductTypeRequestPending($pending));
        }
    }

    /**
     * Notify requester (Inputter) of approval/rejection.
     */
    private function notifyRequester(PendingProductType $pending, string $status): void
    {
        if (!$pending->requester) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($pending->requester->email)->send(new \App\Mail\ProductTypeRequestApproved($pending));
        } elseif ($status === 'rejected') {
            Mail::to($pending->requester->email)->send(new \App\Mail\ProductTypeRequestRejected($pending, $pending->rejection_reason));
        }
    }
}
