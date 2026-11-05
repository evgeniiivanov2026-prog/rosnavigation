import { api, csrf } from "/js/utils/api.js";

document.querySelectorAll(".like-form").forEach((form) => {
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const id = form.querySelector('[name="post_id"]')?.value;
    const button = form.querySelector("button");
    try {
      const data = await api("/api/channel.php?action=like", {
        method: "POST",
        body: { post_id: Number(id) },
      });
      if (button) {
        button.setAttribute("aria-pressed", "true");
        const count = button.querySelector("span");
        if (count) count.textContent = String(data.count);
      }
    } catch (error) {
      if (error.status === 401) {
        window.location.href = "/auth?next=" + encodeURIComponent("/channel");
        return;
      }
      window.alert(error.message);
    }
  });
});

const composer = document.querySelector("#composer");
composer?.addEventListener("submit", async (event) => {
  if (!composer.photo?.files?.length) {
    event.preventDefault();
    const button = composer.querySelector('[type="submit"]');
    if (button) button.disabled = true;
    try {
      const data = await api("/api/channel.php?action=create", {
        method: "POST",
        body: {
          body: composer.body?.value || "",
          route: composer.route?.value || "",
          city: composer.city?.value || "",
        },
      });
      window.location.href = "/channel?sent=" + data.id;
    } catch (error) {
      if (button) button.disabled = false;
      if (error.status === 401) {
        window.location.href = "/auth?next=" + encodeURIComponent("/channel");
        return;
      }
      const box = composer.querySelector(".form-error");
      if (box) box.textContent = error.message;
    }
  } else {
    const token = composer.querySelector('[name="csrf"]');
    if (token && !token.value) token.value = csrf();
  }
});
