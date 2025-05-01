<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function view(Request $request) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $menus = Menu::all();

        return response()->json($menus);
    }

    public function create(Request $request) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug',
            'description' => 'nullable|string|max:2048',
            'type' => 'required|in:breakfast,lunch,dinner',
            'is_published' => 'required|boolean',
            'available_at' => 'nullable|date',
            'available_end_at' => 'nullable|date|after_or_equal:available_at',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['created_by'] = $request->user()->id;

        if ($request->filled('available_at')) {
            $data['available_at'] = Carbon::createFromFormat('d/m/Y', $request->available_at)->format('Y-m-d');
        }

        if ($request->filled('available_end_at')) {
            $data['available_end_at'] = Carbon::createFromFormat('d/m/Y', $request->available_end_at)->format('Y-m-d');
        }

        $menu = Menu::create(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $folderPath = "menus/{$menu->id}/images";
            $imagePath = $request->file('image')->storeAs($folderPath, $data['slug'] . '.png', 'public');

            $menu->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'Menu created successfully',
            'menu' => $menu
        ], 201);
    }

    public function update(Request $request, Menu $menu)
    {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => "sometimes|string|max:255|unique:menus,slug,{$menu->id}",
            'description' => 'nullable|string|max:2048',
            'type' => 'sometimes|in:breakfast,lunch,dinner',
            'is_published' => 'sometimes|boolean',
            'available_at' => 'nullable|date',
            'available_end_at' => 'nullable|date|after_or_equal:available_at',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->filled('available_at')) {
            $data['available_at'] = Carbon::createFromFormat('d/m/Y', $request->available_at)->format('Y-m-d');
        }

        if ($request->filled('available_end_at')) {
            $data['available_end_at'] = Carbon::createFromFormat('d/m/Y', $request->available_end_at)->format('Y-m-d');
        }

        $menu->update(collect($data)->except('image')->toArray());

        // Handle image if present
        if ($request->hasFile('image')) {
            if ($menu->image && Storage::disk('public')->exists($menu->image)) {
                Storage::disk('public')->delete($menu->image);
            }

            $imagePath = $request->file('image')->storeAs("menus/{$menu->id}/images", "{$menu->slug}.png", 'public');

            $menu->update(['image' => $imagePath]);
        }

        return response()->json([
            'message' => 'Menu updated successfully',
            'menu' => $menu
        ], 200);
    }

    public function destroy(Request $request, $id) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Menu::findOrFail($id);

        if ($product->image && \Storage::disk('public')->exists($product->image)) {
            \Storage::disk('public')->deleteDirectory("products/{$product->id}");
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}
