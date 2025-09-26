<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ExportController;

// Routes d'authentification
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Utilisateurs
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::post('users', [UserController::class, 'store']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('activities', [DashboardController::class, 'activities']);
    });

    // Projets
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/stages', [ProjectController::class, 'stages']);
    Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks']);
    Route::get('projects/{project}/users', [ProjectController::class, 'users']);

    // Tâches
    Route::apiResource('tasks', TaskController::class);
    Route::put('tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::put('tasks/{task}/assign', [TaskController::class, 'assign']);
    Route::get('my-tasks', [TaskController::class, 'myTasks']);

    // Routes admin uniquement
    Route::middleware('role:admin')->group(function () {
        // Routes d'administration
    });

    // Exportation de données
    Route::post('/export', [ExportController::class, 'export']);
});