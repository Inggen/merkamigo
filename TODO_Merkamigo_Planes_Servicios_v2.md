# TODO — Merkamigo: Planes de plataforma + Servicios opcionales para crecer

## 0. Objetivo de este cambio

Actualizar la página de **Planes y precios** para que el emprendedor entienda de forma inmediata dos conceptos distintos, sin afectar los planes ya desarrollados:

1. **Planes de plataforma** → Básico, Emprendedor y Negocios.
2. **Servicios opcionales para crecer** → trabajo adicional prestado por Merkamigo, puntual o mensual.

La implementación debe conservar la lógica actual de suscripciones y evitar duplicar beneficios ya incluidos en cada plan.

> Regla de negocio principal: **el plan da acceso a herramientas y capacidades; los servicios son trabajo adicional hecho por Merkamigo.**

---

# 1. REFERENCIA VISUAL OBLIGATORIA

Usar como guía principal de UI/UX el mockup adjunto:

`Mockup_Planes_Precios_Servicios_Merkamigo.png`

Al incorporarlo al repositorio, ubicarlo preferiblemente en:

`public/mockups/planes-precios-servicios-merkamigo.png`

La implementación no tiene que ser pixel-perfect, pero sí debe respetar la **jerarquía, separación visual, orden de bloques, copies principales y lógica comercial** representada en el mockup.

### Estructura visual esperada

1. Hero / encabezado.
2. Planes de plataforma.
3. Toggle Mensual / Anual.
4. Comparación de planes.
5. Servicios opcionales para crecer.
6. Contenido mensual: Esencial / Impulsa.
7. Combinaciones recomendadas.
8. Impulsos puntuales.
9. Preguntas frecuentes.
10. CTA final / footer existente.

---

# 2. REGLAS DE SEGURIDAD DEL REFACTOR

- [ ] NO eliminar ni renombrar los planes actuales: **Básico, Emprendedor, Negocios**.
- [ ] NO cambiar su lógica de permisos, límites o capacidades salvo lo indicado en este TODO.
- [ ] NO crear un cuarto plan llamado Impulsa.
- [ ] NO duplicar Wompi, suscripciones, órdenes o lógica de pagos si ya existe infraestructura reutilizable.
- [ ] NO vender nuevamente un beneficio que el usuario ya tenga incluido en su plan.
- [ ] NO hardcodear precios en Blade/Vue/React si ya existe configuración/modelo para planes o servicios.
- [ ] NO editar migraciones históricas ya aplicadas en producción.
- [ ] Toda migración nueva debe ser aditiva, reversible y compatible con datos existentes.
- [ ] Antes de eliminar código legacy, confirmar que no tiene referencias activas.
- [ ] Al terminar, eliminar rutas, componentes, vistas, servicios, estilos y JS obsoletos del antiguo flujo que ya no se utilicen.

---

# 3. AUDITORÍA OBLIGATORIA ANTES DE PROGRAMAR

Crear primero:

`docs/auditoria-planes-servicios-merkamigo.md`

Documentar:

- [ ] rutas actuales de planes/precios;
- [ ] modelos de planes y suscripciones;
- [ ] tablas de base de datos asociadas;
- [ ] lógica actual de Wompi;
- [ ] implementación actual de destacados;
- [ ] asistente IA;
- [ ] vitrina asistida;
- [ ] cualquier implementación anterior llamada Impulsa;
- [ ] órdenes/pagos adicionales existentes;
- [ ] permisos/policies;
- [ ] componentes frontend de la página actual;
- [ ] qué se reutiliza;
- [ ] qué se modifica;
- [ ] qué se elimina;
- [ ] qué se deja intacto.

No iniciar el refactor hasta terminar esta auditoría.

---

# 4. PLANES DE PLATAFORMA — CONSERVAR

Mantener los tres planes actuales como producto principal de suscripción.

## 4.1 Básico

Precio visible:

**$0 COP**

Mostrar al menos:

- vitrina pública en Plaza;
- hasta 5 productos o servicios;
- 1 colaborador;
- Pídelo en Merkamigo / capacidades base vigentes.

CTA:

**Crear mi vitrina gratis**

## 4.2 Emprendedor

Precio actual:

**$49.900 COP / mes**

Mostrar al menos:

- hasta 20 productos o servicios;
- hasta 2 vitrinas;
- hasta 3 colaboradores;
- Copiloto WhatsApp;
- cobros con Wompi propio;
- ver ventas pagadas;
- 7 días de destacado en Plaza.

CTA:

**Comenzar ahora**

## 4.3 Negocios

