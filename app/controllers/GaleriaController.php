<?php
class GaleriaController extends Controller
{
    public function index(): void
    {
        $u = $this->requiereSesion();
        $this->vista('galeria/index', [
            'titulo'    => 'Mi galería',
            'dueno'     => (new UsuarioModel())->buscarPorId($u['id']),
            'modoAdmin' => false,
        ]);
    }

    public function listar(): void
    {
        $u = $this->requiereSesion();
        $duenoId = $this->duenoSolicitado($u);
        $modelo = new ImagenModel();

        $imagenes = array_map([$this, 'formatear'], $modelo->listarDe($duenoId));
        $this->json([
            'ok'       => true,
            'imagenes' => $imagenes,
            'uso'      => $this->uso($modelo->bytesUsados($duenoId)),
        ]);
    }

    public function subir(): void
    {
        $u = $this->requiereSesion();

        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->json(['ok' => false, 'error' => 'El envío es demasiado grande. Sube menos imágenes a la vez.'], 413);
            return;
        }
        $this->verificarCsrf();

        $archivos = $this->normalizarArchivos($_FILES['imagenes'] ?? null);
        $max = config('subidas.archivos_por_envio');
        if (!$archivos) {
            $this->json(['ok' => false, 'error' => 'Elige al menos una imagen.'], 422);
            return;
        }
        if (count($archivos) > $max) {
            $this->json(['ok' => false, 'error' => "Puedes subir hasta $max imágenes a la vez."], 422);
            return;
        }

        $modelo = new ImagenModel();
        $usados = $modelo->bytesUsados($u['id']);
        $limite = config('subidas.mb_por_usuario') * 1048576;
        $creadas = [];
        $errores = [];

        foreach ($archivos as $f) {
            try {
                $img = Almacen::validar($f);
                if ($usados + $img['tamano'] > $limite) {
                    throw new RuntimeException("No hay espacio para “{$img['nombre']}”: tu límite es de " . config('subidas.mb_por_usuario') . ' MB.');
                }
                $guardado = Almacen::guardar($img);
                $id = $modelo->crear([
                    'usuario_id'      => $u['id'],
                    'titulo'          => $this->tituloDesde($img['nombre']),
                    'archivo'         => $guardado['archivo'],
                    'miniatura'       => $guardado['miniatura'],
                    'nombre_original' => $img['nombre'],
                    'mime'            => $img['mime'],
                    'tamano'          => $img['tamano'],
                    'ancho'           => $img['ancho'],
                    'alto'            => $img['alto'],
                ]);
                $usados += $img['tamano'];
                $creadas[] = $this->formatear($modelo->buscarDe($id, $u['id']));
            } catch (RuntimeException $e) {
                $errores[] = $e->getMessage();
            }
        }

