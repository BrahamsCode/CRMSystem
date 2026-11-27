# Llamadas externas (S1, 2026-11-28)

Auditoría de solo lectura de `public_html` (app, routes, config, resources/views y el JS de `public/legacy`; sin `vendor`) sobre `l13/nucleo`, contrastada después con `laravel13` 23bd4e2 (checkout principal): allí S3 y S5 ya habían adoptado GApi. Se buscó cualquier salida a internet desde el servidor: `Http::`, Guzzle, `file_get_contents`/`fopen`/`curl_*` con http, `SoapClient`, sockets, correos a direcciones fijas y URL fijas. Del navegador se anotan solo las peticiones que hace la página sola, no los enlaces que el usuario pulsa.

**Interruptores:**
- `GApi`: `App\Support\GApi`, controlado por `CRM_GAPI_ENABLED`. Fuera de producción está apagado y solo escribe en el log (NUCLEO.md §9).
- `push_trigger`/`LogPushTrigger`: S3 los quitó en b55fffe; el aviso de push ya va por GApi.

**Estado de `:8081`:** `APP_ENV=local` y `CRM_GAPI_ENABLED=false`. El correo sale por SMTP a Mailpit. `push_api_host = 0` (servidor de desarrollo de g-api). No hay planificador ni worker corriendo, así que las tareas programadas no se ejecutan solas.

## Servidor → internet

| Archivo:línea | Destino | Dueño | Interruptor | Riesgo en desarrollo |
|---|---|---|---|---|
| `app/Services/Base/ShopService.php:147` | g-api `/api/mpa/setStoreTel` (al guardar una tienda con la app activa) | S1 | **GApi** | Ninguno: apagado, solo log |
| `app/Services/Nucleo/StaffLogin.php:119,133,137` | Servidor de soporte `crm.support.api_host` (por defecto b.g-api.net): `verifyOnetimePassword`, `verifyAccountLogin` | S1 | **GApi** | Ninguno: apagado, el login de soporte no entra |
| `app/Services/Promotions/GApiPushTrigger.php:31` | g-api `/api/mpa/pushMessage` (envío de push, push programados de `SendFollowPushCommand`, `GrantPaidMemberPointsCommand`) | S3 | **GApi** (b55fffe) | Ninguno: apagado, solo log |
| `app/Services/Mypage/RecommendationPush.php:34` | g-api `/api/mpa/pushMessage` (al guardar una オススメ con la app activa) | S4 | **ninguno** | **Medio**: en `:8081` sale a b-develop.g-api.net cada vez que se guarda una オススメ; con `push_api_host = 1` (el backup) iría a producción. **Pendiente**: adoptar GApi |
| `app/Services/Integraciones/Appview/GApi.php:29` | g-api `getPhotoGallery`, `getComingHistory` (rutas públicas `appview/photo_gallery.php`, `visit_history.php`) | S5 | **GApi** (3528d74) | Ninguno: apagado, la vista queda sin datos |
| `app/Services/Integraciones/MobileShop/HttpMobileShopClient.php:112` | API de extlink (`services.mobileshop.extlink_url`): JWT, baja del pago. La llaman 有料会員 (`PaidMemberController`), la ficha del cliente (`CustomerDetailData`), `MobileShopController` y `GrantPaidMemberPointsCommand` | S5 | Solo que la URL esté vacía (`MOBILE_SHOP_EXTLINK_API_URL`) | Ninguno mientras la variable siga vacía (en `:8081` lo está). **Además usa `withoutVerifying()`** (no comprueba el certificado TLS, como el legacy). Valorar pasarlo por GApi o por un interruptor propio |
| `app/Services/Customers/CustomerListService.php:151` | b.g-api.net `/api/smatopi/id` | S3 (TODO) | — | Ninguno: es un TODO, aún no llama |

Revisados y **sin llamada** desde el servidor: `QrBaseInfo.php:101` (solo construye el texto del QR con la URL de g-api), `PhotoAlbumUploader.php:20` (`RETURN_URL`: redirección del navegador de la app a b-develop.g-api.net, como el legacy), `MpaController.php:116` (enlace `http://mobile-s.jp/` en la respuesta), `AccessTerminalController.php:42` (solo arma el subdominio `*.crm-s.net`), `ReservationPrintService.php:65,77` (son espacios de nombres XML, no URL que se pidan). No hay `curl_*`, `SoapClient`, sockets ni `file_get_contents` con http.

## Correo

