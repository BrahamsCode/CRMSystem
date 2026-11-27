# Reemplazos hechos para migrar de PHP 7 a PHP 8.5.12

> Versión japonesa: [PHP85-REPLACEMENTS.ja.md](PHP85-REPLACEMENTS.ja.md). Resumen de la migración y mapa de documentos: [MIGRATION.md](MIGRATION.md).

> **Corrección (2026-11-05): producción corría PHP 7 (7.3 o 7.4), no PHP 5.** Este catálogo se escribió comparando con PHP 5. Donde dice "como en PHP 5", léase "como en PHP 5 y en PHP 7": casi siempre los dos daban el mismo resultado (variable no definida → `null`, argumentos de más → aviso y `null`, `'' == 0` → `true`, `date(null)` → 1970). **Las excepciones**, donde PHP 7 ya fallaba y el reemplazo hace lo que hacía PHP 5, están en `PENDING.md` 1b: `mktime()` con `is_dst` (en PHP 7 devolvía `false`, no `ArgumentCountError`, que es de PHP 8), texto vacío usado como array, `ereg`/`split`, división entre 0 y mPDF; y dos casos dudosos por verificar en PHP 7.4 (fila `''` de `pg_fetch_result` y `(int)` sobre texto no numérico).
>
> **Comprobado en PHP 7.4.33 (2026-11-05)** con `tools/scripts/php74-parity.php` (31 casos, original en 7.4 frente a guarda en 8.5.12): 16 idénticos y 15 distintos. Los dos dudosos **sí difieren**: con `''` en un parámetro entero, PHP 7.4 no ejecutaba la función y devolvía `NULL`/`false` (`pg_fetch_result` vacío, `date('Y','')` vacío, `number_format('')` vacío), mientras que la guarda la ejecuta con `0`. Y la guarda de texto en sumas devuelve `float` donde PHP 7 daba `int` (mismo valor). Detalle y decisiones en `PENDING.md` 1.9 y 1b.6 a 1b.8. Para repetir la comprobación tras un cambio, ver la cabecera de `php74-parity.php`.

> Catálogo de cada cambio mecánico de la rama `refactor/fase-0`: qué se buscaba, por qué se reemplazaba y por qué el reemplazo da el mismo resultado que antes. Actualizado: 2026-11-03.
> Los ejemplos son **líneas reales del diff** (`git diff master...HEAD`), con su archivo. Cuando no hay un ejemplo de una sola línea, se muestra la forma general y se indica.
> Las cifras vienen de `CHANGELOG.md` y `REFACTORING.md`. Cada herramienta está en `tools/scripts/` y su cabecera explica cómo usarla.

## Cómo se hicieron los reemplazos (y por qué casi nunca con regex)

La mayoría **no se hizo con expresiones regulares**. Una regex lee el código como texto y no sabe si está dentro de un string, de un comentario o de otra expresión. Ya pasó en este repositorio: el reemplazo del `README` para poner comillas a las claves de los arrays,

```text
buscar:      \[([^0-9<\$'"\]].[^\]'"]*)\]
reemplazar:  ['$1']
```

se ejecutó en una shell que borró `$array`, `$_REQUEST` y `$row` del reemplazo y dejó **24 archivos rotos** (`['tb_shop_cd]']`, o `<?=['name']?>` en lugar de `<?=$array['name']?>`). Fue lo primero que se corrigió (commit `a70f54d0`).

Por eso se usaron, de más a menos:

