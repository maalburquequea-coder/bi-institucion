USE bi_educativo_piura;

CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_padre INT NOT NULL,
    canal ENUM('Sistema','Correo','WhatsApp','Interno') NOT NULL DEFAULT 'Sistema',
    mensaje VARCHAR(255) NOT NULL,
    estado ENUM('Pendiente','Enviado','Fallido','Leido') NOT NULL DEFAULT 'Pendiente',
    fecha_envio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo VARCHAR(50) NOT NULL DEFAULT 'Alerta temprana',
    CONSTRAINT fk_notificaciones_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante),
    CONSTRAINT fk_notificaciones_padre FOREIGN KEY (id_padre) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO roles (id_rol, nombre_rol) VALUES
    (1, 'Administrador'),
    (2, 'Director'),
    (3, 'Docente'),
    (4, 'Tutor'),
    (5, 'Padre')
ON DUPLICATE KEY UPDATE nombre_rol = VALUES(nombre_rol);

INSERT INTO usuarios (dni, nombres, apellidos, correo, contrasena, id_rol) VALUES
    ('70000001', 'Rosa', 'Vargas Sanchez', 'rosa.vargas@example.com', '$2y$10$demo', 5),
    ('70000002', 'Carlos', 'Mendoza Ruiz', 'carlos.mendoza@example.com', '$2y$10$demo', 5),
    ('70000003', 'Elena', 'Chavez Torres', 'elena.chavez@example.com', '$2y$10$demo', 5),
    ('41000001', 'Mariana', 'Lopez Farfan', 'mariana.lopez@example.com', '$2y$10$demo', 3),
    ('41000002', 'Jorge', 'Soto Rivas', 'jorge.soto@example.com', '$2y$10$demo', 4)
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    correo = VALUES(correo),
    id_rol = VALUES(id_rol);

INSERT INTO cursos (id_curso, nombre_curso, nivel) VALUES
    (1, 'Matematica', 'Secundaria'),
    (2, 'Comunicacion', 'Secundaria'),
    (3, 'Ciencia y Tecnologia', 'Secundaria'),
    (4, 'Ciencias Sociales', 'Secundaria'),
    (5, 'EPT', 'Secundaria'),
    (6, 'Religion', 'Secundaria'),
    (7, 'Educacion Fisica', 'Secundaria'),
    (8, 'Ingles', 'Secundaria')
ON DUPLICATE KEY UPDATE
    nombre_curso = VALUES(nombre_curso),
    nivel = VALUES(nivel);

INSERT INTO estudiantes (dni, nombres, apellidos, nivel, grado, seccion, id_padre) VALUES
    ('80000001', 'Luis', 'Vargas Perez', 'Secundaria', 3, 'A', (SELECT id_usuario FROM usuarios WHERE dni = '70000001')),
    ('80000002', 'Ana', 'Mendoza Flores', 'Secundaria', 3, 'A', (SELECT id_usuario FROM usuarios WHERE dni = '70000002')),
    ('80000003', 'Mateo', 'Chavez Leon', 'Secundaria', 4, 'B', (SELECT id_usuario FROM usuarios WHERE dni = '70000003')),
    ('80000004', 'Camila', 'Vargas Perez', 'Secundaria', 2, 'C', (SELECT id_usuario FROM usuarios WHERE dni = '70000001'))
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    nivel = VALUES(nivel),
    grado = VALUES(grado),
    seccion = VALUES(seccion),
    id_padre = VALUES(id_padre);

INSERT INTO calificaciones (id_estudiante, id_curso, id_docente, nota_final, periodo)
SELECT e.id_estudiante, c.id_curso, d.id_usuario, x.nota, 'Bimestre I'
FROM (
    SELECT '80000001' dni, 1 curso, 9.50 nota UNION ALL
    SELECT '80000001', 2, 10.00 UNION ALL
    SELECT '80000001', 3, 12.00 UNION ALL
    SELECT '80000002', 1, 14.00 UNION ALL
    SELECT '80000002', 2, 13.50 UNION ALL
    SELECT '80000003', 1, 8.00 UNION ALL
    SELECT '80000003', 3, 9.00 UNION ALL
    SELECT '80000003', 4, 10.50 UNION ALL
    SELECT '80000004', 1, 16.00 UNION ALL
    SELECT '80000004', 2, 15.00
) x
JOIN estudiantes e ON e.dni = x.dni
JOIN cursos c ON c.id_curso = x.curso
JOIN usuarios d ON d.dni = '41000001'
WHERE NOT EXISTS (
    SELECT 1
    FROM calificaciones cc
    WHERE cc.id_estudiante = e.id_estudiante
      AND cc.id_curso = c.id_curso
      AND cc.periodo = 'Bimestre I'
);

INSERT INTO asistencia (id_estudiante, fecha, estado)
SELECT e.id_estudiante, x.fecha, x.estado
FROM (
    SELECT '80000001' dni, DATE('2026-04-03') fecha, 'Falto' estado UNION ALL
    SELECT '80000001', DATE('2026-04-10'), 'Tardanza' UNION ALL
    SELECT '80000003', DATE('2026-04-02'), 'Falto' UNION ALL
    SELECT '80000003', DATE('2026-04-09'), 'Falto' UNION ALL
    SELECT '80000003', DATE('2026-04-16'), 'Tardanza' UNION ALL
    SELECT '80000002', DATE('2026-04-04'), 'Presente' UNION ALL
    SELECT '80000004', DATE('2026-04-04'), 'Presente'
) x
JOIN estudiantes e ON e.dni = x.dni
WHERE NOT EXISTS (
    SELECT 1
    FROM asistencia a
    WHERE a.id_estudiante = e.id_estudiante
      AND a.fecha = x.fecha
      AND a.estado = x.estado
);

INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado)
SELECT e.id_estudiante, e.id_padre, 'Correo', 'Alerta temprana',
       CONCAT('Alerta temprana: ', e.nombres, ' presenta factores de riesgo academico y requiere seguimiento.'),
       'Enviado'
FROM estudiantes e
WHERE e.dni IN ('80000001', '80000003')
  AND NOT EXISTS (
      SELECT 1
      FROM notificaciones n
      WHERE n.id_estudiante = e.id_estudiante
        AND n.id_padre = e.id_padre
        AND n.mensaje LIKE 'Alerta temprana:%'
  );
