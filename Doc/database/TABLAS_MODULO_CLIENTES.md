# Tablas que necesita el módulo de clientes

Qué tablas hacen falta para el módulo 1, de dónde sale cada una y qué se añadió por
decisión propia.

**Fuentes usadas:**

1. **Estándar de la empresa** — las tablas y campos comunes de
   [ESTANDARES_BD.md](./ESTANDARES_BD.md).
2. **Los mockups de `Doc/mockups/modulo-clientes/`** — las 14 pantallas y las 23 capturas
   del legacy. Es la fuente de qué datos existen realmente.
3. `schema.sql` y los demás documentos de esta carpeta son **solo referenciales**: se
   consultaron, pero no se copiaron.

---

## Respuesta corta

**13 tablas**, de las cuales 2 ya existen en el estándar de la empresa y 11 son nuevas.

| # | Tabla | Origen | Pantalla del módulo |
|---|---|---|---|
| 1 | `shops` | **Estándar** | Tienda de registro, filtros, estadísticas |
| 2 | `terminals` | **Estándar** | ID de terminal en ficha y búsqueda |
| 3 | `customers` | Nueva | Todo el módulo |
| 4 | `customer_groups` | Nueva | Grupos de clientes |
| 5 | `ranks` | Nueva | Rangos de clientes |
| 6 | `rank_schedules` | Nueva | Asignación de rangos |
| 7 | `visit_motives` | Nueva | Motivos de 1ª visita |
| 8 | `visits` | Nueva | Registrar visita |
| 9 | `field_settings` | Nueva | Campos y CSV |
| 10 | `custom_categories` | Nueva | Información adicional |
| 11 | `custom_fields` | Nueva | Información adicional · campos |
| 12 | `custom_field_options` | Nueva | Opciones de select/radio/checkbox |
| 13 | `custom_values` | Nueva | Categorías de tipo múltiple |

Cada tabla nueva corresponde a una pantalla real del módulo. No hay ninguna tabla que
no esté respaldada por una pantalla.

### Del estándar, lo que este módulo NO usa

`taxes`, `products`, `categories`, `category_product`, `product_shop` y `category_shop`
son del lado de ventas. `coupons` hará falta cuando se implemente la pestaña «Mis
cupones» de la ficha, junto con un pivote `coupon_customer`; en el módulo 1 esa pestaña
queda vacía, igual que Puntos, Sellos, Mi álbum, Reservas y Referidos.

---

## `customers` campo a campo

Verificado contra el formulario real del legacy (`customer_new.php`). Se rellenó con datos de prueba y se envió **solo hasta la confirmación** (`customer_new_confirm.php`), sin registrar nada. La primera columna es el nombre del campo que envía el legacy: sirve para migrar los datos.

