<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function view(Request $request) {
        if (!$request->user()->can('Manage Categories')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $categories = Category::all();

        return response()->json($categories);
    }

    public function create(Request $request) {
        if (!$request->user()->can('Manage Categories')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            "name" => "required|string|max:255",
            "slug" => "required|string|max:255|unique:categories,slug",
            "description" => "nullable|string|max:2048",
            "image" => "nullable|image|mimes:jpg,jpeg,png,webp|max:2048",
        ]);

        $category = Category::create(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $folderPath = "categories/{$category->id}/images";
            $imagePath = $request->file('image')->storeAs($folderPath, $data['slug'].'.png', 'public');

            $category->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    public function update(Request $request, $id) {
        // Check permission
        if (!$request->user()->can('Manage Categories')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $category = Category::findOrFail($id);

        $data = $request->validate([
            "name" => "sometimes|string|max:255",
            "slug" => "sometimes|string|max:255|unique:categories,slug,$category->id",
            "description" => "sometimes|nullable|string|max:2048",
            "image" => "sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048",
        ]);

        $category->update(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $slug = $data['slug'] ?? $category->slug;

            $folderPath = "categories/{$category->id}/images";
            $imagePath = $request->file('image')->storeAs($folderPath, "$slug.png", 'public');

            $category->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'category updated successfully',
            'category' => $category
        ]);
    }

    public function destroy(Request $request, $id) {
        if (!$request->user()->can('Manage Categories')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $category = Category::findOrFail($id);

        if ($category->image && \Storage::disk('public')->exists($category->image)) {
            \Storage::disk('public')->deleteDirectory("categories/{$category->id}");
        }

        $category->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
