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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'aprobaciones.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aprobaciones - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
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
        <section class="hero m-0" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 3rem; border-radius: 1.5rem; display: flex; flex-direction: column; justify-content: center; margin-bottom: 2rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);">
            <p class="eyebrow" style="color: #38bdf8; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 0.5rem;">Control de acceso</p>
            <h1 style="color: white; font-size: 2.2rem; margin: 0; font-weight: 700; line-height: 1.2;">
                <i class="fas fa-circle-check" style="color: #38bdf8; margin-right: 12px;"></i> Aprobaciones del administrador
            </h1>
            <p style="color: #cbd5e1; font-size: 1.1rem; margin-top: 0.75rem; font-weight: 400;">Revisa cuentas nuevas y solicitudes de vinculacion padre-estudiante • Piura 2026</p>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Pendientes</p>
                    <h2>Solicitudes de registro</h2>
                </div>
                <span class="pill"><?= count($pendientes) ?> pendientes</span>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert ok"><?= e($mensaje) ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Correo</th>
                            <th>DNI estudiante</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendientes)): ?>
                            <tr><td colspan="5" class="empty">No hay solicitudes pendientes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($pendientes as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></strong>
                                    <small>DNI <?= e($row['dni']) ?></small>
                                </td>
                                <td><?= e($row['nombre_rol']) ?></td>
                                <td><?= e($row['correo']) ?></td>
                                <td><?= e($row['dni_estudiante'] ?: '-') ?></td>
                                <td>
                                    <form method="post" class="inline-actions">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="tipo" value="usuario">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $row['id_usuario'] ?>">
                                        <button name="accion" value="aprobar" type="submit">Aprobar</button>
                                        <button name="accion" value="rechazar" type="submit" class="danger-btn">Rechazar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Vinculaciones</p>
                    <h2>Solicitudes padre-estudiante</h2>
                </div>
                <span class="pill"><?= count($vinculaciones) ?> pendientes</span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Padre</th>
                            <th>Estudiante solicitado</th>
                            <th>Grado</th>
                            <th>Fecha</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vinculaciones)): ?>
                            <tr><td colspan="5" class="empty">No hay vinculaciones pendientes.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($vinculaciones as $row): ?>
                            <?php
                                $estudiante = trim((string) (($row['estudiante_nombres'] ?? '') . ' ' . ($row['estudiante_apellidos'] ?? '')));
                                $grado = trim((string) (($row['nivel'] ?? '') . ' ' . ($row['grado'] ?? '') . ' ' . ($row['seccion'] ?? '')));
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['padre_nombres'] . ' ' . $row['padre_apellidos']) ?></strong>
                                    <small><?= e($row['padre_correo']) ?> · DNI <?= e($row['padre_dni']) ?></small>
                                </td>
                                <td>
                                    <strong><?= e($estudiante !== '' ? $estudiante : 'Estudiante no encontrado') ?></strong>
                                    <small>DNI <?= e($row['dni_estudiante']) ?></small>
                                </td>
                                <td><?= e($grado !== '' ? $grado : '-') ?></td>
                                <td><?= e($row['fecha']) ?></td>
                                <td>
                                    <form method="post" class="inline-actions">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="tipo" value="vinculacion">
                                        <input type="hidden" name="id_solicitud" value="<?= (int) $row['id_solicitud'] ?>">
                                        <button name="accion" value="aprobar" type="submit" <?= empty($row['id_estudiante']) ? 'disabled' : '' ?>>Aprobar</button>
                                        <button name="accion" value="rechazar" type="submit" class="danger-btn">Rechazar</button>
                                    </form>
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
