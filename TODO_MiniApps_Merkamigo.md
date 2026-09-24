# TODO — Evolución de Vitrinas a MiniApps Merkamigo

**Proyecto:** Merkamigo  
**Objetivo:** transformar las vitrinas actuales en experiencias tipo MiniApp, simples, rápidas y enfocadas en conversión, sin crear un producto separado ni romper las funcionalidades existentes.

---

## 0. Principios obligatorios de implementación

- [ ] **No crear un producto paralelo.** La vitrina actual debe evolucionar a MiniApp usando las mismas entidades, usuarios, negocios, productos, servicios y URLs siempre que sea posible.
- [ ] **No eliminar datos ni funcionalidades actuales.** Lo que no se muestre en la nueva interfaz debe mantenerse disponible en administración y/o vistas secundarias.
- [ ] **Mantener compatibilidad con negocios existentes.**
- [ ] **No cambiar URLs públicas existentes sin redirección 301.**
- [ ] **Priorizar mobile-first.**
- [ ] **La MiniApp pública debe abrir directamente desde QR, enlace, Instagram, WhatsApp, feed o resultados de búsqueda.**
- [ ] **No agregar un paso intermedio “Abrir MiniApp”.** La vitrina pública ya es la MiniApp.
- [ ] **Máximo 3 acciones visibles en el primer bloque.**
- [ ] **Cada negocio debe tener 1 acción principal de conversión y máximo 2 acciones secundarias.**
- [ ] **No convertir la MiniApp en dashboard, feed o marketplace.**
- [ ] **La red social de Merkamigo sirve para descubrir; la MiniApp sirve para convertir.**
- [ ] Aplicar identidad visual Merkamigo vigente: rojo corporativo, fondos claros, interfaz limpia, tipografía Poppins/Inter y alto contraste.

---

# FASE 1 — Auditoría y preparación

## 1.1 Revisar implementación actual

- [ ] Identificar modelo principal de negocio/vitrina.
- [ ] Identificar rutas públicas actuales de vitrinas.
- [ ] Identificar controladores y vistas Blade/Livewire/Inertia/Vue/React asociados.
- [ ] Identificar tablas actuales de:
  - negocios
  - categorías
  - productos
  - servicios
  - horarios
  - ubicaciones
  - redes sociales
  - promociones
  - reseñas
  - imágenes/galería
  - usuarios/propietarios
- [ ] Identificar dónde se genera actualmente el QR.
- [ ] Identificar botones actuales de WhatsApp/contacto.
- [ ] Identificar métricas actuales: vistas, clics, productos vistos, QR, etc.
- [ ] Documentar qué funcionalidades actuales se conservarán sin cambios.
- [ ] Antes de migraciones, generar respaldo de base de datos de producción.

## 1.2 Feature flag

- [ ] Crear feature flag para activar la nueva MiniApp por negocio.
- [ ] Ejemplo:
  - `miniapp_enabled`
  - o configuración global + override por negocio.
- [ ] Durante pruebas, permitir alternar entre vitrina clásica y MiniApp sin borrar nada.

---

# FASE 2 — Modelo funcional MiniApp

## 2.1 Agregar configuración de MiniApp al negocio

Crear migración segura únicamente si estos campos no existen.

Agregar al negocio o tabla de configuración relacionada:

- [ ] `business_type`
- [ ] `primary_conversion`
- [ ] `primary_cta_label`
- [ ] `secondary_cta_1`
- [ ] `secondary_cta_2`
- [ ] `contact_channel`
- [ ] `miniapp_enabled`
- [ ] `miniapp_template`
- [ ] `miniapp_published_at`

Valores sugeridos para `business_type`:

- `beauty`
- `restaurant`
- `store`
- `services`
- `professional`
- `tourism`
- `real_estate`
- `education`
- `health`
- `pets`
- `other`

Valores sugeridos para `primary_conversion`:

- `booking`
- `order`
- `buy`
- `quote`
- `contact`
- `request_service`
- `visit`
- `lead`

Valores sugeridos para `contact_channel`:

- `merkamigo`
- `whatsapp`
- `phone`
- `external_link`

