<?php

namespace App\Http\Controllers;

use App\Http\Requests\SitioRequest;
use App\Models\Lectura;
use App\Models\Proyecto;
use App\Models\Sitio;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// SITIOS · lugares físicos con nodos (Configuración)
// La zona horaria del sitio decide con qué hora se MUESTRAN los datos de sus nodos
// (en la base de datos todo está en UTC).
//   GET    /sitios                  index    listado
//   GET    /sitios/create           create   formulario vacío
//   POST   /sitios                  store    guardar
//   GET    /sitios/{sitio}/edit     edit     formulario relleno
//   PUT    /sitios/{sitio}          update   actualizar
//   DELETE /sitios/{sitio}          destroy  borrar (solo si no tiene lecturas guardadas)
// ═════════════════════════════════════════════
class SitioController extends Controller
{
    // Zonas que se ofrecen primero en el desplegable
    private const ZONAS_HABITUALES = ['America/Lima', 'America/Guatemala', 'Europe/Madrid'];

    public function index(): View
    {
        $sitios = Sitio::with('proyecto')->withCount('nodos')->orderBy('nombre')->get();

        return view('sitios.index', compact('sitios'));
    }

    public function create(): View
    {
        return view('sitios.create', ['sitio' => null] + $this->opciones());
    }

    public function store(SitioRequest $request): RedirectResponse
    {
        $sitio = Sitio::create($request->validated());

        return redirect()->route('sitios.index')->with('ok', 'Sitio «' . $sitio->nombre . '» creado.');
    }

    public function edit(Sitio $sitio): View
    {
        $sitio->loadCount('nodos');

        return view('sitios.edit', [
            'sitio' => $sitio,
            'tieneLecturas' => Lectura::where('sitio_id', $sitio->id)->exists(),
        ] + $this->opciones());
    }

    public function update(SitioRequest $request, Sitio $sitio): RedirectResponse
    {
        $sitio->update($request->validated());

        return redirect()->route('sitios.index')->with('ok', 'Sitio «' . $sitio->nombre . '» actualizado.');
    }

    public function destroy(Sitio $sitio): RedirectResponse
    {
        // Las lecturas guardan el sitio donde se midieron: borrarlo dejaría ese histórico sin lugar
        if (Lectura::where('sitio_id', $sitio->id)->exists()) {
            return redirect()->route('sitios.edit', $sitio)
                ->with('error', 'No se puede borrar: hay lecturas medidas en este sitio y perderían su lugar.');
        }

        $nombre = $sitio->nombre;
        $sitio->delete();   // sus nodos quedan "sin sitio" y sus rangos propios se borran (ver migraciones)

        return redirect()->route('sitios.index')->with('ok', 'Sitio «' . $nombre . '» borrado.');
    }

    // Lo que necesita el formulario para sus desplegables
    private function opciones(): array
    {
        return [
            'proyectos' => Proyecto::orderBy('nombre')->get(),
            'tipos' => Sitio::TIPOS,
            'zonasHabituales' => self::ZONAS_HABITUALES,
            'zonas' => DateTimeZone::listIdentifiers(),
        ];
    }
}