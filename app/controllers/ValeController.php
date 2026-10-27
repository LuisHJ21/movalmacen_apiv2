<?php

require_once __DIR__ . '/../models/ValeModel.php';
require_once __DIR__ . '/../models/PalletModel.php';
require_once __DIR__ . '/../models/TransferenciaModel.php';


class ValeController
{
    private ValeModel $valeModel;
    private PalletModel $palletModel;

    private TransferenciaModel $transferenciaModel;
    public function __construct()
    {
        $this->valeModel = new ValeModel();
        $this->palletModel = new PalletModel();
        $this->transferenciaModel = new TransferenciaModel();
    }

    public function GenerarVale(Request|array $request, $conn = null)
    {

        $data = $request instanceof Request
            ? $request->getBody()
            : $request;


        $conExterna = $conn !== null;

        if (!is_array($data) || !isset($data['tMov']) || !is_string($data['tMov']) || trim($data['tMov']) === '') {
            throw new ApiException('tMov_required', 'Tipo de movimiento es requerido', 400);
        }

        // if (!is_array($data) || !isset($data['tipoRef']) || !is_string($data['tipoRef']) || trim($data['tipoRef']) === '') {
        //     throw new ApiException('tipo_required', 'Tipo de documento es requerido', 400);
        // }

        // if (!is_array($data) || !isset($data['referencia']) || !is_string($data['referencia']) || trim($data['referencia']) === '') {
        //     throw new ApiException('referencia_required', 'Referencia es requerida', 400);
        // }

        if (!is_array($data) || !isset($data['fechaReg']) || !is_string($data['fechaReg']) || trim($data['fechaReg']) === '') {
            throw new ApiException('fechaReg_required', 'Fecha de registro es requerida', 400);
        }

        if (!is_array($data) || !isset($data['usuario']) || !is_string($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('usuario_required', 'Usuario es requerido', 400);
        }

        // Con conexión externa (ej. transferencia) confirma quien llama; si no, el vale y su detalle van en su propia transacción
        if ($conExterna) {
            return $this->registrarVale($conn, $data);
        }

        return Database::transaction(fn($conn) => $this->registrarVale($conn, $data));
    }

    private function registrarVale($conn, array $data): array
    {
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
                $articulo['lote'] ??= $articulo['loteDest'];
                $articulo['referencia']['tipo'] = $data['tipoRef'];
                $articulo['referencia']['nro'] = $data['referencia'];
                $articulo['referencia']['almacen'] = $data['almacen'];
            }

            $det_vale = $this->valeModel->saveValeDetalle($conn, $nro_vale, $data['fechaReg'], $data['tMov'], $articulo, $data['almacen']);


            if ($data['tMov'] !== 'I04') {
                $vinculo = $this->palletModel->UpdateVinculoPallet(
                    $conn,
                    $articulo['pallet'] ?? '',
                    $articulo['item'],
                    $det_vale['message']['nromov'],
                    $det_vale['message']['orgmov'],
                    substr($data['tMov'], 0, 1),
                    $codParte
                );


            }

            if (isset($data['pallet']) && trim($data['pallet']) != "" && $data['tMov'] === 'S04') {
                $this->transferenciaModel->SaveOTRPallet($conn, $data['otr'], $data['pallet'], $articulo['item'], $data['usuario'], $articulo['peso'], $det_vale['message']['orgmov'], $det_vale['message']['nromov'], 'O');
            } elseif (isset($data['pallet']) && trim($data['pallet']) != "" && $data['tMov'] === 'I04') {
                $this->transferenciaModel->SaveOTRPallet($conn, $data['otr'], $data['pallet'], $articulo['item'], $data['usuario'], $articulo['peso'], $det_vale['message']['orgmov'], $det_vale['message']['nromov'], 'D');
            }
        }

        header('Cache-Control: no-store');
        return [
            'success' => true,
            'message' => 'Vale generado correctamente',
            'nro_vale' => $nro_vale,
        ];
    }

    public function GetHistorial(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['usuario']) || !is_string($data['usuario']) || trim($data['usuario']) === '') {
            throw new ApiException('usuario_required', 'Usuario es requerido', 400);
        }

        if (!is_array($data) || !isset($data['fechIni']) || !is_string($data['fechIni']) || trim($data['fechIni']) === '') {
            throw new ApiException('fechIni_required', 'Fecha de inicio es requerida', 400);
        }

        if (!is_array($data) || !isset($data['fechFin']) || !is_string($data['fechFin']) || trim($data['fechFin']) === '') {
            throw new ApiException('fechFin_required', 'Fecha de fin es requerida', 400);
        }

        $result = $this->valeModel->getHistory($data);

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }

    public function GetValeDetalle(Request $request)
    {
        $data = $request->getParams();

        if (!is_array($data) || !isset($data['vale']) || !is_string($data['vale']) || trim($data['vale']) === '') {
            throw new ApiException('vale_required', 'Vale es requerido', 400);
        }

        $result = $this->valeModel->getValeDetail($data);

        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $result];
    }
}
