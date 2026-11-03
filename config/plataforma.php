<?php

// ═════════════════════════════════════════════
// Ajustes propios de la plataforma de nodos (se leen con config('plataforma.xxx'))
// ═════════════════════════════════════════════

return [

    // Desfase con el que se da de alta un nodo nuevo (segundos respecto a UTC).
    // El nodo manda su hora local: Lima −5 h = −18000; Guatemala −6 h = −21600.
    'desfase_por_defecto' => (int) env('PLATAFORMA_DESFASE', -18000),

    // Zona horaria con la que se muestran las horas de un nodo que aún no tiene sitio
    'zona_por_defecto' => env('PLATAFORMA_ZONA', 'America/Lima'),

    // Más diferencia que esto entre la hora de la trama y la del servidor → marca "reloj_desajustado"
    'segundos_desajuste' => 300,

    // Una fecha anterior es imposible (01/01/2026 00:00 UTC)
    'inicio_datos_utc' => 1767225600,

    // Tramas como máximo en un reenvío en lote (la especificación pide aceptar al menos 50)
    'max_tramas_lote' => 500,

    // Un nodo está "en línea" si ha enviado algo en estos minutos; "sin conexión" pasadas estas horas
    'minutos_en_linea' => 25,
    'horas_sin_conexion' => 24,

    // Códigos de estado de los sensores (<fuente>_est). Nunca cambian de significado; los nuevos van al final.
    'codigos_estado' => [
        0 => ['SIN_DATOS', 'No se ha intentado leer'],
        1 => ['OK', 'Lectura correcta'],
        2 => ['SIN_RESPUESTA', 'El sensor no contesta: revisar cable o alimentación'],
        3 => ['TRAMA_INVALIDA', 'Datos corruptos (CRC o longitud)'],
        4 => ['SIN_HORA', 'El reloj RTC no responde'],
        5 => ['SD_AUSENTE', 'La tarjeta SD no responde'],
        6 => ['SD_SIN_FORMATO', 'La tarjeta SD no se puede montar'],
        7 => ['SD_LLENA', 'Menos de 1 MB libre en la SD'],
        8 => ['SD_ERROR_ESCRITURA', 'Falla al escribir en la SD'],
    ],

];
