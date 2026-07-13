<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Funciones de presentación / escape
// ---------------------------------------------------------------------------

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function imagenAsset(string $nombre, array $extensiones = ['jpg', 'jpeg', 'png', 'svg']): string
{
    foreach ($extensiones as $extension) {
        $archivo = 'assets/img/' . $nombre . '.' . $extension;
        if (is_file(__DIR__ . '/../' . $archivo)) {
            return BASE_URL . $archivo;
        }
    }

    return BASE_URL . 'assets/img/' . $nombre . '.jpg';
}

// ---------------------------------------------------------------------------
// Funciones de negocio — cálculo de riesgo académico
// ---------------------------------------------------------------------------

function riesgoEtiqueta(float $puntaje): string
{
    if ($puntaje >= 70) {
        return 'Alto';
    }

    if ($puntaje >= 40) {
        return 'Medio';
    }

    return 'Bajo';
}

function riesgoClase(string $riesgo): string
{
    return match ($riesgo) {
        'Alto'  => 'risk-high',
        'Medio' => 'risk-medium',
        default => 'risk-low',
    };
}

// ---------------------------------------------------------------------------
// Funciones de contacto — WhatsApp
// ---------------------------------------------------------------------------

function normalizarTelefonoWhatsApp(?string $telefono): string
{
    $digits = preg_replace('/\D/', '', (string) $telefono);
    if ($digits === '') {
        return '';
    }

    if (strlen($digits) === 9) {
        return '51' . $digits;
    }

    return $digits;
}

function whatsappUrl(?string $telefono, string $mensaje): string
{
    $numero = normalizarTelefonoWhatsApp($telefono);
    if ($numero === '') {
        return '';
    }

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensaje);
}

// ---------------------------------------------------------------------------
// Funciones de sesión y control de acceso
// ---------------------------------------------------------------------------

function iniciarSesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // /tmp siempre es escribible en Docker/Render
        $savePath = sys_get_temp_dir();
        session_save_path($savePath);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('bi_sess');
        session_start();
    }
}

function redirigir(string $ruta): never
{
    header('Location: ' . BASE_URL . $ruta);
    exit;
}

function usuarioActual(): ?array
{
    iniciarSesion();
    return $_SESSION['usuario'] ?? null;
}

function requiereLogin(): array
{
    $usuario = usuarioActual();
    if (!$usuario) {
        redirigir('login.php');
    }

    $limite = 30 * 60;
    if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > $limite) {
        session_unset();
        session_destroy();
        redirigir('login.php?razon=inactividad');
    }
    $_SESSION['ultimo_acceso'] = time();

    return $usuario;
}

// ---------------------------------------------------------------------------
// Funciones de validación — seguridad de contraseñas
// ---------------------------------------------------------------------------

function contrasenaRobusta(string $pass): bool
{
    return strlen($pass) >= 8
        && preg_match('/[A-Z]/', $pass) === 1
        && preg_match('/[a-z]/', $pass) === 1
        && preg_match('/[0-9]/', $pass) === 1
        && preg_match('/[^A-Za-z0-9]/', $pass) === 1;
}
