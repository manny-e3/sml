<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityMasterData;
use App\Models\PendingSecurityMasterData;
use App\Services\SecurityMasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SecurityMasterDataController extends Controller
{
    protected $securityMasterService;
    protected $externalUserService;

    public function __construct(SecurityMasterDataService $securityMasterService, \App\Services\ExternalUserService $externalUserService)
    {
        $this->securityMasterService = $securityMasterService;
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get all security master records
     * ... (Keeping existing OA for index)
     */
    #[OA\Get(
        path: "/api/v1/admin/security-master",
        operationId: "getSecurityMasterRecords",
        summary: "Get All Security Master Records",
        description: "Retrieve paginated list of security master records",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "per_page", in: "query", schema: new OA\Schema(type: "integer", default: 15)),
            new OA\Parameter(name: "category_id", in: "query", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $categoryId = $request->get('category_id');
        
        $securities = $this->securityMasterService->getAllSecurities($perPage, $categoryId);
        
        $users = $this->externalUserService->getAllUsers();
        
        $securities->getCollection()->transform(function ($security) use ($users) {
            $creator = $users->get($security->created_by);
            $creatorName = $creator ? trim(($creator['firstname'] ?? '') . ' ' . ($creator['lastname'] ?? '')) : null;

            return [
                'id' => $security->id,
                'security_name' => $security->security_name,
                'category' => [
                    'id' => $security->category->id,
                    'name' => $security->category->name,
                ],
                'product' => $security->productType ? [
                    'id' => $security->productType->id,
                    'name' => $security->productType->name,
                ] : null,
                'status' => $security->status,
                'created_by' => $security->created_by,
                'created_by_name' => $creatorName,
                'approval_status' => $security->approval_status,
                'fields' => $security->fieldValues->map(function ($value) {
                    return [
                        'field_id' => $value->field_id,
                        'field_name' => $value->field->field_name ?? null,
                        'value' => $value->field_value,
                    ];
                }),
            ];
        });
        
        return response()->json($securities);
    }

    /**
     * Create a new security master record (Request)
     */
    #[OA\Post(
        path: "/api/v1/admin/security-master",
        operationId: "createSecurityMaster",
        summary: "Create Security Master Record (Request)",
        description: "Submit a request to create a new security",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "product_id", "fields", "authoriser_id"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(property: "product_id", type: "integer"),
                    new OA\Property(property: "fields", type: "array", items: new OA\Items(properties: [
                        new OA\Property(property: "field_id", type: "integer"),
                        new OA\Property(property: "field_value", type: "string")
                    ])),
                    new OA\Property(property: "status", type: "integer"),
                    new OA\Property(property: "created_by", type: "integer"),
                    new OA\Property(property: "authoriser_id", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Request submitted successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:market_categories,id',
            'product_id' => 'required|exists:product_types,id',
            'fields' => 'required|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'created_by' => 'required|integer', // Changed to integer for user ID
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $pending = $this->securityMasterService->createRequest($validated);
            
            return response()->json([
                'message' => 'Security master creation request submitted for approval.',
                'data' => $pending
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get a single security master record
     */
    #[OA\Get(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "getSecurityMaster",
        summary: "Get Security Master Record",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show($id): JsonResponse
    {
        try {
            $security = $this->securityMasterService->getSecurity($id);
            return response()->json($security);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Security not found'], 404);
        }
    }

    /**
     * Update a security master record (Request)
     */
    #[OA\Put(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "updateSecurityMaster",
        summary: "Update Security Master Record (Request)",
        description: "Submit a request to update a security",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: "security_name", type: "string"),
            new OA\Property(property: "product_id", type: "integer"),
            new OA\Property(property: "fields", type: "array", items: new OA\Items(properties: [
                new OA\Property(property: "field_id", type: "integer"),
                new OA\Property(property: "field_value", type: "string")
            ])),
            new OA\Property(property: "updated_by", type: "integer"),
            new OA\Property(property: "authoriser_id", type: "integer")
        ])),
        responses: [
            new OA\Response(response: 200, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'security_name' => 'sometimes|string|max:255',
            'product_id' => 'sometimes|exists:product_types,id',
            'fields' => 'sometimes|array',
            'fields.*.field_id' => 'required|exists:security_management,id',
            'fields.*.field_value' => 'nullable',
            'status' => 'sometimes|boolean',
            'updated_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $security = SecurityMasterData::findOrFail($id);
            $pending = $this->securityMasterService->updateRequest($security, $validated);
            
            return response()->json([
                'message' => 'Security master update request submitted for approval.',
                'data' => $pending
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a security master record (Request)
     */
    #[OA\Delete(
        path: "/api/v1/admin/security-master/{id}",
        operationId: "deleteSecurityMaster",
        summary: "Delete Security Master Record (Request)",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Request submitted successfully"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'updated_by' => 'required|integer', // Requester
            'authoriser_id' => 'required|integer',
        ]);

        try {
            $security = SecurityMasterData::findOrFail($id);
            $pending = $this->securityMasterService->deleteRequest($security, $validated);
            
            return response()->json([
                'message' => 'Security master deletion request submitted for approval.',
                'data' => $pending
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Security not found'], 404);
        }
    }

    // Pending Requests

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master",
        operationId: "getPendingSecurityMasterRecords",
        summary: "Get Pending Security Master Requests",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $pending = $this->securityMasterService->getPendingRequests($perPage);

        // Fetch all field names once to avoid N+1
        $fieldIds = $pending->getCollection()->pluck('fields_data.*.field_id')->flatten()->unique();
        $fields = \App\Models\SecurityManagement::whereIn('id', $fieldIds)->pluck('field_name', 'id');

        $pending->getCollection()->transform(function ($item) use ($fields) {
            $data = [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name ?? null,
                'product_id' => $item->product_id,
                'product_name' => $item->productType->name ?? null,
                'security_name' => $item->security_name,
                'status' => $item->status,
                'request_type' => $item->request_type,
                'approval_status' => $item->approval_status,
                'fields_data' => $item->fields_data,
                'created_at' => $item->created_at,
                'requested_by' => $item->requested_by,
                'requester_name' => $item->requester ? ($item->requester->first_name . ' ' . $item->requester->last_name) : null,
                'rejection_reason' => $item->rejection_reason,
            ];
            
            if (!empty($data['fields_data'])) {
                $data['fields_data'] = collect($data['fields_data'])->map(function ($field) use ($fields) {
                    $field['field_name'] = $fields[$field['field_id']] ?? null;
                    return $field;
                })->toArray();
            }
            
            return $data;
        });

        return response()->json($pending);
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}",
        operationId: "getPendingSecurityMasterRecord",
        summary: "Get Single Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function showPending(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        return response()->json($pendingSecurity->load(['requester', 'category', 'mainRecord']));
    }

    #[OA\Get(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/compare",
        operationId: "comparePendingSecurityMaster",
        summary: "Compare Pending Security Master Changes",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Successful operation")]
    )]
    public function showPendingWithComparison(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        $changes = [];
        $currentData = null;

        if (in_array($pendingSecurity->request_type, ['update', 'delete']) && $pendingSecurity->security_master_id) {
            $currentData = SecurityMasterData::with('fieldValues.field')->find($pendingSecurity->security_master_id);
            
            if ($currentData && $pendingSecurity->request_type === 'update') {
                if ($currentData->security_name !== $pendingSecurity->security_name) {
                    $changes['security_name'] = ['old' => $currentData->security_name, 'new' => $pendingSecurity->security_name];
                }
                if ($currentData->status !== $pendingSecurity->status) {
                    $changes['status'] = ['old' => $currentData->status, 'new' => $pendingSecurity->status];
                }
                
                // Compare fields if needed (omitted for brevity but recommended for full feature)
            }
        }

        return response()->json([
            'action_type' => $pendingSecurity->request_type,
            'pending_request' => $pendingSecurity,
            'old_data' => $currentData,
            'new_data' => [
                'security_name' => $pendingSecurity->security_name,
                'status' => $pendingSecurity->status,
                'fields_data' => $pendingSecurity->fields_data
            ],
            'changes' => $changes
        ]);
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/approve",
        operationId: "approveSecurityMaster",
        summary: "Approve Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "pendingSecurity", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Approved successfully")]
    )]
    public function approve(PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        try {
            $result = $this->securityMasterService->approveRequest($pendingSecurity);
            return response()->json([
                'message' => 'Request approved successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/admin/pending-security-master/{pendingSecurity}/reject",
        operationId: "rejectSecurityMaster",
        summary: "Reject Pending Security Master Request",
        tags: ["Security Master Approvals"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(required: ["reason"], properties: [new OA\Property(property: "reason", type: "string")])),
        responses: [new OA\Response(response: 200, description: "Rejected successfully")]
    )]
    public function reject(Request $request, PendingSecurityMasterData $pendingSecurity): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string']);
        
        try {
            $result = $this->securityMasterService->rejectRequest($pendingSecurity, $validated['reason']);
            return response()->json([
                'message' => 'Request rejected successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    #[OA\Post(
        path: "/api/v1/admin/security-master/bulk-upload",
        operationId: "bulkUploadSecurityMaster",
        summary: "Bulk Upload Security Master Records",
        description: "Upload an Excel/CSV file to create multiple security records",
        tags: ["Security Master Data"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "category_id", "created_by", "authoriser_id"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary"),
                        new OA\Property(property: "category_id", type: "integer"),
                        new OA\Property(property: "created_by", type: "integer"),
                        new OA\Property(property: "authoriser_id", type: "integer")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Upload processed successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function bulkUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'category_id' => 'required|exists:market_categories,id',
            'created_by' => 'required|integer',
            'authoriser_id' => 'required|integer',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\SecurityMasterImport(
                    $this->securityMasterService,
                    $request->category_id,
                    $request->product_id, // Pass product_id
                    $request->created_by,
                    $request->authoriser_id
                ),
                $request->file('file')
            );

            return response()->json([
                'message' => 'Bulk upload processed successfully. Valid records have been submitted for approval.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed for some records in the file.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
