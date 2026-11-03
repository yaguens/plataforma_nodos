@extends('layouts.app')

@section('titulo', 'Editar ' . $proyecto->nombre)

@section('contenido')
    <x-encabezado icono="ti-folder" :titulo="$proyecto->nombre">
        <a href="{{ route('proyectos.index') }}">Proyectos</a> / Editar
    </x-encabezado>

    @if (session('error'))
        <div class="aviso-caja error"><i class="ti ti-alert-triangle"></i> {{ session('error') }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ route('proyectos.update', $proyecto) }}">
            @csrf
            @method('PUT')
            @include('proyectos._formulario')

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar cambios</button>
                <a class="btn secundario" href="{{ route('proyectos.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- Borrar: formulario aparte, porque usa otro método (DELETE) --}}
    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-trash"></i> Borrar proyecto</h2></div>
        @if ($proyecto->sitios_count > 0)
            <p class="suave">Este proyecto tiene {{ $proyecto->sitios_count }}
                {{ $proyecto->sitios_count === 1 ? 'sitio' : 'sitios' }}: no se puede borrar mientras los tenga.</p>
        @else
            <form method="POST" action="{{ route('proyectos.destroy', $proyecto) }}"
                  onsubmit="return confirm('¿Seguro que quieres borrar el proyecto «{{ $proyecto->nombre }}»? No se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn peligro"><i class="ti ti-trash"></i> Borrar proyecto</button>
            </form>
        @endif
    </div>
@endsection