# Módulo de Actividades — JEMS

Backend PHP + componente Astro para gestionar actividades, calendario semanal
y suscripciones push vía SendPulse.

---

## 🏗️ Arquitectura

```
                         Usuario
                            │
                ┌───────────┴───────────┐
                ▼                       ▼
       micasajems.com             admin.micasajems.com
       (Astro estático)           (Backend PHP)
                │                       │
                │  fetch (CORS)         │
                └───────────────────────┘
                                │
                                ▼
                          MySQL (Hestia)
```

- **Frontend Astro** → `micasajems.com/public_html/`
- **Backend PHP** → `admin.micasajems.com/public_html/` (subdominio dedicado)
- **BD** → MySQL en el mismo VPS

---

## 📂 Estructura

```
micasajems/
├── backend/                         # PHP API + panel admin
│   ├── .env                         # (NO subir) credenciales reales
│   ├── .env.example                 # plantilla
│   ├── .htaccess                    # bloqueo + rewrite al front controller
│   ├── index.php                    # front controller (entry point)
│   ├── src/
│   │   ├── Bootstrap.php
│   │   ├── Config.php               # lector .env
│   │   ├── Database.php             # PDO singleton
│   │   ├── Request.php / Response.php
│   │   ├── Router.php
│   │   ├── Routes.php               # tabla de rutas
│   │   ├── View.php                 # helper para vistas admin
│   │   ├── polyfill.php             # compat PHP 7.4
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Middleware/
│   │   └── Views/
│   └── database/migrations/         # SQL de tablas y seed
├── src/
│   ├── components/
│   │   ├── Actividades.astro        # componente principal
│   │   ├── CalendarioActividades.astro
│   │   ├── ResumenSemanal.astro
│   │   └── BotonNotificaciones.astro
│   └── lib/api.ts                   # cliente TS hacia el backend
└── .github/workflows/deploy.yml     # CI/CD (2 deploys FTP)
```

> **Nota**: desde esta versión, `backend/index.php` está en la raíz de `backend/`
> (no más `backend/public/`). Esto facilita el deploy FTP directo a `public_html/`.
> El `.htaccess` en la raíz bloquea el acceso a `.env`, `src/`, `database/`, `storage/`.

---

## 🚀 Setup local (desarrollo con XAMPP)

### 1. Base de datos

```bash
mysql -u root -p micasajems < backend/database/migrations/001_actividades.sql
mysql -u root -p micasajems < backend/database/migrations/002_admin_users.sql
mysql -u root -p micasajems < backend/database/migrations/003_suscripciones.sql

# Opcional (datos de ejemplo):
mysql -u root -p micasajems < backend/database/migrations/004_seed_ejemplo.sql
```

### 2. Configurar credenciales

```bash
cd backend
cp .env.example .env
```

Editar `backend/.env` con tus valores de BD local.

Para Astro:

```bash
cp .env.example .env
```

Por defecto apunta a `http://localhost/micasajems/backend`.

### 3. Cambiar la contraseña del admin

Por defecto: `admin` / `Jems2026!` (cambiar inmediatamente).

```bash
php -r "echo password_hash('TuNuevaClaveSegura', PASSWORD_DEFAULT);"
```

```sql
UPDATE admin_users SET password_hash = '<hash>' WHERE username = 'admin';
```

### 4. Probar backend

Con XAMPP corriendo:

```
http://localhost/micasajems/backend/api/actividades
http://localhost/micasajems/backend/admin/login
```

### 5. Probar frontend

```bash
npm install
npm run dev
```

Astro levanta en `http://localhost:4321/`. El proxy reenvía:
- `/api/*` → `http://localhost/micasajems/backend/api/*`
- `/admin/*` → `http://localhost/micasajems/backend/admin/*`

---

## 🚀 Despliegue en producción

### Estructura en el VPS (Hestia)

```
/home/<user>/web/
├── micasajems.com/
│   └── public_html/                ← Astro build (FTP deploy 1)
└── admin.micasajems.com/
    └── public_html/                ← Backend PHP (FTP deploy 2)
        ├── index.php
        ├── .htaccess
        ├── .env                    ← generado por Actions
        ├── src/
        ├── database/
        └── storage/
```

### Setup inicial (una sola vez)

