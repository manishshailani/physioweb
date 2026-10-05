<?php

declare(strict_types=1);

/**
 * Application bootstrap. Included by public/index.php (web) and by the
 * test harness (tests/) so both share identical configuration/error
 * handling/DB-connection logic — there is exactly one place this wiring
 * happens.
 */

use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\View;

$rootPath = dirname(__DIR__);

// Composer autoloader. If it hasn't been generated yet (composer install
// was not run), fall back to a minimal PSR-4 autoloader for the App\
// namespace so the application (minus third-party libraries like
// PHPMailer/Dompdf/PhpSpreadsheet) still runs. This fallback exists
// specifically so this codebase can be exercised in this development
// sandbox, which has no network access to Packagist; production
// deployments should always run `composer install` and will use the real
// autoloader transparently.
$composerAutoload = $rootPath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($rootPath) {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = $rootPath . '/app/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

Env::load($rootPath . '/.env');

$config = require $rootPath . '/config/config.php';

date_default_timezone_set((string) $config['app']['timezone']);

Logger::init($rootPath . '/storage/logs/app.log');
ErrorHandler::register((bool) $config['app']['debug']);

Database::connect($config['database']);

View::init($rootPath . '/app/Views');

Auth::configure((int) $config['security']['login_max_attempts'], (int) $config['security']['login_lockout_minutes']);

return $config;
