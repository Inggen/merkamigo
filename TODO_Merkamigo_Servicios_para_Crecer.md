# TODO — Merkamigo: separar Planes de Plataforma y Servicios para Crecer

## Objetivo

Simplificar la experiencia del emprendedor sin afectar los planes mensuales ya desarrollados.

La estructura final debe quedar así:

- **Mi plan Merkamigo** = suscripción mensual a la plataforma.
  - Básico
  - Emprendedor
  - Negocios
- **Servicios para crecer** = servicios adicionales, puntuales o recurrentes, prestados por Merkamigo.
  - Mejorar mi vitrina
  - Sesión de fotos / contenido
  - Crear contenido para mi negocio
  - Promocionar mi negocio
  - Administración mensual de contenido

### Regla principal

> El plan entrega herramientas y capacidades de plataforma. Los servicios para crecer representan trabajo adicional realizado por Merkamigo para el negocio.

Nunca se debe cobrar nuevamente como servicio algo que el usuario ya tenga incluido dentro de su plan.

---

# 0. REGLAS DE IMPLEMENTACIÓN

- [ ] **NO modificar la lógica de los planes actuales salvo que sea estrictamente necesario para integrarlos con la nueva sección.**
- [ ] **NO renombrar ni eliminar los planes Básico, Emprendedor y Negocios.**
- [ ] **NO modificar precios, límites o permisos existentes de los planes.**
- [ ] Antes de tocar código, realizar inventario de lo ya desarrollado relacionado con:
  - planes;
  - suscripciones;
  - destacados;
  - asistente IA;
  - vitrina asistida;
  - Impulsa;
  - pagos;
  - órdenes;
  - servicios adicionales.
- [ ] Reutilizar componentes, servicios, modelos y tablas existentes cuando tengan sentido.
- [ ] No duplicar lógica existente.
- [ ] No dejar rutas, controladores, vistas, componentes, modelos, migraciones o servicios obsoletos después del refactor.
- [ ] Cualquier eliminación debe hacerse solo después de comprobar que no tiene referencias activas.
- [ ] No ejecutar migraciones destructivas sobre producción.
- [ ] Las nuevas migraciones deben ser aditivas y reversibles.
- [ ] Mantener compatibilidad con registros ya existentes.

---

# 1. AUDITORÍA DEL CÓDIGO ACTUAL — HACER ANTES DE PROGRAMAR

## 1.1 Identificar implementación actual

- [ ] Localizar todos los archivos relacionados con:
  - `plans`
  - `subscriptions`
  - `impulsa`
  - `services`
  - `addons`
  - `destacados`
  - `assistant / ai`
  - `vitrina asistida`
  - `payments`
  - `orders`
- [ ] Identificar modelos y relaciones actuales.
- [ ] Identificar tablas relacionadas en base de datos.
- [ ] Identificar enums, constantes o configuraciones de precios.
- [ ] Identificar rutas públicas y privadas.
- [ ] Identificar componentes Blade / Livewire / Vue / React utilizados.
- [ ] Identificar permisos por rol.

## 1.2 Generar mapa antes del cambio

Crear un documento interno temporal:

`docs/auditoria-servicios-merkamigo.md`

Debe indicar:

- qué existe;
- qué se reutiliza;
- qué se renombra;
- qué se migra;
- qué se elimina;
- qué se conserva intacto.

**No empezar el refactor hasta completar esta auditoría.**

---

# 2. CONSERVAR PLANES ACTUALES

La sección actual de planes debe mantenerse funcionando exactamente igual.

## Planes

### Básico
Mantener capacidades actuales.

### Emprendedor
Mantener capacidades actuales.

### Negocios
Mantener capacidades actuales.

- [ ] Verificar que upgrade/downgrade continúe funcionando.
- [ ] Verificar renovación y estado de suscripción.
- [ ] Verificar restricciones por plan.
- [ ] Verificar permisos y límites.
- [ ] Verificar Wompi/pagos asociados a planes si ya están implementados.

