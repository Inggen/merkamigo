# Plaza del municipio

`C02`, `/plaza/{municipio}` y `/plaza/{municipio}/categorias/{categoria}` (`PlazaController::show`/`category`), más `/buscar` (`PlazaController::buscar`) para la búsqueda transversal por nombre, producto, servicio y categoría. Las tres vistas comparten el layout de Cliente; en estas rutas la cabecera oculta su propio selector de municipio (`:show-municipality-selector="false"`) porque `<x-clientes.search-hero>` ya resuelve el contexto de municipio en la página.

## Contenido por municipio

- Fondo del hero/buscador: siempre se toma de la configuración del municipio en admin (`hero_video_path` o `cover_path`). No se debe reemplazar por imágenes mock, assets de experiencias inmersivas ni fondos hardcodeados por slug, salvo el fallback genérico cuando el municipio no tenga nada configurado.
- Negocios publicados del municipio, filtrables por categoría (ruta), zona y disponibilidad (querystring `zona`, `disponibles`).
- Sección "Destacados": negocios con `featured_until` vigente (destacado manual desde administración).
- Sección "Nuevos": los publicados más recientes que no están destacados.
- Estado vacío explícito cuando una categoría no tiene oferta en el municipio.

## Pendiente

- Cercanía como filtro (depende de que el visitante conceda ubicación; el municipio ya tiene coordenadas para negocios/otras vistas, pero el filtro de distancia en la Plaza no está construido).
- "Ofertas locales" y "recomendados" quedan fuera de este pase — ofertas locales necesita más volumen real de promociones, y recomendados depende de datos de Fase 3 (verificación/recomendaciones).
- Sección "Solicitudes actuales" reservada para Fase 2 ("Pídelo en Merkamigo").
