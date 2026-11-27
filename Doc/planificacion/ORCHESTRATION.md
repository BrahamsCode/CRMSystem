# CRM → Laravel 13: orquestación de agentes

> Creado el 2026-11-26, a partir del modelo de mesavision (`C:\Users\User\VivaTech\mesavision\docs\planificacion`).
> Las decisiones de stack están en [`/CLAUDE.md`](../../CLAUDE.md) y [Doc/MIGRACION-LARAVEL13.md](../MIGRACION-LARAVEL13.md). Este documento define **cómo se reparte el trabajo**.

## 1. Principios

1. **Convenciones primero.** [Doc/contratos/CONVENCIONES.md](../contratos/CONVENCIONES.md) fija quién es dueño de cada carpeta, de cada modelo y de cada migración. Es lo que permite que 6 sesiones toquen el mismo Laravel sin chocar al fusionar.
2. **Una sesión por grupo de módulos, cada una en su worktree y su rama** (`l13/<grupo>`). Lo compartido lo cambia S0, o S1 si es código del núcleo.
3. **Orquestación autónoma.** S0 delega y decide; S1 a S6 trabajan sin pedir confirmación y avisan a S0 al terminar o al bloquearse (`ListAgents`, `SendMessage`, `notify_when_idle`). Las pestañas siguen visibles: Antony mira y corrige cuando quiera.
4. **Modelo según la dificultad:** Opus donde hay incertidumbre (coordinación, núcleo, integraciones); Sonnet donde el análisis ya dejó el trabajo especificado (módulos).
5. **Antony solo interviene en lo suyo:** decisiones de producto que no están en los md y datos que solo él tiene (volcado de producción, credenciales). S0 se lo pide con un mensaje que empieza con **"ANTONY:"**.
6. **Commits y merges locales: solo S0. Nunca push.**

## 2. Dimensionamiento: cuántos agentes

Ver la sección 7 (se basa en los puntos del análisis, `Doc/analisis-legacy/README.md`).

## 3. Sesiones

| Sesión | Carpeta | Rama | Modelo y esfuerzo | Grupo |
|---|---|---|---|---|
| S0 Coordinador CRM | `CRMSystem/` | la de integración (la que tenga `CRMSystem/`) | opus, alto | Coordina, revisa diseños, integra, QA final |
| S1 Núcleo | `CRMSystem-nucleo/` | `l13/nucleo` | opus, alto | Laravel 12 → 13, núcleo (auth del personal, permisos, tiendas, módulos activables, menú, layout), 基本情報管理, importador legacy |
| S2 Clientes | `CRMSystem-clientes/` | `l13/clientes` | sonnet, medio | 顧客管理, 販売管理 y 予約管理 |
| S3 Promoción | `CRMSystem-promocion/` | `l13/promocion` | sonnet, medio | 販売促進管理 (mensajería y fidelización) |
| S4 Contenido | `CRMSystem-contenido/` | `l13/contenido` | sonnet, medio | マイページ管理, ホームページ管理, 端末管理 y señalización |
| S5 Operaciones | `CRMSystem-operaciones/` | `l13/operaciones` | sonnet, **alto** | 業務支援管理 e integraciones externas (API, app, POS, CTI): contratos de alto riesgo |
| S6 Quiosco | `CRMSystem-quiosco/` | `l13/quiosco` | sonnet, medio | Touchcation (contrato de la APK) |
| QA y revisión | subagente dentro de cada sesión | — | sonnet (`CLAUDE_CODE_SUBAGENT_MODEL`) | `senior`, `debugger`, `/code-review` |

Configuración única de sesiones: [scripts/config-sesiones.ps1](../../scripts/config-sesiones.ps1).

## 4. Plan

