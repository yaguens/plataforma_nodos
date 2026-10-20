<?php

use App\Http\Controllers\Api\MedidasController;
use Illuminate\Support\Facades\Route;

// ═════════════════════════════════════════════
// Rutas de la API (Laravel les añade delante /api)
//   POST /api/v1/medidas        → una trama
//   POST /api/v1/medidas/lote   → reenvío en lote
// ═════════════════════════════════════════════

Route::prefix('v1')->group(function () {
    Route::post('/medidas', [MedidasController::class, 'unaTrama']);
    Route::post('/medidas/lote', [MedidasController::class, 'lote']);
});
