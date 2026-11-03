@extends('layouts.app')

@section('titulo', 'Sitios')

@section('contenido')
    <x-encabezado icono="ti-map-pin" titulo="Sitios">
        <a href="{{ route('panel') }}">Panel de nodos</a> / Configuración
        <x-slot:acciones>
            <a class="btn" href="{{ route('sitios.create') }}"><i class="ti ti-plus"></i> Nuevo sitio</a>
        </x-slot:acciones>
    </x-encabezado>

    @if (session('ok'))
        <div class="aviso-caja"><i class="ti ti-circle-check"></i> {{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso-caja error"><i class="ti ti-alert-triangle"></i> {{ session('error') }}</div>
    @endif

    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-list"></i> Todos los sitios ({{ $sitios->count() }})</h2>
        </div>

        @if ($sitios->isEmpty())
            <div class="vacio"><i class="ti ti-map-pin-off"></i>
                Todavía no hay sitios. Crea el primero con el botón <strong>Nuevo sitio</strong>.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>Proyecto</th><th>Tipo</th><th>País</th><th>Hora local</th><th>Nodos</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($sitios as $sitio)
                            <tr>
                                <td><strong>{{ $sitio->nombre }}</strong></td>
                                <td>{{ $sitio->proyecto->nombre }}</td>
                                <td><span class="pill azul">{{ \App\Models\Sitio::TIPOS[$sitio->tipo] ?? $sitio->tipo }}</span></td>
                                <td>{{ $sitio->pais ?: '—' }}</td>
                                <td>
                                    {{ now()->setTimezone($sitio->zona_horaria)->format('H:i') }}
                                    <span class="suave">· {{ $sitio->zona_horaria }}</span>
                                </td>
                                <td>{{ $sitio->nodos_count }}</td>
                                <td style="text-align: right">
                                    <a class="btn secundario" href="{{ route('sitios.edit', $sitio) }}"><i class="ti ti-pencil"></i> Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection