<?php
$usuario       = $usuario       ?? $_SESSION['usuario'] ?? [];
$notificaciones = $notificaciones ?? [];
$hijos         = $hijos         ?? [];
$riesgos       = $riesgos       ?? [];

$portalNombre = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$portalRol    = $usuario['rol'] ?? '';
$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'portal.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        .portal-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin: 0 2rem 1.5rem;
            overflow: hidden;
        }
        .portal-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .portal-card-header .eyebrow {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #0d9488;
            margin: 0 0 2px;
        }
        .portal-card-header h2 { font-size: 1rem; color: #0f172a; margin: 0; }
        .portal-pill {
            background: #f1f5f9;
            color: #475569;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
        }
        .portal-notif-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f8fafc;
            transition: background .15s;
        }
        .portal-notif-item:last-child { border-bottom: none; }
        .portal-notif-item:hover { background: #f8fafc; }
        .portal-empty {
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }
    </style>
</head>
<body class="teacher-page">

    <aside class="teacher-sidebar">
        <div class="teacher-brand">
            <span><?= mb_strtoupper(mb_substr($portalNombre ?: 'A', 0, 1)) ?></span>
            <div>
                <strong><?= e($portalNombre ?: 'Usuario') ?></strong>
                <small><?= e($portalRol) ?></small>
            </div>
        </div>
        <nav>
            <?php if ($portalRol === 'Administrador' || $portalRol === 'Director'): ?>
                <a href="<?= BASE_URL ?>admin.php">
                    <i class="fas fa-gauge" style="width:18px; margin-right:8px; opacity:.75;"></i>Panel principal
                </a>
                <a class="active" href="<?= BASE_URL ?>portal.php">
                    <i class="fas fa-house" style="width:18px; margin-right:8px; opacity:.75;"></i>Mi portal
                </a>
                <a href="<?= BASE_URL ?>usuarios.php">
                    <i class="fas fa-users" style="width:18px; margin-right:8px; opacity:.75;"></i>Usuarios registrados
                </a>
                <a href="<?= BASE_URL ?>asistencia.php">
                    <i class="fas fa-calendar-check" style="width:18px; margin-right:8px; opacity:.75;"></i>Asistencia
                </a>
                <a href="<?= BASE_URL ?>notificaciones.php">
                    <i class="fas fa-bell" style="width:18px; margin-right:8px; opacity:.75;"></i>Notificaciones
                </a>
                <a href="<?= BASE_URL ?>configuracion.php">
                    <i class="fas fa-sliders" style="width:18px; margin-right:8px; opacity:.75;"></i>Configuracion
                </a>
                <a href="<?= BASE_URL ?>auditoria.php">
                    <i class="fas fa-shield-halved" style="width:18px; margin-right:8px; opacity:.75;"></i>Auditoria
                </a>
            <?php else: ?>
                <a class="active" href="<?= BASE_URL ?>portal.php">
                    <i class="fas fa-house" style="width:18px; margin-right:8px; opacity:.75;"></i>Mi portal
                </a>
            <?php endif; ?>

            <?php if (in_array($portalRol, ['Docente', 'Tutor'], true)): ?>
                <a href="<?= BASE_URL ?>docente.php">
                    <i class="fas fa-chalkboard-teacher" style="width:18px; margin-right:8px; opacity:.75;"></i>Modulo docente
                </a>
            <?php endif; ?>

            <?php if ($portalRol === 'Padre'): ?>
                <a href="<?= BASE_URL ?>padre.php">
                    <i class="fas fa-child" style="width:18px; margin-right:8px; opacity:.75;"></i>Modulo padre
                </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>dashboard.php">
                <i class="fas fa-chart-bar" style="width:18px; margin-right:8px; opacity:.75;"></i>Dashboard BI
            </a>
            <a href="<?= BASE_URL ?>logout.php">
                <i class="fas fa-right-from-bracket" style="width:18px; margin-right:8px; opacity:.75;"></i>Cerrar sesion
            </a>
        </nav>
    </aside>

    <main class="teacher-main">

        <section class="teacher-hero" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 3rem 2rem; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;">
            <div>
                <p class="eyebrow" style="color: #38bdf8;">Bienvenido al Portal</p>
                <h1 style="color: white; font-size: 2.5rem; margin: 0.5rem 0;"><?= e($portalNombre) ?></h1>
                <p style="color: #cbd5e1;"><?= e($usuario['correo'] ?? '') ?> <span style="margin: 0 8px;">•</span> Rol: <strong style="color:#fff;"><?= e($portalRol) ?></strong></p>
            </div>
        </section>

        <!-- Notificaciones -->
        <div class="portal-card">
            <div class="portal-card-header">
                <div>
                    <p class="eyebrow">Centro de avisos</p>
                    <h2>Mis notificaciones</h2>
                </div>
                <span class="portal-pill"><?= count($notificaciones) ?> mensajes</span>
            </div>
            <?php if (empty($notificaciones)): ?>
                <p class="portal-empty">Aun no tienes notificaciones.</p>
            <?php else: ?>
                <?php foreach ($notificaciones as $item): ?>
                    <div class="portal-notif-item">
                        <div style="flex:1;">
                            <strong style="color:#0f172a; font-size:14px; display:flex; align-items:center; gap:8px;">
                                <i class="fas fa-circle-info" style="color:#0d9488; font-size:.85rem;"></i>
                                <?= e($item['tipo']) ?>
                            </strong>
                            <small style="color:#64748b; display:block; margin:3px 0 6px;"><?= e($item['canal']) ?> • <?= e($item['fecha']) ?></small>
                            <p style="margin:0; font-size:13px; color:#475569;"><?= e($item['mensaje']) ?></p>
                        </div>
                        <span class="status-badge <?= strtolower($item['estado_correo']) === 'enviado' ? 'ok' : 'pending' ?>" style="flex-shrink:0;">
                            <?= e($item['estado_correo']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Tabla según rol -->
        <?php if ($portalRol === 'Padre'): ?>
            <div class="portal-card">
                <div class="portal-card-header">
                    <div>
                        <p class="eyebrow">Seguimiento familiar</p>
                        <h2>Mis hijos registrados</h2>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Aula</th>
                                <th>Promedio</th>
                                <th>Asistencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($hijos)): ?>
                                <tr>
                                    <td colspan="5" class="empty">
                                        Tu cuenta aun no esta vinculada a estudiantes.<br><br>
                                        <a href="<?= BASE_URL ?>padre.php" class="button-link">Vincular a mi hijo ahora</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($hijos as $hijo): ?>
                                <?php
                                $puntaje = ((float)$hijo['promedio'] < 11 ? 45 : 0)
                                    + ((int)$hijo['cursos_desaprobados'] * 15)
                                    + ((int)$hijo['faltas'] * 8)
                                    + ((int)$hijo['tardanzas'] * 4);
                                $riesgo = riesgoEtiqueta(min(100, $puntaje));
                                ?>
                                <tr>
                                    <td style="padding:1rem 1.5rem;"><strong><?= e($hijo['nombres'] . ' ' . $hijo['apellidos']) ?></strong></td>
                                    <td style="padding:1rem 1.5rem;"><span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:.85rem; color:#475569;"><?= e($hijo['nivel'] . ' ' . $hijo['grado'] . $hijo['seccion']) ?></span></td>
                                    <td style="padding:1rem 1.5rem; font-weight:600; color:<?= (float)$hijo['promedio'] < 11 ? '#ef4444' : '#0f172a' ?>;"><?= number_format((float)$hijo['promedio'], 2) ?></td>
                                    <td style="padding:1rem 1.5rem; color:#64748b;"><?= (int)$hijo['faltas'] ?> faltas / <?= (int)$hijo['tardanzas'] ?> tardanzas</td>
                                    <td><span class="risk <?= riesgoClase($riesgo) ?>"><?= e($riesgo) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="portal-card">
                <div class="portal-card-header">
                    <div>
                        <p class="eyebrow">Gestion docente</p>
                        <h2>Estudiantes con alerta academica</h2>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Aula</th>
                                <th>Promedio</th>
                                <th>Padre/Apoderado</th>
                                <th>Riesgo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riesgos as $row): ?>
                                <?php $riesgo = riesgoEtiqueta((float)$row['puntaje_riesgo']); ?>
                                <tr>
                                    <td><strong><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></strong></td>
                                    <td><?= e($row['nivel'] . ' ' . $row['grado'] . $row['seccion']) ?></td>
                                    <td><?= number_format((float)$row['promedio'], 2) ?></td>
                                    <td><?= e($row['padre'] ?: 'Sin asignar') ?></td>
                                    <td><span class="risk <?= riesgoClase($riesgo) ?>"><?= e($riesgo) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>
