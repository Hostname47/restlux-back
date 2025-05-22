<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PDO;

class ProductsController extends Controller
{
    public function view(Request $request) {
        $products = Product::paginate(14);

        return response()->json($products);
    }

    public function search(Request $request)
    {    
        $request->validate([
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
        ]);
    
        $query = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = 14;
    
        // Unique cache key based on search query and page
        $cacheKey = 'products_search_' . md5($query) . '_page_' . $page;
    
        // Use cache tags 'products' for easy invalidation
        $products = Cache::tags('products')->remember($cacheKey, now()->addMinutes(10), function () use ($query, $perPage) {
            return Product::when($query, function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('slug', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                      
                })
                ->paginate($perPage);
        });
    
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

    public function create_(Request $request) {
        $conn = new PDO("mysql:host=localhost:3306;dbname=restlux", "root", "");
    
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $price = (float) $_POST['price'];
        $stock = (int) $_POST['stock'];
        $category_id = (int) $_POST['category_id'];
        $is_available = 1;
    
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE slug = :slug");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            echo "Ce slug existe déjà. Veuillez en choisir un autre";
            exit;
        }
    
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            echo "Catégorie invalide.";
            exit;
        }

        $query = "INSERT INTO products (name, slug, description, price, is_available, stock, category_id)
                  VALUES (:name, :slug, :description, :price, :is_available, :stock, :category_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':is_available', $is_available);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':category_id', $category_id);
    
        if (!$stmt->execute()) {
            echo "Erreur lors de l'ajout du produit.";
            exit;
        }

        $productId = $conn->lastInsertId();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $folderPath = "products/$productId/images";
            $imagePath = $request->file('image')->storeAs($folderPath, "$slug.png", 'public');

            $updateStmt = $conn->prepare("UPDATE products SET image = :image WHERE id = :id");
            $updateStmt->bindParam(':image', $imagePath);
            $updateStmt->bindParam(':id', $productId);
            $updateStmt->execute();
        }
    
        echo "Produit ajouté avec succès!";
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

        // Clear cache, because this product can be cached
        Cache::tags('products')->flush();

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
