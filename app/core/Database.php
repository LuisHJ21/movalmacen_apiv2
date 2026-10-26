<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/ApiException.php';


class Database
{
    private static $connection = null;

    public static function connection()
    {
        if (self::$connection === null) {
            if (!function_exists('oci_pconnect')) {
                throw new RuntimeException('Extensión Oracle no disponible');
            }
            $settings = DB_DATA[BD_USE];
            self::$connection = @oci_pconnect(
                DB_DATA[BD_USE][0],
                DB_DATA[BD_USE][1],
                DB_DATA[BD_USE][2],
                'UTF8',
            );
            if (self::$connection === false) {
                self::$connection = null;
                throw new RuntimeException(self::error());
            }
        }
        return self::$connection;
    }

    public static function prepare($connection, string $sql)
    {
        $statement = @oci_parse($connection, $sql);
        if ($statement === false) {
            throw new RuntimeException(self::error($connection));
        }
        return $statement;
    }

    public static function bind($statement, string $name, &$value, int $length = -1, int $type = SQLT_CHR): void
    {
        if (!@oci_bind_by_name($statement, $name, $value, $length, $type)) {
            throw new RuntimeException(self::error($statement));
        }
    }

    public static function execute($statement, int $mode = OCI_NO_AUTO_COMMIT): bool
    {
        if (!@oci_execute($statement, $mode)) {
            $error = oci_error($statement);

            // Errores de negocio de los procedimientos/triggers (RAISE_APPLICATION_ERROR: ORA-20000 a ORA-20999)
            if (($error['code'] ?? 0) >= 20000 && $error['code'] <= 20999) {
                $msg = trim(preg_replace('/^ORA-\d+:\s*/', '', strtok($error['message'], "\n")));
                throw new ApiException('business_rule', $msg, 409);
            }

            throw new RuntimeException(self::error($statement));
        }
        return true;
    }

    public static function commit($connection): void
    {
        if (!@oci_commit($connection)) {
            throw new RuntimeException(self::error($connection));
        }
    }

    public static function error($resource = null): string
    {
        $error = $resource === null ? oci_error() : oci_error($resource);
        error_log('Oracle: ' . ($error['message'] ?? 'Error sin detalle'));
        return 'Error de base de datos';
    }

    public static function close(): void
    {
        if (self::$connection !== null) {
            @oci_rollback(self::$connection);
            @oci_close(self::$connection);
            self::$connection = null;
        }
    }
}
