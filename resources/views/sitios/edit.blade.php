@extends('layouts.app')

@section('titulo', 'Editar ' . $sitio->nombre)

@section('contenido')
    <x-encabezado icono="ti-map-pin" :titulo="$sitio->nombre">
        <a href="{{ route('sitios.index') }}">Sitios</a> / Editar
    </x-encabezado>

    @if (session('error'))
        <div class="aviso-caja error"><i class="ti ti-alert-triangle"></i> {{ session('error') }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ route('sitios.update', $sitio) }}">
            @csrf
            @method('PUT')
            @include('sitios._formulario')

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar cambios</button>
                <a class="btn secundario" href="{{ route('sitios.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-titulo"><h2><i class="ti ti-trash"></i> Borrar sitio</h2></div>
        @if ($tieneLecturas)
            <p class="suave">Este sitio tiene lecturas guardadas: no se puede borrar, porque esos datos perderían el lugar donde se midieron.</p>
        @else
            <p class="suave">
                @if ($sitio->nodos_count > 0)
                    Sus {{ $sitio->nodos_count }} {{ $sitio->nodos_count === 1 ? 'nodo quedará' : 'nodos quedarán' }} «sin sitio asignado».
                @endif
                Los rangos definidos solo para este sitio también se borrarán.</p>
            <form method="POST" action="{{ route('sitios.destroy', $sitio) }}"
                  onsubmit="return confirm('¿Seguro que quieres borrar el sitio «{{ $sitio->nombre }}»? No se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn peligro"><i class="ti ti-trash"></i> Borrar sitio</button>
            </form>
        @endif
    </div>
@endsection