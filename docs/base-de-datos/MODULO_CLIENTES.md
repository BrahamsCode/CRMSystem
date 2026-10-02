# Base de datos · Módulo 1: Gestión de clientes

Tablas que necesitan las pantallas del módulo (`docs/mockups/modulo-clientes/`), escritas con los estándares de la empresa.

## Estándares aplicados

| Regla | Columnas |
|---|---|
| Personas | `last_name`, `first_name`, `last_name_kana`, `first_name_kana`, `birth_date` (date), `sex` smallint (ISO 5218: 1 masculino, 2 femenino) |
| Cosas | `name`, `name_kana` |
| Dirección | `zip`, `pref`, `city`, `street_address` |
| Contacto | `tel` / `tel1`, `tel2`, `tel3` · `mail` / `mail1`, `mail2`, `mail3` |
| Comunes (todas las tablas) | `status` smallint default 1 (0 inactivo, 1 activo), `created_at`, `updated_at`, `deleted_at` → `$table->timestamps()` y `$table->softDeletes()` |
| Identificador público | `uid` generado con `getUid()` (solo en las tablas que lo indican) |

Para no repetir, las columnas comunes (`status`, `created_at`, `updated_at`, `deleted_at`) no se listan en cada tabla, pero **todas las llevan**. Las columnas marcadas **(ext.)** no están en el estándar; vienen del legacy y quedan pendientes de confirmar (ver «Decisiones pendientes»).

---

## 1. Tablas del estándar: cuáles usa este módulo

| Tabla estándar | ¿La usa clientes? | Para qué |
|---|---|---|
| `shops` | **Sí** | Tienda de registro del cliente, filtro «Tienda» en Buscar, tienda de cada visita, categorías de información adicional por tienda |
| `terminals` | **Sí (indirecto)** | Terminal (caja/tablet) desde donde se registra la visita. No es el «Registro de terminal» del cliente, que es su móvil (ver `customer_devices`) |
| `coupons` | **Sí** | Pestaña «Mis cupones» de la ficha, mediante `customer_coupon` |
| `taxes` | No | Ventas / POS |
| `products` | No | Ventas / POS |
| `categories` | No | Categorías de productos (POS). Las categorías de información adicional van en otra tabla (`form_categories`) para no mezclarlas |
| `category_product` | No | POS |
| `product_shop` | No | POS |
| `category_shop` | No | POS |

`taxes`, `products`, `categories` y sus tablas intermedias no hacen falta para el módulo de clientes. Se usarán cuando exista el módulo de ventas, que además alimentará el **rango por importe de compra** (suma de ventas por cliente).

---

## 2. Tablas que faltan (no están en el estándar)

| Tabla | Pantalla de origen |
|---|---|
| `customers` | Nuevo cliente, Buscar, Ficha, Estadísticas |
| `customer_companies` | Nuevo cliente / Campos: lugar de trabajo y datos de empresa |
| `customer_groups` | Grupos de clientes |
| `first_visit_motives` | Motivos de primera visita |
| `occupations` | Desplegable «Ocupación» |
| `industries` | Desplegable «Rubro» (también agrupa Estadísticas «Por rubro») |
| `customer_visits` | Registrar visita, Historial de visitas |
| `customer_ranks` | Rangos de clientes |
| `customer_rank_schedules` | Asignación automática de rangos |
| `customer_field_settings` | Campos de registro, búsqueda y CSV |
| `form_categories` | Información adicional (categorías: Mascota, カルテ, 家族情報…) |
| `form_fields` | Campos de cada categoría |
| `form_field_options` | Opciones de selección / botones / casillas |
| `customer_form_records` | Registros de información adicional por cliente |
| `customer_form_values` | Valor de cada campo |
| `customer_devices` | «Registro de terminal», «ID de terminal», conteo DoCoMo / au / SoftBank / PC |
| `customer_coupon` | Pestaña «Mis cupones» |
| `settings` | Ajustes sueltos (rango visible en Mi página, etc.) |

Fuera de este módulo, pero las pide la ficha del cliente: **personal** (`staffs`, para «Personal asignado»), **puntos**, **sellos**, **reservas**, **Mi álbum** y **mensajes / newsletter**. Se definen en sus propios módulos; aquí solo se deja la clave foránea cuando hace falta.

---

## 3. Definición de tablas

### customers · Clientes

