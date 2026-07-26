document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.nav');
  const toggle = document.querySelector('.nav-toggle');
  const menu = document.querySelector('.nav-menu');

  if (!header || !toggle || !menu) return;

  const setMenuState = (isOpen, returnFocus = false) => {
    header.classList.toggle('is-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');

    if (returnFocus) toggle.focus();
  };

  toggle.addEventListener('click', () => {
    setMenuState(!header.classList.contains('is-open'));
  });

  menu.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a') : null;
    if (link) setMenuState(false);
  });

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) setMenuState(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && header.classList.contains('is-open')) {
      setMenuState(false, true);
    }
  });

  const desktopQuery = window.matchMedia('(min-width: 1025px)');
  const closeOnDesktop = (event) => {
    if (event.matches) setMenuState(false);
  };

  if (desktopQuery.addEventListener) {
    desktopQuery.addEventListener('change', closeOnDesktop);
  } else {
    desktopQuery.addListener(closeOnDesktop);
  }
});
