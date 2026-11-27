# Análisis del legacy, vista por vista

> Generado el 2026-11-26 por 8 agentes en paralelo, leyendo `C:\Users\User\VivaTech\crm2-php8` (solo lectura, con grep: los títulos y las tablas de algunos `*_register.php` son inferidos). Excluye `hp/` (obsoleto), `admin/` y `category8`.
> **No leer completo:** buscar el título de la sección con grep y leer solo ese tramo. 1 punto ≈ media jornada de un agente (estimación relativa, sirve para repartir).

| Archivo | Alcance | PHP | Vistas reales | Puntos | Sesión |
|---|---|---|---|---|---|
| [00-nucleo-y-base.md](00-nucleo-y-base.md) | category0, raíz de `system/`, class, inc, tool, `public_html/inc`, `src/` | ~200 | ~50 | 57 | S1 |
| [01-clientes-y-ventas.md](01-clientes-y-ventas.md) | category1 (顧客管理), category2 (販売管理) | 217 | 175 | 63,5 (≈ 40 pendientes: CRMSystem ya cubre parte) | S2 |
| [03a-promocion-mensajeria.md](03a-promocion-mensajeria.md) | category3: mail_mag, decomail, auto, follow, reminder, push, dm, inc | 176 | ~70 | 59,5 | S3 |
| [03b-promocion-fidelizacion.md](03b-promocion-fidelizacion.md) | category3: anketo, anketo2, coupon, mycoupon, point, stamp, smatopi, visit, reservation | 214 | 101 | 52 | S3 |
| [04-web-y-mypage.md](04-web-y-mypage.md) | category6 (マイページ管理), category4 (ホームページ管理) | 174 | ~73 | 47 | S4 |
| [05-operaciones-terminales-reservas.md](05-operaciones-terminales-reservas.md) | category5 (業務支援) 50 pts · category7 (端末管理) 38 pts · category9 (予約) 45 pts | 214 | ~190 | 133 | S5 · S4 · S2 |
| [07-pos-api-y-externos.md](07-pos-api-y-externos.md) | category11 (POS), category12 (SNS), api/, appview/, r/, reserve/, exp_cmd/, version/, cron… | 257 | ~190 | 74 | S5 (reservas: S2) |
| [08-quiosco-y-senalizacion.md](08-quiosco-y-senalizacion.md) | touchcation (100 pts, **fuera**), signage (20), digitalsignage (9) | 425 | ~173 | 29 en alcance | S4 |
| **Total** | | ~1.880 | **~1.020** | **≈ 615** | |

## Reparto (ver `Doc/planificacion/ORCHESTRATION.md`, sección 7)

| Sesión | Qué | Puntos aprox. |
|---|---|---|
| S1 Núcleo | 00 + Fase 1 (Laravel 13) + importador legacy | ~80 |
| S2 Clientes | 01 (pendiente) + 予約 (category9, `r/`, `reserve/`) | ~90 |
| S3 Promoción | 03a + 03b | ~110 |
| S4 Contenido | 04 + 端末管理 (category7, sin kiosk) + signage + digitalsignage | ~105 |
| S5 Operaciones | 業務支援 (category5) + integraciones de 07 (API tienda móvil, `mpa_*`, appview, tool/api, CTI) | ~90 |

## Hallazgos transversales

- **Crontab escrito desde PHP** (`cSendSchedule`) mezcla tareas de mensajería, puntos, sellos y rangos → un solo `schedule:run`; cada módulo registra sus comandos en su ServiceProvider.
- **`login_pass` en texto plano y `md5(login_pass)` en URLs** (clientes, staff, quiosco, app) → hash + tokens firmados; mantener compatibilidad solo donde una integración externa lo exija.
- **SQL guardado como texto o en sesión** (`振分配信`, `pos_search`, `wherestr`, `lineMessage[...]['sql']`) → filtros estructurados.
- **Imágenes en `hp/uploads/`** que leen la app, la señalización y el quiosco → Storage, conservando o redirigiendo las URL.
- **Sin autenticación real** en dispositivos (`CRMPOS-<id>`, `androidId`, `key` sin validar) y en `mpa_*`/`appview` → mantener el contrato, pero agregar validación cuando Japón confirme los clientes.
- **Duplicados:** `category11/yoyaku` ≈ `category9/yoyaku`; `r/` ≈ `reserve/`; `kiosk` ≈ `digital_signage` (category7); 15 copias de `check.php` en touchcation; copias viejas en `category4/{user_hp,shop_hp,recruit,stamp}` y `visit/*_test*`.
- **Fuera de alcance por ahora (Antony, 2026-11-26):** POS `category11` (tablas `tb_c2_*` borradas en 2020; incluye terminales `CRMPOS-<id>` y servidores POS de tienda), SNS `category12` y touchcation completo con su gestión `category7/kiosk/` (confirmado que no se usan).
- **Se mantiene:** `digitalsignage` (Antony cree que se usa). En desarrollo (2026-11-26) no hay displays, programas ni materiales registrados (ディスプレイ管理 / 放映プログラム管理 / 映像振り分け管理 vacíos) y el reproductor sin `display_cd` válido muestra 「ただ今システムがビジーです」: no se pudo ver funcionando. Es Flash: el reproductor se rehace en HTML5.
- **Sin confirmar:** `category3/coupon` y `digital_signage`, `tagsignage`.