| Técnica | Qué es | Cuándo |
|---|---|---|
| **Árbol de sintaxis (AST)** con `nikic/php-parser` | PHP se lee como lo lee el intérprete: se busca un nodo concreto (una llamada a `count`, una suma, un `date()`), se calcula su posición exacta en el archivo y **solo se insertan o cambian esos bytes**. El resto del archivo, con su formato, queda igual | Casi todo: guardas, conexiones `pg_*`, rutas, SQL, secretos |
| **Tokenizador** (`token_get_all`) | El código partido en piezas (variable, string, comentario…). Basta cuando el cambio es local | `$s{0}`, constantes sin comillas, BOM, `${l}` |
| **Rector** (`tools/rector.php`) | Herramienta que aplica reglas ya escritas | Funciones eliminadas en PHP 7 y 8 |
| **Regex** | Solo donde el texto no es PHP o el patrón es inequívoco | JavaScript (`accordion`), líneas de log, la conversión de `ereg` a `preg` |

**Toda herramienta que escribe** comprueba que el archivo resultante sigue compilando y, si no, no lo guarda (`not written, does not parse`). Después de cada paso: `php -l` en PHP 8.5.12, PHPStan, smoke de 2,000 páginas, navegador en 1,999 pantallas y, desde el paso 43, la **salida de cada página comparada antes y después** (`page-fingerprint.php`).

## La idea detrás de las "guardas"

Muchas funciones que en PHP 5 devolvían `null` o `false` con un aviso, y seguían, **en PHP 8.5.12 lanzan una excepción y la página da 500**. Comprobado en el contenedor:

```text
'' + 1                => TypeError: Unsupported operand types: string + int
count(null)           => TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given
implode(',', null)    => TypeError
date('Y', '')         => TypeError: date(): Argument #2 ($timestamp) must be of type ?int, string given
pg_num_rows(false)    => TypeError: … must be of type PgSql\Result, false given
1 / 0                 => DivisionByZeroError
$s = ''; $s[] = 1     => Error: [] operator not supported for strings
mktime(0,0,0,1,1,2020,0) => ArgumentCountError: mktime() expects at most 6 arguments, 7 given
'' == 0               => false   (en PHP 5: true)
date('Y/m/d', null)   => la fecha de hoy   (en PHP 5: 1970/01/01)
```

Una **guarda** es una condición que, solo en el caso que ahora falla, devuelve **lo mismo que devolvía PHP 5**. Con datos normales no cambia nada. Por eso no se usó `empty()`: trata `"0"` como vacío y habría cambiado resultados sin dar ningún error.

---

## 1. Funciones y sintaxis eliminadas (no compilaban o no existían)

| Antes | Después | Por qué | Cuántos | Herramienta |
|---|---|---|---:|---|
| `ereg()`, `eregi()` | `preg_match()` con delimitadores `#…#` y `i` si era `eregi` | Eliminadas en PHP 7.0 | 18 archivos, con `split` | Rector `EregToPregMatchRector` + a mano |
| `split()` | `preg_split()` | Eliminada en PHP 7.0 | Incluido arriba | Rector |
| `pg_freeresult()`, `pg_FreeResult()` | `pg_free_result()` | Alias deprecados | 260 | Rector `RenameFunctionRector` |
| `pg_result()` | `pg_fetch_result()` | Alias deprecado | 3 | Rector |
| `utf8_decode($x)` | `mb_convert_encoding($x, 'ISO-8859-1')` | Deprecada en PHP 8.2 | 1 | Rector |
| `"${var}"` | `"{$var}"` | Deprecado en PHP 8.2 | 2 archivos | Rector y a mano |
| `${l}` fuera de un string | `$l` | En PHP 8.5.12 es la constante `l`: fatal | 1 | `variable-llaves-constante.php` |
| `function NombreClase(` como constructor | `function __construct(` | PHP 8 ya no lo ejecuta al hacer `new`: **falla en silencio** | 10 clases | Rector `Php4ConstructorRector` |
| `$s{$i}` | `$s[$i]` | Sintaxis eliminada en PHP 8.0 | 120 + 484 en librerías | `llaves-a-corchetes.php` |
| `while (list($k, $v) = each($a))` | `foreach ($a as $k => $v)` | `each()` eliminada en PHP 8.0 | 9 (librerías PDF, luego borradas) | Rector |
| `get_magic_quotes_runtime()`, `set_magic_quotes_runtime()` | quitadas | Eliminadas | 1 archivo | A mano |
| `mysql_*` | **sin cambiar** | Solo en `admin/list/rss_list.php`, sin enlaces | 1 | Pendiente (`PENDING.md` 2a) |

