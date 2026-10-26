<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';

class AuthModel
{
    public function obtenerUsuarioActivo(string $usuario): array
    {
        $nick = trim(strtolower($usuario));
        $conn = Database::connection();
        $sql = "SELECT u.COD_USR,u.NOMBRE,u.PERFIL from usuario u where trim(u.cod_usr)= :usuario and u.flag_estado='1'";

        $stmt = Database::prepare($conn, $sql);
        Database::bind($stmt, ':usuario', $nick);
        Database::execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            throw new ApiException('user_inactive', 'El usuario ya no está activo', 401);
        }

        return array_map('trim', $row);
    }

    public function verificarCredenciales(string $usuario, string $clave): array
    {

        $nick = trim(strtolower($usuario));
        $conn = Database::connection();
        $sql = "SELECT u.COD_USR,u.NOMBRE,u.PERFIL,u.CLAVE from usuario u   where trim(u.cod_usr)= :usuario and u.flag_estado='1'";

        $stmt = Database::prepare($conn, $sql);
        Database::bind($stmt, ':usuario', $nick);
        Database::execute($stmt);


        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);


        if (!$row) {
            throw new ApiException('user_not_found', 'El usuario no existe', 404);
        }

        $sql2 = "select ACTUAL_KEY from SISTEMA_KEY t";
        Database::prepare($conn, $sql2);


        $stmt2 = oci_parse($conn, $sql2);

        Database::execute($stmt2);

        $res = oci_fetch_assoc($stmt2);
        oci_free_statement($stmt2);

        if (!$res) {
            throw new ApiException('key_not_found', 'Key no encontrada', 401);
        }

        $key = $res['ACTUAL_KEY'];


        $claveDecrypted = $this->desencriptar($key, $row['CLAVE']);



        if ($clave <> $claveDecrypted) {
            throw new ApiException('pass_incorrect', 'La contraseña es incorrecta', 401);
        }

        $data = $row;



        $data = array_map('trim', $data);


        // Elimina la clave  antes de retornar
        unset($data['CLAVE']);


        return ['user' => $data];
    }




    public function desencriptar($key, $pass): string
    {
        $is_encriptado = $pass;   // colocar argumento como valor encriptado

        $li_olen = mb_strlen($is_encriptado);    // longitud de valor encriptado
        $li_klen = mb_strlen($key);        // logitud del key
        $is_original = '';                // borrar valor original
        $li_kind = 0;                        // inicializar indicador del key
        for ($li_orig = 0; $li_orig < $li_olen; $li_orig = $li_orig + 3) {
            $li_ktemp = ord(substr($key, $li_kind, 1));
            $li_work = intval(substr($is_encriptado, $li_orig, 3));
            $li_work = $li_work - $li_ktemp;

            while ($li_work < 0) {        // dar la vuelta a los valores superiores a 255
                if ($li_work < 0) {
                    $li_work = $li_work + 255;
                }
            }

            $ls_temp = chr($li_work);    // covertir codigo ASCII a caracter
            $is_original = $is_original . $ls_temp;    // sumar al resultado final
            $li_kind = $li_kind + 1;    // correr al siguiente char del key
            if ($li_kind > mb_strlen($key)) {
                $li_kind = 1;
            }    // dar la vuelta al char del key
        }


        return $is_original;
    }
}
