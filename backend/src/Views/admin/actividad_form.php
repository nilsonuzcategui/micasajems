<?php
/**
 * @var int|null  $id
 * @var array|null $current_user
 * @var string|null $flash_error
 * @var string $csrf_token
 * @var bool $app_debug
 */

$isEdit = !empty($id);
$act = null;
$dbError = null;
$notFound = false;

if ($isEdit) {
    try {
        $act = \App\Models\Actividad::find((int)$id);
        if (!$act) {
            $notFound = true;
        }
    } catch (\Throwable $e) {
        $dbError = 'No se pudo cargar la actividad. La BD puede no estar inicializada.';
        if ($app_debug) {
            $dbError .= ' Detalle: ' . $e->getMessage();
        }
    }
}

$v = ($act && is_array($act)) ? $act : [
    'titulo' => '',
    'descripcion' => '',
    'lugar' => '',
    'fecha' => date('Y-m-d'),
    'hora_inicio' => '19:00',
    'hora_fin' => '',
    'categoria' => 'culto',
    'destacado' => false,
    'estado' => 'programada',
];

// Si hubo error de validación, sobrescribir con lo que el usuario tipeó
if (!empty($_SESSION['form_data']) && is_array($_SESSION['form_data'])) {
    $v = array_merge($v, array_intersect_key($_SESSION['form_data'], $v));
    // Normalizar booleano destacado
    $v['destacado'] = !empty($v['destacado']);
}
unset($_SESSION['form_data']);

// Errores de validación por campo (los completa el handler de guardar)
$fieldErrors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);
?>
<div class="container-narrow">
    <header class="header-row">
        <div>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="muted" style="display:inline-block;margin-bottom:8px;">&larr; Volver al listado</a>
            <h1 class="h1"><?= $isEdit ? 'Editar' : 'Nueva' ?> actividad</h1>
        </div>
    </header>

    <?php if ($notFound): ?>
        <div class="alert alert-error">
            <strong>Actividad no encontrada.</strong> Es posible que haya sido eliminada o que el ID sea incorrecto.
            <div style="margin-top:12px;">
                <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="btn btn-secondary">← Volver al listado</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($dbError)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-error">
            <?php if ($flash_error === 'datos_invalidos'): ?>
                <strong>Revisá los campos obligatorios marcados en rojo.</strong>
            <?php elseif ($flash_error === 'csrf_invalido'): ?>
                <strong>Tu sesión expiró.</strong> Recargá la página y volvé a intentar.
            <?php elseif ($flash_error === 'no_autenticado'): ?>
                <strong>Sesión expirada.</strong> <a href="<?= htmlspecialchars(\App\View::adminUrl('login')) ?>" style="color:#fcd34d;text-decoration:underline;">Volver a iniciar sesión</a>.
            <?php else: ?>
                <?= htmlspecialchars((string)$flash_error) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$notFound): ?>
    <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/guardar')) ?>" class="card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>" />
        <?php if ($isEdit && !empty($id)): ?><input type="hidden" name="id" value="<?= (int)$id ?>" /><?php endif; ?>

        <div class="field">
            <label class="label" for="f-titulo">Título *</label>
            <input id="f-titulo" name="titulo" required maxlength="150"
                   value="<?= htmlspecialchars((string)$v['titulo']) ?>"
                   class="input <?= isset($fieldErrors['titulo']) ? 'field-error' : '' ?>" />
            <?php if (isset($fieldErrors['titulo'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($fieldErrors['titulo']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="f-descripcion">Descripción</label>
            <textarea id="f-descripcion" name="descripcion" rows="4"
                      class="textarea <?= isset($fieldErrors['descripcion']) ? 'field-error' : '' ?>"><?= htmlspecialchars((string)($v['descripcion'] ?? '')) ?></textarea>
            <?php if (isset($fieldErrors['descripcion'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($fieldErrors['descripcion']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="f-lugar">Lugar *</label>
            <input id="f-lugar" name="lugar" required maxlength="200"
                   value="<?= htmlspecialchars((string)$v['lugar']) ?>"
                   class="input <?= isset($fieldErrors['lugar']) ? 'field-error' : '' ?>" />
            <?php if (isset($fieldErrors['lugar'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($fieldErrors['lugar']) ?></div>
            <?php endif; ?>
        </div>

        <div class="grid-3">
            <div class="field">
                <label class="label" for="f-fecha">Fecha *</label>
                <input id="f-fecha" type="date" name="fecha" required
                       value="<?= htmlspecialchars((string)$v['fecha']) ?>"
                       class="input <?= isset($fieldErrors['fecha']) ? 'field-error' : '' ?>" />
                <?php if (isset($fieldErrors['fecha'])): ?>
                    <div class="field-error-text"><?= htmlspecialchars($fieldErrors['fecha']) ?></div>
                <?php endif; ?>
            </div>
            <div class="field">
                <label class="label" for="f-hi">Hora inicio *</label>
                <input id="f-hi" type="time" name="hora_inicio" required
                       value="<?= htmlspecialchars((string)$v['hora_inicio']) ?>"
                       class="input <?= isset($fieldErrors['hora_inicio']) ? 'field-error' : '' ?>" />
                <?php if (isset($fieldErrors['hora_inicio'])): ?>
                    <div class="field-error-text"><?= htmlspecialchars($fieldErrors['hora_inicio']) ?></div>
                <?php endif; ?>
            </div>
            <div class="field">
                <label class="label" for="f-hf">Hora fin</label>
                <input id="f-hf" type="time" name="hora_fin"
                       value="<?= htmlspecialchars((string)($v['hora_fin'] ?? '')) ?>"
                       class="input <?= isset($fieldErrors['hora_fin']) ? 'field-error' : '' ?>" />
                <?php if (isset($fieldErrors['hora_fin'])): ?>
                    <div class="field-error-text"><?= htmlspecialchars($fieldErrors['hora_fin']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-3">
            <div class="field">
                <label class="label" for="f-categoria">Categoría</label>
                <select id="f-categoria" name="categoria" class="select">
                    <?php foreach (\App\Models\Actividad::CATEGORIAS as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($v['categoria'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="label" for="f-estado">Estado</label>
                <select id="f-estado" name="estado" class="select">
                    <?php foreach (\App\Models\Actividad::ESTADOS as $e): ?>
                        <option value="<?= htmlspecialchars($e) ?>" <?= ($v['estado'] ?? '') === $e ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($e)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="display:flex;align-items:flex-end;">
                <label style="display:flex;align-items:center;gap:8px;color:#cbd5e1;font-size:14px;cursor:pointer;">
                    <input type="checkbox" name="destacado" value="1" <?= !empty($v['destacado']) ? 'checked' : '' ?> style="accent-color:#D4AF37;width:16px;height:16px;" />
                    Destacar actividad
                </label>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.05);flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:8px;color:#cbd5e1;font-size:14px;cursor:pointer;user-select:none;">
                <input type="checkbox" name="notificar_push" value="1" style="accent-color:#D4AF37;width:16px;height:16px;" />
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#D4AF37" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                Notificar a suscriptores push
            </label>
            <div style="display:flex;gap:12px;">
                <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Crear actividad' ?></button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>