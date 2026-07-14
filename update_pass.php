<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();

// Insertar notas EPT Jesus Aymar (id_estudiante=6, id_curso=5, id_docente=9)
$chkEpt = $pdo->prepare("SELECT COUNT(*) FROM calificaciones WHERE id_estudiante=6 AND id_curso=5 AND id_docente=9");
$chkEpt->execute();
if ((int)$chkEpt->fetchColumn() === 0) {
    $pdo->prepare("INSERT INTO calificaciones (id_estudiante,id_curso,id_docente,nota_final,periodo,fecha_registro) VALUES (6,5,9,11.00,'Unidad 1',NOW())")->execute();
    $pdo->prepare("INSERT INTO calificaciones (id_estudiante,id_curso,id_docente,nota_final,periodo,fecha_registro) VALUES (6,5,9,12.00,'Unidad 2',NOW())")->execute();
    echo "Notas EPT insertadas OK: Unidad1=11, Unidad2=12 → promedio=11.5 (alerta Media).\n";
} else {
    $cal = $pdo->query("SELECT nota_final, periodo FROM calificaciones WHERE id_estudiante=6 AND id_curso=5 AND id_docente=9")->fetchAll(PDO::FETCH_ASSOC);
    $prom = array_sum(array_column($cal,'nota_final')) / count($cal);
    echo "Notas EPT ya existen. Promedio actual: " . round($prom,2) . "\n";
    foreach ($cal as $r) { echo "  " . $r['periodo'] . " = " . $r['nota_final'] . "\n"; }
}

// Insertar falta de asistencia EPT para Jesus Aymar
$chkAs = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE id_estudiante=6 AND fecha='2026-05-25'");
$chkAs->execute();
if ((int)$chkAs->fetchColumn() === 0) {
    $pdo->prepare("INSERT INTO asistencia (id_estudiante,fecha,estado) VALUES (6,'2026-05-25','Falto')")->execute();
    echo "Asistencia EPT insertada: 2026-05-25 Falto.\n";
} else {
    echo "Asistencia 2026-05-25 ya existe.\n";
}

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
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
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

// === TEST IA GEMINI ===
echo "\n--- DIAGNÓSTICO IA GEMINI ---\n";
$apiKey = GEMINI_API_KEY;
echo "GEMINI_API_KEY: " . ($apiKey ? '***(' . strlen($apiKey) . ' chars)' : '(VACÍO - IA no funcionará)') . "\n";
echo "curl disponible: " . (function_exists('curl_init') ? 'SI' : 'NO') . "\n";

if ($apiKey) {
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
    $payload = json_encode(['contents' => [['parts' => [['text' => 'Di solo: OK']]]]]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    echo "HTTP: $httpCode\n";
    if ($curlErr) echo "cURL error: $curlErr\n";
    if ($httpCode === 200) {
        $r = json_decode($result, true);
        $texto = $r['candidates'][0]['content']['parts'][0]['text'] ?? '(sin texto)';
        echo "IA respuesta: " . trim($texto) . "\n";
        echo "RESULTADO: IA FUNCIONANDO OK\n";
    } else {
        $err = json_decode($result, true);
        echo "Error API: " . ($err['error']['message'] ?? $result) . "\n";
        echo "RESULTADO: IA NO FUNCIONA\n";
    }
} else {
    echo "RESULTADO: Configura GEMINI_API_KEY en las variables de entorno de Render.\n";
}
