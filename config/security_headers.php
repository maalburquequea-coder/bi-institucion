<?php
declare(strict_types=1);

/**
 * Cabeceras de seguridad HTTP.
 * Se incluye al inicio de cada punto de entrada (login.php, docente.php, etc.)
 * a través de conexion.php o directamente en cada controlador.
 */
if (!headers_sent()) {
    // Evita que el sitio sea embebido en iframes (clickjacking)
    header('X-Frame-Options: SAMEORIGIN');

    // Evita que el navegador detecte el tipo MIME incorrecto
    header('X-Content-Type-Options: nosniff');

    // Activa el filtro XSS del navegador (IE/Chrome legacy)
    header('X-XSS-Protection: 1; mode=block');

    // Referrer solo al mismo origen
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Política de permisos: deshabilita APIs sensibles no usadas
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy: solo recursos propios + CDNs usadas
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
        "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'self';"
    );
}
