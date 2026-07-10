<?php
/** @var array $data */
$resumen        = $data['resumen'];
$riesgos        = $data['riesgos'];
$cursos         = $data['cursos'];
$asistencia     = $data['asistencia'];
$notificaciones = $data['notificaciones'];
$notasPorCurso        = $data['notasPorCurso'];
$evolucionGeneral     = $data['evolucionGeneral'];
$evolucionEstudiantes = $data['evolucionPorEstudiante'];

$usuario     = $_SESSION['usuario'] ?? [];
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

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

// Conteos de riesgo para insight rápido
$cRiesgoAlto  = 0;
$cRiesgoMedio = 0;
$cRiesgoBajo  = 0;
$aulasDisponibles = [];
foreach ($riesgos as $r) {
    $etq = riesgoEtiqueta((float) $r['puntaje_riesgo']);
    if ($etq === 'Alto')  $cRiesgoAlto++;
    elseif ($etq === 'Medio') $cRiesgoMedio++;
    else $cRiesgoBajo++;

    $aulaItem = trim($r['nivel'] . ' ' . $r['grado'] . $r['seccion']);
    $aulasDisponibles[$aulaItem] = $aulaItem;
}
ksort($aulasDisponibles);

// Notas por curso agrupadas por estudiante (para la vista individual)
$notasPorEstudianteMap = [];
foreach ($notasPorCurso as $n) {
    $notasPorEstudianteMap[(int) $n['id_estudiante']][] = [
        'curso' => $n['nombre_curso'],
        'nota'  => (float) $n['nota_final'],
    ];
}

// Evolución de notas agrupada por estudiante
$evolucionPorEstudianteMap = [];
foreach ($evolucionEstudiantes as $ev) {
    $evolucionPorEstudianteMap[(int) $ev['id_estudiante']][] = [
        'periodo'  => $ev['periodo'],
        'promedio' => (float) $ev['promedio'],
    ];
}