## 2.2 Presets por sector

- [ ] Crear un servicio/configuración central para plantillas por sector.
- [ ] NO duplicar vistas completas por cada sector.
- [ ] Implementar un motor basado en configuración.

Ejemplo:

```php
return [
    'beauty' => [
        'primary_conversion' => 'booking',
        'primary_label' => 'Reservar cita',
        'secondary_actions' => ['services', 'contact'],
        'modules' => ['services', 'promotions', 'schedule', 'location'],
    ],

    'restaurant' => [
        'primary_conversion' => 'order',
        'primary_label' => 'Pedir ahora',
        'secondary_actions' => ['menu', 'location'],
        'modules' => ['products', 'promotions', 'schedule', 'location'],
    ],

    'services' => [
        'primary_conversion' => 'request_service',
        'primary_label' => 'Solicitar servicio',
        'secondary_actions' => ['services', 'quote'],
        'modules' => ['services', 'quote', 'schedule', 'location'],
    ],
];
```

- [ ] Permitir que el emprendedor cambie la acción principal sin modificar código.
- [ ] Permitir personalizar texto CTA dentro de límites definidos.

---

# FASE 3 — Nueva interfaz pública MiniApp

## 3.1 Mantener URL actual

- [ ] Reutilizar URL pública actual del negocio, por ejemplo:
  - `/m/{slug}`
  - o la ruta existente equivalente.
- [ ] No crear una ruta adicional obligatoria `/miniapp`.
- [ ] Si se crea alias nuevo, conservar la URL actual y redirigir correctamente.

## 3.2 Estructura principal

Rediseñar la vitrina pública con esta jerarquía:

### A. Hero

- [ ] Foto principal/portada.
- [ ] Logo del negocio.
- [ ] Nombre.
- [ ] Categoría.
- [ ] Municipio/ciudad.
- [ ] Distancia si existe permiso de ubicación.
- [ ] Calificación si está disponible.
- [ ] Estado: abierto/cerrado si hay horarios configurados.
- [ ] Texto corto del negocio, máximo 1–2 líneas.

### B. Acción principal

- [ ] Botón principal de ancho completo.
- [ ] Debe ser visualmente dominante.
- [ ] Ejemplos:
  - Reservar cita
  - Pedir ahora
  - Comprar
  - Cotizar
  - Solicitar servicio
  - Contactar

### C. Máximo 2 acciones secundarias

- [ ] Mostrar como botones compactos.
- [ ] Ejemplos:
  - Ver servicios
  - Ver menú
  - Cómo llegar
  - Cotizar
  - Contactar
- [ ] Nunca mostrar más de 3 acciones en el primer viewport.

### D. Contenido destacado

- [ ] Mostrar máximo 3–6 productos/servicios destacados.
- [ ] Tarjetas compactas.
- [ ] Foto.
- [ ] Nombre.
- [ ] Precio si aplica.
- [ ] CTA contextual.
- [ ] Agregar “Ver todos” cuando haya más elementos.

### E. Promoción destacada

- [ ] Mostrar máximo 1 promoción principal en home.
- [ ] Debe ser opcional.
- [ ] Permitir vigencia.
- [ ] Ocultar automáticamente promociones vencidas.

### F. Información rápida

- [ ] Horario.
- [ ] Ubicación.
- [ ] Cómo llegar.
- [ ] Redes sociales.
- [ ] Medios de contacto.
- [ ] Mostrar información secundaria en accordions, drawers o bottom sheets para no saturar.

### G. Footer

- [ ] Texto discreto:
  - `MiniApp by Merkamigo`
  - o `Creado con Merkamigo`
- [ ] Link a descubrir más negocios en Merkamigo.
- [ ] No competir visualmente con la conversión del negocio.

---

# FASE 4 — Navegación simplificada

## 4.1 Eliminar navegación innecesaria de la MiniApp pública

- [ ] No mostrar sidebar.
- [ ] No mostrar dashboard.
- [ ] No mostrar feed social dentro de la MiniApp.
- [ ] No mostrar menú principal completo de Merkamigo.
- [ ] No mostrar 8–10 accesos rápidos.
- [ ] No pedir login al comprador para consultar una MiniApp pública.

