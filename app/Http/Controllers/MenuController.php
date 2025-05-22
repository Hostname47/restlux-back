<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;

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

    public function create_(Request $request) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $conn = new PDO("mysql:host=localhost:3306;dbname=restlux", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $available_at = !empty($_POST['available_at']) ? $_POST['available_at'] : null;
        $available_end_at = !empty($_POST['available_end_at']) ? $_POST['available_end_at'] : null;
        $created_by = $request->user()->id;
    
        $stmt = $conn->prepare("SELECT COUNT(*) FROM menus WHERE slug = :slug");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            echo "Ce slug existe déjà. Veuillez en choisir un autre.";
            exit;
        }

        $query = "INSERT INTO menus (name, slug, description, type, is_published, available_at, available_end_at, created_by)
                  VALUES (:name, :slug, :description, :type, :is_published, :available_at, :available_end_at, :created_by)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':is_published', $is_published, PDO::PARAM_BOOL);
        $stmt->bindParam(':available_at', $available_at);
        $stmt->bindParam(':available_end_at', $available_end_at);
        $stmt->bindParam(':created_by', $created_by);
    
        if (!$stmt->execute()) {
            echo "Erreur lors de l'ajout du menu.";
            exit;
        }
    
        $menuId = $conn->lastInsertId();
        $imagePath = null;
    
        if ($request->hasFile('image')) {
            $folderPath = "menus/$menuId/images";
            $imagePath = $request->file('image')->storeAs($folderPath, "$slug.png", 'public');
    
            $updateStmt = $conn->prepare("UPDATE menus SET image = :image WHERE id = :id");
            $updateStmt->bindParam(':image', $imagePath);
            $updateStmt->bindParam(':id', $menuId);
            $updateStmt->execute();
        }
    
        echo "Menu ajouté avec succès!";
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

    public function addProducts(Request $request) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
        ]);

        $menu = Menu::findOrFail($data['menu_id']);

        // Attach the products to the menu
        $menu->products()->syncWithoutDetaching($data['products']);

        return response()->json([
            'message' => 'Products added to menu successfully.',
            'menu' => $menu->load('products'), // optional: load related products
        ]);
    }

    public function removeProducts(Request $request) {
        if (!$request->user()->can('Manage Menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'products' => 'required|array',
            'products.*' => 'exists:products,id',
        ]);

        $menu = Menu::findOrFail($data['menu_id']);

        // Detach the specified products
        $menu->products()->detach($data['products']);

        return response()->json([
            'message' => 'Products removed from menu successfully.',
            'menu' => $menu->load('products'), // optional
        ]);
    }
}
