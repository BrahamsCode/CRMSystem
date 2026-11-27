# Matriz de paridad por módulo

S1, 2026-11-27. Resumen de las tablas de paridad de `Doc/diseno/*.md` en `laravel13` (1c56ac2): cada fila con columna **Estado** cuenta una vez (una pantalla, endpoint o script del legacy). "hecho" incluye los que llevan nota (p. ej. "hecho (falta verlo con el layout)"); "pendiente" incluye los ⏸ (bloqueados o en espera de decisión). Se regenera con el script de S1 (`paridad.py`, ver al final).

## Lectura para el cierre

Comparado con la versión anterior (8e0f4e2): de 422 a 472 hechos y de 60 a 12 pendientes (87 % → 97 %).

- **Núcleo (S1)** y **clientes, ventas y reservas (S2)**: sin pendientes.
- **Promoción (S3)**: 2 pendientes, los de スマトピ連携 (F8, ¿se usa スマトピ? espera a Antony). La columna Estado ya está al día: el resto, incluidos 新着情報 y ポイント, figura como hecho.
- **Contenido y terminales (S4)**: 4 pendientes, los de ビーコン. Son provisionales y esperan a Japón: ya existen los modelos sobre la conexión `beacon`, pero no las pantallas.
- **Operaciones (S5)**: 6 pendientes y 5 «otro».
  - 勤怠 ya está migrado.
  - Quedan con ⏸ カードリーダーモード設定 y カード管理 de category5 (`felica_mode.php`, `card_new.php`, `card_staff_select.php`), que no tienen ruta propia. Las de category1 sí existen (`legacy.clientes.felica-mode`, `legacy.clientes.card-new`).
  - También `exp_cmd/cron/get_shukei_data*.php`, que escribe en el servidor del POS.
  - `index.php` (category5) y `inc/page_control.php` figuran como pendientes, pero la portada (`legacy.nucleo.category-top.`, `config/menu/operaciones.php`) y el paginador ya existen: S5 debe actualizar la columna Estado.
  - Los 5 «otro» se difieren por POS/SNS (fuera de alcance) o los lleva S2.

## Resumen

| Módulo | Sesión | Hecho | Pendiente | Descartado | Otro | Total | % hecho (sin descartados) |
|---|---|---:|---:|---:|---:|---:|---:|
| [Núcleo y 基本情報管理 (category0)](../diseno/nucleo-base.md) | S1 | 16 | 0 | 4 | 0 | 20 | 100 % |
| [顧客管理, 売上 y 予約 (category1, ventas, category9)](../diseno/clientes-ventas-reservas.md) | S2 | 150 | 0 | 22 | 0 | 172 | 100 % |
| [販売促進管理 (category3)](../diseno/promocion.md) | S3 | 141 | 2 | 40 | 0 | 183 | 99 % |
| [マイページ, HP y 端末管理 (category6, category7)](../diseno/contenido-terminales.md) | S4 | 70 | 4 | 46 | 0 | 120 | 95 % |
| [業務支援管理 e integraciones (category5, API)](../diseno/operaciones-integraciones.md) | S5 | 95 | 6 | 23 | 5 | 129 | 90 % |
| **Total** | | **472** | **12** | **135** | **5** | **624** | **97 %** |

## Núcleo y 基本情報管理 (category0) — S1

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad | 16 | 0 | 4 | 0 |

Sin pendientes.

## 顧客管理, 売上 y 予約 (category1, ventas, category9) — S2

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad › Inicio | 1 | 0 | 0 | 0 |
| 1. Tabla de paridad › `customer/` — listas, búsqueda y configuración | 24 | 0 | 6 | 0 |
| 1. Tabla de paridad › `inc/` — ficha, alta, edición, pestañas, visitas, puntos y sellos | 33 | 0 | 3 | 0 |
| 1. Tabla de paridad › `biyou/` — ficha 美容 (sin enlace en el menú; tiene pantalla porque el legacy la tenía) | 4 | 0 | 1 | 0 |
| 1. Tabla de paridad › `config/` — configuración inicial | 5 | 0 | 2 | 0 |
| 1. Tabla de paridad › `entrance/` — entrada y salida | 2 | 0 | 0 | 0 |
| 1. Tabla de paridad › `rank/` — rangos (modelos de S3, pantallas de S2) | 8 | 0 | 0 | 0 |
| 1. Tabla de paridad › `shukei/` — agregaciones | 7 | 0 | 2 | 0 |
| 1. Tabla de paridad › Ventas internas — `category1/karute_menu/` (módulo `ventas`) | 11 | 0 | 1 | 0 |
| 1. Tabla de paridad › 1.2 販売管理 — `category2/` (módulo `ventas`) | 5 | 0 | 0 | 0 |
| 1. Tabla de paridad › 1.3 予約管理 — `category9/yoyaku/` (módulo `reservas`) | 32 | 0 | 6 | 0 |
| 1. Tabla de paridad › 1.3 bis 予約管理 — `category3/reservation/` (cedido por S3) | 11 | 0 | 0 | 0 |
| 1. Tabla de paridad › 1.4 Reserva web pública `r/` y `reserve/` (`routes/publico/reservas.php`) | 7 | 0 | 1 | 0 |