| Columna | Tipo | Nulo | Descripción | Legacy / pantalla |
|---|---|---|---|---|
| `id` | bigserial | | | |
| `uid` | varchar | | `getUid()`. Identificador público (Mi página, QR) | |
| `shop_id` | bigint | | FK `shops`. Tienda de registro | Tienda de registro |
| `member_no` | varchar | | Nº de socio, único (ej. 1100029) | Nº de cliente |
| `management_no` | varchar | sí | Nº de gestión | Nº de gestión |
| `type` | smallint | | 1 persona, 2 empresa. Default 1 | Persona / empresa |
| `last_name` | varchar | | Apellido | Nombre |
| `first_name` | varchar | sí | Nombre | Nombre |
| `last_name_kana` | varchar | sí | Apellido (fonético) | Nombre (fonético) |
| `first_name_kana` | varchar | sí | Nombre (fonético) | Nombre (fonético) |
| `birth_date` | date | sí | Fecha de nacimiento. La edad se calcula, no se guarda | Fecha de nacimiento, Edad |
| `sex` | smallint | sí | 1 masculino, 2 femenino. Null = sin especificar | Sexo |
| `blood_type` | smallint | sí | 1 A, 2 B, 3 O, 4 AB | Grupo sanguíneo |
| `occupation_id` | bigint | sí | FK `occupations` | Ocupación |
| `zip` | varchar | sí | Código postal | Código postal |
| `pref` | varchar | sí | Prefectura / región | Prefectura |
| `city` | varchar | sí | Ciudad / distrito | Ciudad |
| `city_kana` | varchar | sí | **(ext.)** Ciudad (fonético) | Ciudad (fonético) |
| `street_address` | varchar | sí | Calle y número | Calle y número |
| `building` | varchar | sí | **(ext.)** Edificio / depto. | Edificio |
| `building_kana` | varchar | sí | **(ext.)** Edificio (fonético) | Edificio (fonético) |
| `tel1` | varchar | sí | Teléfono | Teléfono |
| `tel2` | varchar | sí | Teléfono móvil | Teléfono móvil |
| `tel3` | varchar | sí | Otro teléfono (libre) | — |
| `fax` | varchar | sí | **(ext.)** Fax | Fax |
| `mail1` | varchar | sí | Email 1 (el que recibe la newsletter) | Email 1 |
| `mail2` | varchar | sí | Email 2 | Email 2 |
| `mail3` | varchar | sí | Email personal | Email personal |
| `mail_error_count` | int | | Correos no entregados. Default 0 | Correos no entregados |
| `newsletter` | smallint | | 1 enviar, 2 no enviar, 3 no entregable. Default 1 | Newsletter |
| `address_type` | smallint | sí | Tipo de dirección (valores por confirmar) | Tipo de dirección |
| `reservation_reminder` | smallint | | 0 no, 1 sí. Default 0 | Recordatorio de reservas |
| `customer_group_id` | bigint | sí | FK `customer_groups` | Grupo de cliente |
| `first_visit_motive_id` | bigint | sí | FK `first_visit_motives` | Motivo de primera visita |
| `staff_id` | bigint | sí | FK `staffs` (módulo de personal) | Personal asignado |
| `referrer_id` | bigint | sí | FK `customers`. Quién lo refirió; la pestaña «Referidos» son los clientes con `referrer_id` = este cliente | Referido por, Referidos |
| `amount_rank_id` | bigint | sí | FK `customer_ranks`. Rango por importe actual | Rango por importe (actual) |
| `prev_amount_rank_id` | bigint | sí | FK `customer_ranks`. Rango por importe anterior | Rango por importe (anterior) |
| `visit_rank_id` | bigint | sí | FK `customer_ranks`. Rango por visitas actual | Rango por visitas (actual) |
| `prev_visit_rank_id` | bigint | sí | FK `customer_ranks`. Rango por visitas anterior | Rango por visitas (anterior) |
| `login_id` | varchar | sí | ID de acceso a Mi página, único | ID de acceso |
| `password` | varchar | sí | Hash (`Hash::make`) | Contraseña |
| `note` | text | sí | Notas | Notas |
| `registration_status` | smallint | | Estado de registro del legacy (登録区分). Valores por confirmar | Estado de registro |

- Fecha de alta = `created_at`; fecha de modificación = `updated_at`.
- `status` sigue el estándar (0 inactivo, 1 activo); la baja del cliente es `deleted_at`.
- Índices: `uid` único, `member_no` único, `login_id` único; índices en `shop_id`, `last_name_kana`, `tel1`, `tel2`, `mail1`, `birth_date`.

### customer_companies · Lugar de trabajo / datos de empresa (1:1 con customers)

