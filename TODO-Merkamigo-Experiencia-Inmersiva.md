# TODO — Experiencia inmersiva de Merkamigo

**Versión:** 1.0  
**Fecha:** 4 de agosto de 2026  
**Estado general:** Propuesta para revisión  
**Prioridad:** MVP posterior a la vitrina web base

## 1. Objetivo

Crear una experiencia inmersiva ligera para web y móvil en la que cada municipio pueda tener una o varias plazas virtuales. Las vitrinas activas de los emprendedores del municipio aparecerán como stands organizados dentro de la plaza y los visitantes podrán recorrerla con un personaje, acercarse a cada stand y consultar sus productos o servicios mediante una interfaz HTML conectada con la vitrina web existente.

La experiencia debe:

- Ser reconocible como la plaza o parque real del municipio, con estilo voxel o low-poly optimizado.
- Reutilizar los datos existentes de vitrinas, productos, servicios y categorías.
- Evitar solapamientos, posiciones fuera de límites y bloqueos de rutas peatonales.
- Funcionar fluidamente en móvil y escritorio.
- Poder configurarse y publicarse desde el administrador sin modificar código para cada stand.
- Escalar cuando existan más vitrinas que espacios disponibles.

## 2. Decisiones de arquitectura

1. **La plaza es una escena fija por municipio.** No se genera mediante prompts durante la navegación.
2. **Los stands son dinámicos.** Se crean a partir de vitrinas activas y se asignan a espacios válidos previamente definidos.
3. **La ubicación se basa en slots, no en coordenadas aleatorias.** Cada slot contiene posición, rotación, tamaño máximo, orientación y estado.
4. **Una municipalidad puede tener varias plazas o instancias.** Esto permite paginar vitrinas cuando se supera la capacidad física de una escena.
5. **El detalle comercial se muestra en HTML superpuesto al canvas 3D.** Se reutilizan componentes y endpoints de la versión web; no se renderizan catálogos como texturas 3D.
6. **La orientación de cada stand se calcula desde la configuración de la plaza.** Puede mirar al centro, hacia afuera o usar una rotación manual por slot.
7. **La asignación de una vitrina debe ser persistente.** El stand conserva su plaza y slot entre visitas, salvo reorganización administrativa.
8. **Los stands se administran como objetos dinámicos, pero no forman parte del plano conceptual permanente.** El plano solo contiene una capa independiente de reservas o `slots de stand`, identificada con un color exclusivo. La IA generadora de la plaza no debe convertir estas marcas en construcciones.
9. **La orientación es propia de cada slot.** No se impone una única dirección a todos los stands: cada reserva guarda su frente según la geometría del mapa, la ruta peatonal y el espacio disponible.

## 3. Actores y permisos

| Actor | Permisos principales |
|---|---|
| Visitante | Entrar a una plaza publicada, escoger personaje genérico, caminar, filtrar stands, cambiar de plaza, abrir vitrinas y usar sus acciones públicas. |
| Emprendedor | Elegir uno de tres tipos de stand, previsualizarlo, ver en qué plaza aparece y solicitar/publicar cambios según las reglas de su plan. No puede mover libremente el stand. |
| Administrador Merkamigo | Crear experiencias, asociarlas a municipios, cargar escenas, definir zonas/slots, orientar stands, fijar capacidades, publicar versiones, reorganizar asignaciones y revisar métricas. |
| Gestor municipal — fase posterior | Administrar únicamente experiencias y contenidos del municipio asignado, sin acceso a otros municipios. |

## 4. Modelo funcional propuesto

### 4.1 Jerarquía

- **Municipalidad:** Zipaquirá, Cajicá, Chía, etc.
- **Experiencia inmersiva:** configuración, escena y versión publicable de un lugar.
- **Plaza:** página o instancia de capacidad dentro de una experiencia; por ejemplo, Plaza 1 y Plaza 2 de Zipaquirá.
- **Zona de stands:** polígono permitido dentro de la plaza.
- **Slot:** espacio exacto y validado donde puede ubicarse un stand.
- **Reserva visual de stand:** huella coloreada que representa un slot en el editor del mapa; no es un stand ni se exporta como parte de la geometría fija de la plaza.
- **Asignación:** relación persistente entre vitrina, plaza y slot.

### 4.2 Entidades mínimas

| Entidad | Campos relevantes |
|---|---|
| `municipalities` | id, nombre, slug, departamento, estado. |
| `immersive_experiences` | id, municipality_id, nombre, slug, descripción, estado, escena (route_name), versión, miniatura. |
| `immersive_plazas` | id, experience_id, nombre, número/orden, capacidad, regla de categorías, estado, fecha de publicación, **punto de aparición, límites navegables, centro de orientación, zonas excluidas, calidad móvil/escritorio, imagen de referencia (plano), imagen de leyenda** (movido aquí desde `immersive_experiences`: una experiencia puede tener varias plazas y cada una es una instancia física distinta, así que la configuración espacial es por plaza, no por experiencia — corrección aplicada en Fase 1 al construir IMM-012). |
| `stand_zones` | id, plaza_id, nombre, polígono/límites, orientación por defecto, centro de referencia, separación mínima, prioridad. |
| `stand_slots` | id, zone_id, código, **posición en la imagen de referencia (x,y relativos), posición en mundo 3D (x,y,z)**, rotación XYZ, tamaño máximo, orientación, plantilla esperada (`stand_template_id`, opcional), categoría permitida opcional, accesible, estado, **origen** (`manual`/`auto_detected`). Ver redefinición de IMM-013 más abajo: la posición de mundo no se adivina a mano, se calcula por calibración desde la imagen de referencia de la plaza. |
| `immersive_object_templates` | id, nombre, slug, **categoría** (`stand`, `construccion`, `arbol`, `fuente`, `monumento`, `personaje`), dimensiones máximas, eje frontal, miniatura, colores permitidos, estado. Renombrado desde `stand_templates` en Fase 1 (redefinición 2 de IMM-013): un solo catálogo cubre todo lo que puede colocarse en una plaza, no solo stands. **Fase 1 solo carga los metadatos**; el modelo 3D real (GLB, LOD) es IMM-020 en Fase 2 — hasta entonces `model_path`/`lod_config` quedan nulos y la plantilla no es renderizable, solo reservable/colocable. |
| `immersive_plaza_props` | id, plaza_id, object_template_id, posición en imagen, posición de mundo, rotación, escala, origen, estado. Nueva en Fase 1: construcciones/árboles/fuentes/monumentos/personajes colocados directo en la plaza, sin el flujo de asignación comercial que sí tienen los stands. |
| `plaza_legend_entries` | id, plaza_id, color_hex, muestras detectadas, object_template_id (nullable hasta que el admin lo confirma), estado (`pendiente`/`confirmado`). Nueva en Fase 1: un color distinto detectado en la imagen de leyenda de una plaza. |
| `avatars` | id, nombre, tipo hombre/mujer, recursos, animaciones, estado. Inicialmente dos genéricos. |
| `immersive_profiles` | user_id, avatar_id, preferencias de control/calidad. |
| `stand_assignments` | id, showcase_id, plaza_id, slot_id, template_id, estado, fecha_asignación, motivo_reubicación. |
| `experience_versions` | id, experience_id, versión, configuración publicada (incluye snapshot de cada plaza hija), checksum, autor, fecha, estado. |
| `immersive_events` | sesión anónima/usuario, experiencia, plaza, evento, vitrina/producto, fecha, dispositivo. |

## 5. Reglas de distribución de stands

### Reglas obligatorias

- Un slot solo puede tener una asignación activa.
- Un stand solo puede ocupar un slot compatible con sus dimensiones.
- El volumen del stand, incluyendo margen de seguridad, debe permanecer dentro de la zona permitida.
- Debe existir una distancia mínima configurable entre stands.
- Ningún stand puede bloquear accesos, rutas peatonales, monumentos, puntos de aparición o zonas de seguridad.
- Antes de guardar o publicar, se debe ejecutar detección de colisiones mediante cajas delimitadoras (`Box3`) y validación contra límites.
- La ubicación del stand no cambia al filtrar categorías; el filtro solo controla visibilidad.
- Al desactivar una vitrina, su asignación pasa a inactiva y el slot queda disponible.
- Al reactivar una vitrina, se intenta recuperar el slot anterior; si está ocupado, se asigna el siguiente compatible.

### Orientación

Cada zona o slot debe admitir uno de estos modos:

- `TOWARD_CENTER`: el frente mira hacia un punto central definido por el administrador.
- `AWAY_FROM_CENTER`: el frente mira en dirección contraria al punto central.
- `FOLLOW_PATH`: el frente queda perpendicular o paralelo a una ruta definida.
- `MANUAL`: el administrador determina la rotación exacta.

El modelo de cada stand debe declarar cuál es su eje frontal para evitar que quede visualmente al revés.

La orientación se previsualiza en el editor mediante una flecha individual dentro de cada reserva. El administrador puede usar una regla automática por zona y corregir slots concretos. Por tanto, dos stands de la misma plaza pueden mirar en direcciones distintas cuando el mapa lo requiera.

### Capa de reservas de stands en el mapa

- Debe existir una capa independiente llamada **“Ubicaciones de stands”**, visible u ocultable en el editor.
- Las reservas se muestran con un color que no se utilice para edificios, zonas verdes, caminos, mobiliario ni áreas caminables.
- Cada reserva muestra código, huella máxima, margen de seguridad, eje frontal y estado: disponible, ocupada, bloqueada o inválida.
- Las reservas sí hacen parte de la configuración espacial versionada, pero no del plano conceptual que la IA transforma en plaza voxel.
- El botón **“Generar plaza”** conserva las reservas y genera la geometría alrededor de ellas sin ocuparlas ni atravesarlas.
- Una validación previa debe detectar intersecciones de edificios, árboles, mobiliario, rutas o límites con las reservas.
- Los stands reales se incorporan en tiempo de ejecución únicamente cuando existe una asignación válida de vitrina, plantilla y slot.

### Asignación automática

Orden sugerido:

1. Resolver el municipio de la vitrina.
2. Buscar la experiencia publicada correspondiente.
3. Buscar plazas activas en orden.
4. Buscar slots libres compatibles con el tamaño y, si aplica, con la categoría.
5. Priorizar distribución equilibrada por zonas, evitando concentrar una misma categoría.
6. Validar límites, separación y colisiones.
7. Crear asignación persistente.
8. Si no hay capacidad, crear una solicitud de nueva plaza o, si está habilitado, activar automáticamente la siguiente plaza preconfigurada.

## 6. Estrategia cuando no caben más stands

Se recomienda combinar dos mecanismos:

1. **Filtros por categoría:** permiten explorar gastronomía, moda, servicios, salud, etc. No aumentan la capacidad física ni cambian las asignaciones.
2. **Navegación entre plazas:** Plaza 1, Plaza 2 y Plaza 3 funcionan como páginas de resultados dentro del mismo municipio. Cada una tiene asignaciones permanentes y capacidad real.

Reglas propuestas:

- Mostrar `Plaza 1 de 3` y botones anterior/siguiente o un mapa selector.
- Permitir acceso directo mediante URL: `/plaza/zipaquira?plaza=2&categoria=gastronomia`.
- Mantener búsqueda por nombre de negocio.
- Si una categoría tiene muchos negocios, ofrecer una plaza temática administrable en una fase posterior.
- No cargar simultáneamente todas las plazas; descargar únicamente la actual.

