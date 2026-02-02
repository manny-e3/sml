<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSecurityRequest;
use App\Http\Requests\UpdateSecurityRequest;
use App\Models\Security;
use App\Models\PendingAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SecuritiesExport;
use App\Imports\SecuritiesImport;

class SecurityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Security::with(['productType', 'creator']);

        // Filters
        if ($request->filled('product_type')) {
            $query->where('product_type_id', $request->product_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issuer')) {
            $query->where('issuer', 'like', '%' . $request->issuer . '%');
        }

        // Sort by latest by default
        $query->latest();

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSecurityRequest $request)
    {
        $data = $request->validated();
        
        // Add required fields for approval workflow
        $data['requested_by'] = Auth::id();
        
        // For now, auto-assign first admin as authoriser (you can modify this logic)
        $authoriser = \App\Models\User::role('admin')->first();
        if (!$authoriser) {
            return response()->json([
                'message' => 'No authoriser available. Please contact administrator.'
            ], 400);
        }
        $data['authoriser_id'] = $authoriser->id;

        try {
            // Use SecurityService to create request
            $securityService = app(\App\Services\SecurityService::class);
            $pending = $securityService->createRequest($data);
            
            return response()->json([
                'message' => 'Security creation request submitted for approval.',
                'data' => $pending,
                'status' => 'pending'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create security request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Security $security)
    {
        return response()->json($security->load('productType', 'creator'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSecurityRequest $request, Security $security)
    {
        $data = $request->validated();
        
        // Re-calculate logic if dates changed
        if (isset($data['issue_date']) || isset($data['maturity_date'])) {
            $issueDate = \Carbon\Carbon::parse($data['issue_date'] ?? $security->issue_date);
            $maturityDate = \Carbon\Carbon::parse($data['maturity_date'] ?? $security->maturity_date);
            $data['tenor'] = $maturityDate->diffInYears($issueDate);
            $data['ttm'] = $maturityDate->diffInYears(now());
        }
        
        // Re-calculate Final Rating
        $data['final_rating'] = ($data['rating_agency'] ?? $security->rating_agency) . '/' . 
                                ($data['local_rating'] ?? $security->local_rating) . '/' . 
                                ($data['global_rating'] ?? $security->global_rating);

        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'update',
                'model_type' => Security::class,
                'model_id' => $security->id,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Security update submitted for approval.',
                'status' => 'pending'
            ], 202);
        }

        $security->update($data);

        return response()->json([
            'message' => 'Security updated successfully.',
            'data' => $security
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Security $security)
    {
        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'delete',
                'model_type' => Security::class,
                'model_id' => $security->id,
                'data' => [],
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Security deletion submitted for approval.',
                'status' => 'pending'
            ], 202);
        }

        $security->delete();
        return response()->json([
            'message' => 'Security deleted successfully.'
        ]);
    }

    /**
     * Import from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new SecuritiesImport, $request->file('file'));
            return response()->json(['message' => 'Securities imported successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
