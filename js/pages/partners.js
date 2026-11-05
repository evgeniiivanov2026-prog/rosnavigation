export function init() {
  const form = document.getElementById('partner-filter');
  const list = document.getElementById('partner-list');
  const empty = document.getElementById('partner-empty');
  if (!form || !list) return;

  const apply = () => {
    const type = form.type.value;
    const region = form.region.value;
    const rating = Number(form.rating.value || 0);
    const day = form.day.checked;
    let shown = 0;
    list.querySelectorAll('.partner').forEach((card) => {
      const ok = (!type || card.dataset.type === type)
        && (!region || card.dataset.region === region)
        && (!rating || Number(card.dataset.rating) >= rating)
        && (!day || card.dataset.day === '1');
      card.hidden = !ok;
      if (ok) shown += 1;
    });
    if (empty) empty.hidden = shown > 0;
    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (region) params.set('region', region);
    if (form.rating.value) params.set('rating', form.rating.value);
    if (day) params.set('day', '1');
    const query = params.toString();
    history.replaceState(null, '', query ? `/partners?${query}` : '/partners');
  };

  form.addEventListener('change', apply);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    apply();
  });
}
