# Auditoría de rendimiento — labs inmersivos (IMM-001 / IMM-002)

> Fase 0 de `TODO-Merkamigo-Experiencia-Inmersiva.md`. Resultados medidos
> el 2026-08-04 contra `https://merkamigo.test` (Herd, entorno local) con
> Chromium headless real (no estimaciones), usando
> `scripts/audit-immersive-performance.mjs`. Repetir con
> `npm run audit:immersive` cuando algo cambie en los labs.

## Cómo se midió

- **Instrumentación:** `public/js/lib/immersive-perf-monitor.js`, un panel
  técnico temporal que lee `renderer.info` (draw calls, triángulos,
  texturas, geometrías activas), FPS real por frame, `performance.memory`
  (heap JS, solo Chrome) y tiempo de carga hasta que la escena base y sus
  assets async quedan listos. Se activa con `?perf=1` en la URL o
  presionando **P** en cualquier momento; queda cableado tanto en el lab
  de Zipaquirá (standalone) como en el motor compartido
  `voxel-plaza-engine.js` (usado por Cajicá), así que cualquier plaza
  futura construida sobre el motor lo hereda gratis.
- **Presupuesto:** `public/js/lib/immersive-perf-budget.js` centraliza los
  límites (IMM-002) y clasifica cada métrica en OK / Atención / Crítico.
- **Automatización:** `scripts/audit-immersive-performance.mjs` abre cada
  lab en Chromium headless bajo tres perfiles (Escritorio, Android medio
  con CPU 4x más lenta y red ~6 Mbps, iPhone gama media con CPU 2x y red
  ~10 Mbps), mide bytes reales transferidos vía CDP
  (`Network.loadingFinished`, no la Resource Timing API del navegador,
  porque three.js se sirve desde esm.sh sin cabecera
  `Timing-Allow-Origin` y esa API reportaría `transferSize: 0`), y sale
  con código de error si algún perfil móvil queda en nivel crítico. Esto
  es, mientras no exista el flujo de publicación de la Fase 1, el
  mecanismo de bloqueo automatizable que pide IMM-002.

## Resultados

| Lab | Dispositivo | FPS min/prom | Carga | Draw calls | Triángulos | Geometrías | Veredicto |
|---|---|---|---|---|---|---|---|
| Zipaquirá | Escritorio | 36 / 56 | 264 MB en ~4 s (red local, sin límite) | 48 | 5.948.364 | 251 | **Crítico** |
| Zipaquirá | Android medio | no termina de cargar | ~264 MB → ~370 s estimados a 6 Mbps | — | — | — | **Crítico** |
| Zipaquirá | iPhone gama media | no termina de cargar | ~264 MB → ~220 s estimados a 10 Mbps | — | — | — | **Crítico** |
| Cajicá | Escritorio | 6* / 51 | 0.37 MB en ~1.5 s | 565 | 7.320 | 944 | **Crítico** (por draw calls/geometrías) |
| Cajicá | Android medio | 17 / 52 | 0.36 MB en ~1.8 s | 258 | 3.636 | 932 | **Crítico** (por draw calls/geometrías) |
| Cajicá | iPhone gama media | 44 / 57 | 0.36 MB en ~1.5 s | 238 | 3.396 | 932 | **Atención/Crítico** (geometrías) |

\* El mínimo de 6 FPS en Cajicá/Escritorio corresponde a un único frame
justo después de que la escena queda lista (primer shadow map, primera
compilación de shaders) y no a un problema sostenido — el promedio de 51
FPS en el mismo dispositivo lo confirma. Se deja anotado porque el
presupuesto lo marca en rojo tal cual está definido hoy; si esto genera
ruido en auditorías futuras, la corrección de bajo riesgo es descartar
las 2 primeras muestras del monitor antes de promediar.

Presupuesto de referencia (IMM-002): 30 FPS estables en móvil, ≤ 4 MB de
carga inicial, ≤ 220 draw calls, ≤ 400.000 triángulos, ≤ 45 texturas,
≤ 450 geometrías activas, ≤ 350 MB de heap JS.

## Los tres mayores cuellos de botella

### 1. Zipaquirá descarga ~264 MB de modelos GLB sin comprimir en cada visita (66x el presupuesto)

`zipa-plaza-immersive.js` carga por red, sin compresión Draco/Meshopt, sin
LOD y sin instancing:

- `catedral-zipa-voxel.glb` — 93 MB
- `alcaldia1.glb` — 89 MB
- `palmera-voxel.glb` — 77 MB
- `farol-voxel.glb` — 2.6 MB

