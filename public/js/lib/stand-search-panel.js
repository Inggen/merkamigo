/**
 * IMM-034: buscador + categorías + selector de plaza dentro de la plaza
 * inmersiva. Mismo patrón autocontenido que `stand-proximity.js`/
 * `stand-vitrina-modal.js` (DOM + estilos propios desde JS, sin depender
 * de las clases HUD de cada escena) — el overlay centrado con backdrop
 * reutiliza exactamente el mismo lenguaje visual que `createVitrinaModal`.
 *
 * Dos fuentes de datos distintas:
 * - Filtro LOCAL (sin red): oculta/muestra los `stands` ya cargados en
 *   ESTA plaza (`root.visible`, nunca toca `position`/`rotation` — "sin
 *   reasignar posiciones").
 * - Resultados GLOBALES (con red, `GET /api/v1/plaza`): busca en TODAS
 *   las plazas — cada resultado trae `immersive_location` (IMM-034,
 *   `PublicBusinessResource`) para saber si está aquí mismo ("Ver aquí",
 *   abre el modal de IMM-033 ya cableado en la escena) o en otro
 *   municipio ("Viajar a...", navegación normal a esa escena).
 */
const DEBOUNCE_MS = 300;

// Mismo set de Heroicons (outline) que ya usa Filament para el ícono de
// cada categoría (`Category::icon`, ver CategorySeeder) — así el ícono
// mostrado aquí siempre coincide con el que un admin configuró, sin
// mantener un segundo mapeo por slug. `star` (para "Todas") y los íconos
// de UI (lupa, grilla, chevron, pin, cerrar) se agregan aparte porque no
// vienen de ninguna categoría.
const ICONS = {
    'magnifying-glass': 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
    'squares-2x2': 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
    'chevron-down': 'm19.5 8.25-7.5 7.5-7.5-7.5',
    'map-pin': 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z##M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
    'x-mark': 'M6 18 18 6M6 6l12 12',
    star: 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
    cake: 'M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5.75a3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-3 0L3 16.5m15-3.379a48.474 48.474 0 0 0-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 0 1 3 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 0 1 6 13.12M12.265 3.11a.375.375 0 1 1-.53 0L12 2.845l.265.265Zm-3 0a.375.375 0 1 1-.53 0L9 2.845l.265.265Zm6 0a.375.375 0 1 1-.53 0L15 2.845l.265.265Z',
    'shopping-bag': 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
    home: 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    sparkles: 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z',
    briefcase: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z',
    'wrench-screwdriver': 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z',
    heart: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
    'building-office-2': 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z',
    tag: 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z##M6 6h.008v.008H6V6Z',
};

function svgIcon(name, extraClass = '') {
    const paths = (ICONS[name] ?? ICONS.tag).split('##');

    return `<svg class="vpe-search-icon ${extraClass}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">${paths.map((d) => `<path stroke-linecap="round" stroke-linejoin="round" d="${d}"/>`).join('')}</svg>`;
}

