USE bi_educativo_piura;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'primer_login') = 0,
    'ALTER TABLE usuarios ADD COLUMN primer_login TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estudiantes' AND COLUMN_NAME = 'codigo_estudiante') = 0,
    'ALTER TABLE estudiantes ADD COLUMN codigo_estudiante VARCHAR(20) NULL AFTER dni',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE estudiantes MODIFY dni VARCHAR(8) NULL;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estudiantes' AND INDEX_NAME = 'uq_estudiantes_codigo') = 0,
    'ALTER TABLE estudiantes ADD UNIQUE KEY uq_estudiantes_codigo (codigo_estudiante)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'estado_cuenta') = 0,
    'ALTER TABLE usuarios ADD COLUMN estado_cuenta ENUM(''pendiente'',''activo'',''rechazado'') NOT NULL DEFAULT ''activo''',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'fecha_registro') = 0,
    'ALTER TABLE usuarios ADD COLUMN fecha_registro TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'token_verificacion') = 0,
    'ALTER TABLE usuarios ADD COLUMN token_verificacion VARCHAR(64) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'correo_verificado') = 0,
    'ALTER TABLE usuarios ADD COLUMN correo_verificado TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
CREATE TABLE IF NOT EXISTS solicitudes_vinculacion (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_padre INT NOT NULL,
    dni_estudiante VARCHAR(8) NOT NULL,
    estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_padre
        FOREIGN KEY (id_padre) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS notificaciones_usuario (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    canal VARCHAR(30) NOT NULL DEFAULT 'Sistema/Correo',
    mensaje VARCHAR(255) NOT NULL,
    estado_correo VARCHAR(30) NOT NULL DEFAULT 'Pendiente SMTP',
    leido TINYINT(1) NOT NULL DEFAULT 0,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificaciones_usuario_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS planes_mejora_ia (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    promedio_referencia DECIMAL(5,2) NOT NULL DEFAULT 0,
    asistencia_referencia INT NOT NULL DEFAULT 0,
    area_critica VARCHAR(120) NOT NULL,
    acciones_json TEXT NOT NULL,
    fecha_generacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plan_estudiante (id_estudiante),
    CONSTRAINT fk_planes_mejora_estudiante
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE usuarios
SET contrasena = '$2y$10$LX0LALBv4tqgNaTessDtSOET/3/QZxQsAFYVhevLTwdVxM73hokXm',
    primer_login = 1,
    estado_cuenta = 'activo'
WHERE contrasena = '$2y$10$demo';

INSERT INTO usuarios (dni, nombres, apellidos, correo, contrasena, id_rol, primer_login, estado_cuenta)
SELECT '40000000', 'Administrador', 'General', 'admin@demo.com',
       '$2y$10$LX0LALBv4tqgNaTessDtSOET/3/QZxQsAFYVhevLTwdVxM73hokXm',
       r.id_rol, 0, 'activo'
FROM roles r
WHERE r.nombre_rol = 'Administrador'
  AND NOT EXISTS (SELECT 1 FROM usuarios WHERE correo = 'admin@demo.com');

