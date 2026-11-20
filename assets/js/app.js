(function () {
  'use strict';

  const $ = (sel, el = document) => el.querySelector(sel);
  const $$ = (sel, el = document) => Array.from(el.querySelectorAll(sel));

  function aviso(mensaje, tipo = 'exito') {
    const zona = $('#avisos-flotantes');
    if (!zona) return;
    const div = document.createElement('div');
    div.className = 'aviso-flotante' + (tipo === 'error' ? ' aviso-flotante--error' : '');
    div.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
    div.textContent = mensaje;
    zona.appendChild(div);
    setTimeout(() => div.remove(), tipo === 'error' ? 6000 : 3500);
  }

  function confirmar({ titulo = '¿Confirmar?', texto = '', boton = 'Aceptar' } = {}) {
    const d = $('#dialogo-confirmar');
    if (!d || typeof d.showModal !== 'function') {
      return Promise.resolve(window.confirm(texto || titulo));
    }
    $('#dialogo-titulo', d).textContent = titulo;
    $('#dialogo-texto', d).textContent = texto;
    $('#dialogo-aceptar', d).textContent = boton;
    d.returnValue = '';
    d.showModal();
    $('[value="cancelar"]', d).focus();
    return new Promise((resolver) => {
      d.addEventListener('close', () => resolver(d.returnValue === 'aceptar'), { once: true });
    });
  }

  window.App = { aviso, confirmar };

  const botonMenu = $('.menu-boton');
  const menu = $('#menu-principal');
  if (botonMenu && menu) {
    botonMenu.addEventListener('click', () => {
      const abierto = menu.classList.toggle('abierto');
      botonMenu.setAttribute('aria-expanded', String(abierto));
    });
  }

  $$('.aviso__cerrar').forEach((b) => b.addEventListener('click', () => b.closest('.aviso').remove()));

  $$('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      if (form.dataset.confirmado) return;
      e.preventDefault();
      const ok = await confirmar({
        titulo: form.dataset.confirmarTitulo || '¿Confirmar?',
        texto: form.dataset.confirmar,
        boton: form.dataset.confirmarBoton || 'Aceptar',
      });
      if (ok) {
        form.dataset.confirmado = '1';
        form.submit();
      }
    });
  });

  $$('.ver-clave').forEach((b) => {
    b.addEventListener('click', () => {
      const input = b.previousElementSibling;
      const mostrar = input.type === 'password';
      input.type = mostrar ? 'text' : 'password';
      b.textContent = mostrar ? 'Ocultar' : 'Ver';
      b.setAttribute('aria-pressed', String(mostrar));
      b.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  $$('input[data-fuerza]').forEach((input) => {
    const ayuda = $('#fuerza-clave');
    const textoOriginal = ayuda ? ayuda.textContent : '';
    input.addEventListener('input', () => {
      if (!ayuda) return;
      const v = input.value;
      ayuda.classList.remove('debil', 'media', 'fuerte');
      if (!v) {
        ayuda.textContent = textoOriginal;
        return;
      }
      let puntos = 0;
      if (v.length >= 8) puntos++;
      if (v.length >= 12) puntos++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) puntos++;
      if (/\d/.test(v)) puntos++;
      if (/[^A-Za-z0-9]/.test(v)) puntos++;
      const valida = v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v);
      if (!valida) {
        ayuda.textContent = 'Aún no cumple: mínimo 8 caracteres, con letras y números.';
        ayuda.classList.add('debil');
      } else if (puntos >= 4) {
        ayuda.textContent = 'Contraseña segura.';
        ayuda.classList.add('fuerte');
      } else {
        ayuda.textContent = 'Contraseña aceptable. Agrega mayúsculas o símbolos para hacerla más segura.';
        ayuda.classList.add('media');
      }
    });
  });

  function mensajePara(input) {
    const v = input.validity;
    if (input.dataset.igualA) {
      const otro = document.getElementById(input.dataset.igualA);
      if (otro && input.value !== otro.value) return 'Las contraseñas no coinciden.';
    }
    if (v.valueMissing) return 'Este campo es obligatorio.';
    if (v.typeMismatch && input.type === 'email') return 'Escribe un correo válido, por ejemplo nombre@correo.com.';
    if (v.tooShort) return `Escribe al menos ${input.minLength} caracteres.`;
    if (v.patternMismatch) return 'Usa solo letras, números, punto, guion o guion bajo.';
    return '';
  }

  function mostrarError(input, mensaje) {
    const campo = input.closest('.campo');
    if (!campo) return;
    let p = campo.querySelector('.campo__error[data-cliente]');
    const delServidor = campo.querySelector('.campo__error:not([data-cliente])');
    if (delServidor && mensaje) delServidor.remove();
    if (!mensaje) {
      if (p) p.remove();
      input.removeAttribute('aria-invalid');
      return;
    }
    if (!p) {
      p = document.createElement('p');
      p.className = 'campo__error';
      p.dataset.cliente = '1';
      p.id = 'error-cliente-' + input.id;
      campo.appendChild(p);
    }
    p.textContent = mensaje;
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-describedby', p.id);
  }

  $$('form[data-validar]').forEach((form) => {
    const campos = $$('input', form).filter((i) => !['hidden', 'checkbox', 'radio'].includes(i.type));
    campos.forEach((input) => {
      input.addEventListener('blur', () => input.value && mostrarError(input, mensajePara(input)));
      input.addEventListener('input', () => input.getAttribute('aria-invalid') && mostrarError(input, mensajePara(input)));
    });
    form.addEventListener('submit', (e) => {
      let primero = null;
      campos.forEach((input) => {
        const m = mensajePara(input);
        mostrarError(input, m);
        if (m && !primero) primero = input;
      });
      if (primero) {
        e.preventDefault();
        primero.focus();
      } else {
        const b = form.querySelector('[type="submit"]');
        if (b) {
          b.disabled = true;
          b.textContent = 'Enviando…';
        }
      }
    });
  });
})();
