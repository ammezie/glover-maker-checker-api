<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register');
    Route::post('/auth/login', 'login');
    Route::post('/auth/logout', 'logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('requests')->group(function () {
        Route::controller(RequestController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('/{request}/approve', 'approve');
            Route::post('/{request}/decline', 'decline');
        });
    });
});