## 4.2 Navegación secundaria

Usar modales, bottom sheets o vistas ligeras para:

- [ ] todos los productos
- [ ] todos los servicios
- [ ] reservas
- [ ] cotizaciones
- [ ] información del negocio
- [ ] reseñas
- [ ] ubicación
- [ ] contacto

- [ ] Mantener botón de regreso visible.
- [ ] Conservar contexto del negocio en todo momento.

---

# FASE 5 — Conversiones por sector

## 5.1 Belleza / salud / citas

- [ ] CTA principal: `Reservar cita`.
- [ ] Listado de servicios.
- [ ] Duración opcional.
- [ ] Precio.
- [ ] Selector de fecha/hora cuando módulo de reservas esté activo.
- [ ] Si aún no existe agenda interna, usar acción fallback configurable.

## 5.2 Restaurante / comida

- [ ] CTA principal: `Pedir ahora`.
- [ ] Mostrar menú/catálogo.
- [ ] Agregar productos a pedido/carrito si ya existe soporte.
- [ ] Permitir fallback a mensajería/contacto si pedidos internos aún no están disponibles.

## 5.3 Tienda / productos

- [ ] CTA principal: `Comprar`.
- [ ] Catálogo.
- [ ] Variantes si existen.
- [ ] Acción por producto.
- [ ] Preparar interfaz para pagos posteriores sin bloquear el MVP.

## 5.4 Técnico / profesional

- [ ] CTA principal: `Solicitar servicio` o `Cotizar`.
- [ ] Mostrar servicios.
- [ ] Formulario corto:
  - nombre
  - mensaje
  - ubicación opcional
  - foto opcional
- [ ] Registrar solicitud dentro de Merkamigo.

## 5.5 Turismo / experiencias

- [ ] CTA principal: `Reservar`.
- [ ] Mostrar experiencias.
- [ ] Precio desde.
- [ ] Disponibilidad si existe.
- [ ] Ubicación.
- [ ] Galería compacta.

---

# FASE 6 — Contacto y mensajería

## 6.1 Abstraer el botón “Contactar”

- [ ] No codificar WhatsApp directamente en la interfaz.
- [ ] Crear acción genérica `Contactar`.
- [ ] Resolver canal según `contact_channel`.

Lógica sugerida:

```php
switch ($business->contact_channel) {
    case 'merkamigo':
        // abrir conversación interna
        break;
    case 'whatsapp':
        // abrir WhatsApp
        break;
    case 'phone':
        // tel:
        break;
    default:
        // formulario de contacto
}
```

## 6.2 Preparación para mensajería interna

- [ ] Integrar con módulo de chat interno si ya está disponible.
- [ ] Crear conversación usuario ↔ negocio.
- [ ] Notificar al negocio.
- [ ] Conservar contexto:
  - negocio
  - producto
  - servicio
  - publicación
  - promoción
- [ ] Si el usuario no inició sesión, permitir iniciar contacto con flujo mínimo o fallback.

---

# FASE 7 — Panel del emprendedor simplificado

## 7.1 Nueva home de “Mi negocio”

Crear una pantalla muy simple.

Mostrar:

- [ ] saludo + nombre del negocio
- [ ] vistas esta semana
- [ ] acciones/conversiones esta semana
- [ ] CTA `Ver mi MiniApp`
- [ ] botón `Agregar producto/servicio`
- [ ] botón `Crear promoción`
- [ ] botón `Ver solicitudes`
- [ ] estado de configuración de MiniApp
- [ ] QR
- [ ] copiar enlace
- [ ] compartir

Ejemplo visual de jerarquía:

```text
Hola Laura 👋

Tu MiniApp recibió
128 visitas esta semana

23 acciones generadas

[ Ver mi MiniApp ]

¿Qué quieres hacer?

[ + Producto / servicio ]
[ Crear promoción ]
[ Ver solicitudes ]

Tu MiniApp
✓ Logo
✓ Horario
✓ Servicios
✓ Ubicación

[ QR ] [ Copiar enlace ] [ Compartir ]
```

