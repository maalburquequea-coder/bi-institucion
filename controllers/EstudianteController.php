<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

class EstudianteController
{
    private EstudianteModel $modelo;

    public function __construct()
    {
        $this->modelo = new EstudianteModel(db());
    }

    public function dashboard(): void
    {
        $this->modelo->generarAlertasWhatsApp();

        $riesgos = $this->modelo->estudiantesEnRiesgo();

        $idsRiesgo = array_column($riesgos, 'id_estudiante');

        $data = [
            'resumen'          => $this->modelo->resumen(),
            'riesgos'          => $riesgos,
            'cursos'           => $this->modelo->rendimientoPorCurso(),
            'asistencia'       => $this->modelo->asistenciaPorGrado(),
            'notificaciones'   => $this->modelo->notificacionesPadres(),
            'notasPorCurso'    => $this->modelo->notasPorCursoEstudiantes($idsRiesgo),
            'evolucionGeneral' => $this->modelo->evolucionNotasGeneral(),
            'evolucionPorEstudiante' => $this->modelo->evolucionNotasPorEstudiante($idsRiesgo),
        ];

        require __DIR__ . '/../views/alertas_v.php';
    }
}
