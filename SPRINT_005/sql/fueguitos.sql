-- Base de datos Fueguitos (MySQL / MariaDB de XAMPP)
-- Importar desde phpMyAdmin > Importar

CREATE DATABASE IF NOT EXISTS fueguitos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fueguitos;

DROP TABLE IF EXISTS libros;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  reset_token   CHAR(64) NULL,
  reset_expira  DATETIME NULL,
  creado        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE libros (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo     VARCHAR(200) NOT NULL,
  autor      VARCHAR(160) NOT NULL,
  categoria  VARCHAR(60)  NOT NULL,
  anio       SMALLINT NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  portada    VARCHAR(255) NULL,
  creado     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria),
  INDEX idx_titulo (titulo)
) ENGINE=InnoDB;

-- Admin inicial: admin@fueguitos.com / password  (CAMBIAR después del primer ingreso)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Administrador', 'admin@fueguitos.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO libros (titulo, autor, categoria, anio, disponible) VALUES
('Don Quijote de la Mancha', 'Miguel de Cervantes', 'Novela',  1605, 1),
('Martín Fierro',            'José Hernández',      'Poesía',  1872, 1),
('Cuentos de la selva',      'Horacio Quiroga',     'Infantil',1918, 1),
('Frankenstein',             'Mary Shelley',        'Novela',  1818, 0),
('El origen de las especies','Charles Darwin',      'Ciencia', 1859, 1),
('Rayuela',                  'Julio Cortázar',      'Novela',  1963, 0);
