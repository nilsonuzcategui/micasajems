<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;

final class Actividad
{
    public const CATEGORIAS = ['culto', 'estudio', 'evento', 'ministerio', 'social', 'otro'];
    public const ESTADOS = ['programada', 'cancelada', 'realizada'];

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM actividades WHERE 1=1';
        $params = [];

        if (!empty($filters['estado'])) {
            $sql .= ' AND estado = :estado';
            $params[':estado'] = $filters['estado'];
        } else {
            $sql .= " AND estado != 'cancelada'";
        }

        if (!empty($filters['desde'])) {
            $sql .= ' AND fecha >= :desde';
            $params[':desde'] = $filters['desde'];
        }

        if (!empty($filters['hasta'])) {
            $sql .= ' AND fecha <= :hasta';
            $params[':hasta'] = $filters['hasta'];
        }

        if (!empty($filters['categoria'])) {
            $sql .= ' AND categoria = :categoria';
            $params[':categoria'] = $filters['categoria'];
        }

        $sql .= ' ORDER BY fecha ASC, hora_inicio ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'cast'], $stmt->fetchAll());
    }

    public static function week(string $anchorDate): array
    {
        $ts = strtotime($anchorDate);
        if ($ts === false) {
            $ts = time();
        }
        $start = date('Y-m-d', $ts);
        $end = date('Y-m-d', strtotime($start . ' +6 days'));

        return self::all(['desde' => $start, 'hasta' => $end]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM actividades WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::cast($row) : null;
    }

    public static function create(array $data, ?int $userId = null): int
    {
        $sql = 'INSERT INTO actividades
            (titulo, descripcion, lugar, fecha, hora_inicio, hora_fin, categoria, destacado, estado, creado_por)
            VALUES
            (:titulo, :descripcion, :lugar, :fecha, :hora_inicio, :hora_fin, :categoria, :destacado, :estado, :creado_por)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':lugar' => $data['lugar'],
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'] ?? null,
            ':categoria' => $data['categoria'] ?? 'culto',
            ':destacado' => !empty($data['destacado']) ? 1 : 0,
            ':estado' => $data['estado'] ?? 'programada',
            ':creado_por' => $userId,
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sql = 'UPDATE actividades SET
            titulo = :titulo,
            descripcion = :descripcion,
            lugar = :lugar,
            fecha = :fecha,
            hora_inicio = :hora_inicio,
            hora_fin = :hora_fin,
            categoria = :categoria,
            destacado = :destacado,
            estado = :estado
            WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':lugar' => $data['lugar'],
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'] ?? null,
            ':categoria' => $data['categoria'] ?? 'culto',
            ':destacado' => !empty($data['destacado']) ? 1 : 0,
            ':estado' => $data['estado'] ?? 'programada',
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM actividades WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public static function stats(): array
    {
        $pdo = Database::connection();
        return [
            'total' => (int)$pdo->query('SELECT COUNT(*) FROM actividades')->fetchColumn(),
            'hoy' => (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE fecha = CURDATE() AND estado != 'cancelada'")->fetchColumn(),
            'proximas' => (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE fecha >= CURDATE() AND estado != 'cancelada'")->fetchColumn(),
            'mes' => (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())")->fetchColumn(),
        ];
    }

    private static function cast(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'],
            'lugar' => $row['lugar'],
            'fecha' => $row['fecha'],
            'hora_inicio' => substr($row['hora_inicio'], 0, 5),
            'hora_fin' => $row['hora_fin'] ? substr($row['hora_fin'], 0, 5) : null,
            'categoria' => $row['categoria'],
            'destacado' => (bool)$row['destacado'],
            'estado' => $row['estado'],
            'created_at' => $row['created_at'],
        ];
    }
}