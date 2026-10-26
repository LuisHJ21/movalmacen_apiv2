<?php

// CONFIGURACIONES PHP
ini_set('error_reporting', '1');
ini_set('display_errors', '1'); // ver errores: 1-Si, 0-NO
ini_set('log_errors', 1); // crear nuevo archivo de errores
ini_set('max_execution_time', '120');

ini_set('error_log', __DIR__ . '/../logs/php-error.log');


// Encabezados CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Datos Oracle

const BD_USE = "prueba";
const DB_DATA = [
    'prueba' => ["rf_prueba7", "rf_prueba7", "ORCL_FISHOLG"],
    'produccion' => ["ref_fisholg", "ref_fisholg", "ORCL_FISHOLG_WEB"]
];

const APP_URL = '/movalmacen/api2';
const APP_NAME = 'MOVALMAEN';
