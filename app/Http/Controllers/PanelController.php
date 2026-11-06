<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Incidencia;
use App\Models\Lectura;
use App\Models\Nodo;
use App\Models\Parametro;
use App\Services\ProcesadorTramas;
use App\Services\Rangos;
use Illuminate\View\View;

// ═════════════════════════════════════════════
// PANEL · estado de cada nodo de un vistazo, agrupados por sitio.
// Es la forma de saber, sin ir al campo, que un nodo o un sensor ha dejado de funcionar.
// Todo se calcula con lo que ya llega en las tramas.
// - Funcionmiento: se hace una consulta por cada nodo, y otra por cada lectura del último ciclo de cada nodo. Luego se calcula el estado de cada nodo y de sus sensores, y se agrupan por sitio.
// Primera parte: cuatro consultas ($nodos, $ultimos, $lecturas, $conflictos...)
// Después: calcula el estado, los sensores y los avisos de ESTE nodo
// ═════════════════════════════════════════════
class PanelController extends Controller
{
    public function index(Rangos $rangos): View
    {
        $nodos = Nodo::with('sitio.proyecto')->orderBy('codigo')->get();

        // El último ciclo MEDIDO de cada nodo (por hora de medida, no de llegada: un lote o la SD
        // pueden traer ciclos antiguos después de los nuevos) y sus lecturas
        $ultimos = $nodos->mapWithKeys(fn (Nodo $nodo) => [$nodo->id => Ciclo::where('nodo_id', $nodo->id)
            ->orderByRaw('medido_en IS NULL')->orderByDesc('medido_en')->orderByDesc('id')->first()])
            ->filter();
        $lecturas = Lectura::with('parametro')->whereIn('ciclo_id', $ultimos->pluck('id'))->orderBy('id')->get()
            ->filter(fn ($l) => $l->parametro->estado !== 'Oculto')
            ->groupBy('nodo_id');

        // Huecos en seq de cada nodo (ciclos que faltan entre el primero y el último)
        $resumen = Ciclo::selectRaw('nodo_id, COUNT(seq) AS con_seq, MIN(seq) AS seq_min, MAX(seq) AS seq_max')
            ->groupBy('nodo_id')->get()->keyBy('nodo_id');

        // Conflictos sin revisar de cada nodo
        $conflictos = Incidencia::where('tipo', 'conflicto')->where('revisada', false)->whereNotNull('nodo_id')
            ->selectRaw('nodo_id, COUNT(*) AS n')->groupBy('nodo_id')->pluck('n', 'nodo_id');

        $tarjetas = $nodos->map(function (Nodo $nodo) use ($ultimos, $lecturas, $resumen, $conflictos, $rangos) {
            [$estado, $color] = $nodo->estado();
            $ciclo = $ultimos->get($nodo->id);
            $apagado = $estado !== 'En línea';
            $avisos = [];   // [nivel, texto] · nivel: 3 rojo · 2 ámbar · 1 gris

            if ($estado === 'En silencio') {
                $avisos[] = [2, 'En silencio: sin datos ' . $nodo->ultima_conexion->locale('es')->diffForHumans()];
            }
            if ($estado === 'Sin conexión') {
                $avisos[] = [3, 'Sin conexión: sin datos ' . $nodo->ultima_conexion->locale('es')->diffForHumans()];
            }
            if (! $nodo->sitio) {
                $avisos[] = [2, 'Sin sitio: asígnale uno para ver su hora local y los rangos'];
            }

            // Valores del último ciclo, por sensor (fuente)
            $sensores = [];
            foreach ($lecturas->get($nodo->id, collect()) as $l) {
                $rango = $rangos->para($l->parametro_id, $nodo->sitio);
                $sensores[Parametro::fuenteDe($l->parametro->codigo)][] = [
                    'lectura' => $l,
                    'parametro' => $l->parametro,
                    'rango' => $rango,
                    'nivel' => Rangos::nivel($l->valor, $rango),
                ];
            }
            foreach ($sensores as $fuente => $valores) {
                $codigo = $valores[0]['lectura']->codigo_estado;
                if ($codigo !== null && $codigo !== 1) {
                    [$nombre, $ayuda] = config('plataforma.codigos_estado')[$codigo] ?? ['Código ' . $codigo, 'código nuevo del firmware'];
                    $avisos[] = [2, 'Sensor ' . $fuente . ': ' . $nombre . ' — ' . $ayuda];
                }
            }

            // Hora del nodo
            $marcas = array_filter(explode(',', (string) $ciclo?->marcas));
            if (array_intersect($marcas, ['sin_hora', 'hora_invalida'])) {
                $avisos[] = [2, 'Nodo sin hora: el reloj no responde o da fechas imposibles'];
            }
            if (in_array('reloj_desajustado', $marcas)) {
                $avisos[] = [2, 'Reloj desajustado: más de 5 min de diferencia con el servidor (¿desfase horario mal puesto?)'];
            }

            // Conflictos y ciclos pendientes
            if ($n = $conflictos->get($nodo->id)) {
                $avisos[] = [3, $n . ($n == 1 ? ' conflicto' : ' conflictos') . ' sin revisar (mismo seq con otro contenido)'];
            }
            $r = $resumen->get($nodo->id);
            if ($r && $r->con_seq > 0) {
                $huecos = ($r->seq_max - $r->seq_min + 1) - $r->con_seq;
                if ($huecos > 0) {
                    $avisos[] = [1, $huecos . ($huecos == 1 ? ' ciclo pendiente' : ' ciclos pendientes') . ': llegarán en lote o desde la SD'];
                }
            }

            usort($avisos, fn ($a, $b) => $b[0] <=> $a[0]);

            return [ // devuelve UNA tarjeta
                'nodo' => $nodo,
                'estado' => $estado,
                'color' => $color,
                'ciclo' => $ciclo,
                'apagado' => $apagado,
                'sensores' => $sensores,
                'avisos' => $avisos,
                'gravedad' => $avisos ? $avisos[0][0] : 0,
                'grupo' => $nodo->sitio ? $nodo->sitio->proyecto->nombre . ' · ' . $nodo->sitio->nombre : 'Sin sitio asignado',
            ];
        });
        //dd($tarjetas->first());
        // Ordena y agrupa las tarjetas por proyecto y sitio, y dentro de cada grupo por gravedad y código de nodo
        // Grupos por sitio (los nodos sin sitio, al final) y, dentro, los más graves primero
        $grupos = $tarjetas->sortBy([['gravedad', 'desc'], fn ($a, $b) => strcmp($a['nodo']->codigo, $b['nodo']->codigo)])
            ->groupBy('grupo')
            ->sortKeys()
            ->sortBy(fn ($tarjetas, $grupo) => $grupo === 'Sin sitio asignado' ? 1 : 0);

        return view('panel', [
            'grupos' => $grupos,
            'total' => $nodos->count(),
            'enLinea' => $tarjetas->where('estado', 'En línea')->count(),
            'enAlarma' => $tarjetas->where('gravedad', 3)->count(),
            'pendientes' => Parametro::where('estado', 'Pendiente')->count(),
            'marcasNombres' => ProcesadorTramas::MARCAS,
        ]);
    }
}
