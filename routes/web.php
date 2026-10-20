<?php

use App\Http\Controllers\TramasController;
use Illuminate\Support\Facades\Route;

// Páginas de la web (las que ven las personas)

Route::redirect('/', '/tramas');

Route::get('/tramas', [TramasController::class, 'index'])->name('tramas');
