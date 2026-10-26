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

            $respuesta

                = [
                    'success' => true,
                    'nro_vale' => $nro_vale,
                ];
        }

        oci_free_statement($stmt);

        return $respuesta;
    }


    public function saveValeDetalle($conn, string $n_vale, string $fecha_reg, string $tipomov, array $datos, string $almacenRef = '')
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

        $almacen = $almacenRef == '' ? $datos['referencia']['almacen'] : $almacenRef;
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

            $respuesta

                = [
                    'success' => true,
                    'message' => [
                        'nromov' => $out_nroMov,
                        'orgmov' => $out_orgMov,
                    ],
                ];
        }

        oci_free_statement($stmt);


        return $respuesta;
    }

    public function getHistory($datos)
    {
        $db = Database::connection();

        $usuario = $datos['usuario'];
        $desde = $datos['fechIni'];
        $hasta = $datos['fechFin'];

        $data = [];

        $sql = "SELECT v.nro_vale,v.flag_estado,to_char(v.fec_registro,'dd/mm/yyyy HH24:mi:ss') fecha,amt.desc_tipo_mov,v.cod_usr,v.tipo_refer,v.nro_refer,v.fec_ingreso,v.almacen 
        from vale_mov v,ARTICULO_MOV_TIPO amt
        where v.tipo_mov=amt.tipo_mov 
        and v.cod_usr = '$usuario' and to_char(v.fec_ingreso,'yyyy-mm-dd')>='$desde' and to_char(v.fec_ingreso,'yyyy-mm-dd')<='$hasta' 
        order by v.nro_vale DESC";

        $stmt = Database::prepare($db, $sql);
        $data = [];
        Database::execute($stmt);

        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }


        if (empty($data)) {
            throw new ApiException('vale_history_error', 'No se encontraron registros para el usuario y rango de fechas proporcionados.', 404);
        }


        oci_free_statement($stmt);
        // oci_close($db);

        return $data;
    }

    public function getValeDetail(array $datos)
    {
        $db = Database::connection();
        $vale = $datos['vale'];
        $data = [];

        $sql = "SELECT am.cod_origen, (select doc_mov_almacen from logparam where reckey = '1') as tipo_doc,
        am.nro_vale,am.nro_mov, am.cod_art, a.desc_art, a.und, usf_alm_cant_proc_am(am.cod_origen, am.nro_mov) as cant_procesada, usf_alm_cant_proc_und2_am(am.cod_origen, am.nro_mov) as cant_proc_und2, am.precio_unit, am.origen_mov_proy, am.nro_mov_proy, am.oper_sec, am.cencos, am.cnta_prsp, decode(a.flag_und2,'0',null, a.flag_und2) as flag_und2, am.matriz, NVL(am.flag_saldo_libre, '0') as flag_saldo_libre, am.centro_benef, am.nro_reservacion, am.item_reservacion,vm.observaciones

        from articulo_mov am, articulo a,vale_mov vm
        where a.cod_art = am.cod_art
        and am.nro_vale=vm.nro_vale
        --and am.flag_Estado <> '0'
        and am.nro_Vale = :vale
        and (usf_alm_cant_proc_am(am.cod_origen, am.nro_mov) > 0 or
        usf_alm_cant_proc_und2_am(am.cod_origen, am.nro_mov) > 0 )";



        $stmt = Database::prepare($db, $sql);

        Database::bind($stmt, ':vale', $vale);

        Database::execute($stmt);

        while ($row = oci_fetch_assoc($stmt)) {


            $data[] = $row;
        }


        if (empty($data)) {
            throw new ApiException('vale_detail_not_found', 'Detalle no encontrado', 404);
        }


        oci_free_statement($stmt);


        return $data;
    }
}
