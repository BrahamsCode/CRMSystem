-- ============================================
-- ESQUEMA DE BASE DE DATOS - MÓDULO CLIENTES
-- Sistema CRM - PostgreSQL 14+
-- Proyecto: VivaTech CRM System
-- Fecha: 2026-10-01
-- Autor: Antony Brahams Paredes Paulino
-- ============================================

-- ============================================
-- EXTENSIONES
-- ============================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- ============================================
-- TIPOS ENUM
-- ============================================

CREATE TYPE tipo_persona_enum AS ENUM ('individual', 'corporativo');
CREATE TYPE estado_cliente_enum AS ENUM ('activo', 'retirado', 'eliminado');
CREATE TYPE sexo_enum AS ENUM ('masculino', 'femenino', 'otro');
CREATE TYPE tipo_registro_enum AS ENUM ('normal', 'multiple');
CREATE TYPE tipo_rango_enum AS ENUM ('monto', 'visitas');

CREATE TYPE tipo_campo_enum AS ENUM (
    'texto',
    'email',
    'alfanumerico',
    'numerico',
    'textarea',
    'checkbox',
    'select',
    'radio',
    'anio',
    'anio_mes',
    'anio_mes_dia',
    'mes_dia',
    'fecha_referencia',
    'tabla',
    'menu'
);

CREATE TYPE ambito_visualizacion_enum AS ENUM ('gestion', 'ambas', 'oculto');

-- ============================================
-- SECUENCIAS
-- ============================================

CREATE SEQUENCE seq_numero_cliente START 1;

-- ============================================
-- TABLAS
-- ============================================

