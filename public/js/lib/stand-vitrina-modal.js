/**
 * IMM-033: modal de vitrina — se abre desde el indicador "Ver vitrina"
 * (IMM-032, `stand-proximity.js`, opción `onOpen`) en vez de navegar a la
 * página completa. Nunca duplica el catálogo dentro de la plaza 3D: cada
 * apertura pide los datos frescos a los mismos endpoints públicos que ya
 * usa la vitrina real (`/api/v1/plaza/negocios/{slug}` y
 * `/api/v1/plaza/negocios/{slug}/productos`), así que cualquier cambio en
 * la vitrina web se ve aquí sin tocar la escena.
 *
 * Todo lo mostrado sale de datos reales de la API — no se inventa ninguna
 * calificación numérica (no existe ese campo en este codebase; solo se
 * muestra el conteo real de reseñas) ni ningún dato que el negocio no
 * tenga cargado.
 *
 * Construido encima del motor sin modificar `voxel-plaza-engine.js`: usa
 * `document.exitPointerLock()`/`requestPointerLock()` (API estándar del
 * navegador, sobre `engine.pointerLockTarget`, ya público) para soltar el
 * cursor al abrir y devolverlo al modo inmersivo al cerrar — así se puede
 * navegar el contenido del modal con el mouse normal — y un listener de
 * teclado en fase de captura (aditivo, no toca `bindInput`) para que el
 * personaje no se mueva solo mientras el modal está abierto.
 */

export function createVitrinaModal(engine, { track = null } = {}) {
    injectStylesOnce();

    const overlay = document.createElement('div');
    overlay.className = 'vpe-vitrina-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Vitrina del negocio');

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'vpe-vitrina-backdrop';
    backdrop.setAttribute('aria-label', 'Cerrar');

    const panel = document.createElement('div');
    panel.className = 'vpe-vitrina-panel';

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'vpe-vitrina-close';
    closeButton.setAttribute('aria-label', 'Cerrar vitrina');
    closeButton.innerHTML = '&times;';

    const content = document.createElement('div');
    content.className = 'vpe-vitrina-content';

    const imageLightbox = document.createElement('div');
    imageLightbox.className = 'vpe-vitrina-image-lightbox';
    imageLightbox.setAttribute('aria-hidden', 'true');

    const imageLightboxBackdrop = document.createElement('button');
    imageLightboxBackdrop.type = 'button';
    imageLightboxBackdrop.className = 'vpe-vitrina-image-lightbox-backdrop';
    imageLightboxBackdrop.setAttribute('aria-label', 'Cerrar imagen ampliada');

    const imageLightboxFrame = document.createElement('div');
    imageLightboxFrame.className = 'vpe-vitrina-image-lightbox-frame';

    const imageLightboxClose = document.createElement('button');
    imageLightboxClose.type = 'button';
    imageLightboxClose.className = 'vpe-vitrina-image-lightbox-close';
    imageLightboxClose.setAttribute('aria-label', 'Cerrar imagen ampliada');
    imageLightboxClose.innerHTML = '&times;';

    const imageLightboxImage = document.createElement('img');
    imageLightboxImage.className = 'vpe-vitrina-image-lightbox-image';
    imageLightboxImage.alt = '';

    imageLightboxFrame.appendChild(imageLightboxClose);
    imageLightboxFrame.appendChild(imageLightboxImage);
    imageLightbox.appendChild(imageLightboxBackdrop);
    imageLightbox.appendChild(imageLightboxFrame);

    panel.appendChild(closeButton);
    panel.appendChild(content);
    overlay.appendChild(backdrop);
    overlay.appendChild(panel);
    overlay.appendChild(imageLightbox);
    document.body.appendChild(overlay);

    let requestToken = 0;
    let keyBlockerBound = false;
    let wasLockedBeforeOpen = false;

    function openImageZoom(src, alt = '') {
        if (!src) {
            return;
        }

        imageLightboxImage.src = src;
        imageLightboxImage.alt = alt;
        imageLightbox.classList.add('is-visible');
        imageLightbox.setAttribute('aria-hidden', 'false');
    }

    function closeImageZoom() {
        imageLightbox.classList.remove('is-visible');
        imageLightbox.setAttribute('aria-hidden', 'true');
        imageLightboxImage.removeAttribute('src');
        imageLightboxImage.alt = '';
    }

    function close() {
        closeImageZoom();
        overlay.classList.remove('is-visible');
        unbindKeyBlocker();

        // Vuelve a modo inmersivo solo si el visitante ya estaba ahí antes
        // de que se abriera el modal (auto o por clic) — si nunca había
        // entrado en pointer lock (o está en móvil, donde no aplica), no
        // se le fuerza uno.
        if (wasLockedBeforeOpen) {
            engine?.pointerLockTarget?.requestPointerLock?.();
        }
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

    function onKeyCapture(event) {
        if (event.type === 'keydown' && event.code === 'Escape') {
            if (imageLightbox.classList.contains('is-visible')) {
                closeImageZoom();

                event.preventDefault();
                event.stopPropagation();

                return;
            }

            close();
        }

        // Bloquea el input ANTES de que llegue a bindInput() del motor —
        // el personaje no camina/salta mientras el modal está abierto.
        event.stopPropagation();
    }

    backdrop.addEventListener('click', close);
    closeButton.addEventListener('click', close);
    imageLightboxBackdrop.addEventListener('click', closeImageZoom);
    imageLightboxClose.addEventListener('click', closeImageZoom);

    async function open(business) {
        requestToken += 1;
        const thisRequest = requestToken;

        wasLockedBeforeOpen = Boolean(document.pointerLockElement);

        if (wasLockedBeforeOpen) {
            document.exitPointerLock();
        }

        overlay.classList.add('is-visible');
        bindKeyBlocker();
        content.innerHTML = renderLoading();

        try {
            const [businessRes, productsRes] = await Promise.all([
                fetch(`/api/v1/plaza/negocios/${business.slug}`),
                fetch(`/api/v1/plaza/negocios/${business.slug}/productos`),
            ]);

            if (thisRequest !== requestToken) {
                return; // se abrió otro stand mientras esta petición seguía en curso
            }

            if (!businessRes.ok) {
                throw new Error(`business fetch failed: ${businessRes.status}`);
            }

            const { data: businessData } = await businessRes.json();
            const { data: products } = productsRes.ok ? await productsRes.json() : { data: [] };

            // IMM-043: solo cuenta como "vitrina abierta" una carga que
            // realmente resolvió — un intento fallido (ver el catch de
            // abajo) no cuenta como apertura real.
            track?.('vitrina_opened', { businessId: businessData.id });

            content.innerHTML = renderLoaded(businessData, products ?? []);
            bindContentHandlers(businessData);
            bindProductViewTracking(products ?? [], businessData.id);
        } catch {
            if (thisRequest === requestToken) {
                content.innerHTML = renderError(business);
            }
        }
    }

    function bindContentHandlers(businessData) {
        const shareButton = content.querySelector('[data-vitrina-share]');

        shareButton?.addEventListener('click', () => {
            navigator.clipboard?.writeText(businessData.url).catch(() => {});
            fetch(`${businessData.url}/compartir`, { method: 'POST' }).catch(() => {});
            const label = shareButton.querySelector('.vpe-vitrina-btn-label');
            const original = label?.textContent;

            if (label) {
                label.textContent = '¡Enlace copiado!';
                setTimeout(() => {
                    label.textContent = original;
                }, 1800);
            }
        });

        content.querySelector('[data-vitrina-whatsapp]')?.addEventListener('click', () => {
            track?.('whatsapp_click', { businessId: businessData.id });
        });

        bindCarousel();
        bindImageZoom();
        bindChat(businessData);
    }

    /**
     * Chat con IA de la vitrina (solo si `business.available_ai_chat`,
     * ver `Business::canUseAiChatbot()`). Sin estado en el servidor: el
     * historial vive en esta clausura mientras el modal está abierto y se
     * reenvía completo en cada mensaje (`AnswerBusinessChatQuestion` lo
     * usa como contexto, no lo persiste).
     */
    function bindChat(businessData) {
        const form = content.querySelector('[data-vitrina-chat-form]');
        const input = content.querySelector('[data-vitrina-chat-input]');
        const messages = content.querySelector('[data-vitrina-chat-messages]');
        const submitButton = form?.querySelector('button[type="submit"]');

        if (!form || !input || !messages) {
            return;
        }

        const history = [];
        let sending = false;

        function appendBubble(role, text) {
            const bubble = document.createElement('div');
            bubble.className = `vpe-vitrina-chat-bubble vpe-vitrina-chat-bubble-${role}`;
            bubble.textContent = text;
            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;

            return bubble;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const question = input.value.trim();

            if (!question || sending) {
                return;
            }

            sending = true;
            input.value = '';
            input.disabled = true;
            if (submitButton) submitButton.disabled = true;

            appendBubble('user', question);
            const typingBubble = appendBubble('assistant', 'Escribiendo…');
            typingBubble.classList.add('is-typing');

            track?.('vitrina_chat_message_sent', { businessId: businessData.id });

            try {
                const response = await fetch(`/api/v1/plaza/negocios/${businessData.slug}/chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ question, history }),
                });

                const { data } = response.ok ? await response.json() : { data: null };

                typingBubble.remove();

                if (data?.answer) {
                    appendBubble('assistant', data.answer);
                    history.push({ role: 'user', content: question });
                    history.push({ role: 'assistant', content: data.answer });

                    // Solo se reenvían los últimos turnos — suficiente para
                    // dar continuidad sin acumular un contexto sin límite.
                    while (history.length > 12) {
                        history.shift();
                    }
                } else {
                    appendBubble('assistant', 'No pude responder en este momento. Escríbele directo al negocio para que te ayude.');
                }
            } catch {
                typingBubble.remove();
                appendBubble('assistant', 'No pude responder en este momento. Escríbele directo al negocio para que te ayude.');
            } finally {
                sending = false;
                input.disabled = false;
                if (submitButton) submitButton.disabled = false;
                input.focus();
            }
        });
    }

    function bindImageZoom() {
        content.querySelectorAll('[data-vitrina-zoom-src]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                openImageZoom(trigger.dataset.vitrinaZoomSrc, trigger.dataset.vitrinaZoomAlt ?? '');
            });
        });
    }

    /**
     * IMM-043: "producto visto" solo cuenta cuando la tarjeta realmente
     * entra al viewport del carrusel (no al abrir el modal, ni por estar
     * en el DOM sin haberse desplazado hasta ahí) — un `IntersectionObserver`
     * acotado al propio contenedor del modal, desconectado al cerrar/
     * reabrir para no acumular observadores de aperturas anteriores.
     */
    function bindProductViewTracking(products, businessId) {
        if (! track || ! products.length) {
            return;
        }

        const cards = content.querySelectorAll('[data-vitrina-carousel] [data-product-id]');

        if (! cards.length) {
            return;
        }

        const seen = new Set();
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                const productId = entry.target.dataset.productId;

                if (seen.has(productId)) {
                    return;
                }

                seen.add(productId);
                track('product_viewed', { businessId, productId: Number(productId) });
            });
        }, { root: content, threshold: 0.6 });

        cards.forEach((card) => observer.observe(card));
    }

    function bindCarousel() {
        const track = content.querySelector('[data-vitrina-carousel]');
        const dots = content.querySelectorAll('[data-vitrina-dots] .vpe-vitrina-dot');
        const nextButton = content.querySelector('[data-vitrina-next]');

        if (!track || !dots.length) {
            return;
        }

        const cardStep = () => (track.children[0]?.getBoundingClientRect().width ?? 0) + 16;

        nextButton?.addEventListener('click', () => {
            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 8;
            track.scrollTo({ left: atEnd ? 0 : track.scrollLeft + cardStep(), behavior: 'smooth' });
        });

        track.addEventListener('scroll', () => {
            const index = Math.round(track.scrollLeft / cardStep());
            dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
        }, { passive: true });
    }

    return { open, close };
}

function renderLoading() {
    return `
        <div class="vpe-vitrina-state">
            <div class="vpe-vitrina-spinner" aria-hidden="true"></div>
            <p>Cargando vitrina…</p>
        </div>
    `;
}

function renderError(business) {
    return `
        <div class="vpe-vitrina-state">
            <p>No pudimos cargar la vitrina de <strong>${escapeHtml(business.name)}</strong> en este momento.</p>
            <a class="vpe-vitrina-btn vpe-vitrina-btn-primary" href="${escapeHtml(business.vitrina_url)}">Ver vitrina completa</a>
        </div>
    `;
}

function renderLoaded(business, products) {
    const recommendations = business.recommendations_summary ?? { count: 0, recent: [] };
    const municipality = business.municipality;
    const location = municipality ? [municipality.name, municipality.department].filter(Boolean).join(', ') : null;

    const metaItems = [
        location ? `<span>📍 ${escapeHtml(location)}</span>` : '',
        // Solo el conteo real de reseñas — no existe una calificación
        // numérica promedio en este codebase, así que nunca se inventa una.
        recommendations.count > 0 ? `<span>⭐ ${recommendations.count} ${recommendations.count === 1 ? 'reseña' : 'reseñas'}</span>` : '',
        business.has_verified_badge ? `<span>✅ ${escapeHtml(business.verified_badge_label || 'Negocio verificado')}</span>` : '',
    ].filter(Boolean).join('');

    return `
        <div class="vpe-vitrina-header">
            <div class="vpe-vitrina-header-info">
                <div class="vpe-vitrina-header-top">
                    ${business.logo_url ? `<img class="vpe-vitrina-logo" src="${escapeHtml(business.logo_url)}" alt="">` : ''}
                    <div>
                        <h2 class="vpe-vitrina-name">${escapeHtml(business.name)}</h2>
                        <span class="vpe-vitrina-local-badge">🏪 Emprendimiento local</span>
                    </div>
                </div>
                ${business.headline || business.description ? `<p class="vpe-vitrina-description">${escapeHtml(business.headline || business.description)}</p>` : ''}
                ${metaItems ? `<div class="vpe-vitrina-meta">${metaItems}</div>` : ''}
            </div>
            ${business.cover_url ? `<button type="button" class="vpe-vitrina-image-trigger vpe-vitrina-cover-trigger" data-vitrina-zoom-src="${escapeHtml(business.cover_url)}" data-vitrina-zoom-alt="${escapeHtml(business.name)}"><img class="vpe-vitrina-cover" src="${escapeHtml(business.cover_url)}" alt="${escapeHtml(business.name)}"></button>` : ''}
        </div>

        <hr class="vpe-vitrina-divider">

        <div class="vpe-vitrina-section-head">
            <div class="vpe-vitrina-section-title-group">
                <span class="vpe-vitrina-section-icon">🛍️</span>
                <h3 class="vpe-vitrina-section-title">Productos y servicios</h3>
            </div>
            <a class="vpe-vitrina-see-all" href="${escapeHtml(business.url)}">Ver catálogo ›</a>
        </div>

        ${products.length ? `
            <div class="vpe-vitrina-carousel-wrap">
                <div class="vpe-vitrina-carousel" data-vitrina-carousel>
                    ${products.slice(0, 8).map(renderProductCard).join('')}
                </div>
                ${products.length > 1 ? `
                    <div class="vpe-vitrina-carousel-controls">
                        <div class="vpe-vitrina-dots" data-vitrina-dots>
                            ${products.slice(0, 8).map((_, i) => `<span class="vpe-vitrina-dot${i === 0 ? ' is-active' : ''}"></span>`).join('')}
                        </div>
                        <button type="button" class="vpe-vitrina-carousel-next" data-vitrina-next aria-label="Ver siguiente producto">›</button>
                    </div>
                ` : ''}
            </div>
        ` : '<p class="vpe-vitrina-empty">Este negocio todavía no tiene productos publicados.</p>'}

        ${business.available_ai_chat ? renderChatSection() : ''}

        ${business.hours_note ? `
            <div class="vpe-vitrina-hours-row">
                <span class="vpe-vitrina-hours-icon">🕐</span>
                <span class="vpe-vitrina-hours-label">Horario de atención</span>
                <span class="vpe-vitrina-hours-value">${escapeHtml(business.hours_note)}</span>
            </div>
        ` : ''}

        ${recommendations.count > 0 ? `
            <h3 class="vpe-vitrina-section-title vpe-vitrina-section-title-compact">Reseñas (${recommendations.count})</h3>
            <div class="vpe-vitrina-recommendations">
                ${recommendations.recent.map((r) => `<p class="vpe-vitrina-recommendation">“${escapeHtml(r.body)}”</p>`).join('')}
            </div>
        ` : ''}

        <div class="vpe-vitrina-actions">
            ${business.whatsapp_number ? `
                <a class="vpe-vitrina-btn vpe-vitrina-btn-whatsapp" data-vitrina-whatsapp href="${escapeHtml(business.url)}/whatsapp" target="_blank" rel="noopener">
                    <span class="vpe-vitrina-btn-icon">💬</span>
                    <span class="vpe-vitrina-btn-text">
                        <span class="vpe-vitrina-btn-label">WhatsApp</span>
                        <span class="vpe-vitrina-btn-sub">Escríbenos ahora</span>
                    </span>
                </a>
            ` : ''}
            <button type="button" class="vpe-vitrina-btn" data-vitrina-share>
                <span class="vpe-vitrina-btn-icon">🔗</span>
                <span class="vpe-vitrina-btn-text">
                    <span class="vpe-vitrina-btn-label">Compartir</span>
                    <span class="vpe-vitrina-btn-sub">Comparte esta vitrina</span>
                </span>
            </button>
            <a class="vpe-vitrina-btn vpe-vitrina-btn-primary" href="${escapeHtml(business.url)}">
                <span class="vpe-vitrina-btn-icon">🏬</span>
                <span class="vpe-vitrina-btn-text">
                    <span class="vpe-vitrina-btn-label">Ver vitrina completa</span>
                    <span class="vpe-vitrina-btn-sub">Conoce más de este negocio</span>
                </span>
            </a>
        </div>

        <p class="vpe-vitrina-footer">🤍 Apoya lo local, impulsa nuestra comunidad</p>
    `;
}

function renderChatSection() {
    return `
        <hr class="vpe-vitrina-divider">

        <div class="vpe-vitrina-section-head">
            <div class="vpe-vitrina-section-title-group">
                <span class="vpe-vitrina-section-icon">🤖</span>
                <h3 class="vpe-vitrina-section-title">Pregúntale al negocio</h3>
            </div>
        </div>

        <div class="vpe-vitrina-chat">
            <div class="vpe-vitrina-chat-messages" data-vitrina-chat-messages>
                <div class="vpe-vitrina-chat-bubble vpe-vitrina-chat-bubble-assistant">👋 ¿Qué quieres saber? Puedo contarte sobre productos, precios, horarios y más.</div>
            </div>
            <form class="vpe-vitrina-chat-form" data-vitrina-chat-form>
                <input
                    type="text"
                    class="vpe-vitrina-chat-input"
                    data-vitrina-chat-input
                    placeholder="Escribe tu pregunta…"
                    maxlength="400"
                    autocomplete="off"
                >
                <button type="submit" class="vpe-vitrina-chat-send" aria-label="Enviar pregunta">➤</button>
            </form>
        </div>
    `;
}

function renderProductCard(product) {
    const photo = product.photos?.[0];

    return `
        <div class="vpe-vitrina-card" data-product-id="${product.id}">
            ${photo ? `<button type="button" class="vpe-vitrina-image-trigger vpe-vitrina-card-image-trigger" data-vitrina-zoom-src="${escapeHtml(photo)}" data-vitrina-zoom-alt="${escapeHtml(product.name)}"><img src="${escapeHtml(photo)}" alt="${escapeHtml(product.name)}"></button>` : '<div class="vpe-vitrina-card-placeholder"></div>'}
            <div class="vpe-vitrina-card-caption">
                <div class="vpe-vitrina-card-name">${escapeHtml(product.name)}</div>
                <div class="vpe-vitrina-card-price">${formatPrice(product)}</div>
            </div>
        </div>
    `;
}

function formatPrice(product) {
    const money = (value) => `$${Number(value).toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;

    let price;

    if (product.has_active_promo) {
        price = `<span class="vpe-vitrina-price-strike">${money(product.price)}</span> ${money(product.promo_price)}`;
    } else if (product.price_type === 'exacto' && product.price) {
        price = money(product.price);
    } else if (product.price_type === 'desde' && product.price) {
        price = `Desde ${money(product.price)}`;
    } else if (product.price_type === 'consultar') {
        price = 'Consultar precio';
    } else {
        price = '';
    }

    return product.unit ? `${price} <span class="vpe-vitrina-price-unit">/ ${escapeHtml(product.unit)}</span>` : price;
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
        .vpe-vitrina-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .vpe-vitrina-overlay.is-visible {
            display: flex;
        }

        .vpe-vitrina-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(10, 12, 18, 0.72);
            border: none;
            cursor: pointer;
        }

        .vpe-vitrina-panel {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 880px;
            max-height: 88vh;
            overflow-y: auto;
            background: #fff;
            color: #1f2430;
            border-radius: 20px;
            padding: 28px 32px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
            font-family: inherit;
        }

        .vpe-vitrina-close {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: none;
            background: rgba(0, 0, 0, 0.06);
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
        }

        .vpe-vitrina-state { text-align: center; padding: 40px 10px; }

        .vpe-vitrina-spinner {
            width: 30px; height: 30px; margin: 0 auto 14px;
            border-radius: 999px;
            border: 3px solid rgba(215, 53, 42, 0.2);
            border-top-color: #d7352a;
            animation: vpe-vitrina-spin 0.8s linear infinite;
        }

        .vpe-vitrina-image-lightbox {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .vpe-vitrina-image-lightbox.is-visible {
            display: flex;
        }

        .vpe-vitrina-image-lightbox-backdrop {
            position: absolute;
            inset: 0;
            border: none;
            background: rgba(10, 12, 18, 0.84);
            cursor: pointer;
        }

        .vpe-vitrina-image-lightbox-frame {
            position: relative;
            z-index: 1;
            width: min(92vw, 1100px);
            max-height: calc(92vh - 40px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vpe-vitrina-image-lightbox-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.92);
            color: #1f2430;
            font-size: 1.7rem;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
        }

        .vpe-vitrina-image-lightbox-image {
            display: block;
            max-width: 100%;
            max-height: calc(92vh - 40px);
            border-radius: 18px;
            object-fit: contain;
            background: #fff;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }

        @keyframes vpe-vitrina-spin { to { transform: rotate(360deg); } }

        .vpe-vitrina-header { display: flex; gap: 20px; align-items: flex-start; margin-bottom: 4px; padding-right: 34px; }
        .vpe-vitrina-header-info { flex: 1; min-width: 0; }
        .vpe-vitrina-header-top { display: flex; gap: 14px; align-items: center; }
        .vpe-vitrina-logo { width: 68px; height: 68px; border-radius: 999px; object-fit: cover; flex-shrink: 0; }
        .vpe-vitrina-name { margin: 0; font-size: 1.35rem; font-weight: 800; line-height: 1.2; }
        .vpe-vitrina-local-badge {
            display: inline-block; margin-top: 6px; padding: 3px 10px;
            border-radius: 999px; background: #fdeceb; color: #d7352a;
            font-size: 0.72rem; font-weight: 700;
        }
        .vpe-vitrina-badge {
            display: inline-block; margin-top: 6px; padding: 3px 10px;
            border-radius: 999px; background: #e6f6ea; color: #1c8a3f;
            font-size: 0.72rem; font-weight: 700;
        }

        .vpe-vitrina-description { font-size: 0.9rem; line-height: 1.5; color: #333a45; margin: 10px 0 0; }

        .vpe-vitrina-meta { display: flex; flex-wrap: wrap; gap: 4px 16px; margin-top: 10px; font-size: 0.82rem; color: #4b5160; }

        .vpe-vitrina-image-trigger {
            padding: 0;
            border: none;
            background: transparent;
            cursor: zoom-in;
        }

        .vpe-vitrina-cover-trigger {
            width: 240px;
            flex-shrink: 0;
        }

        .vpe-vitrina-cover { width: 240px; height: 140px; border-radius: 14px; object-fit: cover; flex-shrink: 0; display: block; }

        .vpe-vitrina-divider { border: none; border-top: 1px solid #eceef1; margin: 20px 0 16px; }

        .vpe-vitrina-section-head { display: flex; align-items: center; justify-content: space-between; margin: 18px 0 12px; }
        .vpe-vitrina-section-title-group { display: flex; align-items: center; gap: 8px; }
        .vpe-vitrina-section-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; border-radius: 7px; background: #fdeceb; font-size: 0.85rem;
        }
        .vpe-vitrina-section-title { font-size: 1.05rem; font-weight: 800; margin: 0; }
        .vpe-vitrina-section-title-compact { margin-top: 22px; margin-bottom: 10px; }
        .vpe-vitrina-see-all {
            font-size: 0.82rem; font-weight: 700; color: #1f2430; text-decoration: none;
            border: 1px solid #e1e3e8; border-radius: 999px; padding: 6px 14px; white-space: nowrap;
        }

        .vpe-vitrina-carousel-wrap { position: relative; }
        .vpe-vitrina-carousel {
            display: flex; gap: 16px; overflow-x: auto; scroll-snap-type: x proximity;
            padding-bottom: 4px; -webkit-overflow-scrolling: touch;
        }
        .vpe-vitrina-card {
            position: relative; flex: 0 0 auto; width: 190px; height: 240px;
            border-radius: 16px; overflow: hidden; background: #f0f1f4; scroll-snap-align: start;
        }
        .vpe-vitrina-card-image-trigger {
            width: 100%;
            height: 100%;
            display: block;
        }
        .vpe-vitrina-card img, .vpe-vitrina-card-placeholder { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vpe-vitrina-card-caption {
            position: absolute; left: 0; right: 0; bottom: 0; padding: 10px 12px 12px;
            background: linear-gradient(to top, rgba(0,0,0,0.72), rgba(0,0,0,0));
            color: #fff;
        }
        .vpe-vitrina-card-name { font-size: 0.85rem; font-weight: 700; line-height: 1.25; }
        .vpe-vitrina-card-price { font-size: 0.82rem; font-weight: 700; color: #ffd7d3; margin-top: 2px; }
        .vpe-vitrina-price-strike { color: rgba(255,255,255,0.6); text-decoration: line-through; font-weight: 500; }
        .vpe-vitrina-price-unit { color: rgba(255,255,255,0.75); font-weight: 500; }

        .vpe-vitrina-carousel-controls { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 12px; }
        .vpe-vitrina-dots { display: flex; gap: 6px; }
        .vpe-vitrina-dot { width: 6px; height: 6px; border-radius: 999px; background: #d8dae0; }
        .vpe-vitrina-dot.is-active { background: #1c8a3f; width: 16px; }
        .vpe-vitrina-carousel-next {
            width: 30px; height: 30px; border-radius: 999px; border: 1px solid #e1e3e8;
            background: #fff; font-size: 1rem; cursor: pointer; line-height: 1;
        }

        .vpe-vitrina-empty { font-size: 0.88rem; color: #7a8190; }

        .vpe-vitrina-hours-row {
            display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px;
            border: 1px solid #eceef1; border-radius: 999px; padding: 10px 18px; margin-top: 18px;
            font-size: 0.85rem; color: #333a45;
        }
        .vpe-vitrina-hours-label { font-weight: 700; }

        .vpe-vitrina-recommendations { display: flex; flex-direction: column; gap: 8px; }
        .vpe-vitrina-recommendation {
            font-size: 0.85rem; font-style: italic; color: #444b57;
            background: #f7f7f9; border-radius: 10px; padding: 10px 12px; margin: 0;
        }

        .vpe-vitrina-chat {
            display: flex; flex-direction: column; gap: 10px;
            border: 1px solid #eceef1; border-radius: 16px; padding: 14px;
            background: #fafafb;
        }
        .vpe-vitrina-chat-messages {
            display: flex; flex-direction: column; gap: 8px;
            max-height: 220px; overflow-y: auto; padding-right: 2px;
        }
        .vpe-vitrina-chat-bubble {
            max-width: 82%; padding: 8px 12px; border-radius: 14px;
            font-size: 0.85rem; line-height: 1.4; white-space: pre-line;
        }
        .vpe-vitrina-chat-bubble-assistant {
            align-self: flex-start; background: #fff; border: 1px solid #eceef1;
            color: #333a45; border-bottom-left-radius: 4px;
        }
        .vpe-vitrina-chat-bubble-user {
            align-self: flex-end; background: #d7352a; color: #fff;
            border-bottom-right-radius: 4px;
        }
        .vpe-vitrina-chat-bubble.is-typing { opacity: 0.6; font-style: italic; }
        .vpe-vitrina-chat-form { display: flex; gap: 8px; }
        .vpe-vitrina-chat-input {
            flex: 1; min-width: 0; padding: 10px 14px;
            border-radius: 999px; border: 1px solid #d8dae0;
            font-size: 0.88rem; font-family: inherit;
        }
        .vpe-vitrina-chat-input:disabled { opacity: 0.6; }
        .vpe-vitrina-chat-send {
            width: 40px; height: 40px; flex-shrink: 0; border-radius: 999px;
            border: none; background: #d7352a; color: #fff;
            font-size: 1rem; cursor: pointer;
        }
        .vpe-vitrina-chat-send:disabled { opacity: 0.6; cursor: default; }

        .vpe-vitrina-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .vpe-vitrina-btn {
            flex: 1; min-width: 180px; display: flex; align-items: center; gap: 10px;
            text-align: left; padding: 10px 16px;
            border-radius: 14px; border: 1px solid #d8dae0; background: #fff; color: #1f2430;
            text-decoration: none; cursor: pointer;
        }
        .vpe-vitrina-btn-icon { font-size: 1.15rem; }
        .vpe-vitrina-btn-text { display: flex; flex-direction: column; line-height: 1.2; }
        .vpe-vitrina-btn-label { font-size: 0.92rem; font-weight: 800; }
        .vpe-vitrina-btn-sub { font-size: 0.72rem; font-weight: 500; opacity: 0.85; }
        .vpe-vitrina-btn-primary { background: #d7352a; border-color: #d7352a; color: #fff; }
        .vpe-vitrina-btn-whatsapp { background: #25d366; border-color: #25d366; color: #fff; }

        .vpe-vitrina-footer { text-align: center; font-size: 0.78rem; color: #8a90a0; margin: 16px 0 4px; }

        @media (max-width: 720px) {
            .vpe-vitrina-panel { padding: 20px; border-radius: 16px; }
            .vpe-vitrina-cover-trigger { display: none; }
            .vpe-vitrina-logo { width: 56px; height: 56px; }
            .vpe-vitrina-name { font-size: 1.15rem; }
            .vpe-vitrina-card { width: 150px; height: 190px; }
        }
    `;
    document.head.appendChild(style);
}
