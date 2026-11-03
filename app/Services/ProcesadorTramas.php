<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Incidencia;
use App\Models\Lectura;
use App\Models\Nodo;
use App\Models\Parametro;
use App\Models\Sonda;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// ═════════════════════════════════════════════
// PROCESADOR DE TRAMAS · la ÚNICA puerta por la que entran los datos de los nodos
// (API en directo, reenvío en lote y, más adelante, importación de la SD).
//
// Una trama = un ciclo de medida de un nodo:
//   {"formato":1, "nodo":"AC3D04", "seq":216, "ts":1790870346, "rtc":1,
//    "OD_est":1, "OD_OD_medida":8.82, "OD_temperatura":25.6}
//
// procesar() devuelve el código HTTP que se responde al nodo:
//   200 guardada, o ya estaba guardada (el nodo no debe reenviarla)
//   400 no se puede guardar (falta nodo, seq, ts o rtc, o no son del tipo correcto)
//   403 el nodo está desactivado en la plataforma
//   409 ese (nodo, seq) ya existía con OTRO contenido: se guarda en incidencias
//   500 fallo del servidor: el nodo lo reintentará más tarde
//
// Regla: rechazar solo lo que no se puede guardar; todo lo demás se guarda y se marca.
// Las horas se guardan en UTC: ts (hora local del nodo) − desfase del nodo(según su ubicación: Lima -5h, Guatemala -6h).
// ═════════════════════════════════════════════
class ProcesadorTramas
{
    // Claves que NO son sensores (via = maestro que reenvía la trama, opcional)
    public const CLAVES_FIJAS = ['formato', 'nodo', 'seq', 'ts', 'rtc', 'via', 'fecha', 'tx', 'origen'];

    // 3 últimos bytes de la MAC en hexadecimal, o SINID
    public const PATRON_NODO = '/^([0-9A-F]{6}|SINID)$/';

    public const FORMATOS_CONOCIDOS = [1];

    // Avisos de calidad que se guardan con los ciclos y las lecturas
    public const MARCAS = [
        'sin_hora' => 'Sin hora',
        'hora_invalida' => 'Hora imposible',
        'reloj_desajustado' => 'Reloj desajustado',
        'sinid' => 'Nodo sin identificador',
        'formato_desconocido' => 'Formato desconocido',
        'fuera_escala' => 'Fuera de escala',
        'no_numerico' => 'Valor no numérico',
        'fuera_de_limite' => 'Valor imposible de guardar',
    ];

