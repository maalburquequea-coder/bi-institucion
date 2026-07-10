USE bi_educativo_piura;

CREATE TABLE IF NOT EXISTS documentos_asistencia (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_docente INT NOT NULL,
    nivel ENUM('Primaria','Secundaria') NOT NULL,
    grado INT NOT NULL,
    seccion VARCHAR(1) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    archivo VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documentos_asistencia_docente
        FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO documentos_asistencia (id_docente, nivel, grado, seccion, titulo, descripcion, archivo)
SELECT u.id_usuario, x.nivel, x.grado, x.seccion, x.titulo, x.descripcion, x.archivo
FROM (
    SELECT '41000001' dni_docente, 'Primaria' nivel, 1 grado, 'A' seccion, 'Registro de asistencia - 1ro Primaria A' titulo,
           'Documento mensual de asistencia y tardanzas.' descripcion, 'uploads/asistencia/primaria_1a_asistencia.txt' archivo UNION ALL
    SELECT '41000001', 'Primaria', 3, 'B', 'Registro de asistencia - 3ro Primaria B',
           'Consolidado de asistencia por estudiante.', 'uploads/asistencia/primaria_3b_asistencia.txt' UNION ALL
    SELECT '41000002', 'Secundaria', 1, 'A', 'Registro de asistencia - 1ro Secundaria A',
           'Reporte de faltas y justificaciones.', 'uploads/asistencia/secundaria_1a_asistencia.txt' UNION ALL
    SELECT '41000002', 'Secundaria', 5, 'C', 'Registro de asistencia - 5to Secundaria C',
           'Seguimiento de asistencia para estudiantes en riesgo.', 'uploads/asistencia/secundaria_5c_asistencia.txt'
) x
JOIN usuarios u ON u.dni = x.dni_docente
WHERE NOT EXISTS (
    SELECT 1
    FROM documentos_asistencia d
    WHERE d.id_docente = u.id_usuario
      AND d.nivel = x.nivel
      AND d.grado = x.grado
      AND d.seccion = x.seccion
      AND d.titulo = x.titulo
);
