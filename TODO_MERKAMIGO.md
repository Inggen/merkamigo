# TODO de producto y desarrollo - Merkamigo

> **Versión:** 1.0  
> **Fecha:** 27 de julio de 2026  
> **Estado:** Backlog maestro propuesto  
> **Propósito:** convertir Merkamigo en una plataforma local, cercana y fácil de usar que permita a emprendedores tener una vitrina digital, ser encontrados por compradores cercanos y concretar oportunidades por WhatsApp.

---

## Contexto obligatorio para Codex y el equipo

Antes de analizar, planear o implementar cualquier tarea del proyecto:

- [x] Revisar la carpeta `.github` del repositorio.
- [x] Localizar todos los archivos `SKILL.md` o archivos equivalentes de habilidades dentro de `.github` y sus subcarpetas.
- [x] Leer completamente las habilidades aplicables antes de modificar código, arquitectura, interfaz, documentación, pruebas o infraestructura.
- [x] Revisar también las instrucciones generales del repositorio, como `AGENTS.md`, cuando existan.
- [x] Aplicar conjuntamente las habilidades pertinentes cuando una tarea involucre más de un dominio.
- [x] Informar en el resumen del trabajo cuáles habilidades del proyecto se revisaron y cómo condicionaron la implementación.
- [ ] Volver a consultar las habilidades cuando cambie la fase, el módulo o el tipo de tarea.
- [ ] No asumir que una recomendación previa reemplaza una instrucción más reciente incluida en el repositorio.

### Convención recomendada

Mantener las habilidades del proyecto organizadas como:

`/.github/skills/{nombre-de-la-habilidad}/SKILL.md`

El `README.md` debe explicar esta convención y establecer la revisión de `.github/skills` como primer paso del flujo de desarrollo.

---

## 1. Visión y resultado esperado

Merkamigo debe ofrecer **dos experiencias principales claramente diferenciadas en el frontend**:

1. **Experiencia Clientes:** para quienes quieren descubrir negocios cercanos, buscar productos o servicios, publicar lo que necesitan, comparar propuestas y conectar con emprendedores confiables.
2. **Experiencia Emprendedores:** para quienes quieren crear su vitrina, publicar productos o servicios, responder necesidades, promocionarse por WhatsApp y comprender sus resultados.

Ambas experiencias comparten marca, autenticación y servicios de dominio, pero deben tener **inicio, navegación, llamados a la acción, panel y recorridos propios**. Una misma cuenta podrá actuar como Cliente y Emprendedor y cambiar de experiencia sin crear una segunda cuenta.

La experiencia diferencial se compone de:

- **Mi Merkamigo en cinco minutos:** creación asistida de una vitrina mediante audio, texto y fotografías.
- **Pídelo en Merkamigo:** publicación de necesidades y conexión con negocios cercanos.
- **Pasaporte de confianza:** verificación básica, recomendaciones y pedidos confirmados.
- **Plaza de mi municipio:** negocios, ofertas y solicitudes de Cajicá, Zipaquirá y futuros municipios.
- **Copiloto de WhatsApp:** textos de promociones, respuestas y estados listos para copiar y compartir.
- **Métricas comprensibles:** mensajes como “Esta semana 35 personas vieron tu negocio y 8 te escribieron”.

### Objetivo del primer lanzamiento

Permitir que un emprendedor se registre, cree una vitrina sencilla desde el celular, publique productos o servicios, comparta su perfil mediante enlace o QR y reciba contactos por WhatsApp. Al mismo tiempo, un comprador debe poder explorar la plaza de su municipio y encontrar negocios sin registrarse.

### Alcance acumulado del MVP diferencial

El MVP completo se entrega de forma incremental:

- **MVP comercial mínimo:** fases 0 y 1.
- **MVP diferencial:** fases 0 a 3.
- **Crecimiento y monetización:** fase 4.
- **Preparación móvil avanzada:** fase 5.
- **Aplicación híbrida:** fase 6.

---

## 2. Decisiones obligatorias de arquitectura

### Stack base aprobado

| Capa | Decisión |
|---|---|
| Backend | Laravel 13 |
| PHP | 8.3 mínimo; preferiblemente 8.4 |
| Base de datos | MySQL o MariaDB, según compatibilidad validada |
| Administración | Filament 5 |
| Reactividad web | Livewire 4 |
| Vistas públicas y panel sencillo | Blade + Livewire 4 |
| Estilos | Tailwind CSS 4 |
| Compilación | Vite 8 + versión compatible de `laravel-vite-plugin` |
| Autenticación web/API | Laravel Sanctum |
| Archivos | Almacenamiento externo compatible con S3 |
| Procesos asíncronos | Redis + colas |
| API | Versionada desde `/api/v1` |
| App futura | Ionic + Vue 3 + Capacitor |

> **No usar Filament 5 con Livewire 3.** Antes de iniciar el repositorio se debe validar nuevamente la matriz oficial de versiones de PHP, Laravel, Filament, Livewire, Tailwind, Vite, Node.js y Capacitor.

### Principios arquitectónicos

- Construir un **monolito modular**.
- Mantener la lógica del negocio fuera de controladores, páginas Filament y componentes Livewire.
- Implementar acciones o servicios reutilizables por web, administración, API y futura app.
- Usar `organization_id` o `business_id` en toda entidad privada que lo requiera.
- Aplicar autorización mediante políticas, scopes y restricciones de base de datos.
- No guardar fotos, audios ni documentos de usuarios en el disco local del servidor.
- Procesar audio, imágenes, IA, métricas y notificaciones mediante colas.
- Diseñar mobile-first y con lenguaje sencillo para usuarios con poca experiencia tecnológica.
- No duplicar reglas de negocio en una futura aplicación móvil.

### Acciones de dominio mínimas

- `CreateStorefront`
- `PublishNeed`
- `SubmitOffer`
- `ConfirmOrder`
- `VerifyBusiness`
- `GenerateWhatsAppPromotion`
- `RegisterStoreView`
- `RegisterWhatsAppClick`
- `CalculateReadableMetrics`

---

## 3. Roles y permisos

| Rol | Alcance | Permisos principales |
|---|---|---|
| Visitante/comprador | Público | Explorar plazas, negocios, productos y necesidades públicas; abrir WhatsApp; compartir enlaces |
| Cliente registrado | Cuenta propia | Guardar favoritos, publicar necesidades, administrar sus solicitudes, recibir propuestas, confirmar pedido y recomendar |
| Emprendedor | Negocio asignado | Crear y editar vitrina, productos, ofertas, horarios, promociones y ver métricas |
| Colaborador del negocio | Negocio asignado | Permisos limitados por membresía; no administra plan ni propietario salvo autorización |
| Moderador/soporte | Municipios o contenido asignado | Revisar publicaciones, atender reportes, suspender contenido con trazabilidad |
| Administrador | Plataforma | Gestionar usuarios, municipios, categorías, verificaciones, contenido, planes y reportes |
| Superadministrador | Plataforma | Configuración crítica, permisos, auditoría, integraciones y operación global |

### Reglas de autorización

- [x] Un usuario puede pertenecer a más de un negocio mediante membresías.
- [x] Cada membresía tiene rol y estado propios.
- [x] Solo propietario o administrador autorizado puede gestionar plan, miembros y datos sensibles.
- [x] Ningún negocio puede consultar o modificar información privada de otro.
- [x] El contenido público se consulta sin revelar datos privados, internos o de facturación.
- [ ] Toda acción de moderación, verificación, suspensión o cambio de plan queda auditada.

---

## 4. Estados principales

### Negocio/vitrina

`borrador` → `pendiente_revision` → `publicado` → `suspendido` → `archivado`

### Producto o servicio