    /**
     * @param  mixed   $trama   la trama ya leída del JSON (array) o lo que haya llegado
     * @param  string  $origen  'red' (en directo), 'lote' (reenvío), 'sd' (importada) o 'simulador'
     * @param  string  $cuerpo  el texto original, que se guarda tal cual
     * @return array{codigo:int, estado:string, mensaje:string, seq:?int}
     */
    public function procesar(mixed $trama, string $origen, string $cuerpo): array
    {
        $cuerpo = mb_scrub($cuerpo, 'UTF-8');   // texto válido siempre (MySQL rechaza bytes sueltos)

        // ───── 1. Campos fijos: sin ellos no se puede guardar ─────
        if (strlen($cuerpo) > 60000) {
            return $this->rechazar(400, 'Trama demasiado grande', null, null, $origen, mb_strcut($cuerpo, 0, 1000, 'UTF-8'));
        }
        if (! is_array($trama) || array_is_list($trama)) {
            return $this->rechazar(400, 'El cuerpo no es un objeto JSON', null, null, $origen, $cuerpo);
        }

        $codigo = (isset($trama['nodo']) && is_string($trama['nodo'])) ? strtoupper(trim($trama['nodo'])) : null;
        $seq = $this->entero($trama['seq'] ?? null);
        $ts = $this->entero($trama['ts'] ?? null);
        $rtc = $this->entero($trama['rtc'] ?? null);

        if ($codigo === null || $codigo === '' || $seq === null || $ts === null || $rtc === null
            || $seq > 4294967295 || $rtc > 2147483647) {
            return $this->rechazar(400, 'Faltan nodo, seq, ts o rtc, o no son del tipo correcto', $codigo, null, $origen, $cuerpo);
        }
        if (strlen($codigo) > 20) {
            return $this->rechazar(400, 'El código del nodo es demasiado largo', $codigo, $seq, $origen, $cuerpo);
        }
        if ($origen !== 'simulador' && ! preg_match(self::PATRON_NODO, $codigo)) {
            return $this->rechazar(400, 'El nodo debe tener 6 caracteres hexadecimales o ser SINID', $codigo, $seq, $origen, $cuerpo);
        }

        // ───── 2. El nodo (si es la primera vez que envía, se da de alta solo, sin sitio) ─────
        try { // buscar o crear el nodo
            $nodo = Nodo::firstOrCreate(
                ['codigo' => $codigo],  // con qué buscar
                [ // qué valores poner si no existía
                    'nombre' => $codigo === 'SINID' ? 'Nodo sin identificador (SINID)' : 'Nodo ' . $codigo,
                    'descripcion' => 'Dado de alta automáticamente al recibir su primera trama',
                    'desfase_s' => config('plataforma.desfase_por_defecto'),
                ]
            );
            if ($nodo->wasRecentlyCreated) { //true si firstOrCreate acaba de crear el nodo, y false si ya existía. 
                $nodo->refresh();   // trae los valores por defecto de la tabla (activo = sí, rol...)
            }
        } catch (QueryException $e) {  // si la base de datos falla
            return $this->error($seq);   // responde 500
        }
        if (! $nodo->activo) {
            return $this->rechazar(403, 'El nodo está desactivado en la plataforma', $codigo, $seq, $origen, $cuerpo, $nodo->id);
        }

        // ───── 3. La hora: ts es la hora LOCAL del nodo → se pasa a UTC con su desfase ─────
        $marcas = [];
        if ($codigo === 'SINID') {
            $marcas[] = 'sinid';
        }
        $formato = $this->entero($trama['formato'] ?? null);
        if ($formato !== null && $formato > 2147483647) {
            $formato = null;
        }
        if (! in_array($formato, self::FORMATOS_CONOCIDOS, true)) {
            $marcas[] = 'formato_desconocido';
        }

        $medidoEn = null;
        if ($ts === 0 || $rtc === 4) {
            $marcas[] = 'sin_hora';
        } else {
            $utc = $ts - $nodo->desfase_s;
            if ($utc < config('plataforma.inicio_datos_utc') || $utc > time() + 86400) {
                $marcas[] = 'hora_invalida';                     // se trata como "sin hora"
            } else {
                $medidoEn = Carbon::createFromTimestampUTC($utc);
                if ($origen === 'red' && abs(time() - $utc) > config('plataforma.segundos_desajuste')) {
                    $marcas[] = 'reloj_desajustado';
                }
            }
        }

        // Maestro que reenvía la trama (opcional): "via":"<código del maestro>"
        $viaId = null;
        if (isset($trama['via']) && is_string($trama['via'])) {
            $viaId = Nodo::where('codigo', strtoupper(trim($trama['via'])))->value('id');
        }

        // ───── 4. ¿Ya estaba? La clave del ciclo es (nodo, seq) ─────
        try {
            if ($seq > 0) {
                $anterior = Ciclo::where('nodo_id', $nodo->id)->where('seq', $seq)->value('cuerpo_original');
                if ($anterior !== null) {
                    if ($this->huella(json_decode($anterior, true)) === $this->huella($trama)) {
                        return ['codigo' => 200, 'estado' => 'repetido', 'mensaje' => 'Ya estaba guardado', 'seq' => $seq];
                    }
                    $this->incidencia('conflicto', 'El ciclo ' . $seq . ' ya existía con otro contenido', $nodo->id, $codigo, $seq, $origen, $cuerpo);
                    return ['codigo' => 409, 'estado' => 'conflicto', 'mensaje' => 'Ciclo repetido con otro contenido', 'seq' => $seq];
                }
            } else {
                // seq = 0: el nodo no pudo leer su contador → el duplicado se evita con (nodo, ts)
                $repetido = Ciclo::where('nodo_id', $nodo->id)->whereNull('seq')
                    ->when($ts > 0,
                        fn ($q) => $q->where('ts', $ts),
                        fn ($q) => $q->where('ts', 0)->where('cuerpo_original', $cuerpo))
                    ->exists();
                if ($repetido) {
                    return ['codigo' => 200, 'estado' => 'repetido', 'mensaje' => 'Ya estaba guardado', 'seq' => $seq];
                }
            }
        } catch (QueryException $e) {
            return $this->error($seq);
        }

        // ───── 5. Guardar el ciclo y sus lecturas en UNA transacción ─────
        try {
            $resultado = DB::transaction(function () use ($trama, $nodo, $seq, $ts, $rtc, $formato, $medidoEn, $origen, $viaId, $marcas, $cuerpo) {
                $ahora = now()->utc();

                $ciclo = Ciclo::create([
                    'nodo_id' => $nodo->id,
                    'seq' => $seq > 0 ? $seq : null,
                    'ts' => $ts,
                    'rtc' => $rtc,
                    'formato' => $formato,
                    'medido_en' => $medidoEn,
                    'recibido_en' => $ahora,
                    'origen' => $origen,
                    'via_nodo_id' => $viaId,
                    'marcas' => $marcas ? implode(',', $marcas) : null,
                    'cuerpo_original' => $cuerpo,
                ]);

                // Estado de cada sensor en este ciclo (claves <fuente>_est, 1 = OK)
                $estados = [];
                foreach ($trama as $clave => $valor) {
                    if (preg_match('/^([^_]+)_est$/', (string) $clave, $m)) {
                        $c = $this->entero($valor);
                        $estados[$m[1]] = ($c !== null && $c <= 2147483647) ? $c : null;
                    }
                }

                // Sondas en uso en este nodo, por fuente (OD, pH...)
                $sondas = Sonda::where('nodo_id', $nodo->id)->whereNull('instalada_hasta')
                    ->orderByDesc('id')->get()->unique('fuente')->keyBy('fuente');

                $nuevos = [];
                $guardadas = 0;
                $yaGuardados = [];   // cada parámetro, una sola vez por ciclo
                foreach ($trama as $clave => $valor) {
                    $clave = (string) $clave;
                    if (in_array($clave, self::CLAVES_FIJAS, true) || preg_match('/^[^_]+_est$/', $clave)) {
                        continue;   // no son medidas
                    }
                    if ($clave === '' || strlen($clave) > 50) {
                        continue;   // no cabe en el catálogo: se queda solo en la trama original
                    }

                    // a. Qué parámetro es (una clave nueva es un sensor nuevo: se crea como Pendiente)
                    $parametro = Parametro::firstOrCreate(['codigo' => $clave], ['nombre' => $clave, 'estado' => 'Pendiente']);
                    if ($parametro->wasRecentlyCreated) {
                        $nuevos[] = $clave;
                    }
                    if (isset($yaGuardados[$parametro->id])) {
                        continue;
                    }
                    $yaGuardados[$parametro->id] = true;

                    // b. El valor: número, o vacío si el sensor falló (null en JSON)
                    $marca = null;
                    if ($valor === null || $valor === '') {
                        $numero = null;
                    } elseif (is_numeric($valor) && abs((float) $valor) >= 1e8) {
                        $numero = null;
                        $marca = 'fuera_de_limite';      // la trama original lo conserva
                    } elseif (is_numeric($valor)) {
                        $numero = (float) $valor;
                        if ($parametro->estado === 'Activo'
                            && ($numero < $parametro->escala_min || $numero > $parametro->escala_max)) {
                            $marca = 'fuera_escala';     // se guarda igual: nunca se corrige
                        }
                    } else {
                        $numero = null;
                        $marca = 'no_numerico';
                    }

                    $fuente = Parametro::fuenteDe($clave);
                    Lectura::create([
                        'ciclo_id' => $ciclo->id,
                        'nodo_id' => $nodo->id,
                        'sitio_id' => $nodo->sitio_id,
                        'parametro_id' => $parametro->id,
                        'sonda_id' => $sondas->get($fuente)?->id,
                        'valor' => $numero,
                        'codigo_estado' => $estados[$fuente] ?? null,
                        'medido_en' => $medidoEn,
                        'marca' => $marca,
                    ]);
                    $guardadas++;
                }

                // ───── 6. Apuntar el último ciclo y el contacto del nodo ─────
                // (lo importado de la SD no cuenta como contacto: el nodo no habló con el servidor)
                $nodo->ultimo_seq = max((int) $nodo->ultimo_seq, $seq);
                if ($origen !== 'sd') {
                    $nodo->ultima_conexion = $ahora;
                    $nodo->primer_contacto = $nodo->primer_contacto ?? $ahora;
                }
                $nodo->save();

                return ['codigo' => 200, 'estado' => 'nuevo', 'mensaje' => 'Guardado', 'seq' => $seq,
                        'lecturas' => $guardadas, 'nuevos' => $nuevos, 'marcas' => $marcas];
            });

            return $resultado;   // solo ahora está guardado: ya se puede responder 200

        } catch (QueryException $e) {
            // Dos envíos a la vez con el mismo seq: el segundo choca con la clave única
            if ($seq > 0) {
                $anterior = Ciclo::where('nodo_id', $nodo->id)->where('seq', $seq)->value('cuerpo_original');
                if ($anterior !== null) {
                    if ($this->huella(json_decode($anterior, true)) === $this->huella($trama)) {
                        return ['codigo' => 200, 'estado' => 'repetido', 'mensaje' => 'Ya estaba guardado', 'seq' => $seq];
                    }
                    $this->incidencia('conflicto', 'El ciclo ' . $seq . ' ya existía con otro contenido', $nodo->id, $codigo, $seq, $origen, $cuerpo);
                    return ['codigo' => 409, 'estado' => 'conflicto', 'mensaje' => 'Ciclo repetido con otro contenido', 'seq' => $seq];
                }
            }
            return $this->error($seq);
        }
    }

