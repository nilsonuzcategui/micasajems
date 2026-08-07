<?php
$items = \App\Models\Actividad::all();
?>
<div class="max-w-6xl mx-auto px-6 py-10">
    <header class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">Actividades</h1>
            <p class="text-slate-400 text-sm mt-1">Gestioná todas las actividades publicadas</p>
        </div>
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="px-5 py-2.5 rounded-xl bg-[#D4AF37] text-[#101829] font-semibold hover:opacity-90">+ Nueva</a>
    </header>

    <?php if (!empty($flash_ok)): ?>
        <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">Actividad guardada correctamente.</div>
    <?php endif; ?>
    <?php if (!empty($flash_deleted)): ?>
        <div class="mb-4 px-4 py-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">Actividad eliminada.</div>
    <?php endif; ?>

    <div class="bg-[#101829] border border-white/10 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#0c121e] text-slate-400 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Título</th>
                    <th class="text-left px-4 py-3">Fecha</th>
                    <th class="text-left px-4 py-3">Hora</th>
                    <th class="text-left px-4 py-3">Lugar</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="text-right px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Sin actividades cargadas.</td></tr>
                <?php else: foreach ($items as $a): ?>
                    <tr class="hover:bg-white/5">
                        <td class="px-4 py-3 text-white font-medium"><?= htmlspecialchars($a['titulo']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($a['fecha']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($a['hora_inicio']) ?><?= $a['hora_fin'] ? '–' . htmlspecialchars($a['hora_fin']) : '' ?></td>
                        <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($a['lugar']) ?></td>
                        <td class="px-4 py-3">
                            <?php
                            switch ($a['estado']) {
                                case 'cancelada':
                                    $estadoColor = 'bg-red-500/15 text-red-300';
                                    break;
                                case 'realizada':
                                    $estadoColor = 'bg-slate-500/15 text-slate-300';
                                    break;
                                default:
                                    $estadoColor = 'bg-emerald-500/15 text-emerald-300';
                            }
                            ?>
                            <span class="px-2 py-1 rounded-md text-xs <?= $estadoColor ?>"><?= htmlspecialchars($a['estado']) ?></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/editar/' . $a['id'])) ?>" class="text-[#D4AF37] hover:underline mr-3">Editar</a>
                            <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('actividades/eliminar')) ?>" class="inline" onsubmit="return confirm('¿Eliminar esta actividad?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                                <button type="submit" class="text-red-400 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>