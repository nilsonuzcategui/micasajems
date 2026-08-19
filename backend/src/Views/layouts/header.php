<?php
/** @var string $title */
/** @var string $app_name */
/** @var array|null $current_user */
/** @var bool $app_debug */
?><!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars(($title ?? 'Admin') . ' · ' . $app_name) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />

    <!-- Tailwind CDN: mejora el estilo, pero NO es necesario para que la página se vea -->
    <script src="https://cdn.tailwindcss.com" defer></script>

    <!-- CSS crítico inline: garantiza que la página se vea SIEMPRE, aunque el CDN tarde o esté bloqueado -->
    <style>
        :root { color-scheme: dark; }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #0a0f1a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 15px;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        a:hover { text-decoration: underline; }
        h1, h2, h3, h4 { margin: 0; }
        nav.jems-nav {
            background: #101829;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 14px 24px;
        }
        nav.jems-nav .inner {
            max-width: 1152px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        nav.jems-nav .brand {
            font-weight: 700;
            color: #D4AF37;
            font-size: 18px;
        }
        nav.jems-nav .right {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
        }
        nav.jems-nav .right a {
            color: #cbd5e1;
        }
        nav.jems-nav .right a:hover { color: #D4AF37; text-decoration: none; }
        nav.jems-nav .btn-new {
            background: #D4AF37;
            color: #101829;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }
        nav.jems-nav .btn-new:hover { background: #c9a233; text-decoration: none; }
        nav.jems-nav .user {
            color: #94a3b8;
        }
        main.jems-main {
            flex: 1;
            width: 100%;
        }
        footer.jems-footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .debug-banner {
            background: #7c2d12;
            color: #fff;
            padding: 6px 16px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }
        .container-page {
            max-width: 1152px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        .container-narrow {
            max-width: 768px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
        }
        .btn-primary {
            background: #D4AF37;
            color: #101829;
        }
        .btn-primary:hover { background: #c9a233; text-decoration: none; }
        .btn-secondary {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.05); text-decoration: none; }
        .btn-danger {
            background: transparent;
            border: none;
            color: #f87171;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
        }
        .btn-danger:hover { color: #fca5a5; text-decoration: underline; }
        .card {
            background: #101829;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .alert-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16,185,129,0.08);
            border: 1px solid rgba(16,185,129,0.2);
            color: #6ee7b7;
        }
        .alert-warning {
            background: rgba(245,158,11,0.08);
            border: 1px solid rgba(245,158,11,0.2);
            color: #fcd34d;
        }
        .alert-info {
            background: rgba(59,130,246,0.08);
            border: 1px solid rgba(59,130,246,0.2);
            color: #93c5fd;
        }
        .label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .field { margin-bottom: 16px; }
        .input, .textarea, .select {
            width: 100%;
            background: #0c121e;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 10px 14px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
        }
        .input:focus, .textarea:focus, .select:focus {
            outline: none;
            border-color: #D4AF37;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        @media (max-width: 640px) {
            .grid-3, .grid-2 { grid-template-columns: 1fr; }
        }
        table.jems-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table.jems-table th {
            background: #0c121e;
            color: #94a3b8;
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        table.jems-table td {
            padding: 12px 16px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        table.jems-table tr:hover td { background: rgba(255,255,255,0.03); }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-green { background: rgba(16,185,129,0.15); color: #6ee7b7; }
        .badge-red { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .badge-gray { background: rgba(100,116,139,0.15); color: #cbd5e1; }
        .h1 { font-size: 28px; font-weight: 700; color: #fff; }
        .h2 { font-size: 20px; font-weight: 700; color: #fff; }
        .muted { color: #94a3b8; font-size: 13px; }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .field-error {
            border-color: #ef4444 !important;
        }
        .field-error-text {
            color: #fca5a5;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<?php if (!empty($app_debug)): ?>
    <div class="debug-banner">⚠ APP_DEBUG=true — los errores muestran detalle completo. NO dejar activo en producción.</div>
<?php endif; ?>

<?php if (!empty($current_user)): ?>
<nav class="jems-nav">
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

<main class="jems-main">