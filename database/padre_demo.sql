USE bi_educativo_piura;

INSERT INTO usuarios (dni, nombres, apellidos, correo, contrasena, id_rol, primer_login, estado_cuenta, fecha_registro)
SELECT '70000010', 'Padre', 'Familia Demo', 'padre.familia@institucion.edu.pe',
       '$2y$10$iyBLbjXSKAI192p/HWvRse7GnXfIywjEf6DjsrY3AxHcxKDgEKRDu',
       r.id_rol, 0, 'activo', NOW()
FROM roles r
WHERE r.nombre_rol = 'Padre'
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    correo = VALUES(correo),
    contrasena = VALUES(contrasena),
    id_rol = VALUES(id_rol),
    primer_login = 0,
    estado_cuenta = 'activo';

INSERT INTO estudiantes (dni, nombres, apellidos, nivel, grado, seccion, id_padre)
VALUES (
    '80000010',
    'Daniela',
    'Familia Rojas',
    'Secundaria',
    3,
    'A',
    (SELECT id_usuario FROM usuarios WHERE correo = 'padre.familia@institucion.edu.pe')
)
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    nivel = VALUES(nivel),
    grado = VALUES(grado),
    seccion = VALUES(seccion),
    id_padre = VALUES(id_padre);

INSERT INTO calificaciones (id_estudiante, id_curso, id_docente, nota_final, periodo)
SELECT e.id_estudiante, cu.id_curso, d.id_usuario, x.nota, x.periodo
FROM (
    SELECT 'Matematica' curso, 10.00 nota, 'Unidad 1' periodo UNION ALL
    SELECT 'Comunicacion', 13.00, 'Unidad 1' UNION ALL
    SELECT 'Ciencia y Tecnologia', 12.00, 'Unidad 1' UNION ALL
    SELECT 'EPT', 9.00, 'Unidad 1' UNION ALL
    SELECT 'Matematica', 11.00, 'Unidad 2' UNION ALL
    SELECT 'Comunicacion', 14.00, 'Unidad 2' UNION ALL
    SELECT 'Ciencia y Tecnologia', 12.00, 'Unidad 2' UNION ALL
    SELECT 'EPT', 10.00, 'Unidad 2'
) x
JOIN estudiantes e ON e.dni = '80000010'
JOIN cursos cu ON cu.nombre_curso = x.curso AND cu.nivel = 'Secundaria'
LEFT JOIN usuarios d ON d.correo = 'profesor.ept@institucion.edu.pe'
WHERE NOT EXISTS (
    SELECT 1
    FROM calificaciones c
    WHERE c.id_estudiante = e.id_estudiante
      AND c.id_curso = cu.id_curso
      AND c.periodo = x.periodo
);

INSERT INTO asistencia (id_estudiante, fecha, estado)
SELECT e.id_estudiante, x.fecha, x.estado
FROM (
    SELECT DATE('2026-05-06') fecha, 'Presente' estado UNION ALL
    SELECT DATE('2026-05-07'), 'Tardanza' UNION ALL
    SELECT DATE('2026-05-08'), 'Falto' UNION ALL
    SELECT DATE('2026-05-13'), 'Presente' UNION ALL
    SELECT DATE('2026-05-14'), 'Presente' UNION ALL
    SELECT DATE('2026-05-15'), 'Falto'
) x
JOIN estudiantes e ON e.dni = '80000010'
WHERE NOT EXISTS (
    SELECT 1 FROM asistencia a WHERE a.id_estudiante = e.id_estudiante AND a.fecha = x.fecha
);

INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado)
SELECT e.id_estudiante, e.id_padre, 'Correo', 'Bajo rendimiento', 'Bajo rendimiento detectado en EPT. Se recomienda revisar el plan de mejora.', 'Pendiente'
FROM estudiantes e
WHERE e.dni = '80000010'
  AND NOT EXISTS (
      SELECT 1 FROM notificaciones n WHERE n.id_estudiante = e.id_estudiante AND n.mensaje LIKE 'Bajo rendimiento detectado%'
  );

INSERT INTO notificaciones (id_estudiante, id_padre, canal, tipo, mensaje, estado)
SELECT e.id_estudiante, e.id_padre, 'Correo', 'Inasistencia', 'Se registraron inasistencias durante el mes actual.', 'Pendiente'
FROM estudiantes e
WHERE e.dni = '80000010'
  AND NOT EXISTS (
      SELECT 1 FROM notificaciones n WHERE n.id_estudiante = e.id_estudiante AND n.mensaje LIKE 'Se registraron inasistencias%'
  );
