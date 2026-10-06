# Estándares de base de datos

Convenciones de nombres y tipos para todas las tablas de los sistemas de la empresa.
El objetivo es que un mismo dato se llame igual en todos los proyectos, de modo que
las integraciones, migraciones y consultas entre sistemas no requieran traducción.

**Motor:** PostgreSQL · **Framework:** Laravel

---

## 1. Reglas generales

| Regla | Convención |
|---|---|
| Nombre de tabla | Plural, `snake_case`, en inglés — `shops`, `products`, `customers` |
| Nombre de columna | Singular, `snake_case`, en inglés — `last_name`, `street_address` |
| Clave primaria | `id` |
| Clave foránea | `{tabla_en_singular}_id` — `shop_id`, `product_id`, `tax_id` |
| Tabla intermedia | Los dos nombres en singular, **en orden alfabético** — `category_product`, `product_shop`, `category_shop` |
| Texto | Siempre `text`, nunca `varchar(n)` |
| Booleanos | **No se usan.** Ver §4 |

En PostgreSQL `text` y `varchar(n)` tienen idéntico rendimiento, así que se usa
`text` siempre y la longitud se valida en la aplicación. Así un cambio de longitud
no obliga a migrar la tabla.

---

## 2. Campos comunes

Estos nombres son obligatorios. Si una tabla guarda uno de estos datos, debe usar
exactamente el nombre indicado.

### 2.1 Personas

Aplica a clientes, administradores, empleados, etc.

| Campo | Tipo | Descripción |
|---|---|---|
| `last_name` | `text` | Apellidos |
| `first_name` | `text` | Nombres |
| `last_name_kana` | `text` | Apellidos en kana (katakana) |
| `first_name_kana` | `text` | Nombres en kana (katakana) |
| `birth_date` | `date` | Fecha de nacimiento |
| `sex` | `smallint` | Sexo — ISO 5218 (`1`: masculino, `2`: femenino) |

El nombre va **siempre separado** en apellido y nombre; no existe un campo `name`
único para personas. Los campos `_kana` existen porque en japonés el orden
alfabético y la búsqueda fonética se hacen sobre la lectura, no sobre los kanji.

> ISO 5218 define además `0` (no conocido) y `9` (no aplicable). El estándar interno
> documenta solo `1` y `2`; si un sistema necesita los otros dos valores, debe
> seguir la norma y no inventar códigos nuevos.

### 2.2 Cosas

Aplica a productos, tiendas, categorías, etc.

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | `text` | Nombre |
| `name_kana` | `text` | Nombre en kana (katakana) |

### 2.3 Direcciones

| Campo | Tipo | Descripción |
|---|---|---|
| `zip` | `text` | Código postal |
| `pref` | `text` | Prefectura (departamento, estado) |
| `city` | `text` | Ciudad |
| `street_address` | `text` | Calle y número |

El código postal va en `text`, no numérico: admite guiones y ceros a la izquierda.

### 2.4 Contacto

| Campo | Tipo | Descripción |
|---|---|---|
| `tel` | `text` | Teléfono |
| `tel1`, `tel2`, `tel3` | `text` | Teléfonos, cuando se necesiten varios |
| `mail` | `text` | Email |
| `mail1`, `mail2`, `mail3` | `text` | Emails, cuando se necesiten varios |

Cuando solo hay un valor se usa `tel` / `mail` sin número. Cuando hay varios se
numeran desde `1` y **no** se mezcla con la versión sin número.

---

## 3. Estado y marcas de tiempo

Todas las tablas implementan `timestamps()` y `softDeletes()` de Laravel.

| Campo | Tipo | Descripción |
|---|---|---|
| `status` | `smallint` | `default 1` — `0`: inactivo, `1`: activo |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |
| `deleted_at` | `timestamp` | Borrado lógico |

`status` y `deleted_at` son cosas distintas: `status = 0` es un registro desactivado
que sigue siendo visible y reactivable; `deleted_at` es un registro eliminado por el
usuario que ya no aparece en las consultas normales.

