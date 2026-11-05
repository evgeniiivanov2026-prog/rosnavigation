export function startPolling(fn, intervalMs = 30000) {
  let timer = 0;
  let stopped = false;

  async function tick() {
    if (stopped) return;
    try {
      await fn();
    } catch (error) {
      console.warn("[poll]", error);
    }
    if (!stopped) timer = window.setTimeout(tick, intervalMs);
  }

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      window.clearTimeout(timer);
      return;
    }
    if (!stopped) tick();
  });

  tick();
  return {
    stop() {
      stopped = true;
      window.clearTimeout(timer);
    },
  };
}
