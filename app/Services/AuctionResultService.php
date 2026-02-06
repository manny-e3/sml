<?php

namespace App\Services;

use App\Models\AuctionResult;
use App\Models\PendingAuctionResult;
use App\Mail\AuctionResultRequestPending;
use App\Mail\AuctionResultRequestApproved;
use App\Mail\AuctionResultRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuctionResultService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated pending requests
     */
    public function getPendingRequests(int $perPage = 15)
    {
        $pending = PendingAuctionResult::with(['requester', 'security', 'mainRecord'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Create Creation Request
     */
    public function createRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $temp = new AuctionResult($data);
            $temp->calculateRatios();
            
            $nonComp = $data['non_competitive_sales'] ?? 0;
            $amtSold = $data['amount_sold'] ?? 0;
            $totalSold = $amtSold + $nonComp;
            
            $dayOfWeek = \Carbon\Carbon::parse($data['auction_date'])->format('l');

            $pending = PendingAuctionResult::create(array_merge($data, [
                'day_of_week' => $dayOfWeek,
                'total_amount_sold' => $totalSold,
                'bid_cover_ratio' => $temp->bid_cover_ratio,
                'subscription_level' => $temp->subscription_level,
                'request_type' => 'create',
                'requested_by' => $data['created_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]));

            // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create Update Request
     */
    public function updateRequest(AuctionResult $mainRecord, array $data)
    {
        return DB::transaction(function () use ($mainRecord, $data) {
             $temp = new AuctionResult(array_merge($mainRecord->toArray(), $data));
             $temp->calculateRatios();
             
             $nonComp = $data['non_competitive_sales'] ?? $mainRecord->non_competitive_sales;
             $amtSold = $data['amount_sold'] ?? $mainRecord->amount_sold;
             $totalSold = $amtSold + $nonComp;
             
             $dayOfWeek = isset($data['auction_date']) 
                ? \Carbon\Carbon::parse($data['auction_date'])->format('l') 
                : $mainRecord->day_of_week;

            $pending = PendingAuctionResult::create(array_merge($data, [
                'auction_result_id' => $mainRecord->id,
                'security_id' => $data['security_id'] ?? $mainRecord->security_id,
                'day_of_week' => $dayOfWeek,
                'total_amount_sold' => $totalSold,
                'bid_cover_ratio' => $temp->bid_cover_ratio,
                'subscription_level' => $temp->subscription_level,
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]));

         
           // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create Delete Request
     */
    public function deleteRequest(AuctionResult $mainRecord, array $data)
    {
        return DB::transaction(function () use ($mainRecord, $data) {
            $pending = PendingAuctionResult::create([
                'auction_result_id' => $mainRecord->id,
                'security_id' => $mainRecord->security_id,
                'auction_date' => $mainRecord->auction_date,
                'amount_offered' => $mainRecord->amount_offered,
                'value_date' => $mainRecord->value_date,
                'tenor_days' => $mainRecord->tenor_days,
                'amount_subscribed' => $mainRecord->amount_subscribed,
                'amount_allotted' => $mainRecord->amount_allotted,
                'amount_sold' => $mainRecord->amount_sold,
                'non_competitive_sales' => $mainRecord->non_competitive_sales,
                'total_amount_sold' => $mainRecord->total_amount_sold,
                'stop_rate' => $mainRecord->stop_rate,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve Request
     */
    public function approveRequest(PendingAuctionResult $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;
            $data = $pending->toArray();
            
            $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'request_type', 'approval_status', 'rejection_reason', 'requested_by', 'selected_authoriser_id', 'auction_result_id'];
            $attributes = array_diff_key($data, array_flip($exclude));
            
            $attributes['approved_by'] = auth()->id();
            $attributes['approved_at'] = now();

            switch ($pending->request_type) {
                case 'create':
                    $attributes['created_by'] = $pending->requested_by;
                    $attributes['status'] = 'Completed'; 
                    $result = AuctionResult::create($attributes);
                    break;

                case 'update':
                    $main = $pending->mainRecord;
                    if ($main) {
                        $attributes['updated_by'] = $pending->requested_by;
                        $main->update($attributes);
                        $result = $main;
                    }
                    break;

                case 'delete':
                    $main = $pending->mainRecord;
                    if ($main) {
                        $main->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);
            
            // Notify Requester
            if ($pending->requested_by) {
                $requester = \App\Models\User::find($pending->requested_by);
                if ($requester) {
                    Mail::to($requester->email)->send(new AuctionResultRequestApproved($pending, auth()->user()));
                }
            }

            return $result;
        });
    }

    /**
     * Reject Request
     */
    public function rejectRequest(PendingAuctionResult $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        $pending->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason
        ]);
        
        // Notify Requester
        if ($pending->requested_by) {
            $requester = \App\Models\User::find($pending->requested_by);
            if ($requester) {
                Mail::to($requester->email)->send(new AuctionResultRequestRejected($pending, auth()->user()));
            }
        }

        return $pending;
    }
    protected function notifySelectedAuthoriser(PendingAuctionResult $pending)
    {
        if ($pending->selected_authoriser_id) {
            // Try to find local user first, then external if needed (though mail needs email)
            // For now, keeping original logic using App\Models\User as requested, 
            // but can be switched to ExternalUserService if User model is not sync'd.
            $authoriser = \App\Models\User::find($pending->selected_authoriser_id);
            if ($authoriser) {
                Mail::to($authoriser->email)->send(new AuctionResultRequestPending($pending, auth()->user()));
            }
        }
    }
}

