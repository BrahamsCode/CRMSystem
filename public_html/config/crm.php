<?php

return [

    /*
     * Barra de módulos (columna estrecha de la izquierda).
     * Solo «clientes» está implementado; el resto queda listo para los módulos siguientes.
     */
    'modulos' => [
        ['key' => 'clientes', 'label' => 'Clientes', 'icon' => 'users', 'route' => 'admin.customers.index'],
        ['key' => 'promo', 'label' => 'Promociones', 'icon' => 'mega', 'route' => null],
        ['key' => 'mipagina', 'label' => 'Mi página', 'icon' => 'heart', 'route' => null],
        ['key' => 'web', 'label' => 'Sitio web', 'icon' => 'globe', 'route' => null],
        ['key' => 'soporte', 'label' => 'Soporte operativo', 'icon' => 'brief', 'route' => null],
        ['key' => 'terminales', 'label' => 'Terminales', 'icon' => 'tablet', 'route' => null],
        ['key' => 'reservas', 'label' => 'Reservas', 'icon' => 'cal', 'route' => null],
        ['key' => 'config', 'label' => 'Configuración', 'icon' => 'sliders', 'route' => null],
    ],

    /*
     * Menú del módulo de clientes. Replica las 10 secciones del sistema legacy,
     * agrupadas en operación diaria y configuración.
     *
     * Las etiquetas son claves de traducción, no texto: se resuelven contra los
     * archivos de idioma del módulo para que cambiar APP_LOCALE cambie el menú.
     */
    'customer_menu' => [
        ['head' => 'modulo'],
        ['label' => 'resumen', 'icon' => 'dash', 'route' => 'admin.customers.index'],
        ['label' => 'nuevo', 'icon' => 'user-plus', 'route' => 'admin.customers.create'],
        ['label' => 'buscar', 'icon' => 'search', 'route' => 'admin.customers.search'],
        ['label' => 'visita', 'icon' => 'visit', 'route' => 'admin.customers.visits'],
        ['label' => 'estadisticas', 'icon' => 'stats', 'route' => 'admin.customers.statistics'],

        ['head' => 'config'],
        ['label' => 'campos', 'icon' => 'sliders', 'route' => 'admin.customers.field-settings'],
        ['label' => 'grupos', 'icon' => 'layers', 'route' => 'admin.customers.groups'],
        ['label' => 'motivos', 'icon' => 'target', 'route' => 'admin.customers.visit-motives'],
        ['label' => 'rangos', 'icon' => 'award', 'route' => 'admin.customers.ranks'],
        ['label' => 'reglas', 'icon' => 'shuffle', 'route' => 'admin.customers.rank-schedules'],
        ['label' => 'info', 'icon' => 'plus-box', 'route' => 'admin.customers.custom-categories'],
        ['label' => 'intervalo', 'icon' => 'cal', 'route' => 'admin.customers.visit-interval'],
    ],

    /*
     * Catálogo de los campos estándar de `customers`, tal como los agrupa la
     * pantalla «Campos y CSV» del sistema legacy.
     *
     * Esto describe las columnas que existen, no cómo están configuradas: los
     * cuatro interruptores por campo viven en la tabla `field_settings`.
     *
     * Código de 4 caracteres, una posición por columna:
     *   visible en registro · obligatorio · filtro de búsqueda · exportar CSV
     *   1 = activo por defecto   0 = inactivo por defecto
     *   x = no aplica a ese campo   R = obligatorio fijo, no se puede desmarcar
     */
    'customer_fields' => [
        'Identificación y membresía' => [
            ['num', 'Nº de cliente', 'xx10'], ['mgmt', 'Nº de gestión', '0000'],
            ['store', 'Tienda de registro', 'xx11'], ['ptype', 'Persona / empresa', '0000'],
            ['joined', 'Fecha de alta', 'xx11'], ['status', 'Estado de registro', 'xx11'],
            ['news', 'Newsletter', '0Rx0'], ['updated', 'Fecha de modificación', 'xx00'],
            ['group', 'Grupo de cliente', 'xx00'], ['pass', 'Contraseña', '0000'],
        ],
        'Datos personales' => [
            ['name', 'Nombre', '1111'], ['namek', 'Nombre (fonético)', '0000'],
            ['birth', 'Fecha de nacimiento', '1111'], ['age', 'Edad', 'xx11'],
            ['sex', 'Sexo', '1111'], ['blood', 'Grupo sanguíneo', '0000'], ['job', 'Ocupación', '1111'],
        ],
        'Contacto' => [
            ['tel', 'Teléfono', '0010'], ['mobile', 'Teléfono móvil', '0000'], ['fax', 'Fax', '0000'],
            ['mail1', 'Email 1', '1111'], ['mail2', 'Email 2', '0000'], ['pmail', 'Email personal', '0000'],
        ],
        'Dirección' => [
            ['zip', 'Código postal', '0000'], ['pref', 'Región / provincia', '0000'],
            ['city', 'Ciudad / distrito', '0000'], ['cityk', 'Ciudad (fonético)', '0000'],
            ['street', 'Calle y número', '0000'], ['bldg', 'Edificio / depto.', '0000'],
            ['bldgk', 'Edificio (fonético)', '0000'],
        ],
        'Trabajo y empresa' => [
            ['wname', 'Lugar de trabajo', '0000'], ['wnamek', 'Lugar de trabajo (fonético)', '0000'],
            ['wind', 'Rubro del lugar de trabajo', '0000'], ['wtel', 'Teléfono del trabajo', '0000'],
            ['wfax', 'Fax del trabajo', '0000'], ['founded', 'Fecha de fundación', '0000'],
            ['capital', 'Capital social', '0000'], ['industry', 'Rubro', '0000'], ['dept', 'Departamento', '0000'],
        ],
        'Representante y persona de contacto' => [
            ['rep', 'Representante: nombre', '0000'], ['repk', 'Representante: nombre (fonético)', '0000'],
            ['repb', 'Representante: fecha de nacimiento', '0000'], ['cname', 'Contacto: nombre', '0000'],
            ['cnamek', 'Contacto: nombre (fonético)', '0000'], ['ctel', 'Contacto: teléfono', '0000'],
            ['cmail', 'Contacto: email', '0000'],
        ],
        'Fidelización y terminal' => [
            ['treg', 'Registro de terminal', 'xx00'], ['tid', 'ID de terminal', 'xx11'],
            ['bounce', 'Correos no entregados', 'xx11'], ['stamps', 'Sellos', 'xx00'],
            ['points', 'Puntos', 'xx00'], ['staff', 'Personal asignado', '0000'],
            ['motive', 'Motivo de primera visita', '0000'],
            ['arn', 'Rango por importe (actual)', 'xx00'], ['arp', 'Rango por importe (anterior)', 'xx00'],
            ['vrn', 'Rango por visitas (actual)', 'xx00'], ['vrp', 'Rango por visitas (anterior)', 'xx00'],
        ],
    ],

    /* Cabeceras de las cuatro columnas de la matriz, en el mismo orden del código */
    'field_columns' => [
        ['Visible en registro', 'Formulario móvil'],
        ['Obligatorio', 'Formulario móvil'],
        ['Filtro de búsqueda', 'En Buscar clientes'],
        ['Exportar CSV', 'Columna del archivo'],
    ],

    /*
     * Información familiar: son columnas propias de `customers`, no una categoría
     * personalizada, así que su configuración también va aquí. Código de 3
     * posiciones: obligatorio · búsqueda · CSV.
     */
    'family_fields' => [
        ['spouse', 'Cónyuge', '110'],
        ['wedding', 'Aniversario de boda', '110'],
    ],
];
