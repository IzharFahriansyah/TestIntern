<?php
// filepath: d:\laragon\www\ApiManpro\routes\api.php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now()
    ]);
});

// Protected routes (perlu login)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Project routes - ACCESSIBLE BY ALL AUTHENTICATED USERS
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/projects/{id}/members', [ProjectController::class, 'members']);
    Route::get('/projects/{id}/tasks', [TaskController::class, 'getProjectTasks']);
    
    // Task routes - ACCESSIBLE BY ALL AUTHENTICATED USERS
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::get('/my-tasks', [TaskController::class, 'getMyTasks']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::post('/tasks/{id}/assign', [TaskController::class, 'assignTask']);
    
    // Admin only routes
    Route::middleware('admin')->group(function () {
        // User CRUD
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Project management (admin only)
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{id}/members', [ProjectController::class, 'addMember']);
        Route::delete('/projects/{id}/members/{userId}', [ProjectController::class, 'removeMember']);
        
        // Task management (admin only)
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    });
});