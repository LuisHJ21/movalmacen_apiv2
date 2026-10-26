<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';


class ReferencesModel
{
    public function getReferences(string $codArt, string $movAlm, string $nroDoc)
    {
        $conn = Database::connection();
        $sql = "";

        $tipo_mov = substr($movAlm, 0, 1);

        $nroDoc = $tipo_mov === 'I' ? $nroDoc : '';

        $sql = "begin :cursor := USF_INFO_VALES_OT_OV_APP(:articulo,:mov,:doc); end;";
        $stmt = oci_parse($conn, $sql);

        $cursor = oci_new_cursor($conn);

        oci_bind_by_name($stmt, ':cursor', $cursor, -1, OCI_B_CURSOR);
        oci_bind_by_name($stmt, ':articulo', $codArt);
        oci_bind_by_name($stmt, ':mov', $movAlm);
        oci_bind_by_name($stmt, ':doc', $nroDoc);

        $data = [];
        Database::execute($stmt);
        oci_execute($cursor);

        if (!empty($cursor)) {
            while ($row = oci_fetch_assoc($cursor)) {

                $data[] = $row;
            }
        }

        if (empty($data)) {
            throw new ApiException('references_not_found', 'No se encontraron referencias para el artículo y movimiento proporcionados', 404);
        }



        oci_free_statement($stmt);
        oci_free_cursor($cursor);

        return $data;
    }
}

/* class otModel extends mainModel
{
    public function getOts($cod_art, $mov_alm, $nro_doc)
    {
        $db = mainModel::conectar();

        $tipo_mov = substr($mov_alm, 0, 1);

        if ($tipo_mov !== 'I' && trim($nro_doc) === '') {
            return [];
        }
        if ($tipo_mov === 'I' && trim($nro_doc) === '') {
            return [
                'result' => 'error',
                'message' => 'SE SOLICITA NÚMERO DE REFERENCIA'
            ];
        }
        $consulta = "SELECT * FROM USF_INFO_VALES_OT_OV_APP('$cod_art','$mov_alm','$nro_doc')";
        $query = oci_parse($db, $consulta);

        $data = [];

        oci_execute($query);

        while ($row = oci_fetch_row($query)) {
            $data[] = [
                'nro' => trim($row[0] ?? ''),
                'desc' => trim($row[1] ?? ''),
                'tipo' => trim($row[2] ?? ''),
                'almacen' => trim($row[4] ?? ''),
                'nroMov' => trim($row[5] ?? ''),
                'restante' => trim($row[3] ?? ''),
                'ot_tipo' => trim($row[6] ?? ''),
            ];
        }

        oci_free_statement($query);
        return $data;
    }
} */
