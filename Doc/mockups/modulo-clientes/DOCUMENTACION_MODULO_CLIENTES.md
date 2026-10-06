# Documentación del Módulo de Gestión de Clientes - Sistema Legacy CRM

## Índice
1. [Visión General](#visión-general)
2. [Estructura del Módulo](#estructura-del-módulo)
3. [Vistas Principales](#vistas-principales)
4. [Flujos de Trabajo](#flujos-de-trabajo)
5. [Configuración y Administración](#configuración-y-administración)
6. [Referencias de Capturas](#referencias-de-capturas)

---

## Visión General

El módulo de **Gestión de Clientes (顧客管理)** es el componente central del sistema CRM legacy. Permite la administración completa del ciclo de vida del cliente, desde el registro inicial hasta el seguimiento de visitas y la gestión de información personalizada.

### Características Principales
- Registro y búsqueda de clientes
- Configuración de campos personalizados
- Gestión de visitas y procesamiento
- Sistema de estadísticas y reportes
- Categorización por grupos y rangos
- Información adicional extensible

---

## Estructura del Módulo

### Menú de Navegación Lateral

El módulo está organizado en **10 secciones principales** accesibles desde el menú lateral:

1. **顧客新規登録** - Registro de Nuevo Cliente
2. **顧客検索** - Búsqueda de Clientes
3. **来店処理** - Procesamiento de Visita
4. **登録・検索項目設定** - Configuración de Campos
5. **顧客情報集計** - Estadísticas de Clientes
6. **顧客グループ設定** - Configuración de Grupos
7. **初回来店動機項目設定** - Configuración de Motivos de Visita
8. **顧客ランク設定** - Configuración de Rangos
9. **顧客ランク振り分け設定** - Distribución de Rangos
10. **顧客追加情報設定** - Configuración de Información Adicional

---

## Vistas Principales

### 1. Portal y Dashboard

#### **01-02: Portal de Inicio**
- **Ruta**: `/system/select.php` → `/system/category1/`
- **Función**: Punto de entrada al sistema, muestra los 9 módulos principales
- **Módulos disponibles**:
  - 顧客管理 (Gestión de Clientes)
  - 販売促進管理 (Gestión de Promociones)
  - マイページ管理 (Gestión de Mi Página)
  - ホームページ管理 (Gestión de Homepage)
  - 業務支援管理 (Gestión de Soporte Empresarial)
  - 端末管理 (Gestión de Terminales)
  - 予約管理 (Gestión de Reservas)
  - 基本情報管理 (Gestión de Información Básica)

#### **03: Dashboard de Clientes**
- **Ruta**: `/system/category1/`
- **Función**: Vista general del módulo de clientes
- **Contenido**:
  - Estadísticas de registro del día
  - Accesos rápidos a funciones principales
  - Panel de navegación del módulo

---

### 2. Gestión de Clientes

#### **04: Búsqueda de Clientes (顧客検索)**
- **Ruta**: `/system/category1/customer/customer_list.php`
- **Función**: Formulario avanzado de búsqueda de clientes
- **Características**:
  - **Información básica**: Número de cliente, nombre, teléfono, email
  - **Datos demográficos**: Fecha de nacimiento, sexo, edad, ocupación
  - **Ubicación**: Código postal, dirección, información de ubicación
  - **Historial**: Fecha de registro, número de visitas, última visita
  - **Categorías personalizadas**: sampleform, Cartel (カルテ), Familia, Mascota
  - **Modos de búsqueda**: AND/OR
  - **Filtros**: Coincidencia/No coincidencia

#### **14: Resultados de Búsqueda**
- **Ruta**: `/system/category1/customer/customer_list.php` (POST)
- **Función**: Listado de clientes que coinciden con los criterios
- **Contenido**:
  - Tabla con: Número de miembro, Número de gestión, Nombre, Registro de terminal, Visitas
  - Paginación (10/20/30/40/50 por página)
  - Botón "詳細" (Detalle) por cada cliente
  - Acciones masivas: Enviar, Exportar CSV, DM CSV

#### **15: Detalle de Cliente (Popup)**
- **Ruta**: `/system/category1/inc/customer_detail.php?kaiin_cd={id}&wintype=no_headers`
- **Función**: Vista completa de información del cliente en ventana emergente
- **Tipo**: Popup/Modal sin headers
- **Contenido**: Todos los datos del cliente incluyendo campos personalizados

#### **07: Registro de Nuevo Cliente**
- **Ruta**: `/system/category1/customer/customer_new.php`
- **Función**: Formulario completo de registro de nuevo cliente
- **Secciones**:
  - Información básica (nombre, dirección, contacto)
  - Información demográfica
  - Información laboral (para clientes corporativos)
  - Campos personalizados (Familia, Mascota, etc.)
  - **Ejemplo visible**: Campo "Mascota" con subcampos Nombre, Tipo, Peso

---

### 3. Procesamiento y Seguimiento

#### **08: Procesamiento de Visita (来店処理)**
- **Ruta**: `/system/category1/customer/customer_search.php`
- **Función**: Registro rápido de visitas de clientes
- **Características**:
  - Búsqueda rápida de cliente
  - Registro de visita
  - Actualización de estadísticas de visita

#### **05: Estadísticas de Clientes (顧客情報集計)**
- **Ruta**: `/system/category1/shukei/shukei_list.php`
- **Función**: Herramienta de generación de reportes y estadísticas
- **Características**:
  - Agregación de datos de clientes
  - Reportes personalizables
  - Exportación de datos

---

### 4. Configuración de Campos

#### **06: Configuración de Campos de Visualización**
- **Ruta**: `/system/category1/customer/form_config.php`
- **Función**: Control de qué campos se muestran en formularios
- **Configuración por campo**:
  - ☑ Visualización en registro móvil
  - ☑ Campo requerido en móvil
  - ☑ Campo de búsqueda
  - ☑ Campo en exportación CSV
- **Secciones configurables**:
  - **Información básica**: ~50 campos estándar
  - **Información familiar**: Cónyuge, Aniversario
  - **Campos personalizados**: Mascota (Nombre, Tipo, Peso)

---

### 5. Gestión de Grupos

#### **09: Listado de Grupos de Clientes**
- **Ruta**: `/system/category1/customer/customer_group_category_new.php`
- **Función**: Vista de grupos de clientes existentes
- **Estado actual**: Sin grupos registrados

#### **16: Formulario de Agregar Grupo**
- **Ruta**: Misma vista que 09
- **Función**: Crear nuevo grupo de clientes
- **Campos**:
  - Nombre del grupo
  - ☑ Usar como valor inicial para nuevos clientes

---

### 6. Configuración de Visitas

#### **10: Configuración de Motivos de Visita**
- **Ruta**: `/system/category1/customer/raiten_douki.php`
- **Función**: Definir opciones de motivación para primera visita
- **Características**:
  - Gestión de opciones de motivación
  - Orden configurable

---

### 7. Sistema de Rangos

#### **11: Configuración de Rangos de Clientes**
- **Ruta**: `/system/category1/rank/customer_rank_list.php`
- **Función**: Definir rangos basados en uso y frecuencia
- **Tipos de rango**:
  - **Rango por monto de uso (使用金額ランク)**
  - **Rango por frecuencia de visita (来店回数ランク)**
- **Características**:
  - Orden por arrastre (drag & drop)
  - Prioridad por posición
  - Botón "Nueva adición" para cada tipo
- **Estado actual**: Sin rangos registrados

#### **12: Distribución de Rangos**
- **Ruta**: `/system/category1/rank/customer_rank_schedule.php`
- **Función**: Configurar reglas automáticas de asignación de rangos
- **Características**:
  - Programación de actualización de rangos
  - Reglas de asignación

---

## Flujos de Trabajo

### Flujo 1: Gestión de Información Adicional (Campos Personalizados)

Este es el flujo más complejo del módulo, con **7 vistas** interconectadas:

#### **Vista 17: Lista de Categorías de Información Adicional**
- **Ruta**: `/system/category1/customer/search_cate_list.php`
- **Función**: Panel principal de gestión de categorías personalizadas
- **Contenido actual**:
  1. **sampleform** - Campos: remarks, remarks2
  2. **カルテ (Cartel)** - Campos de historial médico/servicio
  3. **家族情報 (Información Familiar)** - Datos familiares
  4. **Mascota** - Información de mascotas (Nombre, Tipo, Peso) ⭐
  5. **Ropa** - Información de vestimenta
- **Acciones por categoría**:
  - 🔗 **追加フォーム**: Ver formularios de la categoría
  - 🔗 **情報更新**: Editar categoría
  - ☑ **検索**: Habilitar en búsqueda
  - ☑ **表示**: Habilitar visualización
  - 🔼🔽 **並び**: Ordenar posición

**Flujo desde esta vista:**

```
[17] Lista de Categorías
  |
  ├─► [18] Formulario Nueva Categoría (search_cate_new.php)
  |     └─► Crear nueva categoría personalizada
  |
  ├─► [23] Editar Categoría (search_cate_update.php?tb_search_cate_cd=4)
  |     └─► Modificar categoría "Mascota"
  |          - Nombre: Mascota
  |          - Columnas: 3
  |          - Tipo: Normal (通常タイプ)
  |
  └─► [19] Lista de Formularios de Categoría (search_form_list.php?pcd=1)
        |
        ├─► [20] Editar Campo (search_form_update.php?tb_search_form_cd=2)
        |     └─► Configurar campo existente (ej: remarks2)
        |          - Título del campo
        |          - Tipo de campo: Textarea, Text, Select, Radio, etc.
        |          - Estilo CSS
        |          - Opciones (para Select/Radio/Checkbox)
        |
        └─► [22] Nuevo Campo (search_form_new.php?pcd=4)
              └─► Agregar campo a categoría "Mascota"
                   - Título
                   - Tipo de formulario (14 tipos disponibles)
                   - Configuración específica del tipo
```

#### **Vista 18: Formulario de Nueva Categoría**
- **Ruta**: `/system/category1/customer/search_cate_new.php`
- **Función**: Crear nueva categoría de información adicional
- **Campos**:
  - **Nombre de categoría** (requerido)
  - **Comentario**: Descripción
  - **Columnas de visualización**: 1, 2 o 3 columnas
  - **Tipo de registro**: 
    - 通常タイプ (Normal): Un registro por cliente
    - 複数タイプ (Múltiple): Varios registros por cliente

#### **Vista 19: Lista de Formularios de Categoría**
- **Ruta**: `/system/category1/customer/search_form_list.php?pcd={category_id}`
- **Ejemplo**: `pcd=1` para "sampleform"
- **Función**: Gestionar campos dentro de una categoría
- **Contenido**:
  - Tabla de campos configurados
  - Vista previa del campo
  - Checkboxes: Requerido, Búsqueda
  - Dropdown: Visualización (管理画面のみ/管理・携帯 両画面/非表示)
  - Ordenamiento por arrastre

#### **Vista 20: Editar Campo de Formulario**
- **Ruta**: `/system/category1/customer/search_form_update.php?tb_search_form_cd=2&page=1&pcd=1&kensu=10`
- **Función**: Modificar configuración de campo existente
- **Configuración disponible**:
  - **Título del campo** (requerido)
  - **Unidad**: Etiqueta de unidad (ej: "Kg", "cm")
  - **Comentario**: Texto de ayuda
  - **Tipo de formulario**: 14 tipos disponibles
    - Texto
    - Email
    - Alfanumérico
    - Numérico
    - Textarea
    - Checkbox
    - Select
    - Radio
    - Año
    - Año-Mes
    - Año-Mes-Día
    - Mes-Día
    - Fecha de referencia
    - Tabla
    - Menú
  - **Configuración del formulario**: Para Select/Radio/Checkbox
  - **Clase de estilo CSS**: 10 estilos predefinidos
- **Acciones**:
  - Actualizar
  - Eliminar

#### **Vista 21: Formularios de Categoría "Mascota"**
- **Ruta**: `/system/category1/customer/search_form_list.php?pcd=4`
- **Función**: Mostrar campos de la categoría Mascota
- **Campos configurados**:
  1. **Nombre** (Texto)
     - Búsqueda: ✓
     - Visualización: Ambas pantallas
  2. **Tipo** (Select)
     - Opciones: Perro, Gato, Conejo, Hamster, Otros
     - Búsqueda: ✓
     - Visualización: Ambas pantallas
  3. **Peso** (Numérico)
     - Unidad: Kg
     - Búsqueda: ✓
     - Visualización: Ambas pantallas
- **Columna adicional**: Reemplazo de newsletter (メルマガ置き換え)

#### **Vista 22: Formulario de Nuevo Campo**
- **Ruta**: `/system/category1/customer/search_form_new.php?pcd=4`
- **Función**: Agregar nuevo campo a categoría "Mascota"
- **Proceso**:
  1. Ingresar título del campo
  2. Seleccionar tipo de formulario
  3. Configurar opciones (si aplica)
  4. Definir estilo
  5. Registrar

#### **Vista 23: Editar Categoría**
- **Ruta**: `/system/category1/customer/search_cate_update.php?tb_search_cate_cd=4&page=1&kensu=10`
- **Función**: Modificar configuración de categoría "Mascota"
- **Valores actuales**:
  - Nombre: "Mascota"
  - Columnas: 3
  - Tipo: Normal (通常タイプ)
- **Acciones**:
  - Actualizar
  - Eliminar

---

### Flujo 2: Búsqueda y Visualización de Cliente

```
[04] Búsqueda de Clientes
  |
  ├─► Ingresar criterios de búsqueda
  |   - Campos básicos
  |   - Campos personalizados (Mascota, etc.)
  |   - Historial de visitas
  |
  ├─► Seleccionar modo AND/OR
  |
  └─► [14] Resultados de Búsqueda
        |
        └─► Click en "詳細" (Detalle)
              |
              └─► [15] Popup de Detalle de Cliente
                    └─► Ver toda la información
                         incluyendo campos personalizados
```

---

### Flujo 3: Registro de Nuevo Cliente

```
[07] Formulario de Nuevo Cliente
  |
  ├─► Sección: Información Básica
  |     └─► Nombre, dirección, teléfono, email
  |
  ├─► Sección: Información Demográfica
  |     └─► Fecha de nacimiento, sexo, ocupación
  |
  ├─► Sección: Información Laboral (si aplica)
  |     └─► Empresa, departamento, contacto
  |
  ├─► Sección: Información Familiar
  |     └─► Cónyuge, aniversario
  |
  ├─► Sección: Campos Personalizados
  |     └─► Mascota (Nombre, Tipo, Peso)
  |     └─► Otros campos configurados
  |
  └─► Registrar → Volver a lista/búsqueda
```

---

### Flujo 4: Configuración de Visualización

```
[06] Configuración de Campos
  |
  ├─► Sección: Información Básica
  |     └─► Configurar ~50 campos estándar
  |
  ├─► Sección: Información Familiar
  |     └─► Configurar campos familiares
  |
  ├─► Sección: Campos Personalizados
  |     └─► Configurar campos de Mascota
  |          - Nombre: Ambas pantallas, requerido, búsqueda
  |          - Tipo: Ambas pantallas, requerido, búsqueda
  |          - Peso: Ambas pantallas, requerido, búsqueda
  |
  └─► Guardar configuración
        └─► Afecta a:
             - Formulario de registro [07]
             - Formulario de búsqueda [04]
             - Detalle de cliente [15]
```

---

## Configuración y Administración

### Jerarquía de Configuración

```
Módulo de Clientes
│
├─► Campos de Visualización [06]
│   └─► Define QUÉ se muestra y DÓNDE
│
├─► Categorías de Información Adicional [17-23]
│   ├─► Define categorías personalizadas
│   ├─► Define campos dentro de categorías
│   └─► Configura tipos y validaciones
│
├─► Grupos [09, 16]
│   └─► Categorización de clientes
│
├─► Rangos [11]
│   ├─► Por monto de uso
│   └─► Por frecuencia de visita
│
├─► Distribución de Rangos [12]
│   └─► Reglas automáticas
│
└─► Motivos de Visita [10]
    └─► Opciones de primera visita
```

---

## Referencias de Capturas

### Por Funcionalidad

| Captura | Nombre | Función Principal | Ruta |
|---------|--------|-------------------|------|
| 01-02 | Portal de Inicio | Navegación principal | `/system/select.php` |
| 03 | Dashboard | Vista general del módulo | `/system/category1/` |
| 04 | Búsqueda de Clientes | Formulario de búsqueda avanzada | `customer_list.php` |
| 05 | Estadísticas | Herramienta de reportes | `shukei/shukei_list.php` |
| 06 | Configuración de Campos | Control de visualización | `form_config.php` |
| 07 | Nuevo Cliente | Formulario de registro | `customer_new.php` |
| 08 | Procesamiento de Visita | Registro de visitas | `customer_search.php` |
| 09 | Grupos de Clientes | Lista de grupos | `customer_group_category_new.php` |
| 10 | Motivos de Visita | Configuración de motivos | `raiten_douki.php` |
| 11 | Rangos de Clientes | Configuración de rangos | `customer_rank_list.php` |
| 12 | Distribución de Rangos | Reglas de asignación | `customer_rank_schedule.php` |
| 13 | Información Adicional | Lista inicial (legacy) | - |
| 14 | Resultados de Búsqueda | Listado de clientes | `customer_list.php` (POST) |
| 15 | Detalle de Cliente | Popup con info completa | `inc/customer_detail.php` |
| 16 | Agregar Grupo | Formulario de grupo | `customer_group_category_new.php` |
| 17 | Categorías Info Adicional | Lista de categorías | `search_cate_list.php` |
| 18 | Nueva Categoría | Formulario de categoría | `search_cate_new.php` |
| 19 | Formularios de Categoría | Lista de campos | `search_form_list.php?pcd=1` |
| 20 | Editar Campo | Configuración de campo | `search_form_update.php` |
| 21 | Formularios Mascota | Campos de Mascota | `search_form_list.php?pcd=4` |
| 22 | Nuevo Campo | Agregar campo | `search_form_new.php?pcd=4` |
| 23 | Editar Categoría | Modificar categoría | `search_cate_update.php` |

---

## Tipos de Campo Disponibles

Para campos personalizados en categorías de información adicional:

| Tipo | Nombre en Japonés | Uso | Ejemplo en Mascota |
|------|-------------------|-----|-------------------|
| Texto | テキスト | Texto corto | Nombre |
| Email | メールアドレス | Dirección de email | - |
| Alfanumérico | 半角英数テキスト | Solo caracteres alfanuméricos | - |
| Numérico | 数値テキスト | Solo números | Peso (Kg) |
| Textarea | テキストエリア | Texto largo | - |
| Checkbox | チェックボックス | Selección múltiple | - |
| Select | セレクト | Lista desplegable | Tipo (Perro/Gato/etc.) |
| Radio | ラジオボタン | Selección única | - |
| Año | 年 | Selector de año | - |
| Año-Mes | 年月 | Selector de año y mes | - |
| Año-Mes-Día | 年月日 | Selector de fecha completa | - |
| Mes-Día | 月日 | Selector de mes y día | - |
| Fecha de referencia | 起算日 | Fecha de inicio | - |
| Tabla | テーブル | Tabla de datos | - |
| Menú | メニュー項目 | Menú de opciones | - |

---

## Ejemplo Completo: Categoría "Mascota"

### Configuración de la Categoría
- **Nombre**: Mascota
- **Columnas**: 3
- **Tipo de registro**: Normal (un registro por cliente)

### Campos Configurados

#### 1. Nombre
- **Tipo**: Texto
- **Requerido**: No
- **Búsqueda**: Sí
- **Visualización**: Ambas pantallas (管理・携帯 両画面)
- **Newsletter**: No

#### 2. Tipo
- **Tipo**: Select (Lista desplegable)
- **Opciones**:
  - Perro
  - Gato
  - Conejo
  - Hamster
  - Otros
- **Requerido**: No
- **Búsqueda**: Sí
- **Visualización**: Ambas pantallas

#### 3. Peso
- **Tipo**: Numérico
- **Unidad**: Kg
- **Requerido**: No
- **Búsqueda**: Sí
- **Visualización**: Ambas pantallas

### Integración en el Sistema

1. **En Configuración de Campos [06]**:
   - Aparece en sección "Mascota"
   - Cada campo con sus checkboxes de configuración

2. **En Formulario de Nuevo Cliente [07]**:
   - Sección "Mascota" con los 3 campos
   - Validación según configuración

3. **En Búsqueda de Clientes [04]**:
   - Campos de Mascota disponibles en criterios
   - Búsqueda por nombre, tipo o peso

4. **En Detalle de Cliente [15]**:
   - Información de Mascota visible
   - Si el cliente tiene mascota registrada

---

## Notas de Implementación

### Estado Actual del Sistema
- **Categorías personalizadas activas**: 5
  - sampleform (2 campos)
  - カルテ (Cartel)
  - 家族情報 (Información Familiar)
  - Mascota (3 campos: Nombre, Tipo, Peso) ✓ Configuración completa
  - Ropa
- **Grupos de clientes**: 0 registrados
- **Rangos**: 0 registrados
- **Tienda**: "shop" (única tienda registrada)

### Campos Estándar
El sistema incluye ~50 campos estándar en la información básica:
- Identificación (número de cliente, gestión)
- Personal (nombre, kana, dirección)
- Contacto (teléfono, fax, emails)
- Demográfico (fecha de nacimiento, sexo, edad, ocupación)
- Laboral (empresa, departamento, etc.)
- Sistema (fecha de registro, estado, terminal)

### Consideraciones para Migración
1. **Campos personalizados**: Priorizar migración de categoría "Mascota" (completa y en uso)
2. **Configuración de visualización**: Migrar matriz de checkboxes [06]
3. **Flujo de categorías**: El flujo 17→18→19→20→21→22→23 es crítico
4. **Tipos de campo**: Soportar los 14 tipos disponibles
5. **Popup de detalle**: Implementar como modal/drawer en sistema nuevo

---

## Diagrama de Navegación

```
┌─────────────────────────────────────────────────────────────────┐
│                    PORTAL DE INICIO [01-02]                     │
│                    /system/select.php                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  MÓDULO CLIENTES - Dashboard [03]               │
│                       /system/category1/                        │
└────────┬───────────────────┬────────────────────┬───────────────┘
         │                   │                    │
         │                   │                    │
    ┌────▼─────┐      ┌─────▼────┐        ┌─────▼─────┐
    │ Gestión  │      │ Proceso  │        │ Config    │
    │ Clientes │      │ Visitas  │        │ Sistema   │
    └────┬─────┘      └─────┬────┘        └─────┬─────┘
         │                  │                    │
    ┌────▼─────┐      ┌─────▼────┐        ┌─────▼─────┐
    │[04]Buscar│      │[08]Visita│        │[06]Campos │
    │[07]Nuevo │      │[05]Stats │        │[10]Motivos│
    └────┬─────┘      └──────────┘        │[11]Rangos │
         │                                 │[12]Distrib│
    ┌────▼─────┐                          │[09]Grupos │
    │[14]Lista │                          └─────┬─────┘
    └────┬─────┘                                │
         │                                       │
    ┌────▼─────┐                          ┌─────▼─────┐
    │[15]Detalle│                         │[17]Categ  │
    │  (Popup) │                          │Info Adic  │
    └──────────┘                          └─────┬─────┘
                                                │
                                    ┌───────────┼───────────┐
                                    │           │           │
                              ┌─────▼────┐┌────▼────┐┌────▼────┐
                              │[18]Nueva ││[19]Lista││[23]Edit │
                              │Categoría ││Campos   ││Categoría│
                              └──────────┘└────┬────┘└─────────┘
                                               │
                                    ┌──────────┼──────────┐
                                    │          │          │
                              ┌─────▼────┐┌───▼────┐┌───▼────┐
                              │[20]Editar││[21]List││[22]Nuevo│
                              │Campo     ││Mascota ││Campo   │
                              └──────────┘└────────┘└────────┘
```

---

## Resumen Ejecutivo

El módulo de gestión de clientes del sistema legacy CRM está compuesto por **23 vistas documentadas** organizadas en:

- **3 vistas de entrada**: Portal y Dashboard
- **10 vistas principales**: Correspondientes al menú lateral
- **3 vistas de búsqueda y resultados**: Búsqueda, Lista, Detalle (popup)
- **7 vistas de gestión de campos personalizados**: Flujo completo de categorías

El sistema permite una **alta personalización** mediante:
- Campos personalizados agrupados en categorías
- 14 tipos de campos disponibles
- Configuración granular de visualización
- Sistema de rangos y grupos

La categoría **"Mascota"** está completamente implementada como ejemplo de personalización, con 3 campos (Nombre, Tipo, Peso) configurados para búsqueda y visualización en ambas pantallas (escritorio y móvil).

---

*Documento generado: 2026-10-01*  
*Sistema: 総合業務管理システム CRM (development.crm-s.net)*  
*Módulo: 顧客管理 (Gestión de Clientes)*