Ejemplos reales:

```php
// public_html/hp/mailsyori/mm_sche.php:50
- eregi("Date:[ \t]*([^\r\n]+)", $head, $datereg);
+ preg_match("#Date:[ \t]*([^\r\n]+)#mi", $head, $datereg);

// public_html/admin/inc/qdmail_receiver.php:786
- function QdDecode( $param = null ){
+ function __construct( $param = null ){

// public_html/system/category3/dm/japanese.php:143
- $c=$s{$i};
+ $c=$s[$i];

// public_html/hp/m/sale_list.php
- $page_control.= ${l};
+ $page_control.= $l;
```

**Cuidado con `ereg`:** Rector convirtió mal `mm_sche.php` y `mm_send.php` (`[[:digit:]]` pasó a `[d]`, `[[:space:]]` a `[013\s]`, y un `preg_split` quedó sin delimitadores). Se corrigieron a mano y se probaron con 8 casos (MIME, teléfonos, punto final SMTP).

## 2. Lo que no compila en PHP 8

| Antes | Después | Por qué | Cuántos | Herramienta |
|---|---|---|---:|---|
| `$a[clave]` | `$a['clave']` | Sin comillas es una constante: "Undefined constant", fatal en PHP 8 | 69 | `constantes-sueltas.php` (no toca `"…$a[clave]…"` dentro de un string, que es válido) |
| `define(NOMBRE, …)` | `define('NOMBRE', …)` | Ídem | 6 | Ídem |
| `<?php//` | `<?php //` | Sin espacio, PHP 8 lee la constante `php` | 4 | A mano |
| `case X;` | `case X:` | Deprecado en PHP 8.5.12 | 6 | A mano |
| `(integer)`, `(double)` | `(int)`, `(float)` | Casts deprecados en PHP 8.5.12 | 7 | `deprecaciones-php85.php` |
| `function f(array $x = null)` | `function f(?array $x = null)` | Nulo implícito, deprecado en PHP 8.4 | 9 | Ídem |
| Parámetro opcional antes de uno obligatorio | Se quita el valor por defecto | Deprecado; PHP ya lo trataba como obligatorio | 38 | Ídem |
| `$http_response_header` | `http_get_last_response_headers()` | Deprecado en PHP 8.5.12 | 6 | A mano |
| `mktime(h, i, s, m, d, y, is_dst)` | `mktime(h, i, s, m, d, y)` | El 7.º argumento se eliminó en PHP 7: `ArgumentCountError`. Siempre valía 0 y Japón no tiene horario de verano | 220 | `corregir-argumentos-php8.php` |
| `srand(microtime()…)`, `mt_srand(…)` | comentado | Deprecado, y empeoraba el azar | 4 | A mano |
| Método hijo con otra firma que el padre (Qdmail `smtpObject()`) | Firma igual | Fatal al cargar la clase | 2 | A mano |
| BOM UTF-8 al inicio del archivo | quitado | Bytes antes de `<?php` rompen `header()` y las respuestas AJAX | 28 | `quitar-bom.php` |
| Clases sin propiedades declaradas | `#[\AllowDynamicProperties]` encima de la clase | Crear propiedades al vuelo está deprecado desde PHP 8.2 | 111 clases (2 más el 2026-11-18) | `permitir-propiedades-dinamicas.php` |

```php
// public_html/system/aterna_a4_12.php:32
- define(SC_CHAR, "UTF8");
+ define('SC_CHAR', "UTF8");

// public_html/hp/inc_m/emoji/trans.php:76
- case "i";
+ case "i":

// public_html/admin/list/url_scraper.php:26
- $stat_tokens = explode(' ', $http_response_header[0]);
+ $stat_tokens = explode(' ', (http_get_last_response_headers() ?? [])[0]);

// (una clase de API externa)
- public function request($resource, $method, array $data = NULL, array $files = NULL)
+ public function request($resource, $method, ?array $data = NULL, ?array $files = NULL)
```

