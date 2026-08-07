<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Actividad;
use App\Request;
use App\Response;

final class ActividadController
{
    public function index(): void
    {
        $filters = [
            'estado' => Request::query('estado'),
            'desde' => Request::query('desde'),
            'hasta' => Request::query('hasta'),
            'categoria' => Request::query('categoria'),
        ];
        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        if (Request::query('semana') !== null) {
            $data = Actividad::week((string)Request::query('semana'));
            Response::ok(['semana' => Request::query('semana'), 'items' => $data]);
            return;
        }

        $data = Actividad::all($filters);
        Response::ok($data);
    }

    public function show(int $id): void
    {
        $act = Actividad::find($id);
        if (!$act) {
            Response::error('Actividad no encontrada', 404);
        }
        Response::ok($act);
    }

    public function store(): void
    {
        $data = $this->validate();
        $userId = (int)($_SESSION['admin_user_id'] ?? 0) ?: null;
        $id = Actividad::create($data, $userId);
        Response::ok(['id' => $id, 'message' => 'Actividad creada']);
    }

    public function update(int $id): void
    {
        if (!Actividad::find($id)) {
            Response::error('Actividad no encontrada', 404);
        }
        $data = $this->validate();
        Actividad::update($id, $data);
        Response::ok(['id' => $id, 'message' => 'Actividad actualizada']);
    }

    public function destroy(int $id): void
    {
        if (!Actividad::find($id)) {
            Response::error('Actividad no encontrada', 404);
        }
        Actividad::delete($id);
        Response::ok(['message' => 'Actividad eliminada']);
    }

    private function validate(): array
    {
        $all = Request::all();
        $errors = [];

        $titulo = trim((string)($all['titulo'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $errors['titulo'] = 'Título obligatorio (máx 150 caracteres)';
        }

        $lugar = trim((string)($all['lugar'] ?? ''));
        if ($lugar === '' || mb_strlen($lugar) > 200) {
            $errors['lugar'] = 'Lugar obligatorio (máx 200 caracteres)';
        }

        $fecha = trim((string)($all['fecha'] ?? ''));
        if (!$this->isValidDate($fecha)) {
            $errors['fecha'] = 'Fecha inválida (YYYY-MM-DD)';
        }

        $horaInicio = trim((string)($all['hora_inicio'] ?? ''));
        if (!$this->isValidTime($horaInicio)) {
            $errors['hora_inicio'] = 'Hora de inicio inválida (HH:MM)';
        }

        $horaFin = $all['hora_fin'] ?? null;
        if ($horaFin !== null && $horaFin !== '' && !$this->isValidTime((string)$horaFin)) {
            $errors['hora_fin'] = 'Hora de fin inválida (HH:MM)';
        }

        $categoria = $all['categoria'] ?? 'culto';
        if (!in_array($categoria, Actividad::CATEGORIAS, true)) {
            $errors['categoria'] = 'Categoría inválida';
        }

        $estado = $all['estado'] ?? 'programada';
        if (!in_array($estado, Actividad::ESTADOS, true)) {
            $errors['estado'] = 'Estado inválido';
        }

        if (!empty($errors)) {
            Response::error('Datos inválidos', 422, ['fields' => $errors]);
        }

        return [
            'titulo' => $titulo,
            'descripcion' => isset($all['descripcion']) ? trim((string)$all['descripcion']) : null,
            'lugar' => $lugar,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => ($horaFin !== null && $horaFin !== '') ? $horaFin : null,
            'categoria' => $categoria,
            'destacado' => !empty($all['destacado']),
            'estado' => $estado,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function isValidTime(string $time): bool
    {
        $t = \DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
}