<?php

require_once __DIR__ . '/../models/AlmacenModel.php';
class AlmacenController
{
    private AlmacenModel $almacenModel;


    public  function __construct()
    {
        $this->almacenModel = new AlmacenModel();
    }

    public function obtenerAlmacenes()
    {
        $result = $this->almacenModel->getAlmacenes();

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function ObtenerTipoMovimiento(Request $request)
    {
        $data = $request->getParams();

        // if (!is_array($data) || !isset($data['tipoMov']) || !is_string($data['tipoMov']) || trim($data['tipoMov']) === '') {
        //     throw new ApiException('movement_type_required', 'Tipo de movimiento es requerido', 400);
        // }

        $result = $this->almacenModel->GetTipoMov(trim($data['tipoMov']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function obtenerAlmacenUbicacion(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['almubi']) || !is_string($data['almubi']) || trim($data['almubi']) === '') {
            throw new ApiException('almacen_ubicacion_required', 'Almacen o Ubicación es requerido', 400);
        }

        if (!isset($data['condicion']) || !in_array($data['condicion'], ['1', '2'])) {
            throw new ApiException('condicion_required', 'Condición es requerida y debe ser 1 o 2', 400);
        }

        $result = $this->almacenModel->getAlmUbicacion(trim($data['almubi']), trim($data['condicion']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function obtenerMatriz(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['codArt']) || !is_string($data['codArt']) || trim($data['codArt']) === '') {
            throw new ApiException('codArt_required', 'Código de Artículo es requerido', 400);
        }

        if (!isset($data['tipoMov']) || !is_string($data['tipoMov']) || trim($data['tipoMov']) === '') {
            throw new ApiException('tipoMov_required', 'Tipo de Movimiento es requerido', 400);
        }

        $result = $this->almacenModel->getMatriz(trim($data['codArt']), trim($data['tipoMov']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }
}
