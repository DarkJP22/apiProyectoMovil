<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Proyectos
    Route::apiResource('projects', ProjectController::class);

    // Tareas
    Route::get('projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('tasks/search', [TaskController::class, 'search']);
    Route::put('tasks/{task}/status', [TaskController::class, 'updateStatus']);

    // Usuarios
    Route::get('usuarios', [AuthController::class, 'index']);
});

// Ruta para manejar solicitudes OPTIONS (Preflight de CORS)
Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');