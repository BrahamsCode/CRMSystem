# Diagrama ERD - Base de Datos Módulo Clientes

## Vista Completa del Sistema

```mermaid
erDiagram
    %% ENTIDADES PRINCIPALES
    TIENDAS ||--o{ CLIENTES : "pertenece a"
    TIENDAS ||--o{ GRUPOS_CLIENTES : "configura"
    TIENDAS ||--o{ RANGOS_MONTO : "define"
    TIENDAS ||--o{ RANGOS_VISITAS : "define"
    TIENDAS ||--o{ CATEGORIAS_PERSONALIZADAS : "crea"
    TIENDAS ||--o{ MOTIVOS_VISITA : "define"
    TIENDAS ||--o{ VISITAS : "registra"
    
    %% RELACIONES CLIENTES
    CLIENTES }o--|| GRUPOS_CLIENTES : "pertenece a"
    CLIENTES }o--|| RANGOS_MONTO : "clasificado por"
    CLIENTES }o--|| RANGOS_VISITAS : "clasificado por"
    CLIENTES ||--o{ VISITAS : "tiene"
    CLIENTES ||--o{ VALORES_PERSONALIZADOS : "almacena"
    CLIENTES ||--o{ HISTORIAL_RANGOS : "audita"
    
    %% SISTEMA DE CAMPOS PERSONALIZADOS
    CATEGORIAS_PERSONALIZADAS ||--o{ CAMPOS_PERSONALIZADOS : "contiene"
    CAMPOS_PERSONALIZADOS ||--o{ OPCIONES_CAMPO : "define opciones"
    CATEGORIAS_PERSONALIZADAS ||--o{ VALORES_PERSONALIZADOS : "agrupa valores"
    CAMPOS_PERSONALIZADOS ||--o{ VALORES_PERSONALIZADOS : "referencia"
    
    %% VISITAS Y MOTIVOS
    VISITAS }o--|| MOTIVOS_VISITA : "tiene motivo"
    
    %% DEFINICIONES DE TABLAS
    
    TIENDAS {
        bigserial id PK
        varchar nombre
        varchar codigo UK
        boolean activo
        jsonb configuracion
        timestamptz created_at
        timestamptz updated_at
    }
    
    CLIENTES {
        bigserial id PK
        bigint tienda_id FK
        varchar numero_cliente UK "📌 identificador único"
        varchar numero_gestion
        tipo_persona_enum tipo_persona
        varchar nombre
        varchar nombre_kana
        varchar apellido
        varchar apellido_kana
        varchar email_principal
        varchar email_secundario
        varchar telefono
        varchar telefono_movil
        varchar fax
        varchar codigo_postal
        varchar prefectura
        varchar ciudad
        text direccion
        varchar edificio
        date fecha_nacimiento
        sexo_enum sexo
        varchar tipo_sangre
        varchar ocupacion
        varchar empresa_nombre
        varchar empresa_telefono
        varchar empresa_departamento
        boolean tiene_conyuge
        date fecha_aniversario
        estado_cliente_enum estado
        boolean recibe_newsletter
        integer visitas_count "⚡ calculado automáticamente"
        decimal monto_total_gastado "⚡ calculado automáticamente"
        decimal monto_promedio "⚡ calculado automáticamente"
        date ultima_visita "⚡ calculado automáticamente"
        integer promedio_dias_visita "⚡ calculado automáticamente"
        bigint grupo_id FK
        bigint rango_monto_id FK
        bigint rango_visitas_id FK
        jsonb datos_personalizados "💾 JSONB para campos dinámicos"
        timestamptz created_at
        timestamptz updated_at
        timestamptz deleted_at "🗑️ soft delete"
    }
    
    GRUPOS_CLIENTES {
        bigserial id PK
        bigint tienda_id FK
        varchar nombre
        text descripcion
        boolean es_default
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    RANGOS_MONTO {
        bigserial id PK
        bigint tienda_id FK
        varchar nombre
        text descripcion
        decimal monto_minimo
        decimal monto_maximo
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    RANGOS_VISITAS {
        bigserial id PK
        bigint tienda_id FK
        varchar nombre
        text descripcion
        integer visitas_minimas
        integer visitas_maximas
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    CATEGORIAS_PERSONALIZADAS {
        bigserial id PK
        bigint tienda_id FK
        varchar nombre "ej: Mascota, Familia, Cartel"
        varchar slug UK
        text comentario
        integer columnas_visualizacion "1, 2 o 3"
        tipo_registro_enum tipo_registro "normal o multiple"
        boolean habilitar_busqueda
        boolean habilitar_visualizacion
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    CAMPOS_PERSONALIZADOS {
        bigserial id PK
        bigint categoria_id FK
        varchar nombre "ej: Nombre, Tipo, Peso"
        varchar slug UK
        tipo_campo_enum tipo_campo "14 tipos disponibles"
        text comentario
        varchar unidad "ej: Kg, cm, años"
        varchar clase_estilo "CSS class"
        boolean es_requerido
        boolean habilitar_busqueda
        boolean habilitar_newsletter
        ambito_visualizacion_enum ambito_visualizacion
        jsonb configuracion_extra
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    OPCIONES_CAMPO {
        bigserial id PK
        bigint campo_id FK
        varchar etiqueta "ej: Perro, Gato"
        varchar valor
        integer orden
        boolean activo
    }
    
    VALORES_PERSONALIZADOS {
        bigserial id PK
        bigint cliente_id FK
        bigint categoria_id FK
        jsonb valores "💾 valores del registro"
        integer registro_numero "para categorías tipo multiple"
        timestamptz created_at
        timestamptz updated_at
    }
    
    VISITAS {
        bigserial id PK
        bigint cliente_id FK
        bigint tienda_id FK
        bigint motivo_id FK
        timestamptz fecha_visita
        decimal monto_consumo
        text notas
        varchar procesado_por
        timestamptz created_at
    }
    
    MOTIVOS_VISITA {
        bigserial id PK
        bigint tienda_id FK
        varchar nombre
        text descripcion
        integer orden
        boolean activo
        timestamptz created_at
        timestamptz updated_at
    }
    
    HISTORIAL_RANGOS {
        bigserial id PK
        bigint cliente_id FK
        tipo_rango_enum tipo_rango "monto o visitas"
        bigint rango_anterior_id
        bigint rango_nuevo_id
        varchar razon_cambio
        timestamptz fecha_cambio
    }
```

