<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/app/core/bootstrap.php';

$router = new Router(require BASE_PATH . '/app/config/rutas.php');
$router->despachar($_GET['r'] ?? '', $_SERVER['REQUEST_METHOD'] ?? 'GET');