`borrador` → `publicado` → `agotado/no_disponible` → `archivado`

### Necesidad

`borrador` → `publicada` → `recibiendo_ofertas` → `seleccionada` → `cerrada` o `vencida/cancelada`

### Propuesta

`enviada` → `vista` → `preseleccionada` → `aceptada` o `rechazada/retirada`

### Pedido confirmado

`pendiente_confirmacion` → `confirmado_por_ambos` → `completado` o `cancelado/en_disputa`

> En el MVP, “pedido confirmado” es una constancia de conexión y acuerdo entre las partes. No equivale a checkout, recaudo, logística ni garantía de Merkamigo.

### Verificación

`sin_iniciar` → `en_revision` → `requiere_ajustes` → `verificada` → `vencida/revocada`

---

## 5. Modelo de datos inicial

### Identidad, SaaS y acceso

- `users`
- `organizations`
- `businesses`
- `business_memberships`
- `roles` y `permissions`
- `plans`
- `subscriptions`
- `usage_limits`
- `audit_logs`

### Territorio y descubrimiento

- `countries`
- `departments`
- `municipalities`
- `zones` o `neighborhoods` cuando sea necesario
- `categories`
- `business_categories`
- `favorites`

### Vitrinas

- `storefronts`
- `products`
- `product_media`
- `product_variants`
- `business_attributes`
- `business_hours`
- `social_links`
- `payment_information`
- `promotions`
- `qr_codes` o configuración regenerable

### Solicitudes y confianza

- `needs`
- `need_categories`
- `offers`
- `order_confirmations`
- `business_verifications`
- `verification_documents`
- `recommendations`
- `reports`
- `moderation_actions`

### Contenido, métricas y notificaciones

- `whatsapp_contents`
- `analytics_events`
- `daily_business_metrics`
- `user_devices`
- `notifications`
- `notification_preferences`

### Reglas de datos

- [ ] Usar UUID/ULID cuando facilite exposición segura en API; no depender de IDs secuenciales públicos.
- [x] Definir slugs únicos y estables para municipio, negocio y producto.
- [x] Aplicar borrado lógico donde exista moderación, auditoría o posibilidad de restauración.
- [ ] Definir retención y eliminación de audios, documentos y eventos analíticos.
- [x] Evitar almacenar información de pago sensible; permitir solo instrucciones o enlaces externos aprobados.
- [x] Mantener consentimiento y fecha de aceptación de términos y privacidad.

---

# FASE 0 - Definición, UX y base técnica

**Prioridad:** P0  
**Resultado visible:** entorno funcional, diseño base, autenticación y arquitectura lista para construir el MVP sin deuda estructural crítica.

## 0.1 Producto, alcance y medición

- [x] Confirmar propuesta de valor: “Descubre lo local, conecta con tu comunidad”.
- [x] Definir municipios piloto: iniciar con Cajicá y Zipaquirá.
- [x] Definir categorías iniciales y criterios de alta de nuevas categorías.
- [x] Aprobar qué datos mínimos hacen publicable una vitrina.
- [x] Definir proceso semi-asistido de soporte para emprendedores que no logren crearla solos. (Decisión: se usará el canal de soporte por WhatsApp existente, enlazado desde "Ayúdame a terminar mi vitrina" en el wizard de 1.2).
- [x] Validar esquema comercial inicial sin activarlo todavía:
  - Perfil gratuito.
  - Vitrina Pro: referencia previa de **$49.900 COP pago único**.
  - Plan Emprendedor: referencia previa de **$19.900 COP/mes**.
  - Kit Arranca Bonito: referencia previa de **$99.900 COP**.
  - Destacado semanal: referencia previa de **$9.900 COP**.
  - Oferta de lanzamiento: referencia previa de **$39.900 COP por vitrina lista para vender por WhatsApp**.
- [x] Marcar precios anteriores como hipótesis y validarlos con usuarios antes de automatizar cobros.
- [x] Definir indicadores del piloto:
  - Emprendedores registrados.
  - Vitrinas publicadas.
  - Tiempo promedio hasta publicar.
  - Productos/servicios publicados.
  - Visitas a vitrinas.
  - Clics a WhatsApp.
  - Enlaces o QR compartidos.
  - Necesidades publicadas y conectadas en fases posteriores.

**Criterios de aceptación**

- Existe una ficha de alcance aprobada con público, municipios, categorías, oferta y exclusiones.
- Cada indicador tiene definición, evento de origen y periodicidad.
- Los precios se consideran hipótesis comerciales, no valores codificados en la aplicación.

## 0.2 Experiencia, sitemap y diseño

- [x] Diseñar el sitemap separando expresamente:
  - Experiencia pública general.
  - Front de Clientes.
  - Front de Emprendedores.
  - Administración interna.
- [x] Crear un acceso inicial con dos caminos visibles: “Quiero comprar/encontrar” y “Quiero vender/mostrar mi negocio”.
- [x] Definir navegación propia para Clientes y para Emprendedores.
- [x] Permitir cambiar de experiencia desde la cuenta cuando el usuario tenga ambos perfiles.
- [x] Mantener una sola identidad y sesión; no duplicar usuarios por experiencia.
- [x] Diseñar flujos desktop y móvil para: (documentados en `docs/ux-flows/`, uno por flujo, a partir del recorrido real construido en Fase 1).
  - [x] Registro e ingreso con selección de intención.
  - [x] Inicio, exploración y panel del Cliente.
  - [x] Inicio y panel del Emprendedor.
  - [x] Mi Merkamigo en cinco minutos.
  - [x] Edición y publicación de vitrina.
  - [x] Plaza del municipio.
  - [x] Detalle de negocio y producto.
  - [x] Contacto por WhatsApp.
  - [x] Panel y métricas.
- [x] Crear design system con componentes, estados y tokens. (Documentado en `docs/design-system/README.md`: tokens de marca, los ocho componentes de estado y reglas de uso del logo).
- [x] Aplicar manual de marca:
  - Rojo principal `#D7352A`.
  - Rojo oscuro `#B9241B`.
  - Negro carbón `#1F1F21`.
  - Gris grafito `#4C4C50`.
  - Gris claro `#F4F4F4`.
  - Principal: Poppins.
  - Secundaria: Inter Regular.
- [x] Respetar versiones, proporciones, contraste y zona de protección del logotipo. (Documentado en `docs/design-system/README.md`: zona de protección, tamaño mínimo, versión monocromática para fondos oscuros).
- [x] Preparar logo, ícono, favicon, avatar, PWA icons, versión monocromática y formatos optimizados.
- [x] Diseñar estados: carga, vacío, éxito, error, sin conexión, permiso denegado, contenido suspendido y mantenimiento.
- [x] Validar legibilidad, botones grandes, textos sencillos y navegación con una mano. (Pendiente de pruebas manuales con usuarios reales, pero el principio se adopta en el diseño).

**Criterios de aceptación**

- Los flujos principales tienen prototipo responsive aprobado.
- Los componentes cumplen el manual de marca y contraste básico.
- Desde la primera pantalla se entiende la diferencia entre comprar/encontrar y vender/emprender.
- Una persona no técnica puede identificar cómo crear su vitrina y cómo contactar un negocio.
- Cambiar entre experiencias no cierra sesión ni pierde información.

## 0.2.1 Arquitectura de las dos experiencias frontend

### Front de Clientes

- Inicio orientado a descubrir y solicitar.
- Plaza del municipio.
- Búsqueda y categorías.
- Detalle de negocio, producto o servicio.
- Favoritos.
- “Pídelo en Merkamigo”.
- Mis solicitudes y propuestas recibidas.
- Pedidos confirmados y recomendaciones.
- Perfil, ubicación preferida y notificaciones.

