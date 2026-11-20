(function () {
  'use strict';

  const app = document.getElementById('app-galeria');
  if (!app) return;

  const $ = (id) => document.getElementById(id);
  const cfg = {
    csrf: app.dataset.csrf,
    listar: app.dataset.apiListar,
    subir: app.dataset.apiSubir,
    renombrar: app.dataset.apiRenombrar,
    borrar: app.dataset.apiBorrar,
    usuario: app.dataset.usuario ? Number(app.dataset.usuario) : null,
    maxMb: Number(app.dataset.maxMb),
    maxArchivos: Number(app.dataset.maxArchivos),
  };
  const TIPOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  let imagenes = [];
  let visibles = [];
  const seleccion = new Set();
  let cola = [];
  let indiceVisor = -1;

  function tamano(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return bytes === 0 ? '0 KB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function fecha(iso) {
    return new Date(iso).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  async function api(url, opciones = {}) {
    const r = await fetch(url, {
      ...opciones,
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': cfg.csrf, Accept: 'application/json', ...(opciones.headers || {}) },
    });
    let datos = {};
    try {
      datos = await r.json();
    } catch (e) {
      datos = { ok: false, error: 'Respuesta inesperada del servidor.' };
    }
    if (r.status === 401) {
      App.aviso(datos.error || 'Tu sesión terminó.', 'error');
      setTimeout(() => (location.href = 'index.php?r=login'), 1500);
    }
    if (!r.ok && datos.ok !== false) datos.ok = false;
    return datos;
  }

  function postJson(url, cuerpo) {
    if (cfg.usuario) cuerpo.usuario = cfg.usuario;
    return api(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(cuerpo),
    });
  }

  function mostrarUso(uso) {
    if (!uso) return;
    const pct = Math.min(100, Math.round((uso.bytes / uso.limite) * 100));
    const relleno = $('uso-relleno');
    relleno.style.width = pct + '%';
    relleno.classList.toggle('alto', pct >= 70 && pct < 90);
    relleno.classList.toggle('lleno', pct >= 90);
    relleno.parentElement.setAttribute('aria-valuenow', pct);
    $('uso-texto').textContent = `${tamano(uso.bytes)} de ${Math.round(uso.limite / 1048576)} MB usados (${pct}%)`;
  }

  const plantilla = $('plantilla-tarjeta');
  const galeria = $('galeria');

  function ordenar(lista) {
    const orden = $('orden').value;
    const copia = lista.slice();
    const porFecha = (a, b) => new Date(b.creado) - new Date(a.creado) || b.id - a.id;
    if (orden === 'recientes') copia.sort(porFecha);
    if (orden === 'antiguas') copia.sort((a, b) => -porFecha(a, b));
    if (orden === 'nombre') copia.sort((a, b) => a.titulo.localeCompare(b.titulo, 'es', { numeric: true }));
    if (orden === 'tamano') copia.sort((a, b) => b.tamano - a.tamano);
    return copia;
  }

  function dibujar() {
    const texto = $('buscar').value.trim().toLocaleLowerCase('es');
    visibles = ordenar(imagenes).filter((i) => !texto || i.titulo.toLocaleLowerCase('es').includes(texto));

    galeria.innerHTML = '';
    galeria.setAttribute('aria-busy', 'false');
    const fragmento = document.createDocumentFragment();
    visibles.forEach((img) => fragmento.appendChild(crearTarjeta(img)));
    galeria.appendChild(fragmento);

    const hay = imagenes.length > 0;
    $('herramientas').hidden = !hay;
    $('estado-vacio').hidden = hay;
    $('sin-resultados').hidden = !hay || visibles.length > 0;
    galeria.hidden = !hay;

    const n = imagenes.length;
    const pesoTotal = imagenes.reduce((s, i) => s + i.tamano, 0);
    $('resumen-galeria').textContent = n === 0
      ? 'Aún no hay imágenes.'
      : `${n} ${n === 1 ? 'imagen' : 'imágenes'} · ${tamano(pesoTotal)}`;
    actualizarSeleccion();
  }

  function crearTarjeta(img) {
    const li = plantilla.content.firstElementChild.cloneNode(true);
    li.dataset.id = img.id;
    const foto = li.querySelector('img');
    foto.src = img.mini;
    foto.alt = img.titulo;
    li.querySelector('.tarjeta-imagen__ver').setAttribute('aria-label', 'Ver ' + img.titulo + ' en grande');
    li.querySelector('.tarjeta-imagen__titulo').textContent = img.titulo;
    li.querySelector('.tarjeta-imagen__titulo').title = img.titulo;
    li.querySelector('.tarjeta-imagen__meta').textContent = `${img.tipo} · ${img.ancho}×${img.alto} · ${tamano(img.tamano)} · ${fecha(img.creado)}`;
    const descarga = li.querySelector('[data-accion="descargar"]');
    descarga.href = img.descargar;
    const casilla = li.querySelector('[data-accion="seleccionar"]');
    casilla.checked = seleccion.has(img.id);
    casilla.nextElementSibling.textContent = 'Seleccionar ' + img.titulo;
    li.classList.toggle('seleccionada', casilla.checked);
    return li;
  }

  galeria.addEventListener('click', (e) => {
    const boton = e.target.closest('[data-accion]');
    if (!boton) return;
    const li = boton.closest('li[data-id]');
    const id = Number(li.dataset.id);
    const img = imagenes.find((i) => i.id === id);
    switch (boton.dataset.accion) {
      case 'ver':
        abrirVisor(visibles.findIndex((i) => i.id === id));
        break;
      case 'renombrar':
        pedirTitulo(img);
        break;
      case 'borrar':
        borrar([id], `Se borrará “${img.titulo}”. Esta acción no se puede deshacer.`);
        break;
    }
  });

  galeria.addEventListener('change', (e) => {
    if (e.target.dataset.accion !== 'seleccionar') return;
    const li = e.target.closest('li[data-id]');
    const id = Number(li.dataset.id);
    e.target.checked ? seleccion.add(id) : seleccion.delete(id);
    li.classList.toggle('seleccionada', e.target.checked);
    actualizarSeleccion();
  });

  function actualizarSeleccion() {
    const idsVisibles = new Set(visibles.map((i) => i.id));
    seleccion.forEach((id) => idsVisibles.has(id) || seleccion.delete(id));
    const n = seleccion.size;
    $('contador-seleccion').textContent = n ? `${n} ${n === 1 ? 'seleccionada' : 'seleccionadas'}` : '';
    $('borrar-seleccionadas').disabled = n === 0;
    const todas = $('seleccionar-todas');
    todas.checked = n > 0 && n === visibles.length;
    todas.indeterminate = n > 0 && n < visibles.length;
  }

  $('seleccionar-todas').addEventListener('change', (e) => {
    seleccion.clear();
    if (e.target.checked) visibles.forEach((i) => seleccion.add(i.id));
    dibujar();
  });

  $('borrar-seleccionadas').addEventListener('click', () => {
    const n = seleccion.size;
    borrar([...seleccion], `Se ${n === 1 ? 'borrará 1 imagen' : 'borrarán ' + n + ' imágenes'}. Esta acción no se puede deshacer.`);
  });

  $('buscar').addEventListener('input', dibujar);
  $('orden').addEventListener('change', dibujar);

  async function borrar(ids, texto) {
    const ok = await App.confirmar({
      titulo: ids.length === 1 ? '¿Borrar imagen?' : '¿Borrar imágenes?',
      texto,
      boton: 'Borrar',
    });
    if (!ok) return;

    ids.forEach((id) => {
      const li = galeria.querySelector(`li[data-id="${id}"]`);
      if (li) li.classList.add('saliendo');
    });
    const r = await postJson(cfg.borrar, { ids });
    if (!r.ok) {
      App.aviso(r.error || 'No se pudieron borrar las imágenes.', 'error');
      dibujar();
      return;
    }
    const borradas = new Set(r.ids);
    imagenes = imagenes.filter((i) => !borradas.has(i.id));
    borradas.forEach((id) => seleccion.delete(id));
    mostrarUso(r.uso);
    setTimeout(dibujar, 150);
    App.aviso(r.ids.length === 1 ? 'Imagen eliminada.' : `Se eliminaron ${r.ids.length} imágenes.`);
  }

  const dialogoTitulo = $('dialogo-titulo-imagen');
  function pedirTitulo(img) {
    const input = $('nuevo-titulo');
    input.value = img.titulo;
    dialogoTitulo.returnValue = '';
    dialogoTitulo.showModal();
    input.select();
    dialogoTitulo.addEventListener('close', async () => {
      const titulo = input.value.trim();
      if (dialogoTitulo.returnValue !== 'guardar' || !titulo || titulo === img.titulo) return;
      const r = await postJson(cfg.renombrar, { id: img.id, titulo });
      if (!r.ok) {
        App.aviso(r.error || 'No se pudo cambiar el título.', 'error');
        return;
      }
      Object.assign(img, r.imagen);
      dibujar();
      App.aviso('Título actualizado.');
    }, { once: true });
  }

  const visor = $('visor');
  let inicioToque = null;

  function abrirVisor(i) {
    if (i < 0 || !visibles.length) return;
    indiceVisor = i;
    mostrarEnVisor();
    if (!visor.open) visor.showModal();
    $('visor-cerrar').focus();
  }

  function mostrarEnVisor() {
    const img = visibles[indiceVisor];
    const foto = $('visor-imagen');
    foto.src = img.url;
    foto.alt = img.titulo;
    $('visor-titulo').textContent = img.titulo;
    $('visor-meta').textContent = `${img.original} · ${img.ancho}×${img.alto} · ${tamano(img.tamano)}`;
    $('visor-descargar').href = img.descargar;
    $('visor-posicion').textContent = `${indiceVisor + 1} de ${visibles.length}`;
    const una = visibles.length < 2;
    $('visor-anterior').hidden = una;
    $('visor-siguiente').hidden = una;

    const sig = visibles[(indiceVisor + 1) % visibles.length];
    if (sig) new Image().src = sig.url;
  }

  function moverVisor(paso) {
    indiceVisor = (indiceVisor + paso + visibles.length) % visibles.length;
    mostrarEnVisor();
  }

  $('visor-anterior').addEventListener('click', () => moverVisor(-1));
  $('visor-siguiente').addEventListener('click', () => moverVisor(1));
  $('visor-cerrar').addEventListener('click', () => visor.close());
  visor.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') moverVisor(-1);
    if (e.key === 'ArrowRight') moverVisor(1);
  });

  visor.addEventListener('touchstart', (e) => (inicioToque = e.touches[0].clientX), { passive: true });
  visor.addEventListener('touchend', (e) => {
    if (inicioToque === null) return;
    const dx = e.changedTouches[0].clientX - inicioToque;
    if (Math.abs(dx) > 50) moverVisor(dx < 0 ? 1 : -1);
    inicioToque = null;
  });
  visor.addEventListener('close', () => ($('visor-imagen').src = ''));

  const entrada = $('entrada-archivos');
  if (entrada) {
    const zona = $('zona-subida');

    function problemaDe(f) {
      if (!TIPOS.includes(f.type)) return 'Formato no permitido';
      if (f.size > cfg.maxMb * 1048576) return `Pesa más de ${cfg.maxMb} MB`;
      return '';
    }

    function agregarACola(archivos) {
      for (const f of archivos) {
        if (cola.some((c) => c.archivo.name === f.name && c.archivo.size === f.size)) continue;
        cola.push({ archivo: f, problema: problemaDe(f) });
      }
      dibujarCola();
    }

    function dibujarCola() {
      const lista = $('cola-lista');
      lista.innerHTML = '';
      cola.forEach((c, i) => {
        const li = document.createElement('li');
        li.className = 'cola__elemento' + (c.problema ? ' con-error' : '');
        if (!c.problema) {
          const img = document.createElement('img');
          img.alt = '';
          img.src = URL.createObjectURL(c.archivo);
          img.onload = () => URL.revokeObjectURL(img.src);

          img.onerror = () => {
            c.problema = 'No es una imagen válida';
            dibujarCola();
          };
          li.appendChild(img);
        } else {
          const sin = document.createElement('span');
          sin.className = 'cola__sin-vista';
          li.appendChild(sin);
        }
        const textos = document.createElement('span');
        textos.className = 'cola__textos';
        const nombre = document.createElement('span');
        nombre.className = 'cola__nombre';
        nombre.textContent = c.archivo.name;
        const detalle = document.createElement('span');
        detalle.className = 'cola__detalle';
        detalle.textContent = c.problema || tamano(c.archivo.size);
        textos.append(nombre, detalle);
        li.appendChild(textos);
        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'cola__quitar';
        quitar.setAttribute('aria-label', 'Quitar ' + c.archivo.name);
        quitar.textContent = '×';
        quitar.addEventListener('click', () => {
          cola.splice(i, 1);
          dibujarCola();
        });
        li.appendChild(quitar);
        lista.appendChild(li);
      });

      const validos = cola.filter((c) => !c.problema).length;
      $('cola').hidden = cola.length === 0;
      const boton = $('cola-subir');
      const demasiadas = validos > cfg.maxArchivos;
      boton.disabled = validos === 0 || demasiadas;
      boton.textContent = demasiadas
        ? `Máximo ${cfg.maxArchivos} a la vez`
        : validos === 0 ? 'Subir imágenes' : validos === 1 ? 'Subir 1 imagen' : `Subir ${validos} imágenes`;
    }

    entrada.addEventListener('change', () => {
      agregarACola(entrada.files);
      entrada.value = '';
    });
    ['dragenter', 'dragover'].forEach((ev) =>
      zona.addEventListener(ev, (e) => {
        e.preventDefault();
        zona.classList.add('encima');
      })
    );
    ['dragleave', 'drop'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.remove('encima')));
    zona.addEventListener('drop', (e) => {
      e.preventDefault();
      agregarACola(e.dataTransfer.files);
    });

    window.addEventListener('dragover', (e) => e.preventDefault());
    window.addEventListener('drop', (e) => e.preventDefault());

    $('cola-limpiar').addEventListener('click', () => {
      cola = [];
      dibujarCola();
    });

    $('cola-subir').addEventListener('click', () => {
      const validos = cola.filter((c) => !c.problema);
      if (!validos.length) return;
      const datos = new FormData();
      validos.forEach((c) => datos.append('imagenes[]', c.archivo));

      const boton = $('cola-subir');
      const progreso = $('progreso');
      boton.disabled = true;
      $('cola-limpiar').disabled = true;
      progreso.hidden = false;

      const xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.subir);
      xhr.setRequestHeader('X-CSRF-Token', cfg.csrf);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.addEventListener('progress', (e) => {
        if (!e.lengthComputable) return;
        const pct = Math.round((e.loaded / e.total) * 100);
        $('progreso-relleno').style.width = pct + '%';
        $('progreso-texto').textContent = pct < 100 ? `Subiendo… ${pct}%` : 'Procesando imágenes…';
      });
      xhr.addEventListener('loadend', () => {
        let r = {};
        try {
          r = JSON.parse(xhr.responseText);
        } catch (e) {
          r = { ok: false, error: 'No se pudo completar la subida.' };
        }
        progreso.hidden = true;
        $('progreso-relleno').style.width = '0';
        $('cola-limpiar').disabled = false;

        if (r.imagenes && r.imagenes.length) {
          imagenes = r.imagenes.concat(imagenes);
          const n = r.imagenes.length;
          App.aviso(n === 1 ? 'Se subió 1 imagen.' : `Se subieron ${n} imágenes.`);
          cola = cola.filter((c) => c.problema);
        }
        (r.errores || []).forEach((m) => App.aviso(m, 'error'));
        if (!r.ok && r.error) App.aviso(r.error, 'error');
        if (xhr.status === 401) setTimeout(() => (location.href = 'index.php?r=login'), 1500);
        mostrarUso(r.uso);
        dibujarCola();
        dibujar();
      });
      xhr.send(datos);
    });
  }

  api(cfg.listar).then((r) => {
    if (!r.ok) {
      galeria.innerHTML = '';
      $('resumen-galeria').textContent = r.error || 'No se pudieron cargar las imágenes.';
      return;
    }
    imagenes = r.imagenes;
    mostrarUso(r.uso);
    dibujar();
  });
})();
