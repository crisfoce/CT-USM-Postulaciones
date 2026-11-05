CREATE DATABASE IF NOT EXISTS ct_usm_postulaciones
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE ct_usm_postulaciones;

CREATE TABLE usuario(
    id_usuario INT AUTO_INCREMENT NOT NULL,
    nombre_usuario VARCHAR(100) NOT NULL,
    correo VARCHAR(120) NOT NULL UNIQUE,
    password_usuario VARCHAR(100) NOT NULL,
    rol ENUM('postulante', 'evaluador', 'admin') NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_usuario)
) ENGINE=INNODB;


CREATE TABLE empresa(
    rut_empresa VARCHAR(12) NOT NULL,
    nombre_empresa VARCHAR(150) NOT NULL,
    tamano_empresa VARCHAR(20) NOT NULL,
    nom_rep VARCHAR(100) NOT NULL,
    mail_rep VARCHAR(120) NOT NULL,
    telefono_rep VARCHAR(20) NOT NULL,
    conv_marco_usm BOOLEAN NOT NULL,
    PRIMARY KEY (rut_empresa)
) ENGINE=INNODB;


CREATE TABLE postulacion(
    id_postulacion INT AUTO_INCREMENT NOT NULL,
    rut_empresa VARCHAR(12) NOT NULL,
    codigo_interno VARCHAR(30) NOT NULL,
    fecha_postulacion DATE NOT NULL,
    tipo_iniciativa VARCHAR(20) NOT NULL,
    region_ejecucion VARCHAR(60) NOT NULL,
    region_impacto VARCHAR(60) NOT NULL,
    estado_postulacion VARCHAR(30) NOT NULL DEFAULT 'Borrador',
    nombre_responsable_1 VARCHAR(100) NOT NULL,
    nombre_responsable_2 VARCHAR(100) NOT NULL,
    numero_postulacion INT NOT NULL,
    nombre_iniciativa VARCHAR(150) NOT NULL,
    objetivo_iniciativa VARCHAR(255) NOT NULL,
    descripcion_iniciativa VARCHAR(255) NOT NULL,
    resultados_iniciativa VARCHAR(255) NOT NULL,
    presupuesto_total INT NOT NULL,
    id_usuario_responsable INT NULL,
    id_evaluador INT NULL,

    PRIMARY KEY (id_postulacion),
    UNIQUE (codigo_interno),
    UNIQUE (numero_postulacion),

    FOREIGN KEY (rut_empresa) REFERENCES empresa(rut_empresa)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    FOREIGN KEY (id_usuario_responsable) REFERENCES usuario(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    FOREIGN KEY (id_evaluador) REFERENCES usuario(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE evaluacion(
    id_evaluacion INT AUTO_INCREMENT NOT NULL,
    id_postulacion INT NOT NULL,
    id_evaluador INT NOT NULL,
    comentario TEXT NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    fecha_evaluacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evaluacion),
    FOREIGN KEY (id_postulacion) REFERENCES postulacion(id_postulacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_evaluador) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE sede(
    id_sede INT AUTO_INCREMENT NOT NULL,
    id_postulacion INT NOT NULL,
    nombre_sede VARCHAR(30) NOT NULL,
    PRIMARY KEY (id_sede),
    FOREIGN KEY (id_postulacion) REFERENCES postulacion(id_postulacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE documento(
    id_documento INT AUTO_INCREMENT NOT NULL,
    id_postulacion INT NOT NULL,
    otros_documentos VARCHAR(255),
    PRIMARY KEY (id_documento),
    FOREIGN KEY (id_postulacion) REFERENCES postulacion(id_postulacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE candidato(
    rut_candidato VARCHAR(12) NOT NULL,
    nombre_candidato VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    sede_candidato VARCHAR(100) NOT NULL,
    mail_candidato VARCHAR(120) NOT NULL,
    telefono_candidato VARCHAR(20),
    PRIMARY KEY (rut_candidato)
) ENGINE=INNODB;



CREATE TABLE participacion(
    id_participacion INT AUTO_INCREMENT NOT NULL,
    id_postulacion INT NOT NULL,
    rut_candidato VARCHAR(12) NOT NULL,
    rol_que_cumple VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_participacion),
    UNIQUE (id_postulacion, rut_candidato),
    FOREIGN KEY (id_postulacion) REFERENCES postulacion(id_postulacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (rut_candidato) REFERENCES candidato(rut_candidato)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE etapa_cronograma(
    id_etapa_cronograma INT AUTO_INCREMENT NOT NULL,
    id_postulacion INT NOT NULL,
    nombre_etapa VARCHAR(100) NOT NULL,
    plazo_semanas INT NOT NULL,
    entregable VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_etapa_cronograma),
    FOREIGN KEY (id_postulacion) REFERENCES postulacion(id_postulacion)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=INNODB;