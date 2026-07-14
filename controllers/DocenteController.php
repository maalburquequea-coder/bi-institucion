<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../services/AIService.php';
require_once __DIR__ . '/../services/EmailService.php';

class DocenteController
{
    private AuthModel $auth;

    public function __construct()
    {
        $this->auth = new AuthModel(db());
    }

    public function docente(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if (!in_array($usuario['rol'], ['Docente', 'Tutor'], true)) {
            redirigir('portal.php');
        }

        $seccionActiva = (string) ($_GET['seccion'] ?? 'inicio');
        $mensaje = (string) ($_GET['mensaje'] ?? '');
        $error = (string) ($_GET['error'] ?? '');
        $referenciaUrl = '&grado=' . urlencode((string)($_GET['grado'] ?? '')) . '&aula=' . urlencode((string)($_GET['aula'] ?? ''));

        // --- AJAX: obtener plan de mejora IA en caché ---
        if ($seccionActiva === 'alertas' && ($_GET['accion'] ?? '') === 'get_plan_ia') {
            $idEstudiante = (int) ($_GET['id_estudiante'] ?? 0);
            $targetId = $idEstudiante;

            $studentDetails = null;
            if ($idEstudiante > 0) {
                $modeloEstudiante = new EstudianteModel(db());
                $studentDetails = method_exists($modeloEstudiante, 'getStudentDetailsForPlan')
                    ? $modeloEstudiante->getStudentDetailsForPlan($idEstudiante)
                    : null;

                if ($studentDetails) {
                    $studentDetails['promedio']     = (float)$studentDetails['promedio'];
                    $studentDetails['asistencia']   = (int)$studentDetails['asistencia'];
                    $studentDetails['area_critica'] = (string)$studentDetails['area_critica'];
                    $studentDetails['grado']        = (string)$studentDetails['grado'];
                    $studentDetails['seccion']      = (string)$studentDetails['seccion'];
                }
            }

            $studentDetails = $studentDetails ?? [
                'promedio'    => (float) ($_GET['promedio'] ?? 0),
                'asistencia'  => (int) ($_GET['asistencia'] ?? 0),
                'area_critica'=> (string) ($_GET['area_critica'] ?? 'General'),
                'grado'       => (string) ($_GET['grado'] ?? ''),
                'seccion'     => (string) ($_GET['student_seccion'] ?? ''),
            ];

            if ($studentDetails) {
                $plan = $this->auth->getOrCreatePlanMejoraDocente(
                    $targetId,
                    [
                        'promedio'    => (float)$studentDetails['promedio'],
                        'asistencia'  => (int)$studentDetails['asistencia'],
                        'area_critica'=> (string)$studentDetails['area_critica'],
                        'grado'       => (string)$studentDetails['grado'],
                        'seccion'     => (string)$studentDetails['seccion'],
                    ]
                );
                header('Content-Type: application/json');
                echo json_encode(['plan' => $plan]);
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode(['plan' => [], 'error' => 'No se pudo obtener el plan para el estudiante.']);
            exit;
        }

        // --- AJAX: generar plan de mejora con IA ---
        if ($seccionActiva === 'alertas' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generar_plan_ia') {
            $this->validarCSRF();
            $idEstudiante = (int) ($_POST['id_estudiante'] ?? 0);
            $targetId = $idEstudiante;

            $studentDetails = null;
            if ($idEstudiante > 0) {
                $modeloEstudiante = new EstudianteModel(db());
                $studentDetails = method_exists($modeloEstudiante, 'getStudentDetailsForPlan')
                    ? $modeloEstudiante->getStudentDetailsForPlan($idEstudiante)
                    : null;
            }

            $studentDetails = $studentDetails ?? [
                'promedio'    => (float) ($_POST['promedio'] ?? 0),
                'asistencia'  => (int) ($_POST['asistencia'] ?? 0),
                'area_critica'=> (string) ($_POST['area_critica'] ?? 'General'),
                'grado'       => (string) ($_POST['grado'] ?? ''),
                'seccion'     => (string) ($_POST['seccion'] ?? ''),
            ];

            if ($studentDetails) {
                try {
                    $plan = AIService::generarPlanMejora([
                        'promedio'     => (float)$studentDetails['promedio'],
                        'asistencia'   => (int)$studentDetails['asistencia'],
                        'area_critica' => $studentDetails['area_critica'],
                        'grado'        => $studentDetails['grado'] . ' ' . $studentDetails['seccion'],
                    ]);
                    $this->auth->guardarPlanCache($targetId, (float)$studentDetails['promedio'], (int)$studentDetails['asistencia'], $studentDetails['area_critica'], $plan);

                    header('Content-Type: application/json');
                    echo json_encode(['plan' => $plan, 'message' => 'Nuevo plan generado con éxito.']);
                    exit;
                } catch (Throwable $e) {
                    error_log('Error generando plan IA: ' . $e->getMessage());
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'No se pudo generar el plan. Intente nuevamente.']);
                    exit;
                }
            }
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID de estudiante no válido o datos insuficientes para generar el plan.']);
            exit;
        }
        // --- Fin AJAX ---

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $accion = (string) ($_POST['accion'] ?? '');