---

# 3. REEMPLAZAR EL CONCEPTO VISIBLE “IMPULSA” POR “SERVICIOS PARA CRECER”

No debe presentarse al usuario como un cuarto plan.

## Navegación

Agregar dentro del panel del emprendedor:

**Mi negocio**

- Mi vitrina
- Productos / servicios
- Publicaciones
- Métricas
- Mi plan
- **Servicios para crecer**

Si actualmente existe un menú llamado `Impulsa`, evaluar:

- [ ] renombrarlo a `Servicios para crecer`, si ya corresponde a este concepto;
- [ ] o migrar su contenido al nuevo módulo y retirar la vista anterior.

No dejar ambos menús si hacen lo mismo.

---

# 4. NUEVA PÁGINA: SERVICIOS PARA CRECER

Ruta sugerida:

`/panel/servicios`

Nombre interno sugerido:

`growth-services`

## Encabezado

### Título

**Servicios para crecer**

### Texto

> Tu plan te da las herramientas. Si quieres, Merkamigo también puede hacerlo por ti.

## Diseño

Mostrar tarjetas simples con:

- nombre;
- descripción breve;
- precio;
- modalidad: pago único / mensual;
- estado;
- CTA.

Evitar comparativas complejas tipo tabla de planes.

---

# 5. CATÁLOGO DE SERVICIOS

Crear catálogo administrable. No hardcodear los servicios directamente en las vistas.

## Estructura sugerida

Tabla/modelo:

`growth_services`

Campos sugeridos:

- `id`
- `name`
- `slug`
- `short_description`
- `description`
- `price`
- `billing_type`
  - `one_time`
  - `monthly`
- `service_type`
- `active`
- `sort_order`
- `requires_scheduling`
- `requires_business`
- `metadata` JSON nullable
- timestamps

---

# 6. SERVICIOS INICIALES

## 6.1 Mejora mi vitrina

Nombre público sugerido:

**Mejora mi vitrina**

Incluye trabajo humano para:

- revisar información;
- completar descripciones;
- organizar categorías;
- mejorar presentación;
- revisar imágenes;
- dejar la vitrina lista para publicar.

Precio inicial de referencia:

**$49.900 COP**

> No confundir con tener una vitrina. La vitrina ya forma parte de la plataforma; aquí se cobra el trabajo realizado por el equipo.

---

## 6.2 Sesión de contenido

Nombre público:

**Sesión de contenido**

Incluye según configuración comercial:

- fotografías;
- clips cortos;
- material para publicaciones;
- contenido base para actualizar la vitrina.

Debe requerir agendamiento.

---

## 6.3 Arranca Bonito

Mantener el nombre únicamente si se desea conservar como producto comercial.

Debe redefinirse para evitar duplicación con planes y destacados.

### Nueva definición

**Arranca Bonito**

> Preparamos la imagen inicial de tu negocio para que puedas empezar a comunicar mejor desde Merkamigo.

Puede incluir:

- sesión básica de contenido;
- selección y edición básica de fotografías;
- optimización visual y textual de la vitrina;
- piezas iniciales listas para publicar.

**NO incluir automáticamente:**

- destacado de Plaza si ya está incluido en el plan;
- asistente IA si ya está incluido en el plan;
- capacidades propias del plan.

Precio inicial de referencia:

**$99.900 COP**

---

## 6.4 Crea contenido para mi negocio

Servicio puntual.

El usuario puede seleccionar qué quiere promocionar:

- negocio;
- producto;
- servicio;
- evento;
- promoción;
- lanzamiento.

Luego Merkamigo genera/produce el contenido acordado.

---

## 6.5 Administración mensual de contenido

Este sí puede ser recurrente.

No presentarlo como “plan Merkamigo”.

Presentarlo como:

**Servicio mensual**

Puede incluir diferentes niveles comerciales posteriormente.

Ejemplo:

- creación de contenido;
- edición;
- publicaciones;
- calendario;
- seguimiento.

