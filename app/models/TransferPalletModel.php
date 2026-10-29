<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class TransferPalletModel
{
    public function SaveTransPallet(string $palletO, string $palletD, string $usuario, array $detalles)
    {


        return Database::transaction(function ($conn) use ($palletO, $palletD, $usuario, $detalles) {
            // cantidad va como texto con punto y 4 decimales (ej. "15.5000") y se convierte en Oracle con el separador
            // decimal explícito: OCI_B_INT truncaba los decimales y un texto dependería del NLS de la sesión
            $sql = "begin USP_INSERTA_historial_pallet_traslado(:palletO,:palletD,:traza,"
                . "TO_NUMBER(:cantidad, '999999999990D9999', 'NLS_NUMERIC_CHARACTERS=''.,'''),"
                . ":usuario,:articulo,:ot); end;";

            $stmt = Database::prepare($conn, $sql);


            $traza = '';
            $cantidad = '';
            $articulo = '';
            $ot = '';

            Database::bind($stmt, ':palletO', $palletO, 10);
            Database::bind($stmt, ':palletD', $palletD, 10);
            Database::bind($stmt, ':traza', $traza, 20);
            Database::bind($stmt, ':cantidad', $cantidad, 20);
            Database::bind($stmt, ':usuario', $usuario);
            Database::bind($stmt, ':articulo', $articulo, 12);
            Database::bind($stmt, ':ot', $ot, 10);

            foreach ($detalles as $detalle) {

                $traza = trim((string) ($detalle['traza'] ?? ''));
                $cantidad = number_format(round((float) ($detalle['peso'] ?? 0), 4), 4, '.', '');
                $articulo = trim((string) ($detalle['articulo'] ?? ''));
                $ot = trim((string) ($detalle['ot'] ?? ''));


                Database::execute($stmt);
            }

            oci_free_statement($stmt);

            return true;
        });
    }
}