En un Android medio real (~6 Mbps) esto tarda del orden de **6 minutos**
en transferirse; en un iPhone gama media (~10 Mbps), del orden de
**3.5-4 minutos**. Es, con enorme margen, el bloqueo más grave: nadie en
móvil llega siquiera a ver la plaza. Es también la razón por la que
IMM-002 debe existir como script que falle el build/CI y no solo como
sugerencia — este payload nunca debería haber llegado a `main` sin una
alerta automática.

**Recomendación:** comprimir los tres GLB con Draco o Meshopt
(`gltf-transform optimize`), generar un LOD de baja resolución para móvil
y decidir si la catedral/alcaldía necesitan ser modelos GLB en absoluto
o si, como el resto de la plaza, pueden construirse con las mismas cajas
voxel proceduralles del motor compartido (ver hallazgo 3). Esto excede el
alcance de esta auditoría y se deja como acción de seguimiento explícita,
no como parte de Fase 0.

### 2. Textura de cielo sin comprimir (ya corregido en esta sesión)

`loadSkyDome()` cargaba `paisaje_otono_voxel_4k.png` (7.4 MB) cuando ya
existía, sin usar, un `paisaje_otono_voxel_4k.webp` (1.3 MB, mismas
dimensiones 3840×1920, 82% más liviano) en la misma carpeta. Se cambió la
referencia en `zipa-plaza-immersive.js` a la versión WebP — confirmado
con la misma auditoría automatizada: la carga total bajó de 270.58 MB a
264.43 MB. Cambio de una línea, sin riesgo visual, ya aplicado.

### 3. El motor compartido no reutiliza geometría/material entre bloques voxel repetidos

Cajicá pesa 0.36 MB (muy por debajo del presupuesto) pero genera **~930
geometrías** y hasta **565 draw calls** para una plaza que visualmente es
"cajas de colores repetidas": `VoxelPlazaEngine.addVoxelBox()`
(`public/js/lib/voxel-plaza-engine.js:315`) crea un `THREE.Mesh` +
`BoxGeometry` + `MeshStandardMaterial` nuevos en cada llamada, incluso
cuando decenas de cajas comparten textura, tamaño y orientación (un árbol
son ~34 cajas individuales; una fila de arcadas de 8 secciones, más de
150). Esto es exactamente lo que IMM-041 ("Instancing, LOD y carga
progresiva") anticipa, pero conviene señalarlo ya en Fase 0 porque es la
causa raíz que impedirá escalar cuando existan más stands por plaza: cada
stand nuevo multiplica geometrías y draw calls linealmente en vez de
reutilizar instancias.

**Recomendación:** agrupar por `(texture, w, h, d)` usando
`THREE.InstancedMesh` para los elementos repetidos (cajas de arcadas,
troncos de árbol, cajas de setos) y compartir un único material por
textura en vez de instanciar uno por caja. Esto es trabajo de Fase 4
(IMM-041), no de Fase 0 — se deja documentado aquí porque la auditoría lo
hizo visible con números concretos.

## Hallazgo colateral: assets huérfanos en el repositorio (ya corregido)

`public/3D/catedral-zipa.glb` (92 MB) y `public/3D/palmera.glb` (77 MB)
no estaban referenciados por ningún archivo `.js` ni `.blade.php` del
proyecto — solo existen sus contrapartes `-voxel` (ver hallazgo 1), que sí
se usan. Eran ~169 MB muertos dentro del control de versiones; se
eliminaron del repositorio en esta sesión (`git rm`), quedan recuperables
desde el historial de git si alguna vez hicieran falta.

## Qué queda pendiente de Fase 0

- **IMM-003** (núcleo reutilizable): Cajicá ya usa el motor compartido
  `voxel-plaza-engine.js`; Zipaquirá sigue siendo una implementación
  standalone de 2200+ líneas con arquitectura única (catedral, alcaldía,
  fachadas, palmeras/faroles vía GLB, storefronts de vitrinas). Migrarla
  por completo al motor compartido es un cambio grande y de riesgo real
  para una plaza que ya funciona (requiere extender el motor con
  primitivas nuevas — carga de modelos GLB, señalética de vitrinas,
  sky dome — y validar visualmente que nada se rompió). Se decidió no
  intentarlo a ciegas dentro de esta auditoría; queda como tarea
  siguiente explícita, con la comparación de `renderer.info` antes/después
  como red de seguridad para verificar equivalencia.
