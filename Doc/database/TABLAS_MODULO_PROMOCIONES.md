# Módulo 2 · Promociones (販売促進管理)

Rediseño del módulo `category3` del legacy. La lógica se rehízo, pero cubre todas sus funciones. El código está en:

- `app/Http/Controllers/Admin/Promotions`
- `app/Services/Promotions`
- `resources/views/admin/promotions`

Todas las tablas siguen [ESTANDARES_BD.md](./ESTANDARES_BD.md):

- Texto en `text` y códigos en `smallint` con comentario y enum de PHP.
- Todas llevan `status`, `timestamps()` y `softDeletes()`.
- `uid` en lo que ve el cliente final.

## Qué hacía el legacy y dónde está ahora

| Legacy (category3) | Nuevo |
|---|---|
| メルマガ, デコメール, プッシュ通知: tres pantallas de «発行条件選択» y sus listas de programados y pasados | **Envíos**: un solo flujo con canal email de texto, email con diseño o push. Pestañas Enviados, Programados, Borradores y Automáticos |
| 発行条件選択 (filtros de destinatarios) | Los **mismos filtros de Buscar clientes** (`App\Services\Customers\CustomerSearch`), con recuento en vivo |
| 再配信 | «Volver a enviar»: copia el envío como borrador |
| フォロー (tras la visita, tras el alta, cumpleaños, ciclo de visita, aniversario), 自動 (fechas, días de la semana), リマインダー | **Automatizaciones**: una tabla `message_rules` con `trigger`. Cada ejecución crea un envío normal |
| テンプレート管理 | **Plantillas** |
| テストメールアドレス管理 | **Emails de prueba** y el botón «Enviar prueba» |
| 配信からのマイページアクセス集計 (sexo, edad, ocupación) y 時間別集計 | Detalle de cada envío (clics por perfil, tiempo hasta abrirlo) y **Rendimiento** por canal |
| マイクーポン: alta, lista, ranking, estadísticas, envíos con cupón | **Cupones**: alta con 6 usos, entregas, canje, uso en caja, ranking y estadísticas por periodo |
| スタンプ: diseño, especificación, reglas de emisión, aviso con cupón | **Tarjeta de sellos**: diseño, reglas, premios por número de sellos, vencimiento y aviso |
| ポイント仕様管理 | **Puntos**: % por compra, vencimiento y renovación, avisos, reglas por grupo, alta y referidos |
| アンケート y su análisis | **Encuestas**: preguntas con los tipos de campo del módulo 1, página pública, premio por responder y resultados |
| 来店履歴 | **Historial de visitas**: por hora y día de la semana, y clientes del periodo |
| カードリーダ設定 | Es `shops.visit_interval_seconds`, que ya existía en el módulo 1. Se enlaza desde el historial |
| 会員登録用QRコード / メールマガジン基本情報 | **QR de registro** por tienda y cifras de la lista de correo por tipo de dirección |
| スマトピ | Servidor externo マイコレ: queda como el canal push, detrás de la interfaz `PushSender` |

## Qué se reutiliza del módulo de clientes (sin tablas nuevas)

- **Filtros**: `CustomerSearch` es la misma clase en Buscar clientes y en Envíos. Los filtros de un envío se guardan en `messages.filters` (jsonb).
- **Columnas de `customers`** que usa el módulo:
  - Para elegir a quién se envía: `mail1`, `mail_magazine_flg`, `bounce_count`, `address_type`.
  - Para las automatizaciones: `birth_date`, `wedding_date`, `last_visit_date`, `average_visit_cycle`, `next_visit_date`.
  - Para los puntos por grupo y por referidos: `customer_group_id`, `referrer_id`.
- **`visits`**: dan sellos y puntos (observer `LoyaltyObserver`), alimentan el historial y hacen de «ventas» hasta que exista el módulo de caja.
- **Tipos de campo** de las encuestas: `App\Enums\CustomFieldType`.

## Tablas nuevas

| Tabla | Para qué |
|---|---|
| `coupons` | Tabla del **estándar** (`name`, `type`, `value`), más uso, tienda, costo, vigencia, sorteo, condiciones y visibilidad |
| `coupon_customer` | Cupones entregados: vencimiento, uso y tienda donde se usó. Intermedia en orden alfabético |
| `messages` | Envíos: canal, contenido, filtros, cupón y encuesta, estado (`delivery_status`) y fechas |
| `customer_message` | Destinatarios: enviado, fallido, abierto, clic y número de clics. Su `uid` es el token de los enlaces |
| `message_rules` | Automatizaciones (`trigger` 1–8), calendario, hora, canal, contenido, cupón y filtros extra |
| `message_templates` | Plantillas por categoría |
| `test_mail_addresses` | Emails de prueba por tienda |
| `stamp_settings`, `stamp_rules`, `customer_stamps` | Configuración de la tarjeta, premios por número de sellos y movimientos |
| `point_settings`, `customer_group_point_rules`, `customer_points` | Configuración por tienda y por grupo, y movimientos |
| `surveys`, `survey_questions`, `survey_responses` | Encuestas, preguntas y respuestas (jsonb) |
| `customer_devices` | Tokens de push. Reemplazarán a `customers.device_type` y `easy_login` |
| `customer_locations` | Ubicaciones de la app para el filtro «estuvo cerca de una tienda» |

Además, `customers` gana `stamp_balance` y `point_balance`: el saldo calculado desde los movimientos, para la ficha y los filtros.

### Saldos y vencimientos

- **Saldo**: es siempre la suma de los movimientos. No hay un saldo por movimiento.
- **Vencimiento**: se consume primero lo más antiguo. Lo que vence es lo caducado menos lo ya gastado (`LoyaltyService::expire`), así que el proceso se puede repetir sin efectos dobles.

## Procesos en segundo plano

| Comando | Cada | Qué hace |
|---|---|---|
| `promotions:send-scheduled` | minuto | Envía los programados cuya hora llegó |
| `promotions:run-rules` | minuto | Ejecuta las automatizaciones a su hora, una vez al día |
| `promotions:loyalty` | 15 min | Vence sellos y puntos y envía los avisos previos |

Los envíos salen por la **cola** (`SendMessageBatch`, lotes de 200). En desarrollo hacen falta dos procesos:

```bash
php artisan schedule:work
php artisan queue:work
```

En el servidor: el cron de `schedule:run` y un worker de la cola.

## Seguimiento

- **Apertura**: imagen invisible en los emails con diseño, y toque en la notificación push (`/p/o/{uid}.gif`, `/p/n/{uid}`). En los emails de texto no se puede medir.
- **Clic**: cada enlace del mensaje se cambia por `/p/c/{uid}?u=…`, firmado con `URL::signedRoute`. Así no se puede usar como redirección abierta.
- **Correos fallidos**: suman a `customers.bounce_count`. Al tercero, la dirección pasa a «no entregable», como en el legacy.

## Pendiente de otros módulos

- **Recordatorio de reservas**: la regla se guarda, pero solo se envía cuando exista la tabla `reservations` (módulo 9).
- **Push real (FCM o APNs)**: implementar `PushSender` cuando exista la app de Mi página. Hoy `LogPushSender` escribe en el log.
- **Efectivo o tarjeta**: el POS todavía no indica cómo se pagó. Los puntos usan el % de efectivo.
- **Personal**: la tabla `employees` sigue pendiente en el estándar.
