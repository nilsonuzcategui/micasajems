# 🗄️ Migraciones de BD — Módulo Actividades JEMS

## Orden de ejecución

```bash
# Desde XAMPP / línea de comandos MySQL:
mysql -u root -p micasajems < 001_actividades.sql
mysql -u root -p micasajems < 002_admin_users.sql
mysql -u root -p micasajems < 003_suscripciones.sql

# Opcional (datos de ejemplo):
mysql -u root -p micasajems < 004_seed_ejemplo.sql
```

## Cambiar contraseña del admin

1. Genera un nuevo hash:
   ```bash
   php -r "echo password_hash('TuNuevaClave', PASSWORD_DEFAULT);"
   ```
2. Actualiza la BD:
   ```sql
   UPDATE admin_users
   SET password_hash = '<hash_generado>'
   WHERE username = 'admin';
   ```

## Reiniciar todo

```sql
DROP TABLE IF EXISTS actividades;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS suscriptores_push;
-- Luego vuelve a correr las migraciones 001, 002, 003.
```