---

## Diagrama Simplificado - Core Entities

```mermaid
graph TD
    A[TIENDAS] --> B[CLIENTES]
    B --> C[VISITAS]
    B --> D[GRUPOS]
    B --> E[RANGOS MONTO]
    B --> F[RANGOS VISITAS]
    B --> G[DATOS PERSONALIZADOS JSONB]
    
    H[CATEGORÍAS PERSONALIZADAS] --> I[CAMPOS PERSONALIZADOS]
    I --> J[OPCIONES CAMPO]
    
    B -.almacena.-> G
    H -.define estructura.-> G
    
    style A fill:#e1f5ff
    style B fill:#fff4e1
    style G fill:#ffe1f5
    style H fill:#e1ffe1
```

---

## Flujo de Datos - Campos Personalizados

```mermaid
sequenceDiagram
    participant Admin
    participant CategoriasPers as CATEGORÍAS_PERSONALIZADAS
    participant CamposPers as CAMPOS_PERSONALIZADOS
    participant OpcionesCampo as OPCIONES_CAMPO
    participant Cliente as CLIENTES
    participant ValoresPers as VALORES_PERSONALIZADOS
    
    Admin->>CategoriasPers: 1. Crear categoría "Mascota"
    Note over CategoriasPers: tipo_registro: normal<br/>columnas: 3
    
    Admin->>CamposPers: 2. Crear campo "Nombre" (texto)
    Admin->>CamposPers: 3. Crear campo "Tipo" (select)
    Admin->>CamposPers: 4. Crear campo "Peso" (numérico)
    
    Admin->>OpcionesCampo: 5. Agregar opciones a "Tipo"
    Note over OpcionesCampo: Perro, Gato, Conejo,<br/>Hamster, Otros
    
    Admin->>Cliente: 6. Registrar cliente
    Note over Cliente: datos_personalizados = {<br/>"mascota": {<br/>"nombre": "Firulais",<br/>"tipo": "Perro",<br/>"peso": 15<br/>}<br/>}
    
    alt Categoría tipo "múltiple"
        Admin->>ValoresPers: 7. Crear múltiples registros
        Note over ValoresPers: registro_numero: 1, 2, 3...
    end
```

