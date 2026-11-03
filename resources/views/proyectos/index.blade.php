@extends('layouts.app')

@section('titulo', 'Proyectos')

@section('contenido')
    <x-encabezado icono="ti-folders" titulo="Proyectos">
        <a href="{{ route('panel') }}">Panel de nodos</a> / Configuración
        <x-slot:acciones>
            <a class="btn" href="{{ route('proyectos.create') }}"><i class="ti ti-plus"></i> Nuevo proyecto</a>
        </x-slot:acciones>
    </x-encabezado>

    {{-- Mensajes tras guardar, actualizar o borrar (los envía el controlador con ->with('ok' / 'error', '...')) --}}
    @if (session('ok'))
        <div class="aviso-caja"><i class="ti ti-circle-check"></i> {{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso-caja error"><i class="ti ti-alert-triangle"></i> {{ session('error') }}</div>
    @endif

    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-list"></i> Todos los proyectos ({{ $proyectos->count() }})</h2>
        </div>

        @if ($proyectos->isEmpty())
            <div class="vacio"><i class="ti ti-folder-off"></i>
                Todavía no hay proyectos. Crea el primero con el botón <strong>Nuevo proyecto</strong>.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Sitios</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($proyectos as $proyecto)
                            <tr>
                                <td><strong>{{ $proyecto->nombre }}</strong></td>
                                <td>{{ $proyecto->descripcion ?: '—' }}</td>
                                <td>
                                    <span class="pill {{ match ($proyecto->estado) { 'Activo' => 'verde', 'Pausado' => 'ambar', default => 'gris' } }}">{{ $proyecto->estado }}</span>                                </td>
                                <td>{{ $proyecto->sitios_count }}</td>
                                <td style="text-align: right">
                                    <a class="btn secundario" href="{{ route('proyectos.edit', $proyecto) }}"><i class="ti ti-pencil"></i> Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection