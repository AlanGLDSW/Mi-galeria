<?php
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = '', array $parametros = []): string
{
    $q = $ruta === '' ? [] : ['r' => $ruta];
    $q += $parametros;
    return 'index.php' . ($q ? '?' . http_build_query($q) : '');
}

function recurso(string $ruta): string
{
    $archivo = BASE_PATH . '/' . $ruta;
    $v = is_file($archivo) ? filemtime($archivo) : 1;
    return $ruta . '?v=' . $v;
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Session::csrf()) . '">';
}

function tamano_legible(int $bytes): string
{
    if ($bytes === 0) {
        return '0 KB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

function fecha_legible(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y, H:i', $t);
}
