{{--
    Campos del formulario de rango (los usan create y edit).
    $rango es null al crear. $parametro, $tiposSitio y $sitios los pasa el controlador.
--}}
@php
    $aplicaA = old('aplica_a', $rango?->sitio_id ? 'sitio' : 'tipo');
    $unidad = $parametro->unidad ? ' (' . $parametro->unidad . ')' : '';
@endphp

<div class="formulario">
    <div class="campo">
        <label>Se aplica a</label>
        <div class="opciones-radio">
            <label class="campo-check">
                <input type="radio" name="aplica_a" value="tipo" @checked($aplicaA === 'tipo')> Un tipo de sitio
            </label>
            <label class="campo-check">
                <input type="radio" name="aplica_a" value="sitio" @checked($aplicaA === 'sitio') @disabled($sitios->isEmpty())> Un sitio concreto
            </label>
        </div>
        <span class="ayuda">El rango de un sitio concreto manda sobre el de su tipo.
            @if ($sitios->isEmpty()) Todavía no hay sitios dados de alta. @endif</span>
    </div>

    <div class="campo @error('tipo_sitio') con-error @enderror" id="bloque-tipo">
        <label for="tipo_sitio">Tipo de sitio</label>
        <select id="tipo_sitio" name="tipo_sitio">
            @foreach ($tiposSitio as $valor => $texto)
                <option value="{{ $valor }}" @selected(old('tipo_sitio', $rango?->tipo_sitio) === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
        @error('tipo_sitio') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="campo @error('sitio_id') con-error @enderror" id="bloque-sitio">
        <label for="sitio_id">Sitio</label>
        <select id="sitio_id" name="sitio_id">
            @foreach ($sitios as $sitio)
                <option value="{{ $sitio->id }}" @selected((int) old('sitio_id', $rango?->sitio_id) === $sitio->id)>
                    {{ $sitio->nombre }} · {{ $sitio->proyecto->nombre }}
                </option>
            @endforeach
        </select>
        @error('sitio_id') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="fila-campos">
        <div class="campo @error('optimo_min') con-error @enderror">
            <label for="optimo_min">Óptimo: mínimo{{ $unidad }}</label>
            <input type="number" id="optimo_min" name="optimo_min" step="any" value="{{ old('optimo_min', $rango?->optimo_min) }}">
            @error('optimo_min') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo @error('optimo_max') con-error @enderror">
            <label for="optimo_max">Óptimo: máximo{{ $unidad }}</label>
            <input type="number" id="optimo_max" name="optimo_max" step="any" value="{{ old('optimo_max', $rango?->optimo_max) }}">
            @error('optimo_max') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="fila-campos">
        <div class="campo @error('aceptable_min') con-error @enderror">
            <label for="aceptable_min">Aceptable: mínimo{{ $unidad }}</label>
            <input type="number" id="aceptable_min" name="aceptable_min" step="any" value="{{ old('aceptable_min', $rango?->aceptable_min) }}">
            @error('aceptable_min') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo @error('aceptable_max') con-error @enderror">
            <label for="aceptable_max">Aceptable: máximo{{ $unidad }}</label>
            <input type="number" id="aceptable_max" name="aceptable_max" step="any" value="{{ old('aceptable_max', $rango?->aceptable_max) }}">
            @error('aceptable_max') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
    </div>
    <span class="ayuda" style="margin-top: -10px">Deja vacío un límite si no lo hay por ese lado. Escala del sensor:
        {{ $parametro->escala_min + 0 }} – {{ $parametro->escala_max + 0 }}{{ $unidad }}.</span>

    <div class="campo @error('fuente') con-error @enderror">
        <label for="fuente">Fuente <span class="suave">(opcional, pero recomendable)</span></label>
        <input type="text" id="fuente" name="fuente" maxlength="150"
               value="{{ old('fuente', $rango?->fuente) }}" placeholder="Norma, artículo o persona que da el rango">
        @error('fuente') <span class="error-campo">{{ $message }}</span> @enderror
    </div>
</div>

@push('scripts')
    <script>
        // Muestra solo el desplegable que corresponde a la opción elegida (tipo de sitio o sitio concreto)
        (function () {
            const radios = document.querySelectorAll('input[name="aplica_a"]');
            const tipo = document.getElementById('bloque-tipo');
            const sitio = document.getElementById('bloque-sitio');

            function actualizar() {
                const elegido = document.querySelector('input[name="aplica_a"]:checked').value;
                tipo.style.display = elegido === 'tipo' ? '' : 'none';
                sitio.style.display = elegido === 'sitio' ? '' : 'none';
            }

            radios.forEach(r => r.addEventListener('change', actualizar));
            actualizar();
        })();
    </script>
@endpush