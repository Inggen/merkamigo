// Registro del service worker (1.10 del TODO): habilita la instalación como
// PWA y la página offline informativa. Ver public/sw.js.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Si falla el registro (p. ej. en un navegador sin soporte
            // completo), la app sigue funcionando normalmente con red.
        });
    });
}
