export function locate() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Геолокация недоступна'));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => reject(new Error('Точку не отдали. Выберите трассу из списка.')),
      { enableHighAccuracy: true, timeout: 9000, maximumAge: 60000 }
    );
  });
}
