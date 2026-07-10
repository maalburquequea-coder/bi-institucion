<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';

class PadreController
{
    private AuthModel $auth;

    public function __construct()
    {
        $this->auth = new AuthModel(db());
    }

    public function padre(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Padre') {
            redirigir('portal.php');
        }

        $hijos = $this->auth->hijosDetallePadre((int) $usuario['id_usuario']);
        $idEstudiante = (int) ($_GET['hijo'] ?? ($hijos[0]['id_estudiante'] ?? 0));

        $mensaje = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $accion = (string) ($_POST['accion'] ?? '');
            if ($accion === 'solicitar_vinculacion') {
                $identificador = preg_replace('/\D/', '', (string) ($_POST['dni_estudiante'] ?? ''));
                if (strlen($identificador) >= 8 && strlen($identificador) <= 20) {
                    if ($this->auth->vincularEstudianteDirecto((int) $usuario['id_usuario'], $identificador)) {
                        $mensaje = 'Estudiante vinculado correctamente.';
                        $hijos = $this->auth->hijosDetallePadre((int) $usuario['id_usuario']);
                        $idEstudiante = (int) ($hijos[0]['id_estudiante'] ?? 0);
                    } else {
                        $mensaje = 'No se encontro un estudiante con ese DNI o codigo. Revisa los datos e intenta nuevamente.';
                    }
                }
            } elseif ($accion === 'confirmar_plan' && $idEstudiante > 0) {
                $this->auth->confirmarPlanSeguimiento((int) $usuario['id_usuario'], $idEstudiante);
                $mensaje = 'Seguimiento del plan de mejora confirmado correctamente.';
            }
        }

        $seccionActiva = (string) ($_GET['seccion'] ?? 'inicio');
        if ($seccionActiva === 'alertas' && $idEstudiante > 0) {
            $this->auth->marcarNotificacionesPadreLeidas((int) $usuario['id_usuario'], $idEstudiante);
        }

        $filtros = [
            'mes' => (string) ($_GET['filtro_mes'] ?? ''),
            'curso' => (string) ($_GET['filtro_curso'] ?? ''),
        ];

        $panelPadre = $idEstudiante > 0 ? $this->auth->panelPadre((int) $usuario['id_usuario'], $idEstudiante, $filtros) : [];
        $noLeidas = count(array_filter($panelPadre['notificaciones'] ?? [], fn($n) => ($n['estado'] ?? '') !== 'Leido'));

        require __DIR__ . '/../views/padre_v.php';
    }

    private function validarCSRF(): void
    {
        $token       = (string) ($_POST['csrf_token'] ?? '');
        $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
            die('Error de seguridad: Token CSRF invalido o ausente.');
        }
    }
}
