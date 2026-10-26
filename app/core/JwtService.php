<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtConfigurationException extends RuntimeException
{
}

class JwtService
{
    private string $secret;
    private string $issuer;
    private int $ttl;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: '';
        $this->issuer = getenv('JWT_ISSUER') ?: 'movalmacen-api';
        $ttl = getenv('JWT_TTL');
        $this->ttl = $ttl === false ? 3600 : (int) $ttl;
        if (strlen($this->secret) < 32 || $this->ttl < 1) {
            throw new JwtConfigurationException('Configuración JWT inválida');
        }
    }

    public function issue(array $user): array
    {
        $now = time();
        $identity = array_intersect_key($user, array_flip(['COD_USR', 'NOMBRE', 'PERFIL']));
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->issuer,
            'sub' => (string) $identity['COD_USR'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
            'user' => $identity,
        ];
        return [
            'access_token' => JWT::encode($payload, $this->secret, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $this->ttl,
        ];
    }

    public function verify(string $token): array
    {
        $claims = JWT::decode($token, new Key($this->secret, 'HS256'));
        if (($claims->iss ?? null) !== $this->issuer
            || ($claims->aud ?? null) !== $this->issuer
            || !isset($claims->sub, $claims->iat, $claims->nbf, $claims->exp, $claims->user)
            || !is_string($claims->sub) || $claims->sub === ''
            || !is_int($claims->exp) || !is_int($claims->iat) || !is_int($claims->nbf)
            || !is_object($claims->user)
            || ($claims->user->COD_USR ?? null) !== $claims->sub) {
            throw new UnexpectedValueException('Claims JWT inválidos');
        }
        return (array) $claims->user;
    }
}
