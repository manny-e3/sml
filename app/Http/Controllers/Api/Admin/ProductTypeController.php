<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index()
    {
        return response()->json(ProductType::withCount('securities')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types',
            'description' => 'nullable|string',
        ]);

        $productType = ProductType::create($validated);

        return response()->json([
            'message' => 'Product Type created successfully.',
            'data' => $productType
        ], 201);
    }

    public function show(ProductType $productType)
    {
        return response()->json($productType);
    }

    public function update(Request $request, ProductType $productType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types,name,' . $productType->id,
            'description' => 'nullable|string',
        ]);

        $productType->update($validated);

        return response()->json([
            'message' => 'Product Type updated successfully.',
            'data' => $productType
        ]);
    }

    public function destroy(ProductType $productType)
    {
        if ($productType->securities()->count() > 0) {
            return response()->json(['message' => 'Cannot delete Product Type with existing securities.'], 422);
        }

        $productType->delete();

        return response()->json(['message' => 'Product Type deleted successfully.']);
    }
}