## 7.2 Ocultar complejidad

Mover opciones avanzadas a:

- [ ] `Configuración`
- [ ] `Más`
- [ ] `Administrar negocio`

No mostrar en home:

- [ ] configuraciones técnicas
- [ ] campos SEO avanzados
- [ ] datos poco usados
- [ ] herramientas futuras
- [ ] IA como módulo protagonista

---

# FASE 8 — Onboarding simplificado

## 8.1 Nuevo flujo

### Paso 1 — Tipo de negocio

- [ ] Seleccionar sector.

### Paso 2 — Objetivo principal

- [ ] Preguntar:
  - Reservar
  - Comprar
  - Pedir
  - Cotizar
  - Contactar
  - Solicitar servicio

### Paso 3 — Datos mínimos

- [ ] Nombre del negocio.
- [ ] Logo o foto.
- [ ] Municipio/ciudad.
- [ ] Contacto.
- [ ] Instagram opcional.

### Paso 4 — Generación

- [ ] Aplicar preset automático.
- [ ] Crear MiniApp.
- [ ] Mostrar preview inmediato.
- [ ] CTA: `Publicar mi MiniApp`.

## 8.2 Datos opcionales después

Después de publicar, sugerir completar:

- [ ] productos/servicios
- [ ] horario
- [ ] ubicación
- [ ] promoción
- [ ] redes
- [ ] reseñas

No bloquear publicación por campos secundarios.

---

# FASE 9 — Integración con Merkamigo Social

## 9.1 Feed → MiniApp

- [ ] Cada publicación de negocio debe poder enlazar:
  - negocio
  - producto
  - servicio
  - promoción
- [ ] Al tocar el negocio/producto desde una publicación, abrir la MiniApp correspondiente.
- [ ] No mandar al usuario a una cadena de pantallas intermedias.

Flujo:

```text
Feed / búsqueda / mapa / promoción
               ↓
         MiniApp negocio
               ↓
       acción de conversión
```

## 9.2 Compartir

- [ ] Agregar botón compartir MiniApp.
- [ ] Copiar enlace.
- [ ] Compartir por Web Share API.
- [ ] Generar preview Open Graph.
- [ ] Usar imagen principal + nombre + CTA corto.
- [ ] Mantener QR único por negocio.

---

# FASE 10 — QR

## 10.1 QR del negocio

- [ ] El QR debe apuntar directamente a la MiniApp pública.
- [ ] Mantener QR actual si la URL no cambia.
- [ ] Si cambia la URL, redireccionar la anterior.
- [ ] Añadir página/modal para:
  - visualizar QR
  - descargar QR
  - imprimir QR
  - copiar enlace

## 10.2 Tracking

- [ ] Registrar origen `qr`.
- [ ] Registrar timestamp.
- [ ] Registrar negocio.
- [ ] Registrar sesión/visitante anónimo cuando corresponda.
- [ ] NO guardar ubicación precisa sin permiso.

---

# FASE 11 — Métricas orientadas a resultados

## 11.1 Eventos

Crear tracking normalizado de eventos:

- [ ] `miniapp_view`
- [ ] `primary_cta_click`
- [ ] `secondary_cta_click`
- [ ] `product_view`
- [ ] `service_view`
- [ ] `booking_started`
- [ ] `booking_completed`
- [ ] `quote_started`
- [ ] `quote_submitted`
- [ ] `order_started`
- [ ] `order_completed`
- [ ] `contact_started`
- [ ] `directions_click`
- [ ] `share_click`
- [ ] `qr_visit`

## 11.2 Dashboard simple

Mostrar al emprendedor:

- [ ] visitas
- [ ] acciones generadas
- [ ] reservas
- [ ] pedidos
- [ ] contactos
- [ ] cotizaciones
- [ ] clics en cómo llegar
- [ ] conversión

Fórmula:

```text
conversión = acciones principales completadas / visitas MiniApp * 100
```

- [ ] No usar métricas de vanidad como único indicador.
- [ ] Mantener histórico.

