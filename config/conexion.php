<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

define('APP_NAME',       'BI Educativo Piura 2026');
define('BASE_URL',       $_ENV['BASE_URL']       ?? 'http://localhost:8080/bi_institucion/');
define('MAIL_FROM',      'alburquequerodriguez6@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'alburquequerodriguez6@gmail.com');
define('SMTP_PASS',      'fubetpvlucafucmb');
define('SMTP_SECURE',    'tls');
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

function db(): PDO
{
    static $conexion = null;

    if ($conexion === null) {
        // Render proporciona DATABASE_URL para PostgreSQL
        if (!empty($_ENV['DATABASE_URL'])) {
            $url  = parse_url($_ENV['DATABASE_URL']);
            $host = $url['host'];
            $port = $url['port'] ?? 5432;
            $name = ltrim($url['path'], '/');
            $user = $url['user'];
            $pass = $url['pass'];
        } else {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? 5432;
            $name = $_ENV['DB_NAME'] ?? 'bi_educativo_piura';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASS'] ?? '';
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        $conexion = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $conexion;
}

require_once __DIR__ . '/../helpers/funciones.php';
require_once __DIR__ . '/../config/security_headers.php';
