# Inicio, exploración y panel del Cliente

## Navegación (`resources/views/components/cliente-nav.blade.php`)

Encabezado compartido en escritorio y móvil (mismo componente, sin una variante reducida): logo, selector de municipio, Explorar (`/buscar`), Favoritos (`route('clientes.favoritos')`, requiere sesión), menú de cuenta con "Mi cuenta", cambio de experiencia y cerrar sesión. "Mensajes" aparece deshabilitado con una insignia "Pronto" — es el marcador consciente del futuro Centro de actividad (2.x), no un enlace roto.

## Inicio (`C01`, `/clientes`, `ClientesController::home`)

- Sin municipio preferido (cookie `municipio` vacía): la vista ofrece elegir municipio manualmente o, si el navegador lo permite, autodetectar contra los municipios que ya tienen coordenadas (`Municipality::whereNotNull('latitude')`) — ver 1.5/geolocalización opcional.
- Con municipio preferido: buscador, categorías activas y hasta 6 negocios publicados más recientes del municipio ("destacados" reales, no una marca administrable todavía — ver `docs/architecture/decisiones.md`).
- Guardar municipio: `POST /clientes/municipio` (`SetPreferredMunicipality`), persistido en cookie; "Todos" la limpia.

## Exploración sin cuenta

- Buscar (`/buscar`), Plaza (`/plaza/{municipio}`) y vitrinas (`/m/{slug}`) son públicas: un visitante navega, guarda un negocio en memoria de sesión y comparte enlaces sin registrarse.
- Guardar en Favoritos y "Mis solicitudes" (Fase 2) sí piden cuenta; el CTA de login/registro conserva la URL de destino para no perder el punto del recorrido.

## Favoritos (`route('clientes.favoritos')`)

- Lista negocios y productos guardados (`$request->user()->favorites()`), separados por tipo.

## Pendiente

- `/clientes/actividad` (Centro de actividad) todavía no existe — el ítem "Mensajes" en la navegación ya reserva el espacio.
- Navegación móvil con barra inferior (Explorar, Actividad, Publicar/Pídelo, Favoritos, Perfil) descrita en 1.1.1 no está construida; hoy el mismo header responsivo cubre ambos tamaños.
- Historial de negocios vistos con consentimiento no está implementado.
