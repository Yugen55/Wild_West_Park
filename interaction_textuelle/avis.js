const textarea = document.getElementById("commentaire");
const compteur = document.getElementById("compteur");
const maxCaracteres = 200;

textarea.addEventListener("input", function () {
  const nbCaracteres = textarea.value.length;
  compteur.textContent = nbCaracteres;

  if (nbCaracteres >= maxCaracteres) {
    compteur.classList.add("text-danger");
  } else {
    compteur.classList.remove("text-danger");
  }
});

const dropZone = document.getElementById("drop-zone");
const fileInput = document.getElementById("file-input");
const preview = document.getElementById("preview");

dropZone.addEventListener("click", (e) => {
  if (e.target !== fileInput) fileInput.click();
});

dropZone.addEventListener("dragover", (e) => {
  e.preventDefault();
  dropZone.classList.add("border-primary");
});

dropZone.addEventListener("dragleave", () => {
  dropZone.classList.remove("border-primary");
});

dropZone.addEventListener("drop", (e) => {
  e.preventDefault();
  dropZone.classList.remove("border-primary");
  handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener("change", () => handleFiles(fileInput.files));

function handleFiles(files) {
  preview.innerHTML = "";
  Array.from(files).forEach((file) => {
    if (!file.type.startsWith("image/")) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      const li = document.createElement("li");
      const img = document.createElement("img");
      img.src = e.target.result;
      img.style.cssText =
        "max-width:100px;max-height:100px;margin:4px;border-radius:8px;";
      li.appendChild(img);
      preview.appendChild(li);
    };
    reader.readAsDataURL(file);
  });
}
