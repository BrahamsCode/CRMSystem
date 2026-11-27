# Archivos rotos o que no funcionan

> Versión japonesa: [BROKEN-FILES.ja.md](BROKEN-FILES.ja.md). Resumen de la migración y mapa de documentos: [MIGRATION.md](MIGRATION.md).

> Lista para revisar **al final** de la migración. Aquí no se corrige nada: se anota qué falla, cómo se sabe y qué hay que decidir.
> Regla: lo que aparezca roto durante el trabajo se agrega a este archivo (ver [CLAUDE.md](../CLAUDE.md)).
> Actualizado: 2026-11-11.

**Cómo leer "Evidencia":** ✅ comprobado ejecutándolo · 🔎 deducido leyendo el código, sin ejecutarlo · ❓ depende de algo que no está en Docker.

"¿Ya fallaba?" compara con `master`: **Sí** significa que no lo rompió la migración.

## 1. No pueden funcionar

| Archivo | Qué falla | ¿Ya fallaba? | Evidencia | Qué hay que decidir |
|---|---|---|---|---|
| `touchcation/tagsignage/index.php`, `attach.php`, `complete_attach.php`, `id_search.php` | Fatal `Cannot redeclare class Base`: `system/inc/path_config.php` ya carga `system/class/Base.php`, con el mismo nombre de clase. `customer.php`, `home_x.php` y `home_y.php` cargan la misma clase y no dieron ese fatal sin sesión de quiosco | Sí, `master` carga las dos | ✅ fatal ejecutándolas en Docker; 🔎 "ya fallaba" deducido del código de `master` | Preguntar si esas pantallas de quiosco siguen en uso |
| `system/category7/digital_signage/movie_program_register.php` (opción 最後に追加) y su copia de `kiosk/` | Calcula el orden con `tb_c7_movie_program_cd`, columna que no existe en ninguna tabla de esta BD: el alta falla y no se guarda. Con 先頭に追加, la opción por defecto, funciona. Las bajas de pantallas y programas hacen una consulta sobre `tb_screensaver` con la misma columna: la baja se aplica, pero sus salvapantallas no se borran | Sí | ✅ `digital_signage`: error de SQL; 🔎 `kiosk` | Comprobar si producción tiene la columna; si no, corregir la consulta |
| `hp/user/yoyaku/reserve_list_ps2.php` | Exige `hp/user/inc/navi.php`, que no existe ni en `master` | Sí | ✅ da 500 en el barrido | Crear el archivo o borrar la página |
| `system/category9/yoyaku/yoyaku_new_register.php` | Escribe en `tb_yoyaku.moushikomi_time`, columna que no existe en esta BD (la tabla usa `start_time`), así que la reserva no se guarda. Después redirige a `yoyaku_new_calendar.php`, que no existe. `system/category11/yoyaku/` tiene una copia con el mismo código, sin probar | Sí | ✅ `category9`, con `tb_yoyaku_uketsuke_day` temporal: error de SQL y 404; 🔎 `category11` | Borrar o reparar. Comprobar antes si la BD de producción tiene esa columna |
| `system/category3/process-sortable.php` | Exige `./inc/kihon.php` y `./inc/db.php`, que no existen en `category3/`. Es el orden por arrastre de `coupon/coupon_list.php` y `coupon_bg_list.php` | Sí | ✅ da 500; 🔎 con sesión también, porque falla antes de comprobarla | Corregir las rutas o quitar el arrastre |
| `touchcation/signage_s/signage.php`, `touchcation/signageApp/signage.php`, `touchcation/tagsignage/signage/signage.php` | Usan `$connect` y ninguno carga `db.php` | Sí | ✅ `signage_s` y `signageApp` dan 500; 🔎 `tagsignage` no se pidió | ¿Se usan estas señalizaciones? Si sí, falta el `require` |
| `hp/mailsyori/mm_sche.php`, `mm_send.php` | Exigen `mailsyori/config.php` y `db.php` en rutas que no existen | Sí: en `master` apuntaban a `/home/system/public_html/mailsyori/`, tampoco en el repo | 🔎 `verificar-requires.php` | ¿Son tareas viejas? Si siguen en el cron de producción, hoy fallan |
| `system/aterna_a4_12.php`, `system/category3/mail_mag/aterna_a4_12.php` | Consultan `kaiin_master`, tabla del esquema antiguo (`店id`, `登録区分`) | Sí | 🔎 falta comprobar si existe en producción | Candidatos a borrar, con tu confirmación |
| `system/category0/config/create_immovables.php` | Sus `require` están dentro de un comentario: no hay `$connect` | Sí | ✅ daba 500; ahora 403 (paso 32) | Candidato a borrar, con tu confirmación |
| `digitalsignage/html/immovables/index.php` | Exige `db.php` en su propia carpeta, que no existe | Sí, `master` tiene el mismo `require_once('db.php')` | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-11) | ¿Se usa? Si sí, falta el archivo |
| `hp/inc_m/test/docomo_XHTML/index.php` | Exige `../emoji/trans.php`, que sería `hp/inc_m/test/emoji/`; el archivo está en `hp/inc_m/emoji/`. Es una página de prueba para móviles docomo | Sí | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-11) | Candidata a borrar, con tu confirmación |
| `system/mail.php` (メール送信, solo por IP de desarrollo) | Carga `kihon.php` pero no `db.php`, que es quien carga el autoload: `CrmSystem\Util\Common` no existe en la línea 7 | Sí | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-13) | ¿Se usa? Es una herramienta de envío de prueba. `mail_send.php` (su destino) manda además una copia a una dirección de `glory.ne.jp` |
| `system/category3/mail_mag/mail_schedule.php` | Sin `db.php` ni `path_config.php`: `$util` no existe en `inc/head.php:130` | Sí | ✅ fatal en Docker; desarrollo: 500 (2026-11-13) | Sin enlaces. Candidata a borrar, con tu confirmación |
| `system/category1/customer/kanren_record_confirm.php` | Exige `category1/inc/kanren_record_confirm.php`, que no existe ni en `master` | Sí | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-13) | Sin enlaces. Candidata a borrar |
| `system/category12/test.php` | Exige `inc/left_navi.php`, que no existe | Sí | ✅ fatal en Docker | Página de prueba del módulo SNS. Candidata a borrar |
| `system/category3/coupon/pit_coupon1.php` | Consulta `shop_master`, tabla del esquema antiguo (como `kaiin_master`) | Sí | ✅ error de SQL en Docker | Sin enlaces. Candidata a borrar |
| `admin/mail/index.php` | Exige `admin/inc/files.php`, que no existe ni en `master` | Sí | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-11) | Nada: `admin/` está fuera de alcance (`PENDING.md`, 1.2). Candidata a borrar, con tu confirmación |
| `touchcation/point_app/api/index.php` sin parámetro | Es el enrutador de la API: `?getUserList` carga `getUserList.php`. Sin parámetro busca `home.php` y luego `404.php`, y ninguno existe | Sí | ✅ fatal en Docker; desarrollo: 500 sin cuerpo (2026-11-11) | Nada si la app siempre llama con parámetro. Crear `404.php` daría una respuesta limpia |

