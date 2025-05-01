<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function view(Request $request) {
        if (!$request->user()->can('View Products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $products = Product::paginate(14);

        return response()->json($products);
    }

    public function create(Request $request) {
        if (!$request->user()->can('Create Products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        
        $data = $request->validate([
            "name" => "required|string|max:255",
            "slug" => "required|string|max:255|unique:products,slug",
            "description" => "nullable|string|max:2048",
            "price" => "required|numeric|min:0",
            "image" => "nullable|image|mimes:jpg,jpeg,png,webp|max:2048",
            "is_available" => "required|boolean",
            "stock" => "required|integer|min:0",
            "category_id" => "required|exists:categories,id",
        ]);

        $product = Product::create(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $folderPath = "products/{$product->id}/images";
            $imagePath = $request->file('image')->storeAs($folderPath, $data['slug'].'.png', 'public');

            $product->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    public function update(Request $request, $id) {
        // Check permission
        if (!$request->user()->can('Edit Products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($id);

        $data = $request->validate([
            "name" => "sometimes|string|max:255",
            "slug" => "sometimes|string|max:255|unique:products,slug,".$product->id,
            "description" => "sometimes|nullable|string|max:2048",
            "price" => "sometimes|numeric|min:0",
            "image" => "sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048",
            "is_available" => "sometimes|boolean",
            "stock" => "sometimes|integer|min:0",
            "category_id" => "sometimes|exists:categories,id",
        ]);

        $product->update(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $slug = $data['slug'] ?? $product->slug;

            $folderPath = "products/{$product->id}/images";
            $imagePath = $request->file('image')->storeAs($folderPath, "$slug.png", 'public');

            $product->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function destroy(Request $request, $id) {
        if (!$request->user()->can('Delete Products')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($id);

        if ($product->image && \Storage::disk('public')->exists($product->image)) {
            \Storage::disk('public')->deleteDirectory("products/{$product->id}");
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