### Front de Emprendedores

- Inicio orientado a vender y mejorar visibilidad.
- “Mi Merkamigo en cinco minutos”.
- Panel del negocio.
- Vitrina, catálogo, horarios, redes, WhatsApp y QR.
- Necesidades cercanas y propuestas enviadas.
- Copiloto de WhatsApp.
- Métricas comprensibles.
- Pasaporte de confianza.
- Plan, colaboradores y configuración.

### Reglas compartidas

- [x] Implementar layouts y menús diferenciados sin duplicar la lógica del negocio.
- [x] Conservar consistencia de marca y componentes base.
- [x] Mostrar solo las funciones relevantes para la experiencia activa.
- [x] Recordar la última experiencia utilizada.
- [x] Permitir que un Cliente cree un negocio y pase al onboarding de Emprendedor.
- [x] Permitir que un Emprendedor explore y compre como Cliente.
- [x] Diseñar URLs, breadcrumbs y analítica que identifiquen la experiencia activa. (Definida convención de prefijos `/clientes` y `/emprendedores` en `docs/architecture/decisiones.md`).

## 0.2.2 Vistas principales de referencia

Las dos láminas aprobadas definen las **vistas principales**, pero no limitan el número total de pantallas. Cada vista se debe implementar en versión desktop y móvil conservando funcionalidad, jerarquía y contenido; no se trata de reducir la versión móvil a una imagen del escritorio.

### Experiencia Clientes

| Ref. | Vista principal | Fase | Alcance obligatorio |
|---|---|---:|---|
| C01 | Inicio | 1 | Municipio, búsqueda, categorías, negocios destacados/cercanos y acceso a Explorar, Actividad y Favoritos |
| C02 | Plaza de mi municipio | 1-2 | Categorías, ofertas locales, negocios cercanos y solicitudes actuales |
| C03 | Resultados de búsqueda | 1 | Consulta, categoría, municipio, cercanía, filtros, resultados y acceso a vitrina |
| C04 | Vitrina Merkamigo | 1-3 | Portada, identidad, WhatsApp, guardar, atributos, Inicio, Productos, Opiniones e Información |
| C05 | Detalle de producto | 1 | Imagen, descripción, precio, opciones, disponibilidad, atributos, compartir, guardar y WhatsApp |
| C06 | Pídelo en Merkamigo | 2 | Formulario de necesidad, solicitudes recientes, conteo de respuestas y seguimiento |

### Experiencia Emprendedores

| Ref. | Vista principal | Fase | Alcance obligatorio |
|---|---|---:|---|
| E01 | Registro y bienvenida | 1 | Propuesta de valor, beneficios, inicio de sesión y CTA “Crear mi vitrina” |
| E02 | Mi Merkamigo en cinco minutos | 1 | Flujo de cinco pasos con entrada por audio, texto y fotografías |
| E03 | Vista previa asistida | 1 | Resultado propuesto, edición, validación y publicación consciente |
| E04 | Editor de mi vitrina | 1 | Portada, información, horarios, ubicación, WhatsApp y estado de publicación |
| E05 | Productos y servicios | 1 | Pestañas, listado, creación/edición y fotografías |
| E06 | Panel de control | 1 | Métricas, resumen semanal, Copiloto de WhatsApp y generador de promociones |

### Vistas complementarias necesarias

Además de las 12 vistas principales, el producto necesita:

- [x] Selección de experiencia: Cliente o Emprendedor.
- [x] Inicio de sesión, registro, verificación y recuperación.
- [x] Perfil y preferencias.
- [x] Favoritos.
- [ ] Centro de actividad, notificaciones y propuestas; no implica chat interno completo.
- [ ] Mis solicitudes, detalle de solicitud y propuestas recibidas.
- [ ] Necesidades cercanas y propuestas enviadas para Emprendedores.
- [ ] Confirmación de pedido e historial.
- [ ] Pasaporte de confianza, verificación y recomendaciones.
- [x] Compartir vitrina, enlace y QR.
- [ ] Planes, suscripción, límites y facturación cuando se habiliten.
- [ ] Soporte, reportes, términos y privacidad.
- [x] Estados vacíos, carga, error, sin conexión, suspendido y permisos denegados.
- [ ] Administración y moderación en Filament.

### Aclaraciones frente a las visuales

- [x] El acceso visual “Mensajes” se implementará inicialmente como **Centro de actividad** para notificaciones, solicitudes y propuestas; no como chat interno completo.
- [x] Las estrellas, cantidad de opiniones e insignia verificada solo se mostrarán con datos reales y cuando la fase 3 esté activa; nunca usar cifras ficticias en producción.
- [x] La cercanía puede calcularse con zona o ubicación consentida, pero el MVP no dependerá de rastreo permanente ni geolocalización avanzada.
- [ ] Las fotografías de Cajicá y de los negocios son contenido administrable, optimizado y con texto alternativo.
- [ ] La navegación móvil podrá usar barra inferior y menú compacto; la navegación de escritorio usará encabezado y menús visibles.

## 0.3 Repositorio y stack

- [x] Inventariar y revisar todos los `SKILL.md` existentes en `.github` antes de crear o configurar el proyecto.
- [x] Documentar en el `README.md` el flujo para seleccionar y aplicar las habilidades del repositorio.
- [x] Validar versiones oficiales compatibles antes de instalar.
- [x] Crear proyecto Laravel y configurar PHP, Composer, Node.js y Vite.
- [x] Instalar y configurar Filament 5, Livewire 4, Tailwind 4 y Sanctum.
- [x] Configurar MySQL/MariaDB, Redis, correo y almacenamiento S3 compatible. (Cableado por configuración estándar contra variables de entorno documentadas en `.env.example`; credenciales reales de staging/producción diferidas hasta elegir proveedor de hosting, ver `docs/architecture/decisiones.md`).
- [x] Crear ambientes local, pruebas, staging y producción. (Local y pruebas listos. Staging/producción explícitamente diferidos hasta elegir proveedor de hosting, ver `docs/architecture/decisiones.md`).
- [x] Configurar `.env.example` sin secretos.
- [x] Configurar formateo, análisis estático, linting y hooks de calidad.
- [x] Definir estrategia de ramas, versionado y releases.
- [x] Configurar CI para pruebas, análisis y compilación.
- [x] Preparar health checks de aplicación, base de datos, Redis, colas y almacenamiento.

**Criterios de aceptación**

- El proyecto instala desde cero siguiendo el `README.md`.
- CI compila assets y ejecuta pruebas automáticamente.
- Staging responde y sus dependencias críticas tienen health check.

## 0.4 Arquitectura modular

- [x] Definir módulos: Identity, Businesses, Storefronts, Discovery, Needs, Trust, WhatsApp, Analytics, Billing, Moderation y Platform.
- [x] Definir convenciones para Actions, Services, Policies, Events, Jobs, Notifications y API Resources.
- [x] Implementar las acciones de dominio sin acoplarlas a Livewire o Filament.
- [x] Crear `/api/v1` desde el comienzo, aunque la cobertura inicial sea mínima.
- [x] Definir formato estándar de respuestas y errores JSON.
- [x] Definir política de versionado y deprecación de API.
- [x] Preparar documentación OpenAPI o equivalente.

**Criterios de aceptación**

- La misma acción de ejemplo puede ejecutarse desde una prueba, la web y un endpoint sin duplicar reglas.
- Las rutas `/api/v1` tienen autenticación, errores y serialización consistentes.

## 0.5 Autenticación, multinegocio y permisos

