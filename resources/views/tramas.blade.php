@extends('layouts.app')

@section('titulo', 'Tramas recibidas · Plataforma de nodos')

@section('contenido')
    @php
        // Hora UTC guardada → hora local del sitio del nodo
        $local = fn ($fecha, $nodo) => $fecha ? $fecha->copy()->setTimezone($nodo->zonaHoraria())->format('d/m/Y H:i:s') : null;
        $hace = fn ($fecha) => $fecha ? $fecha->copy()->locale('es')->diffForHumans() : 'nunca';
        $nodoPrueba = $nodos->firstWhere('codigo', 'FFFFFF');
        $siguienteSeq = ($nodoPrueba?->ultimo_seq ?? 0) + 1;
    @endphp

    <h1>Tramas recibidas</h1>
    <p class="suave">Lo último que ha llegado a la API. Las horas se guardan en UTC y aquí se muestran en la hora del sitio de cada nodo
        (sin sitio: {{ config('plataforma.zona_por_defecto') }}).</p>

    <div class="resumen">
        <div class="dato"><span class="cifra">{{ $nodos->count() }}</span><span class="txt">Nodos</span></div>
        <div class="dato"><span class="cifra">{{ $enLinea }}</span><span class="txt">En línea (últimos {{ $minutos }} min)</span></div>
        <div class="dato"><span class="cifra">{{ $ciclosHoy }}</span><span class="txt">Tramas en las últimas 24 h</span></div>
        <div class="dato"><span class="cifra">{{ $pendientes->count() }}</span><span class="txt">Parámetros pendientes de revisar</span></div>
    </div>

    @if ($pendientes->isNotEmpty())
        <div class="panel">
            <strong>Claves nuevas guardadas como parámetros pendientes:</strong>
            @foreach ($pendientes as $p) <span class="codigo">{{ $p }}</span> @endforeach
        </div>
    @endif

    {{-- Últimas tramas --}}
    <div class="panel">
        <h2>Últimas tramas ({{ $ciclos->count() }})</h2>
        @if ($ciclos->isEmpty())
            <div class="vacio">Todavía no ha llegado ninguna trama. Prueba con el formulario de abajo.</div>
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
                                <td><span class="codigo">{{ $c->nodo->codigo }}</span>
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
            <h2>Nodos</h2>
            @if ($nodos->isEmpty())
                <div class="vacio">Los nodos aparecen solos al enviar su primera trama.</div>
            @else
                <table>
                    <thead><tr><th>Código</th><th>Sitio</th><th>Último contacto</th><th>Estado</th></tr></thead>
                    <tbody>
                        @foreach ($nodos as $n)
                            @php
                                $enLineaNodo = $n->ultima_conexion && $n->ultima_conexion->gte(now()->utc()->subMinutes($minutos));
                            @endphp
                            <tr>
                                <td><span class="codigo">{{ $n->codigo }}</span><br><span class="suave">{{ $n->nombre }}</span></td>
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
            <h2>Últimas incidencias</h2>
            @if ($incidencias->isEmpty())
                <div class="vacio">Ninguna trama rechazada ni en conflicto.</div>
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
        <h2>Probar la API</h2>
        <p class="suave">Envía una trama como las de los nodos, con el nodo de pruebas <span class="codigo">FFFFFF</span>.
            Dirección: <span class="codigo">POST {{ url('/api/v1/medidas') }}</span></p>
        <textarea id="trama"></textarea>
        <div class="fila">
            <button class="btn" id="enviar">Enviar a la API</button>
            <button class="btn secundario" id="otra">Generar otra trama</button>
            <span class="suave">Repite el envío sin cambiar nada → 200 (no se duplica) · cambia un valor sin cambiar el seq → 409</span>
        </div>
        <div id="respuesta"></div>
    </div>

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
@endsection
