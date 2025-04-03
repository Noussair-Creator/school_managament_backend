<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes (No Authentication Required)
Route::post('register', [AuthController::class, 'register']);  // Register user
Route::post('login', [AuthController::class, 'login']);  // Login user

// Routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Count Number Of Logged-in Users
    Route::get('/logged-in-users', [AuthController::class, 'countLoggedInUsers']);

    // ----------------------------------------
    // Permissions & Role Management
    // ----------------------------------------
    Route::middleware('role.permission:create role')->post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::middleware('role.permission:all roles')->get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware('role.permission:show role')->get('roles/{roleId}', [RoleController::class, 'show'])->name('roles.show');
    Route::middleware('role.permission:update role')->put('roles/{roleId}', [RoleController::class, 'update'])->name('roles.update');
    Route::middleware('role.permission:delete role')->delete('roles/{roleId}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::middleware('role.permission:assign role')->post('users/{userId}/assign-role', [RoleController::class, 'assignRole'])->name('roles.assign');
    Route::middleware('role.permission:remove role')->post('users/{userId}/revoke-role', [RoleController::class, 'revokeRole'])->name('roles.revoke');

    // Assign & Remove Permissions to Roles
    Route::middleware('role.permission:give permissions')->post('roles/{roleId}/assign-permission', [PermissionController::class, 'assignPermissionToRole'])->name('permissions.assign');
    Route::middleware('role.permission:remove permissions')->post('roles/{roleId}/revoke-permission', [PermissionController::class, 'revokePermissionFromRole'])->name('permissions.revoke');

    // Permissions Management
    Route::middleware('role.permission:show permission')->get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::middleware('role.permission:create permission')->post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::middleware('role.permission:update permission')->put('permissions/{permissionId}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::middleware('role.permission:delete permission')->delete('permissions/{permissionId}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // ----------------------------------------
    // User Management
    // ----------------------------------------
    Route::middleware('role.permission:all users')->get('/users', [UserController::class, 'index'])->name('users.index');
    Route::middleware('role.permission:create user')->post('/users', [UserController::class, 'storeByAdmin'])->name('users.store');
    Route::middleware('role.permission:show user')->get('/users/{userId}', [UserController::class, 'show'])->name('users.show');
    Route::middleware('role.permission:update user')->put('/users/{userId}', [UserController::class, 'update'])->name('users.update');
    Route::middleware('role.permission:delete user')->delete('/users/{userId}', [UserController::class, 'deleteByAdmin'])->name('users.destroy');

    // ----------------------------------------
    // Classroom Management
    // ----------------------------------------
    Route::middleware('role.permission:create classroom')->post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::middleware('role.permission:update classroom')->put('classrooms/{classroomId}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::middleware('role.permission:delete classroom')->delete('classrooms/{classroomId}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');

    // ----------------------------------------
    // Reservations Management
    // ----------------------------------------
    Route::middleware('role.permission:create reservation')->post('classrooms/{classroomId}/make-reservation', [ReservationsController::class, 'makeReservation'])->name('reservations.store');
    Route::middleware('role.permission:update reservation')->put('reservations/{reservationId}', [ReservationsController::class, 'updateReservation'])->name('reservations.update');
    Route::middleware('role.permission:delete reservation')->delete('reservations/{reservationId}/cancel', [ReservationsController::class, 'cancelReservation'])->name('reservations.cancel');
    Route::middleware('role.permission:show reservation')->get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');
    Route::middleware('role.permission:show reservation')->get('reservations/{reservationId}', [ReservationsController::class, 'show'])->name('reservations.show');

    // ----------------------------------------
    // Documents Management
    // ----------------------------------------
    Route::middleware('role.permission:create document')->post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::middleware('role.permission:delete document')->delete('documents/{documentId}', [DocumentController::class, 'delete'])->name('documents.destroy');
    Route::middleware('role.permission:show document')->get('documents/{documentId}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::middleware('role.permission:show document')->get('documents/{documentId}', [DocumentController::class, 'show'])->name('documents.show');
    Route::middleware('role.permission:show document')->get('documents', [DocumentController::class, 'index'])->name('documents.index');

    // ----------------------------------------
    // Posts & Comments Management
    // ----------------------------------------
    Route::middleware('role.permission:create post')->post('posts', [PostController::class, 'store'])->name('posts.store');
    Route::middleware('role.permission:update post')->put('posts/{postId}', [PostController::class, 'update'])->name('posts.update');
    Route::middleware('role.permission:delete post')->delete('posts/{postId}', [PostController::class, 'delete'])->name('posts.destroy');
    Route::middleware('role.permission:show post')->get('posts/{postId}', [PostController::class, 'show'])->name('posts.show');
    Route::middleware('role.permission:show post')->get('posts', [PostController::class, 'index'])->name('posts.index');


    // Comments Management

    Route::middleware('role.permission:show comment')->get('posts/{postId}/comments', [CommentController::class, 'showComments'])->name('comments.index');
    Route::middleware('role.permission:create comment')->post('posts/{postId}/comments', [CommentController::class, 'addComment'])->name('comments.store');
    // ----------------------------------------
    // User Profile Management
    // ----------------------------------------
    // User Profile Management
    Route::middleware('role.permission:show profile')->get('/profile', [UserController::class, 'profile'])->name('profile.show');
    Route::middleware('role.permission:update profile')->put('/profile', [UserController::class, 'update'])->name('profile.update');
    Route::middleware('role.permission:delete profile')->delete('/profile', [UserController::class, 'delete'])->name('profile.destroy');
});

// Public Route for Viewing Classrooms
Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');

// Public Route for Viewing Posts

Route::get('posts', [PostController::class, 'index'])->name('posts.index');
Route::get('posts/{postId}', [PostController::class, 'show'])->name('posts.show');
Route::get('posts/{postId}/comments', [CommentController::class, 'showComments'])->name('comments.index');


// Public Route for Viewing Documents
Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('documents/{documentId}', [DocumentController::class, 'show'])->name('documents.show');


// Public Route for Viewing Reservations
Route::get('classrooms/{classroomId}/reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');

