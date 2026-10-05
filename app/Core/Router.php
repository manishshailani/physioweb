<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple regex-based router. Routes are registered with a method, a path
 * pattern using {param} placeholders, and either a [ControllerClass, method]
 * callable or a Closure. Matching is first-registered-first-matched.
 *
 * SEO note: paths are plain, human-readable segments (e.g.
 * /services/{slug}, /blog/{slug}) by design, per the SEO-friendly-URL
 * requirement — there is no query-string-based routing.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, params:array, handler:mixed, middleware:array}> */
    private array $routes = [];

    public function get(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    public function any(array $methods, string $pattern, mixed $handler, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->add(strtoupper($method), $pattern, $handler, $middleware);
        }
    }

    private function add(string $method, string $pattern, mixed $handler, array $middleware): void
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'params' => $paramNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        $matchedOtherMethod = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $matchedOtherMethod = true;
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], $matches);

            foreach ($route['middleware'] as $middleware) {
                /** @var callable $middleware */
                $result = $middleware($request);
                if ($result === false) {
                    return; // middleware is expected to have sent its own response
                }
            }

            $this->call($route['handler'], $params, $request);
            return;
        }

        if ($matchedOtherMethod) {
            http_response_code(405);
            echo '405 Method Not Allowed';
            return;
        }

        Response::notFound('The page you requested could not be found.');
    }

    private function call(mixed $handler, array $params, Request $request): void
    {
        if ($handler instanceof \Closure) {
            $this->invokeWithResolvedArgs($handler, $params, $request);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $methodName] = $handler;
            $controller = new $class();
            $this->invokeWithResolvedArgs([$controller, $methodName], $params, $request);
            return;
        }

        throw new \RuntimeException('Invalid route handler.');
    }

    /**
     * Calls a route handler (controller method or Closure) with whichever of
     * ($params, $request) its own declared parameters actually ask for, by
     * type, rather than always passing both positionally as ($params,
     * $request).
     *
     * Why this exists: action methods in this codebase are written in two
     * legitimate styles -- `show(array $params)` for a route with a
     * {placeholder} and no need for the request, and `index(Request
     * $request)` for a route with no {placeholder} that still needs query
     * input -- and some need both, as `update(array $params, Request
     * $request)`. A fixed positional call (always `($params, $request)`)
     * breaks the second style: PHP maps the first positional argument to
     * the method's first declared parameter regardless of name, so an
     * `index(Request $request)` action received the (always-empty) $params
     * array where a Request was declared, raising
     * `TypeError: Argument #1 ($request) must be of type Request, array
     * given` on every zero-URL-parameter route that reads request input --
     * notably every admin/patient login POST and every admin list page.
     * This was never caught by this project's own test suite because
     * tests/render_*_test.php render Views directly and never dispatch a
     * real HTTP request through this Router (see tests/README.md).
     *
     * Reflection-based resolution-by-type fixes every existing action
     * method as already written, in whatever order it declares its
     * parameters, without changing any of the ~130 controller action
     * signatures across this codebase -- the smallest change that corrects
     * the actual defect.
     */
    private function invokeWithResolvedArgs(callable $callable, array $params, Request $request): void
    {
        $reflection = is_array($callable)
            ? new \ReflectionMethod($callable[0], $callable[1])
            : new \ReflectionFunction($callable);

        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($typeName === Request::class) {
                $args[] = $request;
            } elseif ($typeName === 'array') {
                $args[] = $params;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        $callable(...$args);
    }
}
