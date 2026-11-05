export function toast(message) {
  const root = document.getElementById('toasts');
  if (!root || !message) return;
  const node = document.createElement('p');
  node.className = 'banner';
  node.textContent = message;
  root.append(node);
  setTimeout(() => node.remove(), 5000);
}