Todos los envíos pasan por el mailer de Laravel. El riesgo depende de `MAIL_MAILER`: en `:8081` es Mailpit y en los worktrees `log`, así que no sale nada. En los datos de `crmsystem2` los 311 correos de clientes son `@example.*`; tb_shop 1, tb_office y el staff 1 usan `@master.master`, que no es un dominio real.

| Archivo:línea | Destino | Dueño | Riesgo en desarrollo |
|---|---|---|---|
| `app/Services/Base/ShopService.php:157` | `crm.notify.shop_created`, que por defecto es **support@glory.ne.jp** (dirección real del proveedor) | S1 | Solo con SMTP real. Tiene el mismo valor por defecto que el legacy |
| `app/Http/Controllers/Admin/Nucleo/ContactController.php:60` | `crm.notify.contact`, que por defecto es **crm@extlink.co.jp** (dirección real del proveedor) | S1 | Solo con SMTP real. Tiene el mismo valor por defecto que el legacy |
| `app/Services/Promotions/MailSender.php:187` | Clientes de la メルマガ y copia a `admin_master.system_mail` (el seeder la cambia a `crm-mail@example.com`; en el backup es **crm-mail@extlink.co.jp**) | S3 | Solo con SMTP real, y con datos que no sean demo |
| `app/Services/Promotions/MailSender.php:259` | `tb_shop.email` (aviso a la tienda) | S3 | Igual |
| `app/Http/Controllers/Admin/Promotions/MailAjaxController.php:132` | Dirección de prueba de la メルマガ que se escribe en el formulario | S3 | El usuario decide el destino |
| `app/Services/Promotions/StampExpiryMailer.php:144`, `StampService.php:92` | Clientes (aviso de sellos o cupones) | S3 | Solo con SMTP real |
| `app/Services/Reservations/ReservationMailer.php:340`, `SimpleReservationMailer.php:107` | Cliente y tienda de la reserva | S2 | Solo con SMTP real |
| `app/Services/Integraciones/Appview/AppCustomer.php:608` | Correo del socio que se registra desde la app | S5 | Solo con SMTP real |
| `app/Http/Controllers/Admin/Senalizacion/ContactController.php:40` | `senalizacion.contact_notify` (`SIGNAGE_CONTACT_NOTIFY`, vacío por defecto) | S4 | Ninguno: vacío no envía |

`.env.example` ya trae `MAIL_MAILER=log` y `APP_ENV=local`, pero no `CRM_GAPI_ENABLED`; conviene añadirlo como `CRM_GAPI_ENABLED=false` para dejarlo explícito. Recomendación: que `crm.notify.*` no tenga como valor por defecto una dirección real fuera de producción. Hoy los dos valores por defecto copian el legacy (S1 puede hacerlo si S0 lo decide).

## Navegador → internet (la página lo pide sola)

| Dónde | Destino | Dueño | Riesgo |
|---|---|---|---|
| `public/legacy/signage/view/index.html` y `view/back/*.html` | `maxcdn.bootstrapcdn.com` (font-awesome 4.3 CSS) | S4 | Bajo: CSS de CDN público (privacidad: el CDN ve la IP) |
| `public/legacy/signage/view/js/jquery.jpostal.js:16` | `jpostal-1006.appspot.com` (búsqueda de código postal) | S4 | Bajo: manda el código postal escrito a un tercero |
| `resources/views/welcome.blade.php` | `fonts.bunny.net` | S1 (plantilla de Laravel, sin ruta que la muestre) | Ninguno |

Enlaces que solo se siguen si el usuario pulsa: `ac.crm-s.jp` (CRM端末登録, `access-terminals/index.blade.php`), `crm-s.jp` (cabecera y システム選択), `smatopi.net/login` (`config/menu/promocion.php:73`), `signage.vizan.co.jp` (`senalizacion/screens/packages.php`). Las URL de las librerías (ckeditor, tinymce, bootstrap, moment…) son comentarios y licencias, no peticiones.

## Para repartir

1. **S4**: `RecommendationPush` → `App\Support\GApi`. Es la única llamada a g-api que en `laravel13` 23bd4e2 todavía sale sin interruptor.
2. **S3**: hecho (`GApiPushTrigger`, b55fffe). Hay que vigilar `CustomerListService` cuando se implemente el TODO de スマトピ.
3. **S5**: `Appview\GApi` hecho (3528d74). Falta `HttpMobileShopClient` → GApi o un interruptor propio, y decidir sobre `withoutVerifying()`.
4. **S0**: decidir los valores por defecto de `crm.notify.*` y si se añade `CRM_GAPI_ENABLED=false` a `.env.example`.