| Legacy (POST) | Pantalla | Columna nueva | Nota |
|---|---|---|---|
| `id`, `login_id` | 会員番号 / ID | `customers.code` | El ID de acceso es el propio Nº de socio |
| `kanri_id` | 管理番号 | `management_no` | |
| `tb_shop_cd` | 登録店舗 | `shop_id` | |
| `type` = 個人 / 法人 | 個人・法人 | `type` 1 / 2 | Obligatorio |
| `name_sei`, `name_mei` | 名前 | `last_name`, `first_name` | En una empresa, `last_name` es la razón social |
| `name_kana_sei`, `name_kana_mei` | 名前(カナ) | `last_name_kana`, `first_name_kana` | El legacy exige katakana de ancho completo; aquí se convierte solo |
| `zip` | 郵便番号 | `zip` | El legacy quita el guion: `530-0001` → `5300001` |
| `pref` | 都道府県 | `pref` | Texto (大阪府) |
| `add` | 市区町村 | `city` | |
| `add_number` | 丁目・番地 | `street_address` | |
| `add_build` | マンション・ビル名 | `building` | Columna aparte: las etiquetas de correo lo imprimen en su propia línea |
| `add_kana`, `add_build_kana` | lecturas de la dirección | *(no se migran)* | No se buscan ni se ordenan por ellas |
| `tel` | 電話番号 | `tel1` | Solo dígitos, igual que el legacy |
| `kojin_mobile` | 携帯電話番号 | `tel2` | Solo personas |
| `fax` | FAX番号 | `tel3` | Estándar §2.4: teléfonos numerados |
| `email1`, `email2` | メールアドレス1 / 2 | `mail1`, `mail2` | |
| `kojin_mail` | 個人メールアドレス | `mail3` | Solo personas |
| `tb_staff_cd` | 指名担当者 | *(pendiente)* | Falta la tabla de empleados en el estándar |
| `searchKaiin_cd` | 紹介者 | `referrer_id` | |
| `kojin_birth_y/m/d` | 生年月日 | `birth_date` | El legacy acepta fechas imposibles (1990/02/29); aquí se validan |
| `kojin_sex` = 男性 / 女性 / vacío | 性別 | `sex` 1 / 2 / 0 | ISO 5218 completo; empresa = 9 |
| `kojin_bloodtype` | 血液型 | `blood_type` | A, B, O, AB |
| `job` | 職業 | `occupation` | Enum `Occupation` (texto japonés → número) |
| `kinmusaki_name`, `kinmusaki_name_kana` | 勤務先 名前 | `customer_companies.name`, `name_kana` | Solo personas |
| `kinmusaki_gyoushu` | 勤務先 業種 | `customer_companies.industry` | |
| `kinmusaki_tel`, `kinmusaki_fax` | 勤務先 電話 / FAX | `customer_companies.tel1`, `tel3` | |
| `houjin_setsuritsu_y/m/d` | 設立年月日 | `customer_companies.founded_on` | Solo empresas |
| `houjin_shihon` | 資本金 | `customer_companies.capital` | `bigint`, unidad mínima |
| `houjin_gyoushu` | 業種 | `customer_companies.industry` | Excluyente con el del lugar de trabajo: una sola columna |
| `houjin_busho` | 部署 | `customer_companies.department` | |
| `daihyousha_name`, `_kana` | 代表者 | `representative_last_name` / `_first_name` (+ `_kana`) | El legacy lo guarda en un solo campo: al migrar se separa por el espacio |
| `daihyousha_birth_y/m/d`, `daihyousha_sex` | 代表者 生年月日 / 性別 | `representative_birth_date`, `representative_sex` | |
| `tantou_name`, `_kana` | 担当者 | `contact_last_name` / `_first_name` (+ `_kana`) | Ídem |
| `tantou_tel`, `tantou_fax`, `tantou_mail` | 担当者 | `contact_tel1`, `contact_tel3`, `contact_mail` | |
| `mailmaga_flg` = 1 / 0 / 9 | メルマガ配信 | `mail_magazine` 1 / 2 / 3 | Enviar / no enviar / no entregable. Era `mail_magazine_flg`; tiene 3 estados, así que no es una bandera (§4) |
| `mailmaga_email_type` | アドレス区分 | `address_type` | パソコン, DoCoMo, AU, Softbank, その他 → 1..5 |
| `stop_yoyaku_reminder_mail_flg` = vacío / 1 | 予約リマインダーメール | `reservation_reminder_flg` 1 / 0 | El legacy la guarda invertida |
| `tb_customer_group_cd` | 顧客グループ | `customer_group_id` | |
| `tb_raiten_douki_master_cd` | 新規来店動機 | `visit_motive_id` | |
| `bikou` | 備考 | `note` | |
| `form5` = 有 / 無 | 配偶者 | `spouse_flg` 1 / 0 | |
| `form6yyy/mmm/ddd` | 結婚記念日 | `wedding_date` | |
| `form7`, `form8`, … | Información adicional | `custom_data` (jsonb) | `formN` es el id del campo personalizado |
| `login_pass` | パスワード | `password` | Obligatoria en el legacy; aquí se guarda cifrada |
| `touroku_kubun` = 2 | (oculto) | `status` | 2 = registrado en el legacy |

### Persona y empresa

En el legacy las dos secciones son excluyentes. Al elegir 個人 se ocultan los datos de empresa, y al elegir 法人 se ocultan los datos personales y el lugar de trabajo. Así se hace aquí:

- El formulario solo envía la sección del tipo elegido.
- La validación descarta lo que no corresponde al tipo.
- `customer_companies` es una fila por cliente: guarda el lugar de trabajo de una persona o los datos de una empresa.

---

## Lo que se añadió además de esos 57 campos

Todo lo de abajo son columnas que **no** están en la lista de Campos y CSV. Cada una
indica de dónde sale.

| Columna | De dónde sale |
|---|---|
| `uid` | Estándar §5 |
| `deleted_at` | Estándar §3 |
| `address_type` | Mockup Nuevo cliente, sección 3 «Tipo de dirección» |
| `reservation_reminder_flg` | Mockup Nuevo cliente, «Recordatorio de reservas por email» |
| `note` | Mockup Nuevo cliente, «Notas» |
| `spouse_flg`, `wedding_date` | Mockup Nuevo cliente, sección 4 «Información familiar» |
| `referrer_id` | Mockup Nuevo cliente, «Referido por → Buscar socio» |
| `visit_count`, `last_visit_date` | Mockup Buscar, filtros «Nº de visitas» y «Días desde la última visita» |
| `average_visit_cycle`, `next_visit_date` | Mockup Buscar, «Ciclo medio» y «Próxima visita prevista» |
| `total_amount`, `average_amount` | Mockup Buscar, bloque Ventas → «Ticket promedio» |
| `custom_data` | Mecanismo de campos personalizados (ver abajo) |

Las seis columnas de estadísticas son valores calculados a partir de `visits`. Se
guardan en la fila del cliente porque la pantalla de búsqueda filtra por ellas, y
recalcularlas en cada consulta sería caro.

---

## Decisiones de diseño tomadas

