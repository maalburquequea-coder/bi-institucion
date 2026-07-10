<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AIService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/UsuarioModel.php';
require_once __DIR__ . '/NotificacionModel.php';
require_once __DIR__ . '/PlanMejoraModel.php';
require_once __DIR__ . '/AuditoriaModel.php';
require_once __DIR__ . '/ConfiguracionModel.php';
require_once __DIR__ . '/AsistenciaModel.php';

/**
 * Fachada de compatibilidad — compone los 6 modelos especializados.
 * Los controladores pueden usar esta clase sin cambios, o instanciar
 * directamente el modelo especializado que necesiten.
 */
class AuthModel
{
    private UsuarioModel       $usuario;
    private NotificacionModel  $notificacion;
    private PlanMejoraModel    $planMejora;
    private AuditoriaModel     $auditoria;
    private ConfiguracionModel $configuracion;
    private AsistenciaModel    $asistencia;

    public function __construct(private PDO $db)
    {
        $this->usuario       = new UsuarioModel($db);
        $this->notificacion  = new NotificacionModel($db);
        $this->planMejora    = new PlanMejoraModel($db);
        $this->auditoria     = new AuditoriaModel($db);
        $this->configuracion = new ConfiguracionModel($db);
        $this->asistencia    = new AsistenciaModel($db);
    }

    // =========================================================
    // panelPadre — orquesta múltiples modelos; se queda aquí.
    // =========================================================

