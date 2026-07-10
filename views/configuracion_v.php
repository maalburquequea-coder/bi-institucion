<?php
$mensaje       = $mensaje       ?? '';
$configuracion = $configuracion ?? [];
$periodos      = $periodos      ?? [];

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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'configuracion.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuracion - <?= e(APP_NAME) ?></title>
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
            <p class="eyebrow" style="color: #38bdf8; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 0.5rem;">Parametros</p>
            <h1 style="color: white; font-size: 2.2rem; margin: 0; font-weight: 700; line-height: 1.2;">
                <i class="fas fa-sliders" style="color: #38bdf8; margin-right: 12px;"></i> Configuracion del sistema
            </h1>
            <p style="color: #cbd5e1; font-size: 1.1rem; margin-top: 0.75rem; font-weight: 400;">Gestion de umbrales de riesgo, parametros de analisis de inteligencia artificial y periodos academicos • Piura 2026</p>
        </section>

        <?php if (!empty($mensaje)): ?>
            <div class="alert ok"><?= e($mensaje) ?></div>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-header"><div><h2>Umbrales y parametros IA</h2></div></div>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="accion" value="config">
                <?php if (empty($configuracion)): ?>
                    <p class="empty" style="grid-column: 1 / -1;">No se encontraron parametros de configuracion definidos en la base de datos.</p>
                <?php endif; ?>
                <?php foreach ($configuracion as $item): ?>
                    <label><?= e($item['descripcion']) ?><input type="text" name="config[<?= e($item['clave']) ?>]" value="<?= e($item['valor']) ?>"></label>
                <?php endforeach; ?>
                <button type="submit">Guardar configuracion</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div><p class="eyebrow">Gestion</p><h2>Periodos academicos</h2></div>
                <span class="pill"><?= count($periodos) ?> registros</span>
            </div>

            <form method="get" class="filters" style="margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; align-items: flex-end;">
                <label>Nombre del periodo<input type="text" name="f_nombre" value="<?= e($_GET['f_nombre'] ?? '') ?>" placeholder="Ej. Bimestre..."></label>
                <label>Estado
                    <select name="f_activo">
                        <option value="">Todos</option>
                        <option value="1" <?= ($_GET['f_activo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($_GET['f_activo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </label>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="button-link" style="background:#0ea5e9; color:white; border:none; padding: 8px 16px; border-radius:6px; cursor:pointer; font-weight:600;">Filtrar</button>
                    <a href="<?= BASE_URL ?>configuracion.php" class="button-link" style="text-decoration:none; color:#64748b; font-size:12px; display:flex; align-items:center;">Limpiar</a>
                </div>
            </form>

            <div class="panel-header"><div><p class="eyebrow">Configuracion</p><h3>Registrar nuevo periodo</h3></div></div>
            <form method="post" class="filters" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="accion" value="periodo">
                <label>Nombre<input type="text" name="nombre" placeholder="Bimestre II 2026" required></label>
                <label>Inicio<input type="date" name="fecha_inicio" required></label>
                <label>Fin<input type="date" name="fecha_fin" required></label>
                <label>Activo<input type="checkbox" name="activo" value="1"></label>
                <button type="submit" class="button-link" style="background:#10b981; color:white; border:none; padding: 8px 16px; border-radius:6px; cursor:pointer; font-weight:600; margin-top: 25px;">Agregar</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Periodo</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($periodos)): ?>
                            <tr><td colspan="4" class="empty">No hay periodos academicos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($periodos as $p): ?>
                            <tr>
                                <td><strong><?= e($p['nombre']) ?></strong></td>
                                <td><?= e($p['fecha_inicio']) ?></td>
                                <td><?= e($p['fecha_fin']) ?></td>
                                <td><span class="status-badge <?= (int) $p['activo'] === 1 ? 'ok' : 'pending' ?>"><?= (int) $p['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
