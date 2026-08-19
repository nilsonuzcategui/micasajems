<?php
/** @var int|null $id */
/** @var array|null $current_user */
/** @var string|null $flash_error */
/** @var string $csrf_token */
/** @var bool $app_debug */

$isEdit = !empty($id);
$act = null;
$dbError = null;
$notFound = false;

if ($isEdit) {
    try {
        $act = \App\Models\Actividad::find((int)$id);
        if (!$act) $notFound = true;
    } catch (\Throwable $e) {
        $dbError = !empty($app_debug)
            ? 'No se pudo cargar la actividad: ' . $e->getMessage()
            : 'No se pudo cargar la actividad. La BD puede no estar inicializada.';
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

if (!empty($_SESSION['form_data']) && is_array($_SESSION['form_data'])) {
    $v = array_merge($v, array_intersect_key($_SESSION['form_data'], $v));
    $v['destacado'] = !empty($v['destacado']);
}
unset($_SESSION['form_data']);

$fieldErrors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['field_errors']);
?>
<div class="container-narrow">
    <div class="page-head">
        <div>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="muted">&larr; Volver al listado</a>
            <h1 class="mt-2"><?= $isEdit ? 'Editar' : 'Nueva' ?> actividad</h1>
        </div>
    </div>

    <?php if ($notFound): ?>
        <div class="alert alert-error">
            <strong>Actividad no encontrada.</strong> Es posible que haya sido eliminada o que el ID sea incorrecto.
            <div class="mt-4">
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
                <strong>Sesión expirada.</strong>
                <a href="<?= htmlspecialchars(\App\View::adminUrl('login')) ?>" style="color:#fcd34d;text-decoration:underline;">Volver a iniciar sesión</a>.
            <?php else: ?>
                <?= htmlspecialchars((string)$flash_error) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$notFound): ?>
    <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/guardar')) ?>" class="card js-validate-actividad">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>" />
        <?php if ($isEdit && !empty($id)): ?><input type="hidden" name="id" value="<?= (int)$id ?>" /><?php endif; ?>

        <div class="field <?= isset($fieldErrors['titulo']) ? 'field-error' : '' ?>">
            <label class="label" for="f-titulo">Título *</label>
            <input id="f-titulo" name="titulo" required maxlength="150"
                   value="<?= htmlspecialchars((string)$v['titulo']) ?>" class="input" />
            <?php if (isset($fieldErrors['titulo'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($fieldErrors['titulo']) ?></div>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="f-descripcion">Descripción</label>
            <textarea id="f-descripcion" name="descripcion" rows="4" class="textarea"><?= htmlspecialchars((string)($v['descripcion'] ?? '')) ?></textarea>
        </div>

        <div class="field <?= isset($fieldErrors['lugar']) ? 'field-error' : '' ?>">
            <label class="label" for="f-lugar">Lugar *</label>
            <input id="f-lugar" name="lugar" required maxlength="200"
                   value="<?= htmlspecialchars((string)$v['lugar']) ?>" class="input" />
            <?php if (isset($fieldErrors['lugar'])): ?>
                <div class="field-error-text"><?= htmlspecialchars($fieldErrors['lugar']) ?></div>
            <?php endif; ?>
        </div>

        <div class="row-3">
            <div class="field <?= isset($fieldErrors['fecha']) ? 'field-error' : '' ?>">
                <label class="label" for="f-fecha">Fecha *</label>
                <input id="f-fecha" type="date" name="fecha" required value="<?= htmlspecialchars((string)$v['fecha']) ?>" class="input" />
                <?php if (isset($fieldErrors['fecha'])): ?>
                    <div class="field-error-text"><?= htmlspecialchars($fieldErrors['fecha']) ?></div>
                <?php endif; ?>
            </div>
            <div class="field <?= isset($fieldErrors['hora_inicio']) ? 'field-error' : '' ?>">
                <label class="label" for="f-hi">Hora inicio *</label>
                <input id="f-hi" type="time" name="hora_inicio" required value="<?= htmlspecialchars((string)$v['hora_inicio']) ?>" class="input" />
                <?php if (isset($fieldErrors['hora_inicio'])): ?>
                    <div class="field-error-text"><?= htmlspecialchars($fieldErrors['hora_inicio']) ?></div>
                <?php endif; ?>
            </div>
            <div class="field">
                <label class="label" for="f-hf">Hora fin</label>
                <input id="f-hf" type="time" name="hora_fin" value="<?= htmlspecialchars((string)($v['hora_fin'] ?? '')) ?>" class="input" />
            </div>
        </div>

        <div class="row-3">
            <div class="field">
                <label class="label" for="f-cat">Categoría</label>
                <select id="f-cat" name="categoria" class="select">
                    <?php foreach (\App\Models\Actividad::CATEGORIAS as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($v['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($c)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="label" for="f-est">Estado</label>
                <select id="f-est" name="estado" class="select">
                    <?php foreach (\App\Models\Actividad::ESTADOS as $e): ?>
                        <option value="<?= htmlspecialchars($e) ?>" <?= ($v['estado'] ?? '') === $e ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($e)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="display:flex;align-items:flex-end;">
                <label class="checkbox-line">
                    <input type="checkbox" name="destacado" value="1" <?= !empty($v['destacado']) ? 'checked' : '' ?> />
                    Destacar actividad
                </label>
            </div>
        </div>

        <div class="flex-between" style="padding-top:16px;border-top:1px solid var(--border);">
            <label class="checkbox-line">
                <input type="checkbox" name="notificar_push" value="1" />
                Notificar a suscriptores push
            </label>
            <div class="row">
                <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Crear actividad' ?></button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>