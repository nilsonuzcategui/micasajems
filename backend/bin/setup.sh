#!/bin/bash
# =====================================================
# Script de setup inicial del backend en producción
# Pegar completo en SSH al VPS después del primer deploy
# =====================================================
# Detecta automáticamente la ruta según tu usuario SSH.

set -e

# Detectar usuario actual y dominio admin
CURRENT_USER=$(whoami)
ADMIN_DOMAIN="admin.micasajems.com"
WEB_ROOT="/home/${CURRENT_USER}/web/${ADMIN_DOMAIN}/public_html"

echo "================================================"
echo "JEMS Backend Setup"
echo "================================================"
echo "Usuario SSH: $CURRENT_USER"
echo "Web root:    $WEB_ROOT"
echo ""

# 1. Verificar que estamos en el lugar correcto
if [ ! -d "$WEB_ROOT" ]; then
    echo "ERROR: No se encontró $WEB_ROOT"
    echo ""
    echo "Verificá:"
    echo "  - Que el subdominio admin.micasajems.com exista en Hestia"
    echo "  - Que el primer deploy haya terminado"
    echo "  - Que el usuario SSH sea el dueño del subdominio"
    echo ""
    echo "Si tu subdominio está en otra ruta, pasámela:"
    echo "  WEB_ROOT=/ruta/custom bash setup.sh"
    exit 1
fi

cd "$WEB_ROOT"

# 2. Verificar archivos esenciales
echo "→ Verificando archivos..."
for f in "index.php" ".env" ".htaccess" "bin/migrate.php" "src/Bootstrap.php"; do
    if [ ! -f "$f" ]; then
        echo "  ✗ Falta: $f"
        echo "  ¿Hiciste el deploy? Esperá a que termine GitHub Actions y reintentá."
        exit 1
    fi
    echo "  ✓ $f"
done

# 3. Verificar PHP
echo ""
echo "→ Verificando PHP..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "  PHP $PHP_VERSION"
if [ "$(printf '%s\n' "7.4.0" "$PHP_VERSION" | sort -V | head -n1)" != "7.4.0" ]; then
    echo "  ⚠ PHP < 7.4 detectado. Recomendado: PHP 7.4 o superior."
fi

# 4. Verificar permisos
echo ""
echo "→ Configurando permisos..."
mkdir -p storage/logs storage/cache
chmod 755 storage storage/logs storage/cache 2>/dev/null || true

# 5. Mostrar configuración de BD (sin mostrar password)
echo ""
echo "→ Configuración de BD actual:"
grep -E "^DB_" .env | sed 's/DB_PASS=.*/DB_PASS=***OCULTO***/'

# 6. Estado actual de las tablas
echo ""
echo "→ Estado actual de las tablas:"
php bin/migrate.php status

# 7. Correr migraciones
echo ""
echo "→ Corriendo migraciones..."
php bin/migrate.php

# 8. Estado final
echo ""
echo "→ Estado final:"
php bin/migrate.php status

# 9. Cambiar contraseña del admin (interactivo)
echo ""
echo "================================================"
echo "Cambio de contraseña del admin"
echo "================================================"
read -p "¿Querés cambiar la clave del admin ahora? (s/N): " CHANGE_PASS

if [[ "$CHANGE_PASS" =~ ^[sS]$ ]]; then
    read -s -p "Nueva contraseña para 'admin': " NEW_PASS
    echo ""
    read -s -p "Confirmar contraseña: " NEW_PASS_2
    echo ""

    if [ "$NEW_PASS" != "$NEW_PASS_2" ]; then
        echo "✗ Las contraseñas no coinciden"
    elif [ -z "$NEW_PASS" ]; then
        echo "✗ La contraseña no puede estar vacía"
    else
        # Generar hash
        HASH=$(php -r "echo password_hash('$NEW_PASS', PASSWORD_DEFAULT);")

        # Obtener DB_USER y DB_PASS del .env
        DB_USER=$(grep "^DB_USER=" .env | cut -d= -f2)
        DB_PASS=$(grep "^DB_PASS=" .env | cut -d= -f2)
        DB_NAME=$(grep "^DB_NAME=" .env | cut -d= -f2)

        if [ -z "$DB_USER" ] || [ -z "$DB_NAME" ]; then
            echo "✗ No se pudieron leer las credenciales del .env"
        else
            # Actualizar
            mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "UPDATE admin_users SET password_hash='$HASH' WHERE username='admin';" 2>/dev/null
            if [ $? -eq 0 ]; then
                echo "✓ Contraseña actualizada para 'admin'"
            else
                echo "✗ Error actualizando la contraseña. Verificá las credenciales de MySQL."
            fi
        fi
    fi
fi

# 10. Test final
echo ""
echo "================================================"
echo "Test final"
echo "================================================"
echo "→ Health check:"
HEALTH=$(curl -s -o /dev/null -w "%{http_code}" "https://${ADMIN_DOMAIN}/api/health" 2>/dev/null || echo "error")
echo "  https://${ADMIN_DOMAIN}/api/health → HTTP $HEALTH"

echo "→ Login page:"
LOGIN=$(curl -s -o /dev/null -w "%{http_code}" "https://${ADMIN_DOMAIN}/login" 2>/dev/null || echo "error")
echo "  https://${ADMIN_DOMAIN}/login → HTTP $LOGIN"

echo ""
echo "================================================"
echo "✓ Setup completado"
echo "================================================"
echo "Próximos pasos:"
echo "  1. Abrí https://${ADMIN_DOMAIN}/login"
echo "  2. Login con: admin / (la contraseña que pusiste)"
echo "  3. Creá tu primera actividad desde el panel"
echo ""