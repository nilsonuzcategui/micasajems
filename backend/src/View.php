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
     * Renderiza una vista con layout completo (header + vista + footer).
     * Captura la salida en buffer para que cualquier excepción dentro
     * de la vista NO rompa el layout.
     */
    public static function renderAdmin(string $view, array $data = []): void
    {
        $data['flash_error']    = $_SESSION['flash_error']    ?? ($_GET['error']   ?? null);
        unset($_SESSION['flash_error']);
        $data['flash_ok']       = $_SESSION['flash_ok']       ?? ($_GET['ok']      ?? null);
        unset($_SESSION['flash_ok']);
        $data['flash_deleted']  = $_SESSION['flash_deleted']  ?? ($_GET['deleted'] ?? null);
        unset($_SESSION['flash_deleted']);
        $data['current_user']   = AuthMiddleware::user();
        $data['csrf_token']     = Request::csrfToken();
        $data['app_name']       = 'JEMS Admin';
        $data['app_debug']      = Config::getBool('APP_DEBUG', false);

        if ($view === 'redirect') {
            if (AuthMiddleware::user()) {
                Response::redirect(self::adminUrl('actividades'));
            }
            Response::redirect(self::adminUrl('login'));
        }

        $file = APP_SRC . '/Views/admin/' . $view . '.php';
        if (!is_file($file)) {
            self::renderErrorPage('Vista no encontrada: ' . htmlspecialchars($view));
            return;
        }

        $data['view_file'] = $file;
        $data['title'] = $data['title'] ?? ucfirst(str_replace('_', ' ', $view));

        // Capturamos la vista en buffer
        $viewContent = '';
        $renderError = null;
        try {
            ob_start();
            extract($data, EXTR_SKIP);
            require $file;
            $viewContent = (string)ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) ob_end_clean();
            $renderError = $e;
        }

        if ($renderError !== null) {
            self::renderErrorPage(
                'Error al renderizar la vista',
                $renderError->getMessage(),
                basename($renderError->getFile()) . ':' . $renderError->getLine(),
                $data['app_debug'] ? $renderError->getTraceAsString() : null
            );
            return;
        }

        // Layout completo
        require APP_SRC . '/Views/layouts/header.php';
        echo $viewContent;
        require APP_SRC . '/Views/layouts/footer.php';
        exit;
    }

    private static function renderErrorPage(string $title, string $message = '', string $where = '', ?string $trace = null): void
    {
        $appName = 'JEMS Admin';
        $isDebug = Config::getBool('APP_DEBUG', false);
        $titlePage = 'Error';
        $backUrl = self::adminUrl('actividades');
        require APP_SRC . '/Views/layouts/header.php';
        ?>
        <div class="container-narrow">
            <div class="alert alert-error" style="margin-top:24px;">
                <strong><?= htmlspecialchars($title) ?></strong><br />
                <?= htmlspecialchars($message) ?>
                <?php if ($where): ?>
                    <br /><small style="opacity:0.7;">en <?= htmlspecialchars($where) ?></small>
                <?php endif; ?>
            </div>
            <?php if ($isDebug && $trace): ?>
                <h3 class="mt-6">Stack trace</h3>
                <pre class="card" style="white-space:pre-wrap;font-size:12px;overflow:auto;"><?= htmlspecialchars($trace) ?></pre>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary mt-4">← Volver a Actividades</a>
        </div>
        <?php
        require APP_SRC . '/Views/layouts/footer.php';
        exit;
    }
}