export function attachSearchPanel(engine, stands, { currentMunicipalitySlug = null, vitrinaModal = null, track = null, container = null } = {}) {
    injectStylesOnce();

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'vpe-search-toggle';
    toggle.id = 'vpe-search-toggle';
    toggle.setAttribute('aria-label', 'Buscar en la plaza');
    toggle.setAttribute('aria-haspopup', 'dialog');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', 'vpe-search-panel');
    toggle.innerHTML = svgIcon('magnifying-glass');

    const overlay = document.createElement('div');
    overlay.className = 'vpe-search-overlay';

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'vpe-search-backdrop';
    backdrop.setAttribute('aria-label', 'Cerrar');

    const panel = document.createElement('div');
    panel.className = 'vpe-search-panel';
    panel.id = 'vpe-search-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'Buscar negocios en la plaza');
    panel.innerHTML = `
        <button type="button" class="vpe-search-close" data-search-close aria-label="Cerrar buscador">${svgIcon('x-mark')}</button>

        <div class="vpe-search-top-row">
            <div class="vpe-search-input-wrap">
                ${svgIcon('magnifying-glass', 'vpe-search-input-icon')}
                <label class="vpe-search-visually-hidden" for="vpe-search-input">Buscar negocio o producto</label>
                <input id="vpe-search-input" type="search" class="vpe-search-input" placeholder="Buscar negocio o producto…" />
            </div>
            <button type="button" class="vpe-search-categories-toggle" data-search-categories-toggle aria-expanded="false" aria-controls="vpe-search-categories">
                ${svgIcon('squares-2x2')}
                <span>Categorías</span>
                ${svgIcon('chevron-down', 'vpe-search-categories-chevron')}
            </button>
        </div>

        <div class="vpe-search-categories" id="vpe-search-categories" data-search-categories role="group" aria-label="Filtrar por categoría"></div>

        <hr class="vpe-search-divider">

        <div class="vpe-search-bottom-row">
            <div class="vpe-search-select-wrap">
                ${svgIcon('map-pin', 'vpe-search-select-icon')}
                <label class="vpe-search-visually-hidden" for="vpe-search-plazas">Viajar a otra plaza</label>
                <select id="vpe-search-plazas" class="vpe-search-select" data-search-plazas>
                    <option value="">Viajar a otra plaza…</option>
                </select>
                ${svgIcon('chevron-down', 'vpe-search-select-chevron')}
            </div>
            <button type="button" class="vpe-search-clear" data-search-clear>Mostrar todos</button>
        </div>

        <div class="vpe-search-count" data-search-count aria-live="polite"></div>
        <div class="vpe-search-results" data-search-results aria-live="polite"></div>
    `;

    overlay.appendChild(backdrop);
    overlay.appendChild(panel);
    (container ?? document.body).appendChild(toggle);
    document.body.appendChild(overlay);

    const input = panel.querySelector('.vpe-search-input');
    const categoriesToggleButton = panel.querySelector('[data-search-categories-toggle]');
    const chipsEl = panel.querySelector('[data-search-categories]');
    const plazasEl = panel.querySelector('[data-search-plazas]');
    const clearButton = panel.querySelector('[data-search-clear]');
    const countEl = panel.querySelector('[data-search-count]');
    const resultsEl = panel.querySelector('[data-search-results]');
    const closeButton = panel.querySelector('[data-search-close]');

    let activeCategory = '';
    let debounceTimer = null;
    let fetchToken = 0;
    let keyBlockerBound = false;
    let wasLockedBeforeOpen = false;

    // Mismo mecanismo que `stand-vitrina-modal.js` (createVitrinaModal):
    // Escape cierra, y un listener en fase de captura evita que las teclas
    // de movimiento (WASD/flechas) lleguen a `bindInput()` del motor
    // mientras el panel está abierto — sin este bloqueo, escribir "s" en
    // el buscador también movía al personaje hacia atrás.
    function onKeyCapture(event) {
        if (event.type === 'keydown' && event.code === 'Escape') {
            closePanel();
        }

        event.stopPropagation();
    }

    function bindKeyBlocker() {
        if (keyBlockerBound) {
            return;
        }

        keyBlockerBound = true;
        window.addEventListener('keydown', onKeyCapture, true);
        window.addEventListener('keyup', onKeyCapture, true);
    }

    function unbindKeyBlocker() {
        if (!keyBlockerBound) {
            return;
        }

        keyBlockerBound = false;
        window.removeEventListener('keydown', onKeyCapture, true);
        window.removeEventListener('keyup', onKeyCapture, true);
    }

    function openPanel() {
        overlay.classList.add('is-visible');
        toggle.setAttribute('aria-expanded', 'true');

        wasLockedBeforeOpen = Boolean(document.pointerLockElement);

        if (wasLockedBeforeOpen) {
            document.exitPointerLock();
        }

        bindKeyBlocker();
        input.focus();
    }

    function closePanel() {
        overlay.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
        unbindKeyBlocker();
        toggle.focus();

        if (wasLockedBeforeOpen) {
            engine?.pointerLockTarget?.requestPointerLock?.();
        }
    }

    toggle.addEventListener('click', () => {
        if (overlay.classList.contains('is-visible')) {
            closePanel();
        } else {
            openPanel();
        }
    });
    backdrop.addEventListener('click', closePanel);
    closeButton.addEventListener('click', closePanel);

    function setCategoriesOpen(isOpen) {
        chipsEl.classList.toggle('is-open', isOpen);
        categoriesToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        categoriesToggleButton.classList.toggle('is-active', isOpen);
    }

    categoriesToggleButton.addEventListener('click', () => {
        setCategoriesOpen(!chipsEl.classList.contains('is-open'));
    });

    // --- Estado inicial desde la URL (compartible) --------------------
    const initialParams = new URLSearchParams(window.location.search);
    input.value = initialParams.get('q') ?? '';
    activeCategory = initialParams.get('categoria') ?? '';

    loadCategories();
    loadPlazas();
    applyLocalFilter();
    if (input.value || activeCategory) {
        openPanel();
        fetchGlobalResults();

        if (activeCategory) {
            setCategoriesOpen(true);
        }
    }

    async function loadCategories() {
        try {
            const res = await fetch('/api/v1/categorias');
            const { data } = await res.json();

            const entries = [{ slug: '', name: 'Todas', icon: 'star' }, ...data];

            chipsEl.innerHTML = entries.map((category) => {
                const isActive = category.slug === activeCategory;

                return `
                    <button type="button" class="vpe-search-category-card${isActive ? ' is-active' : ''}" data-category="${category.slug}" aria-pressed="${isActive ? 'true' : 'false'}">
                        ${svgIcon(category.icon, 'vpe-search-category-icon')}
                        <span>${escapeHtml(category.name)}</span>
                    </button>
                `;
            }).join('');

            chipsEl.querySelectorAll('[data-category]').forEach((chip) => {
                chip.addEventListener('click', () => {
                    activeCategory = chip.dataset.category;
                    chipsEl.querySelectorAll('[data-category]').forEach((c) => {
                        const isActive = c === chip;
                        c.classList.toggle('is-active', isActive);
                        c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                    onFilterChange();
                });
            });
        } catch {
            // Sin categorías, el buscador de texto sigue funcionando.
        }
    }

    async function loadPlazas() {
        try {
            const res = await fetch('/api/v1/municipios');
            const { data } = await res.json();

            data
                .filter((m) => m.immersive_lab_url && m.slug !== currentMunicipalitySlug)
                .forEach((m) => {
                    const option = document.createElement('option');
                    option.value = m.immersive_lab_url;
                    option.textContent = m.name;
                    plazasEl.appendChild(option);
                });
        } catch {
            // Sin selector de plaza, el resto del panel sigue funcionando.
        }
    }

    plazasEl.addEventListener('change', () => {
        if (plazasEl.value) {
            window.location.href = plazasEl.value;
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(onFilterChange, DEBOUNCE_MS);
    });

    clearButton.addEventListener('click', () => {
        input.value = '';
        activeCategory = '';
        chipsEl.querySelectorAll('[data-category]').forEach((c) => {
            const isActive = c.dataset.category === '';
            c.classList.toggle('is-active', isActive);
            c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        onFilterChange();
    });

    function onFilterChange() {
        applyLocalFilter();
        updateShareableUrl();
        fetchGlobalResults();

        // IMM-043: la deduplicación del servidor (misma plaza+tipo+
        // visitante dentro de 30 min) colapsa búsquedas sucesivas de la
        // misma sesión en un solo evento — a propósito, es la misma
        // disciplina anti-ruido que ya usa `RegisterAnalyticsEvent`
        // (contar "hubo búsqueda", no registrar cada tecla como una fila
        // aparte).
        if (input.value.trim()) {
            track?.('search_performed', { metadata: { query: input.value.trim().slice(0, 120) } });
        }

        if (activeCategory) {
            track?.('category_filtered', { metadata: { categoria: activeCategory } });
        }
    }

    function applyLocalFilter() {
        const query = input.value.trim().toLowerCase();
        let visibleCount = 0;

        for (const stand of stands) {
            const matchesQuery = query === '' || stand.business.name.toLowerCase().includes(query);
            const matchesCategory = activeCategory === '' || stand.business.category_slug === activeCategory;
            const matches = matchesQuery && matchesCategory;

            // `logoSprite`/`ownerFigure` viven aparte de `root` en la
            // escena (`dynamic-stand-loader.js`, `attachLogoBadge`/
            // `attachOwnerFigure` los agregan directo a `engine.world`,
            // nunca como hijos de `root`) — sin ocultarlos aquí también,
            // el logo y la persona del stand se quedaban visibles (y el
            // indicador de proximidad seguía activándose) aunque el
            // stand filtrado ya no se viera.
            if (stand.root) {
                stand.root.visible = matches;
            }
            if (stand.logoSprite) {
                stand.logoSprite.visible = matches;
            }
            if (stand.ownerFigure) {
                stand.ownerFigure.visible = matches;
            }

            if (matches) {
                visibleCount += 1;
            }
        }

        countEl.textContent = stands.length
            ? `${visibleCount} de ${stands.length} en esta plaza`
            : '';
    }

    function updateShareableUrl() {
        const url = new URL(window.location.href);
        input.value ? url.searchParams.set('q', input.value) : url.searchParams.delete('q');
        activeCategory ? url.searchParams.set('categoria', activeCategory) : url.searchParams.delete('categoria');
        window.history.replaceState(null, '', url);
    }

    async function fetchGlobalResults() {
        const query = input.value.trim();

        if (query === '' && activeCategory === '') {
            resultsEl.innerHTML = '';

            return;
        }

        const thisFetch = (fetchToken += 1);
        resultsEl.innerHTML = '<p class="vpe-search-loading">Buscando…</p>';

        try {
            const params = new URLSearchParams();
            if (query) params.set('q', query);
            if (activeCategory) params.set('categoria', activeCategory);

            const res = await fetch(`/api/v1/plaza?${params.toString()}`);
            const { data, meta } = await res.json();

            if (thisFetch !== fetchToken) {
                return;
            }

            renderGlobalResults(data ?? [], meta);
        } catch {
            if (thisFetch === fetchToken) {
                resultsEl.innerHTML = '<p class="vpe-search-empty">No pudimos buscar en este momento.</p>';
            }
        }
    }

    function renderGlobalResults(results, meta) {
        if (!results.length) {
            resultsEl.innerHTML = '<p class="vpe-search-empty">Sin resultados.</p>';

            return;
        }

        resultsEl.innerHTML = results.map((business) => {
            const location = business.immersive_location;
            let action;

            if (location && location.municipality_slug === currentMunicipalitySlug) {
                action = `<button type="button" class="vpe-search-result-action" data-open-vitrina data-slug="${escapeHtml(business.slug)}" data-name="${escapeHtml(business.name)}" data-logo="${escapeHtml(business.logo_url ?? '')}" data-url="${escapeHtml(business.url)}">Ver aquí</button>`;
            } else if (location) {
                action = `<a class="vpe-search-result-action" href="${escapeHtml(location.travel_url)}">Viajar a ${escapeHtml(location.plaza_name)}</a>`;
            } else {
                action = `<a class="vpe-search-result-action" href="${escapeHtml(business.url)}">Ver vitrina</a>`;
            }

            return `
                <div class="vpe-search-result">
                    <span class="vpe-search-result-name">${escapeHtml(business.name)}</span>
                    ${action}
                </div>
            `;
        }).join('');

        if (meta?.total !== undefined) {
            countEl.textContent += ` · ${meta.total} en toda la plaza`;
        }

        resultsEl.querySelectorAll('[data-open-vitrina]').forEach((button) => {
            button.addEventListener('click', () => {
                vitrinaModal?.open({
                    slug: button.dataset.slug,
                    name: button.dataset.name,
                    logo_url: button.dataset.logo || null,
                    vitrina_url: button.dataset.url,
                });
            });
        });
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';

    return div.innerHTML;
}

let stylesInjected = false;

function injectStylesOnce() {
    if (stylesInjected) {
        return;
    }

    stylesInjected = true;

    const style = document.createElement('style');
    style.textContent = `
        /* Vive dentro de #generic-header-actions (generic-plaza.blade.php)
           en vez de flotar aparte — mismo estilo plano que el resto de los
           íconos del header (ver .vpe-display-settings-toggle). */
        .vpe-search-toggle {
            width: 38px; height: 38px; border-radius: 999px; border: none;
            display: flex; align-items: center; justify-content: center;
            background: transparent; color: #fff; cursor: pointer;
        }

        .vpe-search-icon { width: 20px; height: 20px; flex-shrink: 0; }

        .vpe-search-overlay {
            position: fixed; inset: 0; z-index: 26;
            display: none; align-items: center; justify-content: center;
            padding: 20px;
        }

        .vpe-search-overlay.is-visible { display: flex; }

        .vpe-search-backdrop {
            position: absolute; inset: 0; border: none;
            background: rgba(10, 12, 18, 0.72); cursor: pointer;
        }

        .vpe-search-panel {
            position: relative; z-index: 1;
            width: 100%; max-width: 460px; max-height: 88vh; overflow-y: auto;
            background: #fff; color: #1f2430; border-radius: 20px;
            padding: 24px; box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
            display: block; font-family: inherit; font-size: 0.85rem;
        }

        .vpe-search-close {
            position: absolute; top: 16px; right: 16px;
            width: 34px; height: 34px; border-radius: 999px; border: none;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0, 0, 0, 0.06); cursor: pointer;
        }
        .vpe-search-close .vpe-search-icon { width: 16px; height: 16px; }

        .vpe-search-visually-hidden {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }

        .vpe-search-top-row { display: flex; gap: 10px; margin: 4px 34px 14px 0; }

        .vpe-search-input-wrap {
            flex: 1; min-width: 0; position: relative; display: flex; align-items: center;
        }
        .vpe-search-input-icon {
            position: absolute; left: 14px; color: #9aa0ac; pointer-events: none;
        }
        .vpe-search-input {
            width: 100%; padding: 11px 14px 11px 40px; border-radius: 999px;
            border: 1px solid #e1e3e8; font-size: 0.85rem; font-family: inherit;
            background: #fff;
        }

        .vpe-search-categories-toggle {
            flex-shrink: 0; display: flex; align-items: center; gap: 8px;
            padding: 0 16px; border-radius: 999px; border: none;
            background: #d7352a; color: #fff; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; white-space: nowrap;
        }
        .vpe-search-categories-chevron { transition: transform 0.2s ease; }
        .vpe-search-categories-toggle.is-active .vpe-search-categories-chevron { transform: rotate(180deg); }

        .vpe-search-categories {
            display: none; grid-template-columns: 1fr 1fr; gap: 10px;
            margin-bottom: 16px;
        }
        .vpe-search-categories.is-open { display: grid; }

        .vpe-search-category-card {
            display: flex; align-items: center; gap: 10px; text-align: left;
            padding: 12px 14px; border-radius: 14px; border: 1px solid #e1e3e8;
            background: #fff; color: #1f2430; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; line-height: 1.2;
        }
        .vpe-search-category-card .vpe-search-icon { color: #1f2430; }
        .vpe-search-category-card.is-active {
            background: #d7352a; border-color: #d7352a; color: #fff;
        }
        .vpe-search-category-card.is-active .vpe-search-icon { color: #fff; }

        .vpe-search-divider { border: none; border-top: 1px solid #eceef1; margin: 4px 0 16px; }

        .vpe-search-bottom-row { display: flex; gap: 10px; margin-bottom: 10px; }

        .vpe-search-select-wrap {
            flex: 1; min-width: 0; position: relative; display: flex; align-items: center;
        }
        .vpe-search-select-icon {
            position: absolute; left: 14px; color: #9aa0ac; pointer-events: none;
        }
        .vpe-search-select-chevron {
            position: absolute; right: 12px; color: #9aa0ac; pointer-events: none;
        }
        .vpe-search-select {
            width: 100%; appearance: none; -webkit-appearance: none; -moz-appearance: none;
            padding: 10px 34px 10px 40px; border-radius: 999px; border: 1px solid #e1e3e8;
            font-size: 0.82rem; font-family: inherit; background: #fff; color: #1f2430;
        }

        .vpe-search-clear {
            flex-shrink: 0; padding: 0 18px; border-radius: 999px; border: none;
            background: #d7352a; color: #fff; font-size: 0.85rem; font-weight: 700; cursor: pointer;
        }

        .vpe-search-count { font-size: 0.78rem; color: #7a8190; margin-bottom: 8px; }

        .vpe-search-results { display: flex; flex-direction: column; gap: 6px; }
        .vpe-search-loading, .vpe-search-empty { font-size: 0.8rem; color: #7a8190; }

        .vpe-search-result {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 8px 10px; border-radius: 10px; background: #f7f7f9;
        }
        .vpe-search-result-name { font-weight: 600; font-size: 0.8rem; }
        .vpe-search-result-action {
            flex-shrink: 0; padding: 5px 10px; border-radius: 999px; border: none;
            background: #d7352a; color: #fff; font-size: 0.72rem; font-weight: 700;
            text-decoration: none; cursor: pointer;
        }

        @media (max-width: 480px) {
            .vpe-search-panel { padding: 20px; border-radius: 16px; }
            .vpe-search-top-row { flex-direction: column; margin-right: 0; }
            .vpe-search-categories-toggle { justify-content: center; padding: 10px 16px; }
        }
    `;
    document.head.appendChild(style);
}
