<?php

/*
 * 勤怠一覧/集計 (category5/working, S2).
 * report_enabled: el legacy solo mostraba 帳票管理 (working_chouhyou.php) en el servidor 202.218.51.245
 * (`$_SERVER['SERVER_ADDR']`); aquí se activa por instalación con CRM_WORKING_REPORT=true.
 */
return [
    'report_enabled' => (bool) env('CRM_WORKING_REPORT', false),
];
