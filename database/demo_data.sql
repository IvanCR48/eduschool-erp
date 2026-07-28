-- =============================================================
-- EduSchool ERP — Demo Data Seed
-- =============================================================
-- Run this file AFTER importing school_admin.sql
-- It populates the system with realistic fictional data so
-- reviewers can see a fully-functional, non-empty dashboard.
--
-- School: "Greenfield Academy" (fictional)
-- All names, DNIs, and emails are completely fictional.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- =============================================================
-- 1. SYSTEM CONFIGURATION — Neutral school name
-- =============================================================
UPDATE `configuracion_sistema`
SET `valor` = 'Greenfield Academy'
WHERE `clave` = 'sistema.nombre';

UPDATE `configuracion_sistema`
SET `valor` = 'America/New_York'
WHERE `clave` = 'sistema.timezone';

UPDATE `configuracion_sistema`
SET `valor` = '2026'
WHERE `clave` = 'academico.anio_lectivo';

-- Also update the older config table
UPDATE `configuraciones_sistema`
SET `valor` = 'EduSchool ERP — Greenfield Academy Demo'
WHERE `clave` = 'system_name';

UPDATE `configuraciones_sistema`
SET `valor` = 'demo@greenfield.edu'
WHERE `clave` = 'from_email';

UPDATE `configuraciones_sistema`
SET `valor` = 'Greenfield Academy'
WHERE `clave` = 'from_name';

-- =============================================================
-- 2. SPECIALTIES (already seeded in school_admin.sql, but ensure)
-- =============================================================

-- =============================================================
-- 3. TURNOS (shifts) — insert if table exists
-- =============================================================
INSERT IGNORE INTO `turnos` (`id`, `nombre`, `hora_inicio`, `hora_fin`) VALUES
(1, 'Morning',   '07:30:00', '12:30:00'),
(2, 'Afternoon', '13:00:00', '18:00:00');

-- =============================================================
-- 4. COURSES
-- =============================================================
DELETE FROM `cursos` WHERE id BETWEEN 1 AND 10;
INSERT INTO `cursos` (`id`, `nombre`, `anio`, `division`, `especialidad_id`, `turno_id`, `capacidad_maxima`, `activo`, `creado_en`) VALUES
(1,  '1st Year A', 1, 'A', 1, 1, 30, 1, '2026-03-01 08:00:00'),
(2,  '2nd Year A', 2, 'A', 1, 1, 30, 1, '2026-03-01 08:00:00'),
(3,  '3rd Year A', 3, 'A', 2, 1, 28, 1, '2026-03-01 08:00:00'),
(4,  '4th Year B', 4, 'B', 2, 2, 25, 1, '2026-03-01 08:00:00'),
(5,  '5th Year B', 5, 'B', 3, 2, 24, 1, '2026-03-01 08:00:00');

-- =============================================================
-- 5. SUBJECTS (materias)
-- =============================================================
DELETE FROM `materias` WHERE id BETWEEN 1 AND 20;
INSERT INTO `materias` (`id`, `nombre`, `codigo`, `especialidad_id`, `anio_materia`, `carga_horaria`, `es_taller`, `activa`, `creado_en`) VALUES
(1,  'Mathematics',        'MAT', NULL, 1, 4, 0, 1, '2026-03-01 08:00:00'),
(2,  'Language & Literature','LEN', NULL, 1, 4, 0, 1, '2026-03-01 08:00:00'),
(3,  'Physics',            'FIS', NULL, 2, 3, 0, 1, '2026-03-01 08:00:00'),
(4,  'Chemistry',          'QUI', NULL, 2, 3, 0, 1, '2026-03-01 08:00:00'),
(5,  'History',            'HIS', NULL, 1, 2, 0, 1, '2026-03-01 08:00:00'),
(6,  'Geography',          'GEO', NULL, 1, 2, 0, 1, '2026-03-01 08:00:00'),
(7,  'Biology',            'BIO', NULL, 2, 3, 0, 1, '2026-03-01 08:00:00'),
(8,  'Programming',        'PRG', 1,    3, 5, 0, 1, '2026-03-01 08:00:00'),
(9,  'Databases',          'BDD', 1,    4, 4, 0, 1, '2026-03-01 08:00:00'),
(10, 'Networks & Systems',  'RED', 1,    5, 4, 0, 1, '2026-03-01 08:00:00'),
(11, 'English',            'ING', NULL, 1, 3, 0, 1, '2026-03-01 08:00:00'),
(12, 'Physical Education', 'EDF', NULL, 1, 2, 0, 1, '2026-03-01 08:00:00'),
(13, 'Electrotechnics Workshop','TLE', 2, 3, 6, 1, 1, '2026-03-01 08:00:00'),
(14, 'Construction Drawing','DIC', 3,   4, 4, 0, 1, '2026-03-01 08:00:00'),
(15, 'Civic Education',    'CIV', NULL, 2, 2, 0, 1, '2026-03-01 08:00:00');

