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
}
