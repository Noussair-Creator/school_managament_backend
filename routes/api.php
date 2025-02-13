<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



// Authenticated Routes: Requires 'auth:sanctum'
// -----------------------------------------------------------
Route::post('register', [AuthController::class, 'register']);  // Register user
Route::post('login', [AuthController::class, 'login']);  // Login user

Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    // ----------------------------------------
    // Superadmin & Admin Routes (User, Roles, Permissions)
    // ----------------------------------------
    Route::middleware('role.permission:superadmin|admin')->group(function () {
        // User Management

        Route::get('/users/{userId}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'storeByAdmin'])->name('users.store');
        Route::put('/users/{userId}', [UserController::class, 'updateByAdmin'])->name('users.update');
        Route::delete('/users/{userId}', [UserController::class, 'deleteByAdmin'])->name('users.destroy');

        // Role Management
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{roleId}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('roles/{roleId}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{roleId}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Assign/Remove Roles to/from Users
        Route::post('users/{userId}/assign-role', [RoleController::class, 'assignRole'])->name('roles.assign');
        Route::post('users/{userId}/remove-role', [RoleController::class, 'revokeRole'])->name('roles.remove');

        // Permission Management
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('permissions/{permissionId}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::put('permissions/{permissionId}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permissionId}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Assign/Revoke Permissions to/from Roles
        Route::post('roles/{roleId}/assign-permission', [PermissionController::class, 'assignPermissionToRole'])->name('permissions.assign');
        Route::post('roles/{roleId}/revoke-permission', [PermissionController::class, 'revokePermissionFromRole'])->name('permissions.revoke');
        Route::get('/roles/{roleId}/permissions', [RoleController::class, 'showPermissions'])->name('roles.permissions');

        // Classroom Management
        Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::put('classrooms/{classroomId}', [ClassroomController::class, 'update'])->name('classrooms.update');

    });

    // ----------------------------------------
    // Teacher, Admin & Superadmin Routes (Classrooms & Reservations)
    // ----------------------------------------
    Route::middleware('role.permission:teacher|superadmin|admin')->group(function () {
        // Classroom Reservation Routes
        Route::post('classrooms/{classroomId}/make-reservation', [ReservationsController::class, 'makeReservation'])->name('reservations.store');
        Route::put('reservations/{reservationId}', [ReservationsController::class, 'updateReservation'])->name('reservations.update');
        Route::get('reservations/{reservationId}', [ReservationsController::class, 'show'])->name('reservations.show');
        Route::get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');
        Route::delete('reservations/{reservationId}/cancel', [ReservationsController::class, 'cancelReservation'])->name('reservations.cancel');

        // Document Routes (Upload & Delete)
        Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
        Route::delete('documents/{documentId}', [DocumentController::class, 'delete'])->name('documents.destroy');

        // User Profile Routes (view, update, delete)
        Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
        Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserController::class, 'delete'])->name('profile.destroy');
    });

    // ----------------------------------------
    // Guest Routes (View Profile)
    // ----------------------------------------
    Route::middleware('role.permission:guest')->group(function () {
        Route::get('/profile', [UserController::class, 'profile'])->name('profile.show');
        Route::put('/profile', [UserController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserController::class, 'delete'])->name('profile.destroy');
    });

    // ----------------------------------------
    // Classroom Viewing Routes (Accessible to All Authenticated Users)
    // ----------------------------------------
    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');  // List all classrooms
    Route::get('classrooms/{classroomId}', [ClassroomController::class, 'show'])->name('classrooms.show');  // Show specific classroom
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');

    Route::get('documents/{documentId}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{documentId}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('reservations/{reservationId}', [ReservationsController::class, 'show'])->name('reservations.show'); // Show specific reservation
    Route::get('reservations', [ReservationsController::class, 'listReservations'])->name('reservations.index');  // List all reservations
});

