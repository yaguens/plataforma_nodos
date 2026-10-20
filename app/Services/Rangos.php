<?php

namespace App\Services;

use App\Models\Rango;
use App\Models\Sitio;
use Illuminate\Support\Collection;

// ═════════════════════════════════════════════
// RANGOS · qué valores son buenos para un parámetro en un sitio.
// Orden de búsqueda: el rango del sitio concreto → el de su tipo (lago, ptar...) → ninguno.
// ═════════════════════════════════════════════
class Rangos
{
    private Collection $todos;

    public function __construct()
    {
        $this->todos = Rango::all();   // son pocos: se cargan una vez por página
    }

    public function para(int $parametroId, ?Sitio $sitio): ?Rango
    {
        if (! $sitio) {
            return null;
        }
        return $this->todos->first(fn ($r) => (int) $r->parametro_id === $parametroId && (int) $r->sitio_id === $sitio->id)
            ?? $this->todos->first(fn ($r) => (int) $r->parametro_id === $parametroId && $r->sitio_id === null && $r->tipo_sitio === $sitio->tipo);
    }

    // 'optimo' | 'aceptable' | 'fuera' | null (sin valor o sin rango). Un límite vacío = sin límite por ese lado.
    public static function nivel(?float $valor, ?Rango $rango): ?string
    {
        if ($rango === null || $valor === null) {
            return null;
        }
        $dentro = fn ($min, $max) => ($min === null || $valor >= $min) && ($max === null || $valor <= $max);

        if ($dentro($rango->optimo_min, $rango->optimo_max)) {
            return 'optimo';
        }
        if ($rango->aceptable_min === null && $rango->aceptable_max === null) {
            return 'fuera';
        }
        return $dentro($rango->aceptable_min, $rango->aceptable_max) ? 'aceptable' : 'fuera';
    }

    // Lo que necesitan las gráficas y los medidores (números o null)
    public static function paraJs(?Rango $rango): ?array
    {
        return $rango ? [
            'optimo_min' => $rango->optimo_min, 'optimo_max' => $rango->optimo_max,
            'aceptable_min' => $rango->aceptable_min, 'aceptable_max' => $rango->aceptable_max,
        ] : null;
    }
}
