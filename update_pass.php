<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();
$hash = '$2y$10$ulDQTm4a33Vf2Zc5INKorO0ghaZHSmf7nG6Z44qOIIkj4ka/wGsk6';
$stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = 'admin@demo.com'");
$stmt->execute([$hash]);
echo 'Contrasena actualizada. Filas: ' . $stmt->rowCount();
