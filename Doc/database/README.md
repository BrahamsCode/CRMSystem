# Base de Datos - Módulo de Gestión de Clientes

> ⚠️ **Empezar por [ESTANDARES_BD.md](./ESTANDARES_BD.md).**
>
> `ESQUEMA_BD_MODULO_CLIENTES.md`, `DIAGRAMA_ERD.md`, `QUERIES_Y_CASOS_DE_USO.md` y
> `schema.sql` son **referenciales**: análisis del sistema legacy, no el esquema a
> implementar. Se escribieron antes de incorporar los estándares de la empresa.
>
> Lo que hay que construir está en
> [TABLAS_MODULO_CLIENTES.md](./TABLAS_MODULO_CLIENTES.md) (módulo 1) y
> [TABLAS_MODULO_PROMOCIONES.md](./TABLAS_MODULO_PROMOCIONES.md) (módulo 2).

## 🎯 Diseño de Base de Datos para PostgreSQL

Esquema completo de base de datos diseñado para el **nuevo sistema CRM** basado en el análisis del **sistema legacy** documentado en 23 vistas.

---

## 📚 Documentos Disponibles

### 1. **[ESQUEMA_BD_MODULO_CLIENTES.md](./ESQUEMA_BD_MODULO_CLIENTES.md)** ⭐ INICIO
👉 **Documento principal** - Esquema completo de base de datos

**Contiene:**
- ✅ 13 tablas definidas con SQL completo
- ✅ 7 tipos ENUM personalizados
- ✅ Diagrama ERD en formato Mermaid
- ✅ Arquitectura de 3 capas para campos personalizados
- ✅ Scripts SQL ejecutables
- ✅ Índices GIN y B-Tree optimizados
- ✅ Triggers automáticos para estadísticas
- ✅ Ejemplo completo: Categoría "Mascota"
- ✅ Migraciones y datos de ejemplo
- ✅ Vista materializada para búsquedas

**Ideal para:** Entender toda la arquitectura, ejecutar el esquema, planificar migración

---

### 2. **[DIAGRAMA_ERD.md](./DIAGRAMA_ERD.md)**
📊 Diagramas visuales de la base de datos

**Contiene:**
- 🔷 Diagrama ERD completo en Mermaid
- 🔷 Diagrama simplificado de entidades core
- 🔷 Flujo de datos de campos personalizados
- 🔷 Arquitectura de 3 capas visualizada
- 🔷 Tipos ENUM del sistema
- 🔷 Índices y triggers
- 🔷 Ejemplo práctico con datos reales
- 🔷 Estadísticas del esquema

**Ideal para:** Visualizar relaciones, presentar a stakeholders, onboarding de equipo

---

### 3. **[QUERIES_Y_CASOS_DE_USO.md](./QUERIES_Y_CASOS_DE_USO.md)**
💻 Queries SQL prácticas y casos de uso

**Contiene:**
- 📝 27+ queries de ejemplo documentadas
- 📝 Búsquedas básicas y avanzadas
- 📝 Búsquedas en JSONB (campos personalizados)
- 📝 Estadísticas y reportes
- 📝 Gestión de rangos automática
- 📝 Gestión de visitas
- 📝 Optimización de performance
- 📝 5 casos de uso completos
- 📝 Funciones útiles en PL/pgSQL

**Ideal para:** Implementar features, optimizar queries, aprender PostgreSQL

---

## 🚀 Inicio Rápido

### Para Implementar el Esquema

```bash
# 1. Conectar a PostgreSQL
psql -U postgres -d crm_database

# 2. Ejecutar el script completo
\i docs/database/ESQUEMA_BD_MODULO_CLIENTES.md
# (extraer solo las secciones SQL del markdown)

# 3. Verificar tablas creadas
\dt

# 4. Insertar datos de ejemplo
-- Ver sección "Migraciones" en ESQUEMA_BD_MODULO_CLIENTES.md
```

### Para Desarrolladores Nuevos

1. **Leer primero**: [ESQUEMA_BD_MODULO_CLIENTES.md](./ESQUEMA_BD_MODULO_CLIENTES.md) (30 min)
   - Entender arquitectura de 3 capas
   - Ver ejemplo "Mascota" completo
   
2. **Visualizar**: [DIAGRAMA_ERD.md](./DIAGRAMA_ERD.md) (15 min)
   - Revisar diagrama ERD completo
   - Entender flujo de datos
   
3. **Practicar**: [QUERIES_Y_CASOS_DE_USO.md](./QUERIES_Y_CASOS_DE_USO.md) (45 min)
   - Ejecutar queries de ejemplo
   - Estudiar casos de uso

---

## 🏗️ Arquitectura del Sistema

### Decisión de Diseño: Híbrido JSONB + Relacional