## 3. Guardas: lo que en PHP 5 seguía y en PHP 8.5.12 es fatal

| Situación | Antes | Después | Qué devuelve la guarda (igual que PHP 5) | Cuántos | Herramienta |
|---|---|---|---|---:|---|
| Consulta fallida y luego `pg_*` | `@pg_num_rows($r)` | `$r ? @pg_num_rows($r) : null` | `null` | 2,137 en 737 archivos | `proteger-pg-silenciado.php` |
| Lo mismo, sin `@` (2026-11-10) | `pg_num_rows($r)` | `$r ? pg_num_rows($r) : null` | `null`: PHP 7 avisaba, devolvía `null` y la página seguía; PHP 8.5.12 la cortaba (`TypeError`). Con una consulta correcta da lo mismo que antes | 7,730 en 1,476 archivos | `proteger-pg-resultado.php` |
| Conexión a otro servidor nula o `false` (POS, beacon, exportaciones; 2026-11-10) | `pg_query($posConnect, $sql)` | `$posConnect ? pg_query($posConnect, $sql) : null` | `null`, como PHP 7.4 (comprobado: `pg_query`, `pg_query_params`, `pg_escape_*`, `pg_last_error` y `pg_close` con `null` o `false` daban `NULL`). `$connect` no se toca | 673 en 136 archivos | `proteger-pg-conexion.php`; verificado con `verify-pg-guard-ast.php` |
| Aviso de conexión fallida en pantalla (2026-11-11, por indicación del jefe de Antony) | `pg_connect("host=…")` | `@pg_connect("host=…")` | `false`, como antes; solo desaparece el aviso de la pantalla, y con él la línea del log | 60 en 52 archivos | `silenciar-conexion.php`; verificado comparando cada archivo con HEAD tras quitar el `@` |
| Fila `''` en `pg_fetch_result` | `pg_fetch_result($q,'','title')` | `null`, con el comentario `PHP 7: pg_fetch_result() with row '' returned NULL` (2026-11-10; antes `pg_fetch_result($q,0,'title')`) | `NULL`, como PHP 7: la función no se ejecutaba. Igual que desarrollo (`『』`) | 25 | `corregir-argumentos-php8.php` |
| `count()` de algo que no es array | `count($arr)` | `(is_countable($arr) ? count($arr) : (isset($arr) ? 1 : 0))` | `0` si es `null`, `1` si es texto | ≈ 570 | `tipos-php85.php`, `array-fn-guard.php` |
| `implode()` y funciones de array con `null` | `array_sum($val)` | `(is_array($val) ? array_sum($val) : null)` | `null` | 802 + 980 | Ídem |
| Funciones de array anidadas | `array_values(array_unique($x))` | `array_values((is_array($x) ? array_unique($x) : array()))` | `array()` dentro, para que la función de fuera no falle | 193 | `array-fn-guard.php` |
| Variable `""` usada como array | `$sns = '';` y luego `$sns[] = …` | `$sns = [];` | Un array, como lo convertía PHP 5 | 17 + sesiones | A mano, `string-as-array.php` |
| Texto vacío en una operación | `$total + $array['x']` | `(is_string($total) && !is_numeric($total) ? (float) $total : $total) + $array['x']` | `''` y `'abc'` valen 0, como en PHP 5 | 724 | `numeric-on-text.php` |
| División entre 0 | `ceil($max/$kensu)` | `($kensu ? ceil($max/$kensu) : 0)` | `0` | 228 en 108 archivos | `division-protegida.php` |
| `checkdate()` con texto | `checkdate($mm, $i, $yy)` | `checkdate((int) $mm, (int) $i, (int) $yy)` | El mismo número | 54 | `tipos-php85.php` |
| `mktime()` y `date()` con texto | `mktime(0,0,0,date("n"),…)` | `mktime(0,0,0,(int) date("n"),…)` | Ídem | 18 + pasos 26g y 43 | A mano y `numeric-on-text.php` |
| `fwrite()`, `fclose()`, `fgets()` sobre un `fopen()` fallido | `fwrite($fp, …)` | `$fp ? fwrite($fp, …) : false` (`feof` → `true`, para no colgar un `while`) | `false` | 162 | `tipos-php85.php` |
| Página 0 en la paginación | `$from=($page-1)*$kensu;` | `$from=max(0, ($page - 1) * $kensu);` | Primera página, sin `OFFSET` negativo (PHP 7 dejaba la lista vacía y avisaba; PHP 8.5.12 da fatal en `pg_num_rows(false)`) | 81 archivos + 3 clases de paginación en el paso 59 (`maClass_sysParts.php`, su `_v0.1` y `card_signage/cPageControl.php`) | A mano |
| `null` en parámetros de funciones internas | `json_decode($sns)` | `json_decode($sns ?? '')` | `''` (deprecado desde 8.1, `TypeError` en PHP 9) | 94 | `null-parametros.php` (guiada por el log) |
| Código de sesión sin comillas en SQL | `"店id = ".$_SESSION['shop_cd']` | `"店id = ".(int) $_SESSION['shop_cd']` | Con sesión sin tienda, `0` en vez de SQL inválido | 285 en 167 archivos | `sql-sesion-int.php` |
| Conexión implícita en `pg_*` | `pg_query($sql)`, `pg_escape_string($val)` | `pg_query($connect, $sql)`, `pg_escape_string($connect, $val)` | La misma conexión, ahora explícita (deprecado en PHP 8.5.12) | 528 (quedan 16) | `pg-conexion-explicita.php` |

