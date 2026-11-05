const sound = document.querySelector("#air-sound");
if (sound) {
  try {
    sound.checked = localStorage.getItem("rn-sound") === "1";
  } catch (error) {
    sound.checked = false;
  }
  sound.addEventListener("change", () => {
    try {
      localStorage.setItem("rn-sound", sound.checked ? "1" : "0");
    } catch (error) {
      /* останется выключенным */
    }
  });
}

const file = document.querySelector("#avatar");
const preview = document.querySelector("#avatar-preview");
file?.addEventListener("change", () => {
  const image = file.files && file.files[0];
  if (!image || !preview) return;
  preview.src = URL.createObjectURL(image);
  preview.hidden = false;
});
