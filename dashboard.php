<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
requiereLogin();

require_once __DIR__ . '/controllers/EstudianteController.php';

$controller = new EstudianteController();
$controller->dashboard();