```php
// public_html/digitalsignage/html/immovables/ajax_list.php:86-94
- $max = @pg_num_rows($query);
+ $max = $query ? @pg_num_rows($query) : null;
- $maxpage=@ceil($max/$kensu);
+ $maxpage=@($kensu ? ceil($max/$kensu) : 0);
- $from=($page-1)*$kensu;
+ $from=max(0, ($page - 1) * $kensu);

// public_html/system/category3/inc/push_list.php:188
- $max = count($arr);
+ $max = (is_countable($arr) ? count($arr) : (isset($arr) ? 1 : 0));

// public_html/system/category1/shukei/model1.php:329
- $total = array_sum($val);
+ $total = (is_array($val) ? array_sum($val) : null);

// public_html/system/category7/digital_signage/movie_program_detail.php:329
- print pg_fetch_result($query,'','title');
+ print pg_fetch_result($query,0,'title');

// public_html/system/category3/anketo/anketo_mail_send.php:94
- $sql .= "店id = ".$_SESSION['shop_cd'].", ";
+ $sql .= "店id = ".(int) $_SESSION['shop_cd'].", ";
```

**Decisión (inicial):** no se envolvieron las ~9,000 llamadas `pg_*()` **sin** `@`. Con una consulta fallida, PHP 5 dejaba la página a medias y PHP 8.5.12 da 500; en uso normal las consultas no fallan, y envolverlas ocultaría errores reales. Solo se protegieron las que ya tenían `@`, porque esas el programador quiso silenciarlas. (El 2026-11-10 se protegieron también 7,730 sin `@`, para que la página siga como en PHP 7: la fila «Lo mismo, sin `@`» de la tabla.)

## 4. Cambios de comportamiento sin error (paso 47)

Estos no rompen nada a la vista: **dan otro resultado**. Se corrigieron para que den el de PHP 5.

