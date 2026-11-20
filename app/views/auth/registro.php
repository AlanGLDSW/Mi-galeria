<?php
$v = fn ($k) => e($viejos[$k] ?? '');
$err = function ($k) use ($errores) {
    return isset($errores[$k]) ? '<p class="campo__error" id="error-' . $k . '">' . e($errores[$k]) . '</p>' : '';
};
$inv = fn ($k) => isset($errores[$k]) ? ' aria-invalid="true" aria-describedby="error-' . $k . '"' : '';
?>
<section class="acceso">
  <div class="acceso__intro">
    <h1>Crea tu galería</h1>
    <p>Regístrate en un minuto. Con tu cuenta podrás subir hasta <?= (int) config('subidas.mb_por_usuario') ?> MB de imágenes y acceder a ellas desde cualquier dispositivo con Internet.</p>
  </div>

  <div class="tarjeta acceso__formulario">
    <h2>Crear cuenta</h2>
    <form action="<?= e(url('registro')) ?>" method="post" novalidate data-validar>
      <?= campo_csrf() ?>
      <div class="campo">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" value="<?= $v('nombre') ?>" required minlength="3" maxlength="100" autocomplete="name"<?= $inv('nombre') ?>>
        <?= $err('nombre') ?>
      </div>
      <div class="campo">
        <label for="usuario">Nombre de usuario</label>
        <input type="text" id="usuario" name="usuario" value="<?= $v('usuario') ?>" required minlength="3" maxlength="30"
               pattern="[A-Za-z0-9_.\-]{3,30}" autocomplete="username"<?= $inv('usuario') ?>>
        <p class="campo__ayuda">Letras, números, punto, guion o guion bajo.</p>
        <?= $err('usuario') ?>
      </div>
      <div class="campo">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="<?= $v('correo') ?>" required maxlength="150" autocomplete="email"<?= $inv('correo') ?>>
        <?= $err('correo') ?>
      </div>
      <div class="campo">
        <label for="clave">Contraseña</label>
        <div class="campo-clave">
          <input type="password" id="clave" name="clave" required minlength="8" maxlength="72" autocomplete="new-password"
                 data-fuerza<?= $inv('clave') ?>>
          <button type="button" class="ver-clave" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button>
        </div>
        <p class="campo__ayuda" id="fuerza-clave">Mínimo 8 caracteres, con al menos una letra y un número.</p>
        <?= $err('clave') ?>
      </div>
      <div class="campo">
        <label for="confirmar">Confirmar contraseña</label>
        <input type="password" id="confirmar" name="confirmar" required maxlength="72" autocomplete="new-password"
               data-igual-a="clave"<?= $inv('confirmar') ?>>
        <?= $err('confirmar') ?>
      </div>
      <button type="submit" class="boton boton--principal boton--ancho">Crear cuenta</button>
    </form>
    <p class="acceso__cambio">¿Ya tienes cuenta? <a href="<?= e(url('login')) ?>">Inicia sesión</a></p>
  </div>
</section>
