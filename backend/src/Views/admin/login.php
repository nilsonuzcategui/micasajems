<?php
/** @var string|null $flash_error */
$hasError = !empty($flash_error);
?>
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-[#101829] border border-white/10 rounded-2xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-[#D4AF37]/15 text-[#D4AF37] flex items-center justify-center text-2xl font-bold mb-4">J</div>
            <h1 class="text-2xl font-bold text-white">Panel JEMS</h1>
            <p class="text-sm text-slate-400 mt-1">Acceso para administradores</p>
        </div>

        <?php if ($hasError): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
                <?= $flash_error === '1' ? 'Usuario o contraseña incorrectos' : htmlspecialchars((string)$flash_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(\App\View::adminUrl('login')) ?>" class="space-y-4">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Usuario</label>
                <input name="username" type="text" required autofocus
                       class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#D4AF37]" />
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1">Contraseña</label>
                <input name="password" type="password" required
                       class="w-full bg-[#0c121e] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#D4AF37]" />
            </div>
            <button type="submit" class="w-full bg-[#D4AF37] hover:opacity-90 text-[#101829] font-bold py-3 rounded-xl transition">
                Ingresar
            </button>
        </form>
    </div>
</div>