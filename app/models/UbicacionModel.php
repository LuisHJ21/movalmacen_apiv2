<?php

require_once __DIR__ . '/../core/Database.php';

class UbicacionModel
{

    public function getUbicaciones(string $filtro)
    {
        $db = Database::connection();
        $data = [];
        $sql = "WITH FILTRO AS (
                    SELECT UPA.COD_RACK FROM UBIC_PALLET_ASIG UPA, UBIC_PALLET UP
                    where 
                    UP.COD_RACK=UPA.COD_RACK 
                    AND UP.ORIGEN_RACK=UPA.ORIGEN_RACK
                    AND UP.FLAG_MULTI<>1
                )                    
                                    
                SELECT trim(UP.COD_RACK) AS cod_rack,   
                        UP.ORIGEN_RACK,   
                        substr(cod_rack,1,2) AS camara,   
                        substr(cod_rack,3,1) AS lado,   
                        substr(cod_rack,4,2) AS nivel,   
                        substr(cod_rack,6,1) AS columna,   
                        substr(cod_rack,7,2) AS posicion ,
                          UP.FLAG_MULTI  
                    FROM UBIC_PALLET UP 
                WHERE UP.FLAG_MOV = '1'
                AND   UP.COD_RACK  NOT IN (SELECT T.COD_RACK FROM FILTRO T) 
                AND   UP.COD_RACK LIKE :filtro
                ORDER BY UP.COD_RACK ASC ";

        $stmt = Database::prepare($db, $sql);
        $filtroBind = "%$filtro%";

        Database::bind($stmt, ':filtro', $filtroBind);
        Database::execute($stmt);



        while ($row = oci_fetch_assoc($stmt)) {
            $datos = array_map('trim', $row);

            $data[] = $datos;
        }

        if (empty($data)) {
            throw new ApiException('ubicacion_not_found', 'Ubicación no encontrada', 404);
        }


        oci_free_statement($stmt);

        return $data;
    }

    public function getUbicacionesFiltro(string $filtro, string $nivel)
    {
        $db = Database::connection();
        $data = [];

        $sql = "WITH FILTRO AS (
                            SELECT UPA.COD_RACK 
                         FROM UBIC_PALLET_ASIG UPA, UBIC_PALLET UP
                    where 
                    UP.COD_RACK=UPA.COD_RACK 
                    AND UP.ORIGEN_RACK=UPA.ORIGEN_RACK
                    AND UP.FLAG_MULTI<>1
                    and up.estado=1
                    ),
                    FILTRO2 AS (
                        SELECT UPA.COD_RACK 
                        FROM UBIC_PALLET UPA
                        WHERE UPA.FLAG_MOV = 1
                        and upa.estado=1
                    )
                    SELECT 
                        UP.COD_RACK AS COD_RACK,
                        (SELECT COUNT(*)
                        FROM FILTRO2 DEP
                        WHERE TRIM(DEP.COD_RACK) LIKE TRIM(UP.COD_RACK) || '%') AS TOTAL_UBIC,
                        (SELECT COUNT(*)
                        FROM FILTRO2 DEP
                        WHERE DEP.COD_RACK NOT IN (SELECT T.COD_RACK FROM FILTRO T) 
                        AND TRIM(DEP.COD_RACK) LIKE TRIM(UP.COD_RACK) || '%') AS DISP_UBIC
                    FROM UBIC_PALLET UP
                    WHERE UP.NIV_RACK = :nivel
                    and up.estado=1
                     AND  REGEXP_LIKE(UP.COD_RACK, '^(' || REPLACE(:filtro, ',', '|') || ')')
                     order by UP.COD_RACK asc ";

        $stmt = Database::prepare($db, $sql);
        Database::bind($stmt, ':filtro', $filtro);
        Database::bind($stmt, ':nivel', $nivel);

        Database::execute($stmt);

        while ($row = oci_fetch_assoc($stmt)) {
            $datos = array_map('trim', $row);
            $porentaje = intval($datos['DISP_UBIC']) * 100 / intval($datos['TOTAL_UBIC']);
            $datos['PORCENTAJE'] = round($porentaje, 2);
            $data[] = $datos;
        }

        if (empty($data)) {
            throw new ApiException('ubicacion_not_found', 'Ubicación no encontrada', 404);
        }


        oci_free_statement($stmt);

        return $data;
    }

    public function saveUbicacion(string $codigo, string $pallet, string $valeI, string $valeS, string $usuario, string $almacenDest)
    {
        $db = Database::connection();
        $multi = 0;
        $respuesta = false;

        $sql = "begin USP_INSERTA_PALLET_ASIGNA(:rack,:almd,:pallet,:valeIng,:valeSal,:multi,:usuario); end;";

        $stmt = Database::prepare($db, $sql);

        Database::bind($stmt, ':rack', $codigo);
        Database::bind($stmt, ':almd', $almacenDest);
        Database::bind($stmt, ':pallet', $pallet);
        Database::bind($stmt, ':valeIng', $valeI);
        Database::bind($stmt, ':valeSal', $valeS);
        Database::bind($stmt, ':multi', $multi);
        Database::bind($stmt, ':usuario', $usuario);



        if (!$stmt) {
            throw new ApiException('failed_save_ubi', oci_error($db)['message'], 400);
        }


        $executeResult = Database::execute($stmt);


        if ($executeResult == false) {
            Database::close();

            throw new ApiException('failed_save_ubi', oci_error($stmt)['message'], 400);
        } else {
            Database::commit($db);
            $respuesta = true;
        }

        oci_free_statement($stmt);


        return $respuesta;
    }
}