---

# FASE 12 — PWA

- [ ] Verificar manifest actual.
- [ ] Configurar nombre dinámico cuando sea viable.
- [ ] Definir iconos.
- [ ] `display: standalone`.
- [ ] theme color Merkamigo.
- [ ] Garantizar navegación móvil correcta.
- [ ] Permitir “Agregar a pantalla de inicio” sin convertirlo en requisito.
- [ ] No mostrar popups invasivos de instalación.

---

# FASE 13 — IA invisible / posterior

NO poner IA como protagonista visual del MVP.

Preparar servicios internos para:

- [ ] generar descripción corta
- [ ] generar CTA recomendado
- [ ] mejorar títulos de productos
- [ ] generar descripciones
- [ ] crear promociones
- [ ] sugerir categorías
- [ ] generar SEO
- [ ] sugerir imagen faltante
- [ ] preparar textos para redes

- [ ] Todas estas funciones deben ser opcionales.
- [ ] El negocio siempre puede editar manualmente.

---

# FASE 14 — SEO y share cards

- [ ] `title` dinámico por MiniApp.
- [ ] `meta description`.
- [ ] Open Graph.
- [ ] Twitter/X card.
- [ ] canonical URL.
- [ ] schema.org `LocalBusiness` cuando aplique.
- [ ] schema.org `Product` / `Service` donde corresponda.
- [ ] municipio y categoría en metadatos.
- [ ] No generar contenido SEO visible excesivo en la interfaz.

---

# FASE 15 — Performance

- [ ] Lazy load de imágenes.
- [ ] Convertir imágenes a WebP/AVIF cuando sea posible.
- [ ] Definir tamaños responsivos.
- [ ] Evitar sliders pesados.
- [ ] Minimizar JavaScript inicial.
- [ ] Precargar hero.
- [ ] Evitar cargar módulos de administración en vista pública.
- [ ] Revisar Core Web Vitals.
- [ ] Objetivo: interacción principal disponible en < 2.5 s en conexión móvil razonable.

---

# FASE 16 — Accesibilidad

- [ ] Targets táctiles mínimos adecuados.
- [ ] Contraste AA.
- [ ] `alt` en imágenes.
- [ ] Labels en formularios.
- [ ] Navegación por teclado.
- [ ] Estados de foco.
- [ ] No depender solo del color.
- [ ] Mensajes de error claros.

---

# FASE 17 — Migración de negocios actuales

## 17.1 Backfill

- [ ] Crear comando Artisan para inferir configuración inicial de cada negocio existente.
- [ ] NO sobrescribir datos manuales.

Ejemplo:

```bash
php artisan merkamigo:configure-miniapps --dry-run
php artisan merkamigo:configure-miniapps
```

- [ ] Primero implementar `--dry-run`.
- [ ] Registrar log de cambios.
- [ ] Definir preset según categoría actual.
- [ ] Si no se puede inferir, usar `other`.
- [ ] No publicar cambios automáticamente en producción sin validación.

## 17.2 Compatibilidad

- [ ] Negocios sin configuración MiniApp deben seguir funcionando.
- [ ] Crear valores default.
- [ ] Evitar `null` errors.
- [ ] Probar vitrinas antiguas con información incompleta.

---

# FASE 18 — Testing

## 18.1 Unit tests

- [ ] Presets por sector.
- [ ] Resolver acción principal.
- [ ] Resolver canal de contacto.
- [ ] Métricas.
- [ ] Generación de URL.
- [ ] Promociones vigentes/vencidas.

## 18.2 Feature tests

- [ ] Abrir MiniApp pública.
- [ ] Negocio sin logo.
- [ ] Negocio sin portada.
- [ ] Negocio sin productos.
- [ ] Negocio con servicios.
- [ ] Negocio cerrado.
- [ ] Negocio sin ubicación.
- [ ] CTA principal.
- [ ] CTA contacto.
- [ ] QR.
- [ ] Compartir.
- [ ] Login no obligatorio para visitante.
- [ ] Acceso del propietario al panel.

## 18.3 Responsive

