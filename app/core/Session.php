<?php
class Session
{
    public static function iniciar(): void
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name('galeria_sid');
        session_set_cookie_params([
            'lifetime' => 0,          
            'path'     => '/',
            'secure'   => $https,     
            'httponly' => true,       
            'samesite' => 'Lax',     
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        $limite = config('app.minutos_sesion', 60) * 60;
        if (isset($_SESSION['usuario'], $_SESSION['ultima_actividad'])
            && time() - $_SESSION['ultima_actividad'] > $limite) {
            self::cerrar();
            session_start();
            self::flash('aviso', 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.');
        }
        $_SESSION['ultima_actividad'] = time();
    }

    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'      => (int) $usuario['id'],
            'nombre'  => $usuario['nombre'],
            'usuario' => $usuario['usuario'],
            'rol'     => $usuario['rol'],
        ];
        unset($_SESSION['csrf']);
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function esAdmin(): bool
    {
        return (self::usuario()['rol'] ?? '') === 'admin';
    }

    public static function actualizarUsuario(array $datos): void
    {
        if (isset($_SESSION['usuario'])) {
            $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $datos);
        }
    }

    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][$tipo] = $mensaje;
    }

    public static function tomarFlash(): array
    {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }

    public static function guardarViejos(array $datos): void
    {
        $_SESSION['viejos'] = $datos;
    }

    public static function tomarViejos(): array
    {
        $v = $_SESSION['viejos'] ?? [];
        unset($_SESSION['viejos']);
        return $v;
    }

    public static function csrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfValido(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
