<?php

use App\Http\Controllers\NodoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\TramasController;
use App\Http\Controllers\RangoController;
use Illuminate\Support\Facades\Route;

// Páginas de la web (las que ven las personas)

Route::get('/', [PanelController::class, 'index'])->name('panel');

Route::get('/nodos/{nodo}', [NodoController::class, 'show'])->name('nodos.show');

Route::get('/tramas', [TramasController::class, 'index'])->name('tramas');

Route::resource('proyectos', ProyectoController::class)->except(['show']);

Route::resource('parametros', ParametroController::class)->only(['index', 'edit', 'update']);

Route::resource('parametros.rangos', RangoController::class)->shallow()->except(['index', 'show']);
