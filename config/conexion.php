<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
    $dotenv->required([
        'DB_HOST', 'DB_NAME', 'DB_USER',
        'SMTP_PASS', 'GEMINI_API_KEY',
        'BASE_URL',
    ]);
}

define('DB_HOST',        $_ENV['DB_HOST']        ?? 'localhost');
define('DB_NAME',        $_ENV['DB_NAME']        ?? 'bi_educativo_piura');
define('DB_USER',        $_ENV['DB_USER']        ?? 'root');
define('DB_PASS',        $_ENV['DB_PASS']        ?? '');
define('APP_NAME',       'BI Educativo Piura 2026');
define('BASE_URL',       $_ENV['BASE_URL']       ?? 'http://localhost:8080/bi_institucion/');
define('MAIL_FROM',      $_ENV['MAIL_FROM']      ?? '');
define('MAIL_FROM_NAME', APP_NAME);
define('SMTP_HOST',      $_ENV['SMTP_HOST']      ?? 'smtp.gmail.com');
define('SMTP_PORT',      (int) ($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER',      $_ENV['SMTP_USER']      ?? '');
define('SMTP_PASS',      $_ENV['SMTP_PASS']      ?? '');
define('SMTP_SECURE',    $_ENV['SMTP_SECURE']    ?? 'tls');
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

function db(): PDO
{
    static $conexion = null;

    if ($conexion === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $conexion = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $conexion;
}

require_once __DIR__ . '/../helpers/funciones.php';
require_once __DIR__ . '/security_headers.php';