| Columna | Tipo | Nulo | Descripción | Legacy / pantalla |
|---|---|---|---|---|
| `id` | bigserial | | | |
| `customer_id` | bigint | | FK `customers`, único | |
| `name` | varchar | sí | Lugar de trabajo / razón social | Lugar de trabajo |
| `name_kana` | varchar | sí | Fonético | Lugar de trabajo (fonético) |
| `industry_id` | bigint | sí | FK `industries` | Rubro |
| `tel` | varchar | sí | | Teléfono del trabajo |
| `fax` | varchar | sí | **(ext.)** | Fax del trabajo |
| `department` | varchar | sí | | Departamento |
| `founded_on` | date | sí | | Fecha de fundación |
| `capital` | bigint | sí | | Capital social |
| `representative_last_name` | varchar | sí | | Representante: nombre |
| `representative_first_name` | varchar | sí | | Representante: nombre |
| `representative_last_name_kana` | varchar | sí | | Representante (fonético) |
| `representative_first_name_kana` | varchar | sí | | Representante (fonético) |
| `representative_birth_date` | date | sí | | Representante: fecha de nacimiento |
| `contact_last_name` | varchar | sí | | Contacto: nombre |
| `contact_first_name` | varchar | sí | | Contacto: nombre |
| `contact_last_name_kana` | varchar | sí | | Contacto (fonético) |
| `contact_first_name_kana` | varchar | sí | | Contacto (fonético) |
| `contact_tel` | varchar | sí | | Contacto: teléfono |
| `contact_mail` | varchar | sí | | Contacto: email |

Separada de `customers` porque solo se usa para empresas o cuando se rellena el lugar de trabajo.

### customer_groups · Grupos de clientes

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `name` | varchar | | Nombre del grupo |
| `default_flg` | smallint | | 1 = valor inicial para clientes nuevos. Default 0 |
| `sort` | int | | Orden |

### first_visit_motives · Motivos de primera visita

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `name` | varchar | | Nombre del motivo |
| `sort` | int | | Orden |

### occupations · Ocupaciones  /  industries · Rubros

Misma estructura para las dos:

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `name` | varchar | | |
| `name_kana` | varchar | sí | |
| `sort` | int | | |

### customer_visits · Visitas

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `uid` | varchar | | `getUid()`. Permite sincronizar visitas hechas desde un terminal |
| `customer_id` | bigint | | FK `customers` |
| `shop_id` | bigint | | FK `shops` |
| `terminal_id` | bigint | sí | FK `terminals`. Null si se registró desde el panel |
| `visited_at` | timestamp | | Fecha y hora de la visita |

El rango por visitas y el contador «N visitas» salen de esta tabla.

### customer_ranks · Rangos

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `type` | smallint | | 1 por importe de compra, 2 por número de visitas |
| `name` | varchar | | Nombre del rango |
| `min_value` | int | | Importe mínimo o visitas mínimas para el rango (por confirmar con la captura de «Añadir rango») |
| `colors` | json | sí | Mismo formato que `products.colors`: `{"back":"#ff0000","front":"#ffffff"}` |
| `sort` | int | | Prioridad (orden por arrastre) |

### customer_rank_schedules · Asignación automática de rangos

Una fila por tipo de rango (importe / visitas).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `rank_type` | smallint | | 1 importe, 2 visitas (igual que `customer_ranks.type`), único |
| `period_days` | int | | Periodo a evaluar, en días |
| `mode` | smallint | | 1 cada semana, 2 meses y días concretos |
| `weekdays` | json | sí | Modo 1. `[0,1,…,6]` (0 domingo) |
| `months` | json | sí | Modo 2. `[1,…,12]` |
| `days` | json | sí | Modo 2. `[1,…,31]`, `"end"` = fin de mes |
| `run_time` | time | | Hora de ejecución |

`status` (estándar) es el interruptor ACTIVADA / DESACTIVADA.

### customer_field_settings · Campos de registro, búsqueda y CSV

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `field_key` | varchar | | Columna de `customers` / `customer_companies` (ej. `birth_date`, `mail1`), único |
| `register_flg` | smallint | | Visible en el formulario móvil |
| `required_flg` | smallint | | Obligatorio en el formulario móvil |
| `search_flg` | smallint | | Filtro en Buscar clientes |
| `csv_flg` | smallint | | Columna del CSV |
| `sort` | int | | |

Los campos de información adicional guardan estos ajustes en `form_fields`.

### form_categories · Categorías de información adicional

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `shop_id` | bigint | sí | FK `shops`. Null = todas las tiendas |
| `name` | varchar | | Nombre de la categoría (Mascota, カルテ…) |
| `comment` | text | sí | Comentario |
| `columns` | smallint | | Columnas al mostrar: 1, 2 o 3. Default 1 |
| `type` | smallint | | 1 normal (un registro por cliente), 2 múltiple (varios registros) |
| `search_flg` | smallint | | Disponible en búsqueda |
| `display_flg` | smallint | | Se muestra en la ficha |
| `sort` | int | | |

