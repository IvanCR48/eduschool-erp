<?php

declare(strict_types=1);

namespace SistemaAdmin\Controllers;

use SistemaAdmin\Contracts\DatabaseInterface;
use SistemaAdmin\Services\BaseService;
use SistemaAdmin\Mappers\UsuarioMapper;
use SistemaAdmin\Models\Usuario;

/**
 * Controlador para la gestión de usuarios (users.php)
 */
class UsuarioController extends BaseService
{
    private UsuarioMapper $usuarioMapper;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
        $this->usuarioMapper = new UsuarioMapper($database);
    }

    /**
     * Procesa las peticiones POST de usuarios
     */
    public function procesarPost(array $post, array $usuarioLogueado): array
    {
        $action = $post['action'] ?? '';
        
        try {
            if ($action === 'save') {
                $userId = isset($post['user_id']) && $post['user_id'] !== '' ? (int)$post['user_id'] : null;
                $nombre = trim((string)($post['nombre'] ?? ''));
                $apellido = trim((string)($post['apellido'] ?? ''));
                $dni = trim((string)($post['dni'] ?? ''));
                $email = isset($post['email']) && trim((string)$post['email']) !== '' ? trim((string)$post['email']) : null;
                $rol = trim((string)($post['rol'] ?? ''));
                $turnoAsignado = trim((string)($post['turno_asignado'] ?? '')) ?: 'ambos';
                $password = (string)($post['password'] ?? '');
                $activo = isset($post['activo']) ? (int)$post['activo'] : 1;
                
                $cargoInput = trim((string)($post['cargo'] ?? ''));
                $cursosPost = $post['cursos'] ?? [];
                
                // Validaciones
                if ($nombre === '' || $apellido === '' || $dni === '' || $rol === '') {
                    throw new \InvalidArgumentException("Complete todos los campos obligatorios (*).");
                }
                
                // Si edita su propio usuario
                if ($userId !== null && (int)$userId === (int)$usuarioLogueado['id']) {
                    if ($rol !== 'admin') {
                        throw new \InvalidArgumentException("No puedes cambiar tu propio rol de Administrador.");
                    }
                    if ($activo === 0) {
                        throw new \InvalidArgumentException("No puedes desactivar tu propia cuenta de Administrador.");
                    }
                }

                // Verificar DNI duplicado
                if ($userId !== null) {
                    $dniExists = $this->database->fetch("SELECT id FROM usuarios WHERE dni = ? AND id != ?", [$dni, $userId]);
                } else {
                    $dniExists = $this->database->fetch("SELECT id FROM usuarios WHERE dni = ?", [$dni]);
                }
                if ($dniExists) {
                    throw new \InvalidArgumentException("El DNI ya se encuentra registrado por otro usuario.");
                }

                // Iniciar Transacción
                $this->database->beginTransaction();

                if ($userId !== null) {
                    // Obtener usuario actual
                    $usuario = $this->usuarioMapper->findById($userId);
                    if (!$usuario) {
                        throw new \InvalidArgumentException("Usuario no encontrado.");
                    }
                    $usuario->setNombre($nombre);
                    $usuario->setApellido($apellido);
                    $usuario->setDni($dni);
                    $usuario->setEmail($email);
                    $usuario->setRol($rol);
                    $usuario->setTurnoAsignado($turnoAsignado);
                    $usuario->setActivo($activo === 1);
                    
                    if ($password !== '') {
                        $usuario->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));
                        $usuario->setMustChangePassword(true);
                    }
                    
                    $this->usuarioMapper->save($usuario);
                    $targetUserId = $userId;
                } else {
                    // Crear Usuario
                    if ($password === '') {
                        throw new \InvalidArgumentException("La contraseña es obligatoria para nuevos usuarios.");
                    }
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $usuario = new Usuario($dni, $apellido, $nombre, $email, $passHash, $rol, $activo === 1, $turnoAsignado);
                    $this->usuarioMapper->save($usuario);
                    $targetUserId = $usuario->getId();
                }

                // Sincronizar equipo_directivo si el rol lo requiere
                if (in_array($rol, ['directivo', 'preceptor', 'secretario'])) {
                    $cargo = ($rol === 'directivo') ? $cargoInput : $rol;
                    if ($rol === 'directivo' && !in_array($cargo, ['Director', 'Vicedirector'])) {
                        throw new \InvalidArgumentException("Debe seleccionar un cargo válido para directivos (Director o Vicedirector).");
                    }

                    $profile = $this->database->fetch("SELECT id FROM equipo_directivo WHERE usuario_id = ?", [$targetUserId]);
                    if ($profile) {
                        $profileId = (int)$profile['id'];
                        $this->database->query("
                            UPDATE equipo_directivo SET nombre = ?, apellido = ?, email = ?, cargo = ?, activo = ? 
                            WHERE id = ?
                        ", [$nombre, $apellido, $email ?: '', $cargo, $activo, $profileId]);
                    } else {
                        $this->database->query("
                            INSERT INTO equipo_directivo (usuario_id, nombre, apellido, email, cargo, activo) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ", [$targetUserId, $nombre, $apellido, $email ?: '', $cargo, $activo]);
                        $profileId = (int)$this->database->lastInsertId();
                    }

                    // Si es preceptor, sincronizar cursos
                    if ($rol === 'preceptor') {
                        $this->database->query("DELETE FROM preceptor_curso WHERE equipo_directivo_id = ?", [$profileId]);
                        $primerCursoId = null;
                        foreach ($cursosPost as $cursoId) {
                            $cursoId = (int)$cursoId;
                            $this->database->query("INSERT INTO preceptor_curso (equipo_directivo_id, curso_id) VALUES (?, ?)", [$profileId, $cursoId]);
                            if ($primerCursoId === null) {
                                $primerCursoId = $cursoId;
                            }
                        }
                        // Sincronizar primer curso para compatibilidad
                        $this->database->query("UPDATE equipo_directivo SET curso_id = ? WHERE id = ?", [$primerCursoId, $profileId]);
                    } else {
                        // Limpiar cursos si ya no es preceptor
                        $this->database->query("DELETE FROM preceptor_curso WHERE equipo_directivo_id = ?", [$profileId]);
                        $this->database->query("UPDATE equipo_directivo SET curso_id = NULL WHERE id = ?", [$profileId]);
                    }

                } else {
                    // Si el rol ya no requiere equipo_directivo (se cambió a admin/profesor)
                    $profile = $this->database->fetch("SELECT id FROM equipo_directivo WHERE usuario_id = ?", [$targetUserId]);
                    if ($profile) {
                        $this->database->query("DELETE FROM preceptor_curso WHERE equipo_directivo_id = ?", [$profile['id']]);
                        $this->database->query("DELETE FROM equipo_directivo WHERE id = ?", [$profile['id']]);
                    }
                }

                $this->database->commit();
                return [
                    'success' => true,
                    'error' => '',
                    'success_message' => "Usuario guardado con éxito.",
                    'redirect' => 'users.php?success=' . urlencode("Usuario guardado con éxito.")
                ];

            } elseif ($action === 'toggle_active') {
                $userId = isset($post['user_id']) ? (int)$post['user_id'] : 0;
                if ($userId === (int)$usuarioLogueado['id']) {
                    throw new \InvalidArgumentException("No puedes desactivar tu propia cuenta.");
                }

                $user = $this->database->fetch("SELECT activo FROM usuarios WHERE id = ?", [$userId]);
                if ($user) {
                    $nuevoEstado = $user['activo'] == 1 ? 0 : 1;
                    $this->database->beginTransaction();
                    $this->database->query("UPDATE usuarios SET activo = ? WHERE id = ?", [$nuevoEstado, $userId]);
                    $this->database->query("UPDATE equipo_directivo SET activo = ? WHERE usuario_id = ?", [$nuevoEstado, $userId]);
                    $this->database->commit();
                    return [
                        'success' => true,
                        'error' => '',
                        'success_message' => "Estado del usuario actualizado correctamente.",
                        'redirect' => null
                    ];
                }
                
                throw new \InvalidArgumentException("Usuario no encontrado.");

            } elseif ($action === 'delete') {
                $userId = isset($post['user_id']) ? (int)$post['user_id'] : 0;
                if ($userId === (int)$usuarioLogueado['id']) {
                    throw new \InvalidArgumentException("No puedes eliminar tu propia cuenta.");
                }

                $this->database->beginTransaction();
                
                // Borrar relaciones si existen
                $profile = $this->database->fetch("SELECT id FROM equipo_directivo WHERE usuario_id = ?", [$userId]);
                if ($profile) {
                    $this->database->query("DELETE FROM preceptor_curso WHERE equipo_directivo_id = ?", [$profile['id']]);
                    $this->database->query("DELETE FROM equipo_directivo WHERE id = ?", [$profile['id']]);
                }
                
                // Eliminar usuario
                $this->database->query("DELETE FROM usuarios WHERE id = ?", [$userId]);
                
                $this->database->commit();
                return [
                    'success' => true,
                    'error' => '',
                    'success_message' => "Usuario eliminado definitivamente del sistema.",
                    'redirect' => null
                ];
            }
        } catch (\Throwable $e) {
            try {
                $this->database->rollBack();
            } catch (\Exception $ex) {}
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'success_message' => '',
                'redirect' => null
            ];
        }

        return [
            'success' => false,
            'error' => 'Acción no permitida.',
            'success_message' => '',
            'redirect' => null
        ];
    }

    /**
     * Retorna los datos necesarios para la vista gestion usuarios
     */
    public function datosVistaGestion(): array
    {
        $usuariosLista = $this->database->fetchAll("
            SELECT u.id, u.dni, u.nombre, u.apellido, u.email, u.rol, u.activo, u.ultimo_acceso,
                   ed.cargo
            FROM usuarios u
            LEFT JOIN equipo_directivo ed ON ed.usuario_id = u.id
            WHERE u.rol IN ('admin', 'directivo', 'preceptor', 'secretario')
            ORDER BY u.apellido, u.nombre
        ");
        
        $stats = [
            'total' => count($usuariosLista),
            'admin' => 0,
            'directivo' => 0,
            'preceptor' => 0,
            'secretario' => 0,
            'activo' => 0,
            'inactivo' => 0
        ];
        
        foreach ($usuariosLista as $u) {
            if ($u['activo'] == 1) $stats['activo']++; else $stats['inactivo']++;
            if (isset($stats[$u['rol']])) {
                $stats[$u['rol']]++;
            }
        }

        $cursosDisponibles = $this->database->fetchAll("
            SELECT c.id, c.anio, c.division, COALESCE(esp.nombre, 'Común') AS especialidad 
            FROM cursos c 
            LEFT JOIN especialidades esp ON esp.id = c.especialidad_id 
            WHERE c.activo = 1 
            ORDER BY c.anio, c.division
        ");

        return [
            'usuariosLista' => $usuariosLista,
            'stats' => $stats,
            'cursosDisponibles' => $cursosDisponibles
        ];
    }
}
