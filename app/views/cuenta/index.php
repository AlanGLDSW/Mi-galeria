<?php
$v = fn ($k) => e($datos[$k] ?? '');
$err = fn ($k) => isset($errores[$k]) ? '<p class="campo__error" id="error-' . $k . '">' . e($errores[$k]) . '</p>' : '';
$inv = fn ($k) => isset($errores[$k]) ? ' aria-invalid="true" aria-describedby="error-' . $k . '"' : '';
$limite = config('subidas.mb_por_usuario') * 1048576;
?>
<div class="cabecera-pagina">
  <div>
    <h1>Mi cuenta</h1>
    <p class="cabecera-pagina__sub">Cambia tus datos o tu contraseña.</p>
  </div>
</div>

<div class="rejilla-dos">
  <form class="tarjeta" action="<?= e(url('cuenta')) ?>" method="post" novalidate data-validar>
    <?= campo_csrf() ?>
    <h2>Datos personales</h2>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" value="<?= $v('nombre') ?>" required minlength="3" maxlength="100"<?= $inv('nombre') ?>>
      <?= $err('nombre') ?>
    </div>
    <div class="campo">
      <label for="usuario">Nombre de usuario</label>
      <input type="text" id="usuario" name="usuario" value="<?= $v('usuario') ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}"<?= $inv('usuario') ?>>
      <?= $err('usuario') ?>
    </div>
    <div class="campo">
      <label for="correo">Correo electrónico</label>
      <input type="email" id="correo" name="correo" value="<?= $v('correo') ?>" required maxlength="150"<?= $inv('correo') ?>>
      <?= $err('correo') ?>
    </div>

    <h2 class="separado">Cambiar contraseña <span class="opcional">(opcional)</span></h2>
    <div class="campo">
      <label for="actual">Contraseña actual</label>
      <input type="password" id="actual" name="actual" maxlength="72" autocomplete="current-password"<?= $inv('actual') ?>>
      <?= $err('actual') ?>
    </div>
    <div class="campo">
      <label for="clave">Contraseña nueva</label>
      <input type="password" id="clave" name="clave" minlength="8" maxlength="72" autocomplete="new-password" data-fuerza<?= $inv('clave') ?>>
      <p class="campo__ayuda" id="fuerza-clave">Déjala vacía si no quieres cambiarla.</p>
      <?= $err('clave') ?>
    </div>
    <div class="campo">
      <label for="confirmar">Confirmar contraseña nueva</label>
      <input type="password" id="confirmar" name="confirmar" maxlength="72" autocomplete="new-password" data-igual-a="clave"<?= $inv('confirmar') ?>>
      <?= $err('confirmar') ?>
    </div>
    <button type="submit" class="boton boton--principal">Guardar cambios</button>
  </form>

  <aside class="tarjeta resumen-cuenta">
    <h2>Resumen</h2>
    <dl>
      <div><dt>Tipo de cuenta</dt><dd><?= ($datos['rol'] ?? '') === 'admin' ? 'Administrador' : 'Usuario' ?></dd></div>
      <div><dt>Miembro desde</dt><dd><?= e(fecha_legible($datos['creado'] ?? null)) ?></dd></div>
      <div><dt>Último acceso</dt><dd><?= e(fecha_legible($datos['ultimo_acceso'] ?? null)) ?></dd></div>
      <div><dt>Espacio usado</dt><dd><?= e(tamano_legible($bytes)) ?> de <?= (int) config('subidas.mb_por_usuario') ?> MB</dd></div>
    </dl>
    <div class="uso__barra"><span class="uso__relleno" style="width: <?= min(100, round($bytes / $limite * 100)) ?>%"></span></div>
    <a class="boton boton--contorno boton--ancho" href="<?= e(url('galeria')) ?>">Ir a mi galería</a>
  </aside>
</div>
