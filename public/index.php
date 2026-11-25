<?php

declare(strict_types=1);

use HotelHub\Core\Application;
use HotelHub\Core\ErrorHandler;
use HotelHub\Core\Request;
use HotelHub\Core\Response;
use HotelHub\Core\Request;
use HotelHub\Core\View;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Core/helpers.php';

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);
ErrorHandler::register($config['app']['debug']);

// Header-a bazë sigurie për çdo përgjigje.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$application = new Application($config['database']);
$router = $application->router();

$method = Request::method() === 'HEAD' ? 'GET' : Request::method();

if (!$router->dispatch($method, Request::path())) {
    if (Request::isApi()) {
        Response::json(['success' => false, 'error' => 'NOT_FOUND', 'message' => 'Endpoint-i nuk ekziston.'], 404);
    }

if (!is_string($path) || !$router->dispatch($method, $path)) {
    if (Request::isApi()) {
        Response::json(['success' => false, 'error' => 'NOT_FOUND', 'message' => 'Endpoint-i nuk u gjet.'], 404);
    }
    Response::html(View::render('errors/404', ['title' => 'Faqja nuk u gjet']), 404);
}
