<?php
declare(strict_types=1);

class NotificacionModel
{
    public function __construct(private PDO $db) {}

    public function crearNotificacionUsuario(int $idUsuario, string $tipo, string $mensaje, string $estadoCorreo): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notificaciones_usuario (id_usuario, tipo, canal, mensaje, estado_correo)
            VALUES (?, ?, 'Sistema/Correo', ?, ?)
        ");
        $stmt->execute([$idUsuario, $tipo, $mensaje, $estadoCorreo]);
    }

    public function notificacionesUsuario(int $idUsuario): array
    {
        $stmt = $this->db->prepare("
            SELECT tipo, canal, mensaje, estado_correo, fecha
            FROM notificaciones_usuario
            WHERE id_usuario = ?
            ORDER BY fecha DESC
            LIMIT 12
        ");
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll();
    }

    public function registrarNotificacionPadreCorreo(int $idEstudiante, int $idPadre, string $mensaje, string $estado): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado, fecha_envio)
            VALUES (?, ?, 'Correo', 'Alerta temprana', ?, ?, NOW())
        ");
        $stmt->execute([$idEstudiante, $idPadre, $mensaje, $estado]);
    }

    public function marcarNotificacionesPadreLeidas(int $idPadre, int $idEstudiante): void
    {
        $stmt = $this->db->prepare("
            UPDATE notificaciones SET estado = 'Leido'
            WHERE id_padre = ? AND id_estudiante = ? AND estado <> 'Leido'
        ");
        $stmt->execute([$idPadre, $idEstudiante]);
    }

    public function crearNotificacionPlanMejora(int $_idDocente, int $idEstudiante, string $mensajePlan): bool
    {
        $stmt = $this->db->prepare("SELECT id_padre FROM estudiantes WHERE id_estudiante = ? LIMIT 1");
        $stmt->execute([$idEstudiante]);
        $idPadre = $stmt->fetchColumn();

        if (!$idPadre || $idPadre === false) {
            return false;
        }

        $insert = $this->db->prepare("
            INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado, fecha_envio)
            VALUES (?, ?, 'Interno', 'Plan de Mejora IA', ?, 'Pendiente', NOW())
        ");
        $insert->execute([$idEstudiante, (int) $idPadre, $mensajePlan]);
        return true;
    }

    public function todasLasNotificaciones(): array
    {
        return $this->db->query("
            SELECT n.tipo AS origen, n.fecha_envio AS fecha, n.canal, n.estado,
                   n.mensaje, CONCAT(e.nombres, ' ', e.apellidos) AS referencia,
                   CONCAT(p.nombres, ' ', p.apellidos) AS destinatario, p.correo, p.telefono,
                   CASE
                       WHEN p.telefono IS NULL OR p.telefono = '' THEN ''
                       ELSE CONCAT('Hola ', p.nombres, ', le informamos que ', e.nombres, ' ', e.apellidos,
                                   ' presenta una alerta academica en la I.E. N. 14008: ', n.mensaje,
                                   ' Por favor revise el sistema BI Educativo o comuniquese con la institucion.')
                   END AS mensaje_whatsapp
            FROM notificaciones n
            JOIN estudiantes e ON e.id_estudiante = n.id_estudiante
            JOIN usuarios p ON p.id_usuario = n.id_padre
            UNION ALL
            SELECT tipo AS origen, fecha, canal, estado_correo AS estado,
                   mensaje, '-' AS referencia,
                   CONCAT(u.nombres, ' ', u.apellidos) AS destinatario, u.correo, u.telefono,
                   '' AS mensaje_whatsapp
            FROM notificaciones_usuario nu
            JOIN usuarios u ON u.id_usuario = nu.id_usuario
            ORDER BY fecha DESC
            LIMIT 150
        ")->fetchAll();
    }
}