-- 1. TIENDAS
CREATE TABLE tiendas (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT true,
    configuracion JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. GRUPOS DE CLIENTES
CREATE TABLE grupos_clientes (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    es_default BOOLEAN DEFAULT false,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_nombre_tienda UNIQUE(tienda_id, nombre),
    CONSTRAINT unique_default_por_tienda UNIQUE(tienda_id, es_default) WHERE es_default = true
);

-- 3. RANGOS POR MONTO
CREATE TABLE rangos_monto (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    monto_minimo DECIMAL(15, 2) DEFAULT 0,
    monto_maximo DECIMAL(15, 2),
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT monto_rango_valido CHECK (monto_maximo IS NULL OR monto_maximo >= monto_minimo)
);

-- 4. RANGOS POR VISITAS
CREATE TABLE rangos_visitas (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    visitas_minimas INTEGER DEFAULT 0,
    visitas_maximas INTEGER,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT visitas_rango_valido CHECK (visitas_maximas IS NULL OR visitas_maximas >= visitas_minimas)
);

-- 5. CLIENTES
CREATE TABLE clientes (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE RESTRICT,

    -- Identificación
    numero_cliente VARCHAR(50) NOT NULL UNIQUE,
    numero_gestion VARCHAR(50),
    tipo_persona tipo_persona_enum DEFAULT 'individual',

    -- Información Personal
    nombre VARCHAR(255) NOT NULL,
    nombre_kana VARCHAR(255),
    apellido VARCHAR(255),
    apellido_kana VARCHAR(255),

    -- Contacto
    email_principal VARCHAR(255),
    email_secundario VARCHAR(255),
    telefono VARCHAR(50),
    telefono_movil VARCHAR(50),
    fax VARCHAR(50),

    -- Dirección
    codigo_postal VARCHAR(10),
    prefectura VARCHAR(100),
    ciudad VARCHAR(255),
    ciudad_kana VARCHAR(255),
    direccion TEXT,
    edificio VARCHAR(255),
    edificio_kana VARCHAR(255),

    -- Datos Demográficos
    fecha_nacimiento DATE,
    sexo sexo_enum,
    tipo_sangre VARCHAR(5),
    ocupacion VARCHAR(100),

    -- Información Laboral
    empresa_nombre VARCHAR(255),
    empresa_nombre_kana VARCHAR(255),
    empresa_industria VARCHAR(100),
    empresa_telefono VARCHAR(50),
    empresa_fax VARCHAR(50),
    empresa_fecha_fundacion DATE,
    empresa_capital DECIMAL(15, 2),
    empresa_departamento VARCHAR(100),
    empresa_contacto_nombre VARCHAR(255),
    empresa_contacto_telefono VARCHAR(50),
    empresa_contacto_email VARCHAR(255),

    -- Información Familiar
    tiene_conyuge BOOLEAN DEFAULT false,
    fecha_aniversario DATE,

    -- Estado y Configuración
    estado estado_cliente_enum DEFAULT 'activo',
    recibe_newsletter BOOLEAN DEFAULT true,
    numero_fallos_envio INTEGER DEFAULT 0,
    terminal_id VARCHAR(100),

    -- Estadísticas
    visitas_count INTEGER DEFAULT 0,
    monto_total_gastado DECIMAL(15, 2) DEFAULT 0,
    monto_promedio DECIMAL(15, 2) DEFAULT 0,
    ultima_visita DATE,
    promedio_dias_visita INTEGER,
    fecha_proxima_visita_estimada DATE,

    -- Clasificación
    grupo_id BIGINT REFERENCES grupos_clientes(id) ON DELETE SET NULL,
    rango_monto_id BIGINT REFERENCES rangos_monto(id) ON DELETE SET NULL,
    rango_visitas_id BIGINT REFERENCES rangos_visitas(id) ON DELETE SET NULL,

    -- Campos Personalizados (JSONB)
    datos_personalizados JSONB DEFAULT '{}'::jsonb,

    -- Auditoría
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,

    -- Constraints
    CONSTRAINT email_principal_formato CHECK (email_principal ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT fecha_nacimiento_valida CHECK (fecha_nacimiento <= CURRENT_DATE)
);

-- 6. CATEGORÍAS PERSONALIZADAS
CREATE TABLE categorias_personalizadas (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    comentario TEXT,
    columnas_visualizacion INTEGER DEFAULT 1 CHECK (columnas_visualizacion BETWEEN 1 AND 3),
    tipo_registro tipo_registro_enum DEFAULT 'normal',
    habilitar_busqueda BOOLEAN DEFAULT true,
    habilitar_visualizacion BOOLEAN DEFAULT true,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_nombre_categoria_tienda UNIQUE(tienda_id, nombre),
    CONSTRAINT unique_slug_categoria_tienda UNIQUE(tienda_id, slug)
);

-- 7. CAMPOS PERSONALIZADOS
CREATE TABLE campos_personalizados (
    id BIGSERIAL PRIMARY KEY,
    categoria_id BIGINT NOT NULL REFERENCES categorias_personalizadas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    tipo_campo tipo_campo_enum NOT NULL,
    comentario TEXT,
    unidad VARCHAR(50),
    clase_estilo VARCHAR(50),
    es_requerido BOOLEAN DEFAULT false,
    habilitar_busqueda BOOLEAN DEFAULT false,
    habilitar_newsletter BOOLEAN DEFAULT false,
    ambito_visualizacion ambito_visualizacion_enum DEFAULT 'ambas',
    configuracion_extra JSONB DEFAULT '{}'::jsonb,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_nombre_campo_categoria UNIQUE(categoria_id, nombre),
    CONSTRAINT unique_slug_campo_categoria UNIQUE(categoria_id, slug)
);

-- 8. OPCIONES DE CAMPO
CREATE TABLE opciones_campo (
    id BIGSERIAL PRIMARY KEY,
    campo_id BIGINT NOT NULL REFERENCES campos_personalizados(id) ON DELETE CASCADE,
    etiqueta VARCHAR(255) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    CONSTRAINT unique_valor_campo UNIQUE(campo_id, valor)
);

-- 9. VALORES PERSONALIZADOS
CREATE TABLE valores_personalizados (
    id BIGSERIAL PRIMARY KEY,
    cliente_id BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    categoria_id BIGINT NOT NULL REFERENCES categorias_personalizadas(id) ON DELETE CASCADE,
    valores JSONB NOT NULL DEFAULT '{}'::jsonb,
    registro_numero INTEGER DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_cliente_categoria_registro UNIQUE(cliente_id, categoria_id, registro_numero)
);

-- 10. MOTIVOS DE VISITA
CREATE TABLE motivos_visita (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_nombre_motivo_tienda UNIQUE(tienda_id, nombre)
);

-- 11. VISITAS
CREATE TABLE visitas (
    id BIGSERIAL PRIMARY KEY,
    cliente_id BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    motivo_id BIGINT REFERENCES motivos_visita(id) ON DELETE SET NULL,
    fecha_visita TIMESTAMPTZ DEFAULT NOW(),
    monto_consumo DECIMAL(15, 2) DEFAULT 0,
    notas TEXT,
    procesado_por VARCHAR(255),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 12. CONFIGURACIÓN DE CAMPOS
CREATE TABLE campos_configuracion (
    id BIGSERIAL PRIMARY KEY,
    tienda_id BIGINT NOT NULL REFERENCES tiendas(id) ON DELETE CASCADE,
    nombre_campo VARCHAR(255) NOT NULL,
    tipo_campo VARCHAR(50) DEFAULT 'estandar',
    visible_movil BOOLEAN DEFAULT true,
    requerido_movil BOOLEAN DEFAULT false,
    habilitar_busqueda BOOLEAN DEFAULT true,
    exportar_csv BOOLEAN DEFAULT true,
    orden INTEGER DEFAULT 0,
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT unique_campo_tienda UNIQUE(tienda_id, nombre_campo)
);

-- 13. HISTORIAL DE RANGOS
CREATE TABLE historial_rangos (
    id BIGSERIAL PRIMARY KEY,
    cliente_id BIGINT NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    tipo_rango tipo_rango_enum NOT NULL,
    rango_anterior_id BIGINT,
    rango_nuevo_id BIGINT,
    razon_cambio VARCHAR(255),
    fecha_cambio TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- ÍNDICES
-- ============================================

-- Tiendas
CREATE INDEX idx_tiendas_activo ON tiendas(activo);

-- Clientes
CREATE INDEX idx_clientes_tienda ON clientes(tienda_id);
CREATE INDEX idx_clientes_nombre ON clientes USING gin(nombre gin_trgm_ops);
CREATE INDEX idx_clientes_apellido ON clientes USING gin(apellido gin_trgm_ops);
CREATE INDEX idx_clientes_email ON clientes(email_principal);
CREATE INDEX idx_clientes_telefono ON clientes(telefono);
CREATE INDEX idx_clientes_estado ON clientes(estado);
CREATE INDEX idx_clientes_grupo ON clientes(grupo_id);
CREATE INDEX idx_clientes_rangos ON clientes(rango_monto_id, rango_visitas_id);
CREATE INDEX idx_clientes_datos_personalizados ON clientes USING gin(datos_personalizados);
CREATE INDEX idx_clientes_deleted_at ON clientes(deleted_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_clientes_numero ON clientes(numero_cliente);
CREATE INDEX idx_clientes_tienda_estado ON clientes(tienda_id, estado);

-- Grupos
CREATE INDEX idx_grupos_tienda ON grupos_clientes(tienda_id);
CREATE INDEX idx_grupos_activo ON grupos_clientes(activo);

-- Rangos
CREATE INDEX idx_rangos_monto_tienda ON rangos_monto(tienda_id);
CREATE INDEX idx_rangos_monto_rangos ON rangos_monto(monto_minimo, monto_maximo);
CREATE INDEX idx_rangos_visitas_tienda ON rangos_visitas(tienda_id);
CREATE INDEX idx_rangos_visitas_rangos ON rangos_visitas(visitas_minimas, visitas_maximas);

-- Categorías y Campos
CREATE INDEX idx_categorias_tienda ON categorias_personalizadas(tienda_id);
CREATE INDEX idx_categorias_slug ON categorias_personalizadas(slug);
CREATE INDEX idx_categorias_activo ON categorias_personalizadas(activo);
CREATE INDEX idx_campos_categoria ON campos_personalizados(categoria_id);
CREATE INDEX idx_campos_tipo ON campos_personalizados(tipo_campo);
CREATE INDEX idx_campos_slug ON campos_personalizados(slug);
CREATE INDEX idx_campos_busqueda ON campos_personalizados(habilitar_busqueda) WHERE habilitar_busqueda = true;
CREATE INDEX idx_opciones_campo ON opciones_campo(campo_id);
CREATE INDEX idx_opciones_activo ON opciones_campo(activo);

-- Valores Personalizados
CREATE INDEX idx_valores_cliente ON valores_personalizados(cliente_id);
CREATE INDEX idx_valores_categoria ON valores_personalizados(categoria_id);
CREATE INDEX idx_valores_jsonb ON valores_personalizados USING gin(valores);

-- Visitas
CREATE INDEX idx_visitas_cliente ON visitas(cliente_id);
CREATE INDEX idx_visitas_tienda ON visitas(tienda_id);
CREATE INDEX idx_visitas_fecha ON visitas(fecha_visita DESC);
CREATE INDEX idx_visitas_motivo ON visitas(motivo_id);

-- Motivos
CREATE INDEX idx_motivos_tienda ON motivos_visita(tienda_id);
CREATE INDEX idx_motivos_activo ON motivos_visita(activo);

-- Configuración
CREATE INDEX idx_config_tienda ON campos_configuracion(tienda_id);
CREATE INDEX idx_config_nombre ON campos_configuracion(nombre_campo);

-- Historial
CREATE INDEX idx_historial_cliente ON historial_rangos(cliente_id);
CREATE INDEX idx_historial_fecha ON historial_rangos(fecha_cambio DESC);
CREATE INDEX idx_historial_tipo ON historial_rangos(tipo_rango);

-- ============================================
-- FUNCIONES
-- ============================================

-- Función para actualizar updated_at
CREATE OR REPLACE FUNCTION actualizar_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Función para actualizar estadísticas de cliente
CREATE OR REPLACE FUNCTION actualizar_estadisticas_cliente()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE clientes SET
        visitas_count = (
            SELECT COUNT(*) FROM visitas WHERE cliente_id = NEW.cliente_id
        ),
        monto_total_gastado = (
            SELECT COALESCE(SUM(monto_consumo), 0) FROM visitas WHERE cliente_id = NEW.cliente_id
        ),
        monto_promedio = (
            SELECT COALESCE(AVG(monto_consumo), 0) FROM visitas WHERE cliente_id = NEW.cliente_id
        ),
        ultima_visita = (
            SELECT MAX(fecha_visita::date) FROM visitas WHERE cliente_id = NEW.cliente_id
        ),
        promedio_dias_visita = (
            SELECT COALESCE(
                EXTRACT(EPOCH FROM (MAX(fecha_visita) - MIN(fecha_visita)))::INTEGER / NULLIF(COUNT(*) - 1, 0) / 86400,
                0
            )
            FROM visitas WHERE cliente_id = NEW.cliente_id
        )
    WHERE id = NEW.cliente_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Función para auditar cambios de rango
CREATE OR REPLACE FUNCTION auditar_cambio_rango()
RETURNS TRIGGER AS $$
BEGIN
    IF (OLD.rango_monto_id IS DISTINCT FROM NEW.rango_monto_id) THEN
        INSERT INTO historial_rangos (cliente_id, tipo_rango, rango_anterior_id, rango_nuevo_id, razon_cambio)
        VALUES (NEW.id, 'monto', OLD.rango_monto_id, NEW.rango_monto_id, 'Actualización automática');
    END IF;

    IF (OLD.rango_visitas_id IS DISTINCT FROM NEW.rango_visitas_id) THEN
        INSERT INTO historial_rangos (cliente_id, tipo_rango, rango_anterior_id, rango_nuevo_id, razon_cambio)
        VALUES (NEW.id, 'visitas', OLD.rango_visitas_id, NEW.rango_visitas_id, 'Actualización automática');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- TRIGGERS
-- ============================================

-- Triggers para updated_at
CREATE TRIGGER trigger_tiendas_updated_at
    BEFORE UPDATE ON tiendas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

CREATE TRIGGER trigger_clientes_updated_at
    BEFORE UPDATE ON clientes
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

CREATE TRIGGER trigger_grupos_updated_at
    BEFORE UPDATE ON grupos_clientes
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

CREATE TRIGGER trigger_categorias_updated_at
    BEFORE UPDATE ON categorias_personalizadas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

CREATE TRIGGER trigger_campos_updated_at
    BEFORE UPDATE ON campos_personalizados
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

-- Trigger para actualizar estadísticas
CREATE TRIGGER trigger_visitas_estadisticas
    AFTER INSERT ON visitas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_estadisticas_cliente();

-- Trigger para auditar cambios de rango
CREATE TRIGGER trigger_clientes_cambio_rango
    AFTER UPDATE ON clientes
    FOR EACH ROW
    WHEN (OLD.rango_monto_id IS DISTINCT FROM NEW.rango_monto_id
       OR OLD.rango_visitas_id IS DISTINCT FROM NEW.rango_visitas_id)
    EXECUTE FUNCTION auditar_cambio_rango();

-- ============================================
-- VISTA MATERIALIZADA
-- ============================================

CREATE MATERIALIZED VIEW vista_clientes_busqueda AS
SELECT
    c.id,
    c.numero_cliente,
    c.nombre || ' ' || COALESCE(c.apellido, '') as nombre_completo,
    c.email_principal,
    c.telefono,
    c.ultima_visita,
    c.monto_total_gastado,
    g.nombre as grupo_nombre,
    rm.nombre as rango_monto_nombre,
    rv.nombre as rango_visitas_nombre,
    c.datos_personalizados,
    c.estado,
    c.tienda_id
FROM clientes c
LEFT JOIN grupos_clientes g ON c.grupo_id = g.id
LEFT JOIN rangos_monto rm ON c.rango_monto_id = rm.id
LEFT JOIN rangos_visitas rv ON c.rango_visitas_id = rv.id
WHERE c.deleted_at IS NULL;

CREATE INDEX idx_mv_clientes_nombre ON vista_clientes_busqueda(nombre_completo);
CREATE INDEX idx_mv_clientes_email ON vista_clientes_busqueda(email_principal);
CREATE INDEX idx_mv_clientes_datos ON vista_clientes_busqueda USING gin(datos_personalizados);
CREATE UNIQUE INDEX idx_mv_clientes_id ON vista_clientes_busqueda(id);

-- ============================================
-- COMENTARIOS
-- ============================================

COMMENT ON TABLE tiendas IS 'Tiendas/sucursales del sistema';
COMMENT ON TABLE clientes IS 'Clientes del sistema con campos estándar y personalizados';
COMMENT ON TABLE grupos_clientes IS 'Grupos de categorización de clientes';
COMMENT ON TABLE rangos_monto IS 'Rangos de clientes basados en monto gastado';
COMMENT ON TABLE rangos_visitas IS 'Rangos de clientes basados en frecuencia de visitas';
COMMENT ON TABLE categorias_personalizadas IS 'Categorías de campos personalizados (ej: Mascota, Familia)';
COMMENT ON TABLE campos_personalizados IS 'Definición de campos personalizados por categoría';
COMMENT ON TABLE opciones_campo IS 'Opciones para campos tipo select, radio y checkbox';
COMMENT ON TABLE valores_personalizados IS 'Valores de campos personalizados para categorías tipo múltiple';
COMMENT ON TABLE visitas IS 'Registro de visitas de clientes';
COMMENT ON TABLE motivos_visita IS 'Catálogo de motivos de visita inicial';
COMMENT ON TABLE campos_configuracion IS 'Configuración de visualización de campos estándar';
COMMENT ON TABLE historial_rangos IS 'Auditoría de cambios en rangos de clientes';

COMMENT ON COLUMN clientes.datos_personalizados IS 'Valores de campos personalizados en formato JSONB';
COMMENT ON COLUMN categorias_personalizadas.tipo_registro IS 'normal: un registro por cliente, multiple: varios registros por cliente';
COMMENT ON COLUMN campos_personalizados.configuracion_extra IS 'Configuraciones adicionales específicas del tipo de campo';
COMMENT ON COLUMN valores_personalizados.registro_numero IS 'Número de registro para categorías que permiten múltiples entradas';

-- ============================================
-- SCRIPT COMPLETADO
-- ============================================

-- Para verificar que todo se creó correctamente:
-- SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;
-- SELECT enumtypid::regtype AS enum_type FROM pg_enum GROUP BY enumtypid ORDER BY enum_type;
