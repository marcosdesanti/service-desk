<?php

use App\Http\Controllers\Api\ContactValidationController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

//Route of Login (public)
Route::post('/v1/login', [AuthController::class, 'login']);

//Protected Routes (required token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Teste de conexão
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Validação de Contatos (que o n8n usará)
    Route::middleware('auth:sanctum')->get('/v1/validate-contact/{phone}', ContactValidationController::class);
});