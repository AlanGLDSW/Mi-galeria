<?php
class Controller
{
    protected function vista(string $vista, array $datos = []): void
    {
        View::render($vista, $datos);
    }

    protected function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    protected function redirigir(string $ruta, array $parametros = []): void
    {
        header('Location: ' . url($ruta, $parametros));
        exit;
    }

    protected function esApi(): bool
    {
        return str_starts_with($_GET['r'] ?? '', 'api/');
    }

    protected function requiereSesion(): array
    {
        $u = Session::usuario();
        if ($u) {

            $fila = (new UsuarioModel())->buscarPorId($u['id']);
            if ($fila) {
                Session::actualizarUsuario(['nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'rol' => $fila['rol']]);
                header('Cache-Control: no-store'); 
                return Session::usuario();
            }
            Session::cerrar();
            session_start();
            $u = null;
        }
        if ($this->esApi()) {
            $this->json(['ok' => false, 'error' => 'Tu sesión terminó. Vuelve a iniciar sesión.'], 401);
            exit;
        }
        Session::flash('aviso', 'Inicia sesión para continuar.');
        $this->redirigir('login');
    }

    protected function requiereAdmin(): array
    {
        $u = $this->requiereSesion();
        if ($u['rol'] !== 'admin') {
            $this->error(403);
            exit;
        }
        return $u;
    }

    protected function verificarCsrf(): void
    {
        $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (Session::csrfValido($token)) {
            return;
        }
        if ($this->esApi()) {
            $this->json(['ok' => false, 'error' => 'La página caducó. Recárgala e intenta de nuevo.'], 419);
            exit;
        }
        Session::flash('error', 'La página caducó. Intenta de nuevo.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url()));
        exit;
    }

    public function error(int $codigo): void
    {
        $mensajes = [
            403 => ['Acceso restringido', 'No tienes permiso para ver esta página.'],
            404 => ['Página no encontrada', 'La página que buscas no existe o fue movida.'],
            405 => ['Acción no permitida', 'Esa acción no se puede hacer de esta forma.'],
        ];
        [$titulo, $mensaje] = $mensajes[$codigo] ?? ['Error', 'Ocurrió un error.'];
        http_response_code($codigo);
        if ($this->esApi()) {
            $this->json(['ok' => false, 'error' => $mensaje], $codigo);
            return;
        }
        View::render('errores/error', compact('titulo', 'mensaje', 'codigo'));
    }
}
