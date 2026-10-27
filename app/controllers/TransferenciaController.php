<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';
require_once __DIR__ . '/../models/TransferenciaModel.php';
require_once __DIR__ . '/ValeController.php';
require_once __DIR__ . '/UbicacionController.php';


class TransferenciaController
{
    private TransferenciaModel $transfer;
    private ValeController $vale;

    private UbicacionController $ubicacion;

    public function __construct()
    {
        $this->transfer = new TransferenciaModel();
        $this->vale = new ValeController();
        $this->ubicacion = new UbicacionController();
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
        if (!is_array($data) || !isset($data['idFormS']) || trim($data['idFormS']) === '') {
            throw new ApiException('idformS_required', 'Form1 es requerido', 400);
        }
        if (!is_array($data) || !isset($data['idFormI']) || trim($data['idFormI']) === '') {
            throw new ApiException('idformI_required', 'Form2 es requerido', 400);
        }
        if (!is_array($data) || !isset($data['detalles']) || !is_array($data['detalles'])) {
            throw new ApiException('detalles_required', 'Detalles son requeridos', 400);
        }

        $result = $this->transfer->SaveOTR($conn, $data);

        $num_otr = $result['otr'];

        $list_articulos = $data['detalles'] ?? [];

        foreach ($list_articulos as $key => $datos) {
            $det_otr = $this->transfer->SavetOTRDet($conn, $datos, $data['almOrigen'], $data['almDestino'], $num_otr, $data['usuario']);
        }

        $RegSalida = date('Y-m-d H:i:s', strtotime($data['fechaReg'] . ' +0 second'));
        $dataVSalida = [
            'tMov' => 'S04',
            'tipoRef' => 'OTR',
            'referencia' => $num_otr,
            'fechaReg' => $RegSalida,
            'almacen' => $data['almOrigen'],
            'observaciones' => $data['observaciones'],
            'idForm' => $data['idFormS'],
            'usuario' => $data['usuario'],
            'detalles' => $list_articulos,
            'pallet' => $data['pallet'] ?? null,
            'otr' => $num_otr,
        ];

        $valeS = $this->vale->GenerarVale($dataVSalida, $conn);



        $RegIngreso = date('Y-m-d H:i:s', strtotime($data['fechaReg'] . ' +1 second'));
        $dataVIngreso = [
            'tMov' => 'I04',
            'tipoRef' => 'OTR',
            'referencia' => $num_otr,
            'fechaReg' => $RegIngreso,
            'almacen' => $data['almDestino'],
            'observaciones' => $data['observaciones'],
            'idForm' => $data['idFormI'],
            'usuario' => $data['usuario'],
            'detalles' => $list_articulos,
            'pallet' => $data['pallet'] ?? null,
            'otr' => $num_otr,
        ];

        $valeI = $this->vale->GenerarVale($dataVIngreso, $conn);

        $resultOtr = [
            'success' => true,
            'vales' => [$valeS['nro_vale'], $valeI['nro_vale']],
        ];

        $dataUbic = [
            'codUbicacion' => $data['ubicacion'],
            'pallet' => $data['pallet'],
            'valeI' => $valeI['nro_vale'],
            'valeS' => $valeS['nro_vale'],
            'usuario' => $data['usuario'],
            'almacenDest' => $data['almDestino']
        ];

        $this->ubicacion->guardarUbicacion($dataUbic);

        Database::commit($conn);

        return $resultOtr;
    }
}
