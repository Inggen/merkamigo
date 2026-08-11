# Personaje principal de las plazas voxel — mecánica canónica

> Documentado el 2026-08-05 a pedido explícito: **esta mecánica se reutiliza
> tal cual está, sin rediseñarla.** Este documento es la referencia
> completa (constantes exactas, fórmulas, orden de actualización) para que
> IMM-030/IMM-031 (selección de avatar y controles multiplataforma, Fase 3)
> y cualquier plaza nueva partan de esto en vez de reinventarlo.
>
> Vive hoy en **`public/js/lib/voxel-plaza-engine.js`**, dentro de la clase
> `VoxelPlazaEngine` — es la implementación que ya usa Cajicá y a la que
> Zipaquirá debe migrar (IMM-003, todavía pendiente). El lab standalone
> `public/js/zipa-plaza-immersive.js` tiene una copia funcionalmente
> equivalente pero duplicada a mano; **la fuente de verdad es la del motor
> compartido**, no la copia.

## 1. Anatomía del avatar (`buildPlayer()`, líneas 381-425)

Un `THREE.Group` (`avatar`) con dos hijos directos:

- `avatar.userData.shadow`: `CircleGeometry(1.2, 20)` plana en el suelo (`rotation.x = -Math.PI/2`, `y = 0.03`), material `MeshBasicMaterial` negro semitransparente (`opacity: 0.18`). Se escala dinámicamente para simular altura (ver §3).
- `avatar.userData.body`: otro `THREE.Group`, contiene las 7 cajas voxel del cuerpo. Todas construidas con `addVoxelBox()` (mismo helper que edificios/mobiliario) y guardadas en `avatar.userData.*` para poder animarlas individualmente:

| Parte | x | y | z | w | h | d | textura |
|---|---|---|---|---|---|---|---|
| `head` | 0 | 3.22 | 0 | 1.25 | 1.25 | 1.12 | `skin` |
| `hair` | 0 | 3.68 | -0.08 | 1.28 | 0.42 | 1.18 | `woodDark` |
| `torso` | 0 | 2.02 | 0 | 1.58 | 1.8 | 0.98 | `shirt` |
| `leftLeg` | -0.38 | 0.72 | 0 | 0.48 | 1.44 | 0.48 | `pants` |
| `rightLeg` | 0.38 | 0.72 | 0 | 0.48 | 1.44 | 0.48 | `pants` |
| `leftArm` | -1.02 | 2.08 | 0 | 0.36 | 1.52 | 0.36 | `skin` |
| `rightArm` | 1.02 | 2.08 | 0 | 0.36 | 1.52 | 0.36 | `skin` |

No hay animación esquelética (sin huesos/blend shapes): cada parte es una caja rígida cuya `rotation.x` se anima directamente (ver §3). Esto es deliberado — coherente con el estilo voxel del resto de la plaza — y es la razón por la que IMM-030 (avatar Hombre/Mujer) puede resolverse cambiando texturas/proporciones de estas mismas cajas sin tocar la lógica de movimiento.

## 2. Física del personaje (`playerState`, líneas 201-221)

```js
{
  moveSpeed: 10.6,        // u/s caminando
  sprintSpeed: 18.6,      // u/s corriendo (Shift)
  acceleration: 40,       // aceleración horizontal en suelo
  airAcceleration: 14,    // aceleración horizontal en el aire (más lenta)
  drag: 18,               // frenado cuando no hay input
  gravity: 28,
  jumpVelocity: 10.9,
  velocity: Vector3(),
  radius: 0.8,            // radio de la caja de colisión del jugador
  height: 3.95,
  feetY: playerStart.y ?? 0,  // altura del suelo para ESTA plaza
  onGround: true,
  turnSpeed: 0.22,
  inputSmoothing: 0.18,   // (declarado, no usado directamente hoy — ver nota)
  jumpCooldown: 0,
  strafeFactor: 0.9,      // penalización de velocidad al moverse solo lateral
  backpedalFactor: 0.78,  // penalización al retroceder
  rotationVelocity: 0,    // (declarado, no usado directamente hoy — ver nota)
}
```

