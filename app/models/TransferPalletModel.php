<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class TransferPalletModel
{
    /**
     * Registra el traslado de cajas del pallet origen al destino: una llamada a
     * USP_INSERTA_historial_pallet_traslado por cada detalle { ot, traza, peso, articulo }.
     * Todo en una sola transacción: si un detalle falla, no queda nada registrado.
     */
    public function SaveTransPallet(string $palletO, string $palletD, string $usuario, array $detalles)
    {
        return Database::transaction(function ($conn) use ($palletO, $palletD, $usuario, $detalles) {
            $sql = "begin USP_INSERTA_historial_pallet_traslado(:palletO,:palletD,:traza,:cantidad,:usuario,:articulo,:ot); end;";

            $stmt = Database::prepare($conn, $sql);

            // oci_bind_by_name enlaza por referencia: se cambian los valores y se vuelve a ejecutar
            $traza = $cantidad = $articulo = $ot = '';
            Database::bind($stmt, ':palletO', $palletO);
            Database::bind($stmt, ':palletD', $palletD);
            Database::bind($stmt, ':traza', $traza);
            Database::bind($stmt, ':cantidad', $cantidad);
            Database::bind($stmt, ':usuario', $usuario);
            Database::bind($stmt, ':articulo', $articulo);
            Database::bind($stmt, ':ot', $ot);

            foreach ($detalles as $detalle) {
                $traza = trim((string) ($detalle['traza'] ?? ''));
                $cantidad = (string) ($detalle['peso'] ?? '');
                $articulo = trim((string) ($detalle['articulo'] ?? ''));
                $ot = trim((string) ($detalle['ot'] ?? ''));

                // Lanza excepción si falla: la transacción se revierte
                Database::execute($stmt);
            }

            oci_free_statement($stmt);

            return true;
        });
    }
}
