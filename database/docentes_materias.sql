USE bi_educativo_piura;

INSERT INTO cursos (nombre_curso, nivel)
SELECT 'Arte y Cultura', 'Secundaria'
WHERE NOT EXISTS (
    SELECT 1 FROM cursos WHERE nombre_curso = 'Arte y Cultura' AND nivel = 'Secundaria'
);

INSERT INTO cursos (nombre_curso, nivel)
SELECT 'Desarrollo Personal, Ciudadania y Civica', 'Secundaria'
WHERE NOT EXISTS (
    SELECT 1 FROM cursos WHERE nombre_curso = 'Desarrollo Personal, Ciudadania y Civica' AND nivel = 'Secundaria'
);

SET @docente := (SELECT id_rol FROM roles WHERE nombre_rol = 'Docente' LIMIT 1);

INSERT INTO usuarios (dni, nombres, apellidos, correo, contrasena, id_rol, primer_login, estado_cuenta, fecha_registro, correo_verificado)
VALUES
    ('41000011', 'Profesor', 'Arte y Cultura', 'profesor.arte@institucion.edu.pe', '$2y$10$rOxlCjL4kAovyw0mgisJZOzXXm96orbhfIizlvLdhF5/AQeAeHeJu', @docente, 1, 'activo', NOW(), 1),
    ('41000012', 'Profesor', 'Ciencia y Tecnologia', 'profesor.ciencia@institucion.edu.pe', '$2y$10$dlVegxip9mVz3zcgozDVkOY41bYH6EC5N9uE3uoz3zR2eDcPyb2Cq', @docente, 1, 'activo', NOW(), 1),
    ('41000013', 'Profesor', 'DPCC', 'profesor.dpcc@institucion.edu.pe', '$2y$10$zkM.yqyYWHC4qURK.TbYpex.6YBCn3ubIq0SOElUoEmXchvc.wuu6', @docente, 1, 'activo', NOW(), 1),
    ('41000014', 'Profesor', 'Ciencias Sociales', 'profesor.sociales@institucion.edu.pe', '$2y$10$KuMWyqk/2o0lJNgelFKoVOrW9Phi03B7H0On2dRdYKTsEFSjDr7Ma', @docente, 1, 'activo', NOW(), 1),
    ('41000015', 'Profesor', 'Comunicacion', 'profesor.comunicacion@institucion.edu.pe', '$2y$10$GLQVHVdkGPmilnumjFz/eOdI3irKpjMVbrfgZLlPM6LmEUbljCEDK', @docente, 1, 'activo', NOW(), 1),
    ('41000016', 'Profesor', 'Educacion Fisica', 'profesor.efisica@institucion.edu.pe', '$2y$10$eoAOIztpkoT9FyPaOLIYW.DVjnZT7hSLZFf8E2OJweqA3M7rc4Gye', @docente, 1, 'activo', NOW(), 1),
    ('41000017', 'Profesor', 'Educacion Religiosa', 'profesor.religion@institucion.edu.pe', '$2y$10$InLiggH5FDOGLdRk1CEmUeK6nzKuonf/vD6waoJ9tCDfGt2jTo2/.', @docente, 1, 'activo', NOW(), 1),
    ('41000018', 'Profesor', 'Ingles', 'profesor.ingles@institucion.edu.pe', '$2y$10$C3HuKMUyLSbWh9kPKOIz/eflYAW2SFof9wJFfDXaledJExR6lmFvC', @docente, 1, 'activo', NOW(), 1),
    ('41000019', 'Profesor', 'Matematica', 'profesor.matematica@institucion.edu.pe', '$2y$10$VbxjlOumJOB4HLa9u8SPme5aqc44C6Tuq1JkBd0X4sH.B1VBskKPe', @docente, 1, 'activo', NOW(), 1)
