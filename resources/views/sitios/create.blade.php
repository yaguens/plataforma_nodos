@extends('layouts.app')

@section('titulo', 'Nuevo sitio')

@section('contenido')
    <x-encabezado icono="ti-map-pin-plus" titulo="Nuevo sitio">
        <a href="{{ route('sitios.index') }}">Sitios</a> / Nuevo
    </x-encabezado>

    @if ($proyectos->isEmpty())
        <div class="aviso-caja error"><i class="ti ti-alert-triangle"></i>
            Un sitio tiene que pertenecer a un proyecto y todavía no hay ninguno.
            <a href="{{ route('proyectos.create') }}">Crea un proyecto</a> primero.</div>
    @else
        <div class="panel">
            <form method="POST" action="{{ route('sitios.store') }}">
                @csrf
                @include('sitios._formulario')

                <div class="acciones-formulario">
                    <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar</button>
                    <a class="btn secundario" href="{{ route('sitios.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    @endif
@endsection