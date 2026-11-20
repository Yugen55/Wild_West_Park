let prix_base = 40;
let ratio_age_adulte = 1;
let ratio_age_enfant = 0.7;
let ratio_age_senior = 0.5;

function achat_ticket() {
  let nb_adulte = Number(document.getElementById("nb_adulte").value);
  let nb_enfant = Number(document.getElementById("nb_enfant").value);
  let nb_senior = Number(document.getElementById("nb_senior").value);
  let nb_jours = Number(document.getElementById("nb_jour").value);
  let prix =
    prix_base *
    (nb_adulte * ratio_age_adulte +
      nb_enfant * ratio_age_enfant +
      nb_senior * ratio_age_senior) *
    nb_jours;
  document.getElementById("prix").textContent = prix;
}
