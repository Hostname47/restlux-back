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
            "description" => "nullable|stringe|max:2048",
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
}