ON DUPLICATE KEY UPDATE
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    correo = VALUES(correo),
    contrasena = VALUES(contrasena),
    id_rol = VALUES(id_rol),
    primer_login = VALUES(primer_login),
    estado_cuenta = VALUES(estado_cuenta),
    correo_verificado = VALUES(correo_verificado);

UPDATE calificaciones c
JOIN cursos cu ON cu.id_curso = c.id_curso
JOIN usuarios nuevo_docente ON nuevo_docente.correo = CASE cu.nombre_curso
    WHEN 'Arte y Cultura' THEN 'profesor.arte@institucion.edu.pe'
    WHEN 'Ciencia y Tecnologia' THEN 'profesor.ciencia@institucion.edu.pe'
    WHEN 'Desarrollo Personal, Ciudadania y Civica' THEN 'profesor.dpcc@institucion.edu.pe'
    WHEN 'Ciencias Sociales' THEN 'profesor.sociales@institucion.edu.pe'
    WHEN 'Comunicacion' THEN 'profesor.comunicacion@institucion.edu.pe'
    WHEN 'Educacion Fisica' THEN 'profesor.efisica@institucion.edu.pe'
    WHEN 'Religion' THEN 'profesor.religion@institucion.edu.pe'
    WHEN 'Ingles' THEN 'profesor.ingles@institucion.edu.pe'
    WHEN 'Matematica' THEN 'profesor.matematica@institucion.edu.pe'
END
JOIN usuarios ept ON ept.correo = 'profesor.ept@institucion.edu.pe'
SET c.id_docente = nuevo_docente.id_usuario
WHERE c.id_docente = ept.id_usuario
  AND cu.nombre_curso <> 'EPT';

INSERT INTO calificaciones (id_estudiante, id_curso, id_docente, nota_final, periodo)
SELECT
    e.id_estudiante,
    cu.id_curso,
    u.id_usuario,
    LEAST(20, GREATEST(5,
        CASE
            WHEN MOD(e.id_estudiante + cu.id_curso, 11) = 0 THEN 9 + p.ajuste
            ELSE 10 + MOD(e.id_estudiante + cu.id_curso + p.ajuste, 8)
        END
    )) AS nota_final,
    p.periodo
FROM estudiantes e
JOIN (
    SELECT 'profesor.arte@institucion.edu.pe' AS correo, 'Arte y Cultura' AS curso
    UNION ALL SELECT 'profesor.ciencia@institucion.edu.pe', 'Ciencia y Tecnologia'
    UNION ALL SELECT 'profesor.dpcc@institucion.edu.pe', 'Desarrollo Personal, Ciudadania y Civica'
    UNION ALL SELECT 'profesor.sociales@institucion.edu.pe', 'Ciencias Sociales'
    UNION ALL SELECT 'profesor.comunicacion@institucion.edu.pe', 'Comunicacion'
    UNION ALL SELECT 'profesor.efisica@institucion.edu.pe', 'Educacion Fisica'
    UNION ALL SELECT 'profesor.religion@institucion.edu.pe', 'Religion'
    UNION ALL SELECT 'profesor.ingles@institucion.edu.pe', 'Ingles'
    UNION ALL SELECT 'profesor.matematica@institucion.edu.pe', 'Matematica'
) asignacion
JOIN usuarios u ON u.correo = asignacion.correo
JOIN cursos cu ON cu.nombre_curso = asignacion.curso AND cu.nivel = 'Secundaria'
JOIN (
    SELECT 'Unidad 1' AS periodo, 0 AS ajuste
    UNION ALL SELECT 'Unidad 2', 1
) p
WHERE e.nivel = 'Secundaria'
  AND e.grado BETWEEN 2 AND 5
  AND NOT EXISTS (
      SELECT 1
      FROM calificaciones existente
      WHERE existente.id_estudiante = e.id_estudiante
        AND existente.id_curso = cu.id_curso
        AND existente.id_docente = u.id_usuario
        AND existente.periodo = p.periodo
  );
