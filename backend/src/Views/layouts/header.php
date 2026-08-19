<?php
/** @var string $title */
/** @var string $app_name */
/** @var array|null $current_user */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width" />
    <title><?= htmlspecialchars(($title ?? 'Admin') . ' · ' . $app_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <style>
        :root { color-scheme: dark; }
        body { font-family: system-ui, sans-serif; background: #0a0f1a; }
    </style>
</head>
<body class="text-slate-200 min-h-screen flex flex-col">
<?php if ($current_user): ?>
<nav class="bg-[#101829] border-b border-white/5">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="font-bold text-[#D4AF37] text-lg">
            <?= htmlspecialchars($app_name) ?>
        </a>
        <div class="flex items-center gap-4 text-sm">
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="hover:text-[#D4AF37]">Actividades</a>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="px-3 py-1 rounded bg-[#D4AF37] text-[#101829] font-semibold hover:opacity-90">+ Nueva</a>
            <span class="text-slate-400"><?= htmlspecialchars($current_user['nombre'] ?? $current_user['username']) ?></span>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('logout')) ?>" class="text-slate-400 hover:text-red-400">Salir</a>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="flex-1">