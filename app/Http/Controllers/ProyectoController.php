<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// PROYECTOS · alta, edición y borrado (Configuración)
//   GET    /proyectos                    index    listado
//   GET    /proyectos/create             create   formulario vacío
//   POST   /proyectos                    store    guardar
//   GET    /proyectos/{proyecto}/edit    edit     formulario relleno
//   PUT    /proyectos/{proyecto}         update   actualizar
//   DELETE /proyectos/{proyecto}         destroy  borrar (solo si no tiene sitios)
// ═════════════════════════════════════════════
class ProyectoController extends Controller
{
    public function index(): View
    {
        $proyectos = Proyecto::withCount('sitios')->orderBy('nombre')->get();

        return view('proyectos.index', compact('proyectos'));
    }

    public function create(): View
    {
        return view('proyectos.create');
    }

    public function store(StoreProyectoRequest $request): RedirectResponse
    {
        $proyecto = Proyecto::create($request->validated());

        return redirect()->route('proyectos.index')
            ->with('ok', 'Proyecto «' . $proyecto->nombre . '» creado.');
    }

    public function edit(Proyecto $proyecto): View
    {
        $proyecto->loadCount('sitios');

        return view('proyectos.edit', compact('proyecto'));
    }

    public function update(UpdateProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $proyecto->update($request->validated());

        return redirect()->route('proyectos.index')
            ->with('ok', 'Proyecto «' . $proyecto->nombre . '» actualizado.');
    }

    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        // La base de datos no deja borrar un proyecto con sitios (restrictOnDelete):
        // se comprueba antes para dar un mensaje claro en vez de un error
        if ($proyecto->sitios()->exists()) {
            return redirect()->route('proyectos.edit', $proyecto)
                ->with('error', 'No se puede borrar: el proyecto tiene sitios. Bórralos o muévelos a otro proyecto antes.');
        }

        $nombre = $proyecto->nombre;
        $proyecto->delete();

        return redirect()->route('proyectos.index')
            ->with('ok', 'Proyecto «' . $nombre . '» borrado.');
    }
}