`feetY` es lo único que cada plaza suele sobreescribir (vía el parámetro `player` del constructor) cuando su suelo no está en `y = 0`.

## 3. Movimiento (`updatePlayer(delta)`, líneas 583-660)

Orden exacto por frame:

1. **Lee input** de `this.movement` (booleans `forward/backward/left/right`) y arma un vector de intención en espacio local, usando `forward`/`right` derivados de `controls.yaw` (así "adelante" siempre es hacia donde mira la cámara, no un eje de mundo fijo).
2. **Si hay input:** normaliza, aplica `sprintSpeed` si `movement.sprint && !movement.backward` (no se puede "sprint-retroceder"), aplica `backpedalFactor`/`strafeFactor` según la dirección, y suaviza la velocidad hacia la deseada con `THREE.MathUtils.damp(..., acceleration|airAcceleration, delta)` — un damp exponencial, no una interpolación lineal. La rotación del personaje (`player.rotation.y`) se amortigua hacia el ángulo de movimiento con `damp(..., 10, delta * turnSpeed * 10)`.
3. **Si no hay input:** la velocidad decae hacia 0 con `damp(..., drag, delta)`.
4. **Salto:** si `movement.jump && onGround && jumpCooldown === 0`, fija `velocity.y = jumpVelocity`, `onGround = false`, `jumpCooldown = 0.22`s (evita saltos repetidos por mantener la tecla).
5. **Gravedad** siempre resta `gravity * delta` a `velocity.y`.
6. **Colisión por eje, no combinada:** mueve X, prueba `collides()`; si choca, revierte X y amortigua `velocity.x *= 0.18` (no la pone en 0 — permite "deslizar" contra la pared). Repite igual para Z. Esto es lo que evita que el personaje se trabe en esquinas.
7. Aplica `velocity.y` a la posición Y sin chequeo de colisión vertical contra `collisions[]` — el único límite vertical es `feetY` (suelo plano de la plaza): si `position.y <= feetY`, se fija a `feetY`, `velocity.y = 0`, `onGround = true`.
8. Llama a `updatePlayerAnimation(delta, speedRatio)` con `speedRatio = |velocity horizontal| / moveSpeed` (clamp a 1).

## 4. Animación procedural (`updatePlayerAnimation`, líneas 555-581)

Sin clips ni mezclador — todo calculado en cada frame a partir de `performance.now()` y `speedRatio`:

- `cycle = performance.now() * 0.012` — fase compartida del ciclo de caminata.
- `swing = sin(cycle * sprintBoost) * 0.62 * speedRatio` (`sprintBoost = 1.28` si corre) → rotación X de brazos/piernas, brazos y piernas opuestos en fase (brazo izq. con pierna izq. invertida, como una caminata real).
- `bounce = |sin(cycle * sprintBoost)| * 0.11 * speedRatio` → sube/baja `body.position.y`.
- **En el aire** (`!onGround`): sobreescribe todo lo anterior con una pose fija (brazos atrás `-0.34`, piernas `0.18`/`-0.18`) — no es parte del ciclo de caminata.
- Rotación de torso (balanceo lateral leve) amortiguada con `damp(..., 8, delta)` hacia `sin(performance.now()*0.004) * 0.08 * speedRatio`.
- La sombra se escala hacia abajo cuando el personaje salta: `scale = 1 - min(0.25, position.y * 0.04)`.

## 5. Cámara persecutora (`updateCamera`, líneas 662-678, `cameraState` líneas 223-232)

```js
{ distance: 15.6, height: 6.15, shoulder: 0.9, smoothing: 0.16,
  sprintDistance: 17.1, sprintHeight: 6.45, target: Vector3() }
```

