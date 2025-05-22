<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SecurityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Spatie\Permission\Models\Permission;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group( function () {
    Route::group(['middleware' => ['role:Product Manager']], function () {
        Route::get("/products", [ProductsController::class, 'view']);
        Route::get("/products/search", [ProductsController::class, "search"]);
        Route::post("/products", [ProductsController::class, 'create']);
        Route::post("/products/native-php", [ProductsController::class, 'create_']);
        Route::post("/products/{id}", [ProductsController::class, 'update']);
        Route::delete("/products/{id}", [ProductsController::class, 'destroy']);

        Route::get("/categories", [CategoryController::class, 'view']);
        Route::post("/categories", [CategoryController::class, 'create']);
        Route::post("/categories/{id}", [CategoryController::class, 'update']);
        Route::delete("/categories/{id}", [CategoryController::class, 'destroy']);

        Route::get("/menus", [MenuController::class, 'view']);
        Route::post("/menus", [MenuController::class, 'create']);
        Route::post("/menus/add-products", [MenuController::class, 'addProducts']);
        Route::post("/menus/remove-products", [MenuController::class, 'removeProducts']);
        Route::post("/menus/{menu}", [MenuController::class, 'update']);
        Route::delete("/menus/{menu}", [MenuController::class, 'destroy']);
        // Job of the director
        Route::post("/permissions/grant", [SecurityController::class, 'grant_permission']);
        Route::post("/permissions/revoke", [SecurityController::class, 'revoke_permission']);
        Route::post("/roles/grant", [SecurityController::class, 'grant_role']);
        Route::post("/roles/revoke", [SecurityController::class, 'revoke_role']);
        Route::post("/employees/create", [SecurityController::class, 'create_employee_with_roles']);
    });
    
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user->load(['roles', 'permissions']);
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});
