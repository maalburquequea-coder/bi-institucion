<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../helpers/funciones.php';

class AdminController
{
    private AuthModel $auth;

    public function __construct()
    {
        $this->auth = new AuthModel(db());
    }

    public function admin(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $resumen = $this->auth->resumenAdmin();
        require __DIR__ . '/../views/admin_v.php';
    }

    public function aprobaciones(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $mensaje = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $tipo = (string) ($_POST['tipo'] ?? 'usuario');
            $accion = (string) ($_POST['accion'] ?? '');

            if ($tipo === 'vinculacion') {
                $idSolicitud = (int) ($_POST['id_solicitud'] ?? 0);
                if ($accion === 'aprobar') {
                    $mensaje = $this->auth->aprobarSolicitudVinculacion($idSolicitud)
                        ? 'Vinculacion aprobada correctamente.'
                        : 'No se pudo aprobar. Verifica que el DNI del estudiante exista.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Aprobaciones', 'Aprobar vinculacion', 'Solicitud ID ' . $idSolicitud);
                } elseif ($accion === 'rechazar') {
                    $mensaje = $this->auth->rechazarSolicitudVinculacion($idSolicitud)
                        ? 'Vinculacion rechazada correctamente.'
                        : 'No se pudo rechazar la solicitud.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Aprobaciones', 'Rechazar vinculacion', 'Solicitud ID ' . $idSolicitud);
                }
            } else {
                $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
                if ($accion === 'aprobar') {
                    $mensaje = $this->auth->aprobarUsuario($idUsuario) ? 'Usuario aprobado correctamente.' : 'No se pudo aprobar el usuario.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Aprobaciones', 'Aprobar usuario', 'Usuario ID ' . $idUsuario);
                } elseif ($accion === 'rechazar') {
                    $mensaje = $this->auth->rechazarUsuario($idUsuario) ? 'Usuario rechazado correctamente.' : 'No se pudo rechazar el usuario.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Aprobaciones', 'Rechazar usuario', 'Usuario ID ' . $idUsuario);
                }
            }
        }

        $pendientes = $this->auth->usuariosPendientes();
        $vinculaciones = $this->auth->solicitudesVinculacionPendientes();
        require __DIR__ . '/../views/aprobaciones_v.php';
    }

    public function usuarios(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $mensaje = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $accion = (string) ($_POST['accion'] ?? 'editar');
            $data = [
                'id_usuario' => (int) ($_POST['id_usuario'] ?? 0),
                'dni' => preg_replace('/\D/', '', (string) ($_POST['dni'] ?? '')),
                'nombres' => trim((string) ($_POST['nombres'] ?? '')),
                'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
                'correo' => trim((string) ($_POST['correo'] ?? '')),
                'telefono' => preg_replace('/\D/', '', (string) ($_POST['telefono'] ?? '')),
                'id_rol' => (int) ($_POST['id_rol'] ?? 0),
                'estado_cuenta' => (string) ($_POST['estado_cuenta'] ?? 'pendiente'),
                'contrasena' => (string) ($_POST['contrasena'] ?? ''),
            ];

            $estados = ['pendiente', 'activo', 'rechazado'];
            if ($accion === 'eliminar') {
                if ($data['id_usuario'] === (int) $usuario['id_usuario']) {
                    $mensaje = 'No puedes eliminar tu propia cuenta.';
                } else {
                    $mensaje = $this->auth->eliminarUsuario($data['id_usuario']) ? 'Usuario eliminado correctamente.' : 'No se pudo eliminar porque tiene informacion vinculada.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Usuarios', 'Eliminar usuario', 'Usuario ID ' . $data['id_usuario']);
                }
            } elseif ($data['nombres'] === '' || $data['apellidos'] === '' || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                $mensaje = 'Revisa nombres, apellidos y correo.';
            } elseif ($data['telefono'] !== '' && strlen($data['telefono']) < 9) {
                $mensaje = 'El numero de WhatsApp debe tener al menos 9 digitos.';
            } elseif (!in_array($data['estado_cuenta'], $estados, true)) {
                $mensaje = 'Estado no valido.';
            } elseif ($accion === 'crear') {
                if (strlen($data['dni']) !== 8 || !contrasenaRobusta($data['contrasena'])) {
                    $mensaje = 'Para crear usuario, DNI debe tener 8 digitos y la contrasena debe tener minimo 8 caracteres, mayuscula, minuscula, numero y simbolo.';
                } else {
                    try {
                        $idNuevo = $this->auth->registrarUsuarioAdmin($data);
                        $mensaje = 'Usuario creado correctamente.';
                        $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Usuarios', 'Crear usuario', 'Usuario ID ' . $idNuevo);
                    } catch (Throwable) {
                        $mensaje = 'No se pudo crear. Revisa que DNI o correo no esten repetidos.';
                    }
                }
            } else { // Accion 'editar'
                try {
                    $mensaje = $this->auth->actualizarUsuarioAdmin($data) ? 'Usuario actualizado correctamente.' : 'No se pudo actualizar.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Usuarios', 'Editar usuario', 'Usuario ID ' . $data['id_usuario']);
                } catch (Throwable) {
                    $mensaje = 'No se pudo actualizar. Es posible que el correo o DNI ya esten registrados por otro usuario.';
                }
            }
        }

        $roles = $this->auth->obtenerRoles();
        $usuarios = $this->auth->usuariosRegistrados();
        require __DIR__ . '/../views/usuarios_v.php';
    }

    public function configuracion(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $mensaje = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $accion = (string) ($_POST['accion'] ?? 'config');
            if ($accion === 'periodo') {
                $data = [
                    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
                    'fecha_inicio' => (string) ($_POST['fecha_inicio'] ?? ''),
                    'fecha_fin' => (string) ($_POST['fecha_fin'] ?? ''),
                    'activo' => isset($_POST['activo']) ? 1 : 0,
                ];
                if ($data['nombre'] === '' || $data['fecha_inicio'] === '' || $data['fecha_fin'] === '') {
                    $mensaje = 'Completa los datos del periodo.';
                } else {
                    $mensaje = $this->auth->guardarPeriodo($data) ? 'Periodo registrado.' : 'No se pudo registrar el periodo.';
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Configuracion', 'Crear periodo', $data['nombre']);
                }
            } elseif ($accion === 'config') {
                try {
                    db()->beginTransaction();
                    $this->auth->actualizarConfiguracion($_POST['config'] ?? []);
                    $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Configuracion', 'Actualizar parametros', 'Parametros de riesgo e IA');
                    db()->commit();
                    $mensaje = 'Configuracion actualizada correctamente.';
                } catch (Throwable $e) {
                    db()->rollBack();
                    $mensaje = 'Error al actualizar la configuracion: ' . $e->getMessage();
                }
            }
        }

        $configuracion = $this->auth->configuracionSistema();
        $periodos = $this->auth->periodosAcademicos();
        require __DIR__ . '/../views/configuracion_v.php';
    }

    public function auditoria(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $acciones = $this->auth->auditoriaSistema();
        $accesos  = $this->auth->accesosSistema();

        $export = (string) ($_GET['export'] ?? '');
        if ($export === 'acciones') {
            $this->exportarAuditoriaXLS($acciones, 'acciones');
        }
        if ($export === 'accesos') {
            $this->exportarAuditoriaXLS($accesos, 'accesos');
        }

        require __DIR__ . '/../views/auditoria_v.php';
    }

    private function exportarAuditoriaXLS(array $registros, string $tipo): never
    {
        $fecha    = date('Y-m-d');
        $filename = 'auditoria_' . $tipo . '_' . $fecha . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"></head><body><table border="1">';

        if ($tipo === 'acciones') {
            echo '<tr><th colspan="6" style="background:#1e3a5f;color:white;font-size:16px;">Historial de Acciones del Sistema - ' . $fecha . '</th></tr>';
            echo '<tr style="background:#eef3f8;"><th>Fecha</th><th>Usuario</th><th>Modulo</th><th>Accion</th><th>Detalle</th><th>IP</th></tr>';
            foreach ($registros as $a) {
                echo '<tr>';
                echo '<td>' . e($a['fecha']) . '</td>';
                echo '<td>' . e($a['usuario'] ?: '-') . '</td>';
                echo '<td>' . e($a['modulo']) . '</td>';
                echo '<td>' . e($a['accion']) . '</td>';
                echo '<td>' . e($a['detalle']) . '</td>';
                echo '<td>' . e($a['ip']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><th colspan="5" style="background:#1e3a5f;color:white;font-size:16px;">Registro de Accesos al Sistema - ' . $fecha . '</th></tr>';
            echo '<tr style="background:#eef3f8;"><th>Fecha</th><th>Correo</th><th>Usuario</th><th>Resultado</th><th>IP</th></tr>';
            foreach ($registros as $a) {
                $resultado = (int) $a['exito'] === 1 ? 'Correcto' : 'Fallido';
                $color     = (int) $a['exito'] === 1 ? '#15803d' : '#b91c1c';
                echo '<tr>';
                echo '<td>' . e($a['fecha']) . '</td>';
                echo '<td>' . e($a['correo']) . '</td>';
                echo '<td>' . e($a['usuario'] ?: '-') . '</td>';
                echo '<td style="color:' . $color . ';font-weight:bold;">' . $resultado . '</td>';
                echo '<td>' . e($a['ip']) . '</td>';
                echo '</tr>';
            }
        }

        echo '</table></body></html>';
        exit;
    }

    public function notificaciones(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        $notificaciones = $this->auth->todasLasNotificaciones();

        if (($_GET['export'] ?? '') === 'excel') {
            $this->exportarNotificacionesXLS($notificaciones);
        }

        require __DIR__ . '/../views/notificaciones_v.php';
    }

    public function asistencia(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'Director') {
            redirigir('portal.php');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF();
            $accion = (string) ($_POST['accion'] ?? '');
            if ($accion === 'eliminar_asistencia') {
                $id = (int) ($_POST['id_documento'] ?? $_POST['id_documento_asistencia'] ?? 0);
                if ($id > 0) {
                    if ($this->auth->eliminarDocumentoAsistencia($id)) {
                        $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Asistencia', 'Eliminar documento', 'Documento ID ' . $id);
                        redirigir('asistencia.php?fecha=' . ($_GET['fecha'] ?? '') . '&mensaje=Registro eliminado correctamente.');
                    } else {
                        redirigir('asistencia.php?fecha=' . ($_GET['fecha'] ?? '') . '&error=Error: Fallo al intentar eliminar el registro.');
                    }
                } else {
                    redirigir('asistencia.php?fecha=' . ($_GET['fecha'] ?? '') . '&error=Error: El registro a eliminar no fue encontrado.');
                }
            }
        }

        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : $this->auth->ultimaFechaAsistencia();
        $nivel = (string) ($_GET['nivel'] ?? '');
        $grado = (int) ($_GET['grado'] ?? 0);
        $seccion = strtoupper(trim((string) ($_GET['seccion'] ?? '')));
        $estado = (string) ($_GET['estado'] ?? '');

        // Validar nivel
        $nivelValido = in_array($nivel, ['Primaria', 'Secundaria'], true) ? $nivel : '';

        // Validar secciÃ³n (mÃ¡s flexible si no hay nivel seleccionado)
        $seccionesValidas = ['A', 'B', 'C', 'D']; // Todas las posibles
        if ($nivelValido === 'Secundaria') {
            $seccionesValidas = ['A', 'B', 'C'];
        }
        $seccionValida = in_array($seccion, $seccionesValidas, true) ? $seccion : '';

        $filtros = [
            'fecha' => $fecha,
            'nivel' => $nivelValido,
            'grado' => $grado > 0 ? $grado : 0,
            'seccion' => $seccionValida,
            'estado' => in_array($estado, ['cargado', 'pendiente', 'parcial'], true) ? $estado : '',
        ];

        $registros = $this->auth->estadoRegistroAsistencia($filtros);
        $kpis = [
            'cargado' => 0, 'pendiente' => 0, 'parcial' => 0
        ];

        foreach ($registros as $registro) {
            if (isset($kpis[$registro['estado']])) {
                $kpis[$registro['estado']]++;
            }
        }

        if (($_GET['export'] ?? '') === 'excel') {
            $this->exportarAsistenciaCSV($registros, $filtros);
        }

        require __DIR__ . '/../views/asistencia_documentos_v.php';
    }

    private function exportarAsistenciaCSV(array $registros, array $filtros): never
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_asistencia_' . $filtros['fecha'] . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        echo '<tr><th colspan="8" style="background:#1e3a5f; color:white; font-size:16px;">I.E. N 14008 "Leonor Cerna de Valdiviezo" - Registro de Asistencia</th></tr>';
        echo '<tr><th colspan="8" style="background:#f1f5f9;">Fecha de consulta: ' . e($filtros['fecha']) . ' | Total registros: ' . count($registros) . '</th></tr>';
        echo '<tr></tr>';
        echo '<tr style="background:#eef3f8;">
                <th style="font-weight:bold;">Docente</th>
                <th style="font-weight:bold;">Correo</th>
                <th style="font-weight:bold;">Nivel</th>
                <th style="font-weight:bold;">Grado</th>
                <th style="font-weight:bold;">Sección</th>
                <th style="font-weight:bold;">Curso</th>
                <th style="font-weight:bold;">Última Carga</th>
                <th style="font-weight:bold;">Estado</th>
              </tr>';
        
        foreach ($registros as $registro) {
            echo '<tr>';
            echo '<td>' . e($registro['docente']) . '</td>';
            echo '<td>' . e($registro['correo']) . '</td>';
            echo '<td>' . e($registro['nivel']) . '</td>';
            echo '<td style="text-align:center;">' . e((string)$registro['grado']) . '</td>';
            echo '<td style="text-align:center;">' . e($registro['seccion']) . '</td>';
            echo '<td>' . e($registro['curso']) . '</td>';
            echo '<td>' . e($registro['fecha_ultima_carga'] ?: 'Sin registro') . '</td>';
            $color = $registro['estado'] === 'cargado' ? '#15803d' : ($registro['estado'] === 'parcial' ? '#b45309' : '#b91c1c');
            echo '<td style="color:' . $color . '; font-weight:bold;">' . e(ucfirst($registro['estado'])) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</body></html>';
        exit;
    }

    private function exportarNotificacionesXLS(array $notificaciones): never
    {
        $fecha = date('Y-m-d');
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="notificaciones_' . $fecha . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"></head><body><table border="1">';
        echo '<tr><th colspan="7" style="background:#1e3a5f;color:white;font-size:16px;">Reporte de Notificaciones - ' . $fecha . '</th></tr>';
        echo '<tr style="background:#eef3f8;"><th>Fecha</th><th>Origen</th><th>Destinatario</th><th>Correo</th><th>Canal</th><th>Estado</th><th>Mensaje</th></tr>';

        foreach ($notificaciones as $n) {
            $estado = strtolower((string) ($n['estado'] ?? ''));
            $color  = $estado === 'enviado' ? '#15803d' : '#b45309';
            echo '<tr>';
            echo '<td>' . e($n['fecha']) . '</td>';
            echo '<td>' . e($n['origen']) . '</td>';
            echo '<td>' . e($n['destinatario']) . '</td>';
            echo '<td>' . e($n['correo']) . '</td>';
            echo '<td>' . e($n['canal']) . '</td>';
            echo '<td style="color:' . $color . ';font-weight:bold;">' . e($n['estado']) . '</td>';
            echo '<td>' . e($n['mensaje']) . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    private function validarCSRF(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');

        if ($token === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
            die('Error de seguridad: Token CSRF invalido.');
        }
    }
}
