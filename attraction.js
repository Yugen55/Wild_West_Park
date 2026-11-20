let unite = "annee";

function setUnite(val) {
  unite = val;
  document
    .getElementById("btnMois")
    .classList.toggle("btn-dark", val === "mois");
  document
    .getElementById("btnMois")
    .classList.toggle("btn-outline-dark", val !== "mois");
  document
    .getElementById("btnAnnee")
    .classList.toggle("btn-dark", val === "annee");
  document
    .getElementById("btnAnnee")
    .classList.toggle("btn-outline-dark", val !== "annee");
}

function calculer() {
  let montant = parseFloat(document.getElementById("montant").value);
  let taux = parseFloat(document.getElementById("taux").value) / 100 / 12;
  let dureeRaw = parseFloat(document.getElementById("duree").value);
  let apport = parseFloat(document.getElementById("apport").value) || 0;

  let duree = unite === "annee" ? dureeRaw * 12 : dureeRaw;

  let capital = montant - apport;

  let mensualite = (capital * taux) / (1 - Math.pow(1 + taux, -duree));
  let total = mensualite * duree;
  let interets = total - capital;

  if (isNaN(mensualite) || !isFinite(mensualite)) {
    mensualite = 0;
    total = 0;
    interets = 0;
  }

  document.getElementById("mensualite").innerText =
    Math.round(mensualite) + " €";
  document.getElementById("total").innerText = Math.round(total) + " €";
  document.getElementById("interets").innerText = Math.round(interets) + " €";
}

function resetCalc() {
  document
    .querySelectorAll(".sim-left input")
    .forEach((input) => (input.value = ""));
}
