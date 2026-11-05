export function createSosButton(root, { onActivate, holdMs = 2000 } = {}) {
  const progress = root.querySelector('.sos-hold__progress');
  const radius = progress ? Number(progress.getAttribute('r')) || 74 : 74;
  const circumference = 2 * Math.PI * radius;
  if (progress) {
    progress.style.strokeDasharray = String(circumference);
    progress.style.strokeDashoffset = String(circumference);
  }
  let start = 0;
  let raf = 0;
  let armed = false;

  function tick(now) {
    const elapsed = now - start;
    const pct = Math.min(elapsed / holdMs, 1);
    if (progress) progress.style.strokeDashoffset = String(circumference * (1 - pct));
    if (pct >= 1) {
      release(false);
      armed = true;
      onActivate?.();
    } else {
      raf = requestAnimationFrame(tick);
    }
  }

  function press(event) {
    if (event.pointerType === 'mouse' && event.button !== 0) return;
    if (start) return;
    armed = false;
    start = performance.now();
    root.classList.add('sos-hold--holding');
    raf = requestAnimationFrame(tick);
  }

  function release(resetVisual = true) {
    cancelAnimationFrame(raf);
    raf = 0;
    start = 0;
    root.classList.remove('sos-hold--holding');
    if (resetVisual && progress) progress.style.strokeDashoffset = String(circumference);
  }

  root.addEventListener('pointerdown', press);
  root.addEventListener('pointerup', () => release(true));
  root.addEventListener('pointercancel', () => release(true));
  root.addEventListener('pointerleave', () => {
    if (start) release(true);
  });
  root.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      armed = true;
      onActivate?.();
    }
  });
  root.addEventListener('click', (event) => {
    event.preventDefault();
  });

  return {
    consume() {
      const ok = armed;
      armed = false;
      return ok;
    },
    arm() {
      armed = true;
    },
    destroy() {
      release(true);
    },
  };
}
