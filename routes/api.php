<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// WhatsApp Webhook - não requer autenticação
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\WhatsappWebhookController::class, 'handleWebhook']);
Route::post('/webhooks/whatsapp/{slug}', [\App\Http\Controllers\WhatsappWebhookController::class, 'handleWebhook']);
