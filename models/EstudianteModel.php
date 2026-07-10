<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/EmailService.php';

class EstudianteModel
{
    public function __construct(private PDO $db)
    {
    }

    public function resumen(): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM estudiantes) AS estudiantes,
                (SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol WHERE r.nombre_rol = 'Padre') AS padres,
                (SELECT COUNT(*) FROM cursos) AS cursos,
                (SELECT COUNT(*) FROM calificaciones WHERE nota_final < 11) AS notas_criticas,
                (SELECT COUNT(*) FROM asistencia WHERE estado IN ('Falto', 'Tardanza')) AS asistencias_riesgo,
                (SELECT COUNT(*) FROM usuarios WHERE estado_cuenta = 'activo') AS usuarios_activos,
                (SELECT COUNT(*) FROM documentos_asistencia WHERE DATE(fecha_subida) = CURDATE()) AS cargas_hoy,
                (SELECT COUNT(*) FROM notificaciones) AS alertas_generadas
        ";

        return $this->db->query($sql)->fetch() ?: [];
    }

    public function estudiantesEnRiesgo(): array
    {
        $sql = "
            SELECT
                e.id_estudiante,
                e.dni,
                e.nombres,
                e.apellidos,
                e.nivel,
                e.grado,
                e.seccion,
                e.id_padre,
                CONCAT(p.nombres, ' ', p.apellidos) AS padre,
                p.correo AS correo_padre,
                p.telefono AS telefono_padre,
                ROUND(COALESCE(AVG(c.nota_final), 0), 2) AS promedio,
                SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) AS cursos_desaprobados,
                SUM(CASE WHEN a.estado = 'Falto' THEN 1 ELSE 0 END) AS faltas,
                SUM(CASE WHEN a.estado = 'Tardanza' THEN 1 ELSE 0 END) AS tardanzas,
                LEAST(
                    100,
                    (CASE WHEN COALESCE(AVG(c.nota_final), 20) < 11 THEN 45 ELSE 0 END) +
                    (SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) * 15) +
                    (SUM(CASE WHEN a.estado = 'Falto' THEN 1 ELSE 0 END) * 8) +
                    (SUM(CASE WHEN a.estado = 'Tardanza' THEN 1 ELSE 0 END) * 4)
                ) AS puntaje_riesgo
            FROM estudiantes e
            LEFT JOIN usuarios p ON p.id_usuario = e.id_padre
            LEFT JOIN calificaciones c ON c.id_estudiante = e.id_estudiante
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            GROUP BY e.id_estudiante, p.id_usuario
            HAVING puntaje_riesgo > 0
            ORDER BY puntaje_riesgo DESC, promedio ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function rendimientoPorCurso(): array
    {
        $sql = "
            SELECT
                cu.nombre_curso,
                ROUND(AVG(c.nota_final), 2) AS promedio,
                SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) AS desaprobados
            FROM cursos cu
            LEFT JOIN calificaciones c ON c.id_curso = cu.id_curso
            GROUP BY cu.id_curso
            ORDER BY promedio ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function evolucionNotasGeneral(): array
    {
        $sql = "
            SELECT periodo, ROUND(AVG(nota_final), 2) AS promedio
            FROM calificaciones
            WHERE periodo IS NOT NULL AND periodo != ''
            GROUP BY periodo
            ORDER BY CASE periodo
                WHEN 'Bimestre I'   THEN 1 WHEN 'Bimestre II'  THEN 2
                WHEN 'Bimestre III' THEN 3 WHEN 'Bimestre IV'  THEN 4
                WHEN 'Unidad 1'     THEN 5 WHEN 'Unidad 2'     THEN 6
                WHEN 'Unidad 3'     THEN 7 WHEN 'Unidad 4'     THEN 8
                WHEN 'Unidad 5'     THEN 9 WHEN 'Unidad 6'     THEN 10
                ELSE 11
            END
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function evolucionNotasPorEstudiante(array $idsEstudiantes): array
    {
        $idsEstudiantes = array_values(array_unique(array_map('intval', $idsEstudiantes)));
        if (empty($idsEstudiantes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsEstudiantes), '?'));
        $stmt = $this->db->prepare("
            SELECT id_estudiante, periodo, ROUND(AVG(nota_final), 2) AS promedio
            FROM calificaciones
            WHERE id_estudiante IN ($placeholders)
              AND periodo IS NOT NULL AND periodo != ''
            GROUP BY id_estudiante, periodo
            ORDER BY id_estudiante, CASE periodo
                WHEN 'Bimestre I'   THEN 1 WHEN 'Bimestre II'  THEN 2
                WHEN 'Bimestre III' THEN 3 WHEN 'Bimestre IV'  THEN 4
                WHEN 'Unidad 1'     THEN 5 WHEN 'Unidad 2'     THEN 6
                WHEN 'Unidad 3'     THEN 7 WHEN 'Unidad 4'     THEN 8
                WHEN 'Unidad 5'     THEN 9 WHEN 'Unidad 6'     THEN 10
                ELSE 11
            END
        ");
        $stmt->execute($idsEstudiantes);
        return $stmt->fetchAll();
    }

    public function notasPorCursoEstudiantes(array $idsEstudiantes): array
    {
        $idsEstudiantes = array_values(array_unique(array_map('intval', $idsEstudiantes)));
        if (empty($idsEstudiantes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsEstudiantes), '?'));
        $stmt = $this->db->prepare("
            SELECT c.id_estudiante, cu.nombre_curso, c.nota_final
            FROM calificaciones c
            JOIN cursos cu ON cu.id_curso = c.id_curso
            WHERE c.id_estudiante IN ($placeholders)
            ORDER BY cu.nombre_curso
        ");
        $stmt->execute($idsEstudiantes);

        return $stmt->fetchAll();
    }

    public function asistenciaPorGrado(): array
    {
        $sql = "
            SELECT
                CONCAT(e.nivel, ' ', e.grado, e.seccion) AS aula,
                COUNT(a.id_asistencia) AS registros,
                SUM(CASE WHEN a.estado = 'Falto' THEN 1 ELSE 0 END) AS faltas,
                SUM(CASE WHEN a.estado = 'Tardanza' THEN 1 ELSE 0 END) AS tardanzas
            FROM estudiantes e
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            GROUP BY e.nivel, e.grado, e.seccion
            ORDER BY e.nivel, e.grado, e.seccion
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function generarAlertasWhatsApp(): int
    {
        $insertadas = 0;

        foreach ($this->estudiantesEnRiesgo() as $row) {
            if (empty($row['id_estudiante']) || empty($row['id_padre'])) {
                continue;
            }

            $riesgo = riesgoEtiqueta((float) $row['puntaje_riesgo']);
            if ($riesgo === 'Bajo') {
                continue;
            }

            $tipo = ((float) $row['promedio'] < 11 || (int) $row['cursos_desaprobados'] > 0)
                ? 'Bajo rendimiento'
                : 'Alerta temprana';

            $mensaje = 'Alerta temprana: ' . trim($row['nombres'] . ' ' . $row['apellidos'])
                . ' presenta riesgo ' . $riesgo
                . ' por rendimiento academico o asistencia. Promedio: '
                . number_format((float) $row['promedio'], 2)
                . ', faltas: ' . (int) $row['faltas'] . '.';

            if (!empty($row['correo_padre']) && !$this->existeNotificacionCanal((int) $row['id_estudiante'], (int) $row['id_padre'], 'Correo', $mensaje)) {
                $asunto = $tipo . ' - BI Educativo';
                $cuerpo = "Estimado padre/madre de familia,\n\n"
                    . $mensaje . "\n\n"
                    . "Por favor ingrese al portal BI Educativo para revisar el detalle y coordinar el seguimiento correspondiente.\n\n"
                    . "Atentamente,\nI.E. N. 14008";
                $estadoCorreo = EmailService::enviar((string) $row['correo_padre'], $asunto, $cuerpo) ? 'Enviado' : 'Fallido';
                $this->registrarNotificacionPadre((int) $row['id_estudiante'], (int) $row['id_padre'], 'Correo', $tipo, $mensaje, $estadoCorreo);
                $insertadas++;
            }

            if (!$this->existeNotificacionCanal((int) $row['id_estudiante'], (int) $row['id_padre'], 'WhatsApp', $mensaje)) {
                $this->registrarNotificacionPadre((int) $row['id_estudiante'], (int) $row['id_padre'], 'WhatsApp', $tipo, $mensaje, 'Pendiente');
                $insertadas++;
            }
        }

        return $insertadas;
    }

    private function existeNotificacionCanal(int $idEstudiante, int $idPadre, string $canal, string $mensaje): bool
    {
        $stmt = $this->db->prepare("
            SELECT id_notificacion
            FROM notificaciones
            WHERE id_estudiante = ? AND id_padre = ? AND canal = ? AND mensaje = ?
            LIMIT 1
        ");
        $stmt->execute([$idEstudiante, $idPadre, $canal, $mensaje]);

        return (bool) $stmt->fetch();
    }

    private function registrarNotificacionPadre(int $idEstudiante, int $idPadre, string $canal, string $tipo, string $mensaje, string $estado): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado, fecha_envio)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$idEstudiante, $idPadre, $canal, $tipo, $mensaje, $estado]);
    }

    public function enviarPlanPadre(int $idEstudiante, array $plan, string $riesgo, string $areaCritica): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id_estudiante,
                e.nombres,
                e.apellidos,
                e.id_padre,
                CONCAT(p.nombres, ' ', p.apellidos) AS padre,
                p.correo AS correo_padre
            FROM estudiantes e
            LEFT JOIN usuarios p ON p.id_usuario = e.id_padre
            WHERE e.id_estudiante = ?
            LIMIT 1
        ");
        $stmt->execute([$idEstudiante]);
        $estudiante = $stmt->fetch();

        if (!$estudiante || empty($estudiante['id_padre'])) {
            return ['ok' => false, 'message' => 'El estudiante no tiene un padre vinculado.'];
        }

        $acciones = array_values(array_filter(array_map('trim', $plan)));
        $resumenAcciones = implode(' | ', array_slice($acciones, 0, 4));
        $mensaje = 'Plan de mejora IA para ' . trim($estudiante['nombres'] . ' ' . $estudiante['apellidos'])
            . '. Riesgo: ' . $riesgo
            . '. Area: ' . $areaCritica
            . '. Acciones: ' . $resumenAcciones;

        if (strlen($mensaje) > 255) {
            $mensaje = substr($mensaje, 0, 252) . '...';
        }

        $insert = $this->db->prepare("
            INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado, fecha_envio)
            VALUES (?, ?, 'Sistema', 'Plan de Mejora IA', ?, 'Pendiente', NOW())
        ");
        $insert->execute([$idEstudiante, (int) $estudiante['id_padre'], $mensaje]);

        return [
            'ok' => true,
            'message' => 'Plan enviado al padre correctamente.',
            'padre' => $estudiante['padre'] ?? '',
            'correo_padre' => $estudiante['correo_padre'] ?? '',
        ];
    }

    public function resumenDocenteEpt(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                grados.grado,
                COALESCE(base.estudiantes, 0) AS estudiantes,
                COALESCE(notas.promedio_ept, 0) AS promedio_ept,
                COALESCE(notas.desaprobados_ept, 0) AS desaprobados_ept,
                COALESCE(asist.faltas, 0) AS faltas,
                COALESCE(asist.tardanzas, 0) AS tardanzas
            FROM (
                SELECT 2 AS grado
                UNION ALL SELECT 3
                UNION ALL SELECT 4
                UNION ALL SELECT 5
            ) grados
            LEFT JOIN (
                SELECT grado, COUNT(*) AS estudiantes
                FROM estudiantes
                WHERE nivel = 'Secundaria'
                  AND grado BETWEEN 2 AND 5
                GROUP BY grado
            ) base ON base.grado = grados.grado
            LEFT JOIN (
                SELECT
                    e.grado,
                    ROUND(AVG(c.nota_final), 2) AS promedio_ept,
                    SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) AS desaprobados_ept
                FROM estudiantes e
                JOIN calificaciones c ON c.id_estudiante = e.id_estudiante
                JOIN cursos cu ON cu.id_curso = c.id_curso
                WHERE e.nivel = 'Secundaria'
                  AND e.grado BETWEEN 2 AND 5
                  AND c.id_docente = ?
                GROUP BY e.grado
            ) notas ON notas.grado = grados.grado
            LEFT JOIN (
                SELECT
                    e.grado,
                    SUM(CASE WHEN a.estado = 'Falto' THEN 1 ELSE 0 END) AS faltas,
                    SUM(CASE WHEN a.estado = 'Tardanza' THEN 1 ELSE 0 END) AS tardanzas
                FROM estudiantes e
                LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
                WHERE e.nivel = 'Secundaria'
                  AND e.grado BETWEEN 2 AND 5
                GROUP BY e.grado
            ) asist ON asist.grado = grados.grado
            ORDER BY grados.grado
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    public function estudiantesEptPorGrado(int $grado, int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id_estudiante,
                e.nombres,
                e.apellidos,
                e.nivel,
                e.grado,
                e.seccion,
                COALESCE(notas.promedio_ept, 0) AS promedio_ept,
                COALESCE(notas.unidades_desaprobadas, 0) AS unidades_desaprobadas,
                COALESCE(asist.faltas, 0) AS faltas,
                COALESCE(asist.tardanzas, 0) AS tardanzas
            FROM estudiantes e
            LEFT JOIN (
                SELECT
                    c.id_estudiante,
                    ROUND(AVG(c.nota_final), 2) AS promedio_ept,
                    SUM(CASE WHEN c.nota_final < 11 THEN 1 ELSE 0 END) AS unidades_desaprobadas
                FROM calificaciones c
                JOIN cursos cu ON cu.id_curso = c.id_curso
                WHERE c.id_docente = ?
                GROUP BY c.id_estudiante
            ) notas ON notas.id_estudiante = e.id_estudiante
            LEFT JOIN (
                SELECT
                    id_estudiante,
                    SUM(CASE WHEN estado = 'Falto' THEN 1 ELSE 0 END) AS faltas,
                    SUM(CASE WHEN estado = 'Tardanza' THEN 1 ELSE 0 END) AS tardanzas
                FROM asistencia
                GROUP BY id_estudiante
            ) asist ON asist.id_estudiante = e.id_estudiante
            WHERE e.nivel = 'Secundaria'
              AND e.grado = ?
            ORDER BY e.seccion, e.apellidos, e.nombres
        ");
        $stmt->execute([$idDocente, $grado]);

        return $stmt->fetchAll();
    }

    public function unidadesAcademicas(): array
    {
        return [
            ['bimestre' => 'Bimestre I', 'unidades' => ['Unidad 1', 'Unidad 2']],
            ['bimestre' => 'Bimestre II', 'unidades' => ['Unidad 3', 'Unidad 4']],
            ['bimestre' => 'Bimestre III', 'unidades' => ['Unidad 5', 'Unidad 6']],
            ['bimestre' => 'Bimestre IV', 'unidades' => ['Unidad 7', 'Unidad 8']],
        ];
    }

    public function docentePanelCompleto(int $idDocente): array
    {
        $cursos = $this->cursosAsignadosDocente($idDocente);
        $riesgos = $this->riesgosDocente($idDocente);
        $rendimiento = $this->rendimientoEstudiantesDocente($idDocente);
        $notificaciones = $this->notificacionesDocente($idDocente);
        $cargas = $this->cargasDocente($idDocente);
        $promedioGeneral = 0.0;

        if (!empty($rendimiento)) {
            $promedioGeneral = array_sum(array_map(fn ($row) => (float) $row['promedio'], $rendimiento)) / count($rendimiento);
        }

        return [
            'kpis' => [
                'total_estudiantes' => count($rendimiento),
                'alertas_activas' => count(array_filter($riesgos, fn ($row) => $row['riesgo'] !== 'Bajo')),
                'asistencias_pendientes' => max(0, count($cursos) - count(array_filter($cargas, fn ($row) => $row['tipo'] === 'Asistencia' && $row['estado'] === 'Cargado'))),
                'promedio_general' => round($promedioGeneral, 2),
            ],
            'cursos' => $cursos,
            'cargas' => $cargas,
            'riesgos' => $riesgos,
            'notificaciones' => $notificaciones,
            'areas' => $this->promedioPorAreaDocente($idDocente),
            'asistencia_semanal' => $this->asistenciaSemanalDocente($idDocente),
            'rendimiento' => $rendimiento,
            'historial_cargas' => $this->historialCargasDocente($idDocente),
        ];
    }

    public function panelCargaDocente(int $idDocente): array
    {
        $cursos = $this->cursosAsignadosDocente($idDocente);
        return [
            'cursos'           => $cursos,
            'historial_cargas' => $this->historialCargasDocente($idDocente),
            'kpis'             => ['total_estudiantes' => 0, 'alertas_activas' => 0, 'asistencias_pendientes' => 0, 'promedio_general' => 0],
            'cargas'           => [],
            'riesgos'          => [],
            'rendimiento'      => [],
            'notificaciones'   => [],
            'areas'            => [],
            'asistencia_semanal' => [],
        ];
    }

    private function cursosAsignadosDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cu.id_curso,
                cu.nombre_curso,
                e.grado,
                e.seccion,
                e.nivel,
                COUNT(DISTINCT e.id_estudiante) AS estudiantes,
                ROUND(AVG(c.nota_final), 2) AS promedio
            FROM calificaciones c
            JOIN cursos cu ON cu.id_curso = c.id_curso
            JOIN estudiantes e ON e.id_estudiante = c.id_estudiante
            WHERE c.id_docente = ?
            GROUP BY cu.id_curso, e.nivel, e.grado, e.seccion
            ORDER BY cu.nombre_curso, e.grado, e.seccion
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    private function rendimientoEstudiantesDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id_estudiante,
                e.nombres,
                e.apellidos,
                e.grado,
                e.seccion,
                cu.nombre_curso,
                ROUND(AVG(c.nota_final), 2) AS promedio,
                ROUND(100 * SUM(CASE WHEN a.estado IN ('Presente','Justificado','Tardanza') THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id_asistencia), 0), 0) AS asistencia,
                p.telefono AS telefono_padre,
                p.correo AS correo_padre
            FROM calificaciones c
            JOIN estudiantes e ON e.id_estudiante = c.id_estudiante
            JOIN cursos cu ON cu.id_curso = c.id_curso
            LEFT JOIN usuarios p ON p.id_usuario = e.id_padre
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            WHERE c.id_docente = ?
            GROUP BY e.id_estudiante, cu.id_curso
            ORDER BY e.grado, e.seccion, e.apellidos, e.nombres
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    private function riesgosDocente(int $idDocente): array
    {
        $rows = $this->rendimientoEstudiantesDocente($idDocente);
        foreach ($rows as &$row) {
            $promedio = (float) $row['promedio'];
            $asistencia = (int) ($row['asistencia'] ?? 100);
            $puntaje = ($promedio < 11 ? 45 : 0) + ($asistencia < 80 ? 24 : 0);
            $row['area_critica'] = $this->getAreaCriticaForStudent((int) $row['id_estudiante']);
            $row['riesgo'] = riesgoEtiqueta($puntaje);
            $row['motivo'] = $promedio < 11 ? 'Bajo rendimiento' : ($asistencia < 80 ? 'Inasistencia' : 'Seguimiento preventivo');
            $row['fecha_deteccion'] = date('Y-m-d');
        }

        return $rows;
    }

    private function getAreaCriticaForStudent(int $idEstudiante): string
    {
        $stmt = $this->db->prepare("
            SELECT cu.nombre_curso
            FROM calificaciones c
            JOIN cursos cu ON cu.id_curso = c.id_curso
            WHERE c.id_estudiante = ?
            ORDER BY c.nota_final ASC
            LIMIT 1
        ");
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchColumn() ?: 'General';
    }

    private function promedioPorAreaDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT cu.nombre_curso, ROUND(AVG(c.nota_final), 2) AS promedio
            FROM calificaciones c
            JOIN cursos cu ON cu.id_curso = c.id_curso
            WHERE c.id_docente = ?
            GROUP BY cu.id_curso
            ORDER BY cu.nombre_curso
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    private function asistenciaSemanalDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                YEARWEEK(a.fecha, 1) AS semana,
                ROUND(100 * SUM(CASE WHEN a.estado IN ('Presente','Justificado','Tardanza') THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id_asistencia), 0), 0) AS asistencia
            FROM asistencia a
            JOIN (
                SELECT DISTINCT id_estudiante
                FROM calificaciones c
                JOIN cursos cu ON cu.id_curso = c.id_curso
                WHERE c.id_docente = ?
            ) mis ON mis.id_estudiante = a.id_estudiante
            GROUP BY YEARWEEK(a.fecha, 1)
            ORDER BY semana
            LIMIT 8
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    private function cargasDocente(int $idDocente): array
    {
        $cursos = $this->cursosAsignadosDocente($idDocente);
        $stmt = $this->db->prepare("
            SELECT nivel, grado, seccion, MAX(fecha_subida) AS ultima
            FROM documentos_asistencia
            WHERE id_docente = ?
              AND DATE(fecha_subida) = CURDATE()
            GROUP BY nivel, grado, seccion
        ");
        $stmt->execute([$idDocente]);
        $asistencias = [];
        foreach ($stmt->fetchAll() as $row) {
            $asistencias[$row['nivel'] . '-' . $row['grado'] . '-' . $row['seccion']] = $row['ultima'];
        }

        $cargas = [];
        foreach ($cursos as $curso) {
            $key = $curso['nivel'] . '-' . $curso['grado'] . '-' . $curso['seccion'];
            $cargas[] = [
                'curso' => $curso['nombre_curso'],
                'grado' => $curso['grado'],
                'seccion' => $curso['seccion'],
                'tipo' => 'Asistencia',
                'estado' => isset($asistencias[$key]) ? 'Cargado' : 'Pendiente',
            ];
            $cargas[] = [
                'curso' => $curso['nombre_curso'],
                'grado' => $curso['grado'],
                'seccion' => $curso['seccion'],
                'tipo' => 'Notas',
                'estado' => (float) $curso['promedio'] > 0 ? 'Cargado' : 'Pendiente',
            ];
        }

        return $cargas;
    }

    private function historialCargasDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT id_documento AS id_documento_asistencia, titulo, nivel, grado, seccion, fecha_subida, archivo
            FROM documentos_asistencia
            WHERE id_docente = ?
            ORDER BY fecha_subida DESC
            LIMIT 40
        ");
        $stmt->execute([$idDocente]);

        return $stmt->fetchAll();
    }

    public function buscarEstudianteDocente(int $idDocente, string $query): array
    {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT
                e.id_estudiante,
                e.dni,
                e.nombres,
                e.apellidos,
                e.nivel,
                e.grado,
                e.seccion,
                ROUND(AVG(c.nota_final), 2) AS promedio,
                ROUND(100 * SUM(CASE WHEN a.estado IN ('Presente','Justificado','Tardanza') THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(DISTINCT a.id_asistencia), 0), 0) AS asistencia
            FROM estudiantes e
            JOIN calificaciones c ON c.id_estudiante = e.id_estudiante
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            WHERE c.id_docente = ?
              AND (e.nombres LIKE ? OR e.apellidos LIKE ? OR e.dni LIKE ?
                   OR CONCAT(e.nombres, ' ', e.apellidos) LIKE ?)
            GROUP BY e.id_estudiante
            ORDER BY e.apellidos, e.nombres
            LIMIT 20
        ");
        $stmt->execute([$idDocente, $like, $like, $like, $like]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['area_critica'] = $this->getAreaCriticaForStudent((int) $row['id_estudiante']);
            $promedio   = (float) $row['promedio'];
            $asistencia = (int) ($row['asistencia'] ?? 100);
            $puntaje    = ($promedio < 11 ? 45 : 0) + ($asistencia < 80 ? 24 : 0);
            $row['riesgo'] = riesgoEtiqueta($puntaje);
        }

        return $rows;
    }

    public function getStudentDetailsForPlan(int $idEstudiante): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id_estudiante,
                e.nombres,
                e.apellidos,
                e.grado,
                e.seccion,
                ROUND(AVG(c.nota_final), 2) AS promedio,
                ROUND(100 * SUM(CASE WHEN a.estado IN ('Presente','Justificado','Tardanza') THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id_asistencia), 0), 0) AS asistencia_porcentaje
            FROM estudiantes e
            LEFT JOIN calificaciones c ON c.id_estudiante = e.id_estudiante
            LEFT JOIN asistencia a ON a.id_estudiante = e.id_estudiante
            WHERE e.id_estudiante = ?
            GROUP BY e.id_estudiante
        ");
        $stmt->execute([$idEstudiante]);
        $studentData = $stmt->fetch();

        if (!$studentData) {
            return null;
        }

        $studentData['area_critica'] = $this->getAreaCriticaForStudent($idEstudiante);
        $studentData['asistencia'] = (int) $studentData['asistencia_porcentaje']; 
        unset($studentData['asistencia_porcentaje']);

        return $studentData;
    }

    private function notificacionesDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT
                n.id_estudiante,
                n.id_padre,
                CONCAT(e.nombres, ' ', e.apellidos) AS estudiante,
                n.tipo,
                n.fecha_envio,
                n.canal,
                n.estado,
                n.mensaje,
                p.telefono,
                p.correo
            FROM notificaciones n
            JOIN estudiantes e ON e.id_estudiante = n.id_estudiante
            LEFT JOIN usuarios p ON p.id_usuario = n.id_padre
            WHERE n.id_estudiante IN (
                SELECT DISTINCT c.id_estudiante
                FROM calificaciones c
                WHERE c.id_docente = ?
            )
            ORDER BY n.fecha_envio DESC
            LIMIT 50
        ");
        $stmt->execute([$idDocente]);
        $rows = $stmt->fetchAll();

        return $rows ?: [];
    }

    public function notificacionesPadres(): array
    {
        $sql = "
            SELECT
                n.fecha_envio,
                n.canal,
                n.estado,
                n.tipo,
                n.mensaje,
                CONCAT(e.nombres, ' ', e.apellidos) AS estudiante,
                CONCAT(p.nombres, ' ', p.apellidos) AS padre,
                p.correo,
                p.telefono,
                CASE
                    WHEN p.telefono IS NULL OR p.telefono = '' THEN ''
                    ELSE CONCAT('Hola ', p.nombres, ', le informamos que ', e.nombres, ' ', e.apellidos, ' presenta una alerta academica en la I.E. N. 14008: ', n.mensaje, ' Por favor revise el sistema BI Educativo o comuniquese con la institucion.')
                END AS mensaje_whatsapp
            FROM notificaciones n
            JOIN estudiantes e ON e.id_estudiante = n.id_estudiante
            JOIN usuarios p ON p.id_usuario = n.id_padre
            ORDER BY n.fecha_envio DESC
            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }
}
