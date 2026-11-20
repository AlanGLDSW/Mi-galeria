<?php
$scripts = ['assets/js/galeria.js'];
$paramUsuario = $modoAdmin ? ['usuario' => $dueno['id']] : [];
$maxMb = (int) config('subidas.mb_por_archivo');
$maxArchivos = (int) config('subidas.archivos_por_envio');
?>
<div id="app-galeria"
     data-csrf="<?= e(Session::csrf()) ?>"
     data-api-listar="<?= e(url('api/imagenes', $paramUsuario)) ?>"
     data-api-subir="<?= e(url('api/subir')) ?>"
     data-api-renombrar="<?= e(url('api/renombrar')) ?>"
     data-api-borrar="<?= e(url('api/borrar')) ?>"
     data-usuario="<?= $modoAdmin ? (int) $dueno['id'] : '' ?>"
     data-max-mb="<?= $maxMb ?>"
     data-max-archivos="<?= $maxArchivos ?>">

  <?php if ($modoAdmin): ?>
    <div class="aviso aviso--aviso" role="status">
      <span class="aviso__icono" aria-hidden="true">i</span>
      <p>Estás viendo la galería de <strong><?= e($dueno['nombre']) ?></strong> (<?= e($dueno['usuario']) ?>) como administrador. Puedes cambiar títulos o borrar imágenes.</p>
      <a class="boton boton--contorno boton--chico" href="<?= e(url('admin')) ?>">Volver a usuarios</a>
    </div>
  <?php endif; ?>

  <div class="cabecera-pagina">
    <div>
      <h1><?= $modoAdmin ? 'Galería de ' . e($dueno['nombre']) : 'Mi galería' ?></h1>
      <p class="cabecera-pagina__sub" id="resumen-galeria">Cargando imágenes…</p>
    </div>
    <div class="uso" aria-live="polite">
      <div class="uso__barra" role="progressbar" aria-label="Espacio usado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <span class="uso__relleno" id="uso-relleno"></span>
      </div>
      <p class="uso__texto" id="uso-texto">—</p>
    </div>
  </div>

  <noscript>
    <div class="aviso aviso--error"><p>Esta galería necesita JavaScript. Actívalo en tu navegador para ver y subir tus imágenes.</p></div>
  </noscript>

  <?php if (!$modoAdmin): ?>
    <section class="tarjeta subir" aria-labelledby="titulo-subir">
      <h2 id="titulo-subir" class="visualmente-oculto">Agregar imágenes</h2>
      <input type="file" id="entrada-archivos" class="entrada-oculta" multiple
             accept="image/jpeg,image/png,image/gif,image/webp">
      <label for="entrada-archivos" class="zona-subida" id="zona-subida">
        <svg class="zona-subida__icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="zona-subida__titulo">Arrastra tus imágenes aquí <span class="solo-tactil">o toca para elegirlas</span><span class="solo-raton">o haz clic para elegirlas</span></span>
        <span class="zona-subida__ayuda">JPG, PNG, GIF o WEBP · hasta <?= $maxMb ?> MB cada una · máximo <?= $maxArchivos ?> a la vez</span>
      </label>

      <div class="cola" id="cola" hidden>
        <ul class="cola__lista" id="cola-lista"></ul>
        <div class="cola__acciones">
          <button type="button" class="boton boton--contorno" id="cola-limpiar">Quitar todas</button>
          <button type="button" class="boton boton--principal" id="cola-subir">Subir imágenes</button>
        </div>
        <div class="progreso" id="progreso" hidden>
          <div class="progreso__barra"><span id="progreso-relleno"></span></div>
          <p class="progreso__texto" id="progreso-texto">Subiendo…</p>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section aria-labelledby="titulo-imagenes">
    <h2 id="titulo-imagenes" class="visualmente-oculto">Imágenes</h2>

    <div class="herramientas" id="herramientas" hidden>
      <div class="herramientas__buscar">
        <label for="buscar" class="visualmente-oculto">Buscar por título</label>
        <input type="search" id="buscar" placeholder="Buscar por título…" autocomplete="off">
      </div>
      <div class="herramientas__orden">
        <label for="orden">Ordenar</label>
        <select id="orden">
          <option value="recientes">Más recientes</option>
          <option value="antiguas">Más antiguas</option>
          <option value="nombre">Nombre (A-Z)</option>
          <option value="tamano">Más pesadas</option>
        </select>
      </div>
      <div class="herramientas__seleccion">
        <label class="casilla">
          <input type="checkbox" id="seleccionar-todas">
          <span>Seleccionar todas</span>
        </label>
        <span class="contador" id="contador-seleccion" aria-live="polite"></span>
        <button type="button" class="boton boton--peligro boton--chico" id="borrar-seleccionadas" disabled>Borrar seleccionadas</button>
      </div>
    </div>

    <ul class="galeria" id="galeria" aria-busy="true">
      <?php for ($i = 0; $i < 4; $i++): ?><li class="tarjeta-imagen tarjeta-imagen--cargando" aria-hidden="true"></li><?php endfor; ?>
    </ul>

    <div class="estado-vacio" id="estado-vacio" hidden>
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="10" r="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
      <h2><?= $modoAdmin ? 'Este usuario no tiene imágenes' : 'Tu galería está vacía' ?></h2>
      <p><?= $modoAdmin ? 'Cuando suba imágenes aparecerán aquí.' : 'Arrastra tus imágenes al recuadro de arriba para agregar las primeras.' ?></p>
    </div>
    <div class="estado-vacio" id="sin-resultados" hidden>
      <h2>Sin resultados</h2>
      <p>Ninguna imagen coincide con tu búsqueda.</p>
    </div>
  </section>

  <template id="plantilla-tarjeta">
    <li class="tarjeta-imagen">
      <div class="tarjeta-imagen__marco">
        <button type="button" class="tarjeta-imagen__ver" data-accion="ver">
          <img alt="" loading="lazy" decoding="async">
        </button>
        <label class="tarjeta-imagen__casilla">
          <input type="checkbox" data-accion="seleccionar">
          <span class="visualmente-oculto">Seleccionar</span>
        </label>
      </div>
      <div class="tarjeta-imagen__info">
        <h3 class="tarjeta-imagen__titulo"></h3>
        <p class="tarjeta-imagen__meta"></p>
        <div class="tarjeta-imagen__acciones">
          <button type="button" class="boton-icono" data-accion="renombrar" title="Cambiar título" aria-label="Cambiar título">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Zm11-15 4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
          </button>
          <a class="boton-icono" data-accion="descargar" title="Descargar" aria-label="Descargar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
          <button type="button" class="boton-icono boton-icono--peligro" data-accion="borrar" title="Borrar" aria-label="Borrar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2m-7 0 1 12h8l1-12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
    </li>
  </template>

  <dialog class="visor" id="visor" aria-labelledby="visor-titulo">
    <div class="visor__barra">
      <div class="visor__textos">
        <h2 id="visor-titulo"></h2>
        <p id="visor-meta"></p>
      </div>
      <div class="visor__botones">
        <a class="boton boton--contorno boton--chico" id="visor-descargar">Descargar</a>
        <button type="button" class="boton boton--contorno boton--chico" id="visor-cerrar">Cerrar</button>
      </div>
    </div>
    <div class="visor__escenario">
      <button type="button" class="visor__flecha visor__flecha--izq" id="visor-anterior" aria-label="Imagen anterior">‹</button>
      <img id="visor-imagen" alt="">
      <button type="button" class="visor__flecha visor__flecha--der" id="visor-siguiente" aria-label="Imagen siguiente">›</button>
    </div>
    <p class="visor__posicion" id="visor-posicion"></p>
  </dialog>

  <dialog class="dialogo" id="dialogo-titulo-imagen" aria-labelledby="dialogo-titulo-imagen-h">
    <form method="dialog" id="form-titulo">
      <h2 class="dialogo__titulo" id="dialogo-titulo-imagen-h">Cambiar título</h2>
      <div class="campo">
        <label for="nuevo-titulo">Título de la imagen</label>
        <input type="text" id="nuevo-titulo" maxlength="150" required>
      </div>
      <div class="dialogo__acciones">
        <button value="cancelar" class="boton boton--contorno" formnovalidate>Cancelar</button>
        <button value="guardar" class="boton boton--principal">Guardar</button>
      </div>
    </form>
  </dialog>
</div>
