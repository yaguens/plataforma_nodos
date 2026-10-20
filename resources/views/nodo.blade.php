@extends('layouts.app')

@section('titulo', $nodo->nombre)

@section('contenido')
    @php
        [$estadoTexto, $estadoColor] = $estado;
        $niveles = ['optimo' => ['Óptimo', 'verde', 'ti-circle-check'], 'aceptable' => ['Aceptable', 'ambar', 'ti-alert-circle'], 'fuera' => ['Fuera de rango', 'rojo', 'ti-alert-triangle']];
    @endphp

    <x-encabezado icono="ti-cpu" :titulo="$nodo->nombre">
        <a href="{{ route('panel') }}">Panel de nodos</a> /
        {{ $nodo->sitio ? $nodo->sitio->proyecto->nombre . ' · ' . $nodo->sitio->nombre : 'Sin sitio asignado' }}
        <x-slot:acciones>
            <span class="pill {{ $estadoColor }}">{{ $estadoTexto }}</span>
        </x-slot:acciones>
    </x-encabezado>

    <div class="resumen">
        <div class="dato"><span class="circulo c-azul"><i class="ti ti-id"></i></span>
            <div><span class="cifra pequena">{{ $nodo->codigo }}</span><span class="txt">{{ \App\Models\Nodo::ROLES[$nodo->rol] ?? $nodo->rol }}</span></div></div>
        <div class="dato"><span class="circulo c-{{ $estadoColor === 'verde' ? 'verde' : ($estadoColor === 'rojo' ? 'rojo' : ($estadoColor === 'ambar' ? 'ambar' : 'gris')) }}"><i class="ti ti-wifi"></i></span>
            <div><span class="cifra pequena">{{ $nodo->ultima_conexion ? $nodo->ultima_conexion->locale('es')->diffForHumans() : 'Nunca' }}</span><span class="txt">Último contacto</span></div></div>
        <div class="dato"><span class="circulo c-turquesa"><i class="ti ti-hash"></i></span>
            <div><span class="cifra pequena">{{ $nodo->ultimo_seq ?? '—' }}</span><span class="txt">Último ciclo (seq)</span></div></div>
        <div class="dato"><span class="circulo c-gris"><i class="ti ti-clock"></i></span>
            <div><span class="cifra pequena" id="reloj">{{ now()->setTimezone($zona)->format('H:i:s') }}</span><span class="txt">Hora del sitio · {{ $zona }}</span></div></div>
    </div>

    {{-- Ahora --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-gauge"></i> Ahora</h2></div>
        @if (! $tarjetas)
            <div class="vacio"><i class="ti ti-antenna-bars-off"></i>Este nodo todavía no ha enviado lecturas.</div>
        @else
            <div class="medidores">
                @foreach ($tarjetas as $t)
                    @php $p = $t['parametro']; $l = $t['lectura']; @endphp
                    <div class="medidor {{ $t['apagado'] ? 'apagado' : '' }}">
                        <h3 title="{{ $p->codigo }}">{{ $p->nombre }}</h3>
                        @if ($t['medidor'])
                            <div class="grafico" id="medidor-{{ $p->id }}"></div>
                        @elseif ($l->valor === null)
                            <div class="sin-dato"><i class="ti ti-plug-connected-x"></i>Sin dato<br>
                                <small>{{ $l->codigo_estado !== null ? (config('plataforma.codigos_estado')[$l->codigo_estado][0] ?? '') : '' }}</small></div>
                        @else
                            <div class="valor-grande">{{ number_format($l->valor, $p->decimales, '.', ',') }} <small>{{ $p->unidad }}</small></div>
                            <x-barra-valor :valor="$l->valor" :min="$p->escala_min" :max="$p->escala_max" :rango="$t['rango']" :apagado="$t['apagado']" />
                        @endif

                        @if ($t['apagado'])
                            <span class="pill gris"><i class="ti ti-clock-off"></i> Sin datos recientes</span>
                        @elseif ($t['nivel'])
                            <span class="pill {{ $niveles[$t['nivel']][1] }}"><i class="ti {{ $niveles[$t['nivel']][2] }}"></i> {{ $niveles[$t['nivel']][0] }}</span>
                        @elseif ($l->valor !== null)
                            <span class="pill gris">Sin rango</span>
                        @endif
                        <span class="hora">{{ $t['hora'] ? $t['hora']->format('d/m/Y H:i:s') . ' · ' . $t['hora']->locale('es')->diffForHumans() : 'sin hora' }}</span>
                    </div>
                @endforeach
            </div>
            <p class="nota">Los colores salen del rango de cada magnitud para el tipo de sitio del nodo @if (! $nodo->sitio)(este nodo aún no tiene sitio, por eso no hay colores)@endif.
                Un valor con más de {{ config('plataforma.minutos_en_linea') }} min sale en gris.</p>
        @endif
    </div>

    {{-- Gráficas --}}
    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-chart-line"></i> Últimas {{ $periodos[$horas] }}</h2>
            <div class="periodos">
                @foreach ($periodos as $h => $texto)
                    <a href="{{ route('nodos.show', ['nodo' => $nodo, 'horas' => $h]) }}" class="{{ $h === $horas ? 'activo' : '' }}">{{ $texto }}</a>
                @endforeach
            </div>
        </div>
        @if (! $graficas)
            <div class="vacio"><i class="ti ti-chart-line"></i>Sin lecturas con hora en este periodo.</div>
        @else
            <div class="graficas">
                @foreach ($graficas as $pid => $g)
                    <div class="grafica">
                        <h3>{{ $g['nombre'] }} <span>{{ $g['unidad'] }}</span></h3>
                        <div class="grafico" id="grafica-{{ $pid }}"></div>
                    </div>
                @endforeach
            </div>
            <p class="nota">Hora del sitio. Zona verde = rango óptimo. Un hueco en la línea = el sensor no dio valor o no llegaron datos
                (pasa el ratón para ver el motivo). Mayús + rueda del ratón para acercar.</p>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
        <script src="{{ asset('js/graficas.js') }}?v={{ filemtime(public_path('js/graficas.js')) }}"></script>
        <script>
            window.CODIGOS_ESTADO = @json($codigos);
            const medidores = @json($medidores);
            const graficas = @json($graficas);

            if (window.echarts) {
                Object.keys(medidores).forEach(function (id) {
                    dibujarMedidor(document.getElementById(id), medidores[id]);
                });
                Object.keys(graficas).forEach(function (id) {
                    dibujarLinea(document.getElementById('grafica-' + id), graficas[id]);
                });
                echarts.connect('nodo');   // el cursor se mueve a la vez en todas las gráficas
            }

            // Reloj con la hora del sitio y recarga cada minuto
            const reloj = document.getElementById('reloj');
            setInterval(function () {
                reloj.textContent = new Date().toLocaleTimeString('es-PE', { timeZone: @json($zona), hour12: false });
            }, 1000);
            setTimeout(function () { location.reload(); }, 60000);
        </script>
    @endpush
@endsection
