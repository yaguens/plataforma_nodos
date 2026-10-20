<?php

use App\Http\Controllers\NodoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\TramasController;
use Illuminate\Support\Facades\Route;

// Páginas de la web (las que ven las personas)

Route::get('/', [PanelController::class, 'index'])->name('panel');

Route::get('/nodos/{nodo}', [NodoController::class, 'show'])->name('nodos.show');

Route::get('/tramas', [TramasController::class, 'index'])->name('tramas');
