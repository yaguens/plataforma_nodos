@extends('layouts.app')

@section('titulo', 'Panel de nodos')

@section('contenido')
    @php
        $niveles = ['optimo' => ['Óptimo', 'ti-circle-check'], 'aceptable' => ['Aceptable', 'ti-alert-circle'], 'fuera' => ['Fuera de rango', 'ti-alert-triangle']];
        $iconosAviso = [3 => 'ti-alert-octagon', 2 => 'ti-alert-triangle', 1 => 'ti-info-circle'];
    @endphp

    <div class="bienvenida">
        <div>
            <h2>Red de nodos de calidad del agua</h2>
            <p>Datos de los nodos modulares de Guatemala, Lima y Madrid. Cada nodo puede llevar sensores distintos:
                las magnitudes nuevas se dan de alta solas al llegar.</p>
        </div>
        <div class="logos">
            <a href="https://www.upm.es" target="_blank" rel="noopener" title="Universidad Politécnica de Madrid">
                <img src="{{ asset('img/logo-upm.png') }}" alt="UPM" onerror="this.closest('a').style.display='none'">
            </a>
            <a href="https://www.inictel-uni.edu.pe" target="_blank" rel="noopener" title="INICTEL-UNI">
                <img src="{{ asset('img/logo-inictel.png') }}" alt="INICTEL-UNI" onerror="this.closest('a').style.display='none'">
            </a>
        </div>
    </div>

    <div class="resumen">
        <div class="dato"><span class="circulo c-azul"><i class="ti ti-cpu"></i></span>
            <div><span class="cifra">{{ $total }}</span><span class="txt">Nodos</span></div></div>
        <div class="dato"><span class="circulo c-verde"><i class="ti ti-wifi"></i></span>
            <div><span class="cifra">{{ $enLinea }}</span><span class="txt">En línea (últimos {{ config('plataforma.minutos_en_linea') }} min)</span></div></div>
        <div class="dato"><span class="circulo {{ $enAlarma ? 'c-rojo' : 'c-gris' }}"><i class="ti ti-alert-triangle"></i></span>
            <div><span class="cifra">{{ $enAlarma }}</span><span class="txt">En alarma</span></div></div>
        <div class="dato"><span class="circulo c-turquesa"><i class="ti ti-sparkles"></i></span>
            <div><span class="cifra">{{ $pendientes }}</span><span class="txt">Magnitudes nuevas por revisar</span></div></div>
    </div>

    @if ($total === 0)
        <div class="panel">
            <div class="vacio"><i class="ti ti-antenna-bars-off"></i>
                Todavía no ha llegado ninguna trama. Los nodos aparecen solos al enviar la primera.<br>
                Para probar sin equipo, usa el formulario de <a href="{{ route('tramas') }}">Tramas y API</a>.</div>
        </div>
    @endif

    @foreach ($grupos as $grupo => $tarjetas)
        @php $sitio = $tarjetas->first()['nodo']->sitio; @endphp
        <div class="grupo-titulo">
            <strong><i class="ti {{ $sitio ? 'ti-map-pin' : 'ti-map-pin-question' }}"></i> {{ $grupo }}</strong>
            <span>
                {{ $tarjetas->count() }} {{ $tarjetas->count() === 1 ? 'nodo' : 'nodos' }}
                @if ($sitio) · {{ \App\Models\Sitio::TIPOS[$sitio->tipo] ?? $sitio->tipo }} · hora de {{ $sitio->zona_horaria }}@endif
            </span>
        </div>

        <div class="tarjetas">
            @foreach ($tarjetas as $t)
                @php $nodo = $t['nodo']; @endphp
                <div class="tarjeta gravedad-{{ $t['gravedad'] }}">
                    <div class="cabecera">
                        <a class="nombre" href="{{ route('nodos.show', $nodo) }}"><span class="punto {{ $t['color'] }}"></span>{{ $nodo->nombre }}</a>
                        <span class="pill {{ $t['color'] }}">{{ $t['estado'] }}</span>
                    </div>
                    <div class="donde">
                        <span class="codigo">{{ $nodo->codigo }}</span> · {{ \App\Models\Nodo::ROLES[$nodo->rol] ?? $nodo->rol }}
                        @if ($t['ciclo'])
                            · ciclo <strong>#{{ $t['ciclo']->seq ?? '—' }}</strong>
                            {{ $t['ciclo']->medido_en ? $t['ciclo']->medido_en->copy()->setTimezone($nodo->zonaHoraria())->format('d/m H:i') : '(sin hora)' }}
                        @endif
                    </div>

                    @if ($t['avisos'])
                        <ul class="avisos">
                            @foreach ($t['avisos'] as [$nivel, $texto])
                                <li class="aviso-{{ $nivel }}"><i class="ti {{ $iconosAviso[$nivel] }}"></i> {{ $texto }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @foreach ($t['sensores'] as $fuente => $valores)
                        @php $codigo = $valores[0]['lectura']->codigo_estado; @endphp
                        <div class="sensor-titulo">
                            <span>Sensor {{ $fuente }}</span>
                            @if ($codigo === null || $codigo === 1)
                                <span class="pill verde">OK</span>
                            @else
                                <span class="pill rojo">{{ config('plataforma.codigos_estado')[$codigo][0] ?? 'Código ' . $codigo }}</span>
                            @endif
                        </div>
                        @foreach ($valores as $v)
                            @php $l = $v['lectura']; $p = $v['parametro']; @endphp
                            <div class="valor-fila">
                                <span title="{{ $p->codigo }}">{{ $p->nombre }}</span>
                                <span class="cifra">
                                    @if ($l->valor === null)
                                        <span class="suave">Sin dato</span>
                                    @else
                                        {{ number_format($l->valor, $p->decimales, '.', ',') }} <small>{{ $p->unidad }}</small>
                                    @endif
                                    @if ($v['nivel'] && ! $t['apagado'])
                                        <i class="ti {{ $niveles[$v['nivel']][1] }} icono-nivel nivel-{{ $v['nivel'] }}" title="{{ $niveles[$v['nivel']][0] }}"></i>
                                    @endif
                                </span>
                                <x-barra-valor :valor="$l->valor" :min="$p->escala_min" :max="$p->escala_max" :rango="$v['rango']" :apagado="$t['apagado']" />
                            </div>
                        @endforeach
                    @endforeach

                    <div class="pie">
                        <span>
                            @if ($nodo->ultima_conexion)
                                <i class="ti ti-clock"></i> {{ $nodo->ultima_conexion->locale('es')->diffForHumans() }}
                            @else
                                Sin contacto todavía
                            @endif
                        </span>
                        <a href="{{ route('nodos.show', $nodo) }}">Medidores y gráficas <i class="ti ti-arrow-right"></i></a>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    @push('scripts')
        <script>
            // Se recarga sola cada minuto para mostrar los datos nuevos
            setTimeout(function () { location.reload(); }, 60000);
        </script>
    @endpush
@endsection
