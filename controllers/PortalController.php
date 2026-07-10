<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

class PortalController
{
    private AuthModel $auth;

    public function __construct()
    {
        $this->auth = new AuthModel(db());
    }

    public function portal(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        $notificaciones = $this->auth->notificacionesUsuario((int) $usuario['id_usuario']);
        $hijos = $usuario['rol'] === 'Padre' ? $this->auth->hijosDelPadre((int) $usuario['id_usuario']) : [];
        $riesgos = $usuario['rol'] !== 'Padre' ? (new EstudianteModel(db()))->estudiantesEnRiesgo() : [];

        require __DIR__ . '/../views/portal_v.php';
    }
}
