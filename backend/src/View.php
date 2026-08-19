<?php
declare(strict_types=1);

namespace App;

use App\Middleware\AuthMiddleware;

final class View
{
    public static function adminUrl(string $path = ''): string
    {
        $base = (string)Config::get('ADMIN_URL', '/admin');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Renderiza una vista del panel admin con layout completo.
     *
     * Usa output buffering para que cualquier excepción o `return;` dentro
     * de la vista NO rompa el layout (header/footer siempre se renderizan).
     */
    public static function renderAdmin(string $view, array $data = []): void
    {
        $data['flash_error'] = $_SESSION['flash_error'] ?? ($_GET['error'] ?? null);
        unset($_SESSION['flash_error']);
        $data['flash_ok'] = $_SESSION['flash_ok'] ?? ($_GET['ok'] ?? null);
        unset($_SESSION['flash_ok']);
        $data['flash_deleted'] = $_SESSION['flash_deleted'] ?? ($_GET['deleted'] ?? null);
        unset($_SESSION['flash_deleted']);
        $data['current_user'] = AuthMiddleware::user();
        $data['csrf_token'] = Request::csrfToken();
        $data['app_name'] = 'JEMS Admin';
        $data['app_debug'] = Config::getBool('APP_DEBUG', false);

        if ($view === 'redirect') {
            if (AuthMiddleware::user()) {
                Response::redirect(self::adminUrl('actividades'));
            }
            Response::redirect(self::adminUrl('login'));
        }

        $file = APP_SRC . '/Views/admin/' . $view . '.php';
        if (!is_file($file)) {
            // Limpiamos cualquier buffer y mostramos error dentro del layout
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            self::renderErrorPage('Vista no encontrada: ' . $view);
            return;
        }

        $data['view_file'] = $file;
        $data['title'] = $data['title'] ?? ucfirst(str_replace('_', ' ', $view));

        extract($data, EXTR_SKIP);

        // Capturamos el contenido de la vista en un buffer
        $viewContent = '';
        $renderError = null;
        try {
            ob_start();
            require $file;
            $viewContent = (string)ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $renderError = $e;
        }

        if ($renderError !== null) {
            $debug = Config::getBool('APP_DEBUG', false);
            $msg = $debug
                ? sprintf(
                    'Error en vista "%s": %s en %s:%d',
                    $view,
                    $renderError->getMessage(),
                    basename($renderError->getFile()),
                    $renderError->getLine()
                )
                : 'Ocurrió un error al renderizar esta vista. Activá APP_DEBUG=true en .env para ver el detalle.';

            self::renderErrorPage($msg, $debug ? $renderError->getTraceAsString() : null);
            return;
        }

        // Layout completo: header + view + footer
        require APP_SRC . '/Views/layouts/header.php';
        echo $viewContent;
        require APP_SRC . '/Views/layouts/footer.php';
        exit;
    }

    /**
     * Renderiza una página de error standalone (cuando algo explotó).
     * Siempre devuelve HTML para que sea legible en el browser.
     */
    private static function renderErrorPage(string $message, ?string $trace = null): void
    {
        $appName = 'JEMS Admin';
        $title = 'Error';
        $isDebug = Config::getBool('APP_DEBUG', false);
        require APP_SRC . '/Views/layouts/header.php';
        ?>
        <div class="max-w-3xl mx-auto px-6 py-10">
            <h1 class="text-2xl font-bold text-red-400 mb-3">⚠ Error</h1>
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-200 text-sm whitespace-pre-wrap">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php if ($isDebug && $trace): ?>
                <h2 class="text-lg font-bold text-slate-300 mt-6 mb-2">Stack trace</h2>
                <pre class="bg-black/40 border border-white/10 rounded-xl p-4 text-xs text-slate-300 overflow-auto"><?= htmlspecialchars($trace) ?></pre>
            <?php endif; ?>
            <a href="<?= htmlspecialchars(self::adminUrl('actividades')) ?>" class="inline-block mt-6 px-5 py-2.5 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5">← Volver a Actividades</a>
        </div>
        <?php
        require APP_SRC . '/Views/layouts/footer.php';
        exit;
    }
}