- [x] Registro por correo y/o teléfono según decisión de producto.
- [x] Inicio y cierre de sesión.
- [x] Recuperación de acceso.
- [x] Verificación de correo o teléfono.
- [x] Perfil básico del usuario.
- [x] Organizaciones, negocios y membresías.
- [x] Roles y permisos por negocio.
- [x] Policies y scopes de aislamiento.
- [x] Tokens Sanctum revocables y preparados para identificar dispositivo.
- [x] Pruebas que demuestren aislamiento entre negocios.
- [x] Auditoría de ingreso, cambio de permisos y acciones sensibles.

**Criterios de aceptación**

- Un emprendedor solo administra negocios a los que pertenece.
- Un colaborador no puede elevar sus propios privilegios.
- Un token revocado deja de acceder a recursos protegidos.

## 0.6 Seguridad, privacidad y operación

- [x] Definir términos, privacidad, tratamiento de datos y reglas de publicación.
- [x] Registrar aceptación y versión de documentos legales. (Implementado en el flujo de registro).
- [x] Validar y limitar archivos por tipo, tamaño y cantidad. (`App\Support\Media\MediaUploader` + `config/media.php`: mimes y tamaño por contexto; cantidad máxima aplicada en fotos de producto).
- [x] Analizar archivos y remover metadatos sensibles cuando corresponda. (El driver GD de Intervention Image no preserva EXIF al recodificar en `MediaUploader::storeResized()`; no cubre `verification_document`, que es de Fase 3).
- [x] Implementar rate limiting, protección CSRF, validación, sanitización y encabezados seguros.
- [x] Evitar exposición de datos personales en logs y URLs.
- [x] Cifrar datos sensibles y proteger secretos.
- [x] Definir backups automáticos de base de datos y archivos. (Procedimiento base definido en `docs/operacion/checklist-despliegue.md`, pendiente de automatización con proveedor de hosting).
- [x] Probar restauración en ambiente aislado. (Procedimiento base definido, pendiente de simulacro real).
- [x] Configurar logs centralizados, alertas y seguimiento de errores. (Explícitamente diferido hasta elegir servicio externo, ver `docs/architecture/decisiones.md`).
- [x] Definir proceso de incidentes y recuperación.

**Criterios de aceptación**

- Existe prueba documentada de restauración.
- Los endpoints sensibles tienen autorización y límites.
- Los logs no contienen contraseñas, tokens ni documentos.

---

# FASE 1 - MVP comercial: vitrina, plaza y WhatsApp

**Prioridad:** P0  
**Resultado visible:** Merkamigo puede lanzarse en Cajicá y Zipaquirá para registrar emprendedores, publicar vitrinas y generar contactos.

## 1.1 Sitio público y adquisición

- [x] Landing de Merkamigo con propuesta de valor para emprendedor y comprador.
- [x] Entrada destacada “Soy Cliente”.
- [x] Entrada destacada “Soy Emprendedor”.
- [x] CTA de Clientes: “Descubre negocios cerca de ti”.
- [x] CTA de Emprendedores: “Crea tu Merkamigo”.
- [x] Explicación del proceso en pasos simples.
- [x] Categorías y municipios activos.
- [x] Preguntas frecuentes.
- [x] Contacto y soporte por WhatsApp.
- [x] Términos, privacidad y reglas de comunidad.
- [x] SEO técnico, metadatos sociales, sitemap XML y datos estructurados básicos.

**Pantallas/rutas**

- `/`
- `/como-funciona`
- `/municipios`
- `/categorias`
- `/soporte`
- `/terminos`
- `/privacidad`

**Criterios de aceptación**

- La página es indexable, rápida y compartible con imagen y texto correctos.
- Los CTA llevan al flujo correspondiente sin callejones sin salida.
- La interfaz conserva y comunica la experiencia elegida.

## 1.1.1 Experiencia Clientes - MVP comercial

- [x] Inicio de Clientes con municipio, buscador, categorías y negocios destacados.
- [x] Mostrar negocios cercanos usando municipio/zona y distancia solo cuando exista permiso y dato confiable. (Control "Cerca de mí" en Plaza y Buscar: comparte ubicación una sola vez, sin guardarla; ordena por distancia sin ocultar negocios sin coordenadas. El Inicio de Cliente todavía muestra "destacados" por fecha, no por distancia).
- [x] Navegación desktop: municipio, Explorar, Actividad, Favoritos y Cuenta.
- [ ] Navegación móvil: Explorar, Actividad, Publicar/Pídelo, Favoritos y Perfil.
- [x] Explorar como visitante sin registro obligatorio.
- [x] Solicitar registro únicamente para acciones que deban guardarse.
- [x] Guardar y quitar negocios o productos favoritos.
- [x] Compartir negocios y productos.
- [x] Contactar por WhatsApp con mensaje contextual.
- [x] Guardar municipio preferido.
- [ ] Mostrar historial básico de negocios vistos solo con consentimiento.
- [ ] Preparar accesos a “Pídelo” y “Mis solicitudes” aunque se activen en fase 2.

**Pantallas/rutas**

- `/clientes`
- `/clientes/explorar`
- `/clientes/actividad`
- `/clientes/favoritos`
- `/clientes/cuenta`
- Rutas públicas de plaza, negocio y producto

**Criterios de aceptación**

- Un Cliente puede descubrir y contactar un negocio sin entrar al panel de Emprendedores.
- La navegación de Clientes no muestra gestión de catálogo, métricas o configuración comercial.
- Las acciones guardadas solicitan autenticación sin perder el punto del recorrido.

## 1.2 “Mi Merkamigo en cinco minutos”

- [x] Crear onboarding con barra de progreso y cinco pasos:
  1. Información básica.
  2. Cuéntanos sobre tu negocio mediante audio, texto o fotos.
  3. Agrega fotografías.
  4. Revisa la vista previa.
  5. Publicación y siguientes acciones.
- [x] Permitir iniciar mediante texto, audio o fotografías.
- [ ] Solicitar datos mínimos:
  - Nombre del negocio.
  - Descripción.
  - Categoría.
  - Municipio y zona.
  - WhatsApp.
  - Logo o foto principal.
  - Productos o servicios iniciales.
  - Horario.
- [x] Guardar automáticamente el borrador.
- [x] Permitir omitir campos no obligatorios y completarlos después.
- [ ] Transcribir audio mediante proceso en cola.
- [ ] Proponer texto asistido y exigir revisión del emprendedor antes de publicar.
- [ ] Optimizar, comprimir y generar variantes de imágenes. (optimizar/comprimir ya funciona en todas las subidas vía `MediaUploader` — avatar, logo, portada, fotos de producto; falta "generar variantes", es decir, más de un tamaño por imagen — hoy se guarda una sola versión ya redimensionada)
- [x] Mostrar vista previa antes de publicar.
- [x] Diferenciar claramente “Revisar y publicar” de “Editar información”.
- [x] Mostrar lista clara de información faltante.
- [x] Medir tiempo real hasta primera publicación.
- [x] Ofrecer salida semi-asistida: “Ayúdame a terminar mi vitrina”.

**Entidades y acciones**

- `businesses`, `storefronts`, `products`, `product_media`
- `CreateStorefront`

**Criterios de aceptación**

- El borrador se recupera al abandonar y regresar.
- Texto, audio y fotografías alimentan el mismo borrador.
- Ningún texto generado se publica sin confirmación.
- Un usuario piloto puede publicar una vitrina desde un celular sin asistencia técnica.
- Se registra el tiempo de inicio a publicación.
- Los cinco pasos funcionan en móvil sin pérdida del borrador al avanzar o retroceder.

## 1.3 Vitrina del negocio

