# Módulo de Actividades — JEMS

Backend PHP + componente Astro para gestionar actividades, calendario semanal
y suscripciones push vía SendPulse.

---

## 📂 Estructura

```
micasajems/
├── backend/                         # PHP API + panel admin (NO se publica)
│   ├── .env                         # (NO subir) credenciales reales
│   ├── .env.example                 # plantilla
│   ├── public/
│   │   ├── index.php                # front controller
│   │   └── .htaccess
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
└── astro.config.mjs                 # proxy /api → backend
```

---

## 🚀 Setup paso a paso

### 1. Base de datos

Conectate a MySQL (XAMPP) y ejecutá:

```bash
mysql -u root -p micasajems < backend/database/migrations/001_actividades.sql
mysql -u root -p micasajems < backend/database/migrations/002_admin_users.sql
mysql -u root -p micasajems < backend/database/migrations/003_suscripciones.sql

# Opcional (datos de ejemplo):
mysql -u root -p micasajems < backend/database/migrations/004_seed_ejemplo.sql
```

> Si tu BD ya existe con el mismo nombre, estos scripts son **idempotentes**
> (usan `CREATE TABLE IF NOT EXISTS`).

### 2. Configurar credenciales

```bash
cd backend
cp .env.example .env
```

Editá `backend/.env` y completá:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `SENDPULSE_API_ID` y `SENDPULSE_API_SECRET` (desde SendPulse → Settings → API)
- `PUBLIC_SENDPULSE_ACCOUNT_ID` → no, ese va en el `.env` de Astro (paso 4)

Para Astro:

```bash
# Raíz del proyecto
cp .env.example .env
```

Editá `.env` y completá:
- `PUBLIC_API_URL` → `http://localhost/micasajems/backend/public` (en producción, tu dominio)
- `PUBLIC_SENDPULSE_ACCOUNT_ID` → account ID de Web Push de SendPulse

### 3. Cambiar la contraseña del admin

Por defecto, las migraciones insertan un usuario `admin` con la clave `Jems2026!`.
**Cambiala apenas entres al panel.**

Desde el panel: `/admin/login` → Dashboard → (próximamente: gestión de usuarios).

Por línea de comandos:

```bash
php -r "echo password_hash('TuNuevaClaveSegura', PASSWORD_DEFAULT);"
```

```sql
UPDATE admin_users
SET password_hash = '<hash_generado>'
WHERE username = 'admin';
```

### 4. Verificar que el backend funciona

Con XAMPP corriendo:

```
http://localhost/micasajems/backend/public/api/actividades
```

Debería devolver un JSON con `ok: true` y la lista de actividades.

Si no tenés XAMPP, podés usar el servidor embebido de PHP:

```bash
cd backend
php -S 127.0.0.1:8765 -t public public/index.php
```

### 5. Panel admin

```
http://localhost/micasajems/backend/public/admin/login
```

Login con `admin` / `Jems2026!` (cambiar después).

### 6. Frontend Astro

```bash
npm install
npm run dev       # desarrollo
npm run build     # producción
```

El componente `Actividades.astro` se incluyó automáticamente en `index.astro`
(sección "Actividades" con resumen semanal + calendario + botón push).

Para reusarlo en otra página:

```astro
---
import Actividades from "../components/Actividades.astro";
---
<Actividades
  mostrarCalendario={true}
  mostrarResumen={true}
  mostrarBotonPush={true}
/>
```

---

## 🔌 Variables de entorno

### `backend/.env`

| Variable | Descripción |
|---|---|
| `DB_HOST` | Host MySQL (default `127.0.0.1`) |
| `DB_PORT` | Puerto (default `3306`) |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` / `DB_PASS` | Credenciales |
| `DB_CHARSET` | Charset (default `utf8mb4`) |
| `APP_ENV` | `development` o `production` |
| `APP_DEBUG` | `true` muestra errores detallados |
| `APP_URL` | URL pública del sitio |
| `API_BASE_URL` | URL del backend (para CORS) |
| `ADMIN_URL` | URL del panel admin |
| `SESSION_NAME` | Nombre de la cookie de sesión |
| `SESSION_LIFETIME` | Duración de la sesión en segundos (default 7200) |
| `ADMIN_DEFAULT_USER` | Solo para seed (no se usa en runtime) |
| `ADMIN_DEFAULT_PASS` | Solo para seed |
| `ADMIN_DEFAULT_EMAIL` | Solo para seed |
| `SENDPULSE_API_URL` | Default `https://api.sendpulse.com` |
| `SENDPULSE_API_ID` | API ID de SendPulse |
| `SENDPULSE_API_SECRET` | API Secret de SendPulse |
| `SENDPULSE_PUSH_WEBHOOK_SECRET` | Opcional, para validar webhooks |

### `.env` (raíz del proyecto Astro)

| Variable | Descripción |
|---|---|
| `PUBLIC_API_URL` | URL del backend (debe coincidir con `API_BASE_URL`) |
| `PUBLIC_SENDPULSE_ACCOUNT_ID` | Account ID público de Web Push de SendPulse |

> Solo se exponen al cliente las variables con prefijo `PUBLIC_*`.

---

## 🛣️ Endpoints API

### Públicos

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/actividades` | Lista todas. Filtros: `?desde=YYYY-MM-DD&hasta=YYYY-MM-DD&categoria=culto` |
| `GET` | `/api/actividades?semana=YYYY-MM-DD` | Resumen semanal (7 días desde la fecha) |
| `GET` | `/api/actividades/{id}` | Detalle de una actividad |
| `POST` | `/api/suscripciones` | Registra log de suscripción push |

### Admin (requieren sesión + CSRF)

| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/api/admin/auth` | Login (body: `username`, `password`) |
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
| `/admin/actividades` | Listado + edición rápida |
| `/admin/actividades/nueva` | Formulario de alta |
| `/admin/actividades/editar/{id}` | Formulario de edición |