Precio actual:

**$99.000 COP / mes**

Mostrar al menos:

- hasta 50 productos o servicios;
- hasta 5 vitrinas;
- hasta 5 colaboradores;
- Asistente IA / chatbot;
- Live Commerce;
- métricas avanzadas 90 días + CSV;
- 15 días de destacado en Plaza.

CTA:

**Comenzar ahora**

---

# 5. FACTURACIÓN MENSUAL / ANUAL

Agregar selector visible únicamente para los planes de plataforma:

- **Mensual**
- **Anual**

## Regla anual

El beneficio será:

> **1 mes gratis pagando 1 año completo**

Interpretación:

- el usuario recibe 12 meses;
- paga el equivalente a 11 mensualidades.

### Valores anuales esperados

- Emprendedor: `49.900 x 11 = 548.900 COP / año`
- Negocios: `99.000 x 11 = 1.089.000 COP / año`

### Implementación

- [ ] No mostrar descuento ficticio porcentual si la oferta comercial es “1 mes gratis”.
- [ ] El backend debe calcular el valor anual, no confiar en el frontend.
- [ ] La suscripción anual debe registrar duración/período correctamente.
- [ ] Mantener lógica mensual existente intacta.
- [ ] Si el sistema actual no soporta anualidad, extender la infraestructura existente; no crear un sistema paralelo.

---

# 6. COMPARACIÓN DE PLANES

Mantener o mejorar la tabla comparativa existente.

Debe estar visualmente debajo de los tres planes.

Comparar, como mínimo:

- productos/servicios;
- vitrinas;
- colaboradores;
- días de destacado;
- Copiloto WhatsApp;
- cobros en línea;
- ventas pagadas;
- Asistente IA;
- Live Commerce;
- métricas avanzadas.

No mezclar servicios humanos dentro de esta tabla.

---

# 7. NUEVA SECCIÓN INDEPENDIENTE: SERVICIOS OPCIONALES PARA CRECER

Esta sección debe verse claramente separada de los planes mediante:

- fondo distinto suave;
- mayor espacio vertical;
- encabezado propio;
- copy explicativo;
- cards distintas a las de planes.

Título:

**Servicios opcionales para crecer**

Subtítulo:

> Son adicionales a tu plan. No todos los negocios los necesitan.

Agregar bloque informativo:

> Si un beneficio ya está incluido en tu plan, la plataforma lo mostrará como incluido y no lo cobrará de nuevo.

---

# 8. CONTENIDO MENSUAL — DOS SERVICIOS PRINCIPALES

Estos servicios deben ser visualmente los más importantes dentro de “Servicios opcionales”.

## 8.1 Contenido mensual Esencial

Precio:

**$299.900 COP / mes**

Incluye inicialmente:

- 4 piezas gráficas;
- 2 reels cortos;
- 6 historias;
- copies para publicar;
- adaptación básica para la vitrina;
- soporte mensual ligero.

Badge:

**Requiere vitrina activa**

CTA:

**Agregar servicio**

## 8.2 Contenido mensual Impulsa

Precio:

**$499.900 COP / mes**

Incluye inicialmente:

- 8 piezas gráficas;
- 4 reels cortos;
- 12 historias;
- copies + calendario mensual;
- optimización de contenido para vitrina y Plaza;
- revisión estratégica mensual.

Badges:

- **Requiere vitrina activa**
- **Más completo**

Texto auxiliar sugerido:

> Ideal para negocios que quieren crecer más rápido.

CTA:

**Agregar servicio**

---

# 9. REGLA DE DEPENDENCIA PARA CONTENIDO MENSUAL

Un servicio de contenido mensual requiere que el usuario tenga al menos una vitrina activa en Merkamigo.

### Validación

1. usuario selecciona servicio;
2. backend valida si tiene vitrina activa;
3. si no tiene vitrina:
   - mostrar explicación;
   - CTA para crear vitrina o seleccionar plan;
4. si tiene vitrina activa:
   - permitir continuar;
5. si tiene varias vitrinas:
   - solicitar a cuál se asociará el servicio.

El servicio mensual de contenido **NO debe obligar a cambiar de plan** si el plan actual soporta correctamente su vitrina y la publicación del contenido.

---

# 10. COMBINACIONES RECOMENDADAS / HÍBRIDOS

No crear nuevos planes técnicos en base de datos.

Los “híbridos” son únicamente una forma comercial de mostrar:

**Plan de plataforma + servicio mensual**

Mostrar como recomendaciones, no como productos duplicados.

## Ejemplo 1

**Emprendedor + Contenido mensual Esencial**

