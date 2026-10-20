<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProcesadorTramas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// ═════════════════════════════════════════════
// API v1 · lo que llaman los nodos
//   POST /api/v1/medidas        una trama (un ciclo)       → {"ok":true} o {"ok":false,"error":"..."}
//   POST /api/v1/medidas/lote   array de tramas atrasadas  → {"resultados":[{"seq":40,"codigo":200}, ...]}
// El nodo decide solo por el código HTTP (ver ProcesadorTramas). La respuesta es mínima
// porque el módem cobra por byte. La cabecera X-API-Key se acepta pero todavía no se comprueba.
// ═════════════════════════════════════════════
class MedidasController extends Controller
{
    public function __construct(private ProcesadorTramas $procesador)
    {
    }

    // Una trama en directo
    public function unaTrama(Request $request): JsonResponse
    {
        $cuerpo = $request->getContent();
        $resultado = $this->procesador->procesar(json_decode($cuerpo, true), 'red', $cuerpo);

        $respuesta = $resultado['codigo'] === 200
            ? ['ok' => true]
            : ['ok' => false, 'error' => $resultado['mensaje']];

        return response()->json($respuesta, $resultado['codigo'], [], JSON_UNESCAPED_UNICODE);
    }

    // Reenvío en lote: cada trama se procesa por separado (una mala no tumba a las demás)
    public function lote(Request $request): JsonResponse
    {
        $tramas = json_decode($request->getContent(), true);

        if (! is_array($tramas) || ($tramas && ! array_is_list($tramas))) {
            return response()->json(['ok' => false, 'error' => 'El cuerpo debe ser un array de tramas'], 400, [], JSON_UNESCAPED_UNICODE);
        }
        $maximo = config('plataforma.max_tramas_lote');
        if (count($tramas) > $maximo) {
            return response()->json(['ok' => false, 'error' => 'Demasiadas tramas: máximo ' . $maximo . ' por petición'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $resultados = [];
        $errores = 0;
        foreach ($tramas as $trama) {
            $r = $this->procesador->procesar($trama, 'lote', json_encode($trama, JSON_UNESCAPED_UNICODE));
            $resultados[] = ['seq' => is_array($trama) ? ($trama['seq'] ?? null) : null, 'codigo' => $r['codigo']];
            if ($r['codigo'] >= 500) {
                $errores++;
            }
        }

        // 500 solo si no se pudo procesar nada (el nodo reintentará todo el lote)
        $codigo = ($tramas && $errores === count($tramas)) ? 500 : 200;
        return response()->json(['resultados' => $resultados], $codigo);
    }
}
