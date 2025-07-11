<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;  // ← pastikan ini ada


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

// Inbound & ack (tidak perlu auth)
Route::prefix('wa')->group(function () {
    Route::post('webhook',           [WhatsappController::class, 'webhook']);
    Route::post('{session}/ack',     [WhatsappController::class, 'ack']);
    Route::get('{session}/history',  [WhatsappController::class, 'history']);
});

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('wa/{session}/send', [WhatsappController::class, 'send']);
    // … route lain yang butuh login …
// });