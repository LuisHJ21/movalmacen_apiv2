<?php

global $router;
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PalletController.php';
require_once __DIR__ . '/../controllers/AlmacenController.php';


$router->post('/login', 'AuthController@login');
$router->post('/refresh', 'AuthController@refresh');
$router->get('/me', 'AuthController@me');


$router->get('/pallet/verify', 'PalletController@VerificarPallet');
$router->get('/pallet/data', 'PalletController@ObtenerDatosPallet');
$router->get('/pallet/info', 'PalletController@ObtenerInfoPallet');

$router->get('/almacen', 'AlmacenController@obtenerAlmacenes');
$router->get('/almacen/tipo-movimiento', 'AlmacenController@ObtenerTipoMovimiento');
$router->get('/almacen/almacen-ubicacion', 'AlmacenController@obtenerAlmacenUbicacion');
$router->get('/almacen/matriz', 'AlmacenController@obtenerMatriz');

$router->get('/ubicacion', 'UbicacionController@obtenerUbicaciones');
$router->get('/ubicacion/filtro', 'UbicacionController@obtenerUbicacionesFiltro');

$router->get('/references', 'ReferencesController@obtenerReferencias');


$router->post('/vale', 'ValeController@GenerarVale');
$router->get('/vale', 'ValeController@GetHistorial');
$router->get('/vale/detalle', 'ValeController@GetValeDetalle');
