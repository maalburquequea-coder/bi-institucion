<?php
declare(strict_types=1);

require_once __DIR__ . '/PortalController.php';
require_once __DIR__ . '/DocenteController.php';
require_once __DIR__ . '/PadreController.php';

/**
 * Compatibility shim — delegates to the focused controllers.
 * Entry points (portal.php, docente.php, padre.php) now use the dedicated
 * controllers directly; this class exists only for any direct callers that
 * may still reference DashboardController.
 */
class DashboardController
{
    public function portal(): void  { (new PortalController)->portal(); }
    public function docente(): void { (new DocenteController)->docente(); }
    public function padre(): void   { (new PadreController)->padre(); }
}
