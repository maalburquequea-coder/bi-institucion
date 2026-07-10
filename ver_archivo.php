<?php
declare(strict_types=1);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/controllers/VisorArchivoController.php';

(new VisorArchivoController)->ver();
