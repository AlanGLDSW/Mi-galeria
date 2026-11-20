<section class="estado-vacio estado-vacio--error">
  <p class="estado-vacio__codigo"><?= (int) $codigo ?></p>
  <h1><?= e($titulo) ?></h1>
  <p><?= e($mensaje) ?></p>
  <?php if (!empty($detalle)): ?>
    <pre class="detalle-error"><?= e($detalle) ?></pre>
  <?php endif; ?>
  <a class="boton boton--principal" href="<?= e(url()) ?>">Ir al inicio</a>
</section>