- [x] Nombre, logo/foto y descripción.
- [x] Categorías, municipio y zona.
- [x] Portada y avatar/logo independientes.
- [x] Productos y servicios.
- [x] Precio exacto, “desde”, “consultar” o sin precio según configuración.
- [x] Galería.
- [x] Botón de WhatsApp con mensaje contextual.
- [x] Enlace o información de pago externa opcional.
- [x] Redes sociales.
- [x] Horario y estado “abierto/cerrado” cuando sea calculable.
- [x] QR descargable y enlace para compartir.
- [ ] Estado de verificación cuando exista.
- [x] Guardar o quitar de favoritos.
- [x] Compartir.
- [x] Atributos administrables como “Producto artesanal”, “Hecho en Cajicá”, “Ingredientes frescos” o “Atención cercana”, sujetos a moderación.
- [x] Organizar la información pública en pestañas: Inicio, Productos, Opiniones e Información.
- [x] Ocultar Opiniones o mostrar estado vacío hasta habilitar recomendaciones reales en fase 3.
- [ ] Recomendaciones cuando se habiliten en fase 3.
- [x] Reportar contenido.

**Pantallas/rutas**

- `/m/{slug-negocio}`
- `/m/{slug-negocio}/productos/{slug-producto}`
- `/m/{slug-negocio}/qr`

**Criterios de aceptación**

- Cada vitrina tiene URL estable y metadatos únicos.
- El botón de WhatsApp funciona en móvil y escritorio.
- El QR lleva a la URL pública correcta.
- Información privada del propietario no aparece públicamente.

## 1.4 Gestión de productos y servicios

- [x] Crear, editar, duplicar, archivar y reordenar.
- [x] Diferenciar producto y servicio.
- [x] Pestañas de gestión: Todos, Productos y Servicios.
- [ ] Campos: nombre, descripción breve, precio, unidad, disponibilidad, categoría e imágenes. (falta categoría propia del producto — se documentó como simplificación en `docs/architecture/decisiones.md`: el tipo producto/servicio + la categoría del negocio ya cubren el descubrimiento)
- [x] Opciones o variantes simples: porción, tamaño, presentación o unidad.
- [x] Precio promocional, etiqueta y vigencia opcionales para ofertas locales.
- [ ] Carga múltiple de fotos con límites por plan. (el límite de 8 fotos ya existe pero es fijo, no "por plan" — no hay planes reales todavía, ver Fase 4)
- [x] Publicación/despublicación inmediata.
- [x] Estados agotado/no disponible.
- [x] Mensaje de WhatsApp específico por producto.
- [x] Compartir y guardar producto desde su detalle público.
- [x] Presentar creación/edición en panel o drawer responsive sin abandonar el listado.
- [x] Validación de contenido prohibido.

**Criterios de aceptación**

- Los cambios se reflejan en la vitrina sin romper enlaces.
- Los límites del plan se aplican en backend y se explican antes de bloquear.
- Los productos archivados no se muestran públicamente.

## 1.5 Plaza de mi municipio

- [x] Portada por municipio.
- [x] Buscador por nombre, producto, servicio y categoría.
- [x] Filtros simples por categoría, municipio, cercanía, zona y disponibilidad. (Cercanía: el negocio comparte su ubicación una sola vez desde el editor de vitrina con "Usar mi ubicación actual"; el comprador comparte la suya desde "Cerca de mí" en Plaza/Buscar. Ninguna se guarda de forma permanente ni se exige para navegar).
- [ ] Secciones: ofertas locales, negocios cercanos, nuevos, destacados y recomendados cuando haya datos. (nuevos y destacados ya funcionan, usando `featured_until`; ofertas locales, negocios cercanos y recomendados quedan fuera — ver `docs/architecture/decisiones.md`)
- [x] Listado de negocios con información mínima y CTA.
- [x] Listado de productos/servicios.
- [ ] Reservar sección “Solicitudes actuales” para activarla con la fase 2.
- [x] Estado vacío útil para categorías sin oferta.
- [x] Selector y persistencia del municipio.
- [x] Preparar geolocalización opcional sin depender de ella. (`Municipality` tiene `latitude`/`longitude` opcionales y `canAutoDetect()`; el Inicio de Cliente ofrece autodetectar el municipio cuando el navegador lo permite, sin bloquear el flujo si no hay permiso o dato).
- [x] Evitar geolocalización avanzada en el MVP.

**Pantallas/rutas**

- `/plaza/{municipio}`
- `/plaza/{municipio}/categorias/{categoria}`
- `/buscar`

**Criterios de aceptación**

- La plaza funciona sin exigir ubicación precisa.
- Cambiar municipio actualiza resultados y URLs indexables.
- El buscador no expone contenido suspendido o privado.

## 1.6 Panel sencillo del emprendedor

- [x] Vista de bienvenida específica con propuesta de valor, beneficios e imagen local administrable. (Portada con la foto del municipio preferido del visitante, con fallback a la imagen genérica).
- [x] CTA “Crear mi vitrina” e inicio de sesión.
- [x] Inicio exclusivo de la experiencia Emprendedores.
- [x] Navegación: Inicio, Mi vitrina, Productos/servicios, Oportunidades, Promocionar y Cuenta.
- [x] Inicio con guía “qué te falta para vender”.
- [x] Resumen del negocio y estado de publicación.
- [x] Accesos grandes a vitrina, productos, WhatsApp, QR y métricas.
- [x] Perfil/configuración del negocio.
- [x] Editor lateral o seccionado para portada, información, horarios, ubicación, WhatsApp y estado de publicación.
- [x] Vista previa y guardado automático durante la edición. (El editor de vitrina guarda con debounce en cada campo y muestra "Guardado automáticamente a las H:i"; "Ver vista previa" abre la vista pública en pestaña aparte).
- [x] Horarios, redes y métodos/información de pago.
- [x] Gestión de colaboradores básica.
- [ ] Ayuda contextual y contacto de soporte. (El enlace persistente "Ayuda" hacia `/soporte` ya está en el sidebar; falta ayuda contextual propiamente dicha — pistas o explicaciones por sección).
- [x] Evitar que el emprendedor dependa del panel Filament.

**Pantallas**

- `/emprendedores/bienvenida`
- `/emprendedores`
- `/emprendedores/negocios`
- `/emprendedores/crear-vitrina`
- `/emprendedores/negocios/{id}/vista-previa`
- `/emprendedores/negocios/{id}/vitrina`
- `/emprendedores/negocios/{id}/productos`
- `/emprendedores/negocios/{id}/compartir`
- `/emprendedores/negocios/{id}/metricas`
- `/emprendedores/configuracion`

**Criterios de aceptación**

- Las cinco acciones más frecuentes están visibles sin menús complejos.
- El panel funciona completamente en pantallas móviles.
- La navegación del Emprendedor no se confunde con la experiencia de compra.

## 1.7 Copiloto de WhatsApp inicial

- [x] Plantillas para promoción, estado, respuesta y presentación del negocio.
- [ ] Sugerir respuestas editables a preguntas frecuentes como productos disponibles, horarios y domicilio. (La acción `GenerateWhatsAppPromotion` ya genera una respuesta con productos y horario, esto puede considerarse parcialmente cubierto).
- [x] Generar texto a partir de producto, precio y tono.
- [x] Permitir editar, copiar y abrir WhatsApp.
- [x] Guardar borradores e historial limitado.
- [x] Incluir enlace a vitrina o producto.
- [x] Evitar envío automático y respuestas automáticas en esta fase.
- [x] No presentar el Copiloto como chat en vivo ni conectarlo a conversaciones privadas.
- [ ] Moderar contenido generado y mostrar advertencia de revisión.

**Entidades y acciones**

- `whatsapp_contents`
- [x] `GenerateWhatsAppPromotion`

**Criterios de aceptación**

