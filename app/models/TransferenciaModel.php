<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class TransFerenciaController
{
    public function SaveOTR($conn, array $datos)
    {


        $respuesta = [];

        $nro_otr = "";

        $alm_org = $datos['almOrigen'];
        $alm_dest = $datos['almDestino'];
        $usuario = $datos['usuario'];

        $sql = "begin usp_orden_translado_aprobado(:usuario,:al_org,:al_dest,:resultado);end; ";

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':usuario', $usuario);
        Database::bind($stmt, ':al_org', $alm_org);
        Database::bind($stmt, ':al_dest', $alm_dest);
        Database::bind($stmt, ':resultado', $nro_otr, 10);


        if (!$stmt) {
            throw new ApiException('otr_save_error', oci_error($conn)['message'], 400);
        }



        $rs = Database::execute($stmt);


        if ($rs == false) {

            throw new ApiException('otr_save_error', oci_error($stmt)['message'], 400);


        } else {

            $respuesta

            = [
                'success' => true,
                'otr' => $nro_otr,
            ];

        }

        oci_free_statement($stmt);

        return $respuesta;

    }


    public function SavetOTRDet($conn, array $datos)
    {

        $respuesta = false;

        $nro_otr = $datos['otr'];
        $articulo = $datos['dataArt']['cod_Art'];
        $cantidad_proy =  $datos['dataArt']['peso'];
        $alm_org =  $datos['almOrigen'];
        $alm_dest = $datos['almDestino'];
        $usuario = $datos['usuario'];

        $sql = "begin usp_orden_translado_aprobado_det(:as_art,:as_otr,:as_cant,:as_origen,:as_destino,:as_usuario);end; ";

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':as_otr', $nro_otr);
        Database::bind($stmt, ':as_art', $articulo);
        Database::bind($stmt, ':as_cant', $cantidad_proy);
        Database::bind($stmt, ':as_origen', $alm_org);
        Database::bind($stmt, ':as_destino', $alm_dest);
        Database::bind($stmt, ':as_usuario', $usuario);

        if (!$stmt) {

            throw new ApiException('otr_det_save_error', oci_error($conn)['message'], 400);

        }

        $rs = Database::execute($stmt);

        if ($rs == false) {

            throw new ApiException('otr_det_save_error', oci_error($stmt)['message'], 400);

        } else {

            $respuesta = true;

        }

        oci_free_statement($stmt);

        return $respuesta;

    }


}
