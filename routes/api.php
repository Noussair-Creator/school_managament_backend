<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Controller Imports ---
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MaterialController; // <-- Added MaterialController

/*
|--------------------------------------------------------------------------
| API Routes - Public
|--------------------------------------------------------------------------
*/

// Authentication
Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('login', [AuthController::class, 'login'])->name('login');

// Public Viewing: Locations
Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');

// Public Viewing: Materials (Optional - uncomment if needed)
// Route::get('materials', [MaterialController::class, 'index'])->name('materials.index.public');
// Route::get('materials/{material}', [MaterialController::class, 'show'])->name('materials.show.public');

// Public Viewing: Posts & Comments
Route::get('posts', [PostController::class, 'index'])->name('posts.index.public');
Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show.public');
Route::get('posts/{post}/comments', [CommentController::class, 'showComments'])->name('comments.index.public');

// Public Viewing: Documents
Route::get('documents', [DocumentController::class, 'index'])->name('documents.index.public');
Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show.public');

// Optional Public Viewing: Reservations for a Location
// Route::get('locations/{location}/reservations', [ReservationsController::class, 'listPublicReservationsForLocation'])->name('locations.reservations.index.public');

/*
|--------------------------------------------------------------------------
| API Routes - Authenticated (via Sanctum)
|--------------------------------------------------------------------------
| Permissions are handled within each controller's __construct method.
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- Authentication ---
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logged-in-users', [AuthController::class, 'countLoggedInUsers'])->name('auth.logged-in.count');

    // --- Role Management ---
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'showPermissions'])->name('roles.permissions');

    // --- Role Permissions ---
    Route::post('roles/{role}/assign-permission', [PermissionController::class, 'assignPermissionToRole'])->name('permissions.assign.role');
    Route::post('roles/{role}/revoke-permission', [PermissionController::class, 'revokePermissionFromRole'])->name('permissions.revoke.role');

    // --- User Roles ---
    Route::post('users/{user}/assign-role', [RoleController::class, 'assignRole'])->name('roles.assign.user');
    Route::post('users/{user}/revoke-role', [RoleController::class, 'revokeRole'])->name('roles.revoke.user');

    // --- Permissions Management ---
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // --- User Management ---
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/responsable', [UserController::class, 'createResponsable'])->name('responsable.store');
    Route::delete('/users/{user}', [UserController::class, 'deleteByAdmin'])->name('users.destroy');

    // --- Profile Management (Self) ---
    Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/profile', [UserController::class, 'deleteProfile'])->name('profile.delete');

    // --- Location Management ---
    // Permissions handled in LocationController constructor
    Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
    Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    // --- Material Management --- // <-- Added Section
    // Permissions (e.g., 'manage materials') handled in MaterialController constructor
    // Provides routes for index, store, show, update, destroy
    Route::apiResource('materials', MaterialController::class);

    // --- Reservations Management ---
    // Permissions handled in ReservationsController constructor
    Route::post('/locations/{location}/reservations', [ReservationsController::class, 'makeReservation'])->name('locations.reservations.store');
    Route::get('/reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');
    Route::put('/reservations/{reservation}', [ReservationsController::class, 'updateReservation'])->name('reservations.update');
    Route::delete('/reservations/{reservation}', [ReservationsController::class, 'cancelReservation'])->name('reservations.destroy'); // Note: This now cancels, doesn't delete row

    // --- Reservation Approval/Rejection --- // <-- Added Section
    // Permissions ('approve reservation') handled in ReservationsController constructor
    Route::post('/reservations/{reservation}/approve', [ReservationsController::class, 'approve'])
        ->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject', [ReservationsController::class, 'reject'])
        ->name('reservations.reject');

    // --- Documents Management ---
    // Permissions handled in DocumentController constructor
    Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::delete('documents/{document}', [DocumentController::class, 'delete'])->name('documents.destroy');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // --- Posts Management ---
    // Permissions handled in PostController constructor
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'delete'])->name('posts.destroy');

    // --- Comments Management ---
    // Permissions handled in CommentController constructor
    Route::post('posts/{post}/comments', [CommentController::class, 'addComment'])->name('comments.store');
});

// Ensure removed sections are kept out or deleted
// /* ... REMOVED/REPLACED SECTIONS ... */
