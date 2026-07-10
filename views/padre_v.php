<?php
$usuario     = $usuario ?? $_SESSION['usuario'] ?? [];
$padreNombre = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
$menu = [
    'inicio'      => 'Mi portal',
    'rendimiento' => 'Rendimiento académico',
    'asistencia'  => 'Asistencia',
    'alertas'     => 'Alertas y notificaciones',
    'plan'        => 'Plan de mejora',
];
$iconos = [
    'inicio'      => 'fa-house',
    'rendimiento' => 'fa-chart-line',
    'asistencia'  => 'fa-calendar-check',
    'alertas'     => 'fa-bell',
    'plan'        => 'fa-lightbulb',
];
$hijo          = $panelPadre['hijo'] ?? null;
$kpis          = $panelPadre['kpis'] ?? ['promedio' => 0, 'asistencia' => 0, 'alertas' => 0, 'plan_activo' => false, 'riesgo' => 'Bajo'];
$notas         = $panelPadre['notas'] ?? [];
$notificaciones= $panelPadre['notificaciones'] ?? [];
$asistencia    = $panelPadre['asistencia'] ?? [];
$promediosArea = $panelPadre['promedios_area'] ?? [];
$evolucionNotas= $panelPadre['evolucion_notas'] ?? [];
$asistenciaMes = $panelPadre['asistencia_mes'] ?? [];
$plan          = $panelPadre['plan'] ?? ['area' => '', 'problema' => '', 'acciones' => [], 'fecha' => '', 'estado' => ''];
$hijos         = isset($hijos) && is_array($hijos) ? $hijos : [];
$mensaje       = $mensaje ?? '';
$seccionActiva = $seccionActiva ?? 'inicio';
$idEstudiante  = (int) ($idEstudiante ?? 0);
$noLeidas      = (int) ($noLeidas ?? 0);

// Preparar datos para Chart.js
$chartLabelsArea    = json_encode(array_column($promediosArea, 'nombre_curso'));
$chartDataArea      = json_encode(array_map(fn($r) => (float)$r['promedio'], $promediosArea));
$chartColorsArea    = json_encode(array_map(fn($r) => (float)$r['promedio'] >= 14 ? '#16a34a' : ((float)$r['promedio'] >= 11 ? '#d97706' : '#dc2626'), $promediosArea));
$chartLabelsPeriodo = json_encode(array_column($evolucionNotas, 'periodo'));
$chartDataPeriodo   = json_encode(array_map(fn($r) => (float)$r['promedio'], $evolucionNotas));
$chartLabelsMes     = json_encode(array_column($asistenciaMes, 'mes'));
$chartDataPresentes = json_encode(array_map(fn($r) => (int)$r['asistencias'], $asistenciaMes));
$chartDataAusentes  = json_encode(array_map(fn($r) => (int)$r['inasistencias'], $asistenciaMes));
$totalAsist   = array_sum(array_column($asistenciaMes, 'asistencias')) + array_sum(array_column($asistenciaMes, 'inasistencias'));
$totalPresent = array_sum(array_column($asistenciaMes, 'asistencias'));

