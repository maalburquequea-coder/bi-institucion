<?php
declare(strict_types=1);

class AuditoriaModel
{
    public function __construct(private PDO $db) {}

    public function registrarAuditoria(?int $idUsuario, string $modulo, string $accion, string $detalle): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO auditoria (id_usuario, modulo, accion, detalle, ip) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $modulo, $accion, $detalle, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Throwable) {}
    }

    public function registrarAcceso(?int $idUsuario, string $correo, bool $exito): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO accesos_sistema (id_usuario, correo, exito, ip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$idUsuario, $correo, $exito ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Throwable) {}
    }

    public function auditoriaSistema(): array
    {
        return $this->db->query("
            SELECT a.*, CONCAT(u.nombres, ' ', u.apellidos) AS usuario
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
            ORDER BY a.fecha DESC
            LIMIT 100
        ")->fetchAll();
    }

    public function contarIntentosFallidos(string $ip, int $minutos = 10): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM accesos_sistema
                WHERE ip = ? AND exito = 0
                  AND fecha >= NOW() - INTERVAL ? MINUTE
            ");
            $stmt->execute([$ip, $minutos]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    public function accesosSistema(): array
    {
        return $this->db->query("
            SELECT ac.*, CONCAT(u.nombres, ' ', u.apellidos) AS usuario
            FROM accesos_sistema ac
            LEFT JOIN usuarios u ON u.id_usuario = ac.id_usuario
            ORDER BY ac.fecha DESC
            LIMIT 100
        ")->fetchAll();
    }
}
