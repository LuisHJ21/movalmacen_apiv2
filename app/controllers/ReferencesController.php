<?php

require_once __DIR__ . '/../models/ReferencesModel.php';
require_once __DIR__ . '/../core/JwtService.php';
require_once __DIR__ . '/../core/ApiException.php';

class ReferencesController
{
    private ReferencesModel $referencesModel;

    public function __construct()
    {
        $this->referencesModel = new ReferencesModel();
    }

    public function obtenerReferencias(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['codArt']) || !is_string($data['codArt']) || trim($data['codArt']) === '') {
            throw new ApiException('cod_art_required', 'Código de artículo es requerido', 400);
        }

        // if (!is_array($data) || !isset($data['movAlm']) || !is_string($data['movAlm']) || trim($data['movAlm']) === '') {
        //     throw new ApiException('mov_alm_required', 'Movimiento de almacén es requerido', 400);
        // }

        $nroDoc = isset($data['nroDoc']) && is_string($data['nroDoc']) ? trim($data['nroDoc']) : '';

        $result = $this->referencesModel->getReferences(trim($data['codArt']), trim($data['movAlm']), $nroDoc);

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }
}
