<?php
declare(strict_types=1);
// Autoload: carrega a classe pelo namespace, sem Composer.
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'App\\') !== 0) return;
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
App\Helpers\Config::load(APP_ROOT . '/.env');
date_default_timezone_set(App\Helpers\Config::get('APP_TIMEZONE', 'America/Sao_Paulo'));
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_exception_handler([App\Helpers\ErrorHandler::class, 'handle']);
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
    App\Middleware\Session::start();
}
