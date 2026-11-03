@extends('layouts.app')

@section('titulo', 'Editar rango · ' . $parametro->nombre)

@section('contenido')
    <x-encabezado icono="ti-target" titulo="Editar rango">
        <a href="{{ route('parametros.index') }}">Parámetros y rangos</a> /
        <a href="{{ route('parametros.edit', $parametro) }}">{{ $parametro->nombre }}</a> / Editar rango
    </x-encabezado>

    <div class="panel">
        <form method="POST" action="{{ route('rangos.update', $rango) }}">
            @csrf
            @method('PUT')
            @include('rangos._formulario')

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar cambios</button>
                <a class="btn secundario" href="{{ route('parametros.edit', $parametro) }}">Cancelar</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-trash"></i> Borrar rango</h2></div>
        <p class="suave">Sin este rango, los valores de {{ $parametro->nombre }} se mostrarán sin colores de óptimo o aceptable
            (salvo que haya otro rango que los cubra).</p>
        <form method="POST" action="{{ route('rangos.destroy', $rango) }}"
              onsubmit="return confirm('¿Seguro que quieres borrar este rango? No se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn peligro"><i class="ti ti-trash"></i> Borrar rango</button>
        </form>
    </div>
@endsection