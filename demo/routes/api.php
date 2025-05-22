<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController; // Import TaskController

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes for the Demo Tasks API
Route::apiResource('tasks', TaskController::class);

// Alternatively, define them individually if more control is needed or if
// apiResource doesn't map exactly to the methods defined (though it should for standard CRUD):
// Route::get('/tasks', [TaskController::class, 'index']);
// Route::post('/tasks', [TaskController::class, 'store']);
// Route::get('/tasks/{id}', [TaskController::class, 'show']);
// Route::put('/tasks/{id}', [TaskController::class, 'update']); // Or Route::patch for partial updates
// Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
