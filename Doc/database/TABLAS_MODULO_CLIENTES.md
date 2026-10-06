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

La pantalla **Campos y CSV** del mockup lista los 57 campos estándar del legacy. Esa es
la lista autoritativa. Así quedó mapeada:

| Legacy | Columna creada | Nota |
|---|---|---|
| `num` Nº de cliente | `code` | Visible al usuario |
| `mgmt` Nº de gestión | `management_no` | |
| `store` Tienda de registro | `shop_id` | FK a `shops` |
| `ptype` Persona / empresa | `type` | `1`: persona, `2`: empresa |
| `joined` Fecha de alta | `created_at` | Estándar §3 |
| `status` Estado de registro | `status` | `1`: registrado, `0`: dado de baja |
| `news` Newsletter | `mail_magazine_flg` | 3 estados, por eso `smallint` |
| `updated` Fecha de modificación | `updated_at` | Estándar §3 |
| `group` Grupo de cliente | `customer_group_id` | |
| `pass` Contraseña | `password` | El usuario de acceso es el propio `code` |
| `name` Nombre | `last_name` + `first_name` | El estándar §2.1 lo separa |
| `namek` Nombre (fonético) | `last_name_kana` + `first_name_kana` | Ídem |
| `birth` Fecha de nacimiento | `birth_date` | |
| `age` Edad | *(ninguna)* | Se calcula desde `birth_date` |
| `sex` Sexo | `sex` | ISO 5218 |
| `blood` Grupo sanguíneo | `blood_type` | |
| `job` Ocupación | `occupation` | |
| `tel` Teléfono | `tel1` | Estándar §2.4 |
| `mobile` Teléfono móvil | `tel2` | Estándar §2.4 |
| `fax` Fax | `fax` | |
| `mail1` / `mail2` | `mail1` / `mail2` | |
| `pmail` Email personal | `mail3` | |
| `zip`, `pref`, `city`, `street` | `zip`, `pref`, `city`, `street_address` | Estándar §2.3 |
| `cityk` Ciudad (fonético) | `city_kana` | |
| `bldg` / `bldgk` | `building` / `building_kana` | |
| `wname`, `wnamek` Lugar de trabajo | `company_name`, `company_name_kana` | |
| `wind` Rubro del lugar de trabajo | `company_industry` | |
| `industry` Rubro | `industry` | **Campo distinto del anterior** |
| `wtel`, `wfax` | `company_tel`, `company_fax` | |
| `founded` Fecha de fundación | `company_founded_date` | |
| `capital` Capital social | `company_capital` | `integer`, unidad mínima |
| `dept` Departamento | `company_dept` | |
| `rep`, `repk`, `repb` Representante | `rep_last_name`/`rep_first_name` (+`_kana`), `rep_birth_date` | Nombre separado |
| `cname`, `cnamek`, `ctel`, `cmail` Contacto | `contact_*` | Ídem |
| `treg` Registro de terminal | `terminal_registered_at` | **Suposición — ver pendientes** |
| `tid` ID de terminal | `terminal_id` | |
| `bounce` Correos no entregados | `bounce_count` | |
| `stamps` Sellos | *(ninguna)* | Otro módulo |
| `points` Puntos | *(ninguna)* | Otro módulo |
| `staff` Personal asignado | *(ninguna)* | **Bloqueado — ver pendientes** |
| `motive` Motivo de primera visita | `visit_motive_id` | |
| `arn` / `arp` Rango por importe | `amount_rank_id` / `prev_amount_rank_id` | |
| `vrn` / `vrp` Rango por visitas | `visit_rank_id` / `prev_visit_rank_id` | |

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

**4. ¿14 o 15 tipos de campo personalizado?**
La documentación dice «14 tipos» pero la lista enumera 15: texto, email, alfanumérico,
numérico, textarea, checkbox, select, radio, año, año‑mes, año‑mes‑día, mes‑día, fecha
de referencia, tabla y menú. Hay que fijarlo antes de codificar el `smallint`.

---

## Estado actual

Las 13 tablas están creadas como migraciones de Laravel en
`public_html/database/migrations/` y verificadas contra el estándar: 0 columnas
`boolean`, 0 `varchar`, claves primarias `bigserial` coherentes con las FK `bigint`.
El rollback y la re-migración corren limpios.

La tabla `admins` existente **no cumple el estándar** (usa `name` en vez de
`last_name`/`first_name`, `varchar`, y no tiene `status`, `softDeletes()` ni `uid`).
Está vacía, así que corregirla ahora no cuesta nada.
