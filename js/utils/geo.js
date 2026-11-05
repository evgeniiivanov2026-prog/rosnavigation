export function locate() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error("Геолокации в этом браузере нет"));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) =>
        resolve({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          accuracy: pos.coords.accuracy,
        }),
      () => reject(new Error("Точку с телефона взять не удалось")),
      { enableHighAccuracy: true, timeout: 9000, maximumAge: 20000 }
    );
  });
}
