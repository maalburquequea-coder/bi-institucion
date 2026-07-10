<?php
$acciones ??= [];
$accesos  ??= [];

$adminNombre = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$adminRol    = $usuario['rol'] ?? 'Administrador';

$menuAdmin = [
    'admin.php'          => ['icono' => 'fa-gauge',          'texto' => 'Panel principal'],
    'usuarios.php'       => ['icono' => 'fa-users',          'texto' => 'Usuarios'],
    'asistencia.php'     => ['icono' => 'fa-calendar-check', 'texto' => 'Asistencia'],
    'notificaciones.php' => ['icono' => 'fa-bell',           'texto' => 'Notificaciones'],
    'configuracion.php'  => ['icono' => 'fa-sliders',        'texto' => 'Configuracion'],
    'auditoria.php'      => ['icono' => 'fa-shield-halved',  'texto' => 'Auditoria'],
    'dashboard.php'      => ['icono' => 'fa-chart-bar',      'texto' => 'Dashboard BI'],
];

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'auditoria.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoria - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        @media print {
            @page { size: A4 landscape; margin: 1.5cm; }

            .teacher-sidebar,
            .no-print { display: none !important; }

            .teacher-page { display: block !important; }
            .teacher-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }

            .print-header { display: block !important; }

            .hero { display: none !important; }

            .panel {
                border: 1px solid #e2e8f0 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                margin-bottom: 1.5rem !important;
            }
            .panel-header { border-bottom: 2px solid #0f172a !important; }

            table { width: 100% !important; border-collapse: collapse !important; font-size: 11px !important; }
            th, td { border: 1px solid #cbd5e1 !important; padding: 5px 8px !important; }
            thead tr { background: #1e3a5f !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            .status-badge { border: 1px solid currentColor !important; }
        }

        .print-header {
            display: none;
            border-bottom: 3px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .print-header h2 { font-size: 18px; margin: 0 0 4px; color: #0f172a; }
        .print-header small { font-size: 11px; color: #64748b; }
    </style>
</head>
<body class="teacher-page">

    <aside class="teacher-sidebar">
        <div class="teacher-brand">
            <span>A</span>
            <div>
                <strong><?= e($adminNombre ?: 'Administrador') ?></strong>
                <small><?= e($adminRol) ?></small>
            </div>
        </div>
        <nav>
            <?php foreach ($menuAdmin as $url => $item): ?>
                <a class="<?= $paginaActual === $url ? 'active' : '' ?>"
                   href="<?= BASE_URL . e($url) ?>">
                    <i class="fas <?= e($item['icono']) ?>" style="width:18px; margin-right:8px; opacity:.75;"></i>
                    <?= e($item['texto']) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>logout.php">
                <i class="fas fa-right-from-bracket" style="width:18px; margin-right:8px; opacity:.75;"></i>
                Cerrar sesion
            </a>
        </nav>
    </aside>

    <main class="teacher-main">

        <!-- Cabecera solo visible al imprimir -->
        <div class="print-header">
            <h2>I.E. N 14008 "Leonor Cerna de Valdiviezo" — Auditoria del sistema</h2>
            <small>Generado por <?= e($adminNombre) ?> · <?= date('d/m/Y H:i') ?> · Piura 2026</small>
        </div>

        <section class="hero m-0" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 3rem; border-radius: 1.5rem; display: flex; flex-direction: column; justify-content: center; margin-bottom: 2rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);">
            <p class="eyebrow" style="color: #38bdf8; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 0.5rem;">Seguimiento</p>
            <h1 style="color: white; font-size: 2.2rem; margin: 0; font-weight: 700; line-height: 1.2;">
                <i class="fas fa-shield-halved" style="color: #38bdf8; margin-right: 12px;"></i> Auditoria del sistema
            </h1>
            <p style="color: #cbd5e1; font-size: 1.1rem; margin-top: 0.75rem; font-weight: 400;">Historial de acciones realizadas, registro detallado de accesos y trazabilidad de actividades dentro de la plataforma • Piura 2026</p>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div><h2>Historial de acciones</h2></div>
                <div class="no-print" style="display:flex;gap:10px;align-items:center;">
                    <span class="pill"><?= count($acciones) ?> registros</span>
                    <a href="<?= BASE_URL ?>auditoria.php?export=acciones"
                       style="background:#0ea5e9;color:white;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-download"></i>Excel
                    </a>
                    <button onclick="window.print()"
                            style="background:#10b981;color:white;border:none;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-file-pdf"></i>PDF
                    </button>
                </div>
                <div class="print-only" style="font-size:12px;color:#64748b;"><?= count($acciones) ?> registros</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Usuario</th><th>Modulo</th><th>Accion</th><th>Detalle</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($acciones)): ?>
                            <tr><td colspan="6" class="empty">No se encontraron registros de auditoria.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($acciones as $a): ?>
                            <tr>
                                <td><?= e($a['fecha']) ?></td>
                                <td><?= e($a['usuario'] ?: '-') ?></td>
                                <td><?= e($a['modulo']) ?></td>
                                <td><?= e($a['accion']) ?></td>
                                <td><?= e($a['detalle']) ?></td>
                                <td><?= e($a['ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div><h2>Registro de accesos</h2></div>
                <div class="no-print" style="display:flex;gap:10px;align-items:center;">
                    <span class="pill"><?= count($accesos) ?> accesos</span>
                    <a href="<?= BASE_URL ?>auditoria.php?export=accesos"
                       style="background:#0ea5e9;color:white;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-download"></i>Excel
                    </a>
                    <button onclick="window.print()"
                            style="background:#10b981;color:white;border:none;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-file-pdf"></i>PDF
                    </button>
                </div>
                <div class="print-only" style="font-size:12px;color:#64748b;"><?= count($accesos) ?> accesos</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Correo</th><th>Usuario</th><th>Resultado</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accesos)): ?>
                            <tr><td colspan="5" class="empty">No hay registros de acceso recientes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($accesos as $a): ?>
                            <tr>
                                <td><?= e($a['fecha']) ?></td>
                                <td><?= e($a['correo']) ?></td>
                                <td><?= e($a['usuario'] ?: '-') ?></td>
                                <td><span class="status-badge <?= (int) $a['exito'] === 1 ? 'ok' : 'pending' ?>"><?= (int) $a['exito'] === 1 ? 'Correcto' : 'Fallido' ?></span></td>
                                <td><?= e($a['ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</body>
</html>
