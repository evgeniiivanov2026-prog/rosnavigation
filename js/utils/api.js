export function csrf() {
  const node = document.querySelector('meta[name="csrf-token"]');
  return node ? node.getAttribute('content') || '' : '';
}

export async function api(path, { method = 'GET', body, form } = {}) {
  const headers = { Accept: 'application/json' };
  if (body && !form) headers['Content-Type'] = 'application/json';
  if (method !== 'GET') headers['X-CSRF-Token'] = csrf();

  const res = await fetch(`/api/${path}`, {
    method,
    headers,
    credentials: 'same-origin',
    body: form || (body ? JSON.stringify(body) : undefined),
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = new Error(data.message || data.error || `HTTP ${res.status}`);
    err.status = res.status;
    err.data = data;
    throw err;
  }
  return data;
}
