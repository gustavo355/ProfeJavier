-- =========================================================
-- asistencia_indel.sql
-- Script de creación de base de datos para MySQL / phpMyAdmin
-- Instituto Nacional Cantón Lourdes — Sistema de Asistencia
--
-- CÓMO USARLO EN phpMyAdmin:
-- 1. Entra a phpMyAdmin y crea una base de datos vacía llamada
--    "asistencia_indel" (cotejamiento utf8mb4_spanish_ci o similar),
--    o simplemente ejecuta este script completo: ya incluye el
--    CREATE DATABASE.
-- 2. Ve a la pestaña "Importar", selecciona este archivo .sql
--    y presiona "Continuar". Eso crea todas las tablas.
-- =========================================================

CREATE DATABASE IF NOT EXISTS asistencia_indel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE asistencia_indel;

-- ---------------------------------------------------------
-- Tabla: roles
-- ---------------------------------------------------------
CREATE TABLE roles (
  id_rol      INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (nombre_rol) VALUES
  ('Administrador'),
  ('Docente');

-- ---------------------------------------------------------
-- Tabla: usuarios
-- ---------------------------------------------------------
CREATE TABLE usuarios (
  id_usuario        INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo   VARCHAR(150) NOT NULL,
  correo            VARCHAR(150) NOT NULL UNIQUE,
  usuario           VARCHAR(50)  NOT NULL UNIQUE,
  contrasena_hash   VARCHAR(255) NOT NULL,
  id_rol            INT NOT NULL,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: secciones
-- ---------------------------------------------------------
CREATE TABLE secciones (
  id_seccion  INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(50) NOT NULL,
  grado       VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: estudiantes
-- ---------------------------------------------------------
CREATE TABLE estudiantes (
  id_estudiante    INT AUTO_INCREMENT PRIMARY KEY,
  carnet           VARCHAR(20) NOT NULL UNIQUE,
  nombre_completo  VARCHAR(150) NOT NULL,
  id_seccion       INT NOT NULL,
  estado           VARCHAR(20) NOT NULL DEFAULT 'Activo',
  FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: asistencia
-- ---------------------------------------------------------
CREATE TABLE asistencia (
  id_asistencia   INT AUTO_INCREMENT PRIMARY KEY,
  id_estudiante   INT NOT NULL,
  id_docente      INT NOT NULL,
  fecha           DATE NOT NULL,
  estado          VARCHAR(20) NOT NULL,
  UNIQUE KEY uq_estudiante_fecha (id_estudiante, fecha),
  FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante),
  FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: servicio_social
-- ---------------------------------------------------------
CREATE TABLE servicio_social (
  id_servicio     INT AUTO_INCREMENT PRIMARY KEY,
  id_estudiante   INT NOT NULL,
  horas           DECIMAL(5,2) NOT NULL,
  fecha           DATE NOT NULL,
  FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: meritos_demeritos
-- ---------------------------------------------------------
CREATE TABLE meritos_demeritos (
  id_registro     INT AUTO_INCREMENT PRIMARY KEY,
  id_estudiante   INT NOT NULL,
  tipo            VARCHAR(20) NOT NULL,   -- 'Mérito' o 'Demérito'
  motivo          VARCHAR(255) NOT NULL,
  fecha           DATE NOT NULL,
  FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Tabla: reportes_generados
-- ---------------------------------------------------------
CREATE TABLE reportes_generados (
  id_reporte             INT AUTO_INCREMENT PRIMARY KEY,
  tipo_reporte           VARCHAR(100) NOT NULL,
  formato                VARCHAR(20)  NOT NULL,
  id_usuario_generador   INT NOT NULL,
  parametros             VARCHAR(255),
  fecha_generacion       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario_generador) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Datos de ejemplo opcionales (puedes borrar este bloque)
-- ---------------------------------------------------------
-- INSERT INTO secciones (nombre, grado) VALUES ('A', '1er Año');
-- INSERT INTO estudiantes (carnet, nombre_completo, id_seccion, estado)
--   VALUES ('2026-001', 'Estudiante de Prueba', 1, 'Activo');
