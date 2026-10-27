<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class TransferenciaModel
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


    public function SavetOTRDet($conn, array $datos, string $origen, string $destino, string $otr, string $user)
    {

        $respuesta = false;

        $nro_otr = $otr;
        $articulo = $datos['codArt'];
        $cantidad_proy =  $datos['peso'];
        $alm_org =  $origen;
        $alm_dest = $destino;
        $usuario = $user;

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

    public function SaveOTRPallet($conn, string $numOtr, string $pallet, string $item, string $usr, string $cantidad, string $amporigen, string $ampnum, string $tipo)
    {


        $sql = "begin USP_INSERTA_HISTORIAL_TRANSF(:otr,:pallet,:item,:usuario,:cant,:amporige,:ampnum,:tipo);end;";

        $stmt = Database::prepare($conn, $sql);


        Database::bind($stmt, ':otr', $numOtr);
        Database::bind($stmt, ':pallet', $pallet);
        Database::bind($stmt, ':item', $item);
        Database::bind($stmt, ':usuario', $usr);
        Database::bind($stmt, ':cant', $cantidad);
        Database::bind($stmt, ':amporige', $amporigen);
        Database::bind($stmt, ':ampnum', $ampnum);
        Database::bind($stmt, ':tipo', $tipo);

        if (!$stmt) {

            throw new ApiException('link_otr_pallet_fail', oci_error($conn)['message'], 500);
        }


        $rs = Database::execute($stmt);


        if ($rs == false) {

            throw new ApiException('link_otr_pallet_fail', oci_error($stmt)['message'], 500);
        }

        oci_free_statement($stmt);


        return true;
    }
}
