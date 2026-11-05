export function csrf() {
  const node = document.querySelector('meta[name="csrf-token"]');
  return node ? node.content : "";
}

export async function api(path, { method = "GET", body, form } = {}) {
  const headers = { Accept: "application/json" };
  if (body && !form) headers["Content-Type"] = "application/json";
  if (method !== "GET") headers["X-CSRF-Token"] = csrf();

  const response = await fetch(path, {
    method,
    headers,
    credentials: "same-origin",
    body: form ? body : body ? JSON.stringify(body) : undefined,
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const error = new Error(data.message || "Сеть не ответила");
    error.status = response.status;
    error.data = data;
    throw error;
  }
  return data;
}
