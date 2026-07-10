<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
class PlanIAController
{
    public function descargar(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();

        $idEstudiante    = (int)($_GET['id_estudiante'] ?? 0);
        $nombreEstudiante = (string)($_GET['nombre'] ?? 'Estudiante');
        $riesgo          = (string)($_GET['riesgo'] ?? 'N/A');

        if ($idEstudiante <= 0) {
            die("ID de estudiante no válido.");
        }

        $auth     = new AuthModel(db());
        $estModel = new EstudianteModel(db());

        $targetId = $idEstudiante;
        $details  = $estModel->getStudentDetailsForPlan($targetId);

        if (!$details) {
            die("No se encontraron datos del estudiante.");
        }

        $planData = $auth->buscarUltimoPlanCacheEstudiante($targetId);

        if (!$planData && $details) {
            $planData = $auth->buscarPlanCache(
                $targetId,
                (float)$details['promedio'],
                (int)$details['asistencia'],
                $details['area_critica']
            );
        }

        $acciones   = $planData ? json_decode($planData['acciones_json'], true) : [];
        $areaCritica = $planData['area_critica'] ?? $details['area_critica'] ?? 'N/A';

        if (empty($acciones)) {
            die("No hay un plan generado para este estudiante. Por favor, genérelo en el panel docente.");
        }

        require __DIR__ . '/../views/plan_ia_v.php';
    }
}