En Docker, el texto de estos fatales sale en pantalla solo porque `docker/php.ini` tiene `display_errors=On` para encontrarlos. Con el `php.ini` de producción (`PENDING.md`, 1.7) es el mismo 500 vacío que da desarrollo.

| 16 archivos de `system/` que cargan `session.php` antes que `db.php`: las copias antiguas de `category4` (`recruit/` ×4, `shop_hp/company_*` ×4, `stamp/` ×4), `category4/page/report.php`, `category3/mycoupon/mycoupon_create.php` y `mycoupon_complete.php`, `category3/anketo/anketo_test.php`. (Los 13 de `touchcation/tagsignage` que se contaban aquí cargan su propia `inc/session.php`, que no usa la BD: no tienen este problema) | `session.php` consulta el personal con `$connect`, que aún no existe. **En producción (PHP 7)** la consulta devolvía `null`, `session.php` tomaba al usuario por no identificado, **vaciaba `$_SESSION` y mandaba al login**: abrir la pantalla cerraba la sesión. En PHP 8.5.12 es un fatal (`TypeError` en `pg_escape_string`) | Sí (en otra forma) | ✅ barrido del módulo 5 (2026-11-05); 🔎 ninguno está enlazado desde el menú ni desde otra pantalla: el menú usa las versiones de `category6` | **Igualado a PHP 7 (2026-11-09):** `session.php` se salta la consulta si no hay `$connect` y cierra la sesión y manda al login, como hacía producción. Medido: arnés en PHP 7.4.33 con el `session.php` de `master` y en PHP 8.5.12 con el nuevo (la misma sesión resultante), y en la web con `report.php` (antes fatal, ahora 302 al login). Queda decidir si se borran |

## 2. Dependen de algo que ya no existe

| Qué | Dónde | Efecto | Evidencia | Qué hay que decidir |
|---|---|---|---|---|
| **Adobe Flash** | 54 `.swf`, 41 pantallas. 17 son la **descarga CSV del personal** (`csv_download/sample.swf`) | La descarga no puede funcionar en un navegador actual | 🔎 `main.as:169`; sin probar con el personal | Ver [DEPENDENCIES.md](DEPENDENCIES.md) 11b: botón HTML con descarga por PHP |
| **APIs de Twitter y Facebook** | `system/category12` (`ajax_postTwitter.php`, `ajax_postFacebook.php`) | Esas versiones de las API ya no existen | 🔎 no se ejecutó para no publicar | Consulta pendiente con tu jefe |

## 3. No se pudieron probar aquí

No se sabe si fallan: dependen de piezas que no están en Docker.

