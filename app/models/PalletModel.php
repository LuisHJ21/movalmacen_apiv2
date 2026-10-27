<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class PalletModel
{
    public function VerifyStatePallet(string $pallet): array
    {
        $conn = Database::connection();

        $sql = "SELECT t.num_pallet,t.num_bulto,t.peso,t.flag_estado as estado,t2.cod_rack as ubicacion,t2.cod_almacen as almacen
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

        $data = array_map('trim', $row);


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



    public function DataPalletforVale(string $numPallet, string $tipoMov, string $codart)
    {
        $data = [];
        $dataOt = [];
        $dataItems = [];

        $dataPallet = $this->VerifyStatePallet($numPallet);


        $conn =  Database::connection();

        $consulta = "begin :cursor := USF_INFO_VALES_OT_OV(:numPallet,:tMov,:art); end;";

        $stmt = Database::prepare($conn, $consulta);
        $cursor = oci_new_cursor($conn);
        $iTmov = substr($tipoMov, 0, 1);

        Database::bind($stmt, ':cursor', $cursor, -1, OCI_B_CURSOR);
        Database::bind($stmt, ':numPallet', $numPallet);
        Database::bind($stmt, ':tMov', $iTmov);
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

                $adicional
                    = (round($dividendo * $factor) % round($divisor * $factor)) / $factor;

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

    public function PalletInfo(string $pallet)
    {
        $conn = Database::connection();

        $sql = "SELECT 
            NUM_PALLET,
            NUM_BULTO,
            PESO,
            COD_USUARIO,
            TO_CHAR(FECH_REGISTRO,'DD/MM/YYYY') AS FECH_REGISTRO,
            FLAG_ESTADO,
            OBSERVACION
        FROM PALLET
        WHERE NUM_PALLET = :pallet";

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':pallet', $pallet);

        Database::execute($stmt);

        $respuesta = oci_fetch_assoc($stmt);

        if (!$respuesta) {
            throw new ApiException('pallet_not_found', 'El pallet no existe', 404);
        }

        $items = [];

        $sql2 = "SELECT
                    pd.ITEM,
                    pd.NUM_BULTOS,
                    pd.PESO,
                    TO_CHAR(pd.FECH_PRODU,'DD/MM/YYYY') AS FECH_PRODU,
                    pd.COD_TEMPLA as LOTE,
                    pd.COD_TRAZA,
                    TO_CHAR(pd.FECH_REGIST,'DD/MM/YYYY') AS FECH_REGIST,
                    pd.COD_USUARIO,
                    pd.COD_ARTICULO,
                    a.DESC_ART AS DESCRIPCION,
                    ppv.COD_PARTE AS COD_PARTE,
                    pp.DESCRIPCION AS DESC_PARTE,
                    am.NRO_VALE AS NRO_VALE,
                    vm.TIPO_MOV AS TIPO_MOV,
                    t.desc_tipo_mov,
                    vm.tipo_refer AS TIPO_REFER,
                    vm.nro_refer AS REFER,
                    pd.PESO_ORIGINAL,
                    pp.especie
           
                FROM PALLET_DET pd, ARTICULO_MOV am, VALE_MOV vm, ARTICULO a,PARTE_PRODUC_PLAN_VALE ppv,PARTE_PRODUCCION pp,
                articulo_mov_tipo t
                where 
        
                pd.nro_mov_am=am.nro_mov (+)
                and pd.cod_origen_am=am.cod_origen (+)
                and pd.cod_articulo=a.cod_art(+)
                and am.nro_mov=ppv.nro_mov_am(+)
                and am.cod_origen=ppv.cod_origen_am(+)
                and am.nro_vale=vm.nro_vale (+)
                and ppv.cod_parte=pp.cod_parte_producc(+)
                and vm.tipo_mov=t.tipo_mov
                and pd.NUM_PALLET = :pallet
                ORDER BY pd.ITEM";

        $stmt2 = Database::prepare($conn, $sql2);

        Database::bind($stmt2, ':pallet', $pallet);

        Database::execute($stmt2);

        while ($row = oci_fetch_assoc($stmt2)) {
            $items[] = $row;
        }

        $respuesta['DETALLES'] = $items;

        return $respuesta;
    }

    public function DataPalletForTransfer(array $datos)
    {
        $dataItems = [];

        $conn = Database::connection();

        $sql = " SELECT * from USF_INFO_OTR_TRANS(:numPallet,:almacen,:tipo) ";

        $numPallet = $datos['pallet'];

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':numPallet', $datos['pallet']);
        Database::bind($stmt, ':almacen', $datos['almacen']);
        Database::bind($stmt, ':tipo', $datos['tipo']);

        if (!$stmt) {

            throw new ApiException('info_otr_failed', oci_error($conn)['message'], 500);
        }

        $r = Database::execute($stmt);

        if (!$r) {
            throw new ApiException('info_otr_failed', oci_error($stmt)['message'], 500);
        }



        while ($row =  oci_fetch_assoc($stmt)) {

            $datos = array_map('trim', $row);


            $factor = 10000;
            $dividendo = floatval($datos['PESO']);
            $divisor = $datos['PESO_UND'] !== "" ? floatval($datos['PESO_UND']) : 1;

            $adicional =
                (round($dividendo * $factor) % round($divisor * $factor)) / $factor;

            $bultos = $datos['NUM_BULTOS'] === "" ? intdiv(floatval($datos['PESO']), $divisor) : $datos['NUM_BULTOS'];



            $dataItems[] = [
                "visible" => true,
                "codArt" => $datos['COD_ART'],
                "item" => $datos['ITEM'],
                "bultos" => $bultos,
                "peso" => $datos['PESO'],
                "fecha" => $datos['FECH_PRODU'],
                "lote" => $datos['COD_TEMPLA'],
                "traza" => $datos['COD_TRAZA'],
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

        $dataPallet = $this->VerifyStatePallet($numPallet);


        $respuesta = [
            'dataItems' => $dataItems,
            'dataArticulo' => $dataArticulo ?? [],
            'dataPallet' => $dataPallet

        ];

        oci_free_statement($stmt);


        return $respuesta;
    }


    public function UpdateVinculoPallet(
        string $conn,
        string $pallet,
        string $item,
        string $nromov,
        string $orgmov,
        string $tipoMov,
        string $codParte
    ): bool {



        $esSalida = $tipoMov === 'S';

        $sql = $esSalida
            ? "BEGIN USP_VINCULAR_SALIDA_PALLETDET(:pallet, :mov, :org, :item, :codparte); END;"
            : "BEGIN USP_VINCULAR_VALE_PALLETDET(:pallet, :mov, :org, :item); END;";


        $stmt = Database::prepare($conn, $sql);

        if (!$stmt) {
            throw new ApiException(
                'vinculo_fail',
                oci_error($conn)['message'] ?? 'Error al preparar la consulta.',
                500
            );
        }




        Database::bind($stmt, ':pallet', $pallet);
        Database::bind($stmt, ':mov', $nromov);
        Database::bind($stmt, ':org', $orgmov);
        Database::bind($stmt, ':item', $item);


        if ($esSalida) {
            Database::bind($stmt, ':codparte', $codParte);
        }


        if (!Database::execute($stmt)) {
            throw new ApiException(
                'vinculo_fail',
                oci_error($stmt)['message'] ?? 'Error al vincular pallet.',
                500
            );
        }

        oci_free_statement($stmt);
        return true;
    }
}