---

## Diagrama de Arquitectura de 3 Capas

```mermaid
graph TB
    subgraph Capa1["CAPA 1: METADATA - Definición"]
        CAT[CATEGORÍAS_PERSONALIZADAS<br/>📋 Mascota, Familia, Cartel]
    end
    
    subgraph Capa2["CAPA 2: SCHEMA - Estructura"]
        CAMPOS[CAMPOS_PERSONALIZADOS<br/>📝 Nombre, Tipo, Peso]
        OPCIONES[OPCIONES_CAMPO<br/>🔘 Perro, Gato, Conejo]
    end
    
    subgraph Capa3["CAPA 3: DATA - Valores"]
        CLIENTES_JSON[CLIENTES.datos_personalizados<br/>💾 JSONB para tipo normal]
        VALORES[VALORES_PERSONALIZADOS<br/>💾 Tabla para tipo múltiple]
    end
    
    CAT --> CAMPOS
    CAMPOS --> OPCIONES
    CAMPOS --> CLIENTES_JSON
    CAMPOS --> VALORES
    CAT --> VALORES
    
    style CAT fill:#e1f5ff
    style CAMPOS fill:#ffe1f5
    style OPCIONES fill:#ffe1f5
    style CLIENTES_JSON fill:#fff4e1
    style VALORES fill:#fff4e1
```

---

## Tipos ENUM del Sistema

```mermaid
graph LR
    subgraph TiposEnum["TIPOS ENUM PostgreSQL"]
        E1[tipo_persona_enum<br/>individual, corporativo]
        E2[estado_cliente_enum<br/>activo, retirado, eliminado]
        E3[sexo_enum<br/>masculino, femenino, otro]
        E4[tipo_registro_enum<br/>normal, multiple]
        E5[tipo_rango_enum<br/>monto, visitas]
        E6[tipo_campo_enum<br/>14 tipos de campo]
        E7[ambito_visualizacion_enum<br/>gestion, ambas, oculto]
    end
    
    style E1 fill:#e1f5ff
    style E2 fill:#ffe1e1
    style E3 fill:#f5e1ff
    style E4 fill:#e1ffe1
    style E5 fill:#fff4e1
    style E6 fill:#ffe1f5
    style E7 fill:#e1fff4
```

### Detalle de tipo_campo_enum (14 tipos)

```
┌─────────────────────────────────────────────────────────┐
│ TIPOS DE CAMPO PERSONALIZADOS                          │
├─────────────────────────────────────────────────────────┤
│ 1.  texto              → Texto simple                  │
│ 2.  email              → Email con validación          │
│ 3.  alfanumerico       → Alfanumérico                  │
│ 4.  numerico           → Número decimal                │
│ 5.  textarea           → Texto largo                   │
│ 6.  checkbox           → Casilla de verificación       │
│ 7.  select             → Lista desplegable             │
│ 8.  radio              → Botones de radio              │
│ 9.  anio               → Solo año (YYYY)               │
│ 10. anio_mes           → Año y mes (YYYY-MM)           │
│ 11. anio_mes_dia       → Fecha completa (YYYY-MM-DD)   │
│ 12. mes_dia            → Mes y día (MM-DD)             │
│ 13. fecha_referencia   → Fecha con referencia          │
│ 14. tabla              → Estructura tabular            │
│ 15. menu               → Menú de opciones              │
└─────────────────────────────────────────────────────────┘
```

---

## Índices Clave del Sistema

