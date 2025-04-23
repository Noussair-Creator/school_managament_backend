<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Controller Imports ---
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Note: Authentication is handled via Sanctum tokens (Bearer).
| Permissions are assumed to be handled within controller constructors or methods.
|
*/

// ========================================
// Public Routes (No Authentication Required)
// ========================================

// --- Authentication ---
Route::post('register', [AuthController::class, 'register'])->name('register'); // Student
Route::post('register/lab-manager', [AuthController::class, 'registerResponsable'])->name('register.labmanager');
Route::post('register/teacher', [AuthController::class, 'registerTeacher'])->name('register.teacher');
Route::post('login', [AuthController::class, 'login'])->name('login');

// --- Public Viewing (Optional - Enable if needed) ---
// Uncomment these if you need unauthenticated access to view these resources.
// Ensure controllers handle lack of auth if uncommented.
// Route::get('locations', [LocationController::class, 'indexPublic'])->name('public.locations.index'); // Use different controller methods if needed
// Route::get('locations/{location}', [LocationController::class, 'showPublic'])->name('public.locations.show');
// Route::get('materials', [MaterialController::class, 'indexPublic'])->name('public.materials.index');
// Route::get('materials/{material}', [MaterialController::class, 'showPublic'])->name('public.materials.show');
// Route::get('posts', [PostController::class, 'indexPublic'])->name('public.posts.index'); // Assuming separate public methods if needed
// Route::get('posts/{post}', [PostController::class, 'showPublic'])->name('public.posts.show');
// Route::get('posts/{post}/comments', [CommentController::class, 'indexPublic'])->name('public.comments.index');
// Route::get('documents', [DocumentController::class, 'indexPublic'])->name('public.documents.index');
// Route::get('documents/{document}', [DocumentController::class, 'showPublic'])->name('public.documents.show');


// ==================================================
// Authenticated Routes (Require 'auth:sanctum' Token)
// ==================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Authentication ---
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('user', [AuthController::class, 'user'])->name('user.show'); // Renamed route name slightly
    // --- User Profile (Self) ---
    Route::get('profile', [UserController::class, 'showProfile'])->name('profile.show');
    Route::post('profile', [UserController::class, 'updateProfile'])->name('profile.update'); // Use POST for updates with potential file uploads or PUT/PATCH otherwise
    Route::delete('profile', [UserController::class, 'deleteProfile'])->name('profile.delete');

    // --- Notification Routes ---
    Route::get('/notifications', [NotificationController::class, 'index']); // Get notifications
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']); // Mark all as read
    Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']); // Mark specific as read (use PATCH or POST)
    Route::delete('/notifications/{notificationId}', [NotificationController::class, 'destroy']); // Delete specific notification


    // --- User Management (Admin/Specific Roles) ---
    // Using apiResource for standard index/show/update/destroy if applicable
    // If only index/destroy are needed:
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    // Route::get('users/{user}', [UserController::class, 'show'])->name('users.show'); // Add if needed
    // Route::put('users/{user}', [UserController::class, 'update'])->name('users.update'); // Add if needed
    Route::delete('users/{user}', [UserController::class, 'deleteByAdmin'])->name('users.destroy');
    // Keep specific actions separate if they don't fit resource pattern
    Route::post('users/responsable', [UserController::class, 'createResponsable'])->name('users.responsable.store'); // More specific name

    // --- Roles & Permissions Management ---
    Route::apiResource('roles', RoleController::class); // index, store, show, update, destroy
    Route::get('roles/{role}/permissions', [RoleController::class, 'showPermissions'])->name('roles.permissions.index');
    Route::post('roles/{role}/permissions', [PermissionController::class, 'assignPermissionToRole'])->name('roles.permissions.assign'); // Changed path slightly for consistency
    Route::delete('roles/{role}/permissions', [PermissionController::class, 'revokePermissionFromRole'])->name('roles.permissions.revoke'); // Changed path slightly & method to DELETE

    Route::apiResource('permissions', PermissionController::class); // index, store, show, update, destroy

    // Assign/Revoke Roles for specific Users
    Route::post('users/{user}/roles', [RoleController::class, 'assignRole'])->name('users.roles.assign'); // Changed path slightly
    Route::delete('users/{user}/roles', [RoleController::class, 'revokeRole'])->name('users.roles.revoke'); // Changed path slightly & method to DELETE

    // --- Location Management ---
    // *** FIX: Use apiResource to include GET /locations ***
    // Assumes LocationController has index, store, show, update, destroy methods
    Route::apiResource('locations', LocationController::class);

    // --- Material Management ---
    // apiResource correctly defines GET/POST/PUT/DELETE/GET{id} for materials
    Route::apiResource('materials', MaterialController::class);

    // --- Reservations Management ---
    Route::post('locations/{location}/reservations', [ReservationsController::class, 'makeReservation'])->name('locations.reservations.store');
    Route::get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');
    // Route::get('reservations/{reservation}', [ReservationsController::class, 'show'])->name('reservations.show'); // Add if needed
    Route::put('reservations/{reservation}', [ReservationsController::class, 'updateReservation'])->name('reservations.update');
    Route::delete('reservations/{reservation}', [ReservationsController::class, 'cancelReservation'])->name('reservations.destroy');
    // Reservation Approval/Rejection
    Route::post('reservations/{reservation}/approve', [ReservationsController::class, 'approve'])->name('reservations.approve');
    Route::post('reservations/{reservation}/reject', [ReservationsController::class, 'reject'])->name('reservations.reject');

    // --- Documents Management ---
    Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::delete('documents/{document}', [DocumentController::class, 'delete'])->name('documents.destroy');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    // Missing routes for listing/viewing documents if needed within authenticated context?
    // Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    // Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

    // --- Posts Management ---
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
    // Route::get('posts', [PostController::class, 'index'])->name('posts.index'); // Add if needed
    // Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show'); // Add if needed
    Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [PostController::class, 'delete'])->name('posts.destroy');

    // --- Comments Management ---
    Route::post('posts/{post}/comments', [CommentController::class, 'addComment'])->name('comments.store');
    // Add routes for updating/deleting comments if needed
    // Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    // Route::delete('comments/{comment}', [CommentController::class, 'delete'])->name('comments.destroy');

}); // End of auth:sanctum middleware group
