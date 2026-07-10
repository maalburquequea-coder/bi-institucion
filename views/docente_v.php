<?php
// Asegurar que el usuario esté definido
$usuario = $usuario ?? $_SESSION['usuario'] ?? [];
$seccionActiva = $seccionActiva ?? 'inicio';
$panelDocente = $panelDocente ?? [];
$resumenRiesgos = $resumenRiesgos ?? ['alto' => 0, 'medio' => 0, 'bajo' => 0];
$error = $error ?? '';

$docenteNombre = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$menu = [
    'inicio'         => 'Mi panel',
    'asistencia'     => 'Registrar asistencia',
    'notas'          => 'Registrar notas',
    'alertas'        => 'Alertas tempranas',
    'reportes'       => 'Reportes',
    'notificaciones' => 'Notificaciones',
];
$resultadosBusqueda  = $resultadosBusqueda  ?? [];
$gradoSeleccionado   = $gradoSeleccionado   ?? 2;
$seccionAula         = $seccionAula         ?? 'A';

// Inicializar sub-arrays para evitar errores de índice
$cursos = $panelDocente['cursos'] ?? [];
$kpis = $panelDocente['kpis'] ?? [
    'total_estudiantes' => 0, 
    'alertas_activas' => 0, 
    'asistencias_pendientes' => 0, 
    'promedio_general' => 0
];
$riesgos = $panelDocente['riesgos'] ?? [];
$notificaciones = $panelDocente['notificaciones'] ?? [];
$cargas = $panelDocente['cargas'] ?? [];
$filtroRiesgo = $_GET['filtro_riesgo'] ?? '';
$filtroCurso = $_GET['filtro_curso'] ?? '';
$filtroEstado = $_GET['filtro_estado'] ?? '';
$filtroTipoReporte = $_GET['filtro_tipo_reporte'] ?? '';
$filtroGradoReporte = $_GET['filtro_grado_reporte'] ?? '';
$filtroPeriodoReporte = $_GET['filtro_periodo_reporte'] ?? '';
$filtroFecha = $_GET['filtro_fecha'] ?? '';