Sin pendientes.

## 販売促進管理 (category3) — S3

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad › お知らせ配信管理 — `mail_mag/` | 22 | 0 | 7 | 0 |
| 1. Tabla de paridad › デコメール管理 — `decomail/` | 6 | 0 | 3 | 0 |
| 1. Tabla de paridad › 自動お知らせ管理 — `auto/` | 7 | 0 | 0 | 0 |
| 1. Tabla de paridad › フォローお知らせ管理 — `follow/` | 6 | 0 | 1 | 0 |
| 1. Tabla de paridad › リマインダお知らせ管理 — `reminder/` | 3 | 0 | 1 | 0 |
| 1. Tabla de paridad › プッシュ通知 — `push/` | 10 | 0 | 1 | 0 |
| 1. Tabla de paridad › DM発行 — `dm/` | 2 | 0 | 1 | 0 |
| 1. Tabla de paridad › 新着情報 — `digital_signage/` (category3) | 2 | 0 | 0 | 0 |
| 1. Tabla de paridad › Motor compartido — `inc/`, `class/`, raíz | 11 | 0 | 2 | 0 |
| 1. Tabla de paridad › アンケート管理 — `anketo/` (tablas `tb_c3_anketo_*`) | 18 | 0 | 2 | 0 |
| 1. Tabla de paridad › お客様アンケート管理 — `anketo2/` (tablas `tb_c3_anketo2_*`) | 8 | 0 | 1 | 0 |
| 1. Tabla de paridad › クーポン — `coupon/` (cupón-imagen antiguo) | 0 | 0 | 8 | 0 |
| 1. Tabla de paridad › マイクーポン管理 — `mycoupon/` | 11 | 0 | 7 | 0 |
| 1. Tabla de paridad › ポイント管理 — `point/` + `class/Point.php` | 11 | 0 | 2 | 0 |
| 1. Tabla de paridad › スタンプ管理 — `stamp/` | 10 | 0 | 1 | 0 |
| 1. Tabla de paridad › スマトピ連携 — `smatopi/` | 0 | 2 | 0 | 0 |
| 1. Tabla de paridad › 来店履歴管理 — `visit/` | 5 | 0 | 3 | 0 |
| 1. Tabla de paridad › 予約管理 — `reservation/` (category3) | 9 | 0 | 0 | 0 |

**Pendientes (2):**

- _1. Tabla de paridad › スマトピ連携 — `smatopi/`_
  - `smatopi_message.php`, `ajax_smatopiMessage.php`, `ajax_smatopiLine.php` (スマトピ / メッセージ作成) — pendiente (F8: espera a Antony, ¿se usa スマトピ?)
  - `smatopi_sent_history.php`, `ajax_smatopiSentHistory.php` (スマトピ / メッセージ送信履歴) — pendiente (F8: espera a Antony, ¿se usa スマトピ?)

## マイページ, HP y 端末管理 (category6, category7) — S4

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad › 1.1 マイページ管理 — `mypage` (category6) | 15 | 0 | 15 | 0 |
| 1. Tabla de paridad › 1.2 ホームページ管理 — `web` (category4) | 0 | 0 | 20 | 0 |
| 1. Tabla de paridad › 1.3 端末管理 — `terminales` (category7) | 25 | 4 | 7 | 0 |
| 1. Tabla de paridad › 1.4 Reproductores — `senalizacion` (`signage/`, `digitalsignage/`) | 30 | 0 | 4 | 0 |

**Pendientes (4):**

