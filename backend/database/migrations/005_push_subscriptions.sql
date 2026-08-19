-- =====================================================
-- 005 - Tabla de suscripciones Web Push
-- =====================================================
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `endpoint` VARCHAR(500) NOT NULL,
  `endpoint_hash` CHAR(64) NOT NULL,
  `p256dh` VARCHAR(200) NOT NULL,
  `auth` VARCHAR(100) NOT NULL,
  `user_agent` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `last_sent_at` TIMESTAMP NULL,
  `fail_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_endpoint` (`endpoint_hash`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;