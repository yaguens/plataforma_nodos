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
 "OD_est":1,"OD_oxigeno":8.82,"OD_temperatura":25.6}
```

(Las claves de las medidas de este ejemplo están pendientes de confirmar con Gonzalo.)

| Campo | Obligatorio | Significado |
| --- | --- | --- |
| `nodo` | Sí | 3 últimos bytes de la MAC en hexadecimal (`AC3D04`) o `SINID` |
| `seq` | Sí | Número de ciclo, guardado en memoria permanente (0 = el nodo no pudo leerlo) |
| `ts` | Sí | Hora **local del país** en segundos: UTC + desfase (Lima −5 h, Guatemala −6 h). 0 = sin hora |
| `rtc` | Sí | Estado del reloj (1 = OK, 4 = sin hora) |
| `formato` | No | Versión del formato de trama (hoy 1) |
| `via` | No | Código del nodo maestro que reenvía la trama de otro nodo |
| `<FUENTE>_est` | No | Estado del sensor `<FUENTE>` (1 = OK, 2 = sin respuesta, 3 = trama inválida…) |
| `<FUENTE>_<magnitud>` | — | Cada valor medido. `null` si el sensor falló. Formato en la sección siguiente |

El servidor pasa `ts` a UTC restando el desfase configurado para cada nodo.

## Formato de las claves de cada medida

Cada valor medido se envía como `<FUENTE>_<magnitud>`, por ejemplo `OD_temperatura`.
La clave dice **qué** se mide; **dónde** se mide lo dice el campo `nodo`.
Así, el oxígeno de Atitlán y el de una PTAR llevan la misma clave y se distinguen por el nodo.

La plataforma da de alta sola cada clave nueva que recibe, así que **cada forma distinta
de escribir una magnitud se convierte en una magnitud distinta**: `OD_temperatura` y
`OD_temp` serían dos parámetros separados, con su historial partido en dos.

### Reglas

1. `FUENTE` = el sensor, en **mayúsculas** y sin guiones bajos: `OD`, `PH`, `COND`, `TURB`.
2. `magnitud` = lo que se mide, en **minúsculas**, sin acentos, espacios ni unidades: `temperatura`, no `Temp_C`.
3. **La misma magnitud se escribe igual en todos los módulos.** Si dos sensores miden temperatura,
   los dos usan `temperatura` (`OD_temperatura`, `PH_temperatura`).
4. **Una clave no se renombra nunca** una vez que hay datos con ella: el historial quedaría partido.
   Si cambia la unidad o el significado, es una clave nueva.
5. Dos sondas del mismo tipo en un mismo módulo se distinguen por la fuente: `OD1_oxigeno`, `OD2_oxigeno`.
6. Máximo 50 caracteres por clave.
7. Claves reservadas, que no pueden usarse para medidas: `formato`, `nodo`, `seq`, `ts`, `rtc`, `via`,
   `fecha`, `tx`, `origen` y cualquier clave terminada en `_est`.

### Claves en uso

Antes de programar una clave nueva en el firmware, se añade a esta tabla para que todos los
módulos usen la misma. Algunos posibles ejemplos:

| Clave | Magnitud | Unidad | Escala del sensor | Módulos | Estado |
| --- | --- | --- | --- | --- | --- |
| `OD_oxigeno` | Oxígeno disuelto medido por el sensor de OD | mg/L | — | M1–M6 | Pendiente de confirmar |
| `OD_temperatura` | Temperatura del agua medida por el sensor de OD | °C | — | M1–M6 | Pendiente de confirmar |
| `PH_ph` | pH medido por el sensor de pH | — (sin unidad) | 0–14 | M1, M2, M7 | Pendiente de confirmar |
| `PH_temperatura` | Temperatura del agua medida por el sensor de pH | °C | — | M1, M2, M7 | Pendiente de confirmar |
| `COND_conductividad` | Conductividad eléctrica medida por el sensor de conductividad | µS/cm | — | M1, M2 | Pendiente de confirmar |
| `COND_temperatura` | Temperatura del agua medida por el sensor de conductividad | °C | — | M1, M2 | Pendiente de confirmar |
| `TURB_turbidez` | Turbidez medida por el sensor de turbidez | NTU | — | M1, M2 | Pendiente de confirmar |

Módulos:

| Módulo | Ubicación | Sondas |
| --- | --- | --- |
| M1 | Muelle de Panajachel | pH, conductividad, turbidez y oxígeno disuelto |
| M2 | Muelle de Santa Catarina Palopó | pH, conductividad, turbidez y oxígeno disuelto |
| M3 | Otro muelle de Panajachel | Oxígeno disuelto |
| M4 | Otro muelle de Santa Catarina Palopó | Oxígeno disuelto |
| M5 | PTAR de Los Cebollales | Oxígeno disuelto (seguimiento del tratamiento aerobio) |
| M6 | PTAR de Santa Catarina | Oxígeno disuelto (seguimiento del tratamiento aerobio) |
| M7 | Laboratorio en Madrid | pH (pruebas en paralelo) |

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
