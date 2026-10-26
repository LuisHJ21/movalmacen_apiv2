<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class PalletModel
{
    public function VerifyStatePallet(string $pallet): array
    {
        $conn = Database::connection();

        $sql = "SELECT t.num_pallet,t.num_bulto,t.peso,t.flag_estado as estado,t2.cod_rack as ubicaccion,t2.cod_almacen as almacen
        from pallet t,ubic_pallet_asig t2 where t.num_pallet=t2.num_pallet(+) and t.num_pallet= :pallet";

        $stmt = Database::prepare($conn, $sql);
        Database::bind($stmt, ':pallet', $pallet);
        Database::execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $data = [];


        if (!$row) {
            throw new ApiException('pallet_not_found', 'El pallet no existe', 404);
        }

        // if ($row['ESTADO'] == '0') {
        //     return ['error' => 'pallet_inactive'];
        // }
        $row['PESO'] = floatval($row['PESO']) ?? 0.0;
        $row['NUM_BULTO'] = floatval($row['NUM_BULTO']) ?? 0.0;

        $data = $row;


        $sql2 = "select distinct t.cod_articulo from pallet_det t where t.num_pallet=:pallet";
        $stmt2 = Database::prepare($conn, $sql2);
        Database::bind($stmt2, ':pallet', $pallet);
        Database::execute($stmt2);
        $articulos = [];
        while ($row2 = oci_fetch_assoc($stmt2)) {
            $articulos[] = $row2['COD_ARTICULO'];
        }
        oci_free_statement($stmt2);

        $data['ARTICULOS'] = $articulos;

        return $data;
    }

    public function DataPalletforVale($pallet, $tipoMov): array
    {
        $conn = Database::connection();

        $sql = "SELECT t.num_pallet,t.num_bulto,t.peso,t.flag_estado as estado,t2.cod_rack as ubicaccion,t2.cod_almacen as almacen
        from pallet t,ubic_pallet_asig t2 where t.num_pallet=t2.num_pallet(+) and t.num_pallet= :pallet";

        $stmt = Database::prepare($conn, $sql);
        Database::bind($stmt, ':pallet', $pallet);
        Database::execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            throw new ApiException('pallet_not_found', 'El pallet no existe', 404);
        }

        return $row;
    }

    public function getDataPallet(string $numPallet, string $tipoMov, string $codart)
    {
        $data = [];
        $dataOt = [];
        $dataItems = [];

        $dataPallet = $this->VerifyStatePallet($numPallet);


        $conn =  Database::connection();

        $consulta = "begin :cursor := USF_INFO_VALES_OT_OV(:numPallet,:tMov,:art); end;";

        $stmt = Database::prepare($conn, $consulta);
        $cursor = oci_new_cursor($conn);

        Database::bind($stmt, ':cursor', $cursor, -1, OCI_B_CURSOR);
        Database::bind($stmt, ':numPallet', $numPallet);
        Database::bind($stmt, ':tMov', $tipoMov);
        Database::bind($stmt, ':art', $codart);

        $r = Database::execute($stmt);
        Database::execute($cursor);

        if ($r != false) {
            $codigoArti = "";
            while ($row = oci_fetch_array($cursor, OCI_ASSOC + OCI_RETURN_NULLS)) {

                $datos = array_map('trim', $row);

                $factor = 10000;
                $dividendo = floatval($datos['PESO']);
                $divisor = $datos['PESO_UND'] !== "" ? floatval($datos['PESO_UND']) : 1;

                $adicional =
                    (round($dividendo * $factor) % round($divisor * $factor)) / $factor;

                $bultos = $datos['NUM_BULTOS'] === "" ? intdiv(floatval($datos['PESO']), $divisor) : $datos['NUM_BULTOS'];


                $dataOt = [
                    "tipo" => $datos['TIPO_DOC'] == "0" ? "" : $datos['TIPO_DOC'],
                    "nro" => $datos['NRO_ORDEN'] == "0" ? "" : $datos['NRO_ORDEN'],
                    "desc" => $datos['DESCRIPCION'] == "0" ? "" : $datos['DESCRIPCION'],
                    "nroMov" => $datos['NRO_MOV'] == "0" ? "" : $datos['NRO_MOV'],
                    "almacen" => $datos['ALMACEN'] == "0" ? "" : $datos['ALMACEN'],
                ];

                $dataItems[] = [
                    "visible" => $tipoMov === 'I' ? true : false,
                    "codArt" => $datos['COD_ART'],
                    "item" => $datos['ITEM'],
                    "bultos" =>  $bultos,
                    "peso" => $datos['PESO'],
                    "fecha" => $datos['FECH_PRODU'],
                    "lote" => $datos['COD_TEMPLA'],
                    "traza" => $datos['COD_TRAZA'],
                    "referencia" => $dataOt,
                    "adicional" => round($adicional, 4),
                    "numPallet" => $datos['NUM_PALLET'],

                    "descArt" => $datos['DESC_ART'],
                    "und" => $datos['UND'],
                    "pesoUnd" => $datos['PESO_UND'],


                ];

                $dataArticulo = [
                    "codigo" => $datos['COD_ART'],
                    "desc" => $datos['DESC_ART'],
                    "und" => $datos['UND'],
                    "pesoUnd" => $datos['PESO_UND'],
                ];
            }



            $data = [
                'dataItems' => $dataItems,
                'dataArticulo' => $dataArticulo ?? [],
                'dataPallet' => $dataPallet,

            ];
        }

        oci_free_statement($stmt);
        oci_free_cursor($cursor);
        return $data;
    }
}
