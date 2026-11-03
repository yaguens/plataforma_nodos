@extends('layouts.app')

@section('titulo', 'Nuevo proyecto')

@section('contenido')
    <x-encabezado icono="ti-folder-plus" titulo="Nuevo proyecto">
        <a href="{{ route('proyectos.index') }}">Proyectos</a> / Nuevo
    </x-encabezado>

    <div class="panel">
        <form method="POST" action="{{ route('proyectos.store') }}">
            @csrf
            @include('proyectos._formulario')

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar</button>
                <a class="btn secundario" href="{{ route('proyectos.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection