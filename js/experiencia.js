document.addEventListener('DOMContentLoaded', () => {
  const caso = document.querySelector('.caso-card-destacado');
  const toggle = document.querySelector('.caso-toggle');

  if (!caso || !toggle) return;

  const textoAbierto = 'Cerrar caso';
  const textoCerrado = 'Ver desarrollo del caso';

  toggle.addEventListener('click', () => {
    const isOpen = caso.classList.toggle('is-open');

    caso.classList.toggle('active', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.textContent = isOpen ? textoAbierto : textoCerrado;
  });
});