| Situación | Antes | Después | Diferencia que se evita | Cuántos | Herramienta |
|---|---|---|---|---:|---|
| Texto comparado con número | `if ( $_mm == 0 )` | `if ( (is_string($_mm) && !is_numeric($_mm) ? (float) $_mm : $_mm) == 0 )` | `'' == 0` era `true` en PHP 5 y es `false` en PHP 8: la rama dejaba de ejecutarse | 999 | `numeric-on-text.php` |
| Número comparado con texto (caso inverso, 2026-11-19) | `if ( $pid == '' )` con `$pid = 0` | `if ( $pid == ((is_int($pid) \|\| is_float($pid)) ? 0 : '') )` | En PHP 7, `0 == ''` y `0.0 != ''` convertían el texto a número; PHP 8 los compara como texto. Comprobado con 13 tipos de valor: la guarda en PHP 8.5.12 da lo mismo que el original en PHP 7.4.33. Lo encontró la comparación de `master` en PHP 7.4 con la rama (`Area.php`: desaparecían las tiendas sin área del selector) | 9 en 8 archivos (`Area.php`, 6 listas y CSV de asistencia con `$syukkin_sum_time`, `mm_send.php`); de 36 candidatas, las otras 27 no pueden valer 0 en ese punto | `int-vs-text-compare.php` + revisión a mano |
| Contador de un bucle comparado con un dato (2026-11-19) | `for ($i = 0; …) { if ($data['type_flg'] == $i) … }` | `((is_string(X) && !is_numeric(X) ? (float) X : X) == $i)`, la guarda de `numeric-on-text.php` | Con el dato `''` o un texto no numérico, PHP 7 marcaba la opción 0 (`'' == 0`); PHP 8 no marca ninguna. Comprobado con 13 valores y contadores 0-3: la guarda en PHP 8.5.12 = el original en PHP 7.4.33 | 35 en 25 archivos (selectores de `<option>`, informes de `category1/shukei`, `category3/visit`, configuración). Lo encontró la comparación con `master` en `shop_new.php` | `int-vs-text-compare.php --fix` (33) + a mano (2: `shop_new.php`, `category0/config/config.php`) |
| Array en una función de texto dentro de un bucle de la petición (2026-11-20) | `foreach ($_REQUEST as $key => $val) { $_REQUEST[$key] = @pg_escape_string($connect, $val); }` | `(is_array($val) ? null : @pg_escape_string($connect, $val))`. En `get_form2()` y `fnc_set_form()` (`system/inc/kihon.php`, `hp/inc_m/kihon.php`) y en `get_form()` de `hp/`: `if (is_array($str)) return '';` (`null` en `get_form`) | Un formulario con casillas `name[]` envía arrays. PHP 7 devolvía `null` (con `@`, sin aviso) y PHP 8 lanza `TypeError`: la selección de clientes de メルマガ y デコメール moría al enviarla. Comprobado: PHP 7.4.33 da `NULL` / `''`, PHP 8.5.12 `TypeError` y con la guarda `NULL` / `''`. La migración ya protegía 72 bucles; estos 24 llevaban `@` y se habían escapado. Lo encontró la pasada de formularios del 2026-11-20 | 24 + 5 funciones | `array-arg-guard.php` |
| `switch` con `case 0` | `switch ( $cstm_val[$k] )` | `switch ( (is_string($cstm_val[$k]) && !is_numeric($cstm_val[$k]) ? (float) $cstm_val[$k] : $cstm_val[$k]) )` | Un valor vacío entraba en `case 0` en PHP 5 y ya no | 2 | Ídem |
| `date()` con fecha `null` | `$t = date('G', $now);` | `$t = date('G', $now ?? 0);` | Con `null`, PHP 5 daba 1970 y PHP 8 **la fecha de hoy** | 1,385 | `date-null-guard.php` |
| `htmlspecialchars()` sin modo | `htmlspecialchars($str)` | `htmlspecialchars($str, ENT_COMPAT \| ENT_HTML401)` | Desde PHP 8.1 también escapa `'`: `O'Neil` se guardaba como `O&#039;&#039;Neil` | 9 (las que guardan o envían por correo) | A mano |

