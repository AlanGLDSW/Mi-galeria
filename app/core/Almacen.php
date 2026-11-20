<?php
class Almacen
{
    public const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private const ANCHO_MINIATURA = 480;

    public static function carpeta(): string
    {
        return BASE_PATH . '/uploads';
    }

    public static function validar(array $f): array
    {
        $nombre = $f['name'] ?? 'archivo';
        $maxMb = config('subidas.mb_por_archivo');

        switch ($f['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException("“{$nombre}” pesa más de $maxMb MB.");
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No se recibió ningún archivo.');
            default:
                throw new RuntimeException("No se pudo recibir “{$nombre}”. Intenta de nuevo.");
        }

        if (!is_uploaded_file($f['tmp_name'])) {
            throw new RuntimeException("No se pudo recibir “{$nombre}”.");
        }
        if ($f['size'] > $maxMb * 1048576) {
            throw new RuntimeException("“{$nombre}” pesa más de $maxMb MB.");
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::TIPOS[$mime])) {
            throw new RuntimeException("“{$nombre}” no es una imagen JPG, PNG, GIF o WEBP.");
        }
        $info = @getimagesize($f['tmp_name']);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            throw new RuntimeException("“{$nombre}” está dañada o no es una imagen válida.");
        }

        return [
            'tmp'    => $f['tmp_name'],
            'nombre' => mb_substr($nombre, 0, 255),
            'mime'   => $mime,
            'tamano' => (int) $f['size'],
            'ancho'  => (int) $info[0],
            'alto'   => (int) $info[1],
        ];
    }

    public static function guardar(array $img): array
    {
        $base = bin2hex(random_bytes(16));
        $archivo = $base . '.' . self::TIPOS[$img['mime']];
        $destino = self::carpeta() . '/' . $archivo;

        if (!move_uploaded_file($img['tmp'], $destino)) {
            throw new RuntimeException("No se pudo guardar “{$img['nombre']}”.");
        }
        @chmod($destino, 0644);

        $miniatura = self::crearMiniatura($destino, $img['mime'], $base);
        return ['archivo' => $archivo, 'miniatura' => $miniatura];
    }

    private static function crearMiniatura(string $ruta, string $mime, string $base): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }
        try {
            $origen = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($ruta),
                'image/png'  => @imagecreatefrompng($ruta),
                'image/gif'  => @imagecreatefromgif($ruta),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            };
            if (!$origen) {
                return null;
            }
            if ($mime === 'image/jpeg') {
                $origen = self::corregirOrientacion($origen, $ruta);
            }

            $w = imagesx($origen);
            $h = imagesy($origen);
            $nw = min(self::ANCHO_MINIATURA, $w);
            $nh = (int) max(1, round($h * $nw / $w));

            $mini = imagecreatetruecolor($nw, $nh);
            imagefill($mini, 0, 0, imagecolorallocate($mini, 30, 36, 51));
            imagecopyresampled($mini, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);

            $nombre = $base . '_mini.jpg';
            imagejpeg($mini, self::carpeta() . '/miniaturas/' . $nombre, 82);
            imagedestroy($origen);
            imagedestroy($mini);
            return $nombre;
        } catch (Throwable $e) {
            error_log('Miniatura: ' . $e->getMessage());
            return null;
        }
    }

    private static function corregirOrientacion($img, string $ruta)
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($ruta);
        $giro = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        return $giro ? imagerotate($img, $giro, 0) : $img;
    }

    public static function borrar(array $fila): void
    {
        $archivo = basename((string) $fila['archivo']);
        if ($archivo !== '') {
            @unlink(self::carpeta() . '/' . $archivo);
        }
        if (!empty($fila['miniatura'])) {
            @unlink(self::carpeta() . '/miniaturas/' . basename($fila['miniatura']));
        }
    }

    public static function enviar(array $img, bool $miniatura, bool $descargar): void
    {
        $ruta = self::carpeta() . '/' . basename($img['archivo']);
        $mime = $img['mime'];
        if ($miniatura && $img['miniatura']) {
            $mini = self::carpeta() . '/miniaturas/' . basename($img['miniatura']);
            if (is_file($mini)) {
                $ruta = $mini;
                $mime = 'image/jpeg';
            }
        }
        if (!is_file($ruta)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, max-age=86400');
        header("Content-Security-Policy: default-src 'none'");
        $disposicion = $descargar ? 'attachment' : 'inline';
        $nombre = $img['nombre_original'];
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre);
        header("Content-Disposition: $disposicion; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($nombre));
        readfile($ruta);
        exit;
    }
}
