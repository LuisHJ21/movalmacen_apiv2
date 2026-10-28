<?php

require_once __DIR__ . '/../models/PalletModel.php';
require_once __DIR__ . '/../core/JwtService.php';
require_once __DIR__ . '/../core/ApiException.php';

class PalletController
{
    private PalletModel $palletModel;

    public function __construct()
    {
        $this->palletModel = new PalletModel();
    }

    public function VerificarPallet(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['pallet']) || !is_string($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Número de pallet es requerido', 400);
        }

        $result = $this->palletModel->VerifyStatePallet(trim($data['pallet']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function ObtenerDatosPallet(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['pallet']) || !is_string($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Número de pallet es requerido', 400);
        }

        // tipoMov vacío ("") es válido: trae todos los ítems sin filtrar por movimiento (ej. transferencia entre pallets)
        if (!isset($data['tipoMov']) || !is_string($data['tipoMov'])) {
            throw new ApiException('movement_type_required', 'Tipo de movimiento es requerido', 400);
        }

        if (!isset($data['codart']) || !is_string($data['codart']) || trim($data['codart']) === '') {
            throw new ApiException('article_code_required', 'Código de artículo es requerido', 400);
        }

        $result = $this->palletModel->DataPalletforVale(trim($data['pallet']), trim($data['tipoMov']), trim($data['codart']));

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function ObtenerInfoPallet(Request $request)
    {

        $data = $request->getParams();

        if (!is_array($data) || !isset($data['pallet']) || !is_string($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Número de pallet es requerido', 400);
        }

        $result = $this->palletModel->PalletInfo($data['pallet']);
        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function ObtenerDatosTransferPallet(Request $request)
    {
        $data = $request->getParams();
        if (!is_array($data) || !isset($data['pallet']) || !is_string($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Número de pallet es requerido', 400);
        }
        if (!is_array($data) || !isset($data['almacen']) || !is_string($data['almacen']) || trim($data['almacen']) === '') {
            throw new ApiException('almacen_required', 'Almacen es requerido', 400);
        }
        if (!is_array($data) || !isset($data['tipo']) || !is_string($data['tipo']) || trim($data['tipo']) === '') {
            throw new ApiException('tipo_required', 'tipo de solicitud requerido', 400);
        }

        $result = $this->palletModel->DataPalletForTransfer($data);
        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }
}
