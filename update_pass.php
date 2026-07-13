<?php
if (($_GET['token'] ?? '') !== 'reset14008') { http_response_code(403); exit('Acceso denegado'); }
require_once __DIR__ . '/config/conexion.php';
$pdo = db();

// Buscar correo específico con detalle completo
$buscar = $pdo->prepare("
    SELECT u.id_usuario, u.nombres, u.apellidos, u.dni, u.correo, u.telefono,
           u.correo_verificado, u.estado_cuenta, r.nombre_rol,
           sv.fecha_solicitud, sv.estado AS estado_vinculacion,
           e.nombres AS nombre_estudiante, e.apellidos AS apellido_estudiante
    FROM usuarios u
    JOIN roles r ON r.id_rol = u.id_rol
    LEFT JOIN solicitudes_vinculacion sv ON sv.id_padre = u.id_usuario
    LEFT JOIN estudiantes e ON e.id_estudiante = sv.id_estudiante
    WHERE u.correo = 'esperanzarodriguezruiz671@gmail.com'
");
$buscar->execute();
$rows = $buscar->fetchAll(PDO::FETCH_ASSOC);
if ($rows) {
    foreach ($rows as $r) { echo "DETALLE: " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n"; }
} else {
    echo "NO REGISTRADO: esperanzarodriguezruiz671@gmail.com\n";
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
