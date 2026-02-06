<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionResult;
use App\Models\PendingAuctionResult;
use App\Services\AuctionResultService;
use App\Services\ExternalUserService;
use App\Http\Requests\StoreAuctionResultRequest;
use App\Http\Requests\UpdateAuctionResultRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuctionResultController extends Controller
{
    protected $service;
    protected $userService;

    public function __construct(AuctionResultService $service, ExternalUserService $userService)
    {
        $this->service = $service;
        $this->userService = $userService;
    }

    // LIST EXISTING
    public function index(Request $request)
    {
        $query = AuctionResult::with('security');

        if ($request->filled('security_id')) {
            $query->where('security_id', $request->security_id);
        }
        
        if ($request->filled('auction_date')) {
            $query->whereDate('auction_date', $request->auction_date);
        }

        $results = $query->latest('auction_date')->paginate(15);
        $this->userService->enrichWithUsers($results, ['created_by' => 'creator', 'authoriser_id' => 'authoriser']);

        return response()->json($results);
    }

    // CREATE REQUEST
    public function store(StoreAuctionResultRequest $request)
    {
        $data = $request->validated();
        // Service expects created_by
        $data['created_by'] = $data['requested_by'] ?? auth()->id();
        
        try {
            $pending = $this->service->createRequest($data);
            
            return response()->json([
                'message' => 'Auction Result creation submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // SHOW EXISTING
    public function show(AuctionResult $auctionResult)
    {
        return response()->json($auctionResult->load('security', 'creator'));
    }

    // UPDATE REQUEST
    public function update(UpdateAuctionResultRequest $request, AuctionResult $auctionResult)
    {
        $data = $request->validated();
        $data['updated_by'] = $data['requested_by'] ?? auth()->id();
        $data['authoriser_id'] = $request->authoriser_id;

        try {
            $pending = $this->service->updateRequest($auctionResult, $data);
            
            return response()->json([
                'message' => 'Auction Result update submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // DELETE REQUEST
    public function destroy(Request $request, AuctionResult $auctionResult)
    {
        $validated = $request->validate([
            'updated_by' => 'required',
            'authoriser_id' => 'required',
        ]);


        try {
            $pending = $this->service->deleteRequest($auctionResult, $validated);
            
            return response()->json([
                'message' => 'Auction Result deletion submitted for approval.',
                'data' => $pending
            ], 202);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // BULK UPLOAD
    #[OA\Post(
        path: "/api/v1/admin/auction-results/bulk-upload",
        operationId: "bulkUploadAuctionResults",
        summary: "Bulk Upload Auction Results",
        tags: ["Auction Results"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "created_by", "authoriser_id"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "created_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer")
                    ]
                )
            )
        ),
        responses: [new OA\Response(response: 200, description: "Uploaded successfully")]
    )]
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\AuctionResultImport(
                    $this->service,
                    $request->created_by,
                    $request->authoriser_id
                ),
                $request->file('file')
            );

            return response()->json([
                'message' => 'Bulk upload processed. Valid records submitted for approval.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // PENDING REQUESTS
    public function pending(Request $request)
    {
        $results = $this->service->getPendingRequests($request->get('per_page', 15));
        return response()->json($results);
    }

    public function showPending(PendingAuctionResult $pendingAuctionResult)
    {
        return response()->json($pendingAuctionResult->load('security', 'requester', 'mainRecord'));
    }

    public function approve(PendingAuctionResult $pendingAuctionResult)
    {
        try {
            $result = $this->service->approveRequest($pendingAuctionResult);
            return response()->json(['message' => 'Request approved successfully.', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, PendingAuctionResult $pendingAuctionResult)
    {
        $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->service->rejectRequest($pendingAuctionResult, $request->reason);
            return response()->json(['message' => 'Request rejected successfully.', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

