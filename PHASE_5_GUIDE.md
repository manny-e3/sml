# 🎯 Phase 5: Security Master List Module - IMPLEMENTATION GUIDE

**Status:** 🔄 IN PROGRESS  
**Date:** December 25, 2024

---

## ✅ Completed So Far

### Models Updated
- ✅ **Security.php** - Complete with relationships, scopes, and calculation methods
- ✅ **MarketCategory.php** - With relationships
- ✅ **ProductType.php** - With relationships
- ✅ **SecurityController** - Resource controller created

---

## 📋 Phase 5 Remaining Tasks

### 5.1 Controllers (Priority: High)
- [ ] Complete SecurityController with:
  - `index()` - List all securities with DataTables
  - `create()` - Show create form
  - `store()` - Save new security (via maker-checker)
  - `show()` - View security details
  - `edit()` - Show edit form
  - `update()` - Update security (via maker-checker)
  - `destroy()` - Delete security (via maker-checker)
  - `export()` - Export to Excel/PDF
  - `import()` - Bulk import from Excel

### 5.2 Views (Priority: High)
- [ ] `securities/index.blade.php` - List view with DataTables
- [ ] `securities/create.blade.php` - Create form
- [ ] `securities/edit.blade.php` - Edit form
- [ ] `securities/show.blade.php` - Detail view
- [ ] `securities/_form.blade.php` - Shared form partial

### 5.3 Form Validation (Priority: High)
- [ ] Create `StoreSecurityRequest`
- [ ] Create `UpdateSecurityRequest`
- [ ] Validation rules for all fields
- [ ] Custom validation for ISIN format
- [ ] Date validation (issue_date < maturity_date)

### 5.4 Auto-Calculations (Priority: High)
- [ ] Tenor calculation (years between issue and maturity)
- [ ] TTM calculation (time to maturity)
- [ ] Effective coupon calculation
- [ ] Day count basis mapping
- [ ] Final rating concatenation

### 5.5 DataTables Integration (Priority: Medium)
- [ ] Server-side processing
- [ ] Search functionality
- [ ] Column sorting
- [ ] Export buttons (Excel, PDF, CSV)
- [ ] Filters (product type, issuer, status)

### 5.6 Excel Import/Export (Priority: Medium)
- [ ] Export to Excel with formatting
- [ ] Export to PDF
- [ ] Import from Excel with validation
- [ ] Bulk upload template
- [ ] Error reporting for failed imports

### 5.7 Routes (Priority: High)
```php
// In routes/web.php
Route::middleware(['auth', 'role:super_admin|inputter'])->group(function () {
    Route::resource('securities', SecurityController::class);
    Route::post('securities/import', [SecurityController::class, 'import'])->name('securities.import');
    Route::get('securities/export/excel', [SecurityController::class, 'exportExcel'])->name('securities.export.excel');
    Route::get('securities/export/pdf', [SecurityController::class, 'exportPdf'])->name('securities.export.pdf');
});
```

---

## 🎨 UI Components Needed

### Index Page Features
- Search bar
- Filter dropdowns (Product Type, Issuer, Status)
- DataTable with:
  - ISIN
  - Security Name
  - Issuer
  - Product Type
  - Issue Date
  - Maturity Date
  - Face Value
  - Status
  - Actions (View, Edit, Delete)
- Export buttons
- Import button
- Create New button

### Create/Edit Form Fields
**Basic Information:**
- Product Type (dropdown)
- ISIN (text, unique)
- Security Name (text)
- Issuer (text)
- Issuer Category (text)

**Dates:**
- Issue Date (date picker)
- Maturity Date (date picker)
- First Settlement Date (date picker)
- Last Trading Date (date picker)

**Financial Details:**
- Face Value (number)
- Issue Price (number)
- Coupon Rate (number, for bonds)
- Coupon Type (dropdown: Fixed, Floating, Zero)
- Coupon Frequency (dropdown: Annual, Semi-Annual, Quarterly)
- Discount Rate (number, for bills)

**Calculated Fields (Auto-filled):**
- Tenor (auto-calculated)
- Effective Coupon (auto-calculated)
- TTM (auto-calculated)
- Day Count Basis (auto-selected)

**Outstanding Values:**
- Outstanding Value (number)
- Amount Issued (number)
- Amount Outstanding (number)

**Rating Information:**
- Rating Agency (text)
- Local Rating (text)
- Global Rating (text)
- Final Rating (auto-concatenated)

**Additional:**
- Listing Status (dropdown: Listed, Unlisted)
- Status (dropdown: Active, Matured, Redeemed)
- Remarks (textarea)

---

## 🔧 Helper Functions Needed

