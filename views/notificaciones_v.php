<?php
$notificaciones = $notificaciones ?? [];

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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'notificaciones.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        @media print {
            @page { size: A4 landscape; margin: 1.5cm; }
            .teacher-sidebar, .no-print { display: none !important; }
            .teacher-page { display: block !important; }
            .teacher-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .print-header { display: block !important; }
            .hero { display: none !important; }
            .panel { border: 1px solid #e2e8f0 !important; box-shadow: none !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10px !important; }
            th, td { border: 1px solid #cbd5e1 !important; padding: 4px 6px !important; }
            thead tr { background: #1e3a5f !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .print-header { display: none; border-bottom: 3px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .print-header h2 { font-size: 17px; margin: 0 0 4px; color: #0f172a; }
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

        <div class="print-header">
            <h2>I.E. N 14008 "Leonor Cerna de Valdiviezo" — Reporte de Notificaciones</h2>
            <small>Generado por <?= e($adminNombre) ?> · <?= date('d/m/Y H:i') ?> · Piura 2026</small>
        </div>

        <section class="hero m-0" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 3rem; border-radius: 1.5rem; display: flex; flex-direction: column; justify-content: center; margin-bottom: 2rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);">
            <p class="eyebrow" style="color: #38bdf8; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 0.5rem;">Alertas</p>
            <h1 style="color: white; font-size: 2.2rem; margin: 0; font-weight: 700; line-height: 1.2;">
                <i class="fas fa-bell" style="color: #38bdf8; margin-right: 12px;"></i> Monitoreo de notificaciones
            </h1>
            <p style="color: #cbd5e1; font-size: 1.1rem; margin-top: 0.75rem; font-weight: 400;">Alertas generadas y estado de envio a usuarios y padres de familia • Piura 2026</p>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div><h2>Todas las alertas generadas</h2></div>
                <div class="no-print" style="display:flex;gap:10px;align-items:center;">
                    <span class="pill"><?= count($notificaciones) ?> notificaciones</span>
                    <a href="<?= BASE_URL ?>notificaciones.php?export=excel"
                       style="background:#0ea5e9;color:white;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-download"></i>Excel
                    </a>
                    <button onclick="window.print()"
                            style="background:#10b981;color:white;border:none;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fas fa-file-pdf"></i>PDF
                    </button>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Origen</th><th>Destinatario</th><th>Referencia</th><th>Canal</th><th>Estado</th><th>Mensaje</th><th>WhatsApp</th><th>Correo</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notificaciones)): ?>
                            <tr><td colspan="9" class="empty">No se encontraron notificaciones registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($notificaciones as $n): ?>
                            <tr>
                                <td><?= e($n['fecha']) ?></td>
                                <td><?= e($n['origen']) ?></td>
                                <td>
                                    <strong><?= e($n['destinatario']) ?></strong>
                                    <small><?= e($n['correo']) ?></small>
                                </td>
                                <td><?= e($n['referencia']) ?></td>
                                <td><?= e($n['canal']) ?></td>
                                <td><span class="risk <?= strtolower((string) ($n['estado'] ?? '')) === 'enviado' ? 'risk-low' : 'risk-medium' ?>"><?= e($n['estado']) ?></span></td>
                                <td><?= e($n['mensaje']) ?></td>
                                <td>
                                    <?php $urlWhatsApp = whatsappUrl($n['telefono'] ?? '', $n['mensaje_whatsapp'] ?? $n['mensaje']); ?>
                                    <?php if ($urlWhatsApp !== ''): ?>
                                        <a class="mini-btn" href="<?= e($urlWhatsApp) ?>" target="_blank" rel="noopener">Enviar</a>
                                    <?php else: ?>
                                        <small>Sin numero</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($n['correo'])): ?>
                                        <?php
                                            $asunto = rawurlencode('Alerta BI Educativo - ' . ($n['referencia'] ?? ''));
                                            $cuerpo = rawurlencode($n['mensaje'] ?? '');
                                        ?>
                                        <a class="mini-btn" style="background:#6366f1;"
                                           href="mailto:<?= e($n['correo']) ?>?subject=<?= $asunto ?>&body=<?= $cuerpo ?>"
                                           target="_blank">Enviar</a>
                                    <?php else: ?>
                                        <small>Sin correo</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
