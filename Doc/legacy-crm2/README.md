# Referencia: documentación del refactor crm2-php8 (PHP 7 → PHP 8.5.12)

> Copia de `C:\Users\User\VivaTech\crm2-php8\doc\` (versiones en español, que son las de referencia) al 2026-11-26, commit `65822b09` de la rama `refactor/fase-0`.
> **Solo lectura:** el original se sigue manteniendo en crm2-php8. Las versiones japonesas (`*.ja.md`) y las herramientas (`tools/`) están allí.
> Plan de la reescritura en Laravel 13: [../MIGRACION-LARAVEL13.md](../MIGRACION-LARAVEL13.md).

| Documento | Para qué sirve en la migración a Laravel |
|---|---|
| [MIGRATION.md](MIGRATION.md) | Resumen del refactor y servicios reemplazados |
| [CONTINUE.md](CONTINUE.md) | Decisiones de Antony, trampas conocidas, credenciales de local y de desarrollo |
| [MANUAL-TESTS.md](MANUAL-TESTS.md) | **Inventario pantalla por pantalla** de los 10 módulos: checklist de paridad para cada módulo de Laravel |
| [INTEGRATIONS.md](INTEGRATIONS.md) | Contratos externos (POS, quiosco, APIs) que Laravel debe respetar |
| [PENDING.md](PENDING.md) | Decisiones abiertas y preguntas para Japón |
| [MISSING-TABLES.md](MISSING-TABLES.md) | Tablas y servidores que faltan en local |
| [BROKEN-FILES.md](BROKEN-FILES.md) | Pantallas que ya estaban rotas en producción: no hace falta reproducirlas |
| [DEPENDENCIES.md](DEPENDENCIES.md) | Librerías de terceros del legacy |
| [PHP85-REPLACEMENTS.md](PHP85-REPLACEMENTS.md) | Comportamientos de PHP 7 que se conservaron (útil para entender resultados raros) |
| [REFACTORING.md](REFACTORING.md) | Plan, porcentajes y seguridad del refactor |
| [CHANGELOG.md](CHANGELOG.md) | Historial por commit |
