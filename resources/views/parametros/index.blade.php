@extends('layouts.app')

@section('titulo', 'Parámetros')

@section('contenido')
    <x-encabezado icono="ti-adjustments-horizontal" titulo="Parámetros y rangos">
        <a href="{{ route('panel') }}">Panel de nodos</a> / Configuración
    </x-encabezado>

    @if (session('ok'))
        <div class="aviso-caja"><i class="ti ti-circle-check"></i> {{ session('ok') }}</div>
    @endif

    @php $pendientes = $parametros->where('estado', 'Pendiente')->count(); @endphp
    @if ($pendientes > 0)
        <div class="aviso-caja"><i class="ti ti-sparkles"></i>
            {{ $pendientes }} {{ $pendientes === 1 ? 'magnitud nueva' : 'magnitudes nuevas' }} por revisar:
            ponles nombre, unidad y escala, y pásalas a <strong>Activo</strong> (o a <strong>Oculto</strong> si no interesan).</div>
    @endif

    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-list"></i> Todas las magnitudes ({{ $parametros->count() }})</h2>
        </div>
        <p class="nota" style="margin: -6px 0 14px">Se dan de alta solas cuando un nodo envía una clave nueva (<span class="codigo">&lt;sensor&gt;_&lt;magnitud&gt;</span>).</p>

        @if ($parametros->isEmpty())
            <div class="vacio"><i class="ti ti-ruler-off"></i>
                Todavía no ha llegado ninguna magnitud. Aparecerán solas con la primera trama de un nodo.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Código</th><th>Nombre</th><th>Unidad</th><th>Escala del sensor</th><th>Rangos</th><th>Estado</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($parametros as $parametro)
                            <tr>
                                <td><span class="codigo">{{ $parametro->codigo }}</span></td>
                                <td><strong>{{ $parametro->nombre }}</strong></td>
                                <td>{{ $parametro->unidad ?: '—' }}</td>
                                <td>{{ $parametro->escala_min + 0 }} – {{ $parametro->escala_max + 0 }}</td>
                                <td>{{ $parametro->rangos_count }}</td>
                                <td>
                                    <span class="pill {{ match ($parametro->estado) { 'Activo' => 'verde', 'Pendiente' => 'ambar', default => 'gris' } }}">{{ $parametro->estado }}</span>
                                </td>
                                <td style="text-align: right">
                                    <a class="btn {{ $parametro->estado === 'Pendiente' ? '' : 'secundario' }}" href="{{ route('parametros.edit', $parametro) }}">
                                        <i class="ti {{ $parametro->estado === 'Pendiente' ? 'ti-checklist' : 'ti-pencil' }}"></i>
                                        {{ $parametro->estado === 'Pendiente' ? 'Revisar' : 'Editar' }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection