# CRMSystem — reglas del proyecto

Reescritura en **Laravel 13 + PHP 8.5** del CRM legacy (VivaTech / Japón). El código vive en `public_html/`.

**Leer primero:** [Doc/MIGRACION-LARAVEL13.md](Doc/MIGRACION-LARAVEL13.md) (contexto, fuentes, estado). Si eres una sesión de la orquestación, tu archivo es `Doc/coordinacion/<tu sesión>.md`.

## Fuentes de verdad

| Qué | Dónde |
|---|---|
| Comportamiento a reproducir | `C:\Users\User\VivaTech\crm2-php8` (legacy migrado a PHP 8.5.12; solo lectura) |
| Muestra visual | `https://development.crm-s.net/system/login.php` (master / master, **solo lectura**: nunca guardar ni borrar nada allí) |
| Análisis vista por vista | `Doc/analisis-legacy/NN-*.md` (buscar la sección por título con grep; no leer completo) |
| Reglas de código y propiedad de carpetas | [Doc/contratos/CONVENCIONES.md](Doc/contratos/CONVENCIONES.md) |
| Estándares de BD | [Doc/database/ESTANDARES_BD.md](Doc/database/ESTANDARES_BD.md) |

## Reglas

- **Paridad funcional:** todo lo que el legacy hace, Laravel lo hace. Los servicios viejos se reemplazan por los de Laravel (Mail + colas, Scheduler, Storage, Policies, StreamedResponse…), paso a paso.
- **Fuera de alcance:** `hp/` (sitio viejo), `admin/` y `system/category8` (女の子管理).
- **Integraciones externas** (POS, quiosco, APIs): mantener URL y contrato. Ver `Doc/legacy-crm2/INTEGRATIONS.md` y `Doc/analisis-legacy/07-*.md`, `08-*.md`.
- **Idioma:** respuestas, documentos y comentarios en español; textos de pantalla en japonés (`lang/ja`); identificadores en inglés, salvo términos de dominio que ya existen.
- **Nombres:** PHP `camelCase` en métodos y variables como Laravel, clases `PascalCase`; BD `snake_case`.
- **Secretos:** nunca en el código ni en git. `Doc/legacy-crm2/env-legacy.example` tiene claves reales de producción: solo para consultar nombres de variables; nunca copiar valores.
- **Nunca** lanzar contra el legacy `system/login.php?activation=1`, `create_customer.php` ni `create_shop.php`.
- **Pruebas:** Pest. Desde cualquier worktree: `bash scripts/en-docker.sh php artisan test` (usa la BD propia de la sesión).
- **Git:** nunca `push`. Los commits y merges los hace solo S0 (ver `Doc/planificacion/ORCHESTRATION.md`).

## Al compactar

Conservar: sesión y rama actuales, tarea en curso y su criterio de terminado, archivos tocados, pruebas que fallan, decisiones tomadas en esta conversación.