        $this->json([
            'ok'       => (bool) $creadas,
            'imagenes' => $creadas,
            'errores'  => $errores,
            'uso'      => $this->uso($usados),
        ], $creadas ? 201 : 422);
    }

    public function renombrar(): void
    {
        $u = $this->requiereSesion();
        $this->verificarCsrf();
        $datos = $this->cuerpoJson();
        $duenoId = $this->duenoSolicitado($u, $datos['usuario'] ?? null);
        $id = (int) ($datos['id'] ?? 0);
        $titulo = trim(preg_replace('/\s+/u', ' ', (string) ($datos['titulo'] ?? '')));

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $this->json(['ok' => false, 'error' => 'El título debe tener entre 1 y 150 caracteres.'], 422);
            return;
        }
        $modelo = new ImagenModel();
        if (!$modelo->buscarDe($id, $duenoId)) {
            $this->json(['ok' => false, 'error' => 'La imagen no existe.'], 404);
            return;
        }
        $modelo->renombrar($id, $duenoId, $titulo);
        $this->json(['ok' => true, 'imagen' => $this->formatear($modelo->buscarDe($id, $duenoId))]);
    }

    public function borrar(): void
    {
        $u = $this->requiereSesion();
        $this->verificarCsrf();
        $datos = $this->cuerpoJson();
        $duenoId = $this->duenoSolicitado($u, $datos['usuario'] ?? null);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($datos['ids'] ?? [])), fn ($n) => $n > 0)));

        if (!$ids) {
            $this->json(['ok' => false, 'error' => 'Selecciona al menos una imagen.'], 422);
            return;
        }
        $modelo = new ImagenModel();
        $borradas = $modelo->borrarVarias($ids, $duenoId);
        if (!$borradas) {
            $this->json(['ok' => false, 'error' => 'Las imágenes no existen o ya fueron borradas.'], 404);
            return;
        }
        array_walk($borradas, [Almacen::class, 'borrar']);

        $this->json([
            'ok'  => true,
            'ids' => array_map('intval', array_column($borradas, 'id')),
            'uso' => $this->uso($modelo->bytesUsados($duenoId)),
        ]);
    }

    public function archivo(): void
    {
        $u = $this->requiereSesion();
        $modelo = new ImagenModel();
        $id = (int) ($_GET['id'] ?? 0);
        $img = $u['rol'] === 'admin' ? $modelo->buscarCualquiera($id) : $modelo->buscarDe($id, $u['id']);
        if (!$img) {
            $this->error(404);
            return;
        }
        Almacen::enviar($img, ($_GET['t'] ?? '') === 'mini', isset($_GET['descargar']));
    }

    private function duenoSolicitado(array $u, $valor = null): int
    {
        $pedido = (int) ($valor ?? ($_GET['usuario'] ?? 0));
        if ($pedido && $pedido !== $u['id']) {
            if ($u['rol'] !== 'admin') {
                $this->json(['ok' => false, 'error' => 'No tienes permiso para ver esa galería.'], 403);
                exit;
            }
            if (!(new UsuarioModel())->buscarPorId($pedido)) {
                $this->json(['ok' => false, 'error' => 'El usuario no existe.'], 404);
                exit;
            }
            return $pedido;
        }
        return $u['id'];
    }

    private function formatear(array $i): array
    {
        $id = (int) $i['id'];
        return [
            'id'        => $id,
            'titulo'    => $i['titulo'],
            'original'  => $i['nombre_original'],
            'tipo'      => strtoupper(Almacen::TIPOS[$i['mime']] ?? ''),
            'tamano'    => (int) $i['tamano'],
            'ancho'     => (int) $i['ancho'],
            'alto'      => (int) $i['alto'],
            'creado'    => date(DATE_ATOM, strtotime($i['creado'])),
            'url'       => url('imagen', ['id' => $id]),
            'mini'      => url('imagen', ['id' => $id, 't' => 'mini']),
            'descargar' => url('imagen', ['id' => $id, 'descargar' => 1]),
        ];
    }

    private function uso(int $bytes): array
    {
        return ['bytes' => $bytes, 'limite' => config('subidas.mb_por_usuario') * 1048576];
    }

    private function normalizarArchivos(?array $campo): array
    {
        if (!$campo || !isset($campo['name'])) {
            return [];
        }
        if (!is_array($campo['name'])) {
            return $campo['error'] === UPLOAD_ERR_NO_FILE ? [] : [$campo];
        }
        $lista = [];
        foreach ($campo['name'] as $i => $nombre) {
            if ($campo['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $lista[] = [
                'name' => $nombre, 'type' => $campo['type'][$i], 'tmp_name' => $campo['tmp_name'][$i],
                'error' => $campo['error'][$i], 'size' => $campo['size'][$i],
            ];
        }
        return $lista;
    }

    private function tituloDesde(string $nombre): string
    {
        $base = trim(preg_replace('/[_-]+/', ' ', pathinfo($nombre, PATHINFO_FILENAME)));
        return mb_substr($base !== '' ? $base : 'Imagen', 0, 150);
    }

    private function cuerpoJson(): array
    {
        $datos = json_decode(file_get_contents('php://input') ?: '', true);
        return is_array($datos) ? $datos : $_POST;
    }
}
