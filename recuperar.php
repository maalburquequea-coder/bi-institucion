<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/controllers/AuthController.php';

(new AuthController())->recuperarContrasena();
