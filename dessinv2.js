const canvasImage = document.getElementById("canvas-image");
const ctxImage = canvasImage.getContext("2d");
let dessin = false;

const img = new Image();
img.src = "images/Plan_du_parc.png";
img.onload = () => {
  ctxImage.drawImage(img, 0, 0, canvasImage.width, canvasImage.height);
};

function getPos(e) {
  const rect = canvasImage.getBoundingClientRect();
  if (e.touches) {
    return {
      x: e.touches[0].clientX - rect.left,
      y: e.touches[0].clientY - rect.top,
    };
  }
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

canvasImage.addEventListener("mousedown", (e) => {
  dessin = true;
  ctxImage.beginPath();
  ctxImage.moveTo(getPos(e).x, getPos(e).y);
});

canvasImage.addEventListener("mousemove", (e) => {
  if (!dessin) return;
  const pos = getPos(e);
  ctxImage.lineTo(pos.x, pos.y);
  ctxImage.strokeStyle = "#FF0000";
  ctxImage.lineWidth = 3;
  ctxImage.lineCap = "round";
  ctxImage.lineJoin = "round";
  ctxImage.stroke();
});

canvasImage.addEventListener("mouseup", () => {
  dessin = false;
});
canvasImage.addEventListener("mouseleave", () => {
  dessin = false;
});

document.getElementById("btn-effacer").addEventListener("click", () => {
  ctxImage.clearRect(0, 0, canvasImage.width, canvasImage.height);
  ctxImage.drawImage(img, 0, 0, canvasImage.width, canvasImage.height);
});
