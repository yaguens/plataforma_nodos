// ═════════════════════════════════════════════
// GRÁFICAS · medidores y líneas con ECharts
// Las usa la vista de cada nodo. Cada página carga antes ECharts:
//   <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
//   <script src="/js/graficas.js"></script>
// ═════════════════════════════════════════════

const COLORES = {
    verde: '#16a34a', ambar: '#f59e0b', rojo: '#e11d48',   // estados (siempre con texto al lado)
    neutro: '#cbd5e1', tinta: '#1c2b36', suave: '#6a7c8a', rejilla: '#e3e9ee'
};

// Número con formato de Perú (punto decimal, coma de miles)
function formatoNumero(valor, decimales) {
    return Number(valor).toLocaleString('en-US', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
}

// Evita que un nombre con símbolos raros se cuele como código en los textos emergentes
function escapar(texto) {
    return String(texto).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

// Nombre de un código de estado del firmware (la página define window.CODIGOS_ESTADO)
function nombreCodigo(codigo) {
    const lista = window.CODIGOS_ESTADO || {};
    return lista[codigo] ? lista[codigo] : 'código ' + codigo;
}

// Colores del arco de un medidor según el rango del proyecto:
// rojo (fuera) · ámbar (aceptable) · verde (óptimo) · ámbar · rojo
function bandasMedidor(p) {
    const min = p.escala_min, max = p.escala_max, r = p.rango;
    if (!r) return [[1, COLORES.neutro]];
    const posicion = function (x) { return Math.min(1, Math.max(0, (x - min) / (max - min))); };
    const optMin = r.optimo_min !== null ? r.optimo_min : min;
    const optMax = r.optimo_max !== null ? r.optimo_max : max;
    const sinAceptable = r.aceptable_min === null && r.aceptable_max === null;
    const aceMin = sinAceptable ? optMin : (r.aceptable_min !== null ? r.aceptable_min : min);
    const aceMax = sinAceptable ? optMax : (r.aceptable_max !== null ? r.aceptable_max : max);
    return [
        [posicion(aceMin), COLORES.rojo],
        [posicion(optMin), COLORES.ambar],
        [posicion(optMax), COLORES.verde],
        [posicion(aceMax), COLORES.ambar],
        [1, COLORES.rojo]
    ];
}

// Número de divisiones de la escala para que las marcas sean números redondos
// (0-14 → 7 divisiones de 2; 0-20 → 4 de 5; 0-50 → 5 de 10)
function divisionesRedondas(ancho) {
    const opciones = [5, 4, 7, 6, 8, 10];
    for (let i = 0; i < opciones.length; i++) {
        const paso = ancho / opciones[i];
        const base = paso / Math.pow(10, Math.floor(Math.log10(paso)));
        if ([1, 2, 2.5, 5].some(function (b) { return Math.abs(base - b) < 1e-9; })) return opciones[i];
    }
    return 5;
}

// ───── Medidor (gauge) de un valor ─────
// p = { valor, escala_min, escala_max, unidad, decimales, rango, apagado, compacto }
// compacto: true → letras más pequeñas (medidores pequeños del panel de sensores)
// apagado: true → todo en gris (el dato es antiguo y no representa el estado actual)
function dibujarMedidor(elemento, p) {
    const grafica = echarts.init(elemento);
    const dentro = Math.min(p.escala_max, Math.max(p.escala_min, p.valor));   // la aguja no se sale
    const tinta = p.apagado ? COLORES.suave : COLORES.tinta;
    const c = p.compacto;
    grafica.setOption({
        animation: false,
        series: [{
            type: 'gauge',
            min: p.escala_min,
            max: p.escala_max,
            startAngle: 210,
            endAngle: -30,
            radius: '94%',
            center: ['50%', '56%'],
            splitNumber: divisionesRedondas(p.escala_max - p.escala_min),
            axisLine: { lineStyle: { width: c ? 9 : 12, color: p.apagado ? [[1, COLORES.neutro]] : bandasMedidor(p) } },
            pointer: { length: '58%', width: c ? 3 : 4, itemStyle: { color: tinta } },
            anchor: { show: true, size: 10, itemStyle: { color: tinta } },
            axisTick: { distance: c ? -9 : -12, length: 4, lineStyle: { color: '#fff', width: 1 } },
            splitLine: { distance: c ? -9 : -12, length: c ? 9 : 12, lineStyle: { color: '#fff', width: 2 } },
            axisLabel: {
                distance: c ? 12 : 16, color: COLORES.suave, fontSize: c ? 9 : 10,
                formatter: function (v) { return Number.isInteger(v) ? v : v.toFixed(1); }
            },
            title: { show: false },
            detail: {
                offsetCenter: [0, '68%'], fontSize: c ? 15 : 20, fontWeight: 700, color: tinta,
                formatter: function () { return formatoNumero(p.valor, p.decimales) + (p.unidad ? ' ' + p.unidad : ''); }
            },
            data: [{ value: dentro }]
        }]
    });
    window.addEventListener('resize', function () { grafica.resize(); });
}

// Redondea un extremo del eje a un número "limpio" (6.09 → 6, 30.7 → 31)
function redondearEje(x, hacia) {
    const orden = Math.floor(Math.log10(Math.abs(x) || 1)) - 1;
    const paso = Math.pow(10, orden);
    const r = hacia === 'abajo' ? Math.floor(x / paso) * paso : Math.ceil(x / paso) * paso;
    return Number(r.toFixed(Math.max(0, -orden)));   // sin restos de coma flotante (5.300000000000001 → 5.3)
}

// ───── Línea en el tiempo de un parámetro ─────
// g = { unidad, decimales, grupo, zoom, rango, inicio, fin (opcionales, en ms),
//       incluirRango (opcional: el eje vertical llega hasta los límites del rango óptimo,
//                     para que se vea si los valores están dentro o fuera de él),
//       series: [{ nombre, color, puntos: [[momento, valor o null, código], ...] }] }
// Un valor null deja un HUECO (nunca se une con una línea que invente valores)
// y al pasar el ratón se ve el código de fallo del sensor.
function dibujarLinea(elemento, g) {
    const grafica = echarts.init(elemento);
    const variasSeries = g.series.length > 1;

    // Zona verde = rango óptimo del proyecto (un lado sin límite llega hasta el borde)
    const zonaOptima = g.rango && (g.rango.optimo_min !== null || g.rango.optimo_max !== null) ? {
        silent: true,
        itemStyle: { color: 'rgba(22, 163, 74, 0.08)' },
        data: [[
            { yAxis: g.rango.optimo_min !== null ? g.rango.optimo_min : -1e9 },
            { yAxis: g.rango.optimo_max !== null ? g.rango.optimo_max : 1e9 }
        ]]
    } : undefined;

    // Límites del rango óptimo que el eje vertical debe incluir (si la página lo pide)
    const limitesEje = (g.incluirRango && g.rango)
        ? [g.rango.optimo_min, g.rango.optimo_max].filter(function (x) { return x !== null && x !== undefined; })
        : [];

    grafica.setOption({
        animation: false,
        useUTC: true,   // los momentos llegan ya en la hora del sitio
        grid: { left: 6, right: 14, top: variasSeries ? 34 : 12, bottom: 4, containLabel: true },
        legend: variasSeries ? {
            type: 'scroll', top: 0, left: 0, right: 0, icon: 'roundRect', itemWidth: 14, itemHeight: 3,
            textStyle: { color: COLORES.suave, fontSize: 12 }, pageIconSize: 10
        } : { show: false },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'line', lineStyle: { color: COLORES.suave } },
            formatter: function (puntos) {
                const momento = echarts.time.format(puntos[0].value[0], '{dd}/{MM}/{yyyy} {HH}:{mm}:{ss}', true);
                let html = '<div style="font-size:12px;color:' + COLORES.suave + '">' + momento + '</div>';
                puntos.forEach(function (p) {
                    const valor = p.value[1], codigo = p.value[2];
                    const texto = (valor === null || valor === undefined)
                        ? 'Sin dato' + (codigo !== null && codigo !== undefined && codigo !== 1 ? ' · ' + nombreCodigo(codigo) : '')
                        : formatoNumero(valor, g.decimales) + (g.unidad ? ' ' + g.unidad : '');
                    html += '<div><span style="display:inline-block;width:12px;height:3px;border-radius:2px;margin-right:6px;vertical-align:middle;background:'
                          + p.color + '"></span><strong>' + escapar(texto) + '</strong>'
                          + (variasSeries ? ' <span style="color:' + COLORES.suave + '">' + escapar(p.seriesName) + '</span>' : '') + '</div>';
                });
                return html;
            }
        },
        xAxis: {
            type: 'time',
            min: g.inicio,   // si la página da el periodo, el eje lo abarca entero
            max: g.fin,      // (si no, se ajusta solo a los datos)
            axisLine: { lineStyle: { color: COLORES.rejilla } },
            axisTick: { show: false },
            axisLabel: { color: COLORES.suave, hideOverlap: true,
                         formatter: { year: '{yyyy}', month: '{dd}/{MM}', day: '{dd}/{MM}', hour: '{HH}:{mm}', minute: '{HH}:{mm}' } },
            splitLine: { show: false }
        },
        yAxis: {
            type: 'value',
            scale: true,
            min: limitesEje.length ? function (v) { return redondearEje(Math.min.apply(null, [v.min].concat(limitesEje)), 'abajo'); } : undefined,
            max: limitesEje.length ? function (v) { return redondearEje(Math.max.apply(null, [v.max].concat(limitesEje)), 'arriba'); } : undefined,
            axisLabel: { color: COLORES.suave },
            splitLine: { lineStyle: { color: COLORES.rejilla } }
        },
        dataZoom: g.zoom ? [{ type: 'inside', zoomOnMouseWheel: 'shift', moveOnMouseWheel: false }] : [],
        series: g.series.map(function (s, i) {
            // Un valor suelto entre dos fallos no tiene línea: se dibuja como un punto para que se vea
            const suelto = s.puntos.map(function (p, j) {
                if (p[1] === null) return false;
                const antes = j === 0 || s.puntos[j - 1][1] === null;
                const despues = j === s.puntos.length - 1 || s.puntos[j + 1][1] === null;
                return antes && despues;
            });
            const pocos = s.puntos.length < 40;
            return {
                name: s.nombre,
                type: 'line',
                data: s.puntos,
                showSymbol: true,
                symbolSize: function (valor, params) { return (pocos || suelto[params.dataIndex]) ? 8 : 0; },
                connectNulls: false,
                lineStyle: { width: 2, color: s.color },
                itemStyle: { color: s.color, borderColor: '#fff', borderWidth: 2 },
                markArea: i === 0 ? zonaOptima : undefined
            };
        })
    });

    if (g.grupo) {
        grafica.group = g.grupo;   // las gráficas del mismo grupo mueven el cursor a la vez
    }
    window.addEventListener('resize', function () { grafica.resize(); });
    return grafica;
}
