<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateParametroRequest;
use App\Models\Parametro;
use App\Models\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// PARÁMETROS · revisión de las magnitudes que envían los nodos (Configuración)
// No se crean ni se borran desde aquí: se dan de alta solas al llegar una clave nueva
// en una trama (estado "Pendiente") y, si no interesan, se ocultan (estado "Oculto").
//   GET  /parametros                     index   listado (pendientes primero)
//   GET  /parametros/{parametro}/edit    edit    formulario + sus rangos
//   PUT  /parametros/{parametro}         update  guardar la revisión
// ═════════════════════════════════════════════
class ParametroController extends Controller
{
    public function index(): View
    {
        $parametros = Parametro::withCount('rangos')
            ->orderByRaw("CASE estado WHEN 'Pendiente' THEN 0 WHEN 'Activo' THEN 1 ELSE 2 END")
            ->orderBy('codigo')
            ->get();

        return view('parametros.index', compact('parametros'));
    }

    public function edit(Parametro $parametro): View
    {
        $parametro->load(['rangos.sitio']);

        return view('parametros.edit', [
            'parametro' => $parametro,
            'tiposSitio' => Sitio::TIPOS,
        ]);
    }

    public function update(UpdateParametroRequest $request, Parametro $parametro): RedirectResponse
    {
        $parametro->update($request->validated());

        return redirect()->route('parametros.index')
            ->with('ok', 'Parámetro «' . $parametro->nombre . '» actualizado.');
    }
}