# Índice Visual de Capturas - Módulo de Gestión de Clientes

## Ubicación de Archivos
📁 `Doc/mockups/modulo-clientes/legacy/`

Total de capturas: **24 capturas** (00–25; la 02 y la 16 eran duplicados y se eliminaron)

---

## Navegación Rápida

- [Vistas Principales (01-13)](#vistas-principales)
- [Búsqueda y Resultados (14-16)](#búsqueda-y-resultados)
- [Información Adicional (17-23)](#gestión-de-información-adicional)

---

## Vistas Principales

### 01: Portal y Navegación Principal
```
📸 01-portal-inicio.png (1.8 MB)
```
- **Ruta**: `/system/select.php`
- **Descripción**: Portal principal del sistema con los 9 módulos
- **Módulos visibles**:
  - ✓ 顧客管理 (Gestión de Clientes) ← Módulo documentado
  - 販売促進管理 (Promociones)
  - マイページ管理 (Mi Página)
  - ホームページ管理 (Homepage)
  - 業務支援管理 (Soporte)
  - 端末管理 (Terminales)
  - 予約管理 (Reservas)
  - 基本情報管理 (Info Básica)

---

### 03: Dashboard del Módulo de Clientes
```
📸 03-dashboard-clientes.png (142 KB)
```
- **Ruta**: `/system/category1/`
- **Descripción**: Panel principal del módulo
- **Contenido**:
  - Estadísticas de registro del día
  - Menú lateral con 10 opciones
  - Accesos rápidos

**Menú lateral visible:**
1. 顧客新規登録 (Nuevo Cliente) → [07]
2. 顧客検索 (Búsqueda) → [04]
3. 来店処理 (Visita) → [08]
4. 登録・検索項目設定 (Campos) → [06]
5. 顧客情報集計 (Estadísticas) → [05]
6. 顧客グループ設定 (Grupos) → [09]
7. 初回来店動機項目設定 (Motivos) → [10]
8. 顧客ランク設定 (Rangos) → [11]
9. 顧客ランク振り分け設定 (Distribución) → [12]
10. 顧客追加情報設定 (Info Adicional) → [17]

---

### 04: Formulario de Búsqueda de Clientes
```
📸 04-busqueda-clientes.png (561 KB)
```
- **Ruta**: `/system/category1/customer/customer_list.php`
- **Descripción**: Búsqueda avanzada con múltiples criterios
- **Secciones**:
  - ✓ Información del cliente (15+ campos)
  - ✓ Campos personalizados (sampleform, カルテ, 家族情報)
  - ✓ Historial de visitas (7 criterios)
  - ✓ Información de cuenta (5 criterios)
  - ✓ Configuración de búsqueda (AND/OR, Coincide/No coincide)

---

### 05: Herramienta de Estadísticas
```
📸 05-estadisticas-clientes.png (139 KB)
```
- **Ruta**: `/system/category1/shukei/shukei_list.php`
- **Descripción**: Generación de reportes y estadísticas
- **Funciones**:
  - Agregación de datos
  - Filtros personalizables
  - Exportación

---

### 06: Configuración de Campos de Visualización
```
📸 06-configuracion-campos.png (464 KB)
```
- **Ruta**: `/system/category1/customer/form_config.php`
- **Descripción**: Control de visualización de campos
- **Configuración disponible**:
  - ☑ Visualización en móvil
  - ☑ Campo requerido
  - ☑ Campo de búsqueda
  - ☑ Campo en CSV

**Secciones configurables:**
- Información básica (~50 campos)
- Información familiar (2 campos)
- **Mascota** (3 campos) ⭐
  - Nombre (Ambas pantallas, búsqueda)
  - Tipo (Ambas pantallas, búsqueda)
  - Peso (Ambas pantallas, búsqueda)

---

### 07: Formulario de Nuevo Cliente
```
📸 07-nuevo-cliente.png (563 KB)
```
- **Ruta**: `/system/category1/customer/customer_new.php`
- **Descripción**: Registro completo de nuevo cliente
- **Secciones visibles**:
  1. Información básica (nombre, dirección, contacto)
  2. Información demográfica (fecha nacimiento, sexo, edad)
  3. Información laboral (empresa, departamento)
  4. Información familiar (cónyuge, aniversario)
  5. **Campos personalizados de Mascota** ⭐
     - Nombre: [_______]
     - Tipo: [Seleccionar ▼] Perro/Gato/Conejo/Hamster/Otros
     - Peso: [___] Kg

---

### 08: Procesamiento de Visita
```
📸 08-procesamiento-visita.png (140 KB)
```
- **Ruta**: `/system/category1/customer/customer_search.php`
- **Descripción**: Registro rápido de visitas de clientes
- **Funciones**:
  - Búsqueda rápida de cliente
  - Registro de visita
  - Actualización de estadísticas

---

### 09: Gestión de Grupos de Clientes
```
📸 09-grupos-clientes.png (147 KB)
```
- **Ruta**: `/system/category1/customer/customer_group_category_new.php`
- **Descripción**: Lista y creación de grupos
- **Estado**: Sin grupos registrados
- **Funcionalidad**:
  - Formulario de nuevo grupo en la misma vista
  - Opción de establecer como valor inicial

---

### 10: Configuración de Motivos de Visita
```
📸 10-motivos-visita.png (144 KB)
```
- **Ruta**: `/system/category1/customer/raiten_douki.php`
- **Descripción**: Definición de opciones de motivación inicial
- **Funciones**:
  - Crear opciones
  - Ordenar por arrastre
  - Activar/desactivar

---

### 11: Configuración de Rangos de Clientes
```
📸 11-rangos-clientes.png (218 KB)
```
- **Ruta**: `/system/category1/rank/customer_rank_list.php`
- **Descripción**: Definir rangos por uso y frecuencia
- **Tipos**:
  - 使用金額ランク (Rango por monto)
  - 来店回数ランク (Rango por visitas)
- **Estado**: Sin rangos registrados
- **Configuración visible**:
  - Visualización en My Page
  - Ordenamiento por arrastre

---

### 12: Distribución de Rangos
```
📸 12-distribucion-rangos.png (492 KB)
```
- **Ruta**: `/system/category1/rank/customer_rank_schedule.php`
- **Descripción**: Reglas automáticas de asignación de rangos
- **Funciones**:
  - Programación de actualización
  - Reglas de asignación

---

### 13: Información Adicional (Vista Legacy)
```
📸 13-informacion-adicional.png (157 KB)
```
- **Descripción**: Vista inicial de información adicional
- **Nota**: Reemplazada por vista [17]

---

## Búsqueda y Resultados

### 14: Resultados de Búsqueda de Clientes
```
📸 14-resultados-busqueda.png (810 KB)
```
- **Ruta**: `/system/category1/customer/customer_list.php` (POST)
- **Descripción**: Listado de clientes encontrados
- **Contenido**:
  - 16 clientes encontrados
  - Tabla con: Número, Nombre, Terminal, Visitas
  - Botón "詳細" (Detalle) por cliente
  - Paginación: 10/20/30/40/50 por página
- **Acciones disponibles**:
  - [ Enviar a estos clientes ]
  - [ Exportar CSV ]
  - [ DM CSV ]

**Clientes visibles en ejemplo:**
1. 十倉テスト 十倉テスト (ID: 1100028)
2. 川本 享永 (ID: 1100027)
3. 岩本 テスト (ID: 1100026)
4. 48002 テスト (ID: 1100025)
5. ... (12 más)

---

### 15: Detalle de Cliente (Popup)
```
📸 15-detalle-cliente.png (93 KB)
```
- **Ruta**: `/system/category1/inc/customer_detail.php?kaiin_cd={id}&wintype=no_headers`
- **Tipo**: Ventana emergente (popup)
- **Descripción**: Vista completa de información del cliente
- **Contenido**:
  - Todos los datos básicos
  - Información demográfica
  - Campos personalizados (incluye Mascota si está registrada)
  - Historial de visitas
  - Información de cuenta

---

### 16: Formulario de Agregar Grupo (misma pantalla que 09)
```
📸 09-grupos-clientes.png (la captura 16 era idéntica y se eliminó)
```
- **Ruta**: `/system/category1/customer/customer_group_category_new.php`
- **Descripción**: Formulario de creación de grupo de clientes
- **Campos**:
  - Nombre del grupo: [_____________]
  - ☑ Usar como valor inicial para nuevos clientes

---

## Gestión de Información Adicional

### Flujo Completo de Campos Personalizados (Vistas 17-23)

### 17: Lista de Categorías de Información Adicional
```
📸 17-lista-categorias-info-adicional.png (159 KB)
```
- **Ruta**: `/system/category1/customer/search_cate_list.php`
- **Descripción**: Panel principal de categorías personalizadas
- **Categorías registradas** (5 total):

| # | Categoría | Búsqueda | Visualizar | Orden |
|---|-----------|----------|------------|-------|
| 1 | sampleform | ☑ | ☑ | 3 |
| 2 | カルテ (Cartel) | ☑ | ☑ | 4 |
| 3 | 家族情報 (Familia) | ☑ | ☑ | 5 |
| 4 | **Mascota** ⭐ | ☐ | ☑ | 8 |
| 5 | Ropa | ☐ | ☐ | 9 |

**Acciones por categoría:**
- 🔗 Ver formularios ([19])
- 🔗 Editar categoría ([23])
- ☑ Habilitar búsqueda
- ☑ Habilitar visualización
- 🔼🔽 Ordenar

---

### 18: Formulario de Nueva Categoría
```
📸 18-formulario-nueva-categoria.png (151 KB)
```
- **Ruta**: `/system/category1/customer/search_cate_new.php`
- **Descripción**: Crear nueva categoría personalizada
- **Campos**:
  - Nombre de categoría: [_____________] (requerido)
  - Comentario: [_____________]
  - Columnas de visualización: [1/2/3 ▼]
  - Tipo de registro: ◉ Normal  ○ Múltiple

**Tipos de registro:**
- **Normal**: Un registro por cliente
- **Múltiple**: Varios registros por cliente (ej: múltiples mascotas)

---

### 19: Lista de Formularios de Categoría
```
📸 19-lista-formularios-categoria.png (147 KB)
```
- **Ruta**: `/system/category1/customer/search_form_list.php?pcd=1`
- **Parámetro**: `pcd=1` (categoría "sampleform")
- **Descripción**: Gestión de campos dentro de una categoría
- **Campos en "sampleform"** (2 total):

| # | Campo | Tipo | Preview | Req | Buscar | Mostrar |
|---|-------|------|---------|-----|--------|---------|
| 1 | remarks | Textarea | [____] | ☐ | ☑ | Gestión |
| 2 | remarks2 | Textarea | [____] | ☐ | ☑ | Gestión |

**Acciones:**
- [Nueva campo] → [22]
- [Editar] por campo → [20]
- Reordenar 🔼🔽

---

### 20: Editar Campo de Formulario
```
📸 20-editar-formulario-campo.png (181 KB)
```
- **Ruta**: `/system/category1/customer/search_form_update.php?tb_search_form_cd=2&page=1&pcd=1&kensu=10`
- **Descripción**: Configuración detallada de campo "remarks2"
- **Opciones disponibles**:
  - Título del campo: [remarks2]
  - Unidad: [_____]
  - Comentario: [_____]
  - Tipo de formulario: [Textarea ▼]
  - Estilo CSS: [24 caracteres ▼]

**14 tipos de formulario disponibles:**
1. Texto
2. Email
3. Alfanumérico
4. Numérico
5. Textarea
6. Checkbox
7. Select (lista desplegable)
8. Radio button
9. Año
10. Año-Mes
11. Año-Mes-Día
12. Mes-Día
13. Fecha de referencia
14. Tabla
15. Menú

**Estilos CSS disponibles:**
- 2 caracteres
- 3 caracteres
- 6 caracteres
- 9 caracteres
- 16 caracteres
- 24 caracteres
- 40 caracteres
- Checkbox/Radio
- Select

**Acciones:**
- [ Actualizar ]
- [ Eliminar ]

---

### 21: Formularios de Categoría "Mascota"
```
📸 21-lista-formularios-mascota.png (155 KB)
```
- **Ruta**: `/system/category1/customer/search_form_list.php?pcd=4`
- **Parámetro**: `pcd=4` (categoría "Mascota")
- **Descripción**: Campos configurados para información de mascotas
- **Campos en "Mascota"** (3 total):

| # | Campo | Tipo | Preview | Req | Buscar | Newsletter | Mostrar |
|---|-------|------|---------|-----|--------|------------|---------|
| 1 | **Nombre** | Texto | [____] | ☐ | ☑ | ☐ | Ambas |
| 2 | **Tipo** | Select | [▼] | ☐ | ☑ | ☐ | Ambas |
| 3 | **Peso** | Numérico | [__] Kg | ☐ | ☑ | ☐ | Ambas |

**Opciones del Select "Tipo":**
- Perro
- Gato
- Conejo
- Hamster
- Otros

**Opciones de "Mostrar":**
- 管理画面のみ (Solo pantalla de gestión)
- 管理・携帯 両画面 (Ambas pantallas) ← Seleccionado
- 非表示 (Oculto)

---

### 22: Formulario de Nuevo Campo
```
📸 22-formulario-nuevo-campo.png (179 KB)
```
- **Ruta**: `/system/category1/customer/search_form_new.php?pcd=4`
- **Parámetro**: `pcd=4` (agregar a categoría "Mascota")
- **Descripción**: Agregar nuevo campo a una categoría existente
- **Campos del formulario**:
  - Título: [_____________] (requerido)
  - Unidad: [_____________]
  - Comentario: [_____________]
  - Tipo: [Texto ▼] (14 opciones)
  - Configuración: [según tipo seleccionado]
  - Estilo: [Seleccionar ▼]

**Proceso:**
1. Ingresar título (ej: "Color de pelo")
2. Seleccionar tipo (ej: "Texto")
3. Configurar estilo
4. Registrar
5. Campo aparece en lista [21]

---

### 23: Editar Categoría
```
📸 23-editar-categoria.png (153 KB)
```
- **Ruta**: `/system/category1/customer/search_cate_update.php?tb_search_cate_cd=4&page=1&kensu=10`
- **Parámetro**: `tb_search_cate_cd=4` (categoría "Mascota")
- **Descripción**: Modificar configuración de categoría
- **Valores actuales de "Mascota"**:
  - Nombre: Mascota
  - Comentario: (vacío)
  - Columnas: 3
  - Tipo: Normal (通常タイプ)

**Acciones:**
- [ Actualizar categoría ]
- [ Eliminar ]

---

## Resumen por Tipo de Vista

### Vistas de Lista
| Captura | Nombre | Elementos |
|---------|--------|-----------|
| [14] | Resultados de búsqueda | 16 clientes |
| [17] | Categorías info adicional | 5 categorías |
| [19] | Campos de "sampleform" | 2 campos |
| [21] | Campos de "Mascota" | 3 campos |
| [09] | Grupos | 0 grupos |
| [11] | Rangos | 0 rangos |

### Vistas de Formulario (Crear)
| Captura | Propósito | Campos Principales |
|---------|-----------|-------------------|
| [07] | Nuevo cliente | ~30 campos + personalizados |
| [18] | Nueva categoría | 4 campos |
| [22] | Nuevo campo | 5 campos |
| [16] | Nuevo grupo | 2 campos |

### Vistas de Formulario (Editar)
| Captura | Propósito | Campos Editables |
|---------|-----------|------------------|
| [20] | Editar campo | 5 campos |
| [23] | Editar categoría | 4 campos |

### Vistas de Configuración
| Captura | Propósito | Elementos Configurables |
|---------|-----------|------------------------|
| [06] | Campos de visualización | ~55 campos |
| [12] | Distribución de rangos | Reglas automáticas |

### Vistas de Popup/Modal
| Captura | Tipo | Contenido |
|---------|------|-----------|
| [15] | Popup | Detalle completo de cliente |

---

## Mapa de Dependencias

```
Configuración Base (primero):
[17] Categorías → [18] Nueva → [19] Lista campos → [22] Nuevo campo
                                                  → [20] Editar campo
             → [23] Editar categoría

Configuración de Visualización:
[06] Config campos (usa categorías de [17])

Uso Final (después de configurar):
[07] Nuevo cliente (usa config de [06] y [17])
[04] Búsqueda (usa config de [06] y [17])
[14] Resultados → [15] Detalle (muestra según [06])
```

---

## Capturas añadidas después

```
📸 00-login.png
📸 24-estadisticas-informe.png
📸 25-estadisticas-informe-filtro.png
```
- **00**: pantalla de login (antes `test-login-page.png`).
- **24 y 25**: informe «顧客情報集計» que el legacy abre en una ventana emergente desde el enlace de la vista 05. Muestra el total por sexo, la distribución por edad de hombres y mujeres y las altas de los últimos 5 años; abajo, el filtro «種別» (業種別 / 県別) con periodo. En el rediseño el informe se muestra directamente, sin ventana emergente.
- Se eliminaron las capturas sin numerar y los duplicados exactos **02** (igual a 01) y **16** (igual a 09).

---

## Tamaño Total de Documentación

| Tipo | Cantidad | Tamaño |
|------|----------|--------|
| Capturas PNG numeradas | 24 | ~7.5 MB |
| **Total** | **24 archivos** | **~7.5 MB** |

---

## Navegación Recomendada para Revisión

### Para entender el módulo:
1. Ver [01-03] → Portal y Dashboard
2. Ver [04] → Capacidades de búsqueda
3. Ver [07] → Formulario completo de cliente
4. Ver [17] → Sistema de campos personalizados

### Para entender campos personalizados (Mascota):
1. Ver [17] → Lista de categorías (Mascota en fila 4)
2. Ver [21] → Campos de Mascota (3 campos)
3. Ver [06] → Configuración de visualización de Mascota
4. Ver [07] → Sección Mascota en formulario de nuevo cliente

### Para entender el flujo completo:
```
[17] → [18] → Volver a [17]
[17] → Click "Ver" → [21] → [22] → Volver a [21]
[21] → Click "Edit" → [20]
[17] → Click "Edit" → [23]
```

---

## Leyenda de Símbolos

| Símbolo | Significado |
|---------|-------------|
| ⭐ | Categoría "Mascota" - Ejemplo completo |
| ☑ | Checkbox marcado / Función habilitada |
| ☐ | Checkbox sin marcar / Función deshabilitada |
| 🔗 | Enlace clickeable a otra vista |
| 🔼🔽 | Botones de ordenamiento |
| [##] | Número de captura de referencia |
| 📸 | Archivo de imagen |

---

*Índice generado: 2026-10-01*  
*Total de vistas documentadas: 23*  
*Sistema: 総合業務管理システム CRM*  
*Módulo: 顧客管理 (Gestión de Clientes)*
