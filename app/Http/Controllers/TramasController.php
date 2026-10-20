<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Incidencia;
use App\Models\Nodo;
use App\Models\Parametro;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// TRAMAS · página de comprobación: lo último que ha llegado a la API,
// los nodos, las incidencias y un formulario para enviar tramas de prueba.
// ═════════════════════════════════════════════
class TramasController extends Controller
{
    public function index(): View
    {
        $minutos = config('plataforma.minutos_en_linea');

        return view('tramas', [
            'ciclos' => Ciclo::with(['nodo.sitio', 'via'])->withCount('lecturas')
                ->orderByDesc('id')->limit(50)->get(),
            'nodos' => Nodo::with('sitio')->orderBy('codigo')->get(),
            'incidencias' => Incidencia::orderByDesc('id')->limit(10)->get(),
            'pendientes' => Parametro::where('estado', 'Pendiente')->orderBy('codigo')->pluck('codigo'),
            'enLinea' => Nodo::where('ultima_conexion', '>=', now()->utc()->subMinutes($minutos))->count(),
            'ciclosHoy' => Ciclo::where('recibido_en', '>=', now()->utc()->subDay())->count(),
            'minutos' => $minutos,
        ]);
    }
}
