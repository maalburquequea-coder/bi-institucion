USE bi_educativo_piura;

CREATE TABLE IF NOT EXISTS configuracion_sistema (
    clave VARCHAR(80) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    grupo VARCHAR(40) NOT NULL DEFAULT 'general',
    actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS periodos_academicos (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    modulo VARCHAR(60) NOT NULL,
    accion VARCHAR(80) NOT NULL,
    detalle VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS accesos_sistema (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    correo VARCHAR(100) NOT NULL,
    exito TINYINT(1) NOT NULL DEFAULT 0,
    ip VARCHAR(45) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_accesos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO configuracion_sistema (clave, valor, descripcion, grupo) VALUES
    ('riesgo_alto', '70', 'Puntaje minimo para alerta de riesgo alto', 'umbrales'),
    ('riesgo_medio', '40', 'Puntaje minimo para alerta de riesgo medio', 'umbrales'),
    ('peso_promedio_bajo', '45', 'Peso IA para promedio menor a 11', 'ia'),
    ('peso_curso_desaprobado', '15', 'Peso IA por cada curso desaprobado', 'ia'),
    ('peso_falta', '8', 'Peso IA por cada falta registrada', 'ia'),
    ('peso_tardanza', '4', 'Peso IA por cada tardanza registrada', 'ia')
ON DUPLICATE KEY UPDATE
    valor = VALUES(valor),
    descripcion = VALUES(descripcion),
    grupo = VALUES(grupo);

INSERT INTO periodos_academicos (nombre, fecha_inicio, fecha_fin, activo)
SELECT 'Bimestre I 2026', '2026-03-01', '2026-05-31', 1
WHERE NOT EXISTS (SELECT 1 FROM periodos_academicos WHERE nombre = 'Bimestre I 2026');
