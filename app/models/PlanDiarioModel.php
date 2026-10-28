<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class PlanDiarioModel
{

    public function listActivePlans()
    {
        $conn = Database::connection();

        $sql = " SELECT 
                p.COD_PARTE_PRODUCC AS CODIGO_PLAN,
                p.FLAG_ESTADO,
                e.ESPECIE,
                p.DESCRIPCION
                FROM PARTE_PRODUCCION p
                INNER JOIN TG_ESPECIES e 
                ON e.ESPECIE = p.ESPECIE
                WHERE p.FLAG_ESTADO = '1'";



        $stmt = Database::prepare($conn, $sql);
        Database::execute($stmt);

        $data = [];

        // CARGA LISTA
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);

        if (empty($data)) {
            throw new ApiException('pland_empty', 'No hay Planes diarios', 404);
        }
        return $data;
    }
}