    // Número entero sin signo: acepta 42, 42.0 y "42" (de un CSV). Si no lo es → null
    private function entero(mixed $valor): ?int
    {
        if (is_int($valor)) {
            return $valor >= 0 ? $valor : null;
        }
        if (is_float($valor)) {
            return ($valor >= 0 && $valor < PHP_INT_MAX && floor($valor) == $valor) ? (int) $valor : null;
        }
        if (is_string($valor) && ctype_digit($valor)) {
            return (int) $valor;
        }
        return null;
    }

    // "Huella" del contenido de una trama, para saber si dos tramas son la misma:
    // solo ts, rtc y los sensores, con los números redondeados a 3 decimales y sin vacíos
    private function huella(mixed $trama): string
    {
        if (! is_array($trama)) {
            return '';
        }
        $huella = [];
        foreach ($trama as $clave => $valor) {
            if (in_array($clave, ['formato', 'nodo', 'seq', 'via', 'fecha', 'tx', 'origen'], true)) {
                continue;
            }
            if ($valor === null || $valor === '') {
                continue;
            }
            $huella[$clave] = is_numeric($valor) ? round((float) $valor, 3) : (is_scalar($valor) ? (string) $valor : json_encode($valor));
        }
        ksort($huella);
        return json_encode($huella);
    }

