<?php

namespace App\Http\Controllers;

use App\Http\Requests\RangoRequest;
use App\Models\Parametro;
use App\Models\Rango;
use App\Models\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// RANGOS · qué valores son buenos para un parámetro (se gestionan desde la página del parámetro)
//   GET    /parametros/{parametro}/rangos/create   create   formulario nuevo
//   POST   /parametros/{parametro}/rangos          store    guardar
//   GET    /rangos/{rango}/edit                    edit     formulario relleno
//   PUT    /rangos/{rango}                         update   actualizar
//   DELETE /rangos/{rango}                         destroy  borrar
// ═════════════════════════════════════════════
class RangoController extends Controller
{
    public function create(Parametro $parametro): View
    {
        return view('rangos.create', ['parametro' => $parametro, 'rango' => null] + $this->opciones());
    }

    public function store(RangoRequest $request, Parametro $parametro): RedirectResponse
    {
        $parametro->rangos()->create($this->datos($request));

        return redirect()->route('parametros.edit', $parametro)->with('ok', 'Rango añadido.');
    }

    public function edit(Rango $rango): View
    {
        $rango->load('parametro');

        return view('rangos.edit', ['parametro' => $rango->parametro, 'rango' => $rango] + $this->opciones());
    }

    public function update(RangoRequest $request, Rango $rango): RedirectResponse
    {
        $rango->update($this->datos($request));

        return redirect()->route('parametros.edit', $rango->parametro_id)->with('ok', 'Rango actualizado.');
    }

    public function destroy(Rango $rango): RedirectResponse
    {
        $parametroId = $rango->parametro_id;
        $rango->delete();

        return redirect()->route('parametros.edit', $parametroId)->with('ok', 'Rango borrado.');
    }

    // Lo que necesita el formulario para sus desplegables
    private function opciones(): array
    {
        return [
            'tiposSitio' => Sitio::TIPOS,
            'sitios' => Sitio::with('proyecto')->orderBy('nombre')->get(),
        ];
    }

    // Los datos validados, listos para guardar: o tipo de sitio o sitio, nunca los dos
    private function datos(RangoRequest $request): array
    {
        $d = $request->validated();
        $porSitio = $d['aplica_a'] === 'sitio';

        return [
            'tipo_sitio' => $porSitio ? null : $d['tipo_sitio'],
            'sitio_id' => $porSitio ? $d['sitio_id'] : null,
            'optimo_min' => $d['optimo_min'] ?? null,
            'optimo_max' => $d['optimo_max'] ?? null,
            'aceptable_min' => $d['aceptable_min'] ?? null,
            'aceptable_max' => $d['aceptable_max'] ?? null,
            'fuente' => $d['fuente'] ?? null,
        ];
    }
}