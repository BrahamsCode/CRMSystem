# Medición de avance y consumo de la migración a Laravel 13

Corte: 2026-11-27, sobre `origin/laravel13` (`65968f9`, «S0: cierre de fase (paridad 97 %, regresión limpia, 989 en verde)»).

Fuentes:
- **Paridad:** [Doc/qa/paridad.md](qa/paridad.md), generada por el script de S1 a partir de las tablas de `Doc/diseno/*.md`.
- **Código:** `git` sobre `origin/laravel13`.
- **Tokens:** los registros locales de Claude Code (`~/.claude/projects/*CRMSystem*/**/*.jsonl`). Se suman los campos `usage` de cada respuesta, sin duplicados.

## 1. Avance de la migración

### 1.1 Paridad funcional por módulo

Se cuenta cada pantalla, endpoint o script del legacy que está dentro del alcance. Los «descartados» no entran en el porcentaje: son archivos muertos, duplicados o fuera de alcance.

| Módulo | Sesión | Hecho | Pendiente | Descartado | Otro | Total | % hecho |
|---|---|---:|---:|---:|---:|---:|---:|
| Núcleo y 基本情報管理 (category0) | S1 | 16 | 0 | 4 | 0 | 20 | **100 %** |
| 顧客管理, 売上 y 予約 (category1, ventas, category9) | S2 | 150 | 0 | 22 | 0 | 172 | **100 %** |
| 販売促進管理 (category3) | S3 | 141 | 2 | 40 | 0 | 183 | **99 %** |
| マイページ, HP y 端末管理 (category6, category7) | S4 | 70 | 4 | 46 | 0 | 120 | **95 %** |
| 業務支援管理 e integraciones (category5, API) | S5 | 95 | 6 | 23 | 5 | 129 | **90 %** |
| **Total** | | **472** | **12** | **135** | **5** | **624** | **97 %** |

```
Núcleo       ████████████████████ 100 %
Clientes     ████████████████████ 100 %
Promoción    ████████████████████  99 %
Contenido    ███████████████████░  95 %
Operaciones  ██████████████████░░  90 %
TOTAL        ███████████████████░  97 %
```

### 1.2 Qué falta (12 pendientes)

Casi todo lo que falta depende de Antony o de Japón, no de trabajo de las sesiones:

| Pendiente | Cant. | Bloqueado por |
|---|---:|---|
| スマトピ連携 (F8) | 2 | Antony: ¿se usa スマトピ? |
| ビーコン (lista y detalle de beacons y receptores) | 4 | Japón: la BD externa `b-develop.g-api.net` no responde en dev; hay que confirmar producción y conseguir acceso |
| カードリーダーモード / カード管理 de category5 | 3 | Rutas propias pendientes (S5); las de category1 ya existen |
| `exp_cmd/cron/get_shukei_data*.php` | 1 | Escribe en el servidor del POS, que está fuera de alcance; lo confirma Japón |
| `index.php` (category5) e `inc/page_control.php` | 2 | Ya están hechos; falta actualizar la columna Estado (S5) |

Los 5 «otro» de S5 quedan diferidos porque son de POS o SNS (fuera de alcance), o porque los lleva S2.

### 1.3 Avance del proyecto completo

El 97 % es la **paridad funcional** (fase 2). Para dejar el sistema en producción quedan otras fases:

| Fase | Estado | % |
|---|---|---:|
| 0. Análisis del legacy (8 documentos, ~1 020 vistas, ~615 puntos) | Hecha | 100 % |
| 1. Docker PHP 8.5 + Laravel 13, orquestación | Hecha | 100 % |
| 2. Paridad funcional con misma BD y misma UI | Casi hecha | **97 %** |
| 3. Validación con datos reales (volcado de producción) | No empezada: falta el volcado de Japón | 0 % |
| 4. Respuestas de Japón (signage admin, g-api, GoTel, módulos en uso) | Pedidas | 0 % |
| 5. Responsive y rediseño de la UI | Al final, por decisión de Antony | 0 % |
| 6. Paso a producción (despliegue, migración de contraseñas md5, cron → Scheduler) | No empezada | 0 % |

