const form = document.querySelector("#join-form");
if (!form) {
  // только вход или уже в колонне
} else {
  const steps = [...form.querySelectorAll(".wizard__step")];
  const next = form.querySelector("[data-next]");
  const prev = form.querySelector("[data-prev]");
  const submit = form.querySelector("[data-submit]");
  const errorBox = form.querySelector(".form-error");
  let index = 0;

  function show(step) {
    index = step;
    steps.forEach((node, i) => node.classList.toggle("is-active", i === index));
    form.classList.toggle("is-last", index === steps.length - 1);
    if (prev) prev.hidden = index === 0;
    errorBox && (errorBox.textContent = "");
  }

  function say(text) {
    if (errorBox) errorBox.textContent = text || "";
  }

  show(0);

  next?.addEventListener("click", () => {
    const current = steps[index];
    const invalid = [...current.querySelectorAll("input, select")].find((input) => !input.checkValidity());
    if (invalid) {
      invalid.reportValidity();
      return;
    }
    show(Math.min(index + 1, steps.length - 1));
  });
  prev?.addEventListener("click", () => show(Math.max(index - 1, 0)));

  form.addEventListener("submit", async (event) => {
    if (!form.checkValidity()) return;
    event.preventDefault();
    const body = Object.fromEntries(new FormData(form).entries());
    if (body.website) {
      window.location.href = "/auth?welcome=1";
      return;
    }
    submit && (submit.disabled = true);
    try {
      const headers = {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')?.content || "",
      };
      const response = await fetch("/api/auth.php?action=register", {
        method: "POST",
        headers,
        credentials: "same-origin",
        body: JSON.stringify(body),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.message || "Не удалось занять место");
      const nextUrl = new URLSearchParams(window.location.search).get("next") || "";
      window.location.href = "/auth?welcome=1&next=" + encodeURIComponent(nextUrl);
    } catch (error) {
      say(error.message);
      if (submit) submit.disabled = false;
    }
  });
}