Implementar inicialmente el soporte técnico para recurrencia aunque los paquetes se activen luego desde administración.

---

# 7. DESTACADOS Y FUNCIONES DE PLATAFORMA

Los destacados no deben presentarse mezclados con servicios humanos.

Mantenerlos como **impulsos promocionales de plataforma** si ya están desarrollados.

Ejemplo:

### Destacar mi negocio

- 7 días
- 14 días
- 30 días

Antes de mostrar precio:

- [ ] revisar plan actual;
- [ ] revisar beneficios disponibles;
- [ ] revisar días incluidos o saldo promocional;

Si el usuario ya tiene un beneficio disponible, mostrar:

**Incluido en tu plan**

CTA:

**Usar beneficio**

No mostrar botón de compra mientras tenga beneficio aplicable.

---

# 8. ASISTENTE IA

Antes de venderlo como adicional:

- [ ] comprobar plan actual.

Si está incluido:

**Incluido en tu plan**

CTA:

**Configurar asistente**

Si no está incluido y comercialmente se permite compra independiente:

mostrar precio y botón comprar.

Toda esta validación debe estar centralizada en backend.

No repetir condicionales del plan directamente en cada vista.

Crear o reutilizar un servicio del dominio, por ejemplo:

`BusinessEntitlementsService`

Responsabilidades:

- determinar capacidades del plan;
- validar beneficios incluidos;
- validar si un servicio puede comprarse;
- evitar cobros duplicados.

---

# 9. FLUJO DE COMPRA

## Servicios digitales inmediatos

Flujo:

`Servicio → Resumen → Pago → Confirmación → Activación`

## Servicios humanos

Flujo:

`Servicio → Seleccionar negocio → Detalles → Pago → Solicitud creada → Agendar / Coordinar`

No mandar al usuario fuera del panel innecesariamente.

---

# 10. MODELO DE ÓRDENES DE SERVICIO

Crear o reutilizar modelo genérico.

Nombre sugerido:

`service_orders`

Campos:

- `id`
- `user_id`
- `business_id`
- `growth_service_id`
- `amount`
- `currency`
- `status`
- `payment_status`
- `payment_reference`
- `scheduled_at` nullable
- `notes` nullable
- `metadata` JSON nullable
- timestamps

## Estados sugeridos

- `pending_payment`
- `paid`
- `pending_scheduling`
- `scheduled`
- `in_progress`
- `pending_approval`
- `completed`
- `cancelled`

No crear estados redundantes si el proyecto ya dispone de un sistema genérico compatible.

---

# 11. MIS SERVICIOS

Agregar dentro del panel:

`/panel/servicios/mis-servicios`

Mostrar compras realizadas.

Cada tarjeta/fila debe mostrar:

- servicio;
- negocio;
- fecha;
- valor;
- estado;
- próxima acción.

Ejemplos:

**Arranca Bonito**

`Pagado · Falta agendar`

CTA:

`Agendar sesión`

---

# 12. AGENDAMIENTO

Para servicios que lo requieran:

- [ ] almacenar `requires_scheduling`;
- [ ] permitir seleccionar fechas disponibles si ya existe módulo compatible;
- [ ] si aún no existe agenda automática, implementar inicialmente solicitud de fecha.

MVP permitido:

- usuario propone fecha;
- administrador confirma;
- usuario recibe notificación.

Evitar integrar un calendario complejo en esta fase si no es necesario.

---

# 13. ADMINISTRACIÓN

Agregar módulo al panel administrativo.

## Servicios

El administrador puede:

- crear;
- editar;
- activar/desactivar;
- ordenar;
- cambiar precio;
- cambiar descripción;
- definir si es puntual o mensual;
- definir si requiere agenda.

## Órdenes

El administrador puede:

- visualizar compra;
- negocio;
- usuario;
- pago;
- servicio;
- estado;
- fecha solicitada/agendada;
- notas internas;
- cambiar estado.

---

# 14. PAGOS