$rendimiento = $panelDocente['rendimiento'] ?? [];
$areas = $panelDocente['areas'] ?? [];
$asistenciaSemanal = $panelDocente['asistencia_semanal'] ?? [];
$historialCargas = $panelDocente['historial_cargas'] ?? [];
$primerCurso = $cursos[0] ?? ['nombre_curso' => 'Curso asignado', 'grado' => 2, 'seccion' => 'A', 'nivel' => 'Secundaria'];
$cursoActual = (string) ($primerCurso['nombre_curso'] ?? 'Curso asignado');
?>
<?php $csrf_token = $_SESSION['csrf_token'] ?? ''; // Asegurar que el token CSRF esté disponible ?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel docente - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <style>
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #0ea5e9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        .report-actions {
            display: flex;
            gap: 12px;
            flex-wrap: nowrap; /* Fuerza a que todos los elementos se queden en una sola fila */
            justify-content: flex-start; /* Alinea los elementos a la izquierda */
            align-items: center;
            width: 100%;
            grid-column: 1 / -1; /* Ocupa todo el ancho si el padre es un grid */
        }
        @media (max-width: 600px) {
            .report-actions {
                gap: 6px; /* Reducimos el espacio entre botones */
            }
            .report-actions .mini-btn, 
            .report-actions .teacher-primary {
                padding: 6px 10px !important; /* Botones más compactos */
                font-size: 11px !important;    /* Texto más pequeño */
                min-width: auto !important;    /* Eliminamos el ancho mínimo forzado */
            }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body class="teacher-page">
    <aside class="teacher-sidebar">
        <div class="teacher-brand">
            <span>D</span>
            <div>
                <strong><?= e($docenteNombre) ?></strong>
                <small>Docente</small>
            </div>
        </div>
        <nav>
            <?php foreach ($menu as $clave => $texto): ?>
                <a class="<?= $seccionActiva === $clave ? 'active' : '' ?>" href="<?= BASE_URL ?>docente.php?seccion=<?= e($clave) ?>"><?= e($texto) ?></a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>logout.php">Cerrar sesion</a>
        </nav>
    </aside>

    <main class="teacher-main">
        <section class="teacher-hero" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 3rem 2rem; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;">
            <div>
                <p class="eyebrow" style="color: #38bdf8;">I.E. N 14008 "Leonor Cerna de Valdiviezo"</p>
                <h1 style="color: white; font-size: 2.5rem; margin: 0.5rem 0;"><?= e($menu[$seccionActiva]) ?></h1>
                <p style="color: #cbd5e1;"><?= e($docenteNombre) ?> <span style="margin: 0 8px;">•</span> Gestión Curricular EPT</p>
            </div>
        </section>

        <?php if ($seccionActiva === 'inicio'): ?>
            <section class="kpi-container">
                <article class="kpi-card blue">
                    <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="kpi-content">
                        <span class="kpi-label">Total estudiantes</span>
                        <strong class="kpi-value"><?= (int) $kpis['total_estudiantes'] ?></strong>
                    </div>
                </article>
                <article class="kpi-card red">
                    <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="kpi-content">
                        <span class="kpi-label">Alertas activas</span>
                        <strong class="kpi-value"><?= (int) $kpis['alertas_activas'] ?></strong>
                    </div>
                </article>
                <article class="kpi-card amber">
                    <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="kpi-content">
                        <span class="kpi-label">Asistencias pendientes</span>
                        <strong class="kpi-value"><?= (int) $kpis['asistencias_pendientes'] ?></strong>
                    </div>
                </article>
                <article class="kpi-card teal">
                    <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="kpi-content">
                        <span class="kpi-label">Promedio general</span>
                        <strong class="kpi-value"><?= number_format((float) $kpis['promedio_general'], 2) ?></strong>
                    </div>
                </article>
                <article class="kpi-card" style="background: linear-gradient(135deg,#1e293b,#334155); color:#fff; border:none;">
                    <div class="kpi-content" style="width:100%;">
                        <span class="kpi-label" style="color:#94a3b8;">Distribución de riesgo</span>
                        <div style="display:flex; gap:10px; margin-top:8px; font-size:13px;">
                            <span style="color:#f87171;"><strong><?= (int)$resumenRiesgos['alto'] ?></strong> Alto</span>
                            <span style="color:#fbbf24;"><strong><?= (int)$resumenRiesgos['medio'] ?></strong> Medio</span>
                            <span style="color:#34d399;"><strong><?= (int)$resumenRiesgos['bajo'] ?></strong> Bajo</span>
                        </div>
                        <div style="height:5px; border-radius:4px; background:#334155; margin-top:10px; overflow:hidden; display:flex;">
                            <?php
                                $total = max(1, $resumenRiesgos['alto'] + $resumenRiesgos['medio'] + $resumenRiesgos['bajo']);
                                $pAlto  = round($resumenRiesgos['alto']  / $total * 100);
                                $pMedio = round($resumenRiesgos['medio'] / $total * 100);
                                $pBajo  = 100 - $pAlto - $pMedio;
                            ?>
                            <span style="width:<?= $pAlto ?>%;background:#f87171;"></span>
                            <span style="width:<?= $pMedio ?>%;background:#fbbf24;"></span>
                            <span style="width:<?= $pBajo ?>%;background:#34d399;"></span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="teacher-card" style="padding:16px 20px;">
                <form method="get" action="<?= BASE_URL ?>docente.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="seccion" value="inicio">
                    <i class="fas fa-search" style="color:#94a3b8;font-size:.95rem;"></i>
                    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                           placeholder="Buscar alumno por nombre o DNI..."
                           style="flex:1;min-width:200px;padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.9rem;background:var(--bg-input,#f8fafc);"
                           minlength="2" maxlength="100" autocomplete="off">
                    <button type="submit" class="mini-btn" style="height:36px;min-width:90px;">Buscar</button>
                    <?php if (!empty($_GET['q'])): ?>
                        <a href="<?= BASE_URL ?>docente.php?seccion=inicio" class="mini-btn" style="height:36px;min-width:90px;background:#64748b;text-decoration:none;display:flex;align-items:center;justify-content:center;">Limpiar</a>
                    <?php endif; ?>
                </form>
                <?php if (!empty($_GET['q'])): ?>
                    <?php if (empty($resultadosBusqueda)): ?>
                        <p style="margin:10px 0 0;font-size:.87rem;color:#92400e;">No se encontraron alumnos para <strong>"<?= e($_GET['q']) ?>"</strong> en tus aulas.</p>
                    <?php else: ?>
                        <div class="table-wrap" style="margin-top:14px;">
                            <table style="width:100%;border-collapse:collapse;font-size:.87rem;">
                                <thead>
                                    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Alumno</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">DNI</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Grado / Secc.</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Promedio</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Asistencia</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Área crítica</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:600;">Riesgo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($resultadosBusqueda as $r):
                                    $promR = (float) $r['promedio'];
                                    $asiR  = (int) ($r['asistencia'] ?? 0);
                                ?>
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td style="padding:8px 10px;font-weight:600;"><?= e($r['apellidos'] . ', ' . $r['nombres']) ?></td>
                                        <td style="padding:8px 10px;font-variant-numeric:tabular-nums;"><?= e($r['dni']) ?></td>
                                        <td style="padding:8px 10px;"><?= (int)$r['grado'] ?>° <?= e($r['seccion']) ?></td>
                                        <td style="padding:8px 10px;font-weight:700;color:<?= $promR >= 11 ? '#16a34a' : '#dc2626' ?>;"><?= number_format($promR, 1) ?></td>
                                        <td style="padding:8px 10px;font-weight:600;color:<?= $asiR >= 80 ? '#0284c7' : '#dc2626' ?>;"><?= $asiR ?>%</td>
                                        <td style="padding:8px 10px;"><?= e($r['area_critica']) ?></td>
                                        <td style="padding:8px 10px;"><span class="risk <?= riesgoClase($r['riesgo']) ?>"><?= e($r['riesgo']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="teacher-grid">
                <article class="teacher-card">
                    <div class="panel-header"><div><p class="eyebrow">Cursos</p><h2>Cursos asignados</h2></div></div>
                    <div class="table-wrap">
                        <table class="paginated">
                            <thead><tr><th>Curso</th><th>Aula</th><th>Estudiantes</th><th>Rendimiento</th></tr></thead>
                            <tbody>
                                <?php foreach ($cursos as $curso): ?>
                                    <?php $prom = (float) $curso['promedio']; $bar = $prom >= 14 ? 'good' : ($prom >= 11 ? 'warn' : 'bad'); ?>
                                    <tr>
                                        <td><strong><?= e($curso['nombre_curso']) ?></strong></td>
                                        <td><?= (int) $curso['grado'] ?> <?= e($curso['seccion']) ?></td>
                                        <td><?= (int) $curso['estudiantes'] ?></td>
                                        <td><div class="progress <?= $bar ?>"><i style="width: <?= min(100, $prom * 5) ?>%"></i></div><small><?= number_format($prom, 2) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="teacher-card">
                    <div class="panel-header"><div><p class="eyebrow">Hoy</p><h2>Estado de cargas</h2></div></div>
                    <div class="table-wrap">
                        <table class="paginated">
                            <thead><tr><th>Curso</th><th>Tipo</th><th>Aula</th><th>Estado</th></tr></thead>
                            <tbody>
                                <?php foreach ($cargas as $carga): ?>
                                    <tr>
                                        <td><?= e($carga['curso']) ?></td>
                                        <td><?= e($carga['tipo']) ?></td>
                                        <td><?= (int) $carga['grado'] ?> <?= e($carga['seccion']) ?></td>
                                        <td><span class="status-badge <?= $carga['estado'] === 'Cargado' ? 'ok' : 'pending' ?>"><?= e($carga['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <section class="teacher-grid">
                <article class="teacher-card">
                    <div class="panel-header"><div><p class="eyebrow">Riesgo</p><h2>Estudiantes en riesgo</h2></div></div>
                    <div class="table-wrap">
                        <table class="paginated">
                            <thead><tr><th>Estudiante</th><th>Curso</th><th>Nivel</th><th>Motivo</th><th></th></tr></thead>
                            <tbody>
                                <?php foreach ($riesgos as $row): ?>
                                    <tr>
                                        <td><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></td>
                                        <td><?= e($row['nombre_curso']) ?></td>
                                        <td><span class="risk <?= riesgoClase($row['riesgo']) ?>"><?= e($row['riesgo']) ?></span></td>
                                        <td><?= e($row['motivo']) ?></td>
                                        <td><button class="mini-btn" data-modal="plan-modal" data-student-id="<?= (int) $row['id_estudiante'] ?>" data-student="<?= e($row['nombres'] . ' ' . $row['apellidos']) ?>" data-risk="<?= e($row['riesgo']) ?>" data-promedio="<?= number_format((float) $row['promedio'], 2) ?>" data-asistencia="<?= (int) ($row['asistencia'] ?? 0) ?>" data-area-critica="<?= e($row['area_critica'] ?? 'General') ?>" data-grado="<?= e($row['grado']) ?>" data-seccion="<?= e($row['seccion']) ?>">Ver plan IA</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="teacher-card">
                    <div class="panel-header"><div><p class="eyebrow">Padres</p><h2>Ultimas notificaciones</h2></div></div>
                    <div class="table-wrap">
                        <table class="paginated">
                            <thead><tr><th>Estudiante</th><th>Tipo</th><th>Canal</th><th>Estado</th></tr></thead>
                            <tbody>
                                <?php foreach ($notificaciones as $item): ?>
                                    <tr>
                                        <td><?= e($item['estudiante']) ?></td>
                                        <td><?= e($item['tipo']) ?></td>
                                        <td><?= e($item['canal']) ?></td>
                                        <td><span class="status-badge <?= $item['estado'] === 'Leido' || $item['estado'] === 'Enviado' ? 'ok' : 'pending' ?>"><?= e($item['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <!-- ── Dashboard BI integrado ─────────────────────────── -->
            <section class="teacher-grid">
                <article class="teacher-card">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Dashboard BI</p>
                            <h2>Promedio por área curricular</h2>
                        </div>
                    </div>
                    <div style="height:300px; position:relative; padding:10px;">
                        <canvas id="chartDocenteRendimiento"></canvas>
                    </div>
                </article>
                <article class="teacher-card">
                    <div class="panel-header">
                        <div>
                            <p class="eyebrow">Dashboard BI</p>
                            <h2>Asistencia semanal</h2>
                        </div>
                    </div>
                    <div style="height:300px; position:relative; padding:10px;">
                        <canvas id="chartDocenteAsistencia"></canvas>
                    </div>
                </article>
            </section>

            <section class="teacher-card">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Dashboard BI</p>
                        <h2>Rendimiento por estudiante</h2>
                    </div>
                    <a href="<?= BASE_URL ?>docente.php?seccion=alertas" class="button-link">Ver alertas</a>
                </div>
                <div class="table-wrap">
                    <table class="paginated">
                        <thead>
                            <tr><th>Estudiante</th><th>Curso</th><th>Nota promedio</th><th>% Asistencia</th><th>Riesgo</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendimiento as $row):
                                $prom = (float)$row['promedio'];
                                $colorNota = $prom >= 14 ? '#16a34a' : ($prom >= 11 ? '#d97706' : '#dc2626');
                                $risgo = isset($row['riesgo']) ? $row['riesgo'] : ($prom < 11 ? 'Alto' : ($prom < 14 ? 'Medio' : 'Bajo'));
                            ?>
                                <tr>
                                    <td><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></td>
                                    <td><?= e($row['nombre_curso']) ?></td>
                                    <td><strong style="color:<?= $colorNota ?>;"><?= number_format($prom, 2) ?></strong></td>
                                    <td><?= (int)($row['asistencia'] ?? 0) ?>%</td>
                                    <td><span class="risk <?= riesgoClase($risgo) ?>"><?= e($risgo) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($seccionActiva === 'asistencia' || $seccionActiva === 'notas'): ?>
            <section class="teacher-card">
                <div class="panel-header"><div><p class="eyebrow">Carga</p><h2><?= $seccionActiva === 'asistencia' ? 'Registrar asistencia' : 'Registrar notas' ?></h2></div></div>
                
                <?php if (!empty($mensaje)): ?><div class="alert ok"><?= e($mensaje) ?></div><?php endif; ?>
                <?php if (!empty($error)): ?><div class="alert error" style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca;"><?= e($error) ?></div><?php endif; ?>

                <form action="<?= BASE_URL ?>docente.php?seccion=<?= e($seccionActiva) ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <div class="teacher-filters">
                        <label>Área Curricular (Fijo)
                            <input type="text" value="<?= e($cursoActual) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                            <input type="hidden" name="curso" value="<?= e($cursoActual) ?>">
                        </label>
                        <label>Grado
                            <select name="grado">
                                <?php for ($i = 2; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= ((int) ($gradoSeleccionado ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?>°</option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label>Seccion
                            <select name="seccion">
                                <?php foreach (['A', 'B', 'C'] as $seccion): ?>
                                    <option value="<?= e($seccion) ?>" <?= (($seccionAula ?? 'A') === $seccion) ? 'selected' : '' ?>><?= e($seccion) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <input type="hidden" name="nivel" value="<?= e($primerCurso['nivel'] ?? 'Secundaria') ?>">
                    </div>
                    <input id="file-input" name="archivo_documento" type="file" accept=".csv,.xls,.xlsx" style="display:none;">
                    <input type="hidden" id="csv-editado" name="csv_editado">

                    <!-- Zona de arrastre -->
                    <div id="drop-zone" style="border:2px dashed #cbd5e1;background:#f8fafc;padding:2.5rem 2rem;border-radius:16px;text-align:center;transition:all .25s ease;">
                        <i class="fas fa-cloud-arrow-up" style="font-size:2.5rem;color:#94a3b8;display:block;margin-bottom:12px;"></i>
                        <strong style="display:block;font-size:1.1rem;color:#1e293b;margin-bottom:6px;">
                            Arrastra tu archivo aqui
                        </strong>
                        <p style="color:#64748b;font-size:14px;margin-bottom:20px;">Formatos aceptados: CSV, XLS, XLSX</p>
                        <button type="button" id="btn-seleccionar"
                            style="background:#0ea5e9;color:white;border:none;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-folder-open"></i> Seleccionar archivo
                        </button>
                    </div>

                    <!-- Tarjeta del archivo seleccionado (oculta inicialmente) -->
                    <div id="file-card" style="display:none;margin-top:14px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:16px 18px;gap:14px;flex-direction:column;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <i id="file-card-icon" class="fas fa-file-excel" style="font-size:2rem;color:#16a34a;flex-shrink:0;"></i>
                            <div style="flex:1;min-width:0;">
                                <strong id="file-card-name" style="display:block;color:#15803d;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></strong>
                                <small id="file-card-meta" style="color:#4ade80;font-size:12px;"></small>
                            </div>
                        </div>

                        <!-- Título editable + acciones -->
                        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                            <label style="flex:1;min-width:200px;font-size:13px;color:#374151;font-weight:600;">
                                Titulo del documento
                                <input id="titulo-doc" name="titulo_doc" type="text"
                                    style="margin-top:4px;display:block;width:100%;border:1.5px solid #86efac;border-radius:8px;padding:8px 12px;font-size:14px;background:white;color:#1e293b;outline:none;"
                                    placeholder="Ej. Asistencia EPT 3ro A">
                            </label>
                            <button type="button" id="btn-cambiar"
                                style="background:white;color:#0ea5e9;border:1.5px solid #0ea5e9;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-arrows-rotate"></i> Cambiar archivo
                            </button>
                            <button type="button" id="btn-eliminar-archivo"
                                style="background:white;color:#ef4444;border:1.5px solid #ef4444;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Vista previa del contenido -->
                    <div class="table-wrap preview-wrap" style="margin-top:14px;border:1px dashed #ddd;border-radius:8px;background:#f9fafb;overflow-x:auto;">
                        <table id="file-preview" style="width:100%;font-size:13px;border-collapse:collapse;">
                            <thead id="preview-thead">
                                <tr><th style="padding:10px;text-align:left;background:#f3f4f6;border-bottom:1px solid #ddd;">Vista previa del contenido</th></tr>
                            </thead>
                            <tbody id="preview-tbody" style="background:white;">
                                <tr><td class="empty" style="padding:15px;text-align:center;color:#6b7280;">Seleccione un archivo para verificar su informacion antes de confirmar la carga.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <button class="teacher-primary" id="btn-confirmar" type="submit" disabled
                        style="opacity:.45;cursor:not-allowed;">
                        <i class="fas fa-upload"></i> Confirmar carga
                    </button>
                </form>
            </section>

            <section class="teacher-card">
                <div class="panel-header"><div><p class="eyebrow">Historial</p><h2>Mis cargas de <?= $seccionActiva === 'asistencia' ? 'Asistencia' : 'Notas' ?></h2></div></div>
                <div class="table-wrap">
                    <table class="paginated">
                        <thead><tr><th>Fecha</th><th>Curso</th><th>Aula</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($historialCargas as $row): ?>
                                <tr>
                                    <td><?= e($row['fecha_subida']) ?></td>
                                    <td><?= e($row['titulo']) ?></td>
                                    <td><?= (int) $row['grado'] ?> <?= e($row['seccion']) ?></td>
                                    <td><span class="status-badge ok">Cargado</span></td>
                                    <td style="white-space: nowrap;">
                                        <a href="<?= BASE_URL ?>ver_archivo.php?archivo=<?= e($row['archivo']) ?>" target="_blank" class="mini-btn" style="display: inline-block; padding: 4px 8px; font-size: 12px; background: #0ea5e9; color: white; border-radius: 4px; text-decoration: none;">Ver</a>
                                        <a href="<?= BASE_URL ?>uploads/asistencia/<?= e($row['archivo']) ?>" download class="mini-btn" style="display: inline-block; padding: 4px 8px; font-size: 12px; background: #10b981; color: white; border-radius: 4px; text-decoration: none;">Descargar</a>
                                        <form action="<?= BASE_URL ?>docente.php?seccion=<?= e($seccionActiva) ?>" method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que desea eliminar esta carga de <?= $seccionActiva ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="accion" value="eliminar_asistencia">
                                            <input type="hidden" name="id_documento_asistencia" value="<?= (int) ($row['id_documento_asistencia'] ?? 0) ?>">
                                            <button type="submit" class="mini-btn" style="padding: 4px 8px; font-size: 12px; background: #ef4444; border:none; color:white; cursor:pointer; border-radius: 4px;">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($seccionActiva === 'dashboard'): ?>
            <section class="teacher-card" style="padding: 16px;">
                <form class="teacher-filters" method="get" action="<?= BASE_URL ?>docente.php">
                    <input type="hidden" name="seccion" value="dashboard">
                    <label>Área Curricular (Fijo)
                        <input type="text" value="<?= e($cursoActual) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                        <input type="hidden" name="filtro_curso" value="">
                    </label>
                    <label>Grado
                        <select name="filtro_grado">
                            <option value="">Todos</option><option>2</option><option>3</option><option>4</option><option>5</option>
                        </select>
                    </label>
                    <label>Seccion
                        <select name="filtro_seccion">
                            <option value="">Todas</option><option>A</option><option>B</option><option>C</option>
                        </select>
                    </label>
                    <label>Periodo
                        <select name="filtro_periodo">
                            <option value="">Todos</option><option>Bimestre I</option><option>Bimestre II</option><option>Bimestre III</option><option>Bimestre IV</option>
                        </select>
                    </label>
                    <div style="display:flex; gap:10px; margin-top: 25px;">
                        <button type="submit" class="mini-btn" style="min-width: 100px;">Filtrar</button>
                        <a href="<?= BASE_URL ?>docente.php?seccion=dashboard" class="mini-btn" style="min-width: 100px; background:#64748b; text-decoration:none; display:flex; align-items:center; justify-content:center;">Limpiar filtros</a>
                    </div>
                </form>
            </section>
            <section class="teacher-grid">
                <article class="teacher-card"><div class="panel-header"><div><h2>Promedio por area curricular</h2></div></div><div style="height: 320px; position: relative; padding: 10px;"><canvas id="chartDocenteRendimiento"></canvas></div></article>
                <article class="teacher-card"><div class="panel-header"><div><h2>Tendencia de asistencia por semana</h2></div></div><div style="height: 320px; position: relative; padding: 10px;"><canvas id="chartDocenteAsistencia"></canvas></div></article>
            </section>
            <section class="teacher-card">
                <div class="panel-header"><div><h2>Rendimiento por estudiante</h2></div></div>
                <div class="table-wrap"><table class="paginated"><thead><tr><th>Estudiante</th><th>Curso</th><th>Nota promedio</th><th>% asistencia</th></tr></thead><tbody><?php foreach ($rendimiento as $row): ?><tr><td><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></td><td><?= e($row['nombre_curso']) ?></td><td><?= number_format((float) $row['promedio'], 2) ?></td><td><?= (int) ($row['asistencia'] ?? 0) ?>%</td></tr><?php endforeach; ?></tbody></table></div>
            </section>
        <?php endif; ?>

        <?php if ($seccionActiva === 'alertas'): ?>
            <section class="teacher-kpis risk-summary">
                <article><span>Riesgo alto</span><strong><?= (int) $resumenRiesgos['alto'] ?></strong></article>
                <article><span>Riesgo medio</span><strong><?= (int) $resumenRiesgos['medio'] ?></strong></article>
                <article><span>Riesgo bajo</span><strong><?= (int) $resumenRiesgos['bajo'] ?></strong></article>
            </section>
            <section class="teacher-card">
                <form class="teacher-filters" method="get" action="<?= BASE_URL ?>docente.php">
                    <input type="hidden" name="seccion" value="alertas">
                    <label>Área Curricular (Fijo)
                        <input type="text" value="<?= e($cursoActual) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                        <input type="hidden" name="filtro_curso" value="">
                    </label>
                    <label>Nivel de riesgo
                        <select name="filtro_riesgo">
                            <option value="">Todos</option>
                            <option value="Alto" <?= $filtroRiesgo === 'Alto' ? 'selected' : '' ?>>Alto</option>
                            <option value="Medio" <?= $filtroRiesgo === 'Medio' ? 'selected' : '' ?>>Medio</option>
                            <option value="Bajo" <?= $filtroRiesgo === 'Bajo' ? 'selected' : '' ?>>Bajo</option>
                        </select>
                    </label>
                    <div style="display:flex; gap:10px; margin-top: 25px;">
                        <button type="submit" class="mini-btn" style="min-width: 100px;">Filtrar</button>
                        <a href="<?= BASE_URL ?>docente.php?seccion=alertas" class="mini-btn" style="min-width: 100px; background:#64748b; text-decoration:none; display:flex; align-items:center; justify-content:center;">Limpiar filtros</a>
                    </div>
                </form>
                <div class="table-wrap"><table class="paginated"><thead><tr><th>Estudiante</th><th>Curso</th><th>Nivel</th><th>Motivo</th><th>Fecha deteccion</th><th></th></tr></thead><tbody><?php foreach ($riesgos as $row): ?><tr><td><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></td><td><?= e($row['nombre_curso']) ?></td><td><span class="risk <?= riesgoClase($row['riesgo']) ?>"><?= e($row['riesgo']) ?></span></td><td><?= e($row['motivo']) ?></td><td><?= e($row['fecha_deteccion']) ?></td><td><button class="mini-btn" data-modal="plan-modal" data-student-id="<?= (int) $row['id_estudiante'] ?>" data-student="<?= e($row['nombres'] . ' ' . $row['apellidos']) ?>" data-risk="<?= e($row['riesgo']) ?>" data-promedio="<?= number_format((float) $row['promedio'], 2) ?>" data-asistencia="<?= (int) ($row['asistencia'] ?? 0) ?>" data-area-critica="<?= e($row['area_critica'] ?? $cursoActual) ?>" data-grado="<?= e($row['grado']) ?>" data-seccion="<?= e($row['seccion']) ?>">Ver plan IA</button></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
        <?php endif; ?>

        <?php if ($seccionActiva === 'reportes'): ?>
            <section class="teacher-card">
                <div class="report-header-actions">
                <form class="teacher-filters" method="get" action="<?= BASE_URL ?>docente.php">
                    <input type="hidden" name="seccion" value="reportes">
                    <label>Área Curricular (Fijo)
                        <input type="text" value="<?= e($cursoActual) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                    </label>
                    <label>Tipo de reporte
                        <select name="filtro_tipo_reporte">
                            <option value="">Todos</option>
                            <option value="Rendimiento academico" <?= $filtroTipoReporte === 'Rendimiento academico' ? 'selected' : '' ?>>Rendimiento academico</option>
                            <option value="Asistencia" <?= $filtroTipoReporte === 'Asistencia' ? 'selected' : '' ?>>Asistencia</option>
                            <option value="Alertas generadas" <?= $filtroTipoReporte === 'Alertas generadas' ? 'selected' : '' ?>>Alertas generadas</option>
                        </select>
                    </label>
                    <label>Grado
                        <select name="filtro_grado_reporte">
                            <option value="">Todos</option>
                            <?php for ($i = 2; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= ((int) $filtroGradoReporte === $i) ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                        </select>
                    </label>
                    <label>Periodo
                        <select name="filtro_periodo_reporte">
                            <option value="">Todos</option><option value="Bimestre I" <?= $filtroPeriodoReporte === 'Bimestre I' ? 'selected' : '' ?>>Bimestre I</option><option value="Bimestre II" <?= $filtroPeriodoReporte === 'Bimestre II' ? 'selected' : '' ?>>Bimestre II</option><option value="Bimestre III" <?= $filtroPeriodoReporte === 'Bimestre III' ? 'selected' : '' ?>>Bimestre III</option><option value="Bimestre IV" <?= $filtroPeriodoReporte === 'Bimestre IV' ? 'selected' : '' ?>>Bimestre IV</option>
                        </select>
                    </label>
                    <div class="report-actions" style="margin-top: 25px;">
                        <button type="submit" class="mini-btn" style="min-width: 100px;">Filtrar</button>
                        <a href="<?= BASE_URL ?>docente.php?seccion=reportes" class="mini-btn" style="min-width: 100px; background:#64748b; text-decoration:none; display:flex; align-items:center; justify-content:center;">Limpiar filtros</a>
                        <button class="teacher-primary" id="export-excel" type="button" style="margin:0;">Exportar Excel</button>
                        <button class="teacher-primary" id="export-pdf" type="button" style="margin:0;">Exportar PDF</button>
                    </div>
                </form>
                </div>
                <div class="table-wrap">
                    <table class="paginated" id="report-data-table"><thead><tr><th>Estudiante</th><th>Curso</th><th>Promedio</th><th>Asistencia</th><th>Riesgo</th></tr></thead><tbody><?php foreach ($riesgos as $row): ?><tr><td><?= e($row['nombres'] . ' ' . $row['apellidos']) ?></td><td><?= e($row['nombre_curso']) ?></td><td><?= number_format((float) $row['promedio'], 2) ?></td><td><?= (int) ($row['asistencia'] ?? 0) ?>%</td><td><?= e($row['riesgo']) ?></td></tr><?php endforeach; ?></tbody></table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($seccionActiva === 'notificaciones'): ?>
            <section class="teacher-card" style="padding: 16px;">
                <form class="teacher-filters" method="get" action="<?= BASE_URL ?>docente.php">
                    <input type="hidden" name="seccion" value="notificaciones">
                    <label>Área Curricular (Fijo)
                        <input type="text" value="<?= e($cursoActual) ?>" readonly style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
                    </label>
                    <label>Estado
                        <select name="filtro_estado">
                            <option value="">Todos</option>
                            <option value="Leido" <?= $filtroEstado === 'Leido' ? 'selected' : '' ?>>Leido</option>
                            <option value="Pendiente" <?= $filtroEstado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="Enviado" <?= $filtroEstado === 'Enviado' ? 'selected' : '' ?>>Enviado</option>
                        </select>
                    </label>
                    <label>Fecha
                        <input type="date" name="filtro_fecha" value="<?= e($filtroFecha) ?>">
                    </label>
                    <div style="display:flex; gap:10px; margin-top: 25px;">
                        <button type="submit" class="mini-btn" style="min-width: 100px;">Filtrar</button>
                        <a href="<?= BASE_URL ?>docente.php?seccion=notificaciones" class="mini-btn" style="min-width: 100px; background:#64748b; text-decoration:none; display:flex; align-items:center; justify-content:center;">Limpiar filtros</a>
                    </div>
                </form>
                <div class="table-wrap">
                    <table class="paginated">
                        <thead><tr><th>Estudiante</th><th>Tipo alerta</th><th>Fecha envio</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($notificaciones as $item): ?>
                                <tr>
                                    <td><strong><?= e($item['estudiante']) ?></strong><br><small><?= e($item['mensaje'] ?? '') ?></small></td>
                                    <td><?= e($item['tipo']) ?></td>
                                    <td><?= e($item['fecha_envio']) ?></td>
                                    <td><span class="status-badge <?= $item['estado'] === 'Pendiente' ? 'pending' : 'ok' ?>"><?= e($item['estado']) ?></span></td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <?php 
                                            $mensajeWA = 'Hola, le informamos sobre el seguimiento de ' . $item['estudiante'] . ': ' . ($item['mensaje'] ?? '');
                                            $urlWA = whatsappUrl($item['telefono'] ?? '', $mensajeWA); 
                                            ?>
                                            <?php if ($urlWA): ?>
                                                <a href="<?= e($urlWA) ?>" target="_blank" class="mini-btn" style="background:#10b981; text-decoration:none; color:white;">WhatsApp</a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['correo'])): ?>
                                                <button class="mini-btn resend-btn" type="button" 
                                                    data-id-estudiante="<?= (int)($item['id_estudiante'] ?? 0) ?>"
                                                    data-id-padre="<?= (int)($item['id_padre'] ?? 0) ?>"
                                                    data-mensaje="<?= e($item['mensaje'] ?? '') ?>"
                                                    data-correo="<?= e($item['correo']) ?>" 
                                                    style="background:#0ea5e9;">
                                                    Reenviar Correo
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <div class="modal-backdrop" id="plan-modal" style="backdrop-filter: blur(4px); background: rgba(15, 23, 42, 0.7);">
        <div class="teacher-modal">
            <button class="modal-close" type="button" style="background: #f1f5f9; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; top: 1rem; right: 1rem;">✕</button>
            <p class="eyebrow">Plan IA</p>
            <h2 id="plan-title"></h2>
            <div id="plan-content">
                <p>Cargando plan...</p>
            </div>
            <button id="btn-generar-nuevo-plan" class="teacher-primary" style="display:none; margin-top:15px;">Generar nuevo plan IA</button>
            <button id="btn-descargar-plan-pdf" class="teacher-primary" style="display:none; margin-top:15px; background:#0d9488;">📥 Descargar Plan (PDF)</button>
            <button id="btn-enviar-padre" class="teacher-primary" style="display:none; margin-top:15px; background:#2563eb;">📧 Enviar al padre</button>
        </div>
    </div>

    <script>
    const BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

    document.querySelectorAll('.paginated').forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        if (rows.length <= 10) return;
        let page = 0;
        const pager = document.createElement('div');
        pager.className = 'pager';
        const prev = document.createElement('button');
        const next = document.createElement('button');
        const label = document.createElement('span');
        prev.innerHTML = '<i class="fas fa-chevron-left"></i> Anterior';
        next.innerHTML = 'Siguiente <i class="fas fa-chevron-right"></i>';
        pager.append(prev, label, next);
        table.after(pager);
        const draw = () => {
            rows.forEach((row, index) => row.style.display = index >= page * 10 && index < (page + 1) * 10 ? '' : 'none');
            label.textContent = `Pagina ${page + 1} de ${Math.ceil(rows.length / 10)}`;
            prev.disabled = page === 0;
            next.disabled = page >= Math.ceil(rows.length / 10) - 1;
        };
        prev.addEventListener('click', () => { page--; draw(); });
        next.addEventListener('click', () => { page++; draw(); });
        draw();
    });

    document.querySelectorAll('.modal-backdrop').forEach((modal) => {
        modal.addEventListener('click', (event) => { if (event.target === modal) modal.classList.remove('show'); });
        modal.querySelector('.modal-close').addEventListener('click', () => modal.classList.remove('show'));
    });

    const fileInput       = document.getElementById('file-input');
    const dropZone        = document.getElementById('drop-zone');
    const fileCard        = document.getElementById('file-card');
    const fileCardName    = document.getElementById('file-card-name');
    const fileCardMeta    = document.getElementById('file-card-meta');
    const fileCardIcon    = document.getElementById('file-card-icon');
    const tituloDoc       = document.getElementById('titulo-doc');
    const btnSelec        = document.getElementById('btn-seleccionar');
    const btnCambiar      = document.getElementById('btn-cambiar');
    const btnEliminarArch = document.getElementById('btn-eliminar-archivo');
    const btnConfirmar    = document.getElementById('btn-confirmar');
    const previewThead    = document.getElementById('preview-thead');
    const previewTbody    = document.getElementById('preview-tbody');

    const iconosPorExt  = { csv: 'fa-file-csv', xls: 'fa-file-excel', xlsx: 'fa-file-excel' };
    const coloresPorExt = { csv: '#0ea5e9', xls: '#16a34a', xlsx: '#16a34a' };

    /* ── Limpia la selección y vuelve al estado inicial ── */
    const limpiarSeleccion = () => {
        fileInput.value          = '';
        fileCard.style.display   = 'none';
        btnConfirmar.disabled      = true;
        btnConfirmar.style.opacity = '.45';
        btnConfirmar.style.cursor  = 'not-allowed';
        previewThead.innerHTML = '<tr><th style="padding:10px;text-align:left;background:#f3f4f6;border-bottom:1px solid #ddd;">Vista previa del contenido</th></tr>';
        previewTbody.innerHTML = '<tr><td class="empty" style="padding:15px;text-align:center;color:#6b7280;">Seleccione un archivo para verificar su informacion antes de confirmar la carga.</td></tr>';
        if (tituloDoc) tituloDoc.value = '';
    };

    /* ── Renderiza el preview del CSV (solo lectura) ── */
    const renderCSV = (text) => {
        const lines = text.split(/\r?\n/).filter(Boolean);
        if (lines.length === 0) return;

        const cols = lines[0].split(',').map(c => c.trim());
        previewThead.innerHTML = '<tr>' +
            cols.map(h => `<th style="padding:7px 10px;background:#1e293b;color:white;font-size:11px;white-space:nowrap;">${h}</th>`).join('') + '</tr>';

        previewTbody.innerHTML = lines.slice(1, 11).map((line, i) =>
            `<tr style="background:${i % 2 === 0 ? 'white' : '#f8fafc'}">` +
            line.split(',').slice(0, cols.length).map(c =>
                `<td style="padding:6px 10px;border-bottom:1px solid #f1f5f9;">${c.trim()}</td>`
            ).join('') + '</tr>'
        ).join('') || `<tr><td colspan="${cols.length}" class="empty" style="padding:14px;text-align:center;">Sin filas de datos.</td></tr>`;
    };

    /* ── Activa la tarjeta y preview al seleccionar archivo ── */
    const activarArchivo = (file) => {
        if (!file) return;
        const ext  = file.name.split('.').pop().toLowerCase();
        const kb   = (file.size / 1024).toFixed(1);
        const mb   = file.size > 1024 * 1024 ? ` · ${(file.size / 1024 / 1024).toFixed(2)} MB` : '';
        const base = file.name.replace(/\.[^.]+$/, '');

        fileCard.style.display   = 'flex';
        fileCardName.textContent = file.name;
        fileCardMeta.textContent = `${ext.toUpperCase()} · ${kb} KB${mb}`;
        fileCardIcon.className   = `fas ${iconosPorExt[ext] || 'fa-file'} fa-2x`;
        fileCardIcon.style.color = coloresPorExt[ext] || '#64748b';

        if (tituloDoc) tituloDoc.value = base; // título pre-rellenado con el nombre del archivo

        btnConfirmar.disabled      = false;
        btnConfirmar.style.opacity = '1';
        btnConfirmar.style.cursor  = 'pointer';

        if (ext !== 'csv') {
            previewThead.innerHTML = '<tr><th style="padding:10px;text-align:left;background:#f3f4f6;border-bottom:1px solid #ddd;">Vista previa del contenido</th></tr>';
            previewTbody.innerHTML = `<tr><td style="padding:14px;color:#475569;">
                <i class="fas ${iconosPorExt[ext] || 'fa-file'}" style="color:${coloresPorExt[ext]};margin-right:8px;"></i>
                <strong>${file.name}</strong><br>
                <small>Vista previa no disponible para ${ext.toUpperCase()}. El archivo se cargara correctamente al confirmar.</small>
            </td></tr>`;
            return;
        }

        const reader = new FileReader();
        reader.onload = () => renderCSV(reader.result);
        reader.readAsText(file);
    };

    if (dropZone && fileInput) {
        btnSelec.addEventListener('click', () => fileInput.click());
        btnCambiar.addEventListener('click', () => { limpiarSeleccion(); fileInput.click(); });
        btnEliminarArch.addEventListener('click', () => {
            if (confirm('¿Deseas quitar este archivo?')) limpiarSeleccion();
        });

        window.addEventListener('dragover', e => e.preventDefault());
        window.addEventListener('drop', e => e.preventDefault());
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#0ea5e9'; dropZone.style.background = '#eff6ff'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = '#cbd5e1'; dropZone.style.background = '#f8fafc'; });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.borderColor = '#cbd5e1';
            dropZone.style.background  = '#f8fafc';
            const dt = e.dataTransfer;
            if (dt.files[0]) {
                const transfer = new DataTransfer();
                transfer.items.add(dt.files[0]);
                fileInput.files = transfer.files;
                activarArchivo(dt.files[0]);
            }
        });
        fileInput.addEventListener('change', () => activarArchivo(fileInput.files[0]));
    }
    document.querySelectorAll('.resend-btn').forEach((button) => {
        button.addEventListener('click', () => {
            if (!confirm('¿Desea reenviar esta notificación por correo electrónico al padre?')) return;

            const originalText = button.textContent;
            button.textContent = 'Enviando...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrf_token ?>');
            formData.append('accion', 'reenviar_notificacion_correo');
            formData.append('id_estudiante', button.dataset.idEstudiante);
            formData.append('id_padre', button.dataset.idPadre);
            formData.append('mensaje', button.dataset.mensaje);
            formData.append('correo', button.dataset.correo);

            fetch(`${BASE_URL}docente.php?seccion=notificaciones`, { method: 'POST', body: formData })
                .then(async (res) => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        throw new Error(text || 'Respuesta invalida del servidor');
                    }
                })
                .then(data => {
                    if (data.success) {
                        button.textContent = 'Reenviado';
                        button.style.background = '#64748b';
                    } else {
                        alert('Error: ' + data.error);
                        button.textContent = originalText;
                        button.disabled = false;
                    }
                })
                .catch((error) => {
                    alert('Error al enviar correo: ' + error.message);
                    button.textContent = originalText;
                    button.disabled = false;
                });
        });
    });
    const reportTable = document.querySelector('.report-actions + .table-wrap table');
    document.getElementById('export-pdf')?.addEventListener('click', () => window.print());
    document.getElementById('export-excel')?.addEventListener('click', () => {
        if (!reportTable) return;

        const schoolName = "I.E. N 14008 'Leonor Cerna de Valdiviezo'";
        const reportTitle = "REPORTE DE SEGUIMIENTO ACADÉMICO Y RIESGO - BI 2026";
        const date = new Date().toLocaleDateString();

        let excelTemplate = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="UTF-8"></head>
            <body>
                <table border="1">
                    <thead>
                        <tr><th colspan="5" style="background:#0d9488; color:white; font-size:16px;">${schoolName}</th></tr>
                        <tr><th colspan="5" style="background:#f8fafc; font-size:12px;">${reportTitle}</th></tr>
                        <tr><th colspan="5" style="text-align:right;">Exportado el: ${date}</th></tr>
                        <tr style="background:#eef2f7;">
                            <th style="font-weight:bold;">Estudiante</th>
                            <th style="font-weight:bold;">Curso</th>
                            <th style="font-weight:bold;">Promedio</th>
                            <th style="font-weight:bold;">Asistencia</th>
                            <th style="font-weight:bold;">Riesgo</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${Array.from(reportTable.querySelectorAll('tbody tr')).map(tr => tr.innerHTML).join('</tr><tr>')}
                    </tbody>
                </table>
            </body>
            </html>`;

        const blob = new Blob([excelTemplate], { type: 'application/vnd.ms-excel' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Reporte_Seguimiento_BI_' + <?= json_encode(preg_replace('/[^A-Za-z0-9]+/', '_', $cursoActual)) ?> + '.xls';
        link.click();
    });

    // --- Plan IA Modal Logic ---
    const planModal = document.getElementById('plan-modal');
    const planTitle = document.getElementById('plan-title');
    const planContent = document.getElementById('plan-content');
    const btnGenerarNuevoPlan = document.getElementById('btn-generar-nuevo-plan');
    const btnDescargarPlanPdf = document.getElementById('btn-descargar-plan-pdf');
    const btnEnviarPadre = document.getElementById('btn-enviar-padre');
    let currentPlanActions = []; // Para almacenar las acciones del plan actual

    document.querySelectorAll('[data-modal="plan-modal"]').forEach((button) => {
        button.addEventListener('click', () => {
            const studentId = button.dataset.studentId;
            const studentName = button.dataset.student;
            const studentRisk = button.dataset.risk;
            const studentPromedio = button.dataset.promedio;
            const studentAsistencia = button.dataset.asistencia;
            const studentAreaCritica = button.dataset.areaCritica;
            const studentGrado = button.dataset.grado;
            const studentSeccion = button.dataset.seccion;

            planTitle.textContent = `Plan IA para ${studentName} - riesgo ${studentRisk}`;
            planContent.innerHTML = '<div class="spinner"></div><p style="text-align:center; color:#64748b;">Recuperando plan de mejora...</p>';
            btnGenerarNuevoPlan.style.display = 'none';
            btnDescargarPlanPdf.style.display = 'none';
            btnEnviarPadre.style.display = 'none';
            btnGenerarNuevoPlan.disabled = false; // Reset disabled state
            btnEnviarPadre.disabled = false; // Reset disabled state
            btnEnviarPadre.textContent = '📧 Enviar al padre'; // Reset text
            planModal.classList.add('show');

            // Función para configurar los botones de descarga y envío
            const setupPlanButtons = (plan) => {
                currentPlanActions = plan;
                btnDescargarPlanPdf.style.display = 'inline-block';
                btnDescargarPlanPdf.onclick = () => {
                    const url = `${BASE_URL}descargar_plan_ia.php?id_estudiante=${studentId}&nombre=${encodeURIComponent(studentName)}&riesgo=${encodeURIComponent(studentRisk)}`;
                    window.open(url, '_blank');
                };
                
                btnEnviarPadre.style.display = 'inline-block';
                btnEnviarPadre.onclick = () => {
                    if (!confirm(`¿Está seguro de enviar esta estrategia de mejora al panel del padre de ${studentName}?`)) return;
                    
                    btnEnviarPadre.disabled = true;
                    btnEnviarPadre.textContent = 'Enviando...';
                    
                    fetch(`${BASE_URL}docente.php?seccion=alertas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `csrf_token=${encodeURIComponent('<?= $csrf_token ?>')}&accion=enviar_plan_padre&id_estudiante=${studentId}&mensaje_plan=${encodeURIComponent(`Estrategia IA (${studentAreaCritica}): ` + currentPlanActions.join(' '))}`
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                btnEnviarPadre.textContent = '✅ Plan enviado';
                                btnEnviarPadre.disabled = true; // Deshabilitar después de enviar con éxito
                            } else {
                                alert(data.error);
                                btnEnviarPadre.disabled = false;
                                btnEnviarPadre.textContent = '📧 Enviar al padre';
                            }
                        });
                };
            };

            const renderPlan = (plan) => {
                // Actualizar el contenido del modal con el plan
                planContent.innerHTML = '<div style="background:#f8fafc; padding:15px; border-radius:8px; border-left:4px solid #0d9488; margin-top:15px;"><h3 style="font-size:14px; margin-bottom:10px; color:#0f172a;">Estrategia de Intervención Académica</h3><ul style="text-align:left; padding-left:20px;">' + plan.map(item => `<li>${item}</li>`).join('') + '</ul></div>';
                setupPlanButtons(plan); // Configurar los botones
            };

            const fallbackPlan = () => {
                const asistencia = Number(studentAsistencia || 0);
                const promedio = Number(studentPromedio || 0);

                if (asistencia < 80) {
                    return [
                        `Coordinar con la familia un compromiso de asistencia para ${studentName}.`,
                        `Recuperar las actividades pendientes de ${studentAreaCritica} durante la semana.`,
                        'Asignar acompanamiento breve al inicio de cada clase para revisar avances.',
                        'Registrar evidencia de mejora y revisar el progreso en la siguiente sesion.'
                    ];
                }

                if (promedio < 13) {
                    return [
                        `Reforzar ${studentAreaCritica} con ejercicios guiados de 20 minutos por sesion.`,
                        'Resolver una practica corta y revisarla con retroalimentacion inmediata.',
                        'Trabajar en pareja con un estudiante de apoyo durante las actividades clave.',
                        'Evaluar nuevamente el tema al cierre de la semana con una tarea aplicada.'
                    ];
                }

                return [
                    `Mantener seguimiento preventivo en ${studentAreaCritica} para sostener el rendimiento.`,
                    'Proponer un reto practico adicional para fortalecer autonomia y precision.',
                    'Revisar una evidencia semanal y dar retroalimentacion puntual.',
                    'Comunicar a la familia los avances y recomendaciones de continuidad.'
                ];
            };

            const fetchAndDisplayPlan = () => {
                fetch(`${BASE_URL}docente.php?seccion=alertas&accion=get_plan_ia&id_estudiante=${studentId}&promedio=${studentPromedio}&asistencia=${studentAsistencia}&area_critica=${encodeURIComponent(studentAreaCritica)}&grado=${encodeURIComponent(studentGrado)}&student_seccion=${encodeURIComponent(studentSeccion)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.plan && data.plan.length > 0) {
                            renderPlan(data.plan);
                        } else {
                            planContent.innerHTML = '<p style="text-align:center;">No se encontró un plan. ¿Desea generar uno nuevo basado en este curso?</p>';
                            btnGenerarNuevoPlan.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching plan:', error);
                        planContent.innerHTML = '<p style="color:red; text-align:center;">Error de comunicación con el servidor o la API de IA. Se mostrará un plan genérico.</p>';
                        renderPlan(fallbackPlan()); // Mostrar plan genérico en caso de error
                        btnGenerarNuevoPlan.style.display = 'block';
                    });
            };

            fetchAndDisplayPlan(); // Initial fetch

            // Handle "Generar nuevo plan" button click
            btnGenerarNuevoPlan.onclick = () => {
                planContent.innerHTML = '<div class="spinner"></div><p style="text-align:center; color:#64748b;">La IA de Gemini está diseñando acciones personalizadas...</p>';
                btnGenerarNuevoPlan.disabled = true;

                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                formData.append('accion', 'generar_plan_ia');
                formData.append('id_estudiante', studentId);
                // Enviar también los datos académicos para la generación del plan si es necesario
                formData.append('promedio', studentPromedio);
                formData.append('asistencia', studentAsistencia);
                formData.append('area_critica', studentAreaCritica);
                formData.append('grado', studentGrado);
                formData.append('seccion', studentSeccion);

                fetch(`${BASE_URL}docente.php?seccion=alertas`, { method: 'POST', body: formData })
                    .then(response => response.json()) // Asegurarse de que la respuesta sea JSON
                    .then(data => {
                        if (data.plan && data.plan.length > 0) {
                            renderPlan(data.plan); // Usar renderPlan para mostrar y configurar botones
                            alert(data.message || 'Plan generado con éxito.');
                        } else { planContent.innerHTML = '<p style="color:red;">Fallo al generar el plan. ' + (data.error || '') + '</p>'; }
                    })
                    .catch(error => {
                        console.error('Error generating plan:', error);
                        planContent.innerHTML = '<p style="color:red;">Error al generar el plan. Verifique la conexión o la API Key.</p>';
                        btnGenerarNuevoPlan.style.display = 'block'; // Mostrar botón para reintentar
                        btnGenerarNuevoPlan.disabled = false; // Habilitar botón para reintentar
                    })
                    .finally(() => { btnGenerarNuevoPlan.disabled = false; btnGenerarNuevoPlan.style.display = 'none'; });
            };
        });
    });
    document.querySelectorAll('a[target="_blank"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.href.includes('ver_archivo.php')) {
                e.preventDefault();
                window.open(this.href, 'ver_archivo', 'width=1000,height=800,menubar=no,status=no,toolbar=no');
            }
        });
    });

    // --- Chart.js for Teacher Dashboard ---
    document.addEventListener('DOMContentLoaded', function() {
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1500, easing: 'easeOutQuart' },
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        boxWidth: 10, 
                        padding: 20, 
                        font: { size: 12, family: "'Inter', sans-serif", weight: '500' },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    } 
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 }
                }
            }
        };

        // Gráfico 1: Promedio por área (Polar Area)
        const areasLabels = <?= json_encode(array_column($areas, 'nombre_curso')) ?>;
        const areasData = <?= json_encode(array_map(fn($a) => (float)$a['promedio'], $areas)) ?>;
        if (areasLabels.length > 0 && areasData.length > 0) {
            new Chart(document.getElementById('chartDocenteRendimiento'), {
                type: 'bar',
                data: {
                    labels: areasLabels,
                    datasets: [{
                        label: 'Promedio por Área',
                        data: areasData,
                        backgroundColor: [
                            '#10b981cc', '#6366f1cc', '#f59e0bcc', '#f43f5ecc', '#8b5cf6cc', '#06b6d4cc', '#64748bcc', '#3b82f6cc'
                        ],
                        borderRadius: 8,
                        borderWidth: 0
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 20,
                            grid: { color: '#f1f5f9' },
                            ticks: { stepSize: 5 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }


        // Gráfico 2: Asistencia (Doughnut)
        const asistenciaLabels = <?= json_encode(array_column($asistenciaSemanal, 'semana')) ?>;
        const asistenciaData = <?= json_encode(array_map(fn($s) => (int)$s['asistencia'], $asistenciaSemanal)) ?>;
        if (asistenciaLabels.length > 0 && asistenciaData.length > 0) {
            new Chart(document.getElementById('chartDocenteAsistencia'), {
                type: 'doughnut',
                data: {
                    labels: asistenciaLabels.map(week => `Semana ${week}`), // Prettier labels
                    datasets: [{
                        label: 'Porcentaje de Asistencia',
                        data: asistenciaData,
                        backgroundColor: [
                            '#10b981', '#6366f1', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#64748b', '#3b82f6'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }],
                    cutout: '75%'
                },
                options: chartOptions
            });
        }
    });
    </script>
</body>
</html>