// Umbrales para color de KPIs
$colorPromedio  = (float)$kpis['promedio'] >= 14 ? 'kpi-green' : ((float)$kpis['promedio'] >= 11 ? 'kpi-yellow' : 'kpi-red');
$colorAsistencia= (int)$kpis['asistencia'] >= 85 ? 'kpi-green' : ((int)$kpis['asistencia'] >= 70 ? 'kpi-yellow' : 'kpi-red');
$periodosUnicos = array_unique(array_column($notas, 'periodo'));
$tiposUnicos    = array_unique(array_column($notificaciones, 'tipo'));
$canalesUnicos  = array_unique(array_column($notificaciones, 'canal'));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel padre - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ── KPI color variants ── */
        .kpi-green { border-left-color: #16a34a !important; }
        .kpi-green strong { color: #16a34a; }
        .kpi-yellow { border-left-color: #d97706 !important; }
        .kpi-yellow strong { color: #d97706; }
        .kpi-red { border-left-color: #dc2626 !important; }
        .kpi-red strong { color: #dc2626; }
        /* ── KPI mini icon ── */
        .kpi-icon { font-size: 22px; float: right; opacity: .25; margin-top: -4px; }
        /* ── Chart container ── */
        .chart-wrap { position: relative; width: 100%; }
        /* ── Recent notification badge ── */
        .recently-sent { border-left: 4px solid #0ea5e9 !important; background: #f0f9ff !important; }
        .recent-badge { display:inline-block; padding:2px 6px; font-size:10px; font-weight:700;
                        text-transform:uppercase; background:#0ea5e9; color:#fff; border-radius:4px; margin-left:6px; }
        /* ── Plan steps ── */
        .plan-steps { list-style: none; padding: 0; margin: 16px 0; display: grid; gap: 10px; }
        .plan-steps li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px;
                         background: #f8fafc; border-radius: 10px; border-left: 4px solid #0d9488; font-size: 14px; }
        .plan-steps li .step-num { font-weight: 800; color: #0d9488; min-width: 20px; }
        /* ── Action buttons row ── */
        .action-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .btn-dl { display: inline-flex; align-items: center; gap: 7px; padding: 10px 16px;
                  border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none;
                  border: none; cursor: pointer; transition: opacity .15s; }
        .btn-dl:hover { opacity: .85; }
        .btn-dl-blue  { background: #0284c7; color: #fff; }
        .btn-dl-teal  { background: #0d9488; color: #fff; }
        .btn-dl-green { background: #16a34a; color: #fff; }
        /* ── Unread row ── */
        .unread-row td:first-child { border-left: 3px solid #0ea5e9; }
        /* ── Riesgo badge en hero ── */
        .riesgo-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px;
                        font-weight: 700; margin-left: 10px; vertical-align: middle; }
        .riesgo-alto   { background: #fee2e2; color: #dc2626; }
        .riesgo-medio  { background: #fef3c7; color: #d97706; }
        .riesgo-bajo   { background: #dcfce7; color: #16a34a; }
        /* ── Asistencia resumen donut ── */
        .asist-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: center; }
        @media(max-width:640px){ .asist-summary { grid-template-columns: 1fr; } .action-row { flex-direction: column; } }
    </style>
</head>
<body class="parent-page">
    <aside class="parent-sidebar">
        <div class="parent-brand">
            <div class="brand-avatar"><i class="fa-solid fa-user-tie"></i></div>
            <div>
                <strong><?= e($padreNombre) ?></strong>
                <small><i class="fa-solid fa-circle-check"></i> Familiar</small>
            </div>
        </div>
        <nav>
            <?php foreach ($menu as $clave => $texto): ?>
                <a class="<?= $seccionActiva === $clave ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>padre.php?seccion=<?= e($clave) ?>&hijo=<?= $idEstudiante ?>">
                    <i class="fa-solid <?= $iconos[$clave] ?> nav-icon-bi"></i>
                    <span><?= e($texto) ?></span>
                    <?= $clave === 'alertas' && $noLeidas > 0 ? '<b class="badge-nav">' . $noLeidas . '</b>' : '' ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket nav-icon-bi"></i> <span>Cerrar sesión</span>
            </a>
        </nav>
    </aside>

    <main class="parent-main">
        <?php if (!empty($mensaje)): ?>
            <div class="alert ok"><?= e($mensaje) ?></div>
        <?php endif; ?>

        <?php if (empty($hijos)): ?>
            <!-- Vinculación -->
            <section class="parent-card parent-link-card">
                <p class="eyebrow">Vinculación de estudiante</p>
                <h1>Datos del estudiante</h1>
                <p>Coloque los datos del estudiante para vincularlo automáticamente.</p>
                <form method="post" action="<?= BASE_URL ?>padre.php" class="parent-filters"
                      onsubmit="return confirm('¿Está segura de enviar estos datos y vincular automáticamente al estudiante?');">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="accion" value="solicitar_vinculacion">
                    <label>DNI o código del estudiante<input name="dni_estudiante" maxlength="20" inputmode="numeric" required></label>
                    <label>Nombre completo<input name="nombre_estudiante" required></label>
                    <label>Grado<input name="grado" required></label>
                    <button class="parent-primary" type="submit">Enviar datos</button>
                </form>
            </section>
        <?php else: ?>

            <!-- Hero header -->
            <section class="parent-hero">
                <div>
                    <p class="eyebrow">Panel padre</p>
                    <h1>
                        <?= e($menu[$seccionActiva]) ?>
                        <?php if ($seccionActiva === 'inicio' && isset($kpis['riesgo'])): ?>
                            <span class="riesgo-badge riesgo-<?= strtolower($kpis['riesgo']) ?>">
                                Riesgo <?= e($kpis['riesgo']) ?>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <p><?= e($padreNombre) ?> — Seguimiento de <strong><?= e($hijo['nombres'] . ' ' . $hijo['apellidos']) ?></strong>
                       · <?= e($hijo['grado']) ?>° <?= e($hijo['seccion']) ?>.</p>
                </div>
                <?php if (count($hijos) > 1): ?>
                    <form>
                        <input type="hidden" name="seccion" value="<?= e($seccionActiva) ?>">
                        <select name="hijo" onchange="this.form.submit()">
                            <?php foreach ($hijos as $item): ?>
                                <option value="<?= (int)$item['id_estudiante'] ?>"
                                    <?= (int)$item['id_estudiante'] === $idEstudiante ? 'selected' : '' ?>>
                                    <?= e($item['nombres'] . ' ' . $item['apellidos']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </section>

            <!-- ══════════════════════════════════════════════════════
                 INICIO
            ══════════════════════════════════════════════════════ -->
            <?php if ($seccionActiva === 'inicio'): ?>

                <section class="parent-kpis">
                    <article class="parent-card <?= $colorPromedio ?>">
                        <i class="fa-solid fa-graduation-cap kpi-icon"></i>
                        <span>Promedio General</span>
                        <strong><?= number_format((float)$kpis['promedio'], 2) ?></strong>
                        <small style="color:#94a3b8; font-size:12px;">
                            <?= (float)$kpis['promedio'] >= 14 ? 'Destacado' : ((float)$kpis['promedio'] >= 11 ? 'En proceso' : 'Necesita apoyo') ?>
                        </small>
                    </article>
                    <article class="parent-card <?= $colorAsistencia ?>">
                        <i class="fa-solid fa-calendar-check kpi-icon"></i>
                        <span>Asistencia</span>
                        <strong><?= (int)$kpis['asistencia'] ?>%</strong>
                        <small style="color:#94a3b8; font-size:12px;">
                            <?= (int)$kpis['asistencia'] >= 85 ? 'Regular' : ((int)$kpis['asistencia'] >= 70 ? 'Con faltas' : 'Crítica') ?>
                        </small>
                    </article>
                    <article class="parent-card <?= (int)$kpis['alertas'] > 0 ? 'danger' : '' ?>">
                        <i class="fa-solid fa-bell kpi-icon"></i>
                        <span>Notificaciones</span>
                        <strong><?= (int)$kpis['alertas'] ?></strong>
                        <small style="color:#94a3b8; font-size:12px;">
                            <?= (int)$kpis['alertas'] > 0 ? 'Sin leer' : 'Al día' ?>
                        </small>
                    </article>
                    <article class="parent-card <?= $kpis['plan_activo'] ? 'kpi-yellow' : 'kpi-green' ?>">
                        <i class="fa-solid fa-lightbulb kpi-icon"></i>
                        <span>Plan de Mejora</span>
                        <strong style="font-size:20px;"><?= $kpis['plan_activo'] ? 'Activo' : 'Sin alertas' ?></strong>
                        <small style="color:#94a3b8; font-size:12px;">
                            <?= $kpis['plan_activo'] ? 'Revisar el plan' : 'Todo en orden' ?>
                        </small>
                    </article>
                </section>

                <?php if (!empty($promediosArea)): ?>
                <section class="parent-card">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-chart-bar" style="color:#0d9488; margin-right:6px;"></i>Promedio por área</h2>
                    </div>
                    <div class="chart-wrap" style="height:220px;">
                        <canvas id="chartInicioArea"></canvas>
                    </div>
                </section>
                <?php endif; ?>

                <section class="parent-card">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-bell" style="color:#0d9488; margin-right:6px;"></i>Últimas notificaciones</h2>
                        <a class="button-link" href="<?= BASE_URL ?>padre.php?seccion=alertas&hijo=<?= $idEstudiante ?>">Ver todas</a>
                    </div>
                    <div class="notifications-list">
                        <?php if (empty($notificaciones)): ?>
                            <p class="empty" style="padding:12px;">No hay notificaciones recientes.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($notificaciones, 0, 3) as $item):
                                $esReciente = (time() - strtotime($item['fecha_envio'])) < 86400;
                            ?>
                                <article class="<?= $item['estado'] !== 'Leido' ? 'unread' : '' ?> <?= $esReciente ? 'recently-sent' : '' ?>">
                                    <strong>
                                        <?= e($item['tipo']) ?>
                                        <?php if ($esReciente): ?><span class="recent-badge">Nuevo</span><?php endif; ?>
                                    </strong>
                                    <p><?= e($item['mensaje']) ?></p>
                                    <small><?= e($item['fecha_envio']) ?> · <?= e($item['estado']) ?> (<?= e($item['canal']) ?>)</small>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════
                 RENDIMIENTO
            ══════════════════════════════════════════════════════ -->
            <?php if ($seccionActiva === 'rendimiento'): ?>

                <section class="parent-card">
                    <form class="parent-filters" id="form-filtro-rendimiento">
                        <label>Área Curricular
                            <select id="rend-filtro-area">
                                <option value="">Todas</option>
                                <?php foreach ($promediosArea as $row): ?>
                                    <option value="<?= e($row['nombre_curso']) ?>"><?= e($row['nombre_curso']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Período
                            <select id="rend-filtro-periodo">
                                <option value="">Todos</option>
                                <?php foreach ($periodosUnicos as $p): ?>
                                    <option value="<?= e($p) ?>"><?= e($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="button" id="btn-limpiar-rend" class="mini-btn" style="margin-top:25px; background:#64748b;">Limpiar</button>
                    </form>
                    <p id="resultado-rend" style="font-size:13px; color:#64748b; margin:8px 0 0 4px;"></p>
                </section>

                <section class="parent-grid" style="grid-template-columns: 1fr 1fr;">
                    <article class="parent-card">
                        <h2 style="margin-bottom:16px;"><i class="fa-solid fa-chart-bar" style="color:#0d9488; margin-right:6px;"></i>Promedio por área</h2>
                        <p style="font-size:12px; color:#94a3b8; margin-bottom:10px;">🟢 ≥14 Destacado &nbsp; 🟡 11-13 En proceso &nbsp; 🔴 &lt;11 Necesita apoyo</p>
                        <div class="chart-wrap" style="height:260px;">
                            <canvas id="chartPromedioArea"></canvas>
                        </div>
                    </article>
                    <article class="parent-card">
                        <h2 style="margin-bottom:16px;"><i class="fa-solid fa-chart-line" style="color:#0d9488; margin-right:6px;"></i>Evolución por período</h2>
                        <p style="font-size:12px; color:#94a3b8; margin-bottom:10px;">Tendencia del promedio general a lo largo del año.</p>
                        <div class="chart-wrap" style="height:260px;">
                            <canvas id="chartEvolucion"></canvas>
                        </div>
                    </article>
                </section>

                <section class="parent-card">
                    <h2><i class="fa-solid fa-table" style="color:#0d9488; margin-right:6px;"></i>Todas las notas</h2>
                    <div class="table-wrap">
                        <table id="tabla-notas" class="paginated">
                            <thead>
                                <tr><th>Área</th><th>Nota</th><th>Período</th><th>Tipo evaluación</th><th>Fecha</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notas)): ?>
                                    <tr><td colspan="5" class="empty" style="padding:20px; text-align:center;">Sin registros de notas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($notas as $row):
                                        $nota = (float)$row['nota_final'];
                                        $notaColor = $nota >= 14 ? '#16a34a' : ($nota >= 11 ? '#d97706' : '#dc2626');
                                    ?>
                                    <tr class="fila-nota"
                                        data-area="<?= e($row['nombre_curso']) ?>"
                                        data-periodo="<?= e($row['periodo']) ?>">
                                        <td><?= e($row['nombre_curso']) ?></td>
                                        <td><strong style="color:<?= $notaColor ?>; font-size:15px;"><?= number_format($nota, 2) ?></strong></td>
                                        <td><?= e($row['periodo']) ?></td>
                                        <td><?= e($row['tipo_evaluacion']) ?></td>
                                        <td><?= e($row['fecha_registro']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════
                 ASISTENCIA
            ══════════════════════════════════════════════════════ -->
            <?php if ($seccionActiva === 'asistencia'): ?>

                <section class="parent-kpis" style="grid-template-columns: repeat(3, 1fr);">
                    <article class="parent-card <?= $colorAsistencia ?>">
                        <i class="fa-solid fa-calendar-check kpi-icon"></i>
                        <span>Asistencia General</span>
                        <strong><?= (int)$kpis['asistencia'] ?>%</strong>
                    </article>
                    <article class="parent-card <?= (100 - (int)$kpis['asistencia']) > 20 ? 'danger' : 'kpi-green' ?>">
                        <i class="fa-solid fa-triangle-exclamation kpi-icon"></i>
                        <span>Alerta Inasistencia</span>
                        <strong style="font-size:18px;"><?= (100 - (int)$kpis['asistencia']) > 20 ? 'Activa' : 'Sin alerta' ?></strong>
                    </article>
                    <article class="parent-card">
                        <i class="fa-solid fa-calendar-xmark kpi-icon"></i>
                        <span>Faltas totales</span>
                        <strong><?= array_sum(array_column($asistenciaMes, 'inasistencias')) ?></strong>
                    </article>
                </section>

                <section class="parent-card" style="margin-bottom:var(--spacing-unit);">
                    <form class="parent-filters" method="get" action="<?= BASE_URL ?>padre.php">
                        <input type="hidden" name="seccion" value="asistencia">
                        <input type="hidden" name="hijo" value="<?= $idEstudiante ?>">
                        <label>Filtrar por mes
                            <input type="month" name="filtro_mes" value="<?= e($_GET['filtro_mes'] ?? '') ?>" onchange="this.form.submit()">
                        </label>
                        <a href="<?= BASE_URL ?>padre.php?seccion=asistencia&hijo=<?= $idEstudiante ?>"
                           class="button-link" style="margin-top:25px; text-decoration:none; font-size:12px; color:#64748b;">
                            Limpiar filtros
                        </a>
                    </form>

                    <div class="asist-summary">
                        <div class="chart-wrap" style="height:200px; max-width:220px; margin:auto;">
                            <canvas id="chartAsistDonut"></canvas>
                        </div>
                        <div>
                            <h3 style="margin:0 0 12px;">Resumen de asistencia</h3>
                            <div style="display:grid; gap:8px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:14px;height:14px;border-radius:3px;background:#16a34a;display:inline-block;"></span>
                                    <span>Presentes / Justificados: <strong><?= $totalPresent ?> días</strong></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:14px;height:14px;border-radius:3px;background:#dc2626;display:inline-block;"></span>
                                    <span>Inasistencias: <strong><?= $totalAsist - $totalPresent ?> días</strong></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                    <span style="width:14px;height:14px;border-radius:3px;background:#0d9488;display:inline-block;"></span>
                                    <span>Total registros: <strong><?= $totalAsist ?> días</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if (!empty($asistenciaMes)): ?>
                <section class="parent-card">
                    <h2><i class="fa-solid fa-chart-column" style="color:#0d9488; margin-right:6px;"></i>Asistencia mensual</h2>
                    <p style="font-size:12px; color:#94a3b8; margin-bottom:12px;">🟢 Días presentes &nbsp; 🔴 Faltas</p>
                    <div class="chart-wrap" style="height:240px;">
                        <canvas id="chartAsistMes"></canvas>
                    </div>
                </section>
                <?php endif; ?>

                <section class="parent-card">
                    <h2><i class="fa-solid fa-table" style="color:#0d9488; margin-right:6px;"></i>Registro diario</h2>
                    <div class="table-wrap">
                        <table class="paginated">
                            <thead>
                                <tr><th>Fecha</th><th>Descripción</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($asistencia)): ?>
                                    <tr><td colspan="3" class="empty" style="padding:20px; text-align:center;">No hay registros para el período seleccionado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($asistencia as $row): ?>
                                        <tr>
                                            <td><?= e($row['fecha']) ?></td>
                                            <td><?= e($row['curso']) ?></td>
                                            <td><span class="status-badge <?= $row['estado'] === 'Falto' ? 'danger' : 'ok' ?>"><?= e($row['estado']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════
                 ALERTAS
            ══════════════════════════════════════════════════════ -->
            <?php if ($seccionActiva === 'alertas'): ?>

                <section class="parent-card">
                    <form class="parent-filters" id="form-filtro-alertas">
                        <label>Tipo de alerta
                            <select id="filtro-tipo">
                                <option value="">Todas</option>
                                <?php foreach ($tiposUnicos as $t): ?>
                                    <option value="<?= e($t) ?>"><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Canal
                            <select id="filtro-canal">
                                <option value="">Todos</option>
                                <?php foreach ($canalesUnicos as $c): ?>
                                    <option value="<?= e($c) ?>"><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Estado
                            <select id="filtro-estado">
                                <option value="">Todos</option>
                                <option value="Pendiente">Pendiente</option>
                                <option value="Enviado">Enviado</option>
                                <option value="Leido">Leído</option>
                                <option value="Fallido">Fallido</option>
                            </select>
                        </label>
                        <label>Fecha <input type="date" id="filtro-fecha"></label>
                        <button type="button" id="btn-limpiar-filtros" class="mini-btn" style="margin-top:25px; background:#64748b;">Limpiar</button>
                    </form>
                    <p id="resultado-filtro" style="font-size:13px; color:#64748b; margin:8px 0 0 4px;"></p>
                    <div class="table-wrap">
                        <table id="tabla-alertas" class="paginated">
                            <thead>
                                <tr><th>Fecha</th><th>Tipo</th><th>Canal</th><th>Mensaje</th><th>Estado</th><th>Acción</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notificaciones)): ?>
                                    <tr><td colspan="6" class="empty" style="padding:20px; text-align:center;">No hay notificaciones.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($notificaciones as $item):
                                        $esReciente = (time() - strtotime($item['fecha_envio'])) < 86400;
                                    ?>
                                    <tr class="fila-alerta <?= $item['estado'] !== 'Leido' ? 'unread-row' : '' ?> <?= $esReciente ? 'recently-sent' : '' ?>"
                                        data-tipo="<?= e($item['tipo']) ?>"
                                        data-canal="<?= e($item['canal']) ?>"
                                        data-estado="<?= e($item['estado']) ?>"
                                        data-fecha="<?= e(substr($item['fecha_envio'], 0, 10)) ?>">
                                        <td style="white-space:nowrap;"><?= e($item['fecha_envio']) ?></td>
                                        <td><?= e($item['tipo']) ?><?php if ($esReciente): ?><span class="recent-badge">Nuevo</span><?php endif; ?></td>
                                        <td><span class="status-badge <?= $item['canal'] === 'Interno' ? 'ok' : 'pending' ?>"><?= e($item['canal']) ?></span></td>
                                        <td style="max-width:340px; word-break:break-word; font-size:13px;"><?= e($item['mensaje']) ?></td>
                                        <td><span class="status-badge <?= $item['estado'] === 'Leido' ? 'ok' : ($item['estado'] === 'Fallido' ? 'danger' : 'pending') ?>"><?= e($item['estado']) ?></span></td>
                                        <td><?php if ($item['estado'] !== 'Leido'): ?><button class="mini-btn mark-read" type="button">Marcar leído</button><?php endif; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════
                 PLAN DE MEJORA
            ══════════════════════════════════════════════════════ -->
            <?php if ($seccionActiva === 'plan'): ?>

                <?php if ($kpis['plan_activo']): ?>

                    <section class="parent-card" style="border-top: 4px solid #0d9488;">
                        <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
                            <div style="width:48px; height:48px; border-radius:12px; background:#f0fdfa; display:grid; place-items:center;">
                                <i class="fa-solid fa-lightbulb" style="font-size:22px; color:#0d9488;"></i>
                            </div>
                            <div>
                                <p class="eyebrow" style="margin:0;">Plan de Mejora IA · Activo</p>
                                <h2 style="margin:2px 0 0;"><?= e($plan['area']) ?></h2>
                            </div>
                            <span class="status-badge pending" style="margin-left:auto;"><?= e($plan['estado']) ?></span>
                        </div>

                        <p style="padding:10px 14px; background:#fef9c3; border-radius:8px; font-size:14px; border-left:4px solid #d97706;">
                            <i class="fa-solid fa-circle-info" style="color:#d97706;"></i>
                            <?= e($plan['problema']) ?>
                        </p>

                        <h3 style="margin: 18px 0 8px; font-size:15px; color:#374151;">
                            <i class="fa-solid fa-list-check" style="color:#0d9488; margin-right:6px;"></i>Estrategias recomendadas
                        </h3>
                        <?php if (!empty($plan['acciones'])): ?>
                            <ul class="plan-steps">
                                <?php foreach ($plan['acciones'] as $i => $accion): ?>
                                    <li>
                                        <span class="step-num"><?= $i + 1 ?>.</span>
                                        <span><?= e($accion) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <p style="font-size:13px; color:#94a3b8; margin-top:6px;">
                            <i class="fa-solid fa-calendar" style="margin-right:4px;"></i><?= e($plan['fecha']) ?>
                        </p>

                        <div class="action-row">
                            <a href="<?= BASE_URL ?>descargar_plan_ia.php?id_estudiante=<?= $idEstudiante ?>&nombre=<?= urlencode($hijo['nombres'] . ' ' . $hijo['apellidos']) ?>&riesgo=<?= urlencode($kpis['riesgo']) ?>"
                               target="_blank" class="btn-dl btn-dl-blue">
                                <i class="fa-solid fa-file-pdf"></i> Descargar Plan (PDF)
                            </a>
                            <a href="<?= BASE_URL ?>generar_recurso.php?area=<?= urlencode($plan['area']) ?>&grado=<?= urlencode((string)$hijo['grado']) ?>"
                               target="_blank" class="btn-dl btn-dl-teal">
                                <i class="fa-solid fa-book-open"></i> Material de Refuerzo (PDF)
                            </a>
                            <form method="post" action="<?= BASE_URL ?>padre.php?seccion=plan&hijo=<?= $idEstudiante ?>" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="accion" value="confirmar_plan">
                                <button class="btn-dl btn-dl-green" type="submit">
                                    <i class="fa-solid fa-circle-check"></i> Confirmar seguimiento
                                </button>
                            </form>
                        </div>
                    </section>

                <?php else: ?>

                    <section class="parent-card" style="text-align:center; padding: 40px 20px;">
                        <div style="font-size:52px; margin-bottom:12px;">✅</div>
                        <h2 style="color:#16a34a; margin-bottom:8px;">¡Todo en orden!</h2>
                        <p>Su hijo no presenta alertas académicas activas en este momento.</p>
                    </section>

                    <?php if (!empty($plan['area']) && $plan['area'] !== 'Sin datos'): ?>
                        <details class="parent-card" style="padding:18px; cursor:pointer;">
                            <summary style="font-weight:700; color:#0284c7; font-size:15px; list-style:none; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-clock-rotate-left"></i> Ver último plan de mejora completado
                            </summary>
                            <div style="margin-top:14px; border-top:1px solid #e2e8f0; padding-top:14px;">
                                <p class="eyebrow">Registro Histórico</p>
                                <h3><?= e($plan['area']) ?></h3>
                                <p><?= e($plan['problema'] ?: 'Intervención académica finalizada con éxito.') ?></p>
                                <?php if (!empty($plan['acciones'])): ?>
                                    <ul class="plan-steps">
                                        <?php foreach ($plan['acciones'] as $i => $accion): ?>
                                            <li><span class="step-num"><?= $i + 1 ?>.</span><span><?= e($accion) ?></span></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <p style="font-size:13px; color:#94a3b8;">
                                    <i class="fa-solid fa-calendar" style="margin-right:4px;"></i><?= e($plan['fecha']) ?>
                                    · <span class="status-badge ok" style="padding:2px 8px; font-size:11px;"><?= e($plan['estado']) ?></span>
                                </p>
                            </div>
                        </details>
                    <?php endif; ?>

                <?php endif; ?>

                <section class="parent-card">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="color:#0d9488; margin-right:6px;"></i>Historial de planes</h2>
                    <div class="table-wrap">
                        <?php if (!empty($plan['area']) && $plan['area'] !== 'Sin datos'): ?>
                            <table>
                                <thead><tr><th>Área crítica</th><th>Fecha</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><?= e($plan['area']) ?></td>
                                        <td><?= e($plan['fecha']) ?></td>
                                        <td><span class="status-badge <?= $plan['estado'] === 'Activo' ? 'pending' : 'ok' ?>"><?= e($plan['estado']) ?></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty" style="padding:10px;">No existen registros históricos de planes de mejora.</p>
                        <?php endif; ?>
                    </div>
                </section>

            <?php endif; ?>

        <?php endif; ?>
    </main>

    <script>
    // ── Paginación de tablas ──────────────────────────────────────────
    document.querySelectorAll('.paginated').forEach(table => {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        if (rows.length <= 10) return;
        let page = 0;
        const pager = document.createElement('div');
        pager.className = 'pager';
        const prev = document.createElement('button');
        const next = document.createElement('button');
        const label = document.createElement('span');
        prev.textContent = 'Anterior'; next.textContent = 'Siguiente';
        pager.append(prev, label, next);
        table.after(pager);
        const draw = () => {
            rows.forEach((r, i) => r.style.display = i >= page * 10 && i < (page + 1) * 10 ? '' : 'none');
            label.textContent = `Página ${page + 1} de ${Math.ceil(rows.length / 10)}`;
            prev.disabled = page === 0;
            next.disabled = page >= Math.ceil(rows.length / 10) - 1;
        };
        prev.addEventListener('click', () => { page--; draw(); });
        next.addEventListener('click', () => { page++; draw(); });
        draw();
    });

    // ── Opciones globales Chart.js ────────────────────────────────────
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
        Chart.defaults.font.size   = 12;
        Chart.defaults.color       = '#596579';

        // ── Chart: Inicio — mini bar área ────────────────────────────
        const ctxInicioArea = document.getElementById('chartInicioArea');
        if (ctxInicioArea) {
            const labels  = <?= $chartLabelsArea ?>;
            const datos   = <?= $chartDataArea ?>;
            const colores = <?= $chartColorsArea ?>;
            new Chart(ctxInicioArea, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Promedio', data: datos, backgroundColor: colores, borderRadius: 6 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { callbacks: {
                        label: ctx => `  Promedio: ${ctx.parsed.x.toFixed(2)}`
                    }}},
                    scales: {
                        x: { min: 0, max: 20, grid: { color: '#f1f5f9' }, ticks: { stepSize: 5 } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        // ── Chart: Rendimiento — barras horizontales por área ────────
        const ctxArea = document.getElementById('chartPromedioArea');
        if (ctxArea) {
            const labels  = <?= $chartLabelsArea ?>;
            const datos   = <?= $chartDataArea ?>;
            const colores = <?= $chartColorsArea ?>;
            new Chart(ctxArea, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Promedio', data: datos, backgroundColor: colores, borderRadius: 8, borderSkipped: false }] },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => `  ${ctx.parsed.x.toFixed(2)} / 20` } }
                    },
                    scales: {
                        x: { min: 0, max: 20, grid: { color: '#f1f5f9' },
                             ticks: { stepSize: 5, callback: v => v === 0 ? '' : v } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        // ── Chart: Rendimiento — línea evolución ─────────────────────
        const ctxEvol = document.getElementById('chartEvolucion');
        if (ctxEvol) {
            const labels = <?= $chartLabelsPeriodo ?>;
            const datos  = <?= $chartDataPeriodo ?>;
            new Chart(ctxEvol, {
                type: 'line',
                data: { labels, datasets: [{
                    label: 'Promedio',
                    data: datos,
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13,148,136,.12)',
                    pointBackgroundColor: datos.map(v => v >= 14 ? '#16a34a' : (v >= 11 ? '#d97706' : '#dc2626')),
                    pointRadius: 6, pointHoverRadius: 8,
                    borderWidth: 2.5, tension: 0.35, fill: true
                }] },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => `  Promedio: ${ctx.parsed.y.toFixed(2)}` } }
                    },
                    scales: {
                        y: { min: 0, max: 20, grid: { color: '#f1f5f9' }, ticks: { stepSize: 5 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // ── Chart: Asistencia — donut resumen ────────────────────────
        const ctxDonut = document.getElementById('chartAsistDonut');
        if (ctxDonut) {
            const presentes   = <?= (int)$totalPresent ?>;
            const inasistencias = <?= (int)($totalAsist - $totalPresent) ?>;
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Presentes / Justificados', 'Inasistencias'],
                    datasets: [{
                        data: [presentes, inasistencias],
                        backgroundColor: ['#16a34a', '#dc2626'],
                        borderWidth: 3, borderColor: '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: {
                            label: ctx => `  ${ctx.label}: ${ctx.parsed} días`
                        }}
                    }
                },
                plugins: [{
                    id: 'centerText',
                    afterDraw(chart) {
                        const { ctx, chartArea: { top, bottom, left, right } } = chart;
                        const cx = (left + right) / 2, cy = (top + bottom) / 2;
                        const pct = presentes + inasistencias > 0
                            ? Math.round(presentes / (presentes + inasistencias) * 100) : 0;
                        ctx.save();
                        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                        ctx.font = 'bold 22px Segoe UI'; ctx.fillStyle = pct >= 85 ? '#16a34a' : (pct >= 70 ? '#d97706' : '#dc2626');
                        ctx.fillText(pct + '%', cx, cy - 8);
                        ctx.font = '11px Segoe UI'; ctx.fillStyle = '#94a3b8';
                        ctx.fillText('asistencia', cx, cy + 12);
                        ctx.restore();
                    }
                }]
            });
        }

        // ── Chart: Asistencia — barras mensuales ─────────────────────
        const ctxMes = document.getElementById('chartAsistMes');
        if (ctxMes) {
            const labels    = <?= $chartLabelsMes ?>;
            const presentes = <?= $chartDataPresentes ?>;
            const ausentes  = <?= $chartDataAusentes ?>;
            new Chart(ctxMes, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Presentes', data: presentes, backgroundColor: '#16a34a', borderRadius: 6 },
                        { label: 'Faltas',    data: ausentes,  backgroundColor: '#dc2626', borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'rect', padding: 16 } },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { grid: { display: false }, stacked: false },
                        y: { grid: { color: '#f1f5f9' }, beginAtZero: true,
                             ticks: { stepSize: 5, precision: 0 } }
                    }
                }
            });
        }
    }

    // ── Filtro alertas ────────────────────────────────────────────────
    const filtroTipo   = document.getElementById('filtro-tipo');
    const filtroCanal  = document.getElementById('filtro-canal');
    const filtroEstado = document.getElementById('filtro-estado');
    const filtroFecha  = document.getElementById('filtro-fecha');
    const btnLimpiar   = document.getElementById('btn-limpiar-filtros');
    const resultado    = document.getElementById('resultado-filtro');

    function aplicarFiltros() {
        const tipo   = filtroTipo   ? filtroTipo.value.toLowerCase()   : '';
        const canal  = filtroCanal  ? filtroCanal.value.toLowerCase()  : '';
        const estado = filtroEstado ? filtroEstado.value.toLowerCase() : '';
        const fecha  = filtroFecha  ? filtroFecha.value                : '';
        const filas  = document.querySelectorAll('.fila-alerta');
        let visibles = 0;
        filas.forEach(fila => {
            const ok = (!tipo   || fila.dataset.tipo.toLowerCase().includes(tipo)) &&
                       (!canal  || fila.dataset.canal.toLowerCase() === canal)     &&
                       (!estado || fila.dataset.estado.toLowerCase() === estado)   &&
                       (!fecha  || fila.dataset.fecha === fecha);
            fila.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        if (resultado) resultado.textContent = (tipo || canal || estado || fecha)
            ? `Mostrando ${visibles} de ${filas.length} notificaciones` : '';
    }

    [filtroTipo, filtroCanal, filtroEstado, filtroFecha].forEach(el => {
        if (el) el.addEventListener('change', aplicarFiltros);
    });
    if (btnLimpiar) btnLimpiar.addEventListener('click', () => {
        [filtroTipo, filtroCanal, filtroEstado, filtroFecha].forEach(el => { if (el) el.value = ''; });
        aplicarFiltros();
    });

    // ── Filtro rendimiento ────────────────────────────────────────────
    const rendFiltroArea    = document.getElementById('rend-filtro-area');
    const rendFiltroPeriodo = document.getElementById('rend-filtro-periodo');
    const btnLimpiarRend    = document.getElementById('btn-limpiar-rend');
    const resultadoRend     = document.getElementById('resultado-rend');

    function aplicarFiltrosRend() {
        const area    = rendFiltroArea    ? rendFiltroArea.value.toLowerCase()    : '';
        const periodo = rendFiltroPeriodo ? rendFiltroPeriodo.value.toLowerCase() : '';
        const filas   = document.querySelectorAll('.fila-nota');
        let visibles  = 0;
        filas.forEach(fila => {
            const ok = (!area    || fila.dataset.area.toLowerCase()    === area) &&
                       (!periodo || fila.dataset.periodo.toLowerCase() === periodo);
            fila.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        if (resultadoRend) resultadoRend.textContent = (area || periodo)
            ? `Mostrando ${visibles} de ${filas.length} registros` : '';
    }

    [rendFiltroArea, rendFiltroPeriodo].forEach(el => {
        if (el) el.addEventListener('change', aplicarFiltrosRend);
    });
    if (btnLimpiarRend) btnLimpiarRend.addEventListener('click', () => {
        [rendFiltroArea, rendFiltroPeriodo].forEach(el => { if (el) el.value = ''; });
        aplicarFiltrosRend();
    });

    // ── Marcar como leído (UI) ────────────────────────────────────────
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            row.classList.remove('unread-row');
            const estadoSpan = row.querySelector('td:nth-child(5) span');
            if (estadoSpan) { estadoSpan.textContent = 'Leido'; estadoSpan.className = 'status-badge ok'; }
            row.dataset.estado = 'Leido';
            btn.remove();
        });
    });
    </script>
</body>
</html>
