<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LocalController;
use App\Http\Controllers\OrganizerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\OrganizerOnly;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartCheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\OrganizerRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserTicketController;
use App\Http\Controllers\RefundController;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/recover-password', [PasswordRecoveryController::class, 'sendPasswordResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

Route::post('/organizer-requests', [OrganizerRequestController::class, 'store']);

Route::any('/webhooks/mercado-pago', MercadoPagoWebhookController::class);

// Backend-only fallback pages for Checkout Pro redirects.
//TODO change this to real routes in the frontend
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

Route::middleware('auth:sanctum')->group(function () {
    // Admin routes
    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::get('/admins', [AdminController::class, 'index']);
        Route::post('/admins', [AdminController::class, 'store']);
        Route::patch('/admins/{user}', [AdminController::class, 'promote']);
        Route::delete('/admins/{user}', [AdminController::class, 'demote']);

        Route::get('/organizers', [OrganizerController::class, 'index']);
        Route::post('/organizers', [OrganizerController::class, 'store']);
        Route::patch('/organizers/{user}/promote', [OrganizerController::class, 'promote']);
        Route::patch('/organizers/{user}/demote', [OrganizerController::class, 'demote']);

        Route::get('/organizer-requests', [OrganizerRequestController::class, 'index']);
        Route::get('/organizer-requests/{organizerRequest}', [OrganizerRequestController::class, 'show']);
        Route::patch('/organizer-requests/{organizerRequest}/approve', [OrganizerRequestController::class, 'approve']);
        Route::patch('/organizer-requests/{organizerRequest}/reject', [OrganizerRequestController::class, 'reject']);

        Route::post('/refunds', [RefundController::class, 'store']);
    }); 

    // Organizer routes
    Route::middleware(OrganizerOnly::class)->group(function () {
        Route::apiResource('/events', EventController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('/locals', LocalController::class)->only(['store']);
        Route::apiResource('/event-categories', EventCategoryController::class)->only(['store']);
    });
    
    // Normal user routes
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/user', [AuthController::class, 'destroyAccount']);

    // Tickets (authenticated user's tickets)
    Route::get('/tickets', [UserTicketController::class, 'index']);

    // Cart (authenticated user's own cart)
    Route::apiResource('/cart', CartController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['cart' => 'cartItem']);

    Route::post('/cart/checkout', CartCheckoutController::class);
});


Route::middleware(['auth:sanctum', OrganizerOnly::class])->group(function (){
    Route::apiResource('staffs', StaffController::class);
});

Route::post('/tickets/validate', [UserTicketController::class, 'validateTicket']);