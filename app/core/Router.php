<?php

require_once __DIR__ . '/ApiException.php';

class Router
{
    private $request;
    private $routes = ['get' => [], 'post' => [], 'put' => []];

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function put($path, $callback)
    {
        $this->routes['put'][$path] = $callback;
    }

    public function resolve()
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();



        $path = str_replace(APP_URL, '', strtolower($path));

        $callback = $this->routes[$method][$path] ?? null;

        if (!$callback) {
            foreach ($this->routes[$method] ?? [] as $route => $handler) {
                // Extraer los nombres de parámetros
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $paramNames);
                $paramNames = $paramNames[1]; // nos quedamos solo con los nombres

                // Crear patrón de regex
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route);

                // Comparar la URL real con el patrón
                if (preg_match("#^$pattern$#", $path, $matches)) {
                    array_shift($matches); // quitar coincidencia completa

                    //  Asignar a un objeto asociativo
                    $paramsAssoc = [];
                    foreach ($paramNames as $index => $name) {
                        $paramsAssoc[$name] = $matches[$index];
                    }

                    // Guardar en request
                    $this->request->addRouteParams($paramsAssoc);
                    $callback = $handler;
                    break;
                }
            }
        }

        if (!$callback) {
            $this->sendError(new ApiException('route_not_found', 'Ruta no encontrada', 404));
            return;
        }


        // Todas las rutas requieren JWT, excepto el inicio de sesión.
        if (!($method === 'post' && $path === '/login')) {
            require_once __DIR__ . '/JwtService.php';
            $token = $this->request->getBearerToken();
            try {
                if ($token === null) {
                    throw new UnexpectedValueException('Token requerido');
                }
                $this->request->setUser((new JwtService())->verify($token));
            } catch (JwtConfigurationException $e) {
                $this->sendError(new ApiException('auth_not_configured', 'Autenticación no configurada', 500, $e));
                return;
            } catch (Exception $e) {
                header('WWW-Authenticate: Bearer');
                $this->sendError(new ApiException('invalid_token', 'Token ausente, inválido o expirado', 401, $e));
                return;
            }
        }

        [$controllerName, $methodName] = explode('@', $callback);
        try {
            require_once __DIR__ . "/../controllers/$controllerName.php";
            $controller = new $controllerName();

            echo json_encode($controller->$methodName($this->request));
        } catch (ApiException $e) {
            $this->sendError($e);
        } catch (Throwable $e) {
            error_log('Unhandled API exception: ' . $e);
            $this->sendError(new ApiException('internal_error', 'Error interno', 500));
        }
    }

    private function sendError(ApiException $error): void
    {
        http_response_code($error->getStatusCode());
        echo json_encode([
            'error' => $error->getMessage(),
            'code' => $error->getErrorCode(),
        ]);
    }
}
