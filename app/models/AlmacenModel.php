<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class AlmacenModel
{
    public function getAlmacenes()
    {
        $db = Database::connection();
        $data = [];

        $sql = " WITH p as (
                    select distinct t.almacen,t.desc_almacen,sum(aa.sldo_total) saldo from almacen t,articulo_almacen aa where t.almacen=aa.almacen(+)
                    and t.flag_tipo_almacen='T' and t.flag_estado='1'  group by t.almacen,t.desc_almacen order by t.desc_almacen)
                    ,
                    f as (

                    SELECT AU.COD_ALMACEN ,A.DESC_ALMACEN,( LISTAGG(TRIM(au.cod_ubicacion), ',') WITHIN GROUP (ORDER BY AU.COD_ALMACEN ,A.DESC_ALMACEN) ) as COD_UBICACION FROM ALMACEN_UBIC AU, ALMACEN A
                                WHERE AU.COD_ALMACEN=A.ALMACEn
                                GROUP BY   AU.COD_ALMACEN ,A.DESC_ALMACEN
                                )

                    select p.almacen,p.desc_almacen,f.cod_ubicacion, p.saldo from p,f
                    where p.almacen=f.cod_almacen (+) 
                    ";

        $stmt = Database::prepare($db, $sql);

        Database::execute($stmt);


        while ($row = oci_fetch_assoc($stmt)) {


            $data[] = array_map('trim', $row);
        }

        oci_free_statement($stmt);

        if (empty($data)) {
            throw new ApiException('almacenes_not_found', 'Almacenes no encontrados', 404);
        }

        return $data;
    }


    public function GetTipoMov(string $tipoMov)
    {

        $conn = Database::connection();

        $sql = "SELECT distinct a.tipo_mov as cod_mov,substr(a.tipo_mov,1,1) as tipo_mov,  a.desc_tipo_mov as nom_mov,a.Flag_Clase_Mov as clase_mov,a.flag_contabiliza as f_contabiliza,a.flag_solicita_ref as f_ref
            FROM  articulo_mov_tipo a,
                almacen_tipo_mov  b
            WHERE a.tipo_mov    = b.tipo_mov
                and a.flag_estado   = '1'
            and a.tipo_mov    like :tipomov ";

        $stmt = Database::prepare($conn, $sql);

        $data = [];

        $tipoMovBind = $tipoMov . '%';

        Database::bind($stmt, ':tipomov', $tipoMovBind);
        Database::execute($stmt);



        while ($row = oci_fetch_assoc($stmt)) {
            $row['TIPO_MOV'] = $row['TIPO_MOV'] === "I" ? "ING" : ($row['TIPO_MOV'] === "S" ? "SAL" : "UNK");
            $data[] =  array_map('trim', $row);
        }

        if (empty($data)) {
            throw new ApiException('movement_type_not_found', 'Tipo de movimiento no encontrado', 404);
        }

        oci_free_statement($stmt);

        return $data;
    }

    public function getAlmUbicacion(string $almubi, string $condicion)
    {
        $db = Database::connection();
        $data = [];

        $sqlADD = $condicion == '1' ? "P.COD_UBICACION " : "P.COD_ALMACEN ";

        $sql = " WITH P as (SELECT AU.COD_ALMACEN  ,A.DESC_ALMACEN,( LISTAGG(TRIM(au.cod_ubicacion), ',') WITHIN GROUP (ORDER BY AU.COD_ALMACEN ,A.DESC_ALMACEN) ) as COD_UBICACION FROM ALMACEN_UBIC AU, ALMACEN A
            WHERE AU.COD_ALMACEN=A.ALMACEN 
            GROUP BY   AU.COD_ALMACEN ,A.DESC_ALMACEN) 
            SELECT * from P where $sqlADD like :valor";

        $stmt = Database::prepare($db, $sql);
        $valorBind = "%$almubi%";
        Database::bind($stmt, ':valor', $valorBind);
        Database::execute($stmt);



        while ($row = oci_fetch_assoc($stmt)) {
            $data[] =  array_map('trim', $row);
        }

        if (empty($data)) {
            throw new ApiException('almacen_ubicacion_not_found', 'Almacen o Ubicación no encontrado', 404);
        }

        oci_free_statement($stmt);

        return $data;
    }

    public function getMatriz(string $codArt, string $tipoMov)
    {
        $db = Database::connection();
        $data = [];
        $sql = "SELECT distinct tmms.GRP_CNTBL,
                    tmms.COD_SUB_CAT,
                    tmms.MATRIZ,
                    mcf.DESCRIPCION
            FROM TIPO_MOV_MATRIZ_SUBCAT tmms,
                    MATRIZ_CNTBL_FINAN  mcf,
                    ARTICULO_SUB_CATEG asub,
                    ARTICULO a,
                    grupo_contable gc
            WHERE tmms.MATRIZ = mcf.MATRIZ
            and   asub.cod_sub_cat=a.sub_cat_art
            and   tmms.COD_SUB_CAT=asub.cod_sub_cat
            and tmms.grp_cntbl=gc.grp_cntbl
            and  trim(tmms.TIPO_MOV) 	= :tipoMov
            and trim(a.cod_art) = :codArt
            ORDER BY tmms.GRP_CNTBL ASC,
                    tmms.COD_SUB_CAT ASC";

        $stmt = Database::prepare($db, $sql);
        Database::bind($stmt, ':tipoMov', $tipoMov);
        Database::bind($stmt, ':codArt', $codArt);

        Database::execute($stmt);



        while ($row = oci_fetch_assoc($stmt)) {

            $data[] = array_map('trim', $row);
        }

        if (empty($data)) {
            throw new ApiException('matriz_not_found', 'Matriz no encontrada', 404);
        }

        oci_free_statement($stmt);

        return $data;
    }
}
