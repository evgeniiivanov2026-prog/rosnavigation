export function createSosButton(root, { onActivate, holdMs = 2000 } = {}) {
  let start = 0;
  let raf = 0;
  const progress = root.querySelector('.sos-button__progress');
  const radius = progress ? Number(progress.getAttribute('r')) || 70 : 70;
  const circumference = 2 * Math.PI * radius;
  if (progress) {
    progress.style.strokeDasharray = String(circumference);
    progress.style.strokeDashoffset = String(circumference);
  }

  function tick(now) {
    const elapsed = now - start;
    const pct = Math.min(elapsed / holdMs, 1);
    if (progress) progress.style.strokeDashoffset = String(circumference * (1 - pct));
    if (pct >= 1) {
      release();
      onActivate?.();
    } else {
      raf = requestAnimationFrame(tick);
    }
  }

  function press(e) {
    if (e.button !== undefined && e.button !== 0) return;
    if (start) return;
    start = performance.now();
    root.classList.add('sos-button--holding');
    if (root.setPointerCapture && e.pointerId !== undefined) {
      try { root.setPointerCapture(e.pointerId); } catch (err) { /* уже захвачен */ }
    }
    raf = requestAnimationFrame(tick);
  }

  function release() {
    cancelAnimationFrame(raf);
    raf = 0;
    start = 0;
    root.classList.remove('sos-button--holding');
    if (progress) progress.style.strokeDashoffset = String(circumference);
  }

  root.addEventListener('pointerdown', press);
  root.addEventListener('pointerup', release);
  root.addEventListener('pointercancel', release);

  root.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (e.repeat) return;
      onActivate?.();
    }
  });

  return { destroy() { release(); } };
}
