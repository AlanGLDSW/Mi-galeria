<?php
$v = fn ($k) => e($datos[$k] ?? '');
$err = fn ($k) => isset($errores[$k]) ? '<p class="campo__error" id="error-' . $k . '">' . e($errores[$k]) . '</p>' : '';
$inv = fn ($k) => isset($errores[$k]) ? ' aria-invalid="true" aria-describedby="error-' . $k . '"' : '';
$rol = $datos['rol'] ?? $usuario['rol'];
?>
<nav class="migas" aria-label="Ruta"><a href="<?= e(url('admin')) ?>">Usuarios</a> <span aria-hidden="true">›</span> <span><?= e($usuario['nombre']) ?></span></nav>

<div class="cabecera-pagina">
  <div>
    <h1>Editar usuario</h1>
    <p class="cabecera-pagina__sub">Miembro desde el <?= e(fecha_legible($usuario['creado'])) ?> · último acceso: <?= e(fecha_legible($usuario['ultimo_acceso'])) ?></p>
  </div>
</div>

<form class="tarjeta formulario-angosto" action="<?= e(url('admin/editar')) ?>" method="post" novalidate data-validar>
  <?= campo_csrf() ?>
  <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
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
  <fieldset class="campo">
    <legend>Rol</legend>
    <label class="opcion"><input type="radio" name="rol" value="usuario" <?= $rol !== 'admin' ? 'checked' : '' ?>> <span><strong>Usuario</strong> · administra solo su galería</span></label>
    <label class="opcion"><input type="radio" name="rol" value="admin" <?= $rol === 'admin' ? 'checked' : '' ?>> <span><strong>Administrador</strong> · puede modificar o borrar cualquier usuario</span></label>
    <?= $err('rol') ?>
  </fieldset>
  <div class="campo">
    <label for="clave">Nueva contraseña <span class="opcional">(opcional)</span></label>
    <input type="password" id="clave" name="clave" minlength="8" maxlength="72" autocomplete="new-password" data-fuerza<?= $inv('clave') ?>>
    <p class="campo__ayuda" id="fuerza-clave">Déjala vacía para conservar la contraseña actual.</p>
    <?= $err('clave') ?>
  </div>
  <div class="botones">
    <button type="submit" class="boton boton--principal">Guardar cambios</button>
    <a class="boton boton--contorno" href="<?= e(url('admin')) ?>">Cancelar</a>
  </div>
</form>
