<?php
/** @var int|null $id */
$isEdit = !empty($id);
$act = $isEdit ? \App\Models\Actividad::find((int)$id) : null;
if ($isEdit && !$act) {
    echo '<div class="max-w-3xl mx-auto px-6 py-10 text-red-400">Actividad no encontrada.</div>';
    return;
}

$v = $act ?? [
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
?>
<div class="max-w-3xl mx-auto px-6 py-10">
    <header class="mb-8">
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="text-sm text-slate-400 hover:text-[#D4AF37]">&larr; Volver al listado</a>
        <h1 class="text-3xl font-bold text-white mt-2"><?= $isEdit ? 'Editar' : 'Nueva' ?> actividad</h1>
    </header>

    <?php if (!empty($flash_error)): ?>
        <div class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <?= $flash_error === 'datos_invalidos' ? 'Revisá los campos obligatorios.' : htmlspecialchars((string)$flash_error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/guardar')) ?>" class="bg-[#101829] border border-white/10 rounded-2xl p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$id ?>" /><?php endif; ?>

        <div>
            <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Título *</label>
            <input name="titulo" required maxlength="150" value="<?= htmlspecialchars($v['titulo']) ?>"
                   class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]" />
        </div>

        <div>
            <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Descripción</label>
            <textarea name="descripcion" rows="4"
                      class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]"><?= htmlspecialchars($v['descripcion'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Lugar *</label>
            <input name="lugar" required maxlength="200" value="<?= htmlspecialchars($v['lugar']) ?>"
                   class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]" />
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Fecha *</label>
                <input type="date" name="fecha" required value="<?= htmlspecialchars($v['fecha']) ?>"
                       class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]" />
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Hora inicio *</label>
                <input type="time" name="hora_inicio" required value="<?= htmlspecialchars($v['hora_inicio']) ?>"
                       class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]" />
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Hora fin</label>
                <input type="time" name="hora_fin" value="<?= htmlspecialchars($v['hora_fin'] ?? '') ?>"
                       class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]" />
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Categoría</label>
                <select name="categoria" class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]">
                    <?php foreach (\App\Models\Actividad::CATEGORIAS as $c): ?>
                        <option value="<?= $c ?>" <?= $v['categoria'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Estado</label>
                <select name="estado" class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#D4AF37]">
                    <?php foreach (\App\Models\Actividad::ESTADOS as $e): ?>
                        <option value="<?= $e ?>" <?= $v['estado'] === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
                    <input type="checkbox" name="destacado" value="1" <?= !empty($v['destacado']) ? 'checked' : '' ?> class="accent-[#D4AF37] w-4 h-4" />
                    Destacar actividad
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="px-5 py-2.5 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5">Cancelar</a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#D4AF37] text-[#101829] font-semibold hover:opacity-90">Guardar</button>
        </div>
    </form>
</div>