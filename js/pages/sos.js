import { api } from "/js/utils/api.js";
import { locate } from "/js/utils/geo.js";
import { createSosButton } from "/js/components/sos-button.js";
import { chirp } from "/js/components/radio-sound.js";

const form = document.querySelector("#sos-form");
const button = document.querySelector("#sos-hold");
const label = document.querySelector("#point-label");
const latInput = document.querySelector("#lat");
const lngInput = document.querySelector("#lng");
const errorBox = document.querySelector("#sos-error");
let map;
let pin;
let sending = false;

function say(text) {
  if (!errorBox) return;
  errorBox.hidden = !text;
  errorBox.textContent = text || "";
}

function setPoint(lat, lng, source) {
  if (latInput) latInput.value = lat.toFixed(5);
  if (lngInput) lngInput.value = lng.toFixed(5);
  if (label) {
    label.textContent = source + ": " + lat.toFixed(4) + ", " + lng.toFixed(4);
  }
  if (map && pin) {
    const ll = [lat, lng];
    pin.setLatLng(ll);
    map.panTo(ll);
  }
}

function selectedType() {
  return form?.querySelector('input[name="type"]:checked')?.value || "";
}

async function send() {
  if (!form || sending) return;
  const type = selectedType();
  const lat = Number(latInput?.value);
  const lng = Number(lngInput?.value);
  if (!type) {
    say("Выбери, что случилось.");
    form.querySelector('input[name="type"]')?.focus();
    return;
  }
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    say("Нужна точка. Разреши геолокацию или поставь метку на карте.");
    return;
  }
  say("");
  sending = true;
  if (button) button.classList.add("is-locked");
  try {
    const data = await api("/api/sos.php?action=create", {
      method: "POST",
      body: {
        type,
        lat,
        lng,
        route: form.route?.value || "",
        km: form.km?.value || "",
        comment: form.comment?.value || "",
      },
    });
    chirp();
    window.location.href = "/sos?sent=" + data.sos_id;
  } catch (error) {
    sending = false;
    button?.classList.remove("is-locked");
    if (error.status === 401) {
      window.location.href = "/auth?next=" + encodeURIComponent("/sos");
      return;
    }
    say(error.message || "Сигнал не ушёл.");
  }
}

if (button && form) {
  button.type = "button";
  createSosButton(button, { onActivate: send, holdMs: 2000 });
}

function tiles() {
  return L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap",
    maxZoom: 19,
  });
}

function dot() {
  return L.divIcon({
    className: "pin-marker pin-marker--sos",
    html: "<span></span>",
    iconSize: [18, 18],
    iconAnchor: [9, 9],
  });
}

const canvas = document.querySelector("#sos-map");
if (canvas && window.L && form) {
  map = L.map(canvas, { zoomControl: true, attributionControl: true }).setView([55.75, 49.1], 5);
  tiles().addTo(map);
  pin = L.marker([55.75, 49.1], { draggable: true, icon: dot() }).addTo(map);
  pin.on("dragend", () => {
    const ll = pin.getLatLng();
    setPoint(ll.lat, ll.lng, "Метка");
  });
  map.on("click", (event) => setPoint(event.latlng.lat, event.latlng.lng, "Метка"));
  window.setTimeout(() => map.invalidateSize(), 200);
  locate()
    .then((pos) => setPoint(pos.lat, pos.lng, "С телефона"))
    .catch(() => {
      if (label && !latInput?.value) {
        label.textContent = "Телефон не отдал точку. Поставь метку на карте или впиши координаты.";
      }
    });
}

const done = document.querySelector("#sos-done-map");
if (done && window.L) {
  const lat = Number(done.dataset.lat);
  const lng = Number(done.dataset.lng);
  if (Number.isFinite(lat) && Number.isFinite(lng)) {
    const view = L.map(done, { zoomControl: true, attributionControl: true }).setView([lat, lng], 11);
    tiles().addTo(view);
    L.marker([lat, lng], { icon: dot() }).addTo(view);
    window.setTimeout(() => view.invalidateSize(), 200);
  }
}
