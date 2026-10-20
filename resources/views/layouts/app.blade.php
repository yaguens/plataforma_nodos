<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Plataforma de nodos')</title>
    <style>
        :root {
            --fondo: #f3f6f8; --blanco: #fff; --texto: #1c2b36; --suave: #6a7c8a; --borde: #e3e9ee;
            --acento: #0f8a7e; --verde: #16a34a; --ambar: #b45309; --rojo: #be123c; --gris: #64748b;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: var(--fondo); color: var(--texto); font-size: 14px; }
        header { background: #12303a; color: #fff; padding: 14px 28px; display: flex; align-items: baseline; gap: 14px; }
        header strong { font-size: 17px; }
        header span { color: #9fb7c0; font-size: 13px; }
        main { max-width: 1200px; margin: 0 auto; padding: 24px 20px 60px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 16px; margin: 0 0 12px; }
        .suave { color: var(--suave); }
        .panel { background: var(--blanco); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 18px 20px; margin-bottom: 20px; }
        .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin: 18px 0 20px; }
        .dato { background: var(--blanco); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 14px 18px; }
        .dato .cifra { display: block; font-size: 24px; font-weight: 700; }
        .dato .txt { color: var(--suave); font-size: 12.5px; }
        .tabla-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--suave); padding: 8px 10px; border-bottom: 1px solid var(--borde); white-space: nowrap; }
        td { padding: 8px 10px; border-bottom: 1px solid var(--borde); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .codigo { font-family: Consolas, monospace; background: #eef3f6; padding: 1px 6px; border-radius: 4px; font-size: 12.5px; }
        .pill { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
        .pill.verde { background: #dcfce7; color: #166534; } .pill.ambar { background: #fef3c7; color: var(--ambar); }
        .pill.rojo { background: #ffe4e6; color: var(--rojo); } .pill.gris { background: #eef2f6; color: var(--gris); }
        .pill.azul { background: #e0f2fe; color: #075985; }
        details summary { cursor: pointer; color: var(--acento); }
        pre { background: #0f1f26; color: #d6e6ea; padding: 10px 12px; border-radius: 8px; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
        textarea { width: 100%; min-height: 150px; font-family: Consolas, monospace; font-size: 12.5px; border: 1px solid var(--borde); border-radius: 8px; padding: 10px; }
        .btn { background: var(--acento); color: #fff; border: 0; border-radius: 8px; padding: 9px 16px; font-weight: 600; cursor: pointer; }
        .btn.secundario { background: #eef3f6; color: var(--texto); }
        .fila { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 10px; }
        .dos-columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .dos-columnas { grid-template-columns: 1fr; } }
        .respuesta { margin-top: 10px; padding: 10px 12px; border-radius: 8px; font-family: Consolas, monospace; font-size: 12.5px; }
        .respuesta.bien { background: #dcfce7; } .respuesta.mal { background: #ffe4e6; }
        .vacio { color: var(--suave); padding: 18px 0; text-align: center; }
    </style>
</head>
<body>
    <header>
        <strong>Plataforma de nodos</strong>
        <span>Recepción de datos de nodos modulares · Guatemala · Lima · Madrid</span>
    </header>
    <main>
        @yield('contenido')
    </main>
</body>
</html>
