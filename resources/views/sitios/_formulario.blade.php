{{--
    Campos del formulario de sitio (los usan create y edit). $sitio es null al crear.
    $proyectos, $tipos, $zonasHabituales y $zonas los pasa el controlador.
--}}
<div class="formulario">
    <div class="campo @error('proyecto_id') con-error @enderror">
        <label for="proyecto_id">Proyecto</label>
        <select id="proyecto_id" name="proyecto_id" required>
            <option value="">— Elige un proyecto —</option>
            @foreach ($proyectos as $proyecto)
                <option value="{{ $proyecto->id }}" @selected((int) old('proyecto_id', $sitio?->proyecto_id) === $proyecto->id)>{{ $proyecto->nombre }}</option>
            @endforeach
        </select>
        @error('proyecto_id') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="campo @error('nombre') con-error @enderror">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="100" required
               value="{{ old('nombre', $sitio?->nombre) }}" placeholder="Por ejemplo: Muelle de Panajachel">
        @error('nombre') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="fila-campos">
        <div class="campo @error('tipo') con-error @enderror">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                @foreach ($tipos as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('tipo', $sitio?->tipo ?? 'otro') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
            <span class="ayuda">Los rangos de los parámetros se definen por tipo.</span>
            @error('tipo') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo @error('pais') con-error @enderror">
            <label for="pais">País <span class="suave">(opcional)</span></label>
            <input type="text" id="pais" name="pais" maxlength="60"
                   value="{{ old('pais', $sitio?->pais) }}" placeholder="Guatemala, Perú...">
            @error('pais') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="campo @error('zona_horaria') con-error @enderror">
        <label for="zona_horaria">Zona horaria</label>
        @php $zonaActual = old('zona_horaria', $sitio?->zona_horaria ?? 'America/Lima'); @endphp
        <select id="zona_horaria" name="zona_horaria" required>
            <optgroup label="Habituales">
                @foreach ($zonasHabituales as $zona)
                    <option value="{{ $zona }}" @selected($zonaActual === $zona)>{{ $zona }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Todas">
                @foreach ($zonas as $zona)
                    @continue(in_array($zona, $zonasHabituales))
                    <option value="{{ $zona }}" @selected($zonaActual === $zona)>{{ $zona }}</option>
                @endforeach
            </optgroup>
        </select>
        <span class="ayuda">Con ella se muestran las horas de los nodos del sitio. En la base de datos todo se guarda en UTC.</span>
        @error('zona_horaria') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="fila-campos">
        <div class="campo @error('latitud') con-error @enderror">
            <label for="latitud">Latitud <span class="suave">(opcional)</span></label>
            <input type="number" id="latitud" name="latitud" step="any" min="-90" max="90"
                   value="{{ old('latitud', $sitio?->latitud) }}" placeholder="14.7412">
            @error('latitud') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo @error('longitud') con-error @enderror">
            <label for="longitud">Longitud <span class="suave">(opcional)</span></label>
            <input type="number" id="longitud" name="longitud" step="any" min="-180" max="180"
                   value="{{ old('longitud', $sitio?->longitud) }}" placeholder="-91.1573">
            @error('longitud') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
    </div>
    <span class="ayuda" style="margin-top: -10px">En grados decimales (en Google Maps: clic derecho sobre el punto y copia las coordenadas). Servirán para un futuro mapa de nodos.</span>
</div>