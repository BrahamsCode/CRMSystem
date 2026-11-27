# S4 — Contenido y terminales (マイページ, ホームページ, 端末管理, señalización)

**Modelo:** Sonnet 5.5, esfuerzo medio · **Carpeta:** `CRMSystem-contenido/` · **Rama:** `l13/contenido`
**Módulos:** マイページ管理 (`mypage`), ホームページ管理 (`web`), 端末管理 (`terminales`), señalización (`senalizacion`). **Es dueña de:** sus carpetas según `Doc/contratos/CONVENCIONES.md` (secciones 2 y 3).

## Modo autónomo

Trabajas sin pedir confirmación. Al terminar cada tarea: actualizar este archivo y seguir con la siguiente. Si falta un detalle menor, decide lo más simple que respete las convenciones, anótalo en "Decisiones" y sigue. Al terminar todo o bloquearte: `SendMessage` a "S0 Coordinador CRM" (una línea: qué hiciste, qué sigue, qué necesitas). Cada entregable terminado con pruebas en verde: `git add` de tus rutas, commit (mensaje en español) y `git push origin <tu rama>`; después avisa a S0, que integra en `laravel13`. No haces merge ni cambias de rama; nunca `--force`.

## Próximo paso — Esperar a S0 (capa de datos 1–5 entregada)

Capa de datos de los submódulos 1–5 hecha y en verde (2026-11-27). Siguiente, cuando S1 integre layout, rutas de admin y Policies: pantallas en el orden del diseño (§4), empezando por マイコレ. Sin núcleo se puede adelantar: rutas públicas `routes/publico/senalizacion.php` (XML `hp/uploads/c7/display/{cd}.xml` y `floormap.xml`) con su prueba.

## Después (cuando S0 apruebe el diseño y el núcleo esté integrado)

Implementar submódulo por submódulo en el orden del diseño: migraciones, modelos, Form Requests, controladores, vistas Blade (textos en `lang/ja/<modulo>.php`), rutas en `routes/modulos/<modulo>.php`, menú en `config/menu/<modulo>.php`, pruebas Pest. Cada submódulo terminado: pruebas en verde (`bash scripts/en-docker.sh php artisan test`), filas en `hecho`, commit y push de tu rama, y aviso a S0.

**Sin `hp/`:** el sitio viejo no se migra. Marca como `descartado: sin consumidor` el contenido que solo leía `hp/`, salvo que S0 diga otra cosa; lo que leen la app (`api/mpa_*`, `appview`) o la señalización sí se migra. Imágenes de `hp/uploads/` → Storage, conservando o redirigiendo las URL que devuelven las APIs y que leen los reproductores (`hp/uploads/c7/display/`).
**Fuera de alcance:** touchcation y su gestión `category7/kiosk/` (情報端末管理, `tb_nextouch_*`): confirmado que no se usan. **Señalización:** `digital_signage` de category7 + los reproductores. Los reproductores (`signage/`, `digitalsignage/`) son dispositivos: mantener su contrato (AppKey `md5(HTTP_HOST + '<>' + rank)` incluida) o avisar a S0 antes de cambiarlo. `digitalsignage` **se mantiene** (Antony): su reproductor es Flash y se rehace en HTML5 con el mismo contrato (`?shop_cd=&display_cd=`, XML del programa, ruleta y tragaperras). En desarrollo no hay displays registrados: crea datos de prueba con seeders.

## Lee solo

- `CLAUDE.md`, `Doc/contratos/CONVENCIONES.md`, `Doc/database/ESTANDARES_BD.md`, `Doc/analisis-legacy/README.md` (hallazgos transversales)
- Del análisis, por secciones (grep del título): `Doc/analisis-legacy/04-web-y-mypage.md`; en `05-operaciones-terminales-reservas.md` solo la sección de category7 (端末管理) **sin** 情報端末管理 (`kiosk/`); en `08-quiosco-y-senalizacion.md` solo signage y digitalsignage
- Para dudas de comportamiento: el código en `C:\Users\User\VivaTech\crm2-php8\public_html` (solo lectura) y `Doc/legacy-crm2/MANUAL-TESTS.md` (tu módulo)

## Pedidos

Ver diseño §5. Abiertos: a S1 columnas de `terminals`, de `shops` para floormap (`floor_name`, `service_category`, `company_details`), paquetes imagen/QR/PDF, `push.host`, conexión `beacon`, Policies, importador. A Antony (vía S0): GoTel/PDF y ビーコン.

## Decisiones

- S0 (2026-11-26): touchcation y `category7/kiosk` fuera de alcance (no se usan). La AppKey `md5(host<>rank)` era de `signageApp` (touchcation): ya no aplica a S4.
- category4 entero y lo de category6 que solo leía `hp/`: descartado: sin consumidor. Vivo: マイコレ, 2 opciones de app, オススメ, 求人, ガチャリン.
- Tablas pivote en vez de listas `<>`; `id = cd` en displays, programas, materiales y `web_signage_terminals`; `legacy_cd` solo en `web_signage_contents`; resto por `legacy_id_map` (S1). `hyouji_flg` → `status`.
- XML: misma forma que el legacy pero escapado; 【応募資格】 corregido; materiales borrados se omiten. `tb_customer_card.name` no se migra (nunca se escribía).
- Imágenes de マイコレ con su nombre legacy en `hp/uploads/c6/<carpeta>/` del disco public. `DatabaseSeeder` llama a `MypageSeeder` y `TerminalesSeeder` (2 líneas).

## Hecho

- 2026-11-27 Capa de datos 1–5: 5 migraciones, 11 modelos, 5 enums, 7 factories, 2 seeders, `SignageFeedBuilder` + 14 pruebas (suite 100 verde).

- 2026-11-26 Diseño `Doc/diseno/contenido-terminales.md` (paridad, esquema con mapeo, servicios, orden 60 pts, pedidos).
