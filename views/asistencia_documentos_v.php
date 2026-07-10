<?php
$filtros = $filtros ?? [
    'fecha'   => date('Y-m-d'),
    'nivel'   => '',
    'grado'   => 0,
    'seccion' => '',
    'estado'  => '',
];
$kpis     = $kpis     ?? ['cargado' => 0, 'pendiente' => 0, 'parcial' => 0];
$registros = $registros ?? [];

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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'asistencia.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de asistencia - <?= e(APP_NAME) ?></title>
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
            <p class="eyebrow" style="color: #38bdf8; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; font-size: 12px; margin-bottom: 0.5rem;">Gestion de Asistencia</p>
            <h1 style="color: white; font-size: 2.2rem; margin: 0; font-weight: 700; line-height: 1.2;">
                <i class="fas fa-calendar-check" style="color: #38bdf8; margin-right: 12px;"></i> Panel de Control de Asistencia Docente
            </h1>
            <p style="color: #cbd5e1; font-size: 1.1rem; margin-top: 0.75rem; font-weight: 400;">Monitoreo y seguimiento de registros de asistencia por grado, seccion y nivel educativo • Piura 2026</p>
        </section>

        <section class="attendance-kpis" aria-label="Indicadores de asistencia">
            <article class="attendance-kpi kpi-loaded">
                <div class="kpi-icon"></div>
                <div class="kpi-content-group">
                    <strong><?= (int) $kpis['cargado'] ?></strong>
                    <span>Registraron<br>asistencia</span>
                </div>
            </article>
            <article class="attendance-kpi kpi-pending">
                <div class="kpi-icon"></div>
                <div class="kpi-content-group">
                    <strong><?= (int) $kpis['pendiente'] ?></strong>
                    <span>Asistencia<br>pendiente</span>
                </div>
            </article>
            <article class="attendance-kpi kpi-partial">
                <div class="kpi-icon"></div>
                <div class="kpi-content-group">
                    <strong><?= (int) $kpis['parcial'] ?></strong>
                    <span>Registro<br>parcial</span>
                </div>
            </article>
        </section>

        <section class="attendance-card">
            <div class="attendance-section-title">
                <div><h2>Buscar estado de carga</h2></div>
                <span><?= count($registros) ?> registros encontrados</span>
            </div>
            <form method="get" class="attendance-filters">
                <label>
                    Nivel educativo
                    <select name="nivel">
                        <option value="">Todos</option>
                        <option value="Primaria" <?= $filtros['nivel'] === 'Primaria' ? 'selected' : '' ?>>Primaria</option>
                        <option value="Secundaria" <?= $filtros['nivel'] === 'Secundaria' ? 'selected' : '' ?>>Secundaria</option>
                    </select>
                </label>
                <label>
                    Grado
                    <select name="grado">
                        <option value="">Todos</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= (int) $filtros['grado'] === $i ? 'selected' : '' ?>><?= $i ?> grado</option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>
                    Seccion
                    <select name="seccion">
                        <option value="">Todas</option>
                        <?php $seccionesFiltro = ($filtros['nivel'] === 'Secundaria') ? ['A', 'B', 'C'] : ['A', 'B', 'C', 'D']; ?>
                        <?php foreach ($seccionesFiltro as $sec): ?>
                            <option value="<?= e($sec) ?>" <?= $filtros['seccion'] === $sec ? 'selected' : '' ?>><?= e($sec) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Estado de carga
                    <select name="estado">
                        <option value="">Todos</option>
                        <option value="cargado" <?= $filtros['estado'] === 'cargado' ? 'selected' : '' ?>>Cargado</option>
                        <option value="pendiente" <?= $filtros['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="parcial" <?= $filtros['estado'] === 'parcial' ? 'selected' : '' ?>>Parcial</option>
                    </select>
                </label>
                <div class="attendance-actions">
                    <button type="submit">Filtrar</button>
                    <a class="button-link clear-link" href="<?= BASE_URL ?>asistencia.php">Limpiar filtros</a>
                    <a class="button-link report-link" href="<?= BASE_URL ?>asistencia.php?<?= e(http_build_query(array_merge($filtros, ['export' => 'excel']))) ?>">Exportar Excel</a>
                </div>
            </form>
        </section>

        <section class="attendance-card">
            <div class="attendance-section-title">
                <div><h2>Estado de registro por docente</h2></div>
                <span><?= count($registros) ?> registros encontrados</span>
            </div>

            <div class="attendance-table-wrap">
                <table class="attendance-table paginated">
                    <thead>
                        <tr>
                            <th>Docente</th>
                            <th>Grado</th>
                            <th>Seccion</th>
                            <th>Nivel</th>
                            <th>Curso</th>
                            <th>Ultima carga</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr><td colspan="8" class="empty">No hay resultados para los filtros seleccionados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($registros as $row): ?>
                            <tr>
                                <td>
                                    <span class="doc-status-dot state-dot-<?= e($row['estado']) ?>"></span>
                                    <strong><?= e($row['docente']) ?></strong>
                                    <small><?= e($row['correo']) ?></small>
                                </td>
                                <td><span class="class-chip"><?= (int) $row['grado'] ?> grado</span></td>
                                <td><span class="section-chip"><?= e($row['seccion']) ?></span></td>
                                <td><span class="level-chip"><?= e($row['nivel']) ?></span></td>
                                <td><?= e($row['curso']) ?></td>
                                <td><?= e($row['fecha_ultima_carga'] ?: 'Sin registro') ?></td>
                                <td>
                                    <span class="load-state state-<?= e($row['estado']) ?>">
                                        <i></i><?= e(ucfirst($row['estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['id_documento'] > 0): ?>
                                        <a href="<?= BASE_URL ?>ver_archivo.php?archivo=<?= e($row['archivo']) ?>" target="_blank" class="button-link" style="padding: 2px 8px; font-size: 12px; background: #0ea5e9;">Ver</a>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Seguro que desea eliminar este registro de asistencia?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="accion" value="eliminar_asistencia">
                                            <input type="hidden" name="id_documento_asistencia" value="<?= (int) ($row['id_documento_asistencia'] ?? $row['id_documento'] ?? 0) ?>">
                                            <button type="submit" class="button-link" style="padding: 2px 8px; font-size: 12px; background: #ef4444; border:none; color:white; cursor:pointer;">Eliminar</button>
                                        </form>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
    document.querySelectorAll('a[target="_blank"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.href.includes('ver_archivo.php')) {
                e.preventDefault();
                window.open(this.href, 'ver_asistencia', 'width=1000,height=800,menubar=no,status=no,toolbar=no');
            }
        });
    });
    </script>
</body>
</html>
