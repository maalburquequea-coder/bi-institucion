<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();

// Actualizar contraseña del admin
$hash = '$2y$10$ulDQTm4a33Vf2Zc5INKorO0ghaZHSmf7nG6Z44qOIIkj4ka/wGsk6';
$stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = 'admin@demo.com'");
$stmt->execute([$hash]);
echo 'Admin contrasena actualizada. Filas: ' . $stmt->rowCount() . "\n";

// Verificar correo + resetear contraseña de Santos
$hashSantos = '$2y$10$IXSVJGOCbD6wR5gBytNIjOCWb3I3MPpYg6iGy3z2ModGNH7pJlK6e';
$stmt2 = $pdo->prepare("UPDATE usuarios SET correo_verificado = 1, estado_cuenta = 'activo', contrasena = ? WHERE correo = 'perarodriguez742@gmail.com'");
$stmt2->execute([$hashSantos]);
echo 'Santos verificado y contrasena actualizada. Filas: ' . $stmt2->rowCount() . "\n";
