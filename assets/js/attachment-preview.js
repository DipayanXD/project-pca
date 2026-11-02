/* One native, keyboard-accessible dialog shared by both complaint detail pages. */
document.addEventListener("DOMContentLoaded", () => {
  const trigger = document.querySelector("[data-image-preview]");
  const dialog = document.getElementById("attachment-preview");
  if (!trigger || !dialog || typeof dialog.showModal !== "function") return;

  const image = dialog.querySelector("[data-preview-image]");
  const stage = dialog.querySelector("[data-preview-stage]");
  const status = dialog.querySelector("[data-preview-status]");
  const zoomIn = dialog.querySelector("[data-preview-zoom-in]");
  const zoomOut = dialog.querySelector("[data-preview-zoom-out]");
  const zoomLabel = dialog.querySelector("[data-preview-zoom]");
  let zoom = 1;
  let loaded = false;
  let previousOverflow = "";
  let pointerStart = null;

  function resizeImage() {
    zoomLabel.value = `${Math.round(zoom * 100)}%`;
    zoomIn.disabled = !loaded || zoom >= 4;
    zoomOut.disabled = !loaded || zoom <= 0.5;
    if (!loaded) return;
    const fit = Math.min(1, (stage.clientWidth - 32) / image.naturalWidth,
      (stage.clientHeight - 32) / image.naturalHeight);
    image.style.width = `${image.naturalWidth * fit * zoom}px`;
    image.style.height = `${image.naturalHeight * fit * zoom}px`;
  }

  trigger.addEventListener("click", (event) => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    zoom = 1;
    loaded = false;
    image.hidden = true;
    status.hidden = false;
    status.textContent = "Loading image…";
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    dialog.showModal();
    stage.scrollTo(0, 0);
    resizeImage();
    image.src = trigger.href;
  });

  image.addEventListener("load", () => {
    loaded = true;
    image.hidden = false;
    status.hidden = true;
    resizeImage();
  });
  image.addEventListener("error", () => {
    loaded = false;
    image.hidden = true;
    status.hidden = false;
    status.textContent = "This image could not be loaded. Close the preview and refresh the page to try again.";
    resizeImage();
  });
  zoomIn.addEventListener("click", () => { zoom = Math.min(4, zoom + 0.25); resizeImage(); });
  zoomOut.addEventListener("click", () => { zoom = Math.max(0.5, zoom - 0.25); resizeImage(); });
  dialog.querySelector("[data-preview-close]").addEventListener("click", () => dialog.close());
  // Native dialog handles Esc, focus containment and making the background inert.
  dialog.addEventListener("close", () => {
    document.body.style.overflow = previousOverflow;
    image.removeAttribute("src");
    trigger.focus({ preventScroll: true });
  });
  dialog.addEventListener("pointerdown", (event) => {
    pointerStart = { target: event.target, x: event.clientX, y: event.clientY };
  });
  dialog.addEventListener("click", (event) => {
    const outsideImage = event.target === dialog || event.target === stage;
    if (outsideImage && pointerStart?.target === event.target &&
        Math.hypot(event.clientX - pointerStart.x, event.clientY - pointerStart.y) < 5) {
      dialog.close();
    }
    pointerStart = null;
  });
  window.addEventListener("resize", () => { if (dialog.open) resizeImage(); });
});