### SecurityService.php
```php
class SecurityService
{
    public function calculateTenor($issueDate, $maturityDate)
    {
        // Calculate years between dates
    }
    
    public function calculateTTM($maturityDate)
    {
        // Calculate time to maturity from today
    }
    
    public function calculateEffectiveCoupon($couponRate, $couponType, $couponFrequency)
    {
        // Calculate effective coupon based on type and frequency
    }
    
    public function getDayCountBasis($productType)
    {
        // Map product type to day count convention
    }
    
    public function concatenateRating($ratingAgency, $localRating, $globalRating)
    {
        // Combine rating information
    }
    
    public function checkMaturityStatus($maturityDate)
    {
        // Check if security is matured
    }
}
```

---

## 📊 DataTables Configuration

```javascript
$('#securities-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route('securities.index') }}',
    columns: [
        { data: 'isin', name: 'isin' },
        { data: 'security_name', name: 'security_name' },
        { data: 'issuer', name: 'issuer' },
        { data: 'product_type', name: 'productType.name' },
        { data: 'issue_date', name: 'issue_date' },
        { data: 'maturity_date', name: 'maturity_date' },
        { data: 'face_value', name: 'face_value' },
        { data: 'status', name: 'status' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    buttons: ['excel', 'pdf', 'csv']
});
```

---

## 🔐 Maker-Checker Integration

### For Create Operation:
1. Inputter fills form
2. Selects Authoriser
3. Submits → Creates PendingAction record
4. Email sent to Authoriser
5. Authoriser reviews and approves/rejects
6. If approved → Security created
7. Email sent to Inputter

### For Update Operation:
1. Inputter edits security
2. Changes stored in PendingAction (old_data + new_data)
3. Selects Authoriser
4. Submits → Awaits approval
5. Authoriser reviews changes
6. If approved → Security updated
7. Notifications sent

### For Delete Operation:
1. Inputter requests deletion
2. PendingAction created with action_type='delete'
3. Authoriser reviews
4. If approved → Soft delete
5. Notifications sent

---

## 📝 Validation Rules

```php
'product_type_id' => 'required|exists:product_types,id',
'isin' => 'required|string|size:12|unique:securities,isin',
'security_name' => 'required|string|max:255',
'issuer' => 'required|string|max:255',
'issue_date' => 'required|date',
'maturity_date' => 'required|date|after:issue_date',
'face_value' => 'required|numeric|min:0',
'coupon_rate' => 'nullable|numeric|min:0|max:100',
'discount_rate' => 'nullable|numeric|min:0|max:100',
'status' => 'required|in:Active,Matured,Redeemed',
```

---

## 🎯 Next Steps to Complete Phase 5

1. **Implement SecurityController methods** (2-3 hours)
2. **Create all Blade views** (3-4 hours)
3. **Add Form Request validation** (1 hour)
4. **Integrate DataTables** (2 hours)
5. **Add Excel import/export** (2-3 hours)
6. **Implement auto-calculations** (1-2 hours)
7. **Add routes** (30 minutes)
8. **Testing** (2-3 hours)

**Total Estimated Time:** 14-18 hours

---

## 📦 Required Packages

Already installed:
- ✅ Maatwebsite Excel
- ✅ DomPDF
- ✅ DataTables.js
- ✅ Bootstrap 5

---

## 🎨 Sample Code Snippets

### SecurityController@index
```php
public function index(Request $request)
{
    if ($request->ajax()) {
        $securities = Security::with('productType', 'creator')
            ->select('securities.*');
        
        return DataTables::of($securities)
            ->addColumn('actions', function ($security) {
                return view('securities.partials.actions', compact('security'));
            })
            ->make(true);
    }
    
    $productTypes = ProductType::active()->get();
    return view('securities.index', compact('productTypes'));
}
```

### Auto-calculation on form
```javascript
$('#issue_date, #maturity_date').on('change', function() {
    let issueDate = new Date($('#issue_date').val());
    let maturityDate = new Date($('#maturity_date').val());
    
    if (issueDate && maturityDate) {
        let years = (maturityDate - issueDate) / (365.25 * 24 * 60 * 60 * 1000);
        $('#tenor').val(Math.round(years));
    }
});
```

---

## ✅ What's Already Done

- ✅ Database schema complete
- ✅ Models with relationships
- ✅ Calculation methods in Security model
- ✅ Scopes for filtering
- ✅ Audit trail setup
- ✅ Activity logging setup
- ✅ Resource controller created

---

## 📈 Progress

```
Phase 5 Progress: ████░░░░░░░░░░░░░░░░ 20%

Completed:
- Models ✅
- Database ✅
- Controller skeleton ✅

Remaining:
- Controller logic
- Views
- Validation
- DataTables
- Import/Export
- Routes
- Testing
```

---

**This guide provides the roadmap to complete Phase 5. Would you like me to continue implementing the remaining components?**
