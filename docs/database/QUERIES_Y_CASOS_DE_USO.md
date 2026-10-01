# Queries y Casos de Uso - Base de Datos Módulo Clientes

## 📋 Índice

1. [Búsquedas Básicas](#búsquedas-básicas)
2. [Búsquedas en Campos Personalizados](#búsquedas-en-campos-personalizados)
3. [Búsqueda Avanzada Multi-criterio](#búsqueda-avanzada-multi-criterio)
4. [Estadísticas y Reportes](#estadísticas-y-reportes)
5. [Gestión de Rangos](#gestión-de-rangos)
6. [Gestión de Visitas](#gestión-de-visitas)
7. [Consultas de Performance](#consultas-de-performance)
8. [Casos de Uso Completos](#casos-de-uso-completos)

---

## Búsquedas Básicas

### 1. Buscar cliente por número

```sql
SELECT * FROM clientes
WHERE numero_cliente = 'CLI-001';
```

### 2. Buscar por nombre (búsqueda exacta)

```sql
SELECT id, numero_cliente, nombre, apellido, email_principal, telefono
FROM clientes
WHERE nombre = 'Juan'
  AND deleted_at IS NULL;
```

### 3. Buscar por nombre (búsqueda difusa - aproximada)

```sql
-- Requiere extensión pg_trgm
SELECT 
    id, 
    numero_cliente, 
    nombre, 
    apellido,
    similarity(nombre, 'Juan') as similitud
FROM clientes
WHERE nombre % 'Juan'  -- Operador de similitud
ORDER BY similitud DESC
LIMIT 10;
```

### 4. Buscar por email o teléfono

```sql
SELECT * FROM clientes
WHERE email_principal ILIKE '%juan@example.com%'
   OR telefono = '123-456-7890'
  AND deleted_at IS NULL;
```

### 5. Listar clientes activos de una tienda

```sql
SELECT 
    numero_cliente,
    nombre || ' ' || COALESCE(apellido, '') as nombre_completo,
    email_principal,
    telefono,
    estado,
    ultima_visita
FROM clientes
WHERE tienda_id = 1
  AND estado = 'activo'
  AND deleted_at IS NULL
ORDER BY ultima_visita DESC NULLS LAST;
```

---

## Búsquedas en Campos Personalizados

### 6. Buscar clientes con mascota tipo "Perro"

```sql
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    c.datos_personalizados -> 'mascota' ->> 'nombre' as mascota_nombre,
    c.datos_personalizados -> 'mascota' ->> 'tipo' as mascota_tipo,
    c.datos_personalizados -> 'mascota' ->> 'peso' as mascota_peso
FROM clientes c
WHERE c.datos_personalizados -> 'mascota' ->> 'tipo' = 'Perro'
  AND c.deleted_at IS NULL;
```

### 7. Buscar por peso de mascota (campo numérico en JSONB)

```sql
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    c.datos_personalizados -> 'mascota' ->> 'nombre' as mascota,
    (c.datos_personalizados -> 'mascota' ->> 'peso')::numeric as peso_kg
FROM clientes c
WHERE (c.datos_personalizados -> 'mascota' ->> 'peso')::numeric > 10
  AND (c.datos_personalizados -> 'mascota' ->> 'peso')::numeric < 50
  AND c.deleted_at IS NULL
ORDER BY peso_kg DESC;
```

### 8. Buscar clientes que tienen mascota (cualquier tipo)

```sql
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    c.datos_personalizados -> 'mascota' as info_mascota
FROM clientes c
WHERE c.datos_personalizados ? 'mascota'  -- Operador de existencia de clave
  AND c.deleted_at IS NULL;
```

### 9. Buscar por múltiples categorías personalizadas

```sql
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    c.datos_personalizados -> 'mascota' ->> 'nombre' as mascota,
    c.datos_personalizados -> 'familia' ->> 'conyuge' as tiene_conyuge,
    c.datos_personalizados -> 'cartel' ->> 'mensaje' as mensaje_cartel
FROM clientes c
WHERE c.datos_personalizados ? 'mascota'
  AND c.datos_personalizados -> 'familia' ->> 'conyuge' = 'Sí'
  AND c.deleted_at IS NULL;
```

### 10. Clientes con múltiples mascotas (tipo múltiple)

```sql
SELECT 
    c.id,
    c.nombre,
    c.apellido,
    COUNT(vp.id) as cantidad_mascotas,
    jsonb_agg(vp.valores ORDER BY vp.registro_numero) as mascotas
FROM clientes c
JOIN valores_personalizados vp ON c.id = vp.cliente_id
JOIN categorias_personalizadas cat ON vp.categoria_id = cat.id
WHERE cat.slug = 'mascota'
  AND c.deleted_at IS NULL
GROUP BY c.id, c.nombre, c.apellido
HAVING COUNT(vp.id) >= 2
ORDER BY cantidad_mascotas DESC;
```

---

## Búsqueda Avanzada Multi-criterio

### 11. Búsqueda avanzada completa (similar a la vista 04 del legacy)

```sql
SELECT 
    c.id,
    c.numero_cliente,
    c.nombre || ' ' || COALESCE(c.apellido, '') as nombre_completo,
    c.email_principal,
    c.telefono,
    c.fecha_nacimiento,
    c.ciudad,
    c.ultima_visita,
    c.monto_total_gastado,
    g.nombre as grupo,
    rm.nombre as rango_monto,
    rv.nombre as rango_visitas,
    c.datos_personalizados -> 'mascota' ->> 'tipo' as tipo_mascota
FROM clientes c
LEFT JOIN grupos_clientes g ON c.grupo_id = g.id
LEFT JOIN rangos_monto rm ON c.rango_monto_id = rm.id
LEFT JOIN rangos_visitas rv ON c.rango_visitas_id = rv.id
WHERE c.tienda_id = 1
  AND c.deleted_at IS NULL
  -- Criterios básicos
  AND (
      c.nombre ILIKE '%Juan%'
      OR c.apellido ILIKE '%Perez%'
      OR c.email_principal ILIKE '%juan%'
  )
  -- Criterios demográficos
  AND c.sexo = 'masculino'
  AND c.fecha_nacimiento BETWEEN '1980-01-01' AND '1990-12-31'
  -- Criterios de ubicación
  AND c.prefectura IN ('Tokyo', 'Osaka')
  -- Criterios de estado
  AND c.estado = 'activo'
  AND c.recibe_newsletter = true
  -- Criterios de actividad
  AND c.monto_total_gastado > 10000
  AND c.visitas_count >= 5
  AND c.ultima_visita >= CURRENT_DATE - INTERVAL '6 months'
  -- Criterios de clasificación
  AND c.grupo_id = 1
  AND c.rango_monto_id IN (1, 2)
  -- Criterios en campos personalizados
  AND c.datos_personalizados -> 'mascota' ->> 'tipo' IN ('Perro', 'Gato')
ORDER BY c.ultima_visita DESC NULLS LAST
LIMIT 100;
```

### 12. Búsqueda por rango de fechas de visita

```sql
SELECT DISTINCT
    c.id,
    c.numero_cliente,
    c.nombre,
    c.apellido,
    MAX(v.fecha_visita) as ultima_visita,
    COUNT(v.id) as total_visitas,
    SUM(v.monto_consumo) as total_gastado
FROM clientes c
JOIN visitas v ON c.id = v.cliente_id
WHERE v.fecha_visita BETWEEN '2025-01-01' AND '2025-12-31'
  AND c.deleted_at IS NULL
GROUP BY c.id, c.numero_cliente, c.nombre, c.apellido
HAVING COUNT(v.id) >= 3
ORDER BY total_gastado DESC;
```

---

## Estadísticas y Reportes

### 13. Top 10 clientes por monto gastado

```sql
SELECT 
    c.numero_cliente,
    c.nombre || ' ' || COALESCE(c.apellido, '') as nombre_completo,
    c.monto_total_gastado,
    c.visitas_count,
    c.monto_promedio,
    c.ultima_visita,
    g.nombre as grupo,
    rm.nombre as rango
FROM clientes c
LEFT JOIN grupos_clientes g ON c.grupo_id = g.id
LEFT JOIN rangos_monto rm ON c.rango_monto_id = rm.id
WHERE c.tienda_id = 1
  AND c.deleted_at IS NULL
ORDER BY c.monto_total_gastado DESC
LIMIT 10;
```

### 14. Estadísticas por grupo de clientes

```sql
SELECT 
    g.nombre as grupo,
    COUNT(c.id) as total_clientes,
    SUM(c.visitas_count) as total_visitas,
    SUM(c.monto_total_gastado) as monto_total,
    AVG(c.monto_promedio) as promedio_por_cliente,
    MAX(c.monto_total_gastado) as mayor_gasto
FROM grupos_clientes g
LEFT JOIN clientes c ON g.id = c.grupo_id AND c.deleted_at IS NULL
WHERE g.tienda_id = 1
  AND g.activo = true
GROUP BY g.id, g.nombre
ORDER BY total_clientes DESC;
```

### 15. Distribución de clientes por rango de monto

```sql
SELECT 
    rm.nombre as rango,
    rm.monto_minimo,
    rm.monto_maximo,
    COUNT(c.id) as cantidad_clientes,
    ROUND(
        COUNT(c.id)::numeric * 100.0 / 
        SUM(COUNT(c.id)) OVER (), 
        2
    ) as porcentaje
FROM rangos_monto rm
LEFT JOIN clientes c ON rm.id = c.rango_monto_id AND c.deleted_at IS NULL
WHERE rm.tienda_id = 1
  AND rm.activo = true
GROUP BY rm.id, rm.nombre, rm.monto_minimo, rm.monto_maximo
ORDER BY rm.orden;
```

### 16. Análisis de tipos de mascota

```sql
SELECT 
    c.datos_personalizados -> 'mascota' ->> 'tipo' as tipo_mascota,
    COUNT(*) as cantidad,
    AVG((c.datos_personalizados -> 'mascota' ->> 'peso')::numeric) as peso_promedio,
    MIN((c.datos_personalizados -> 'mascota' ->> 'peso')::numeric) as peso_minimo,
    MAX((c.datos_personalizados -> 'mascota' ->> 'peso')::numeric) as peso_maximo
FROM clientes c
WHERE c.datos_personalizados ? 'mascota'
  AND c.datos_personalizados -> 'mascota' ->> 'tipo' IS NOT NULL
  AND c.deleted_at IS NULL
GROUP BY c.datos_personalizados -> 'mascota' ->> 'tipo'
ORDER BY cantidad DESC;
```

### 17. Clientes inactivos (sin visitas recientes)

```sql
SELECT 
    c.id,
    c.numero_cliente,
    c.nombre,
    c.apellido,
    c.email_principal,
    c.telefono,
    c.ultima_visita,
    CURRENT_DATE - c.ultima_visita::date as dias_sin_visitar
FROM clientes c
WHERE c.tienda_id = 1
  AND c.estado = 'activo'
  AND c.deleted_at IS NULL
  AND (
      c.ultima_visita IS NULL 
      OR c.ultima_visita < CURRENT_DATE - INTERVAL '6 months'
  )
ORDER BY c.ultima_visita ASC NULLS FIRST
LIMIT 50;
```

---

## Gestión de Rangos

### 18. Asignar rangos por monto automáticamente

```sql
UPDATE clientes c
SET rango_monto_id = (
    SELECT rm.id
    FROM rangos_monto rm
    WHERE rm.tienda_id = c.tienda_id
      AND rm.activo = true
      AND c.monto_total_gastado >= rm.monto_minimo
      AND (rm.monto_maximo IS NULL OR c.monto_total_gastado <= rm.monto_maximo)
    ORDER BY rm.monto_minimo DESC
    LIMIT 1
)
WHERE c.tienda_id = 1
  AND c.deleted_at IS NULL;
```

### 19. Asignar rangos por frecuencia de visitas

```sql
UPDATE clientes c
SET rango_visitas_id = (
    SELECT rv.id
    FROM rangos_visitas rv
    WHERE rv.tienda_id = c.tienda_id
      AND rv.activo = true
      AND c.visitas_count >= rv.visitas_minimas
      AND (rv.visitas_maximas IS NULL OR c.visitas_count <= rv.visitas_maximas)
    ORDER BY rv.visitas_minimas DESC
    LIMIT 1
)
WHERE c.tienda_id = 1
  AND c.deleted_at IS NULL;
```

### 20. Ver historial de cambios de rangos de un cliente

```sql
SELECT 
    hr.id,
    hr.tipo_rango,
    CASE 
        WHEN hr.tipo_rango = 'monto' THEN rm_ant.nombre
        WHEN hr.tipo_rango = 'visitas' THEN rv_ant.nombre
    END as rango_anterior,
    CASE 
        WHEN hr.tipo_rango = 'monto' THEN rm_nue.nombre
        WHEN hr.tipo_rango = 'visitas' THEN rv_nue.nombre
    END as rango_nuevo,
    hr.razon_cambio,
    hr.fecha_cambio
FROM historial_rangos hr
LEFT JOIN rangos_monto rm_ant ON hr.rango_anterior_id = rm_ant.id AND hr.tipo_rango = 'monto'
LEFT JOIN rangos_monto rm_nue ON hr.rango_nuevo_id = rm_nue.id AND hr.tipo_rango = 'monto'
LEFT JOIN rangos_visitas rv_ant ON hr.rango_anterior_id = rv_ant.id AND hr.tipo_rango = 'visitas'
LEFT JOIN rangos_visitas rv_nue ON hr.rango_nuevo_id = rv_nue.id AND hr.tipo_rango = 'visitas'
WHERE hr.cliente_id = 1
ORDER BY hr.fecha_cambio DESC;
```

---

## Gestión de Visitas

### 21. Registrar una nueva visita

```sql
INSERT INTO visitas (cliente_id, tienda_id, motivo_id, fecha_visita, monto_consumo, notas, procesado_por)
VALUES (
    1,                          -- cliente_id
    1,                          -- tienda_id
    2,                          -- motivo_id (ej: "Compra")
    NOW(),                      -- fecha_visita
    5000.00,                    -- monto_consumo
    'Cliente compró producto X', -- notas
    'Juan Staff'                -- procesado_por
);

-- Las estadísticas del cliente se actualizan automáticamente por trigger
```

### 22. Obtener últimas visitas de un cliente

```sql
SELECT 
    v.id,
    v.fecha_visita,
    v.monto_consumo,
    mv.nombre as motivo,
    v.notas,
    v.procesado_por
FROM visitas v
LEFT JOIN motivos_visita mv ON v.motivo_id = mv.id
WHERE v.cliente_id = 1
ORDER BY v.fecha_visita DESC
LIMIT 10;
```

### 23. Visitas por día/mes/año

```sql
-- Visitas por día
SELECT 
    DATE(v.fecha_visita) as fecha,
    COUNT(*) as total_visitas,
    SUM(v.monto_consumo) as monto_total
FROM visitas v
WHERE v.tienda_id = 1
  AND v.fecha_visita >= CURRENT_DATE - INTERVAL '30 days'
GROUP BY DATE(v.fecha_visita)
ORDER BY fecha DESC;

-- Visitas por mes
SELECT 
    TO_CHAR(v.fecha_visita, 'YYYY-MM') as mes,
    COUNT(*) as total_visitas,
    COUNT(DISTINCT v.cliente_id) as clientes_unicos,
    SUM(v.monto_consumo) as monto_total,
    AVG(v.monto_consumo) as monto_promedio
FROM visitas v
WHERE v.tienda_id = 1
  AND v.fecha_visita >= CURRENT_DATE - INTERVAL '12 months'
GROUP BY TO_CHAR(v.fecha_visita, 'YYYY-MM')
ORDER BY mes DESC;
```

### 24. Top motivos de visita

```sql
SELECT 
    mv.nombre as motivo,
    COUNT(v.id) as total_visitas,
    SUM(v.monto_consumo) as monto_total,
    AVG(v.monto_consumo) as monto_promedio
FROM motivos_visita mv
LEFT JOIN visitas v ON mv.id = v.motivo_id
WHERE mv.tienda_id = 1
  AND mv.activo = true
GROUP BY mv.id, mv.nombre
ORDER BY total_visitas DESC;
```

---

## Consultas de Performance

### 25. Usar vista materializada para búsqueda rápida

```sql
-- Consultar la vista materializada (más rápido)
SELECT * FROM vista_clientes_busqueda
WHERE nombre_completo ILIKE '%Juan%'
   OR email_principal ILIKE '%juan%'
ORDER BY monto_total_gastado DESC
LIMIT 20;

-- Refrescar la vista cuando sea necesario
REFRESH MATERIALIZED VIEW CONCURRENTLY vista_clientes_busqueda;
```

### 26. Optimizar búsqueda en JSONB con índice GIN

```sql
-- Esta query aprovecha el índice GIN en datos_personalizados
EXPLAIN ANALYZE
SELECT * FROM clientes
WHERE datos_personalizados @> '{"mascota": {"tipo": "Perro"}}'::jsonb
  AND deleted_at IS NULL;
```

### 27. Búsqueda full-text con pg_trgm

```sql
-- Búsqueda difusa rápida
SELECT 
    id, 
    numero_cliente, 
    nombre, 
    apellido,
    similarity(nombre || ' ' || COALESCE(apellido, ''), 'Juan Perez') as score
FROM clientes
WHERE (nombre || ' ' || COALESCE(apellido, '')) % 'Juan Perez'
ORDER BY score DESC
LIMIT 10;
```

---

## Casos de Uso Completos

### Caso 1: Registro Completo de Nuevo Cliente con Mascota

```sql
BEGIN;

-- 1. Insertar cliente
INSERT INTO clientes (
    tienda_id,
    numero_cliente,
    nombre,
    apellido,
    email_principal,
    telefono,
    codigo_postal,
    prefectura,
    ciudad,
    direccion,
    fecha_nacimiento,
    sexo,
    estado,
    grupo_id,
    datos_personalizados
)
VALUES (
    1,
    'CLI-' || LPAD(nextval('seq_numero_cliente')::text, 6, '0'),
    'María',
    'González',
    'maria.gonzalez@example.com',
    '987-654-3210',
    '100-0001',
    'Tokyo',
    'Chiyoda',
    'Marunouchi 1-1-1',
    '1985-03-15',
    'femenino',
    'activo',
    1,
    '{
        "mascota": {
            "nombre": "Michi",
            "tipo": "Gato",
            "peso": 4.5
        },
        "familia": {
            "conyuge": "Sí",
            "fecha_aniversario": "2010-06-20"
        }
    }'::jsonb
)
RETURNING id;

-- 2. Registrar primera visita
INSERT INTO visitas (cliente_id, tienda_id, motivo_id, fecha_visita, monto_consumo, procesado_por)
VALUES (
    currval('clientes_id_seq'),
    1,
    1,
    NOW(),
    0,
    'System'
);

COMMIT;
```

### Caso 2: Crear Categoría Personalizada "Vehículo"

```sql
BEGIN;

-- 1. Crear categoría
INSERT INTO categorias_personalizadas (
    tienda_id, nombre, slug, comentario, columnas_visualizacion, tipo_registro
)
VALUES (
    1,
    'Vehículo',
    'vehiculo',
    'Información del vehículo del cliente',
    3,
    'normal'
)
RETURNING id;

-- Supongamos que retorna id = 2

-- 2. Crear campos
INSERT INTO campos_personalizados (categoria_id, nombre, slug, tipo_campo, orden) VALUES
(2, 'Marca', 'marca', 'select', 1),
(2, 'Modelo', 'modelo', 'texto', 2),
(2, 'Año', 'anio', 'anio', 3),
(2, 'Matrícula', 'matricula', 'alfanumerico', 4),
(2, 'Color', 'color', 'texto', 5);

-- 3. Agregar opciones para campo "Marca"
INSERT INTO opciones_campo (campo_id, etiqueta, valor, orden)
SELECT id, 'Toyota', 'Toyota', 1 FROM campos_personalizados WHERE slug = 'marca' AND categoria_id = 2
UNION ALL
SELECT id, 'Honda', 'Honda', 2 FROM campos_personalizados WHERE slug = 'marca' AND categoria_id = 2
UNION ALL
SELECT id, 'Nissan', 'Nissan', 3 FROM campos_personalizados WHERE slug = 'marca' AND categoria_id = 2
UNION ALL
SELECT id, 'Mazda', 'Mazda', 4 FROM campos_personalizados WHERE slug = 'marca' AND categoria_id = 2
UNION ALL
SELECT id, 'Mitsubishi', 'Mitsubishi', 5 FROM campos_personalizados WHERE slug = 'marca' AND categoria_id = 2;

COMMIT;
```

### Caso 3: Cliente con Múltiples Mascotas (tipo múltiple)

```sql
-- Si la categoría "mascota" fuera tipo "multiple" en lugar de "normal"

-- Cliente tiene 2 mascotas
INSERT INTO valores_personalizados (cliente_id, categoria_id, valores, registro_numero) VALUES
(1, 1, '{"nombre": "Firulais", "tipo": "Perro", "peso": 15}'::jsonb, 1),
(1, 1, '{"nombre": "Michi", "tipo": "Gato", "peso": 4.5}'::jsonb, 2);

-- Consultar todas las mascotas del cliente
SELECT 
    c.nombre as cliente,
    vp.registro_numero,
    vp.valores ->> 'nombre' as nombre_mascota,
    vp.valores ->> 'tipo' as tipo,
    vp.valores ->> 'peso' as peso
FROM clientes c
JOIN valores_personalizados vp ON c.id = vp.cliente_id
JOIN categorias_personalizadas cat ON vp.categoria_id = cat.id
WHERE c.id = 1
  AND cat.slug = 'mascota'
ORDER BY vp.registro_numero;
```

### Caso 4: Exportar Clientes a CSV (simulado)

```sql
COPY (
    SELECT 
        c.numero_cliente,
        c.nombre || ' ' || COALESCE(c.apellido, '') as nombre_completo,
        c.email_principal,
        c.telefono,
        c.ciudad,
        c.estado,
        c.visitas_count,
        c.monto_total_gastado,
        c.ultima_visita,
        g.nombre as grupo,
        rm.nombre as rango_monto,
        c.datos_personalizados -> 'mascota' ->> 'tipo' as tipo_mascota
    FROM clientes c
    LEFT JOIN grupos_clientes g ON c.grupo_id = g.id
    LEFT JOIN rangos_monto rm ON c.rango_monto_id = rm.id
    WHERE c.tienda_id = 1
      AND c.deleted_at IS NULL
    ORDER BY c.numero_cliente
) TO '/tmp/clientes_export.csv' WITH CSV HEADER;
```

### Caso 5: Soft Delete y Recuperación

```sql
-- Soft delete (marcar como eliminado)
UPDATE clientes
SET deleted_at = NOW(),
    estado = 'eliminado'
WHERE id = 1;

-- Recuperar cliente
UPDATE clientes
SET deleted_at = NULL,
    estado = 'activo'
WHERE id = 1;

-- Listar clientes eliminados
SELECT 
    id,
    numero_cliente,
    nombre,
    apellido,
    deleted_at,
    EXTRACT(DAY FROM NOW() - deleted_at) as dias_eliminado
FROM clientes
WHERE deleted_at IS NOT NULL
ORDER BY deleted_at DESC;
```

---

## Funciones Útiles

### Función: Calcular próxima visita estimada

```sql
CREATE OR REPLACE FUNCTION calcular_proxima_visita(p_cliente_id BIGINT)
RETURNS DATE AS $$
DECLARE
    v_promedio_dias INTEGER;
    v_ultima_visita DATE;
BEGIN
    SELECT promedio_dias_visita, ultima_visita
    INTO v_promedio_dias, v_ultima_visita
    FROM clientes
    WHERE id = p_cliente_id;
    
    IF v_promedio_dias IS NULL OR v_ultima_visita IS NULL THEN
        RETURN NULL;
    END IF;
    
    RETURN v_ultima_visita + v_promedio_dias;
END;
$$ LANGUAGE plpgsql;

-- Usar la función
SELECT 
    id,
    nombre,
    ultima_visita,
    promedio_dias_visita,
    calcular_proxima_visita(id) as proxima_visita_estimada
FROM clientes
WHERE deleted_at IS NULL
  AND ultima_visita IS NOT NULL
LIMIT 10;
```

### Función: Validar estructura de campo personalizado

```sql
CREATE OR REPLACE FUNCTION validar_campo_personalizado(
    p_categoria_slug VARCHAR,
    p_datos JSONB
)
RETURNS BOOLEAN AS $$
DECLARE
    v_campos_requeridos RECORD;
    v_campo_nombre VARCHAR;
BEGIN
    -- Verificar que todos los campos requeridos estén presentes
    FOR v_campos_requeridos IN
        SELECT cp.slug
        FROM campos_personalizados cp
        JOIN categorias_personalizadas cat ON cp.categoria_id = cat.id
        WHERE cat.slug = p_categoria_slug
          AND cp.es_requerido = true
          AND cp.activo = true
    LOOP
        IF NOT (p_datos ? v_campos_requeridos.slug) THEN
            RETURN FALSE;
        END IF;
    END LOOP;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- Usar la función
SELECT validar_campo_personalizado(
    'mascota',
    '{"nombre": "Firulais", "tipo": "Perro"}'::jsonb
) as es_valido;
```

---

## Consejos de Performance

### 1. Usar EXPLAIN ANALYZE

```sql
EXPLAIN ANALYZE
SELECT * FROM clientes
WHERE datos_personalizados @> '{"mascota": {"tipo": "Perro"}}'::jsonb
  AND deleted_at IS NULL;
```

### 2. Vacuum y Analyze periódicos

```sql
-- Vacuum completo
VACUUM FULL ANALYZE clientes;

-- Solo analyze
ANALYZE clientes;
```

### 3. Monitorear queries lentas

```sql
-- Ver queries lentas (requiere pg_stat_statements)
SELECT 
    query,
    calls,
    total_time,
    mean_time,
    max_time
FROM pg_stat_statements
WHERE query LIKE '%clientes%'
ORDER BY mean_time DESC
LIMIT 10;
```

---

**Creado**: 2026-10-01  
**Autor**: Antony Brahams Paredes Paulino  
**Proyecto**: VivaTech CRM System  
**PostgreSQL**: 14+
