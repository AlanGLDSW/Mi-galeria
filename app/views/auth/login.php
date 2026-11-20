<?php
$valor = $viejos['usuario'] ?? $recordado;
$marcado = isset($viejos['recordar']) ? $viejos['recordar'] : $recordado !== '';
?>
<section class="acceso">
  <div class="acceso__intro">
    <h1>Tus imágenes, en cualquier dispositivo</h1>
    <p>Guarda tus fotos en tu propia galería privada. Súbelas desde la computadora o el celular, míralas en grande y borra las que ya no necesites.</p>
    <ul class="ventajas">
      <li>Galería privada: solo tú ves tus imágenes.</li>
      <li>Arrastra y suelta varias imágenes a la vez.</li>
      <li>Se adapta a celulares, tabletas y computadoras.</li>
    </ul>
  </div>

  <div class="tarjeta acceso__formulario">
    <h2>Iniciar sesión</h2>
    <form action="<?= e(url('login')) ?>" method="post" novalidate data-validar>
      <?= campo_csrf() ?>
      <div class="campo">
        <label for="usuario">Usuario o correo</label>
        <input type="text" id="usuario" name="usuario" value="<?= e($valor) ?>" autocomplete="username"
               required maxlength="150" <?= $valor === '' ? 'autofocus' : '' ?>>
      </div>
      <div class="campo">
        <label for="clave">Contraseña</label>
        <div class="campo-clave">
          <input type="password" id="clave" name="clave" autocomplete="current-password" required maxlength="72"
                 <?= $valor !== '' ? 'autofocus' : '' ?>>
          <button type="button" class="ver-clave" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button>
        </div>
      </div>
      <label class="casilla">
        <input type="checkbox" name="recordar" <?= $marcado ? 'checked' : '' ?>>
        <span>Recordar mi usuario en este equipo</span>
      </label>
      <button type="submit" class="boton boton--principal boton--ancho">Entrar</button>
    </form>
    <p class="acceso__cambio">¿No tienes cuenta? <a href="<?= e(url('registro')) ?>">Crea una gratis</a></p>
  </div>
</section>
