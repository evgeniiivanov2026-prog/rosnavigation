import { api } from "/js/utils/api.js";

const form = document.querySelector("#partner-filter");
const list = document.querySelector("#partner-list");
if (!form || !list) {
  // фильтр без скрипта остаётся обычной формой
} else {
  let timer = 0;
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    load();
  });
  form.addEventListener("change", () => load());
  form.querySelector('[name="q"]')?.addEventListener("input", () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(load, 250);
  });

  async function load() {
    const params = new URLSearchParams(new FormData(form));
    const url = "/api/partners.php?" + params.toString();
    list.setAttribute("aria-busy", "true");
    try {
      const data = await api(url);
      history.replaceState(null, "", "/partners" + (params.toString() ? "?" + params.toString() : ""));
      draw(data.partners || []);
    } catch (error) {
      list.insertAdjacentHTML(
        "afterbegin",
        '<p class="form-error">Список не обновился. Можно обновить страницу.</p>'
      );
    } finally {
      list.removeAttribute("aria-busy");
    }
  }

  function draw(partners) {
    list.replaceChildren();
    if (!partners.length) {
      const empty = document.createElement("div");
      empty.className = "empty";
      empty.innerHTML = "<h3>Пусто на этом плече</h3><p>Сними фильтр или поищи соседний город.</p>";
      list.append(empty);
      return;
    }
    const demo = document.body.dataset.demo === "1";
    partners.forEach((partner) => {
      const card = document.createElement("article");
      card.className = "card partner";
      const top = document.createElement("div");
      top.className = "partner__top";
      const kind = document.createElement("span");
      kind.className = "eyebrow";
      kind.textContent = partner.type_label;
      top.append(kind);
      if (partner.works_24_7) {
        const chip = document.createElement("span");
        chip.className = "chip";
        chip.textContent = "Круглосуточно";
        top.append(chip);
      }
      const title = document.createElement("h3");
      title.textContent = partner.name;
      const where = document.createElement("p");
      where.className = "partner__where";
      where.textContent = partner.city + " · " + partner.address;
      const text = document.createElement("p");
      text.textContent = partner.description;
      const meta = document.createElement("p");
      meta.className = "partner__meta";
      const bits = [String(partner.rating).replace(".", ",") + " · " + partner.rating_count + " оценок"];
      if (partner.price_from) bits.push("от " + new Intl.NumberFormat("ru-RU").format(partner.price_from) + " ₽");
      meta.textContent = bits.join(" · ");
      const actions = document.createElement("div");
      actions.className = "actions";
      const link = document.createElement("a");
      link.className = "btn btn--ghost";
      if (demo || !partner.lat) {
        link.href = "/map?partner=" + partner.id;
        link.textContent = "На карте";
      } else {
        link.href = "https://yandex.ru/maps/?rtext=~" + partner.lat + "," + partner.lng + "&rtt=auto";
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = "Маршрут";
      }
      actions.append(link);
      card.append(top, title, where, text, meta, actions);
      list.append(card);
    });
  }
}