    public function panelPadre(int $idPadre, int $idEstudiante, array $filtros = []): array
    {
        if ($idEstudiante >= 900) {
            $mockData = [
                900 => ['nombres' => 'Jesus Aymar', 'apellidos' => 'AGUIRRE COBA', 'promedio' => 8.5,  'asistencia' => 70, 'area_critica' => 'EPT - Robótica y Gestión de Proyectos',      'grado' => '4', 'seccion' => 'C', 'riesgo' => 'Alto'],
                901 => ['nombres' => 'Carlos',       'apellidos' => 'Mendoza',      'promedio' => 10.2, 'asistencia' => 78, 'area_critica' => 'Análisis de Curvas de Aprendizaje Digital', 'grado' => '4', 'seccion' => 'B', 'riesgo' => 'Alto'],
            ];
            if (isset($mockData[$idEstudiante])) {
                $m      = $mockData[$idEstudiante];
                $cache  = $this->planMejora->buscarPlanCache($idEstudiante, (float)$m['promedio'], (int)$m['asistencia'], $m['area_critica']);
                $acciones = $cache ? json_decode($cache['acciones_json'], true) : [
                    "Reforzar el módulo de " . $m['area_critica'],
                    "Revisar cronograma de entregas pendientes",
                    "Sesión de retroalimentación con el docente de EPT",
                ];
                return [
                    'hijo'           => ['id_estudiante' => $idEstudiante, 'nombres' => $m['nombres'], 'apellidos' => $m['apellidos'], 'grado' => $m['grado'], 'seccion' => $m['seccion'], 'nivel' => 'Secundaria'],
                    'notas'          => [], 'asistencia' => [], 'notificaciones' => [], 'promedios_area' => [], 'evolucion_notas' => [], 'asistencia_mes' => [],
                    'kpis'           => ['promedio' => $m['promedio'], 'asistencia' => $m['asistencia'], 'alertas' => 1, 'plan_activo' => true, 'riesgo' => $m['riesgo']],
                    'plan'           => ['area' => $m['area_critica'], 'problema' => 'Se detectaron factores de riesgo academico o de asistencia.', 'acciones' => $acciones, 'fecha' => date('Y-m-d'), 'estado' => 'Activo'],
                ];
            }
        }

        $hijo = $this->hijoAutorizado($idPadre, $idEstudiante);
        if (!$hijo) return [];

        $config = [];
        foreach ($this->configuracion->configuracionSistema() as $item) {
            $config[$item['clave']] = $item['valor'];
        }

        $notas          = $this->notasHijo($idEstudiante);
        $asistencia     = $this->asistenciaHijo($idEstudiante, $filtros);
        $notificaciones = $this->notificacionesHijo($idPadre, $idEstudiante);

        $promedio   = !empty($notas) ? array_sum(array_map(fn($r) => (float) $r['nota_final'], $notas)) / count($notas) : 0;
        $totalAsist = count($asistencia);
        $presentes  = count(array_filter($asistencia, fn($r) => in_array($r['estado'], ['Presente', 'Justificado', 'Tardanza'], true)));
        $porcentajeAsistencia = $totalAsist > 0 ? round(($presentes / $totalAsist) * 100) : 100;

        $puntaje = ((float)$promedio < 11 ? (float)($config['peso_promedio_bajo'] ?? 45) : 0)
            + (($porcentajeAsistencia < 80) ? (float)($config['peso_falta'] ?? 8) * 3 : 0)
            + (count(array_filter($notificaciones, fn($r) => ($r['estado'] ?? '') !== 'Leido')) * (float)($config['peso_tardanza'] ?? 4));

        $riesgo      = $this->obtenerEtiquetaRiesgo($puntaje, $config);
        $areaCritica = $this->areaCritica($notas);
        $acciones    = [
            'Revisar las actividades pendientes del area critica.',
            'Acompañar un horario de estudio semanal en casa.',
            'Comunicarse con el docente para seguimiento.',
            'Verificar asistencia y puntualidad durante el bimestre.',
        ];

        $planProblema = 'Se detectaron factores de riesgo academico o de asistencia.';
        $planResuelto = false;

        // Prioridad 1: notificación explícita del docente (siempre más reciente y específica)
        $stmtMsg = $this->db->prepare("
            SELECT mensaje FROM notificaciones
            WHERE id_estudiante = ? AND tipo = 'Plan de Mejora IA'
            ORDER BY fecha_envio DESC LIMIT 1
        ");
        $stmtMsg->execute([$idEstudiante]);
        $mensajeNotif = $stmtMsg->fetchColumn();

        if ($mensajeNotif) {
            if (preg_match('/Estrategia IA \(([^)]+)\):\s*(.+)$/su', $mensajeNotif, $m)) {
                $areaCritica = trim($m[1]);
                $partes      = preg_split('/\.\s+/', trim($m[2]));
                $parsed      = array_values(array_filter(array_map(fn($a) => rtrim(trim($a), '.'), $partes)));
                if (!empty($parsed)) { $acciones = $parsed; }
            } else {
                $acciones = [trim($mensajeNotif)];
            }
            $planProblema = 'Su docente le ha enviado un plan de mejora personalizado para su hijo.';
            $riesgo       = 'Alto';
            $planResuelto = true;
        }

        // Prioridad 2: caché de plan generado por IA (fallback si no hay notificación del docente)
        if (!$planResuelto) {
            $planCache = $this->planMejora->buscarUltimoPlanCacheEstudiante($idEstudiante);
            if ($planCache) {
                $acciones     = json_decode($planCache['acciones_json'], true);
                $areaCritica  = $planCache['area_critica'];
                $planProblema = 'Su docente le ha enviado un plan de mejora personalizado para su hijo.';
                $riesgo       = 'Alto';
                $planResuelto = true;
            }
        }

        // Prioridad 3: generar plan automático por IA según métricas actuales
        if (!$planResuelto && $riesgo !== 'Bajo') {
            $promedioRef = round($promedio, 2);
            $cache       = $this->planMejora->buscarPlanCache($idEstudiante, $promedioRef, $porcentajeAsistencia, $areaCritica);
            if ($cache) {
                $acciones = json_decode($cache['acciones_json'], true);
            } else {
                $acciones = AIService::generarPlanMejora([
                    'promedio'    => $promedioRef,
                    'asistencia'  => $porcentajeAsistencia,
                    'area_critica'=> $areaCritica,
                    'grado'       => $hijo['grado'] . ' ' . $hijo['seccion'],
                ]);
                $this->planMejora->guardarPlanCache($idEstudiante, $promedioRef, $porcentajeAsistencia, $areaCritica, $acciones);
            }
        }

        return [
            'hijo'           => $hijo,
            'notas'          => $notas,
            'asistencia'     => $asistencia,
            'notificaciones' => $notificaciones,
            'promedios_area' => $this->promediosAreaHijo($idEstudiante),
            'evolucion_notas'=> $this->evolucionNotasHijo($idEstudiante),
            'asistencia_mes' => $this->asistenciaMesHijo($idEstudiante),
            'kpis'           => [
                'promedio'    => round($promedio, 2),
                'asistencia'  => $porcentajeAsistencia,
                'alertas'     => count(array_filter($notificaciones, fn($r) => $r['estado'] !== 'Leido')),
                'plan_activo' => $riesgo !== 'Bajo',
                'riesgo'      => $riesgo,
            ],
            'plan' => [
                'area'     => $areaCritica,
                'problema' => $riesgo === 'Bajo' ? '' : $planProblema,
                'acciones' => $acciones,
                'fecha'    => date('Y-m-d'),
                'estado'   => $riesgo === 'Bajo' ? 'Completado' : 'Activo',
            ],
        ];
    }

    public function confirmarPlanSeguimiento(int $idPadre, int $idEstudiante): void
    {
        $this->auditoria->registrarAuditoria($idPadre, 'Plan de Mejora', 'Confirmacion', "Padre confirma seguimiento para estudiante ID: $idEstudiante");
    }

    // =========================================================
    // Helpers privados de panelPadre
    // =========================================================

    private function hijoAutorizado(int $idPadre, int $idEstudiante): ?array
    {
        $stmt = $this->db->prepare("SELECT id_estudiante, dni, nombres, apellidos, nivel, grado, seccion FROM estudiantes WHERE id_padre = ? AND id_estudiante = ? LIMIT 1");
        $stmt->execute([$idPadre, $idEstudiante]);
        return $stmt->fetch() ?: null;
    }

    private function notasHijo(int $idEstudiante): array
    {
        $stmt = $this->db->prepare("SELECT cu.nombre_curso, c.nota_final, c.periodo, 'Unidad/Bimestre' AS tipo_evaluacion, c.fecha_registro FROM calificaciones c JOIN cursos cu ON cu.id_curso = c.id_curso WHERE c.id_estudiante = ? ORDER BY c.fecha_registro DESC, cu.nombre_curso");
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }

    private function promediosAreaHijo(int $idEstudiante): array
    {
        $stmt = $this->db->prepare("SELECT cu.nombre_curso, ROUND(AVG(c.nota_final), 2) AS promedio FROM calificaciones c JOIN cursos cu ON cu.id_curso = c.id_curso WHERE c.id_estudiante = ? GROUP BY cu.id_curso ORDER BY cu.nombre_curso");
        $stmt->execute([$idEstudiante]);
        $promedios = [];
        foreach ($stmt->fetchAll() as $row) {
            $promedios[(string) $row['nombre_curso']] = (float) $row['promedio'];
        }
        $areas = ['Matematica', 'Comunicacion', 'Religion', 'EPT', 'Educacion Fisica', 'Ingles'];
        return array_map(fn(string $area): array => ['nombre_curso' => $area, 'promedio' => $promedios[$area] ?? 0], $areas);
    }

    private function evolucionNotasHijo(int $idEstudiante): array
    {
        $stmt = $this->db->prepare("SELECT periodo, ROUND(AVG(nota_final), 2) AS promedio FROM calificaciones WHERE id_estudiante = ? GROUP BY periodo ORDER BY MIN(fecha_registro)");
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }

    private function asistenciaHijo(int $idEstudiante, array $filtros = []): array
    {
        $sql    = "SELECT fecha, 'Asistencia General' AS curso, estado FROM asistencia WHERE id_estudiante = ?";
        $params = [$idEstudiante];
        if (!empty($filtros['mes'])) { $sql .= " AND DATE_FORMAT(fecha, '%Y-%m') = ?"; $params[] = $filtros['mes']; }
        $sql .= " ORDER BY fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function asistenciaMesHijo(int $idEstudiante): array
    {
        $stmt = $this->db->prepare("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(CASE WHEN estado IN ('Presente','Justificado','Tardanza') THEN 1 ELSE 0 END) AS asistencias, SUM(CASE WHEN estado = 'Falto' THEN 1 ELSE 0 END) AS inasistencias FROM asistencia WHERE id_estudiante = ? GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY mes");
        $stmt->execute([$idEstudiante]);
        return $stmt->fetchAll();
    }

    private function notificacionesHijo(int $idPadre, int $idEstudiante): array
    {
        $stmt = $this->db->prepare("SELECT id_notificacion, fecha_envio, COALESCE(tipo, 'Alerta temprana') AS tipo, mensaje, canal, estado FROM notificaciones WHERE id_padre = ? AND id_estudiante = ? ORDER BY fecha_envio DESC");
        $stmt->execute([$idPadre, $idEstudiante]);
        return $stmt->fetchAll();
    }

    private function areaCritica(array $notas): string
    {
        if (empty($notas)) return 'Sin datos';
        usort($notas, fn($a, $b) => (float) $a['nota_final'] <=> (float) $b['nota_final']);
        return (string) $notas[0]['nombre_curso'];
    }

    private function obtenerEtiquetaRiesgo(float $puntaje, array $config): string
    {
        $alto  = (float)($config['riesgo_alto']  ?? 70);
        $medio = (float)($config['riesgo_medio'] ?? 40);
        if ($puntaje >= $alto)  return 'Alto';
        if ($puntaje >= $medio) return 'Medio';
        return 'Bajo';
    }

    // =========================================================
    // Delegaciones a modelos especializados
    // =========================================================

    // — UsuarioModel —
    public function obtenerRolesRegistro(): array              { return $this->usuario->obtenerRolesRegistro(); }
    public function obtenerRoles(): array                      { return $this->usuario->obtenerRoles(); }
    public function buscarUsuarioPorCorreo(string $c): ?array  { return $this->usuario->buscarUsuarioPorCorreo($c); }
    public function buscarUsuarioPorId(int $id): ?array        { return $this->usuario->buscarUsuarioPorId($id); }
    public function buscarIdUsuarioPorCorreo(string $c): int   { return $this->usuario->buscarIdUsuarioPorCorreo($c); }
    public function buscarIdPadreDeEstudiante(int $id): int    { return $this->usuario->buscarIdPadreDeEstudiante($id); }
    public function registrarUsuario(array $d): int            { return $this->usuario->registrarUsuario($d); }
    public function registrarUsuarioAdmin(array $d): int       { return $this->usuario->registrarUsuarioAdmin($d); }
    public function actualizarUsuarioAdmin(array $d): bool     { return $this->usuario->actualizarUsuarioAdmin($d); }
    public function eliminarUsuario(int $id): bool             { return $this->usuario->eliminarUsuario($id); }
    public function guardarTokenVerificacion(int $id, string $t): bool         { return $this->usuario->guardarTokenVerificacion($id, $t); }
    public function buscarUsuarioPorTokenVerificacion(string $t): ?array       { return $this->usuario->buscarUsuarioPorTokenVerificacion($t); }
    public function verificarCorreoUsuario(int $id): bool      { return $this->usuario->verificarCorreoUsuario($id); }
    public function marcarPrimerLogin(int $id): void           { $this->usuario->marcarPrimerLogin($id); }
    public function usuariosPendientes(): array                 { return $this->usuario->usuariosPendientes(); }
    public function usuariosRegistrados(): array                { return $this->usuario->usuariosRegistrados(); }
    public function resumenAdmin(): array                       { return $this->usuario->resumenAdmin(); }
    public function aprobarUsuario(int $id): bool              { return $this->usuario->aprobarUsuario($id); }
    public function rechazarUsuario(int $id): bool             { return $this->usuario->rechazarUsuario($id); }
    public function crearSolicitudVinculacion(int $ip, string $d): void        { $this->usuario->crearSolicitudVinculacion($ip, $d); }
    public function vincularEstudianteDirecto(int $ip, string $d): bool        { return $this->usuario->vincularEstudianteDirecto($ip, $d); }
    public function solicitudesVinculacionPendientes(): array   { return $this->usuario->solicitudesVinculacionPendientes(); }
    public function aprobarSolicitudVinculacion(int $id): bool  { return $this->usuario->aprobarSolicitudVinculacion($id); }
    public function rechazarSolicitudVinculacion(int $id): bool { return $this->usuario->rechazarSolicitudVinculacion($id); }
    public function hijosDelPadre(int $id): array               { return $this->usuario->hijosDelPadre($id); }
    public function hijosDetallePadre(int $id): array           { return $this->usuario->hijosDetallePadre($id); }
    public function solicitudesPadre(int $id): array            { return $this->usuario->solicitudesPadre($id); }

    // — NotificacionModel —
    public function crearNotificacionUsuario(int $id, string $t, string $m, string $e): void { $this->notificacion->crearNotificacionUsuario($id, $t, $m, $e); }
    public function notificacionesUsuario(int $id): array       { return $this->notificacion->notificacionesUsuario($id); }
    public function registrarNotificacionPadreCorreo(int $ie, int $ip, string $m, string $e): void { $this->notificacion->registrarNotificacionPadreCorreo($ie, $ip, $m, $e); }
    public function marcarNotificacionesPadreLeidas(int $ip, int $ie): void    { $this->notificacion->marcarNotificacionesPadreLeidas($ip, $ie); }
    public function crearNotificacionPlanMejora(int $id, int $ie, string $m): bool { return $this->notificacion->crearNotificacionPlanMejora($id, $ie, $m); }
    public function todasLasNotificaciones(): array             { return $this->notificacion->todasLasNotificaciones(); }

    // — PlanMejoraModel —
    public function buscarPlanCache(int $id, float $p, int $a, string $ar): ?array             { return $this->planMejora->buscarPlanCache($id, $p, $a, $ar); }
    public function buscarUltimoPlanCacheEstudiante(int $id): ?array                           { return $this->planMejora->buscarUltimoPlanCacheEstudiante($id); }
    public function guardarPlanCache(int $id, float $p, int $a, string $ar, array $ac): void  { $this->planMejora->guardarPlanCache($id, $p, $a, $ar, $ac); }
    public function getOrCreatePlanMejoraDocente(int $id, array $d): array                    { return $this->planMejora->getOrCreatePlanMejoraDocente($id, $d); }

    // — AuditoriaModel —
    public function registrarAuditoria(?int $id, string $m, string $a, string $d): void { $this->auditoria->registrarAuditoria($id, $m, $a, $d); }
    public function registrarAcceso(?int $id, string $c, bool $e): void                  { $this->auditoria->registrarAcceso($id, $c, $e); }
    public function contarIntentosFallidos(string $ip, int $min = 10): int               { return $this->auditoria->contarIntentosFallidos($ip, $min); }
    public function auditoriaSistema(): array                   { return $this->auditoria->auditoriaSistema(); }
    public function accesosSistema(): array                     { return $this->auditoria->accesosSistema(); }

    // — ConfiguracionModel —
    public function configuracionSistema(): array               { return $this->configuracion->configuracionSistema(); }
    public function actualizarConfiguracion(array $i): void     { $this->configuracion->actualizarConfiguracion($i); }
    public function periodosAcademicos(array $f = []): array    { return $this->configuracion->periodosAcademicos($f); }
    public function guardarPeriodo(array $d): bool              { return $this->configuracion->guardarPeriodo($d); }

    // — AsistenciaModel —
    public function documentosAsistencia(?string $n, ?int $g): array { return $this->asistencia->documentosAsistencia($n, $g); }
    public function ultimaFechaAsistencia(): string             { return $this->asistencia->ultimaFechaAsistencia(); }
    public function guardarDocumentoAsistencia(array $d): bool  { return $this->asistencia->guardarDocumentoAsistencia($d); }
    public function eliminarDocumentoAsistencia(int $id): bool  { return $this->asistencia->eliminarDocumentoAsistencia($id); }
    public function estadoRegistroAsistencia(array $f): array   { return $this->asistencia->estadoRegistroAsistencia($f); }
}
