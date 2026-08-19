<?php
/** @var string|null $flash_ok */
/** @var string|null $flash_error */
/** @var string|null $flash_deleted */
/** @var string $csrf_token */
/** @var bool $app_debug */

try {
    $items = \App\Models\Actividad::all();
    $dbError = null;
} catch (\Throwable $e) {
    $items = [];
    $dbError = !empty($app_debug)
        ? 'No se pudo cargar la lista: ' . $e->getMessage()
        : 'No se pudo cargar la lista de actividades. Verificá que la BD esté accesible.';
}

$totalCount = is_array($items) ? count($items) : 0;
$hoy = date('Y-m-d');
$hoyCount = 0;
$proximasCount = 0;
if ($items) {
    foreach ($items as $a) {
        if (($a['fecha'] ?? '') === $hoy && ($a['estado'] ?? '') !== 'cancelada') $hoyCount++;
        if (($a['fecha'] ?? '') >= $hoy && ($a['estado'] ?? '') !== 'cancelada') $proximasCount++;
    }
}
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Actividades</h1>
            <p class="muted">Gestioná todas las actividades publicadas</p>
        </div>
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="btn btn-primary">+ Nueva actividad</a>
    </div>

    <?php if (!empty($dbError)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <?php if (!empty($flash_ok)): ?>
        <div class="alert alert-success">Actividad guardada correctamente.</div>
    <?php endif; ?>

    <?php if (!empty($flash_deleted)): ?>
        <div class="alert alert-warning">Actividad eliminada.</div>
    <?php endif; ?>

    <?php if (isset($_GET['push_total'])): ?>
        <?php
        $pushOk = (int)($_GET['push_ok'] ?? 0);
        $pushFail = (int)($_GET['push_fail'] ?? 0);
        $pushTotal = (int)$_GET['push_total'];
        $pushMsg = $pushTotal === 0
            ? 'Actividad guardada. Aún no hay suscriptores push suscriptos.'
            : ('Notificaciones push enviadas: ' . $pushOk . ' ok, ' . $pushFail . ' fallidas (de ' . $pushTotal . ' totales).');
        $pushBg = $pushFail === 0 ? 'success' : 'warning';
        ?>
        <div class="alert alert-<?= $pushBg ?>"><?= htmlspecialchars($pushMsg) ?></div>
    <?php endif; ?>

    <?php if (!$dbError && $totalCount > 0): ?>
        <div class="stats">
            <div class="stat">
                <div class="label">Total</div>
                <div class="num"><?= $totalCount ?></div>
            </div>
            <div class="stat">
                <div class="label">Hoy</div>
                <div class="num"><?= $hoyCount ?></div>
            </div>
            <div class="stat">
                <div class="label">Próximas</div>
                <div class="num"><?= $proximasCount ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card card-padded-0">
        <?php if (!empty($dbError)): ?>
            <div class="empty-state">No se pueden listar actividades debido al error de BD.</div>
        <?php elseif (empty($items)): ?>
            <div class="empty-state">
                <p class="mb-4">Sin actividades cargadas.</p>
                <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="btn btn-primary">+ Crear la primera</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $a): ?>
                            <?php
                            $estadoColor = 'badge-green';
                            if (($a['estado'] ?? '') === 'cancelada') $estadoColor = 'badge-red';
                            elseif (($a['estado'] ?? '') === 'realizada') $estadoColor = 'badge-gray';
                            ?>
                            <tr>
                                <td style="color:#fff;font-weight:600;"><?= htmlspecialchars($a['titulo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($a['fecha'] ?? '') ?></td>
                                <td>
                                    <?= htmlspecialchars(substr($a['hora_inicio'] ?? '', 0, 5)) ?>
                                    <?php if (!empty($a['hora_fin'])): ?>–<?= htmlspecialchars(substr($a['hora_fin'], 0, 5)) ?><?php endif; ?>
                                </td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($a['lugar'] ?? '') ?></td>
                                <td><span class="badge <?= $estadoColor ?>"><?= htmlspecialchars($a['estado'] ?? '') ?></span></td>
                                <td class="actions">
                                    <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/editar/' . $a['id'])) ?>" class="link-gold">Editar</a>
                                    <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/eliminar')) ?>" class="js-delete" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                                        <button type="submit" class="btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>