<?php

namespace SistemaAdmin\Services;

use SistemaAdmin\Models\Profesor;
use SistemaAdmin\Mappers\ProfesorMapper;
use SistemaAdmin\Exceptions\ProfesorNoEncontradoException;
use SistemaAdmin\Contracts\DatabaseInterface;
use DateTime;

/**
 * Implementación concreta del ServicioProfesores
 * 
 * Contiene la lógica de negocio para la gestión de profesores.
 * Implementa la interfaz IServicioProfesores.
 */
class ServicioProfesores extends BaseService
{
    private ProfesorMapper $profesorMapper;

    public function __construct(DatabaseInterface $database, ProfesorMapper $profesorMapper)
    {
        parent::__construct($database);
        $this->profesorMapper = $profesorMapper;
    }

    public function crear(Profesor $profesor): Profesor
    {
        // Validar que el DNI no esté duplicado
        $profesorExistente = $this->profesorMapper->findByDni($profesor->getDni());
        if ($profesorExistente !== null) {
            $estado = $profesorExistente->esActivo() ? 'activo' : 'inactivo';
            throw new \InvalidArgumentException(
                "Ya existe un profesor con el DNI {$profesor->getDni()} ({$profesorExistente->getNombreCompleto()}) - Estado: {$estado}. " .
                "Si desea reactivarlo, búsquelo en la lista de profesores y use la opción 'Reactivar'."
            );
        }

        // Validar que el email no esté duplicado
        if ($profesor->getEmail() !== null && $profesor->getEmail() !== '') {
            if ($this->profesorMapper->existsByEmail($profesor->getEmail())) {
                $emailExistente = $this->profesorMapper->findByEmail($profesor->getEmail());
                $nombreCompleto = $emailExistente ? $emailExistente->getNombreCompleto() : 'otro profesor';
                throw new \InvalidArgumentException(
                    "Ya existe un profesor registrado con el email {$profesor->getEmail()} ({$nombreCompleto})"
                );
            }

            $userConflict = $this->emailExisteEnUsuarios($profesor->getEmail(), $profesor->getDni());
            if ($userConflict !== null) {
                throw new \InvalidArgumentException(
                    "El email ya está registrado para el usuario: {$userConflict['nombre']} {$userConflict['apellido']} (DNI: {$userConflict['dni']}, Rol: {$userConflict['rol']})"
                );
            }
        }
        
        // Validar datos del profesor
        $this->validarProfesor($profesor);
        
        // Guardar en la base de datos
        return $this->profesorMapper->save($profesor);
    }

    public function actualizar(Profesor $profesor): Profesor
    {
        // Verificar que el profesor existe
        $profesorExistente = $this->profesorMapper->findById($profesor->getId());
        if ($profesorExistente === null) {
            throw new ProfesorNoEncontradoException($profesor->getId());
        }

        // Validar que el email no esté duplicado por otro profesor
        if ($profesor->getEmail() !== null && $profesor->getEmail() !== '') {
            if ($this->profesorMapper->existsByEmail($profesor->getEmail(), $profesor->getId())) {
                $emailExistente = $this->profesorMapper->findByEmail($profesor->getEmail());
                $nombreCompleto = $emailExistente ? $emailExistente->getNombreCompleto() : 'otro profesor';
                throw new \InvalidArgumentException(
                    "Ya existe otro profesor registrado con el email {$profesor->getEmail()} ({$nombreCompleto})"
                );
            }

            $userConflict = $this->emailExisteEnUsuarios($profesor->getEmail(), $profesor->getDni());
            if ($userConflict !== null) {
                throw new \InvalidArgumentException(
                    "El email ya está registrado para el usuario: {$userConflict['nombre']} {$userConflict['apellido']} (DNI: {$userConflict['dni']}, Rol: {$userConflict['rol']})"
                );
            }
        }
        
        // Validar datos del profesor
        $this->validarProfesor($profesor);
        
        // Actualizar en la base de datos
        $this->profesorMapper->update($profesor);

        // Sincronizar con la tabla usuarios
        $this->database->query(
            "UPDATE usuarios SET dni = ?, email = ?, nombre = ?, apellido = ? WHERE dni = ? AND rol = 'profesor'",
            [
                $profesor->getDni(),
                $profesor->getEmail(),
                $profesor->getNombre(),
                $profesor->getApellido(),
                $profesorExistente->getDni()
            ]
        );
        
        return $profesor;
    }

