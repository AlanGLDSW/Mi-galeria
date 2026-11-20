<?php
class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->db->prepare('SELECT id, nombre, usuario, correo, rol, creado, ultimo_acceso FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function buscarParaLogin(string $usuarioOCorreo): ?array
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE usuario = ? OR correo = ? LIMIT 1');
        $st->execute([$usuarioOCorreo, mb_strtolower($usuarioOCorreo)]);
        return $st->fetch() ?: null;
    }

    public function verificarClave(int $id, string $clave): bool
    {
        $st = $this->db->prepare('SELECT clave FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        $hash = $st->fetchColumn();
        return $hash && password_verify($clave, $hash);
    }

    public function existe(string $campo, string $valor, int $exceptoId = 0): bool
    {
        if (!in_array($campo, ['usuario', 'correo'], true)) {
            throw new InvalidArgumentException('Campo no válido');
        }
        $st = $this->db->prepare("SELECT 1 FROM usuarios WHERE $campo = ? AND id <> ?");
        $st->execute([$valor, $exceptoId]);
        return (bool) $st->fetchColumn();
    }

    public function crear(array $d): int
    {
        $st = $this->db->prepare('INSERT INTO usuarios (nombre, usuario, correo, clave, rol) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$d['nombre'], $d['usuario'], $d['correo'], password_hash($d['clave'], PASSWORD_DEFAULT), $d['rol'] ?? 'usuario']);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $campos = ['nombre = ?', 'usuario = ?', 'correo = ?'];
        $valores = [$d['nombre'], $d['usuario'], $d['correo']];
        if (isset($d['rol'])) {
            $campos[] = 'rol = ?';
            $valores[] = $d['rol'];
        }
        if (!empty($d['clave'])) {
            $campos[] = 'clave = ?';
            $valores[] = password_hash($d['clave'], PASSWORD_DEFAULT);
        }
        $valores[] = $id;
        $this->db->prepare('UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?')->execute($valores);
    }

    public function registrarAcceso(int $id): void
    {
        $this->db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$id]);
    }

    public function listarConResumen(string $buscar = ''): array
    {
        $sql = 'SELECT u.id, u.nombre, u.usuario, u.correo, u.rol, u.creado, u.ultimo_acceso,
                       COUNT(i.id) AS imagenes, COALESCE(SUM(i.tamano), 0) AS bytes
                  FROM usuarios u
                  LEFT JOIN imagenes i ON i.usuario_id = u.id';
        $valores = [];
        if ($buscar !== '') {
            $sql .= ' WHERE u.nombre LIKE ? OR u.usuario LIKE ? OR u.correo LIKE ?';
            $like = '%' . addcslashes($buscar, '%_\\') . '%';
            $valores = [$like, $like, $like];
        }
        $sql .= ' GROUP BY u.id ORDER BY u.rol = "admin" DESC, u.nombre';
        $st = $this->db->prepare($sql);
        $st->execute($valores);
        return $st->fetchAll();
    }

    public function contarAdmins(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
    }

    public function borrar(int $id): void
    {
        $this->db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    }
}
