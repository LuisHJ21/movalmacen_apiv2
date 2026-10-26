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
    /** Duración máxima de la sesión desde el login, aunque se renueve el token */
    private int $maxSession;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: '';
        $this->issuer = getenv('JWT_ISSUER') ?: 'movalmacen-api';
        $ttl = getenv('JWT_TTL');
        $this->ttl = $ttl === false ? 3600 : (int) $ttl;
        $maxSession = getenv('JWT_MAX_SESSION');
        $this->maxSession = $maxSession === false ? 43200 : (int) $maxSession;
        if (strlen($this->secret) < 32 || $this->ttl < 1 || $this->maxSession < $this->ttl) {
            throw new JwtConfigurationException('Configuración JWT inválida');
        }
    }

    /**
     * Emite un token. $authTime es la hora del login original: al renovar se conserva,
     * y el vencimiento nunca supera auth_time + JWT_MAX_SESSION.
     */
    public function issue(array $user, ?int $authTime = null): array
    {
        $now = time();
        $authTime = $authTime ?? $now;
        $exp = min($now + $this->ttl, $authTime + $this->maxSession);
        $identity = array_intersect_key($user, array_flip(['COD_USR', 'NOMBRE', 'PERFIL']));
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->issuer,
            'sub' => (string) $identity['COD_USR'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'auth_time' => $authTime,
            'user' => $identity,
        ];
        return [
            'access_token' => JWT::encode($payload, $this->secret, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $exp - $now,
        ];
    }

    public function verify(string $token): array
    {
        return (array) $this->verifyClaims($token)->user;
    }

    /** Valida el token y devuelve todos sus claims (lo usa /refresh para conservar auth_time) */
    public function verifyClaims(string $token): object
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
        return $claims;
    }

    /** Hora del login original; los tokens emitidos antes de existir auth_time usan iat */
    public function authTime(object $claims): int
    {
        return is_int($claims->auth_time ?? null) ? $claims->auth_time : $claims->iat;
    }
}
