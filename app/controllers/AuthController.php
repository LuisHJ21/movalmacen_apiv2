<?php

require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../core/JwtService.php';
require_once __DIR__ . '/../core/ApiException.php';

class AuthController
{
    public function login(Request $request)
    {

        $data = $request->getBody();

        if (
            !is_array($data) || !isset($data['usuario'], $data['clave'])
            || !is_string($data['usuario']) || !is_string($data['clave'])
            || trim($data['usuario']) === '' || $data['clave'] === ''
        ) {
            throw new ApiException('credentials_required', 'Usuario y Contraseña son requeridos', 400);
        }

        try {
            $jwt = new JwtService();
        } catch (JwtConfigurationException $e) {
            throw new ApiException('auth_not_configured', 'Autenticación no configurada', 500, $e);
        }

        $auth = new AuthModel();
        $result  = $auth->verificarCredenciales($data['usuario'], $data['clave']);

        header('Cache-Control: no-store');
        return array_merge(['success' => true, 'data' => $result['user']], $jwt->issue($result['user']));
    }


    public function me(Request $request)
    {
        header('Cache-Control: no-store');
        return ['success' => true, 'data' => $request->getUser()];
    }
}
