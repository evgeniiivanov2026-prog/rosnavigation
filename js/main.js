/**
 * РосНавигация — поведение лендинга.
 * Меню, плавное появление блоков, заявка.
 * Адреса форума и почты заданы в index.html (data-forum, data-email, форма).
 */
(function () {
  "use strict";

  var root = document.documentElement;
  var header = document.querySelector(".site-header");
  var progress = document.querySelector(".progress");
  var navToggle = document.querySelector(".nav-check");
  var navPanel = document.querySelector(".nav-panel");
  var navButton = document.querySelector(".nav-toggle");
  var form = document.querySelector("#signup");
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var yearNode = document.querySelector("#year");
  if (yearNode) yearNode.textContent = String(new Date().getFullYear());

  function scrollBehavior() {
    return reduceMotion ? "auto" : "smooth";
  }

  /* Полоска прокрутки и плотнее шапка после первого экрана */
  function onScroll() {
    if (header) {
      header.classList.toggle("is-stuck", window.scrollY > 8);
    }
    if (!progress) return;
    var scrollable = root.scrollHeight - window.innerHeight;
    var ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
    progress.style.transform = "scaleX(" + Math.min(1, Math.max(0, ratio)) + ")";
  }

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  /* Мобильное меню: чекбокс работает и без скрипта, здесь только подписи и закрытие */
  function syncMenu() {
    if (!navToggle || !navButton) return;
    var open = navToggle.checked;
    navButton.setAttribute("aria-expanded", open ? "true" : "false");
    var label = navButton.querySelector(".visually-hidden");
    if (label) label.textContent = open ? "Закрыть меню" : "Открыть меню";
  }

  if (navToggle) {
    navToggle.addEventListener("change", syncMenu);
    syncMenu();
  }

  if (navPanel && navToggle) {
    navPanel.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        if (window.matchMedia("(max-width: 959px)").matches) {
          navToggle.checked = false;
          syncMenu();
        }
      });
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && navToggle && navToggle.checked) {
      navToggle.checked = false;
      syncMenu();
      if (navButton) navButton.focus();
    }
  });

  /* Подсветка текущего раздела в меню */
  var sectionLinks = Array.prototype.slice.call(
    document.querySelectorAll(".nav-panel a[href^='#']")
  );
  var sections = sectionLinks
    .map(function (link) {
      return document.querySelector(link.getAttribute("href"));
    })
    .filter(Boolean);

  if (sections.length && "IntersectionObserver" in window) {
    var currentId = "";
    var sectionObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) currentId = entry.target.id;
        });
        sectionLinks.forEach(function (link) {
          var active = link.getAttribute("href") === "#" + currentId;
          if (active) link.setAttribute("aria-current", "true");
          else link.removeAttribute("aria-current");
        });
      },
      { rootMargin: "-45% 0px -50% 0px", threshold: 0 }
    );
    sections.forEach(function (section) {
      sectionObserver.observe(section);
    });
  }

  /* Появление секций. Без скрипта блоки и так видны — см. css */
  var revealNodes = document.querySelectorAll(".reveal");
  function showReveal(node) {
    node.classList.add("is-in");
  }

  if (revealNodes.length && "IntersectionObserver" in window && !reduceMotion) {
    var revealObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          showReveal(entry.target);
          revealObserver.unobserve(entry.target);
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -6% 0px" }
    );
    revealNodes.forEach(function (node) {
      revealObserver.observe(node);
    });
    /* Если наблюдатель не сработал, текст не должен остаться невидимым */
    window.setTimeout(function () {
      revealNodes.forEach(showReveal);
    }, 1800);
  } else {
    revealNodes.forEach(showReveal);
  }

  /* Предварительная запись */
  if (form) {
    form.noValidate = true;
    var success = form.querySelector(".form-success");
    var summary = form.querySelector(".form-summary");
    var endpoint = (form.getAttribute("data-endpoint") || "").trim();
    var email =
      (document.body.getAttribute("data-email") || "info@rosnavigation.ru").trim();

    function fieldError(input, message) {
      var field = input.closest(".field");
      var box = field ? field.querySelector(".field__error") : null;
      input.setAttribute("aria-invalid", message ? "true" : "false");
      if (box) box.textContent = message || "";
      return !message;
    }

    function isContact(value) {
      var digits = value.replace(/\D/g, "");
      if (digits.length >= 10 && digits.length <= 15) return true;
      if (/^@?[a-zA-Z][a-zA-Z0-9_]{3,31}$/.test(value)) return true;
      if (/^(?:https?:\/\/)?t\.me\/[a-zA-Z0-9_]{3,32}\/?$/i.test(value)) return true;
      return false;
    }

    function showSummary(message) {
      if (!summary) return;
      summary.hidden = !message;
      summary.textContent = message || "";
    }

    function showSuccess(text) {
      showSummary("");
      form.querySelectorAll(".field, .consent, .consent-error, .hp, .form-actions, .form-note").forEach(function (node) {
        node.hidden = true;
      });
      if (success) {
        success.hidden = false;
        var detail = success.querySelector(".form-success__detail");
        if (detail) detail.textContent = text;
      }
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      var nameInput = form.querySelector("#person-name");
      var contactInput = form.querySelector("#person-contact");
      var consent = form.querySelector("#person-consent");
      var honeypot = form.querySelector("#company-website");
      var name = nameInput.value.trim();
      var contact = contactInput.value.trim();
      var ok = true;

      showSummary("");

      if (!/^[\p{L}\p{M}\d\s.'-]{2,60}$/u.test(name)) {
        ok = fieldError(nameInput, "Напишите имя или позывной: от 2 до 60 символов.") && ok;
      } else {
        fieldError(nameInput, "");
      }

      if (!isContact(contact)) {
        ok =
          fieldError(
            contactInput,
            "Нужен телефон или Telegram: +7 900 000-00-00 или @username."
          ) && ok;
      } else {
        fieldError(contactInput, "");
      }

      var consentError = form.querySelector(".consent-error");
      if (!consent.checked) {
        ok = false;
        consent.setAttribute("aria-invalid", "true");
        if (consentError) consentError.textContent = "Нужно согласие на связь по заявке.";
      } else {
        consent.setAttribute("aria-invalid", "false");
        if (consentError) consentError.textContent = "";
      }

      if (!ok) {
        showSummary("Проверьте поля — заявка ещё не собрана.");
        var firstInvalid = form.querySelector("[aria-invalid='true']");
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      /* Скрытое поле заполнено — это бот, письмо не уходит */
      if (honeypot && honeypot.value.trim()) {
        showSuccess("Заявка принята.");
        return;
      }

      var submitButton = form.querySelector("[type='submit']");
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = "Отправляем…";
      }

      var doneMailto = function () {
        var subject = encodeURIComponent("Предварительная запись — РосНавигация");
        var body = encodeURIComponent("Имя: " + name + "\nКонтакт: " + contact + "\n");
        showSuccess(
          "Письмо открыто в почтовой программе на адрес " +
            email +
            ". Если окно не появилось, отправьте имя и контакт туда вручную."
        );
        window.location.href = "mailto:" + email + "?subject=" + subject + "&body=" + body;
      };

      if (!endpoint) {
        doneMailto();
        return;
      }

      fetch(endpoint, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          name: name,
          contact: contact,
          _subject: "Предварительная запись — РосНавигация",
        }),
      })
        .then(function (response) {
          if (!response.ok) throw new Error("request failed");
          showSuccess("Заявка отправлена. Мы свяжемся с вами по указанному контакту.");
        })
        .catch(function () {
          if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = "Оставить заявку";
          }
          showSummary("Сервис заявки не ответил. Откройте письмо на " + email + ".");
          doneMailto();
        });
    });
  }
})();