Tres cosas donde había más de una opción razonable:

**1. Un solo `ranks` con `type`, en lugar de dos tablas.** Los rangos por importe y por
visitas tienen la misma forma (nombre, mínimo, máximo, orden). Se unificaron con
`type` `smallint`, que es el mismo patrón que ya usan `taxes` y `coupons` en el estándar.

**2. El rango anterior es un campo, no un historial.** La pantalla Campos y CSV lista
`arp` y `vrp` como campos del cliente, y la ficha los muestra como «Actual — · Anterior —».
Con dos columnas basta; no hace falta tabla de auditoría.

**3. Campos personalizados en dos sitios según el tipo.** Las categorías de tipo normal
(un registro por cliente) guardan su valor en `customers.custom_data`; las de tipo
múltiple (varias mascotas, por ejemplo) usan la tabla `custom_values`. Ambas columnas
son `jsonb` y no `json`, porque la pantalla Buscar filtra por campos personalizados y
PostgreSQL solo admite índices GIN sobre `jsonb`.

**4. Dirección: las cuatro columnas del estándar más `building`.** El legacy separa 市区町村, 丁目・番地 y マンション・ビル名, cada uno con su lectura en kana. Se decidió:
- `city` y `street_address` como pide el estándar.
- `building` aparte, porque el CSV para correo postal lo imprime en su propia línea.
- Las lecturas en kana de la dirección se descartan: no se buscan ni se ordenan por ellas.

**5. Fax en `tel3`.** El estándar numera los teléfonos (`tel1`–`tel3`) y el legacy tiene exactamente tres por persona: teléfono, móvil y fax. En `customer_companies` se usa `tel1` y `tel3` con el mismo significado, para que el fax sea siempre `tel3`.

**6. Sexo con ISO 5218 completo.** El legacy permite dejarlo vacío, que se guarda como `0`. Una empresa recibe `9` (no aplica). La columna ya no admite `null`.

**7. Empresa en `customer_companies` (1:1).** En el legacy cada cliente empresa es su propia empresa: no hay empresas compartidas entre clientes. Por eso no se creó una tabla `companies` con N:1, sino una fila por cliente. Guarda el lugar de trabajo de una persona o los datos de una empresa, porque las dos secciones son excluyentes.

**8. Normalización al guardar, como el legacy.**
- Teléfonos y código postal solo con dígitos.
- Lecturas en katakana de ancho completo. Donde el legacy da error, aquí se convierte solo: el hiragana y el katakana de medio ancho pasan a katakana de ancho completo.
- Se rechazan las fechas imposibles, que el legacy aceptaba.

---

## Pendientes de confirmar

**1. ¿La configuración es por tienda o global?**
Se asumió **por tienda**: `customer_groups`, `ranks`, `rank_schedules`, `visit_motives`,
`field_settings` y `custom_categories` llevan todas `shop_id`. El legacy documentado
tiene una sola tienda (`shop`), pero la pantalla de búsqueda muestra cuatro
(テスト2, Polos, Pantalones, shop). Si en realidad los grupos y rangos son comunes a
todas las tiendas, hay que quitar ese `shop_id` de las seis tablas.

**2. Falta la tabla `employees` en el estándar.**
«Personal asignado» aparece en 3 pantallas y el estándar menciona a los empleados como
ejemplo de datos de persona, pero no define la tabla. Es compartida con el POS y
reservas, así que debería definirse en el estándar. Mientras tanto, `customers` y
`visits` no llevan `employee_id`; cuando se defina, una sola migración añade las dos
columnas.

**3. ¿Qué es exactamente `treg` «Registro de terminal»?**
Es un campo aparte de `tid` «ID de terminal». Se creó como `terminal_registered_at`
(timestamp), suponiendo que guarda cuándo el cliente registró su terminal. Conviene
verificarlo contra el legacy.

**4. ~~¿14 o 15 tipos de campo personalizado?~~ Resuelto: son 15.** El selector del legacy (`search_form_new.php`, campo `form_type`) tiene テキスト, メールアドレス, 半角英数テキスト, 数値テキスト, テキストエリア, チェックボックス, セレクト, ラジオボタン, 年, 年月, 年月日, 月日, 起算日, テーブル y メニュー項目. Los tamaños (`style_class`) son los 10 de `FieldSize`.

---

## Estado actual

Tras estas decisiones hay que recrear la base: `php artisan migrate:fresh --seed`.

Las 13 tablas (más `customer_companies`) están creadas como migraciones de Laravel en
`public_html/database/migrations/` y verificadas contra el estándar: 0 columnas
`boolean`, 0 `varchar`, claves primarias `bigserial` coherentes con las FK `bigint`.
El rollback y la re-migración corren limpios.

La tabla `admins` existente **no cumple el estándar** (usa `name` en vez de
`last_name`/`first_name`, `varchar`, y no tiene `status`, `softDeletes()` ni `uid`).
Está vacía, así que corregirla ahora no cuesta nada.