    public function actualizarInformacionFicha(
        int $profesorId,
        ?string $telefonoFijo,
        ?string $telefonoCelular,
        ?string $email,
        ?string $domicilio,
        ?string $titulo,
        ?int $especialidadId,
        ?string $fechaNacimiento = null,
        ?string $fechaIngreso = null
    ): bool {
        $profesor = $this->profesorMapper->findById($profesorId);
        if ($profesor === null || !$profesor->esActivo()) {
            throw new ProfesorNoEncontradoException($profesorId);
        }

        if ($email !== null && $email !== '') {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
                throw new \InvalidArgumentException("El formato del email es inválido");
            }
            if (strtolower($email) !== strtolower((string)$profesor->getEmail())) {
                if ($this->profesorMapper->existsByEmail($email, $profesorId)) {
                    $emailExistente = $this->profesorMapper->findByEmail($email);
                    $nombreCompleto = $emailExistente ? $emailExistente->getNombreCompleto() : 'otro profesor';
                    throw new \InvalidArgumentException(
                        "Ya existe otro profesor registrado con el email {$email} ({$nombreCompleto})"
                    );
                }

                $userConflict = $this->emailExisteEnUsuarios($email, $profesor->getDni());
                if ($userConflict !== null) {
                    throw new \InvalidArgumentException(
                        "El email ya está registrado para el usuario: {$userConflict['nombre']} {$userConflict['apellido']} (DNI: {$userConflict['dni']}, Rol: {$userConflict['rol']})"
                    );
                }
            }
        }

        if ($telefonoFijo !== null && $telefonoFijo !== '') {
            $telefonoLimpio = preg_replace('/\D/', '', $telefonoFijo);
            if (strlen($telefonoLimpio) < 6 || strlen($telefonoLimpio) > 15) {
                throw new \InvalidArgumentException("Teléfono fijo inválido: {$telefonoFijo}");
            }
        }

        if ($telefonoCelular !== null && $telefonoCelular !== '') {
            $celularLimpio = preg_replace('/\D/', '', $telefonoCelular);
            if (strlen($celularLimpio) < 6 || strlen($celularLimpio) > 15) {
                throw new \InvalidArgumentException("Teléfono celular inválido: {$telefonoCelular}");
            }
        }

        $result = $this->profesorMapper->updateInformacionFicha(
            $profesorId,
            $telefonoFijo,
            $telefonoCelular,
            $email,
            $domicilio,
            $titulo,
            $especialidadId,
            $fechaNacimiento,
            $fechaIngreso
        );

        if ($result) {
            $this->database->query(
                "UPDATE usuarios SET email = ? WHERE dni = ? AND rol = 'profesor'",
                [$email, $profesor->getDni()]
            );
        }

