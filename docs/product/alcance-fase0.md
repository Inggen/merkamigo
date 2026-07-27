# Ficha de alcance — Fase 0 (0.1 Producto, alcance y medición)

> Versión breve. Formaliza decisiones que ya estaban expresadas en `TODO_MERKAMIGO.md`; no reemplaza una sesión de validación con usuarios reales, que sigue pendiente antes de automatizar cualquier cobro.

## Propuesta de valor

**"Descubre lo local, conecta con tu comunidad."**

## Público

- **Clientes/compradores:** personas que quieren descubrir negocios cercanos, buscar productos o servicios, publicar necesidades y contactar por WhatsApp.
- **Emprendedores:** dueños de negocio local que quieren una vitrina digital sencilla, visibilidad y contactos por WhatsApp.

## Municipios piloto

- Cajicá
- Zipaquirá

Los siguientes municipios se agregarán solo después de validar el piloto en estos dos.

## Categorías iniciales (provisionales)

Conjunto mínimo para poder sembrar datos y probar el flujo de creación de vitrina; se ajustará con datos reales del piloto:

- Alimentos y bebidas
- Moda y accesorios
- Hogar y decoración
- Belleza y cuidado personal
- Servicios profesionales
- Servicios para el hogar
- Salud y bienestar
- Otros

**Criterio de alta de nuevas categorías:** una categoría nueva se agrega solo cuando (a) al menos 3 negocios del mismo municipio la solicitan o encajan mal en las existentes, y (b) un administrador la aprueba explícitamente. No se crean categorías por autoservicio en el MVP.

## Datos mínimos que hacen publicable una vitrina

- Nombre del negocio
- Categoría
- Municipio y zona
- Número de WhatsApp válido
- Descripción breve
- Logo o foto principal
- Al menos un producto o servicio

## Soporte semi-asistido

Para emprendedores que no logren completar el flujo solos: acceso a "Ayúdame a terminar mi vitrina" (ver `0.1` y `1.2` del TODO), que abre un contacto de soporte por WhatsApp. La construcción de este flujo de soporte pertenece a Fase 1; aquí solo se deja documentada la necesidad.

## Esquema comercial inicial (hipótesis, no implementado)

| Producto | Precio de referencia |
|---|---|
| Perfil gratuito | $0 |
| Vitrina Pro | $49.900 COP pago único |
| Plan Emprendedor | $19.900 COP/mes |
| Kit Arranca Bonito | $99.900 COP |
| Destacado semanal | $9.900 COP |
| Oferta de lanzamiento | $39.900 COP por vitrina lista para vender por WhatsApp |

Estos precios son **hipótesis comerciales**. No se codifican en la aplicación ni se activa cobro alguno hasta validarlos con usuarios reales (Fase 4).

## Indicadores del piloto

| Indicador | Definición | Evento de origen | Periodicidad |
|---|---|---|---|
| Emprendedores registrados | Usuarios con al menos un negocio creado | `business created` | Diaria (acumulado) |
| Vitrinas publicadas | Negocios en estado `publicado` | Cambio de estado de `storefront`/`business` | Diaria |
| Tiempo promedio hasta publicar | Minutos entre inicio de "Mi Merkamigo en 5 minutos" y primera publicación | Timestamp de inicio de borrador vs. publicación | Semanal |
| Productos/servicios publicados | Productos en estado `publicado` | `product created`/`published` | Diaria |
| Visitas a vitrinas | Vistas registradas de `storefront` | `RegisterStoreView` | Diaria |
| Clics a WhatsApp | Clics en el botón de contacto | `RegisterWhatsAppClick` | Diaria |
| Enlaces o QR compartidos | Eventos de copia/descarga de enlace o QR | Evento de analítica dedicado | Diaria |
| Necesidades publicadas y conectadas | Necesidades creadas y cerradas con conexión | `PublishNeed` / cierre de necesidad | Semanal (se activa en Fase 2) |

## Fuera de alcance en esta ficha

Prototipos visuales completos, sistema de diseño con componentes y precios activados con cobro real — ver `docs/product/sitemap.md` y `docs/architecture/decisiones.md` para lo diferido explícitamente.
