<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class TransferPalletModel
{
    public function SaveTransPallet(string $palletO, string $palletD, string $usuario, array $datos)
    {

        $conn = Database::connection();

        $sql = "begin USP_INSERTA_historial_pallet_traslado(:palletO,:palletD,:traza,:cantidad,:usuario,:articulo,:ot); end;";

        $stmt = Database::prepare($conn, $sql);


        Database::bind($stmt, ':palletO', $palletO);
        Database::bind($stmt, ':palletD', $palletD);
        Database::bind($stmt, ':traza', $datos['traza']);
        Database::bind($stmt, ':cantidad', $datos['peso']);
        Database::bind($stmt, ':usuario', $usuario);
        Database::bind($stmt, ':articulo', $datos['articulo']);
        Database::bind($stmt, ':ot', $datos['ot']);

        if (!$stmt) {

            throw new ApiException('transp_failed', oci_error($conn)['message'], 500);

        }


        $rs = Database::execute($stmt);


        if ($rs == false) {

            throw new ApiException('transp_failed', oci_error($stmt)['message'], 500);

        }

        oci_free_statement($stmt);


        return true;
    }


}
