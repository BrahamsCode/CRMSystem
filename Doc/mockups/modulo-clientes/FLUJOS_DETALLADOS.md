# Flujos Detallados del Módulo de Gestión de Clientes

## Índice de Flujos
1. [Flujo de Búsqueda y Visualización](#flujo-1-búsqueda-y-visualización-de-clientes)
2. [Flujo de Registro de Cliente](#flujo-2-registro-de-nuevo-cliente)
3. [Flujo de Gestión de Campos Personalizados](#flujo-3-gestión-de-campos-personalizados)
4. [Flujo de Configuración del Sistema](#flujo-4-configuración-del-sistema)

---

## Flujo 1: Búsqueda y Visualización de Clientes

### Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        INICIO: Portal [01-02]                           │
│                     Usuario accede al sistema                           │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │  Dashboard Clientes    │
                    │        [03]            │
                    │  /category1/           │
                    └────────────┬───────────┘
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │  Click en "顧客検索"   │
                    │  (Búsqueda Clientes)   │
                    └────────────┬───────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   VISTA: Búsqueda de Clientes [04]                      │
│                   customer_list.php (GET)                               │
├─────────────────────────────────────────────────────────────────────────┤
│  Secciones del formulario:                                              │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ ▣ Información del Cliente                                        │  │
│  │   • Número de cliente                                            │  │
│  │   • Tienda de registro: ☑ shop                                   │  │
│  │   • Nombre: _______________                                      │  │
│  │   • Teléfono: _______________                                    │  │
│  │   • Email: _______________                                       │  │
│  │   • Fecha de nacimiento: MM / DD                                 │  │
│  │   • Sexo: [ Seleccionar ▼ ]                                      │  │
│  │   • Edad: ___ ～ ___ años                                        │  │
│  │   • Ocupación: [ Seleccionar ▼ ]                                 │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │ ▣ Campos Personalizados                                          │  │
│  │   sampleform:                                                    │  │
│  │   • remarks: _______________                                     │  │
│  │   • remarks2: _______________                                    │  │
│  │                                                                   │  │
│  │   Cartel (カルテ):                                               │  │
│  │   • Contenido del mensaje: _______________                       │  │
│  │   • Nombre del staff: _______________                            │  │
│  │                                                                   │  │
│  │   Información Familiar:                                          │  │
│  │   • Cónyuge: [ Seleccionar ▼ ]                                   │  │
│  │   • Aniversario: __________ ～ __________                        │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │ ▣ Historial de Visitas                                           │  │
│  │   • Período: [ Seleccionar ▼ ]                                   │  │
│  │   • Número de visitas: ___ ～ ___ veces                          │  │
│  │   • Última visita: ___ días atrás                                │  │
│  │   • Promedio de ciclo de visita: ___ ～ ___ días                 │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │ ▣ Configuración de Búsqueda                                      │  │
│  │   Método: ◉ AND  ○ OR                                            │  │
│  │   Filtro: ◉ Coincide  ○ No coincide                             │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  [ Buscar con estas condiciones ]                                       │
└────────────────────────────────┬─────────────────────────────────────────┘
                                 │
                                 │ Usuario hace click en "Buscar"
                                 │ POST con criterios
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   VISTA: Resultados de Búsqueda [14]                    │
│                   customer_list.php (POST)                              │
├─────────────────────────────────────────────────────────────────────────┤
│  16 clientes encontrados                                                │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Mostrar: [10▼] por página    |  1-10 de 16                       │  │
│  ├─────┬──────┬──────────┬──────────┬────────┬────────┐             │  │
│  │ #   │ Núm  │ Nombre   │ Terminal │ Visitas│ Acción │             │  │
│  ├─────┼──────┼──────────┼──────────┼────────┼────────┤             │  │
│  │ 1   │11028 │十倉テスト│ No       │   -    │[詳細]  │◄─── Click   │  │
│  │ 2   │11027 │川本 享永 │ No       │   -    │[詳細]  │             │  │
│  │ 3   │11026 │岩本 テスト│ No       │   -    │[詳細]  │             │  │
│  │ ... │ ...  │ ...      │ ...      │  ...   │ ...    │             │  │
│  └─────┴──────┴──────────┴──────────┴────────┴────────┘             │  │
│                                                                          │
│  [ Enviar a estos ] [ Exportar CSV ] [ DM CSV ]                         │
│                                                                          │
│  Paginación: [1] [2] [Siguiente >]                                      │
└────────────────────────────────┬─────────────────────────────────────────┘
                                 │
                                 │ Click en botón "詳細"
                                 │ Abre ventana emergente
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   POPUP: Detalle de Cliente [15]                        │
│              inc/customer_detail.php?kaiin_cd=1778836382001652942       │
│                    &wintype=no_headers                                  │
├─────────────────────────────────────────────────────────────────────────┤
│  ╔═══════════════════════════════════════════════════════════════════╗ │
│  ║              Información Completa del Cliente                     ║ │
│  ╠═══════════════════════════════════════════════════════════════════╣ │
│  ║ Datos Básicos:                                                    ║ │
│  ║ • Número: 1100028                                                 ║ │
│  ║ • Nombre: 十倉テスト 十倉テスト                                  ║ │
│  ║ • Teléfono: xxx-xxxx-xxxx                                         ║ │
│  ║ • Email: test@example.com                                         ║ │
│  ║                                                                    ║ │
│  ║ Información Adicional:                                            ║ │
│  ║ • Fecha de nacimiento: 1990/01/01 (36 años)                       ║ │
│  ║ • Sexo: Masculino                                                 ║ │
│  ║ • Ocupación: Empleado                                             ║ │
│  ║                                                                    ║ │
│  ║ Campos Personalizados:                                            ║ │
│  ║ [Si tiene mascota registrada]                                     ║ │
│  ║ Mascota:                                                          ║ │
│  ║ • Nombre: Firulais                                                ║ │
│  ║ • Tipo: Perro                                                     ║ │
│  ║ • Peso: 15 Kg                                                     ║ │
│  ║                                                                    ║ │
│  ║ Historial:                                                        ║ │
│  ║ • Fecha de registro: 2025/06/15                                   ║ │
│  ║ • Última visita: -                                                ║ │
│  ║                                                                    ║ │
│  ║                        [ Cerrar ]                                 ║ │
│  ╚═══════════════════════════════════════════════════════════════════╝ │
└─────────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Usuario cierra popup
                                 ▼
                    ┌────────────────────────┐
                    │  Volver a resultados   │
                    │  de búsqueda [14]      │
                    └────────────────────────┘
```

### Pasos Detallados

1. **Acceso al módulo** → Portal [01] → Dashboard [03]
2. **Selección de búsqueda** → Click en "顧客検索" en menú lateral
3. **Criterios de búsqueda** → Vista [04]
   - Ingresar nombre, teléfono, etc.
   - Seleccionar campos personalizados (Mascota)
   - Configurar AND/OR
4. **Ejecución** → Click en "Buscar"
5. **Revisión de resultados** → Vista [14]
   - Ver lista paginada
   - Opciones de exportación
6. **Ver detalle** → Click en "詳細" → Popup [15]
7. **Cierre** → Volver a resultados

---

## Flujo 2: Registro de Nuevo Cliente

### Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     Dashboard Clientes [03]                             │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │ Click en "顧客新規登録"│
                    │ (Nuevo Cliente)        │
                    └────────────┬───────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              VISTA: Formulario de Nuevo Cliente [07]                    │
│                   customer_new.php                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 1: Información Básica                                     │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ Nombre (Kana): _______________  _______________                │    │
│  │ Nombre:        _______________  _______________                │    │
│  │ Código postal: □□□-□□□□  [Buscar dirección]                   │    │
│  │ Prefectura: [ Seleccionar ▼ ]                                  │    │
│  │ Ciudad/Municipio: _______________                              │    │
│  │ Dirección: _______________                                     │    │
│  │ Edificio/Depto: _______________                                │    │
│  │ Teléfono: _______________                                      │    │
│  │ FAX: _______________                                           │    │
│  │ Email 1: _______________                                       │    │
│  │ Email 2: _______________                                       │    │
│  └────────────────────────────────────────────────────────────────┘    │
│         │                                                               │
│         ▼                                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 2: Información Demográfica                                │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ Fecha de nacimiento: □□□□ / □□ / □□                          │    │
│  │ Sexo: ○ Masculino  ○ Femenino                                  │    │
│  │ Tipo de sangre: [ Seleccionar ▼ ]                              │    │
│  │ Teléfono móvil: _______________                                │    │
│  │ Email personal: _______________                                │    │
│  │ Ocupación: [ Seleccionar ▼ ]                                   │    │
│  └────────────────────────────────────────────────────────────────┘    │
│         │                                                               │
│         ▼                                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 3: Información Laboral (Opcional)                         │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ Nombre de empresa (Kana): _______________                      │    │
│  │ Nombre de empresa: _______________                             │    │
│  │ Industria: [ Seleccionar ▼ ]                                   │    │
│  │ Teléfono: _______________                                      │    │
│  │ FAX: _______________                                           │    │
│  │ Departamento: _______________                                  │    │
│  │ Persona de contacto: _______________                           │    │
│  │ Teléfono de contacto: _______________                          │    │
│  │ Email de contacto: _______________                             │    │
│  └────────────────────────────────────────────────────────────────┘    │
│         │                                                               │
│         ▼                                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 4: Información Familiar                                   │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ Cónyuge: ○ Sí  ○ No                                            │    │
│  │ Aniversario de boda: □□□□ / □□ / □□                          │    │
│  └────────────────────────────────────────────────────────────────┘    │
│         │                                                               │
│         ▼                                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 5: Campos Personalizados - Mascota ★                      │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ ┌──────────────────────────────────────────────────────────┐  │    │
│  │ │ Nombre: _______________                                   │  │    │
│  │ │                                                            │  │    │
│  │ │ Tipo: [ Seleccionar ▼ ]                                   │  │    │
│  │ │       • Perro                                             │  │    │
│  │ │       • Gato                                              │  │    │
│  │ │       • Conejo                                            │  │    │
│  │ │       • Hamster                                           │  │    │
│  │ │       • Otros                                             │  │    │
│  │ │                                                            │  │    │
│  │ │ Peso: _____ Kg                                            │  │    │
│  │ └──────────────────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────────────────┘    │
│         │                                                               │
│         ▼                                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ PASO 6: Otros Campos Personalizados                            │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │ sampleform:                                                    │    │
│  │ • remarks: _________________________                           │    │
│  │ • remarks2: _________________________                          │    │
│  │                                                                 │    │
│  │ Cartel (カルテ):                                               │    │
│  │ • Contenido del mensaje: _________________________             │    │
│  │ • Nombre del staff: _________________________                  │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  [ Registrar Cliente ]  [ Cancelar ]                                    │
└────────────────────────────────┬─────────────────────────────────────────┘
                                 │
                                 │ Click en "Registrar"
                                 │ Validación de campos
                                 ▼
                    ┌────────────────────────┐
                    │ ¿Datos válidos?        │
                    └────┬──────────┬────────┘
                         │No        │Sí
                         │          │
                    ┌────▼────┐     │
                    │ Mostrar │     │
                    │ Errores │     │
                    └────┬────┘     │
                         │          │
                         └──────────┼─────────────────┐
                                    │                 │
                                    ▼                 ▼
                    ┌──────────────────────┐  ┌─────────────┐
                    │ Guardar en BD        │  │ Mensaje de  │
                    │ Asignar número       │  │ confirmación│
                    └──────────┬───────────┘  └─────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │ Redirigir a:         │
                    │ • Lista [14]         │
                    │ • Detalle [15]       │
                    │ • Nuevo cliente [07] │
                    └──────────────────────┘
```

### Validaciones del Formulario

| Campo | Tipo | Requerido | Validación |
|-------|------|-----------|------------|
| Nombre | Texto | Sí | No vacío |
| Email 1 | Email | No | Formato email válido |
| Fecha de nacimiento | Fecha | No | Formato YYYY/MM/DD |
| Teléfono | Texto | No | Formato de teléfono |
| **Mascota - Nombre** | Texto | Según config | Máx. caracteres |
| **Mascota - Tipo** | Select | Según config | Opción válida |
| **Mascota - Peso** | Numérico | Según config | Solo números, > 0 |

---

## Flujo 3: Gestión de Campos Personalizados

### Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     Dashboard Clientes [03]                             │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
                    ┌────────────────────────┐
                    │ Click en "顧客追加情報"│
                    │ 設定"                  │
                    └────────────┬───────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│        VISTA: Lista de Categorías de Info Adicional [17]                │
│                   search_cate_list.php                                  │
├─────────────────────────────────────────────────────────────────────────┤
│  [ Nueva Categoría ] | [ Volver a lista ]                               │
│                                                                          │
│  ┌────┬─────────────┬─────────┬─────────┬────────┬────────┬────────┐  │
│  │ #  │ Categoría   │ Campos  │ Editar  │ Buscar │ Mostrar│ Orden  │  │
│  ├────┼─────────────┼─────────┼─────────┼────────┼────────┼────────┤  │
│  │ 1  │ sampleform  │ [Ver]   │ [Edit]  │ ☑      │ ☑      │ 🔼🔽   │  │
│  │ 2  │ カルテ      │ [Ver]   │ [Edit]  │ ☑      │ ☑      │ 🔼🔽   │  │
│  │ 3  │ 家族情報    │ [Ver]   │ [Edit]  │ ☑      │ ☑      │ 🔼🔽   │  │
│  │ 4  │ Mascota ★   │ [Ver]   │ [Edit]  │ ☐      │ ☑      │ 🔼🔽   │  │
│  │ 5  │ Ropa        │ [Ver]   │ [Edit]  │ ☐      │ ☐      │ 🔼🔽   │  │
│  └────┴─────────────┴─────────┴─────────┴────────┴────────┴────────┘  │
│                                                                          │
│  [ Actualizar configuración ]                                           │
└─────┬──────────────────┬─────────────────┬─────────────────────────────┘
      │                  │                 │
      │Click [Nueva]     │Click [Ver]      │Click [Edit]
      │                  │                 │
      ▼                  ▼                 ▼
┌─────────────┐  ┌────────────────┐  ┌──────────────┐
│   [18]      │  │     [19]       │  │    [23]      │
│   Nueva     │  │  Lista de      │  │   Editar     │
│ Categoría   │  │   Campos       │  │  Categoría   │
└─────────────┘  └────────┬───────┘  └──────────────┘
                          │
                          │
                  ┌───────┴───────┐
                  │               │
            Click [Nuevo]   Click [Edit]
                  │               │
                  ▼               ▼
          ┌──────────┐    ┌──────────┐
          │   [22]   │    │   [20]   │
          │  Nuevo   │    │  Editar  │
          │  Campo   │    │  Campo   │
          └──────────┘    └──────────┘
```

### Flujo Detallado: Crear Categoría "Mascota"

```
INICIO: Usuario quiere agregar campos de Mascota

1. Navegar a Lista de Categorías [17]
   └─► search_cate_list.php

2. Click en "Nueva Categoría"
   └─► Abre [18] search_cate_new.php

3. Completar formulario [18]:
   ┌────────────────────────────────────┐
   │ Nombre: Mascota                    │
   │ Comentario: Información de mascota │
   │ Columnas: [3 ▼]                    │
   │ Tipo: ◉ Normal  ○ Múltiple         │
   │                                     │
   │ [ Registrar ]                      │
   └────────────────────────────────────┘
   
4. Click en "Registrar"
   └─► Crea categoría ID=4
   └─► Redirige a [17] (ahora muestra "Mascota" en la lista)

5. Click en [Ver] de la categoría "Mascota"
   └─► Abre [21] search_form_list.php?pcd=4
   └─► Muestra: "No hay campos registrados"

6. Click en "Nuevo Campo" [21] → [22]
   └─► search_form_new.php?pcd=4

7. Crear CAMPO 1: Nombre
   ┌────────────────────────────────────────┐
   │ Título: Nombre                         │
   │ Unidad: (vacío)                        │
   │ Comentario: Nombre de la mascota       │
   │ Tipo: [Texto ▼]                        │
   │ Estilo: [24 caracteres ▼]             │
   │                                         │
   │ [ Registrar ]                          │
   └────────────────────────────────────────┘
   └─► Campo ID=7 creado

8. Repetir paso 6-7 para CAMPO 2: Tipo
   ┌────────────────────────────────────────┐
   │ Título: Tipo                           │
   │ Unidad: (vacío)                        │
   │ Comentario: Tipo de mascota            │
   │ Tipo: [Select ▼]                       │
   │ Configuración:                         │
   │   Perro|Perro                          │
   │   Gato|Gato                            │
   │   Conejo|Conejo                        │
   │   Hamster|Hamster                      │
   │   Otros|Otros                          │
   │ Estilo: [Select ▼]                    │
   │                                         │
   │ [ Registrar ]                          │
   └────────────────────────────────────────┘
   └─► Campo ID=8 creado

9. Repetir paso 6-7 para CAMPO 3: Peso
   ┌────────────────────────────────────────┐
   │ Título: Peso                           │
   │ Unidad: Kg                             │
   │ Comentario: Peso de la mascota         │
   │ Tipo: [Numérico ▼]                     │
   │ Estilo: [6 caracteres ▼]              │
   │                                         │
   │ [ Registrar ]                          │
   └────────────────────────────────────────┘
   └─► Campo ID=9 creado

10. Ahora en [21] se ven los 3 campos:
    ┌───┬────────┬──────────┬─────────┬────────┬────────┬────────┐
    │ # │ Nombre │ Tipo     │ Preview │ Req    │ Buscar │ Mostrar│
    ├───┼────────┼──────────┼─────────┼────────┼────────┼────────┤
    │ 1 │ Nombre │ Texto    │ [___]   │ ☐      │ ☑      │ Ambas  │
    │ 2 │ Tipo   │ Select   │ [▼]     │ ☐      │ ☑      │ Ambas  │
    │ 3 │ Peso   │ Numérico │ [__] Kg │ ☐      │ ☑      │ Ambas  │
    └───┴────────┴──────────┴─────────┴────────┴────────┴────────┘

11. Configurar visualización en [06]
    └─► form_config.php
    └─► Sección "Mascota" ahora aparece
    └─► Marcar checkboxes según necesidad

FIN: Categoría "Mascota" completa y funcional
     - Aparece en formulario de nuevo cliente [07]
     - Aparece en búsqueda [04]
     - Aparece en detalle [15]
```

---

## Flujo 4: Configuración del Sistema

### Configuración de Visualización de Campos

```
┌─────────────────────────────────────────────────────────────────────────┐
│              VISTA: Configuración de Campos [06]                        │
│                   form_config.php                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Leyenda:                                                                │
│  ■ = Visualización en registro móvil                                    │
│  ■ = Campo requerido en móvil                                           │
│  ■ = Campo de búsqueda                                                  │
│  ■ = Campo en CSV                                                       │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ SECCIÓN: Información Básica (50 campos)                          │  │
│  ├────────────────┬───────┬───────┬───────┬───────┐                  │  │
│  │ Campo          │ Móvil │ Req   │ Buscar│ CSV   │                  │  │
│  ├────────────────┼───────┼───────┼───────┼───────┤                  │  │
│  │ Número cliente │ ☑     │ ☐     │ ☐     │ ☐     │                  │  │
│  │ Tienda         │ ☑     │ ☑     │ ☐     │ ☐     │                  │  │
│  │ Nombre         │ ☑     │ ☑     │ ☑     │ ☑     │                  │  │
│  │ Email 1        │ ☑     │ ☑     │ ☑     │ ☑     │                  │  │
│  │ Teléfono       │ ☐     │ ☐     │ ☑     │ ☐     │                  │  │
│  │ F. Nacimiento  │ ☑     │ ☑     │ ☑     │ ☑     │                  │  │
│  │ Sexo           │ ☑     │ ☑     │ ☑     │ ☑     │                  │  │
│  │ ...            │ ...   │ ...   │ ...   │ ...   │                  │  │
│  └────────────────┴───────┴───────┴───────┴───────┘                  │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ SECCIÓN: Información Familiar                                     │  │
│  ├────────────────┬─────────────┬───────┬───────┬───────┐            │  │
│  │ Campo          │ Visualizar  │ Buscar│ CSV   │ Req   │            │  │
│  ├────────────────┼─────────────┼───────┼───────┼───────┤            │  │
│  │ Cónyuge        │[Gestión ▼]  │ ☑     │ ☑     │ ☐     │            │  │
│  │ Aniversario    │[Gestión ▼]  │ ☑     │ ☑     │ ☐     │            │  │
│  └────────────────┴─────────────┴───────┴───────┴───────┘            │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ SECCIÓN: Mascota ★★★                                             │  │
│  ├────────────────┬─────────────┬───────┬───────┬───────┐            │  │
│  │ Campo          │ Visualizar  │ Buscar│ CSV   │ Req   │            │  │
│  ├────────────────┼─────────────┼───────┼───────┼───────┤            │  │
│  │ Nombre         │[Ambas ▼]    │ ☐     │ ☑     │ ☐     │            │  │
│  │ Tipo           │[Ambas ▼]    │ ☐     │ ☑     │ ☐     │            │  │
│  │ Peso           │[Ambas ▼]    │ ☐     │ ☑     │ ☐     │            │  │
│  └────────────────┴─────────────┴───────┴───────┴───────┘            │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  [ Guardar configuración ]                                              │
│                                                                          │
│  Opciones de "Visualizar":                                              │
│  • 管理画面のみ (Solo pantalla de gestión)                              │
│  • 管理・携帯 両画面 (Ambas pantallas)                                  │
│  • 非表示 (Oculto)                                                      │
└─────────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Guardar
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    IMPACTO DE LA CONFIGURACIÓN                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Si "Nombre" de Mascota tiene:                                          │
│  • Visualizar = "Ambas"    → Aparece en [07] Nuevo Cliente             │
│  • Búsqueda = ☑            → Aparece en [04] Búsqueda                   │
│  • CSV = ☑                 → Se incluye en exportación                  │
│  • Requerido = ☑           → No se puede guardar sin él                 │
│                                                                          │
│  Vistas afectadas:                                                       │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ [07] Formulario Nuevo Cliente                                  │    │
│  │  └─► Sección "Mascota" con campos configurados                 │    │
│  │                                                                 │    │
│  │ [04] Búsqueda de Clientes                                      │    │
│  │  └─► Campos de Mascota en criterios                            │    │
│  │                                                                 │    │
│  │ [14] Exportación CSV                                           │    │
│  │  └─► Columnas de Mascota incluidas                             │    │
│  │                                                                 │    │
│  │ [15] Detalle de Cliente                                        │    │
│  │  └─► Información de Mascota visible                            │    │
│  └────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Matriz de Navegación Entre Vistas

| Desde Vista | Acción | A Vista | Método |
|-------------|--------|---------|--------|
| [01-02] Portal | Click módulo "顧客管理" | [03] Dashboard | GET |
| [03] Dashboard | Click "顧客検索" | [04] Búsqueda | GET |
| [04] Búsqueda | Click "Buscar" | [14] Resultados | POST |
| [14] Resultados | Click "詳細" | [15] Detalle | Popup |
| [03] Dashboard | Click "顧客新規登録" | [07] Nuevo | GET |
| [07] Nuevo | Submit formulario | [14] Resultados | POST |
| [03] Dashboard | Click "顧客追加情報設定" | [17] Categorías | GET |
| [17] Categorías | Click "Nueva" | [18] Nueva Cat | GET |
| [17] Categorías | Click "Ver" | [19] Lista Campos | GET |
| [17] Categorías | Click "Edit" | [23] Edit Cat | GET |
| [19] Lista Campos | Click "Nuevo Campo" | [22] Nuevo Campo | GET |
| [19] Lista Campos | Click "Edit Campo" | [20] Edit Campo | GET |
| [03] Dashboard | Click "登録・検索項目設定" | [06] Config Campos | GET |

---

## Ejemplo Real: Cliente con Mascota

### Datos del Cliente
```
Nombre: Juan Pérez
Email: juan.perez@example.com
Teléfono: 555-1234
Fecha de nacimiento: 1985/03/15
Sexo: Masculino
Ocupación: Empleado

Mascota:
  Nombre: Firulais
  Tipo: Perro
  Peso: 15 Kg
```

### Recorrido en el Sistema

#### 1. Registro (Vista [07])
```
Usuario: Staff de la tienda
Acción: Registrar nuevo cliente con mascota

[07] Formulario de Nuevo Cliente
  ↓
Completa datos básicos
  ↓
Completa datos demográficos
  ↓
Llega a sección "Mascota"
  ┌─────────────────────────┐
  │ Nombre: Firulais        │
  │ Tipo: [Perro ▼]         │
  │ Peso: 15 Kg             │
  └─────────────────────────┘
  ↓
Click "Registrar"
  ↓
Cliente guardado con ID: 1100029
```

#### 2. Búsqueda (Vista [04])
```
Usuario: Staff busca clientes con perros

[04] Búsqueda de Clientes
  ↓
Scroll a sección "Mascota"
  ┌─────────────────────────┐
  │ Tipo: [Perro ▼]         │
  └─────────────────────────┘
  ↓
Click "Buscar"
  ↓
[14] Resultados
  ┌──────────────────────────┐
  │ Juan Pérez   [Detalle]   │
  │ María López  [Detalle]   │
  └──────────────────────────┘
```

#### 3. Visualización (Vista [15])
```
Usuario: Staff ve detalle del cliente

[14] Click en "Detalle"
  ↓
[15] Popup de Detalle
  ┌─────────────────────────┐
  │ CLIENTE: Juan Pérez     │
  │ Email: juan.perez@...   │
  │ Teléfono: 555-1234      │
  │                         │
  │ MASCOTA:                │
  │ • Nombre: Firulais      │
  │ • Tipo: Perro           │
  │ • Peso: 15 Kg           │
  └─────────────────────────┘
```

---

## Notas de Implementación

### Estados de las Vistas

| Vista | Tipo | Puede tener datos | Estado actual |
|-------|------|-------------------|---------------|
| [17] Categorías | Lista | Sí | 5 categorías |
| [19] Lista Campos | Lista | Sí | Variable por categoría |
| [21] Mascota Campos | Lista | Sí | 3 campos |
| [09] Grupos | Lista | Sí | 0 grupos |
| [11] Rangos | Lista | Sí | 0 rangos |
| [14] Resultados | Lista | Sí | Según búsqueda |

### Validaciones Críticas

1. **Al crear categoría**: Nombre único
2. **Al crear campo**: Nombre único dentro de categoría
3. **Al registrar cliente**: Campos requeridos según config
4. **Al buscar**: Al menos un criterio

---

*Documento generado: 2026-10-01*  
*Sistema: 総合業務管理システム CRM*
