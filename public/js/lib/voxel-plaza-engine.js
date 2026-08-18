/**
 * Motor compartido para los labs inmersivos de plaza voxel (estilo
 * Minecraft) de Merkamigo. Nace de refactorizar zipa-plaza-immersive.js:
 * antes cada lab copiaba el personaje, la cámara, la física y las
 * texturas, y colocaba cada edificio con una llamada suelta a
 * addVoxelBox con coordenadas escritas a mano. Este módulo separa:
 *
 * 1. El motor (`VoxelPlazaEngine`): personaje, cámara perseguidora,
 *    colisiones, texturas voxel y el loop de animación. Se reutiliza
 *    completo entre plazas — nunca se reescribe por plaza.
 * 2. Las piezas de construcción (`standardBuilders`): casa colonial,
 *    fila de arcadas, banca, jardinera, farol, árbol, palmera, nube,
 *    NPC, seto, fuente, puesto de mercado, vehículo, estatua. También
 *    se reutilizan; una plaza nueva casi nunca necesita inventar una
 *    pieza desde cero.
 * 3. Generadores de posición (`row`, `ring`, `rectPerimeter`): en vez de
 *    escribir a mano el x/z de cada elemento de una fila o de un
 *    perímetro (como hacía el primer lab, edificio por edificio),
 *    describen "N elementos a lo largo de esta línea/anillo/rectángulo"
 *    y devuelven la lista de posiciones ya calculada.
 *
 * Hoy el único consumidor es `generic-plaza-immersive.js`, que no describe
 * ningún `layout` propio a mano: arma la plaza completa a partir de los
 * datos de una `ImmersivePlaza` (elementos y stands guardados en BD) — el
 * arreglo declarativo `{ type, ...opciones }` que consume `place()` sigue
 * siendo el mecanismo que usa el editor espacial del admin para eso mismo.
 */
import * as THREE from 'https://esm.sh/three@0.179.1';
import { GLTFLoader } from 'https://esm.sh/three@0.179.1/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'https://esm.sh/three@0.179.1/examples/jsm/loaders/DRACOLoader.js';
import { MeshoptDecoder } from 'https://esm.sh/three@0.179.1/examples/jsm/libs/meshopt_decoder.module.js';
import { attachPerfMonitor } from './immersive-perf-monitor.js';
import { applyTiling } from './texture-tiling-utils.js';

export { THREE };

// IMM-041: ningún template real usa hoy un GLB comprimido con Draco/
// Meshopt (confirmado: `model_url` real siempre es un .glb sin comprimir
// todavía), así que esto no cambia nada del comportamiento actual — deja
// el loader compartido listo para cuando se empiece a usar compresión,
// sin costo si nunca se necesita (el decoder solo se descarga si el GLB
// realmente trae streams comprimidos). Decodificador Draco alojado en el
// CDN oficial de Google (mismo que usan los ejemplos propios de Three.js).
const sharedGltfLoader = new GLTFLoader();
const dracoLoader = new DRACOLoader();
dracoLoader.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
sharedGltfLoader.setDRACOLoader(dracoLoader);
sharedGltfLoader.setMeshoptDecoder(MeshoptDecoder);

/**
 * IMM-041: las 3 fábricas `material()` de este archivo creaban un
 * `MeshStandardMaterial` nuevo en cada llamada, aunque dos cajas con la
 * misma textura y las mismas opciones (`transparent`/`opacity`/`emissive`)
 * son visualmente idénticas — en una plaza real con ~1.150 mallas
 * repetidas (árboles, tejas, ventanas de casas) eso significaba miles de
 * materiales redundantes. `cache` es un `WeakMap` por textura (para no
 * retener texturas ya descartadas) con un `Map` interno por combinación
 * de opciones — sin cambiar ningún comportamiento visual, solo reutiliza
 * la misma instancia cuando la combinación ya se vio antes.
 */
function cachedMaterial(cache, texture, extra = {}) {
    const { transparent = false, opacity = 1, emissive = 0x000000 } = extra;
    const key = `${transparent}|${opacity}|${emissive}`;

    let byOptions = cache.get(texture);

    if (!byOptions) {
        byOptions = new Map();
        cache.set(texture, byOptions);
    }

    let material = byOptions.get(key);

    if (!material) {
        material = new THREE.MeshStandardMaterial({
            map: texture,
            roughness: 0.94,
            metalness: 0.03,
            transparent,
            opacity,
            emissive,
        });
        byOptions.set(key, material);
    }

    return material;
}

export const basePalette = {
    plaza: 0xd3bb8b,
    pavement: 0x676159,
    plazaDark: 0xb49257,
    stone: 0xd6c18d,
    stoneDark: 0xad8557,
    stoneLight: 0xe5d3a8,
    white: 0xf4ebe2,
    ochre: 0xd69a43,
    coral: 0xc9754b,
    butter: 0xe2c36b,
    roof: 0xc56f3a,
    roofDark: 0x86461d,
    roofClay: 0xb76134,
    wood: 0x6d4b30,
    woodDark: 0x311b0c,
    leaf: 0x6f9d37,
    leafDark: 0x3f6221,
    mountain: 0x617f4f,
    mountainDark: 0x374a31,
    trim: 0xa17c57,
    iron: 0x8493a4,
    concrete: 0xb5b7b9,
    brick: 0xad5a3b,
    glass: 0x7bc0e9,
    water: 0x73d2e5,
    flower: 0xdb5775,
    cloth: 0xc98e39,
    skin: 0xdfaa77,
    shirt: 0x2869d0,
    pants: 0x33445c,
    // Texturas dedicadas del personaje jugable (IMM-030): mismos valores
    // que skin/woodDark/shirt/pants de arriba, pero en claves propias que
    // solo usa `buildPlayer()` — así un preset de avatar puede
    // sobreescribirlas sin recolorear balcones/árboles/vehículos/NPCs de
    // fondo, que siguen usando las claves genéricas.
    avatarSkin: 0xdfaa77,
    avatarHair: 0x311b0c,
    avatarShirt: 0x2869d0,
    avatarPants: 0x33445c,
    grass: 0x90b85e,
    grassDark: 0x64853f,
    path: 0xcab07f,
    patina: 0x6f8f79,
    patinaDark: 0x3f5646,
    accent: 0xdf3527,
    brickAccent: 0xe6e0d3,
};

/**
 * Presets de avatar jugable (IMM-030): claves de `avatarSkin`/`avatarHair`/
 * `avatarShirt`/`avatarPants` para pasar como override de `palette` al
 * construir un `VoxelPlazaEngine` (ej. `{ ...basePalette, ...avatarPresets.mujer }`).
 * `hombre` reproduce exactamente los valores por defecto de `basePalette`
 * de arriba — elegirlo o no elegir nada se ve idéntico en ese preset.
 * `mujer` además agrega dos cajas de trenza en `buildAvatarBoxes()` (única
 * diferencia de geometría entre presets, a propósito acotada al cabello —
 * la física/animación sigue intocable, ver
 * docs/architecture/personaje-inmersivo.md).
 */
export const avatarPresets = {
    hombre: { avatarSkin: 0xdfaa77, avatarHair: 0x311b0c, avatarShirt: 0x2869d0, avatarPants: 0x33445c },
    mujer: { avatarSkin: 0xdfaa77, avatarHair: 0x4a2a12, avatarShirt: 0xb8386b, avatarPants: 0x2c2333 },
};

/**
 * Genera UNA textura voxel suelta (canvas con ruido + acento opcional +
 * líneas de junta opcionales). Extraído de `createVoxelTextures` para que
 * `createAvatarTextures` (figuras de avatar independientes del motor,
 * fuera de `this.textures`) pueda reusar exactamente el mismo algoritmo de
 * pintado sin generar las ~35 texturas completas de una plaza.
 */
function paintVoxelTexture(base, noise = 18, accent = null, lines = false) {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    const baseColor = new THREE.Color(base);

    ctx.fillStyle = `#${baseColor.getHexString()}`;
    ctx.fillRect(0, 0, size, size);

    for (let y = 0; y < size; y += 4) {
        for (let x = 0; x < size; x += 4) {
            const color = baseColor.clone();
            color.offsetHSL(0, 0, (Math.random() - 0.5) * (noise / 255));
            ctx.fillStyle = `#${color.getHexString()}`;
            ctx.fillRect(x, y, 4, 4);
        }
    }

    if (accent) {
        ctx.fillStyle = `#${new THREE.Color(accent).getHexString()}`;
        for (let i = 0; i < size; i += 8) {
            ctx.fillRect(i, 0, 2, size);
            ctx.fillRect(0, i, size, 1);
        }
    }

    if (lines) {
        ctx.strokeStyle = 'rgba(76, 57, 35, 0.18)';
        ctx.lineWidth = 1;
        for (let i = 0; i < size; i += 8) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i, size);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(0, i);
            ctx.lineTo(size, i);
            ctx.stroke();
        }
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.NearestFilter;

    return texture;
}

function paintPavementTexture(base) {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    const baseColor = new THREE.Color(base);
    const block = 8;

    ctx.fillStyle = `#${baseColor.getHexString()}`;
    ctx.fillRect(0, 0, size, size);

    for (let y = 0; y < size; y += block) {
        for (let x = 0; x < size; x += block) {
            const color = baseColor.clone();
            color.offsetHSL(0, 0, (Math.random() - 0.5) * 0.14);
            ctx.fillStyle = `#${color.getHexString()}`;
            ctx.fillRect(x, y, block, block);

            ctx.fillStyle = 'rgba(255, 255, 255, 0.04)';
            ctx.fillRect(x, y, block, 1);
            ctx.fillRect(x, y, 1, block);
        }
    }

    ctx.strokeStyle = 'rgba(210, 206, 198, 0.34)';
    ctx.lineWidth = 1;
    for (let i = 0; i < size; i += block) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i, size);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(0, i);
        ctx.lineTo(size, i);
        ctx.stroke();
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.NearestFilter;

    return texture;
}

export function createVoxelTextures(colors) {
    const cache = {};

    const make = (name, base, noise = 18, accent = null, lines = false) => {
        cache[name] = paintVoxelTexture(base, noise, accent, lines);
    };

    make('plaza', colors.plaza, 16, colors.plazaDark, true);
    cache.pavement = paintPavementTexture(colors.pavement);
    make('stone', colors.stone, 14, colors.stoneDark);
    make('stoneLight', colors.stoneLight, 10, colors.stone);
    make('white', colors.white, 10, 0xd6ccc2);
    make('ochre', colors.ochre, 10, 0xb97926);
    make('coral', colors.coral, 10, 0x9e5432);
    make('butter', colors.butter, 10, 0xbf9f47);
    make('roof', colors.roof, 16, colors.roofDark, true);
    make('roofClay', colors.roofClay, 18, colors.roofDark, true);
    make('wood', colors.wood, 18, colors.woodDark);
    make('woodDark', colors.woodDark, 12, 0x160d06);
    make('leaf', colors.leaf, 22, colors.leafDark);
    make('mountain', colors.mountain, 24, colors.mountainDark);
    make('glass', colors.glass, 8, 0xd9f6ff);
    make('trim', colors.trim, 12, colors.woodDark);
    make('iron', colors.iron, 8, 0x53606f);
    make('concrete', colors.concrete, 12, 0x8a8c8e);
    make('brick', colors.brick, 14, 0x7a3d26, true);
    make('water', colors.water, 8, 0xc7f4fb);
    make('flower', colors.flower, 14, 0xf8b8ca);
    make('cloth', colors.cloth, 12, 0x7e541d);
    make('skin', colors.skin, 10, 0xf3cb9e);
    make('shirt', colors.shirt, 12, 0x133d88);
    make('pants', colors.pants, 10, 0x1e2736);
    make('avatarSkin', colors.avatarSkin, 10, 0xf3cb9e);
    make('avatarHair', colors.avatarHair, 12, 0x160d06);
    make('avatarShirt', colors.avatarShirt, 12, 0x133d88);
    make('avatarPants', colors.avatarPants, 10, 0x1e2736);
    make('grass', colors.grass, 16, colors.grassDark);
    make('path', colors.path, 12, colors.plazaDark, true);
    make('patina', colors.patina, 12, colors.patinaDark, true);
    make('accent', colors.accent, 10, 0x8f120a);
    make('brickAccent', colors.brickAccent ?? 0xe6e0d3, 8, colors.stone);
    // Pedido del usuario: "tipo de objeto especial" estrictamente
    // colisionante — azul claro semitransparente con bordes en azul más
    // fuerte (ver `COLLISION_BARRIER_TEXTURE`/`addCollisionBarrierEdges()`
    // más abajo). Color fijo, no parte de la paleta de la plaza: no tiene
    // sentido "personalizar" el aspecto de un bloqueador invisible.
    cache[COLLISION_BARRIER_TEXTURE] = paintVoxelTexture(0x93c5fd, 6, 0x60a5fa, false);

    return cache;
}