```mermaid
graph TD
    subgraph IndicesGIN["Índices GIN (Full-Text + JSONB)"]
        I1[idx_clientes_nombre_trgm<br/>Búsqueda difusa de nombre]
        I2[idx_clientes_apellido_trgm<br/>Búsqueda difusa de apellido]
        I3[idx_clientes_datos_personalizados<br/>Búsqueda en JSONB]
        I4[idx_valores_jsonb<br/>Búsqueda en valores múltiples]
    end
    
    subgraph IndicesBTree["Índices B-Tree (Estándar)"]
        I5[idx_clientes_tienda<br/>Filtro por tienda]
        I6[idx_clientes_estado<br/>Filtro por estado]
        I7[idx_clientes_rangos<br/>Compuesto monto+visitas]
        I8[idx_visitas_fecha<br/>Ordenado DESC]
    end
    
    style I1 fill:#e1f5ff
    style I2 fill:#e1f5ff
    style I3 fill:#ffe1f5
    style I4 fill:#ffe1f5
    style I5 fill:#fff4e1
    style I6 fill:#fff4e1
    style I7 fill:#fff4e1
    style I8 fill:#fff4e1
```

---

## Triggers Automáticos

```mermaid
graph LR
    subgraph Triggers["TRIGGERS DEL SISTEMA"]
        T1[actualizar_updated_at<br/>📅 Auto-actualiza timestamps]
        T2[actualizar_estadisticas_cliente<br/>📊 Recalcula stats en cada visita]
        T3[auditar_cambio_rango<br/>📝 Registra cambios de rangos]
    end
    
    VISITAS -->|INSERT| T2
    CLIENTES -->|UPDATE| T1
    CLIENTES -->|UPDATE rango| T3
    T3 --> HISTORIAL_RANGOS
    
    style T1 fill:#e1f5ff
    style T2 fill:#ffe1f5
    style T3 fill:#fff4e1
```

---

## Ejemplo Práctico: Cliente con Mascota

### Estructura de Datos

```json
{
  "cliente_id": 1,
  "nombre": "Juan Pérez",
  "datos_personalizados": {
    "mascota": {
      "nombre": "Firulais",
      "tipo": "Perro",
      "peso": 15
    },
    "familia": {
      "conyuge": "Sí",
      "fecha_aniversario": "2015-06-20"
    }
  }
}
```

### Query para Buscar

```sql
-- Buscar clientes con mascota tipo "Perro"
SELECT 
    id,
    nombre,
    apellido,
    datos_personalizados -> 'mascota' ->> 'nombre' as mascota_nombre,
    datos_personalizados -> 'mascota' ->> 'tipo' as mascota_tipo,
    datos_personalizados -> 'mascota' ->> 'peso' as mascota_peso
FROM clientes
WHERE datos_personalizados -> 'mascota' ->> 'tipo' = 'Perro'
  AND (datos_personalizados -> 'mascota' ->> 'peso')::numeric > 10
  AND estado = 'activo';
```

---

## Estadísticas de Complejidad

```
┌──────────────────────────────────────────────────────┐
│ MÉTRICAS DEL ESQUEMA                                │
├──────────────────────────────────────────────────────┤
│ Tablas totales:              13                     │
│ Tipos ENUM:                  7                      │
│ Foreign Keys:                19                     │
│ Índices GIN:                 6                      │
│ Índices B-Tree:              25+                    │
│ Triggers:                    6                      │
│ Funciones:                   4                      │
│ Constraints CHECK:           8                      │
│ Constraints UNIQUE:          12                     │
├──────────────────────────────────────────────────────┤
│ Campos en tabla CLIENTES:    ~55                    │
│ Tipos de campo dinámicos:    15                     │
│ Categorías de ejemplo:       5 (Mascota, etc.)     │
└──────────────────────────────────────────────────────┘
```

---

## Leyenda de Símbolos

| Símbolo | Significado |
|---------|-------------|
| 📌 | Campo clave/identificador |
| ⚡ | Campo calculado automáticamente |
| 💾 | Almacenamiento JSONB |
| 🗑️ | Soft delete (deleted_at) |
| 🔘 | Opciones predefinidas |
| 📋 | Metadata/configuración |
| 📝 | Definición de estructura |
| 📊 | Estadísticas/métricas |
| 📅 | Timestamp automático |

---

**Creado**: 2026-10-01  
**Autor**: Antony Brahams Paredes Paulino  
**Proyecto**: VivaTech CRM System  
**Base de datos**: PostgreSQL 14+
