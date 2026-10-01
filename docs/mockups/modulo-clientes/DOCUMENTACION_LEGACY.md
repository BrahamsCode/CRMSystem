# Documentación del Sistema Legacy - Módulo de Gestión de Clientes

## Sistema Legacy CRM - 総合業務管理システム

📅 **Fecha de documentación**: Octubre 2026  
🏢 **Sistema**: CRM Legacy (development.crm-s.net)  
📦 **Módulo**: 顧客管理 (Gestión de Clientes)  
📊 **Vistas documentadas**: 23 capturas de pantalla

> ⚠️ **Nota**: Este documento cubre el **sistema LEGACY**. Para los mockups del **nuevo sistema** ver [README.md](./README.md)

---

## 🎯 Propósito de esta Documentación

Esta documentación completa del módulo de gestión de clientes del sistema legacy tiene como objetivo:

1. **Servir de referencia** para el proceso de refactorización hacia el nuevo sistema
2. **Documentar todos los flujos** de trabajo y vistas existentes
3. **Preservar el conocimiento** del sistema legacy antes de su migración
4. **Facilitar el diseño** del nuevo sistema basado en funcionalidad existente

---

## 📚 Documentos Disponibles

### 1. **[INDICE_CAPTURAS.md](./INDICE_CAPTURAS.md)** 
👉 **COMENZAR AQUÍ** - Índice visual rápido

- Navegación rápida a todas las 23 capturas
- Descripción breve de cada vista
- Tamaños de archivo y referencias
- Mapa de dependencias entre vistas
- Leyenda de símbolos

**Ideal para:** Vista general rápida del módulo, encontrar una captura específica

---

### 2. **[DOCUMENTACION_MODULO_CLIENTES.md](./DOCUMENTACION_MODULO_CLIENTES.md)**
📖 Documentación completa del módulo

- Visión general del módulo
- Estructura y menú de navegación (10 secciones)
- Descripción detallada de cada vista (01-23)
- Flujos de trabajo principales
- Sistema de configuración
- Ejemplo completo: Categoría "Mascota"
- Notas de implementación

**Ideal para:** Entender la arquitectura, planificar la migración, referencia completa

---

### 3. **[FLUJOS_DETALLADOS.md](./FLUJOS_DETALLADOS.md)**
🔄 Diagramas de flujo y procesos

- Flujo 1: Búsqueda y Visualización de Clientes
- Flujo 2: Registro de Nuevo Cliente
- Flujo 3: Gestión de Campos Personalizados (7 vistas)
- Flujo 4: Configuración del Sistema
- Matriz de navegación entre vistas
- Ejemplo real: Cliente con Mascota

**Ideal para:** Entender procesos paso a paso, diseñar la UX del nuevo sistema

---

## 🌟 Características Clave Documentadas

### 1. Búsqueda Avanzada [04, 14, 15]
- Más de 30 criterios de búsqueda
- Búsqueda en campos personalizados
- Popup de detalle de cliente

### 2. Campos Personalizados [17-23]
- Sistema de categorías extensibles
- 14 tipos de campo disponibles
- **Ejemplo completo**: Categoría "Mascota" (Nombre, Tipo, Peso)

### 3. Configuración de Visualización [06]
- Control granular de qué campos se muestran
- Configuración móvil vs. escritorio
- Control de exportación CSV

### 4. Sistema de Rangos [11-12]
- Rangos por monto y frecuencia
- Reglas automáticas de asignación

---

## 📁 Estructura de Archivos

```
docs/mockups/modulo-clientes/
│
├── README.md                              ← Mockups NUEVO sistema
├── DOCUMENTACION_LEGACY.md                ← Este archivo (índice legacy)
├── INDICE_CAPTURAS.md                     ← Índice visual ⭐ INICIO
├── DOCUMENTACION_MODULO_CLIENTES.md       ← Documentación completa
├── FLUJOS_DETALLADOS.md                   ← Diagramas de flujo
│
└── legacy/                                ← 23 capturas + 5 legacy
    ├── 01-portal-inicio.png
    ├── 02-menu-principal-clientes.png
    ├── ...
    └── 23-editar-categoria.png
```

---

## 🚀 Inicio Rápido

### Para Nuevos Desarrolladores

1. Leer [INDICE_CAPTURAS.md](./INDICE_CAPTURAS.md) (5 min)
2. Ver capturas [01-07] en carpeta `legacy/` (10 min)
3. Leer "Ejemplo Completo: Categoría Mascota" en [DOCUMENTACION_MODULO_CLIENTES.md](./DOCUMENTACION_MODULO_CLIENTES.md) (15 min)
4. Estudiar Flujo 3 en [FLUJOS_DETALLADOS.md](./FLUJOS_DETALLADOS.md) (20 min)

