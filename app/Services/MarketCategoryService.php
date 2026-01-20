<?php

namespace App\Services;

use App\Models\MarketCategory;
use App\Models\PendingMarketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;

class MarketCategoryService
{
    /**
     * Get paginated list of market categories.
     */
    public function getAllCategories(int $perPage = 15): LengthAwarePaginator
    {
        return MarketCategory::latest()->paginate($perPage);
    }

    /**
     * Get paginated list of pending requests.
     */
    public function getPendingRequests(int $perPage = 15): LengthAwarePaginator
    {
        return PendingMarketCategory::where('approval_status', 'pending')
            ->with(['requester', 'marketCategory'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a request to create a new market category.
     */
    public function createRequest(array $data): PendingMarketCategory
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingMarketCategory::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'request_type' => 'create',
                'requested_by' => auth()->id(),
                'approval_status' => 'pending',
            ]);

            $this->notifyAuthorisers($pending);

            return $pending;
        });
    }

    /**
     * Create a request to update an existing market category.
     */
    public function updateRequest(MarketCategory $category, array $data): PendingMarketCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $pending = PendingMarketCategory::create([
                'market_category_id' => $category->id,
                'name' => $data['name'] ?? $category->name,
                'code' => $data['code'] ?? $category->code,
                'description' => $data['description'] ?? $category->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $category->is_active,
                'request_type' => 'update',
                'requested_by' => auth()->id(),
                'approval_status' => 'pending',
            ]);

            $this->notifyAuthorisers($pending);

            return $pending;
        });
    }

    /**
     * Create a request to delete a market category.
     */
    public function deleteRequest(MarketCategory $category): PendingMarketCategory
    {
        return DB::transaction(function () use ($category) {
            $pending = PendingMarketCategory::create([
                'market_category_id' => $category->id,
                'name' => $category->name, // Snapshot for reference
                'code' => $category->code,
                'request_type' => 'delete',
                'requested_by' => auth()->id(),
                'approval_status' => 'pending',
            ]);

            $this->notifyAuthorisers($pending);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingMarketCategory $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        // Prevent self-approval if enforced
        if ($pending->requested_by === auth()->id()) {
            throw new \Exception('You cannot approve your own request.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = MarketCategory::create([
                        'name' => $pending->name,
                        'code' => $pending->code,
                        'description' => $pending->description,
                        'is_active' => $pending->is_active,
                    ]);
                    break;

                case 'update':
                    $category = $pending->marketCategory;
                    if ($category) {
                        $category->update([
                            'name' => $pending->name,
                            'code' => $pending->code,
                            'description' => $pending->description,
                            'is_active' => $pending->is_active,
                        ]);
                        $result = $category;
                    }
                    break;

                case 'delete':
                    $category = $pending->marketCategory;
                    if ($category) {
                        $category->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            // Notify Requester
            // $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingMarketCategory $pending, string $reason): PendingMarketCategory
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        // Prevent self-rejection if enforced
        if ($pending->requested_by === auth()->id()) {
            throw new \Exception('You cannot reject your own request.');
        }

        $pending->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Notify Requester
        // $this->notifyRequester($pending, 'rejected');

        return $pending;
    }

    /**
     * Notify authorisers.
     */
    private function notifyAuthorisers(PendingMarketCategory $pending): void
    {
        $authorisers = User::role(['authoriser', 'super_admin'])
            ->where('is_active', true)
            ->get();
        
        foreach ($authorisers as $authoriser) {
            Mail::to($authoriser->email)->send(new \App\Mail\MarketCategoryRequestPending($pending));
        }
    }

    /**
     * Notify requester (Optional/Future).
     */
    // private function notifyRequester(PendingMarketCategory $pending, string $status): void
    // {
    //     // Implementation for notifying inputter of approval/rejection
    // }
}
