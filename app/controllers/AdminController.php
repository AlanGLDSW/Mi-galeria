<?php

class AdminController extends Controller
{
    public function usuarios(): void
    {
        $this->requiereAdmin();
        $buscar = trim((string) ($_GET['buscar'] ?? ''));
        $usuarios = (new UsuarioModel())->listarConResumen($buscar);
        $this->vista('admin/usuarios', [
            'titulo'   => 'Administración de usuarios',
            'usuarios' => $usuarios,
            'buscar'   => $buscar,
            'totales'  => [
                'usuarios' => count($usuarios),
                'imagenes' => array_sum(array_column($usuarios, 'imagenes')),
                'bytes'    => array_sum(array_column($usuarios, 'bytes')),
            ],
        ]);
    }

    public function editarForm(): void
    {
        $this->requiereAdmin();
        $usuario = (new UsuarioModel())->buscarPorId((int) ($_GET['id'] ?? 0));
        if (!$usuario) {
            $this->error(404);
            return;
        }
        $viejos = Session::tomarViejos();
        $this->vista('admin/editar', [
            'titulo'  => 'Editar usuario',
            'usuario' => $usuario,
            'datos'   => $viejos ?: $usuario,
            'errores' => $_SESSION['errores_form'] ?? [],
        ]);
        unset($_SESSION['errores_form']);
    }

    public function editar(): void
    {
        $admin = $this->requiereAdmin();
        $this->verificarCsrf();
        $modelo = new UsuarioModel();
        $id = (int) ($_POST['id'] ?? 0);
        $actual = $modelo->buscarPorId($id);
        if (!$actual) {
            $this->error(404);
            return;
        }

        [$datos, $errores] = ValidarUsuario::revisar($_POST, $id);
        $datos['rol'] = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'usuario';

        if ($actual['rol'] === 'admin' && $datos['rol'] !== 'admin' && $modelo->contarAdmins() <= 1) {
            $errores['rol'] = 'Debe existir al menos un administrador.';
        }

        if ($errores) {
            $_SESSION['errores_form'] = $errores;
            Session::guardarViejos(array_diff_key($datos, ['clave' => 1]));
            Session::flash('error', 'Revisa los campos marcados.');
            $this->redirigir('admin/editar', ['id' => $id]);
        }

        $modelo->actualizar($id, $datos);
        if ($id === $admin['id']) {
            Session::actualizarUsuario(['nombre' => $datos['nombre'], 'usuario' => $datos['usuario'], 'rol' => $datos['rol']]);
        }
        Session::flash('exito', "Se actualizaron los datos de {$datos['nombre']}.");
        $this->redirigir($id === $admin['id'] && $datos['rol'] !== 'admin' ? 'galeria' : 'admin');
    }

    public function borrar(): void
    {
        $admin = $this->requiereAdmin();
        $this->verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $modelo = new UsuarioModel();
        $usuario = $modelo->buscarPorId($id);

        if (!$usuario) {
            Session::flash('error', 'El usuario no existe.');
        } elseif ($id === $admin['id']) {
            Session::flash('error', 'No puedes borrar tu propia cuenta desde el panel.');
        } else {
            $archivos = (new ImagenModel())->archivosDe($id);
            $modelo->borrar($id);
            array_walk($archivos, [Almacen::class, 'borrar']);
            $n = count($archivos);
            Session::flash('exito', "Se eliminó la cuenta de {$usuario['nombre']} y sus $n " . ($n === 1 ? 'imagen' : 'imágenes') . '.');
        }
        $this->redirigir('admin');
    }

    public function galeria(): void
    {
        $this->requiereAdmin();
        $dueno = (new UsuarioModel())->buscarPorId((int) ($_GET['id'] ?? 0));
        if (!$dueno) {
            $this->error(404);
            return;
        }
        $this->vista('galeria/index', [
            'titulo' => 'Galería de ' . $dueno['nombre'],
            'dueno'  => $dueno,
            'modoAdmin' => true,
        ]);
    }
}
