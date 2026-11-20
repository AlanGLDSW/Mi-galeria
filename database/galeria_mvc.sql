

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(30) NOT NULL UNIQUE,
  correo VARCHAR(150) NOT NULL UNIQUE,
  clave VARCHAR(255) NOT NULL,            
  rol ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS imagenes (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  archivo VARCHAR(64) NOT NULL UNIQUE,
  miniatura VARCHAR(64) NULL,
  nombre_original VARCHAR(255) NOT NULL,
  mime VARCHAR(30) NOT NULL,
  tamano INT UNSIGNED NOT NULL,
  ancho INT UNSIGNED NOT NULL,
  alto INT UNSIGNED NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX imagenes_usuario (usuario_id, creado),
  CONSTRAINT fk_imagenes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO usuarios (nombre, usuario, correo, clave, rol)
VALUES ('Administrador', 'admin', 'admin@migaleria.local', '$2y$10$a.tC.bk8QQM8THDv8/QKKey//zxYkmHtJWwBTkZgG/lV0bUigeE4i', 'admin');
