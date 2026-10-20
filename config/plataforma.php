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

    // Un nodo está "en línea" si ha enviado algo en estos minutos
    'minutos_en_linea' => 30,

];
