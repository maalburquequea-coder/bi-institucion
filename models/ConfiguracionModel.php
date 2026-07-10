<?php
declare(strict_types=1);

class ConfiguracionModel
{
    public function __construct(private PDO $db) {}

    public function configuracionSistema(): array
    {
        $stmt = $this->db->query("
            SELECT clave, valor, descripcion, grupo, actualizado
            FROM configuracion_sistema
            ORDER BY grupo, clave
        ");
        return $stmt->fetchAll();
    }

    public function actualizarConfiguracion(array $items): void
    {
        $stmt = $this->db->prepare("UPDATE configuracion_sistema SET valor = ? WHERE clave = ?");
        foreach ($items as $clave => $valor) {
            $stmt->execute([(string) $valor, (string) $clave]);
        }
    }

    public function periodosAcademicos(array $filtros = []): array
    {
        $sql    = "SELECT id_periodo, nombre, fecha_inicio, fecha_fin, activo FROM periodos_academicos WHERE 1=1";
        $params = [];

        if (!empty($filtros['nombre'])) {
            $sql .= " AND nombre LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
        }
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $sql .= " AND activo = ?";
            $params[] = (int) $filtros['activo'];
        }

        $sql .= " ORDER BY fecha_inicio DESC, id_periodo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function guardarPeriodo(array $data): bool
    {
        if ((int) ($data['activo'] ?? 0) === 1) {
            $this->db->exec("UPDATE periodos_academicos SET activo = 0");
        }
        $stmt = $this->db->prepare("
            INSERT INTO periodos_academicos (nombre, fecha_inicio, fecha_fin, activo)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            (int) $data['activo'],
        ]);
    }
}
