let dist = {
  Lundi: [
    "8:00",
    "9:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
  ],
  Mardi: [
    "8:00",
    "9:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "15:00",
    "16:00",
    "17:00",
  ],
  Mercredi: [
    "8:00",
    "9:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
  ],
  Jeudi: [
    "8:00",
    "9:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
  ],
  Vendredi: [
    "8:00",
    "9:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
  ],
};

let hour;
let day;

function CreateCalendar(dict) {
  let CalendarSlots = document.getElementsByClassName("hour");

  Object.keys(dict).forEach(function (key, index) {
    CalendarSlots[index].innerHTML = "";

    dict[key].forEach(function (heure) {
      let bouton = document.createElement("button");
      let creneau = key + " " + heure;
      let estReserve = creneaux_reserves.includes(creneau);

      bouton.classList.add("time", "btn", "btn-outline-dark", "rounded-pill");

      if (!estReserve) {
        bouton.innerText = heure;
        bouton.dataset.hour = heure;
        bouton.dataset.day = key;

        bouton.addEventListener("click", function () {
          let ancien = document.querySelector(".selected");
          if (ancien) ancien.classList.remove("selected");

          bouton.classList.add("selected");

          let confirmer = document.querySelector(".confirmer");
          confirmer.classList.remove("d-none");
          confirmer.innerText = "Confirmer : " + heure + " " + key;

          hour = heure;
          day = key;
        });
      } else {
        bouton.classList.add("disabled");
        bouton.innerText = "Réservé";
        bouton.disabled = true;
      }

      CalendarSlots[index].appendChild(bouton);
    });
  });
}

window.onload = async function () {
  CreateCalendar(dist);

  let confirmer = document.querySelector(".confirmer");

  confirmer.addEventListener("click", async function () {
    if (!hour || !day) return;

    confirmer.disabled = true;
    confirmer.innerText = "Envoi en cours...";

    try {
      const res = await fetch("/bien/ajouter_visite.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `hour=${hour}&day=${day}&id_utilisateur=${id_utilisateur}&id_bien_actuel=${id_bien_actuel}`,
      });

      const data = await res.json();
      console.log("Réponse:", data);
      console.log("Status:", res.status);

      let message = document.getElementById("message");

      if (data.success) {
        message.innerHTML = `<div class="alert alert-success">✅ Réservation confirmée : ${day} à ${hour}</div>`;
        confirmer.classList.add("d-none");
        let selected = document.querySelector(".selected");
        if (selected) {
          selected.classList.remove("selected", "btn-outline-dark");
          selected.classList.add("disabled");
          selected.innerText = "Réservé";
          selected.disabled = true;
        }
      } else {
        message.innerHTML = `<div class="alert alert-danger">❌ ${data.error}</div>`;
        confirmer.disabled = false;
        confirmer.innerText = "Confirmer : " + hour + " " + day;
      }
    } catch {
      document.getElementById("message").innerHTML =
        `<div class="alert alert-danger">❌ Erreur réseau</div>`;
      confirmer.disabled = false;
    }
  });
};
