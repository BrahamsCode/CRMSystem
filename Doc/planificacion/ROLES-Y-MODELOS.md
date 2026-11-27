# Roles y modelos

| # | Rol | Qué hace | Cómo | Modelo |
|---|---|---|---|---|
| 0 | **Antony (Product Owner)** | Decide el producto, aporta datos de producción y credenciales, responde los mensajes "ANTONY:" | — | — |
| 1 | **Coordinador** | Reparte tareas, revisa diseños, decide pedidos, hace commits y merges locales, QA de integración. No escribe código de módulos | Sesión **S0** | Opus 5.5, alto |
| 2 | **Núcleo e integraciones** | Laravel 13, auth, permisos, tiendas, módulos activables, layout, menú, APIs externas, importador legacy | Sesión **S1** | Opus 5.5, alto |
| 3 | **Módulos** | Diseño e implementación de su grupo con pruebas | Sesiones **S2–S5** | Sonnet 5.5, medio |
| 4 | **QA y revisión** | Pruebas, revisión de código y seguridad | Subagente en cada sesión | Sonnet 5.5 |
| — | Búsquedas | Buscar en el legacy, resúmenes | Subagente `Explore` | Haiku 5.5 |

**Fable 5.2** solo ante un bloqueo que Opus no resuelve.

- Con suscripción, el modelo cambia **qué tan rápido se consume el límite**: Opus ≈ 2× Sonnet. Por eso solo S0 y S1 van en Opus.
- Los módulos van en Sonnet porque el análisis vista por vista (`Doc/analisis-legacy/`) ya deja el trabajo especificado.
