<?php

require_once __DIR__ . '/../models/TransferPalletModel.php';


class TransferPalletController
{
    private TransferPalletModel $transfer;

    public function __construct()
    {
        $this->transfer = new TransferPalletModel();
    }

    public function GenerarTraslado(Request $request)
    {

        $data = $request->getBody();

        if (!is_array($data) || !isset($data['palletO']) || !is_string($data['palletO']) || trim($data['palletO']) === '') {
            throw new ApiException('palletO_Empty', 'Pallet origen es requerido', 400);
        }
        if (!is_array($data) || !isset($data['palletD']) || !is_string($data['palletD']) || trim($data['palletD']) === '') {
            throw new ApiException('palletD_Empty', 'Pallet destino es requerido', 400);
        }

        if (!is_array($data) || !isset($data['detalles']) || !is_array($data['detalles']) || empty($data['detalles'])) {
            throw new ApiException('detalles_Empty', 'Las cajas son requeridas', 400);
        }

        $result = $this->transfer->SaveTransPallet($data['palletO'], $data['palletD'], $data['usuario'], $data['detalles']);


        return [
            'success' => $result,
        ];

    }

}