## 7. TODO priorizado

### Fase 0 — Diagnóstico y base técnica

- [x] **IMM-001 — Auditar los labs actuales de Zipaquirá y Cajicá**  
  **Resultado visible:** informe de peso, triángulos, draw calls, texturas, tiempos de carga, memoria y FPS en móvil/escritorio.  
  **Pantallas/endpoints:** labs actuales; panel técnico temporal.  
  **Entidades:** ninguna.  
  **Dependencias:** acceso al repositorio y modelos actuales.  
  **Criterio de aceptación:** registrar métricas en al menos un Android medio, un iPhone compatible y un escritorio; identificar los tres mayores cuellos de botella.  
  **Estado:** Hecho. Panel técnico temporal (`public/js/lib/immersive-perf-monitor.js`, `?perf=1` o tecla P) cableado en ambos labs; auditoría automatizada real con Chromium headless (`npm run audit:immersive` → `scripts/audit-immersive-performance.mjs`) sobre Escritorio/Android medio/iPhone gama media. Informe completo y los tres cuellos de botella en `docs/operacion/auditoria-rendimiento-labs-inmersivos.md`.

- [x] **IMM-002 — Definir presupuesto de rendimiento**  
  **Resultado visible:** límites automáticos para publicación.  
  **Reglas:** objetivo móvil de 30 FPS estables, carga inicial ideal menor a 4 MB, límite configurable de draw calls, texturas y memoria.  
  **Dependencias:** IMM-001.  
  **Criterio de aceptación:** el administrador recibe advertencia o bloqueo si una versión excede límites críticos.  
  **Estado:** Hecho para lo que ya existe hoy. Presupuesto centralizado en `public/js/lib/immersive-perf-budget.js`, coloreado en vivo en el panel técnico y aplicado por `scripts/audit-immersive-performance.mjs`, que sale con código de error si un perfil móvil queda en crítico. Falta enganchar este mismo chequeo al flujo de publicación/versionado una vez exista (Fase 1, IMM-014) — hoy no hay panel de publicación al cual bloquear.

- [x] **IMM-003 — Crear el núcleo reutilizable de experiencia**  
  **Resultado visible:** un único visor Three.js/WebGL reutilizable por todas las ciudades.  
  **Pantallas:** carga, error, modo ligero, controles táctiles y escritorio.  
  **Dependencias:** arquitectura actual del frontend.  
  **Criterio de aceptación:** la escena se inicializa por configuración, no mediante código duplicado por municipio.  
  **Estado:** Hecho (2026-08-05). Zipaquirá migrada de una implementación standalone de 2194 líneas a `public/js/lib/voxel-plaza-engine.js` — el mismo motor que ya usaba Cajicá. `public/js/zipa-plaza-immersive.js` quedó en 839 líneas (-62%): personaje, cámara, colisiones, física, texturas voxel y panel técnico ahora son 100% del motor compartido; solo queda geometría propia de la plaza (catedral/alcaldía en GLB, palmera/farol en GLB, domo de cielo, señalética de vitrinas) y sus propias variantes de `colonialHouse`/`arcadeRow`/`planter` — **no** los `standardBuilders` del motor, porque estos ya habían divergido de los originales de Zipaquirá (el motor le agregó a `colonialHouse` una base de piedra para Cajicá que Zipaquirá nunca tuvo); reusarlos a ciegas habría cambiado su apariencia en silencio.  
  Hallazgo colateral: **~550 líneas de código muerto** (`buildFlagCornerCluster`, `buildFrontChurchEdge`, `buildNorthernColonialEdge`, `buildNorthGrassBarrier`, `buildSouthGrassBarrier`, `buildChurchSideComplex`) sin un solo punto de llamada — nunca afectaron la escena renderizada; se eliminaron sin cambio de comportamiento.  
  Motor extendido con 2 capacidades mínimas y aditivas (no rompen Cajicá): `start(layout, builders, { deferSceneReady: true })` para plazas con carga async propia (GLB/texturas) que deben avisar ellas mismas cuándo la escena está lista, y `engine.onUpdate(callback)` para un hook por frame (usado por el HUD de coordenadas de Zipaquirá). `this.sun` de las luces ahora es público (antes una `const` local) para poder afinar el mapa de sombras por plaza.  
  **Verificado, no solo revisado:** capturas antes/después pixel-a-pixel idénticas, `renderer.info` casi idéntico (48→49 draw calls, 5.948.364→5.948.376 triángulos — diferencia de ruido de medición, no de contenido), cero errores de consola, y una prueba interactiva real (mover, saltar, colisionar) vía Chromium headless. `npm run audit:immersive` reproduce exactamente los mismos ~264 MB y el mismo veredicto crítico que el informe de auditoría original — la pesadez de Zipaquirá (GLB sin comprimir) sigue intacta y es harina de otro costal (IMM-020/compresión de assets), no algo que esta migración debía resolver. Fase 0 queda cerrada.

### Fase 1 — Administración de experiencias y plazas

- [x] **IMM-010 — CRUD de experiencias inmersivas**  
  **Resultado visible:** el administrador crea, edita, duplica, previsualiza, publica y archiva una experiencia.  
  **Pantallas/endpoints:** listado, formulario, detalle, previsualización; API de experiencias.  
  **Entidades:** `immersive_experiences`, `experience_versions`.  
  **Reglas/permisos:** solo administrador; slug único; no publicar sin escena, punto de aparición y límites.  
  **Criterio de aceptación:** crear una experiencia para Zipaquirá y otra para Cajicá sin cambios de código.  
  **Estado:** Hecho. Migraciones, modelos y recurso Filament (`Administración > Experiencias inmersivas`) creados y probados. **Duplicar:** `ImmersiveExperience::duplicate()` clona experiencia + plazas + zonas + slots + elementos como borrador nuevo (slug único autoincremental), acción "Duplicar" en el listado. **Validación antes de publicar:** `assertReadyToPublish()` bloquea publicar sin `route_name` asignado y sin al menos una plaza con punto de aparición + límites navegables — corre en `saving()` (no solo en la acción de publicar) pero SOLO al pasar a `publicada` (`isDirty('status')`), no en cada guardado de un registro ya publicado (bug real encontrado y corregido: Eloquent dispara `saving()` en todo `save()`, con o sin cambios — sin ese resguardo, reeditar cualquier campo de una experiencia publicada, o re-correr el seeder, fallaba la validación). **Previsualización real:** primera versión con una vista 2D (SVG superpuesto sobre la imagen de referencia: zonas como polígonos, zonas excluidas punteadas, slots como círculos por estado, elementos como cuadros), reemplazada por pedido explícito del usuario antes de cerrar Fase 1: "el botón de vista previa" debe dejar entrar y caminar la experiencia real, no solo ver el plano 2D — ver `ImmersivePlazaResource::enterExperienceAction()`, que abre `ImmersiveExperience::previewUrl()` (protegido para administradores). La acción 2D (`previewAction()`, vista `plaza-preview.blade.php`) quedó como código sin usar tras ese reemplazo — nunca se conectó a ninguna tabla/página (se encontró el 2026-08-12 al revisar tests fallando; el instinto inicial fue "arreglarla" cableándola de nuevo, pero el usuario recordó que ya estaba deliberadamente reemplazada, así que se eliminó por completo en vez de repararla: método, vista y sus 3 tests en `ImmersiveExperienceDuplicationAndPreviewTest.php`). Seeder actualizado para que las experiencias publicadas tengan una plaza válida. Fase 1 queda cerrada.

- [~] **IMM-011 — Asociar experiencia a municipalidad**  
  **Resultado visible:** selector de municipio y validación de asociación.  
  **Entidades:** `municipalities`, `immersive_experiences`.  
  **Reglas:** una experiencia pertenece a un municipio; un municipio puede tener varias experiencias, pero solo una experiencia principal publicada por tipo/contexto.  
  **Criterio de aceptación:** las vitrinas nunca aparecen en una municipalidad distinta.  
  **Estado:** Hecho para lo que esta fase cubre. `immersive_experiences.municipality_id` es una FK obligatoria (`Select::make('municipality_id')->relationship(...)` en el formulario); `ImmersiveExperience::booted()` bloquea que un municipio tenga dos experiencias en estado `publicada` a la vez. `Municipality::immersiveLabUrl()` ya NO tiene el `match($slug)` hardcodeado: ahora resuelve dinámicamente vía `publishedImmersiveExperience()->labUrl()`, que a su vez lee `route_name` (un `Select` sobre `config('immersive.available_scenes')`, no texto libre). `database/seeders/ImmersiveExperienceSeeder.php` publica las dos experiencias existentes (Zipaquirá/Cajicá) para que el CTA de `search-hero.blade.php` (hoy con `showImmersiveCta` en `false` en todos los llamadores, así que no es visible aún en producción) siga resolviendo exactamente igual que antes — verificado sin regresión. 8 tests en `ImmersiveExperienceResourceTest`. Sigue pendiente la conexión con las vitrinas/stands propiamente dichos (IMM-022/IMM-023), que no es parte de IMM-011.

- [x] **IMM-012 — Gestión de plazas/páginas de capacidad**  
  **Resultado visible:** crear Plaza 1, Plaza 2, ordenar, activar y definir capacidad.  
  **Entidades:** `immersive_plazas`.  
  **Dependencias:** IMM-010.  
  **Criterio de aceptación:** al llenarse Plaza 1, las nuevas vitrinas se asignan a Plaza 2 sin superposición.  
  **Estado:** Hecho el modelo/admin (`Administración > Experiencias inmersivas > Plazas`): orden, capacidad, regla de categorías, estado, fecha de publicación, y toda la configuración espacial que antes vivía mal ubicada en `immersive_experiences` (ver corrección de arquitectura en §4.2). `ImmersivePlaza::hasCapacityAvailable()`/`occupiedSlotsCount()` ya existen. Falta la asignación automática en sí que mueve una vitrina de una plaza llena a la siguiente — eso es IMM-022 (Fase 2), que depende de que existan vitrinas con stand asignado.