Estimación del avance global, ponderando por esfuerzo: **~70–75 %**. La paridad era lo más pesado. Lo que queda es sobre todo validar, decidir, ajustar la UI y desplegar.

## 2. Volumen de trabajo producido

| Métrica | Valor |
|---|---:|
| Commits en `laravel13` desde 2026-11-26 | 329 |
| Commits por rama de sesión (`l13/*`, incluyen las fusiones de laravel13) | 311–326 cada una |
| Archivos PHP en `app/` | 581 (59 420 líneas) |
| Vistas Blade (`resources/views`) | 516 |
| Archivos de prueba | 141 |
| Pruebas Pest en verde | **989** |
| Regresión de punta a punta (125 URL del menú con master y con un usuario limitado) | 0 errores 500, 0 páginas en blanco |
| Fallos del legacy corregidos y documentados | ver [Doc/qa/para-japon.md](qa/para-japon.md) §1 |

## 3. Tokens consumidos

### 3.1 Total

| Concepto | Tokens |
|---|---:|
| Entrada sin caché | 16 210 |
| Salida (lo que escribieron los modelos) | 7 500 603 |
| Escritura en caché | 26 140 592 |
| Lectura de caché (contexto reutilizado) | 2 058 904 377 |
| **Total procesado** | **≈ 2 093 millones** |
| Tokens «nuevos» (sin lectura de caché) | ≈ 33,7 millones |

La lectura de caché es el 98 % del total. En cada turno el modelo vuelve a leer el contexto ya cacheado (archivos, conversación), y esa lectura se cobra mucho más barata que la entrada normal. La cifra que mejor refleja el trabajo real es la **salida: 7,5 M tokens**.

### 3.2 Por sesión

| Sesión | Modelo | Respuestas | Salida | Total procesado | % del total |
|---|---|---:|---:|---:|---:|
| S0 Coordinador + sesión de preparación | Opus 5.5 (+ subagentes Sonnet) | 811 | 811 058 | 229,5 M | 11 % |
| S1 Núcleo | Opus 5.5 (+ Haiku) | 827 | 628 238 | 275,7 M | 13 % |
| S2 Clientes | Sonnet 5.5 | 2 646 | 2 581 509 | 480,3 M | 23 % |
| S3 Promoción | Sonnet 5.5 | 826 | 831 030 | 298,2 M | 14 % |
| S4 Contenido | Sonnet 5.5 | 1 473 | 1 639 795 | 418,6 M | 20 % |
| S5 Operaciones | Sonnet 5.5 | 1 097 | 995 656 | 388,1 M | 19 % |
| Quiosco (cerrada al recortar el alcance) | Sonnet 5.5 | 21 | 13 317 | 2,2 M | 0,1 % |
| **Total** | | **7 701** | **7 500 603** | **2 092,6 M** | 100 % |

Por modelo: Sonnet 5.5 ≈ 1 616 M (77 %), Opus 5.5 ≈ 476 M (23 %), Haiku 5.5 < 0,1 M.

### 3.3 Alcance y límites de la medición

- **Periodo medido:** del 2026-11-27 01:42 al 16:21 UTC, unas 14,5 h de reloj con 6 sesiones en paralelo. En hora de Lima, del 26-11 a las 20:42 al 27-11 a las 11:21.
- **Qué no incluye:**
  - El trabajo de CRMSystem anterior a esta migración (el repo existe desde 2026-10-01): sus registros ya no están en este equipo, porque Claude Code los borra pasado un tiempo.
  - El trabajo hecho en `crm2-php8` (migración del legacy a PHP 8.5).
- **S0 comparte carpeta de registros** con la sesión de preparación (análisis, Docker, orquestación). Por eso aparecen juntas.
- **Costo:** no se calcula aquí. Depende del plan contratado (suscripción o API) y de la tarifa vigente de cada modelo.
- **Cómo recalcular:** con el script `tokens.py` (suma `usage` por `message.id` + `requestId` en los `.jsonl` de `~/.claude/projects/*CRMSystem*`). Se puede copiar a `scripts/` si se quiere versionar.