        return $result;
    }

    public function eliminar(int $profesorId): bool
    {
        $profesor = $this->profesorMapper->findById($profesorId);
        if ($profesor === null) {
            throw new ProfesorNoEncontradoException($profesorId);
        }
        $dni = $profesor->getDni();
        $deleted = $this->profesorMapper->delete($profesorId);
        if ($dni !== '') {
            $user = $this->database->fetch("SELECT id FROM usuarios WHERE dni = ? AND rol = 'profesor'", [$dni]);
            if ($user) {
                $userId = (int) $user['id'];
                try {
                    $this->database->query("DELETE FROM usuarios WHERE id = ?", [$userId]);
                } catch (\Throwable $e) {
                    $suffix = '_deleted_' . time();
                    $this->database->query(
                        "UPDATE usuarios SET activo = 0, email = NULL, dni = CONCAT(dni, ?) WHERE id = ?",
                        [$suffix, $userId]
                    );
                }
            }
        }
        return $deleted;
    }

    public function buscarPorId(int $id): ?Profesor
    {
        return $this->profesorMapper->findById($id);
    }

    public function buscarPorDni(string $dni): ?Profesor
    {
        return $this->profesorMapper->findByDni($dni);
    }

    public function obtenerPorId(int $profesorId): Profesor
    {
        $profesor = $this->profesorMapper->findById($profesorId);
        if ($profesor === null) {
            throw new ProfesorNoEncontradoException($profesorId);
        }
        
        return $profesor;
    }

    public function obtenerTodos(): array
    {
        return $this->profesorMapper->findActive();
    }

    public function buscarPorNombre(string $nombre): array
    {
        return $this->profesorMapper->findByNombre($nombre);
    }

    public function obtenerPorEspecialidad(string $especialidad): array
    {
        return $this->profesorMapper->findByEspecialidad($especialidad);
    }

    public function buscarPorEspecialidad(string $especialidad): array
    {
        return $this->profesorMapper->findByEspecialidad($especialidad);
    }

    public function obtenerPorMateriaYCurso(int $materiaId, int $cursoId): array
    {
        return $this->profesorMapper->findByMateriaYCurso($materiaId, $cursoId);
    }

    public function obtenerEstadisticas(): array
    {
        $profesores = $this->obtenerTodos();
        $totalProfesores = count($profesores);
        
        // Contar profesores con y sin cursos asignados
        $conCursos = 0;
        $sinCursos = 0;
        $especialidades = [];
        
        foreach ($profesores as $profesor) {
            if ($this->tieneCursosAsignados($profesor->getId())) {
                $conCursos++;
            } else {
                $sinCursos++;
            }
            
            if ($profesor->getEspecialidad()) {
                $especialidad = $profesor->getEspecialidad();
                if (!isset($especialidades[$especialidad])) {
                    $especialidades[$especialidad] = 0;
                }
                $especialidades[$especialidad]++;
            }
        }
        
        return [
            'total_profesores' => $totalProfesores,
            'con_cursos' => $conCursos,
            'sin_cursos' => $sinCursos,
            'especialidades' => $especialidades,
            'promedio_edad' => $this->calcularPromedioEdad($profesores)
        ];
    }

    public function tieneCursosAsignados(int $profesorId): bool
    {
        return $this->profesorMapper->tieneCursosAsignados($profesorId);
    }

    public function obtenerCursosAsignados(int $profesorId): array
    {
        return $this->profesorMapper->getCursosAsignados($profesorId);
    }

    public function obtenerMateriasAsignadas(int $profesorId): array
    {
        return $this->profesorMapper->getMateriasAsignadas($profesorId);
    }

    public function esProfesorActivo(int $profesorId): bool
    {
        $profesor = $this->profesorMapper->findById($profesorId);
        return $profesor !== null && $profesor->esActivo();
    }

    public function obtenerProfesoresSinCursos(): array
    {
        $profesores = $this->obtenerTodos();
        
        return array_filter($profesores, function($profesor) {
            return !$this->tieneCursosAsignados($profesor->getId());
        });
    }

    public function obtenerProfesoresPorEspecialidad(): array
    {
        $profesores = $this->obtenerTodos();
        $porEspecialidad = [];
        
        foreach ($profesores as $profesor) {
            $especialidad = $profesor->getEspecialidad() ?: 'Sin especialidad';
            if (!isset($porEspecialidad[$especialidad])) {
                $porEspecialidad[$especialidad] = [];
            }
            $porEspecialidad[$especialidad][] = $profesor;
        }
        
        return $porEspecialidad;
    }

    public function obtenerResumenMensual(int $mes, int $anio): array
    {
        $profesores = $this->obtenerTodos();
        $fechaInicio = new DateTime("$anio-$mes-01");
        $fechaFin = new DateTime("$anio-$mes-" . $fechaInicio->format('t'));
        
        $nuevosProfesores = 0;
        $profesoresActivos = 0;
        
        foreach ($profesores as $profesor) {
            if ($profesor->getFechaIngreso() && 
                $profesor->getFechaIngreso() >= $fechaInicio && 
                $profesor->getFechaIngreso()<= $fechaFin) {
                $nuevosProfesores++;
            }
            
            if ($profesor->esActivo()) {
                $profesoresActivos++;
            }
        }
        
        return [
            'mes' => $mes,
            'anio' => $anio,
            'nuevos_profesores' => $nuevosProfesores,
            'profesores_activos' => $profesoresActivos,
            'total_profesores' => count($profesores)
        ];
    }

    /**
     * Valida los datos de un profesor
     */
    private function validarProfesor(Profesor $profesor): void
    {
        if (empty(trim($profesor->getDni()))) {
            throw new \InvalidArgumentException("El DNI es requerido");
        }
        
        if (empty(trim($profesor->getApellido()))) {
            throw new \InvalidArgumentException("El apellido es requerido");
        }
        
        if (empty(trim($profesor->getNombre()))) {
            throw new \InvalidArgumentException("El nombre es requerido");
        }
        
        // Validar formato de DNI
        if (!preg_match('/^[0-9A-Za-z\.\-]{5,20}$/', $profesor->getDni())) {
            throw new \InvalidArgumentException("El DNI debe tener entre 7 y 8 dígitos");
        }
        
        // Validar email si se proporciona
        if ($profesor->getEmail() && !filter_var($profesor->getEmail(), FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            throw new \InvalidArgumentException("El formato del email es inválido");
        }

        // Validar teléfonos si se proporcionan (permitir formato flexible, mínimo 6 dígitos alfanuméricos)
        if ($profesor->getTelefonoFijo()) {
            $telefonoLimpio = preg_replace('/\D/', '', $profesor->getTelefonoFijo());
            if (strlen($telefonoLimpio) < 6 || strlen($telefonoLimpio) > 15) {
                throw new \InvalidArgumentException("Teléfono fijo inválido: {$profesor->getTelefonoFijo()}");
            }
        }

        if ($profesor->getTelefonoCelular()) {
            $celularLimpio = preg_replace('/\D/', '', $profesor->getTelefonoCelular());
            if (strlen($celularLimpio) < 6 || strlen($celularLimpio) > 15) {
                throw new \InvalidArgumentException("Teléfono celular inválido: {$profesor->getTelefonoCelular()}");
            }
        }
    }

    public function dniExiste(string $dni, ?int $excluirId = null): bool
    {
        $profesor = $this->profesorMapper->findByDni($dni);
        
        if ($profesor === null) {
            return false;
        }
        
        // Si se proporciona un ID a excluir, verificar que no sea el mismo profesor
        if ($excluirId !== null && $profesor->getId() === $excluirId) {
            return false;
        }
        
        return true;
    }

    public function obtenerPorMateria(int $materiaId): array
    {
        return $this->profesorMapper->findByMateria($materiaId);
    }

    public function asignarCurso(int $profesorId, int $cursoId): bool
    {
        $this->obtenerPorId($profesorId);
        $asignacionExistente = $this->profesorMapper->buscarAsignacionCursoExistente($profesorId, $cursoId);
        if ($asignacionExistente) {
            return $this->profesorMapper->reactivarAsignacionCurso($profesorId, $cursoId);
        }
        return $this->profesorMapper->crearAsignacionCurso($profesorId, $cursoId);
    }

    public function desasignarCurso(int $profesorId, int $asignacionId): bool
    {
        $this->obtenerPorId($profesorId);
        return $this->profesorMapper->desasignarCurso($asignacionId, $profesorId);
    }

    public function asignarMateria(int $profesorId, int $materiaId, int $cursoId, ?string $grupoTaller = null): array
    {
        $this->obtenerPorId($profesorId);

        $materia = $this->database->fetch('SELECT es_taller FROM materias WHERE id = ?', [$materiaId]);
        $esTaller = $materia ? (int) $materia['es_taller'] : 0;
        
        if ($esTaller === 1) {
            if ($grupoTaller === null || $grupoTaller === '') {
                return ['success' => false, 'error' => 'Debe seleccionar un grupo de taller para una materia de taller'];
            }
            $grupoTaller = strtoupper(trim($grupoTaller));
            if (!in_array($grupoTaller, ['A', 'B', 'C', 'D', 'E'], true)) {
                return ['success' => false, 'error' => 'Grupo de taller inválido'];
            }
        } else {
            $grupoTaller = null;
        }

        $filaAnio = $this->profesorMapper->buscarMateriaFilaMismoAnioLectivo($profesorId, $materiaId, $cursoId, $grupoTaller);
        if ($filaAnio !== null) {
            if (!empty((int) $filaAnio['activo'])) {
                $msg = 'Esta materia ya está asignada para este curso en el año lectivo actual';
                if ($grupoTaller !== null) {
                    $msg = "Esta materia y grupo ({$grupoTaller}) ya están asignados para este curso en el año lectivo actual";
                }
                return ['success' => false, 'error' => $msg];
            }

            if ($this->profesorMapper->reactivarMateriaProfesor((int) $filaAnio['id'])) {
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Existía una asignación previa dada de baja pero no se pudo reactivar. Recargue la página e intente de nuevo.'];
        }

        $conflicto = $this->profesorMapper->buscarConflictoMateriaCurso($profesorId, $materiaId, $cursoId, $grupoTaller);
        if ($conflicto) {
            $msg = "No se puede asignar esta materia. El profesor {$conflicto['apellido']}, {$conflicto['nombre']} ya dicta esta materia en este curso";
            if ($grupoTaller !== null) {
                $msg = "No se puede asignar esta materia. El profesor {$conflicto['apellido']}, {$conflicto['nombre']} ya dicta esta materia en el grupo {$grupoTaller} de este curso";
            }
            return [
                'success' => false,
                'error' => $msg
            ];
        }

        $insertOk = $this->profesorMapper->asignarMateria($profesorId, $materiaId, $cursoId, $grupoTaller);
        if (!$insertOk) {
            return ['success' => false, 'error' => 'No se insertó la asignación (la base no reportó filas nuevas). Revise la tabla profesor_materia y los permisos del usuario MySQL.'];
        }

        return ['success' => true];
    }

    public function desasignarMateria(int $profesorId, int $materiaCursoId): bool
    {
        $this->obtenerPorId($profesorId);
        return $this->profesorMapper->desasignarMateria($materiaCursoId, $profesorId);
    }

    public function crearSuplencia(
        int $profesorId,
        int $materiaId,
        string $fechaInicio,
        ?string $fechaFin,
        string $motivo,
        ?int $suplenteId,
        int $fueraServicio,
        int $usuarioId
    ): array {
        $this->obtenerPorId($profesorId);

        if ($this->profesorMapper->buscarSuplenciaActivaPorMateria($profesorId, $materiaId)) {
            return ['success' => false, 'error' => 'Ya existe una suplencia activa para esta materia'];
        }

        $this->profesorMapper->crearSuplencia(
            $profesorId,
            $suplenteId,
            $materiaId,
            $fechaInicio,
            $fechaFin,
            $motivo,
            $fueraServicio,
            $usuarioId
        );

        return ['success' => true];
    }

    public function finalizarSuplencia(int $profesorId, int $suplenciaId): bool
    {
        $this->obtenerPorId($profesorId);
        return $this->profesorMapper->finalizarSuplencia($suplenciaId, $profesorId);
    }

    public function guardarSuplente(
        string $dni,
        string $apellido,
        string $nombre,
        ?string $telefonoCelular,
        ?string $email,
        ?string $especialidad
    ): array {
        $existente = $this->profesorMapper->buscarSuplentePorDni($dni);
        if ($existente) {
            $estado = !empty($existente['activo']) ? 'activo' : 'inactivo';
            $nombreCompleto = ($existente['apellido'] ?? '') . ', ' . ($existente['nombre'] ?? '');
            return [
                'success' => false,
                'error' => "Ya existe un suplente con el DNI {$dni} ({$nombreCompleto}) - Estado: {$estado}. Por favor, verifique los datos o use el suplente existente."
            ];
        }

        $id = $this->profesorMapper->crearSuplente($dni, $apellido, $nombre, $telefonoCelular, $email, $especialidad);
        return ['success' => true, 'suplente_id' => $id];
    }

    public function obtenerDatosFicha(int $profesorId): ?array
    {
        return $this->profesorMapper->obtenerProfesorFicha($profesorId);
    }

    public function obtenerMateriasPorCurso(int $cursoId): array
    {
        $cursoInfo = $this->profesorMapper->obtenerInfoCurso($cursoId);
        if (!$cursoInfo) {
            return ['success' => false, 'error' => 'Curso no encontrado'];
        }

        $especialidadCursoId = isset($cursoInfo['especialidad_id']) ? (int)$cursoInfo['especialidad_id'] : null;
        $materias = $this->profesorMapper->obtenerMateriasDisponiblesPorCurso($cursoId, $especialidadCursoId);

        return ['success' => true, 'materias' => $materias];
    }

    /**
     * Preceptores del equipo que comparten al menos un curso de trabajo con los IDs indicados.
     *
     * @param list<int> $cursoIds
     * @return list<array<string, mixed>>
     */
    public function listarPreceptoresCompartenCursos(array $cursoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $cursoIds), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids);

        return $this->database->fetchAll(
            "SELECT ed.id, ed.apellido, ed.nombre, ed.telefono, ed.email, ed.cargo, ed.usuario_id,
                    u.dni AS usuario_login,
                    c.anio, c.division, esp.nombre AS especialidad_curso
             FROM equipo_directivo ed
             LEFT JOIN usuarios u ON u.id = ed.usuario_id
             LEFT JOIN cursos c ON c.id = ed.curso_id
             LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
             WHERE ed.activo = 1
               AND LOWER(TRIM(ed.cargo)) = 'preceptor'
               AND (
                 ed.id IN (SELECT pcsub.equipo_directivo_id FROM preceptor_curso pcsub WHERE pcsub.curso_id IN ($ph))
                 OR ed.curso_id IN ($ph)
               )
             ORDER BY ed.apellido, ed.nombre",
            $params
        );
    }

    /**
     * @param list<array<string, mixed>> $preceptores
     * @return list<array<string, mixed>>
     */
    public function filtrarPreceptoresEquipoPorTexto(array $preceptores, string $search): array
    {
        $needle = strtolower(trim($search));
        if ($needle === '') {
            return $preceptores;
        }

        return array_values(array_filter($preceptores, static function (array $p) use ($needle): bool {
            $blob = strtolower(
                ($p['apellido'] ?? '') . ' ' . ($p['nombre'] ?? '') . ' ' . ($p['usuario_login'] ?? '')
            );

            return strpos($blob, $needle) !== false;
        }));
    }

    /**
     * @param list<int> $profesorIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function mapaCursosActivosPorProfesorIds(array $profesorIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $profesorIds), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $cursosRows = $this->database->fetchAll(
            "SELECT 
                pc.profesor_id,
                c.anio,
                c.division,
                esp.nombre AS especialidad,
                t.nombre AS turno
            FROM profesor_curso pc
            JOIN cursos c ON pc.curso_id = c.id
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id
            LEFT JOIN turnos t ON c.turno_id = t.id
            WHERE pc.profesor_id IN ($placeholders)
              AND pc.activo = 1
            ORDER BY 
                c.anio,
                CASE 
                    WHEN c.division REGEXP '^[0-9]+$' THEN CAST(c.division AS UNSIGNED) 
                    ELSE 999 
                END,
                c.division",
            $ids
        );
        $map = [];
        foreach ($cursosRows as $row) {
            $pid = (int) $row['profesor_id'];
            $map[$pid][] = $row;
        }

        return $map;
    }

    /**
     * Nombres de especialidad distintos entre profesores activos (filtro de listado).
     *
     * @return list<array{especialidad: string}>
     */
    public function listarNombresEspecialidadFiltroProfesoresActivos(): array
    {
        return $this->database->fetchAll(<<<'SQL'
            SELECT DISTINCT esp.nombre as especialidad
            FROM profesores p
            LEFT JOIN especialidades esp ON p.especialidad_id = esp.id
            WHERE p.activo = 1 AND esp.nombre IS NOT NULL AND esp.nombre != ''
            ORDER BY esp.nombre
            SQL,
            []
        );
    }

    /**
     * @return array{anio: mixed, division: mixed, especialidad: mixed|null}|null
     */
    public function obtenerFilaCursoParaEtiqueta(int $cursoId): ?array
    {
        if ($cursoId < 1) {
            return null;
        }
        $row = $this->database->fetch(<<<'SQL'
            SELECT c.anio, c.division, esp.nombre as especialidad 
            FROM cursos c 
            LEFT JOIN especialidades esp ON c.especialidad_id = esp.id 
            WHERE c.id = ?
            SQL,
            [$cursoId]
        );

        return $row === null ? null : $row;
    }

    /**
     * @return list<array{id: int|string, nombre: string}>
     */
    public function listarEspecialidadesActivasIdNombre(): array
    {
        return $this->database->fetchAll(
            'SELECT id, nombre FROM especialidades WHERE activa = 1 ORDER BY nombre',
            []
        );
    }

    private function emailExisteEnUsuarios(string $email, ?string $excluirDni = null): ?array
    {
        $sql = "SELECT id, dni, nombre, apellido, rol FROM usuarios WHERE LOWER(TRIM(email)) = ? AND activo = 1";
        $params = [strtolower(trim($email))];
        if ($excluirDni !== null && $excluirDni !== '') {
            $sql .= " AND dni != ?";
            $params[] = $excluirDni;
        }
        return $this->database->fetch($sql, $params) ?: null;
    }

    /**
     * Calcula el promedio de edad de los profesores
     */
    private function calcularPromedioEdad(array $profesores): float
    {
        $edades = [];
        
        foreach ($profesores as $profesor) {
            if ($profesor->getFechaNacimiento()) {
                $edades[] = $profesor->getEdad();
            }
        }
        
        return !empty($edades) ? array_sum($edades) / count($edades) : 0.0;
    }
}