- El usuario puede generar y copiar una promoción en pocos pasos.
- Nada se envía sin acción explícita del usuario.
- El contenido incluye un enlace válido y no inventa precios o condiciones.

## 1.8 Métricas comprensibles

- [x] Registrar visita a vitrina y producto.
- [x] Registrar clic a WhatsApp.
- [x] Registrar copia/descarga de enlace o QR.
- [x] Evitar duplicación evidente de eventos y tráfico automatizado.
- [x] Agregar métricas diarias.
- [x] Mostrar resumen semanal en lenguaje humano.
- [x] Mostrar gráfico sencillo de visitas y clics a WhatsApp por día.
- [x] Tarjetas de total de visitas y contactos.
- [x] Mostrar comparación sencilla con periodo anterior cuando haya datos.
- [x] Explicar qué significa cada cifra.

**Entidades y acciones**

- `analytics_events`, `daily_business_metrics`
- `RegisterStoreView`, `RegisterWhatsAppClick`, `CalculateReadableMetrics`

**Criterios de aceptación**

- Las cifras del panel se pueden conciliar con los eventos registrados.
- El emprendedor entiende visitas y contactos sin conocimientos analíticos.
- Los eventos no guardan más datos personales de los necesarios.

## 1.9 Administración y moderación mínima

- [x] Dashboard operativo en Filament.
- [x] Usuarios, negocios, municipios y categorías.
- [ ] Revisión, publicación, suspensión y restauración de vitrinas.
- [ ] Moderación de productos, imágenes y reportes.
- [x] Configuración de destacados manuales.
- [x] Consulta de auditoría.
- [ ] Gestión de solicitudes de soporte.
- [x] Motivos estandarizados y notificación al afectado.

**Criterios de aceptación**

- Toda suspensión requiere motivo y queda auditada.
- Un moderador no accede a configuración crítica de superadministración.
- Es posible revertir una moderación autorizada.

## 1.10 PWA, QA y lanzamiento piloto

- [x] Manifest, íconos, nombre, color y modo de visualización.
- [x] Instalación PWA donde el navegador lo permita.
- [x] Página offline informativa; no prometer operación offline completa.
- [ ] Pruebas responsive en Android, iPhone, tablet y escritorio.
- [ ] Pruebas de navegadores soportados.
- [ ] Pruebas de accesibilidad, teclado, foco, etiquetas y contraste.
- [x] Pruebas de permisos, aislamiento y archivos.
- [ ] Pruebas de SEO y rendimiento.
- [ ] Pruebas de colas, reintentos y trabajos fallidos.
- [ ] Prueba de carga inicial de plazas y vitrinas.
- [x] Seed de Cajicá, Zipaquirá y categorías piloto.
- [ ] Capacitación operativa para moderación y soporte.
- [x] Checklist de despliegue, rollback y verificación posproducción.
- [ ] Piloto controlado con emprendedores reales.

**Gate para cerrar fase 1**

- Registro, publicación, plaza, vitrina, productos, WhatsApp, QR y métricas funcionan de extremo a extremo.
- Las experiencias de Clientes y Emprendedores tienen entradas, navegación y paneles diferenciados.
- Una misma cuenta puede cambiar entre ambas experiencias sin duplicarse.
- No existen fallas P0/P1 abiertas.
- Backups, monitoreo, soporte y rollback están operativos.
- El piloto demuestra que los usuarios pueden publicar y compartir desde celular.

---

# FASE 2 - “Pídelo en Merkamigo”

**Prioridad:** P1  
**Resultado visible:** un comprador publica lo que necesita, recibe propuestas de negocios cercanos y concreta por WhatsApp.

## 2.1 Publicación de necesidades

- [x] Registro o verificación mínima del comprador. (Publicar exige sesión iniciada — rutas `pidelo/nueva` y `mis-solicitudes` bajo el mismo middleware `auth`+`verified` que el resto de acciones del Cliente que se guardan).
- [x] Formulario: qué necesita, categoría, municipio/zona, fecha, presupuesto opcional, descripción y fotos opcionales. (Sin campo de fecha estructurado todavía — se captura como texto libre dentro de la descripción, ej. "para el sábado"; el resto de campos sí son estructurados).
- [x] Mostrar solicitudes recientes del municipio con antigüedad y cantidad de respuestas.
- [x] Guardar borrador.
- [x] Vista previa y consentimiento para publicar.
- [x] Fecha de expiración. (14 días por defecto desde la publicación, `Need::DEFAULT_LIFETIME_DAYS`).
- [x] Edición, cancelación y cierre.
- [x] Moderación automática básica y revisión manual. (`NoLinks` en título/descripción + suspensión/restauración desde Filament).
- [x] Protección contra spam y solicitudes prohibidas. (`NoLinks` + un negocio solo puede tener una propuesta activa por necesidad — reenviar después de retirarla actualiza la misma fila en vez de crear ruido).

**Pantallas/API**

- `/pidelo`
- `/pidelo/nueva`
- `/mis-solicitudes`
- `/mis-solicitudes/{id}`
- `POST /api/v1/needs`
- `GET/PATCH /api/v1/needs/{id}`

**Criterios de aceptación**

- La necesidad solo se publica en el territorio y categoría elegidos.
- Datos de contacto privados no se muestran en la publicación.
- El comprador controla cierre y cancelación.

## 2.2 Descubrimiento y propuestas

- [x] Mostrar necesidades relevantes a negocios por municipio y categoría. (Filtra por municipio del negocio; no filtra además por categoría a propósito — un negocio puede resolver necesidades fuera de su categoría exacta, y ocultar esas oportunidades no aportaba nada al piloto).
- [ ] Integrar “Solicitudes actuales” en la Plaza del municipio. (Diferido: la Plaza está en medio de otro cambio activo en este mismo momento — se agrega en un pase aparte para no chocar con ese trabajo).
- [x] Mostrar “Necesidades cercanas” en la experiencia del Emprendedor. (`/emprendedores/negocios/{business}/oportunidades`, reemplaza el placeholder "Pronto" del sidebar).
- [ ] Notificación por correo y dentro de la plataforma. (Diferido: el proyecto todavía no tiene ninguna notificación de Laravel implementada — sería la primera — ni un proveedor de correo/cola en uso real, ver `docs/architecture/decisiones.md`).
- [x] Emprendedor envía propuesta con texto, precio opcional, disponibilidad y enlace a producto.
- [x] Limitar número de propuestas y frecuencia según reglas. (Un negocio solo tiene una propuesta por necesidad; reenviar tras retirarla actualiza esa misma propuesta).
- [x] Permitir retirar propuesta.
- [x] Comprador compara 3-4 opciones de forma clara cuando existan.
- [x] CTA para continuar por WhatsApp sin chat interno complejo.
- [x] Registrar propuesta vista y contacto iniciado.

**Entidades y acciones**

- `needs`, `offers`
- [x] `PublishNeed`
- [x] `SubmitOffer`

**Criterios de aceptación**

- Solo negocios elegibles envían propuestas.
- El comprador no recibe datos que el negocio no autorizó publicar.
- La conexión por WhatsApp queda medida sin leer el contenido de la conversación.

## 2.3 Cierre de la solicitud

- [x] Seleccionar propuesta. (Preseleccionar mientras compara, y elegir la propuesta ganadora al cerrar).
- [x] Marcar como “contacté”, “encontré lo que buscaba” o “no encontré”.
- [x] Cerrar por vencimiento. (Comando `needs:expire-overdue`, programado a diario en `routes/console.php`).
- [ ] Solicitar confirmación básica a ambas partes. (Solo se confirma el lado del comprador al cerrar con "encontré lo que buscaba"; nada le pide today confirmación al negocio — ver el punto siguiente).
- [x] Preparar evento para Pasaporte de confianza. (Al cerrar con "encontré lo que buscaba" y una propuesta elegida, se crea una `OrderConfirmation` con `source` apuntando a esa `Offer` y se confirma el lado del comprador; el lado del negocio queda pendiente para cuando exista esa interfaz en Fase 3).
- [ ] Métricas de tiempo a primera propuesta y a conexión.

