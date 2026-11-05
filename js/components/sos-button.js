export function createSosButton(root, { onActivate, holdMs = 2000 } = {}) {
  const bar = root.querySelector(".sos-hold__bar");
  const ring = 2 * Math.PI * 70;
  let start = 0;
  let frame = 0;
  let origin = null;
  if (bar) {
    bar.style.strokeDasharray = String(ring);
    bar.style.strokeDashoffset = String(ring);
  }

  function paint(progress) {
    if (!bar) return;
    bar.style.strokeDashoffset = String(ring * (1 - progress));
    root.setAttribute("aria-valuenow", String(Math.round(progress * 100)));
  }

  function release() {
    cancelAnimationFrame(frame);
    frame = 0;
    start = 0;
    origin = null;
    root.classList.remove("is-holding");
    paint(0);
  }

  function tick() {
    const progress = Math.min((performance.now() - start) / holdMs, 1);
    paint(progress);
    if (progress >= 1) {
      release();
      onActivate?.();
      return;
    }
    frame = requestAnimationFrame(tick);
  }

  function press(event) {
    if (root.classList.contains("is-locked") || start) return;
    if (event.button !== undefined && event.button !== 0) return;
    start = performance.now();
    origin = [event.clientX, event.clientY];
    root.classList.add("is-holding");
    if (root.setPointerCapture && event.pointerId !== undefined) {
      root.setPointerCapture(event.pointerId);
    }
    frame = requestAnimationFrame(tick);
  }

  function move(event) {
    if (!origin) return;
    const dx = event.clientX - origin[0];
    const dy = event.clientY - origin[1];
    if (Math.hypot(dx, dy) > 64) release();
  }

  root.addEventListener("pointerdown", press);
  root.addEventListener("pointermove", move);
  root.addEventListener("pointerup", release);
  root.addEventListener("pointercancel", release);
  root.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      if (!root.classList.contains("is-locked")) onActivate?.();
    }
  });

  return { destroy: release };
}