Reutilizar la infraestructura de pagos que ya tenga Merkamigo.

No crear una segunda integración con Wompi si ya existe.

- [ ] Identificar servicio actual de pagos.
- [ ] Crear concepto/tipo de pago para `growth_service`.
- [ ] Guardar referencia de la orden.
- [ ] Confirmar pago vía mecanismo existente/webhook.
- [ ] Marcar orden como `paid` únicamente desde respuesta segura/backend.

Nunca confiar en confirmación del frontend.

---

# 15. EXPERIENCIA DE USUARIO

## En lugar de mostrar

“Compra Plan Impulsa”

mostrar:

**¿En qué quieres que te ayudemos?**

Tarjetas:

- Mejorar mi vitrina
- Crear contenido
- Tomar fotos de mi negocio
- Promocionar una oferta
- Ayudarme todos los meses

La interfaz debe orientarse a la necesidad, no al nombre técnico del producto.

---

# 16. DETECCIÓN DE BENEFICIOS DEL PLAN

Antes de cada compra ejecutar una validación como:

```text
1. Obtener negocio.
2. Obtener plan vigente.
3. Obtener capabilities/entitlements.
4. Revisar si el beneficio solicitado ya está incluido.
5. Si está incluido → ofrecer activar/usar.
6. Si no está incluido → permitir compra.
```

Esta regla debe existir una sola vez en backend.

No repetirla en controladores y componentes.

---

# 17. LIMPIEZA DE CÓDIGO ANTERIOR

Después de implementar y probar la nueva arquitectura:

- [ ] localizar código antiguo de `Impulsa` que haya quedado sin uso;
- [ ] eliminar rutas antiguas sin referencias;
- [ ] eliminar componentes no usados;
- [ ] eliminar controladores sin uso;
- [ ] eliminar vistas duplicadas;
- [ ] retirar strings antiguos;
- [ ] retirar precios hardcodeados anteriores;
- [ ] eliminar imports muertos;
- [ ] eliminar CSS exclusivo de componentes retirados;
- [ ] eliminar JS no usado;
- [ ] revisar navegación y breadcrumbs;
- [ ] revisar permisos/policies.

### IMPORTANTE

No eliminar migraciones históricas ya aplicadas en producción.

Si una tabla vieja ya está en producción y deja de usarse:

- primero retirar dependencias;
- verificar datos;
- documentar;
- crear migración nueva solo si realmente es necesario modificarla.

Nunca editar una migración que ya fue ejecutada en producción.

---

# 18. COMPATIBILIDAD DE DATOS EXISTENTES

Si ya existen compras/registros del antiguo módulo Impulsa:

- [ ] no perder registros;
- [ ] crear script/migración de normalización si corresponde;
- [ ] mapear registros antiguos hacia `growth_services/service_orders` cuando sea posible;
- [ ] conservar referencias de pagos;
- [ ] documentar cualquier registro que no pueda migrarse automáticamente.

No borrar información comercial histórica.

---

# 19. TESTS MÍNIMOS OBLIGATORIOS

## Planes

- [ ] Básico continúa funcionando.
- [ ] Emprendedor continúa funcionando.
- [ ] Negocios continúa funcionando.
- [ ] Upgrade funciona.
- [ ] Downgrade funciona si ya estaba habilitado.

## Servicios

- [ ] listado de servicios solo muestra activos.
- [ ] puede abrir detalle.
- [ ] puede comprar servicio.
- [ ] pago genera orden.
- [ ] webhook confirma pago.
- [ ] orden cambia de estado correctamente.

## Beneficios

- [ ] usuario no puede pagar por una función ya incluida en su plan cuando exista beneficio disponible.
- [ ] usuario sí puede comprar servicio humano aunque tenga plan superior.

## Seguridad

- [ ] un usuario no puede ver órdenes de otro negocio.
- [ ] un usuario no puede manipular precio desde frontend.
- [ ] un usuario no puede marcar una orden como pagada.
- [ ] validar business ownership.

---

# 20. UI FINAL ESPERADA

