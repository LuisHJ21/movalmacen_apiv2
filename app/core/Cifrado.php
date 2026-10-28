<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ApiException.php';

/**
 * Cifrado del sistema (claves de usuario y QR de cajas): cada carácter va como un número de 3 dígitos
 * desplazado según la clave de SISTEMA_KEY. Debe ser la inversa exacta del cifrado del sistema web:
 * no "corregir" su lógica (incluida la vuelta del índice de la clave) o dejarían de coincidir.
 */
class Cifrado
{
    /** Clave vigente del sistema (SISTEMA_KEY.ACTUAL_KEY) */
    public static function clave(): string
    {
        $conn = Database::connection();
        $stmt = Database::prepare($conn, "select ACTUAL_KEY from SISTEMA_KEY t");
        Database::execute($stmt);
        $res = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$res) {
            throw new ApiException('key_not_found', 'Key no encontrada', 401);
        }

        return $res['ACTUAL_KEY'];
    }

    /** Un valor cifrado son solo dígitos, en grupos de 3 */
    public static function pareceCifrado(string $valor): bool
    {
        return $valor !== '' && ctype_digit($valor) && strlen($valor) % 3 === 0;
    }

    public static function desencriptar($key, $pass): string
    {
        $is_encriptado = $pass;   // colocar argumento como valor encriptado

        $li_olen = mb_strlen($is_encriptado);    // longitud de valor encriptado
        $is_original = '';                // borrar valor original
        $li_kind = 0;                        // inicializar indicador del key
        for ($li_orig = 0; $li_orig < $li_olen; $li_orig = $li_orig + 3) {
            $li_ktemp = ord(substr($key, $li_kind, 1));
            $li_work = intval(substr($is_encriptado, $li_orig, 3));
            $li_work = $li_work - $li_ktemp;

            while ($li_work < 0) {        // dar la vuelta a los valores superiores a 255
                $li_work = $li_work + 255;
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
