<?php
declare(strict_types=1);

/**
 * Script de Llenado de Datos (Database Seeder) para SistemaAdmin
 * 
 * Este script limpia la base de datos y genera una gran cantidad de datos
 * realistas (alumnos, materias, notas, asistencias, horarios, profesores y preceptores)
 * para realizar pruebas de carga, rendimiento y funcionalidad general.
 * 
 * Uso desde consola:
 *   php llenar_datos.php
 */

// Evitar timeout y límites de memoria
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

echo "====================================================\n";
echo "🚀 INICIANDO LLENADO DE DATOS DE PRUEBA (SEEDER)\n";
echo "====================================================\n\n";

try {
    // 1. Cargar dependencias de base de datos
    require_once __DIR__ . '/includes/database_bootstrap.php';
    $db = sistema_admin_db_adapter();
    $conn = $db->getPdo();

    // 2. Definir cuentas de usuario originales a preservar/restaurar
    // Estos son los usuarios que estaban originalmente en el sistema
    $usuariosOriginales = [
        [
            'id' => 2,
            'dni' => '12345678',
            'apellido' => 'García',
            'nombre' => 'María Elena',
            'email' => 'director@escuela-demo.edu',
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=1$VkFPamd6OHBBcDhDSnlKSQ$gu2SKCr5VSzmYizkeTLUhT8SJ7mSy3h1uSku5J/PHN4', // admin123 o similar
            'must_change_password' => 1,
            'rol' => 'directivo',
            'activo' => 1,
            'ultimo_acceso' => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2025-09-29 04:18:31',
            'actualizado_en' => '2025-09-29 04:18:31',
        ],
        [
            'id' => 3,
            'dni' => '87654321',
            'apellido' => 'López',
            'nombre' => 'Carlos Alberto',
            'email' => 'preceptor@escuela-demo.edu',
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=1$VkFPamd6OHBBcDhDSnlKSQ$gu2SKCr5VSzmYizkeTLUhT8SJ7mSy3h1uSku5J/PHN4',
            'must_change_password' => 1,
            'rol' => 'preceptor',
            'activo' => 1,
            'ultimo_acceso' => '2025-11-12 17:30:12',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2025-09-29 04:18:31',
            'actualizado_en' => '2025-11-12 17:30:12',
        ],
        [
            'id' => 4,
            'dni' => '11223344',
            'apellido' => 'Martínez',
            'nombre' => 'Ana Beatriz',
            'email' => 'secretario@escuela-demo.edu',
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=1$VkFPamd6OHBBcDhDSnlKSQ$gu2SKCr5VSzmYizkeTLUhT8SJ7mSy3h1uSku5J/PHN4',
            'must_change_password' => 1,
            'rol' => 'secretario',
            'activo' => 1,
            'ultimo_acceso' => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2025-09-29 04:18:31',
            'actualizado_en' => '2025-09-29 04:18:31',
        ],
        [
            'id' => 9,
            'dni' => 'secretario3',
            'apellido' => 'Martínez',
            'nombre' => 'Alan Ezequielzxz',
            'email' => 'lucas.acosta@email.com',
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=1$YkFWTVhOSjFPMTAuWmU5UA$t37o5zfUdgUhWY0cuXFKqnJuQStpZh3B/VEslT9MSL4',
            'must_change_password' => 1,
            'rol' => 'secretario',
            'activo' => 0,
            'ultimo_acceso' => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2025-11-13 00:43:19',
            'actualizado_en' => '2025-11-13 00:43:33',
        ],
        [
            'id' => 21,
            'dni' => '48678088',
            'apellido' => 'Martínez',
            'nombre' => 'Alan Ezequiel',
            'email' => 'alanme317@gmail.com',
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=3$TUEvUUlTUXdRZXZzSld0Tg$6F4udllfAGIS8cBxAXs+DddEpZNnZsX3AEbjr3Aj5nc',
            'must_change_password' => 1,
            'rol' => 'profesor',
            'activo' => 1,
            'ultimo_acceso' => '2026-03-29 11:45:20',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2026-03-29 02:12:14',
            'actualizado_en' => '2026-03-29 11:45:20',
        ],
        [
            'id' => 22,
            'dni' => 'Elivaanperejil#software',
            'apellido' => 'Admin',
            'nombre' => 'Usuario 1',
            'email' => null,
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=1$aUFHZkhpdjBvaG53Tm8zLw$GNcfXHki6wf9QH2XJ6vE4clfli9FzDcnuLULi44OhkI',
            'must_change_password' => 1,
            'rol' => 'admin',
            'activo' => 1,
            'ultimo_acceso' => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2026-05-02 17:21:51',
            'actualizado_en' => '2026-05-02 17:24:13',
        ],
        [
            'id' => 23,
            'dni' => 'alandesalojasistema#tripode',
            'apellido' => 'Admin',
            'nombre' => 'Usuario 2',
            'email' => null,
            'telefono' => null,
            'password_hash' => '$argon2id$v=19$m=65536,t=4,p=3$TWVhSklWakFoc0dVU0xMSQ$FoaKhYFoCDpOBsA/H7DS0+8ha0lITetnmpGqQaDMfT8',
            'must_change_password' => 0,
            'rol' => 'admin',
            'activo' => 1,
            'ultimo_acceso' => '2026-05-25 23:24:28',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'creado_en' => '2026-05-02 17:21:51',
            'actualizado_en' => '2026-05-25 23:24:28',
        ]
    ];

    // 3. Limpiar base de datos (con reseteo de FKs)
    echo "⚠️  Vaciando tablas existentes...\n";
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $tablasALimpiar = [
        'notas', 'asistencia_virtual', 'horarios', 'profesor_materia', 
        'profesor_curso', 'preceptor_curso', 'materia_curso', 'estudiantes', 
        'profesores', 'equipo_directivo', 'usuarios', 'cursos', 'materias', 
        'especialidades', 'turnos', 'contactos_emergencia', 'responsables'
    ];
    foreach ($tablasALimpiar as $tabla) {
        $conn->exec("TRUNCATE TABLE `$tabla` ;");
    }
    
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "✅  Tablas vaciadas.\n\n";

    // 4. Pre-generar Hash para los nuevos usuarios de prueba (password: "clave123")
    echo "🔑  Generando hash de contraseña base para los nuevos usuarios...\n";
    $hashComun = password_hash('clave123', PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
    echo "✅  Hash generado.\n\n";

    // 5. Cargar Especialidades y Turnos base
    echo "⚙️  Creando Especialidades y Turnos...\n";
    $db->query("INSERT INTO especialidades (id, nombre, codigo, descripcion, activa) VALUES 
        (1, 'Técnico en Informática', 'INF', 'Especialización en programación y sistemas', 1),
        (2, 'Técnico en Electromecánica', 'EMC', 'Especialización en mecánica y electricidad', 1),
        (3, 'Técnico en Construcciones', 'CON', 'Especialización en construcción civil', 1)");

    $db->query("INSERT INTO turnos (id, nombre, hora_inicio, hora_fin, activo) VALUES 
        (1, 'Mañana', '08:00:00', '12:00:00', 1),
        (2, 'Tarde', '13:00:00', '17:00:00', 1),
        (3, 'Vespertino', '18:00:00', '22:00:00', 1)");
    echo "✅  Especialidades y Turnos listos.\n\n";

    // 6. Restaurar usuarios administrativos originales
    echo "👤  Restaurando cuentas administrativas originales...\n";
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (id, dni, apellido, nombre, email, telefono, password_hash, must_change_password, rol, activo, creado_en, actualizado_en)
        VALUES (:id, :dni, :apellido, :nombre, :email, :telefono, :password_hash, :must_change_password, :rol, :activo, :creado_en, :actualizado_en)
    ");
    foreach ($usuariosOriginales as $u) {
        $stmtUser->execute([
            ':id' => $u['id'],
            ':dni' => $u['dni'],
            ':apellido' => $u['apellido'],
            ':nombre' => $u['nombre'],
            ':email' => $u['email'],
            ':telefono' => $u['telefono'],
            ':password_hash' => $u['password_hash'],
            ':must_change_password' => $u['must_change_password'],
            ':rol' => $u['rol'],
            ':activo' => $u['activo'],
            ':creado_en' => $u['creado_en'],
            ':actualizado_en' => $u['actualizado_en'],
        ]);
    }
    
    // Restaurar perfiles en equipo_directivo y profesores de los originales si corresponde
    // María Elena García (id 2) -> directivo (Director)
    $db->query("INSERT INTO equipo_directivo (usuario_id, curso_id, apellido, nombre, cargo, activo) VALUES 
        (2, NULL, 'García', 'María Elena', 'director', 1)");
    // Carlos Alberto López (id 3) -> preceptor
    $db->query("INSERT INTO equipo_directivo (usuario_id, curso_id, apellido, nombre, cargo, activo) VALUES 
        (3, NULL, 'López', 'Carlos Alberto', 'preceptor', 1)");
    $idPreceptorCarlos = (int)$db->lastInsertId();
    // Alan Ezequiel Martínez (id 21) -> profesor
    $db->query("INSERT INTO profesores (dni, apellido, nombre, email, activo) VALUES 
        ('48678088', 'Martínez', 'Alan Ezequiel', 'alanme317@gmail.com', 1)");
    $idProfesorAlan = (int)$db->lastInsertId();

    echo "✅  Cuentas originales restauradas.\n\n";

    // Arrays de datos para generación aleatoria
    $apellidos = ['González', 'Rodríguez', 'Gómez', 'Fernández', 'López', 'Díaz', 'Martínez', 'Pérez', 'Romero', 'Sánchez', 'Álvarez', 'Ruiz', 'Torres', 'Ramírez', 'Flores', 'Acosta', 'Medina', 'Herrera', 'Aguirre', 'Guzmán', 'Castro', 'Giménez', 'Silva', 'Pereyra', 'Sosa', 'Benítez', 'Mendoza', 'Correa', 'Cáceres', 'Ríos'];
    $nombresM = ['Juan', 'Carlos', 'Pedro', 'Luis', 'Miguel', 'Jorge', 'Alberto', 'Alejandro', 'José', 'Fernando', 'Diego', 'Gabriel', 'Lucas', 'Matías', 'Nicolás', 'Santiago', 'Tomás', 'Martín', 'Cristian', 'Sebastián', 'Federico', 'Gonzalo', 'Enzo', 'Joaquín', 'Bautista'];
    $nombresF = ['María', 'Ana', 'Laura', 'Sofía', 'Camila', 'Valentina', 'Isabella', 'Martina', 'Lucía', 'Mariana', 'Elena', 'Beatriz', 'Claudia', 'Patricia', 'Gabriela', 'Natalia', 'Florencia', 'Andrea', 'Victoria', 'Julia', 'Micaela', 'Luciana', 'Rocío', 'Delfina', 'Julieta'];

    // Funciones helper
    $generarDni = function() {
        return (string)rand(30000000, 49999999);
    };
    $generarNombreCompleto = function() use ($apellidos, $nombresM, $nombresF) {
        $ape = $apellidos[array_rand($apellidos)];
        $esM = rand(0, 1) === 1;
        $nom = $esM ? $nombresM[array_rand($nombresM)] : $nombresF[array_rand($nombresF)];
        return ['nombre' => $nom, 'apellido' => $ape, 'sexo' => $esM ? 'M' : 'F'];
    };

    // 7. Generar más Preceptores y Profesores (Usuarios y sus perfiles)
    echo "👥  Generando personal docente y no docente de prueba...\n";
    
    // Generar 8 Preceptores adicionales
    $preceptoresIds = [$idPreceptorCarlos]; // Agregar el original
    for ($i = 0; $i < 8; $i++) {
        $pers = $generarNombreCompleto();
        $dni = $generarDni();
        $email = strtolower($pers['nombre'] . '.' . $pers['apellido'] . '@escuela-demo.edu');
        
        $db->query("
            INSERT INTO usuarios (dni, apellido, nombre, email, password_hash, must_change_password, rol, activo)
            VALUES (?, ?, ?, ?, ?, 0, 'preceptor', 1)
        ", [$dni, $pers['apellido'], $pers['nombre'], $email, $hashComun]);
        
        $userId = (int)$db->lastInsertId();
        
        $db->query("
            INSERT INTO equipo_directivo (usuario_id, curso_id, apellido, nombre, cargo, activo)
            VALUES (?, NULL, ?, ?, 'preceptor', 1)
        ", [$userId, $pers['apellido'], $pers['nombre']]);
        
        $preceptoresIds[] = (int)$db->lastInsertId();
    }
    echo "   - Creados " . (count($preceptoresIds) - 1) . " preceptores de prueba.\n";

    // Generar 20 Profesores adicionales
    $profesoresIds = [$idProfesorAlan]; // Agregar el original
    for ($i = 0; $i < 20; $i++) {
        $pers = $generarNombreCompleto();
        $dni = $generarDni();
        $email = strtolower($pers['nombre'] . '.' . $pers['apellido'] . '@email.com');
        
        $db->query("
            INSERT INTO usuarios (dni, apellido, nombre, email, password_hash, must_change_password, rol, activo)
            VALUES (?, ?, ?, ?, ?, 0, 'profesor', 1)
        ", [$dni, $pers['apellido'], $pers['nombre'], $email, $hashComun]);
        
        $userId = (int)$db->lastInsertId();
        
        $db->query("
            INSERT INTO profesores (dni, apellido, nombre, email, activo)
            VALUES (?, ?, ?, ?, 1)
        ", [$dni, $pers['apellido'], $pers['nombre'], $email]);
        
        $profesoresIds[] = (int)$db->lastInsertId();
    }
    echo "   - Creados " . (count($profesoresIds) - 1) . " profesores de prueba.\n\n";

    // 8. Crear Cursos
    echo "🏫  Creando Cursos...\n";
    // Formato de cursos:
    // Ciclo Básico: 1° a 3° año, sin especialidad
    // Ciclo Superior: 4° a 7° año, especialidad Informática (1), Electromecánica (2) o Construcciones (3)
    $cursosCreados = [];
    $cursosEstructura = [
        // Ciclo Básico
        ['anio' => 1, 'div' => '1', 'esp' => null, 'turno' => 1],
        ['anio' => 1, 'div' => '2', 'esp' => null, 'turno' => 2],
        ['anio' => 2, 'div' => '1', 'esp' => null, 'turno' => 1],
        ['anio' => 2, 'div' => '2', 'esp' => null, 'turno' => 2],
        ['anio' => 3, 'div' => '1', 'esp' => null, 'turno' => 1],
        ['anio' => 3, 'div' => '2', 'esp' => null, 'turno' => 2],
        
        // Informática (Especialidad 1)
        ['anio' => 4, 'div' => '1', 'esp' => 1, 'turno' => 1],
        ['anio' => 5, 'div' => '1', 'esp' => 1, 'turno' => 1],
        ['anio' => 6, 'div' => '1', 'esp' => 1, 'turno' => 2],
        ['anio' => 7, 'div' => '1', 'esp' => 1, 'turno' => 2],

        // Electromecánica (Especialidad 2)
        ['anio' => 4, 'div' => '2', 'esp' => 2, 'turno' => 1],
        ['anio' => 5, 'div' => '2', 'esp' => 2, 'turno' => 1],
        ['anio' => 6, 'div' => '2', 'esp' => 2, 'turno' => 2],
        ['anio' => 7, 'div' => '2', 'esp' => 2, 'turno' => 2],

        // Construcciones (Especialidad 3)
        ['anio' => 4, 'div' => '3', 'esp' => 3, 'turno' => 1],
        ['anio' => 5, 'div' => '3', 'esp' => 3, 'turno' => 2],
        ['anio' => 6, 'div' => '3', 'esp' => 3, 'turno' => 3],
        ['anio' => 7, 'div' => '3', 'esp' => 3, 'turno' => 3],
    ];

    foreach ($cursosEstructura as $ce) {
        $nombreCurso = $ce['anio'] . "°" . $ce['div'];
        $db->query("
            INSERT INTO cursos (nombre, anio, division, especialidad_id, turno_id, capacidad_maxima, activo)
            VALUES (?, ?, ?, ?, ?, 35, 1)
        ", [$nombreCurso, $ce['anio'], $ce['div'], $ce['esp'], $ce['turno']]);
        
        $cursoId = (int)$db->lastInsertId();
        
        $cursosCreados[] = [
            'id' => $cursoId,
            'anio' => $ce['anio'],
            'division' => $ce['div'],
            'especialidad_id' => $ce['esp'],
            'turno_id' => $ce['turno']
        ];
    }
    echo "   - Creados " . count($cursosCreados) . " cursos (Ciclo Básico y Superior).\n\n";

    // Asignar preceptores a cursos (cada preceptor cuida ~2 cursos)
    echo "📋  Asignando Preceptores a Cursos...\n";
    foreach ($cursosCreados as $idx => $cur) {
        $precId = $preceptoresIds[$idx % count($preceptoresIds)];
        $db->query("
            INSERT INTO preceptor_curso (equipo_directivo_id, curso_id)
            VALUES (?, ?)
        ", [$precId, $cur['id']]);
    }
    echo "✅  Asignaciones completadas.\n\n";

    // 9. Crear Materias
    echo "📚  Creando Materias...\n";
    $materiasDef = [
        // Comunes a todos (Ciclo Básico y Superior)
        ['nombre' => 'Matemática', 'codigo' => 'MAT', 'esp' => null, 'taller' => 0],
        ['nombre' => 'Lengua y Literatura', 'codigo' => 'LEN', 'esp' => null, 'taller' => 0],
        ['nombre' => 'Educación Física', 'codigo' => 'EF', 'esp' => null, 'taller' => 0],
        ['nombre' => 'Inglés', 'codigo' => 'ING', 'esp' => null, 'taller' => 0],
        ['nombre' => 'Historia', 'codigo' => 'HIS', 'esp' => null, 'taller' => 0],
        
        // Ciclo Básico Taller
        ['nombre' => 'Procedimientos Técnicos (Taller)', 'codigo' => 'PT_TAL', 'esp' => null, 'taller' => 1],
        ['nombre' => 'Sistemas Tecnológicos (Taller)', 'codigo' => 'ST_TAL', 'esp' => null, 'taller' => 1],
        ['nombre' => 'Lenguajes Tecnológicos', 'codigo' => 'LT_TAL', 'esp' => null, 'taller' => 1],

        // Informática
        ['nombre' => 'Programación Orientada a Objetos', 'codigo' => 'POO', 'esp' => 1, 'taller' => 0],
        ['nombre' => 'Bases de Datos', 'codigo' => 'BD', 'esp' => 1, 'taller' => 0],
        ['nombre' => 'Diseño de Aplicaciones Web', 'codigo' => 'DAW', 'esp' => 1, 'taller' => 0],
        ['nombre' => 'Redes y Seguridad', 'codigo' => 'RED', 'esp' => 1, 'taller' => 0],
        ['nombre' => 'Sistemas Operativos', 'codigo' => 'SO', 'esp' => 1, 'taller' => 0],

        // Electromecánica
        ['nombre' => 'Electrotecnia General', 'codigo' => 'ELT', 'esp' => 2, 'taller' => 0],
        ['nombre' => 'Sistemas Mecánicos', 'codigo' => 'MEC', 'esp' => 2, 'taller' => 0],
        ['nombre' => 'Automatización y Control', 'codigo' => 'AUT', 'esp' => 2, 'taller' => 0],
        ['nombre' => 'Termodinámica', 'codigo' => 'TER', 'esp' => 2, 'taller' => 0],

        // Construcciones
        ['nombre' => 'Estructuras Civiles', 'codigo' => 'EST', 'esp' => 3, 'taller' => 0],
        ['nombre' => 'Materiales de Construcción', 'codigo' => 'MCO', 'esp' => 3, 'taller' => 0],
        ['nombre' => 'Topografía', 'codigo' => 'TOP', 'esp' => 3, 'taller' => 0],
        ['nombre' => 'Instalaciones Sanitarias', 'codigo' => 'SAN', 'esp' => 3, 'taller' => 0],
    ];

    $materiasCreadas = [];
    foreach ($materiasDef as $md) {
        // Asignar carga horaria aleatoria de 2 a 6 horas semanales
        $carga = rand(2, 6);
        $db->query("
            INSERT INTO materias (nombre, codigo, especialidad_id, anio_materia, carga_horaria, es_taller, activa)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ", [$md['nombre'], $md['codigo'], $md['esp'], rand(1, 7), $carga, $md['taller']]);
        
        $materiaId = (int)$db->lastInsertId();
        
        $materiasCreadas[] = [
            'id' => $materiaId,
            'nombre' => $md['nombre'],
            'especialidad_id' => $md['esp'],
            'es_taller' => $md['taller']
        ];
    }
    echo "   - Creadas " . count($materiasCreadas) . " materias en catálogo.\n\n";

    // 10. Vincular Materias a Cursos (materia_curso) e insertar Docentes
    echo "🔗  Vinculando Materias a Cursos y asignando Profesores...\n";
    $materiaCursoMap = []; // Guardará parejas [materia_curso_id, curso_id, materia_id]
    
    foreach ($cursosCreados as $cur) {
        foreach ($materiasCreadas as $mat) {
            $debeVincular = false;
            
            // Lógica de compatibilidad
            if ($mat['especialidad_id'] === null) {
                // Las materias comunes van a todos los cursos
                // Excepto taller que solo va a ciclo básico (años 1 a 3)
                if ($mat['es_taller'] === 1) {
                    if ($cur['anio'] <= 3) {
                        $debeVincular = true;
                    }
                } else {
                    $debeVincular = true;
                }
            } else {
                // Las materias de especialidad van al curso que tenga la misma especialidad y sea ciclo superior
                if ($mat['especialidad_id'] === $cur['especialidad_id'] && $cur['anio'] >= 4) {
                    $debeVincular = true;
                }
            }

            if ($debeVincular) {
                $db->query("
                    INSERT INTO materia_curso (materia_id, curso_id, activo)
                    VALUES (?, ?, 1)
                ", [$mat['id'], $cur['id']]);
                
                $mcId = (int)$db->lastInsertId();
                $materiaCursoMap[] = [
                    'id' => $mcId,
                    'curso_id' => $cur['id'],
                    'materia_id' => $mat['id']
                ];

                // Asignar un profesor aleatorio a esta materia en este curso
                $profId = $profesoresIds[array_rand($profesoresIds)];
                $db->query("
                    INSERT INTO profesor_materia (profesor_id, materia_id, curso_id, anio_academico, activo)
                    VALUES (?, ?, ?, 2026, 1)
                ", [$profId, $mat['id'], $cur['id']]);

                // Asignar profesor_curso si no existe ya
                $existePC = $db->fetch("
                    SELECT 1 FROM profesor_curso WHERE profesor_id = ? AND curso_id = ?
                ", [$profId, $cur['id']]);
                if (!$existePC) {
                    $db->query("
                        INSERT INTO profesor_curso (profesor_id, curso_id, activo)
                        VALUES (?, ?, 1)
                    ", [$profId, $cur['id']]);
                }
            }
        }
    }
    echo "   - Creados " . count($materiaCursoMap) . " enlaces materia-curso en 'materia_curso'.\n";
    echo "   - Profesores asignados a cursos y materias individuales.\n\n";

    // 11. Crear Estudiantes
    echo "👨‍🎓  Creando Estudiantes...\n";
    $estudiantesCreados = [];
    $gruposTaller = ['A', 'B', 'C', 'D', 'E'];
    
    // Crear alrededor de 250 estudiantes
    $totalEstudiantes = 250;
    
    for ($i = 0; $i < $totalEstudiantes; $i++) {
        $pers = $generarNombreCompleto();
        $dni = $generarDni();
        $dniResp = $generarDni();
        $email = strtolower($pers['nombre'] . '.' . $pers['apellido'] . '@gmail.com');
        
        // Seleccionar curso aleatorio
        $curso = $cursosCreados[array_rand($cursosCreados)];
        
        // Si el curso es Ciclo Básico (1° a 3°), tiene grupo taller. Si es superior, no necesariamente.
        $grupoT = $curso['anio'] <= 3 ? $gruposTaller[array_rand($gruposTaller)] : null;
        
        $fechaNac = date('Y-m-d', strtotime('-' . rand(13, 19) . ' years'));
        
        $db->query("
            INSERT INTO estudiantes (dni, dni_responsable, apellido, nombre, fecha_nacimiento, curso_id, grupo_taller, email, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ", [$dni, $dniResp, $pers['apellido'], $pers['nombre'], $fechaNac, $curso['id'], $grupoT, $email]);
        
        $estId = (int)$db->lastInsertId();
        
        $estudiantesCreados[] = [
            'id' => $estId,
            'curso_id' => $curso['id']
        ];
    }
    echo "   - Creados " . count($estudiantesCreados) . " estudiantes distribuidos en los cursos.\n\n";

    // 12. Crear Notas (Calificaciones)
    echo "📝  Generando Calificaciones (Notas) de prueba...\n";
    $totalNotas = 0;
    
    // Preparar INSERT
    $stmtNota = $conn->prepare("
        INSERT INTO notas (estudiante_id, materia_id, profesor_id, calificacion, bimestre, evaluation_context, school_year, tipo_evaluacion, fecha, observaciones)
        VALUES (:estudiante_id, :materia_id, :profesor_id, :calificacion, :bimestre, 'regular', 2026, :tipo_evaluacion, :fecha, 'Nota autogenerada')
    ");
    
    // Mapear profesores asignados por [curso_id, materia_id] para asociarlos en las notas
    $profesorMap = [];
    $asignacionesProf = $db->fetchAll("SELECT profesor_id, materia_id, curso_id FROM profesor_materia WHERE activo = 1");
    foreach ($asignacionesProf as $ap) {
        $key = $ap['curso_id'] . '_' . $ap['materia_id'];
        $profesorMap[$key] = (int)$ap['profesor_id'];
    }

    // Por cada estudiante, buscar las materias de su curso
    foreach ($estudiantesCreados as $est) {
        $cursoId = $est['curso_id'];
        
        // Encontrar materias vinculadas a este curso
        $materiasDeCurso = [];
        foreach ($materiaCursoMap as $mcm) {
            if ($mcm['curso_id'] === $cursoId) {
                $materiasDeCurso[] = $mcm['materia_id'];
            }
        }
        
        // Crear de 2 a 4 notas por materia
        foreach ($materiasDeCurso as $materiaId) {
            // Obtener profesor asignado
            $pkey = $cursoId . '_' . $materiaId;
            $profId = $profesorMap[$pkey] ?? null;
            
            $numNotas = rand(2, 4);
            for ($n = 0; $n < $numNotas; $n++) {
                // Calificaciones realistas: más aprobados que desaprobados
                $randVal = rand(1, 100);
                if ($randVal <= 10) {
                    $calif = rand(1, 3); // Muy baja
                } elseif ($randVal <= 25) {
                    $calif = rand(4, 5); // Desaprobada
                } elseif ($randVal <= 65) {
                    $calif = rand(6, 8); // Aprobada regular
                } else {
                    $calif = rand(9, 10); // Excelente
                }
                
                // Agregar decimales comunes (.00, .50)
                $decimal = rand(0, 1) === 1 ? 0.50 : 0.00;
                $calif = max(1.00, min(10.00, $calif + $decimal));

                $bimestre = rand(1, 4);
                $tiposEval = ['parcial', 'trabajo_practico', 'examen', 'otro'];
                $tipoEval = $tiposEval[array_rand($tiposEval)];
                
                // Fecha aleatoria de clases en 2026
                $mes = rand(3, 11);
                $dia = rand(1, 28);
                $fecha = sprintf('2026-%02d-%02d', $mes, $dia);

                $stmtNota->execute([
                    ':estudiante_id' => $est['id'],
                    ':materia_id' => $materiaId,
                    ':profesor_id' => $profId,
                    ':calificacion' => $calif,
                    ':bimestre' => $bimestre,
                    ':tipo_evaluacion' => $tipoEval,
                    ':fecha' => $fecha
                ]);
                $totalNotas++;
            }
        }
    }
    echo "   - Creadas " . $totalNotas . " notas/calificaciones en total.\n\n";

    // 13. Crear Asistencias (asistencia_virtual)
    echo "📅  Generando Asistencias de prueba...\n";
    $totalAsistencias = 0;
    
    // Obtener los IDs de usuarios preceptores reales para registrarlos
    $preceptoresUsers = $db->fetchAll("SELECT id FROM usuarios WHERE rol = 'preceptor' AND activo = 1");
    $preceptoresUserIds = array_map(fn($u) => (int)$u['id'], $preceptoresUsers);
    
    if (empty($preceptoresUserIds)) {
        $preceptoresUserIds = [3]; // Fallback Carlos
    }

    $stmtAsistencia = $conn->prepare("
        INSERT INTO asistencia_virtual (estudiante_id, curso_id, materia_id, fecha, estado, observacion, registrado_por)
        VALUES (:estudiante_id, :curso_id, NULL, :fecha, :estado, :observacion, :registrado_por)
    ");

    $estadosAsist = ['Presente', 'Presente', 'Presente', 'Presente', 'Ausente', 'Tardanza', 'Media falta', 'Ausente justificado'];
    
    // Para no tardar demasiado, generamos 5 días de asistencia para cada estudiante
    $diasClase = [
        '2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12', '2026-03-13',
        '2026-04-06', '2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10'
    ];

    foreach ($estudiantesCreados as $est) {
        $cursoId = $est['curso_id'];
        
        // Elegir 5 fechas de asistencia aleatorias de los días de clase
        $fechasSeleccionadas = array_rand(array_flip($diasClase), 5);
        
        foreach ($fechasSeleccionadas as $fecha) {
            $estado = $estadosAsist[array_rand($estadosAsist)];
            $observacion = $estado !== 'Presente' ? 'Registrado en control diario' : null;
            $preceptor = $preceptoresUserIds[array_rand($preceptoresUserIds)];

            $stmtAsistencia->execute([
                ':estudiante_id' => $est['id'],
                ':curso_id' => $cursoId,
                ':fecha' => $fecha,
                ':estado' => $estado,
                ':observacion' => $observacion,
                ':registrado_por' => $preceptor
            ]);
            $totalAsistencias++;
        }
    }
    echo "   - Creados " . $totalAsistencias . " registros de asistencia virtual.\n\n";

    // 14. Crear Horarios
    echo "⏱️  Generando grilla de Horarios escolares...\n";
    $totalHorarios = 0;
    
    $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    
    // Bloques horarios según el turno del curso
    $bloquesHorarios = [
        1 => [ // Mañana
            ['ini' => '08:00:00', 'fin' => '09:30:00'],
            ['ini' => '09:40:00', 'fin' => '11:10:00'],
            ['ini' => '11:20:00', 'fin' => '12:00:00']
        ],
        2 => [ // Tarde
            ['ini' => '13:00:00', 'fin' => '14:30:00'],
            ['ini' => '14:40:00', 'fin' => '16:10:00'],
            ['ini' => '16:20:00', 'fin' => '17:00:00']
        ],
        3 => [ // Vespertino
            ['ini' => '18:00:00', 'fin' => '19:30:00'],
            ['ini' => '19:40:00', 'fin' => '21:10:00'],
            ['ini' => '21:15:00', 'fin' => '22:00:00']
        ]
    ];

    $stmtHorario = $conn->prepare("
        INSERT INTO horarios (curso_id, materia_id, profesor_id, dia_semana, hora_inicio, hora_fin, aula, activo)
        VALUES (:curso_id, :materia_id, :profesor_id, :dia_semana, :hora_inicio, :hora_fin, :aula, 1)
    ");

    foreach ($cursosCreados as $cur) {
        $turnoId = $cur['turno_id'] ?? 1;
        $bloques = $bloquesHorarios[$turnoId] ?? $bloquesHorarios[1];
        
        // Buscar las materias vinculadas a este curso
        $materiasDeCurso = [];
        foreach ($materiaCursoMap as $mcm) {
            if ($mcm['curso_id'] === $cur['id']) {
                $materiasDeCurso[] = $mcm['materia_id'];
            }
        }
        
        // Crear horarios para cada día de la semana
        foreach ($diasSemana as $diaIdx => $diaName) {
            // Asignar de 1 a 2 materias de este curso por día
            $matsDia = (array)array_rand(array_flip($materiasDeCurso), min(count($materiasDeCurso), rand(1, 2)));
            
            foreach ($matsDia as $bIdx => $materiaId) {
                if (isset($bloques[$bIdx])) {
                    $bloque = $bloques[$bIdx];
                    
                    // Buscar profesor asignado
                    $pkey = $cur['id'] . '_' . $materiaId;
                    $profId = $profesorMap[$pkey] ?? null;

                    $aula = 'Aula ' . rand(1, 15);
                    
                    $stmtHorario->execute([
                        ':curso_id' => $cur['id'],
                        ':materia_id' => $materiaId,
                        ':profesor_id' => $profId,
                        ':dia_semana' => $diaName,
                        ':hora_inicio' => $bloque['ini'],
                        ':hora_fin' => $bloque['fin'],
                        ':aula' => $aula
                    ]);
                    $totalHorarios++;
                }
            }
        }
    }
    echo "   - Creados " . $totalHorarios . " turnos de horarios semanales.\n\n";

    echo "====================================================\n";
    echo "🎉 ¡PROCESO DE LLENADO DE DATOS FINALIZADO CON ÉXITO!\n";
    echo "====================================================\n";
    echo "Resumen de Registros Creados:\n";
    echo " - Especialidades: 3\n";
    echo " - Turnos: 3\n";
    echo " - Cursos: " . count($cursosCreados) . "\n";
    echo " - Materias en Catálogo: " . count($materiasCreadas) . "\n";
    echo " - Enlaces Materia-Curso: " . count($materiaCursoMap) . "\n";
    echo " - Usuarios Totales: " . (count($usuariosOriginales) + 8 + 20) . " (7 originales + 28 nuevos de prueba)\n";
    echo " - Estudiantes Totales: " . count($estudiantesCreados) . "\n";
    echo " - Calificaciones (Notas): " . $totalNotas . "\n";
    echo " - Asistencias Diarias: " . $totalAsistencias . "\n";
    echo " - Grilla de Horarios: " . $totalHorarios . "\n";
    echo "\nℹ️  Credenciales de acceso comunes creadas:\n";
    echo " - Contraseña para TODOS los nuevos usuarios: clave123\n";
    echo " - Puedes iniciar sesión con el DNI de cualquier usuario.\n";
    echo "====================================================\n";

} catch (Throwable $e) {
    echo "\n❌ ERROR CRÍTICO DURANTE EL LLENADO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace: \n" . $e->getTraceAsString() . "\n";
    exit(1);
}
