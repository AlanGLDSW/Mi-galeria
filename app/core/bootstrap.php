<?php
declare(strict_types=1);

$GLOBALS['config'] = require BASE_PATH . '/app/config/config.php';

function config(string $clave, $defecto = null)
{
    $valor = $GLOBALS['config'];
    foreach (explode('.', $clave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $defecto;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

spl_autoload_register(function (string $clase): void {
    foreach (['core', 'controllers', 'models'] as $carpeta) {
        $ruta = BASE_PATH . "/app/$carpeta/$clase.php";
        if (is_file($ruta)) {
            require $ruta;
            return;
        }
    }
});

require BASE_PATH . '/app/core/helpers.php';

date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');

ini_set('display_errors', config('app.depurar') ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    $esApi = str_starts_with($_GET['r'] ?? '', 'api/');
    http_response_code(500);
    if ($esApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Ocurrió un error en el servidor.']);
        return;
    }
    $detalle = config('app.depurar') ? $e->getMessage() : null;
    View::render('errores/error', [
        'titulo'  => 'Algo salió mal',
        'codigo'  => 500,
        'mensaje' => 'Ocurrió un error en el servidor. Intenta de nuevo en unos minutos.',
        'detalle' => $detalle,
    ]);
});

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

Session::iniciar();
