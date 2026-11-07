<?php
namespace App\Core;

class Router {
    private array $routes = [];
    private $guard = null;

    public function setGuard(callable $guard): void {
        $this->guard = $guard;
    }

    public function get(string $path, $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'pattern' => '#^/' . trim($pattern, '/') . '$#',
            'handler' => $handler
        ];
    }

    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Detect current route from query parameter ?r= or REQUEST_URI
        if (isset($_GET['r'])) {
            $uri = '/' . trim($_GET['r'], '/');
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            // Remove subfolder if running inside Laragon subdirectory like /mrpepsi
            $scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            if (!empty($scriptDir) && strpos($uri, '/' . $scriptDir) === 0) {
                $uri = substr($uri, strlen('/' . $scriptDir));
            }
            $uri = '/' . trim($uri, '/');
        }

        // Guard hak akses (permission) sebelum route dieksekusi
        if ($this->guard !== null) {
            call_user_func($this->guard, $uri);
        }

        foreach ($this->routes as $route) {
            $methodMatch = ($route['method'] === $requestMethod) || ($route['method'] === 'GET' && $requestMethod === 'HEAD');
            if ($methodMatch && preg_match($route['pattern'], $uri, $matches)) {
                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (method_exists($controller, $action)) {
                            call_user_func_array([$controller, $action], $params);
                            return;
                        }
                    }
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h1 style='color:#E3262E;'>404 - Halaman Tidak Ditemukan</h1>";
        echo "<p>Rute [{$requestMethod} {$uri}] tidak terdaftar di sistem.</p>";
        echo "<p><a href='" . url('/') . "' style='display:inline-block; margin-top:15px; padding:10px 20px; background:#2B3A9E; color:#fff; text-decoration:none; border-radius:8px;'>Kembali ke Beranda</a></p>";
        echo "</div>";
    }
}
