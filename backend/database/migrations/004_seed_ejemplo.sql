-- =====================================================
-- 004 - Datos de ejemplo (opcional)
-- =====================================================
INSERT INTO `actividades`
  (`titulo`, `descripcion`, `lugar`, `fecha`, `hora_inicio`, `hora_fin`, `categoria`, `destacado`, `estado`)
VALUES
  ('Culto de Adoración Familiar', 'Servicio dominical con alabanzas y predicación de la Palabra.', 'Templo principal - La Urbina', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 'culto', 1, 'programada'),
  ('Estudio Bíblico: Romanos', 'Estudio capítulo por capítulo de la carta a los Romanos.', 'Salón multiuso', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '19:00:00', '21:00:00', 'estudio', 0, 'programada'),
  ('Reunión de Jóvenes', 'Encuentro semanal de alabanza y palabra para jóvenes.', 'Casa de retiro JEMS', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '18:30:00', '21:00:00', 'ministerio', 1, 'programada');

-- Ajustar la fecha del primer registro al próximo domingo
UPDATE `actividades`
SET `fecha` = DATE_ADD(CURDATE(), INTERVAL ((7 - WEEKDAY(CURDATE()) + 7) MOD 7) DAY)
WHERE `titulo` = 'Culto de Adoración Familiar';