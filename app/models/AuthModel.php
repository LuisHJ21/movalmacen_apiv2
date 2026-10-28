<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';
require_once __DIR__ . '/../core/Cifrado.php';

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

        $key = Cifrado::clave();

        $claveDecrypted = Cifrado::desencriptar($key, $row['CLAVE']);



        if ($clave <> $claveDecrypted) {
            throw new ApiException('pass_incorrect', 'La contraseña es incorrecta', 401);
        }

        $data = $row;



        $data = array_map('trim', $data);


        // Elimina la clave  antes de retornar
        unset($data['CLAVE']);


        return ['user' => $data];
    }




    /** Se mantiene por compatibilidad: la lógica está en Cifrado (compartida con la lectura de QR de cajas) */
    public function desencriptar($key, $pass): string
    {
        return Cifrado::desencriptar($key, $pass);
    }
}