## Panel

### Mi plan

Debe seguir mostrando:

- Básico
- Emprendedor
- Negocios

Sin mezclar servicios.

### Servicios para crecer

Encabezado:

**Haz crecer tu negocio**

Texto:

> Tu plan te da las herramientas. Si quieres, nosotros también podemos ayudarte a hacerlo.

Tarjetas orientadas por acción.

---

# 21. COPY RECOMENDADO

## Menú

**Servicios para crecer**

## Encabezado

**¿En qué podemos ayudarte?**

## Subtexto

**Contrata ayuda puntual o mensual para mejorar la presencia de tu negocio.**

## Badges

- Pago único
- Servicio mensual
- Requiere agenda
- Incluido en tu plan

---

# 22. NO HACER

- [ ] No crear un cuarto plan llamado Impulsa.
- [ ] No mezclar planes de software con servicios humanos.
- [ ] No repetir funcionalidades del plan dentro de un paquete pagado sin validación.
- [ ] No duplicar integración de pagos.
- [ ] No crear modelos nuevos si ya existe uno genérico reutilizable.
- [ ] No hardcodear precios en la vista.
- [ ] No dejar componentes viejos comentados “por si acaso”.
- [ ] No dejar rutas legacy activas sin necesidad.
- [ ] No eliminar migraciones históricas.
- [ ] No tocar producción antes de ejecutar pruebas.

---

# 23. ORDEN RECOMENDADO DE EJECUCIÓN PARA CODEX / CLAUDE

1. Auditar código actual.
2. Documentar arquitectura encontrada.
3. Confirmar qué partes existentes se reutilizarán.
4. Crear catálogo genérico de servicios si no existe.
5. Crear órdenes de servicio si no existe modelo compatible.
6. Crear `BusinessEntitlementsService` o equivalente.
7. Implementar backend de servicios.
8. Integrar pagos reutilizando infraestructura actual.
9. Crear página `Servicios para crecer`.
10. Crear `Mis servicios`.
11. Crear administración.
12. Migrar referencias antiguas de Impulsa si existen.
13. Retirar UI anterior duplicada.
14. Eliminar código muerto comprobado.
15. Ejecutar tests automáticos.
16. Ejecutar pruebas manuales de planes existentes.
17. Probar compra de cada tipo de servicio.
18. Revisar permisos y seguridad.
19. Revisar responsive.
20. Entregar informe final de archivos creados/modificados/eliminados.

---

# 24. CRITERIOS DE ACEPTACIÓN

La tarea está terminada únicamente cuando:

- Los planes Básico, Emprendedor y Negocios funcionan igual que antes.
- El usuario entiende visualmente que “Mi plan” y “Servicios para crecer” son cosas diferentes.
- No existe un cuarto plan visualmente equivalente a los planes mensuales.
- Los beneficios ya incluidos en un plan no se venden nuevamente.
- Los servicios humanos pueden comprarse independientemente del plan.
- Los pagos quedan registrados y asociados a una orden.
- Los servicios que necesitan coordinación pueden agendarse.
- El administrador puede gestionar servicios y órdenes.
- No existen vistas duplicadas del antiguo Impulsa.
- No quedan rutas o componentes muertos relacionados con la implementación anterior.
- No se modificaron migraciones históricas ya ejecutadas.
- No se perdieron datos históricos.
- Tests y flujos actuales de suscripción continúan pasando.

---

# 25. ENTREGA FINAL DEL AGENTE

Al terminar, Codex / Claude debe generar:

`docs/implementacion-servicios-para-crecer.md`

con:

- resumen de cambios;
- archivos creados;
- archivos modificados;
- archivos eliminados;
- migraciones creadas;
- decisiones de arquitectura;
- compatibilidad con implementación anterior;
- pruebas ejecutadas;
- pendientes o riesgos detectados.

Además debe indicar explícitamente:

> “Los planes existentes Básico, Emprendedor y Negocios fueron preservados y validados después del refactor.”

