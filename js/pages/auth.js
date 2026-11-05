export function init({ toast }) {
  const form = document.getElementById('join-form');
  if (!form) return;
  const steps = [...form.querySelectorAll('[data-step]')];
  if (!steps.length) return;
  form.classList.add('is-wizard');
  let index = 0;
  const dots = [...form.querySelectorAll('.wizard__steps span')];

  function show(next) {
    index = next;
    steps.forEach((step, i) => step.classList.toggle('is-on', i === index));
    dots.forEach((dot, i) => dot.classList.toggle('is-on', i <= index));
    const field = steps[index].querySelector('input, select, textarea');
    field?.focus();
  }

  function validate(step) {
    const fields = [...step.querySelectorAll('input, select, textarea')];
    for (const field of fields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  }

  form.addEventListener('click', (event) => {
    const next = event.target.closest('[data-next]');
    const prev = event.target.closest('[data-prev]');
    if (next) {
      event.preventDefault();
      if (!validate(steps[index])) return;
      show(Math.min(index + 1, steps.length - 1));
    }
    if (prev) {
      event.preventDefault();
      show(Math.max(index - 1, 0));
    }
  });

  form.addEventListener('submit', (event) => {
    if (!validate(steps[0]) || !validate(steps[1])) {
      event.preventDefault();
      toast('Заполните телефон, пароль и имя.');
      show(0);
    }
  });
  show(0);
}
