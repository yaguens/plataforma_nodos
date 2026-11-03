@extends('layouts.app')

@section('titulo', 'Editar ' . $parametro->nombre)

@section('contenido')
    <x-encabezado icono="ti-ruler-measure" :titulo="$parametro->nombre">
        <a href="{{ route('parametros.index') }}">Parámetros y rangos</a> / <span class="codigo">{{ $parametro->codigo }}</span>
    </x-encabezado>

    {{-- Mensaje tras añadir, editar o borrar un rango (vuelven a esta página) --}}
    @if (session('ok'))
        <div class="aviso-caja"><i class="ti ti-circle-check"></i> {{ session('ok') }}</div>
    @endif

    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-forms"></i> Datos de la magnitud</h2></div>

        <form method="POST" action="{{ route('parametros.update', $parametro) }}">
            @csrf
            @method('PUT')

            <div class="formulario">
                <div class="campo">
                    <label>Código en la trama</label>
                    <span class="codigo">{{ $parametro->codigo }}</span>
                    <span class="ayuda">Lo envía el nodo y no se puede cambiar: el sensor <strong>{{ \App\Models\Parametro::fuenteDe($parametro->codigo) }}</strong>.</span>
                </div>

                <div class="campo @error('nombre') con-error @enderror">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="100" required
                           value="{{ old('nombre', $parametro->nombre) }}" placeholder="Por ejemplo: Oxígeno disuelto">
                    @error('nombre') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="fila-campos">
                    <div class="campo @error('unidad') con-error @enderror">
                        <label for="unidad">Unidad <span class="suave">(opcional)</span></label>
                        <input type="text" id="unidad" name="unidad" maxlength="20"
                               value="{{ old('unidad', $parametro->unidad) }}" placeholder="mg/L, °C...">
                        @error('unidad') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                    <div class="campo @error('decimales') con-error @enderror">
                        <label for="decimales">Decimales</label>
                        <input type="number" id="decimales" name="decimales" min="0" max="6" step="1" required
                               value="{{ old('decimales', $parametro->decimales) }}">
                        @error('decimales') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="fila-campos">
                    <div class="campo @error('escala_min') con-error @enderror">
                        <label for="escala_min">Escala: mínimo</label>
                        <input type="number" id="escala_min" name="escala_min" step="any" required
                               value="{{ old('escala_min', $parametro->escala_min + 0) }}">
                        @error('escala_min') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                    <div class="campo @error('escala_max') con-error @enderror">
                        <label for="escala_max">Escala: máximo</label>
                        <input type="number" id="escala_max" name="escala_max" step="any" required
                               value="{{ old('escala_max', $parametro->escala_max + 0) }}">
                        @error('escala_max') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                </div>
                <span class="ayuda" style="margin-top: -10px">La escala son los valores que <strong>puede</strong> medir el sensor (no los buenos: eso son los rangos). Marca los extremos de los medidores y las barritas.</span>

                <div class="campo">
                    {{-- Un checkbox desmarcado no se envía: el hidden manda un 0 por defecto y, si está marcado, lo sustituye el 1 --}}
                    <input type="hidden" name="mostrar_medidor" value="0">
                    <label class="campo-check">
                        <input type="checkbox" name="mostrar_medidor" value="1" @checked(old('mostrar_medidor', $parametro->mostrar_medidor))>
                        Mostrar como medidor (gauge) en la página del nodo
                    </label>
                </div>

                <div class="campo @error('estado') con-error @enderror">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        @foreach (\App\Models\Parametro::ESTADOS as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $parametro->estado) === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    <span class="ayuda">Pendiente: sin revisar · Activo: se muestra · Oculto: se sigue guardando, pero no se muestra.</span>
                    @error('estado') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar</button>
                <a class="btn secundario" href="{{ route('parametros.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- Rangos de este parámetro --}}
    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-target"></i> Rangos ({{ $parametro->rangos->count() }})</h2>
            <a class="btn" href="{{ route('parametros.rangos.create', $parametro) }}"><i class="ti ti-plus"></i> Añadir rango</a>
        </div>

        @if ($parametro->rangos->isEmpty())
            <div class="vacio"><i class="ti ti-target-off"></i>Este parámetro no tiene rangos: sus valores se muestran sin colores de óptimo o aceptable.</div>
        @else
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th>Se aplica a</th><th>Óptimo</th><th>Aceptable</th><th>Fuente</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($parametro->rangos as $rango)
                            <tr>
                                <td>
                                    @if ($rango->sitio)
                                        <i class="ti ti-map-pin"></i> {{ $rango->sitio->nombre }}
                                    @else
                                        Sitios de tipo <strong>{{ $tiposSitio[$rango->tipo_sitio] ?? $rango->tipo_sitio }}</strong>
                                    @endif
                                </td>
                                <td>{{ $rango->optimo_min ?? '—' }} a {{ $rango->optimo_max ?? '—' }}</td>
                                <td>{{ $rango->aceptable_min ?? '—' }} a {{ $rango->aceptable_max ?? '—' }}</td>
                                <td class="suave">{{ $rango->fuente ?: '—' }}</td>
                                <td style="text-align: right">
                                    <a class="btn secundario" href="{{ route('rangos.edit', $rango) }}"><i class="ti ti-pencil"></i> Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="nota">Un guion (—) es «sin límite por ese lado». El rango de un sitio concreto manda sobre el de su tipo.</p>
        @endif
    </div>
@endsection