<?php
/**
 * Script para verificar errores de sintaxis en todo el proyecto
 */
$directory = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($directory);
$errorCount = 0;

echo "Revisando archivos PHP...\n";

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getPathname();
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return_var);
        
        if ($return_var !== 0) {
            echo "ERROR ENCONTRADO: " . $output[0] . "\n";
            $errorCount++;
        }
    }
}

echo "\nRevisión finalizada. Errores totales: $errorCount\n";