<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class ValeModel
{

    public function saveVale($conn, array $datos)
    {

        $respuesta = "";
        $tipo_mov = ($datos['tMov']);
        $tipo_doc = ($datos['tipoRef']);

        $referencia = $datos['referencia'];

        $recepcionado = $datos['recepcionado'] ?? '';
        $recepcionado = strtoupper($recepcionado);

        $obs = $datos['observaciones'] ?? '';
        $obs = strtoupper($obs);

        $fecha_registro = $datos['fechaReg'];
        $fecha_registro = str_replace('T', ' ', $fecha_registro);

        $almacen = $datos['almacen'];


        $idReg = $datos['idForm'];


        $usuario = $datos['usuario'];

        $nro_vale = "";


        $sql = "begin usp_save_vale_app(:as_tipo_mov,:as_usr,:as_refer,:as_fecha,:as_recepcionado,:as_obs,:as_tipo_refer,:as_almacen,:as_idreg,:out_vale) ;end; ";

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':as_tipo_mov', $tipo_mov);
        Database::bind($stmt, ':as_usr', $usuario);
        Database::bind($stmt, ':as_refer', $referencia);
        Database::bind($stmt, ':as_fecha', $fecha_registro);
        Database::bind($stmt, ':as_recepcionado', $recepcionado);
        Database::bind($stmt, ':as_obs', $obs);
        Database::bind($stmt, ':as_tipo_refer', $tipo_doc);
        Database::bind($stmt, ':as_almacen', $almacen);
        Database::bind($stmt, ':as_idreg', $idReg);
        Database::bind($stmt, ':out_vale', $nro_vale, 10);

        if (!$stmt) {

            throw new ApiException('vale_save_error', oci_error($conn)['message'], 400);
        }


        $rs = Database::execute($stmt);


        if ($rs == false) {
            throw new ApiException('vale_save_error', oci_error($stmt)['message'], 400);
        } else {

            $respuesta =

                [
                    'success' => true,
                    'nro_vale' => $nro_vale
                ];
        }

        oci_free_statement($stmt);

        return $respuesta;
    }


    public function saveValeDetalle($conn, string $n_vale, string $fecha_reg, string $tipomov, array $datos)
    {


        $respuesta = "";

        $tipo_mov = $tipomov;
        $nro_vale = $n_vale;
        $fecha_registro = $fecha_reg;
        $fecha_registro = str_replace('T', ' ', $fecha_registro);

        $tipo_doc = $datos['referencia']['tipo'];
        $referencia = ($datos['referencia']['nro']);
        $articulo = $datos['codArt'];
        $cantidad = $datos['peso'];
        $matriz = $datos['matriz'];
        $lote = $datos['lote'];
        $moneda = "S/.";

        $almacen = $datos['referencia']['almacen'];
        $nromov = $datos['referencia']['nroMov'];


        $out_nroMov = "";
        $out_orgMov = "";


        $sql = "begin usp_save_vale_detalle_appV2(:as_nro_vale,:as_refer,:as_fecha,:as_art,:as_cantidad,:as_matriz,:as_lote,:as_moneda,:as_tipo_refer,:as_tipo_mov,:as_almacen,:as_nromov,:out_nromov,:out_orgmov);end; ";

        $stmt = Database::prepare($conn, $sql);


        Database::bind($stmt, ':as_nro_vale', $nro_vale);
        Database::bind($stmt, ':as_tipo_refer', $tipo_doc);
        Database::bind($stmt, ':as_refer', $referencia);
        Database::bind($stmt, ':as_fecha', $fecha_registro);
        Database::bind($stmt, ':as_art', $articulo);
        Database::bind($stmt, ':as_cantidad', $cantidad);
        Database::bind($stmt, ':as_matriz', $matriz);
        Database::bind($stmt, ':as_lote', $lote);
        Database::bind($stmt, ':as_moneda', $moneda);
        Database::bind($stmt, ':as_tipo_mov', $tipo_mov);
        Database::bind($stmt, ':as_almacen', $almacen);
        Database::bind($stmt, ':as_nromov', $nromov);
        Database::bind($stmt, ':out_nromov', $out_nroMov, 10);
        Database::bind($stmt, ':out_orgmov', $out_orgMov, 2);

        if (!$stmt) {

            throw new ApiException('vale_det_error', oci_error($conn)['message'], 400);
        }


        $rs = Database::execute($stmt);


        if ($rs == false) {

            throw new ApiException('vale_det_error', oci_error($stmt)['message'], 400);
        } else {

            $respuesta =

                [
                    'success' => true,
                    'message' => [
                        'nromov' => $out_nroMov,
                        'orgmov' => $out_orgMov
                    ]
                ];
        }

        oci_free_statement($stmt);


        return $respuesta;
    }
}
