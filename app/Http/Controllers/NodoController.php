<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Nodo;
use App\Models\Parametro;
use App\Services\Rangos;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// VISTA DEL NODO · cómo está un nodo AHORA (medidores) y sus últimas horas (gráficas).
//   /nodos/3            → últimas 24 h
//   /nodos/3?horas=6    → últimas 6 h (también 24 y 168 = 7 días)
// Los parámetros con "mostrar como medidor" salen como gauge; el resto, como número con barrita.
// Un dato con más de "minutos_en_linea" sale en gris: ya no representa el estado actual.
// Las horas se guardan en UTC y aquí se muestran en la zona horaria del sitio del nodo.
// ═════════════════════════════════════════════
class NodoController extends Controller
{
    public const PERIODOS = [6 => '6 h', 24 => '24 h', 168 => '7 días'];

    public function show(Request $request, Nodo $nodo, Rangos $rangos): View
    {
        $horas = (int) $request->query('horas', 24);
        if (! array_key_exists($horas, self::PERIODOS)) {
            $horas = 24;
        }

        $nodo->load('sitio.proyecto');
        $zona = $nodo->zonaHoraria();
        $minutos = config('plataforma.minutos_en_linea');

        // Parámetros que envía este nodo (menos los ocultos)
        $parametros = Parametro::whereIn('id', Lectura::where('nodo_id', $nodo->id)->select('parametro_id')->distinct())
            ->where('estado', '!=', 'Oculto')->orderBy('id')->get();

        // ───── Ahora: el último valor de cada parámetro ─────
        $tarjetas = [];
        $medidores = [];
        foreach ($parametros as $p) {
            $l = Lectura::where('nodo_id', $nodo->id)->where('parametro_id', $p->id)
                ->orderByRaw('medido_en IS NULL')->orderByDesc('medido_en')->orderByDesc('id')->first();

            $rango = $rangos->para($p->id, $nodo->sitio);
            $apagado = ! $l->medido_en || $l->medido_en->diffInMinutes(now(), true) > $minutos;
            $comoMedidor = $p->mostrar_medidor && $l->valor !== null;

            $tarjetas[] = [
                'parametro' => $p, 'lectura' => $l, 'rango' => $rango, 'apagado' => $apagado,
                'nivel' => Rangos::nivel($l->valor, $rango), 'medidor' => $comoMedidor,
                'hora' => $l->medido_en?->copy()->setTimezone($zona),
            ];
            if ($comoMedidor) {
                $medidores['medidor-' . $p->id] = [
                    'valor' => $l->valor,
                    'escala_min' => $p->escala_min,
                    'escala_max' => $p->escala_max,
                    'unidad' => (string) $p->unidad,
                    'decimales' => $p->decimales,
                    'rango' => Rangos::paraJs($rango),
                    'apagado' => $apagado,
                ];
            }
        }
        usort($tarjetas, fn ($a, $b) => $b['medidor'] <=> $a['medidor']);   // primero los medidores

        // ───── Gráficas de las últimas horas ─────
        $desde = now()->utc()->subHours($horas);
        $lecturas = Lectura::where('nodo_id', $nodo->id)->where('medido_en', '>=', $desde)
            ->whereIn('parametro_id', $parametros->pluck('id'))
            ->orderBy('medido_en')->orderBy('id')
            ->get(['parametro_id', 'valor', 'codigo_estado', 'medido_en']);

        $corte = $minutos * 60 * 1000;   // un hueco mayor que esto corta la línea (no se inventan valores)
        $graficas = [];
        foreach ($lecturas->groupBy('parametro_id') as $pid => $lista) {
            $p = $parametros->firstWhere('id', $pid);
            $rango = Rangos::paraJs($rangos->para($p->id, $nodo->sitio));
            $puntos = [];
            $anterior = null;
            foreach ($lista as $l) {
                $momento = $this->momento($l->medido_en, $zona);
                if ($anterior !== null && $momento - $anterior > $corte) {
                    $puntos[] = [$anterior + 1, null, null];
                }
                $puntos[] = [$momento, $l->valor, $l->codigo_estado];
                $anterior = $momento;
            }
            $graficas[$pid] = [
                'nombre' => $p->nombre,
                'unidad' => (string) $p->unidad,
                'decimales' => $p->decimales,
                'grupo' => 'nodo',
                'zoom' => true,
                'inicio' => $this->momento($desde, $zona),
                'fin' => $this->momento(now()->utc(), $zona),
                'rango' => $rango ? ['optimo_min' => $rango['optimo_min'], 'optimo_max' => $rango['optimo_max']] : null,
                'series' => [['nombre' => $nodo->nombre, 'color' => '#2a78d6', 'puntos' => $puntos]],
            ];
        }

        return view('nodo', [
            'nodo' => $nodo,
            'estado' => $nodo->estado(),
            'zona' => $zona,
            'horas' => $horas,
            'periodos' => self::PERIODOS,
            'tarjetas' => $tarjetas,
            'medidores' => $medidores,
            'graficas' => $graficas,
            'codigos' => array_map(fn ($c) => $c[0], config('plataforma.codigos_estado')),
        ]);
    }

    // Momento para las gráficas (milisegundos): la hora del SITIO escrita como si fuera UTC.
    // La gráfica dibuja en UTC (useUTC), así que siempre enseña la hora del sitio,
    // esté donde esté quien mira la página.
    private function momento(CarbonInterface $fecha, string $zona): int
    {
        $local = $fecha->copy()->setTimezone($zona);
        return ($local->getTimestamp() + $local->getOffset()) * 1000;
    }
}