- [x] **IMM-013 — Editor de configuración espacial**  
  **Redefinición 1 (2026-08-05, con el usuario):** la redacción original no explicaba de dónde salen las coordenadas de cada slot ni exigía que existiera antes un catálogo de los objetos que se van a reservar. Corrección: (1) debe existir un catálogo de objetos antes de poder crear una reserva; (2) el administrador sube una imagen de referencia (planta/vista aérea) a la plaza, guía visual que el sistema NO interpreta automáticamente — la "IA que genera geometría desde un plano" sigue fuera de alcance del MVP (sección 11); (3) cada reserva se ubica sobre esa imagen y se traduce a coordenadas de mundo por calibración lineal contra `navigable_bounds` (`worldX = minX + (imgX/imgWidth) * (maxX-minX)`, análogo en Z) — el slot guarda ambas posiciones.  
  **Redefinición 2 (2026-08-05, con imagen de ejemplo del usuario — un plano real de Zipaquirá con leyenda de colores tipo "S1→ Stands, ⬛ Monumento, ▬ Pileta grande, ● Pino..."):** el catálogo no es solo de stands — cubre construcciones, árboles por tipo, fuentes, monumentos y personajes (una tabla `immersive_object_templates` con `category`, no tablas separadas). Y "validar la imagen" es detección real de color: el admin sube DOS imágenes por plaza (`reference_image_path` = plano, `legend_image_path` = leyenda aparte); el sistema agrupa píxeles por color en la leyenda (`PlazaLegendEntry`, un color por fila) y el admin mapea cada uno a un objeto del catálogo — con la opción de **crear la plantilla ahí mismo si no existe** (`createOptionForm` en el Select). Solo cuando todos los colores de la leyenda están confirmados, "Generar ubicaciones" repite la detección sobre el plano y crea un `StandSlot` (categoría `stand`) o `ImmersivePlazaProp` (el resto) por cada mancha encontrada, en `source = 'auto_detected'` para revisión. No hay OCR ni detección de la flecha de orientación dibujada — la orientación sale de la regla de la zona (`StandZone::default_orientation`), no de píxeles.  
  **Resultado visible:** el administrador define punto de aparición, área caminable, zonas excluidas y una capa coloreada e independiente de reservas de stands sobre una previsualización. Cada reserva muestra su propia flecha de orientación.  
  **Entidades:** `immersive_object_templates`, `stand_zones`, `stand_slots`, `immersive_plaza_props`, `plaza_legend_entries`.  
  **Reglas:** validar polígonos, tamaños, rutas y colisiones antes de guardar; las reservas no se interpretan como construcciones ni se integran a la geometría fija generada.  
  **Criterio de aceptación:** ningún slot válido sale del parque, bloquea una zona excluida o queda ocupado por un elemento generado; su frente coincide con la flecha configurada en el mapa.  
  **Estado:** Hecho para lo redefinido arriba. `App\Domain\Immersive\Support\SpatialGeometry` implementa las validaciones reales (punto en polígono, rectángulo en polígono, colisión con margen) — corren en `StandZone`/`StandSlot::booted()` en cada guardado, manual o automático. `App\Domain\Immersive\Support\ColorBlobDetector` (GD puro, sin dependencias nuevas) detecta colores distintos de la leyenda y manchas del plano; probado con imágenes sintéticas (`tests/Unit/Immersive/ColorBlobDetectorTest.php`) y con el flujo completo subir-plano-y-leyenda → detectar → confirmar → generar (`tests/Feature/Immersive/GeneratePlazaLayoutFromImageTest.php`). Recursos Filament completos para las 5 entidades. Limitación documentada en el propio `ColorBlobDetector`: dos categorías con colores casi idénticos en la leyenda se funden en una sola detección — es una limitación de usar solo color como señal, no de la implementación. La detección por vértices de `SpatialGeometry` (no por intersección real de segmentos) es otra simplificación de MVP documentada en el código.

- [x] **IMM-014 — Versionado y publicación segura**  
  **Resultado visible:** borrador, previsualización, publicación y reversión a versión anterior.  
  **Entidades:** `experience_versions`.  
  **Criterio de aceptación:** una edición no afecta usuarios hasta publicarse y puede revertirse.  
  **Estado:** Hecho. `ImmersiveExperience::publish()` (única vía a `status = 'publicada'`, ver IMM-011) y `revertToVersion()` viven en el modelo, reutilizados por la acción "Publicar versión" y por la pestaña "Versiones" del editor de experiencia (`VersionsRelationManager`), que además previsualiza el `config_snapshot` completo de cualquier versión anterior (incluidas las plazas hijas) antes de decidir revertir. Revertir crea una versión NUEVA en vez de reescribir el historial. 4 tests en `ImmersiveExperienceVersioningTest.php`, incluida la interacción real con el botón "Revertir a esta versión" vía Livewire.

### Fase 2 — Stands y cuenta del emprendedor

- [x] **IMM-020 — Crear tres plantillas de stand optimizadas**  
  **Resultado visible:** tres alternativas visualmente diferenciadas, coherentes con Merkamigo y de huella espacial normalizada.  
  **Entidades:** `immersive_object_templates` (`category = 'stand'`; el catálogo ya existe y es administrable desde Fase 1, IMM-013 — esta tarea es cargarle el `model_path`/`lod_config` real a las plantillas de stand).  
  **Reglas:** cada plantilla declara dimensiones, eje frontal, puntos de marca y LOD móvil/escritorio.  
  **Criterio de aceptación:** las tres caben en los slots compatibles, no cambian el área ocupada inesperadamente y cargan con instancias o recursos compartidos.  
  **Estado:** Hecho, con una decisión de arquitectura deliberada: en vez de `model_path` (GLB), las tres plantillas se resuelven por `builder_key` — el mismo mecanismo procedural (`addVoxelBox` sobre primitivas, sin geometría descargada) que ya usa el resto de la plaza compartida. Esto evita el problema de peso de assets que la auditoría de Fase 0 (IMM-003) documentó para los GLB sin comprimir de Zipaquirá, y no requiere LOD aparte porque las primitivas ya son baratas de renderizar a cualquier distancia. `ImmersiveObjectTemplate::isRenderable()` acepta `model_path` O `builder_key`, así que el mecanismo GLB sigue disponible para cuando IMM-020b (generador asistido por IA) lo necesite.
  Dos builders nuevos en `public/js/lib/voxel-plaza-engine.js` (`buildStandBooth`, `buildStandTable`), registrados en `standardBuilders` como `standBooth`/`standTable`; la tercera plantilla reutiliza el `marketStall` ya existente en el motor compartido. `database/seeders/ImmersiveObjectTemplateSeeder.php` carga las tres (`Caseta de madera` 4.2×3.8, `Mesa exhibidora` 3.2×2.4, `Toldo de mercado` 3.0×3.0), registrado en `DatabaseSeeder`. Migración `add_builder_key_to_immersive_object_templates_table`; campo expuesto en el formulario y la tabla de Filament (`Select` curado de claves de `standardBuilders`, columna "Forma voxel"). Verificado visualmente vía un HTML+Playwright temporal (borrado tras confirmar) — las tres huellas caben dentro de sus dimensiones declaradas sin solaparse.