```php
// public_html/admin/api/class/Customer.php:2316
- if ( $_mm == 0 ) {
+ if ( (is_string($_mm) && !is_numeric($_mm) ? (float) $_mm : $_mm) == 0 ) {

// public_html/appview/class/QuickReservation.php:185
- $t = date('G', $now);
+ $t = date('G', $now ?? 0);

// public_html/system/inc/kihon.php:33
- $str = htmlspecialchars($str);
+ $str = htmlspecialchars($str, ENT_COMPAT | ENT_HTML401);
```

Por qué `(float)` y no `(int)`: en PHP 5, `'1.5' == 1.5` era `true`; `(int)` lo habría convertido en `1`. La guarda solo actúa si el valor es texto **no numérico** (`''`, `'abc'`), que PHP 5 convertía en `0`; los textos numéricos se dejan tal cual, porque PHP 8 los compara igual que PHP 5.

Las llamadas a `htmlspecialchars()` que solo pintan HTML **no se cambiaron**: escapar también la comilla simple ahí es más seguro y el navegador muestra lo mismo.

## 5. Entorno y librerías

| Antes | Después | Por qué | Cuántos | Herramienta |
|---|---|---|---:|---|
| `'/home/system/public_html/…'` | `dirname(__DIR__, N) . '/…'` | Rutas fijas a un servidor; en Docker el código está en `/var/www/html` | 1,588 rutas en 1,470 archivos | `rutas-dinamicas.php` |
| `/usr/bin/php` en `exec()` y en el crontab | `crm_php_cli()` (`inc/php_cli.php`) | En la imagen `php:8.5-fpm` PHP está en `/usr/local/bin/php`: **el envío masivo no enviaba nada** | 10 archivos | A mano |
| Contraseñas y claves en el código | `env_value('POS_DB_PASSWORD')` | Secretos fuera del código (`.env`) | 114 en 85 archivos (3 más el 2026-11-17 y 18) | `secretos-a-env.php` |
| `mb_send_mail(…)` | `crm_send_mail(…)` (misma firma, Symfony Mailer) | Envío por SMTP configurable (`MAILER_DSN`) | 97 llamadas en 58 archivos | `correo-a-symfony.php` |
| Qdmail (decomail) | `CrmDecomail` (misma interfaz) | Librería de 2008 sin mantenimiento | 3 archivos | A mano |
| `$json->decode($s)` (Services_JSON) | `json_decode($s, true)` | Nativo desde PHP 5.2 | 23 archivos, 49 `require` | `services-json-a-nativo.php` |
| `new mPDF('ja', 'A4')` | `new \Mpdf\Mpdf(['mode' => 'ja', 'format' => 'A4', …])` | mPDF 5.7 no funcionaba desde PHP 7 | 3 | A mano |
| `Dotenv::createImmutable` | `Dotenv::createUnsafeImmutable` | Dotenv 5 ya no llama a `putenv()` y el código lee con `getenv()` | 7 | A mano |
| `JWT::decode($jwt, $key, ['RS256'])` | `JWT::decode($jwt, new Key($key, 'RS256'))` | firebase/php-jwt 7 | 1 | A mano |
| `php_value` / `php_flag` en `.htaccess` | Dentro de `<IfModule mod_php.c>` + `.user.ini` equivalente | Con PHP-FPM, Apache daba 500 en esas carpetas | 3 carpetas | A mano |
| `imap_open()` | `Pop3Simple` (cliente POP3 propio) | La extensión IMAP salió del núcleo en PHP 8.4 | 1 | A mano |
| jQuery 1.2.3 … 3.1.1 (34 copias) | jQuery 3.7.1 + Migrate, **con el mismo nombre de archivo** | XSS conocidos; así no se tocan las páginas | 34 | `jquery-compuesto.php` |
| `.accordion('activate', N)` | `.accordion('option', 'active', N)` | Quitado en jQuery UI 1.10 | 498 archivos | `acordeon-activate.php` (regex) |

