<?php
/** @var string $title */
/** @var string $app_name */
/** @var array|null $current_user */
/** @var string $csrf_token */
/** @var bool $app_debug */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars(($title ?? 'Admin') . ' · ' . $app_name) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="stylesheet" href="/admin.css?v=2" />
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token ?? '') ?>" />
    <script src="/jquery.min.js" defer></script>
    <script src="/admin.js?v=2" defer></script>
</head>
<body class="layout">
<?php if (!empty($app_debug)): ?>
    <div class="debug-banner">⚠ APP_DEBUG=true — los errores muestran detalle completo. NO dejar activo en producción.</div>
<?php endif; ?>

<?php if (!empty($current_user)): ?>
<nav class="navbar">
    <div class="inner">
        <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>" class="brand"><?= htmlspecialchars($app_name) ?></a>
        <div class="right">
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades')) ?>">Actividades</a>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('actividades/nueva')) ?>" class="btn-new">+ Nueva</a>
            <span class="user"><?= htmlspecialchars($current_user['nombre'] ?? $current_user['username'] ?? '') ?></span>
            <a href="<?= htmlspecialchars(\App\View::adminUrl('logout')) ?>">Salir</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="main">