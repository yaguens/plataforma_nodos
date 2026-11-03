@extends('layouts.app')

@section('titulo', 'Nuevo rango · ' . $parametro->nombre)

@section('contenido')
    <x-encabezado icono="ti-target" titulo="Nuevo rango">
        <a href="{{ route('parametros.index') }}">Parámetros y rangos</a> /
        <a href="{{ route('parametros.edit', $parametro) }}">{{ $parametro->nombre }}</a> / Nuevo rango
    </x-encabezado>

    <div class="panel">
        <form method="POST" action="{{ route('parametros.rangos.store', $parametro) }}">
            @csrf
            @include('rangos._formulario')

            <div class="acciones-formulario">
                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> Guardar</button>
                <a class="btn secundario" href="{{ route('parametros.edit', $parametro) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection