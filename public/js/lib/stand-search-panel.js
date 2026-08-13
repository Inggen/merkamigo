/**
 * IMM-034: buscador + chips de categoría + selector de plaza dentro de la
 * plaza inmersiva. Mismo patrón autocontenido que `stand-proximity.js`/
 * `stand-vitrina-modal.js` (DOM + estilos propios desde JS, sin depender
 * de las clases HUD de cada escena).
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

export function attachSearchPanel(engine, stands, { currentMunicipalitySlug = null, vitrinaModal = null, track = null } = {}) {
    injectStylesOnce();

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'vpe-search-toggle';
    toggle.id = 'vpe-search-toggle';
    toggle.setAttribute('aria-label', 'Buscar en la plaza');
    toggle.setAttribute('aria-haspopup', 'dialog');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', 'vpe-search-panel');
    toggle.textContent = '🔍';

    const panel = document.createElement('div');
    panel.className = 'vpe-search-panel';
    panel.id = 'vpe-search-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'Buscar negocios en la plaza');
    panel.innerHTML = `
        <div class="vpe-search-row">
            <label class="vpe-search-visually-hidden" for="vpe-search-input">Buscar negocio o producto</label>
            <input id="vpe-search-input" type="search" class="vpe-search-input" placeholder="Buscar negocio o producto…" />
        </div>
        <div class="vpe-search-chips" data-search-categories role="group" aria-label="Filtrar por categoría"></div>
        <div class="vpe-search-row">
            <label class="vpe-search-visually-hidden" for="vpe-search-plazas">Viajar a otra plaza</label>
            <select id="vpe-search-plazas" class="vpe-search-select" data-search-plazas>
                <option value="">Viajar a otra plaza…</option>
            </select>
            <button type="button" class="vpe-search-clear" data-search-clear>Mostrar todos</button>
        </div>
        <div class="vpe-search-count" data-search-count aria-live="polite"></div>
        <div class="vpe-search-results" data-search-results aria-live="polite"></div>
    `;

    document.body.appendChild(toggle);
    document.body.appendChild(panel);

    const input = panel.querySelector('.vpe-search-input');
    const chipsEl = panel.querySelector('[data-search-categories]');
    const plazasEl = panel.querySelector('[data-search-plazas]');
    const clearButton = panel.querySelector('[data-search-clear]');
    const countEl = panel.querySelector('[data-search-count]');
    const resultsEl = panel.querySelector('[data-search-results]');

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
        panel.classList.add('is-visible');
        toggle.setAttribute('aria-expanded', 'true');

        wasLockedBeforeOpen = Boolean(document.pointerLockElement);

        if (wasLockedBeforeOpen) {
            document.exitPointerLock();
        }

        bindKeyBlocker();
        input.focus();
    }

    function closePanel() {
        panel.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
        unbindKeyBlocker();
        toggle.focus();

        if (wasLockedBeforeOpen) {
            engine?.pointerLockTarget?.requestPointerLock?.();
        }
    }

    toggle.addEventListener('click', () => {
        if (panel.classList.contains('is-visible')) {
            closePanel();
        } else {
            openPanel();
        }
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
    }

    async function loadCategories() {
        try {
            const res = await fetch('/api/v1/categorias');
            const { data } = await res.json();

            chipsEl.innerHTML = ['', ...data.map((c) => c.slug)].map((slug) => {
                const category = data.find((c) => c.slug === slug);
                const label = category ? category.name : 'Todas';

                return `<button type="button" class="vpe-search-chip${slug === activeCategory ? ' is-active' : ''}" data-category="${slug}" aria-pressed="${slug === activeCategory ? 'true' : 'false'}">${escapeHtml(label)}</button>`;
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

            if (stand.root) {
                stand.root.visible = matches;
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
        .vpe-search-toggle {
            position: fixed; top: 20px; right: 20px; z-index: 25;
            width: 44px; height: 44px; border-radius: 999px; border: none;
            background: rgba(10, 12, 18, 0.72); color: #fff; font-size: 1.1rem;
            cursor: pointer; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .vpe-search-panel {
            position: fixed; top: 72px; right: 20px; z-index: 25;
            width: min(320px, calc(100vw - 40px));
            max-height: 70vh; overflow-y: auto;
            background: #fff; color: #1f2430; border-radius: 16px;
            padding: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            display: none; font-family: inherit; font-size: 0.85rem;
        }

        .vpe-search-panel.is-visible { display: block; }

        .vpe-search-visually-hidden {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }

        .vpe-search-row { display: flex; gap: 8px; margin-bottom: 10px; }
        .vpe-search-input, .vpe-search-select {
            flex: 1; padding: 8px 10px; border-radius: 10px; border: 1px solid #d8dae0;
            font-size: 0.85rem; font-family: inherit;
        }

        .vpe-search-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
        .vpe-search-chip {
            padding: 5px 12px; border-radius: 999px; border: 1px solid #d8dae0;
            background: #fff; font-size: 0.76rem; cursor: pointer; font-weight: 600;
        }
        .vpe-search-chip.is-active { background: #d7352a; border-color: #d7352a; color: #fff; }

        .vpe-search-clear {
            padding: 8px 12px; border-radius: 10px; border: 1px solid #d8dae0;
            background: #f5f5f7; font-size: 0.78rem; font-weight: 700; cursor: pointer;
        }

        .vpe-search-count { font-size: 0.76rem; color: #7a8190; margin-bottom: 8px; }

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
    `;
    document.head.appendChild(style);
}
