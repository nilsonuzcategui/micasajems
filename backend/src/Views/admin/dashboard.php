<?php
/** @var array $stats */
/** @var array $upcoming */
try {
    $stats = \App\Models\Actividad::stats();
} catch (\Throwable $e) {
    $stats = ['total' => 0, 'hoy' => 0, 'proximas' => 0, 'mes' => 0];
    $dbError = 'No se pudo conectar a la base de datos. Corré las migraciones: php bin/migrate.php';
}

try {
    $upcoming = \App\Models\Actividad::all(['desde' => date('Y-m-d')]);
    $upcoming = array_slice($upcoming, 0, 5);
} catch (\Throwable $e) {
    $upcoming = [];
}
?>
<div class="max-w-6xl mx-auto px-6 py-10">
    <header class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">Dashboard</h1>
            <p class="text-slate-400 text-sm mt-1">Resumen del módulo de actividades</p>
        </div>
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="px-5 py-2.5 rounded-xl bg-[#D4AF37] text-[#101829] font-semibold hover:opacity-90">
            + Nueva actividad
        </a>
    </header>

    <?php if (!empty($dbError)): ?>
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <strong>Error de BD:</strong> <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <?php
        $cards = [
            ['label' => 'Total', 'value' => $stats['total']],
            ['label' => 'Hoy', 'value' => $stats['hoy']],
            ['label' => 'Próximas', 'value' => $stats['proximas']],
            ['label' => 'Este mes', 'value' => $stats['mes']],
        ];
        foreach ($cards as $c): ?>
            <div class="bg-[#101829] border border-white/10 rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-slate-400"><?= htmlspecialchars($c['label']) ?></p>
                <p class="text-3xl font-bold text-[#D4AF37] mt-2"><?= (int)$c['value'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="bg-[#101829] border border-white/10 rounded-2xl p-6">
        <h2 class="text-lg font-bold text-white mb-4">Próximas actividades</h2>
        <?php if (empty($upcoming)): ?>
            <p class="text-slate-400 text-sm">
                <?php if (empty($dbError)): ?>
                    Aún no hay actividades programadas. <a class="text-[#D4AF37] underline" href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>">Crear la primera</a>.
                <?php else: ?>
                    Una vez corregido el error de BD, las actividades aparecerán aquí.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <ul class="divide-y divide-white/5">
                <?php foreach ($upcoming as $a): ?>
                    <li class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-white font-semibold"><?= htmlspecialchars($a['titulo']) ?></p>
                            <p class="text-xs text-slate-400">
                                <?= htmlspecialchars($a['fecha']) ?> &middot; <?= htmlspecialchars($a['hora_inicio']) ?>
                                <?= $a['hora_fin'] ? ' – ' . htmlspecialchars($a['hora_fin']) : '' ?>
                                &middot; <?= htmlspecialchars($a['lugar']) ?>
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/editar/' . $a['id'])) ?>" class="text-sm text-[#D4AF37] hover:underline">Editar</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>