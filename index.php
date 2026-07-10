<?php
require_once __DIR__ . '/config/conexion.php';

$usuario = usuarioActual();

if ($usuario) {
    redirigir('portal.php');
}

redirigir('login.php');
