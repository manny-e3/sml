<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SecurityManagementController extends Controller
{
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
