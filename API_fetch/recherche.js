const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const resultsList = document.getElementById("results");

let debounceTimer;
let controller;

searchInput.addEventListener("input", (e) => {
  const query = e.target.value.trim();

  clearTimeout(debounceTimer);

  if (query.length === 0) {
    resultsList.innerHTML = "";
    return;
  }

  debounceTimer = setTimeout(() => {
    fetchResults(query);
  }, 300);
});

searchForm.addEventListener("submit", (e) => {
  e.preventDefault();
  const query = searchInput.value.trim();
  if (query.length > 0) {
    fetchResults(query);
  }
});

async function fetchResults(query) {
  if (controller) controller.abort();
  controller = new AbortController();

  try {
    const response = await fetch(
  `/search.php?q=${encodeURIComponent(query)}`,
  { signal: controller.signal }
);

    if (!response.ok) {
      throw new Error(`Erreur HTTP: ${response.status}`);
    }

    const data = await response.json();
    displayResults(data);
  } catch (error) {
    if (error.name !== "AbortError") {
      console.error("Erreur lors de la recherche:", error);
      resultsList.innerHTML =
        '<li class="list-group-item text-danger">Erreur lors de la recherche</li>';
    }
  }
}

function displayResults(data) {
  resultsList.innerHTML = "";

  if (data.length === 0) {
    resultsList.innerHTML =
      '<li class="list-group-item">Aucun résultat trouvé</li>';
    return;
  }

  data.forEach(item => {
    const li = document.createElement("li");
    li.className = "list-group-item";

    const link = document.createElement("a");
    link.href = `/attraction.php?id=${item.id_attraction}`;
    link.textContent = item.name;
    link.style.textDecoration = "none";
    link.style.color = "inherit";

    li.appendChild(link);
    resultsList.appendChild(li);
  });
}