- [x] **IMM-020b — Generador de objetos 3D asistido por IA**  
  **Origen (2026-08-05):** propuesto por el usuario al ver que crear cada plantilla del catálogo (construcciones, stands, árboles, fuentes, monumentos) a mano en Three.js es lento. Encaja con cómo ya funciona el motor: `voxel-plaza-engine.js` describe cada objeto como un árbol de primitivas JSON (`addVoxelBox` con posición/tamaño/textura/rotación — ver `standardBuilders`), no como GLB. La propuesta es que la IA genere ese JSON en vez de que un admin lo escriba a mano.  
  **Resultado visible:** al crear/editar un `immersive_object_template`, el admin sube 3 imágenes de referencia (frontal, lateral, superior), escribe instrucciones, y una IA con visión genera un JSON de primitivas voxel. Un panel Three.js embebido junto al formulario renderiza ese JSON en vivo, en modo estático (sin personaje ni movimiento). El admin puede pedir refinamientos ("hazlo más alto", "el techo va en rojo") que la IA aplica sobre el JSON anterior, hasta guardar la versión final.  
  **Entidades:** `immersive_object_templates.model_definition` (json, nuevo campo — coexiste con `builder_key`/`model_path`; `isRenderable()` acepta cualquiera de los tres).  
  **Reglas:** el JSON generado se valida contra el vocabulario de primitivas que el motor realmente soporta antes de aceptarlo — nunca se ejecuta ni se guarda JSON sin pasar por esa validación.  
  **Estado:** Hecho, con una decisión de proveedor distinta a la propuesta original: el TODO decía "la API de Claude", pero el proyecto ya tenía una integración de IA funcionando con **OpenAI** (`OpenAiSetting`, patrón contrato+implementación+binding de `GeneratesAssistedText`/`OpenAiTextGenerator`) — se reutilizó ese mismo patrón en vez de introducir un segundo proveedor desde cero, ya que la Responses API de OpenAI ya soporta visión (`input_image`) y salida JSON estructurada (`text.format` tipo `json_schema`) nativamente.
  **Esquema `model_definition`:** `{"version": 1, "boxes": [{x,y,z,w,h,d,texture,rotationY,collidable}, ...]}`, coordenadas locales al grupo igual que los builders manuales. `app/Domain/Immersive/Support/VoxelDefinitionBounds.php` calcula el bounding box (rectángulo rotado en XZ + extensión vertical desde el suelo) para completar `max_width/max_depth/max_height` al guardar. `app/Domain/Immersive/Support/VoxelDefinitionValidator.php` (PHP puro, sin librería de JSON Schema — no hay ninguna instalada en el proyecto) valida estructura, tipos, un allowlist de texturas (`VoxelDefinitionValidator::ALLOWED_TEXTURES`, que hay que mantener sincronizado a mano con `createVoxelTextures()` del JS), un máximo de cajas (`config('immersive.voxel_definition.max_boxes')`, default 40) y que el bounding box no exceda los máximos de la plantilla — acumula todos los errores en una sola `VoxelDefinitionValidationException` en vez de fallar uno a la vez.
  **Generador:** `app/Domain/Immersive/Contracts/GeneratesVoxelObjectDefinition.php` + `app/Domain/Immersive/Support/OpenAiVoxelObjectGenerator.php` (binding en `AppServiceProvider`). A diferencia de `OpenAiTextGenerator` (fallback silencioso a texto determinístico), este generador **nunca falla en silencio** — no existe un "objeto sin IA" razonable, así que cualquier fallo se propaga como `VoxelGenerationException` y la UI lo muestra como error sin perder la última definición válida en pantalla. Las 3 imágenes se codifican en base64 inline (no URL pública, para no depender de que el disco `public` sea accesible desde internet) y se comprimen/redimensionan con `intervention/image` antes de enviarlas (ancho máximo 1024px, JPEG progresivo) para acotar costo/latencia — también se extiende `set_time_limit()` y se topa el timeout HTTP a 55s para absorber generaciones lentas sin que PHP corte la petición a medio camino.
  **UI:** el flujo interactivo completo (subir imágenes, generar/refinar, bitácora, guardar) vive en un componente Livewire real, `app/Livewire/ImmersiveObjectTemplateAiGenerator.php` + `resources/views/livewire/immersive-object-template-ai-generator.blade.php` — no en el Schema builder de Filament (no tiene precedente de layout side-by-side en este proyecto, y este panel no es un formulario CRUD estándar). Se embebe en dos puntos: una página de recurso dedicada (`ImmersiveObjectTemplateResource` → acción "Generar con IA" en la tabla → `Pages/GenerateWithAi.php`, que solo resuelve/autoriza el registro y delega el resto) y un modal rápido desde `EditImmersiveObjectTemplate` (mismo componente, reutilizado). El panel de previsualización usa un nuevo `createStandaloneVoxelTarget()` en `voxel-plaza-engine.js` (helper mínimo con solo `.world`/`.addVoxelBox`, sin cámara de personaje ni loop de física — el motor completo de plaza caminable no se toca, ver `docs/architecture/personaje-inmersivo.md`) más un nuevo `buildFromDefinition(engine, {x,z,rotation,definition})` exportado junto a `standardBuilders`. El script de previsualización se dispara con `x-init`+`import()` dinámico (no un `<script type="module">` con imports estáticos) precisamente porque un script de importación estática no se ejecuta cuando el HTML se inyecta dinámicamente (como en el modal de Filament) — se verificó el bug real (canvas vacío en el modal) y se corrigió antes de cerrar la tarea.
  **Puente mínimo de stands dinámicos (ampliación de alcance acordada con el usuario):** al diseñar esto se encontró que ningún stand asignado automáticamente (IMM-022) se veía todavía en una plaza real — `zipa-plaza-immersive.js`/`cajica-plaza-immersive.js` siguen con su `layout` de geometría fija 100% hardcodeado (decisión de arquitectura #1: la plaza es una escena fija por municipio), pero nada leía `StandSlot`/`StandAssignment` para dibujar la capa dinámica de stands que sí manda la decisión #2. Se cerró ese hueco sin tocar la geometría fija: `app/Http/Controllers/Api/V1/ImmersivePlazaStandsController.php` (`GET /api/v1/inmersivo/plazas/{plaza}/stands`, público) devuelve los stands `isLive()` de una plaza con su `world_position`/`rotation` y el descriptor de renderizado (`builder_key` o `model_definition`) de su plantilla asignada. `public/js/lib/dynamic-stand-loader.js` (`loadDynamicStands(engine, plazaId)`) hace `fetch` a ese endpoint y llama a `standardBuilders[builder_key]` o `buildFromDefinition` según corresponda, en silencio si algo falla (es una capa adicional sobre una escena que ya funciona sin ella). Enganchado tras `engine.start(...)` en ambos scripts; Cajicá pasó de `Route::view` a un controlador (`PlazaController::cajicaInmersiva()`, análogo a `zipaInmersiva()`) para poder resolver y pasar el `plazaId` real.
  **Verificado, no solo revisado:** 27 tests nuevos (`VoxelDefinitionBoundsTest`, `VoxelDefinitionValidatorTest`, `OpenAiVoxelObjectGeneratorTest` con `Http::fake`, `ImmersivePlazaStandsEndpointTest`, `ObjectTemplateAiGeneratorPageTest`) más los 37 ya existentes de Fase 1/2, los 65 pasando sin regresión; `pint`/`phpstan` limpios en todo el árbol tocado. En navegador real: el panel "Generar con IA" (página completa y modal) renderiza el canvas y dibuja una `model_definition` de prueba correctamente en ambos contextos, cero errores de consola. Para el puente de stands: se creó un stand real (zona/slot/asignación/plantilla con `model_definition` de 2 cajas) en la plaza real de Zipaquirá, se confirmó por API que el endpoint lo devuelve, y se comparó `window.__immersivePerf` de `/labs/zipa-inmersiva` con la asignación `publicado` (196 geometrías, 52 draw calls) contra `pausado` (194 geometrías, 50 draw calls) — diferencia exacta de 2, una por caja del `model_definition` de prueba, cero errores de consola en ambos casos. Todos los datos de verificación (plantilla, negocio, usuario, zona/slot, admin temporal) se limpiaron de la base de datos de desarrollo al terminar.

- [x] **IMM-021 — Selector de stand en la cuenta del emprendedor**  
  **Resultado visible:** sección “Mi stand en la plaza” con tres tarjetas, previsualización 3D/imagen y estado de publicación.  
  **Pantallas/endpoints:** configuración de vitrina y API de preferencia.  
  **Entidades:** `stand_assignments`, `immersive_object_templates`, vitrina.  
  **Reglas:** solo el propietario puede cambiar su plantilla; no puede modificar posición ni dimensiones.  
  **Criterio de aceptación:** el cambio se refleja en la experiencia publicada sin duplicar el stand.  
  **Estado:** Hecho. Página Livewire `resources/views/pages/emprendedores/negocios/⚡mi-stand.blade.php` en `emprendedores/negocios/{business}/mi-stand` (dentro del grupo `business.team`, con `setPermissionsTeamId` re-fijado en `boot()` igual que `⚡vitrina.blade.php`, porque las peticiones AJAX de Livewire no pasan por el middleware de ruta). Muestra la tarjeta de estado (7 variantes: no publicado, sin_configurar, pendiente, sin_cupo, pausado, reubicación_requerida, publicado con nombre de plaza y código de slot) seguida de las 3 plantillas publicadas con miniatura, dimensiones y botón "Elegir"/badge "Seleccionado". `chooseTemplate()` solo cambia `object_template_id` sobre la `StandAssignment` existente y reinvoca `AssignBusinessToStand` (IMM-022) — nunca crea una segunda asignación ni toca posición/tamaño. Enlace añadido en `emprendedores/home.blade.php` ("Mi stand en la plaza", visible solo si la vitrina está publicada). 4 tests en `BusinessStandSelectorTest.php` (ver tarjetas correctas, cambiar de plantilla conserva el mismo slot, mensaje de pendiente sin experiencia publicada, un extraño no puede administrar el stand ajeno — `assertForbidden`). Verificado además en navegador real vía Playwright: login, ver la página, clic en "Elegir", toast de éxito, selección actualizada, cero errores de consola.

- [x] **IMM-022 — Asignador automático de slots**  
  **Resultado visible:** toda vitrina elegible recibe plaza y ubicación automáticamente.  
  **Entidades:** `stand_assignments`, `stand_slots`, vitrinas.  
  **Dependencias:** IMM-012, IMM-013, IMM-020.  
  **Criterio de aceptación:** pruebas masivas no producen slots duplicados, colisiones ni asignaciones cruzadas entre municipios.  
  **Estado:** Hecho. Tabla `stand_assignments` (migración `create_stand_assignments_table`) con `business_id` único, `stand_slot_id` único nullable, `previous_slot_id` (para recuperar el mismo espacio al reactivar), `object_template_id`, `status` (enum: sin_configurar/pendiente/publicado/pausado/sin_cupo/reubicacion_requerida), `motivo_reubicacion`. Modelo `StandAssignment` con relaciones a `business`/`plaza`/`slot`/`previousSlot`/`template`.
  `AssignBusinessToStand` (`app/Domain/Immersive/Actions/`) implementa exactamente el orden del §5 del TODO: resuelve municipio → experiencia publicada del municipio → plazas activas en orden → dentro de cada plaza, la zona menos ocupada primero → slot compatible por tamaño (`max_width`/`max_depth`/`max_height`) y categoría. Intenta primero `previous_slot_id` si sigue siendo compatible, antes de buscar uno nuevo. `ReleaseBusinessStand` libera el `StandSlot` (vuelve a `disponible`) y recuerda el slot liberado en `previous_slot_id`. 6 tests en `BusinessStandAssignmentTest.php`: asignación automática al publicar, `pendiente` sin experiencia publicada, `sin_cupo` sin slot compatible, pausar/recuperar el mismo slot, liberar al eliminar la vitrina, y dos negocios reciben slots distintos (no colisión). Probado también manualmente contra la base de datos de desarrollo real.

- [x] **IMM-023 — Elegibilidad y estados del stand**  
  **Resultado visible:** estados Sin configurar, Pendiente, Publicado, Pausado, Sin cupo y Reubicación requerida.  
  **Reglas:** solo vitrinas activas, públicas y con información mínima aparecen; registrar motivos.  
  **Criterio de aceptación:** suspender o eliminar una vitrina la retira de la escena sin dejar recursos huérfanos.  
  **Estado:** Hecho, cubierto por el mismo mecanismo de IMM-022: `BusinessStandObserver` (registrado en `AppServiceProvider::configureImmersiveStandSync()`, acoplamiento en un solo sentido — `Business` no conoce el dominio Immersive) escucha `saved()` y `deleting()` de `Business`. En cada guardado reevalúa desde cero si la vitrina está publicada (asigna/reubica vía `AssignBusinessToStand`) o no (libera vía `ReleaseBusinessStand`) — es una sincronización idempotente y autocorrectiva, no un simple detector de transición, así que cualquier estado inconsistente se autocorrige en el siguiente guardado. Al eliminar una vitrina, `deleting()` libera su slot antes de que se borre el registro, sin dejar `stand_slots` huérfanos marcados como ocupados. Los 6 estados están cubiertos por los mismos tests de `BusinessStandAssignmentTest.php` y por la tarjeta de estado de IMM-021 en la UI del emprendedor.
  Regresión de cierre de fase: `vendor/bin/pint` y `vendor/bin/phpstan analyse` limpios sobre todos los archivos nuevos/modificados de Fase 2; 37 tests pasando en `tests/Feature/Immersive` + `tests/Unit/Immersive` (incluye los 8 tests preexistentes de Fase 0/1, sin regresiones). Fase 2 queda cerrada. IMM-020b, deferido en el primer pase de esta fase, se retomó y cerró en una sesión posterior (2026-08-05) — ver su propia entrada arriba, que además incluye el puente mínimo de stands dinámicos (65 tests en total sobre `tests/Feature/Immersive` + `tests/Unit/Immersive`).

### Fase 3 — Avatar, recorrido e interacción comercial

- [x] **IMM-030 — Selección de avatar genérico**  
  **Resultado visible:** elegir Hombre o Mujer antes de entrar y cambiarlo desde ajustes.  
  **Entidades:** ~~`avatars`, `immersive_profiles`~~ — decisión tomada al implementar: sin tablas nuevas. Se guarda en `localStorage` (clave `vpe-avatar`), mismo patrón que ya usa "Apariencia" en Ajustes y la sensibilidad táctil del motor. No había ningún requisito real de sincronizar entre dispositivos ni de administrar el catálogo desde el panel (2 avatares fijos, sin personalización física), así que las entidades relacionales quedaron fuera por sobre-ingeniería innecesaria.  
  **Reglas:** selección opcional; valor por defecto; sin personalización física en el MVP.  
  **Criterio de aceptación:** ambos avatares comparten escala, colisionador y animaciones básicas de espera, caminar y girar.  
  **Estado:** Hecho. `avatarPresets` (Hombre/Mujer) en `voxel-plaza-engine.js`, con 4 texturas dedicadas del jugador (`avatarSkin`/`avatarHair`/`avatarShirt`/`avatarPants`) separadas de las claves globales de paleta que reusan balcones/árboles/vehículos/NPCs — así elegir avatar no recolorea el resto de la escena. Selector `<x-immersive.avatar-picker>` en el overlay de entrada de Zipaquirá, Cajicá y la escena genérica, y en `/settings/avatar`. Cero cambios a física/cámara/animación del personaje.
  **Corrección (2026-08-10):** el primer pase solo conectó el selector en `zipa-plaza-immersive.js`/`cajica-plaza-immersive.js` — la escena genérica (`generic-plaza-immersive.js`, la que realmente usa hoy la experiencia publicada de Cajicá vía `route_name = labs.generic-plaza`) no recibía ningún `palette` al construir el motor, así que siempre mostraba el avatar por defecto sin importar la preferencia guardada. Corregido agregando el mismo `palette: { ...basePalette, ...avatarPresets[loadAvatarPreference()] }` y el picker ahí también.

- [x] **IMM-031 — Controles multiplataforma**  
  **Resultado visible:** teclado/ratón en escritorio y joystick/botones táctiles en móvil.  
  **Reglas:** impedir salir del área caminable o atravesar stands/edificios; incluir botón para regresar al centro.  
  **Criterio de aceptación:** recorrido completo en móvil y escritorio sin quedar atrapado.  
  **Estado:** Encontrado ya implementado en el motor compartido al iniciar IMM-030 — el checkbox estaba desactualizado. `bindMobileControls()` (`voxel-plaza-engine.js`) trae joystick, botón de salto, botón de sprint/acción y slider de sensibilidad táctil; `movementBounds`/`clampPositionToMovementBounds` ya impiden salir del área caminable (clamp cada frame) y la colisión existente impide atravesar edificios/stands. **Falta:** el botón explícito "volver al centro" no existe todavía — no es parte de esta entrega (IMM-030), queda anotado como pendiente puntual dentro de IMM-031 si se necesita.

- [x] **IMM-032 — Activación por proximidad al stand**  
  **Resultado visible:** indicador “Ver vitrina” al entrar en el radio de interacción; apertura por toque/clic, no automáticamente de forma intrusiva.  
  **Reglas:** un único stand activo; umbral y tiempo de espera configurables; priorizar el stand hacia el que mira el personaje.  
  **Criterio de aceptación:** stands cercanos no abren el modal equivocado ni generan aperturas repetidas.  
  **Estado:** Hecho. `ImmersivePlazaStandsController` ahora expone `business` (nombre, logo, URL de vitrina) por stand. Nuevo `public/js/lib/stand-proximity.js` (`attachStandProximity()`) — construido sobre el punto de extensión público `engine.onUpdate()`, sin tocar `updatePlayer`/`updateCamera`/`bindInput`: cada frame calcula el stand más cercano dentro de un radio (6m por defecto) priorizando el que el personaje mira de frente (producto punto), con una espera de 220ms antes de cambiar de stand activo para evitar parpadeo. El clic en el indicador navega directo a la vitrina real (`vitrinas.show`) — IMM-033 podrá redirigir ese mismo clic a abrir un modal en vez de navegar. Cableado en las 3 escenas activas (Zipaquirá, Cajicá, genérica) vía `loadDynamicStands()`, que ahora devuelve el registro de stands en vez de descartarlo. Radio/tiempo de espera quedan como constantes en código, no en base de datos (mismo criterio que IMM-030).

- [x] **IMM-033 — Modal HTML de vitrina**  
  **Resultado visible:** nombre, logo, descripción breve, productos/servicios, precios, galería, horario, reseñas resumidas y acciones disponibles como WhatsApp, compartir o ver vitrina completa.  
  **Pantallas/endpoints:** overlay HTML responsive reutilizando los endpoints/componentes públicos actuales.  
  **Entidades:** vitrinas, productos/servicios, horarios, reseñas.  
  **Reglas:** no duplicar el catálogo dentro del GLB; estados de carga, vacío y error; cerrar con botón, Escape o gesto accesible.  
  **Criterio de aceptación:** los cambios de la vitrina web se ven en el modal sin editar la escena 3D.  
  **Estado:** Hecho. Nuevo `public/js/lib/stand-vitrina-modal.js` — al hacer clic en el indicador de IMM-032 (`stand-proximity.js`, opción `onOpen` agregada) pide los datos frescos a los endpoints públicos ya existentes (`GET /api/v1/plaza/negocios/{slug}` y `/productos`, `DiscoveryController`), nunca datos precargados en la plaza — así los cambios en la vitrina web se ven sin tocar la escena. WhatsApp/compartir/ver-completa reutilizan las rutas reales de `VitrinaController` (`vitrinas.whatsapp`/`vitrinas.compartir`/`vitrinas.show`) tal cual, cero lógica duplicada. Único campo nuevo agregado al backend: `recommendations_summary` en `PublicBusinessResource` (conteo + hasta 3 extractos de `Recommendation` — no hay calificación numérica en este codebase). Estados de carga/error/vacío, cierre por botón/Escape/clic en fondo, y el input de movimiento se bloquea mientras el modal está abierto (listener aditivo en fase de captura, sin tocar `bindInput`/`voxel-plaza-engine.js`). Verificado con el negocio real de Cajicá (Daviu Decco) vía Playwright: el clic no navega, el modal muestra datos reales, Escape cierra, el personaje no se mueve con el modal abierto y vuelve a moverse al cerrarlo.

- [x] **IMM-034 — Búsqueda, categorías y navegación entre plazas**  
  **Resultado visible:** buscador, chips de categorías, contador de resultados, selector de plaza y opción “Mostrar todos”.  
  **Reglas:** filtrar visibilidad sin reasignar posiciones; restaurar estado al cerrar un modal; URL compartible.  
  **Criterio de aceptación:** buscar una vitrina informa en qué plaza está y permite viajar a ella.  
  **Estado:** Hecho — con esto, Fase 3 completa queda cerrada. Nuevo `public/js/lib/stand-search-panel.js`: filtro local instantáneo (oculta/muestra los stands ya cargados en la plaza actual vía `root.visible`, nunca toca `position`/`rotation`) + resultados globales reutilizando `GET /api/v1/plaza` (mismo motor que `plaza/buscar.blade.php`, sin endpoint nuevo). Cada resultado sabe en qué plaza está (`immersive_location`, nuevo en `PublicBusinessResource`, vía `Business::standAssignment→plaza→experience→municipality`) y ofrece "Ver aquí" (abre el modal de IMM-033 sin caminar) o "Viajar a..." (navega a la otra escena, vía `Municipality::immersiveLabUrl()`, ya existente). Selector de plaza y chips de categoría alimentados por `/api/v1/municipios` (+`immersive_lab_url`, nuevo) y `/api/v1/categorias`. Filtro reflejado en la URL (`?q=&categoria=`) con `history.replaceState` y restaurado al cargar. Cableado en las 3 escenas.  
  **Hallazgo importante, fuera de este alcance:** durante la verificación se encontró un bug **preexistente y real** (no introducido por esta tarea, reproducido incluso sin ningún cambio de código): `Cache::remember()` sobre una `Illuminate\Database\Eloquent\Collection` con `CACHE_STORE=redis` falla con 500 ("incomplete object... unserialize()") en la SEGUNDA lectura de caché en adelante — afecta `GET /api/v1/municipios` y `/api/v1/categorias` ya en producción, antes de esta tarea. No se solucionó aquí (cambiar el cacheo de esos endpoints ya-shipeados está fuera del alcance de IMM-034 y hay un test existente que valida ese cacheo); reportado al usuario para decidir cómo priorizarlo.  
  **Corregido (2026-08-12):** el bug de arriba pasó de "hallazgo fuera de alcance" a bloqueo real — el usuario reportó, con la consola de producción (`merkamigo.com`) abierta, que la experiencia inmersiva se quedaba pegada en "Cargando la plaza..." porque `stand-search-panel.js` (`loadCategories()`/`loadPlazas()`, ver IMM-034 arriba) recibía 500 de ambos endpoints. Causa raíz confirmada: PHP no puede deserializar de forma confiable un grafo de objetos Eloquent (`Collection` + modelos + relaciones cargadas) guardado en caché — un problema conocido de `unserialize()` con autoload de clases, no exclusivo de Redis pero que se manifiesta ahí en este caso. Arreglado en `DiscoveryController::municipios()`/`categorias()`: ahora se cachea el array YA RESUELTO por el `JsonResource` (`->resolve()`) en vez de la `Collection` de modelos cruda — cero objetos PHP en el payload cacheado, cero riesgo de este bug, sin cambiar el JSON de respuesta (mismo contrato, mismo test de invalidación de caché pasando). Verificado en tinker que el valor cacheado ahora es un array plano, que dos lecturas consecutivas (miss y hit) devuelven contenido idéntico, y con Playwright que la plaza carga completa sin errores ni peticiones fallidas.

### Post-Fase 3 — Personalización visual del stand (pedido 2026-08-10, fuera del alcance de IMM-030/032/033/034)

- [x] **Insignia con el logo del negocio sobre el stand**  
  **Resultado visible:** un distintivo circular con el logo del negocio, flotando sobre cada stand ocupado, visible dentro de la escena 3D (no un overlay 2D) para reconocerlo desde lejos.  
  **Estado:** Hecho. `attachLogoBadge()` en `dynamic-stand-loader.js` — un `THREE.Sprite` (siempre mirando a la cámara) con el logo dibujado en canvas sobre un fondo circular blanco (legible con logos transparentes u oscuros). Funciona igual para stands GLB, `model_definition` o builder — se agrega a `engine.world` directamente, NO como hijo del objeto del stand, con altura acotada (máx. ~3.6 unidades sobre la base, luego capada por el límite de `maxPitch` de la cámara del personaje — canónica, no se toca). **Bug real encontrado y corregido durante la verificación:** la primera versión sí la agregaba como hija del stand, y como los stands suelen traer una escala aplicada para ajustarse al slot (ej. 3x), esa escala se propagaba a la posición local de la insignia y la disparaba muy por encima de lo esperado (verificado: terminaba en Y≈13.5 en vez de ~4.5) — quedaba fuera de la vista sin que hubiera ningún error de consola. Corregido calculando la posición en espacio de mundo aparte. Verificado con el stand real de Cajicá (Daviu Decco) vía Playwright: la insignia se ve clara junto al letrero del stand.

- [x] **Color personalizable del stand, elegido por el emprendedor**  
  **Resultado visible:** el emprendedor puede elegir un color para su propio stand desde su panel (no un ajuste del admin ni del catálogo de plantillas), y ese color se aplica al render 3D real — tanto si el stand es un `.glb` subido como si es un `model_definition` (JSON de cajas generado/guardado en BD).  
  **Aclarado por el usuario:** el color es una elección del negocio, no de la plantilla; y las vitrinas también pueden ser objetos 3D guardados como JSON (`model_definition`), no solo `.glb` — el mecanismo de color debe cubrir ambos casos, no solo GLB.  
  **Estado:** Hecho. Campo `stand_color` (`#rrggbb`, nullable) en `storefronts` — mismo lugar que `headline`/`description`/`cover_path`, guardado a través de `UpdateStorefront` (reutilizada, sin acción nueva) desde el editor de vitrina real (`⚡vitrina.blade.php`, `<input type="color">`, autoguardado con el mismo patrón que el resto del formulario). Expuesto en el endpoint de stands (`ImmersivePlazaStandsController`). En 3D: `applyPrimaryColor()` (`dynamic-stand-loader.js`) — **heurística automática** (decisión del usuario): recolorea la malla de mayor volumen en espacio de mundo, sin depender de nombres de malla; funciona igual para GLB, `model_definition` y builder porque los tres terminan siendo un árbol de `Mesh`. Verificado directamente sobre el GLB real de Cajicá (Daviu Decco) que sus materiales son color sólido sin textura (recolor limpio) y que no se comparten entre negocios que usan la misma plantilla (confirmado con Three.js/GLTFLoader). Tests: `StorefrontEditorTest` (guardar/rechazar formato inválido) y `ImmersivePlazaStandsEndpointTest` (expuesto/null en el endpoint).

- [ ] **Espacio de video/pantalla proyectada dentro del stand 3D**  
  **Resultado visible:** poder agregar un video (o un "espacio" tipo pantalla) que se reproduce dentro de la escena 3D, en el propio stand — mismo criterio que el logo: debe vivir dentro de la experiencia inmersiva 3D, no ser un overlay 2D aparte.  
  **Estado:** Pendiente — anotado a pedido del usuario ("para que lo anotes"), todavía sin diseñar. Probable mecanismo: `THREE.VideoTexture` sobre un plano posicionado en el stand (mismo patrón de posicionamiento en espacio de mundo ya usado para el logo, para evitar el mismo bug de herencia de escala). Pendiente decidir: origen del video (¿el emprendedor sube un archivo? ¿URL de YouTube/Vimeo no serviría directamente con `VideoTexture`, que necesita un `<video>` HTML real?), autoplay/mute (los navegadores bloquean autoplay con audio sin interacción del usuario), y costo de rendimiento de tener varios videos reproduciéndose a la vez en una plaza con muchos stands.

### Fase 4 — Rendimiento, accesibilidad y analítica

- [x] **IMM-040 — Calidad adaptativa y modo ligero**  
  **Resultado visible:** selección automática Móvil ligero / Equilibrado / Alto y control manual.  
  **Reglas:** limitar pixel ratio, sombras, distancia, vegetación y LOD según capacidad.  
  **Criterio de aceptación:** la experiencia entra automáticamente en modo ligero en dispositivos de baja capacidad y mantiene navegación funcional.  
  **Estado:** Hecho (2026-08-10/12). Nuevo `public/js/lib/immersive-quality.js`: `resolveQualityTier()` resuelve entre override manual (localStorage, mismo patrón que `avatar-preference.js`) → `mobile_quality_profile`/`desktop_quality_profile` de la plaza (columnas ya existentes en BD desde Fase 1, hasta ahora inertes) usando `detectDeviceProfile()` (reutilizado de `immersive-perf-monitor.js`, no duplicado) → `ligero`/`alto` fijo como último respaldo. Tres presets (`pixelRatioCap`, `shadows`, `shadowMapSize`, `fogFar`) alimentan los mismos hooks que `VoxelPlazaEngine` ya exponía como parámetros de constructor. Selector manual "Automático / Ligero / Equilibrado / Alto" en el panel técnico (`immersive-perf-monitor.js`, `?perf=1`/tecla P), persistido en `vpe-quality-override`. Degradación automática real: si `evaluateBudget()` marca "critical" varias muestras seguidas sin override manual, se guarda un nivel más bajo y se recarga la página con la calidad ya aplicada desde el arranque (`watchForAutomaticDowngrade()`).  
  **Corrección (2026-08-12, pedido explícito del usuario):** la primera versión incluía además un filtro de densidad de vegetación (omitir una fracción de árboles/palmeras en modo `ligero`, acumulador tipo Bresenham en `VoxelPlazaEngine.place()` y en `dynamic-stand-loader.js`). El usuario pidió quitarlo por completo — "no obligar a usar 3D" y el resto de la calidad adaptativa no dependen de ocultar vegetación, y el comportamiento automático resultaba sorpresivo. Eliminado el filtro de ambos lugares y el campo `vegetationDensity` de los presets — la vegetación siempre se renderiza completa, el resto de la calidad adaptativa (pixel ratio, sombras, niebla, degradación automática) queda intacto.

- [x] **IMM-041 — Instancing, LOD y carga progresiva**  
  **Resultado visible:** suelo y estructura principal aparecen primero; decoración y stands se cargan después sin bloquear.  
  **Reglas:** `InstancedMesh` para stands/objetos repetidos, materiales compartidos, recursos comprimidos y liberación de memoria al cambiar de plaza.  
  **Criterio de aceptación:** cambiar de plaza no acumula escenas, texturas ni listeners en memoria.  
  **Estado:** Hecho (2026-08-10), con `InstancedMesh` deliberadamente fuera de esta entrega — decisión documentada, no olvido: el editor espacial del admin necesita arrastrar/seleccionar/eliminar cada prop individualmente (`draggables`, `TransformControls`), algo incompatible con instancias agrupadas por geometría/material sin una reescritura mayor del editor; el resto de la tarea sí se hizo:
  - **Caché de materiales:** nuevo `cachedMaterial(cache, texture, extra)` en `voxel-plaza-engine.js`, cacheado por `(textura, transparent, opacity, emissive)` vía `WeakMap` por instancia de motor — de miles a decenas de materiales por plaza sin cambio visual.
  - **Caché de plantillas GLB:** `glbTemplateCache` (`Map` por `modelUrl`) + `.clone(true)` — dos stands con la misma plantilla GLB ya no disparan cada uno su propio fetch+parseo completo; generaliza al motor compartido el patrón ad-hoc que antes solo existía hardcodeado en el lab de Zipaquirá (ya eliminado, ver sección "Post-Fase 4" abajo).
  - **Draco/Meshopt:** `DRACOLoader`/`MeshoptDecoder` registrados sobre el `GLTFLoader` compartido — sin efecto en los datos actuales (ningún template real usa `model_url` todavía) pero listo para cuando se use.
  - **Carga progresiva real:** `dynamic-stand-loader.js` reemplazó el `for...of { await }` secuencial por lotes de concurrencia limitada (6 a la vez) con lo estructural (stands, cualquier objeto con GLB real) priorizado antes que la decoración de una sola caja; la plaza genérica además espera a que terminen los stands antes de pedir los props decorativos.
  - **Liberación de memoria:** nuevo `dispose()` en `VoxelPlazaEngine` (libera geometrías/materiales/texturas, cancela el loop de animación, quita listeners) — higiene para si se introduce navegación tipo SPA entre plazas; hoy el cambio de plaza sigue siendo recarga completa de página, así que no había una fuga activa que resolver.
  **Regresiones evitadas antes de shipear (encontradas en revisión propia, no reportadas por el usuario):** `applyStandPrimaryColor()` y `applyTiling()` mutaban en el sitio el material/textura de un objeto — seguro cuando cada objeto tenía su propio material sin compartir, pero con las dos cachés nuevas de arriba (voxel y GLB) un material puede ahora ser compartido entre varias instancias, así que recolorear o retilear uno afectaba a todos los que lo comparten. `applyStandPrimaryColor()` se corrigió clonando el material completo antes de mutar su color — verificado con Playwright que dos cajas que comparten material dejan de compartirlo tras recolorear una sola. `applyTiling()` en cambio solo clonaba la **textura**, no el **material** — arreglo incompleto que dejó pasar el mismo bug de fondo (ver corrección real más abajo, 2026-08-12, reportada por el usuario).

- [x] **IMM-042 — Alternativa accesible 2D**  
  **Resultado visible:** botón “Ver negocios en lista” con las mismas categorías y vitrinas.  
  **Reglas:** no obligar a usar 3D; navegación por teclado, etiquetas accesibles y reducción de movimiento.  
  **Criterio de aceptación:** todas las vitrinas y acciones comerciales pueden consultarse sin recorrer la escena.  
  **Estado:** Hecho (2026-08-10). CTA "Ver negocios en lista" agregado a `search-hero.blade.php` junto al de "Ver experiencia inmersiva", visible independientemente de si la 3D está habilitada — navega directo a `buscar.blade.php`/`DiscoveryController::plaza()`, que ya cubrían categorías, tarjetas de negocio y paginación sin cambios de backend. Endurecida la accesibilidad del panel de búsqueda dentro de la escena 3D (`stand-search-panel.js`, que antes no tenía `role="dialog"`, `aria-modal`, cierre con Escape ni gestión de foco): se le agregó el mismo mecanismo que ya existía en `stand-vitrina-modal.js`, más `aria-label`/`aria-pressed` en chips de categoría y selector de plaza. Nuevo modo de movimiento reducido (`public/js/lib/reduced-motion-preference.js`, localStorage `vpe-reduced-motion`, default = `prefers-reduced-motion`) expuesto como toggle en `/settings/avatar` — apaga únicamente el balanceo idle decorativo de los NPCs (`VoxelPlazaEngine.updateActors()`), nunca la animación del propio personaje.

- [x] **IMM-043 — Analítica de la experiencia**  
  **Resultado visible:** tablero con entradas por plaza, búsquedas, categorías, vitrinas abiertas, productos vistos, clics a WhatsApp y rendimiento por dispositivo.  
  **Entidades:** `immersive_events`.  
  **Reglas:** evitar datos personales innecesarios; definir retención y anonimización.  
  **Criterio de aceptación:** distinguir visita a plaza, acercamiento, apertura de vitrina y conversión.  
  **Estado:** Hecho (2026-08-10), replicando el patrón ya probado de `analytics_events` (`RegisterAnalyticsEvent`) en vez de inventar un mecanismo nuevo. Migración/modelo `ImmersiveEvent` (`app/Domain/Analytics/`) con tipos `plaza_entry`, `search_performed`, `category_filtered`, `vitrina_opened`, `product_viewed`, `whatsapp_click`, `performance_sample`; `visitor_hash` sin IP/UA en crudo, dedup por ventana de 30 min, misma lista de bots que el dominio hermano. Endpoint `POST /api/v1/inmersivo/plazas/{plaza}/eventos` con throttle. Instrumentación vía un `track(type, payload)` opcional inyectado a `attachStandProximity`, `createVitrinaModal`, `attachSearchPanel` y cada script de escena — entrada a la plaza, apertura de vitrina, clic a WhatsApp, búsqueda/categoría y producto visto (`IntersectionObserver` por tarjeta), más una muestra periódica de rendimiento etiquetada con `detectDeviceProfile()`. Retención: `PruneImmersiveEvents` + comando `immersive-events:prune`, programado semanal, default 6 meses. Widgets Filament nuevos (`ImmersiveEventsOverview`, `ImmersivePlazaActivityChart`) con el mismo estilo visual del resto del panel.

### Post-Fase 4 — Eliminación de los labs hardcodeados y cierre del bug de tiling (2026-08-12)

- [x] **Eliminar por completo los labs hardcodeados de Zipaquirá y Cajicá**  
  **Origen:** pedido explícito del usuario, ya anunciado desde IMM-011 ("eliminar por completo todo lo que esté hardcodeado y dejar la plaza genérica por defecto"), ejecutado ahora que Fase 4 (calidad adaptativa, analítica) ya estaba cableada en la escena genérica.  
  **Resultado visible:** `/labs/zipa-inmersiva` y `/labs/cajica-inmersiva` ya no existen (404); toda plaza real se sirve por `/labs/plaza/{municipio}` (`labs.generic-plaza`), data-driven desde `ImmersivePlaza`.  
  **Estado:** Hecho. Borrados: las 2 vistas blade y los 2 scripts JS de escena (`cajica-plaza-immersive.js`, `zipa-plaza-immersive.js`), los métodos `PlazaController::zipaInmersiva()`/`cajicaInmersiva()`, las 2 rutas en `web.php`, las 2 entradas en `SitemapController`, las 2 opciones del selector `route_name` en `config('immersive.available_scenes')` (confirmado en BD que ninguna de las 3 experiencias reales dependía de ellas — las 3 ya usaban `labs.generic-plaza`), y `database/seeders/ImmersiveExperienceSeeder.php` (existía solo para publicar esas dos rutas, ya obsoleto). Actualizados 12 archivos de test que referenciaban las rutas/`route_name` eliminados — la mayoría solo usaban el valor como relleno de fixture (cambiado a `labs.generic-plaza`); `ImmersivePlazaPreviewAccessTest` se reescribió porque `genericPlaza()` responde 404 cuando no hay plaza resoluble, a diferencia de los controladores viejos que renderizaban la vista igual con `immersivePlazaId = null`. Verificado con Playwright: rutas viejas → 404, `/labs/plaza/cajica` → 200 sin errores de consola. `pint` limpio; de los tests tocados, 109 pasan y 1 falla — **preexistente y no relacionada**, ya resuelta (ver entrada siguiente).

- [x] **Investigar "árboles, personaje y piso no cargan en la experiencia inmersiva de Cajicá"**  
  **Origen:** reporte del usuario. Dos síntomas distintos con causas independientes:  
  1. *Personaje ("Don Luis") y un vehículo no visibles:* estaban en `status = 'borrador'` en `immersive_plaza_props` — un prop en borrador nunca es visible en la experiencia pública (`ImmersivePlazaPropsController` filtra `status = 'confirmado'` salvo `?preview=1` con admin), pero sí es visible en el editor espacial del admin (que muestra todo). El usuario los confirmó a `'confirmado'` en paralelo mientras se investigaba — no fue un bug de código, era contenido sin publicar.  
  2. *Piso/objetos con tiling que se ven bien en el editor pero no en la experiencia real:* dos causas combinadas, ambas ya resueltas. (a) `cajica-plaza-immersive.js`/`zipa-plaza-immersive.js` (los labs hardcodeados de arriba) **nunca llamaban a `loadDynamicProps()`**, solo a `loadDynamicStands()` — si el visitante entraba por esa URL vieja, ningún árbol, piso ni decoración de base de datos se cargaba jamás, sin importar el tiling; se resuelve de raíz al eliminar esos labs (ver arriba), porque la escena genérica sí llama a ambas funciones. (b) Antes de esta sesión, `ImmersivePlazaPropsController::index()` no incluía el campo `tiling` en su respuesta JSON en absoluto — el editor espacial sí aplicaba el tiling guardado (lee directo de BD), pero la experiencia inmersiva real nunca lo recibía por API y siempre mostraba la textura sin repetir (ya corregido en un commit previo a esta sesión, `ImmersivePlazaPropsController.php:39`).  
  **Verificado:** los 208 props confirmados de la plaza real de Cajicá (id 2), incluidos los 47 con tiling configurado (`Casa colonial`, `Anden`, `Anden2`, `Calle pavimento`), aparecen todos en `engine.world` con la posición correcta tras cargar `/labs/plaza/cajica` — 0 faltantes, verificado por comparación directa entre la respuesta de la API y el grafo de escena real vía Playwright.

- [x] **Quitar el filtro de densidad de vegetación por completo**  
  Ver detalle en IMM-040 arriba — pedido explícito del usuario al revisar la calidad adaptativa: sin excepciones por nivel de calidad, todo árbol/palmera configurado siempre se renderiza.

- [x] **Revisión de "Zonas excluidas" (pedido del usuario: verificar si funciona o eliminarla)**  
  Confirmado con una prueba real (no solo lectura de código) que la funcionalidad SÍ funciona de punta a punta: se guarda en `immersive_plazas.excluded_zones`, se dibuja en rojo en el editor espacial, y `StandZone`/`StandSlot` (`booted()`) rechazan con `ValidationException` cualquier zona o slot cuyo polígono invada una zona excluida — probado creando una zona invasora (rechazada con el mensaje correcto) y una válida (aceptada). No se eliminó nada; queda pendiente como gap real solo la ausencia de tests automatizados sobre esta validación.

- [x] **Corrección: no reparar la acción "Vista previa" (2D) — eliminarla, ya reemplazada**  
  **Contexto:** al revisar la falla preexistente de `test_the_preview_action_is_visible_on_the_plazas_list` (ver IMM-010 arriba), el primer instinto fue "arreglarla" cableando `ImmersivePlazaResource::previewAction()` (la vista 2D con SVG) de vuelta en `ImmersivePlazasTable`/`EditImmersivePlaza`, asumiendo que era una función legítima que se quedó desconectada por descuido. El usuario recordó a tiempo que esa acción 2D **ya estaba deliberadamente reemplazada** desde Fase 1 por `enterExperienceAction()` ("Entrar a la experiencia", que abre el recorrido 3D real vía `ImmersiveExperience::previewUrl()`) — el cableado que faltaba era código muerto de una versión anterior, no una regresión.  
  **Estado:** Hecho. Revertido el cableado; eliminados por completo `ImmersivePlazaResource::previewAction()`, la vista `resources/views/filament/immersive/plaza-preview.blade.php` y sus 3 tests en `ImmersiveExperienceDuplicationAndPreviewTest.php` (el que la daba por "visible en el listado" y los 2 que probaban el render del SVG directamente). `enterExperienceAction()` sigue siendo la única acción de vista previa, ya correctamente cableada en el listado y en la edición desde antes. `pint`/tests limpios tras la eliminación.  
  **Lección para próximas veces:** una falla de test que parece "función legítima nunca conectada" merece revisar primero si esa función fue reemplazada a propósito antes de asumir que es un descuido y repararla.

- [x] **Corrección real: el tiling de un objeto reajustaba el de otros objetos (piso/andenes) al compartir material**  
  **Reporte del usuario (con capturas):** en el editor espacial, cambiar el tiling de un objeto (ej. el piso naranja) reajustaba el tiling de otros objetos ya configurados (ej. los andenes), a pesar de que cada uno debería guardar su propio valor independiente.  
  **Causa real:** `applyTiling()` (`texture-tiling-utils.js`) clonaba la **textura** antes de mutar `.repeat`, pero nunca clonaba el **material** — y desde el cacheo de materiales de IMM-041, varios objetos con el mismo `builder_key`/textura comparten la misma instancia de material. `material.map = texturaClonada` seguía mutando ese material compartido, así que el último tiling aplicado "ganaba" para todos los objetos que lo comparten — exactamente el mismo problema que `applyStandPrimaryColor()` ya había resuelto para el color, pero que el arreglo de tiling de esa misma sesión (ver nota arriba en IMM-041) dejó incompleto por clonar solo la textura y no el material contenedor.  
  **Estado:** Hecho. `applyTiling()` ahora también clona el material completo (mismo patrón `cloneForInstance` que `stand-color-utils.js`) y lo reasigna a la malla antes de fijarle la textura con el repeat correcto — deja de compartirse con cualquier otro objeto que use el mismo material cacheado. Verificado con Playwright: dos objetos con el mismo `builder_key` (comparten material, confirmado `sharedBefore: true`) reciben tiling distinto (`{u:2,v:2}` y `{u:5,v:5}`) y terminan con materiales distintos (`materialsDiffer: true`), cada uno reteniendo su propio valor sin afectar al otro. `pint` limpio, tests de `PlazaSpatialEditorTest`/`ImmersivePlazaStandsEndpointTest` (incluida "saving a prop persists its texture tiling") pasan sin regresión.

- [x] **Preloader de entrada con textos aleatorios**  
  **Pedido del usuario:** mientras carga la experiencia, mostrar un overlay con textos rotativos tipo "Armando la iglesia...", "Generando vegetación...".  
  **Estado:** Hecho. Nuevo `public/js/lib/immersive-preloader.js` — 11 mensajes aleatorios rotando cada 1.8s, con timeout de seguridad de 30s. El overlay vive como HTML/CSS estático en `generic-plaza.blade.php` (visible desde el primer pintado, sin esperar a Three.js ni al resto del módulo) y se oculta con fade en el mismo punto donde ya se marca la escena como lista (`engine.perf.markSceneReady()`). Respeta `prefers-reduced-motion`. Verificado con Playwright interceptando las respuestas de props/stands para forzar una carga lenta: aparece de inmediato, rota mensajes, desaparece limpio del DOM al terminar.

- [x] **Ajustes de UI/UX de la escena pública, pedidos por el usuario (2026-08-12)**  
  - **Píldora "With ♥ by inggen.com" se estiraba al ancho completo en móvil:** el `@media (max-width: 768px)` forzaba `left/right: 14px`. Corregido con `width: max-content` + `max-width` como tope, mismo tamaño de contenido en cualquier pantalla.  
  - **Botón táctil sin función asignada:** el botón "●" (`vpe-btn-action`) solo disparaba un evento (`voxelplaza:action`) que nadie escuchaba en todo el código. Reasignado a "Correr" (⚡) — mantenerlo presionado activa `this.movement.sprint`, igual que Shift/Mayús en escritorio.  
  - **Ruta pública renombrada:** `/labs/plaza/{municipio}` → `/exp/plaza/{municipio}` (solo cambió el segmento de URI en `routes/web.php`; el nombre interno de ruta `labs.generic-plaza` no cambió, así que `route()`/`Municipality::immersiveLabUrl()` se actualizaron solos sin tocar otros archivos).  
  **Estado:** Hecho los tres, verificados con Playwright (escritorio y viewport móvil real).

- [x] **Bug real: el avatar Hombre/Mujer elegido en Ajustes nunca llegaba a los personajes de la plaza**  
  **Reporte del usuario:** los "dueños" que aparecen parados junto a su stand no reflejaban el avatar elegido en `/settings/avatar`.  
  **Causa real:** el script que sincroniza la elección hacia `users.avatar_preset` (que es lo que lee `ImmersivePlazaStandsController` para esos personajes) usaba `$wire.syncAvatarPreset(...)` dentro de un `<script>` plano — `$wire` no existe ahí, tirando `$wire is not defined` en consola silenciosamente en cada intento. Confirmado en base de datos real: ningún usuario tenía jamás `avatar_preset` guardado.  
  **Estado:** Hecho. Envuelto en `@script/@endscript` (la forma correcta en Livewire de tener `$wire` disponible). Efecto secundario encontrado y corregido de paso: tras guardar, el botón perdía visualmente su estado "seleccionado" hasta recargar (el re-render de Livewire borraba el `aria-pressed` que el picker maneja por su cuenta, un widget 100% cliente/localStorage) — envuelto en `wire:ignore`. Verificado extremo a extremo con un usuario de prueba: clic → petición Livewire real → `avatar_preset` persistido en BD → botón se mantiene marcado sin recargar.

- [x] **Bug real: el menú de Ajustes salía duplicado en /settings/avatar**  
  **Reporte del usuario (con captura):** toda la barra de navegación (Perfil/Seguridad/Apariencia/Avatar) aparecía dos veces, una debajo de la otra.  
  **Causa real:** `avatar.blade.php` envolvía `<x-pages::settings.layout>` (que incluye el layout COMPLETO de la página, con todo el menú) dos veces — una por cada sección ("Avatar" y "Movimiento reducido") — en vez de usarlo una sola vez con la segunda sección como un `<section>` normal adentro, que es el patrón que ya usan el resto de páginas de Ajustes (ver `security.blade.php`, con "Actualizar contraseña" + "Autenticación en dos pasos" + "Llaves de acceso" dentro de un único layout).  
  **Estado:** Hecho. Reescrito con un solo `<x-pages::settings.layout>` y "Movimiento reducido" como sección interna. Verificado con Playwright: un solo menú, ambas secciones visibles.

- [x] **Bug real: la cámara no giraba con el mouse en pointer lock en Safari (macOS)**  
  **Reporte del usuario:** tras activar la captura del mouse (cursor oculto, confirmado — el lock sí se activaba), mover el mouse no rotaba la cámara.  
  **Causa real:** Safari expone el movimiento relativo del mouse bajo pointer lock con las propiedades con prefijo `webkitMovementX`/`webkitMovementY` — las estándar `movementX`/`movementY` que ya leía `onMouseMove()` llegan en 0 ahí, así que `deltaX`/`deltaY` siempre daban 0 y la cámara quedaba inmóvil pese a que el lock funcionaba genuinamente (cursor oculto real). No reproducible en Chromium (headless o no), que sí soporta las propiedades estándar — encontrado únicamente gracias a que el usuario confirmó explícitamente "el cursor sí desaparece" al preguntarle, descartando que fuera un problema de que el lock nunca se activara.  
  **Estado:** Hecho. `onMouseMove()` ahora usa `event.movementX || event.webkitMovementX || 0` (mismo respaldo para Y) — aditivo, no cambia nada en navegadores donde `movementX`/`movementY` ya funcionan. Verificado con Playwright: (1) Chrome real sigue rotando la cámara igual que antes; (2) simulando el caso Safari exacto (evento con `movementX/Y` en 0 y solo `webkitMovementX/Y` poblados) confirma que el respaldo activa la rotación correctamente.

- [x] **Bug real de producción: `Cache::remember()` sobre `Collection` de Eloquent tumbaba `/api/v1/municipios` y `/api/v1/categorias` con 500**  
  **Reporte del usuario:** la experiencia inmersiva en `merkamigo.com` quedaba pegada en "Cargando la plaza..." — con la consola de producción abierta se veían ambos endpoints devolviendo 500, consumidos por `stand-search-panel.js` (`loadCategories()`/`loadPlazas()`).  
  **Causa real:** exactamente el bug ya documentado como "fuera de alcance" en IMM-034 arriba, ahora bloqueando funcionalidad real: PHP no deserializa de forma confiable un grafo de objetos Eloquent (`Collection` + modelos + relaciones) guardado tal cual en caché — "The script tried to call a method on an incomplete object... unserialize()".  
  **Estado:** Hecho. `DiscoveryController::municipios()`/`categorias()` ahora cachean el array YA RESUELTO por el `JsonResource` (`->resolve()`), nunca la `Collection` de modelos cruda — cero objetos PHP en el payload cacheado, mismo JSON de respuesta de siempre. Verificado en tinker (valor cacheado es array plano, lectura miss+hit dan contenido idéntico) y con Playwright contra la plaza real (preloader desaparece, cero peticiones fallidas). Tests de `DiscoveryApiTest` sin regresión.

- [x] **Bug real: un stand publicado podía quedar en "Sin configurar" en vez de "Pausado"**  
  **Reporte del usuario:** su vitrina funcionó en la plaza, luego "desapareció"; la vitrina seguía publicada pero "Mi stand en la plaza" mostraba "Sin configurar" (el estado inicial, "nunca se intentó asignar") en vez de un estado que reflejara que sí tuvo un espacio antes.  
  **Causa real:** `ReleaseBusinessStand::handle()` decidía "tuvo un slot" (`$hadSlot`) mirando solo `stand_slot_id` en el momento de la llamada. Si se libera dos veces seguidas sin publicar en el medio (ej. `AssignBusinessToStand` ya había limpiado `stand_slot_id` al resolver `sin_cupo`/`reubicación_requerida`, y el negocio se vuelve a guardar sin estar publicado), la segunda liberación encontraba ese campo en null y degradaba el estado a `sin_configurar` en vez de `pausado` — perdiendo la memoria de que el negocio sí tuvo un espacio, aunque `previous_slot_id` (el campo pensado exactamente para recordar esto) seguía teniéndolo.  
  **Estado:** Hecho. `$hadSlot` ahora también cuenta `previous_slot_id`. Nuevo test de regresión (`test_releasing_a_business_stand_twice_in_a_row_stays_paused`) reproduce la doble liberación y confirma que el estado se queda en `pausado`. **Importante:** el fix corrige el comportamiento hacia adelante, pero NO revierte por sí solo un `StandAssignment` que ya haya quedado atascado en `sin_configurar` antes de este cambio — esos casos necesitan un guardado nuevo del negocio (cualquier edición y "Guardar", con la vitrina ya publicada) para que el observador vuelva a intentar la asignación.

### Fase 5 — QA, seguridad y despliegue

- [ ] **IMM-050 — Pruebas del motor de distribución**  
  **Resultado visible:** suite automatizada para 0, 1, capacidad máxima y sobrecupo de vitrinas; concurrencia de altas/cambios.  
  **Criterio de aceptación:** no hay doble asignación aunque dos vitrinas se creen simultáneamente.  
  **Estado:** Pendiente.

- [ ] **IMM-051 — QA multidispositivo y recuperación**  
  **Resultado visible:** matriz de navegadores/dispositivos y mensajes útiles si WebGL falla.  
  **Reglas:** fallback a listado 2D, reintento de recursos y registro de errores sin bloquear la web.  
  **Criterio de aceptación:** un fallo de la escena no impide consultar vitrinas.  
  **Estado:** Pendiente.

- [ ] **IMM-052 — Seguridad y publicación de recursos**  
  **Resultado visible:** validación de archivos, permisos, rutas y contenido; CDN/caché con versionado.  
  **Reglas:** no confiar en URLs o configuraciones aportadas por usuarios; sanitizar contenido HTML; usar recursos aprobados por administración.  
  **Criterio de aceptación:** un emprendedor no puede inyectar código, modelos o texturas arbitrarias en la experiencia.  
  **Estado:** Pendiente.

- [ ] **IMM-053 — Piloto controlado y despliegue gradual**  
  **Resultado visible:** lanzamiento inicial a un porcentaje de usuarios y monitoreo de carga, errores y conversiones.  
  **Dependencias:** todas las tareas críticas anteriores.  
  **Criterio de aceptación:** posibilidad de desactivar la experiencia por feature flag y volver a la vista web sin despliegue de emergencia.  
  **Estado:** Pendiente.

## 8. Panel administrativo propuesto

### Ruta de navegación

`Administración > Experiencias inmersivas`

### Vistas

1. **Listado de experiencias:** municipio, nombre, plazas, capacidad total, ocupación, versión, estado, peso y última publicación.
2. **Crear/editar experiencia:** municipio, nombre, slug, miniatura, escena, límites, punto de aparición, centro de orientación y perfiles de calidad.
3. **Plazas:** orden, capacidad, ocupación, regla de asignación, estado y navegación.
4. **Editor espacial:** zonas permitidas/excluidas, rutas y capa independiente de reservas de stands, con color propio, huella, estado, orientación individual y previsualización de colisiones.
5. **Asignaciones:** vitrina, categoría, stand, plaza, slot, estado; búsqueda y reubicación controlada.
6. **Versiones:** borrador, publicación, historial, comparación básica y reversión.
7. **Métricas y rendimiento:** comportamiento comercial y salud técnica por dispositivo.

## 9. Datos adicionales relevantes en el administrador

- Coordenada y rotación del punto de aparición del personaje.
- Polígono navegable y zonas excluidas.
- Punto central de referencia para orientar stands.
- Modo de orientación por zona y excepción manual por slot.
- Eje frontal de cada plantilla de stand.
- Separación mínima entre stands y margen peatonal.
- Capacidad máxima calculada y capacidad administrativa permitida.
- Orden/prioridad de ocupación de zonas y plazas.
- Categorías permitidas o preferidas por zona.
- Horarios o fechas de publicación de una plaza/evento.
- Escena ligera y escena de alta calidad, si se manejan recursos distintos.
- Presupuestos máximos de peso, triángulos, draw calls, materiales y texturas.
- Imagen de carga, miniatura, instrucciones y mensaje de indisponibilidad.
- Versión de recursos y estrategia de caché.
- Estado: borrador, validando, publicada, pausada, archivada.

## 10. Criterios globales de aceptación del MVP

- Un administrador puede crear una experiencia, asociarla a un municipio, configurar al menos una plaza y publicarla sin editar código.
- Un emprendedor puede escoger uno de tres stands y ver el cambio reflejado sin alterar su ubicación.
- Una vitrina de Zipaquirá nunca se genera en Cajicá u otro municipio.
- Ningún stand se solapa, sale de la zona permitida ni bloquea rutas definidas.
- Cuando se llena una plaza, el sistema asigna las nuevas vitrinas a otra plaza activa o las marca claramente como Sin cupo.
- El usuario puede escoger avatar Hombre o Mujer, recorrer la escena y abrir la vitrina correcta.
- El modal HTML utiliza datos vigentes de la vitrina web y es usable en móvil.
- La experiencia incluye búsqueda, filtros por categoría y navegación entre plazas.
- Existe alternativa de listado 2D si el dispositivo no soporta la experiencia o el usuario prefiere no usarla.
- El administrador puede medir aperturas de vitrinas y clics comerciales sin recolectar datos innecesarios.
- Las pruebas en dispositivos objetivo cumplen el presupuesto de rendimiento definido en IMM-002.

## 11. Alcance recomendado del primer MVP

### Incluido

- Zipaquirá como experiencia piloto y Cajicá como segunda validación.
- Una escena fija optimizada por municipio.
- Múltiples plazas/páginas configurables.
- Slots predefinidos con asignación automática.
- Tres stands.
- Dos avatares genéricos.
- Movimiento básico y colisiones.
- Modal HTML de vitrina.
- Búsqueda, categorías y navegación entre plazas.
- Calidad adaptativa, fallback 2D y analítica básica.

### Fuera de alcance inicial

- Personalización detallada del avatar.
- Multijugador en tiempo real o chat de proximidad.
- Construcción libre o movimiento manual de stands por emprendedores.
- Generación automática de plazas por IA dentro del producto.
- Compra o pago dentro del mundo 3D.
- Voz, realidad virtual, realidad aumentada o clima dinámico.
- Eventos masivos sincronizados en tiempo real.

## 12. Orden de implementación recomendado

1. Auditar y unificar los labs actuales.
2. Crear modelo de datos y administrador de experiencias/plazas.
3. Construir editor de zonas y slots.
4. Crear tres stands y asignador automático.
5. Integrar cuenta del emprendedor.
6. Incorporar avatares, controles y colisiones.
7. Reutilizar la vitrina web mediante modal HTML.
8. Agregar filtros y navegación entre plazas.
9. Optimizar, medir, probar y desplegar gradualmente.

## 13. Recomendación técnica final

Mantener **Three.js con WebGL 2**, una escena fija optimizada por municipio y recursos reutilizables. Los stands repetidos deben renderizarse mediante instancias y el catálogo debe permanecer en HTML. La configuración espacial debe almacenarse como datos versionados y el servidor debe decidir asignaciones de forma transaccional para evitar duplicados cuando se registren varios emprendedores al mismo tiempo.

La primera tarea de desarrollo no debería ser crear más modelos: debe ser construir el sistema de **zonas + slots + asignaciones persistentes**, porque esa base resuelve la organización, orientación, crecimiento y administración de todas las plazas futuras.