-- =============================================================
-- 6. MATERIA_CURSO (subject assignments to courses)
-- =============================================================
DELETE FROM `materia_curso` WHERE id BETWEEN 1 AND 50;
INSERT INTO `materia_curso` (`id`, `materia_id`, `curso_id`, `activo`, `creado_en`) VALUES
-- Course 1 (1st A)
(1,  1,  1, 1, '2026-03-01 08:00:00'),
(2,  2,  1, 1, '2026-03-01 08:00:00'),
(3,  5,  1, 1, '2026-03-01 08:00:00'),
(4,  6,  1, 1, '2026-03-01 08:00:00'),
(5,  11, 1, 1, '2026-03-01 08:00:00'),
(6,  12, 1, 1, '2026-03-01 08:00:00'),
(7,  15, 1, 1, '2026-03-01 08:00:00'),
-- Course 2 (2nd A)
(8,  1,  2, 1, '2026-03-01 08:00:00'),
(9,  2,  2, 1, '2026-03-01 08:00:00'),
(10, 3,  2, 1, '2026-03-01 08:00:00'),
(11, 4,  2, 1, '2026-03-01 08:00:00'),
(12, 7,  2, 1, '2026-03-01 08:00:00'),
(13, 11, 2, 1, '2026-03-01 08:00:00'),
(14, 12, 2, 1, '2026-03-01 08:00:00'),
-- Course 3 (3rd A)
(15, 1,  3, 1, '2026-03-01 08:00:00'),
(16, 3,  3, 1, '2026-03-01 08:00:00'),
(17, 4,  3, 1, '2026-03-01 08:00:00'),
(18, 8,  3, 1, '2026-03-01 08:00:00'),
(19, 13, 3, 1, '2026-03-01 08:00:00'),
(20, 11, 3, 1, '2026-03-01 08:00:00'),
-- Course 4 (4th B)
(21, 8,  4, 1, '2026-03-01 08:00:00'),
(22, 9,  4, 1, '2026-03-01 08:00:00'),
(23, 3,  4, 1, '2026-03-01 08:00:00'),
(24, 14, 4, 1, '2026-03-01 08:00:00'),
(25, 11, 4, 1, '2026-03-01 08:00:00'),
-- Course 5 (5th B)
(26, 9,  5, 1, '2026-03-01 08:00:00'),
(27, 10, 5, 1, '2026-03-01 08:00:00'),
(28, 3,  5, 1, '2026-03-01 08:00:00'),
(29, 14, 5, 1, '2026-03-01 08:00:00'),
(30, 11, 5, 1, '2026-03-01 08:00:00');

-- =============================================================
-- 7. TEACHERS (profesores)
-- =============================================================
DELETE FROM `profesores` WHERE id BETWEEN 1 AND 10;
INSERT INTO `profesores` (`id`, `dni`, `apellido`, `nombre`, `fecha_nacimiento`, `domicilio`, `telefono_celular`, `email`, `titulo`, `especialidad_id`, `fecha_ingreso`, `activo`, `creado_en`) VALUES
(1, '20111001', 'Miller',   'James',    '1975-04-12', '14 Oak Street',      '+1-555-0101', 'j.miller@greenfield.edu',   'Licentiate in Mathematics',   NULL, '2015-03-01', 1, '2026-03-01 08:00:00'),
(2, '20111002', 'Johnson',  'Sarah',    '1980-08-23', '27 Pine Avenue',     '+1-555-0102', 's.johnson@greenfield.edu',  'Licentiate in Literature',    NULL, '2017-03-01', 1, '2026-03-01 08:00:00'),
(3, '20111003', 'Garcia',   'Carlos',   '1978-11-05', '8 Maple Lane',       '+1-555-0103', 'c.garcia@greenfield.edu',   'Licentiate in Physics',       2,    '2016-03-01', 1, '2026-03-01 08:00:00'),
(4, '20111004', 'Williams', 'Patricia', '1982-02-17', '33 Elm Road',        '+1-555-0104', 'p.williams@greenfield.edu', 'Engineer in Informatics',     1,    '2018-03-01', 1, '2026-03-01 08:00:00'),
(5, '20111005', 'Brown',    'Robert',   '1970-06-30', '55 Birch Boulevard', '+1-555-0105', 'r.brown@greenfield.edu',    'Licentiate in Chemistry',     2,    '2012-03-01', 1, '2026-03-01 08:00:00'),
(6, '20111006', 'Davis',    'Emily',    '1985-09-14', '19 Cedar Court',     '+1-555-0106', 'e.davis@greenfield.edu',    'Licentiate in History',       NULL, '2019-03-01', 1, '2026-03-01 08:00:00'),
(7, '20111007', 'Martinez', 'Luis',     '1977-01-22', '42 Willow Way',      '+1-555-0107', 'l.martinez@greenfield.edu', 'Engineer in Networks',        1,    '2014-03-01', 1, '2026-03-01 08:00:00');

