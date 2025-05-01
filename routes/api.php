<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group( function () {
    Route::group(['middleware' => ['role:Product Manager']], function () {
        Route::get("/products", [ProductsController::class, 'view']);
        Route::post("/products", [ProductsController::class, 'create']);
        Route::post("/products/{id}", [ProductsController::class, 'update']);
        Route::delete("/products/{id}", [ProductsController::class, 'destroy']);

        Route::get("/categories", [CategoryController::class, 'view']);
        Route::post("/categories", [CategoryController::class, 'create']);
        Route::post("/categories/{id}", [CategoryController::class, 'update']);
        Route::delete("/categories/{id}", [CategoryController::class, 'destroy']);
    });
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});