- _1. Tabla de paridad › 1.3 端末管理 — `terminales` (category7)_
  - `beacon/beacon_list.php` (ビーコン管理) — pendiente (provisional, espera a Japón): solo modelos `Terminales\Beacon\{Beacon,BeaconRec
  - `beacon/beacon_detail.php` + `ajax_beaconSave.php` (ビーコン詳細) — pendiente (provisional, espera a Japón): solo modelos `Terminales\Beacon\{Beacon,BeaconRec
  - `beacon/terminal_list.php` (受信端末一覧) — pendiente (provisional, espera a Japón): solo modelos `Terminales\Beacon\{Beacon,BeaconRec
  - `beacon/terminal_detail.php` + `ajax_terminalSave.php` (受信端末詳細) — pendiente (provisional, espera a Japón): solo modelos `Terminales\Beacon\{Beacon,BeaconRec

## 業務支援管理 e integraciones (category5, API) — S5

| Sección | Hecho | Pendiente | Descartado | Otro |
|---|---:|---:|---:|---:|
| 1. Tabla de paridad › 1.1 スタッフ管理 (`system/category5/staff/`) | 16 | 0 | 1 | 0 |
| 1. Tabla de paridad › 1.2 勤怠管理 — terminal de asistencia (`system/category5/kintai/`) | 12 | 0 | 2 | 0 |
| 1. Tabla de paridad › 1.3 勤怠一覧 / 集計 (`system/category5/working/`) | 10 | 0 | 2 | 0 |
| 1. Tabla de paridad › 1.4 スケジュール管理 (`system/category5/schedule/`) | 2 | 0 | 4 | 0 |
| 1. Tabla de paridad › 1.5 初期設定管理 (`system/category5/config/`) y menú | 1 | 5 | 0 | 0 |
| 1. Tabla de paridad › 1.6 API JWT de la tienda móvil (`api/`) | 13 | 0 | 0 | 0 |
| 1. Tabla de paridad › 1.7 API de la app マイページ (`api/mpa_*.php`) | 5 | 0 | 1 | 1 |
| 1. Tabla de paridad › 1.8 `appview/` (WebView de la app del cliente) | 20 | 0 | 8 | 0 |
| 1. Tabla de paridad › 1.9 `system/tool/api` y CTI (`system/desktop_request`) | 12 | 0 | 0 | 1 |
| 1. Tabla de paridad › 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`) | 4 | 1 | 5 | 0 |
| 1. Tabla de paridad › 1.11 Fuera de alcance de S5 (para que no falten filas) | 0 | 0 | 0 | 3 |

**Pendientes (6) y estado sin clasificar (5):**

- _1. Tabla de paridad › 1.5 初期設定管理 (`system/category5/config/`) y menú_
  - `felica_mode.php` (カードリーダーモード設定) — pendiente ⏸
  - `card_new.php` (カード管理 (新規カード管理)) — pendiente ⏸
  - `card_staff_select.php` ((fragmento)) — pendiente ⏸
  - `index.php` (category5) (業務支援管理 (menú))
  - `inc/page_control.php` ((paginador))
- _1. Tabla de paridad › 1.7 API de la app マイページ (`api/mpa_*.php`)_
  - `mpa_customerSync.php` (`/api/mpa_customerSync.php`) — diferido: POS fuera de alcance (`PosGateway::syncCustomers()`); al retomarlo, comando Arti
- _1. Tabla de paridad › 1.9 `system/tool/api` y CTI (`system/desktop_request`)_
  - `tool/api/category0/setPosdomain.php` (`/system/tool/api/category0/setPosdomain.php`) — diferido: POS fuera de alcance (escribe `tb_shop.pos_domain`; `PosGateway::setShopDomain()
- _1. Tabla de paridad › 1.10 Utilitarios (`version/`, `air/`, `ptag/`, `exp_cmd/`, `cron/`, `instagram/`, `ipad/`)_
  - `exp_cmd/cron/get_shukei_data.php`, `get_shukei_data2.php` (`/exp_cmd/cron/get_shukei_data*.php`) — pendiente ⏸ (escribe en otro servidor, 203.183.91.218, con la clave del POS, y no se sabe 
- _1. Tabla de paridad › 1.11 Fuera de alcance de S5 (para que no falten filas)_
  - A.1 `system/pos_session.php`, `system/terminal_login.php`, `category11/terminal.php` — diferido: POS fuera de alcance
  - A.5 `r/`, `reserve/`, `getReservationWaitTime.php` — de S2 (reservas)
  - `category11/*`, `category12/*` — diferido: POS / SNS fuera de alcance

## Cómo se obtuvo

`git show origin/laravel13:Doc/diseno/<doc>.md` de los cinco documentos y un script que recorre cada tabla Markdown cuya cabecera tiene la columna `Estado`, toma el título `##`/`###` como sección y clasifica la celda por su primera palabra (`hecho…`, `pendiente…`/`⏸…`, `descartado…`; el resto, "otro"). Las filas de otras tablas (contratos, decisiones, pruebas) no cuentan.

