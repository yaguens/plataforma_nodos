# Contrato de la API con los nodos (v1)

## Direcciones

| Uso | Método y dirección |
| --- | --- |
| En directo: una trama por ciclo | `POST /api/v1/medidas` |
| Reenvío en lote: array de tramas atrasadas | `POST /api/v1/medidas/lote` |

Cabecera `Content-Type: application/json`. HTTP sin redirecciones.
La cabecera `X-API-Key` se acepta pero todavía no se comprueba.

## Trama

```json
{"formato":1,"nodo":"AC3D04","seq":216,"ts":1790870346,"rtc":1,
 "OD_est":1,"OD_OD_medida":8.82,"OD_temperatura":25.6}
```

| Campo | Obligatorio | Significado |
| --- | --- | --- |
| `nodo` | Sí | 3 últimos bytes de la MAC en hexadecimal (`AC3D04`) o `SINID` |
| `seq` | Sí | Número de ciclo, guardado en memoria permanente (0 = el nodo no pudo leerlo) |
| `ts` | Sí | Hora **local del país** en segundos: UTC + desfase (Lima −5 h, Guatemala −6 h). 0 = sin hora |
| `rtc` | Sí | Estado del reloj (1 = OK, 4 = sin hora) |
| `formato` | No | Versión del formato de trama (hoy 1) |
| `via` | No | Código del nodo maestro que reenvía la trama de otro nodo |
| `<fuente>_est` | No | Estado del sensor `<fuente>` (1 = OK, 2 = sin respuesta, 3 = trama inválida…) |
| `<fuente>_<magnitud>` | — | Cada valor medido. `null` si el sensor falló |

El servidor pasa `ts` a UTC restando el desfase configurado para cada nodo.

## Respuestas

| Código | Situación | El nodo |
| --- | --- | --- |
| 200 | Guardado, o ya estaba guardado (mismo nodo, seq y contenido) | No reintenta |
| 400 | Trama mal formada: falta nodo, seq, ts o rtc | No reintenta |
| 403 | Nodo desactivado en la plataforma | No reintenta |
| 409 | Mismo (nodo, seq) con otro contenido: va a incidencias | No reintenta |
| 500 | Error del servidor o de la base de datos | **Reintenta** |

Respuesta de `/medidas`: `{"ok":true}` o `{"ok":false,"error":"..."}`.
Respuesta de `/medidas/lote` (HTTP 200): `{"resultados":[{"seq":40,"codigo":200},{"seq":44,"codigo":409}]}`;
el nodo vuelve a enviar solo las de código 500.
