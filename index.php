<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';

$usuario = usuarioActual();

if ($usuario) {
    redirigir('portal.php');
}

redirigir('login.php');