| Qué | Por qué | Evidencia |
|---|---|---|
| ~~`admin/` (160 archivos)~~ | Conecta a `host=localhost` con una BD que tampoco existe en desarrollo. **Fuera de alcance** (Antony, 2026-11-11): las 160 cargan sin avisos de PHP, salvo `mail/index.php` (sección 1) | ✅ mismo mensaje que desarrollo |
| `category9/yoyaku/ajax_getReservationData.php`, `category11/yoyaku/ajax_yoyaku_koumoku_new.php` y similares con `$posConnect` | Necesitan un servidor POS externo: dan 500 antes y después de la migración. Las 7 pantallas del menú de `category11/yoyaku` ya no: muestran lo mismo que desarrollo (2026-11-09) | ❓ |
| `category11/item` | Sus tablas (`tb_c2_category`, …) no están en el backup | ❓ |
| `system/category1/customer/customer_export.php` | Conecta a `host=localhost` con otra contraseña | ❓ |

## 4. Funcionan, pero tienen un defecto conocido

| Archivo | Qué pasa | Evidencia |
|---|---|---|
| `system/category1/customer/csv_from_js.php` | Con 22,000 clientes supera el límite de 30 s de CPU. Su comentario de 2013 dice que nadie lo usa | ✅ fatal en el log, sin sesión |
| `system/category1/customer/csv_download/csv_file_out.php` | Mismo límite de 30 s (log del 2026-10-30) | ✅ fatal en el log |
| `system/category0/config/system_file_export.php:101-102` | `preg_match("/\uploads\/c9/")`: `\u` no existe en PCRE, el patrón es inválido y devuelve `false`, así que `uploads/c9` y `c11` no se excluyen de la exportación. Igual en PHP 5 | 🔎 |
| `system/category11/placeview/index.php` (`../inc/inc.php`) | `BOX_FLG_COMPLETE` se define 3 veces y `BOX_FLG_CALLING` no se define. Riesgo aceptado: no se toca | 🔎 ver [REFACTORING.md](REFACTORING.md) |
| `system/category0/shop/shop_list.php` (検索対象 = 全て, solo por IP) | `Shop.php:34` busca `disp_flg = 'all'`: la opción 全て no muestra ninguna tienda. Igual en `master` | 🔎 + ✅ probado 2026-11-13 |
| `system/category0/shop/shop_list.php` (búsqueda por メールアドレス) | `Shop.php:32` busca en la columna `mail`, que `tb_shop` no tiene (se llama `email`): la búsqueda muestra un error de SQL. Igual en `master`, mismo código. Pregunta 14 de `PENDING.md` | ✅ pasada de formularios del 2026-11-20 |
| `system/category11/sumup/sumup_graph.php` | En `master` da 500 (`Cannot use temporary expression in write context`, línea 299). La rama ya lo compila y la página carga, pero llama a `setGraph1()`…`setGraph12()`, que no están definidas en ningún archivo, y el navegador da un error de JavaScript. Usa las tablas `tb_c2_*` (`MISSING-TABLES.md`, sección 1) | ✅ navegador contra `master` en PHP 7.4.33 y contra la rama (2026-11-20) |
| `system/category4/contents/contents_recover_confirm.php:58/68` | Busca duplicados por `id` y `email1` en `tb_c4_contents`, que no tiene esas columnas (código copiado de la recuperación de clientes): las dos consultas fallan con aviso y la recuperación sigue. Igual en `master` | ✅ 2026-11-13 |
| `system/category3/visit/customer_detail.php:32` | Sin un cliente válido redirige a `./customer_list.php`, que no existe en `category3/visit` (ni en `master`): 404. Con un cliente no se pudo abrir: la BD local no tiene clientes. Se deja por paridad | ✅ 2026-11-19 |

**Peligrosas al abrirlas, ya con 403** (Antony, 2026-11-16): `system/category0/config/test_fix.php` borraba `tb_base_system_child` y la rellenaba sin ninguna condición, y `system/category3/visit/fix_visit_stats.php` reescribía todo `tb_raiten_rireki`, las dos con un GET de cualquier persona con la sesión iniciada. Guarda `PHP_SAPI !== 'cli'`: 403 por web (comprobado con y sin sesión de master, y la BD no cambia). **Por CLI tampoco hacen nada** (revisado el 2026-11-18): cargan `session.php` antes de tocar la BD, y sin sesión `session.php` termina con `exit`. Nadie las enlaza ni las llama, en esta rama ni en `master` (ni enlaces, ni `include`, ni cron, ni `exec`); están desde el primer commit. Así que hoy no las puede ejecutar nadie. Si se decide borrarlas, no hace falta nada más.

## Candidatos a borrar (requieren tu confirmación)

- 3 copias de Qdmail, sin uso desde el paso 20h.
- Las 2 copias de `aterna_a4_12.php` que consultan `kaiin_master` y `create_immovables.php`, si se confirma que no se usan.