| Paso | Quién | Entregable | Depende de |
|---|---|---|---|
| 0 | Antony | Commit del contexto (este documento, `Doc/`, `scripts/`, `CLAUDE.md`, `docker/`) y doble clic en `INICIAR-CRM.bat` | — |
| 1 | S1 | **Fase 1:** Laravel 12 → 13, `php: ^8.3`, dependencias compatibles (Fortify, Pest), pruebas en verde en PHP 8.5, `Dockerfile` de Render a PHP 8.5 | 0 |
| 1' | S2–S6 | **Diseño** de su grupo en `Doc/diseno/<grupo>.md`: cada vista del análisis → ruta, controlador, tablas nuevas (según `ESTANDARES_BD.md`), orden y estimación. **Sin código** | 0 |
| 2 | S1 | **Núcleo:** carga de `routes/modulos/*.php` y `config/menu/*.php`, layout del sistema del personal, login del personal, permisos (Policies), tiendas, módulos activables (システム選択). Documentado en `Doc/contratos/NUCLEO.md` | 1 |
| 3 | S0 | Revisa los diseños y `NUCLEO.md`; ajusta `CONVENCIONES.md`; integra la rama de S1 y actualiza las demás | 1', 2 |
| 4 | S2–S6 | **Implementación** submódulo por submódulo, en el orden de su diseño, con pruebas Pest y la tabla de paridad al día | 3 |
| 5 | S1 · S5 | S1: importador de datos legacy (`tb_*` → tablas nuevas). S5: integraciones externas (API tienda móvil, app, POS, `tool/api`, CTI) con el mismo contrato | 2 |
| 6 | S0 | Integración continua: cada submódulo terminado con pruebas en verde → commit en su rama → merge en integración → actualizar las demás ramas | 4 |
| 7 | S0 | QA de punta a punta (Playwright) contra la muestra de desarrollo, módulo por módulo | 6 |

**Criterio de salida:** todas las vistas del análisis en `hecho` o `descartado` con motivo; integraciones externas con su contrato probado; pruebas en verde.

## 5. Coordinación

- **Estado:** `Doc/coordinacion/S0-coordinador.md` a `S5-operaciones.md`. Cada sesión edita solo el suyo. Formato en [../coordinacion/README.md](../coordinacion/README.md).
- **Mensajes:** S0 asigna con `SendMessage` + `notify_when_idle`. Cada sesión le avisa a S0 al terminar o al bloquearse.
- **Pedidos:** un cambio a algo ajeno (modelo de otro módulo, paquete de Composer, componente del layout) se pide a S0, que lo reenvía al dueño.
- **Sin esperas:** mientras el núcleo no esté, cada módulo trabaja contra factories y seeders propios.

## 6. Si algo se traba

| Situación | Qué hacer |
|---|---|
| Un módulo necesita algo del núcleo que no existe | Seguir con un stub mínimo en su propia carpeta, anotarlo en "Pedidos" y avisar a S0 |
| Conflicto de merge en código | S0 se lo devuelve a la sesión dueña |
| Falló dos veces la misma tarea | S0 escala a Antony con el resumen |
| Comportamiento del legacy dudoso | Ver el código en crm2-php8 y la muestra de desarrollo (solo lectura); si sigue la duda, decidir lo más simple, anotarlo en "Decisiones" y seguir |
| Límite de uso | Abrir menos sesiones: `scripts\iniciar-sesiones.ps1 -Sesiones S0,S1,S3` |

## 7. Dimensionamiento: por qué 6 trabajadores + S0

Base: [Doc/analisis-legacy/README.md](../analisis-legacy/README.md): **≈ 1.020 vistas reales y ≈ 615 puntos** (1 punto ≈ media jornada de un agente), sin `hp/`.

| Opción | Reparto | Por qué no / por qué sí |
|---|---|---|
| 3 trabajadores | ~205 puntos cada uno | El camino crítico (núcleo) se alarga y cada sesión acumula contexto de demasiados módulos |
| 5 trabajadores (primer borrador) | S1 ~150, S2 ~40, S4 ~176 | Desbalanceado: S2 se queda sin trabajo y S4 y S1 tardan el triple |
| **6 trabajadores (elegido)** | **80–115 puntos cada uno** | Balanceado; S1 queda con menos carga porque es el camino crítico; cada grupo comparte tablas internas (poca dependencia cruzada) |
| 8 o más | <80 cada uno | Más merges, más pedidos cruzados sobre los mismos modelos (`Customer`, `Shop`, `Staff`) y S0 se vuelve el cuello de botella; el límite de uso se consume mucho más rápido sin ganar velocidad real |

**Ritmo esperado:** S1 desbloquea al resto con la Fase 1 + núcleo (~25 puntos). Mientras tanto, S2–S6 hacen su diseño (paso 1'). Después, las 6 avanzan en paralelo; el grupo más grande (~115) marca la duración total.

**Si se acaba el límite de uso:** priorizar S0 + S1 + S2 + S3 (núcleo, clientes y promoción son lo que más usa el negocio) y abrir S4–S6 después: `scripts\iniciar-sesiones.ps1 -Sesiones S0,S1,S2,S3`.

**Recortes posibles** (confirmar con Japón, ver hallazgos en el README del análisis): POS `category11`, SNS `category12`, serie N de touchcation, `tagsignage`, `digitalsignage` → ≈ −60 puntos.
