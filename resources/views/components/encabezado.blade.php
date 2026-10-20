{{--
    Encabezado de una página: icono, título, una línea debajo (el contenido) y botones a la derecha.
    Uso:
    <x-encabezado icono="ti-layout-dashboard" titulo="Panel de nodos">
        Texto de debajo
        <x-slot:acciones> <a class="btn" href="...">Botón</a> </x-slot:acciones>
    </x-encabezado>
--}}
@props(['icono', 'titulo'])

<div class="encabezado">
    <div class="titulo">
        <span class="icono-pagina"><i class="ti {{ $icono }}"></i></span>
        <div>
            <h1>{{ $titulo }}</h1>
            @if (trim($slot) !== '')
                <div class="ruta">{{ $slot }}</div>
            @endif
        </div>
    </div>
    @isset($acciones)
        <div class="acciones">{{ $acciones }}</div>
    @endisset
</div>
