<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Checkout Pro return routes (production fallback when frontend routes are not ready).
Route::get('/checkout/success', function (Request $request) {
    return response()->json([
        'message' => __('Checkout finished with success redirect.'),
        'status' => 'success',
        'query' => $request->query(),
    ]);
});

Route::get('/checkout/failure', function (Request $request) {
    return response()->json([
        'message' => __('Checkout finished with failure redirect.'),
        'status' => 'failure',
        'query' => $request->query(),
    ], 400);
});

Route::get('/checkout/pending', function (Request $request) {
    return response()->json([
        'message' => __('Checkout finished with pending redirect.'),
        'status' => 'pending',
        'query' => $request->query(),
    ], 202);
});