/**
 * Pedido del usuario: un objeto "estrictamente colisionante" — semi-
 * transparente azul claro con bordes marcados en azul más fuerte, para
 * que se note en el editor que ahí hay una barrera invisible en la
 * experiencia real. `VoxelDefinitionValidator` exige en el servidor que
 * cualquier caja con esta textura sea `collidable`, así que el aspecto
 * especial y el bloqueo del paso siempre van de la mano.
 */
const COLLISION_BARRIER_TEXTURE = 'collisionBarrier';
const COLLISION_BARRIER_OPACITY = 0.35;
const COLLISION_BARRIER_EDGE_COLOR = 0x1d4ed8;

function addCollisionBarrierEdges(mesh) {
    mesh.add(new THREE.LineSegments(
        new THREE.EdgesGeometry(mesh.geometry),
        new THREE.LineBasicMaterial({ color: COLLISION_BARRIER_EDGE_COLOR }),
    ));
}

/**
 * Solo las 4 texturas del personaje (`avatarSkin/avatarHair/avatarShirt/
 * avatarPants`), para figuras de avatar independientes del motor — las
 * "personas" que `dynamic-stand-loader.js` planta junto a cada stand
 * pueden usar un preset distinto al del visitante que camina, así que no
 * pueden compartir `this.textures` del motor (colisionaría entre stands
 * con presets distintos). Generar solo 4 canvases en vez de las ~35 de
 * `createVoxelTextures` importa: puede llamarse una vez por stand.
 */
function createAvatarTextures(presetColors) {
    return {
        avatarSkin: paintVoxelTexture(presetColors.avatarSkin, 10, 0xf3cb9e),
        avatarHair: paintVoxelTexture(presetColors.avatarHair, 12, 0x160d06),
        avatarShirt: paintVoxelTexture(presetColors.avatarShirt, 12, 0x133d88),
        avatarPants: paintVoxelTexture(presetColors.avatarPants, 10, 0x1e2736),
    };
}

/**
 * IMM-020b: un "objetivo" mínimo compatible con `addVoxelBox`/
 * `buildFromDefinition`, para previsualizar un objeto suelto (sin plaza,
 * sin personaje, sin física) — por ejemplo, el panel de generación por IA en
 * el admin. Deliberadamente NO es un `VoxelPlazaEngine`: ese motor trae
 * cámara en tercera persona, control de personaje y loop de animación
 * pensados para una plaza caminable completa (ver
 * docs/architecture/personaje-inmersivo.md — esa lógica se reutiliza tal
 * cual y no se toca aquí), que sería peso muerto para una vitrina estática
 * de un solo objeto. Reutiliza el mismo diccionario de texturas
 * (`createVoxelTextures`) para que el objeto se vea igual que dentro de una
 * plaza real.
 */
export function createStandaloneVoxelTarget(palette = basePalette) {
    const world = new THREE.Group();
    const textures = createVoxelTextures(palette);
    const materialCache = new WeakMap();

    function material(texture, extra = {}) {
        return cachedMaterial(materialCache, texture, extra);
    }

    function addVoxelBox({
        x, y, z, w, h, d, texture = 'stone', group = world, castShadow = true, receiveShadow = true,
        opacity = 1, emissive = 0x000000, rotationX = 0, rotationY = 0, rotationZ = 0,
    }) {
        const isCollisionBarrier = texture === COLLISION_BARRIER_TEXTURE;
        const mesh = new THREE.Mesh(
            new THREE.BoxGeometry(w, h, d),
            material(textures[texture], {
                transparent: isCollisionBarrier || opacity < 1,
                opacity: isCollisionBarrier ? COLLISION_BARRIER_OPACITY : opacity,
                emissive,
            }),
        );

        mesh.position.set(x, y, z);
        // `rotationX`/`rotationZ` son opcionales (por defecto 0, igual que
        // antes) — solo el editor de cajas de un objeto los usa, para el
        // resto de llamadores (personaje, builders estándar, suelo) esto se
        // comporta exactamente igual que antes.
        mesh.rotation.set(rotationX, rotationY, rotationZ);
        mesh.castShadow = castShadow;
        mesh.receiveShadow = receiveShadow;
        group.add(mesh);

        if (isCollisionBarrier) {
            addCollisionBarrierEdges(mesh);
        }

        return mesh;
    }

    return { world, textures, addVoxelBox };
}

/**
 * Etiquetas de texto "X"/"Y"/"Z" junto a cada línea de color de un
 * `THREE.AxesHelper` — el helper por sí solo solo dibuja las líneas, sin
 * ningún texto. Solo para visores de edición (editor de objeto, editor
 * espacial de plaza, previsualización del generador IA) — la experiencia
 * inmersiva real nunca debe llevar esto.
 */
export function createAxisLabels(size = 4) {
    const group = new THREE.Group();

    const specs = [
        { text: 'X', color: '#ef4444', position: [size, 0, 0] },
        { text: 'Y', color: '#22c55e', position: [0, size, 0] },
        { text: 'Z', color: '#3b82f6', position: [0, 0, size] },
    ];

    specs.forEach(({ text, color, position }) => {
        const canvas = document.createElement('canvas');
        canvas.width = 64;
        canvas.height = 64;

        const ctx = canvas.getContext('2d');
        ctx.font = '500 30px sans-serif';
        ctx.fillStyle = color;
        ctx.globalAlpha = 0.75;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 32, 34);

        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
            map: new THREE.CanvasTexture(canvas),
            depthTest: false,
            transparent: true,
            opacity: 0.85,
        }));

        const spriteScale = Math.max(0.3, size * 0.09);
        sprite.scale.set(spriteScale, spriteScale, spriteScale);
        sprite.position.set(...position);
        group.add(sprite);
    });

    return group;
}

/**
 * Anatomía de 7 cajas del personaje jugable (cabeza/cabello/torso/piernas/
 * brazos) — extraída de `VoxelPlazaEngine.buildPlayer()` para que también
 * la use `buildAvatarFigure()` (figuras sueltas, ej. la "persona" que
 * `dynamic-stand-loader.js` planta junto a cada stand con el preset del
 * dueño del negocio, que puede ser distinto al del visitante que camina).
 * `addVoxelBox` es cualquier función con la forma
 * `{x,y,z,w,h,d,texture,group} => Mesh` — el método del motor
 * (`this.addVoxelBox`) y el closure de `createStandaloneVoxelTarget()`
 * cumplen la misma forma.
 *
 * `presetKey === 'mujer'` agrega dos cajas de trenza colgando detrás del
 * torso — única diferencia de geometría entre presets, a propósito acotada
 * al cabello (ver comentario de `avatarPresets` y
 * docs/architecture/personaje-inmersivo.md §9).
 */
function buildAvatarBoxes(addVoxelBox, group, presetKey) {
    const boxes = {};

    boxes.head = addVoxelBox({
        x: 0, y: 3.22, z: 0, w: 1.25, h: 1.25, d: 1.12, texture: 'avatarSkin', group,
    });

    boxes.hair = addVoxelBox({
        x: 0, y: 3.68, z: -0.08, w: 1.28, h: 0.42, d: 1.18, texture: 'avatarHair', group,
    });

    boxes.torso = addVoxelBox({
        x: 0, y: 2.02, z: 0, w: 1.58, h: 1.8, d: 0.98, texture: 'avatarShirt', group,
    });

    boxes.leftLeg = addVoxelBox({
        x: -0.38, y: 0.72, z: 0, w: 0.48, h: 1.44, d: 0.48, texture: 'avatarPants', group,
    });

    boxes.rightLeg = addVoxelBox({
        x: 0.38, y: 0.72, z: 0, w: 0.48, h: 1.44, d: 0.48, texture: 'avatarPants', group,
    });

    boxes.leftArm = addVoxelBox({
        x: -1.02, y: 2.08, z: 0, w: 0.36, h: 1.52, d: 0.36, texture: 'avatarSkin', group,
    });

    boxes.rightArm = addVoxelBox({
        x: 1.02, y: 2.08, z: 0, w: 0.36, h: 1.52, d: 0.36, texture: 'avatarSkin', group,
    });

    if (presetKey === 'mujer') {
        boxes.leftBraid = addVoxelBox({
            x: -0.52, y: 2.55, z: -0.58, w: 0.22, h: 1.5, d: 0.22, texture: 'avatarHair', group,
        });

        boxes.rightBraid = addVoxelBox({
            x: 0.52, y: 2.55, z: -0.58, w: 0.22, h: 1.5, d: 0.22, texture: 'avatarHair', group,
        });
    }

    return boxes;
}

// Como máximo 2 entradas ('hombre'/'mujer') — evita repintar las 4
// texturas del avatar por cada stand cuando una plaza tiene muchos
// negocios (relevante: cargar los stands/props de una plaza ya es el
// cuello de botella de rendimiento medido en esta sesión).
const avatarTexturesCache = new Map();

// IMM-041: caché de materiales a nivel de módulo (no por llamada) — a
// diferencia del motor principal, `buildAvatarFigure()` se invoca UNA VEZ
// POR STAND (una plaza con 20 negocios llama esto 20 veces), y las
// texturas por preset ya son las mismas instancias compartidas
// (`avatarTexturesCache` arriba) — cachear también el material evita
// crear ~8 materiales redundantes por cada figura repetida. Nunca se
// mutan estos materiales (`attachOwnerFigure()` solo posiciona la
// figura), así que compartirlos entre stands es seguro.
const avatarMaterialsCache = new WeakMap();

/**
 * Figura de avatar suelta y estática (sin animación, sin física, sin
 * cámara) — para "personas" plantadas en el mundo con un preset propio,
 * independiente del preset del visitante que camina. Mismo patrón que
 * `createStandaloneVoxelTarget()`: closure propio de `{ addVoxelBox,
 * material, textures }`, nunca `this.textures` del motor.
 */
export function buildAvatarFigure(presetKey = 'hombre') {
    const key = avatarPresets[presetKey] ? presetKey : 'hombre';

    if (!avatarTexturesCache.has(key)) {
        avatarTexturesCache.set(key, createAvatarTextures(avatarPresets[key]));
    }

    const textures = avatarTexturesCache.get(key);

    function material(texture, extra = {}) {
        return cachedMaterial(avatarMaterialsCache, texture, extra);
    }

    function addVoxelBox({
        x, y, z, w, h, d, texture, group,
        castShadow = true, receiveShadow = true, opacity = 1, emissive = 0x000000,
    }) {
        const mesh = new THREE.Mesh(
            new THREE.BoxGeometry(w, h, d),
            material(textures[texture], { transparent: opacity < 1, opacity, emissive }),
        );
        mesh.position.set(x, y, z);
        mesh.castShadow = castShadow;
        mesh.receiveShadow = receiveShadow;
        group.add(mesh);

        return mesh;
    }

    const avatar = new THREE.Group();
    const body = new THREE.Group();
    avatar.add(body);

    const shadow = new THREE.Mesh(
        new THREE.CircleGeometry(1.2, 20),
        new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.18 }),
    );
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = 0.03;
    avatar.add(shadow);

    buildAvatarBoxes(addVoxelBox, body, key);

    return avatar;
}

/**
 * Motor de la escena: cámara, luces, texturas, personaje, física y
 * loop de animación. No sabe nada de una plaza en particular — recibe
 * su `layout` (arreglo declarativo) y sus `builders` propios en
 * `start()`.
 */
