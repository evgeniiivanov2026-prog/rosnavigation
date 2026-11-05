const toggle = document.querySelector("#theme-toggle");

function applyTheme(light) {
  document.documentElement.toggleAttribute("data-theme", light);
  if (light) document.documentElement.setAttribute("data-theme", "light");
  else document.documentElement.removeAttribute("data-theme");
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.content = light ? "#F4F6F8" : "#0A0E13";
  if (toggle) {
    toggle.setAttribute("aria-pressed", light ? "true" : "false");
    toggle.setAttribute("aria-label", light ? "Тёмная тема" : "Светлая тема");
  }
}

applyTheme(document.documentElement.getAttribute("data-theme") === "light");
toggle?.addEventListener("click", () => {
  const light = document.documentElement.getAttribute("data-theme") !== "light";
  applyTheme(light);
  try {
    localStorage.setItem("rn-theme", light ? "light" : "dark");
  } catch (error) {
    /* память браузера может быть закрыта */
  }
});

document.querySelectorAll(".toast").forEach((toast) => {
  window.setTimeout(() => toast.remove(), 7000);
});

const install = document.querySelector("#install-app");
let deferredPrompt = null;
window.addEventListener("beforeinstallprompt", (event) => {
  event.preventDefault();
  deferredPrompt = event;
  if (install) install.hidden = false;
});
install?.addEventListener("click", async () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  await deferredPrompt.userChoice;
  deferredPrompt = null;
  install.hidden = true;
});

if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register("/sw.js").catch(() => {});
  });
}
