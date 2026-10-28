<?php

global $router;
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PalletController.php';
require_once __DIR__ . '/../controllers/AlmacenController.php';
require_once __DIR__ . '/../controllers/TransferenciaController.php';
require_once __DIR__ . '/../controllers/PlanDiarioController.php';
require_once __DIR__ . '/../controllers/TransferPalletController.php';


$router->post('/login', 'AuthController@login');
$router->post('/refresh', 'AuthController@refresh');
$router->get('/me', 'AuthController@me');


$router->get('/pallet/verify', 'PalletController@VerificarPallet');
$router->get('/pallet/data', 'PalletController@ObtenerDatosPallet');
$router->get('/pallet/datatransfer', 'PalletController@ObtenerDatosTransferPallet');
$router->get('/pallet/info', 'PalletController@ObtenerInfoPallet');

$router->get('/almacen', 'AlmacenController@obtenerAlmacenes');
$router->get('/almacen/tipo-movimiento', 'AlmacenController@ObtenerTipoMovimiento');
$router->get('/almacen/almacen-ubicacion', 'AlmacenController@obtenerAlmacenUbicacion');
$router->get('/almacen/matriz', 'AlmacenController@obtenerMatriz');

$router->get('/ubicacion', 'UbicacionController@obtenerUbicaciones');
$router->get('/ubicacion/filtro', 'UbicacionController@obtenerUbicacionesFiltro');
$router->post('/ubicacion', 'UbicacionController@guardarUbicacion');

$router->get('/references', 'ReferencesController@obtenerReferencias');

$router->post('/vale', 'ValeController@GenerarVale');
$router->get('/vale', 'ValeController@GetHistorial');
$router->get('/vale/detalle', 'ValeController@GetValeDetalle');

$router->post('/transferencia', 'TransferenciaController@GenerarOTR');

$router->get('/plandiario', 'PlanDiarioController@obtenerPlanDiarioActivo');

$router->post('/caja/leer', 'CajaController@leer');
$router->post('/transferpallet', 'TransferPalletController@GenerarTraslado');