export class VoxelPlazaEngine {
    constructor({
        container,
        lockTrigger = null,
        palette = basePalette,
        avatarPreset = 'hombre',
        reducedMotion = false,
        // IMM-040: sin valor explícito, se mantiene el comportamiento de
        // siempre (pixelRatioCap 2, sombras encendidas, mapa de sombra
        // 2048, sin recorte adicional de niebla) — pasar `quality` es
        // aditivo, ningún llamador existente se ve afectado si no lo hace.
        quality = { pixelRatioCap: 2, shadows: true, shadowMapSize: 2048, fogFar: Infinity },
        // IMM-040: distinto de `quality` de arriba (los valores ya
        // resueltos que aplica el renderer) — este es el selector manual
        // del panel técnico, reenviado tal cual a `attachPerfMonitor()`.
        qualityControl = null,
        fog = { color: 0xb6d7f3, near: 78, far: 260 },
        groundSize = 300,
        groundTexture = 'grass',
        skyColor = 0x87ceeb,
        playerStart = { x: 0, y: 0, z: 0 },
        playerFacing = 0,
        movementBounds = null,
        player = {},
        camera = {},
    }) {
        if (!container) {
            throw new Error('VoxelPlazaEngine requiere un contenedor.');
        }

        this.quality = quality;
        fog = { ...fog, far: Math.min(fog.far, quality.fogFar) };

        this.container = container;
        this.lockTrigger = lockTrigger;
        this.collisions = [];
        this.animatedActors = [];
        this.updateCallbacks = [];
        this.clock = new THREE.Clock();
        this.groundSize = groundSize;
        this.groundTexture = groundTexture;
        this.movementBounds = this.normalizeMovementBounds(movementBounds);

        this.movement = { forward: false, backward: false, left: false, right: false, jump: false, sprint: false };

        this.controls = {
            isLocked: false,
            isDragging: false,
            lastX: 0,
            lastY: 0,
            yaw: playerFacing,
            pitch: -0.16,
            minPitch: -1.05,
            maxPitch: 0.24,
            mouseSensitivity: 0.0026,
            // Multiplicador aparte del de mouse: un arrastre de dedo cubre
            // muchos más píxeles que un `movementX` de mouse, y el usuario
            // final lo puede ajustar desde el menú de "Configuración de
            // pantalla" del header (`display-settings-panel.js`), que lee/
            // escribe este mismo `controls.touchSensitivity` y llama a
            // `persistTouchSensitivity()` de aquí abajo.
            touchSensitivity: this.loadTouchSensitivity(),
        };

        this.playerState = {
            moveSpeed: 10.6,
            sprintSpeed: 18.6,
            acceleration: 40,
            airAcceleration: 14,
            drag: 18,
            gravity: 28,
            jumpVelocity: 10.9,
            velocity: new THREE.Vector3(),
            radius: 0.8,
            height: 3.95,
            feetY: playerStart.y ?? 0,
            onGround: true,
            turnSpeed: 0.22,
            inputSmoothing: 0.18,
            jumpCooldown: 0,
            strafeFactor: 0.9,
            backpedalFactor: 0.78,
            rotationVelocity: 0,
            ...player,
        };

        this.cameraState = {
            distance: 15.6,
            height: 6.15,
            shoulder: 0.9,
            smoothing: 0.16,
            sprintDistance: 17.1,
            sprintHeight: 6.45,
            target: new THREE.Vector3(),
            ...camera,
        };

        this.scene = new THREE.Scene();
        // Pedido del usuario: niebla configurable por plaza (`fog.enabled`
        // en falso la apaga del todo) — sin esa clave, el comportamiento de
        // siempre se mantiene igual.
        if (fog.enabled !== false) {
            this.scene.fog = new THREE.Fog(fog.color, fog.near, fog.far);
        }

        this.camera = new THREE.PerspectiveCamera(54, window.innerWidth / window.innerHeight, 0.1, 1000);

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, this.quality.pixelRatioCap));
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.shadowMap.enabled = this.quality.shadows;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.renderer.setClearColor(skyColor, 1);
        this.container.appendChild(this.renderer.domElement);

        this.world = new THREE.Group();
        this.scene.add(this.world);

        this.textures = createVoxelTextures(palette);
        // IMM-041: caché por instancia de motor (no a nivel de módulo,
        // porque cada plaza usa su propia `palette`/texturas — cachear
        // entre plazas distintas no aportaría nada y complicaría liberar
        // memoria al cambiar de escena).
        this.materialCache = new WeakMap();
        // Independiente de `palette`: solo decide si `buildPlayer()` agrega
        // las cajas de trenza del preset 'mujer' (ver `buildAvatarBoxes`),
        // no qué colores usa (eso ya lo trae `palette` mezclado con
        // `avatarPresets[...]` por quien construye el motor).
        this.avatarPreset = avatarPreset;
        this.reducedMotion = reducedMotion;
        this.perf = attachPerfMonitor({ renderer: this.renderer, label: 'Plaza voxel', quality: qualityControl });

        this.setupLights();
        this.buildGround();

        this.player = this.buildPlayer();
        const safePlayerStart = this.clampPositionToMovementBounds(new THREE.Vector3(
            playerStart.x ?? 0,
            this.playerState.feetY,
            playerStart.z ?? 0,
        ));
        this.player.position.copy(safePlayerStart);
        this.player.rotation.y = playerFacing;
        this.scene.add(this.player);

        this.bindInput();
        this.bindMobileControls();
    }

    setupLights() {
        const ambient = new THREE.HemisphereLight(0xe8f5ff, 0xb89262, 1.65);
        this.scene.add(ambient);

        // Público (`this.sun`, no una const local) para que una plaza pueda
        // afinar su mapa de sombras (ej. una plaza muy pesada en polígonos
        // puede necesitar un `mapSize` menor) sin duplicar todo el setup de
        // luces.
        this.sun = new THREE.DirectionalLight(0xfff2d0, 3.1);
        this.sun.position.set(55, 78, 36);
        // IMM-040: `castShadow` sigue el mismo interruptor que
        // `renderer.shadowMap.enabled` — con sombras apagadas por calidad,
        // no tiene sentido que la luz siga calculando su mapa de sombra.
        this.sun.castShadow = this.quality.shadows;
        this.sun.shadow.mapSize.set(this.quality.shadowMapSize, this.quality.shadowMapSize);
        this.sun.shadow.camera.left = -120;
        this.sun.shadow.camera.right = 120;
        this.sun.shadow.camera.top = 120;
        this.sun.shadow.camera.bottom = -120;
        this.scene.add(this.sun);

        const fillLight = new THREE.DirectionalLight(0xc5e7ff, 0.55);
        fillLight.position.set(-40, 28, -18);
        this.scene.add(fillLight);
    }

    buildGround() {
        this.addVoxelBox({
            x: 0,
            y: -2,
            z: 0,
            w: this.groundSize,
            h: 4,
            d: this.groundSize,
            texture: this.groundTexture,
            castShadow: false,
        });
    }

    material(texture, extra = {}) {
        return cachedMaterial(this.materialCache, texture, extra);
    }

    addCollisionBox(x, y, z, w, h, d) {
        this.collisions.push(new THREE.Box3().setFromCenterAndSize(
            new THREE.Vector3(x, y, z),
            new THREE.Vector3(w, h, d),
        ));
    }

    addRoundPlanterCollision(x, z, radius, height = 1.6) {
        this.addCollisionBox(x, height / 2, z, radius * 1.85, height, radius * 1.85);
    }

    syncObjectCollision(object, enabled = true) {
        if (!object?.userData || !Array.isArray(this.collisions)) {
            return;
        }

        const previousBox = object.userData.collisionBox;

        if (previousBox) {
            this.collisions = this.collisions.filter((box) => box !== previousBox);
            object.userData.collisionBox = null;
        }

        if (!enabled) {
            return;
        }

        object.updateWorldMatrix(true, true);

        const box = new THREE.Box3().setFromObject(object);

        if (box.isEmpty()) {
            return;
        }

        object.userData.collisionBox = box;
        this.collisions.push(box);
    }

    normalizeMovementBounds(bounds) {
        if (!bounds || typeof bounds !== 'object') {
            return null;
        }

        const minX = Number(bounds.minX);
        const maxX = Number(bounds.maxX);
        const minZ = Number(bounds.minZ);
        const maxZ = Number(bounds.maxZ);

        if ([minX, maxX, minZ, maxZ].some((value) => Number.isNaN(value))) {
            return null;
        }

        return {
            minX: Math.min(minX, maxX),
            maxX: Math.max(minX, maxX),
            minZ: Math.min(minZ, maxZ),
            maxZ: Math.max(minZ, maxZ),
        };
    }

    clampPositionToMovementBounds(position) {
        if (!this.movementBounds) {
            return position;
        }

        const safePosition = position.clone();
        const radius = this.playerState.radius;
        const minX = this.movementBounds.minX + radius;
        const maxX = this.movementBounds.maxX - radius;
        const minZ = this.movementBounds.minZ + radius;
        const maxZ = this.movementBounds.maxZ - radius;

        safePosition.x = THREE.MathUtils.clamp(
            safePosition.x,
            Math.min(minX, maxX),
            Math.max(minX, maxX),
        );
        safePosition.z = THREE.MathUtils.clamp(
            safePosition.z,
            Math.min(minZ, maxZ),
            Math.max(minZ, maxZ),
        );

        return safePosition;
    }

    addVoxelBox({
        x,
        y,
        z,
        w,
        h,
        d,
        texture = 'stone',
        group = this.world,
        collidable = false,
        castShadow = true,
        receiveShadow = true,
        opacity = 1,
        emissive = 0x000000,
        rotationX = 0,
        rotationY = 0,
        rotationZ = 0,
    }) {
        const isCollisionBarrier = texture === COLLISION_BARRIER_TEXTURE;
        const mesh = new THREE.Mesh(
            new THREE.BoxGeometry(w, h, d),
            this.material(this.textures[texture], {
                transparent: isCollisionBarrier || opacity < 1,
                opacity: isCollisionBarrier ? COLLISION_BARRIER_OPACITY : opacity,
                emissive,
            }),
        );

        mesh.position.set(x, y, z);
        // `rotationX`/`rotationZ` son opcionales (por defecto 0, igual que
        // antes) — el resto de llamadores (personaje, builders estándar,
        // suelo) nunca los usan, así que esto no cambia nada para ellos.
        // Bug real reportado por el usuario: el editor de objeto (3D) ya
        // rota libre en los 3 ejes (`createStandaloneVoxelTarget` de más
        // abajo), pero este método — el que usa `buildFromDefinition()`
        // para instanciar el objeto en una plaza real — solo aplicaba
        // `rotationY`, así que cualquier caja rotada en X/Z se veía
        // "derecha" (sin su rotación) apenas se colocaba en la plaza.
        mesh.rotation.set(rotationX, rotationY, rotationZ);
        mesh.castShadow = castShadow;
        mesh.receiveShadow = receiveShadow;
        group.add(mesh);

        if (isCollisionBarrier) {
            addCollisionBarrierEdges(mesh);
            // Pedido del usuario: visible mientras se edita/coloca la
            // plaza (para saber dónde queda la barrera), pero invisible en
            // la experiencia inmersiva real — `dynamic-stand-loader.js` la
            // apaga buscando esta marca, sin tocar `this.collisions`.
            mesh.userData.isCollisionBarrier = true;
        }

        if (collidable) {
            // Las colisiones estáticas deben usar coordenadas de mundo: muchos
            // edificios viven dentro de grupos rotados alrededor de la plaza.
            mesh.updateWorldMatrix(true, false);
            const box = new THREE.Box3().setFromObject(mesh);
            // Pedido del usuario: "la barrera permite que el personaje
            // pase" — bug real. `renderObjectByPriority()` construye el
            // objeto (con esta caja ya colisionando) y SOLO DESPUÉS le
            // aplica el escalado del prop (`applyScaleToObject`) — si el
            // admin estira la barrera con el gizmo de Escalar, la malla
            // visible crecía pero esta caja de colisión, calculada antes
            // de escalar, se quedaba con el tamaño original. Guardar la
            // referencia acá permite que `refreshBoxCollisions()` la
            // recalcule después de aplicar el escalado, sin duplicarla.
            mesh.userData.collisionBox = box;
            this.collisions.push(box);
        }

        return mesh;
    }

    /**
     * Recalcula en el sitio (sin duplicar entradas) la caja de colisión de
     * cada malla colisionable dentro de `root` — ver el comentario de más
     * arriba en `addVoxelBox()`. Se llama después de aplicar cualquier
     * transform externo (el escalado de un prop) que `addVoxelBox` no
     * pudo tener en cuenta porque todavía no existía en ese momento.
     */
    refreshBoxCollisions(root) {
        root.traverse((child) => {
            if (!child.isMesh || !child.userData.collisionBox) {
                return;
            }

            child.updateWorldMatrix(true, false);
            child.userData.collisionBox.setFromObject(child);
        });
    }

    createColorTexture(hex) {
        const key = `swatch-${hex.toString(16)}`;

        if (this.textures[key]) {
            return key;
        }

        const canvas = document.createElement('canvas');
        canvas.width = 8;
        canvas.height = 8;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = `#${hex.toString(16).padStart(6, '0')}`;
        ctx.fillRect(0, 0, 8, 8);

        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.magFilter = THREE.NearestFilter;
        texture.minFilter = THREE.NearestFilter;
        this.textures[key] = texture;

        return key;
    }

    buildPlayer() {
        const avatar = new THREE.Group();

        avatar.userData.body = new THREE.Group();
        avatar.userData.body.position.y = 0;
        avatar.add(avatar.userData.body);

        avatar.userData.shadow = new THREE.Mesh(
            new THREE.CircleGeometry(1.2, 20),
            new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.18 }),
        );
        avatar.userData.shadow.rotation.x = -Math.PI / 2;
        avatar.userData.shadow.position.y = 0.03;
        avatar.add(avatar.userData.shadow);

        Object.assign(
            avatar.userData,
            buildAvatarBoxes(this.addVoxelBox.bind(this), avatar.userData.body, this.avatarPreset),
        );

        return avatar;
    }

    createNpc(x, z, shirtColor, seed = 0) {
        const group = new THREE.Group();
        group.position.set(x, 0, z);
        this.world.add(group);

        const body = new THREE.Group();
        group.add(body);

        const shirtTexture = this.createColorTexture(shirtColor);

        this.addVoxelBox({ x: 0, y: 1.7, z: 0, w: 1.02, h: 2.18, d: 0.8, texture: shirtTexture, group: body });
        this.addVoxelBox({ x: 0, y: 3.14, z: 0, w: 0.94, h: 0.98, d: 0.72, texture: 'skin', group: body });
        this.addVoxelBox({ x: -0.25, y: 0.5, z: 0, w: 0.3, h: 1, d: 0.3, texture: 'wood', group: body });
        this.addVoxelBox({ x: 0.25, y: 0.5, z: 0, w: 0.3, h: 1, d: 0.3, texture: 'wood', group: body });

        this.animatedActors.push({ group, body, phase: seed, drift: 0.16 + (seed % 3) * 0.05 });
    }

    /**
     * Genera N posiciones a lo largo de una línea recta sin que la
     * plaza tenga que escribir cada x/z a mano.
     */
    row({ from, step, count, build }) {
        const entries = [];

        for (let i = 0; i < count; i += 1) {
            const pos = { x: from.x + step.x * i, z: from.z + step.z * i };
            entries.push(build(i, pos));
        }

        return entries;
    }

    /** Genera N posiciones repartidas en un anillo alrededor de un centro. */
    ring({ center, radius, count, startAngle = 0, build }) {
        const entries = [];

        for (let i = 0; i < count; i += 1) {
            const angle = startAngle + (Math.PI * 2 * i) / count;
            const pos = { x: center.x + Math.cos(angle) * radius, z: center.z + Math.sin(angle) * radius, angle };
            entries.push(build(i, pos));
        }

        return entries;
    }

    /**
     * Genera posiciones recorriendo el perímetro de un rectángulo
     * (útil para casas/arcadas rodeando toda una plaza sin repetir la
     * misma llamada cuatro veces con coordenadas distintas).
     */
    rectPerimeter({ x, z, width, depth, step, build }) {
        const entries = [];
        const halfW = width / 2;
        const halfD = depth / 2;
        const sides = [
            { from: { x: x - halfW, z: z - halfD }, to: { x: x + halfW, z: z - halfD }, rotation: 0 },
            { from: { x: x + halfW, z: z - halfD }, to: { x: x + halfW, z: z + halfD }, rotation: -Math.PI / 2 },
            { from: { x: x + halfW, z: z + halfD }, to: { x: x - halfW, z: z + halfD }, rotation: Math.PI },
            { from: { x: x - halfW, z: z + halfD }, to: { x: x - halfW, z: z - halfD }, rotation: Math.PI / 2 },
        ];

        sides.forEach((side, sideIndex) => {
            const length = Math.hypot(side.to.x - side.from.x, side.to.z - side.from.z);
            const count = Math.max(1, Math.round(length / step));

            for (let i = 0; i < count; i += 1) {
                const t = (i + 0.5) / count;
                const pos = {
                    x: side.from.x + (side.to.x - side.from.x) * t,
                    z: side.from.z + (side.to.z - side.from.z) * t,
                };
                entries.push(build(sideIndex, i, pos, side.rotation));
            }
        });

        return entries;
    }

    /**
     * Coloca un arreglo declarativo de elementos usando un registro de
     * builders `{ tipo: (engine, opciones) => void }`. Es la pieza que
     * reemplaza las decenas de llamadas sueltas y con coordenadas
     * repetidas a mano del primer lab: una plaza describe QUÉ va DÓNDE
     * como datos, y el motor se encarga de construirlo.
     */
    place(entries, builders) {
        entries.flat().forEach((entry) => {
            if (!entry) {
                return;
            }

            if (entry.type === 'custom') {
                entry.build(this, entry);
                return;
            }

            const builder = builders[entry.type];

            if (!builder) {
                console.warn(`[voxel-plaza-engine] Tipo de elemento desconocido: "${entry.type}".`);
                return;
            }

            builder(this, entry);
        });
    }

    getPlayerBounds(position) {
        const centerY = position.y + this.playerState.height / 2;
        return new THREE.Box3().setFromCenterAndSize(
            new THREE.Vector3(position.x, centerY, position.z),
            new THREE.Vector3(this.playerState.radius * 2, this.playerState.height, this.playerState.radius * 2),
        );
    }

    collides(position) {
        const bounds = this.getPlayerBounds(position);
        return this.collisions.some((box) => box.intersectsBox(bounds));
    }

    updateActors(time) {
        // IMM-042: con movimiento reducido activo, los NPCs se quedan en
        // su pose base — es la única animación puramente decorativa que
        // se apaga (nunca updatePlayer/updateCamera/updatePlayerAnimation).
        if (this.reducedMotion) {
            return;
        }

        this.animatedActors.forEach((actor) => {
            actor.body.position.y = Math.sin(time * actor.drift + actor.phase) * 0.06;
            actor.group.rotation.y = Math.sin(time * 0.25 + actor.phase) * 0.2;
        });
    }

    updatePlayerAnimation(delta, speedRatio) {
        const cycle = performance.now() * 0.012;
        const sprintBoost = this.movement.sprint ? 1.28 : 1;
        const swing = speedRatio > 0.03 ? Math.sin(cycle * sprintBoost) * 0.62 * speedRatio : 0;
        const bounce = speedRatio > 0.03 ? Math.abs(Math.sin(cycle * sprintBoost)) * 0.11 * speedRatio : 0;

        this.player.userData.body.position.y = bounce;
        this.player.userData.leftArm.rotation.x = swing;
        this.player.userData.rightArm.rotation.x = -swing;
        this.player.userData.leftLeg.rotation.x = -swing;
        this.player.userData.rightLeg.rotation.x = swing;

        if (!this.playerState.onGround) {
            this.player.userData.leftArm.rotation.x = -0.34;
            this.player.userData.rightArm.rotation.x = -0.34;
            this.player.userData.leftLeg.rotation.x = 0.18;
            this.player.userData.rightLeg.rotation.x = -0.18;
        }

        this.player.userData.body.rotation.y = THREE.MathUtils.damp(
            this.player.userData.body.rotation.y,
            speedRatio > 0.03 ? Math.sin(performance.now() * 0.004) * 0.08 * speedRatio : 0,
            8,
            delta,
        );
        this.player.userData.shadow.scale.setScalar(1 - Math.min(0.25, this.player.position.y * 0.04));
    }

    updatePlayer(delta) {
        const input = new THREE.Vector3();
        const forward = new THREE.Vector3(Math.sin(this.controls.yaw), 0, Math.cos(this.controls.yaw));
        const right = new THREE.Vector3(Math.cos(this.controls.yaw), 0, -Math.sin(this.controls.yaw));

        if (this.movement.forward) input.z -= 1;
        if (this.movement.backward) input.z += 1;
        if (this.movement.left) input.x -= 1;
        if (this.movement.right) input.x += 1;

        const hasInput = input.lengthSq() > 0;
        const desiredVelocity = new THREE.Vector3();

        if (hasInput) {
            input.normalize();
            const speedMultiplier = this.movement.sprint && !this.movement.backward
                ? this.playerState.sprintSpeed
                : this.playerState.moveSpeed;
            const directionalSpeed = (input.z > 0 ? this.playerState.backpedalFactor : 1)
                * (input.x !== 0 && input.z === 0 ? this.playerState.strafeFactor : 1);

            desiredVelocity
                .copy(right)
                .multiplyScalar(input.x)
                .addScaledVector(forward, input.z)
                .normalize()
                .multiplyScalar(speedMultiplier * directionalSpeed);

            const acceleration = this.playerState.onGround ? this.playerState.acceleration : this.playerState.airAcceleration;
            this.playerState.velocity.x = THREE.MathUtils.damp(this.playerState.velocity.x, desiredVelocity.x, acceleration, delta);
            this.playerState.velocity.z = THREE.MathUtils.damp(this.playerState.velocity.z, desiredVelocity.z, acceleration, delta);

            const facingAngle = Math.atan2(desiredVelocity.x, desiredVelocity.z);
            this.player.rotation.y = THREE.MathUtils.damp(this.player.rotation.y, facingAngle, 10, delta * (this.playerState.turnSpeed * 10));
        } else {
            this.playerState.velocity.x = THREE.MathUtils.damp(this.playerState.velocity.x, 0, this.playerState.drag, delta);
            this.playerState.velocity.z = THREE.MathUtils.damp(this.playerState.velocity.z, 0, this.playerState.drag, delta);
        }

        this.playerState.jumpCooldown = Math.max(0, this.playerState.jumpCooldown - delta);

        if (this.movement.jump && this.playerState.onGround && this.playerState.jumpCooldown === 0) {
            this.playerState.velocity.y = this.playerState.jumpVelocity;
            this.playerState.onGround = false;
            this.playerState.jumpCooldown = 0.22;
        }

        this.playerState.velocity.y -= this.playerState.gravity * delta;

        const next = this.player.position.clone();
        next.x += this.playerState.velocity.x * delta;
        this.clampPositionToMovementBounds(next);
        if (!this.collides(next)) {
            this.player.position.x = next.x;
        } else {
            this.playerState.velocity.x *= 0.18;
        }

        next.copy(this.player.position);
        next.z += this.playerState.velocity.z * delta;
        this.clampPositionToMovementBounds(next);
        if (!this.collides(next)) {
            this.player.position.z = next.z;
        } else {
            this.playerState.velocity.z *= 0.18;
        }

        this.player.position.y += this.playerState.velocity.y * delta;

        if (this.player.position.y <= this.playerState.feetY) {
            this.player.position.y = this.playerState.feetY;
            this.playerState.velocity.y = 0;
            this.playerState.onGround = true;
        } else {
            this.playerState.onGround = false;
        }

        const horizontalSpeed = new THREE.Vector2(this.playerState.velocity.x, this.playerState.velocity.z).length() / this.playerState.moveSpeed;
        this.updatePlayerAnimation(delta, Math.min(horizontalSpeed, 1));
    }

    updateCamera() {
        const yawVector = new THREE.Vector3(Math.sin(this.controls.yaw), 0, Math.cos(this.controls.yaw));
        const desiredDistance = this.movement.sprint ? this.cameraState.sprintDistance : this.cameraState.distance;
        const desiredHeight = this.movement.sprint ? this.cameraState.sprintHeight : this.cameraState.height;
        const desiredPosition = this.player.position.clone().add(
            new THREE.Vector3(
                yawVector.x * desiredDistance + Math.cos(this.controls.yaw) * this.cameraState.shoulder,
                desiredHeight + Math.sin(-this.controls.pitch) * 2.1,
                yawVector.z * desiredDistance - Math.sin(this.controls.yaw) * this.cameraState.shoulder,
            ),
        );

        this.camera.position.lerp(desiredPosition, this.cameraState.smoothing);

        this.cameraState.target.lerp(this.player.position.clone().add(new THREE.Vector3(0, 3.35, 0)), 0.22);
        this.camera.lookAt(this.cameraState.target);
    }

    bindInput() {
        const pointerLockTarget = this.renderer.domElement;
        this.pointerLockTarget = pointerLockTarget;

        const lockPointer = () => pointerLockTarget.requestPointerLock?.();

        const onPointerLockChange = () => {
            this.controls.isLocked = document.pointerLockElement === pointerLockTarget;
            this.container.classList.toggle('is-locked', this.controls.isLocked);
        };

        const onMouseMove = (event) => {
            if (!this.controls.isLocked && !this.controls.isDragging) {
                return;
            }

            // Safari expuso `movementX`/`movementY` con captura de mouse
            // bajo el prefijo `webkitMovementX`/`webkitMovementY` durante
            // mucho tiempo (el estándar sin prefijo puede llegar en 0/
            // undefined ahí) — sin este respaldo, la cámara quedaba
            // completamente inmóvil en pointer lock en Safari aunque el
            // cursor sí se ocultara (bug real reportado por el usuario).
            const movementX = event.movementX || event.webkitMovementX || 0;
            const movementY = event.movementY || event.webkitMovementY || 0;

            const deltaX = this.controls.isLocked ? movementX : event.clientX - this.controls.lastX;
            const deltaY = this.controls.isLocked ? movementY : event.clientY - this.controls.lastY;

            this.controls.lastX = event.clientX;
            this.controls.lastY = event.clientY;
            this.controls.yaw -= deltaX * this.controls.mouseSensitivity;
            this.controls.pitch -= deltaY * this.controls.mouseSensitivity * 0.88;
            this.controls.pitch = THREE.MathUtils.clamp(this.controls.pitch, this.controls.minPitch, this.controls.maxPitch);
        };

        const onMouseDown = (event) => {
            this.controls.isDragging = true;
            this.controls.lastX = event.clientX;
            this.controls.lastY = event.clientY;
        };

        const onMouseUp = () => {
            this.controls.isDragging = false;
        };

        // Arrastrar el dedo en la pantalla (fuera del stick/botones, que
        // capturan su propio puntero) mueve la cámara igual que arrastrar
        // el mouse — mismo cálculo de `onMouseMove()` vía `clientX/Y`, sin
        // depender de pointer lock (que no aplica en táctil).
        const onTouchLookStart = (event) => {
            if (event.pointerType !== 'touch') return;
            this.controls.isDragging = true;
            this.controls.lastX = event.clientX;
            this.controls.lastY = event.clientY;
        };

        const onTouchLookMove = (event) => {
            if (event.pointerType !== 'touch' || !this.controls.isDragging) return;
            event.preventDefault();

            const deltaX = event.clientX - this.controls.lastX;
            const deltaY = event.clientY - this.controls.lastY;

            this.controls.lastX = event.clientX;
            this.controls.lastY = event.clientY;

            const sensitivity = this.controls.mouseSensitivity * this.controls.touchSensitivity;
            this.controls.yaw -= deltaX * sensitivity;
            this.controls.pitch -= deltaY * sensitivity * 0.88;
            this.controls.pitch = THREE.MathUtils.clamp(this.controls.pitch, this.controls.minPitch, this.controls.maxPitch);
        };

        const onTouchLookEnd = (event) => {
            if (event.pointerType !== 'touch') return;
            this.controls.isDragging = false;
        };

        const setMovement = (code, pressed) => {
            if (code === 'ArrowUp' || code === 'KeyW') this.movement.forward = pressed;
            if (code === 'ArrowDown' || code === 'KeyS') this.movement.backward = pressed;
            if (code === 'ArrowLeft' || code === 'KeyA') this.movement.left = pressed;
            if (code === 'ArrowRight' || code === 'KeyD') this.movement.right = pressed;
            if (code === 'Space') this.movement.jump = pressed;
            if (code === 'ShiftLeft' || code === 'ShiftRight') this.movement.sprint = pressed;
        };

        window.addEventListener('keydown', (event) => setMovement(event.code, true));
        window.addEventListener('keyup', (event) => setMovement(event.code, false));

        window.addEventListener('resize', () => {
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });

        pointerLockTarget.style.touchAction = 'none';

        pointerLockTarget.addEventListener('click', lockPointer);
        pointerLockTarget.addEventListener('mousedown', onMouseDown);
        window.addEventListener('mouseup', onMouseUp);
        window.addEventListener('mousemove', onMouseMove);
        pointerLockTarget.addEventListener('pointerdown', onTouchLookStart);
        pointerLockTarget.addEventListener('pointermove', onTouchLookMove);
        window.addEventListener('pointerup', onTouchLookEnd);
        window.addEventListener('pointercancel', onTouchLookEnd);
        document.addEventListener('pointerlockchange', onPointerLockChange);
        this.lockTrigger?.addEventListener('click', lockPointer);
    }

    /**
     * Stick virtual (mueve `this.movement.forward/backward/left/right`,
     * igual que WASD/flechas) más botones de saltar y de acción, para
     * celular/tableta. Solo se muestran en dispositivos cuyo input
     * principal es táctil (`hover: none` + `pointer: coarse`) — un
     * escritorio con pantalla táctil secundaria no los ve. No toca la
     * física/cámara: solo alimenta las mismas banderas que ya usa
     * `updateMovement()`.
     */
    bindMobileControls() {
        this.injectMobileControlsStyles();

        const wrapper = document.createElement('div');
        wrapper.className = 'vpe-mobile-controls';

        const joystick = document.createElement('div');
        joystick.className = 'vpe-joystick';

        const stick = document.createElement('div');
        stick.className = 'vpe-joystick-stick';
        joystick.appendChild(stick);

        const joystickRadius = 40;
        const deadzone = 0.32;
        let joystickPointerId = null;
        let joystickCenter = { x: 0, y: 0 };

        const resetJoystick = () => {
            stick.style.transform = 'translate(-50%, -50%)';
            this.movement.forward = false;
            this.movement.backward = false;
            this.movement.left = false;
            this.movement.right = false;
        };

        const updateJoystickFromPointer = (event) => {
            const dx = event.clientX - joystickCenter.x;
            const dy = event.clientY - joystickCenter.y;
            const distance = Math.min(Math.hypot(dx, dy), joystickRadius);
            const angle = Math.atan2(dy, dx);
            const clampedX = Math.cos(angle) * distance;
            const clampedY = Math.sin(angle) * distance;

            stick.style.transform = `translate(calc(-50% + ${clampedX}px), calc(-50% + ${clampedY}px))`;

            const nx = clampedX / joystickRadius;
            const ny = clampedY / joystickRadius;

            this.movement.forward = ny < -deadzone;
            this.movement.backward = ny > deadzone;
            this.movement.left = nx < -deadzone;
            this.movement.right = nx > deadzone;
        };

        joystick.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            joystickPointerId = event.pointerId;
            joystick.setPointerCapture(joystickPointerId);
            joystick.classList.add('is-active');
            const rect = joystick.getBoundingClientRect();
            joystickCenter = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
            updateJoystickFromPointer(event);
        });

        joystick.addEventListener('pointermove', (event) => {
            if (event.pointerId !== joystickPointerId) return;
            event.preventDefault();
            updateJoystickFromPointer(event);
        });

        const releaseJoystick = (event) => {
            if (event.pointerId !== joystickPointerId) return;
            joystickPointerId = null;
            joystick.classList.remove('is-active');
            resetJoystick();
        };

        joystick.addEventListener('pointerup', releaseJoystick);
        joystick.addEventListener('pointercancel', releaseJoystick);

        const bindHoldButton = (button, onPress, onRelease) => {
            let pointerId = null;

            button.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                pointerId = event.pointerId;
                button.setPointerCapture(pointerId);
                button.classList.add('is-active');
                onPress();
            });

            const release = (event) => {
                if (event.pointerId !== pointerId) return;
                pointerId = null;
                button.classList.remove('is-active');
                onRelease();
            };

            button.addEventListener('pointerup', release);
            button.addEventListener('pointercancel', release);
        };

        const buttons = document.createElement('div');
        buttons.className = 'vpe-action-buttons';

        const actionButton = document.createElement('button');
        actionButton.type = 'button';
        actionButton.className = 'vpe-btn vpe-btn-action';
        actionButton.setAttribute('aria-label', 'Correr');
        actionButton.textContent = '⚡';

        const jumpButton = document.createElement('button');
        jumpButton.type = 'button';
        jumpButton.className = 'vpe-btn vpe-btn-jump';
        jumpButton.setAttribute('aria-label', 'Saltar');
        jumpButton.textContent = '↑';

        bindHoldButton(jumpButton, () => {
            this.movement.jump = true;
        }, () => {
            this.movement.jump = false;
        });

        // Mismo botón de mantener presionado para correr que ya usa el
        // teclado (Shift/Mayús, ver `bindInput()`): sostenerlo activa
        // `this.movement.sprint`, soltarlo lo desactiva.
        bindHoldButton(actionButton, () => {
            this.movement.sprint = true;
        }, () => {
            this.movement.sprint = false;
        });

        buttons.appendChild(actionButton);
        buttons.appendChild(jumpButton);

        wrapper.appendChild(joystick);
        wrapper.appendChild(buttons);
        this.container.appendChild(wrapper);
    }

    loadTouchSensitivity() {
        try {
            const stored = Number.parseFloat(localStorage.getItem('vpe-touch-sensitivity'));

            return Number.isFinite(stored) ? THREE.MathUtils.clamp(stored, 0.4, 2.2) : 1;
        } catch {
            return 1;
        }
    }

    persistTouchSensitivity(value) {
        try {
            localStorage.setItem('vpe-touch-sensitivity', String(value));
        } catch {
            // Almacenamiento no disponible (ej. modo privado) — la
            // preferencia solo dura la sesión actual, sin romper nada.
        }
    }

    injectMobileControlsStyles() {
        if (document.getElementById('vpe-mobile-controls-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'vpe-mobile-controls-style';
        style.textContent = `
            .vpe-mobile-controls {
                position: fixed;
                inset: 0;
                z-index: 25;
                pointer-events: none;
                display: none;
            }

            @media (hover: none) and (pointer: coarse) {
                .vpe-mobile-controls {
                    display: block;
                }
            }

            .vpe-joystick {
                position: absolute;
                left: max(22px, env(safe-area-inset-left, 0px) + 14px);
                bottom: max(22px, env(safe-area-inset-bottom, 0px) + 14px);
                width: 108px;
                height: 108px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.28);
                background: rgba(10, 18, 33, 0.1);
                pointer-events: auto;
                touch-action: none;
                backdrop-filter: blur(10px);
                -webkit-tap-highlight-color: transparent;
                transition: background 160ms ease, border-color 160ms ease;
            }

            .vpe-joystick.is-active {
                background: rgba(10, 18, 33, 0.58);
                border-color: rgba(255, 255, 255, 0.5);
            }

            .vpe-joystick-stick {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 46px;
                height: 46px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.42);
                border: 1px solid rgba(255, 255, 255, 0.55);
                transform: translate(-50%, -50%);
                transition: background 160ms ease;
                pointer-events: none;
            }

            .vpe-joystick.is-active .vpe-joystick-stick {
                background: rgba(255, 255, 255, 0.72);
            }

            .vpe-action-buttons {
                position: absolute;
                right: max(22px, env(safe-area-inset-right, 0px) + 14px);
                bottom: max(22px, env(safe-area-inset-bottom, 0px) + 14px);
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                pointer-events: none;
            }

            .vpe-btn {
                pointer-events: auto;
                touch-action: none;
                -webkit-tap-highlight-color: transparent;
                width: 58px;
                height: 58px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.28);
                background: rgba(10, 18, 33, 0.1);
                color: rgba(255, 255, 255, 0.75);
                font-size: 1.05rem;
                display: flex;
                backdrop-filter: blur(10px);
                align-items: center;
                justify-content: center;
                transition: background 160ms ease, border-color 160ms ease, transform 120ms ease;
            }

            .vpe-btn.is-active {
                background: rgba(10, 18, 33, 0.6);
                border-color: rgba(255, 255, 255, 0.55);
                color: rgba(255, 255, 255, 0.95);
                transform: scale(0.94);
            }

            .vpe-btn-jump {
                width: 68px;
                height: 68px;
                font-size: 1.3rem;
            }

            @media (min-width: 900px) {
                .vpe-joystick {
                    width: 124px;
                    height: 124px;
                }

                .vpe-joystick-stick {
                    width: 52px;
                    height: 52px;
                }

                .vpe-btn {
                    width: 66px;
                    height: 66px;
                }

                .vpe-btn-jump {
                    width: 76px;
                    height: 76px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Registra un callback a ejecutar en cada frame, después de mover
     * cámara/personaje y antes de renderizar (ej. un HUD de coordenadas
     * propio de una plaza). Devuelve una función para des-registrarlo.
     */
    onUpdate(callback) {
        this.updateCallbacks.push(callback);

        return () => {
            this.updateCallbacks = this.updateCallbacks.filter((cb) => cb !== callback);
        };
    }

    animate() {
        // IMM-041: se guarda el id devuelto (antes se descartaba) solo
        // para poder cancelar el loop desde `dispose()` — el resto del
        // método, incluidas las llamadas a updatePlayer/updateCamera, no
        // cambia.
        this._animationFrameId = requestAnimationFrame(() => this.animate());

        const delta = Math.min(this.clock.getDelta(), 0.05);
        const time = this.clock.elapsedTime;

        this.updatePlayer(delta);
        this.updateActors(time);
        this.updateCamera();
        this.updateCallbacks.forEach((callback) => callback(delta, time));
        this.renderer.render(this.scene, this.camera);
        this.perf.sample();
    }

    /**
     * Construye el layout declarativo y arranca el loop de animación.
     * `deferSceneReady`: cuando una plaza carga assets async propios
     * (GLB/texturas fuera del layout declarativo), pasa `true` y llama tú
     * mismo a `engine.perf.markSceneReady()` cuando esos assets terminen —
     * si no, el panel técnico marcaría la escena lista antes de tiempo.
     */
    start(layout = [], builders = standardBuilders, { deferSceneReady = false } = {}) {
        this.place(layout, builders);
        this.updateCamera();

        if (!deferSceneReady) {
            this.perf.markSceneReady();
        }

        this.animate();
    }

    /**
     * IMM-041 (criterio de aceptación: "cambiar de plaza no acumula
     * escenas, texturas ni memoria"). Hoy cambiar de plaza es una recarga
     * completa de página (rutas normales, sin `wire:navigate` ni router
     * del lado del cliente entre plazas) — el navegador ya libera todo
     * esto solo, así que nada llama a este método todavía. Queda listo
     * para el día que se introduzca una transición de plaza sin recargar.
     * No toca `bindInput()`/`bindMobileControls()` (canónicas, no se
     * rediseñan): si algún día hace falta quitar esos listeners también,
     * esas dos funciones necesitarán su propio gancho de limpieza aparte.
     */
    dispose() {
        if (this._animationFrameId !== undefined) {
            cancelAnimationFrame(this._animationFrameId);
            this._animationFrameId = undefined;
        }

        this.scene.traverse((object) => {
            object.geometry?.dispose();

            const materials = Array.isArray(object.material) ? object.material : (object.material ? [object.material] : []);
            materials.forEach((material) => {
                material.map?.dispose();
                material.dispose();
            });
        });

        // Texturas del diccionario compartido (`createVoxelTextures`): se
        // liberan aparte porque no todas están necesariamente asignadas a
        // un material en la escena en este momento (paleta completa
        // generada de una vez, no todo color se usa siempre).
        Object.values(this.textures ?? {}).forEach((texture) => texture.dispose());

        this.renderer.dispose();

        if (this.renderer.domElement.parentElement === this.container) {
            this.container.removeChild(this.renderer.domElement);
        }
    }
}

// --- Piezas de construcción reutilizables ------------------------------
// Cada builder recibe `(engine, opciones)` y solo usa métodos públicos
// del motor (`addVoxelBox`, `addCollisionBox`, `createNpc`...), así que
// no dependen de estado interno de una plaza concreta.

function buildColonialHouse(engine, {
    x,
    z,
    rotation = 0,
    width = 12,
    depth = 10,
    bodyHeight = 7.8,
    paletteSwap = 'white',
    roofTexture = 'roofClay',
    shutterColor = 'leaf',
    doorOffset = 0,
    hasBalcony = false,
    windows = 3,
}) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.45, z: 0, w: width + 0.4, h: 0.9, d: depth, texture: 'stoneLight', group, collidable: true });
    engine.addVoxelBox({ x: 0, y: bodyHeight / 2 + 0.9, z: 0, w: width, h: bodyHeight, d: depth, texture: paletteSwap, group, collidable: true });
    engine.addVoxelBox({ x: 0, y: bodyHeight + 1.18, z: 0, w: width + 0.9, h: 1, d: depth + 1.1, texture: roofTexture, group });
    engine.addVoxelBox({ x: 0, y: bodyHeight + 2, z: 0, w: width + 1.7, h: 0.7, d: depth + 1.8, texture: roofTexture, group });
    engine.addVoxelBox({ x: 0, y: bodyHeight + 0.35, z: 0.12, w: width - 0.3, h: 0.34, d: depth - 0.5, texture: 'trim', group });

    const spacing = windows > 1 ? (width - 3.6) / (windows - 1) : 0;
    const startX = -((windows - 1) * spacing) / 2;

    for (let i = 0; i < windows; i += 1) {
        const wx = startX + i * spacing;
        const isDoorBay = Math.abs(wx - doorOffset) < 0.8;

        if (isDoorBay) {
            engine.addVoxelBox({ x: wx, y: 1.55, z: 5.04, w: 2.35, h: 3.1, d: 0.22, texture: 'wood', group });
            continue;
        }

        engine.addVoxelBox({ x: wx, y: 1.65, z: 5.02, w: 1.3, h: 2.5, d: 0.2, texture: 'glass', group });
        engine.addVoxelBox({ x: wx - 0.78, y: 1.65, z: 5.08, w: 0.24, h: 2.5, d: 0.12, texture: shutterColor, group });
        engine.addVoxelBox({ x: wx + 0.78, y: 1.65, z: 5.08, w: 0.24, h: 2.5, d: 0.12, texture: shutterColor, group });

        if (hasBalcony && i === Math.floor(windows / 2)) {
            engine.addVoxelBox({ x: wx, y: 5.25, z: 5.18, w: 2.8, h: 0.24, d: 0.55, texture: 'trim', group });
            engine.addVoxelBox({ x: wx, y: 5.72, z: 5.3, w: 2.8, h: 0.2, d: 0.18, texture: 'woodDark', group });
            engine.addVoxelBox({ x: wx, y: 6.05, z: 5.12, w: 1.22, h: 1.82, d: 0.18, texture: 'glass', group });

            [-0.9, -0.45, 0, 0.45, 0.9].forEach((rail) => {
                engine.addVoxelBox({ x: wx + rail, y: 5.98, z: 5.28, w: 0.1, h: 0.66, d: 0.1, texture: 'woodDark', group });
            });
        } else {
            engine.addVoxelBox({ x: wx, y: 3.25, z: 4.98, w: 1.18, h: 1.56, d: 0.18, texture: 'glass', group });
        }
    }
}

function buildArcadeRow(engine, {
    x,
    z,
    sections = 8,
    rotation = 0,
    paletteSwap = 'white',
    upperColor = 'trim',
    shutterColor = 'leaf',
    roofTexture = 'roofClay',
    baseTexture = 'stoneLight',
    depth = 8.6,
    spacing = 7.1,
    frontOffset = 4.02,
    facadeJitter = 0,
}) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    for (let i = 0; i < sections; i += 1) {
        const offset = i * spacing;
        const width = 6.5 + (i % 3) * 0.55;
        const bodyHeight = 7.2 + (i % 4) * 0.55;
        const corniceY = bodyHeight + 0.25;
        const roofBaseY = bodyHeight + 0.8;
        const facadeShift = facadeJitter === 0 ? 0 : ((i % 3) - 1) * facadeJitter;
        const upperInset = i % 2 === 0 ? 0.4 : 0.12;
        const frontZ = frontOffset + facadeShift;
        const hasBalcony = i % 4 === 1 || i % 4 === 2;
        const hasLargeDoor = i % 5 === 0;
        const shutterWidth = 0.28;
        const windowHeight = hasBalcony ? 1.95 : 1.6;
        const windowY = hasBalcony ? 5.55 : 3.2;

        engine.addVoxelBox({ x: offset, y: 0.45, z: 0, w: width + 0.3, h: 0.9, d: depth, texture: baseTexture, group, collidable: true });
        engine.addVoxelBox({ x: offset, y: bodyHeight / 2 + 0.9, z: 0, w: width, h: bodyHeight, d: depth, texture: paletteSwap, group, collidable: true });
        engine.addVoxelBox({ x: offset, y: corniceY, z: upperInset * 0.35, w: width - 0.4, h: 0.36, d: depth - 0.48, texture: upperColor, group });
        engine.addVoxelBox({ x: offset, y: roofBaseY, z: 0, w: width + 0.95, h: 0.95, d: depth + 0.85, texture: roofTexture, group });
        engine.addVoxelBox({ x: offset, y: roofBaseY + 0.92, z: 0, w: width + 1.7, h: 0.7, d: depth + 1.5, texture: roofTexture, group });

        engine.addVoxelBox({ x: offset - 2.05, y: 2.25, z: frontZ - 0.55, w: 0.62, h: 3.9, d: 0.62, texture: upperColor, group });
        engine.addVoxelBox({ x: offset + 2.05, y: 2.25, z: frontZ - 0.55, w: 0.62, h: 3.9, d: 0.62, texture: upperColor, group });
        engine.addVoxelBox({ x: offset, y: 4.15, z: frontZ - 0.55, w: 4.8, h: 0.62, d: 0.4, texture: upperColor, group });

        engine.addVoxelBox({ x: offset, y: hasLargeDoor ? 1.5 : 1.18, z: frontZ, w: hasLargeDoor ? 2.45 : 1.9, h: hasLargeDoor ? 3.02 : 2.35, d: 0.24, texture: 'wood', group });
        engine.addVoxelBox({ x: offset - 2.04, y: 1.55, z: frontZ, w: 1.02, h: 2.45, d: 0.22, texture: 'glass', group });
        engine.addVoxelBox({ x: offset + 2.04, y: 1.55, z: frontZ, w: 1.02, h: 2.45, d: 0.22, texture: 'glass', group });
        engine.addVoxelBox({ x: offset - 2.67, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset - 1.41, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 1.41, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 2.67, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });

        if (hasBalcony) {
            const balconyWidth = width - 1.55;

            engine.addVoxelBox({ x: offset, y: 5.15, z: frontZ + 0.08, w: balconyWidth, h: 0.26, d: 0.62, texture: upperColor, group });
            engine.addVoxelBox({ x: offset, y: 5.65, z: frontZ + 0.28, w: balconyWidth, h: 0.2, d: 0.18, texture: 'woodDark', group });

            for (let rail = -2; rail <= 2; rail += 1) {
                engine.addVoxelBox({ x: offset + rail * 0.78, y: 5.92, z: frontZ + 0.25, w: 0.12, h: 0.72, d: 0.12, texture: 'woodDark', group });
            }
        }

        engine.addVoxelBox({ x: offset - 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        engine.addVoxelBox({ x: offset + 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        engine.addVoxelBox({ x: offset - 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset - 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset, y: hasBalcony ? 5.95 : 3.15, z: frontZ - 0.05, w: hasBalcony ? 1.3 : 1.15, h: hasBalcony ? 1.78 : 1.45, d: 0.18, texture: i % 2 === 0 ? 'glass' : upperColor, group });
    }
}

function buildBench(engine, { x, z, rotation = 0 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 1.1, z: 0, w: 4.8, h: 0.48, d: 1.25, texture: 'trim', group });
    engine.addVoxelBox({ x: 0, y: 1.95, z: -0.52, w: 4.8, h: 0.92, d: 0.35, texture: 'trim', group });
    engine.addVoxelBox({ x: -1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
    engine.addVoxelBox({ x: 1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
}

function buildLongBench(engine, { x, z, rotation = 0, length = 16 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.9, z: 0, w: length, h: 0.9, d: 2.2, texture: 'stone', group });
    engine.addCollisionBox(x, 1.1, z, rotation === 0 ? length : 2.2, 2.2, rotation === 0 ? 2.2 : length);
}

function buildPlanter(engine, { x, z, radius = 5, tree = null, flowers = false, trunk = 19 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.52, z: 0, w: radius * 2.1, h: 1.05, d: radius * 2.1, texture: 'stone', group });
    engine.addVoxelBox({ x: 0, y: 1.24, z: 0, w: radius * 1.62, h: 0.72, d: radius * 1.62, texture: flowers ? 'flower' : 'grass', group });
    engine.addRoundPlanterCollision(x, z, radius);

    if (tree === 'palm') {
        buildPalm(engine, { x, z, trunk });
    }

    if (tree === 'tree') {
        buildTree(engine, { x, z, height: 10 });
    }
}

function buildLamp(engine, { x, z, height = 10, hanging = true }) {
    for (let i = 0; i < height; i += 1) {
        engine.addVoxelBox({ x, y: i + 0.5, z, w: 0.68, h: 1, d: 0.68, texture: 'iron', collidable: i < 3 });
    }

    engine.addVoxelBox({ x, y: height + 0.5, z, w: 1.2, h: 0.35, d: 1.2, texture: 'iron' });

    if (hanging) {
        engine.addVoxelBox({ x: x + 1.15, y: height - 0.4, z, w: 0.35, h: 0.35, d: 0.35, texture: 'iron' });
        engine.addVoxelBox({
            x: x + 1.85, y: height - 1.45, z, w: 1.2, h: 1.2, d: 1.2, texture: 'glass', opacity: 0.94, emissive: 0x111111,
        });
    }
}

function buildTree(engine, { x, z, height = 10 }) {
    for (let i = 0; i < height; i += 1) {
        engine.addVoxelBox({ x, y: i + 1, z, w: 1.1, h: 1, d: 1.1, texture: 'wood', collidable: i < 5 });
    }

    for (let lx = -3; lx <= 3; lx += 1) {
        for (let lz = -3; lz <= 3; lz += 1) {
            if (Math.abs(lx) + Math.abs(lz) > 4) {
                continue;
            }

            engine.addVoxelBox({
                x: x + lx * 1.06,
                y: height + 2 + (Math.abs(lx) + Math.abs(lz) === 0 ? 0.8 : 0),
                z: z + lz * 1.06,
                w: 1.85,
                h: 1.85,
                d: 1.85,
                texture: 'leaf',
            });
        }
    }
}

function buildPalm(engine, { x, z, trunk = 18 }) {
    for (let i = 0; i < trunk; i += 1) {
        engine.addVoxelBox({
            x, y: i + 1, z, w: 1.38, h: 1, d: 1.38, texture: i % 2 === 0 ? 'wood' : 'trim', collidable: i < 6,
        });
    }

    for (let i = 0; i < 16; i += 1) {
        const angle = (Math.PI * 2 * i) / 16;
        const radius = 4.6 + (i % 2) * 1.1;
        engine.addVoxelBox({
            x: x + Math.cos(angle) * radius, y: trunk + 2.4 + (i % 2) * 0.5, z: z + Math.sin(angle) * radius, w: 4.5, h: 1.08, d: 1.2, texture: 'leaf',
        });
    }

    for (let i = 0; i < 6; i += 1) {
        engine.addVoxelBox({
            x, y: trunk - 0.3 + i * 0.75, z, w: 3.1 - i * 0.18, h: 0.85, d: 3.1 - i * 0.18, texture: i < 2 ? 'woodDark' : 'leaf',
        });
    }
}

function buildCloud(engine, { x, y, z, scale = 1 }) {
    const cloud = new THREE.Group();
    const parts = [
        [0, 0, 0, 6.4, 1.7, 2.8],
        [4.2, 0.8, 0.4, 4.8, 1.2, 2.4],
        [-4.4, 0.75, -0.2, 5.2, 1.35, 2.4],
        [8.2, 0.25, 0.2, 3.1, 1, 1.8],
    ];

    parts.forEach(([px, py, pz, w, h, d]) => {
        engine.addVoxelBox({
            x: px * scale, y: py * scale, z: pz * scale, w: w * scale, h: h * scale, d: d * scale, texture: 'white', castShadow: false, receiveShadow: false, group: cloud,
        });
    });

    cloud.position.set(x, y, z);
    engine.scene.add(cloud);
}

function buildNpcEntry(engine, { x, z, shirtColor = 0x2869d0, seed = 0 }) {
    engine.createNpc(x, z, shirtColor, seed);
}

/**
 * Seto bajo y recortado alrededor de un rectángulo (bordea jardineras
 * rectangulares, como en la plaza de Cajicá) con una franja de flores
 * opcional pegada al interior.
 */
function buildHedgeRect(engine, { x, z, width, depth, height = 1.1, thickness = 0.9, texture = 'leaf', flowerBand = true }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    const halfW = width / 2;
    const halfD = depth / 2;

    engine.addVoxelBox({ x: 0, y: height / 2, z: -halfD + thickness / 2, w: width, h: height, d: thickness, texture, group });
    engine.addVoxelBox({ x: 0, y: height / 2, z: halfD - thickness / 2, w: width, h: height, d: thickness, texture, group });
    engine.addVoxelBox({ x: -halfW + thickness / 2, y: height / 2, z: 0, w: thickness, h: height, d: depth - thickness * 2, texture, group });
    engine.addVoxelBox({ x: halfW - thickness / 2, y: height / 2, z: 0, w: thickness, h: height, d: depth - thickness * 2, texture, group });

    if (flowerBand) {
        const bandInset = thickness * 0.55;

        engine.addVoxelBox({ x: 0, y: height * 0.62, z: -halfD + thickness + bandInset, w: width - thickness * 2, h: height * 0.32, d: bandInset, texture: 'flower', group });
        engine.addVoxelBox({ x: 0, y: height * 0.62, z: halfD - thickness - bandInset, w: width - thickness * 2, h: height * 0.32, d: bandInset, texture: 'flower', group });
    }

    engine.addCollisionBox(x, height / 2, z - halfD + thickness / 2, width, height, thickness);
    engine.addCollisionBox(x, height / 2, z + halfD - thickness / 2, width, height, thickness);
    engine.addCollisionBox(x - halfW + thickness / 2, height / 2, z, thickness, height, depth - thickness * 2);
    engine.addCollisionBox(x + halfW - thickness / 2, height / 2, z, thickness, height, depth - thickness * 2);
}

/**
 * Fuente central de plaza: base circular, tazón intermedio y remate,
 * con un plano de agua en cada nivel. Es la única pieza que usa
 * geometría cilíndrica en vez de cajas — un punto focal redondo
 * destaca mejor en medio de una plaza voxel cuadriculada.
 */
function buildFountain(engine, { x, z, radius = 6, tiers = 3 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    const stoneMat = engine.material(engine.textures.stone);
    const stoneLightMat = engine.material(engine.textures.stoneLight);
    const waterMat = engine.material(engine.textures.water, { transparent: true, opacity: 0.88 });

    const base = new THREE.Mesh(new THREE.CylinderGeometry(radius, radius * 1.06, 1, 16), stoneMat);
    base.position.y = 0.5;
    base.castShadow = true;
    base.receiveShadow = true;
    group.add(base);

    const basePool = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.88, radius * 0.88, 0.3, 16), waterMat);
    basePool.position.y = 1.05;
    group.add(basePool);

    const stem = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.22, radius * 0.3, 1.8, 12), stoneLightMat);
    stem.position.y = 1.9;
    stem.castShadow = true;
    group.add(stem);

    if (tiers > 2) {
        const midBowl = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.55, radius * 0.48, 0.7, 14), stoneMat);
        midBowl.position.y = 2.85;
        midBowl.castShadow = true;
        group.add(midBowl);

        const midPool = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.46, radius * 0.46, 0.2, 14), waterMat);
        midPool.position.y = 3.18;
        group.add(midPool);

        const stem2 = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.1, radius * 0.16, 1.1, 10), stoneLightMat);
        stem2.position.y = 3.7;
        group.add(stem2);
    }

    const crown = new THREE.Mesh(new THREE.SphereGeometry(radius * 0.18, 12, 10), stoneLightMat);
    crown.position.y = tiers > 2 ? 4.35 : 2.55;
    group.add(crown);

    for (let i = 0; i < 6; i += 1) {
        const angle = (Math.PI * 2 * i) / 6;
        const jet = new THREE.Mesh(
            new THREE.CylinderGeometry(0.05, 0.05, 1.1, 6),
            engine.material(engine.textures.water, { transparent: true, opacity: 0.6, emissive: 0x1c4b52 }),
        );
        jet.position.set(Math.cos(angle) * radius * 0.3, (tiers > 2 ? 4.9 : 3.1), Math.sin(angle) * radius * 0.3);
        group.add(jet);
    }

    engine.addCollisionBox(x, 0.6, z, radius * 2, 1.2, radius * 2);
}

/**
 * Puesto de mercado con sombrilla de rayas — el carrito colorido típico
 * de una plaza de pueblo.
 */
function buildMarketStall(engine, { x, z, rotation = 0, stripeColors = ['accent', 'butter'] }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 1.05, z: 0, w: 2.6, h: 1.1, d: 1.6, texture: 'wood', group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 1.7, z: 0, w: 2.4, h: 0.2, d: 1.5, texture: 'woodDark', group });

    const poleHeight = 2.9;
    engine.addVoxelBox({ x: 0, y: 1.8 + poleHeight / 2, z: 0, w: 0.18, h: poleHeight, d: 0.18, texture: 'iron', group });

    const canopyY = 1.8 + poleHeight;
    const segments = 8;
    const canopyRadius = 2.5;

    for (let i = 0; i < segments; i += 1) {
        const angle = (Math.PI * 2 * i) / segments;
        const nextAngle = (Math.PI * 2 * (i + 1)) / segments;
        const midAngle = (angle + nextAngle) / 2;

        engine.addVoxelBox({
            x: Math.cos(midAngle) * canopyRadius * 0.42,
            y: canopyY + 0.18,
            z: Math.sin(midAngle) * canopyRadius * 0.42,
            w: canopyRadius * 0.62,
            h: 0.32,
            d: canopyRadius * 0.62,
            texture: stripeColors[i % stripeColors.length],
            rotationY: midAngle,
            group,
        });
    }

    engine.addVoxelBox({ x: 0, y: canopyY + 0.5, z: 0, w: 0.5, h: 0.5, d: 0.5, texture: stripeColors[0], group });
}

/**
 * Vehículo voxel muy simplificado (camioneta/carro de barrio) para
 * bordear la calle perimetral de la plaza, como en la foto de
 * referencia de Cajicá.
 */
function buildVehicle(engine, { x, z, rotation = 0, bodyColor = 'shirt', vehicleType = 'car' }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    const isPickup = vehicleType === 'pickup';
    const length = isPickup ? 4.6 : 4.1;
    const cabinLength = isPickup ? 2 : 3.2;

    engine.addVoxelBox({ x: 0, y: 0.55, z: 0, w: 1.7, h: 0.7, d: length, texture: bodyColor, group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 1.05, z: -(length - cabinLength) / 2, w: 1.6, h: 0.6, d: cabinLength, texture: bodyColor, group });
    engine.addVoxelBox({ x: 0, y: 1.05, z: -(length - cabinLength) / 2, w: 1.62, h: 0.36, d: cabinLength - 0.3, texture: 'glass', group });

    if (isPickup) {
        engine.addVoxelBox({ x: 0, y: 0.9, z: (length - cabinLength) / 2 + 0.2, w: 1.55, h: 0.32, d: length - cabinLength - 0.3, texture: 'woodDark', group });
    }

    [[-0.75, -length / 2 + 0.7], [0.75, -length / 2 + 0.7], [-0.75, length / 2 - 0.7], [0.75, length / 2 - 0.7]].forEach(([wx, wz]) => {
        engine.addVoxelBox({ x: wx, y: 0.28, z: wz, w: 0.42, h: 0.42, d: 0.42, texture: 'woodDark', group });
    });
}

/** Estatua/monumento sobre pedestal para un rincón de la plaza. */
function buildStatue(engine, { x, z, height = 6, figureTexture = 'patina' }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.4, z: 0, w: 3.4, h: 0.8, d: 3.4, texture: 'stone', group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 1.3, z: 0, w: 2.3, h: 1, d: 2.3, texture: 'stoneLight', group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 1.3 + (height - 2.4) / 2 + 1, z: 0, w: 1.1, h: height - 2.4, d: 1.1, texture: figureTexture, group, collidable: true });
    engine.addVoxelBox({ x: 0, y: height + 0.3, z: 0, w: 0.7, h: 0.6, d: 0.7, texture: 'stoneLight', group });
}

/**
 * IMM-020 del TODO inmersivo: una de las tres plantillas de stand del
 * catálogo (`ImmersiveObjectTemplate.builder_key = 'standBooth'`) — caseta
 * cerrada de madera con mostrador y letrero al frente (+Z, su eje frontal).
 * Huella normalizada: ~4.2 × 3.8.
 */
function buildStandBooth(engine, {
    x, z, rotation = 0, wallTexture = 'wood', roofTexture = 'roofClay', signColor = 0xd7352a,
}) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.1, z: 0, w: 3.6, h: 0.2, d: 3.2, texture: 'stoneLight', group });
    engine.addVoxelBox({ x: 0, y: 1.3, z: -1.4, w: 3.6, h: 2.4, d: 0.3, texture: wallTexture, group, collidable: true });
    engine.addVoxelBox({ x: -1.65, y: 1.3, z: 0, w: 0.3, h: 2.4, d: 3.2, texture: wallTexture, group, collidable: true });
    engine.addVoxelBox({ x: 1.65, y: 1.3, z: 0, w: 0.3, h: 2.4, d: 3.2, texture: wallTexture, group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 1, z: 1.4, w: 3.2, h: 1, d: 0.5, texture: wallTexture, group, collidable: true });
    engine.addVoxelBox({ x: 0, y: 2.6, z: 0, w: 4.2, h: 0.3, d: 3.8, texture: roofTexture, group });
    engine.addVoxelBox({ x: 0, y: 2.9, z: 0, w: 3.6, h: 0.4, d: 3.2, texture: roofTexture, group });

    const signTexture = engine.createColorTexture(signColor);
    engine.addVoxelBox({ x: 0, y: 2.35, z: 1.66, w: 2.2, h: 0.5, d: 0.1, texture: signTexture, group });
}

/**
 * IMM-020 del TODO inmersivo: segunda plantilla de stand
 * (`builder_key = 'standTable'`) — mesa exhibidora al aire libre con
 * mantel y letrero de fondo, sin paredes. Eje frontal +Z. Huella
 * normalizada: ~3.2 × 2.4.
 */
function buildStandTable(engine, {
    x, z, rotation = 0, clothColor = 'cloth', frameTexture = 'iron',
}) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.95, z: 0, w: 3.2, h: 0.15, d: 1.6, texture: 'stoneLight', group, collidable: true });

    [[-1.4, -0.65], [1.4, -0.65], [-1.4, 0.65], [1.4, 0.65]].forEach(([lx, lz]) => {
        engine.addVoxelBox({ x: lx, y: 0.45, z: lz, w: 0.15, h: 0.9, d: 0.15, texture: frameTexture, group });
    });

    engine.addVoxelBox({ x: 0, y: 0.55, z: 0.75, w: 3.2, h: 0.75, d: 0.1, texture: clothColor, group });
    engine.addVoxelBox({ x: -1.5, y: 1.4, z: -0.7, w: 0.12, h: 1.8, d: 0.12, texture: frameTexture, group });
    engine.addVoxelBox({ x: 1.5, y: 1.4, z: -0.7, w: 0.12, h: 1.8, d: 0.12, texture: frameTexture, group });
    engine.addVoxelBox({ x: 0, y: 2.3, z: -0.7, w: 3.2, h: 0.6, d: 0.12, texture: clothColor, group });
}

/**
 * IMM-020b del TODO inmersivo: renderiza una `model_definition` generada por
 * IA (o refinada a mano) — una lista de cajas voxel ya validada en el
 * backend (`VoxelDefinitionValidator`), en vez de un builder escrito a mano.
 * Se exporta aparte de `standardBuilders` porque, a diferencia de esos
 * builders de forma fija, necesita el payload `definition` por instancia
 * (cada plantilla generada por IA tiene su propia lista de cajas).
 */
export function buildFromDefinition(engine, {
    x, y = 0, z, rotation = 0, definition,
}) {
    const group = new THREE.Group();
    group.position.set(x, y, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    (definition?.boxes ?? []).forEach((box) => {
        const mesh = engine.addVoxelBox({
            x: box.x,
            y: box.y,
            z: box.z,
            w: box.w,
            h: box.h,
            d: box.d,
            texture: box.texture,
            rotationX: box.rotationX ?? 0,
            rotationY: box.rotationY ?? 0,
            rotationZ: box.rotationZ ?? 0,
            collidable: Boolean(box.collidable),
            emissive: box.emissive ? parseInt(box.emissive.slice(1), 16) : 0x000000,
            group,
        });

        // Bug real reportado por el usuario: el tiling elegido por caja en
        // el editor de objeto (`box.tiling`) nunca se aplicaba fuera de ese
        // editor — un objeto recién agregado a una plaza (o en la escena
        // pública) siempre se veía con la textura sin repetir, aunque la
        // plantilla sí tuviera un tiling guardado. `applyTiling()` clona
        // textura y material antes de mutar (ver texture-tiling-utils.js),
        // así que aplicarlo aquí por caja no afecta otras cajas/objetos que
        // compartan la misma textura base.
        if (box.tiling) {
            applyTiling(mesh, box.tiling);
        }
    });

    return group;
}

// IMM-041: generaliza al motor compartido el patrón que antes solo existía
// hardcodeado en `zipa-plaza-immersive.js` (`palmModelTemplate`/
// `lampModelTemplate` + `.clone(true)`) — dos stands que usan la misma
// plantilla GLB ya no disparan cada uno su propio fetch+parseo completo,
// el segundo en adelante solo clona la jerarquía ya parseada. `clone(true)`
// de Three.js comparte geometría/material por referencia entre clones (no
// los duplica) — es el mismo motivo por el que `applyStandPrimaryColor()`
// y `applyTiling()` ya asumen que un GLB puede llegar con textura/material
// compartido con otras instancias, igual que la ruta voxel.
const glbTemplateCache = new Map();

function loadGlbTemplate(url) {
    if (!glbTemplateCache.has(url)) {
        glbTemplateCache.set(url, new Promise((resolve, reject) => {
            sharedGltfLoader.load(url, (gltf) => resolve(gltf.scene), undefined, (error) => reject(error));
        }));
    }

    return glbTemplateCache.get(url);
}

async function loadGlbAt(engine, {
    x, y = 0, z, rotation = 0, url,
}) {
    const template = await loadGlbTemplate(url);
    const model = template.clone(true);
    model.position.set(x, y, z);
    model.rotation.y = rotation;
    model.traverse((child) => {
        if (child.isMesh) {
            child.castShadow = true;
            child.receiveShadow = true;
        }
    });
    engine.world.add(model);

    return model;
}

function normalizeScaleVector(scale = null) {
    if (typeof scale === 'number') {
        return { x: scale, y: scale, z: scale };
    }

    if (scale && typeof scale === 'object') {
        return {
            x: Number(scale.x ?? 1),
            y: Number(scale.y ?? 1),
            z: Number(scale.z ?? 1),
        };
    }

    return { x: 1, y: 1, z: 1 };
}

function applyScaleToObject(engine, object, scale = null) {
    const vector = normalizeScaleVector(scale);

    object.scale.set(vector.x, vector.y, vector.z);

    // Pedido del usuario: "la barrera permite que el personaje pase" —
    // `addVoxelBox()` ya registró la colisión de cada caja ANTES de que
    // este escalado existiera, así que quedaba con el tamaño original sin
    // importar cuánto se estirara el objeto visualmente. Recalcularla
    // acá, después de escalar, la deja alineada con lo que realmente se
    // ve (ver `refreshBoxCollisions()`).
    engine.refreshBoxCollisions?.(object);

    return object;
}

/**
 * IMM-020b: resuelve y dibuja un objeto (stand o elemento de plaza) según
 * la misma prioridad que usa la plaza en vivo — GLB real (`modelUrl`) >
 * definición generada por IA (`modelDefinition`) > forma voxel procedural
 * (`builderKey`). Un solo lugar para esta regla: la usan tanto
 * `dynamic-stand-loader.js` (stands en la escena pública) como el editor
 * espacial del admin, para que nunca diverjan entre sí.
 */
export async function renderObjectByPriority(engine, {
    x, y = 0, z, rotation = 0, scale = null, modelUrl, modelDefinition, builderKey, builders = standardBuilders,
}) {
    if (modelUrl) {
        try {
            return applyScaleToObject(engine, await loadGlbAt(engine, {
                x,
                y,
                z,
                rotation,
                url: modelUrl,
            }), scale);
        } catch (error) {
            // Un GLB roto/faltante no debe dejar el objeto invisible — sigue
            // con las siguientes opciones en vez de abortar.
            console.error(`No se pudo cargar el modelo GLB: ${modelUrl}`, error);
        }
    }

    if (modelDefinition) {
        return applyScaleToObject(engine, buildFromDefinition(engine, {
            x,
            y,
            z,
            rotation,
            definition: modelDefinition,
        }), scale);
    }

    if (builderKey && builders[builderKey]) {
        return applyScaleToObject(engine, callBuilderAsSingleObject(engine, builders[builderKey], {
            x,
            y,
            z,
            rotation,
        }), scale);
    }

    return null;
}

/**
 * La mayoría de `standardBuilders` ya crean su propio `THREE.Group`
 * posicionado en (x,z) y lo devuelven — pero algunos, más antiguos
 * (`buildLamp`, `buildTree`), agregan varias cajas sueltas directo a
 * `engine.world` con coordenadas ABSOLUTAS, sin grupo ni valor de retorno.
 * Para que cualquier builder sea siempre un único objeto posicionable (lo
 * que necesita el editor espacial para poder arrastrarlo), esta función
 * detecta ese caso y reagrupa lo que el builder agregó, sin modificar los
 * builders existentes uno por uno.
 */
function callBuilderAsSingleObject(engine, builder, {
    x, y = 0, z, rotation,
}) {
    const childCountBefore = engine.world.children.length;

    builder(engine, { x, z, rotation });

    const added = engine.world.children.slice(childCountBefore);

    if (added.length === 1) {
        return added[0];
    }

    if (added.length === 0) {
        return null;
    }

    const wrapper = new THREE.Group();

    added.forEach((child) => {
        engine.world.remove(child);
        // Los builders "planos" ya hornearon (x,z) en cada caja como
        // coordenadas absolutas — se restan aquí para que el wrapper
        // pueda moverse como un todo sin duplicar el desplazamiento.
        child.position.x -= x;
        child.position.z -= z;
        wrapper.add(child);
    });

    wrapper.position.set(x, y, z);
    engine.world.add(wrapper);

    return wrapper;
}

export const standardBuilders = {
    colonialHouse: buildColonialHouse,
    arcadeRow: buildArcadeRow,
    bench: buildBench,
    longBench: buildLongBench,
    planter: buildPlanter,
    lamp: buildLamp,
    tree: buildTree,
    palm: buildPalm,
    cloud: buildCloud,
    npc: buildNpcEntry,
    hedgeRect: buildHedgeRect,
    fountain: buildFountain,
    marketStall: buildMarketStall,
    vehicle: buildVehicle,
    statue: buildStatue,
    standBooth: buildStandBooth,
    standTable: buildStandTable,
    voxelBox: (engine, opts) => engine.addVoxelBox(opts),
};
