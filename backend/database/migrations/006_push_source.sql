-- =====================================================
-- 006 - Columna `source` para distinguir SendPulse vs WebPush VAPID
-- =====================================================
-- Esta migración agrega la columna `source` a las tablas existentes
-- de suscripciones push para poder distinguir el origen de cada una.

-- Si la columna no existe, la agregamos (compatible con re-ejecución)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'suscriptores_push'
      AND COLUMN_NAME = 'source'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE suscriptores_push ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT ''unknown'' AFTER ip, ADD INDEX idx_source (source)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists2 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'push_subscriptions'
      AND COLUMN_NAME = 'source'
);
SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE push_subscriptions ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT ''webpush'' AFTER ip, ADD INDEX idx_source (source)',
    'SELECT 1'
);
PREPARE stmt FROM @sql2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;