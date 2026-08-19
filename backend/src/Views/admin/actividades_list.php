<?php
/** @var array|null $current_user */
/** @var string|null $flash_error */
/** @var string|null $flash_ok */
/** @var string|null $flash_deleted */
/** @var string $csrf_token */
/** @var bool $app_debug */

try {
    $items = \App\Models\Actividad::all();
    $dbError = null;
} catch (\Throwable $e) {
    $items = [];
    $dbError = $app_debug
        ? 'No se pudo cargar la lista de actividades: ' . $e->getMessage()
        : 'No se pudo cargar la lista de actividades. Verificá que la BD esté accesible y que hayas corrido las migraciones.';
}

// Estadísticas rápidas
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
<div class="container-page">
    <header class="header-row">
        <div>
            <h1 class="h1">Actividades</h1>
            <p class="muted" style="margin-top:4px;">Gestioná todas las actividades publicadas</p>
        </div>
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="btn btn-primary">+ Nueva actividad</a>
    </header>

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
        $pushBg = $pushFail === 0 ? 'success' : 'warning';
        $pushMsg = $pushTotal === 0
            ? 'Actividad guardada. Aún no hay suscriptores push suscriptos.'
            : ('Notificaciones push enviadas: ' . $pushOk . ' ok, ' . $pushFail . ' fallidas (de ' . $pushTotal . ' totales).');
        ?>
        <div class="alert alert-<?= $pushBg ?>"><?= htmlspecialchars($pushMsg) ?></div>
    <?php endif; ?>

    <?php if (!$dbError && $totalCount > 0): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;">
            <div class="card" style="padding:16px;">
                <div class="label">Total</div>
                <div style="font-size:24px;font-weight:700;color:#D4AF37;"><?= $totalCount ?></div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label">Hoy</div>
                <div style="font-size:24px;font-weight:700;color:#D4AF37;"><?= $hoyCount ?></div>
            </div>
            <div class="card" style="padding:16px;">
                <div class="label">Próximas</div>
                <div style="font-size:24px;font-weight:700;color:#D4AF37;"><?= $proximasCount ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:0;overflow:hidden;">
        <?php if (!empty($dbError)): ?>
            <div style="padding:32px;text-align:center;color:#94a3b8;">No se pueden listar actividades debido al error de BD.</div>
        <?php elseif (empty($items)): ?>
            <div style="padding:48px 32px;text-align:center;color:#94a3b8;">
                <p style="margin-bottom:12px;">Sin actividades cargadas.</p>
                <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="btn btn-primary">+ Crear la primera</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="jems-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $a): ?>
                            <?php
                            $estadoColor = 'badge-green';
                            if (($a['estado'] ?? '') === 'cancelada') {
                                $estadoColor = 'badge-red';
                            } elseif (($a['estado'] ?? '') === 'realizada') {
                                $estadoColor = 'badge-gray';
                            }
                            ?>
                            <tr>
                                <td style="color:#fff;font-weight:600;"><?= htmlspecialchars($a['titulo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($a['fecha'] ?? '') ?></td>
                                <td><?= htmlspecialchars(substr($a['hora_inicio'] ?? '', 0, 5)) ?><?= !empty($a['hora_fin']) ? '–' . htmlspecialchars(substr($a['hora_fin'], 0, 5)) : '' ?></td>
                                <td style="color:#94a3b8;"><?= htmlspecialchars($a['lugar'] ?? '') ?></td>
                                <td><span class="badge <?= $estadoColor ?>"><?= htmlspecialchars($a['estado'] ?? '') ?></span></td>
                                <td style="text-align:right;">
                                    <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/editar/' . $a['id'])) ?>" style="color:#D4AF37;margin-right:12px;">Editar</a>
                                    <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/eliminar')) ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar esta actividad?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
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