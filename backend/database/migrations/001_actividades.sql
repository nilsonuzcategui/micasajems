-- =====================================================
-- 001 - Tabla de actividades
-- =====================================================
CREATE TABLE IF NOT EXISTS `actividades` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `lugar` VARCHAR(200) NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NULL,
  `categoria` ENUM('culto','estudio','evento','ministerio','social','otro') NOT NULL DEFAULT 'culto',
  `destacado` TINYINT(1) NOT NULL DEFAULT 0,
  `estado` ENUM('programada','cancelada','realizada') NOT NULL DEFAULT 'programada',
  `creado_por` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_fecha` (`fecha`),
  INDEX `idx_estado_fecha` (`estado`, `fecha`),
  INDEX `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;