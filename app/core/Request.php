<?php

class Request
{
    private $routeParams = [];
    private $user = null;

    public function getBearerToken()
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }
        return preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches) ? $matches[1] : null;
    }

    public function setUser(array $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function addRouteParams($params)
    {
        $this->routeParams = array_merge($this->routeParams, $params);
    }

    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($path, PHP_URL_PATH);
    }

    public function getMethod()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getBody()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    public function getParams()
    {

        return array_merge($this->routeParams, $_GET ?? []);
    }
}
