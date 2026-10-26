<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ApiException.php';


class  ErrorModel
{

    public function insertError(array $data)
    {
        $conn = Database::connection();
        $respuesta = false;
        $codigo = date("YmdHis");
        $hora = HORA . ' | ' . HOY;
        $ip = $this->getIP();

        $mensaje = "+------------- TICKET N°: $codigo -------------+ \n";
        $mensaje .= "DIA|HORA: " . $hora . "\n";
        $mensaje .= "TIPO: APP\n";
        $mensaje .= "TERMINAL: " . $data['device'] . "\n";
        $mensaje .= "IP: " . $ip . "\n";
        $mensaje .= "USUARIO: " . $data['usuario'] . "\n";
        $mensaje .= "APLICACION: " . APP_NAME . "\n";
        $mensaje .= "OPERACION: " . $data['accion'] . "\n";
        $mensaje .= "MENSAJE: \n" . $data['error'] . "";

        $sql = "begin REG_ERROR_APP(:as_codigo,:as_usuario,:as_terminal,:as_objeto,:as_control,:as_mensaje); end;";

        $stmt = Database::prepare($conn, $sql);

        Database::bind($stmt, ':as_codigo', $codigo);
        Database::bind($stmt, ':as_usuario', $data['usuario']);
        Database::bind($stmt, ':as_terminal', $data['device']);
        Database::bind($stmt, ':as_objeto', $data['app']);
        Database::bind($stmt, ':as_control', $data['accion']);
        Database::bind($stmt, ':as_mensaje', $mensaje);
        // oci_bind_by_name($stmt,':as_archivo',$archivo,-1,SQLT_BLOB);


        $rs = Database::execute($stmt);


        if ($rs == false) {
            Database::close();
        } else {
            Database::commit($conn);
        }

        oci_free_statement($stmt);

        return $respuesta;
    }

    public function getIP()
    {
        // Revisa las cabeceras que podrían contener la IP real del cliente.
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Si está detrás de un proxy, la IP real puede estar en la cabecera 'X-Forwarded-For'
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]); // Devuelve la primera IP, que es la original
        }
        return $_SERVER['REMOTE_ADDR']; // Si no está detrás de un proxy, usa REMOTE_ADDR
    }
}
