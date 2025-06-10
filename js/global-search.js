// Sistema de búsqueda global para todas las páginas
;(() => {
  let searchTimeout = null
  let lastSearchTerm = ""

  function createSearchPanel() {
    // Verificar si ya existe
    if (document.getElementById("search-panel")) {
      return
    }

    const searchPanel = document.createElement("div")
    searchPanel.id = "search-panel"
    searchPanel.className = "search-panel"
    searchPanel.innerHTML = `
            <div class="search-container">
                <div class="search-header">
                    <input type="text" id="search-input" placeholder="¿Qué estás buscando?" autocomplete="off">
                    <button id="search-close" aria-label="Cerrar búsqueda">&times;</button>
                </div>
                <div class="search-results" id="search-results">
                    <div class="search-message">Escribe para buscar productos</div>
                </div>
            </div>
        `

    document.body.appendChild(searchPanel)

    // Agregar estilos
    addSearchStyles()

    // Configurar eventos
    setupSearchEvents()
  }

  function addSearchStyles() {
    if (document.getElementById("search-styles")) {
      return
    }

    const style = document.createElement("style")
    style.id = "search-styles"
    style.textContent = `
            .search-panel {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(5px);
                z-index: 9000;
                display: flex;
                align-items: flex-start;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .search-panel.active {
                opacity: 1;
                visibility: visible;
            }

            .search-container {
                width: 100%;
                max-width: 700px;
                margin-top: 120px;
                background-color: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                overflow: hidden;
                transform: translateY(-30px);
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
            }

            .search-panel.active .search-container {
                transform: translateY(0);
                opacity: 1;
            }

            .search-header {
                display: flex;
                align-items: center;
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
            }

            #search-input {
                flex: 1;
                border: none;
                font-size: 18px;
                padding: 10px 0;
                outline: none;
                font-family: "Poppins", sans-serif;
                color: #333;
            }

            #search-input::placeholder {
                color: #aaa;
            }

            #search-close {
                background: none;
                border: none;
                font-size: 24px;
                color: #666;
                cursor: pointer;
                padding: 5px 10px;
                margin-left: 10px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s ease;
            }

            #search-close:hover {
                background-color: #f0f0f0;
                color: #333;
            }

            .search-results {
                max-height: 60vh;
                overflow-y: auto;
                padding: 0;
            }

            .search-message {
                padding: 30px;
                text-align: center;
                color: #666;
                font-size: 16px;
            }

            .search-results-header {
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
                font-weight: 600;
                color: #333;
            }

            .search-results-grid {
                padding: 10px;
            }

            .search-result-item {
                display: flex;
                align-items: center;
                padding: 10px;
                border-radius: 8px;
                text-decoration: none;
                color: #333;
                transition: background-color 0.2s ease;
            }

            .search-result-item:hover {
                background-color: #f5f5f5;
            }

            .search-result-image {
                width: 50px;
                height: 50px;
                background-color: #f0f0f0;
                border-radius: 6px;
                margin-right: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .search-result-image img {
                max-width: 40px;
                max-height: 40px;
                object-fit: contain;
            }

            .search-result-info h3 {
                margin: 0 0 5px 0;
                font-size: 14px;
                font-weight: 500;
            }

            .search-result-price {
                margin: 0;
                font-size: 14px;
                font-weight: 600;
                color: #667eea;
            }

            .search-results-footer {
                padding: 15px 20px;
                border-top: 1px solid #eee;
                text-align: center;
            }

            .search-view-all {
                color: #667eea;
                text-decoration: none;
                font-weight: 500;
            }

            .search-view-all:hover {
                text-decoration: underline;
            }
        `

    document.head.appendChild(style)
  }

  function setupSearchEvents() {
    const searchButtons = document.querySelectorAll('.CircularButtons a[title="Buscar"]')
    searchButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.preventDefault()
        toggleSearchPanel(true)
      })
    })

    const closeButton = document.getElementById("search-close")
    if (closeButton) {
      closeButton.addEventListener("click", () => {
        toggleSearchPanel(false)
      })
    }

    const searchInput = document.getElementById("search-input")
    if (searchInput) {
      searchInput.addEventListener("input", handleSearchInput)
      searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          toggleSearchPanel(false)
        }
      })
    }

    // Cerrar al hacer clic fuera
    document.addEventListener("click", (e) => {
      const searchPanel = document.getElementById("search-panel")
      const searchButtons = document.querySelectorAll('.CircularButtons a[title="Buscar"]')

      let clickedOnSearchButton = false
      searchButtons.forEach((button) => {
        if (button.contains(e.target)) {
          clickedOnSearchButton = true
        }
      })

      if (
        searchPanel &&
        searchPanel.classList.contains("active") &&
        !searchPanel.contains(e.target) &&
        !clickedOnSearchButton
      ) {
        toggleSearchPanel(false)
      }
    })
  }

  function toggleSearchPanel(show = true) {
    const searchPanel = document.getElementById("search-panel")
    const searchInput = document.getElementById("search-input")

    if (show) {
      searchPanel.classList.add("active")
      setTimeout(() => {
        searchInput.focus()
      }, 300)
    } else {
      searchPanel.classList.remove("active")
      searchInput.value = ""
      lastSearchTerm = ""
    }
  }

  function handleSearchInput(e) {
    const searchTerm = e.target.value.trim()

    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }

    if (searchTerm === lastSearchTerm || searchTerm.length < 2) {
      if (searchTerm.length === 0) {
        showSearchMessage("Escribe para buscar productos")
      } else if (searchTerm.length === 1) {
        showSearchMessage("Escribe al menos 2 caracteres para buscar")
      }
      return
    }

    showSearchMessage("Buscando...", true)

    searchTimeout = setTimeout(() => {
      performSearch(searchTerm)
      lastSearchTerm = searchTerm
    }, 300)
  }

  async function performSearch(searchTerm) {
    try {
      const response = await fetch(`backend/php/search-products.php?q=${encodeURIComponent(searchTerm)}`)
      const result = await response.json()

      if (result.success) {
        displaySearchResults(result.data.products, searchTerm)
      } else {
        throw new Error(result.message || "Error en la búsqueda")
      }
    } catch (error) {
      console.error("Error en la búsqueda:", error)
      showSearchMessage("Error al buscar productos. Intenta de nuevo.")
    }
  }

  function displaySearchResults(products, searchTerm) {
    const resultsContainer = document.getElementById("search-results")

    if (!products || products.length === 0) {
      showSearchMessage(`No se encontraron productos para "${searchTerm}"`)
      return
    }

    let html = `
            <div class="search-results-header">
                <span>${products.length} resultado${products.length !== 1 ? "s" : ""} para "${searchTerm}"</span>
            </div>
            <div class="search-results-grid">
        `

    products.forEach((product) => {
      html += `
                <a href="index.php#producto-${product.idProducto}" class="search-result-item">
                    <div class="search-result-image">
                        <img src="${product.imagen || "img/main/placeholder-product.png"}" alt="${product.nombre}">
                    </div>
                    <div class="search-result-info">
                        <h3>${highlightSearchTerm(product.nombre, searchTerm)}</h3>
                        <p class="search-result-price">$${formatPrice(product.precio)}</p>
                    </div>
                </a>
            `
    })

    html += `
            </div>
            <div class="search-results-footer">
                <a href="index.php#busqueda?q=${encodeURIComponent(searchTerm)}" class="search-view-all">Ver todos los resultados</a>
            </div>
        `

    resultsContainer.innerHTML = html
  }

  function showSearchMessage(message, loading = false) {
    const resultsContainer = document.getElementById("search-results")
    resultsContainer.innerHTML = `
            <div class="search-message ${loading ? "loading" : ""}">
                ${message}
            </div>
        `
  }

  function highlightSearchTerm(text, term) {
    if (!term) return text
    const regex = new RegExp(`(${escapeRegExp(term)})`, "gi")
    return text.replace(regex, "<mark>$1</mark>")
  }

  function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
  }

  function formatPrice(price) {
    return new Intl.NumberFormat("es-CO", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", createSearchPanel)
  } else {
    createSearchPanel()
  }

  // Exponer función global para uso manual si es necesario
  window.initGlobalSearch = createSearchPanel
})()
