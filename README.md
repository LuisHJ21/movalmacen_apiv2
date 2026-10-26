# Autenticación JWT

Configura en el entorno del proceso PHP (IIS/FastCGI):

- `JWT_SECRET`: secreto aleatorio de al menos 32 bytes, sin valor predeterminado.
- `JWT_TTL`: vigencia en segundos; predeterminado `3600`.
- `JWT_ISSUER`: emisor y audiencia; predeterminado `movalmacen-api2`.

Genera un secreto con `php -r "echo bin2hex(random_bytes(32));"` y guárdalo en la configuración del servidor. No lo publiques ni lo agregues al repositorio. Reinicia el proceso PHP después de cambiar su entorno. No se cargan archivos `.env` automáticamente.

`POST /movalmacen/api2/login` recibe JSON:

```json
{"usuario":"miusuario","clave":"micontraseña"}
```

Conserva `success` y `data` en la respuesta y añade `access_token`, `token_type` (`Bearer`) y `expires_in`. El token contiene la identidad del usuario, sin contraseña.

Envía `Authorization: Bearer <access_token>` para consultar `GET /movalmacen/api2/me` y las demás rutas. Todas las rutas registradas están protegidas salvo `POST /login`. Los tokens ausentes, inválidos o vencidos reciben HTTP 401. La configuración JWT inválida devuelve HTTP 500.

Usa HTTPS. Al vencer el token, inicia sesión nuevamente. No hay refresh tokens ni revocación individual; los cambios de usuario o perfil se reflejan al emitir un nuevo token. Cambiar el secreto invalida todos los tokens anteriores.

Verificación local sin Oracle: `php tests/jwt.php`.
