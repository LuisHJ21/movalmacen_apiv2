<?php

require_once __DIR__ . '/../models/ValeModel.php';


class ValeController
{

    private ValeModel $valeModel;

    public function __construct()
    {
        $this->valeModel = new ValeModel();
    }

    public function GenerarVale(Request $request, $conn = null)
    {
        $data = $request->getBody();
        $conExterna = $conn !== null;

        if (!is_array($data) || !isset($data['tMov']) || !is_string($data['tMov']) || trim($data['tMov']) === '') {
            throw new ApiException('tMov_required', 'Tipo de movimiento es requerido', 400);
        }

        if (!is_array($data) || !isset($data['tipoRef']) || !is_string($data['tipoRef']) || trim($data['tipoRef']) === '') {
            throw new ApiException('tipo_required', 'Tipo de documento es requerido', 400);
        }

        if (!is_array($data) || !isset($data['referencia']) || !is_string($data['referencia']) || trim($data['referencia']) === '') {
            throw new ApiException('referencia_required', 'Referencia es requerida', 400);
        }

        if (!is_array($data) || !isset($data['fechaReg']) || !is_string($data['fechaReg']) || trim($data['fechaReg']) === '') {
            throw new ApiException('fechaReg_required', 'Fecha de registro es requerida', 400);
        }

        if (!is_array($data) || !isset($data['usuario']) || !is_string($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('usuario_required', 'Usuario es requerido', 400);
        }

        if (!$conExterna) {
            $conn = Database::connection();
        }



        $result = $this->valeModel->saveVale($conn, $data);

        $list_articulos = $data['detalles'] ?? [];

        if (!is_array($list_articulos) || empty($list_articulos)) {
            throw new ApiException('detalles_required', 'Detalles de artículos son requeridos', 400);
        }

        $nro_vale = $result['nro_vale'] ?? '';
        $codParte = $data['codParte'] ?? '';

        foreach ($list_articulos as $key => $articulo) {
            if ($data['tMov'] == 'S04') {
                $articulo['lote'] = $articulo['loteOrg'];
                $articulo['referencia']['tipo'] = $data['tipoRef'];
                $articulo['referencia']['nro'] = $data['referencia'];
                $articulo['referencia']['almacen'] = $data['almacen'];
            } elseif ($data['tMov'] == 'I04') {
                $articulo['lote'] = isset($articulo['lote']) ? $articulo['lote'] : $articulo['loteDest'];
                $articulo['referencia']['tipo'] = $data['tipoRef'];
                $articulo['referencia']['nro'] = $data['referencia'];
                $articulo['referencia']['almacen'] = $data['almacen'];
            }

            $det_vale = $this->valeModel->saveValeDetalle($conn, $nro_vale, $data['fechaReg'], $data['tMov'], $articulo);


            // if ($data['tMov'] !== 'I04') {
            //     $vincular = $this->pallet->updateMovPallet(
            //         $conn,
            //         $articulo['numPallet'] ?? '',
            //         $articulo['item'],
            //         $det_vale['message']['nromov'],
            //         $det_vale['message']['orgmov'],
            //         substr($data['tMov'], 0, 1),
            //         $codParte
            //     );
            //     if ($vincular['result'] == 'error') {
            //         throw new Exception($vincular['message']);
            //     }
            // }

            // if (isset($data['pallet']) && trim($data['pallet']) != "" && $data['tMov'] === 'S04') {
            //     $otrPallet = $this->tranfer->saveOtrPallet($conn, $data['otr'], $data['pallet'], $articulo['item'], $data['usuario'], $articulo['peso'], $det_vale['message']['orgmov'], $det_vale['message']['nromov'], 'O');
            //     if ($otrPallet['result'] == 'error') {
            //         throw new Exception($otrPallet['message']);
            //         break;
            //     }
            // } elseif (isset($datos['pallet']) && trim($datos['pallet']) != "" && $datos['tMov'] === 'I04') {
            //     $otrPallet = $this->tranfer->saveOtrPallet($conexion, $datos['otr'], $datos['pallet'], $articulo['item'], $datos['usuario'], $articulo['peso'], $det_vale['message']['orgmov'], $det_vale['message']['nromov'], 'D');

            //     if ($otrPallet['result'] == 'error') {

            //         throw new Exception($otrPallet['message']);
            //         break;
            //     }
            // }
        }

        if (!$conExterna) {
            Database::commit($conn);
        }

        header('Cache-Control: no-store');
        return [
            'success' => true,
            'message' => 'Vale generado correctamente',
            'nro_vale' => $nro_vale
        ];
    }
}
