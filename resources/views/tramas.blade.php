@extends('layouts.app')

@section('titulo', 'Tramas y API')

@section('contenido')
    @php
        // Hora UTC guardada → hora local del sitio del nodo
        $local = fn ($fecha, $nodo) => $fecha ? $fecha->copy()->setTimezone($nodo->zonaHoraria())->format('d/m/Y H:i:s') : null;
        $hace = fn ($fecha) => $fecha ? $fecha->copy()->locale('es')->diffForHumans() : 'nunca';
        $nodoPrueba = $nodos->firstWhere('codigo', 'FFFFFF');
        $siguienteSeq = ($nodoPrueba?->ultimo_seq ?? 0) + 1;
    @endphp

    <x-encabezado icono="ti-api" titulo="Tramas y API">
        Lo último que ha llegado a la API. Horas en la zona del sitio de cada nodo. (Por defecto: {{ config('plataforma.zona_por_defecto') }}).
    </x-encabezado>

    <div class="resumen">
        <div class="dato"><span class="circulo c-azul"><i class="ti ti-cpu"></i></span>
            <div><span class="cifra">{{ $nodos->count() }}</span><span class="txt">Nodos</span></div></div>
        <div class="dato"><span class="circulo c-verde"><i class="ti ti-wifi"></i></span>
            <div><span class="cifra">{{ $enLinea }}</span><span class="txt">En línea (últimos {{ $minutos }} min)</span></div></div>
        <div class="dato"><span class="circulo c-turquesa"><i class="ti ti-arrows-down-up"></i></span>
            <div><span class="cifra">{{ $ciclosHoy }}</span><span class="txt">Tramas en las últimas 24 h</span></div></div>
        <div class="dato"><span class="circulo c-ambar"><i class="ti ti-sparkles"></i></span>
            <div><span class="cifra">{{ $pendientes->count() }}</span><span class="txt">Magnitudes nuevas por revisar</span></div></div>
    </div>

    @if ($pendientes->isNotEmpty())
        <div class="aviso-caja">
            <i class="ti ti-sparkles"></i> <strong>Claves nuevas guardadas como pendientes:</strong>
            @foreach ($pendientes as $p) <span class="codigo">{{ $p }}</span> @endforeach
        </div>
    @endif

    {{-- Direcciones de la API --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-world"></i> Direcciones de la API (v1)</h2></div>
        <table>
            <tbody>
                <tr><td>En directo · una trama por ciclo</td><td><span class="codigo">POST {{ url('/api/v1/medidas') }}</span></td></tr>
                <tr><td>Reenvío en lote · array de tramas</td><td><span class="codigo">POST {{ url('/api/v1/medidas/lote') }}</span></td></tr>
            </tbody>
        </table>
        <p class="nota">Cabecera <span class="codigo">Content-Type: application/json</span>. Respuestas: 200 guardado (o ya estaba) · 400 mal formada ·
            403 nodo desactivado · 409 mismo seq con otro contenido · 500 error del servidor (el nodo reintenta). Detalle en <span class="codigo">docs/contrato_api.md</span>.</p>
    </div>

    {{-- Últimas tramas --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-list"></i> Últimas tramas ({{ $ciclos->count() }})</h2></div>
        @if ($ciclos->isEmpty())
            <div class="vacio"><i class="ti ti-inbox"></i>Todavía no ha llegado ninguna trama. Prueba con el formulario de abajo.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Medida (hora del sitio)</th><th>Recibida</th><th>Nodo</th><th>Seq</th><th>Origen</th><th>Lecturas</th><th>Avisos</th><th>Trama</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($ciclos as $c)
                            <tr>
                                <td>{{ $local($c->medido_en, $c->nodo) ?? 'sin hora' }}</td>
                                <td class="suave">{{ $hace($c->recibido_en) }}</td>
                                <td><a href="{{ route('nodos.show', $c->nodo) }}"><span class="codigo">{{ $c->nodo->codigo }}</span></a>
                                    @if ($c->via) <span class="suave">vía {{ $c->via->codigo }}</span> @endif</td>
                                <td>{{ $c->seq ?? '—' }}</td>
                                <td><span class="pill {{ $c->origen === 'red' ? 'azul' : 'ambar' }}">{{ $c->origen }}</span></td>
                                <td>{{ $c->lecturas_count }}</td>
                                <td>
                                    @foreach (array_filter(explode(',', (string) $c->marcas)) as $m)
                                        <span class="pill ambar">{{ \App\Services\ProcesadorTramas::MARCAS[$m] ?? $m }}</span>
                                    @endforeach
                                </td>
                                <td><details><summary>ver</summary><pre>{{ $c->cuerpo_original }}</pre></details></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="dos-columnas">
        {{-- Nodos --}}
        <div class="panel">
            <div class="panel-titulo"><h2><i class="ti ti-cpu"></i> Nodos</h2></div>
            @if ($nodos->isEmpty())
                <div class="vacio"><i class="ti ti-antenna-bars-off"></i>Los nodos aparecen solos al enviar su primera trama.</div>
            @else
                <table>
                    <thead><tr><th>Código</th><th>Sitio</th><th>Último contacto</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($nodos as $n)
                            @php
                                $enLineaNodo = $n->ultima_conexion && $n->ultima_conexion->gte(now()->utc()->subMinutes($minutos));
                            @endphp
                            <tr>
                                <td><a href="{{ route('nodos.show', $n) }}"><span class="codigo">{{ $n->codigo }}</span></a><br><span class="suave">{{ $n->nombre }}</span></td>
                                <td>{{ $n->sitio?->nombre ?? 'Sin sitio' }}</td>
                                <td class="suave">{{ $hace($n->ultima_conexion) }}</td>
                                <td>
                                    @if (! $n->activo) <span class="pill gris">Desactivado</span>
                                    @elseif ($enLineaNodo) <span class="pill verde">En línea</span>
                                    @else <span class="pill ambar">En silencio</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Incidencias --}}
        <div class="panel">
            <div class="panel-titulo"><h2><i class="ti ti-alert-triangle"></i> Últimas incidencias</h2></div>
            @if ($incidencias->isEmpty())
                <div class="vacio"><i class="ti ti-circle-check"></i>Ninguna trama rechazada ni en conflicto.</div>
            @else
                <table>
                    <thead><tr><th>Recibida</th><th>Tipo</th><th>Nodo · seq</th><th>Motivo</th></tr></thead>
                    <tbody>
                        @foreach ($incidencias as $i)
                            <tr>
                                <td class="suave">{{ $hace($i->recibido_en) }}</td>
                                <td><span class="pill {{ $i->tipo === 'conflicto' ? 'ambar' : 'rojo' }}">{{ $i->tipo }}</span></td>
                                <td>{{ $i->codigo_nodo ?? '—' }} · {{ $i->seq ?? '—' }}</td>
                                <td>{{ $i->motivo }}<details><summary>trama</summary><pre>{{ $i->cuerpo_original }}</pre></details></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Probar la API --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-send"></i> Probar la API</h2></div>
        <p class="suave" style="margin-top:0">Envía una trama como las de los nodos, con el nodo de pruebas <span class="codigo">FFFFFF</span>.</p>
        <textarea id="trama"></textarea>
        <div class="fila">
            <button class="btn" id="enviar"><i class="ti ti-send"></i> Enviar a la API</button>
            <button class="btn secundario" id="otra"><i class="ti ti-refresh"></i> Generar otra trama</button>
            <span class="suave">Repite el envío sin cambiar nada → 200 (no se duplica) · cambia un valor sin cambiar el seq → 409</span>
        </div>
        <div id="respuesta"></div>
    </div>

    @push('scripts')
    <script>
        let seq = {{ $siguienteSeq }};
        const caja = document.getElementById('trama');
        const salida = document.getElementById('respuesta');

        // Trama de ejemplo: ts = hora local de Lima en segundos (UTC − 5 h), como manda el nodo de Gonzalo
        function nuevaTrama() {
            const ts = Math.floor(Date.now() / 1000) - 5 * 3600;
            caja.value = JSON.stringify({
                formato: 1, nodo: 'FFFFFF', seq: seq, ts: ts, rtc: 1,
                OD_est: 1, OD_OD_medida: +(6 + Math.random() * 2).toFixed(2), OD_temperatura: +(24 + Math.random() * 3).toFixed(1),
                pH_est: 1, pH_pH: +(7 + Math.random()).toFixed(2)
            }, null, 2);
        }

        document.getElementById('otra').addEventListener('click', function () { seq++; nuevaTrama(); });

        document.getElementById('enviar').addEventListener('click', function () {
            fetch('{{ url('/api/v1/medidas') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': 'pruebas' },
                body: caja.value
            })
            .then(function (r) {
                return r.text().then(function (texto) {
                    salida.className = 'respuesta ' + (r.status === 200 ? 'bien' : 'mal');
                    salida.textContent = 'HTTP ' + r.status + ' · ' + texto + ' · la página se recarga en 3 s';
                    setTimeout(function () { location.reload(); }, 3000);
                });
            })
            .catch(function () {
                salida.className = 'respuesta mal';
                salida.textContent = 'No se pudo conectar con el servidor.';
            });
        });

        nuevaTrama();
    </script>
    @endpush
@endsection
