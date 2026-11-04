@extends('layouts.app')

@section('titulo', 'Configurar ' . $nodo->nombre)

@section('contenido')
    <x-encabezado icono="ti-cpu" :titulo="$nodo->nombre">
        <a href="{{ route('nodos.index') }}">Nodos</a> / <span class="codigo">{{ $nodo->codigo }}</span>
        <x-slot:acciones>
            <a class="btn secundario" href="{{ route('nodos.show', $nodo) }}"><i class="ti ti-chart-line"></i> Medidores y gráficas</a>
        </x-slot:acciones>
    </x-encabezado>

    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-settings"></i> Configuración</h2></div>

        <form method="POST" action="{{ route('nodos.update', $nodo) }}">
            @csrf
            @method('PUT')

            <div class="formulario">
                <div class="campo">
                    <label>Código</label>
                    <span class="codigo">{{ $nodo->codigo }}</span>
                    <span class="ayuda">Los 3 últimos bytes de la MAC del nodo: lo envía en cada trama y no se puede cambiar.</span>
                </div>

                <div class="campo @error('nombre') con-error @enderror">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="100" required
                           value="{{ old('nombre', $nodo->nombre) }}" placeholder="Por ejemplo: Boya muelle norte">
                    @error('nombre') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo @error('sitio_id') con-error @enderror">
                    <label for="sitio_id">Sitio</label>
                    <select id="sitio_id" name="sitio_id">
                        <option value="">— Sin sitio asignado —</option>
                        @foreach ($sitios as $sitio)
                            <option value="{{ $sitio->id }}" @selected((int) old('sitio_id', $nodo->sitio_id) === $sitio->id)>
                                {{ $sitio->nombre }} · {{ $sitio->proyecto->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('sitio_id') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo @error('desfase_horas') con-error @enderror">
                    <label for="desfase_horas">Desfase horario del nodo (horas respecto a UTC)</label>
                    <div class="fila">
                        <input type="number" id="desfase_horas" name="desfase_horas" step="0.5" min="-12" max="14" required
                               style="max-width: 140px; margin: 0"
                               value="{{ old('desfase_horas', $nodo->desfase_s / 3600) }}">
                        <button type="button" class="btn secundario" id="usar-desfase-sitio" style="margin: 0">
                            <i class="ti ti-clock"></i> Usar el del sitio</button>
                    </div>
                    <span class="ayuda" id="ayuda-desfase"></span>
                    <span class="ayuda">El nodo envía su hora <strong>local</strong>: con este valor se pasa a UTC (Lima −5, Guatemala −6).
                        Solo afecta a las tramas que lleguen <strong>a partir de ahora</strong>: las ya guardadas no se recalculan.</span>
                    @error('desfase_horas') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="fila-campos">
                    <div class="campo @error('rol') con-error @enderror">
                        <label for="rol">Rol</label>
                        <select id="rol" name="rol" required>
                            @foreach (\App\Models\Nodo::ROLES as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('rol', $nodo->rol) === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('rol') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                    <div class="campo @error('maestro_id') con-error @enderror" id="bloque-maestro">
                        <label for="maestro_id">Maestro</label>
                        <select id="maestro_id" name="maestro_id">
                            @forelse ($maestros as $maestro)
                                <option value="{{ $maestro->id }}" @selected((int) old('maestro_id', $nodo->maestro_id) === $maestro->id)>
                                    {{ $maestro->codigo }} · {{ $maestro->nombre }}
                                </option>
                            @empty
                                <option value="">No hay ningún nodo con rol «Maestro»</option>
                            @endforelse
                        </select>
                        <span class="ayuda">El nodo que reenvía sus tramas.</span>
                        @error('maestro_id') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="campo @error('descripcion') con-error @enderror">
                    <label for="descripcion">Descripción <span class="suave">(opcional)</span></label>
                    <input type="text" id="descripcion" name="descripcion" maxlength="255"
                           value="{{ old('descripcion', $nodo->descripcion) }}" placeholder="Dónde está, qué sensores lleva...">
                    @error('descripcion') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <input type="hidden" name="activo" value="0">
                    <label class="campo-check">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $nodo->activo))>
                        Nodo activo
                    </label>
                    <span class="ayuda">Si lo desactivas, la API rechazará sus tramas (403) y quedarán en incidencias. Sus datos anteriores se conservan.</span>
                </div>
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar</button>
                <a class="btn secundario" href="{{ route('nodos.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- Datos que se rellenan solos con las tramas (solo consulta) --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-info-circle"></i> Actividad</h2></div>
        <table>
            <tbody>
                <tr><th>Primer contacto</th>
                    <td>{{ $nodo->primer_contacto ? $nodo->primer_contacto->copy()->setTimezone($nodo->zonaHoraria())->format('d/m/Y H:i') : '—' }}</td></tr>
                <tr><th>Último contacto</th>
                    <td>{{ $nodo->ultima_conexion ? $nodo->ultima_conexion->locale('es')->diffForHumans() : 'Nunca' }}</td></tr>
                <tr><th>Último ciclo (seq)</th><td>{{ $nodo->ultimo_seq ?? '—' }}</td></tr>
                <tr><th>Horas mostradas en</th><td>{{ $nodo->zonaHoraria() }}</td></tr>
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script>
            (function () {
                // Desfase actual (en horas) de la zona de cada sitio, calculado en el servidor
                const desfases = @json($desfasesSitios);
                const sitio = document.getElementById('sitio_id');
                const campo = document.getElementById('desfase_horas');
                const boton = document.getElementById('usar-desfase-sitio');
                const ayuda = document.getElementById('ayuda-desfase');

                function texto(h) { return 'UTC' + (h < 0 ? '−' : '+') + Math.abs(h) + ' h'; }

                function actualizarAyuda() {
                    const h = desfases[sitio.value];
                    if (h === undefined) {
                        ayuda.textContent = 'Elige un sitio para ver qué desfase le corresponde.';
                        boton.disabled = true;
                        return;
                    }
                    boton.disabled = false;
                    const distinto = parseFloat(campo.value) !== h;
                    ayuda.innerHTML = 'La zona del sitio está hoy en <strong>' + texto(h) + '</strong>'
                        + (distinto ? ' · <span style="color: var(--ambar)">no coincide con el desfase del nodo</span>' : ' · coincide ✓');
                }

                boton.addEventListener('click', function () {
                    campo.value = desfases[sitio.value];
                    actualizarAyuda();
                });
                sitio.addEventListener('change', actualizarAyuda);
                campo.addEventListener('input', actualizarAyuda);
                actualizarAyuda();

                // El campo "Maestro" solo tiene sentido para un nodo esclavo
                const rol = document.getElementById('rol');
                const bloqueMaestro = document.getElementById('bloque-maestro');
                function mostrarMaestro() { bloqueMaestro.style.display = rol.value === 'esclavo' ? '' : 'none'; }
                rol.addEventListener('change', mostrarMaestro);
                mostrarMaestro();
            })();
        </script>
    @endpush
@endsection