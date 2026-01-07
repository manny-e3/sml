<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionResult;
use App\Models\Security;
use App\Models\PendingAction;
use App\Http\Requests\StoreAuctionResultRequest;
use App\Http\Requests\UpdateAuctionResultRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuctionResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AuctionResult::with('security');

        if ($request->filled('security_id')) {
            $query->where('security_id', $request->security_id);
        }
        
        if ($request->filled('auction_date')) {
            $query->whereDate('auction_date', $request->auction_date);
        }

        return response()->json($query->latest('auction_date')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuctionResultRequest $request)
    {
        $data = $request->validated();
        
        // Auto-calculate total amount sold
        $data['non_competitive_sales'] = $data['non_competitive_sales'] ?? 0;
        $data['total_amount_sold'] = $data['amount_sold'] + $data['non_competitive_sales'];
        
        // Fill model to use calculation helper
        $auctionResult = new AuctionResult($data);
        $auctionResult->calculateRatios();
        
        // Day
        $auctionResult->day_of_week = \Carbon\Carbon::parse($data['auction_date'])->format('l');

        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            // Include calculated fields in data for approval
            $data['day_of_week'] = $auctionResult->day_of_week;
            $data['total_amount_sold'] = $auctionResult->total_amount_sold;
            $data['bid_cover_ratio'] = $auctionResult->bid_cover_ratio;
            $data['subscription_level'] = $auctionResult->subscription_level;

            PendingAction::create([
                'action_type' => 'create',
                'model_type' => AuctionResult::class,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Auction Result recording submitted for approval.',
                'status' => 'pending'
            ], 202);
        }
        
        $auctionResult->created_by = Auth::id();
        $auctionResult->save();

        return response()->json([
            'message' => 'Auction Result created successfully.',
            'data' => $auctionResult
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AuctionResult $auctionResult)
    {
        return response()->json($auctionResult->load('security', 'creator'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuctionResultRequest $request, AuctionResult $auctionResult)
    {
        $data = $request->validated();
        
        $data['non_competitive_sales'] = $data['non_competitive_sales'] ?? 0;
        $data['total_amount_sold'] = $data['amount_sold'] + $data['non_competitive_sales'];
        
        $auctionResult->fill($data);
        $auctionResult->calculateRatios();
        $auctionResult->day_of_week = \Carbon\Carbon::parse($data['auction_date'])->format('l');
        
        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
             $data['day_of_week'] = $auctionResult->day_of_week;
             $data['total_amount_sold'] = $auctionResult->total_amount_sold;
             $data['bid_cover_ratio'] = $auctionResult->bid_cover_ratio;
             $data['subscription_level'] = $auctionResult->subscription_level;

            PendingAction::create([
                'action_type' => 'update',
                'model_type' => AuctionResult::class,
                'model_id' => $auctionResult->id,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Auction Result update submitted for approval.',
                'status' => 'pending'
            ], 202);
        }

        $auctionResult->updated_by = Auth::id();
        $auctionResult->save();

        return response()->json([
            'message' => 'Auction Result updated successfully.',
            'data' => $auctionResult
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuctionResult $auctionResult)
    {
        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'delete',
                'model_type' => AuctionResult::class,
                'model_id' => $auctionResult->id,
                'data' => [],
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Auction Result deletion submitted for approval.',
                'status' => 'pending'
            ], 202);
        }

        $auctionResult->delete();
        return response()->json([
            'message' => 'Auction Result deleted successfully.'
        ]);
    }
}
