<?php

declare(strict_types=1);

use HotelHub\Core\Application;
use HotelHub\Core\ErrorHandler;
use HotelHub\Core\Response;
use HotelHub\Core\View;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Core/helpers.php';

$config = require dirname(__DIR__) . '/config/config.php';
ErrorHandler::register($config['app']['debug']);

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$application = new Application($config['database']);
$router = $application->router();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!is_string($path) || !$router->dispatch($method, $path)) {
    Response::html(View::render('errors/404', ['title' => 'Faqja nuk u gjet']), 404);
}
