-- =====================================================
-- 003 - Log mínimo de suscriptores push (SendPulse es la fuente de verdad)
-- =====================================================
CREATE TABLE IF NOT EXISTS `suscriptores_push` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sendpulse_id` VARCHAR(120) NULL,
  `user_agent` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;