```php
// public_html/admin/list/ajax/versionUpExport.php:874
- $base_dir = '/home/system';
+ $base_dir = dirname(__DIR__, 4);

// public_html/system/category3/class/cSendSchedule.php:356
- $otherData .= "0 10 * * * /usr/bin/php /home/system/public_html/system/category3/point/setPaidMemberPoint.php \n";
+ $otherData .= "0 10 * * * " . crm_php_cli() . " " . dirname(__DIR__, 3) . "/system/category3/point/setPaidMemberPoint.php \n";

// public_html/touchcation/tagsignage/print/birthDayCoupon.php:26
- $couponData = $json->decode( pg_fetch_result($query, 0, 'birthday_coupon_settings') );
+ $couponData = json_decode(pg_fetch_result($query, 0, 'birthday_coupon_settings'), true);
```

```js
// public_html/system/category0/config/area.php:139
- $(".ac1").accordion('activate', 0);
+ $(".ac1").accordion('option', 'active', 0);
```

## 6. Seguridad (no hacía falta para migrar)

Se hizo en la misma rama, pero **no es necesario para PHP 8.5.12**. Resumen; el detalle está en `REFACTORING.md`, sección B.

| Antes | Después | Contra qué | Cuántos |
|---|---|---|---:|
| `"cd = ".$_REQUEST['cd']` | `"cd = ".(int) $_REQUEST['cd']` | SQLi en valores numéricos | 1,228 + 661 |
| `"name = '".$x."'"` | `"name = '".pg_escape_string($connect, (string) $x)."'"` | SQLi entre comillas | ≈ 600 |
| `<?=$_POST['x']?>` | `<?=h($_POST['x'])?>` | XSS reflejado | 2,728 + indirectos |
| `system('rm -rf '.$del_path)` | `system("rm -rf " . escapeshellarg((string) $del_path))` | Inyección de comandos | 1,106 variables |
| `"From: ".$nombre` | `"From: ".crm_strip_newlines($nombre)` | Inyección de cabeceras de correo | 10 |
| Página sin control de sesión | `require` de `session.php`, 403 por URL directa o solo CLI | Acceso sin login | Pasos 25b a 32 |
| — | `inc/csrf.php` (guarda central) | CSRF | 1 archivo |

```php
// public_html/system/category7/digital_signage/display_delete.php:49
- system('rm -rf '.$del_path);
+ system("rm -rf " . escapeshellarg((string) $del_path));

// public_html/system/category3/inc/mail_confirm_frm.php:371
- <?=$_POST['pic_link']?>
+ <?=h($_POST['pic_link'])?>
```

## 7. Errores de los reemplazos, y cómo se detectaron

| Qué pasó | Cómo se vio | Arreglo |
|---|---|---|
| `(int)` aplicado por el nombre de la variable a `$chk_cd = " and cd <> 5"` (era SQL, no un número) | El smoke test, en 1 de 5 pantallas | Auditoría de cada variable convertida |
| Doble escape de comillas: `createform_sql()` guardaba `A''1` | Solo un flujo completo con una comilla en el dato | `undo_confirm_quote_doubling()` en el alta de clientes |
| Guardas de array anidadas con `: null` seguían fallando | Pasada con parámetros vacíos | `: array()` cuando el resultado entra en otra función de array |
| Rector convirtió mal patrones POSIX de `ereg` | Revisión del diff | A mano, con 8 casos probados |
| Renombrar por tokens al traducir `tools/scripts` | Comparar la salida de la versión vieja y la nueva (el lint pasaba) | Corregido antes del commit |

**Conclusión:** el lint no basta para validar un cambio masivo. Lo que cazó los errores fue comparar la salida de las páginas, o de las herramientas, antes y después.
