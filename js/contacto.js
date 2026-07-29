(function () {
  'use strict';

  const formulario = document.getElementById('formulario-contacto');
  const estado = document.getElementById('formulario-estado');
  const inicio = document.getElementById('inicio-formulario');
  const boton = document.getElementById('enviar-consulta');

  if (!formulario || !estado || !inicio || !boton) return;

  inicio.value = String(Math.floor(Date.now() / 1000));

  const mensajes = {
    enviado: {
      tipo: 'exito',
      texto: 'Tu consulta fue enviada correctamente. Recibirás una respuesta del equipo de Grupo ECORES.'
    },
    invalido: {
      tipo: 'error',
      texto: 'Revisa los campos obligatorios e intenta nuevamente.'
    },
    limite: {
      tipo: 'error',
      texto: 'La consulta no pudo enviarse todavía. Espera unos segundos e intenta nuevamente.'
    },
    error: {
      tipo: 'error',
      texto: 'No pudimos enviar tu consulta. También puedes escribir a pbravo@grupoecores.cl.'
    }
  };

  const url = new URL(window.location.href);
  const resultado = mensajes[url.searchParams.get('estado')];

  if (resultado) {
    estado.textContent = resultado.texto;
    estado.classList.add(`formulario-estado--${resultado.tipo}`);
    estado.hidden = false;
    window.requestAnimationFrame(function () {
      estado.classList.add('formulario-estado--visible');
    });
    estado.scrollIntoView({ block: 'nearest' });

    url.searchParams.delete('estado');
    window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);

    if (resultado.tipo === 'exito') {
      window.setTimeout(function () {
        estado.classList.remove('formulario-estado--visible');
        window.setTimeout(function () {
          estado.hidden = true;
        }, 350);
      }, 7000);
    }
  }

  formulario.addEventListener('submit', function () {
    if (!formulario.checkValidity()) return;

    boton.disabled = true;
    boton.textContent = 'Enviando…';
    boton.setAttribute('aria-busy', 'true');
  });
}());
