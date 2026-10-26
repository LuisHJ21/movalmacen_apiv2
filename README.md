# Autenticación JWT

Configura en el entorno del proceso PHP (IIS/FastCGI):

- `JWT_SECRET`: secreto aleatorio de al menos 32 bytes, sin valor predeterminado.
- `JWT_TTL`: vigencia en segundos; predeterminado `3600`.
- `JWT_MAX_SESSION`: duración máxima de la sesión en segundos desde el login, aunque se renueve el token; predeterminado `43200` (12 h). Debe ser mayor o igual a `JWT_TTL`.
- `JWT_ISSUER`: emisor y audiencia; predeterminado `movalmacen-api2`.

Genera un secreto con `php -r "echo bin2hex(random_bytes(32));"` y guárdalo en la configuración del servidor. No lo publiques ni lo agregues al repositorio. Reinicia el proceso PHP después de cambiar su entorno. No se cargan archivos `.env` automáticamente.

`POST /movalmacen/api2/login` recibe JSON:

```json
{"usuario":"miusuario","clave":"micontraseña"}
```

Conserva `success` y `data` en la respuesta y añade `access_token`, `token_type` (`Bearer`) y `expires_in`. El token contiene la identidad del usuario, sin contraseña.

Envía `Authorization: Bearer <access_token>` para consultar `GET /movalmacen/api2/me` y las demás rutas. Todas las rutas registradas están protegidas salvo `POST /login`. Los tokens ausentes, inválidos o vencidos reciben HTTP 401. La configuración JWT inválida devuelve HTTP 500.

`POST /movalmacen/api2/refresh` (con `Authorization: Bearer <access_token>` vigente, sin cuerpo) devuelve un token nuevo con el mismo formato que `/login`. El usuario se toma del propio token, se revalida en la base de datos que siga activo (`flag_estado = '1'`; si no, HTTP 401 `user_inactive`) y se conserva la hora del login (`auth_time`): el token renovado nunca vence después de `auth_time + JWT_MAX_SESSION`, así que pasado ese tope hay que iniciar sesión nuevamente. La app lo llama cuando al token le quedan menos de 15 minutos y hay actividad; sin actividad el token vence normalmente.

Usa HTTPS. No hay revocación individual: desactivar al usuario impide su siguiente renovación, pero el token vigente sirve hasta vencer. Los cambios de usuario o perfil se reflejan al emitir un nuevo token. Cambiar el secreto invalida todos los tokens anteriores.

Verificación local sin Oracle: `php tests/jwt.php`.
