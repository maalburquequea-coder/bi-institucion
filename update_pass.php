<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();

// Activar cuenta de Jesus Otero (esperanzarodriguezruiz671@gmail.com)
$activa = $pdo->prepare("UPDATE usuarios SET correo_verificado = 1, estado_cuenta = 'activo' WHERE correo = 'esperanzarodriguezruiz671@gmail.com'");
$activa->execute();
echo "Jesus Otero activado. Filas: " . $activa->rowCount() . "\n";

// Diagnóstico SMTP
echo "SMTP_HOST: " . (SMTP_HOST ?: '(vacio)') . "\n";
echo "SMTP_PORT: " . SMTP_PORT . "\n";
echo "SMTP_SECURE: " . (SMTP_SECURE ?: '(vacio)') . "\n";
echo "SMTP_USER: " . (SMTP_USER ?: '(vacio)') . "\n";
echo "SMTP_PASS: " . (SMTP_PASS ? '***(' . strlen(SMTP_PASS) . ' chars)' : '(vacio)') . "\n";
echo "MAIL_FROM: " . (MAIL_FROM ?: '(vacio)') . "\n";

// Test SMTP directo con PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/vendor/autoload.php';
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->Timeout    = 15;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress('alburquequequeayana24@gmail.com');
    $mail->Subject = 'Test SMTP';
    $mail->Body    = 'Prueba de correo desde Render.';
    $mail->send();
    echo "SMTP OK: correo enviado.\n";
} catch (Exception $e) {
    echo "SMTP ERROR DETALLE: " . $mail->ErrorInfo . "\n";
}

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
