<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();

// Activar cuenta de Jesus Otero (esperanzarodriguezruiz671@gmail.com)
$activa = $pdo->prepare("UPDATE usuarios SET correo_verificado = 1, estado_cuenta = 'activo' WHERE correo = 'esperanzarodriguezruiz671@gmail.com'");
$activa->execute();
echo "Jesus Otero activado. Filas: " . $activa->rowCount() . "\n";

// Test SMTP
require_once __DIR__ . '/services/EmailService.php';
$smtp_ok = EmailService::enviar('alburquequequeayana24@gmail.com', 'Test SMTP BI Educativo', "SMTP funcionando OK desde Render.");
echo $smtp_ok ? "SMTP OK: correo de prueba enviado a alburquequequeayana24@gmail.com\n" : "SMTP ERROR: revisa SMTP_USER en Render.\n";

// Actualizar contraseña del admin
$hash = '$2y$10$ulDQTm4a33Vf2Zc5INKorO0ghaZHSmf7nG6Z44qOIIkj4ka/wGsk6';
$stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = 'admin@demo.com'");
$stmt->execute([$hash]);
echo 'Admin contrasena actualizada. Filas: ' . $stmt->rowCount() . "\n";

// Eliminar mariarodriguez96387@gmail.com
$u = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = 'mariarodriguez96387@gmail.com'");
$u->execute();
$uid = $u->fetchColumn();
if ($uid) {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM calificaciones WHERE id_docente = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM documentos_asistencia WHERE id_docente = ?")->execute([$uid]);
    $pdo->prepare("UPDATE estudiantes SET id_padre = NULL WHERE id_padre = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM solicitudes_vinculacion WHERE id_padre = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM notificaciones WHERE id_padre = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM notificaciones_usuario WHERE id_usuario = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM auditoria WHERE id_usuario = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM accesos_sistema WHERE id_usuario = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$uid]);
    $pdo->commit();
    echo "mariarodriguez96387 eliminado OK.\n";
} else {
    echo "mariarodriguez96387 no encontrado.\n";
}

// Verificar correo + resetear contraseña de Santos
$hashSantos = '$2y$10$IXSVJGOCbD6wR5gBytNIjOCWb3I3MPpYg6iGy3z2ModGNH7pJlK6e';
$stmt2 = $pdo->prepare("UPDATE usuarios SET correo_verificado = 1, estado_cuenta = 'activo', contrasena = ? WHERE correo = 'perarodriguez742@gmail.com'");
$stmt2->execute([$hashSantos]);
echo 'Santos verificado y contrasena actualizada. Filas: ' . $stmt2->rowCount() . "\n";