1. **En Hestia CPanel**:
   - Crear el dominio `micasajems.com` (ya debe existir)
   - Crear el subdominio `admin.micasajems.com`
   - Crear un usuario FTP específico para `admin.micasajems.com`
   - Apuntar DNS de `admin.micasajems.com` al VPS
   - Activar HTTPS (Let's Encrypt) en ambos

2. **Subir el backend manualmente la primera vez**:
   - Conectar por SFTP con el usuario de `admin.micasajems.com`
   - Subir todo el contenido de `backend/` excepto `.env`
   - Crear manualmente `backend/.env` con los valores de producción
   - `chmod 640 .env`

3. **Correr las migraciones SQL**:
   ```bash
   cd /home/<user>/web/admin.micasajems.com/public_html
   mysql -u<db_user> -p<db_pass> micasajems < database/migrations/001_actividades.sql
   mysql -u<db_user> -p<db_pass> micasajems < database/migrations/002_admin_users.sql
   mysql -u<db_user> -p<db_pass> micasajems < database/migrations/003_suscripciones.sql
   ```

4. **Cambiar la clave del admin** (insertar nuevo hash en BD)

5. **Limpiar WordPress** si quedó algo en `public_html/` de `micasajems.com`

### Configurar GitHub Actions

Ir a **GitHub → Settings → Secrets and variables → Actions** y agregar:

| Secret | Descripción |
|---|---|
| `SSH_HOST` | Host FTP del frontend (micasajems.com) |
| `SSH_USER` | Usuario FTP del frontend |
| `SSH_PASSWORD` | Password FTP del frontend |
| `ADMIN_SSH_HOST` | Host FTP del admin (admin.micasajems.com) |
| `ADMIN_SSH_USER` | Usuario FTP del admin |
| `ADMIN_SSH_PASSWORD` | Password FTP del admin |
| `DB_HOST` | Host MySQL (generalmente `localhost`) |
| `DB_PORT` | Puerto MySQL (default `3306`) |
| `DB_NAME` | Nombre de la BD |
| `DB_USER` | Usuario de la BD |
| `DB_PASS` | Password de la BD |
| `SENDPULSE_API_ID` | API ID de SendPulse |
| `SENDPULSE_API_SECRET` | API Secret de SendPulse |

> **Nota**: el action usa **FTP** (puerto 21) bajo el nombre "SSH" por compatibilidad.
> Si tu servidor requiere **FTPS** (FTP sobre TLS), ver "FTPS" más abajo.

### FTPS (FTP sobre TLS)

Si tu servidor requiere conexión segura (FTPS), cambiá la action a:

```yaml
- uses: SamKirkland/FTP-Deploy-Action@v4.3.0
  with:
    protocol: ftps
    server: ${{ secrets.SSH_HOST }}
    ...
```
https://micasajems.com/                      → sitio público
https://admin.micasajems.com/api/actividades → JSON
https://admin.micasajems.com/admin/login     → panel admin
```

---

## 🔌 Variables de entorno

### `backend/.env`

| Variable | Descripción |
|---|---|
| `DB_HOST` | Host MySQL |
| `DB_PORT` | Puerto MySQL (default `3306`) |
| `DB_NAME` | Nombre de la BD |
| `DB_USER` / `DB_PASS` | Credenciales |
| `DB_CHARSET` | Charset (default `utf8mb4`) |
| `APP_ENV` | `production` o `development` |
| `APP_DEBUG` | `true` muestra errores detallados |
| `APP_URL` | URL del admin (ej: `https://admin.micasajems.com`) |
| `API_BASE_URL` | URL del API (ej: `https://admin.micasajems.com/api`) |
| `ADMIN_URL` | URL del panel (ej: `https://admin.micasajems.com`) |
| `SESSION_NAME` | Nombre de la cookie de sesión |
| `SESSION_LIFETIME` | Duración de la sesión en segundos |
| `SENDPULSE_API_URL` | Default `https://api.sendpulse.com` |
| `SENDPULSE_API_ID` | API ID de SendPulse |
| `SENDPULSE_API_SECRET` | API Secret de SendPulse |

### `.env` (raíz Astro)

| Variable | Descripción |
|---|---|
| `PUBLIC_API_URL` | URL del backend. En dev: `http://localhost/micasajems/backend`. En prod: `https://admin.micasajems.com/api` |
| `PUBLIC_ADMIN_URL` | URL del admin. Default: `https://admin.micasajems.com` |
| `PUBLIC_SENDPULSE_ACCOUNT_ID` | Account ID público de Web Push de SendPulse |

---

## 🛣️ Endpoints API

### Públicos (sin auth)

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/actividades` | Lista todas. Filtros: `?desde=&hasta=&categoria=` |
| `GET` | `/api/actividades?semana=YYYY-MM-DD` | Resumen semanal |
| `GET` | `/api/actividades/{id}` | Detalle de una actividad |
| `POST` | `/api/suscripciones` | Registra log de suscripción push |

### Admin (requieren sesión + CSRF)

| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/api/admin/auth` | Login |
| `GET` | `/api/admin/auth/me` | Datos del usuario actual |
| `POST` | `/api/admin/auth/logout` | Cierra sesión |
| `POST` | `/api/admin/actividades` | Crea actividad |
| `PUT` | `/api/admin/actividades/{id}` | Actualiza actividad |
| `DELETE` | `/api/admin/actividades/{id}` | Elimina actividad |

### Vistas HTML admin

| Ruta | Descripción |
|---|---|
| `/admin/login` | Formulario de login |
| `/admin/dashboard` | Dashboard con KPIs |
| `/admin/actividades` | Listado |
| `/admin/actividades/nueva` | Alta |
| `/admin/actividades/editar/{id}` | Edición |

---

## 🔔 Notificaciones Push (SendPulse + Web Push nativo)

El componente `BotonNotificaciones.astro` usa un esquema **híbrido** para que
siempre haya un canal funcionando:

1. **SendPulse SDK** (si está configurado) — carga el script oficial de SendPulse
   y le pide al usuario permiso de notificaciones.
2. **Web Push nativo (VAPID)** como fallback automático si SendPulse falla o no
   está configurado. Este canal funciona sin terceros: las suscripciones se
   guardan en la tabla `push_subscriptions` y el backend las entrega usando
   `WebPushClient`.

> Esto significa que las notificaciones **siempre funcionarán** aunque no hayas
> completado la registración en SendPulse.

### Configurar SendPulse (opcional, recomendado)

1. Crear cuenta en [sendpulse.com](https://sendpulse.com).
2. Ir a **Web Push** → crear un nuevo sitio para `micasajems.com` (y/o
   `admin.micasajems.com`).
3. Copiar el `account_id` → en `.env` de Astro (raíz):
   ```
   PUBLIC_SENDPULSE_ACCOUNT_ID=<tu_account_id>
   ```
4. Verificá que el dominio esté registrado correctamente en SendPulse (es un
   requisito del propio SendPulse; sin esto, el SDK cargará pero no inicializará
   y el frontend caerá automáticamente al fallback VAPID).
5. HTTPS obligatorio (ya activo por Let's Encrypt en Hestia).

### Disparar notificaciones al crear/editar una actividad

En el formulario de actividad del admin hay un checkbox **"Notificar a los
suscriptores push"**. Marcándolo, al guardar la actividad se envía
automáticamente una notificación a todos los suscriptores con el título, lugar y
fecha del evento. El listado muestra estadísticas del envío
(`total / ok / fallidas`).

Si querés usar SendPulse como canal de envío masivo en lugar del VAPID local,
hay que llamar a la API de SendPulse; eso queda como evolución futura.

---

## 🛠️ Stack

- **PHP 7.4+** (con polyfills para 8.0+)
- **MySQL 5.7 / MariaDB 10+**
- **Astro 5** + **Tailwind 4**
- **FullCalendar 6** (vía CDN)
- **SendPulse Web Push** (vía CDN)

---

## 🔍 Diagnóstico de errores en producción

Si ves un 500 sin más info, hay 3 endpoints útiles para diagnosticar:

| Endpoint | Qué prueba |
|---|---|
| `/info.php` | PHP standalone (no usa el framework). Si esto NO funciona, el problema es PHP/Apache, no tu código |
| `/api/health` | BD + extensiones + storage. Si esto funciona pero el login no, el problema es de credenciales |
| `storage/logs/php_errors.log` | Log completo de errores PHP (visible por FTP) |

### Pasos para diagnosticar un 500

1. **Visitá `/info.php`**: si responde con info de PHP, el problema es tu código/Config
2. **Si `/info.php` también da 500**, el problema es PHP/Apache en el server:
   - Verificá la versión de PHP seleccionada en Hestia (Web → admin.micasajems.com → PHP version)
   - Verificá que PHP-FPM esté corriendo (SSH: `systemctl status php8.2-fpm` o similar)
   - Verificá el log de Apache: `/var/log/apache2/domains/admin.micasajems.com.error.log`
3. **Forzá una subida completa** si la action de GitHub tiene un state file cacheado:
   - Conectate por SFTP al server y borrá `.ftp-deploy-state-backend` en `public_html/`
   - Hacé un nuevo push

### Forzar debug en producción

Si todo funciona pero necesitás ver el error exacto, editá `.env` en el server (por SFTP o SSH):

```env
APP_DEBUG=true
APP_ENV=development
```

Después de ver el error, volvé a `false` / `production`.

---

## 📝 CORS

El backend (`admin.micasajems.com`) refleja el header `Access-Control-Allow-Origin`
basado en `HTTP_ORIGIN`. Esto permite que `micasajems.com` consuma la API.

Para mayor seguridad en producción, en `Response.php` podrías restringir a:

```php
$allowed = ['https://micasajems.com', 'https://www.micasajems.com'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
```