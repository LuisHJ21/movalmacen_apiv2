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

        if (!isset($data['usuario']) || !is_string($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('usuario_required', 'Usuario es requerido', 400);
        }

        // El body trae "detalle" (se acepta también "detalles")
        $detalles = $data['detalle'] ?? $data['detalles'] ?? null;
        if (!is_array($detalles) || empty($detalles)) {
            throw new ApiException('detalles_Empty', 'Las cajas son requeridas', 400);
        }

        foreach ($detalles as $i => $detalle) {
            if (!is_array($detalle) || !isset($detalle['traza'], $detalle['peso'], $detalle['articulo']) || !is_numeric($detalle['peso']) || (float) $detalle['peso'] <= 0) {
                throw new ApiException('detalle_invalido', 'Detalle ' . ($i + 1) . ': traza, peso y artículo son requeridos', 400);
            }
        }

        $result = $this->transfer->SaveTransPallet(trim($data['palletO']), trim($data['palletD']), trim($data['usuario']), $detalles);

        header('Cache-Control: no-store');


        return [
            'success' => $result,
        ];

    }

}