### form_fields · Campos de cada categoría

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `form_category_id` | bigint | | FK `form_categories` |
| `name` | varchar | | Título del campo |
| `unit` | varchar | sí | Unidad (Kg, cm) |
| `comment` | text | sí | Texto de ayuda |
| `type` | smallint | | 1 texto, 2 email, 3 alfanumérico, 4 número, 5 área de texto, 6 casillas, 7 selección, 8 botones de opción, 9 año, 10 año-mes, 11 fecha, 12 mes-día, 13 fecha de referencia, 14 tabla, 15 menú |
| `size` | smallint | sí | Tamaño del campo (estilo predefinido 1–10) |
| `required_flg` | smallint | | Obligatorio |
| `search_flg` | smallint | | Filtro de búsqueda |
| `display` | smallint | | 0 oculto, 1 solo admin, 2 admin y móvil |
| `newsletter_tag_flg` | smallint | | Se puede usar como reemplazo en la newsletter (メルマガ置き換え) |
| `sort` | int | | |

### form_field_options · Opciones

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `form_field_id` | bigint | | FK `form_fields` |
| `name` | varchar | | Texto que se muestra (Perro, Gato…) |
| `value` | varchar | | Valor que se guarda |
| `sort` | int | | |

### customer_form_records · Registros de información adicional

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `customer_id` | bigint | | FK `customers` |
| `form_category_id` | bigint | | FK `form_categories`. Único con `customer_id` si la categoría es de tipo normal |
| `sort` | int | | Orden cuando hay varios (tipo múltiple: varias mascotas) |

### customer_form_values · Valores

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `customer_form_record_id` | bigint | | FK `customer_form_records` |
| `form_field_id` | bigint | | FK `form_fields`. Único con `customer_form_record_id` |
| `value` | text | sí | Valor. Casillas como JSON (`["1","3"]`), fechas en ISO (`2026-05-15`) |

### customer_devices · Registro de terminal del cliente

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `customer_id` | bigint | | FK `customers` |
| `carrier` | smallint | | 1 DoCoMo, 2 au, 3 SoftBank, 4 PC, 9 otro / sin identificar |
| `device_uid` | varchar | | ID de terminal |
| `registered_at` | timestamp | | |

### customer_coupon · Cupones del cliente (intermedia)

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `customer_id` | bigint | | FK `customers` |
| `coupon_id` | bigint | | FK `coupons` (estándar) |
| `expires_at` | timestamp | sí | Vencimiento para este cliente |
| `used_at` | timestamp | sí | Fecha de uso |

Se nombra en singular y en orden alfabético, como `category_product`.

### settings · Ajustes

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | bigserial | | |
| `key` | varchar | | Único. Ej. `customer.mypage_rank_display` |
| `value` | json | sí | Ej. `"both"`, `"amount"`, `"visits"`, `"none"` |

---

## 4. Ejemplo de migración (customers)

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('uid')->unique();
    $table->foreignId('shop_id')->constrained();
    $table->string('member_no')->unique();
    $table->string('management_no')->nullable();
    $table->smallInteger('type')->default(1);           // 1 persona, 2 empresa
    $table->string('last_name');
    $table->string('first_name')->nullable();
    $table->string('last_name_kana')->nullable();
    $table->string('first_name_kana')->nullable();
    $table->date('birth_date')->nullable();
    $table->smallInteger('sex')->nullable();            // 1 masculino, 2 femenino
    // … resto de columnas según la tabla de arriba
    $table->smallInteger('status')->default(1);         // 0 inactivo, 1 activo
    $table->timestamps();
    $table->softDeletes();
});
```

En el modelo, `uid` se rellena al crear:

```php
protected static function booted(): void
{
    static::creating(fn (self $m) => $m->uid ??= self::getUid());
}
```

---

## 5. Decisiones pendientes

1. **Dirección:** el estándar tiene `zip`, `pref`, `city`, `street_address`. El legacy añade ciudad (fonético), edificio y edificio (fonético). ¿Se añaden como `city_kana`, `building` y `building_kana` o el edificio va dentro de `street_address`?
2. **Fax:** ¿columna `fax` o se usa `tel3`?
3. **Sexo sin especificar:** ¿`null` o `0` (ISO 5218 «no conocido»)?
4. **Cliente empresa:** ¿la razón social va en `last_name` o en `customer_companies.name`?
5. **Estado de registro** y **Tipo de dirección:** faltan los valores del legacy.
6. **Rangos:** confirmar las condiciones del formulario «Añadir rango» (solo mínimo o mínimo y máximo).
