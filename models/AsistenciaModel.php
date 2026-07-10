<?php
declare(strict_types=1);

class AsistenciaModel
{
    public function __construct(private PDO $db) {}

    public function documentosAsistencia(?string $nivel, ?int $grado): array
    {
        $sql    = "SELECT d.*, CONCAT(u.nombres, ' ', u.apellidos) AS docente, u.correo FROM documentos_asistencia d JOIN usuarios u ON u.id_usuario = d.id_docente WHERE 1 = 1";
        $params = [];
        if ($nivel) { $sql .= " AND d.nivel = ?"; $params[] = $nivel; }
        if ($grado) { $sql .= " AND d.grado = ?"; $params[] = $grado; }
        $sql .= " ORDER BY d.nivel, d.grado, d.seccion, d.fecha_subida DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function ultimaFechaAsistencia(): string
    {
        $fecha = $this->db->query("SELECT MAX(fecha_subida)::date FROM documentos_asistencia")->fetchColumn();
        return $fecha ?: date('Y-m-d');
    }

    public function guardarDocumentoAsistencia(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO documentos_asistencia (id_docente, nivel, grado, seccion, titulo, archivo, fecha_subida)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['id_docente'], $data['nivel'], $data['grado'],
            $data['seccion'], $data['titulo'], $data['archivo'],
        ]);
    }

    public function eliminarDocumentoAsistencia(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT archivo FROM documentos_asistencia WHERE id_documento = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();
            if ($doc && !empty($doc['archivo'])) {
                $ruta = __DIR__ . '/../uploads/asistencia/' . $doc['archivo'];
                if (file_exists($ruta)) { @unlink($ruta); }
            }
            return $this->db->prepare("DELETE FROM documentos_asistencia WHERE id_documento = ?")->execute([$id]);
        } catch (Throwable) {
            return false;
        }
    }

    public function estadoRegistroAsistencia(array $filtros): array
    {
        $sqlAsignaciones = "
            SELECT DISTINCT
                u.id_usuario, CONCAT(u.nombres, ' ', u.apellidos) AS docente, u.correo,
                e.nivel, e.grado, e.seccion, cu.nombre_curso AS curso
            FROM usuarios u
            JOIN calificaciones c ON c.id_docente = u.id_usuario
            JOIN cursos cu ON cu.id_curso = c.id_curso
            JOIN estudiantes e ON e.id_estudiante = c.id_estudiante
            WHERE u.estado_cuenta = 'activo'

            UNION

            SELECT DISTINCT
                u.id_usuario, CONCAT(u.nombres, ' ', u.apellidos) AS docente, u.correo,
                d.nivel, d.grado, d.seccion,
                TRIM(REPLACE(REPLACE(d.titulo, 'Asistencia ', ''), 'Notas ', '')) AS curso
            FROM usuarios u
            JOIN documentos_asistencia d ON d.id_docente = u.id_usuario
            WHERE u.estado_cuenta = 'activo'
        ";

        $docStmt = $this->db->prepare("
            SELECT id_documento AS id_documento_asistencia, titulo, archivo, fecha_subida
            FROM documentos_asistencia
            WHERE id_docente = ? AND nivel = ? AND grado = ? AND seccion = ?
            ORDER BY fecha_subida DESC
            LIMIT 1
        ");

        $where  = [];
        $params = [];
        if ($filtros['nivel']   !== '') { $where[] = "nivel = ?";  $params[] = $filtros['nivel']; }
        if ($filtros['grado']    > 0)  { $where[] = "grado = ?";  $params[] = (int)$filtros['grado']; }
        if ($filtros['seccion'] !== '') { $where[] = "seccion = ?"; $params[] = $filtros['seccion']; }

        $finalSql = "SELECT * FROM ($sqlAsignaciones) AS t";
        if (!empty($where)) { $finalSql .= " WHERE " . implode(" AND ", $where); }
        $finalSql .= " ORDER BY docente, nivel, grado, seccion";

        $stmtAsig = $this->db->prepare($finalSql);
        $stmtAsig->execute($params);
        $asignaciones = $stmtAsig->fetchAll();

        $registros = [];
        foreach ($asignaciones as $asig) {
            $docStmt->execute([$asig['id_usuario'], $asig['nivel'], $asig['grado'], $asig['seccion']]);
            $documento    = $docStmt->fetch() ?: null;
            $fechaUltima  = $documento ? substr((string) $documento['fecha_subida'], 0, 10) : '';
            $estado       = 'pendiente';

            if ($fechaUltima === $filtros['fecha']) {
                $estado = 'cargado';
            } elseif ($documento) {
                $estado = 'parcial';
            }

            if ($filtros['estado'] !== '' && $estado !== $filtros['estado']) {
                continue;
            }

            $registros[] = [
                'id_documento'            => (int) ($documento['id_documento_asistencia'] ?? 0),
                'docente'                 => $asig['docente'],
                'correo'                  => $asig['correo'],
                'grado'                   => (int) $asig['grado'],
                'seccion'                 => $asig['seccion'],
                'nivel'                   => $asig['nivel'],
                'curso'                   => $asig['curso'],
                'fecha_ultima_carga'      => $documento['fecha_subida'] ?? '',
                'id_documento_asistencia' => (int) ($documento['id_documento_asistencia'] ?? 0),
                'estado'                  => $estado,
                'titulo'                  => $documento['titulo'] ?? '',
                'archivo'                 => $documento['archivo'] ?? '',
            ];
        }

        return $registros;
    }
}
