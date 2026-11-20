<?php
class CuentaController extends Controller
{
    public function index(): void
    {
        $u = $this->requiereSesion();
        $modelo = new UsuarioModel();
        $this->vista('cuenta/index', [
            'titulo'  => 'Mi cuenta',
            'datos'   => Session::tomarViejos() ?: $modelo->buscarPorId($u['id']),
            'errores' => $_SESSION['errores_form'] ?? [],
            'bytes'   => (new ImagenModel())->bytesUsados($u['id']),
        ]);
        unset($_SESSION['errores_form']);
    }

    public function guardar(): void
    {
        $u = $this->requiereSesion();
        $this->verificarCsrf();
        $modelo = new UsuarioModel();

        [$datos, $errores] = ValidarUsuario::revisar($_POST, $u['id']);

        if ($datos['clave'] !== '' && !$modelo->verificarClave($u['id'], (string) ($_POST['actual'] ?? ''))) {
            $errores['actual'] = 'La contraseña actual no es correcta.';
        }

        if ($errores) {
            $_SESSION['errores_form'] = $errores;
            Session::guardarViejos(array_diff_key($datos, ['clave' => 1]));
            Session::flash('error', 'Revisa los campos marcados.');
            $this->redirigir('cuenta');
        }

        $modelo->actualizar($u['id'], $datos);
        Session::actualizarUsuario(['nombre' => $datos['nombre'], 'usuario' => $datos['usuario']]);
        Session::flash('exito', $datos['clave'] !== '' ? 'Datos y contraseña actualizados.' : 'Datos actualizados.');
        $this->redirigir('cuenta');
    }
}