-- =============================================================
-- 8. TEACHER SYSTEM USERS (usuarios for teacher login)
-- =============================================================
-- password: teacher123  (bcrypt hash)
DELETE FROM `usuarios` WHERE id IN (3,4,5,6,7,8,9);
INSERT INTO `usuarios` (`id`, `dni`, `apellido`, `nombre`, `email`, `telefono`, `password_hash`, `must_change_password`, `rol`, `activo`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `creado_en`, `actualizado_en`) VALUES
(3, '20111004', 'Williams', 'Patricia', 'p.williams@greenfield.edu', NULL, '$2y$10$LrxPvfD3467HdpJPfRaJw.ocvI3z.sXR6D4Ts3GHFpy64h9Z4liC6', 0, 'profesor', 1, NULL, 0, NULL, '2026-03-01 08:00:00', '2026-03-01 08:00:00'),
(4, '20111007', 'Martinez', 'Luis',     'l.martinez@greenfield.edu', NULL, '$2y$10$LrxPvfD3467HdpJPfRaJw.ocvI3z.sXR6D4Ts3GHFpy64h9Z4liC6', 0, 'profesor', 1, NULL, 0, NULL, '2026-03-01 08:00:00', '2026-03-01 08:00:00'),
(5, '99000001', 'Thompson', 'Michael',  'director@greenfield.edu',   NULL, '$2y$10$LrxPvfD3467HdpJPfRaJw.ocvI3z.sXR6D4Ts3GHFpy64h9Z4liC6', 0, 'directivo', 1, NULL, 0, NULL, '2026-03-01 08:00:00', '2026-03-01 08:00:00'),
(6, '99000002', 'Anderson', 'Linda',    'preceptor@greenfield.edu',  NULL, '$2y$10$LrxPvfD3467HdpJPfRaJw.ocvI3z.sXR6D4Ts3GHFpy64h9Z4liC6', 0, 'preceptor', 1, NULL, 0, NULL, '2026-03-01 08:00:00', '2026-03-01 08:00:00'),
(7, '99000003', 'Wilson',   'Susan',    'secretary@greenfield.edu',  NULL, '$2y$10$LrxPvfD3467HdpJPfRaJw.ocvI3z.sXR6D4Ts3GHFpy64h9Z4liC6', 0, 'secretario', 1, NULL, 0, NULL, '2026-03-01 08:00:00', '2026-03-01 08:00:00');

-- =============================================================
-- 9. DIRECTOR / PRECEPTOR (equipo directivo)
-- =============================================================
DELETE FROM `equipo_directivo` WHERE id BETWEEN 1 AND 5;
INSERT INTO `equipo_directivo` (`id`, `usuario_id`, `curso_id`, `apellido`, `nombre`, `cargo`, `telefono`, `email`, `foto`, `activo`) VALUES
(1, 5, NULL, 'Thompson', 'Michael', 'Principal',            '+1-555-0200', 'director@greenfield.edu',  NULL, 1),
(2, 6, 1,    'Anderson', 'Linda',   'Homeroom Teacher / 1A','+1-555-0201', 'preceptor@greenfield.edu', NULL, 1),
(3, 6, 2,    'Anderson', 'Linda',   'Homeroom Teacher / 2A','+1-555-0201', 'preceptor@greenfield.edu', NULL, 1),
(4, NULL,NULL,'Parker',  'Thomas',  'Vice Principal',       '+1-555-0202', 't.parker@greenfield.edu',  NULL, 1),
(5, 7, NULL, 'Wilson',   'Susan',   'Secretary',            '+1-555-0203', 'secretary@greenfield.edu', NULL, 1);

-- =============================================================
-- 10. PRECEPTOR-COURSE ASSIGNMENTS
-- =============================================================
DELETE FROM `preceptor_curso` WHERE id BETWEEN 1 AND 10;
INSERT INTO `preceptor_curso` (`id`, `equipo_directivo_id`, `curso_id`) VALUES
(1, 2, 1),
(2, 2, 2),
(3, 2, 3);

-- =============================================================
-- 11. TEACHER-COURSE ASSIGNMENTS (profesor_curso)
-- =============================================================
DELETE FROM `profesor_curso` WHERE id BETWEEN 1 AND 30;
INSERT INTO `profesor_curso` (`id`, `profesor_id`, `curso_id`, `anio_academico`, `activo`) VALUES
(1,  1, 1, 2026, 1), (2,  1, 2, 2026, 1), (3,  1, 3, 2026, 1),
(4,  2, 1, 2026, 1), (5,  2, 2, 2026, 1),
(6,  3, 2, 2026, 1), (7,  3, 3, 2026, 1), (8,  3, 4, 2026, 1), (9,  3, 5, 2026, 1),
(10, 4, 3, 2026, 1), (11, 4, 4, 2026, 1),
(12, 5, 2, 2026, 1), (13, 5, 3, 2026, 1),
(14, 6, 1, 2026, 1), (15, 6, 2, 2026, 1),
(16, 7, 4, 2026, 1), (17, 7, 5, 2026, 1);

-- =============================================================
-- 12. TEACHER-SUBJECT-COURSE ASSIGNMENTS (profesor_materia)
-- =============================================================
DELETE FROM `profesor_materia` WHERE id BETWEEN 1 AND 40;
INSERT INTO `profesor_materia` (`id`, `profesor_id`, `materia_id`, `curso_id`, `anio_academico`, `grupo_taller`, `activo`) VALUES
-- James Miller — Math
(1,  1, 1,  1, 2026, NULL, 1),
(2,  1, 1,  2, 2026, NULL, 1),
(3,  1, 1,  3, 2026, NULL, 1),
-- Sarah Johnson — Language
(4,  2, 2,  1, 2026, NULL, 1),
(5,  2, 2,  2, 2026, NULL, 1),
-- Carlos Garcia — Physics / Chemistry
(6,  3, 3,  2, 2026, NULL, 1),
(7,  3, 3,  3, 2026, NULL, 1),
(8,  3, 3,  4, 2026, NULL, 1),
(9,  3, 3,  5, 2026, NULL, 1),
(10, 5, 4,  2, 2026, NULL, 1),
(11, 5, 4,  3, 2026, NULL, 1),
-- Patricia Williams — Programming / Databases
(12, 4, 8,  3, 2026, NULL, 1),
(13, 4, 8,  4, 2026, NULL, 1),
(14, 4, 9,  4, 2026, NULL, 1),
(15, 4, 9,  5, 2026, NULL, 1),
-- Luis Martinez — Networks / Construction Drawing
(16, 7, 10, 5, 2026, NULL, 1),
(17, 7, 14, 4, 2026, NULL, 1),
(18, 7, 14, 5, 2026, NULL, 1),
-- Emily Davis — History / Civic Education
(19, 6, 5,  1, 2026, NULL, 1),
(20, 6, 6,  1, 2026, NULL, 1),
(21, 6, 15, 1, 2026, NULL, 1),
(22, 6, 5,  2, 2026, NULL, 1);

