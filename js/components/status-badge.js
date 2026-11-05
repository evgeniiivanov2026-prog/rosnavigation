const labels = {
  on_air: ['На приёме', 'success'],
  on_route: ['На маршруте', 'accent'],
  need_help: ['Нужна помощь', 'danger'],
  resting: ['Отдыхаю', 'muted'],
  offline: ['Молчу', 'muted'],
};

export function statusBadge(status) {
  const [label, tone] = labels[status] || labels.offline;
  const node = document.createElement('span');
  node.className = `status status--${tone}`;
  const dot = document.createElement('i');
  dot.className = 'status__dot';
  dot.setAttribute('aria-hidden', 'true');
  node.append(dot, document.createTextNode(label));
  return node;
}
