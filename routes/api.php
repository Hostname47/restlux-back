<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function(Request $request) {
    dd("Hello from test");
    dd("Another");
});

Route::get('/user', function (Request $request) {
    print "smth";
    return $request->user();
})->middleware('auth:sanctum');