    // Rechaza la trama y la apunta en incidencias (solo las que llegan por red o en lote)
    private function rechazar(int $codigoHttp, string $motivo, ?string $codigo, ?int $seq, string $origen, string $cuerpo, ?int $nodoId = null): array
    {
        if (in_array($origen, ['red', 'lote'], true)) {
            $this->incidencia('rechazada', $motivo, $nodoId, $codigo, $seq, $origen, $cuerpo);
        }
        return ['codigo' => $codigoHttp, 'estado' => 'rechazada', 'mensaje' => $motivo, 'seq' => $seq];
    }

    private function error(?int $seq): array
    {
        return ['codigo' => 500, 'estado' => 'error', 'mensaje' => 'Error de base de datos', 'seq' => $seq];
    }

    // Guarda una incidencia (si falla, no pasa nada: no debe impedir responder al nodo).
    // Si esa misma trama ya se apuntó, no se repite.
    private function incidencia(string $tipo, string $motivo, ?int $nodoId, ?string $codigo, ?int $seq, string $origen, string $cuerpo): void
    {
        try {
            $cuerpo = mb_strcut($cuerpo, 0, 10000, 'UTF-8');
            $codigo = $codigo !== null ? mb_strcut($codigo, 0, 50, 'UTF-8') : null;
            $seq = ($seq !== null && $seq <= 4294967295) ? $seq : null;

            $existe = Incidencia::where('tipo', $tipo)->where('codigo_nodo', $codigo)
                ->where('seq', $seq)->where('cuerpo_original', $cuerpo)->exists();
            if ($existe) {
                return;
            }
            Incidencia::create([
                'tipo' => $tipo,
                'motivo' => $motivo,
                'nodo_id' => $nodoId,
                'codigo_nodo' => $codigo,
                'seq' => $seq,
                'origen' => $origen,
                'recibido_en' => now()->utc(),
                'cuerpo_original' => $cuerpo,
            ]);
        } catch (QueryException $e) {
            // sin registro, pero seguimos
        }
    }
}
