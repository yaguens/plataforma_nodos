{{--
    Campos del formulario de proyecto (los usan create y edit).
    $proyecto existe al editar; al crear no existe, de ahí el "?? null".
    old('campo', valor) = lo que escribió el usuario si la validación falló; si no, el valor guardado.
--}}
@php $proyecto = $proyecto ?? null; @endphp

<div class="formulario">
    <div class="campo @error('nombre') con-error @enderror">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="100" required
               value="{{ old('nombre', $proyecto?->nombre) }}" placeholder="Por ejemplo: Lago Atitlán">
        @error('nombre') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="campo @error('descripcion') con-error @enderror">
        <label for="descripcion">Descripción <span class="suave">(opcional)</span></label>
        <input type="text" id="descripcion" name="descripcion" maxlength="255"
               value="{{ old('descripcion', $proyecto?->descripcion) }}" placeholder="Para qué es este proyecto">
        @error('descripcion') <span class="error-campo">{{ $message }}</span> @enderror
    </div>

    <div class="campo @error('estado') con-error @enderror">
        <label for="estado">Estado</label>
        <select id="estado" name="estado" required>
            @foreach (\App\Models\Proyecto::ESTADOS as $estado)
                <option value="{{ $estado }}" @selected(old('estado', $proyecto?->estado ?? 'Activo') === $estado)>{{ $estado }}</option>
            @endforeach
        </select>
        @error('estado') <span class="error-campo">{{ $message }}</span> @enderror
    </div>
</div>