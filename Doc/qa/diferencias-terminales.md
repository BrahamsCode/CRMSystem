# Diferencias: 端末管理 (category7)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: portada, `felica/terminal_list.php` ~ `/admin/terminales/felica-terminals`, `digital_signage/` ~ `/admin/terminales/signage-programs`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| `felica/terminal_list.php` | La muestra pinta esta pantalla con la **UI clásica** (`import.css`: cabecera blanca con pestañas de colores, LOGIN NAME naranja); local con la UI 2 como el resto | Captura: [capturas/terminales-felica_terminals.png](capturas/terminales-felica_terminals.png). Parece que la página legacy carga su propio `<head>` en vez de `Base::createHead`. Decidir si se copia (rompería la coherencia con el resto) o se acepta como mejora. |
| Título del bloque | Local muestra 「サイネージ管理」 como título de la sección (h1 del área) y la muestra lo deja vacío | Consecuencia de la UI clásica de la muestra. |
| `digital_signage/` | La muestra responde 403 (índice de carpeta) | Sin comparar. |

Menú lateral, cabecera y pie de la portada: sin diferencias (en las dos BD el menú está vacío para master).

## Segunda pasada (S1, 2026-11-27): signage y digitalsignage de S4

| Pantalla | Resultado |
|---|---|
| CMS `signage/index.php` ~ `/admin/senalizacion` | La muestra pide su propio login (「ログインください」, `?s=`): sin comparar. En `localhost:8081` da **500** `relation "terminal" does not exist`: a la BD principal le falta el esquema `signage` (`database/schema/signage-admin.sql`); es del entorno, no del código. |
| Reproductor `signage/view/index.php` | Mismo 500 en local (mismo motivo). |
| `digitalsignage/index.php` | Mismo esqueleto (title, recursos e id) sin ejecutar el JS de la muestra (sus sondeos podrían marcar cupones como impresos). |
| `digitalsignage/roulette.php` | La muestra devuelve `#cover`; local `#roulette` y `#gameMessage`. Depende de la configuración de ルーレット de cada BD: revisar con la misma configuración. |
