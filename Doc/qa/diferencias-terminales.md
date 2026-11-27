# Diferencias: 端末管理 (category7)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: portada, `felica/terminal_list.php` ~ `/admin/terminales/felica-terminals`, `digital_signage/` ~ `/admin/terminales/signage-programs`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| `felica/terminal_list.php` | La muestra pinta esta pantalla con la **UI clásica** (`import.css`: cabecera blanca con pestañas de colores, LOGIN NAME naranja); local con la UI 2 como el resto | Captura: [capturas/terminales-felica_terminals.png](capturas/terminales-felica_terminals.png). Parece que la página legacy carga su propio `<head>` en vez de `Base::createHead`. Decidir si se copia (rompería la coherencia con el resto) o se acepta como mejora. |
| Título del bloque | Local muestra 「サイネージ管理」 como título de la sección (h1 del área) y la muestra lo deja vacío | Consecuencia de la UI clásica de la muestra. |
| `digital_signage/` | La muestra responde 403 (índice de carpeta) | Sin comparar. |

Menú lateral, cabecera y pie de la portada: sin diferencias (en las dos BD el menú está vacío para master).
