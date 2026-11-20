<?php $yo = $usuarioSesion['id']; ?>
<div class="cabecera-pagina">
  <div>
    <h1>Usuarios</h1>
    <p class="cabecera-pagina__sub">Como administrador puedes modificar o borrar los datos de cualquier usuario.</p>
  </div>
</div>

<div class="cifras">
  <div class="cifra"><span class="cifra__valor"><?= (int) $totales['usuarios'] ?></span><span class="cifra__etiqueta">usuarios<?= $buscar !== '' ? ' encontrados' : '' ?></span></div>
  <div class="cifra"><span class="cifra__valor"><?= (int) $totales['imagenes'] ?></span><span class="cifra__etiqueta">imágenes</span></div>
  <div class="cifra"><span class="cifra__valor"><?= e(tamano_legible((int) $totales['bytes'])) ?></span><span class="cifra__etiqueta">espacio usado</span></div>
</div>

<form class="buscador" method="get" action="index.php" role="search">
  <input type="hidden" name="r" value="admin">
  <label for="buscar-usuario" class="visualmente-oculto">Buscar usuario</label>
  <input type="search" id="buscar-usuario" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, usuario o correo">
  <button class="boton boton--contorno" type="submit">Buscar</button>
  <?php if ($buscar !== ''): ?><a class="boton boton--texto" href="<?= e(url('admin')) ?>">Limpiar</a><?php endif; ?>
</form>

<?php if (!$usuarios): ?>
  <div class="estado-vacio"><h2>Sin resultados</h2><p>No hay usuarios que coincidan con “<?= e($buscar) ?>”.</p></div>
<?php else: ?>
<div class="tabla-envoltura">
  <table class="tabla">
    <thead>
      <tr>
        <th scope="col">Usuario</th>
        <th scope="col">Correo</th>
        <th scope="col">Rol</th>
        <th scope="col">Imágenes</th>
        <th scope="col">Último acceso</th>
        <th scope="col"><span class="visualmente-oculto">Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $u): $esYo = (int) $u['id'] === $yo; ?>
        <tr>
          <td data-etiqueta="Usuario">
            <div class="persona">
              <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
              <span><strong><?= e($u['nombre']) ?></strong><?= $esYo ? ' <span class="etiqueta etiqueta--suave">Tú</span>' : '' ?><br><span class="tenue">@<?= e($u['usuario']) ?></span></span>
            </div>
          </td>
          <td data-etiqueta="Correo"><?= e($u['correo']) ?></td>
          <td data-etiqueta="Rol"><span class="etiqueta<?= $u['rol'] === 'admin' ? '' : ' etiqueta--suave' ?>"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Usuario' ?></span></td>
          <td data-etiqueta="Imágenes"><?= (int) $u['imagenes'] ?> · <?= e(tamano_legible((int) $u['bytes'])) ?></td>
          <td data-etiqueta="Último acceso"><?= e(fecha_legible($u['ultimo_acceso'])) ?></td>
          <td class="tabla__acciones">
            <a class="boton boton--contorno boton--chico" href="<?= e(url('admin/galeria', ['id' => $u['id']])) ?>">Galería</a>
            <a class="boton boton--contorno boton--chico" href="<?= e(url('admin/editar', ['id' => $u['id']])) ?>">Editar</a>
            <?php if ($esYo): ?>
              <button class="boton boton--peligro boton--chico" type="button" disabled title="No puedes borrar tu propia cuenta">Borrar</button>
            <?php else: ?>
              <form action="<?= e(url('admin/borrar')) ?>" method="post"
                    data-confirmar="Se borrará la cuenta de <?= e($u['nombre']) ?> y sus <?= (int) $u['imagenes'] ?> imágenes. Esta acción no se puede deshacer."
                    data-confirmar-titulo="¿Borrar usuario?" data-confirmar-boton="Borrar usuario">
                <?= campo_csrf() ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button class="boton boton--peligro boton--chico" type="submit">Borrar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