- Posición deseada = posición del jugador + offset calculado desde `controls.yaw` (detrás del personaje) + un desplazamiento lateral fijo (`shoulder`) + una elevación extra según `controls.pitch` (`sin(-pitch) * 2.1`).
- Corriendo, usa `sprintDistance`/`sprintHeight` en vez de los valores base (cámara se aleja un poco).
- La posición de cámara y el punto que mira (`target`, fijado a la cabeza del personaje `+3.35` en Y) se suavizan por separado con `lerp` (`smoothing = 0.16` para posición, `0.22` fijo para el target) — dos velocidades de suavizado distintas es lo que le da la sensación de "cámara con inercia propia".

## 6. Controles (`bindInput`, líneas 680-740; `controls`/`movement`, líneas 187-199)

- **Teclado:** flechas o WASD para movimiento, Espacio para saltar, Shift (cualquiera) para sprint. Mapeo directo `event.code → movement.*`, sin remapeo configurable todavía.
- **Mouse:** dos modos conviven —
  - **Pointer lock** (`requestPointerLock()` al hacer click sobre el canvas): usa `event.movementX/Y` directo, cursor oculto (`.is-locked` en el contenedor).
  - **Drag sin lock** (si el navegador no soporta o el usuario no ha hecho click): calcula delta manualmente contra `lastX/lastY` mientras `isDragging`.
  - En ambos casos, `yaw -= deltaX * mouseSensitivity` (0.0026) y `pitch` análogo con un factor extra `*0.88`, clamped entre `minPitch (-1.05)` y `maxPitch (0.24)`.
- **Resize:** reajusta aspect ratio y tamaño del renderer.
- **No hay controles táctiles todavía** — el joystick/botones táctiles para móvil es exactamente el alcance de IMM-031 (Fase 3, pendiente). Esta mecánica de física/cámara no debería cambiar; solo hay que alimentar `this.movement` desde eventos táctiles en vez de teclado.

## 7. Colisión (`getPlayerBounds`/`collides`, líneas 535-546)

AABB puro: el jugador es una caja `Box3` centrada en su posición (`radius*2` de ancho/profundidad, `height` de alto) contra la lista plana `this.collisions` (cajas `Box3` generadas por `addCollisionBox`/`addVoxelBox({collidable: true})` de todo lo construido en la plaza). `Array.some()` sobre todas las colisiones cada frame — sin particionado espacial; aceptable a la escala actual de una plaza, pero es el primer sitio a optimizar si `collisions.length` crece mucho (relevante para IMM-041, Instancing/LOD).

## 8. Orden de actualización por frame (`animate()`, líneas 742-753)

`updatePlayer(delta)` → `updateActors(time)` (NPCs decorativos, no el jugador) → `updateCamera()` → `renderer.render()` → `perf.sample()`. El jugador siempre se actualiza antes que la cámara lo siga, en el mismo frame — no hay un frame de retraso.

## 9. Qué NO tocar vs. qué sí puede variar por plaza/avatar

**Reutilizar tal cual (no rediseñar):** el algoritmo de `updatePlayer` completo (damp-based, colisión por eje, salto con cooldown), la fórmula de `updateCamera`, el esquema de animación procedural de `updatePlayerAnimation`, el binding de input (teclado + pointer lock/drag).

**Lo que sí varía por contexto, ya soportado por el constructor:**
- `playerStart` / `playerFacing` (punto de aparición) — por plaza, ver `ImmersivePlaza.spawn_point` (IMM-012).
- `player` / `camera` (overrides parciales de `playerState`/`cameraState`) — el constructor los mezcla con `{...defaults, ...overrides}`, así que un avatar "más grande" o una cámara "más cercana" no requieren tocar esta lógica, solo pasar overrides.
- Texturas de las 7 cajas del cuerpo — es lo que IMM-030 (avatar Hombre/Mujer) debería variar, no la física ni la animación.
- Geometría de cabello del preset `mujer` (dos cajas de trenza extra, `buildAvatarBoxes()` en `voxel-plaza-engine.js`) — única excepción de geometría entre presets, deliberadamente acotada al cabello; cuelgan del grupo `body` y siguen su bamboleo/giro sin que `updatePlayerAnimation` sepa que existen (no lee `head`/`hair`, solo `body/leftArm/rightArm/leftLeg/rightLeg/shadow`).
