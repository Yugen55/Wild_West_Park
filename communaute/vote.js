document.getElementById("add-option").addEventListener("click", ajouter_option);

function ajouter_option() {
  let container = document.getElementById("options-list");
  let div = document.createElement("div");
  div.innerHTML = `<input type="text" class="form-control" name="options[]" placeholder="Autre option">`;
  container.appendChild(div);
}
