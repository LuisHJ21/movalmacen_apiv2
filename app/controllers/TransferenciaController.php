<?php

require_once __DIR__ . '/../models/TransferenciaModel.php';
require_once __DIR__ . '/../core/Database.php.php';
require_once __DIR__ . '/../core/ApiException.php';


class TransferenciaController
{
    private TransferenciaModel $transfer;

    public function __construct()
    {
        $this->transfer = new TransferenciaModel();
    }

    public function GenerarOTR(Request $request)
    {

        $data = $request->getBody();


        $conn = Database::connection();

        if (!is_array($data) || !isset($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('user_required', 'Usuario es requerido', 400);
        }
        if (!is_array($data) || !isset($data['pallet']) || trim($data['pallet']) === '') {
            throw new ApiException('pallet_required', 'Pallet es requerido', 400);
        }
        if (!is_array($data) || !isset($data['almOrigen']) || trim($data['almOrigen']) === '') {
            throw new ApiException('alm_org_required', 'Almacen Origen es requerido', 400);
        }
        if (!is_array($data) || !isset($data['almDestino']) || trim($data['almDestino']) === '') {
            throw new ApiException('alm_dest_required', 'Almacen Destino es requerido', 400);
        }
        if (!is_array($data) || !isset($data['idForm']) || trim($data['idForm']) === '') {
            throw new ApiException('idform_required', 'Form es requerido', 400);
        }

        if (!is_array($data) || !isset($data['detalles']) || !is_array($data['detalles'])) {
            throw new ApiException('detalles_required', 'Detalles son requeridos', 400);
        }

        $result = $this->transfer->SaveOTR($conn, $data);

        $num_otr = $result['otr'];

        $list_articulos = $data['detalles'] ?? [];

        foreach ($list_articulos as $key => $datos) {
            $det_otr = $this->transfer->SavetOTRDet($conn, $datos);

        }


    }
}
