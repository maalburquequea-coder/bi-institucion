<?php
// Script de un solo uso — importa schema y datos a PostgreSQL
// Eliminar este archivo después de ejecutarlo

$token = $_GET['token'] ?? '';
if ($token !== 'setup2026bi') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/config/conexion.php';

$pdo = db();

// Verificar si ya existe
$existe = $pdo->query("SELECT to_regclass('public.roles')")->fetchColumn();
if ($existe) {
    exit('La base de datos ya tiene el schema. Tablas existentes.');
}

$schema = file_get_contents(__DIR__ . '/database/schema_pg.sql');
$data   = file_get_contents(__DIR__ . '/database/data_pg.sql');

try {
    echo "<pre>";
    echo "Importando schema...\n";
    $pdo->exec($schema);
    echo "Schema OK.\n";

    echo "Importando datos...\n";
    $pdo->exec($data);
    echo "Datos OK.\n";

    echo "\n✅ Base de datos lista. Ahora ELIMINA este archivo (setup_db.php).\n";
    echo "</pre>";
} catch (PDOException $e) {
    echo "<pre>ERROR: " . $e->getMessage() . "</pre>";
}