---

## 🗃️ Modelo de datos

### `actividades`
- `id`, `titulo`, `descripcion`, `lugar`, `fecha`, `hora_inicio`, `hora_fin`
- `categoria` ∈ `{culto, estudio, evento, ministerio, social, otro}`
- `destacado` (bool), `estado` ∈ `{programada, cancelada, realizada}`
- `creado_por` (FK a `admin_users`), `created_at`, `updated_at`

### `admin_users`
- `id`, `username` (único), `email` (único), `password_hash`, `nombre`
- `rol` ∈ `{admin, editor}`, `ultimo_acceso`, `activo`, `created_at`

### `suscriptores_push`
- `id`, `sendpulse_id`, `user_agent`, `ip`, `created_at`
- Log mínimo. SendPulse es la fuente de verdad de la lista de suscriptores.

---

## 🔔 SendPulse — Web Push

1. Crear cuenta en [sendpulse.com](https://sendpulse.com)
2. Ir a **Web Push** → crear un nuevo sitio
3. Configurar el dominio (en producción requiere HTTPS)
4. Copiar el `account_id` (aparece en el snippet de instalación)
5. Pegarlo en `.env` de Astro como `PUBLIC_SENDPULSE_ACCOUNT_ID`

El componente `BotonNotificaciones.astro`:
- Carga el script oficial de SendPulse (`cdn.sendpulse.com/push/push.js`) solo cuando el usuario hace clic
- Llama a `PushSubscriber(accountId).subscribe()`
- Muestra mensajes de éxito/error

Para enviar notificaciones: usar el panel de SendPulse → Web Push → New Campaign.

---

## 🛠️ Stack

- **PHP 7.4+** (con polyfills para 8.0+)
- **MySQL 5.7 / MariaDB 10+**
- **Astro 5** + **Tailwind 4**
- **FullCalendar 6** (vía CDN)
- **SendPulse Web Push** (vía CDN)

---

## 📝 Notas

- El backend está pensado para correr **bajo XAMPP** (`http://localhost/micasajems/backend/public/`)
- En producción, ajustar `APP_URL`, `API_BASE_URL` y `ADMIN_URL` a tu dominio
- HTTPS obligatorio en producción para Web Push
- El panel admin usa Tailwind via CDN para mantenerlo simple y sin build step
- CORS: el backend acepta cualquier origen en `HTTP_ORIGIN` (ajustar si es necesario)

---

## 🌐 Acceso al admin durante desarrollo

Hay **3 formas** de acceder al panel admin:

### Opción 1: vía proxy de Astro (recomendado)

`astro.config.mjs` ya tiene configurado el proxy para `/admin` → backend PHP.

Con XAMPP corriendo, simplemente:

```bash
npm run dev
```

Y abrir: **`http://localhost:4321/admin/login`**

### Opción 2: directo desde XAMPP

```
http://localhost/micasajems/backend/public/admin/login
```

Útil si no querés levantar el dev server de Astro.

### Opción 3: enlace discreto en el footer

El componente `Footer.astro` ya tiene un ícono de candado (🔒) que abre
`/admin/login` en pestaña nueva.

---

## 🚀 Despliegue en producción

### Estructura recomendada en el servidor

```
/home/user/micasajems.com/
├── public_html/                 ← build de Astro (contenido de /dist)
│   ├── index.html
│   ├── _astro/
│   └── ...
├── api/                         ← backend PHP (renombrar "backend" → "api")
│   ├── public/                  ← único directorio expuesto a la web
│   │   ├── index.php
│   │   └── .htaccess
│   ├── src/
│   ├── database/
│   ├── .env
│   └── ...
```

### Apache / Nginx

Configurar el virtualhost para que:
- `https://micasajems.com/` → sirva `/public_html/` (sitio estático)
- `https://micasajems.com/admin/` → redirija al PHP (`/api/public/index.php`)
- `https://micasajems.com/api/` → redirija al PHP

#### Apache (vhost)

```apache
<VirtualHost *:443>
    ServerName micasajems.com
    DocumentRoot /home/user/micasajems.com/public_html

    # Backend PHP para admin y API
    Alias /admin /home/user/micasajems.com/api/public
    Alias /api /home/user/micasajems.com/api/public

    <Directory /home/user/micasajems.com/api/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    <Directory /home/user/micasajems.com/public_html>
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

El `.htaccess` de `backend/public/` ya está preparado para rutear tanto
`/api/*` como `/admin/*` al `index.php` del backend.

#### Nginx

```nginx
server {
    listen 443 ssl;
    server_name micasajems.com;
    root /home/user/micasajems.com/public_html;
    index index.html;

    # Backend PHP
    location ~ ^/(admin|api)/ {
        root /home/user/micasajems.com/api/public;
        try_files $uri $uri/ /index.php$is_args$args;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }
}
```

### Variables de entorno en producción

Editar `.env` del backend:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://micasajems.com
API_BASE_URL=https://micasajems.com/api
ADMIN_URL=https://micasajems.com/admin
```

Y `.env` de la raíz (si seguís usando Astro como fuente de configuración):

```env
PUBLIC_API_URL=https://micasajems.com/api
PUBLIC_SENDPULSE_ACCOUNT_ID=<tu_account_id>
```

> Si hosteás el frontend en **Vercel/Netlify/Cloudflare Pages**,
> el proxy de Vite no aplica. En ese caso el frontend apunta directo a
> `https://micasajems.com/api` y el backend se deploya aparte.