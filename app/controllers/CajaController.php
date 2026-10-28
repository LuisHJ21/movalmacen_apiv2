<?php

require_once __DIR__ . '/../core/Cifrado.php';
require_once __DIR__ . '/../core/ApiException.php';

class CajaController
{
    /** Campos del QR de la caja, en orden: codigo_art|ot|fecha_prod|trazabilidad|lote */
    private const CAMPOS_QR = ['codArt', 'ot', 'fecha', 'traza', 'lote'];

    /**
     * POST /caja/leer { qr }: lee el QR de la etiqueta de una caja.
     * El QR puede venir cifrado (solo dígitos): se desencripta antes de separar por "|".
     */
    public function leer(Request $request)
    {
        $data = $request->getBody();

        if (!is_array($data) || !isset($data['qr']) || !is_string($data['qr']) || trim($data['qr']) === '') {
            throw new ApiException('qr_required', 'El código QR es requerido', 400);
        }

        $qr = trim($data['qr']);
        $cifrado = Cifrado::pareceCifrado($qr);
        $texto = $cifrado ? Cifrado::desencriptar(Cifrado::clave(), $qr) : $qr;

        $partes = array_map('trim', explode('|', $texto));
        if (count($partes) !== count(self::CAMPOS_QR)) {
            throw new ApiException('qr_invalido', 'El código QR no corresponde a una etiqueta de caja', 400);
        }

        header('Cache-Control: no-store');
        return [
            'success' => true,
            'data' => array_combine(self::CAMPOS_QR, $partes) + ['cifrado' => $cifrado],
        ];
    }
}
