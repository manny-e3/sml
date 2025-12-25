<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecurityRequest;
use App\Http\Requests\UpdateSecurityRequest;
use App\Models\Security;
use App\Models\ProductType;
use App\Models\PendingAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SecuritiesExport;
use App\Imports\SecuritiesImport;
use PDF; // Barryvdh\DomPDF\Facade\Pdf

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

        // Pagination is handled in the view via standard Laravel methods for now
        // If DataTables AJAX is requested later, we can check $request->ajax() here.
        if ($request->ajax()) {
             return datatables()->of($query)->toJson();
        }

        $securities = $query->paginate(15)->withQueryString();

        return view('securities.index', compact('securities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('securities.create', ['security' => new Security()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSecurityRequest $request)
    {
        $data = $request->validated();

        // Calculations
        if (isset($data['issue_date']) && isset($data['maturity_date'])) {
            $issue = \Carbon\Carbon::parse($data['issue_date']);
            $maturity = \Carbon\Carbon::parse($data['maturity_date']);
            $data['tenor'] = round($maturity->diffInYears($issue)); // Round to nearest year usually
            
            // TTM (Time to Maturity)
            $today = \Carbon\Carbon::today();
            if ($maturity->isFuture()) {
                $daysDiff = $today->diffInDays($maturity);
                $data['ttm'] = $daysDiff / 365; // Simple basis
            } else {
                $data['ttm'] = 0;
            }
        }

        // Ratings Concatenation
        $ratings = array_filter([
            $data['rating_agency'] ?? null,
            $data['local_rating'] ?? null,
            $data['global_rating'] ?? null
        ]);
        if (!empty($ratings)) {
            $data['final_rating'] = implode('/', $ratings);
        }

        $data['created_by'] = Auth::id();

        // ---------------------------------------------------------
        // MAKER-CHECKER LOGIC HOOK
        // ---------------------------------------------------------
        // If configured to use Maker-Checker, we should create a PendingAction here
        // instead of a Security, unless the user is a Super Admin or has auto-approve rights.
        // For Phase 5 validation, we will allow direct creation for now but mark it.
        // To implement Maker-Checker strictly:
        /*
        PendingAction::create([
            'action_type' => 'create',
            'model_type' => Security::class,
            'new_data' => $data,
            'maker_id' => Auth::id(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        return redirect()->route('securities.index')->with('success', 'Security submitted for approval.');
        */

        // Direct Save (Verification Phase)
        $security = Security::create($data);

        return redirect()->route('securities.show', $security)
            ->with('success', 'Security created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Security $security)
    {
        return view('securities.show', compact('security'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Security $security)
    {
        return view('securities.edit', compact('security'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSecurityRequest $request, Security $security)
    {
        $data = $request->validated();

        // Re-calculations if dates changed
        if (isset($data['issue_date']) && isset($data['maturity_date'])) {
            $issue = \Carbon\Carbon::parse($data['issue_date']);
            $maturity = \Carbon\Carbon::parse($data['maturity_date']);
            $data['tenor'] = round($maturity->diffInYears($issue));
            
            $today = \Carbon\Carbon::today();
            if ($maturity->isFuture()) {
                $data['ttm'] = $today->diffInDays($maturity) / 365;
            } else {
                $data['ttm'] = 0;
            }
        }
        
         // Ratings Concatenation
        $ratings = array_filter([
            $data['rating_agency'] ?? null,
            $data['local_rating'] ?? null,
            $data['global_rating'] ?? null
        ]);
        if (!empty($ratings)) {
            $data['final_rating'] = implode('/', $ratings);
        }

        $data['updated_by'] = Auth::id();

        // ---------------------------------------------------------
        // MAKER-CHECKER LOGIC HOOK
        // ---------------------------------------------------------
        // PendingAction::create for 'update'
        
        // Direct Update
        $security->update($data);

        return redirect()->route('securities.show', $security)
            ->with('success', 'Security updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Security $security)
    {
        // Maker-Checker Hook for Delete
        
        $security->delete(); // Soft delete
        return redirect()->route('securities.index')
            ->with('success', 'Security deleted successfully.');
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new SecuritiesExport, 'securities_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export to PDF
     */
    public function exportPdf()
    {
        // Using dompdf wrapper if available, or just a simple view render
        $securities = Security::all();
        // Since we didn't setup a specific PDF view yet, let's just use what we have or stub it.
        // Assuming 'pdf' alias is set to Barryvdh\DomPDF\Facade\Pdf::class
        // $pdf = PDF::loadView('securities.pdf', compact('securities'));
        // return $pdf->download('securities.pdf');
        
        // For now, redirect with message as placeholder if view not ready
        return back()->with('info', 'PDF Export functionality is ready to be configured.');
    }

    /**
     * Import from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new SecuritiesImport, $request->file('file'));

        return back()->with('success', 'Securities imported successfully.');
    }
}