```php
Schema::create('ejemplo', function (Blueprint $table) {
    $table->id();
    // ... campos propios
    $table->smallInteger('status')->default(1);
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 4. No se usan booleanos

Los campos de dos o más estados se guardan como `smallint` con los valores
documentados en el comentario de la columna. Nunca `boolean`.

Ejemplos del estándar actual:

| Campo | Tabla | Valores |
|---|---|---|
| `status` | todas | `0`: inactivo, `1`: activo |
| `sex` | personas | `1`: masculino, `2`: femenino |
| `type` | `taxes` | `1`: impuesto no incluido, `2`: impuesto incluido |
| `type` | `coupons` | `1`: porcentaje, `2`: valor fijo |
| `online_flg` | `shops` | bandera de disponibilidad en línea |

**Por qué:** un `smallint` admite un tercer estado el día que el negocio lo pide
(`2`: suspendido, `3`: en revisión) sin migrar la columna ni tocar los datos
existentes. Un `boolean` obliga a una migración y a reescribir cada consulta.

**Convenciones de uso:**

- Los campos que son banderas verdaderas llevan el sufijo `_flg`.
- Los campos que clasifican en categorías se llaman `type`.
- `1` es siempre el valor por defecto y el caso afirmativo o más común.
- Todo campo `smallint` con códigos **debe** llevar su significado en los comentarios
  de la migración y, preferiblemente, en un enum de PHP respaldado por `int`.

```php
enum TaxType: int
{
    case NotIncluded = 1;   // 税抜 — impuesto no incluido
    case Included = 2;      // 税込 — impuesto incluido
}
```

---

## 5. Campo `uid`

Algunas tablas llevan un `uid` (`text`) para identificar el registro en las vistas de
los usuarios finales, en lugar de exponer el `id` autoincremental. Evita que se pueda
deducir cuántos registros hay o acceder a otros cambiando un número en la URL.

Se genera a partir de la hora unix más un número aleatorio de 9 dígitos, convertido a
base 36. El resultado es corto, legible y ordenable.

```php
public static function getUid() {
    $n = time() . random_int(100000000, 999999999);
    return base_convert($n, 10, 36);
}
```

Se define como método estático en una clase helper para poder llamarlo desde
cualquier modelo.

**Ejemplo:**

```
t: 1744808070 ==> 2025-04-16 12:54:30
r: 673402999
n: 1744808070673402999
result: d982ua39k587
```

### Comportamiento verificado

Ambas propiedades se comprobaron ejecutando la función, no por deducción:

- **No pierde precisión.** `n` tiene 19 dígitos (~1,7 × 10¹⁸), por debajo de
  `PHP_INT_MAX` (9,22 × 10¹⁸). La conversión de ida y vuelta devuelve el número
  original exacto. El límite se alcanza en el año 2262.
- **El orden funciona.** El uid mide 12 caracteres de forma constante entre 2001 y
  2120, y el orden lexicográfico de cadenas de igual longitud en base 36 coincide con
  el orden numérico. A partir de 2120 pasa a 13 caracteres y el orden alfabético
  dejaría de coincidir con el cronológico.

### Al implementarlo

- Poner **índice único** sobre `uid`. La probabilidad de colisión es baja (9 × 10⁸
  valores por segundo) pero no es cero, y una carga masiva genera muchos registros
  en el mismo segundo.
- El uid revela el momento de creación del registro. No usarlo donde eso sea
  información sensible.
- No es un secreto ni sustituye a un control de permisos: que la URL sea difícil de
  adivinar no autoriza a quien la tenga.

---

## 6. Tipos de datos

| Dato | Tipo | Nota |
|---|---|---|
| Texto | `text` | Nunca `varchar(n)` |
| Clave primaria | `serial` / `bigserial` | Ver aviso abajo |
| Clave foránea | `bigint` | |
| Dinero | `integer` | En la unidad mínima de la moneda, sin decimales |
| Porcentaje / tasa | `numeric(5,2)` | Ej. `rate` en `taxes` |
| Coordenadas | `numeric(9,6)` | `latitude`, `longitude` |
| Códigos de estado | `smallint` | Ver §4 |
| Orden de visualización | `integer` | Campo `sort` |
| Estructuras flexibles, solo lectura | `json` | Ej. `colors` |
| Estructuras flexibles, con búsqueda | `jsonb` | Obligatorio si se va a indexar — ver abajo |
| Fecha | `date` | |
| Fecha y hora | `timestamp` | |

> **Aviso de consistencia.** El estándar escrito declara las claves primarias como
> `serial` (entero de 4 bytes) y las foráneas como `bigint` (8 bytes). Son tipos
> distintos y no deberían mezclarse: una FK `bigint` apuntando a una PK `serial`
> desperdicia espacio en el índice y falla si la tabla supera los 2.147 millones de
> filas. En Laravel, `$table->id()` genera `bigserial`, que es lo que corresponde a
> las FK `bigint` ya definidas. **Recomendación: usar `bigserial` en todas las PK.**
> Conviene confirmarlo con quien mantiene el estándar.

### `json` frente a `jsonb`

El estándar escrito solo menciona `json`, que es correcto para un campo como
`products.colors`: se guarda y se muestra, nunca se consulta por su contenido.

**Pero PostgreSQL no admite índices GIN sobre `json`**, solo sobre `jsonb`:

```
ERROR:  data type json has no default operator class for access method "gin"
```

Por tanto, cualquier columna json **cuyo contenido se vaya a buscar o filtrar debe
ser `jsonb`**. En Laravel, `$table->json()` frente a `$table->jsonb()`.

### Dinero como entero

`price`, `sub_price` y `coupons.value` son `integer`. Funciona directo con el yen,
que no tiene decimales. **Para monedas con decimales (sol, dólar, euro) el valor debe
guardarse en la unidad mínima** — céntimos — y dividirse entre 100 al mostrarlo.
Nunca en `float`.

---

## 7. Tablas compartidas

Estas tablas forman parte del estándar y deben replicarse con la misma estructura en
todos los sistemas que las necesiten.

### `shops`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `name` | `text` | |
| `name_kana` | `text` | |
| `zip` | `text` | Código postal |
| `pref` | `text` | Prefectura (departamento, estado) |
| `city` | `text` | Ciudad |
| `street_address` | `text` | Calle y número |
| `latitude` | `numeric(9,6)` | |
| `longitude` | `numeric(9,6)` | |
| `tel` | `text` | Teléfono |
| `mail` | `text` | Email |
| `invoice_registration_number` | `text` | R.U.C. |
| `online_flg` | `smallint` | |
| `uid` | `text` | |

### `terminals`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `shop_id` | `bigint` | Tienda a la que pertenece (`shops.id`) |
| `name` | `text` | |
| `uid` | `text` | |

### `taxes`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `name` | `text` | |
| `type` | `smallint` | `1`: impuesto no incluido, `2`: impuesto incluido — default `1` |
| `rate` | `numeric(5,2)` | |

### `products`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `name` | `text` | |
| `name_kana` | `text` | |
| `price` | `integer` | Precio unitario |
| `sub_price` | `integer` | Precio unitario cuando es hijo dentro de un grupo (combo) |
| `tax_id` | `bigint` | Impuesto (`taxes.id`) |
| `code` | `text` | Código PLU, SKU, UPC (para mostrar como código de barras) |
| `colors` | `json` | Colores del botón en POS, tienda en línea… `{"back": "#ff0000", "front": "#ffffff"}` |
| `sort` | `integer` | Orden de visualización |
| `uid` | `text` | |

### `categories`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `p_id` | `bigint` | Categoría padre (`categories.id`) |
| `name` | `text` | |
| `name_kana` | `text` | |
| `colors` | `json` | Colores del botón |
| `sort` | `integer` | Orden de visualización |
| `uid` | `text` | |

La jerarquía se arma con `p_id` apuntando a la misma tabla.

### `coupons`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `serial` | |
| `name` | `text` | |
| `type` | `smallint` | `1`: porcentaje, `2`: valor fijo — not null |
| `value` | `integer` | not null, mayor que 0 |

```
type = 1, value = 10  =>  10 % de descuento
type = 2, value = 150 =>  150 yenes (soles, etc.) de descuento
```

### Tablas intermedias

| Tabla | Relaciona |
|---|---|
| `category_product` | `category_id`, `product_id` |
| `product_shop` | `shop_id`, `product_id` |
| `category_shop` | `shop_id`, `category_id` |

Todas llevan `id` propio (`serial`) además de las dos claves foráneas.

---

## 8. Lista de verificación para una tabla nueva

Antes de dar por cerrada una migración:

- [ ] Nombre de tabla en plural, inglés, `snake_case`
- [ ] Los datos de persona usan `last_name` / `first_name` / `*_kana` / `birth_date` / `sex`
- [ ] Los datos de dirección usan `zip` / `pref` / `city` / `street_address`
- [ ] Los datos de contacto usan `tel` / `mail` (o numerados)
- [ ] Todo el texto es `text`, no `varchar(n)`
- [ ] Ningún campo es `boolean` — se usa `smallint` con códigos documentados
- [ ] Cada `smallint` con códigos tiene su significado en un comentario y un enum de PHP
- [ ] Lleva `status` con default `1`
- [ ] Lleva `timestamps()` y `softDeletes()`
- [ ] Si es visible para el usuario final, lleva `uid` con índice único
- [ ] Las claves foráneas se llaman `{tabla_singular}_id` y son `bigint`
- [ ] Las tablas intermedias nombran las dos tablas en singular y orden alfabético
- [ ] El dinero es `integer` en la unidad mínima de la moneda

---

## Puntos a confirmar con quien mantiene el estándar

1. **`serial` frente a `bigserial` en las claves primarias.** Las FK ya son `bigint`;
   las PK deberían ser `bigserial` para que coincidan (§6).
2. **Tabla de empleados o personal.** §2.1 menciona a los empleados como ejemplo de
   datos de persona, pero no hay una tabla `employees` / `staff` definida. Varios
   sistemas la necesitan (en el CRM, el campo «personal asignado» del cliente).
3. **Valores `0` y `9` de `sex`.** Si algún sistema admite «no conocido» o «no
   aplicable», conviene fijarlos ahora según ISO 5218.
4. **Monedas con decimales.** Confirmar que el criterio de guardar en la unidad
   mínima (céntimos) es el oficial para los proyectos fuera de Japón.
5. **`jsonb` para columnas consultables.** El estándar solo contempla `json`, que no
   admite índices GIN. Conviene incorporar la distinción al documento oficial (§6).
