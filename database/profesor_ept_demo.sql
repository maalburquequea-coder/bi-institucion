USE bi_educativo_piura;

INSERT INTO usuarios (dni, nombres, apellidos, correo, contrasena, id_rol, primer_login, estado_cuenta, fecha_registro)
SELECT '41000010', 'Profesor', 'EPT Demo', 'profesor.ept@institucion.edu.pe',
       '$2y$10$f0MUh0sXbvqvEIV9LUy/U.Qh7LzJfXdh2K8N33yk9yQUHCsP2soUK',
       r.id_rol, 0, 'activo', NOW()
FROM roles r
WHERE r.nombre_rol = 'Docente'
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    correo = VALUES(correo),
    contrasena = VALUES(contrasena),
    id_rol = VALUES(id_rol),
    primer_login = 0,
    estado_cuenta = 'activo';

INSERT INTO cursos (nombre_curso, nivel)
SELECT 'EPT', 'Secundaria'
WHERE NOT EXISTS (
    SELECT 1 FROM cursos WHERE nombre_curso = 'EPT' AND nivel = 'Secundaria'
);

INSERT INTO calificaciones (id_estudiante, id_curso, id_docente, nota_final, periodo)
SELECT e.id_estudiante, cu.id_curso, d.id_usuario, x.nota, x.periodo
FROM (
    SELECT '80000004' dni, 15.00 nota, 'Unidad 1' periodo UNION ALL
    SELECT '80000004', 14.00, 'Unidad 2' UNION ALL
    SELECT '80000001', 10.00, 'Unidad 1' UNION ALL
    SELECT '80000001', 11.00, 'Unidad 2' UNION ALL
    SELECT '80000002', 13.00, 'Unidad 1' UNION ALL
    SELECT '80000002', 12.00, 'Unidad 2' UNION ALL
    SELECT '80000003', 9.00, 'Unidad 1' UNION ALL
    SELECT '80000003', 10.00, 'Unidad 2'
) x
JOIN estudiantes e ON e.dni = x.dni
JOIN cursos cu ON cu.nombre_curso = 'EPT' AND cu.nivel = 'Secundaria'
JOIN usuarios d ON d.correo = 'profesor.ept@institucion.edu.pe'
WHERE NOT EXISTS (
    SELECT 1
    FROM calificaciones c
    WHERE c.id_estudiante = e.id_estudiante
      AND c.id_curso = cu.id_curso
      AND c.id_docente = d.id_usuario
      AND c.periodo = x.periodo
);
