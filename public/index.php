<?php

declare(strict_types=1);

/**
 * Front controller. Every request is routed through here (see public/.htaccess).
 */

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

$config = require dirname(__DIR__) . '/app/bootstrap.php';

Session::start((string) $config['session']['name'], (int) $config['session']['lifetime_minutes']);
Response::securityHeaders();

$router = require dirname(__DIR__) . '/routes/web.php';

$request = new Request();
$router->dispatch($request);