- Plan Emprendedor: $49.900
- Servicio Esencial: $299.900
- Total mensual: **$349.800 COP**

## Ejemplo 2

**Negocios + Contenido mensual Impulsa**

- Plan Negocios: $99.000
- Servicio Impulsa: $499.900
- Total mensual: **$598.900 COP**

### Regla técnica

No crear `hybrid_plan`, `combo_plan` ni una suscripción duplicada si no es estrictamente necesario.

El checkout debe manejar dos conceptos separados:

- suscripción del plan;
- servicio adicional.

Si ambos son recurrentes, pueden mostrarse juntos al usuario, pero deben conservar trazabilidad independiente en backend.

---

# 11. IMPULSOS PUNTUALES

Mostrar como una tercera sub-sección dentro de Servicios opcionales.

Título:

**Impulsos puntuales**

Texto:

> Servicios de pago único o de corta duración para necesidades específicas.

## 11.1 Destacar tu vitrina

Precios actuales de referencia:

- 7 días → **$9.900 COP**
- 14 días → **$16.900 COP**
- 30 días → **$29.900 COP**

Antes de cobrar, revisar si el usuario tiene días incluidos disponibles en su plan.

Si aplica beneficio:

**Incluido en tu plan**

CTA:

**Usar beneficio**

## 11.2 Asistente IA para tu vitrina

Precio independiente de referencia:

**$29.900 COP**

Si Negocios ya lo incluye:

- mostrar “Incluido en Negocios”;
- no cobrar nuevamente;
- CTA: **Configurar asistente**.

## 11.3 Vitrina asistida

Precio:

**$49.900 COP**

Incluye trabajo humano de optimización de:

- textos;
- fotos existentes;
- categorías;
- organización de información.

## 11.4 Sesión de fotos básica

Precio:

**$79.900 COP**

Definición sugerida:

- sesión básica;
- cantidad configurable de imágenes finales;
- requiere agendamiento.

## 11.5 Kit Arranca Bonito

Precio:

**$99.900 COP**

Definirlo como paquete inicial para nuevos negocios:

- sesión de fotos básica;
- mejora de vitrina;
- destacado inicial, solo si no está ya cubierto por el plan.

### Regla crítica

Si el destacado está incluido en el plan del usuario, el backend debe descontarlo del combo o sustituirlo por el beneficio existente según la regla comercial definida.

Nunca cobrar dos veces el mismo beneficio.

---

# 12. CATÁLOGO DE SERVICIOS — BACKEND

No hardcodear cada card en frontend.

Crear o reutilizar catálogo administrable.

Modelo sugerido:

`growth_services`

Campos mínimos sugeridos:

- `id`
- `name`
- `slug`
- `short_description`
- `description`
- `price`
- `billing_type` (`one_time`, `monthly`)
- `service_category` (`monthly_content`, `boost`, `human_service`, etc.)
- `requires_business`
- `requires_active_storefront`
- `requires_scheduling`
- `active`
- `sort_order`
- `metadata` JSON nullable
- timestamps

Si ya existe una entidad compatible, reutilizarla y extenderla.

---

# 13. ENTITLEMENTS / BENEFICIOS DEL PLAN

Centralizar la lógica en un único servicio de dominio, por ejemplo:

`BusinessEntitlementsService`

Debe responder:

- plan activo;
- límites;
- días de destacado disponibles;
- IA incluida o no;
- capacidades habilitadas;
- si un servicio adicional se puede comprar;
- si un beneficio debe mostrarse como incluido.

No duplicar condicionales por plan en varias vistas/controladores.

---

# 14. CHECKOUT Y PAGOS

Reutilizar Wompi o la pasarela ya existente.

## Casos

### A. Comprar solo un plan

`Plan → Resumen → Pago → Activación`

### B. Comprar solo un servicio puntual

`Servicio → Seleccionar vitrina → Pago → Orden → Ejecución`

### C. Comprar contenido mensual

`Servicio mensual → Seleccionar vitrina → Pago/recurrente → Orden activa`

### D. Comprar combinación recomendada

Mostrar un único resumen visual, pero registrar internamente:

- suscripción de plataforma;
- servicio mensual adicional.

No fusionar ambos conceptos de forma que se pierda trazabilidad.

---

# 15. ÓRDENES DE SERVICIO

Crear o reutilizar:

`service_orders`

Campos sugeridos:

- `id`
- `user_id`
- `business_id`
- `growth_service_id`
- `amount`
- `currency`
- `billing_type`
- `status`
- `payment_status`
- `payment_reference`
- `scheduled_at` nullable
- `started_at` nullable
- `completed_at` nullable
- `notes` nullable
- `metadata` JSON nullable
- timestamps

