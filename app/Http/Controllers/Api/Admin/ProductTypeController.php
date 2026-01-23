<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\PendingProductType;
use App\Services\ProductTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductTypeController extends Controller
{
    protected $productTypeService;

    public function __construct(ProductTypeService $productTypeService)
    {
        $this->productTypeService = $productTypeService;
    }

    #[OA\Get(
        path: "/api/v1/admin/product-types",
        operationId: "getProductTypes",
        summary: "Get List of Product Types",
        tags: ["Product Types"],
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
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $productTypes = $this->productTypeService->getAllProductTypes($perPage);
        return response()->json($productTypes);
    }

    #[OA\Post(
        path: "/api/v1/admin/product-types",
        operationId: "createProductType",
        summary: "Create Product Type (Request)",
        description: "Submit a request to create a new product type, selecting an authoriser.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["market_category_id", "name", "code", "authoriser_id"],
                properties: [
                    new OA\Property(property: "market_category_id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "authoriser_id", type: "integer", description: "The ID of the authoriser who should approve this request"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Product Type creation request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_category_id' => 'required|exists:market_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->productTypeService->createRequest($validated);
            return response()->json([
                'message' => 'Product Type creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "getProductType",
        summary: "Get Product Type Details",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show(ProductType $productType): JsonResponse
    {
        return response()->json($productType->load('marketCategory'));
    }

    #[OA\Put(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "updateProductType",
        summary: "Update Product Type (Request)",
        description: "Submit a request to update an existing product type, selecting an authoriser.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["authoriser_id"],
                properties: [
                    new OA\Property(property: "market_category_id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "authoriser_id", type: "integer", description: "The ID of the authoriser who should approve this request"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200, 
                description: "Request created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Product Type update request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, ProductType $productType): JsonResponse
    {
        $validated = $request->validate([
            'market_category_id' => 'sometimes|exists:market_categories,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'requested_by' => 'required',
            'authoriser_id' => 'required',
        ]);

        try {
            $pending = $this->productTypeService->updateRequest($productType, $validated);
            return response()->json([
                'message' => 'Product Type update request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/admin/product-types/{productType}",
        operationId: "deleteProductType",
        summary: "Delete Product Type (Request)",
        description: "Submit a request to delete a product type, selecting an authoriser. Pass authoriser_id in request body or query param.",
        tags: ["Product Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "productType",
                in: "path",
                description: "Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "authoriser_id",
                in: "query",
                description: "The ID of the authoriser who should approve this request",
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
                        new OA\Property(property: "message", type: "string", example: "Product Type deletion request submitted for approval."),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, ProductType $productType): JsonResponse
    {
        $validated = $request->validate([
            'authoriser_id' => 'required',
            'requested_by'  => 'required',
        ]);

        try {
            $pending = $this->productTypeService->deleteRequest($productType, $validated);
            return response()->json([
                'message' => 'Product Type deletion request submitted for approval.',
                'data' => $pending
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-product-types",
        operationId: "getPendingProductTypes",
        summary: "Get Pending Product Types",
        description: "Retrieve a paginated list of pending product type requests.",
        tags: ["Product Type Approvals"],
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
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->productTypeService->getPendingRequests($perPage);
        return response()->json($pending);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}/approve",
        operationId: "approveProductType",
        summary: "Approve Pending Product Type",
        description: "Approve a pending product type request. Only the assigned authoriser can approve.",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
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
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function approve(PendingProductType $pendingProductType): JsonResponse
    {
        try {
            $result = $this->productTypeService->approveRequest($pendingProductType);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-product-types/{pendingProductType}/reject",
        operationId: "rejectProductType",
        summary: "Reject Pending Product Type",
        description: "Reject a pending product type request. Only the assigned authoriser can reject.",
        tags: ["Product Type Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "pendingProductType",
                in: "path",
                description: "Pending Product Type ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["reason"],
                properties: [
                    new OA\Property(property: "reason", type: "string")
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
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function reject(Request $request, PendingProductType $pendingProductType): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->productTypeService->rejectRequest($pendingProductType, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
