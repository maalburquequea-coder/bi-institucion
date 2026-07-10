<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../helpers/funciones.php';

class AuthController
{
    private AuthModel $auth;

    public function __construct()
    {
        $this->auth = new AuthModel(db());
    }

    public function login(): void
    {
        iniciarSesion();
        $error = '';
        $ok = '';

        if (((string) ($_GET['verificado'] ?? '')) === '1') {
            $ok = 'Correo verificado correctamente. Ya puedes iniciar sesion.';
        } elseif (((string) ($_GET['verificado'] ?? '')) === '0') {
            $error = 'El enlace de verificacion no es valido o ya fue usado.';
        } elseif (((string) ($_GET['razon'] ?? '')) === 'inactividad') {
            $error = 'Tu sesion expiro por inactividad. Por favor vuelve a iniciar sesion.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = trim((string) ($_POST['correo'] ?? ''));
            $contrasena = (string) ($_POST['contrasena'] ?? '');

            // Rate limiting: máximo 5 intentos fallidos por IP en 10 minutos
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $intentosFallidos = $this->auth->contarIntentosFallidos($ip, 10);
            if ($intentosFallidos >= 5) {
                $error = 'Demasiados intentos fallidos. Espere 10 minutos antes de intentarlo nuevamente.';
                require __DIR__ . '/../views/login_v.php';
                return;
            }

            $usuario = $this->auth->buscarUsuarioPorCorreo($correo);

            if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
                $this->auth->registrarAcceso($usuario['id_usuario'] ?? null, $correo, false);
                $error = 'Correo o contrasena incorrectos.';
            } elseif (($usuario['estado_cuenta'] ?? 'pendiente') !== 'activo') {
                $this->auth->registrarAcceso((int) $usuario['id_usuario'], $correo, false);
                $error = 'Tu cuenta esta ' . ($usuario['estado_cuenta'] ?? 'pendiente') . '. Pongase en contacto con soporte si cree que es un error.';
            } elseif ((int) ($usuario['correo_verificado'] ?? 1) !== 1) {
                $this->auth->registrarAcceso((int) $usuario['id_usuario'], $correo, false);
                $error = 'Tu cuenta fue aprobada, pero aun debes verificar tu correo. Revisa tu bandeja de entrada o spam.';
            } else {
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['usuario'] = [
                    'id_usuario' => (int) $usuario['id_usuario'],
                    'nombres' => $usuario['nombres'],
                    'apellidos' => $usuario['apellidos'],
                    'correo' => $usuario['correo'],
                    'rol' => $usuario['nombre_rol'],
                ];
                $this->auth->registrarAcceso((int) $usuario['id_usuario'], $correo, true);
                $this->auth->registrarAuditoria((int) $usuario['id_usuario'], 'Acceso', 'Login', 'Ingreso al sistema');
                
                // Se procesa la lÃ³gica antes de la redirecciÃ³n
                $this->procesarPrimerAcceso($usuario);
                $this->redireccionarPorRol($usuario['nombre_rol']);
            }
        }

