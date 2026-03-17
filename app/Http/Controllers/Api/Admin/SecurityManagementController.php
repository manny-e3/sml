<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SecurityManagementController extends Controller
{
    protected $securityManagementService;

    public function __construct(\App\Services\SecurityManagementService $securityManagementService)
    {
        $this->securityManagementService = $securityManagementService;
    }

    /**
     * Create a new security management field (pending approval)
     * 
     * @OA\Post(
     *     path="/api/v1/admin/security-management",
     *     summary="Create Security Management Field",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id", "field_name", "field_type", "created_by", "authoriser_id"},
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="product_id", type="integer", nullable=true),
     *             @OA\Property(property="field_name", type="string"),
     *             @OA\Property(property="field_type", type="string", enum={"Float", "Decimal", "Int", "Text"}),
     *             @OA\Property(property="required", type="boolean"),
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="created_by", type="integer"),
     *             @OA\Property(property="authoriser_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Request submitted")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:market_categories,id',
            'product_id' => 'nullable|exists:product_types,id',
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|in:Float,Decimal,Int,Text',
            'required' => 'boolean',
            'status' => 'boolean',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $pending = $this->securityManagementService->createRequest($validated);
            return response()->json([
                'message' => 'Security management field creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get pending requests
     */
    public function pending(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->securityManagementService->getPendingRequests($perPage);
        
        // Transform response to include readable names
        $pending->getCollection()->transform(function ($item) {
            $data = $item->toArray();
            $data['category_name'] = $item->category->name ?? null;
            $data['product_name'] = $item->productType->name ?? null;
            $data['requester_name'] = $item->requester ? ($item->requester->first_name . ' ' . $item->requester->last_name) : null;
            return $data;
        });

        return response()->json($pending);
    }

    /**
     * Approve a pending request
     */
    public function approve($id)
    {
        try {
            $pending = \App\Models\PendingSecurityManagement::findOrFail($id);
            $result = $this->securityManagementService->approveRequest($pending);
            return response()->json(['message' => 'Request approved successfully', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject a pending request
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        
        try {
            $pending = \App\Models\PendingSecurityManagement::findOrFail($id);
            $result = $this->securityManagementService->rejectRequest($pending, $request->rejection_reason);
            return response()->json(['message' => 'Request rejected successfully', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Get security management fields by category ID
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management/category/{categoryId}",
     *     operationId="getSecurityManagementFieldsByCategory",
     *     summary="Get Security Management Fields by Category",
     *     description="Retrieve all active security management fields for a specific market category",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         description="Market Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=Active, 0=Inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="category_name", type="string", example="Bonds"),
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="field_name", type="string", example="Issue Category"),
     *                     @OA\Property(property="field_type", type="string", example="Text"),
     *                     @OA\Property(property="required", type="boolean", example=true),
     *                     @OA\Property(property="status", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getFieldsByCategory(Request $request, $categoryId)
    {
        $category = \App\Models\MarketCategory::find($categoryId);
        
        if (!$category) {
            return response()->json([
                'message' => 'Market category not found'
            ], 404);
        }

        $query = \App\Models\SecurityManagement::where('category_id', $categoryId);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to active fields only
            $query->where('status', 1);
        }

        $fields = $query->orderBy('id')->get([
            'id',
            'field_name',
            'field_type',
            'required',
            'status'
        ]);

        return response()->json([
            'category_id' => $category->id,
            'category_name' => $category->name,
            'category_code' => $category->code,
            'fields' => $fields
        ]);
    }

    /**
     * Get all security management fields grouped by category
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-management/all",
     *     operationId="getAllSecurityManagementFields",
     *     summary="Get All Security Management Fields",
     *     description="Retrieve all security management fields grouped by market category",
     *     tags={"Security Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="category_name", type="string"),
     *                 @OA\Property(property="fields", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getAllFields()
    {
        $categories = \App\Models\MarketCategory::where('is_active', true)
            ->with(['securityManagementFields' => function($query) {
                $query->where('status', 1)->orderBy('id');
            }])
            ->get(['id', 'name', 'code']);

        $result = $categories->map(function($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_code' => $category->code,
                'fields' => $category->securityManagementFields
            ];
        });

        return response()->json($result);
    }
}
