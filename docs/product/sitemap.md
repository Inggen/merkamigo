# Sitemap y tokens de marca — Fase 0 (0.2 Experiencia, sitemap y diseño)

> Versión breve: define la información arquitectónica (sitemap, navegación, tokens) que la fundación técnica necesita para no bloquearse. El prototipo visual responsive completo y el design system con componentes/estados quedan pendientes para una sesión de diseño dedicada (ver "Pendiente" al final).

## Sitemap por experiencia

```
/
├── Público general
│   ├── / (landing)
│   ├── /como-funciona
│   ├── /municipios
│   ├── /categorias
│   ├── /soporte
│   ├── /terminos
│   └── /privacidad
│
├── Clientes (/clientes/**)
│   ├── /clientes            (inicio: municipio, buscador, categorías, destacados)
│   ├── /clientes/explorar
│   ├── /clientes/actividad
│   ├── /clientes/favoritos
│   ├── /clientes/cuenta
│   ├── /plaza/{municipio}
│   ├── /plaza/{municipio}/categorias/{categoria}
│   ├── /buscar
│   ├── /m/{slug-negocio}                       (vitrina pública)
│   ├── /m/{slug-negocio}/productos/{slug}
│   ├── /m/{slug-negocio}/qr
│   ├── /pidelo                                 (Fase 2)
│   ├── /pidelo/nueva                           (Fase 2)
│   ├── /mis-solicitudes                        (Fase 2)
│   └── /mis-solicitudes/{id}                   (Fase 2)
│
├── Emprendedores (/emprendedores/**)
│   ├── /emprendedores/bienvenida               (registro/login con propuesta de valor)
│   ├── /emprendedores                          (inicio: qué falta para vender)
│   ├── /emprendedores/negocios
│   ├── /emprendedores/crear-vitrina            ("Mi Merkamigo en 5 minutos")
│   ├── /emprendedores/negocios/{id}/vista-previa
│   ├── /emprendedores/negocios/{id}/vitrina
│   ├── /emprendedores/negocios/{id}/productos
│   ├── /emprendedores/negocios/{id}/compartir
│   ├── /emprendedores/negocios/{id}/metricas
│   └── /emprendedores/configuracion
│
└── Administración (Filament, /admin/**)
    ├── Usuarios, negocios, municipios, categorías
    ├── Revisión / publicación / suspensión de vitrinas
    ├── Moderación de productos, imágenes y reportes
    └── Auditoría
```

Regla compartida: una sola identidad/sesión. El selector "Quiero comprar/encontrar" vs. "Quiero vender/mostrar mi negocio" decide la experiencia activa, recordada en el perfil, y cambiable sin cerrar sesión ni duplicar cuenta.

## Vistas principales de referencia

### Experiencia Clientes

| Ref. | Vista | Fase | Ruta |
|---|---|---:|---|
| C01 | Inicio | 1 | `/clientes` |
| C02 | Plaza de mi municipio | 1-2 | `/plaza/{municipio}` |
| C03 | Resultados de búsqueda | 1 | `/buscar` |
| C04 | Vitrina Merkamigo | 1-3 | `/m/{slug-negocio}` |
| C05 | Detalle de producto | 1 | `/m/{slug-negocio}/productos/{slug}` |
| C06 | Pídelo en Merkamigo | 2 | `/pidelo` |

### Experiencia Emprendedores

| Ref. | Vista | Fase | Ruta |
|---|---|---:|---|
| E01 | Registro y bienvenida | 1 | `/emprendedores/bienvenida` |
| E02 | Mi Merkamigo en cinco minutos | 1 | `/emprendedores/crear-vitrina` |
| E03 | Vista previa asistida | 1 | `/emprendedores/negocios/{id}/vista-previa` |
| E04 | Editor de mi vitrina | 1 | `/emprendedores/negocios/{id}/vitrina` |
| E05 | Productos y servicios | 1 | `/emprendedores/negocios/{id}/productos` |
| E06 | Panel de control | 1 | `/emprendedores` |

## Tokens de marca

| Token | Valor |
|---|---|
| Rojo principal | `#D7352A` |
| Rojo oscuro | `#B9241B` |
| Negro carbón | `#1F1F21` |
| Gris grafito | `#4C4C50` |
| Gris claro | `#F4F4F4` |
| Títulos | Poppins SemiBold |
| Subtítulos | Poppins Medium |
| Texto | Inter Regular |

Estos tokens se cargarán como variables de Tailwind 4 (`@theme`) cuando se construyan las primeras pantallas de Fase 1; en esta fundación técnica solo queda documentado el valor, no aplicado a componentes visuales todavía.

## Pendiente

- Los flujos de 0.2 y el design system con componentes/estados ya están documentados en [`docs/ux-flows/`](../ux-flows/README.md) y [`docs/design-system/README.md`](../design-system/README.md), a partir de lo realmente construido en Fase 1 — no como un prototipo visual aparte.
- Sigue pendiente: validación de legibilidad, tamaño de botones y navegación con una mano con usuarios reales (requiere una sesión con usuarios, no solo documentación).

## Láminas de referencia de 0.2.2

El 27 de julio de 2026 el usuario compartió las dos láminas aprobadas de 0.2.2 (Experiencia Clientes: C01-C06; Experiencia Emprendedores: E01-E06). Se usaron para confirmar que el logo, el rojo de marca y los nombres de navegación ya construidos en esta fundación técnica coinciden con el diseño de referencia (p. ej. ítems de navegación Cliente: Explorar, Mensajes, Favoritos). El contenido completo de cada pantalla (tarjetas de negocios, buscador con filtros, editor de vitrina con pestañas, panel con métricas) se construye en Fase 1 siguiendo esas láminas como diseño definitivo; no se adelantó en este pase de Fase 0.
