@extends('layouts.app')

@section('titulo', 'Nodos')

@section('contenido')
    <x-encabezado icono="ti-cpu" titulo="Nodos">
        <a href="{{ route('panel') }}">Panel de nodos</a> / Configuración
    </x-encabezado>

    @if (session('ok'))
        <div class="aviso-caja"><i class="ti ti-circle-check"></i> {{ session('ok') }}</div>
    @endif

    @php $sinSitio = $nodos->whereNull('sitio_id')->count(); @endphp
    @if ($sinSitio > 0)
        <div class="aviso-caja"><i class="ti ti-map-pin-question"></i>
            {{ $sinSitio }} {{ $sinSitio === 1 ? 'nodo no tiene' : 'nodos no tienen' }} sitio:
            asígnaselo para ver su hora local y sus rangos, y revisa su <strong>desfase horario</strong>.</div>
    @endif

    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-list"></i> Todos los nodos ({{ $nodos->count() }})</h2>
        </div>
        <p class="nota" style="margin: -6px 0 14px">Se dan de alta solos con su primera trama. Para que dejen de enviar datos, desactívalos.</p>

        @if ($nodos->isEmpty())
            <div class="vacio"><i class="ti ti-antenna-bars-off"></i>
                Todavía no ha llegado ninguna trama. Los nodos aparecen solos al enviar la primera.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Código</th><th>Nombre</th><th>Sitio</th><th>Rol</th><th>Desfase</th><th>Conexión</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($nodos as $nodo)
                            @php [$estado, $color] = $nodo->estado(); @endphp
                            <tr>
                                <td><span class="codigo">{{ $nodo->codigo }}</span></td>
                                <td><a href="{{ route('nodos.show', $nodo) }}"><strong>{{ $nodo->nombre }}</strong></a></td>
                                <td>
                                    @if ($nodo->sitio)
                                        {{ $nodo->sitio->nombre }} <span class="suave">· {{ $nodo->sitio->proyecto->nombre }}</span>
                                    @else
                                        <span class="pill ambar">Sin sitio</span>
                                    @endif
                                </td>
                                <td>{{ \App\Models\Nodo::ROLES[$nodo->rol] ?? $nodo->rol }}</td>
                                <td>{{ $nodo->desfaseTexto() }}</td>
                                <td><span class="pill {{ $color }}">{{ $estado }}</span></td>
                                <td style="text-align: right">
                                    <a class="btn {{ $nodo->sitio ? 'secundario' : '' }}" href="{{ route('nodos.edit', $nodo) }}">
                                        <i class="ti ti-settings"></i> Configurar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection