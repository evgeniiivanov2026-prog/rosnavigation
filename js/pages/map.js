import { api, csrf } from "/js/utils/api.js";
import { locate } from "/js/utils/geo.js";
import { startPolling } from "/js/utils/poll.js";

const canvas = document.querySelector("#help-map");
const list = document.querySelector("#map-list");
const card = document.querySelector("#map-card");
const offline = document.querySelector("#offline-note");
const filters = document.querySelector("#map-filters");
if (!canvas || !window.L || !list) {
  if (offline) {
    offline.hidden = false;
    offline.textContent = "Карта не собралась. Список ниже всё ещё можно читать.";
  }
} else {
  const params = new URLSearchParams(window.location.search);
  const viewer = Number(document.body.dataset.user || 0);
  let here = null;
  let payload = { sos: [], helpers: [] };
  let radius = 0;
  let mode = "all";

  const map = L.map(canvas, { zoomControl: true }).setView([56.5, 44], 5);
  const tiles = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap",
    maxZoom: 19,
  }).addTo(map);
  tiles.on("tileerror", () => {
    if (offline) {
      offline.hidden = false;
      offline.textContent = "Подложка карты не загрузилась. Точки и список на месте.";
    }
  });
  const group = L.layerGroup().addTo(map);

  function items() {
    const sos = (payload.sos || []).map((row) => ({ ...row, kind: "sos" }));
    const helpers = (payload.helpers || []).map((row) => ({ ...row, kind: row.status || "on_air" }));
    return [...sos, ...helpers].filter((item) => {
      if (mode === "sos" && item.kind !== "sos") return false;
      if (mode === "on_air" && item.kind !== "on_air") return false;
      if (mode === "on_route" && item.kind !== "on_route") return false;
      if (radius && here && item.distance_km != null && item.distance_km > radius) return false;
      return item.lat != null && item.lng != null;
    });
  }

  function marker(item) {
    if (item.kind === "cluster") {
      return L.divIcon({
        className: "pin-cluster",
        html: "<span>" + escapeHtml(item.count + (item.route ? " · " + item.route : "")) + "</span>",
        iconSize: [72, 36],
        iconAnchor: [36, 18],
      });
    }
    const kind = item.kind === "sos" ? "sos" : item.kind;
    return L.divIcon({
      className: "pin-marker pin-marker--" + kind,
      html: "<span></span>",
      iconSize: [18, 18],
      iconAnchor: [9, 9],
    });
  }

  function draw() {
    group.clearLayers();
    const zoom = map.getZoom();
    const buckets = new Map();
    items().forEach((item) => {
      const point = map.project([item.lat, item.lng], zoom);
      const key = Math.round(point.x / 56) + ":" + Math.round(point.y / 56);
      if (!buckets.has(key)) buckets.set(key, []);
      buckets.get(key).push(item);
    });
    buckets.forEach((bucket) => {
      if (bucket.length === 1) {
        const item = bucket[0];
        const pin = L.marker([item.lat, item.lng], { icon: marker(item) });
        pin.on("click", () => focus(item));
        pin.addTo(group);
        return;
      }
      const lat = bucket.reduce((sum, item) => sum + Number(item.lat), 0) / bucket.length;
      const lng = bucket.reduce((sum, item) => sum + Number(item.lng), 0) / bucket.length;
      const routes = [...new Set(bucket.map((item) => item.route).filter(Boolean))];
      const pin = L.marker([lat, lng], {
        icon: marker({ kind: "cluster", count: bucket.length, route: routes.length === 1 ? routes[0] : "" }),
      });
      pin.on("click", () => map.setView([lat, lng], Math.min(zoom + 2, 14)));
      pin.addTo(group);
    });
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[char]));
  }

  function renderList(rows) {
    list.replaceChildren();
    card.replaceChildren();
    if (!rows.length) {
      const empty = document.createElement("div");
      empty.className = "empty";
      const title = document.createElement("h3");
      title.textContent = "На этом плече тихо";
      const text = document.createElement("p");
      text.textContent = "Сними фильтр или расширь радиус. Молчание тоже ответ.";
      empty.append(title, text);
      list.append(empty);
      return;
    }
    rows.forEach((item) => list.append(rowCard(item)));
  }

  function rowCard(item) {
    const article = document.createElement("article");
    article.className = "signal";
    article.id = item.kind === "sos" ? "signal-" + item.id : "driver-" + item.id;
    const top = document.createElement("div");
    top.className = "signal__top";
    const eyebrow = document.createElement("span");
    eyebrow.className = "eyebrow";
    eyebrow.textContent = item.kind === "sos" ? item.type_label || "Сигнал" : statusName(item.status);
    top.append(eyebrow);
    const title = document.createElement("h3");
    const place = [item.route, item.km ? item.km + " км" : ""].filter(Boolean).join(" · ");
    title.textContent = place || item.name || "Точка";
    const text = document.createElement("p");
    text.textContent = item.comment || [item.city, item.truck_brand].filter(Boolean).join(" · ");
    const who = document.createElement("p");
    who.className = "signal__who";
    const person = item.author || item;
    const link = document.createElement("a");
    link.href = "/profile/" + person.id;
    link.textContent = person.name || "";
    who.append(link);
    if (item.distance_km != null) {
      const dist = document.createElement("span");
      dist.textContent = " · " + item.distance_km + " км";
      who.append(dist);
    }
    const actions = document.createElement("div");
    actions.className = "actions";
    const open = document.createElement("button");
    open.type = "button";
    open.className = "btn btn--ghost";
    open.textContent = "Показать";
    open.addEventListener("click", () => focus(item, false));
    actions.append(open);
    if (item.kind === "sos" && viewer && item.status === "active" && item.user_id !== viewer) {
      actions.append(postForm("accept", item.id, "Еду", "btn btn--primary"));
    }
    if (item.kind === "sos" && viewer && item.user_id === viewer) {
      actions.append(postForm("complete", item.id, "Выручили", "btn btn--primary"));
      actions.append(postForm("cancel", item.id, "Снять сигнал", "btn btn--ghost"));
    }
    article.append(top, title, text, who, actions);
    return article;
  }

  function postForm(action, id, label, className) {
    const form = document.createElement("form");
    form.method = "post";
    form.action = "/map";
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      try {
        await api("/api/sos.php?action=" + action, { method: "POST", body: { sos_id: id } });
        await refresh();
      } catch (error) {
        if (error.status === 401) {
          window.location.href = "/auth?next=" + encodeURIComponent("/map");
          return;
        }
        window.alert(error.message);
      }
    });
    ["csrf", "action", "sos_id", "back"].forEach((name) => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = name;
      input.value = name === "csrf" ? csrf() : name === "action" ? action : name === "sos_id" ? String(id) : "/map";
      form.append(input);
    });
    const button = document.createElement("button");
    button.className = className;
    button.type = "submit";
    button.textContent = label;
    form.append(button);
    return form;
  }

  function focus(item, fly = true) {
    if (fly) map.setView([item.lat, item.lng], Math.max(map.getZoom(), 9));
    list.querySelectorAll(".is-selected").forEach((node) => node.classList.remove("is-selected"));
    const node = document.getElementById(item.kind === "sos" ? "signal-" + item.id : "driver-" + item.id);
    if (!node) return;
    node.classList.add("is-selected");
    node.scrollIntoView({ block: "nearest" });
  }

  function statusName(status) {
    return { on_air: "На приёме", on_route: "На маршруте", need_help: "Нужна помощь" }[status] || "На связи";
  }

  async function refresh() {
    const query = new URLSearchParams();
    if (here) {
      query.set("lat", String(here.lat));
      query.set("lng", String(here.lng));
    }
    try {
      payload = await api("/api/poll.php?" + query.toString());
      if (offline) offline.hidden = true;
      draw();
      renderList(items());
      const sosId = Number(params.get("sos") || 0);
      if (sosId && !payload._focused) {
        payload._focused = true;
        const found = (payload.sos || []).find((row) => row.id === sosId);
        if (found) focus({ ...found, kind: "sos" });
      }
    } catch (error) {
      if (offline) {
        offline.hidden = false;
        offline.textContent = "Связь потеряна. Показываю последние данные.";
      }
    }
  }

  filters?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-filter]");
    if (!button) return;
    filters.querySelectorAll("[data-filter]").forEach((node) => node.classList.remove("is-on"));
    button.classList.add("is-on");
    const value = button.dataset.filter;
    if (value.startsWith("r")) {
      radius = Number(value.slice(1));
      if (radius && !here) {
        window.alert("Без точки с телефона радиус не посчитать. Показываю всю страну.");
        radius = 0;
      }
    } else {
      mode = value;
    }
    draw();
    renderList(items());
  });

  map.on("zoomend", draw);
  locate()
    .then((pos) => {
      here = pos;
      map.setView([pos.lat, pos.lng], 8);
      const me = L.circleMarker([pos.lat, pos.lng], {
        radius: 7,
        color: "#7eb6ff",
        weight: 2,
        fillColor: "#1e4fa3",
        fillOpacity: 0.9,
      }).addTo(map);
      me.bindTooltip("Ты здесь");
    })
    .catch(() => {})
    .finally(() => {
      window.setTimeout(() => map.invalidateSize(), 200);
      startPolling(refresh, 15000);
    });

  const partnerId = Number(params.get("partner") || 0);
  if (partnerId) {
    api("/api/partners.php")
      .then((data) => {
        const partner = (data.partners || []).find((row) => row.id === partnerId);
        if (!partner || partner.lat == null) return;
        map.setView([partner.lat, partner.lng], 11);
        L.marker([partner.lat, partner.lng], {
          icon: L.divIcon({
            className: "pin-marker pin-marker--on_route",
            html: "<span></span>",
            iconSize: [18, 18],
            iconAnchor: [9, 9],
          }),
        }).addTo(map).bindTooltip(partner.name);
      })
      .catch(() => {});
  }
}
