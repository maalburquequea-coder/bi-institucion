<?php
declare(strict_types=1);

class UsuarioModel
{
    public function __construct(private PDO $db) {}

    // --- Roles ---

    public function obtenerRolesRegistro(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id_rol, nombre_rol FROM roles WHERE nombre_rol IN ('Docente', 'Padre') ORDER BY nombre_rol");
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    public function obtenerRoles(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol");
            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable) {
            return [];
        }
    }

    // --- Búsquedas de usuarios ---

    public function buscarUsuarioPorCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre_rol FROM usuarios u
            JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.correo = ? LIMIT 1
        ");
        $stmt->execute([$correo]);
        return $stmt->fetch() ?: null;
    }

    public function buscarUsuarioPorId(int $idUsuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre_rol FROM usuarios u
            JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.id_usuario = ? LIMIT 1
        ");
        $stmt->execute([$idUsuario]);
        return $stmt->fetch() ?: null;
    }

    public function buscarIdUsuarioPorCorreo(string $correo): int
    {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$correo]);
        return (int) $stmt->fetchColumn();
    }

    public function buscarIdPadreDeEstudiante(int $idEstudiante): int
    {
        $stmt = $this->db->prepare("SELECT id_padre FROM estudiantes WHERE id_estudiante = ? LIMIT 1");
        $stmt->execute([$idEstudiante]);
        return (int) $stmt->fetchColumn();
    }

    // --- Registro y actualización ---

    public function registrarUsuario(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (dni, nombres, apellidos, correo, telefono, contrasena, id_rol, primer_login, estado_cuenta, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'activo', NOW())
        ");
        $stmt->execute([
            $data['dni'], $data['nombres'], $data['apellidos'], $data['correo'],
            $data['telefono'] ?? null,
            password_hash($data['contrasena'], PASSWORD_DEFAULT),
            $data['id_rol'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function registrarUsuarioAdmin(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (dni, nombres, apellidos, correo, telefono, contrasena, id_rol, primer_login, estado_cuenta, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $stmt->execute([
            $data['dni'], $data['nombres'], $data['apellidos'], $data['correo'],
            $data['telefono'] ?? null,
            password_hash($data['contrasena'], PASSWORD_DEFAULT),
            $data['id_rol'], $data['estado_cuenta'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarUsuarioAdmin(array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET nombres = ?, apellidos = ?, correo = ?, telefono = ?, id_rol = ?, estado_cuenta = ?
            WHERE id_usuario = ?
        ");
        return $stmt->execute([
            $data['nombres'], $data['apellidos'], $data['correo'],
            $data['telefono'] ?? null, $data['id_rol'],
            $data['estado_cuenta'], $data['id_usuario'],
        ]);
    }

    public function eliminarUsuario(int $idUsuario): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM calificaciones WHERE id_docente = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM documentos_asistencia WHERE id_docente = ?")->execute([$idUsuario]);
            $this->db->prepare("UPDATE estudiantes SET id_padre = NULL WHERE id_padre = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM solicitudes_vinculacion WHERE id_padre = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM notificaciones WHERE id_padre = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM notificaciones_usuario WHERE id_usuario = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM auditoria WHERE id_usuario = ?")->execute([$idUsuario]);
            $this->db->prepare("DELETE FROM accesos_sistema WHERE id_usuario = ?")->execute([$idUsuario]);
            $res = $this->db->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$idUsuario]);
            $this->db->commit();
            return $res;
        } catch (Throwable) {
            $this->db->rollBack();
            return false;
        }
    }

    // --- Verificación de correo ---

    public function guardarTokenVerificacion(int $idUsuario, string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET token_verificacion = ?, correo_verificado = 0 WHERE id_usuario = ?");
        return $stmt->execute([$token, $idUsuario]);
    }

    public function buscarUsuarioPorTokenVerificacion(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT id_usuario, correo, correo_verificado FROM usuarios WHERE token_verificacion = ? LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function verificarCorreoUsuario(int $idUsuario): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET token_verificacion = NULL, correo_verificado = 1 WHERE id_usuario = ?");
        return $stmt->execute([$idUsuario]);
    }

    public function marcarPrimerLogin(int $idUsuario): void
    {
        $this->db->prepare("UPDATE usuarios SET primer_login = 0 WHERE id_usuario = ?")->execute([$idUsuario]);
    }

    // --- Listados de usuarios (admin) ---

    public function usuariosPendientes(): array
    {
        $stmt = $this->db->query("
            SELECT u.id_usuario, u.dni, u.nombres, u.apellidos, u.correo, u.telefono, u.estado_cuenta, r.nombre_rol, sv.dni_estudiante
            FROM usuarios u
            JOIN roles r ON r.id_rol = u.id_rol
            LEFT JOIN solicitudes_vinculacion sv ON sv.id_padre = u.id_usuario AND sv.estado = 'pendiente'
            WHERE u.estado_cuenta = 'pendiente'
            ORDER BY u.fecha_registro DESC, u.id_usuario DESC
        ");
        return $stmt->fetchAll();
    }

    public function usuariosRegistrados(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT u.id_usuario, u.dni, u.nombres, u.apellidos, u.correo, u.telefono,
                       u.estado_cuenta, u.fecha_registro, u.id_rol, r.nombre_rol
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY u.fecha_registro DESC, u.id_usuario DESC
            ");
            return $stmt ? $stmt->fetchAll() : [];
        } catch (Throwable) {
            return [];
        }
    }

    public function resumenAdmin(): array
    {
        return $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM usuarios WHERE estado_cuenta = 'activo') AS usuarios_activos,
                (SELECT COUNT(*) FROM notificaciones) AS alertas_generadas,
                (SELECT COUNT(*) FROM documentos_asistencia WHERE DATE(fecha_subida) = CURDATE()) AS cargas_hoy
        ")->fetch() ?: [];
    }

    // --- Aprobación de usuarios ---

    public function aprobarUsuario(int $idUsuario): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE usuarios SET estado_cuenta = 'activo' WHERE id_usuario = ?")->execute([$idUsuario]);
            $solicitud = $this->solicitudPendiente($idUsuario);
            if ($solicitud) {
                $estudiante = $this->buscarEstudiantePorIdentificador($solicitud['dni_estudiante']);
                if ($estudiante) {
                    $this->db->prepare("UPDATE estudiantes SET id_padre = ? WHERE id_estudiante = ?")->execute([$idUsuario, $estudiante['id_estudiante']]);
                    $this->db->prepare("UPDATE solicitudes_vinculacion SET estado = 'aprobada' WHERE id_solicitud = ?")->execute([$solicitud['id_solicitud']]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Throwable) {
            $this->db->rollBack();
            return false;
        }
    }

    public function rechazarUsuario(int $idUsuario): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado_cuenta = 'rechazado' WHERE id_usuario = ?");
        return $stmt->execute([$idUsuario]);
    }

    // --- Vinculación padre-estudiante ---

    public function crearSolicitudVinculacion(int $idPadre, string $dniEstudiante): void
    {
        if ($dniEstudiante === '') return;
        $existe = $this->db->prepare("SELECT id_solicitud FROM solicitudes_vinculacion WHERE id_padre = ? AND dni_estudiante = ? AND estado = 'pendiente' LIMIT 1");
        $existe->execute([$idPadre, $dniEstudiante]);
        if ($existe->fetch()) return;
        $this->db->prepare("INSERT INTO solicitudes_vinculacion (id_padre, dni_estudiante, estado) VALUES (?, ?, 'pendiente')")->execute([$idPadre, $dniEstudiante]);
    }

    public function vincularEstudianteDirecto(int $idPadre, string $dniEstudiante): bool
    {
        if ($dniEstudiante === '') return false;
        $estudiante = $this->buscarEstudiantePorIdentificador($dniEstudiante);
        if (!$estudiante) return false;
        $this->db->prepare("UPDATE estudiantes SET id_padre = ? WHERE id_estudiante = ?")->execute([$idPadre, $estudiante['id_estudiante']]);
        $this->db->prepare("INSERT INTO solicitudes_vinculacion (id_padre, dni_estudiante, estado) VALUES (?, ?, 'aprobada')")->execute([$idPadre, $dniEstudiante]);
        return true;
    }

    public function solicitudesVinculacionPendientes(): array
    {
        $stmt = $this->db->query("
            SELECT sv.id_solicitud, sv.id_padre, sv.dni_estudiante, sv.estado, sv.fecha,
                   u.nombres AS padre_nombres, u.apellidos AS padre_apellidos, u.dni AS padre_dni,
                   u.correo AS padre_correo, u.telefono AS padre_telefono,
                   e.id_estudiante, e.nombres AS estudiante_nombres, e.apellidos AS estudiante_apellidos,
                   e.grado, e.seccion, e.nivel, e.id_padre AS padre_actual
            FROM solicitudes_vinculacion sv
            JOIN usuarios u ON u.id_usuario = sv.id_padre
            LEFT JOIN estudiantes e ON e.dni = sv.dni_estudiante OR e.codigo_estudiante = sv.dni_estudiante
            WHERE sv.estado = 'pendiente'
            ORDER BY sv.fecha DESC, sv.id_solicitud DESC
        ");
        return $stmt->fetchAll();
    }

    public function aprobarSolicitudVinculacion(int $idSolicitud): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_solicitud, id_padre, dni_estudiante FROM solicitudes_vinculacion WHERE id_solicitud = ? AND estado = 'pendiente' LIMIT 1");
            $stmt->execute([$idSolicitud]);
            $solicitud = $stmt->fetch();
            if (!$solicitud) { $this->db->rollBack(); return false; }

            $estudiante = $this->buscarEstudiantePorIdentificador((string) $solicitud['dni_estudiante']);
            if (!$estudiante) { $this->db->rollBack(); return false; }

            $this->db->prepare("UPDATE estudiantes SET id_padre = ? WHERE id_estudiante = ?")->execute([(int) $solicitud['id_padre'], (int) $estudiante['id_estudiante']]);
            $this->db->prepare("UPDATE solicitudes_vinculacion SET estado = 'aprobada' WHERE id_solicitud = ?")->execute([$idSolicitud]);
            $this->db->commit();
            return true;
        } catch (Throwable) {
            $this->db->rollBack();
            return false;
        }
    }

    public function rechazarSolicitudVinculacion(int $idSolicitud): bool
    {
        $stmt = $this->db->prepare("UPDATE solicitudes_vinculacion SET estado = 'rechazada' WHERE id_solicitud = ? AND estado = 'pendiente'");
        return $stmt->execute([$idSolicitud]);
    }

    // --- Panel padre (listados de hijos) ---

    public function hijosDelPadre(int $idPadre): array
    {
        $stmt = $this->db->prepare("
            SELECT e.id_estudiante, e.nombres, e.apellidos, e.nivel, e.grado, e.seccion,
                   ROUND(COALESCE(AVG(c.nota_final), 0), 2) AS promedio,
                   SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) AS cursos_desaprobados,
                   SUM(CASE WHEN a.estado = 'Falto' THEN 1 ELSE 0 END) AS faltas,
                   SUM(CASE WHEN a.estado = 'Tardanza' THEN 1 ELSE 0 END) AS tardanzas
            FROM estudiantes e
            LEFT JOIN calificaciones c ON c.id_estudiante = e.id_estudiante
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            WHERE e.id_padre = ?
            GROUP BY e.id_estudiante
            ORDER BY e.apellidos, e.nombres
        ");
        $stmt->execute([$idPadre]);
        return $stmt->fetchAll();
    }

    public function hijosDetallePadre(int $idPadre): array
    {
        $stmt = $this->db->prepare("SELECT id_estudiante, dni, nombres, apellidos, nivel, grado, seccion FROM estudiantes WHERE id_padre = ? ORDER BY apellidos, nombres");
        $stmt->execute([$idPadre]);
        return $stmt->fetchAll();
    }

    public function solicitudesPadre(int $idPadre): array
    {
        $stmt = $this->db->prepare("SELECT dni_estudiante, estado, fecha FROM solicitudes_vinculacion WHERE id_padre = ? ORDER BY fecha DESC");
        $stmt->execute([$idPadre]);
        return $stmt->fetchAll();
    }

    // --- Privados ---

    private function solicitudPendiente(int $idPadre): ?array
    {
        $stmt = $this->db->prepare("SELECT id_solicitud, dni_estudiante FROM solicitudes_vinculacion WHERE id_padre = ? AND estado = 'pendiente' LIMIT 1");
        $stmt->execute([$idPadre]);
        return $stmt->fetch() ?: null;
    }

    private function buscarEstudiantePorIdentificador(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id_estudiante FROM estudiantes WHERE dni = ? OR codigo_estudiante = ? LIMIT 1");
        $stmt->execute([$id, $id]);
        return $stmt->fetch() ?: null;
    }
}
