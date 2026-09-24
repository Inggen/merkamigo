let widgetScriptPromise = null;

function loadWompiWidget() {
    if (window.WidgetCheckout) return Promise.resolve();
    if (widgetScriptPromise) return widgetScriptPromise;

    widgetScriptPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://checkout.wompi.co/widget.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('No se pudo cargar el widget de pago de Wompi.'));
        document.head.appendChild(script);
    });

    return widgetScriptPromise;
}

function openWidget(payload) {
    const checkout = new window.WidgetCheckout({
        currency: payload.currency,
        amountInCents: payload.amountInCents,
        reference: payload.reference,
        publicKey: payload.publicKey,
        signature: { integrity: payload.signature },
    });

    checkout.open((result) => {
        const transactionId = result?.transaction?.id;
        const separator = payload.redirectUrl.includes('?') ? '&' : '?';

        window.location.href = transactionId
            ? `${payload.redirectUrl}${separator}id=${transactionId}`
            : payload.redirectUrl;
    });
}

/**
 * Abre el checkout de Wompi en una modal, sin salir de la página (pedido
 * del usuario: el botón de pagar redirigía a la página hospedada de
 * Wompi en vez de abrir el pago en una modal). `url` es la misma ruta que
 * antes se navegaba directo (`marketplace.checkout.create`); si algo
 * falla (red, widget bloqueado, JS deshabilitado) cae de vuelta a
 * navegar esa misma URL, que sigue sirviendo el flujo de redirect de
 * respaldo (`OrderCheckoutController::create`).
 */
window.merkamigoOpenWompiCheckout = async function (url) {
    let payload;

    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const body = await response.json().catch(() => null);
            throw new Error(body?.message ?? 'No se pudo iniciar el pago.');
        }

        payload = await response.json();
        await loadWompiWidget();
    } catch (error) {
        window.location.href = url;
        return;
    }

    openWidget(payload);
};

/**
 * Misma idea que `merkamigoOpenWompiCheckout`, pero para el carrito del
 * Live (`marketplace.live.checkout`), que es un <form method="POST"> con
 * CSRF y throttle — no un simple link GET. Recibe el propio elemento
 * <form> (incluye el token vía @csrf) en vez de una URL. Si el fetch
 * falla, se deja que el <form> real se someta (respaldo: abre pestaña
 * nueva al checkout hospedado, comportamiento anterior).
 */
window.merkamigoOpenWompiCheckoutPost = async function (form) {
    let payload;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            body: new FormData(form),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => null);
            throw new Error(body?.message ?? 'No se pudo iniciar el pago.');
        }

        payload = await response.json();
        await loadWompiWidget();
    } catch (error) {
        form.submit();
        return;
    }

    openWidget(payload);
};
