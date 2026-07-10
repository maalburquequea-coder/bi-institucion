<?php
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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'admin.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel administrador - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        .admin-quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            padding: 0 2rem 2rem;
        }
        .admin-quick-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            transition: box-shadow .2s, transform .2s;
        }
        .admin-quick-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,.08);
            transform: translateY(-2px);
        }
        .admin-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .admin-card-info strong { display: block; font-size: 14px; color: #0f172a; }
        .admin-card-info p { font-size: 11px; color: #64748b; margin: 3px 0 0; }
        .admin-btn-open {
            background: #0d9488;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s;
        }
        .admin-btn-open:hover { background: #0f766e; }
        .admin-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem 1rem;
        }
        .admin-section-header h2 { font-size: 1.15rem; color: #0f172a; margin: 0; }
        .admin-section-header .pill {
            background: #f1f5f9;
            color: #475569;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
        }
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

        <section class="teacher-hero" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 3rem 2rem; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;">
            <div>
                <p class="eyebrow" style="color: #38bdf8;">I.E. N 14008 "Leonor Cerna de Valdiviezo"</p>
                <h1 style="color: white; font-size: 2.5rem; margin: 0.5rem 0;">Panel Administrativo</h1>
                <p style="color: #cbd5e1;"><?= e($adminNombre) ?> <span style="margin: 0 8px;">•</span> Gestion institucional y monitoreo BI <span style="margin: 0 8px;">•</span> Piura 2026</p>
            </div>
        </section>

        <section class="kpi-container" style="grid-template-columns: repeat(3, 1fr); padding: 0 2rem 2rem;">
            <article class="kpi-card blue">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Usuarios activos</span>
                    <strong class="kpi-value"><?= (int)($resumen['usuarios_activos'] ?? 0) ?></strong>
                </div>
            </article>
            <article class="kpi-card red">
                <div class="kpi-icon"><i class="fas fa-bell"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Alertas generadas</span>
                    <strong class="kpi-value"><?= (int)($resumen['alertas_generadas'] ?? 0) ?></strong>
                </div>
            </article>
            <article class="kpi-card teal">
                <div class="kpi-icon"><i class="fas fa-file-arrow-up"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Cargas del dia</span>
                    <strong class="kpi-value"><?= (int)($resumen['cargas_hoy'] ?? 0) ?></strong>
                </div>
            </article>
        </section>

        <div class="admin-section-header">
            <h2>Accesos rapidos</h2>
            <span class="pill">Configuracion general</span>
        </div>

        <div class="admin-quick-grid">
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#eff6ff; color:#3b82f6;"><i class="fas fa-user-gear"></i></div>
                    <div class="admin-card-info">
                        <strong>Gestion de usuarios</strong>
                        <p>Roles y aprobacion de cuentas.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>usuarios.php">Abrir</a>
            </div>
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#f0fdfa; color:#0d9488;"><i class="fas fa-calendar-check"></i></div>
                    <div class="admin-card-info">
                        <strong>Control de asistencia</strong>
                        <p>Seguimiento de registros docentes.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>asistencia.php">Abrir</a>
            </div>
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#fffbeb; color:#f59e0b;"><i class="fas fa-sliders"></i></div>
                    <div class="admin-card-info">
                        <strong>Configuracion del sistema</strong>
                        <p>Parametros e umbrales IA.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>configuracion.php">Abrir</a>
            </div>
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#f8fafc; color:#64748b;"><i class="fas fa-shield-halved"></i></div>
                    <div class="admin-card-info">
                        <strong>Auditoria y accesos</strong>
                        <p>Historial de seguridad e IPs.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>auditoria.php">Abrir</a>
            </div>
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#fdf4ff; color:#a855f7;"><i class="fas fa-bell"></i></div>
                    <div class="admin-card-info">
                        <strong>Notificaciones</strong>
                        <p>Historial de alertas enviadas.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>notificaciones.php">Abrir</a>
            </div>
            <div class="admin-quick-card">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="admin-icon-wrap" style="background:#ecfdf5; color:#10b981;"><i class="fas fa-chart-bar"></i></div>
                    <div class="admin-card-info">
                        <strong>Dashboard BI</strong>
                        <p>Indicadores y graficos institucionales.</p>
                    </div>
                </div>
                <a class="admin-btn-open" href="<?= BASE_URL ?>dashboard.php">Abrir</a>
            </div>
        </div>

    </main>
</body>
</html>
