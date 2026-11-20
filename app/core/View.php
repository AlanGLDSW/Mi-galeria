<?php
class View
{
    public static function render(string $__vista, array $__variables = [], string $__plantilla = 'layout'): void
    {
        $__variables['usuarioSesion'] = Session::usuario();
        $__variables['flash'] = $__variables['flash'] ?? Session::tomarFlash();

        extract($__variables, EXTR_SKIP);
        ob_start();
        require BASE_PATH . "/app/views/$__vista.php";
        $contenido = ob_get_clean();

        require BASE_PATH . "/app/views/$__plantilla.php";
    }
}
