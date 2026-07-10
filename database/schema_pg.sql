-- Schema PostgreSQL para BI Educativo Piura
-- Ejecutar en Render PostgreSQL

-- Extensiones
CREATE EXTENSION IF NOT EXISTS unaccent;

-- ================================================
-- TABLAS (sin FKs primero para evitar conflictos)
-- ================================================

CREATE TABLE IF NOT EXISTS roles (
    id_rol    SERIAL PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario          SERIAL PRIMARY KEY,
    dni                 VARCHAR(8)   NOT NULL UNIQUE,
    nombres             VARCHAR(100) NOT NULL,
    apellidos           VARCHAR(100) NOT NULL,
    correo              VARCHAR(100) DEFAULT NULL,
    telefono            VARCHAR(20)  DEFAULT NULL,
    contrasena          VARCHAR(255) NOT NULL,
    id_rol              INTEGER      DEFAULT NULL,
    primer_login        SMALLINT     NOT NULL DEFAULT 1,
    estado_cuenta       VARCHAR(20)  NOT NULL DEFAULT 'activo',
    fecha_registro      TIMESTAMP    DEFAULT NOW(),
    token_verificacion  VARCHAR(64)  DEFAULT NULL,
    correo_verificado   SMALLINT     NOT NULL DEFAULT 1,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
);
CREATE INDEX IF NOT EXISTS idx_correo ON usuarios(correo);

CREATE TABLE IF NOT EXISTS estudiantes (
    id_estudiante      SERIAL PRIMARY KEY,
    dni                VARCHAR(8)   DEFAULT NULL UNIQUE,
    codigo_estudiante  VARCHAR(20)  DEFAULT NULL UNIQUE,
    nombres            VARCHAR(100) NOT NULL,
    apellidos          VARCHAR(100) NOT NULL,
    nivel              VARCHAR(20)  NOT NULL,
    grado              INTEGER      NOT NULL,
    seccion            VARCHAR(1)   NOT NULL,
    id_padre           INTEGER      DEFAULT NULL,
    CONSTRAINT fk_estudiantes_padre FOREIGN KEY (id_padre) REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS cursos (
    id_curso     SERIAL PRIMARY KEY,
    nombre_curso VARCHAR(100) NOT NULL,
    nivel        VARCHAR(20)  NOT NULL
);

CREATE TABLE IF NOT EXISTS calificaciones (
    id_nota         SERIAL PRIMARY KEY,
    id_estudiante   INTEGER         DEFAULT NULL,
    id_curso        INTEGER         DEFAULT NULL,
    id_docente      INTEGER         DEFAULT NULL,
    nota_final      DECIMAL(4,2)    DEFAULT NULL,
    periodo         VARCHAR(20)     DEFAULT NULL,
    fecha_registro  TIMESTAMP       NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_calif_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante),
    CONSTRAINT fk_calif_curso      FOREIGN KEY (id_curso)      REFERENCES cursos (id_curso),
    CONSTRAINT fk_calif_docente    FOREIGN KEY (id_docente)    REFERENCES usuarios (id_usuario)
);
CREATE INDEX IF NOT EXISTS idx_calif_estudiante ON calificaciones(id_estudiante);
CREATE INDEX IF NOT EXISTS idx_calif_docente    ON calificaciones(id_docente);

CREATE TABLE IF NOT EXISTS asistencia (
    id_asistencia  SERIAL PRIMARY KEY,
    id_estudiante  INTEGER     DEFAULT NULL,
    fecha          DATE        DEFAULT NULL,
    estado         VARCHAR(20) DEFAULT NULL,
    CONSTRAINT fk_asistencia_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante)
);
CREATE INDEX IF NOT EXISTS idx_asist_estudiante ON asistencia(id_estudiante);