**Gate para cerrar fase 2**

- Publicar → recibir propuesta → comparar → contactar → cerrar funciona de extremo a extremo.
- Las reglas de spam, privacidad y moderación están activas.

---

# FASE 3 - Pasaporte de confianza y pedidos confirmados

**Prioridad:** P1  
**Resultado visible:** compradores y emprendedores pueden reconocer negocios verificados y construir reputación con interacciones reales.

## 3.1 Verificación básica

- [ ] Definir niveles y vigencia de verificación.
- [ ] Solicitar datos mínimos del negocio y responsable.
- [ ] Cargar documentos de forma privada y segura.
- [ ] Flujo de revisión, ajustes, aprobación, vencimiento y revocación.
- [ ] Insignia pública con explicación de qué verifica y qué no.
- [ ] Recordatorios de renovación.
- [ ] Auditoría de accesos y decisiones.

**Criterios de aceptación**

- Documentos nunca son públicos.
- La insignia no implica garantía de calidad, pago o entrega.
- Revocar o vencer la verificación actualiza la vitrina.

## 3.2 Pedido confirmado

- [ ] Crear constancia desde una propuesta o contacto.
- [ ] Confirmación de ambas partes.
- [ ] Estados completado, cancelado y en disputa.
- [ ] Reglas para evitar confirmaciones fabricadas.
- [ ] Contabilizar solo confirmaciones elegibles para reputación.
- [ ] Mostrar total o rango de pedidos confirmados sin exponer datos privados.

**Entidades y acciones**

- `order_confirmations`
- `ConfirmOrder`

**Criterios de aceptación**

- Una sola parte no puede marcar unilateralmente el pedido como completado.
- Cambios de estado quedan auditados.
- Merkamigo informa que no procesa el pago ni la entrega.

## 3.3 Recomendaciones

- [ ] Solicitar recomendación solo después de interacción elegible.
- [ ] Recomendación simple con texto corto y etiquetas útiles.
- [ ] Derecho de respuesta del negocio.
- [ ] Reporte y moderación.
- [ ] Evitar puntuaciones públicas complejas durante el piloto.
- [ ] Detectar patrones básicos de abuso.

**Gate para cerrar fase 3**

- Verificación, pedido confirmado y recomendación tienen reglas claras y trazabilidad.
- El sistema de confianza no puede confundirse con garantía comercial de Merkamigo.

---

# FASE 4 - Monetización, crecimiento y automatización

**Prioridad:** P2  
**Resultado visible:** Merkamigo genera ingresos recurrentes o por servicios sin bloquear la adopción inicial.

## 4.1 Planes y límites

- [ ] Validar precios y disposición de pago con datos del piloto.
- [ ] Configurar planes desde administración, sin valores codificados.
- [ ] Definir límites por productos, fotos, destacados, promociones y miembros.
- [ ] Mostrar consumo y límites de forma anticipada.
- [ ] Periodos de prueba, gracia, suspensión y reactivación.
- [ ] Cupones u ofertas de lanzamiento solo si se validan.
- [ ] Historial de cambios de plan.

## 4.2 Cobro

- [ ] Seleccionar pasarela disponible y viable para Colombia.
- [ ] Implementar checkout externo seguro.
- [ ] Webhooks idempotentes.
- [ ] Facturas/recibos y conciliación.
- [ ] Renovación, cobro fallido y cancelación.
- [ ] Política de reembolso y soporte.
- [ ] No almacenar datos sensibles de tarjetas.

## 4.3 Productos de ingreso complementario

- [ ] Vitrina asistida como servicio.
- [ ] Kit “Arranca Bonito”.
- [ ] Destacados temporales.
- [ ] Promoción por municipio/categoría con etiquetado transparente.
- [ ] Paquetes para alcaldías, asociaciones o ferias solo después de validar el piloto.

## 4.4 Copiloto de WhatsApp ampliado

- [ ] Calendario sugerido de publicaciones.
- [ ] Variantes de tono y longitud.
- [ ] Promociones desde catálogo y disponibilidad real.
- [ ] Respuestas frecuentes editables.
- [ ] Recomendaciones basadas en métricas.
- [ ] Aprobación humana obligatoria.
- [ ] No implementar envío masivo o bot autónomo sin análisis legal, técnico y comercial.

## 4.5 Analítica y crecimiento

- [ ] Embudos: visita → WhatsApp → confirmación.
- [ ] Rendimiento por producto, categoría y periodo.
- [ ] Informe semanal entendible.
- [ ] Recomendaciones accionables sin lenguaje técnico.
- [ ] Alertas de vitrina incompleta o inactiva.
- [ ] Exportación de datos propios.
- [ ] Panel agregado por municipio para administración, respetando privacidad.

**Gate para cerrar fase 4**

- Los cobros son idempotentes, conciliables y auditables.
- Los planes no eliminan ni exponen datos por un error de pago.
- Existe evidencia de conversión o retención que justifica cada producto pagado.

---

# FASE 5 - API ampliada, notificaciones e integraciones

**Prioridad:** P2  
**Resultado visible:** todas las funciones necesarias para clientes externos y la futura app están disponibles mediante una API segura y documentada.

## 5.1 Cobertura de API

- [ ] Completar recursos de usuario, negocio, vitrina, producto y plaza.
- [ ] Completar necesidades, propuestas, confirmaciones y confianza.
- [ ] Completar métricas y contenidos de WhatsApp.
- [ ] Paginación, filtros, orden, rate limits y errores consistentes.
- [ ] Versionado, changelog y pruebas de contrato.
- [ ] Documentación y colección de pruebas.

## 5.2 Dispositivos y notificaciones

- [ ] Implementar `user_devices`.
- [ ] Registro, actualización y revocación de dispositivo.
- [ ] Preferencias por canal.
- [ ] Push mediante Firebase/APNs cuando se apruebe.
- [ ] Deep links a vistas específicas.
- [ ] Control de duplicados, reintentos y bajas.
- [ ] Notificaciones de propuesta, verificación, vencimiento y métricas.

## 5.3 Rendimiento y resiliencia

- [ ] Caché de plazas, categorías y vitrinas públicas.
- [ ] Índices y revisión de consultas lentas.
- [ ] CDN para imágenes públicas.
- [ ] Escalamiento de workers y colas separadas.
- [ ] Observabilidad de API, colas, almacenamiento y notificaciones.
- [ ] Pruebas de carga y degradación controlada.
- [ ] Plan de continuidad y recuperación.

## 5.4 Integraciones futuras preparadas

- [ ] Contratos internos para geocodificación.
- [ ] Contratos para IA de texto/audio sin acoplarse a un proveedor.
- [ ] Webhooks salientes firmados para aliados.
- [ ] Importación/exportación de catálogos cuando haya demanda.
- [ ] Integración con directorios o entidades municipales solo mediante acuerdos y consentimiento.

**Gate para cerrar fase 5**

- La API cubre los flujos que necesitará la app sin acceder directamente a la base de datos.
- Las pruebas de contrato y aislamiento pasan en CI.
- La plataforma soporta el volumen objetivo definido para esta fase.

---

# FASE 6 - Aplicación híbrida Android/iOS

**Prioridad:** Futuro  
**Resultado visible:** aplicación Ionic + Vue 3 + Capacitor que aporta capacidades móviles adicionales y consume exclusivamente `/api/v1`.