Estados sugeridos:

- pending_payment
- paid
- pending_scheduling
- scheduled
- in_progress
- pending_approval
- completed
- cancelled

---

# 16. SERVICIOS MENSUALES — ESTADO Y RECURRENCIA

Para contenido mensual almacenar además:

- fecha inicio;
- próximo cobro;
- estado recurrente;
- vitrina asociada;
- paquete contratado;
- historial de renovaciones;
- cancelación al final del período si aplica.

Si ya existe motor de suscripciones reutilizable, extenderlo.

No crear una segunda arquitectura de recurrencia si no hace falta.

---

# 17. MIS SERVICIOS

Agregar en panel del emprendedor:

`/panel/servicios`

Subsecciones sugeridas:

- **Servicios disponibles**
- **Mis servicios**

Mostrar por servicio contratado:

- nombre;
- vitrina;
- modalidad;
- valor;
- estado;
- próxima renovación si aplica;
- próxima acción;
- agendamiento si aplica.

---

# 18. AGENDAMIENTO

Aplica a servicios como sesión de fotos o Arranca Bonito.

MVP:

1. cliente compra;
2. propone fecha;
3. administración confirma;
4. se notifica al cliente.

No integrar calendario complejo si no existe aún.

---

# 19. ADMINISTRACIÓN

Crear/ajustar módulo admin para:

### Servicios

- crear;
- editar;
- activar/desactivar;
- precio;
- modalidad;
- categoría;
- orden;
- requisitos;
- copy;
- metadata.

### Órdenes

- cliente;
- negocio;
- servicio;
- pago;
- estado;
- fechas;
- notas internas;
- seguimiento.

---

# 20. UI / UX — CRITERIOS OBLIGATORIOS

La página final debe parecerse conceptualmente al mockup de referencia.

### Planes

- cards grandes;
- tres columnas;
- precio visible;
- CTA claro;
- anualidad visible;
- tabla comparativa debajo.

### Servicios

- bloque visual totalmente separado;
- contenido mensual primero;
- dos cards grandes para $299.900 y $499.900;
- combos recomendados después;
- impulsos puntuales en cards pequeñas al final.

### No hacer

- no mezclar contenido mensual dentro de las cards de planes;
- no presentar Impulsa como plan de plataforma;
- no poner todos los servicios con el mismo peso visual;
- no mostrar al usuario demasiada lógica técnica.

---

# 21. COPY PRINCIPAL

## Hero

**Un plan para cada etapa de tu negocio**

> Empieza gratis con tu vitrina en la Plaza. Cuando quieras crecer, mejora de plan o agrega servicios que te ayuden a vender más.

## Planes

**Planes de plataforma**

> Desbloquea la capacidad de tu vitrina y accede a herramientas para mostrar tus productos, conectar con clientes y hacer crecer tu negocio.

## Servicios

**Servicios opcionales para crecer**

> Son adicionales a tu plan. No todos los negocios los necesitan.

## Contenido mensual

> Nos encargamos de crear contenido para tu negocio y mantener tu vitrina activa con publicaciones que conectan.

## Híbridos

**Combinaciones recomendadas**

> Tu plan te da las herramientas y el servicio te entrega el contenido listo para publicar.

## Impulsos

**Impulsos puntuales**

> Servicios de pago único o de corta duración para necesidades específicas.

---

# 22. LIMPIEZA DE CÓDIGO LEGACY

Después de implementar y probar:

- [ ] eliminar UI antigua duplicada de Impulsa;
- [ ] retirar rutas legacy sin uso;
- [ ] eliminar componentes muertos;
- [ ] eliminar controladores sin referencias;
- [ ] eliminar CSS/JS exclusivo de vistas retiradas;
- [ ] eliminar strings y precios hardcodeados obsoletos;
- [ ] eliminar imports muertos;
- [ ] limpiar navegación/breadcrumbs;
- [ ] revisar policies y permisos.

No eliminar datos históricos.

No editar migraciones históricas.

---

# 23. MIGRACIÓN DE DATOS EXISTENTES

Si existen compras o configuraciones del módulo anterior:

- [ ] identificar registros;
- [ ] mapearlos al nuevo catálogo;
- [ ] conservar referencias de pago;
- [ ] conservar historial;
- [ ] documentar lo que no pueda migrarse automáticamente.

---

# 24. TESTS OBLIGATORIOS

## Planes

