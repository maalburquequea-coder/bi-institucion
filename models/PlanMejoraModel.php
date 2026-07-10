<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/AIService.php';

class PlanMejoraModel
{
    public function __construct(private PDO $db) {}

    public function buscarPlanCache(int $idEstudiante, float $promedio, int $asistencia, string $areaCritica): ?array
    {
        $stmt = $this->db->prepare("
            SELECT acciones_json
            FROM planes_mejora_ia
            WHERE id_estudiante = ? AND promedio_referencia = ? AND asistencia_referencia = ? AND area_critica = ?
            ORDER BY fecha_generacion DESC LIMIT 1
        ");
        $stmt->execute([$idEstudiante, $promedio, $asistencia, $areaCritica]);
        return $stmt->fetch() ?: null;
    }

    public function buscarUltimoPlanCacheEstudiante(int $idEstudiante): ?array
    {
        $stmt = $this->db->prepare("
            SELECT acciones_json, area_critica
            FROM planes_mejora_ia
            WHERE id_estudiante = ?
            ORDER BY fecha_generacion DESC LIMIT 1
        ");
        $stmt->execute([$idEstudiante]);
        $row = $stmt->fetch();
        if ($row) return $row;

        $stmt = $this->db->prepare("
            SELECT pmi.acciones_json, pmi.area_critica
            FROM planes_mejora_ia pmi
            JOIN estudiantes e_cache ON e_cache.id_estudiante = pmi.id_estudiante
            JOIN estudiantes e_real  ON e_real.id_estudiante  = ?
            WHERE e_cache.nombres  = e_real.nombres
              AND e_cache.apellidos = e_real.apellidos
            ORDER BY pmi.fecha_generacion DESC LIMIT 1
        ");
        $stmt->execute([$idEstudiante]);
        return $stmt->fetch() ?: null;
    }

    public function guardarPlanCache(int $idEstudiante, float $promedio, int $asistencia, string $areaCritica, array $acciones): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO planes_mejora_ia (id_estudiante, promedio_referencia, asistencia_referencia, area_critica, acciones_json)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$idEstudiante, $promedio, $asistencia, $areaCritica, json_encode($acciones)]);
    }

    public function getOrCreatePlanMejoraDocente(int $idEstudiante, array $studentData): array
    {
        $promedio     = (float) $studentData['promedio'];
        $asistencia   = (int)   $studentData['asistencia'];
        $areaCritica  = (string) $studentData['area_critica'];
        $gradoSeccion = (string) $studentData['grado'] . ' ' . $studentData['seccion'];

        $cachedPlan = $this->buscarPlanCache($idEstudiante, $promedio, $asistencia, $areaCritica);
        if ($cachedPlan) {
            return json_decode($cachedPlan['acciones_json'], true);
        }

        $plan = AIService::generarPlanMejora([
            'promedio'    => $promedio,
            'asistencia'  => $asistencia,
            'area_critica'=> $areaCritica,
            'grado'       => $gradoSeccion,
        ]);
        $this->guardarPlanCache($idEstudiante, $promedio, $asistencia, $areaCritica, $plan);
        return $plan;
    }
}
