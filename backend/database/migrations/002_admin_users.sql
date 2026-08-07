-- =====================================================
-- 002 - Tabla de usuarios administradores
-- =====================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(120) NULL,
  `rol` ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  `ultimo_acceso` TIMESTAMP NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Seed inicial del usuario admin (cambiar la contraseña luego)
-- El hash corresponde a: Jems2026!
-- Generar uno nuevo con: php -r "echo password_hash('TuClave', PASSWORD_DEFAULT);"
-- =====================================================
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `nombre`, `rol`, `activo`)
VALUES (
  'admin',
  'admin@micasajems.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrador',
  'admin',
  1
)
ON DUPLICATE KEY UPDATE `username` = `username`;