- [ ] Básico funciona.
- [ ] Emprendedor funciona.
- [ ] Negocios funciona.
- [ ] Upgrade/downgrade existente continúa funcionando.
- [ ] Mensual funciona.
- [ ] Anual calcula 11 mensualidades y habilita 12 meses.

## Servicios

- [ ] listado muestra solo activos;
- [ ] Esencial cuesta $299.900;
- [ ] Impulsa cuesta $499.900;
- [ ] requiere vitrina activa;
- [ ] se puede seleccionar vitrina;
- [ ] compra genera orden;
- [ ] pago seguro confirma orden;
- [ ] mensualidad/renovación funciona si aplica.

## Beneficios

- [ ] destacado incluido no se cobra otra vez;
- [ ] IA incluida en Negocios no se cobra otra vez;
- [ ] usuario sí puede comprar servicios humanos aunque tenga plan Negocios.

## Híbridos

- [ ] Emprendedor + Esencial muestra $349.800;
- [ ] Negocios + Impulsa muestra $598.900;
- [ ] backend registra conceptos separados.

## Seguridad

- [ ] no manipular precio desde frontend;
- [ ] validar ownership de negocio;
- [ ] usuario no accede a órdenes ajenas;
- [ ] frontend no puede marcar pagos como confirmados.

## UI

- [ ] responsive desktop/tablet/mobile;
- [ ] jerarquía visual equivalente al mockup;
- [ ] planes y servicios claramente separados;
- [ ] servicios mensuales fáciles de comparar.

---

# 25. ORDEN DE EJECUCIÓN PARA CODEX / CLAUDE

1. Auditar código actual.
2. Generar `docs/auditoria-planes-servicios-merkamigo.md`.
3. Confirmar componentes y backend reutilizables.
4. Preservar planes actuales.
5. Implementar toggle Mensual / Anual.
6. Implementar anualidad de 11 pagos = 12 meses.
7. Crear/ajustar catálogo de servicios.
8. Implementar `BusinessEntitlementsService` o equivalente.
9. Crear servicios mensuales Esencial / Impulsa.
10. Crear impulsos puntuales.
11. Implementar recomendaciones híbridas sin crear nuevos planes reales.
12. Reutilizar pagos existentes.
13. Crear/ajustar órdenes de servicio.
14. Implementar recurrencia de contenido mensual usando infraestructura existente.
15. Crear/ajustar “Mis servicios”.
16. Crear administración.
17. Actualizar página de Planes y precios siguiendo el mockup.
18. Migrar datos legacy si existen.
19. Eliminar código muerto comprobado.
20. Ejecutar tests.
21. Validar manualmente todos los planes actuales.
22. Validar pagos y renovaciones.
23. Entregar informe técnico final.

---

# 26. CRITERIOS DE ACEPTACIÓN

La tarea está completa solamente cuando:

- Básico, Emprendedor y Negocios siguen funcionando.
- Planes y servicios se entienden visualmente como productos distintos.
- Existe mensual/anual para planes de plataforma.
- El anual aplica 1 mes gratis: paga 11 y recibe 12.
- Existen claramente los servicios de contenido de $299.900 y $499.900.
- Ambos requieren vitrina activa.
- Existen combinaciones recomendadas sin duplicar productos en backend.
- Los impulsos puntuales siguen disponibles.
- Los beneficios incluidos en un plan no se cobran dos veces.
- Los pagos quedan trazables.
- Los servicios humanos pueden agendarse.
- No existe un cuarto plan llamado Impulsa.
- No queda UI vieja duplicada.
- No queda código muerto comprobado del flujo anterior.
- No se pierden datos históricos.
- No se modifican migraciones ya ejecutadas.
- La UI final sigue la estructura del mockup referenciado.

---

# 27. ENTREGA FINAL DEL AGENTE

Crear:

`docs/implementacion-planes-servicios-merkamigo.md`

Incluir:

- resumen de cambios;
- archivos creados;
- archivos modificados;
- archivos eliminados;
- migraciones nuevas;
- decisiones de arquitectura;
- cómo se preservaron planes existentes;
- cómo se manejó anualidad;
- cómo se implementaron servicios mensuales;
- cómo se evitó doble cobro de beneficios;
- cómo se migró/eliminó Impulsa legacy;
- pruebas ejecutadas;
- riesgos o pendientes.

Cerrar el documento con esta confirmación explícita:

> “Los planes existentes Básico, Emprendedor y Negocios fueron preservados y validados después del refactor. Los servicios opcionales fueron implementados como una capa comercial y operativa separada, sin convertirlos en nuevos planes de plataforma.”
