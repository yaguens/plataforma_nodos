{{--
    Barrita de un valor dentro de la escala del sensor, con las zonas del rango
    (rojo · ámbar · verde · ámbar · rojo) y una marca donde está el valor.
    Uso: <x-barra-valor :valor="7.2" :min="0" :max="14" :rango="$rango" :apagado="false" />
--}}
@props(['valor', 'min', 'max', 'rango' => null, 'apagado' => false])

@php
    $min = (float) $min;
    $max = (float) $max;
    $pos = fn ($x) => $max > $min ? round(min(100, max(0, ((float) $x - $min) / ($max - $min) * 100)), 1) : 0;

    if ($apagado || ! $rango || $max <= $min) {
        $fondo = 'var(--borde)';
    } else {
        $sinAceptable = $rango->aceptable_min === null && $rango->aceptable_max === null;
        $o1 = $rango->optimo_min !== null ? $pos($rango->optimo_min) : 0;
        $o2 = $rango->optimo_max !== null ? $pos($rango->optimo_max) : 100;
        $a1 = $sinAceptable ? $o1 : ($rango->aceptable_min !== null ? $pos($rango->aceptable_min) : 0);
        $a2 = $sinAceptable ? $o2 : ($rango->aceptable_max !== null ? $pos($rango->aceptable_max) : 100);
        $fondo = "linear-gradient(to right, var(--rojo) 0 {$a1}%, var(--ambar) {$a1}% {$o1}%, var(--verde) {$o1}% {$o2}%, "
               . "var(--ambar) {$o2}% {$a2}%, var(--rojo) {$a2}% 100%)";
    }
@endphp

@if ($valor !== null && $max > $min)
    <div class="barra-valor {{ $apagado ? 'apagada' : '' }}" title="Escala {{ $min }} – {{ $max }}">
        <span class="zonas" style="background: {{ $fondo }}"></span>
        <span class="aguja" style="left: {{ $pos($valor) }}%"></span>
    </div>
@endif