// Info completa por estudiante (tarjeta de resumen + gráficos)
$estudiantesInfoMap = [];
foreach ($riesgos as $r) {
    $etq = riesgoEtiqueta((float) $r['puntaje_riesgo']);
    $iniciales = mb_strtoupper(
        mb_substr($r['nombres'], 0, 1) . mb_substr($r['apellidos'], 0, 1),
        'UTF-8'
    );
    $estudiantesInfoMap[(int) $r['id_estudiante']] = [
        'nombre'         => trim($r['nombres'] . ' ' . $r['apellidos']),
        'iniciales'      => $iniciales,
        'riesgo'         => strtolower($etq),
        'riesgoLabel'    => $etq,
        'dni'            => $r['dni'],
        'aula'           => $r['nivel'] . ' ' . $r['grado'] . $r['seccion'],
        'promedio'       => number_format((float) $r['promedio'], 2),
        'faltas'         => (int) $r['faltas'],
        'tardanzas'      => (int) $r['tardanzas'],
        'padre'          => $r['padre'] ?: 'Sin asignar',
        'correo_padre'   => $r['correo_padre'] ?? '',
        'telefono_padre' => $r['telefono_padre'] ?? '',
    ];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard BI - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── Grid de gráficos ── */
        .bi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            padding: 0 2rem 1.5rem;
        }
        @media (max-width: 1100px) { .bi-grid { grid-template-columns: 1fr; } }

        .chart-card {
            background: #fff;
            border-radius: 1.25rem;
            padding: 1.5rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            transition: box-shadow .2s, transform .2s;
        }
        .chart-card:hover {
            box-shadow: 0 10px 28px rgba(0,0,0,.1);
            transform: translateY(-3px);
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .chart-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-header h3 i { color: #0d9488; font-size: 14px; }
        .chart-badge {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }

        /* ── Banda de insight ── */
        .insight-band {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 0 2rem 1.5rem;
        }
        .insight-item {
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .insight-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .insight-item strong { display: block; font-size: 22px; font-weight: 800; line-height: 1; }
        .insight-item span   { font-size: 12px; color: #64748b; margin-top: 3px; display: block; }

        /* ── Tabla de alertas ── */
        #alertas.panel {
            border-radius: 1.25rem !important;
            box-shadow: 0 4px 16px rgba(0,0,0,.06) !important;
            border: 1px solid #f1f5f9 !important;
            margin: 0 2rem 2rem !important;
        }

        /* ── Tarjeta alumno seleccionado ── */
        #tarjetaEstudiante {
            display: none;
            margin: 0 1.5rem 1.25rem;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.1rem 1.4rem;
            animation: slideDown .2s ease;
        }
        #tarjetaEstudiante.visible { display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .tc-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            display: grid; place-items: center;
            font-size: 1.2rem; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .tc-nombre { font-size: 15px; font-weight: 700; color: #0f172a; }
        .tc-sub    { font-size: 12px; color: #64748b; margin-top: 2px; }
        .tc-stats  { display: flex; gap: 1rem; flex-wrap: wrap; flex: 1; }
        .tc-stat   { text-align: center; }
        .tc-stat strong { display: block; font-size: 18px; font-weight: 800; line-height: 1; }
        .tc-stat span   { font-size: 11px; color: #64748b; }
        .tc-close {
            margin-left: auto; background: none; border: none;
            color: #94a3b8; font-size: 18px; cursor: pointer;
            padding: 4px 8px; border-radius: 6px; transition: color .15s;
        }
        .tc-close:hover { color: #ef4444; }

        /* ── Headers ordenables ── */
        .th-sort {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        .th-sort:hover { background: #f1f5f9; }
        .th-sort.sort-asc,
        .th-sort.sort-desc { background: #f0fdfa; color: #0d9488; }
        .sort-arrow { font-size: 11px; opacity: .4; margin-left: 4px; }
        .th-sort.sort-asc  .sort-arrow,
        .th-sort.sort-desc .sort-arrow { opacity: 1; color: #0d9488; }
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

        <!-- Hero -->
        <section class="teacher-hero" style="background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%); padding: 2.5rem 2rem; border-radius: 0 0 2rem 2rem; margin-bottom: 2rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
                <div>
                    <p class="eyebrow" style="color:#38bdf8; font-weight:800; text-transform:uppercase; font-size:11px; letter-spacing:.08em; margin-bottom:6px;">Business Intelligence</p>
                    <h1 style="color:white; font-size:2rem; margin:0 0 8px; font-weight:700; line-height:1.2;">
                        <i class="fas fa-chart-bar" style="color:#38bdf8; margin-right:10px;"></i>Analisis Predictivo y Rendimiento Estudiantil
                    </h1>
                    <p style="color:#94a3b8; font-size:0.95rem; margin:0;">Visualizacion de indicadores criticos para la prevencion de desercion escolar • I.E. N 14008 • Piura 2026</p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div style="background:rgba(239,68,68,.18); border:1px solid rgba(239,68,68,.35); border-radius:10px; padding:8px 16px; text-align:center;">
                        <strong style="color:#fca5a5; font-size:22px; display:block;"><?= $cRiesgoAlto ?></strong>
                        <span style="color:#fca5a5; font-size:11px;">Riesgo Alto</span>
                    </div>
                    <div style="background:rgba(245,158,11,.18); border:1px solid rgba(245,158,11,.35); border-radius:10px; padding:8px 16px; text-align:center;">
                        <strong style="color:#fcd34d; font-size:22px; display:block;"><?= $cRiesgoMedio ?></strong>
                        <span style="color:#fcd34d; font-size:11px;">Riesgo Medio</span>
                    </div>
                    <div style="background:rgba(16,185,129,.18); border:1px solid rgba(16,185,129,.35); border-radius:10px; padding:8px 16px; text-align:center;">
                        <strong style="color:#6ee7b7; font-size:22px; display:block;"><?= $cRiesgoBajo ?></strong>
                        <span style="color:#6ee7b7; font-size:11px;">Riesgo Bajo</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- KPIs principales -->
        <section class="kpi-container" style="padding: 0 2rem 1.5rem;" aria-label="Indicadores principales">
            <article class="kpi-card blue">
                <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Estudiantes</span>
                    <strong class="kpi-value"><?= (int) ($resumen['estudiantes'] ?? 0) ?></strong>
                </div>
            </article>
            <article class="kpi-card teal">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Padres</span>
                    <strong class="kpi-value"><?= (int) ($resumen['padres'] ?? 0) ?></strong>
                </div>
            </article>
            <article class="kpi-card red">
                <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Notas Criticas</span>
                    <strong class="kpi-value"><?= (int) ($resumen['notas_criticas'] ?? 0) ?></strong>
                </div>
            </article>
            <article class="kpi-card amber">
                <div class="kpi-icon"><i class="fas fa-bell"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Alertas</span>
                    <strong class="kpi-value"><?= (int) ($resumen['alertas_generadas'] ?? 0) ?></strong>
                </div>
            </article>
        </section>

        <!-- Banda de insight de riesgo -->
        <div class="insight-band">
            <div class="insight-item">
                <div class="insight-icon" style="background:#fef2f2; color:#ef4444;">
                    <i class="fas fa-circle-exclamation"></i>
                </div>
                <div>
                    <strong style="color:#ef4444;"><?= $cRiesgoAlto ?></strong>
                    <span>Estudiantes en riesgo alto — requieren atencion inmediata</span>
                </div>
            </div>
            <div class="insight-item">
                <div class="insight-icon" style="background:#fffbeb; color:#f59e0b;">
                    <i class="fas fa-circle-half-stroke"></i>
                </div>
                <div>
                    <strong style="color:#f59e0b;"><?= $cRiesgoMedio ?></strong>
                    <span>Estudiantes en riesgo medio — seguimiento recomendado</span>
                </div>
            </div>
            <div class="insight-item">
                <div class="insight-icon" style="background:#f0fdf4; color:#10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <strong style="color:#10b981;"><?= $cRiesgoBajo ?></strong>
                    <span>Estudiantes en riesgo bajo — situacion estable</span>
                </div>
            </div>
        </div>

        <!-- Graficos 2x2 -->
        <div class="bi-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-bar"></i> Rendimiento por Curso</h3>
                    <span class="chart-badge" id="badgeRendimiento">Promedios</span>
                </div>
                <div class="chart-container"><canvas id="chartRendimiento"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-check"></i> Asistencia General</h3>
                    <span class="chart-badge">Por Aulas</span>
                </div>
                <div class="chart-container"><canvas id="chartAsistencia"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribucion de Riesgo</h3>
                    <span class="chart-badge" id="badgeRiesgo"><?= count($riesgos) ?> estudiantes</span>
                </div>
                <div class="chart-container"><canvas id="chartRiesgo"></canvas></div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-arrow-trend-up"></i> Evolucion de Notas</h3>
                    <span class="chart-badge" id="badgeEvolucion">Tendencia general</span>
                </div>
                <div class="chart-container"><canvas id="chartEvolucion"></canvas></div>
            </div>
        </div>

        <!-- Tabla de alertas tempranas -->
        <section id="alertas" class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Seguimiento prioritario</p>
                    <h2>Alertas tempranas de desercion</h2>
                </div>
                <span class="pill" id="contadorResultados"><?= count($riesgos) ?> casos detectados</span>
            </div>

            <!-- Filtro de búsqueda -->
            <div style="padding: 0 1.5rem 1.25rem; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <div style="position:relative; flex:1; min-width:220px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input
                        type="text"
                        id="buscarEstudiante"
                        placeholder="Buscar por nombre, DNI o apoderado..."
                        oninput="filtrarTabla()"
                        style="width:100%; padding:9px 12px 9px 34px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; color:#1e293b; background:#f8fafc; outline:none; box-sizing:border-box; transition:border-color .2s;"
                        onfocus="this.style.borderColor='#0d9488'; this.style.background='#fff';"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';"
                    >
                </div>
                <select
                    id="filtroRiesgo"
                    onchange="filtrarTabla()"
                    style="padding:9px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; color:#1e293b; background:#f8fafc; outline:none; cursor:pointer; transition:border-color .2s;"
                    onfocus="this.style.borderColor='#0d9488';"
                    onblur="this.style.borderColor='#e2e8f0';"
                >
                    <option value="">Todos los riesgos</option>
                    <option value="alto">Alto</option>
                    <option value="medio">Medio</option>
                    <option value="bajo">Bajo</option>
                </select>
                <select
                    id="filtroAula"
                    onchange="filtrarTabla()"
                    style="padding:9px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; color:#1e293b; background:#f8fafc; outline:none; cursor:pointer; transition:border-color .2s;"
                    onfocus="this.style.borderColor='#0d9488';"
                    onblur="this.style.borderColor='#e2e8f0';"
                >
                    <option value="">Todas las aulas</option>
                    <?php foreach ($aulasDisponibles as $aulaOpcion): ?>
                        <option value="<?= e(strtolower($aulaOpcion)) ?>"><?= e($aulaOpcion) ?></option>
                    <?php endforeach; ?>
                </select>
                <button
                    onclick="limpiarFiltros()"
                    style="padding:9px 16px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; color:#64748b; background:#f8fafc; cursor:pointer; transition:all .2s; white-space:nowrap;"
                    onmouseover="this.style.background='#e2e8f0';"
                    onmouseout="this.style.background='#f8fafc';"
                >
                    <i class="fas fa-xmark" style="margin-right:5px;"></i>Limpiar
                </button>
                <button
                    onclick="exportarFiltrado()"
                    style="padding:9px 16px; border:1px solid #0d9488; border-radius:10px; font-size:13px; color:#0d9488; background:#f0fdfa; cursor:pointer; transition:all .2s; white-space:nowrap; font-weight:600;"
                    onmouseover="this.style.background='#0d9488'; this.style.color='#fff';"
                    onmouseout="this.style.background='#f0fdfa'; this.style.color='#0d9488';"
                >
                    <i class="fas fa-file-excel" style="margin-right:5px;"></i>Exportar Excel
                </button>
            </div>

            <!-- Tarjeta de resumen del alumno seleccionado -->
            <div id="tarjetaEstudiante">
                <div class="tc-avatar" id="tcAvatar"></div>
                <div>
                    <div class="tc-nombre" id="tcNombre"></div>
                    <div class="tc-sub" id="tcSub"></div>
                </div>
                <div class="tc-stats">
                    <div class="tc-stat">
                        <strong id="tcPromedio"></strong>
                        <span>Promedio</span>
                    </div>
                    <div class="tc-stat">
                        <strong id="tcFaltas"></strong>
                        <span>Faltas</span>
                    </div>
                    <div class="tc-stat">
                        <strong id="tcTardanzas"></strong>
                        <span>Tardanzas</span>
                    </div>
                    <div class="tc-stat" id="tcPadreWrap">
                        <strong id="tcPadre" style="font-size:13px;"></strong>
                        <span id="tcCorreo" style="font-size:11px; display:block; color:#64748b;"></span>
                        <span>Apoderado</span>
                    </div>
                </div>
                <button class="tc-close" onclick="limpiarFiltros()" title="Cerrar">✕</button>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Aula</th>
                            <th>Padre / Apoderado</th>
                            <th class="th-sort" onclick="ordenarTabla('promedio')" id="th-promedio" title="Ordenar por promedio">
                                Promedio <span class="sort-arrow" id="arrow-promedio">↕</span>
                            </th>
                            <th class="th-sort" onclick="ordenarTabla('faltas')" id="th-faltas" title="Ordenar por faltas">
                                Faltas / Tardanzas <span class="sort-arrow" id="arrow-faltas">↕</span>
                            </th>
                            <th class="th-sort" onclick="ordenarTabla('riesgo')" id="th-riesgo" title="Ordenar por nivel de riesgo">
                                Riesgo <span class="sort-arrow" id="arrow-riesgo">↕</span>
                            </th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riesgos)): ?>
                            <tr>
                                <td colspan="7" class="empty">Aun no hay datos academicos para calcular riesgos.</td>
                            </tr>
                        <?php endif; ?>
                        <tr id="sinResultados" style="display:none;">
                            <td colspan="7" class="empty" style="color:#94a3b8; text-align:center; padding:2rem;">
                                <i class="fas fa-search" style="display:block; font-size:1.5rem; margin-bottom:8px; opacity:.4;"></i>
                                No se encontraron estudiantes con ese criterio de busqueda.
                            </td>
                        </tr>
                        <?php foreach ($riesgos as $row): ?>
                            <?php
                            $riesgo  = riesgoEtiqueta((float) $row['puntaje_riesgo']);
                            $nombre  = trim($row['nombres'] . ' ' . $row['apellidos']);
                            $aula    = $row['nivel'] . ' ' . $row['grado'] . $row['seccion'];
                            $msg     = 'Hola, le informamos que ' . $nombre
                                     . ' presenta riesgo ' . $riesgo
                                     . ' en el seguimiento academico. Promedio: ' . number_format((float) $row['promedio'], 2)
                                     . ', faltas: ' . (int) $row['faltas']
                                     . '. Por favor revise el sistema BI Educativo o comuniquese con la I.E. N. 14008.';
                            $urlWA   = whatsappUrl($row['telefono_padre'] ?? '', $msg);
                            $busquedaData = strtolower($nombre . ' ' . $row['dni'] . ' ' . $aula . ' ' . ($row['padre'] ?? ''));
                            ?>
                            <tr data-fila="1" data-id="<?= (int) $row['id_estudiante'] ?>" data-riesgo="<?= strtolower(e($riesgo)) ?>" data-aula="<?= e(strtolower($aula)) ?>" data-busqueda="<?= e($busquedaData) ?>" data-promedio="<?= number_format((float) $row['promedio'], 2, '.', '') ?>" data-faltas="<?= (int) $row['faltas'] ?>" onclick="verEstudiante(this)" style="cursor:pointer;">
                                <td>
                                    <strong><?= e($nombre) ?></strong>
                                    <small>DNI <?= e($row['dni']) ?></small>
                                </td>
                                <td><?= e($aula) ?></td>
                                <td>
                                    <?= e($row['padre'] ?: 'Sin asignar') ?>
                                    <small><?= e($row['correo_padre'] ?: '') ?></small>
                                </td>
                                <td>
                                    <strong style="font-size:15px;"><?= number_format((float) $row['promedio'], 2) ?></strong>
                                </td>
                                <td><?= (int) $row['faltas'] ?> f / <?= (int) $row['tardanzas'] ?> t</td>
                                <td><span class="risk <?= riesgoClase($riesgo) ?>"><?= e($riesgo) ?></span></td>
                                <td>
                                    <?php if ($urlWA !== ''): ?>
                                        <a class="mini-btn" href="<?= e($urlWA) ?>" target="_blank" rel="noopener">
                                            <i class="fas fa-comment" style="margin-right:4px;"></i>WhatsApp
                                        </a>
                                    <?php else: ?>
                                        <small style="color:#94a3b8;">Sin numero</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <script>
    let chartRendimiento, chartRiesgo, chartEvolucion;
    let vistaIndividualActiva = false;
    const datosRendimientoGeneral = {
        labels: <?= json_encode(array_column($cursos, 'nombre_curso')) ?>,
        data: <?= json_encode(array_map(fn($c) => (float) $c['promedio'], $cursos)) ?>
    };
    const datosEvolucionGeneral = {
        labels: <?= json_encode(array_column($evolucionGeneral, 'periodo')) ?>,
        data: <?= json_encode(array_map(fn($e) => (float) $e['promedio'], $evolucionGeneral)) ?>
    };
    const coloresRiesgoGeneral = ['#EF4444', '#F59E0B', '#10B981'];
    const notasPorEstudiante = <?= json_encode($notasPorEstudianteMap, JSON_UNESCAPED_UNICODE) ?>;
    const evolucionPorEstudiante = <?= json_encode($evolucionPorEstudianteMap, JSON_UNESCAPED_UNICODE) ?>;
    const estudiantesInfo = <?= json_encode($estudiantesInfoMap, JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const base = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1200, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 16,
                        font: { size: 12, weight: '500' },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 }
                }
            }
        };

        // Rendimiento por Curso (barras horizontales)
        chartRendimiento = new Chart(document.getElementById('chartRendimiento'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($cursos, 'nombre_curso')) ?>,
                datasets: [{
                    label: 'Promedio',
                    data: <?= json_encode(array_map(fn($c) => (float) $c['promedio'], $cursos)) ?>,
                    backgroundColor: '#0D9488',
                    borderRadius: 6
                }]
            },
            options: {
                ...base,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, max: 20, grid: { color: '#f1f5f9' }, border: { display: false } },
                    y: { grid: { display: false }, border: { display: false } }
                }
            }
        });

        // Asistencia General (barras verticales)
        new Chart(document.getElementById('chartAsistencia'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($asistencia, 'aula')) ?>,
                datasets: [{
                    label: 'Faltas',
                    data: <?= json_encode(array_column($asistencia, 'faltas')) ?>,
                    backgroundColor: '#3B82F6',
                    borderRadius: 4
                }]
            },
            options: {
                ...base,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });

        // Distribucion de Riesgo (dona)
        chartRiesgo = new Chart(document.getElementById('chartRiesgo'), {
            type: 'doughnut',
            data: {
                labels: ['Alto', 'Medio', 'Bajo'],
                datasets: [{
                    data: [<?= $cRiesgoAlto ?>, <?= $cRiesgoMedio ?>, <?= $cRiesgoBajo ?>],
                    backgroundColor: ['#EF4444', '#F59E0B', '#10B981'],
                    borderWidth: 0,
                    cutout: '68%'
                }]
            },
            options: {
                ...base,
                scales: { x: { display: false }, y: { display: false } }
            }
        });

        // Evolucion de Notas (linea) — datos reales de la BD
        chartEvolucion = new Chart(document.getElementById('chartEvolucion'), {
            type: 'line',
            data: {
                labels: datosEvolucionGeneral.labels,
                datasets: [{
                    label: 'Tendencia general',
                    data: datosEvolucionGeneral.data,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139,92,246,.08)',
                    fill: true,
                    tension: 0.45,
                    pointRadius: 5,
                    pointBackgroundColor: '#8B5CF6'
                }]
            },
            options: {
                ...base,
                scales: {
                    y: { beginAtZero: false, min: 0, max: 20, grid: { color: '#f1f5f9' }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });
    });

    function filtrarTabla() {
        const texto = document.getElementById('buscarEstudiante').value.toLowerCase().trim();
        const riesgo = document.getElementById('filtroRiesgo').value.toLowerCase();
        const aula = document.getElementById('filtroAula').value.toLowerCase();
        const filas = document.querySelectorAll('#alertas tbody tr[data-fila]');
        let visibles = 0;
        let idVisible = null;

        filas.forEach(function(fila) {
            const contenido = fila.dataset.busqueda || '';
            const nivelRiesgo = fila.dataset.riesgo || '';
            const aulaFila = fila.dataset.aula || '';
            const coincideTexto = texto === '' || contenido.includes(texto);
            const coincideRiesgo = riesgo === '' || nivelRiesgo === riesgo;
            const coincideAula = aula === '' || aulaFila === aula;

            if (coincideTexto && coincideRiesgo && coincideAula) {
                fila.style.display = '';
                visibles++;
                idVisible = fila.dataset.id;
            } else {
                fila.style.display = 'none';
            }
        });

        const sinResultados = document.getElementById('sinResultados');
        if (sinResultados) {
            sinResultados.style.display = visibles === 0 ? '' : 'none';
        }

        const contador = document.getElementById('contadorResultados');
        if (contador) {
            contador.textContent = visibles + ' caso' + (visibles !== 1 ? 's' : '') + ' encontrado' + (visibles !== 1 ? 's' : '');
        }

        if (visibles === 1 && idVisible) {
            mostrarVistaEstudiante(idVisible);
        } else {
            mostrarVistaGeneral();
        }
    }

    function verEstudiante(fila) {
        const id = fila.dataset.id;
        if (!id || !estudiantesInfo[id]) return;
        document.getElementById('buscarEstudiante').value = estudiantesInfo[id].nombre;
        document.getElementById('filtroRiesgo').value = '';
        document.getElementById('filtroAula').value = '';
        filtrarTabla();
    }

    function mostrarVistaEstudiante(id) {
        const info = estudiantesInfo[id];
        if (!info || !chartRendimiento || !chartRiesgo || !chartEvolucion) return;

        vistaIndividualActiva = true;

        // Tarjeta de resumen
        const coloresAvatar = { alto: '#ef4444', medio: '#f59e0b', bajo: '#10b981' };
        const colorAvatar   = coloresAvatar[info.riesgo] || '#64748b';
        const tarjeta = document.getElementById('tarjetaEstudiante');
        if (tarjeta) {
            document.getElementById('tcAvatar').textContent    = info.iniciales || '??';
            document.getElementById('tcAvatar').style.background = colorAvatar;
            document.getElementById('tcNombre').textContent   = info.nombre;
            document.getElementById('tcSub').textContent      = 'DNI ' + info.dni + ' • ' + info.aula
                + ' • Riesgo ' + info.riesgoLabel;
            document.getElementById('tcSub').style.color = colorAvatar;
            document.getElementById('tcPromedio').textContent  = info.promedio;
            document.getElementById('tcFaltas').textContent    = info.faltas;
            document.getElementById('tcTardanzas').textContent = info.tardanzas;
            document.getElementById('tcPadre').textContent     = info.padre;
            document.getElementById('tcCorreo').textContent    = info.correo_padre;
            tarjeta.classList.add('visible');
        }

        // Rendimiento: notas del alumno por curso
        const notas = notasPorEstudiante[id] || [];
        chartRendimiento.data.labels = notas.map(n => n.curso);
        chartRendimiento.data.datasets[0].data = notas.map(n => n.nota);
        chartRendimiento.data.datasets[0].label = 'Notas de ' + info.nombre;
        chartRendimiento.update();

        // Distribucion: resalta la categoria de riesgo del alumno
        const categorias = ['alto', 'medio', 'bajo'];
        chartRiesgo.data.datasets[0].backgroundColor = coloresRiesgoGeneral.map(
            (color, i) => categorias[i] === info.riesgo ? color : '#e2e8f0'
        );
        chartRiesgo.update();

        // Evolucion: curva real de notas del alumno por periodo
        const evo = evolucionPorEstudiante[id] || [];
        chartEvolucion.data.labels = evo.map(e => e.periodo);
        chartEvolucion.data.datasets[0].data = evo.map(e => e.promedio);
        chartEvolucion.data.datasets[0].label = 'Evolucion de ' + info.nombre;
        chartEvolucion.update();

        const badgeRendimiento = document.getElementById('badgeRendimiento');
        if (badgeRendimiento) badgeRendimiento.textContent = info.nombre;

        const badgeRiesgo = document.getElementById('badgeRiesgo');
        if (badgeRiesgo) badgeRiesgo.textContent = info.nombre + ' • Riesgo ' + info.riesgo.charAt(0).toUpperCase() + info.riesgo.slice(1);

        const badgeEvolucion = document.getElementById('badgeEvolucion');
        if (badgeEvolucion) badgeEvolucion.textContent = info.nombre;
    }

    function mostrarVistaGeneral() {
        if (!vistaIndividualActiva || !chartRendimiento || !chartRiesgo || !chartEvolucion) return;
        vistaIndividualActiva = false;

        chartRendimiento.data.labels = datosRendimientoGeneral.labels;
        chartRendimiento.data.datasets[0].data = datosRendimientoGeneral.data;
        chartRendimiento.data.datasets[0].label = 'Promedio';
        chartRendimiento.update();

        const tarjetaG = document.getElementById('tarjetaEstudiante');
        if (tarjetaG) tarjetaG.classList.remove('visible');

        chartRiesgo.data.datasets[0].backgroundColor = coloresRiesgoGeneral;
        chartRiesgo.update();

        chartEvolucion.data.labels = datosEvolucionGeneral.labels;
        chartEvolucion.data.datasets[0].data = datosEvolucionGeneral.data;
        chartEvolucion.data.datasets[0].label = 'Tendencia general';
        chartEvolucion.update();

        const badgeRendimiento = document.getElementById('badgeRendimiento');
        if (badgeRendimiento) badgeRendimiento.textContent = 'Promedios';

        const badgeRiesgo = document.getElementById('badgeRiesgo');
        if (badgeRiesgo) badgeRiesgo.textContent = Object.keys(estudiantesInfo).length + ' estudiantes';

        const badgeEvolucion = document.getElementById('badgeEvolucion');
        if (badgeEvolucion) badgeEvolucion.textContent = 'Tendencia general';
    }

    function limpiarFiltros() {
        document.getElementById('buscarEstudiante').value = '';
        document.getElementById('filtroRiesgo').value = '';
        document.getElementById('filtroAula').value = '';
        filtrarTabla();
    }

    const _sortState = { columna: null, dir: 1 };
    const _riesgoOrd = { alto: 3, medio: 2, bajo: 1 };

    function ordenarTabla(columna) {
        if (_sortState.columna === columna) {
            _sortState.dir *= -1;
        } else {
            _sortState.columna = columna;
            _sortState.dir = 1;
        }

        // Actualizar clases e indicadores de los headers
        ['promedio', 'faltas', 'riesgo'].forEach(function(col) {
            const th    = document.getElementById('th-' + col);
            const arrow = document.getElementById('arrow-' + col);
            if (!th || !arrow) return;
            th.classList.remove('sort-asc', 'sort-desc');
            arrow.textContent = '↕';
            if (col === columna) {
                th.classList.add(_sortState.dir === 1 ? 'sort-asc' : 'sort-desc');
                arrow.textContent = _sortState.dir === 1 ? '↑' : '↓';
            }
        });

        const tbody = document.querySelector('#alertas tbody');
        const filas = Array.from(tbody.querySelectorAll('tr[data-fila]'));

        filas.sort(function(a, b) {
            let va, vb;
            if (columna === 'promedio') {
                va = parseFloat(a.dataset.promedio) || 0;
                vb = parseFloat(b.dataset.promedio) || 0;
            } else if (columna === 'faltas') {
                va = parseInt(a.dataset.faltas) || 0;
                vb = parseInt(b.dataset.faltas) || 0;
            } else {
                va = _riesgoOrd[a.dataset.riesgo] || 0;
                vb = _riesgoOrd[b.dataset.riesgo] || 0;
            }
            return (va - vb) * _sortState.dir;
        });

        // Reinsertar en orden (preserva la fila sinResultados al inicio)
        const sinResultados = document.getElementById('sinResultados');
        filas.forEach(function(fila) { tbody.appendChild(fila); });
        if (sinResultados) tbody.insertBefore(sinResultados, tbody.firstChild);
    }

    function exportarFiltrado() {
        const filas = document.querySelectorAll('#alertas tbody tr[data-fila]');
        const visibles = Array.from(filas).filter(f => f.style.display !== 'none');

        if (visibles.length === 0) {
            alert('No hay datos para exportar con los filtros actuales.');
            return;
        }

        const fechaHoy = new Date().toISOString().split('T')[0];
        const textoBusqueda = document.getElementById('buscarEstudiante').value.trim();
        const riesgoFiltro  = document.getElementById('filtroRiesgo').value;
        const aulaFiltro    = document.getElementById('filtroAula').value;

        const filtrosDesc = [
            textoBusqueda ? 'Búsqueda: ' + textoBusqueda : '',
            riesgoFiltro  ? 'Riesgo: ' + riesgoFiltro.charAt(0).toUpperCase() + riesgoFiltro.slice(1) : '',
            aulaFiltro    ? 'Aula: ' + aulaFiltro : ''
        ].filter(Boolean).join(' | ') || 'Todos los estudiantes';

        let html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
                 + 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
                 + 'xmlns="http://www.w3.org/TR/REC-html40">'
                 + '<head><meta charset="UTF-8"></head><body><table border="1">';

        html += '<tr><th colspan="8" style="background:#1e3a5f;color:white;font-size:16px;">'
              + 'I.E. N° 14008 &quot;Leonor Cerna de Valdiviezo&quot; — Alertas Tempranas de Deserción'
              + '</th></tr>';
        html += '<tr><th colspan="8" style="background:#f1f5f9;font-size:12px;">'
              + 'Fecha: ' + fechaHoy + ' &nbsp;|&nbsp; Filtros: ' + filtrosDesc
              + ' &nbsp;|&nbsp; Total: ' + visibles.length + ' estudiante(s)'
              + '</th></tr>';
        html += '<tr></tr>';
        html += '<tr style="background:#eef3f8;">'
              + '<th>Estudiante</th><th>DNI</th><th>Aula</th>'
              + '<th>Padre / Apoderado</th><th>Correo</th>'
              + '<th>Promedio</th><th>Faltas / Tardanzas</th><th>Riesgo</th>'
              + '</tr>';

        visibles.forEach(function(fila) {
            const celdas = fila.querySelectorAll('td');
            const nombre   = celdas[0].querySelector('strong')?.textContent.trim() || '';
            const dni      = (celdas[0].querySelector('small')?.textContent || '').replace('DNI', '').trim();
            const aula     = celdas[1].textContent.trim();
            const padre    = Array.from(celdas[2].childNodes)
                               .filter(n => n.nodeType === 3)
                               .map(n => n.textContent.trim())
                               .join('') || celdas[2].firstChild?.textContent.trim() || '';
            const correo   = celdas[2].querySelector('small')?.textContent.trim() || '';
            const promedio = celdas[3].textContent.trim();
            const faltas   = celdas[4].textContent.trim();
            const riesgo   = celdas[5].textContent.trim();
            const color    = riesgo === 'Alto' ? '#b91c1c' : (riesgo === 'Medio' ? '#b45309' : '#15803d');

            html += '<tr>'
                  + '<td>' + nombre + '</td>'
                  + '<td>' + dni    + '</td>'
                  + '<td>' + aula   + '</td>'
                  + '<td>' + padre  + '</td>'
                  + '<td>' + correo + '</td>'
                  + '<td style="text-align:center;">' + promedio + '</td>'
                  + '<td style="text-align:center;">' + faltas   + '</td>'
                  + '<td style="color:' + color + ';font-weight:bold;">' + riesgo + '</td>'
                  + '</tr>';
        });

        html += '</table></body></html>';

        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'alertas_' + (riesgoFiltro || 'todos') + '_' + fechaHoy + '.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>
