<?php
/** @var string|null $flash_error */
$hasError = !empty($flash_error);
?>
<div class="login-shell">
    <div class="login-card">
        <div class="logo">J</div>
        <h1>Panel JEMS</h1>
        <p class="muted">Acceso para administradores</p>

        <?php if ($hasError): ?>
            <div class="alert alert-error mb-4">
                <?= $flash_error === '1'
                    ? 'Usuario o contraseña incorrectos'
                    : htmlspecialchars((string)$flash_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('login')) ?>">
            <div class="field">
                <label class="label" for="username">Usuario</label>
                <input id="username" name="username" type="text" required autofocus class="input" autocomplete="username" />
            </div>
            <div class="field">
                <label class="label" for="password">Contraseña</label>
                <input id="password" name="password" type="password" required class="input" autocomplete="current-password" />
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Ingresar</button>
        </form>
    </div>
</div>