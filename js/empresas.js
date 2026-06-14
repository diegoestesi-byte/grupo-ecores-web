document.addEventListener('DOMContentLoaded', () => {
  const pista = document.querySelector('.empresas-pista');
  const panels = Array.from(document.querySelectorAll('.empresa-panel'));
  const prev = document.querySelector('.empresas-control.prev');
  const next = document.querySelector('.empresas-control.next');
  const indicadores = Array.from(document.querySelectorAll('.empresas-indicadores span'));

  if (!pista || panels.length === 0 || !prev || !next) return;

  let activeIndex = 0;
  let scrollTimer;

  const setActive = (index) => {
    activeIndex = Math.max(0, Math.min(index, panels.length - 1));
    indicadores.forEach((indicador, indicadorIndex) => {
      indicador.classList.toggle('active', indicadorIndex === activeIndex);
    });
  };

  const scrollToPanel = (index) => {
    const targetIndex = Math.max(0, Math.min(index, panels.length - 1));

    panels[targetIndex].scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
      inline: 'center'
    });

    setActive(targetIndex);
  };

  const updateFromScroll = () => {
    const pistaRect = pista.getBoundingClientRect();
    const pistaCenter = pistaRect.left + pistaRect.width / 2;

    const closestIndex = panels.reduce((closest, panel, index) => {
      const panelRect = panel.getBoundingClientRect();
      const panelCenter = panelRect.left + panelRect.width / 2;
      const distance = Math.abs(panelCenter - pistaCenter);

      return distance < closest.distance ? { index, distance } : closest;
    }, { index: activeIndex, distance: Number.POSITIVE_INFINITY }).index;

    setActive(closestIndex);
  };

  next.addEventListener('click', () => scrollToPanel(activeIndex + 1));
  prev.addEventListener('click', () => scrollToPanel(activeIndex - 1));

  pista.addEventListener('scroll', () => {
    window.clearTimeout(scrollTimer);
    scrollTimer = window.setTimeout(updateFromScroll, 80);
  }, { passive: true });

  window.addEventListener('resize', updateFromScroll);

  setActive(0);
});
