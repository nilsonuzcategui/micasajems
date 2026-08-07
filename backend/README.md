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

## 🔔 SendPulse — Web Push

1. Crear cuenta en [sendpulse.com](https://sendpulse.com)
2. Ir a **Web Push** → crear un nuevo sitio para `micasajems.com`
3. Copiar el `account_id` → pegarlo en `.env` Astro como `PUBLIC_SENDPULSE_ACCOUNT_ID`
4. En producción, configurar HTTPS (ya hecho por Let's Encrypt en Hestia)

El componente `BotonNotificaciones.astro` carga el script oficial de SendPulse
solo cuando el usuario hace clic y llama a `PushSubscriber.subscribe()`.

---

## 🛠️ Stack

- **PHP 7.4+** (con polyfills para 8.0+)
- **MySQL 5.7 / MariaDB 10+**
- **Astro 5** + **Tailwind 4**
- **FullCalendar 6** (vía CDN)
- **SendPulse Web Push** (vía CDN)

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