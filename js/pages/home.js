import { api } from "/js/utils/api.js";
import { startPolling } from "/js/utils/poll.js";

const count = document.querySelector("#live-count");
if (count) {
  startPolling(async () => {
    const data = await api("/api/poll.php");
    count.textContent = String(data.online);
    const air = document.querySelector("#live-air");
    const helped = document.querySelector("#live-helped");
    if (air) air.textContent = String(data.on_air);
    if (helped) helped.textContent = String(data.helped);
  }, 30000);
}