            if ($accion === 'eliminar_asistencia') {
                $idDoc = (int) ($_POST['id_documento_asistencia'] ?? $_POST['id_documento'] ?? 0);

                if ($idDoc > 0) {
                    if ($this->auth->eliminarDocumentoAsistencia($idDoc)) {
                        $modulo = $seccionActiva === 'notas' ? 'Notas' : 'Asistencia';
                        $this->auth->registrarAuditoria((int) $usuario['id_usuario'], $modulo, 'Docente elimino archivo', "ID Documento: $idDoc");
                        redirigir('docente.php?seccion=' . $seccionActiva . $referenciaUrl . '&mensaje=Archivo eliminado correctamente.');
                    } else {
                        redirigir('docente.php?seccion=' . $seccionActiva . $referenciaUrl . '&error=Error: No se pudo eliminar el archivo.');
                    }
                }
                redirigir('docente.php?seccion=' . $seccionActiva . $referenciaUrl . '&error=Error: El identificador del documento no es válido.');
            }

            if ($accion === 'enviar_plan_padre') {
                $idEstudiante  = (int) ($_POST['id_estudiante'] ?? 0);
                $mensajePlan   = (string) ($_POST['mensaje_plan'] ?? '');
                $targetId = $idEstudiante;

                if ($targetId > 0 && !empty($mensajePlan)) {
                    if ($this->auth->crearNotificacionPlanMejora((int) $usuario['id_usuario'], $targetId, $mensajePlan)) {
                        $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Notificaciones', 'Envio Plan IA', "Plan de mejora registrado para portal y correo: Estudiante ID $targetId");
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Plan de mejora enviado al padre correctamente.']);
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'No se pudo enviar el plan. El estudiante no tiene un padre vinculado.']);
                        exit;
                    }
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Datos insuficientes para enviar el plan.']);
                exit;
            }

            if ($accion === 'reenviar_notificacion_correo') {
                $idEstudiante = (int)($_POST['id_estudiante'] ?? 0);
                $idPadre      = (int)($_POST['id_padre'] ?? 0);
                $mensaje      = (string)($_POST['mensaje'] ?? '');
                $correoPadre  = (string)($_POST['correo'] ?? '');

                header('Content-Type: application/json');

                try {
                    // Buscar padre real vinculado al estudiante real
                    $padreReal = $this->auth->buscarIdPadreDeEstudiante($idEstudiante);
                    if ($padreReal > 0) {
                        $idPadre = $padreReal;
                        $padreDatos = $this->auth->buscarUsuarioPorId($idPadre);
                        if ($padreDatos && !empty($padreDatos['correo'])) {
                            $correoPadre = $padreDatos['correo'];
                        }
                    }

                    // Fallback: buscar padre por correo si aún no se encontró
                    if ($idPadre <= 0 && $correoPadre !== '') {
                        $idPadre = $this->auth->buscarIdUsuarioPorCorreo($correoPadre);
                    }

                    if ($idEstudiante <= 0 || $idPadre <= 0 || $correoPadre === '') {
                        echo json_encode(['success' => false, 'error' => 'No se encontró un estudiante o padre real para registrar el correo. Verifique la vinculación.']);
                        exit;
                    }

                    $exito = EmailService::enviar($correoPadre, 'Recordatorio: Alerta Academica - BI Educativo', $mensaje);

                    $this->auth->registrarNotificacionPadreCorreo($idEstudiante, $idPadre, $mensaje, $exito ? 'Enviado' : 'Fallido');
                    $this->auth->registrarAuditoria((int)$usuario['id_usuario'], 'Notificaciones', 'Reenvio Correo', "Reenvio a padre ID: $idPadre");

                    echo json_encode([
                        'success' => true,
                        'message' => $exito
                            ? 'Correo enviado y registrado con exito.'
                            : 'No se pudo enviar por Gmail, pero quedo registrado como Fallido.',
                    ]);
                    exit;
                } catch (Throwable $e) {
                    error_log('Error al reenviar notificacion por correo: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'No se pudo registrar el envio: ' . $e->getMessage()]);
                    exit;
                }
            }

            $csvEditado      = trim((string) ($_POST['csv_editado'] ?? ''));
            $tieneCSVEditado = $csvEditado !== '';
            $tieneArchivo    = isset($_FILES['archivo_documento']) && $_FILES['archivo_documento']['error'] !== UPLOAD_ERR_NO_FILE;

            if ($tieneCSVEditado || $tieneArchivo) {
                $ext       = $tieneCSVEditado ? 'csv' : strtolower(pathinfo($_FILES['archivo_documento']['name'], PATHINFO_EXTENSION));
                $permitidos = ['xls', 'xlsx', 'csv'];

                if (!in_array($ext, $permitidos, true)) {
                    $error = 'Error: Solo se permiten archivos Excel o CSV.';
                } else {
                    $nombreArchivo = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $rutaDestino   = __DIR__ . '/../uploads/asistencia/' . $nombreArchivo;

                    if (!is_dir(__DIR__ . '/../uploads/asistencia/')) {
                        mkdir(__DIR__ . '/../uploads/asistencia/', 0777, true);
                    }

                    $guardado = $tieneCSVEditado
                        ? (file_put_contents($rutaDestino, $csvEditado) !== false)
                        : ($_FILES['archivo_documento']['error'] === UPLOAD_ERR_OK && move_uploaded_file($_FILES['archivo_documento']['tmp_name'], $rutaDestino));

                    if ($guardado) {
                        if ($seccionActiva === 'asistencia' && !$this->validarContenidoAsistencia($rutaDestino, $ext)) {
                            @unlink($rutaDestino);
                            redirigir('docente.php?seccion=' . $seccionActiva . '&error=Error: El formato del archivo de asistencia es inválido.');
                        }

                        $this->auth->guardarDocumentoAsistencia([
                            'id_docente' => (int) $usuario['id_usuario'],
                            'nivel'      => (string) ($_POST['nivel'] ?? 'Secundaria'),
                            'grado'      => (int) ($_POST['grado'] ?? 0),
                            'seccion'    => (string) ($_POST['seccion'] ?? ''),
                            'titulo'     => ($seccionActiva === 'asistencia' ? 'Asistencia' : 'Notas') . ' ' . ($_POST['curso'] ?? 'Curso'),
                            'archivo'    => $nombreArchivo,
                        ]);

                        $idCurso = (int) ($_POST['id_curso'] ?? 0);
                        if ($seccionActiva === 'notas' && $idCurso > 0) {
                            $this->procesarNotasExcel($rutaDestino, $ext, (int) $usuario['id_usuario'], $idCurso, db());
                        } elseif ($seccionActiva === 'asistencia') {
                            $this->procesarAsistenciaExcel($rutaDestino, $ext, db());
                        }

                        redirigir('docente.php?seccion=' . $seccionActiva . '&mensaje=Archivo de ' . $seccionActiva . ' cargado correctamente.');
                    }
                }
            }
        }

        $modelo = new EstudianteModel(db());

        // Secciones de carga solo necesitan cursos + historial — omitir queries pesados
        if (in_array($seccionActiva, ['asistencia', 'notas'], true)) {
            $panelDocente   = $modelo->panelCargaDocente((int) $usuario['id_usuario']);
            $tipoBusqueda   = $seccionActiva === 'asistencia' ? 'Asistencia' : 'Notas';
            $panelDocente['historial_cargas'] = array_values(array_filter(
                $panelDocente['historial_cargas'],
                fn($c) => stripos((string) ($c['titulo'] ?? ''), $tipoBusqueda) !== false
            ));
            $grados            = [];
            $gradoSeleccionado = 2;
            $seccionAula       = 'A';
            $estudiantes       = [];
            $unidades          = [];
            $resumenRiesgos    = ['alto' => 0, 'medio' => 0, 'bajo' => 0];
            require __DIR__ . '/../views/docente_v.php';
            return;
        }

        $grados            = $modelo->resumenDocenteEpt((int) $usuario['id_usuario']);
        $gradoSeleccionado = (int) ($_GET['grado'] ?? ($grados[0]['grado'] ?? 2));

        if ($gradoSeleccionado < 2 || $gradoSeleccionado > 5) {
            $gradoSeleccionado = !empty($grados) ? (int)$grados[0]['grado'] : 2;
        }

        $seccionAula = strtoupper(trim((string) ($_GET['aula'] ?? 'A')));
        if (!in_array($seccionAula, ['A', 'B', 'C'], true)) { $seccionAula = 'A'; }

        $panelDocente = $modelo->docentePanelCompleto((int) $usuario['id_usuario']);

        $panelDocente['kpis']['total_estudiantes'] = count($panelDocente['rendimiento']);
        $panelDocente['kpis']['alertas_activas']   = count(array_filter($panelDocente['riesgos'], fn($r) => $r['riesgo'] !== 'Bajo'));

        $resumenRiesgos = [
            'alto'  => count(array_filter($panelDocente['riesgos'], fn($r) => $r['riesgo'] === 'Alto')),
            'medio' => count(array_filter($panelDocente['riesgos'], fn($r) => $r['riesgo'] === 'Medio')),
            'bajo'  => count(array_filter($panelDocente['riesgos'], fn($r) => $r['riesgo'] === 'Bajo')),
        ];

        $estudiantes = $modelo->estudiantesEptPorGrado($gradoSeleccionado, (int) $usuario['id_usuario']);
        $unidades    = $modelo->unidadesAcademicas();

        if (isset($panelDocente['historial_cargas'])) {
            $tipoBusqueda = $seccionActiva === 'asistencia' ? 'Asistencia' : 'Notas';
            $panelDocente['historial_cargas'] = array_filter($panelDocente['historial_cargas'], function($carga) use ($tipoBusqueda) {
                return stripos((string)$carga['titulo'], $tipoBusqueda) !== false;
            });
        }

        if ($seccionActiva === 'alertas') {
            $fRiesgo = (string) ($_GET['filtro_riesgo'] ?? '');
            $fCurso  = (string) ($_GET['filtro_curso'] ?? '');

            $panelDocente['riesgos'] = array_filter($panelDocente['riesgos'], function($r) use ($fRiesgo, $fCurso) {
                return (empty($fRiesgo) || $r['riesgo'] === $fRiesgo) &&
                       (empty($fCurso)  || $r['nombre_curso'] === $fCurso);
            });
        }

        if ($seccionActiva === 'notificaciones') {
            $fEstado = (string) ($_GET['filtro_estado'] ?? '');
            $fFecha  = (string) ($_GET['filtro_fecha'] ?? '');

            $panelDocente['notificaciones'] = array_filter($panelDocente['notificaciones'], function($n) use ($fEstado, $fFecha) {
                return (empty($fEstado) || ($n['estado'] ?? '') === $fEstado) &&
                       (empty($fFecha)  || (isset($n['fecha_envio']) && strpos($n['fecha_envio'], $fFecha) !== false));
            });
        }

        if ($seccionActiva === 'reportes') {
            $fGrado = (string) ($_GET['filtro_grado_reporte'] ?? '');

            $panelDocente['riesgos'] = array_filter($panelDocente['riesgos'], function($r) use ($fGrado) {
                return empty($fGrado) || (string)($r['grado'] ?? '') === $fGrado;
            });
        }

        if (!empty($panelDocente['riesgos'])) {
            usort($panelDocente['riesgos'], fn($a, $b) => strcmp($a['apellidos'] . $a['nombres'], $b['apellidos'] . $b['nombres']));
        }

        $resultadosBusqueda = [];
        if ($seccionActiva === 'inicio') {
            $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
            if (mb_strlen($q) >= 2) {
                $resultadosBusqueda = $modelo->buscarEstudianteDocente((int) $usuario['id_usuario'], $q);
            }
        }

        require __DIR__ . '/../views/docente_v.php';
    }

    private function validarContenidoAsistencia(string $ruta, string $ext): bool
    {
        if ($ext === 'csv') {
            if (($handle = fopen($ruta, 'r')) !== false) {
                $headers = fgetcsv($handle, 1000, ",");
                fclose($handle);

                if (!$headers) return false;

                $headerLine = strtolower(implode(' ', $headers));
                foreach (['dni', 'estado'] as $req) {
                    if (strpos($headerLine, $req) === false) {
                        return false;
                    }
                }
                return true;
            }
        }
        return file_exists($ruta) && filesize($ruta) > 100;
    }

    private function validarCSRF(): void
    {
        $token       = (string) ($_POST['csrf_token'] ?? '');
        $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
            die('Error de seguridad: Token CSRF invalido o ausente.');
        }
    }

    private function procesarNotasExcel(string $ruta, string $ext, int $idDocente, int $idCurso, PDO $pdo): void
    {
        $filas = $this->parsearExcelInterno($ruta, $ext);
        if (empty($filas)) return;

        $filaEnc = null;
        $colDni  = 1;
        $colNotas = [];

        foreach ($filas as $idx => $fila) {
            foreach ($fila as $ci => $celda) {
                $upper = strtoupper((string)$celda);
                if (str_contains($upper, 'D.N.I') || str_contains($upper, 'DNI') || str_contains($upper, 'CODIGO')) {
                    $filaEnc = $idx;
                    $colDni  = $ci;
                    for ($c = $ci + 1; $c < count($fila); $c++) {
                        $h = strtoupper(trim((string)($fila[$c] ?? '')));
                        if (str_contains($h, 'NOTA') || (str_contains($h, 'LOGRO') && !str_contains($h, 'PROMEDIO'))) {
                            $colNotas[] = $c;
                        }
                    }
                    break 2;
                }
            }
        }

        if ($filaEnc === null || empty($colNotas)) return;

        $periodos = ['Unidad 1', 'Unidad 2', 'Unidad 3', 'Unidad 4'];
        for ($i = $filaEnc + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $dni  = trim((string)($fila[$colDni] ?? ''));
            if ($dni === '' || !is_numeric($dni)) continue;

            $stmtE = $pdo->prepare("SELECT id_estudiante FROM estudiantes WHERE dni = ? LIMIT 1");
            $stmtE->execute([$dni]);
            $idEst = (int)$stmtE->fetchColumn();
            if ($idEst === 0) continue;

            foreach ($colNotas as $n => $colNota) {
                $nota = $this->letraANota(trim((string)($fila[$colNota] ?? '')));
                if ($nota === null) continue;
                $periodo = $periodos[$n] ?? ('Unidad ' . ($n + 1));

                $chk = $pdo->prepare("SELECT COUNT(*) FROM calificaciones WHERE id_estudiante=? AND id_curso=? AND id_docente=? AND periodo=?");
                $chk->execute([$idEst, $idCurso, $idDocente, $periodo]);
                if ((int)$chk->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE calificaciones SET nota_final=?, fecha_registro=NOW() WHERE id_estudiante=? AND id_curso=? AND id_docente=? AND periodo=?")
                        ->execute([$nota, $idEst, $idCurso, $idDocente, $periodo]);
                } else {
                    $pdo->prepare("INSERT INTO calificaciones (id_estudiante,id_curso,id_docente,nota_final,periodo,fecha_registro) VALUES (?,?,?,?,?,NOW())")
                        ->execute([$idEst, $idCurso, $idDocente, $nota, $periodo]);
                }
            }
        }
    }

    private function procesarAsistenciaExcel(string $ruta, string $ext, PDO $pdo): void
    {
        $filas = $this->parsearExcelInterno($ruta, $ext);
        if (empty($filas)) return;

        $filaEnc  = null;
        $colDni   = 1;
        $colFechas = [];

        foreach ($filas as $idx => $fila) {
            foreach ($fila as $ci => $celda) {
                $upper = strtoupper((string)$celda);
                if (str_contains($upper, 'D.N.I') || str_contains($upper, 'DNI') || str_contains($upper, 'CODIGO')) {
                    $filaEnc = $idx;
                    $colDni  = $ci;
                    for ($c = $ci + 2; $c < count($fila); $c++) {
                        $fecha = $this->parsearFechaExcel(trim((string)($fila[$c] ?? '')));
                        if ($fecha !== null) {
                            $colFechas[$c] = $fecha;
                        }
                    }
                    break 2;
                }
            }
        }

        if ($filaEnc === null || empty($colFechas)) return;

        for ($i = $filaEnc + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $dni  = trim((string)($fila[$colDni] ?? ''));
            if ($dni === '' || !is_numeric($dni)) continue;

            $stmtE = $pdo->prepare("SELECT id_estudiante FROM estudiantes WHERE dni = ? LIMIT 1");
            $stmtE->execute([$dni]);
            $idEst = (int)$stmtE->fetchColumn();
            if ($idEst === 0) continue;

            foreach ($colFechas as $col => $fecha) {
                $raw    = strtoupper(trim((string)($fila[$col] ?? '')));
                $estado = match($raw) {
                    'F' => 'Falto',
                    'A' => 'Presente',
                    'T' => 'Tardanza',
                    default => null,
                };
                if ($estado === null) continue;

                $chk = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE id_estudiante=? AND fecha=?");
                $chk->execute([$idEst, $fecha]);
                if ((int)$chk->fetchColumn() === 0) {
                    $pdo->prepare("INSERT INTO asistencia (id_estudiante,fecha,estado) VALUES (?,?,?)")
                        ->execute([$idEst, $fecha, $estado]);
                } else {
                    $pdo->prepare("UPDATE asistencia SET estado=? WHERE id_estudiante=? AND fecha=?")
                        ->execute([$estado, $idEst, $fecha]);
                }
            }
        }
    }

    private function letraANota(string $letra): ?float
    {
        return match(strtoupper($letra)) {
            'AD' => 18.0,
            'A'  => 15.0,
            'B'  => 12.0,
            'C'  =>  9.0,
            default => null,
        };
    }

    private function parsearFechaExcel(string $valor): ?string
    {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $valor, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $valor, $m)) {
            return sprintf('%04d-%02d-%02d', 2000 + (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (is_numeric($valor) && (int)$valor > 40000 && (int)$valor < 55000) {
            return date('Y-m-d', (int)(((float)$valor - 25569) * 86400));
        }
        return null;
    }

    private function sinNamespacesExcel(string $xml): string
    {
        $xml = (string)preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
        return (string)preg_replace('/<(\/?)\w+:/', '<$1', $xml);
    }

    private function parsearExcelInterno(string $ruta, string $ext): array
    {
        if ($ext === 'csv') {
            $filas  = [];
            $handle = fopen($ruta, 'r');
            if (!$handle) return [];
            $primera = (string)fgets($handle);
            rewind($handle);
            $sep = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
            while (($datos = fgetcsv($handle, 4000, $sep)) !== false) {
                $filas[] = array_map('strval', $datos);
            }
            fclose($handle);
            return $filas;
        }

        if (!in_array($ext, ['xlsx', 'xls'], true) || !class_exists('ZipArchive')) return [];

        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) return [];

        $cadenas = [];
        $ssXml   = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = @simplexml_load_string($this->sinNamespacesExcel($ssXml));
            if ($ss) {
                foreach ($ss->si as $si) {
                    $cadenas[] = isset($si->t) ? (string)$si->t : implode('', array_map(fn($r) => (string)($r->t ?? ''), iterator_to_array($si->r ?? new SimpleXMLElement('<r/>'), false)));
                }
            }
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        $relXml    = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relXml !== false) {
            $rels = @simplexml_load_string($this->sinNamespacesExcel($relXml));
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    if (str_contains((string)$rel['Type'], 'worksheet')) {
                        $t = (string)$rel['Target'];
                        $sheetPath = str_starts_with($t, '/') ? ltrim($t, '/') : 'xl/' . $t;
                        break;
                    }
                }
            }
        }

        $hojaXml = $zip->getFromName($sheetPath);
        if ($hojaXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = $zip->getNameIndex($i);
                if ($n !== false && str_contains($n, 'worksheets/') && str_ends_with($n, '.xml')) {
                    $hojaXml = $zip->getFromIndex($i);
                    if ($hojaXml !== false) break;
                }
            }
        }
        $zip->close();
        if ($hojaXml === false) return [];

        $hoja = @simplexml_load_string($this->sinNamespacesExcel($hojaXml));
        if (!$hoja || !isset($hoja->sheetData)) return [];

        $rawFilas = [];
        $maxCol   = 0;
        foreach ($hoja->sheetData->row as $fila) {
            $idxFila = (int)$fila['r'] - 1;
            $celdas  = [];
            foreach ($fila->c as $celda) {
                preg_match('/^([A-Z]+)\d+$/i', (string)$celda['r'], $m);
                if (!$m) continue;
                $colStr = strtoupper($m[1]);
                $colIdx = 0;
                for ($j = 0; $j < strlen($colStr); $j++) {
                    $colIdx = $colIdx * 26 + (ord($colStr[$j]) - 64);
                }
                $colIdx--;
                $maxCol = max($maxCol, $colIdx);
                $tipo   = (string)($celda['t'] ?? '');
                $val    = isset($celda->v) ? (string)$celda->v : '';
                if ($tipo === 's') $val = $cadenas[(int)$val] ?? $val;
                elseif ($tipo === 'inlineStr') $val = (string)($celda->is->t ?? '');
                $celdas[$colIdx] = $val;
            }
            if (!empty($celdas)) $rawFilas[$idxFila] = $celdas;
        }

        if (empty($rawFilas)) return [];
        ksort($rawFilas);
        $resultado = [];
        foreach ($rawFilas as $celdas) {
            $fila = [];
            for ($c = 0; $c <= $maxCol; $c++) $fila[] = $celdas[$c] ?? '';
            $resultado[] = $fila;
        }
        return $resultado;
    }
}