-- =============================================================
-- 13. STUDENTS (estudiantes) — 42 fictional students
-- =============================================================
DELETE FROM `estudiantes` WHERE id BETWEEN 1 AND 50;
INSERT INTO `estudiantes` (`id`, `dni`, `apellido`, `nombre`, `fecha_nacimiento`, `domicilio`, `telefono`, `email`, `curso_id`, `fecha_ingreso`, `activo`, `creado_en`) VALUES
-- Course 1 (1st Year A) — 9 students
(1,  '30100001','Adams',      'Oliver',    '2011-03-12', '10 Greenway Blvd', '555-3001', 'o.adams@student.edu',      1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(2,  '30100002','Baker',      'Emma',      '2011-07-24', '22 Sunrise Rd',    '555-3002', 'e.baker@student.edu',      1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(3,  '30100003','Carter',     'Noah',      '2011-01-08', '7 Hillside Ave',   '555-3003', 'n.carter@student.edu',     1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(4,  '30100004','Davis',      'Ava',       '2010-11-15', '45 Lakewood Dr',   '555-3004', 'a.davis@student.edu',      1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(5,  '30100005','Edwards',    'Liam',      '2011-05-29', '3 Meadow Path',    '555-3005', 'l.edwards@student.edu',    1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(6,  '30100006','Foster',     'Sophia',    '2010-09-03', '18 Riverbend Ln',  '555-3006', 's.foster@student.edu',     1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(7,  '30100007','Green',      'William',   '2011-02-17', '6 Summit Ct',      '555-3007', 'w.green@student.edu',      1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(8,  '30100008','Harris',     'Isabella',  '2010-12-21', '29 Valleyview Rd', '555-3008', 'i.harris@student.edu',     1, '2026-03-01', 1, '2026-03-01 08:00:00'),
(9,  '30100009','Ingram',     'James',     '2011-04-06', '11 Forestway Ct',  '555-3009', 'j.ingram@student.edu',     1, '2026-03-01', 1, '2026-03-01 08:00:00'),
-- Course 2 (2nd Year A) — 9 students
(10, '30100010','Jackson',    'Mia',       '2010-06-14', '5 Clover St',      '555-3010', 'm.jackson@student.edu',    2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(11, '30100011','Knight',     'Ethan',     '2009-10-30', '8 Chestnut Ave',   '555-3011', 'e.knight@student.edu',     2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(12, '30100012','Lewis',      'Charlotte', '2010-03-19', '16 Willowbrook',   '555-3012', 'c.lewis@student.edu',      2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(13, '30100013','Morgan',     'Alexander', '2010-08-07', '23 Fireside Dr',   '555-3013', 'a.morgan@student.edu',     2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(14, '30100014','Nelson',     'Amelia',    '2009-12-25', '34 Brookside Ct',  '555-3014', 'am.nelson@student.edu',    2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(15, '30100015','Oliver',     'Henry',     '2010-01-11', '9 Pinecrest Rd',   '555-3015', 'h.oliver@student.edu',     2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(16, '30100016','Parker',     'Luna',      '2009-07-28', '47 Cedarwood Ln',  '555-3016', 'l.parker@student.edu',     2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(17, '30100017','Quinn',      'Sebastian', '2010-04-16', '12 Ironwood Ave',  '555-3017', 's.quinn@student.edu',      2, '2025-03-01', 1, '2026-03-01 08:00:00'),
(18, '30100018','Roberts',    'Grace',     '2009-11-02', '38 Baywood Blvd',  '555-3018', 'g.roberts@student.edu',    2, '2025-03-01', 1, '2026-03-01 08:00:00'),
-- Course 3 (3rd Year A) — 8 students
(19, '30100019','Smith',      'Michael',   '2009-02-23', '4 Poplar St',      '555-3019', 'mi.smith@student.edu',     3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(20, '30100020','Taylor',     'Chloe',     '2008-09-10', '21 Oakdale Rd',    '555-3020', 'c.taylor@student.edu',     3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(21, '30100021','Underwood',  'Daniel',    '2009-05-27', '6 Northgate Dr',   '555-3021', 'd.underwood@student.edu',  3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(22, '30100022','Vega',       'Penelope',  '2008-12-04', '15 Southpark Ct',  '555-3022', 'p.vega@student.edu',       3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(23, '30100023','Wallace',    'Jack',      '2009-08-18', '30 Westfield Ave', '555-3023', 'j.wallace@student.edu',    3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(24, '30100024','Xavier',     'Lily',      '2008-03-31', '7 Eastview Blvd',  '555-3024', 'li.xavier@student.edu',    3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(25, '30100025','Young',      'Owen',      '2009-01-14', '52 Central St',    '555-3025', 'o.young@student.edu',      3, '2024-03-01', 1, '2026-03-01 08:00:00'),
(26, '30100026','Zimmerman',  'Aria',      '2008-06-22', '19 Midtown Ave',   '555-3026', 'a.zimmerman@student.edu',  3, '2024-03-01', 1, '2026-03-01 08:00:00'),
-- Course 4 (4th Year B) — 8 students
(27, '30100027','Allen',      'Lucas',     '2008-10-08', '3 Northview Dr',   '555-3027', 'lu.allen@student.edu',     4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(28, '30100028','Bennett',    'Ella',      '2007-02-19', '14 Westpark Ln',   '555-3028', 'el.bennett@student.edu',   4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(29, '30100029','Collins',    'Aiden',     '2008-07-05', '27 Eastwood Rd',   '555-3029', 'ai.collins@student.edu',   4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(30, '30100030','Dixon',      'Scarlett',  '2007-11-29', '8 Southridge Ct',  '555-3030', 'sc.dixon@student.edu',     4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(31, '30100031','Evans',      'Jackson',   '2008-04-13', '41 Creekside Ave', '555-3031', 'ja.evans@student.edu',     4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(32, '30100032','Fischer',    'Madison',   '2007-08-26', '5 Riverside Blvd', '555-3032', 'ma.fischer@student.edu',   4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(33, '30100033','Gomez',      'Logan',     '2008-01-09', '32 Hillcrest Dr',  '555-3033', 'lo.gomez@student.edu',     4, '2023-03-01', 1, '2026-03-01 08:00:00'),
(34, '30100034','Hughes',     'Zoe',       '2007-05-17', '17 Lakeshore Rd',  '555-3034', 'zo.hughes@student.edu',    4, '2023-03-01', 1, '2026-03-01 08:00:00'),
-- Course 5 (5th Year B) — 8 students
(35, '30100035','Irons',      'Carter',    '2007-09-04', '25 Parkview Ave',  '555-3035', 'ca.irons@student.edu',     5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(36, '30100036','Jensen',     'Abigail',   '2006-12-21', '9 Glenwood St',    '555-3036', 'ab.jensen@student.edu',    5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(37, '30100037','Kim',        'Elijah',    '2007-04-07', '36 Birchwood Dr',  '555-3037', 'el.kim@student.edu',       5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(38, '30100038','Lawson',     'Hannah',    '2006-10-15', '11 Maplewood Ln',  '555-3038', 'ha.lawson@student.edu',    5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(39, '30100039','Moore',      'Levi',      '2007-06-28', '43 Ridgeway Ave',  '555-3039', 'le.moore@student.edu',     5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(40, '30100040','Nash',       'Nora',      '2006-03-11', '6 Fernwood Ct',    '555-3040', 'no.nash@student.edu',      5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(41, '30100041','Owen',       'Matthew',   '2007-01-25', '28 Elmwood Blvd',  '555-3041', 'ma.owen@student.edu',      5, '2022-03-01', 1, '2026-03-01 08:00:00'),
(42, '30100042','Price',      'Victoria',  '2006-08-03', '13 Sycamore Rd',   '555-3042', 'vi.price@student.edu',     5, '2022-03-01', 1, '2026-03-01 08:00:00');

-- =============================================================
-- 14. GRADES (notas) — 3 bimesters × key subjects per student
-- Bimestre 1 = Spring, Bimestre 2 = Fall, Bimestre 3 = Finals
-- =============================================================
DELETE FROM `notas` WHERE school_year = 2026;

INSERT INTO `notas` (`estudiante_id`, `materia_id`, `profesor_id`, `calificacion`, `bimestre`, `evaluation_context`, `school_year`, `tipo_evaluacion`, `fecha`, `observaciones`) VALUES
-- ---- COURSE 1: Math (materia 1), Language (2), History (5) ----
-- Student 1 Adams Oliver
(1,1,1, 7.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(1,1,1, 8.00,2,'regular',2026,'parcial','2026-06-20',NULL),
(1,1,1, 8.50,3,'regular',2026,'examen','2026-09-10',NULL),
(1,2,2, 9.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(1,2,2, 8.50,2,'regular',2026,'parcial','2026-06-22',NULL),
(1,2,2, 9.00,3,'regular',2026,'examen','2026-09-12',NULL),
(1,5,6, 8.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(1,5,6, 7.50,2,'regular',2026,'parcial','2026-06-23',NULL),
-- Student 2 Baker Emma
(2,1,1, 9.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(2,1,1, 9.00,2,'regular',2026,'parcial','2026-06-20',NULL),
(2,1,1, 9.50,3,'regular',2026,'examen','2026-09-10','Excellent performance'),
(2,2,2, 8.50,1,'regular',2026,'parcial','2026-04-17',NULL),
(2,2,2, 9.00,2,'regular',2026,'parcial','2026-06-22',NULL),
(2,5,6, 9.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(2,5,6, 8.50,2,'regular',2026,'parcial','2026-06-23',NULL),
-- Student 3 Carter Noah
(3,1,1, 6.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(3,1,1, 6.50,2,'regular',2026,'parcial','2026-06-20',NULL),
(3,1,1, 7.00,3,'regular',2026,'examen','2026-09-10',NULL),
(3,2,2, 7.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(3,2,2, 6.50,2,'regular',2026,'parcial','2026-06-22',NULL),
(3,5,6, 7.50,1,'regular',2026,'parcial','2026-04-18',NULL),
-- Student 4 Davis Ava
(4,1,1, 5.00,1,'regular',2026,'parcial','2026-04-15','Needs extra support'),
(4,1,1, 5.50,2,'regular',2026,'parcial','2026-06-20',NULL),
(4,1,1, 6.00,3,'regular',2026,'examen','2026-09-10',NULL),
(4,2,2, 7.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(4,2,2, 7.50,2,'regular',2026,'parcial','2026-06-22',NULL),
(4,5,6, 6.00,1,'regular',2026,'parcial','2026-04-18',NULL),
-- Students 5-9 (Course 1) simplified
(5,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(5,1,1, 8.50,2,'regular',2026,'parcial','2026-06-20',NULL),
(5,2,2, 7.50,1,'regular',2026,'parcial','2026-04-17',NULL),
(6,1,1, 7.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(6,2,2, 8.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(6,5,6, 7.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(7,1,1, 9.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(7,1,1, 8.50,2,'regular',2026,'parcial','2026-06-20',NULL),
(7,2,2, 9.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(8,1,1, 6.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(8,2,2, 7.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(9,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(9,2,2, 8.50,1,'regular',2026,'parcial','2026-04-17',NULL),
-- ---- COURSE 2: Math(1), Physics(3), Language(2) ----
(10,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(10,1,1, 7.50,2,'regular',2026,'parcial','2026-06-20',NULL),
(10,3,3, 7.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(10,2,2, 8.00,1,'regular',2026,'parcial','2026-04-17',NULL),
(11,1,1, 9.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(11,1,1, 9.50,2,'regular',2026,'parcial','2026-06-20','Outstanding'),
(11,3,3, 9.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(11,2,2, 8.50,1,'regular',2026,'parcial','2026-04-17',NULL),
(12,1,1, 7.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(12,3,3, 6.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(12,2,2, 7.50,1,'regular',2026,'parcial','2026-04-17',NULL),
(13,1,1, 8.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(13,3,3, 8.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(14,1,1, 6.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(14,3,3, 5.50,1,'regular',2026,'parcial','2026-04-16','Struggling with equations'),
(15,1,1, 7.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(15,3,3, 7.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(16,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(16,3,3, 8.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(17,1,1, 9.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(17,3,3, 8.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(18,1,1, 7.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(18,3,3, 6.00,1,'regular',2026,'parcial','2026-04-16',NULL),
-- ---- COURSE 3: Math(1), Physics(3), Programming(8) ----
(19,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(19,3,3, 7.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(19,8,4, 9.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(19,8,4, 9.50,2,'regular',2026,'parcial','2026-06-23','Excellent project work'),
(20,1,1, 7.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(20,3,3, 6.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(20,8,4, 7.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(21,1,1, 9.50,1,'regular',2026,'parcial','2026-04-15','Top student'),
(21,3,3, 9.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(21,8,4, 8.50,1,'regular',2026,'parcial','2026-04-18',NULL),
(22,1,1, 6.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(22,8,4, 7.50,1,'regular',2026,'parcial','2026-04-18',NULL),
(23,1,1, 8.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(23,8,4, 8.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(24,1,1, 7.50,1,'regular',2026,'parcial','2026-04-15',NULL),
(24,8,4, 6.50,1,'regular',2026,'parcial','2026-04-18',NULL),
(25,1,1, 9.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(25,8,4, 9.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(26,1,1, 7.00,1,'regular',2026,'parcial','2026-04-15',NULL),
(26,8,4, 7.00,1,'regular',2026,'parcial','2026-04-18',NULL),
-- ---- COURSE 4: Programming(8), Databases(9), Physics(3) ----
(27,8,4, 9.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(27,9,4, 8.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(27,3,3, 7.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(28,8,4, 7.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(28,9,4, 7.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(28,3,3, 8.00,1,'regular',2026,'parcial','2026-04-16',NULL),
(29,8,4, 8.50,1,'regular',2026,'parcial','2026-04-18',NULL),
(29,9,4, 9.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(29,3,3, 8.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(30,8,4, 6.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(30,9,4, 6.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(31,8,4, 7.50,1,'regular',2026,'parcial','2026-04-18',NULL),
(31,9,4, 8.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(32,8,4, 8.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(32,9,4, 7.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(33,8,4, 9.50,1,'regular',2026,'parcial','2026-04-18','Excellent developer'),
(33,9,4, 9.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(34,8,4, 7.00,1,'regular',2026,'parcial','2026-04-18',NULL),
(34,9,4, 7.50,1,'regular',2026,'parcial','2026-04-19',NULL),
-- ---- COURSE 5: Databases(9), Networks(10), Physics(3) ----
(35,9,4,  8.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(35,10,7, 9.00,1,'regular',2026,'parcial','2026-04-20',NULL),
(35,3,3,  7.50,1,'regular',2026,'parcial','2026-04-16',NULL),
(36,9,4,  7.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(36,10,7, 7.50,1,'regular',2026,'parcial','2026-04-20',NULL),
(37,9,4,  9.50,1,'regular',2026,'parcial','2026-04-19','Outstanding'),
(37,10,7, 9.00,1,'regular',2026,'parcial','2026-04-20',NULL),
(38,9,4,  6.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(38,10,7, 6.00,1,'regular',2026,'parcial','2026-04-20',NULL),
(39,9,4,  8.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(39,10,7, 8.50,1,'regular',2026,'parcial','2026-04-20',NULL),
(40,9,4,  7.50,1,'regular',2026,'parcial','2026-04-19',NULL),
(40,10,7, 7.00,1,'regular',2026,'parcial','2026-04-20',NULL),
(41,9,4,  8.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(41,10,7, 8.50,1,'regular',2026,'parcial','2026-04-20',NULL),
(42,9,4,  9.00,1,'regular',2026,'parcial','2026-04-19',NULL),
(42,10,7, 9.50,1,'regular',2026,'parcial','2026-04-20','Best in class');

-- =============================================================
-- 15. ATTENDANCE (asistencia_virtual) — last 30 days
-- Using only Course 1 & 2 for a realistic sample
-- =============================================================
DELETE FROM `asistencia_virtual` WHERE DATE(fecha) >= '2026-07-01' AND registrado_por IN (1,2,3,4,5,6,7);

INSERT INTO `asistencia_virtual` (`estudiante_id`, `curso_id`, `materia_id`, `fecha`, `estado`, `observacion`, `registrado_por`) VALUES
-- Week of July 7
(1,1,1,'2026-07-07','Presente',NULL,1),(2,1,1,'2026-07-07','Presente',NULL,1),(3,1,1,'2026-07-07','Presente',NULL,1),
(4,1,1,'2026-07-07','Ausente','No notification received',1),(5,1,1,'2026-07-07','Presente',NULL,1),
(6,1,1,'2026-07-07','Presente',NULL,1),(7,1,1,'2026-07-07','Tardanza',NULL,1),(8,1,1,'2026-07-07','Presente',NULL,1),(9,1,1,'2026-07-07','Presente',NULL,1),
-- Week of July 8
(1,1,2,'2026-07-08','Presente',NULL,2),(2,1,2,'2026-07-08','Presente',NULL,2),(3,1,2,'2026-07-08','Ausente justificado','Medical certificate',2),
(4,1,2,'2026-07-08','Presente',NULL,2),(5,1,2,'2026-07-08','Presente',NULL,2),(6,1,2,'2026-07-08','Presente',NULL,2),
(7,1,2,'2026-07-08','Presente',NULL,2),(8,1,2,'2026-07-08','Media falta',NULL,2),(9,1,2,'2026-07-08','Presente',NULL,2),
-- Week of July 14
(10,2,1,'2026-07-14','Presente',NULL,1),(11,2,1,'2026-07-14','Presente',NULL,1),(12,2,1,'2026-07-14','Presente',NULL,1),
(13,2,1,'2026-07-14','Ausente',NULL,1),(14,2,1,'2026-07-14','Presente',NULL,1),(15,2,1,'2026-07-14','Presente',NULL,1),
(16,2,1,'2026-07-14','Tardanza','Arrived 10 minutes late',1),(17,2,1,'2026-07-14','Presente',NULL,1),(18,2,1,'2026-07-14','Presente',NULL,1),
-- Week of July 15
(10,2,3,'2026-07-15','Presente',NULL,3),(11,2,3,'2026-07-15','Presente',NULL,3),(12,2,3,'2026-07-15','Presente',NULL,3),
(13,2,3,'2026-07-15','Ausente',NULL,3),(14,2,3,'2026-07-15','Presente',NULL,3),(15,2,3,'2026-07-15','Presente',NULL,3),
(16,2,3,'2026-07-15','Presente',NULL,3),(17,2,3,'2026-07-15','Presente',NULL,3),(18,2,3,'2026-07-15','Ausente justificado','Family emergency',3),
-- Week of July 21
(1,1,1,'2026-07-21','Presente',NULL,1),(2,1,1,'2026-07-21','Presente',NULL,1),(3,1,1,'2026-07-21','Presente',NULL,1),
(4,1,1,'2026-07-21','Presente',NULL,1),(5,1,1,'2026-07-21','Ausente',NULL,1),(6,1,1,'2026-07-21','Presente',NULL,1),
(7,1,1,'2026-07-21','Presente',NULL,1),(8,1,1,'2026-07-21','Presente',NULL,1),(9,1,1,'2026-07-21','Presente',NULL,1),
-- Week of July 22
(19,3,8,'2026-07-22','Presente',NULL,4),(20,3,8,'2026-07-22','Presente',NULL,4),(21,3,8,'2026-07-22','Presente',NULL,4),
(22,3,8,'2026-07-22','Tardanza','Bus delay',4),(23,3,8,'2026-07-22','Presente',NULL,4),(24,3,8,'2026-07-22','Presente',NULL,4),
(25,3,8,'2026-07-22','Presente',NULL,4),(26,3,8,'2026-07-22','Ausente',NULL,4);

-- =============================================================
-- 16. DISCIPLINARY RECORDS (llamados_atencion)
-- =============================================================
DELETE FROM `llamados_atencion` WHERE id BETWEEN 1 AND 10;
INSERT INTO `llamados_atencion` (`id`, `estudiante_id`, `usuario_id`, `fecha`, `motivo`, `sancion`, `observaciones`) VALUES
(1, 4,  1, '2026-04-10', 'Repeated failure to bring required materials to class', 'Written warning', 'Parent was notified by phone'),
(2, 13, 6, '2026-04-22', 'Disruptive behavior during Physics lab', 'Detention — 1 hour after school', 'Incident log attached'),
(3, 30, 5, '2026-05-05', 'Three consecutive unexcused absences without notification', 'Suspension warning', 'Family meeting scheduled for May 8'),
(4, 7,  1, '2026-05-18', 'Use of mobile phone during examination', 'Grade reduction on the affected test', 'Exam invalidated as per school policy'),
(5, 22, 6, '2026-06-03', 'Inappropriate language directed at a classmate', 'Apology letter required + 2 lunchtime detentions', 'Counselor referral recommended');

-- =============================================================
-- 17. TIMETABLE / SCHEDULE (horarios)
-- =============================================================
DELETE FROM `horarios` WHERE id BETWEEN 1 AND 50;
INSERT INTO `horarios` (`id`, `curso_id`, `materia_id`, `profesor_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`, `activo`) VALUES
-- Course 1 schedule
(1,  1, 1,  1, 'lunes',    '07:30:00', '08:30:00', '101', 1),
(2,  1, 2,  2, 'lunes',    '08:30:00', '09:30:00', '101', 1),
(3,  1, 5,  6, 'martes',   '07:30:00', '08:30:00', '101', 1),
(4,  1, 6,  6, 'martes',   '08:30:00', '09:30:00', '101', 1),
(5,  1, 11, 2, 'miercoles','07:30:00', '08:30:00', '101', 1),
(6,  1, 12, 6, 'jueves',   '07:30:00', '08:30:00', 'GYM', 1),
(7,  1, 1,  1, 'viernes',  '07:30:00', '08:30:00', '101', 1),
-- Course 2 schedule
(8,  2, 1,  1, 'lunes',    '09:30:00', '10:30:00', '102', 1),
(9,  2, 3,  3, 'lunes',    '10:30:00', '11:30:00', '102', 1),
(10, 2, 4,  5, 'martes',   '09:30:00', '10:30:00', '102', 1),
(11, 2, 2,  2, 'miercoles','09:30:00', '10:30:00', '102', 1),
(12, 2, 7,  5, 'jueves',   '09:30:00', '10:30:00', 'LAB', 1),
-- Course 3 schedule
(13, 3, 8,  4, 'lunes',    '07:30:00', '09:30:00', 'PC1', 1),
(14, 3, 3,  3, 'martes',   '07:30:00', '08:30:00', '201', 1),
(15, 3, 13, 5, 'miercoles','07:30:00', '09:30:00', 'WRK', 1),
(16, 3, 1,  1, 'jueves',   '07:30:00', '08:30:00', '201', 1),
-- Course 4 schedule
(17, 4, 8,  4, 'lunes',    '13:00:00', '15:00:00', 'PC2', 1),
(18, 4, 9,  4, 'martes',   '13:00:00', '15:00:00', 'PC2', 1),
(19, 4, 3,  3, 'miercoles','13:00:00', '14:00:00', '301', 1),
(20, 4, 14, 7, 'jueves',   '13:00:00', '15:00:00', '302', 1),
-- Course 5 schedule
(21, 5, 10, 7, 'lunes',    '15:00:00', '17:00:00', 'PC2', 1),
(22, 5, 9,  4, 'martes',   '15:00:00', '17:00:00', 'PC2', 1),
(23, 5, 3,  3, 'miercoles','15:00:00', '16:00:00', '301', 1),
(24, 5, 14, 7, 'jueves',   '15:00:00', '17:00:00', '302', 1);

-- =============================================================
-- 18. RESPONSABLES (guardians) — for Family Portal demo
-- =============================================================
DELETE FROM `responsables` WHERE id BETWEEN 1 AND 10;
INSERT INTO `responsables` (`id`, `estudiante_id`, `nombre`, `apellido`, `dni`, `parentesco`, `telefono_celular`, `email`, `es_contacto_emergencia`) VALUES
(1, 1,  'Robert',  'Adams',   '10100001', 'Father', '555-4001', 'r.adams@email.com',   1),
(2, 2,  'Susan',   'Baker',   '10100002', 'Mother', '555-4002', 's.baker@email.com',   1),
(3, 10, 'Thomas',  'Jackson', '10100003', 'Father', '555-4003', 't.jackson@email.com', 1),
(4, 19, 'Jennifer','Smith',   '10100004', 'Mother', '555-4004', 'j.smith@email.com',   1),
(5, 27, 'David',   'Allen',   '10100005', 'Father', '555-4005', 'd.allen@email.com',   1),
(6, 35, 'Patricia','Irons',   '10100006', 'Mother', '555-4006', 'p.irons@email.com',   1);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- =============================================================
-- SUMMARY
-- =============================================================
-- School: Greenfield Academy (fictional)
-- Users seeded:
--   admin@escuela.edu     / admin123   (Admin)
--   director@greenfield.edu / admin123 (Principal/Director)
--   preceptor@greenfield.edu/ admin123 (Homeroom Teacher)
--   secretary@greenfield.edu/ admin123 (Secretary)
--   p.williams@greenfield.edu/ admin123(Teacher)
--   l.martinez@greenfield.edu/ admin123(Teacher)
-- =============================================================