### Para Diseñadores UX

1. Ver todas las capturas en `legacy/` (30 min)
2. Revisar diagramas de flujo en [FLUJOS_DETALLADOS.md](./FLUJOS_DETALLADOS.md) (30 min)
3. Comparar con mockups del nuevo sistema en [README.md](./README.md)

---

## 🎓 Caso de Estudio: Categoría "Mascota"

La categoría **"Mascota"** es el ejemplo más completo de campo personalizado documentado:

### ✅ Completo y Funcional
- 3 campos configurados (Nombre, Tipo, Peso)
- Diferentes tipos (Texto, Select, Numérico)
- Visible en todas las vistas relevantes
- Configuración de visualización definida

### 📸 Vistas Donde Aparece

| Vista | Captura | Cómo Se Ve |
|-------|---------|------------|
| Configuración | [06] | Sección "Mascota" con checkboxes |
| Nuevo cliente | [07] | Formulario con los 3 campos |
| Categorías | [17] | Fila 4 en tabla |
| Campos Mascota | [21] | Lista de 3 campos completa |
| Editar categoría | [23] | Config: 3 columnas, Normal |

---

## 📊 Estadísticas

- **23 vistas** documentadas con capturas
- **4 flujos** principales diagramados
- **10 secciones** del menú lateral
- **5 categorías** personalizadas en sistema
- **14 tipos** de campo disponibles
- **~9.2 MB** de capturas de pantalla

---

## 💡 Para el Nuevo Sistema

### Funcionalidades a Migrar

| Prioridad | Funcionalidad | Vistas | Estado Legacy |
|-----------|---------------|--------|---------------|
| 🔴 Alta | Campos básicos | [06, 07] | ~50 campos |
| 🔴 Alta | Búsqueda avanzada | [04, 14] | >30 criterios |
| 🔴 Alta | Categoría Mascota | [17-23] | Completa ⭐ |
| 🟡 Media | Configuración visualización | [06] | Funcional |
| 🟢 Baja | Rangos | [11-12] | 0 configurados |
| 🟢 Baja | Grupos | [09, 16] | 0 configurados |

### Mejoras Sugeridas

1. ✨ Flujo de configuración más simple ([17→19→22] requiere muchos clicks)
2. ✨ Vista previa en tiempo real al configurar campos
3. ✨ Validación de duplicados
4. ✨ Búsqueda por pasos (wizard) en lugar de formulario largo
5. ✨ Responsive nativo (eliminar separación móvil/escritorio)

---

## 🔗 Enlaces Rápidos

### Documentación
- [INDICE_CAPTURAS.md](./INDICE_CAPTURAS.md) - Índice visual
- [DOCUMENTACION_MODULO_CLIENTES.md](./DOCUMENTACION_MODULO_CLIENTES.md) - Doc completa
- [FLUJOS_DETALLADOS.md](./FLUJOS_DETALLADOS.md) - Diagramas de flujo

### Mockups Nuevo Sistema
- [README.md](./README.md) - Mockups del nuevo diseño
- [Claude Design Canvas](https://claude.ai/artifact/H85itqausayWcS3rntBH65) - Lienzo interactivo

### Sistema Legacy
- URL: https://development.crm-s.net
- Login: master/master
- Módulo: `/system/category1/`

---

## 📝 Menú de Navegación del Sistema

| # | Opción (Japonés) | Español | Vista |
|---|------------------|---------|-------|
| 1 | 顧客新規登録 | Nuevo Cliente | [07] |
| 2 | 顧客検索 | Búsqueda | [04] |
| 3 | 来店処理 | Procesamiento Visita | [08] |
| 4 | 登録・検索項目設定 | Config Campos | [06] |
| 5 | 顧客情報集計 | Estadísticas | [05] |
| 6 | 顧客グループ設定 | Grupos | [09] |
| 7 | 初回来店動機項目設定 | Motivos Visita | [10] |
| 8 | 顧客ランク設定 | Rangos | [11] |
| 9 | 顧客ランク振り分け設定 | Distribución Rangos | [12] |
| 10 | 顧客追加情報設定 | Info Adicional | [17] |

---

## 📞 Información

**Proyecto**: VivaTech CRM System  
**Documentado por**: Antony Brahams Paredes Paulino  
**GitHub**: [@BrahamsCode](https://github.com/BrahamsCode)  
**Empresa**: BrahamsCompany  
**Fecha**: 2026-10-01  

---

**🚀 Empezar por → [INDICE_CAPTURAS.md](./INDICE_CAPTURAS.md)**

---

*Documentación del sistema legacy - Para mockups del nuevo sistema ver [README.md](./README.md)*
