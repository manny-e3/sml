<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityMasterData;
use App\Services\SecurityMasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecurityMasterDataController extends Controller
{
    protected $securityMasterService;

    public function __construct(SecurityMasterDataService $securityMasterService)
    {
        $this->securityMasterService = $securityMasterService;
    }

    /**
     * Get all security master records
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-master",
     *     operationId="getSecurityMasterRecords",
     *     summary="Get All Security Master Records",
     *     description="Retrieve paginated list of security master records",
     *     tags={"Security Master Data"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $categoryId = $request->get('category_id');
        
        $securities = $this->securityMasterService->getAllSecurities($perPage, $categoryId);
        
        return response()->json($securities);
    }

    /**
     * Create a new security master record
     *
     * @OA\Post(
     *     path="/api/v1/admin/security-master",
     *     operationId="createSecurityMaster",
     *     summary="Create Security Master Record",
     *     description="Create a new security with dynamic field values",
     *     tags={"Security Master Data"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id", "security_name", "fields"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="security_name", type="string", example="FGN Bond 2025"),
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="field_id", type="integer", example=1),
     *                     @OA\Property(property="field_value", type="string", example="Federal Government")
     *                 )
     *             ),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Security created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:market_categories,id',
            'security_name' => 'required|string|max:255',
            'fields' => 'required|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'created_by' => 'nullable|string',
        ]);

        try {
            $security = $this->securityMasterService->createSecurity($validated);
            
            return response()->json([
                'message' => 'Security master record created successfully.',
                'data' => $security
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get a single security master record
     *
     * @OA\Get(
     *     path="/api/v1/admin/security-master/{id}",
     *     operationId="getSecurityMaster",
     *     summary="Get Security Master Record",
     *     description="Retrieve a single security with all field values",
     *     tags={"Security Master Data"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Security Master ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $security = $this->securityMasterService->getSecurity($id);
            return response()->json($security);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Security not found'
            ], 404);
        }
    }

    /**
     * Update a security master record
     *
     * @OA\Put(
     *     path="/api/v1/admin/security-master/{id}",
     *     operationId="updateSecurityMaster",
     *     summary="Update Security Master Record",
     *     description="Update security with field values",
     *     tags={"Security Master Data"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Security Master ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="security_name", type="string"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="field_id", type="integer"),
     *                     @OA\Property(property="field_value", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Security updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'security_name' => 'sometimes|string|max:255',
            'fields' => 'sometimes|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'updated_by' => 'nullable|string',
        ]);

        try {
            $security = SecurityMasterData::findOrFail($id);
            $updated = $this->securityMasterService->updateSecurity($security, $validated);
            
            return response()->json([
                'message' => 'Security master record updated successfully.',
                'data' => $updated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a security master record
     *
     * @OA\Delete(
     *     path="/api/v1/admin/security-master/{id}",
     *     operationId="deleteSecurityMaster",
     *     summary="Delete Security Master Record",
     *     description="Delete a security and all its field values",
     *     tags={"Security Master Data"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Security Master ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Security deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $security = SecurityMasterData::findOrFail($id);
            $this->securityMasterService->deleteSecurity($security);
            
            return response()->json([
                'message' => 'Security master record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Security not found'
            ], 404);
        }
    }
}