```
┌─────────────────────────────────────────────────────────┐
│ CAMPOS ESTÁNDAR (55 campos)                            │
│ ➜ Almacenados en columnas normales de la tabla         │
│ ➜ Tipado fuerte, validación automática                 │
│ ➜ Búsquedas rápidas con índices B-Tree                 │
├─────────────────────────────────────────────────────────┤
│ CAMPOS PERSONALIZADOS - TIPO NORMAL (1 registro)       │
│ ➜ Almacenados en JSONB: clientes.datos_personalizados  │
│ ➜ Flexible, sin límite de categorías                   │
│ ➜ Búsquedas con índices GIN                            │
├─────────────────────────────────────────────────────────┤
│ CAMPOS PERSONALIZADOS - TIPO MÚLTIPLE (N registros)    │
│ ➜ Almacenados en tabla: valores_personalizados         │
│ ➜ Relacional puro (EAV)                                │
│ ➜ Un registro por entrada (ej: múltiples mascotas)     │
└─────────────────────────────────────────────────────────┘
```

### Por Qué Este Diseño

| Requisito | Solución | Ventaja |
|-----------|----------|---------|
| **50+ campos estándar** | Columnas normales | ⚡ Máxima performance |
| **Campos dinámicos ilimitados** | JSONB + Metadata | ✨ Flexibilidad total |
| **14 tipos de campo** | Tipo ENUM + validación app | 🛡️ Integridad controlada |
| **Búsquedas complejas** | Índices GIN en JSONB | 🔍 Búsqueda nativa rápida |
| **Múltiples registros** | Tabla valores_personalizados | 📦 Relacional tradicional |

---

## 📊 Estadísticas del Esquema

```
┌──────────────────────────────────────────────────────────┐
│ COMPONENTES DEL ESQUEMA                                 │
├──────────────────────────────────────────────────────────┤
│ Tablas principales:             13                      │
│ Tipos ENUM:                     7                       │
│ Foreign Keys:                   19                      │
│ Índices GIN (JSONB/Texto):      6                       │
│ Índices B-Tree (Estándar):      25+                     │
│ Triggers automáticos:           6                       │
│ Funciones PL/pgSQL:             4                       │
│ Constraints CHECK:              8                       │
│ Constraints UNIQUE:             12                      │
│ Soft deletes:                   ✅ (deleted_at)         │
│ Auditoría:                      ✅ (historial_rangos)   │
│ Multi-tenant:                   ✅ (por tienda_id)      │
└──────────────────────────────────────────────────────────┘
```

---

## 🔑 Entidades Principales

### 1. **tiendas**
Tiendas/sucursales del sistema (multi-tenant)

### 2. **clientes** ⭐ CORE
Tabla principal con:
- 55 campos estándar
- Campo JSONB para datos personalizados
- Soft delete (deleted_at)
- Estadísticas calculadas automáticamente

### 3. **grupos_clientes**
Categorización de clientes

### 4. **rangos_monto** y **rangos_visitas**
Clasificación automática por comportamiento

### 5. **categorias_personalizadas** 📋 METADATA
Define categorías dinámicas (ej: Mascota, Familia, Vehículo)

### 6. **campos_personalizados** 📝 SCHEMA
Define estructura de campos (14 tipos disponibles)

### 7. **opciones_campo** 🔘
Opciones para campos tipo select/radio/checkbox

### 8. **valores_personalizados** 💾
Valores para categorías tipo "múltiple"

### 9. **visitas**
Registro de visitas con triggers automáticos para estadísticas

### 10. **motivos_visita**
Catálogo de motivos de visita

### 11. **historial_rangos** 📜
Auditoría de cambios de rangos

---

## 🎨 Ejemplo Completo: Categoría "Mascota"

### Estructura Definida

```sql
-- CAPA 1: Categoría
categorias_personalizadas
  id: 1
  nombre: "Mascota"
  slug: "mascota"
  tipo_registro: "normal"
  columnas_visualizacion: 3

-- CAPA 2: Campos
campos_personalizados
  1. Nombre (tipo: texto)
  2. Tipo (tipo: select, opciones: Perro/Gato/Conejo/Hamster/Otros)
  3. Peso (tipo: numérico, unidad: Kg)

-- CAPA 3: Valores en cliente
clientes.datos_personalizados
{
  "mascota": {
    "nombre": "Firulais",
    "tipo": "Perro",
    "peso": 15
  }
}
```

### Query para Buscar

```sql
SELECT * FROM clientes
WHERE datos_personalizados -> 'mascota' ->> 'tipo' = 'Perro'
  AND (datos_personalizados -> 'mascota' ->> 'peso')::numeric > 10;
```

---

## 🔍 Capacidades de Búsqueda

### Búsquedas Soportadas

✅ **Nombre difuso** (pg_trgm): `Juan` encuentra `Juana`, `Juanito`  
✅ **Email parcial**: `@gmail` encuentra todos los Gmail  
✅ **Teléfono**: Búsqueda exacta o parcial  
✅ **Campos personalizados**: Operadores JSONB nativos  
✅ **Rangos de fecha**: `BETWEEN`, comparaciones  
✅ **Múltiples criterios**: AND/OR complejos  
✅ **Estadísticas**: Agregaciones, GROUP BY  
✅ **Full-text search**: Preparado para tsvector  

---

## ⚡ Performance

### Índices Clave

