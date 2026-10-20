<?php

namespace Database\Seeders;

use App\Models\Nodo;
use App\Models\Parametro;
use App\Models\Proyecto;
use App\Models\Rango;
use App\Models\Sitio;
use App\Services\ProcesadorTramas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ═════════════════════════════════════════════
// DATOS DE DEMOSTRACIÓN · dos sitios, dos nodos y 24 h de tramas (una cada 10 min),
// metidas por la MISMA puerta que usan los nodos reales (ProcesadorTramas).
//   php artisan db:seed --class=DemoSeeder
// Para empezar de cero en tu PC (¡borra TODOS los datos locales!): php artisan migrate:fresh
// Los rangos son de ejemplo: los reales deben darlos quienes conocen el lago y las plantas.
// ═════════════════════════════════════════════
class DemoSeeder extends Seeder
{
    public function run(ProcesadorTramas $procesador): void
    {
        if (Nodo::whereIn('codigo', ['DE0001', 'DE0002'])->exists()) {
            $this->command?->warn('Los datos de demostración ya estaban cargados.');
            return;
        }

        DB::transaction(fn () => $this->cargar($procesador));   // o todo o nada

        $this->command?->info('Datos de demostración cargados: 2 nodos con 24 h de tramas.');
    }

    private function cargar(ProcesadorTramas $procesador): void
    {
        // ───── Proyectos y sitios ─────
        $lima = Proyecto::create(['nombre' => 'Pruebas Lima', 'descripcion' => 'Pruebas de laboratorio en INICTEL-UNI']);
        $atitlan = Proyecto::create(['nombre' => 'Atitlán', 'descripcion' => 'Calidad del agua del lago Atitlán']);

        $lab = Sitio::create(['proyecto_id' => $lima->id, 'nombre' => 'Laboratorio INICTEL', 'tipo' => 'laboratorio',
            'pais' => 'Perú', 'zona_horaria' => 'America/Lima']);
        $muelle = Sitio::create(['proyecto_id' => $atitlan->id, 'nombre' => 'Muelle de Panajachel (demo)', 'tipo' => 'lago',
            'pais' => 'Guatemala', 'zona_horaria' => 'America/Guatemala']);

        // ───── Parámetros (ya revisados) ─────
        $od = Parametro::updateOrCreate(['codigo' => 'OD_OD_medida'],
            ['nombre' => 'Oxígeno disuelto', 'unidad' => 'mg/L', 'decimales' => 2, 'escala_min' => 0, 'escala_max' => 15, 'estado' => 'Activo']);
        $temp = Parametro::updateOrCreate(['codigo' => 'OD_temperatura'],
            ['nombre' => 'Temperatura del agua', 'unidad' => '°C', 'decimales' => 1, 'escala_min' => 0, 'escala_max' => 40, 'estado' => 'Activo']);
        $ph = Parametro::updateOrCreate(['codigo' => 'pH_pH'],
            ['nombre' => 'pH', 'unidad' => null, 'decimales' => 2, 'escala_min' => 0, 'escala_max' => 14, 'estado' => 'Activo']);

        // ───── Rangos de ejemplo por tipo de sitio ─────
        foreach (['lago', 'laboratorio'] as $tipo) {
            Rango::create(['parametro_id' => $od->id, 'tipo_sitio' => $tipo, 'optimo_min' => 6, 'aceptable_min' => 4, 'fuente' => 'Ejemplo']);
            Rango::create(['parametro_id' => $temp->id, 'tipo_sitio' => $tipo, 'optimo_min' => 18, 'optimo_max' => 26,
                'aceptable_min' => 15, 'aceptable_max' => 30, 'fuente' => 'Ejemplo']);
            Rango::create(['parametro_id' => $ph->id, 'tipo_sitio' => $tipo, 'optimo_min' => 6.5, 'optimo_max' => 8.5,
                'aceptable_min' => 6, 'aceptable_max' => 9, 'fuente' => 'Ejemplo']);
        }

        // ───── Nodos: Lima manda su hora local (UTC − 5 h) y Guatemala la suya (UTC − 6 h) ─────
        $nodos = [
            Nodo::create(['codigo' => 'DE0001', 'nombre' => 'Demo · laboratorio Lima', 'sitio_id' => $lab->id,
                'rol' => 'independiente', 'desfase_s' => -5 * 3600]),
            Nodo::create(['codigo' => 'DE0002', 'nombre' => 'Demo · muelle Panajachel', 'sitio_id' => $muelle->id,
                'rol' => 'maestro', 'desfase_s' => -6 * 3600]),
        ];

        // ───── 24 h de tramas, una cada 10 minutos ─────
        $ahora = intdiv(time(), 600) * 600;
        foreach ($nodos as $n => $nodo) {
            $seq = 1;
            for ($t = $ahora - 24 * 3600; $t <= $ahora; $t += 600) {
                $hora = ($t % 86400) / 3600;   // hora UTC del día, para hacer un ciclo diario
                $onda = sin(($hora - 14) / 24 * 2 * M_PI);
                $trama = [
                    'formato' => 1, 'nodo' => $nodo->codigo, 'seq' => $seq++, 'ts' => $t + $nodo->desfase_s, 'rtc' => 1,
                    'OD_est' => 1,
                    'OD_OD_medida' => round(6.4 + 1.6 * $onda - $n * 1.5 + mt_rand(-20, 20) / 100, 2),
                    'OD_temperatura' => round(22 + 3 * $onda + $n * 2 + mt_rand(-10, 10) / 10, 1),
                    'pH_est' => 1,
                    'pH_pH' => round(7.6 + 0.3 * $onda + mt_rand(-5, 5) / 100, 2),
                ];
                // Un rato sin respuesta del sensor OD en el nodo del muelle (para ver un hueco en la gráfica)
                if ($n === 1 && $seq > 60 && $seq < 66) {
                    $trama['OD_est'] = 2;
                    $trama['OD_OD_medida'] = null;
                    $trama['OD_temperatura'] = null;
                }
                $procesador->procesar($trama, 'simulador', json_encode($trama));
            }
        }

        // Que los nodos de demostración salgan "en línea"
        Nodo::whereIn('codigo', ['DE0001', 'DE0002'])->update(['ultima_conexion' => now()->utc()]);
    }
}