        require __DIR__ . '/../views/login_v.php';
    }

    private function procesarPrimerAcceso(array $usuario): void
    {
        if ((int) ($usuario['primer_login'] ?? 1) === 1) {
            $mensaje = 'Bienvenido a la plataforma BI Educativo Piura 2026. Desde aqui recibiras alertas y seguimiento academico.';
            $correoEnviado = EmailService::enviar($usuario['correo'], 'Primer acceso a BI Educativo', $mensaje);
            $this->auth->crearNotificacionUsuario((int) $usuario['id_usuario'], 'Primer acceso', $mensaje, $correoEnviado ? 'Enviado' : 'Pendiente SMTP');
            $this->auth->marcarPrimerLogin((int) $usuario['id_usuario']);
        }
    }

    private function redireccionarPorRol(string $rol): void
    {
        match ($rol) {
            'Docente', 'Tutor'         => redirigir('docente.php'),
            'Padre'                    => redirigir('padre.php'),
            'Administrador', 'Director' => redirigir('admin.php'),
            default                    => redirigir('portal.php'),
        };
    }

    public function registro(): void
    {
        iniciarSesion();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $roles = $this->auth->obtenerRolesRegistro();
        $error = '';
        $ok = '';
        $data = []; // Inicializar para evitar errores en la vista (GET)

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validarCSRF(); // Validar CSRF al inicio de la peticiÃ³n POST
            $data = [
                'dni' => preg_replace('/\D/', '', (string) ($_POST['dni'] ?? '')),
                'nombres' => trim((string) ($_POST['nombres'] ?? '')),
                'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
                'correo' => strtolower(trim((string) ($_POST['correo'] ?? ''))),
                'telefono' => preg_replace('/\D/', '', (string) ($_POST['telefono'] ?? '')),
                'contrasena' => (string) ($_POST['contrasena'] ?? ''),
                'id_rol' => (int) ($_POST['id_rol'] ?? 0),
                'dni_estudiante' => preg_replace('/\D/', '', (string) ($_POST['dni_estudiante'] ?? '')),
            ];

            $rolesPermitidos = array_column($roles, 'id_rol');

            if (strlen($data['dni']) !== 8 || $data['nombres'] === '' || $data['apellidos'] === '') {
                $error = 'Completa DNI, nombres y apellidos correctamente.';
            } elseif (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingresa un correo electronico valido.';
            } elseif ($data['telefono'] !== '' && strlen($data['telefono']) < 9) {
                $error = 'Ingresa un numero de WhatsApp valido o deja el campo vacio.';
            } elseif (!contrasenaRobusta($data['contrasena'])) {
                $error = 'La contrasena debe tener minimo 8 caracteres, mayuscula, minuscula, numero y simbolo.';
            } elseif (!in_array($data['id_rol'], $rolesPermitidos, true)) {
                $error = 'Selecciona un tipo de usuario valido.';
            } elseif ($this->auth->buscarUsuarioPorCorreo($data['correo'])) {
                $error = 'Ese correo ya esta registrado.';
            } else {
                try {
                    $idUsuario = $this->auth->registrarUsuario($data);

                    $rolSeleccionado = '';
                    foreach ($roles as $rol) {
                        if ((int) $rol['id_rol'] === $data['id_rol']) {
                            $rolSeleccionado = $rol['nombre_rol'];
                        }
                    }

                    if ($rolSeleccionado === 'Padre' && !empty($data['dni_estudiante'])) {
                        $this->auth->vincularEstudianteDirecto($idUsuario, $data['dni_estudiante']);
                    }

                    // Generar token y guardarlo para verificación
                    $tokenVerificacion = bin2hex(random_bytes(32));
                    $this->auth->guardarTokenVerificacion($idUsuario, $tokenVerificacion);
                    $urlVerificacion = BASE_URL . "verificar_correo.php?token=" . $tokenVerificacion;

                    $mensaje = "Hola {$data['nombres']},\n\n"
                        . "Tu cuenta fue registrada correctamente en BI Educativo Piura 2026.\n"
                        . "Para activar tu acceso, verifica tu correo en el siguiente enlace:\n"
                        . $urlVerificacion . "\n\n"
                        . "Luego podrás iniciar sesión con tu correo: {$data['correo']}.";

                    $correoEnviado = EmailService::enviar($data['correo'], 'Verifica tu cuenta en BI Educativo', $mensaje);
                    $this->auth->crearNotificacionUsuario($idUsuario, 'Registro', $mensaje, $correoEnviado ? 'Enviado' : 'Pendiente SMTP');
                    $ok = $correoEnviado
                        ? 'Cuenta registrada correctamente. Revisa tu bandeja de entrada para verificar tu correo y activar el acceso.'
                        : 'Cuenta registrada. No se pudo enviar el correo de activación (revise configuración SMTP).';
                } catch (Exception $e) {
                    $error = 'No se pudo registrar. Verifique que el DNI o correo no existan.';
                }
            }
        }

        require __DIR__ . '/../views/registro_v.php';
    }

    private function validarCSRF(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');

        if ($token === '' || $tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
            die('Error de seguridad: Token CSRF invalido.');
        }
    }

    public function verificarCorreo(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '') {
            redirigir('login.php?verificado=0');
        }

        try {
            $usuario = $this->auth->buscarUsuarioPorTokenVerificacion($token);

            if (!$usuario) {
                redirigir('login.php?verificado=0');
            }

            $idUsuario = (int) $usuario['id_usuario'];
            if ($this->auth->verificarCorreoUsuario($idUsuario)) {
                $this->auth->registrarAuditoria($idUsuario, 'Cuenta', 'Verificacion correo', 'Correo verificado por enlace');
                redirigir('login.php?verificado=1');
            }
        } catch (Throwable) {
        }

        redirigir('login.php?verificado=0');
    }

    public function logout(): void
    {
        iniciarSesion();
        if (isset($_SESSION['usuario'])) {
            $this->auth->registrarAuditoria((int)$_SESSION['usuario']['id_usuario'], 'Acceso', 'Logout', 'Cierre de sesión manual');
        }
        $_SESSION = [];
        session_destroy();
        redirigir('login.php');
    }
}
