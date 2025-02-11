<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ----------------------------------------
// Auth Routes: User Registration & Login
// ----------------------------------------
Route::post('register', [AuthController::class, 'register'])->name('auth.register');  // Register user
Route::post('login', [AuthController::class, 'login'])->name('auth.login');  // Login user

// -----------------------------------------------------------
// Authenticated Routes: Requires 'auth:sanctum'
// -----------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');  // Logout user

    // ----------------------------------------
    // User Routes: For Superadmins Only
    // ----------------------------------------
    Route::middleware('role.permission:superadmin|admin')->group(function () {
        // Manage users (view, create, update, delete)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{userId}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users', [UserController::class, 'storeByAdmin'])->name('users.store'); // Only superadmin can create users
        Route::put('/users/{userId}', [UserController::class, 'updateByAdmin'])->name('users.update');
        Route::delete('/users/{userId}', [UserController::class, 'deleteByAdmin'])->name('users.destroy');

        // Role Routes
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{roleId}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('roles/{roleId}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{roleId}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Assign and revoke roles to users
        Route::post('users/{userId}/assign-role', [RoleController::class, 'assignRole'])->name('roles.assign');
        Route::post('users/{userId}/remove-role', [RoleController::class, 'revokeRole'])->name('roles.remove');

        // Permissions Routes
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('permissions/{permissionId}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::put('permissions/{permissionId}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permissionId}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Assign and revoke permissions to roles
        Route::post('roles/{roleId}/assign-permission', [PermissionController::class, 'assignPermissionToRole'])->name('permissions.assign');
        Route::post('roles/{roleId}/revoke-permission', [PermissionController::class, 'revokePermissionFromRole'])->name('permissions.revoke');
        Route::get('/roles/{roleId}/permissions', [RoleController::class, 'showPermissions'])->name('roles.permissions');

        // Classroom Routes (Create, Update, Delete)
        Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store'); // Create classroom
        Route::put('classrooms/{classroomId}', [ClassroomController::class, 'update'])->name('classrooms.update'); // Update classroom
        Route::delete('classrooms/{classroomId}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy'); // Delete classroom
    });

    // ----------------------------------------
    // Reservation Routes (Accessible to Teachers, Guest, Superadmins, and Admins)
    // ----------------------------------------
    Route::middleware('role.permission:teacher|superadmin|admin')->group(function () {
        Route::post('classrooms/{classroomId}/make-reservation', [ReservationsController::class, 'reserve'])->name('reservations.store');  // Reserve classroom
        // Route::delete('classrooms/{classroomId}/unreserve', [ReservationsController::class, 'unreserve'])->name('reservations.destroy');  // Unreserve classroom
        Route::put('reservations/{reservationId}', [ReservationsController::class, 'updateReservation'])->name('reservations.update');   // Update reservation
        Route::get('reservations/{reservationId}', [ReservationsController::class, 'show'])->name('reservations.show'); // Show specific reservation
        Route::get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');  // List all reservations
        Route::delete('reservations/{reservationId}/cancel', [ReservationsController::class, 'cancelReservation'])->name('reservations.cancel');  // List all reservations

        Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
        Route::delete('documents/{id}', [DocumentController::class, 'delete'])->name('documents.destroy');

        Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
        Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserController::class, 'delete'])->name('profile.destroy');
    });

    // ----------------------------------------
    // Guest Routes (Guest)
    // ----------------------------------------
    Route::middleware('role.permission:guest')->group(function () {
        Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
        Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserController::class, 'delete'])->name('profile.destroy');
    });

    // ----------------------------------------
    // Classroom Viewing Routes (For All Authenticated Users)
    // ----------------------------------------
    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');  // List all classrooms
    Route::get('classrooms/{classroomId}', [ClassroomController::class, 'show'])->name('classrooms.show');  // Show details of a classroom
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('reservations/{reservationId}', [ReservationsController::class, 'show'])->name('reservations.show'); // Show specific reservation
    Route::get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');  // List all reservations
});



// // ----------------------------------------
// // Auth Routes: User Registration & Login
// // ----------------------------------------

// Route::post('register', [AuthController::class, 'register']);  // Register user
// Route::post('login', [AuthController::class, 'login']);  // Login user


// // -----------------------------------------------------------
// // Classroom Routes: Access Control for Superadmins & Teachers
// // -----------------------------------------------------------

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('logout', [AuthController::class, 'logout']);  // Logout user

//     // Superadmin routes: Manage Classrooms (Create, Update, Delete)
//     Route::middleware('role:superadmin')->group(function () {
//         Route::post('classrooms', [ClassroomController::class, 'store']); // Create classroom
//         Route::put('classrooms/{classroomId}', [ClassroomController::class, 'update']); // Update classroom
//         Route::delete('classrooms/{classroomId}', [ClassroomController::class, 'destroy']); // Delete classroom
//     });

//     // Teacher routes: Reserve, Unreserve Classrooms, List Reservations
//     Route::middleware('role:teacher')->group(function () {
//         Route::post('classrooms/{classroomId}/reserve', [ReservationsController::class, 'reserve']);  // Reserve classroom
//         Route::delete('classrooms/{classroomId}/unreserve', [ReservationsController::class, 'unreserve']);  // Unreserve classroom
//         Route::get('reservations', [ReservationsController::class, 'listReservations']);  // List reservations for teacher
//     });

//     // All authenticated users can view classrooms
//     Route::get('classrooms', [ClassroomController::class, 'index']);  // List all classrooms
//     Route::get('classrooms/{classroomId}', [ClassroomController::class, 'show']);  // Show details of a classroom
// });