```sql
-- Búsqueda difusa de nombre
CREATE INDEX idx_clientes_nombre_trgm ON clientes USING gin(nombre gin_trgm_ops);

-- Búsqueda en JSONB
CREATE INDEX idx_clientes_datos_personalizados ON clientes USING gin(datos_personalizados);

-- Búsqueda por tienda y estado (multi-tenant)
CREATE INDEX idx_clientes_tienda_estado ON clientes(tienda_id, estado);

-- Soft delete
CREATE INDEX idx_clientes_deleted_at ON clientes(deleted_at) WHERE deleted_at IS NULL;
```

### Triggers Automáticos

```sql
-- 1. Actualizar updated_at en modificaciones
CREATE TRIGGER trigger_clientes_updated_at
    BEFORE UPDATE ON clientes
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

-- 2. Recalcular estadísticas después de cada visita
CREATE TRIGGER trigger_visitas_estadisticas
    AFTER INSERT ON visitas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_estadisticas_cliente();
    
-- 3. Auditar cambios de rangos
CREATE TRIGGER trigger_clientes_cambio_rango
    AFTER UPDATE ON clientes
    FOR EACH ROW
    EXECUTE FUNCTION auditar_cambio_rango();
```

---

## 🛠️ Tecnologías Requeridas

- **PostgreSQL**: 14+ (recomendado 15+)
- **Extensiones**:
  - `uuid-ossp` - UUIDs
  - `pg_trgm` - Búsqueda difusa
  
```sql
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

---

## 📖 Documentación de Origen

Este esquema fue diseñado basándose en:

- **23 vistas documentadas** del sistema legacy japonés
- **Análisis completo** del módulo de gestión de clientes (顧客管理)
- **4 flujos principales** identificados
- **5 categorías personalizadas** de ejemplo
- **14 tipos de campo** soportados

Ver documentación del sistema legacy:
- [docs/mockups/modulo-clientes/DOCUMENTACION_LEGACY.md](../mockups/modulo-clientes/DOCUMENTACION_LEGACY.md)
- [docs/mockups/modulo-clientes/FLUJOS_DETALLADOS.md](../mockups/modulo-clientes/FLUJOS_DETALLADOS.md)
- [docs/mockups/modulo-clientes/INDICE_CAPTURAS.md](../mockups/modulo-clientes/INDICE_CAPTURAS.md)

---

## 🎯 Próximos Pasos

### Fase 1: Implementación de BD ✅
- [x] Diseñar esquema completo
- [x] Crear scripts SQL
- [x] Documentar con diagramas
- [x] Proveer ejemplos de queries

### Fase 2: Desarrollo Backend (Pendiente)
- [ ] Crear Laravel migrations del esquema
- [ ] Implementar Eloquent models
- [ ] Crear seeders con datos de ejemplo
- [ ] Implementar API REST para CRUD
- [ ] Validaciones de campos personalizados
- [ ] Sistema de búsqueda avanzada

### Fase 3: Testing (Pendiente)
- [ ] Tests unitarios de models
- [ ] Tests de performance de queries
- [ ] Tests de integridad referencial
- [ ] Tests de triggers

### Fase 4: Migración (Pendiente)
- [ ] Script de migración desde sistema legacy
- [ ] Validación de datos migrados
- [ ] Rollback plan

---

## 📞 Información del Proyecto

**Proyecto**: VivaTech CRM System  
**Cliente**: VivaTech (Osaka, Japón)  
**Documentado por**: Antony Brahams Paredes Paulino  
**GitHub**: [@BrahamsCode](https://github.com/BrahamsCode)  
**Empresa**: BrahamsCompany  
**Fecha**: 2026-10-01  
**Versión**: 1.0  

---

## 📝 Changelog

### v1.0 - 2026-10-01
- ✅ Esquema inicial completo basado en 23 vistas del legacy
- ✅ 13 tablas definidas con relaciones
- ✅ Sistema de campos personalizados con 3 capas
- ✅ Ejemplo completo de categoría "Mascota"
- ✅ Triggers automáticos para estadísticas
- ✅ Índices GIN optimizados para JSONB
- ✅ Scripts SQL ejecutables
- ✅ 27+ queries de ejemplo
- ✅ Diagramas ERD en Mermaid
- ✅ Documentación completa

---

## 🔗 Enlaces Rápidos

- **[Esquema Completo](./ESQUEMA_BD_MODULO_CLIENTES.md)** - Documento principal
- **[Diagramas ERD](./DIAGRAMA_ERD.md)** - Visualizaciones
- **[Queries y Casos de Uso](./QUERIES_Y_CASOS_DE_USO.md)** - Ejemplos prácticos
- **[Sistema Legacy](../mockups/modulo-clientes/DOCUMENTACION_LEGACY.md)** - Origen del diseño

---

**🚀 Empezar por → [ESQUEMA_BD_MODULO_CLIENTES.md](./ESQUEMA_BD_MODULO_CLIENTES.md)**

---

*Diseño optimizado para PostgreSQL 14+ con JSONB, índices GIN, triggers automáticos y arquitectura multi-tenant*
