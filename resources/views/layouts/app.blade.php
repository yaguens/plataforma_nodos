<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Plataforma de nodos') · Plataforma de nodos</title>

    {{-- Fuente Inter e iconos Tabler (desde internet), como en la web anterior --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/plataforma.css') }}?v={{ filemtime(public_path('css/plataforma.css')) }}">
</head>
<body>
<div class="app">

    {{-- ═════ MENÚ LATERAL ═════ --}}
    <aside class="lateral">
        <a class="marca" href="{{ route('panel') }}">
            <span class="logo"><i class="ti ti-broadcast"></i></span>
            <span>
                <span class="nombre">Plataforma de nodos</span>
                <span class="sub">Monitorización IoT modular</span>
            </span>
        </a>

        <nav class="menu">
            <div class="seccion">Monitorización</div>
            <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel', 'nodos.show') ? 'activo' : '' }}">
                <i class="ti ti-layout-dashboard"></i> Panel de nodos
            </a>
            <span class="proximo"><i class="ti ti-chart-line"></i> Historial y exportación <span class="etiqueta">pronto</span></span>

            <div class="seccion">Recepción</div>
            <a href="{{ route('tramas') }}" class="{{ request()->routeIs('tramas') ? 'activo' : '' }}">
                <i class="ti ti-api"></i> Tramas y API
            </a>

            <div class="seccion">Configuración</div>
            <a href="{{ route('proyectos.index') }}" class="{{ request()->routeIs('proyectos.*') ? 'activo' : '' }}">
                <i class="ti ti-folders"></i> Proyectos
            </a>
            <a href="{{ route('sitios.index') }}" class="{{ request()->routeIs('sitios.*') ? 'activo' : '' }}">
                <i class="ti ti-map-pin"></i> Sitios
            </a>
            <a href="{{ route('nodos.index') }}" class="{{ request()->routeIs('nodos.index', 'nodos.edit') ? 'activo' : '' }}">
                <i class="ti ti-cpu"></i> Nodos
            </a>
            <a href="{{ route('parametros.index') }}" class="{{ request()->routeIs('parametros.*', 'parametros.rangos.*', 'rangos.*') ? 'activo' : '' }}">
                <i class="ti ti-adjustments-horizontal"></i> Parámetros y rangos
            </a>

            <div class="seccion">Información</div>
            <a href="{{ route('acerca') }}" class="{{ request()->routeIs('acerca') ? 'activo' : '' }}">
                <i class="ti ti-info-circle"></i> Acerca de la plataforma
            </a>
        </nav>

        {{-- Logos: copia los archivos en public/img/ con estos nombres --}}
        <div class="logos-lateral">
            <a href="https://www.upm.es" target="_blank" rel="noopener" title="Universidad Politécnica de Madrid">
                <img src="{{ asset('img/logo-upm.png') }}" alt="UPM" onerror="this.closest('a').style.display='none'">
            </a>
            <a href="https://www.inictel-uni.edu.pe" target="_blank" rel="noopener" title="INICTEL-UNI">
                <img src="{{ asset('img/logo-inictel.png') }}" alt="INICTEL-UNI" onerror="this.closest('a').style.display='none'">
            </a>
        </div>
        <div class="pie-lateral">UPM · INICTEL-UNI · 2026</div>
    </aside>

    {{-- ═════ CONTENIDO ═════ --}}
    <div class="contenido">
        <header class="barra-superior">
            <span class="chip" title="Hora universal: así se guardan todas las horas">
                <i class="ti ti-world"></i> UTC <strong id="reloj-utc">{{ now()->utc()->format('H:i') }}</strong>
            </span>
            <span class="chip"><i class="ti ti-calendar"></i> {{ now()->utc()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</span>
        </header>

        <main class="pagina">
            @yield('contenido')
        </main>
    </div>
</div>

<script>
    // Reloj UTC de la barra superior
    (function () {
        const reloj = document.getElementById('reloj-utc');
        setInterval(function () {
            reloj.textContent = new Date().toISOString().slice(11, 16);
        }, 10000);
    })();
</script>
@stack('scripts')
</body>
</html>