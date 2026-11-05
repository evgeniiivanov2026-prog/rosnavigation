export function readFlag(key) {
  try {
    return localStorage.getItem(key);
  } catch (error) {
    return null;
  }
}

export function writeFlag(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch (error) {
    /* приватный режим */
  }
}
