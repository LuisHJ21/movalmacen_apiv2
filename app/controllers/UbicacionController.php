<?php

require_once __DIR__ . '/../models/UbicacionModel.php';

class UbicacionController
{
    private UbicacionModel $ubicacionModel;

    public function __construct()
    {
        $this->ubicacionModel = new UbicacionModel();
    }

    public function obtenerUbicaciones(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['filtro']) || !is_string($data['filtro']) || trim($data['filtro']) === '') {
            throw new ApiException('filtro_required', 'Filtro es requerido', 400);
        }

        $result = $this->ubicacionModel->getUbicaciones(trim($data['filtro']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function obtenerUbicacionesFiltro(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['filtro']) || !is_string($data['filtro']) || trim($data['filtro']) === '') {
            throw new ApiException('filtro_required', 'Filtro es requerido', 400);
        }

        if (!isset($data['nivel']) || !is_string($data['nivel']) || trim($data['nivel']) === '') {
            throw new ApiException('nivel_required', 'Nivel es requerido', 400);
        }

        $result = $this->ubicacionModel->getUbicacionesFiltro(trim($data['filtro']), trim($data['nivel']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function guardarUbicacion(Request $request)
    {
        $data = $request->getBody();

        if (!is_array($data) || !isset($data['codRack']) || !is_string($data['codRack']) || trim($data['codRack']) === '') {
            throw new ApiException('cod_rack_required', 'Código de rack es requerido', 400);
        }

        if (!is_array($data) || !isset($data['numPallet']) || !is_string($data['numPallet']) || trim($data['numPallet']) === '') {
            throw new ApiException('num_pallet_required', 'Número de pallet es requerido', 400);
        }

        if (!is_array($data) || !isset($data['valeI']) || !is_string($data['valeI']) || trim($data['valeI']) === '') {
            throw new ApiException('vale_i_required', 'Vale de entrada es requerido', 400);
        }

        if (!is_array($data) || !isset($data['valeS']) || !is_string($data['valeS']) || trim($data['valeS']) === '') {
            throw new ApiException('vale_s_required', 'Vale de salida es requerido', 400);
        }

        if (!isset($data['usuario']) || !is_string($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('usuario_required', 'Usuario es requerido', 400);
        }

        $result = $this->ubicacionModel->saveUbicacion(trim($data['codRack']), trim($data['pallet']), trim($data['valeI']), trim($data['valeS']), trim($data['usuario']), trim($data['almacenDest']));

        if (!$result) {
            throw new ApiException('failed_save_ubi', 'Error al guardar la ubicación desconocido', 400);
        }

        header('Cache-Control: no-store');
        return ['success' => true, 'message' => 'Ubicación guardada correctamente'];
    }
}