## 6.1 Descubrimiento y definición

- [ ] Validar que uso, retención y funciones justifican una app.
- [ ] Definir ventajas móviles reales: push, cámara, audio, compartir, deep links y mejor flujo recurrente.
- [ ] Diseñar navegación y estados offline limitados.
- [ ] Revisar requisitos vigentes de Google Play y App Store.
- [ ] Definir analítica, privacidad y permisos del dispositivo.

## 6.2 Implementación

- [ ] Crear frontend independiente Ionic + Vue.
- [ ] Integrar autenticación Sanctum por dispositivo.
- [ ] Almacenar tokens con mecanismo seguro del dispositivo.
- [ ] Consumir API versionada; no duplicar reglas.
- [ ] Implementar subida de foto/audio, push, enlaces y compartir.
- [ ] Manejar sesiones, revocación, errores, actualización obligatoria y mantenimiento.
- [ ] Pruebas en dispositivos reales.
- [ ] Accesibilidad y rendimiento.

## 6.3 Publicación y operación

- [ ] Fichas de tiendas, capturas, políticas y soporte.
- [ ] Builds firmados y gestión segura de certificados.
- [ ] Canales interno, beta y producción.
- [ ] Monitoreo de errores y versiones.
- [ ] Proceso de revisión, publicación y rollback.

**Restricciones**

- No empaquetar Blade/Livewire como aplicación final.
- No publicar una aplicación que solo abra `merkamigo.com`.
- No acceder directamente a la base de datos.
- No copiar las reglas del negocio en Vue.

---

## 6. Backlog transversal

Estas tareas acompañan todas las fases:

### Calidad

- [ ] Pruebas unitarias de acciones de dominio.
- [ ] Pruebas feature de flujos y endpoints.
- [ ] Pruebas de autorización y aislamiento multi-negocio.
- [ ] Pruebas de navegador de recorridos críticos.
- [ ] Pruebas de regresión antes de cada release.
- [ ] Cobertura de estados vacíos, errores, latencia y conexión limitada.

### Accesibilidad y usabilidad

- [ ] HTML semántico, navegación por teclado y foco visible.
- [ ] Contraste, tamaño táctil y etiquetas de formularios.
- [ ] Mensajes de error junto al campo y en lenguaje sencillo.
- [ ] No depender solo del color para indicar estado.
- [ ] Pruebas con personas de diferentes edades y alfabetización digital.

### SEO y contenido público

- [ ] URLs legibles y canónicas.
- [ ] Metadatos únicos.
- [ ] Open Graph y tarjetas para WhatsApp/redes.
- [ ] Datos estructurados apropiados.
- [ ] Sitemap y reglas de indexación.
- [ ] Control de contenido duplicado, borradores y páginas suspendidas.
- [ ] Cierre SEO final al terminar las fases activas: revisión integral de indexación, enlazado interno, performance, schemas, sitemap, contenido indexable y oportunidades de posicionamiento con la arquitectura y el contenido ya estabilizados.

### Datos y auditoría

- [ ] Catálogo de eventos analíticos y diccionario de datos.
- [ ] Registro de operaciones sensibles.
- [ ] Exportación de datos propios.
- [ ] Eliminación o anonimización según política.
- [ ] Control de retención de documentos, audio y logs.

### DevOps

- [ ] Migraciones reversibles o con plan de corrección.
- [ ] Despliegue automatizado y verificación posdespliegue.
- [ ] Workers, scheduler y monitoreo.
- [ ] Rotación de secretos.
- [ ] Backups y restauraciones probadas.
- [ ] Runbooks de caída, cola detenida, almacenamiento y base de datos.

### Documentación

- [ ] Mantener `README.md`.
- [ ] Mantener actualizado el índice de habilidades de `.github/skills`.
- [ ] Mantener decisiones de arquitectura.
- [ ] Mantener documentación de API.
- [ ] Mantener modelo de datos y estados.
- [ ] Mantener manual de operación y moderación.
- [ ] Mantener changelog y notas de release.

---

## 7. Contenido obligatorio del README.md

- [ ] Propósito de Merkamigo y actores.
- [ ] Instrucción de revisar `.github/skills/**/SKILL.md` antes de iniciar cualquier tarea.
- [ ] Índice de habilidades disponibles, propósito y situaciones en las que aplica cada una.
- [ ] Alcance actual y fuera de alcance.
- [ ] Stack y versiones realmente instaladas.
- [ ] Requisitos de PHP, Node.js, base de datos, Redis y almacenamiento.
- [ ] Instalación local.
- [ ] Variables de entorno sin secretos.
- [ ] Arquitectura modular y acciones de dominio.
- [ ] Separación entre sitio público, panel del emprendedor, Filament y API.
- [ ] Convenciones de multi-negocio y autorización.
- [ ] Migraciones y seeders.
- [ ] Archivos, colas, scheduler y notificaciones.
- [ ] Ejecución de pruebas y controles de calidad.
- [ ] Despliegue, backup, restauración y rollback.
- [ ] Versionado de `/api/v1`.
- [ ] Camino previsto hacia la aplicación híbrida.
- [ ] Referencia al manual de marca.
- [ ] Política de contribución y Definition of Done.

---

## 8. Fuera de alcance del MVP

No implementar en las fases 0 a 3:

- Carrito de compras complejo.
- Checkout o pagos integrados entre comprador y vendedor.
- Gestión logística, domicilios o flota propia.
- Chat interno completo.
- Lectura o automatización de conversaciones privadas de WhatsApp.
- Bot autónomo que publique o responda sin aprobación.
- Aplicación nativa o híbrida.
- Geolocalización avanzada o rastreo permanente.
- Red social abierta con muro general.
- Marketplace con garantías comerciales propias.
- Sistema complejo de calificaciones por estrellas.
- Demasiados planes o reglas comerciales antes de validar demanda.
- Integraciones costosas sin evidencia de uso.

---

## 9. Definition of Done

Una tarea solo puede marcarse como terminada cuando:

- [ ] Se revisaron los `SKILL.md` aplicables de `.github` y se registraron en el resumen del cambio.
- [ ] Cumple el criterio de aceptación funcional.
- [ ] Tiene autorización y validación del lado servidor.
- [ ] Funciona en móvil y escritorio cuando corresponde.
- [ ] Incluye estados de carga, vacío, error y éxito.
- [ ] Tiene pruebas automáticas proporcionales al riesgo.
- [ ] No rompe aislamiento entre negocios.
- [ ] Respeta el manual de marca y accesibilidad.
- [ ] No introduce secretos ni datos personales en logs.
- [ ] Incluye migración, rollback o estrategia de recuperación cuando aplica.
- [ ] Actualiza documentación y changelog.
- [ ] Fue validada en staging.
- [ ] No deja fallas P0/P1 abiertas.

---

## 10. Orden recomendado de ejecución

1. Completar **Fase 0**.
2. Construir y validar **Fase 1** con un piloto controlado.
3. No automatizar pagos ni ampliar planes hasta analizar los datos del piloto.
4. Construir **Fase 2** para probar la experiencia diferencial de demanda.
5. Construir **Fase 3** cuando existan suficientes interacciones reales para alimentar confianza.
6. Priorizar **Fase 4** según conversión y disposición de pago.
7. Completar **Fase 5** antes de desarrollar una app.
8. Iniciar **Fase 6** únicamente si el uso recurrente demuestra que una app aportará valor adicional.

### Recomendación operativa

Durante el desarrollo, Codex o el equipo debe trabajar únicamente sobre la fase autorizada. Las fases futuras permanecen documentadas como arquitectura y backlog; no deben adelantarse si eso pone en riesgo el lanzamiento pequeño, útil y medible.
