<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ThesisController;

// ==========================================
// PUBLIC ROUTES (No login required)
// ==========================================

// Authentication
Route::post('/login', [AuthController::class, 'login']);

// Archive Viewing & Searching (Students and Guests)
Route::get('/theses', [ThesisController::class, 'index']);
Route::get('/theses/{id}', [ThesisController::class, 'show']);


// ==========================================
// PROTECTED ROUTES (Requires Login Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // Get the currently logged-in user's profile data
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // Add this right above your Admin CRUD Operations for Theses
    Route::get('/authors', function () {
        return response()->json(\App\Models\Author::all(), 200);
    });

    // Admin CRUD Operations for Theses
    // (Your frontend will hide the buttons that trigger these routes if the user is a 'student')
    Route::post('/theses', [ThesisController::class, 'store']);
    Route::put('/theses/{id}', [ThesisController::class, 'update']);
    Route::delete('/theses/{id}', [ThesisController::class, 'destroy']);

    // Admin CRUD Operations for Users
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']); // <-- Add this new line!
    Route::delete('/users/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);

});
