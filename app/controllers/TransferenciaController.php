<?php

require_once __DIR__ . '/../core/Database.php.php';
require_once __DIR__ . '/../core/ApiException.php';
require_once __DIR__ . '/../models/TransferenciaModel.php';
require_once __DIR__ . '/ValeController.php';


class TransferenciaController
{
    private TransferenciaModel $transfer;
    private ValeController $vale;

    public function __construct()
    {
        $this->transfer = new TransferenciaModel();
        $this->vale = new ValeController();
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
        if (!is_array($data) || !isset($data['idFormS']) || trim($data['idForm']) === '') {
            throw new ApiException('idformS_required', 'Form1 es requerido', 400);
        }
        if (!is_array($data) || !isset($data['idFormI']) || trim($data['idForm']) === '') {
            throw new ApiException('idformI_required', 'Form2 es requerido', 400);
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

        $RegSalida = date('Y-m-d H:i:s', strtotime($datos['fechaReg'] . ' +0 second'));
        $dataVSalida = [
            'tMov' => 'S04',
            'tipo' => 'OTR',
            'referencia' => $num_otr,
            'fechaReg' => $RegSalida,
            'almacen' => $datos['almOrigen'],
            'observaciones' => $datos['observaciones'],
            'idForm' => $datos['idFormS'],
            'usuario' => $datos['usuario'],
            'detalles' => $list_articulos,
            'pallet' => $datos['pallet'] ?? null,
            'otr' => $num_otr,
        ];

        $valeS = $this->vale->GenerarVale($dataVSalida, $conn);



        $RegIngreso = date('Y-m-d H:i:s', strtotime($datos['fechaReg'] . ' +1 second'));
        $dataVIngreso = [
            'tMov' => 'I04',
            'tipo' => 'OTR',
            'referencia' => $num_otr,
            'fechaReg' => $RegIngreso,
            'almacen' => $datos['almDestino'],
            'observaciones' => $datos['observaciones'],
            'idForm' => $datos['idFormI'],
            'usuario' => $datos['usuario'],
            'detalles' => $list_articulos,
            'pallet' => $datos['pallet'] ?? null,
            'otr' => $num_otr,
        ];

        $valeI = $this->vale->GenerarVale($dataVIngreso, $conn);

        $resultOtr = [
            'success' => true,
            'vales' => [$valeS['nro_vale'],$valeS['nro_vale']],
        ];

        Database::commit($conn);

        return $resultOtr;


    }
}