Probar:

- [ ] 320 px
- [ ] 375 px
- [ ] 390 px
- [ ] 430 px
- [ ] tablet
- [ ] desktop

Navegadores:

- [ ] Chrome
- [ ] Safari iPhone
- [ ] Safari macOS
- [ ] Edge
- [ ] Android Chrome

---

# FASE 19 — Criterios de aceptación

La primera versión se considera terminada cuando:

- [ ] Un negocio existente puede convertirse en MiniApp sin volver a registrar información.
- [ ] La misma URL pública continúa funcionando.
- [ ] La primera pantalla muestra identidad + máximo 3 acciones.
- [ ] Existe una sola acción principal claramente dominante.
- [ ] El contenido cambia según el tipo de negocio.
- [ ] El usuario puede llegar desde QR directamente a la MiniApp.
- [ ] El comprador no necesita registrarse para verla.
- [ ] Productos/servicios actuales siguen disponibles.
- [ ] Se puede compartir enlace y QR.
- [ ] El emprendedor puede ver métricas básicas.
- [ ] El panel del negocio muestra únicamente acciones frecuentes en la home.
- [ ] Merkamigo Social enlaza correctamente a MiniApps.
- [ ] No se perdió ninguna información existente.
- [ ] No se rompieron rutas antiguas.
- [ ] No existen migraciones destructivas.
- [ ] La vista es claramente usable desde celular.

---

# FASE 20 — Orden recomendado de ejecución para Codex

Ejecutar en este orden:

1. [ ] Auditar modelos, rutas, controladores y DB actual.
2. [ ] Crear feature flag.
3. [ ] Crear configuración/presets por sector.
4. [ ] Agregar campos mínimos necesarios con migración segura.
5. [ ] Crear resolver de MiniApp.
6. [ ] Rediseñar vista pública reutilizando URL existente.
7. [ ] Implementar CTA principal y acciones secundarias.
8. [ ] Adaptar productos/servicios.
9. [ ] Adaptar promociones.
10. [ ] Adaptar horarios/ubicación.
11. [ ] Crear abstracción de contacto.
12. [ ] Integrar mensajería interna/fallback.
13. [ ] Simplificar panel de negocio.
14. [ ] Simplificar onboarding.
15. [ ] Conectar feed/búsqueda/mapa con MiniApp.
16. [ ] Añadir tracking de conversiones.
17. [ ] Ajustar QR y compartir.
18. [ ] PWA.
19. [ ] SEO/Open Graph.
20. [ ] Tests.
21. [ ] Ejecutar backfill en `--dry-run`.
22. [ ] QA en staging.
23. [ ] Activación progresiva en producción.

---

# NO HACER

- [ ] No crear un tercer producto independiente de Merkamigo.
- [ ] No duplicar negocios ni productos.
- [ ] No crear una MiniApp distinta en código por cada sector.
- [ ] No mostrar dashboard al comprador.
- [ ] No llenar la pantalla de accesos rápidos.
- [ ] No obligar a instalar PWA.
- [ ] No obligar a iniciar sesión para consultar.
- [ ] No eliminar WhatsApp todavía si existen negocios que dependen de él.
- [ ] No mostrar “IA” como argumento central del producto.
- [ ] No ejecutar migraciones destructivas en producción.
- [ ] No borrar columnas antiguas en esta fase.
- [ ] No cambiar slugs existentes.
- [ ] No romper SEO ni enlaces ya compartidos.
- [ ] No mezclar el feed social dentro de la experiencia de conversión de la MiniApp.

---

# Resultado esperado

**Merkamigo = descubrimiento local**

Búsqueda, categorías, cerca de mí, mapa, promociones, feed, publicaciones y recomendaciones.

**MiniApp del negocio = conversión**

Una experiencia directa y simple para:

- comprar
- pedir
- reservar
- cotizar
- solicitar
- contactar

La plataforma puede ser compleja internamente, pero para el usuario debe sentirse simple:

> **Quiero esto → hago clic aquí.**

Y para el emprendedor:

> **Quiero vender → comparto mi Merkamigo.**
