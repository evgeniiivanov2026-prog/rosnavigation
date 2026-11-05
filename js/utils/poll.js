export function startPolling(fn, intervalMs = 30000) {
  let timer = null;
  let stopped = false;

  async function tick() {
    if (stopped) return;
    try {
      await fn();
    } catch (error) {
      console.warn('[poll]', error);
    }
    if (!stopped) timer = setTimeout(tick, intervalMs);
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearTimeout(timer);
      return;
    }
    if (!stopped) tick();
  });

  tick();
  return {
    stop() {
      stopped = true;
      clearTimeout(timer);
    },
  };
}
