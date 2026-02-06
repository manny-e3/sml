<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketCategory;
use App\Models\PendingMarketCategory;
use App\Services\MarketCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MarketCategoryController extends Controller
{
    protected $marketCategoryService;

    public function __construct(MarketCategoryService $marketCategoryService)
    {
        $this->marketCategoryService = $marketCategoryService;
    }

    #[OA\Get(
        path: "/api/v1/admin/market-categories",
        operationId: "getMarketCategories",
        summary: "Get List of Market Categories",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $categories = $this->marketCategoryService->getAllCategories($perPage);
        return response()->json($categories);
    }

    #[OA\Get(
        path: "/api/v1/market-categories/all",
        operationId: "getAllActiveMarketCategories",
        summary: "Get All Active Market Categories",
        description: "Retrieve a list of all active market categories (for dropdowns)",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "code", type: "string")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function all(): JsonResponse
    {
        $categories = $this->marketCategoryService->getAllActiveCategories();
        return response()->json($categories);
    }

    #[OA\Post(
        path: "/api/v1/admin/market-categories",
        operationId: "createMarketCategory",
        summary: "Create Market Category (Request)",
        description: "Submit a request to create a new market category",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "code"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Market Category creation request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255', 
            'code' => 'required|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        $existing = MarketCategory::where('name', $request->name)
            ->orWhere('code', $request->code)
            ->first();

        if ($existing) {
             return response()->json([
                'message' => 'Market Category with this name or code already exists.',
                'error_code' => 'DUPLICATE_ENTRY'
            ], 400);
        }

        try {
            $pending = $this->marketCategoryService->createRequest($validated);
            return response()->json([
                'message' => 'Market Category creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/admin/market-categories/{marketCategory}",
        operationId: "getMarketCategory",
        summary: "Get Market Category Details",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "marketCategory",
                in: "path",
                description: "Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function show(MarketCategory $marketCategory): JsonResponse
    {
        return response()->json($marketCategory);
    }

    #[OA\Put(
        path: "/api/v1/admin/market-categories/{marketCategory}",
        operationId: "updateMarketCategory",
        summary: "Update Market Category (Request)",
        description: "Submit a request to update an existing market category",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "marketCategory",
                in: "path",
                description: "Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Market Category update request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
   public function update(Request $request, MarketCategory $marketCategory): JsonResponse
{
    $validated = $request->validate([
        'name'           => 'sometimes|string|max:255',
        'code'           => 'sometimes|string|max:10',
        'description'    => 'nullable|string',
        'is_active'       => 'sometimes|boolean',
        'requested_by'    => 'required|integer',
        'authoriser_id'   => 'required|integer',
    ]);

    /**
     * ✅ Check duplicate only if name or code is being updated
     * ✅ Exclude the current record from duplicate check
     */
    if ($request->filled('name') || $request->filled('code')) {
        $existing = MarketCategory::query()
            ->where(function ($query) use ($request) {
                if ($request->filled('name')) {
                    $query->where('name', $request->name);
                }

                if ($request->filled('code')) {
                    $query->orWhere('code', $request->code);
                }
            })
            ->where('id', '!=', $marketCategory->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message'    => 'Market Category with this name or code already exists.',
                'error_code' => 'DUPLICATE_ENTRY',
            ], 409);
        }
    }

    try {
        $pending = $this->marketCategoryService->updateRequest($marketCategory, $validated);

        return response()->json([
            'message' => 'Market Category update request submitted for approval.',
            'data'    => $pending,
        ], 200);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([ 
            'message' => 'Unable to submit update request at this time.',
        ], 500);
    }
}


    #[OA\Delete(
        path: "/api/v1/admin/market-categories/{marketCategory}",
        operationId: "deleteMarketCategory",
        summary: "Delete Market Category (Request)",
        description: "Submit a request to delete a market category",
        tags: ["Market Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "marketCategory",
                in: "path",
                description: "Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Market Category deletion request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function destroy(Request $request, MarketCategory $marketCategory): JsonResponse
    {
        $validated = $request->validate([
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->marketCategoryService->deleteRequest($marketCategory, $validated);
            return response()->json([
                'message' => 'Market Category deletion request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-market-categories",
        operationId: "getPendingMarketCategories",
        summary: "Get Pending Market Categories",
        description: "Retrieve a paginated list of pending market category requests",
        tags: ["Market Category Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(property: "meta", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->marketCategoryService->getPendingRequests($perPage);
        return response()->json($pending);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-market-categories/{pendingMarketCategory}",
        operationId: "getPendingMarketCategory",
        summary: "Get Single Pending Market Category",
        description: "Retrieve details of a single pending market category request",
        tags: ["Market Category Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [   
            new OA\Parameter(
                name: "pendingMarketCategory",
                in: "path",
                description: "Pending Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "code", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "is_active", type: "boolean"),
                        new OA\Property(property: "action_type", type: "string"),
                        new OA\Property(property: "requested_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer"),
                        new OA\Property(property: "approval_status", type: "string"),
                        new OA\Property(property: "created_at", type: "string"),
                        new OA\Property(property: "updated_at", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function showPending(PendingMarketCategory $pendingMarketCategory): JsonResponse
    {
        return response()->json($pendingMarketCategory);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-market-categories/{pendingMarketCategory}/compare",
        operationId: "comparePendingMarketCategory",
        summary: "Compare Pending Market Category Changes",
        description: "Retrieve both old (current) and new (pending) data for update requests to show what will change",
        tags: ["Market Category Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingMarketCategory",
                in: "path",
                description: "Pending Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "action_type", type: "string", example: "update"),
                        new OA\Property(property: "pending_request", type: "object"),
                        new OA\Property(
                            property: "old_data",
                            type: "object",
                            description: "Current data from main table (null for create requests)"
                        ),
                        new OA\Property(
                            property: "new_data",
                            type: "object",
                            description: "Proposed changes from pending request"
                        ),
                        new OA\Property(
                            property: "changes",
                            type: "object",
                            description: "Fields that will be changed (only for update requests)"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function showPendingWithComparison(PendingMarketCategory $pendingMarketCategory): JsonResponse
    {
        $changes = [];

        // For update and delete requests, fetch the current data
        if (in_array($pendingMarketCategory->request_type, ['update', 'delete']) && $pendingMarketCategory->market_category_id) {
            $currentData = MarketCategory::find($pendingMarketCategory->market_category_id);
            
            if ($currentData) {
                // Calculate changes for update requests
                if ($pendingMarketCategory->request_type === 'update') {
                    if ($currentData->name !== $pendingMarketCategory->name) {
                        $changes['name'] = [
                            'old' => $currentData->name,
                            'new' => $pendingMarketCategory->name
                        ];
                    }
                    
                    if ($currentData->code !== $pendingMarketCategory->code) {
                        $changes['code'] = [
                            'old' => $currentData->code,
                            'new' => $pendingMarketCategory->code
                        ];
                    }
                    
                    if ($currentData->description !== $pendingMarketCategory->description) {
                        $changes['description'] = [
                            'old' => $currentData->description,
                            'new' => $pendingMarketCategory->description
                        ];
                    }
                    
                    if ($currentData->is_active !== $pendingMarketCategory->is_active) {
                        $changes['is_active'] = [
                            'old' => $currentData->is_active,
                            'new' => $pendingMarketCategory->is_active
                        ];
                    }
                }
            }
        }

        return response()->json($changes);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-market-categories/{pendingMarketCategory}/approve",
        operationId: "approveMarketCategory",
        summary: "Approve Pending Market Category",
        description: "Approve a pending market category request",
        tags: ["Market Category Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingMarketCategory",
                in: "path",
                description: "Pending Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: "Approved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Request approved successfully."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function approve(PendingMarketCategory $pendingMarketCategory): JsonResponse
    {
        try {
            $result = $this->marketCategoryService->approveRequest($pendingMarketCategory);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-market-categories/{pendingMarketCategory}/reject",
        operationId: "rejectMarketCategory",
        summary: "Reject Pending Market Category",
        description: "Reject a pending market category request",
        tags: ["Market Category Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingMarketCategory",
                in: "path",
                description: "Pending Market Category ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["reason"],
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "Duplicate entry")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Rejected successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Request rejected successfully."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function reject(Request $request, PendingMarketCategory $pendingMarketCategory): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->marketCategoryService->rejectRequest($pendingMarketCategory, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
