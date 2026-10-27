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


        $nivel = trim($data['nivel']);
        if (!ctype_digit($nivel) || (int) $nivel < 1 || (int) $nivel > 5) {
            throw new ApiException('nivel_invalid', 'Nivel debe ser un número del 1 al 5', 400);
        }

        $result = $this->ubicacionModel->getUbicacionesFiltro(trim($data['filtro']), trim($data['nivel']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    /** Con $conn (transacción externa, ej. transferencia) no hace commit: lo confirma quien llama */
    public function guardarUbicacion(Request|array $request, $conn = null)
    {

        $data = $request instanceof Request
            ? $request->getBody()
            : $request;

        // if (!is_array($data) || !isset($data['codUbicacion']) || !is_string($data['codUbicacion']) || trim($data['codUbicacion']) === '') {
        //     throw new ApiException('cod_rack_required', 'Código de rack es requerido', 400);
        // }

        if (!is_array($data) || !isset($data['pallet']) || !is_string($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Número de pallet es requerido', 400);
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

        $result = $this->ubicacionModel->saveUbicacion(trim($data['codUbicacion'] ?? ''), trim($data['pallet']), trim($data['valeI']), trim($data['valeS']), trim($data['usuario']), trim($data['almacenDest'] ?? ''), $conn);

        if (!$result) {
            throw new ApiException('failed_save_ubi', 'Error al guardar la ubicación desconocido', 400);
        }

        header('Cache-Control: no-store');
        return ['success' => true, 'message' => 'Ubicación guardada correctamente'];
    }
}
