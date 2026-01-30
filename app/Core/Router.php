<?php

namespace App\Core;

/**
 * Simple Router for handling URL routes
 */
class Router
{
    private array $routes = [];
    private string $controllerNamespace = 'App\\Controllers\\';

    /**
     * Register GET route
     */
    public function get(string $path, string $controller, string $method = 'index'): self
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'method' => $method];
        return $this;
    }

    /**
     * Register POST route
     */
    public function post(string $path, string $controller, string $method = 'store'): self
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'method' => $method];
        return $this;
    }

    /**
     * Register PUT route
     */
    public function put(string $path, string $controller, string $method = 'update'): self
    {
        $this->routes['PUT'][$path] = ['controller' => $controller, 'method' => $method];
        return $this;
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, string $controller, string $method = 'destroy'): self
    {
        $this->routes['DELETE'][$path] = ['controller' => $controller, 'method' => $method];
        return $this;
    }

    /**
     * Dispatch the route
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Treat HEAD requests as GET for routing purposes
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Remove domain path if application is in subdirectory
        $basePath = str_replace('/public/index.php', '', $_SERVER['SCRIPT_NAME']);
        $path = str_replace($basePath, '', $path);
        $path = '/' . ltrim($path, '/');
        // Normalize trailing slash (remove it unless it's the root path)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Try to match route
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            $this->executeRoute($route);
            return;
        }

        // Try pattern matching
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            $matches = [];
            if ($this->matchRoute($pattern, $path, $matches)) {
                $this->executeRoute($route, $matches);
                return;
            }
        }

        // Route not found - show custom 404 page
        $this->show404();
    }

    /**
     * Display custom 404 page
     */
    public function show404(): void
    {
        http_response_code(404);
        $customPage = dirname(__DIR__, 2) . '/public/404.php';
        if (file_exists($customPage)) {
            include $customPage;
        } else {
            echo '404 Not Found';
        }
        exit;
    }

    /**
     * Execute controller action
     */
    private function executeRoute(array $route, array $params = []): void
    {
        $controllerName = $this->controllerNamespace . $route['controller'];
        $method = $route['method'];

        if (!class_exists($controllerName)) {
            error_log("Router: Controller not found: $controllerName");
            $this->show404();
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            error_log("Router: Method not found: $method in $controllerName");
            $this->show404();
        }

        // Add matched parameters to $_GET so controllers can access them
        foreach ($params as $key => $value) {
            if ($key !== 0) { // Skip numeric keys from preg_match
                $_GET[$key] = $value;
            }
        }

        $controller->$method();
    }

    /**
     * Simple pattern matching (/:id, /:slug, etc)
     */
    private function matchRoute(string $pattern, string $path, array &$matches): bool
    {
        $pattern = preg_replace('/:(\w+)/', '(?P<$1>[a-zA-Z0-9_-]+)', $pattern);
        $pattern = '#^' . $pattern . '$#i';

        if (preg_match($pattern, $path, $matches)) {
            return true;
        }
        return false;
    }
}