CREATE TABLE IF NOT EXISTS documentos_asistencia (
    id_documento  SERIAL PRIMARY KEY,
    id_docente    INTEGER      NOT NULL,
    nivel         VARCHAR(20)  NOT NULL,
    grado         INTEGER      NOT NULL,
    seccion       VARCHAR(1)   NOT NULL,
    titulo        VARCHAR(150) NOT NULL,
    descripcion   VARCHAR(255) DEFAULT NULL,
    archivo       VARCHAR(255) NOT NULL,
    fecha_subida  TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_docs_docente FOREIGN KEY (id_docente) REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion  SERIAL PRIMARY KEY,
    id_estudiante    INTEGER      NOT NULL,
    id_padre         INTEGER      NOT NULL,
    canal            VARCHAR(20)  NOT NULL DEFAULT 'Sistema',
    mensaje          VARCHAR(255) NOT NULL,
    estado           VARCHAR(20)  NOT NULL DEFAULT 'Pendiente',
    fecha_envio      TIMESTAMP    NOT NULL DEFAULT NOW(),
    tipo             VARCHAR(50)  NOT NULL DEFAULT 'Alerta temprana',
    CONSTRAINT fk_notif_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante),
    CONSTRAINT fk_notif_padre      FOREIGN KEY (id_padre)      REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS notificaciones_usuario (
    id_notificacion  SERIAL PRIMARY KEY,
    id_usuario       INTEGER      NOT NULL,
    tipo             VARCHAR(50)  NOT NULL,
    canal            VARCHAR(30)  NOT NULL DEFAULT 'Sistema/Correo',
    mensaje          VARCHAR(255) NOT NULL,
    estado_correo    VARCHAR(30)  NOT NULL DEFAULT 'Pendiente SMTP',
    leido            SMALLINT     NOT NULL DEFAULT 0,
    fecha            TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_notifu_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS accesos_sistema (
    id_acceso   SERIAL PRIMARY KEY,
    id_usuario  INTEGER      DEFAULT NULL,
    correo      VARCHAR(100) NOT NULL,
    exito       SMALLINT     NOT NULL DEFAULT 0,
    ip          VARCHAR(45)  DEFAULT NULL,
    fecha       TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_accesos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
);
CREATE INDEX IF NOT EXISTS idx_ip_fecha ON accesos_sistema(ip, fecha);

CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria  SERIAL PRIMARY KEY,
    id_usuario    INTEGER      DEFAULT NULL,
    modulo        VARCHAR(60)  NOT NULL,
    accion        VARCHAR(80)  NOT NULL,
    detalle       VARCHAR(255) NOT NULL,
    ip            VARCHAR(45)  DEFAULT NULL,
    fecha         TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS planes_mejora_ia (
    id_plan               SERIAL PRIMARY KEY,
    id_estudiante         INTEGER      NOT NULL,
    promedio_referencia   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    asistencia_referencia INTEGER      NOT NULL DEFAULT 0,
    area_critica          VARCHAR(120) NOT NULL,
    acciones_json         TEXT         NOT NULL,
    fecha_generacion      TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_planes_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_plan_estudiante ON planes_mejora_ia(id_estudiante);

CREATE TABLE IF NOT EXISTS solicitudes_vinculacion (
    id_solicitud    SERIAL PRIMARY KEY,
    id_padre        INTEGER     NOT NULL,
    dni_estudiante  VARCHAR(8)  NOT NULL,
    estado          VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    fecha           TIMESTAMP   NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_solicitud_padre FOREIGN KEY (id_padre) REFERENCES usuarios (id_usuario)
);

CREATE TABLE IF NOT EXISTS temas_docente (
    id_tema    SERIAL PRIMARY KEY,
    id_docente INTEGER      NOT NULL,
    grado      INTEGER      NOT NULL,
    seccion    VARCHAR(5)   NOT NULL,
    area       VARCHAR(100) NOT NULL,
    bimestre   SMALLINT     NOT NULL,
    unidad     SMALLINT     NOT NULL,
    sesion_1   VARCHAR(300) NOT NULL DEFAULT '',
    sesion_2   VARCHAR(300) NOT NULL DEFAULT '',
    sesion_3   VARCHAR(300) NOT NULL DEFAULT '',
    sesion_4   VARCHAR(300) NOT NULL DEFAULT '',
    actualizado TIMESTAMP   DEFAULT NOW(),
    UNIQUE (id_docente, grado, seccion, area, bimestre, unidad),
    CONSTRAINT fk_temas_docente FOREIGN KEY (id_docente) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS configuracion_sistema (
    clave       VARCHAR(80)  NOT NULL PRIMARY KEY,
    valor       VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    grupo       VARCHAR(40)  NOT NULL DEFAULT 'general',
    actualizado TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS periodos_academicos (
    id_periodo   SERIAL PRIMARY KEY,
    nombre       VARCHAR(80) NOT NULL,
    fecha_inicio DATE        NOT NULL,
    fecha_fin    DATE        NOT NULL,
    activo       SMALLINT    NOT NULL DEFAULT 0
);
