<?php
class AuthController extends Controller
{
    private const COOKIE_RECORDAR = 'galeria_recordar';
    private const INTENTOS_MAX = 5;
    private const ESPERA_SEG = 60;

    public function loginForm(): void
    {
        if (Session::usuario()) {
            $this->redirigir('galeria');
        }
        $recordado = $_COOKIE[self::COOKIE_RECORDAR] ?? '';
        $this->vista('auth/login', [
            'titulo'    => 'Iniciar sesión',
            'recordado' => preg_match('/^[A-Za-z0-9_.@-]{3,150}$/', $recordado) ? $recordado : '',
            'viejos'    => Session::tomarViejos(),
        ]);
    }

    public function login(): void
    {
        $this->verificarCsrf();
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $clave = (string) ($_POST['clave'] ?? '');
        $recordar = isset($_POST['recordar']);

        $bloqueo = $_SESSION['login_bloqueado_hasta'] ?? 0;
        if ($bloqueo > time()) {
            $seg = $bloqueo - time();
            Session::flash('error', "Demasiados intentos. Espera $seg segundos e intenta de nuevo.");
            Session::guardarViejos(['usuario' => $usuario]);
            $this->redirigir('login');
        }

        $fila = $usuario !== '' ? (new UsuarioModel())->buscarParaLogin($usuario) : null;
        if (!$fila || !password_verify($clave, $fila['clave'])) {
            $_SESSION['login_fallidos'] = ($_SESSION['login_fallidos'] ?? 0) + 1;
            if ($_SESSION['login_fallidos'] >= self::INTENTOS_MAX) {
                $_SESSION['login_bloqueado_hasta'] = time() + self::ESPERA_SEG;
                $_SESSION['login_fallidos'] = 0;
            }
            Session::flash('error', 'Usuario o contraseña incorrectos.');
            Session::guardarViejos(['usuario' => $usuario, 'recordar' => $recordar]);
            $this->redirigir('login');
        }

        unset($_SESSION['login_fallidos'], $_SESSION['login_bloqueado_hasta']);
        $modelo = new UsuarioModel();
        $accesoAnterior = $fila['ultimo_acceso'];
        $modelo->registrarAcceso((int) $fila['id']);
        Session::entrar($fila);

        $opciones = ['path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])];
        if ($recordar) {
            setcookie(self::COOKIE_RECORDAR, $fila['usuario'], ['expires' => time() + 30 * 86400] + $opciones);
        } else {
            setcookie(self::COOKIE_RECORDAR, '', ['expires' => time() - 3600] + $opciones);
        }

        $saludo = "¡Hola, {$fila['nombre']}!";
        if ($accesoAnterior) {
            $saludo .= ' Tu último acceso fue el ' . fecha_legible($accesoAnterior) . '.';
        }
        Session::flash('exito', $saludo);
        $this->redirigir('galeria');
    }

    public function registroForm(): void
    {
        if (Session::usuario()) {
            $this->redirigir('galeria');
        }
        $this->vista('auth/registro', [
            'titulo'  => 'Crear cuenta',
            'viejos'  => Session::tomarViejos(),
            'errores' => $_SESSION['errores_form'] ?? [],
        ]);
        unset($_SESSION['errores_form']);
    }

    public function registro(): void
    {
        $this->verificarCsrf();
        [$datos, $errores] = ValidarUsuario::revisar($_POST, 0, true);
        if ($errores) {
            $_SESSION['errores_form'] = $errores;
            Session::guardarViejos(array_diff_key($datos, ['clave' => 1]));
            Session::flash('error', 'Revisa los campos marcados.');
            $this->redirigir('registro');
        }

        $modelo = new UsuarioModel();
        $id = $modelo->crear($datos);
        $modelo->registrarAcceso($id);
        Session::entrar($modelo->buscarPorId($id));
        Session::flash('exito', "¡Bienvenido, {$datos['nombre']}! Tu galería está lista: sube tus primeras imágenes.");
        $this->redirigir('galeria');
    }

    public function logout(): void
    {
        $this->verificarCsrf();
        Session::cerrar();
        session_start();
        Session::flash('exito', 'Cerraste sesión correctamente.');
        $this->redirigir('login');
    }
}
