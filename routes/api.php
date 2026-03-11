<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CupboardController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\BorrowController;
use App\Http\Controllers\Api\AuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes — Inventory Management System
| Base: /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /* ── Public ─────────────────────────────────────── */
    Route::post('/login', [AuthController::class, 'login']);

    /* ── Protected (requires Sanctum token) ─────────── */
    Route::middleware(['auth:sanctum', 'active.user'])->group(function () {

        // Auth
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::get('/me',       [AuthController::class, 'me']);

        // ── Admin only ────────────────────────────────
        Route::middleware('role:admin')->group(function () {

            // User management
            Route::apiResource('users', UserController::class);
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

            // Cupboard management
            Route::apiResource('cupboards', CupboardController::class);

            // Place management (nested under cupboard)
            Route::apiResource('cupboards.places', PlaceController::class)
                 ->shallow();

            // Borrow management — admin sees all
            Route::get('borrows',         [BorrowController::class, 'index']);
            Route::get('borrows/{borrow}', [BorrowController::class, 'show']);
            Route::patch('borrows/{borrow}/return', [BorrowController::class, 'processReturn']);

            // Audit log — admin only
            Route::get('audit-logs', [AuditLogController::class, 'index']);

            // Item management — admin CRUD
            Route::post('items',               [ItemController::class, 'store']);
            Route::put('items/{item}',         [ItemController::class, 'update']);
            Route::delete('items/{item}',      [ItemController::class, 'destroy']);
            Route::patch('items/{item}/status',[ItemController::class, 'updateStatus']);
            Route::patch('items/{item}/quantity',[ItemController::class, 'adjustQuantity']);
        });

        // ── Staff + Admin shared ──────────────────────

        // Inventory — both roles can browse
        Route::get('items',        [ItemController::class, 'index']);
        Route::get('items/{item}', [ItemController::class, 'show']);

        // Storage map — read only for staff
        Route::get('cupboards',              [CupboardController::class, 'index']);
        Route::get('cupboards/{cupboard}',   [CupboardController::class, 'show']);
        Route::get('places/{place}',         [PlaceController::class, 'show']);

        // Borrows — staff creates borrows
        Route::post('borrows',              [BorrowController::class, 'store']);

        // Staff sees their own processed borrows
        Route::get('my-borrows',            [BorrowController::class, 'myBorrows']);
    });
});
