<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/verify-token', [AuthController::class, 'verifyToken']);
});

// Ruta de prueba para verificar que el servidor funciona
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'mensaje' => 'Servidor BioVet funcionando']);
});