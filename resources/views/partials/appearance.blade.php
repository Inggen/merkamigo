<script>
    if (! window.localStorage.getItem('flux.appearance')) {
        window.localStorage.setItem('flux.appearance', 'light');
    }
</script>
@fluxAppearance
<script>
    // Flux elimina la preferencia al elegir Sistema; conservarla distingue
    // esa elección explícita de la primera visita (que usa Claro).
    (() => {
        const applyMerkamigoAppearance = window.Flux.applyAppearance;
        window.Flux.applyAppearance = function (appearance) {
            applyMerkamigoAppearance(appearance);
            if (appearance === 'system') {
                window.localStorage.setItem('flux.appearance', 'system');
            }
        };
    })();
</script>
