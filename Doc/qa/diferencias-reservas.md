# Diferencias: 予約管理 (category9)

Revisión visual S1, 2026-11-27 (método en [README.md](README.md)). Pantallas: `/system/category9/` y `yoyaku/yoyaku_config.php` ~ `/admin/reservas/settings/shop`.

| Pantalla | Qué difiere | Detalle |
|---|---|---|
| `/system/category9/` | Desde `7d54d3a` redirige a `yoyaku/time_table.php` como el legacy, pero esa pantalla (la agenda) **no existe** en local: 404 | Es la entrada del módulo (pestaña 予約管理 de la cabecera); los popups de ajustes se abren desde ella. |
| `yoyaku_config.php` (各店舗設定) | Sin diferencias | Popup `layouts.legacy-popup`. |
