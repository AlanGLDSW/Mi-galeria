# Mi Galería de imágenes

**Aplicación en línea:** https://mi-galeria.site.je

Es una aplicación web de gestión de imágenes que emplea el patrón Modelo Vista Controlador (MVC).

- **Servidor:** PHP 8 con MySQL. Controladores, modelos y una API que responde en JSON.
- **Cliente:** HTML, CSS y JavaScript. La galería se dibuja en el navegador con los datos de la API.

Cada usuario tiene su propia galería privada, donde puede agregar, ver, renombrar, descargar y borrar imágenes. El **administrador** puede modificar o borrar los datos de cualquier usuario y revisar su galería.

## Estructura

```
index.php                 Controlador frontal: todas las peticiones entran aquí
app/
  config/config.php       Datos de la base de datos y límites de subida
  config/rutas.php        Tabla de rutas (página o API) → controlador y acción
  core/                   Núcleo: Router, Controller, View, Database, Session, Almacen
  controllers/            AuthController, GaleriaController, CuentaController, AdminController
  models/                 UsuarioModel, ImagenModel
  views/                  Plantillas HTML (layout, acceso, galería, cuenta, administración)
assets/css, assets/js     Estilos y JavaScript del cliente (app.js y galeria.js)
uploads/                  Imágenes subidas (privada: solo se entregan a través de PHP)
database/galeria_mvc.sql  Tablas y administrador inicial
```

## Comprobación del funcionamiento: probar en la computadora (XAMPP)

1. Se realiza una copia la carpeta `galeria` dentro de `C:\xampp\htdocs\backend\producto_integrador`.
2. En XAMPP enciende **Apache** y **MySQL**.
3. Abre `http://localhost/phpmyadmin`, y se da clic en la pestaña **Importar** y se sube el archivo `database/galeria_mvc.sql`.
4. Abre en el navegador de Chrome `http://localhost/backend/producto_integrador/galeria`.
5. Entra con las credenciales del administrador **admin / Admin2026** (se cambia la contraseña en "Mi cuenta" por seguridad), también pueden crear una cuenta nueva para probar.

## Sitio publicado en Internet (InfinityFree, gratis)

1. Crea una cuenta en https://www.infinityfree.com y crea un sitio con un subdominio gratis.
2. En el panel (**Control Panel**), entra a **MySQL Databases** y crea una base. Anota estos datos, que más adelante se necesitaran para agregarlos al archivo `app/config/config.php`:
   - el host (`sql204.infinityfree.com`)
   - el puerto (3306)
   - el nombre de la base (`if0_XXXX_galeria`)
   - el usuario (`if0_XXXX`)
   - la contraseña de la cuenta de hosting
3. Ahora se abre **phpMyAdmin** desde el panel de InfinityFree, y se importa la base de datos `database/galeria_mvc.sql`.
   Antes de importarla, se borró el comentario de la base daatos para mi aplicacion y sus dos primeras instrucciones para crear la DB (`CREATE DATABASE` y `USE`): en el hosting la base ya existe con otro nombre.
4. Ahora, se procede a editar el archivo que se encuentra en la ruta: `app/config/config.php` con los datos del paso 2.
5. Abre el **File Manager**. Y dentro de la carpeta `htdocs` sube **el contenido** de la carpeta `galeria`.
6. Abre tu dirección (`https://mi-galeria.site.je/`) y este link es el que se compartirá en el foro.

## Seguridad

- Contraseñas guardadas con `password_hash()` y consultas preparadas con PDO.
- Sesión con cookie `HttpOnly` y `SameSite=Lax`. Se genera un identificador nuevo al entrar y la sesión se cierra tras 60 minutos sin actividad.
- Token CSRF en todos los formularios y en las peticiones de la API.
- Pausa de 60 segundos tras 5 intentos fallidos de inicio de sesión.
- Las imágenes se validan por su contenido real (`finfo` y `getimagesize`) y se guardan con nombres aleatorios. Solo se entregan a su dueño o al administrador.
- Las carpetas `app`, `uploads` y `database` están bloqueadas para el navegador mediante `.htaccess`.

## Nota sobre la configuración

En este repositorio, `app/config/config.php` trae los datos de una instalación local (XAMPP).
Los datos reales del hosting, incluida la contraseña, solo están en el servidor y no se publican.
