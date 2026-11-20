<?php
$u = $usuarioSesion;
$rutaActual = $_GET['r'] ?? '';
$activo = fn (string $prefijo) => str_starts_with($rutaActual, $prefijo) ? ' aria-current="page"' : '';
$iconos = ['exito' => '✓', 'error' => '!', 'aviso' => 'i'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Galería personal de imágenes: sube, organiza y borra tus fotos desde cualquier dispositivo.">
  <meta name="theme-color" content="#0f1420">
  <title><?= e($titulo ?? 'Inicio') ?> · <?= e(config('app.nombre')) ?></title>
  <link rel="icon" href="<?= e(recurso('assets/img/icono.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(recurso('assets/css/estilos.css')) ?>">
  <script>document.documentElement.classList.add('js');</script>
</head>
<body>
  <a class="saltar" href="#contenido">Saltar al contenido</a>

  <header class="encabezado">
    <div class="contenedor encabezado__barra">
      <a class="marca" href="<?= e(url()) ?>">
        <img src="<?= e(recurso('assets/img/icono.svg')) ?>" alt="" width="36" height="36">
        <span><?= e(config('app.nombre')) ?></span>
      </a>

      <?php if ($u): ?>
        <button class="menu-boton" type="button" aria-expanded="false" aria-controls="menu-principal">
          <span class="menu-boton__icono" aria-hidden="true"></span>
          <span class="visualmente-oculto">Abrir menú</span>
        </button>
        <nav class="menu" id="menu-principal" aria-label="Principal">
          <a href="<?= e(url('galeria')) ?>"<?= $activo('galeria') ?>>Mi galería</a>
          <a href="<?= e(url('cuenta')) ?>"<?= $activo('cuenta') ?>>Mi cuenta</a>
          <?php if ($u['rol'] === 'admin'): ?>
            <a href="<?= e(url('admin')) ?>"<?= $activo('admin') ?>>Usuarios</a>
          <?php endif; ?>
          <span class="menu__usuario">
            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
            <span class="menu__nombre"><?= e($u['nombre']) ?><?php if ($u['rol'] === 'admin'): ?> <span class="etiqueta">Admin</span><?php endif; ?></span>
          </span>
          <form action="<?= e(url('logout')) ?>" method="post" class="menu__salir">
            <?= campo_csrf() ?>
            <button type="submit" class="boton boton--contorno boton--chico">Cerrar sesión</button>
          </form>
        </nav>
      <?php endif; ?>
    </div>
  </header>

  <main id="contenido" class="contenedor principal">
    <?php foreach ($flash as $tipo => $mensaje): ?>
      <div class="aviso aviso--<?= e($tipo) ?>" role="<?= $tipo === 'error' ? 'alert' : 'status' ?>">
        <span class="aviso__icono" aria-hidden="true"><?= $iconos[$tipo] ?? 'i' ?></span>
        <p><?= e($mensaje) ?></p>
        <button type="button" class="aviso__cerrar" aria-label="Cerrar aviso">×</button>
      </div>
    <?php endforeach; ?>

    <?= $contenido ?>
  </main>

  <footer class="pie">
    <div class="contenedor pie__contenido">
      <p><strong>Universidad de Guadalajara - UDG Virtual</strong></p>
      <p>Licenciatura en Desarrollo de Sistemas Web - Lenguajes de programación backend</p>
      <p>Producto integrador: Mi aplicación construida en un lenguaje de programación back end</p>
      <p>Estudiante: Alan Arturo González Corona - Asesor: Luis Eduardo Alvarez Becerra</p>
    </div>
  </footer>

  <div class="avisos-flotantes" id="avisos-flotantes" aria-live="polite" aria-atomic="false"></div>

  <dialog class="dialogo" id="dialogo-confirmar" aria-labelledby="dialogo-titulo">
    <form method="dialog">
      <h2 class="dialogo__titulo" id="dialogo-titulo">¿Confirmar?</h2>
      <p class="dialogo__texto" id="dialogo-texto"></p>
      <div class="dialogo__acciones">
        <button value="cancelar" class="boton boton--contorno">Cancelar</button>
        <button value="aceptar" class="boton boton--peligro" id="dialogo-aceptar">Aceptar</button>
      </div>
    </form>
  </dialog>

  <script src="<?= e(recurso('assets/js/app.js')) ?>" defer></script>
  <?php foreach ($scripts ?? [] as $s): ?>
    <script src="<?= e(recurso($s)) ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
