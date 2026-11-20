<?php
class ImagenModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function listarDe(int $usuarioId): array
    {
        $st = $this->db->prepare(
            'SELECT id, titulo, nombre_original, mime, tamano, ancho, alto, creado
               FROM imagenes WHERE usuario_id = ? ORDER BY creado DESC, id DESC'
        );
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }

    public function buscarDe(int $id, int $usuarioId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM imagenes WHERE id = ? AND usuario_id = ?');
        $st->execute([$id, $usuarioId]);
        return $st->fetch() ?: null;
    }

    public function buscarCualquiera(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM imagenes WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function bytesUsados(int $usuarioId): int
    {
        $st = $this->db->prepare('SELECT COALESCE(SUM(tamano), 0) FROM imagenes WHERE usuario_id = ?');
        $st->execute([$usuarioId]);
        return (int) $st->fetchColumn();
    }

    public function crear(array $d): int
    {
        $st = $this->db->prepare(
            'INSERT INTO imagenes (usuario_id, titulo, archivo, miniatura, nombre_original, mime, tamano, ancho, alto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $d['usuario_id'], $d['titulo'], $d['archivo'], $d['miniatura'], $d['nombre_original'],
            $d['mime'], $d['tamano'], $d['ancho'], $d['alto'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function renombrar(int $id, int $usuarioId, string $titulo): bool
    {
        $st = $this->db->prepare('UPDATE imagenes SET titulo = ? WHERE id = ? AND usuario_id = ?');
        $st->execute([$titulo, $id, $usuarioId]);
        return $st->rowCount() > 0 || $this->buscarDe($id, $usuarioId) !== null;
    }

    public function borrarVarias(array $ids, int $usuarioId): array
    {
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT id, archivo, miniatura FROM imagenes WHERE usuario_id = ? AND id IN ($marcas)");
        $st->execute([$usuarioId, ...$ids]);
        $filas = $st->fetchAll();
        if ($filas) {
            $marcas = implode(',', array_fill(0, count($filas), '?'));
            $this->db->prepare("DELETE FROM imagenes WHERE usuario_id = ? AND id IN ($marcas)")
                ->execute([$usuarioId, ...array_column($filas, 'id')]);
        }
        return $filas;
    }

    public function archivosDe(int $usuarioId): array
    {
        $st = $this->db->prepare('SELECT archivo, miniatura FROM imagenes WHERE usuario_id = ?');
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }
}
