/**
 * Lab inmersivo de la plaza principal de Zipaquirá (IMM-003: migrado al
 * motor compartido `lib/voxel-plaza-engine.js`, el mismo que usa Cajicá).
 * Personaje, cámara, colisiones, física, texturas voxel y panel técnico
 * viven en el motor y NO se reescriben aquí — ver
 * `docs/architecture/personaje-inmersivo.md`.
 *
 * Lo que sí es propio de esta plaza y vive en este archivo:
 *   - Su geometría (`buildWorld` y las funciones `build*` que llama).
 *   - Modelos GLB reales (catedral, alcaldía, palmera, farol) en vez de
 *     las versiones voxel procedurales del motor — por eso NO se usan los
 *     `standardBuilders` compartidos: `colonialHouse`/`arcadeRow` del
 *     motor ya divergieron de los originales de esta plaza (el motor le
 *     agregó una base de piedra a `colonialHouse` para Cajicá que esta
 *     plaza nunca tuvo), así que replicar esos números tal cual aquí es
 *     más seguro que heredar una versión que ya cambió.
 *   - El domo de cielo, sin niebla de escena (esta plaza nunca tuvo
 *     `scene.fog`; el domo de 250 de radio ya cubre el horizonte).
 *   - El contador de assets async (GLB/textura) para el overlay de carga,
 *     porque el motor marca la escena lista al terminar el layout
 *     síncrono — esta plaza necesita esperar además a que terminen sus
 *     cargas async (`{ deferSceneReady: true }`).
 */
import * as THREE from 'https://esm.sh/three@0.179.1';
import { GLTFLoader } from 'https://esm.sh/three@0.179.1/examples/jsm/loaders/GLTFLoader.js';
import { VoxelPlazaEngine } from './lib/voxel-plaza-engine.js';
import { loadDynamicStands } from './lib/dynamic-stand-loader.js';

const container = document.getElementById('zipa-immersive-scene');
const lockTrigger = document.getElementById('zipa-lock-trigger');
const coordinatesDisplay = document.getElementById('zipa-player-coordinates');
const loadingOverlay = document.getElementById('zipa-loading-overlay');
const immersiveBusinesses = Array.isArray(window.zipaImmersiveBusinesses) ? window.zipaImmersiveBusinesses.slice(0, 4) : [];

if (!container) {
    throw new Error('Zipa immersive container not found.');
}

const engine = new VoxelPlazaEngine({
    container,
    lockTrigger,
    playerStart: { x: -1.95, y: 0, z: 29 },
    playerFacing: Math.PI,
});

// Ajustes propios de esta plaza que no son parte del comportamiento
// genérico del motor (ver cabecera del archivo).
engine.scene.fog = null;
engine.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.25));
engine.renderer.shadowMap.type = THREE.BasicShadowMap;
engine.sun.shadow.mapSize.set(1024, 1024);
engine.controls.yaw = 5.761592653589793;

const plazaLayout = {
    centerX: 8,
    centerZ: 2,
    baseWidth: 210,
    baseDepth: 170,
    plazaWidth: 186,
    plazaDepth: 146,
    edgeNorth: -70,
    edgeSouth: 74,
    edgeWest: -89,
    edgeEast: 105,
    cathedralX: 12,
    cathedralZ: -75,
    annexX: -120,
    annexZ: 56,
};

const labelTextures = new Map();
const gltfLoader = new GLTFLoader();
const textureLoader = new THREE.TextureLoader();
let palmModelTemplate = null;
let lampModelTemplate = null;
const pendingPalmPlacements = [];
const pendingLampPlacements = [];
let remainingSceneAssets = 5;
let baseSceneReady = false;

function syncLoadingOverlay() {
    if (!loadingOverlay) {
        return;
    }

    loadingOverlay.classList.toggle('is-hidden', baseSceneReady && remainingSceneAssets <= 0);

    if (baseSceneReady && remainingSceneAssets <= 0) {
        engine.perf.markSceneReady();
    }
}

function settleSceneAsset() {
    remainingSceneAssets = Math.max(0, remainingSceneAssets - 1);
    syncLoadingOverlay();
}

function updateCoordinatesDisplay() {
    if (!coordinatesDisplay) {
        return;
    }

    const { x, y, z } = engine.player.position;
    coordinatesDisplay.textContent = `X: ${x.toFixed(2)} · Y: ${y.toFixed(2)} · Z: ${z.toFixed(2)}`;
}

engine.onUpdate(() => updateCoordinatesDisplay());

// --- Modelos GLB reutilizables (palmera, farol): se cargan una vez y se
// clonan en cada punto de plantación, igual que un `standardBuilder` pero
// con un modelo real en vez de cajas voxel. --------------------------------

function prepareModelShadows(root, { castShadow = false, receiveShadow = true } = {}) {
    root.traverse((child) => {
        if (!child.isMesh) {
            return;
        }

        child.castShadow = castShadow;
        child.receiveShadow = receiveShadow;
        child.frustumCulled = true;
        child.matrixAutoUpdate = false;
        child.updateMatrix();

        if (!child.material) {
            return;
        }

        if (Array.isArray(child.material)) {
            child.material.forEach((materialItem) => {
                materialItem.needsUpdate = true;
            });

            return;
        }

        child.material.needsUpdate = true;
    });

    root.matrixAutoUpdate = false;
    root.updateMatrixWorld(true);
}

function brightenModel(root, { emissive = 0x2c2c2c, emissiveIntensity = 0.28, roughness = 0.78 } = {}) {
    root.traverse((child) => {
        if (!child.isMesh || !child.material) {
            return;
        }

        const materials = Array.isArray(child.material) ? child.material : [child.material];

        materials.forEach((materialItem) => {
            if ('emissive' in materialItem) {
                materialItem.emissive = new THREE.Color(emissive);
                materialItem.emissiveIntensity = emissiveIntensity;
            }

            if ('roughness' in materialItem) {
                materialItem.roughness = Math.min(materialItem.roughness ?? roughness, roughness);
            }

            if ('metalness' in materialItem) {
                materialItem.metalness = Math.min(materialItem.metalness ?? 0.08, 0.08);
            }

            materialItem.needsUpdate = true;
        });
    });
}

function loadPalmModel() {
    gltfLoader.load(
        '/3D/palmera-voxel.glb',
        (gltf) => {
            palmModelTemplate = gltf.scene;
            prepareModelShadows(palmModelTemplate, { castShadow: false, receiveShadow: false });

            pendingPalmPlacements.splice(0).forEach((placement) => {
                placePalmModel(placement);
            });

            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/palmera-voxel.glb', error);
            settleSceneAsset();
        },
    );
}

function loadLampModel() {
    gltfLoader.load(
        '/3D/farol-voxel.glb',
        (gltf) => {
            lampModelTemplate = gltf.scene;
            prepareModelShadows(lampModelTemplate, { castShadow: false, receiveShadow: false });
            brightenModel(lampModelTemplate, { emissive: 0x3a3328, emissiveIntensity: 0.34, roughness: 0.72 });

            pendingLampPlacements.splice(0).forEach((placement) => {
                placeLampModel(placement);
            });

            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/farol-voxel.glb', error);
            settleSceneAsset();
        },
    );
}

function loadSkyDome() {
    textureLoader.load(
        '/3D/paisaje_otono_voxel_4k.webp',
        (texture) => {
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.mapping = THREE.EquirectangularReflectionMapping;
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.ClampToEdgeWrapping;
            texture.magFilter = THREE.LinearFilter;
            texture.minFilter = THREE.LinearMipmapLinearFilter;
            texture.generateMipmaps = true;
            texture.anisotropy = Math.min(8, engine.renderer.capabilities.getMaxAnisotropy());
            texture.repeat.x = -1;
            texture.needsUpdate = true;

            const skyDome = new THREE.Mesh(
                new THREE.SphereGeometry(250, 96, 64),
                new THREE.MeshBasicMaterial({ map: texture, side: THREE.BackSide, fog: false }),
            );

            skyDome.position.set(plazaLayout.centerX, 52, plazaLayout.centerZ - 8);
            skyDome.rotation.y = Math.PI * 1.08;
            skyDome.renderOrder = -10;
            engine.scene.add(skyDome);
            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/paisaje_otono_voxel_4k.webp', error);
            settleSceneAsset();
        },
    );
}

function placePalmModel({ x, z, trunk, baseY = 0 }) {
    if (!palmModelTemplate) {
        pendingPalmPlacements.push({ x, z, trunk, baseY });

        return;
    }

    const palm = palmModelTemplate.clone(true);
    const rawBox = new THREE.Box3().setFromObject(palm);
    const rawSize = rawBox.getSize(new THREE.Vector3());
    const targetHeight = trunk + 8;
    const scale = targetHeight / Math.max(rawSize.y, 1);

    palm.scale.setScalar(scale);

    const scaledBox = new THREE.Box3().setFromObject(palm);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

    palm.position.set(x - scaledCenter.x, baseY - scaledBox.min.y, z - scaledCenter.z);
    palm.updateMatrix();
    palm.updateMatrixWorld(true);

    engine.world.add(palm);
    engine.addCollisionBox(x, Math.max(3.8, trunk * 0.32), z, 2.2, Math.max(7.5, trunk * 0.64), 2.2);
}

function placeLampModel({ x, z, height = 10 }) {
    if (!lampModelTemplate) {
        pendingLampPlacements.push({ x, z, height });

        return;
    }

    const lamp = lampModelTemplate.clone(true);
    const rawBox = new THREE.Box3().setFromObject(lamp);
    const rawSize = rawBox.getSize(new THREE.Vector3());
    const targetHeight = height + 5;
    const scale = targetHeight / Math.max(rawSize.y, 1);

    lamp.scale.setScalar(scale);

    const scaledBox = new THREE.Box3().setFromObject(lamp);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

    lamp.position.set(x - scaledCenter.x, 0.08 - scaledBox.min.y, z - scaledCenter.z);
    lamp.rotation.y = Math.PI / 2;
    lamp.updateMatrix();
    lamp.updateMatrixWorld(true);

    engine.world.add(lamp);
    engine.addCollisionBox(x, Math.max(3, targetHeight * 0.28), z, 1.8, Math.max(6, targetHeight * 0.56), 1.8);
}

// --- Señalética de vitrinas (paneles con nombre/producto/precio) ----------

function createLabelTexture(key, text, {
    width = 512,
    height = 160,
    background = '#f4e4c1',
    foreground = '#3a2412',
    accent = '#b45d2c',
    font = '700 44px Instrument Sans, sans-serif',
    subFont = '600 30px Instrument Sans, sans-serif',
} = {}) {
    if (labelTextures.has(key)) {
        return labelTextures.get(key);
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = background;
    ctx.fillRect(0, 0, width, height);

    ctx.strokeStyle = accent;
    ctx.lineWidth = 10;
    ctx.strokeRect(8, 8, width - 16, height - 16);

    const lines = Array.isArray(text) ? text : [text];
    const fonts = lines.length > 1 ? [font, subFont, subFont] : [font];
    const startY = lines.length > 1 ? 62 : 92;

    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = foreground;

    lines.forEach((line, index) => {
        ctx.font = fonts[index] ?? subFont;
        ctx.fillText(String(line).slice(0, 28), width / 2, startY + (index * 42));
    });

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.needsUpdate = true;
    labelTextures.set(key, texture);

    return texture;
}

function addLabelPlane({ x, y, z, width, height, rotation = 0, text, key, background, foreground, accent, group }) {
    const texture = createLabelTexture(key, text, { background, foreground, accent });
    const plane = new THREE.Mesh(
        new THREE.PlaneGeometry(width, height),
        new THREE.MeshBasicMaterial({ map: texture, transparent: false, side: THREE.DoubleSide }),
    );

    plane.position.set(x, y, z);
    plane.rotation.y = rotation;
    group.add(plane);

    return plane;
}

// --- Piezas propias de esta plaza (no son `standardBuilders`: ver nota de
// la cabecera sobre por qué `colonialHouse`/`arcadeRow`/`planter` no se
// heredan del motor compartido). -------------------------------------------

function buildGround() {
    engine.addVoxelBox({ x: 0, y: -2, z: 0, w: 300, h: 4, d: 300, texture: 'grass', castShadow: false });
    engine.addVoxelBox({
        x: plazaLayout.centerX, y: -0.18, z: plazaLayout.centerZ, w: plazaLayout.baseWidth, h: 0.48, d: plazaLayout.baseDepth, texture: 'path', castShadow: false,
    });
    engine.addVoxelBox({
        x: plazaLayout.centerX, y: 0.12, z: plazaLayout.centerZ, w: plazaLayout.plazaWidth, h: 0.3, d: plazaLayout.plazaDepth, texture: 'plaza', castShadow: false,
    });
}

function buildCloud(x, y, z, scale = 1) {
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

function buildSkyProps() {
    buildCloud(-110, 56, -30, 0.74);
    buildCloud(-76, 60, -54, 0.9);
    buildCloud(-28, 57, -68, 1.02);
    buildCloud(18, 62, -24, 0.76);
    buildCloud(64, 58, -58, 0.92);
    buildCloud(106, 61, -18, 0.8);
}

function buildPalm({ x, z, trunk = 18, baseY = 0 }) {
    placePalmModel({ x, z, trunk, baseY });
}

function buildTree({ x, z, height = 10 }) {
    for (let i = 0; i < height; i += 1) {
        engine.addVoxelBox({ x, y: i + 1, z, w: 1.1, h: 1, d: 1.1, texture: 'wood', collidable: i < 5 });
    }

    for (let lx = -3; lx <= 3; lx += 1) {
        for (let lz = -3; lz <= 3; lz += 1) {
            if (Math.abs(lx) + Math.abs(lz) > 4) {
                continue;
            }

            engine.addVoxelBox({
                x: x + lx * 1.06, y: height + 2 + (Math.abs(lx) + Math.abs(lz) === 0 ? 0.8 : 0), z: z + lz * 1.06, w: 1.85, h: 1.85, d: 1.85, texture: 'leaf',
            });
        }
    }
}

function buildLamp({ x, z, height = 10 }) {
    placeLampModel({ x, z, height });
}

function buildBench({ x, z, rotation = 0 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 1.1, z: 0, w: 4.8, h: 0.48, d: 1.25, texture: 'trim', group });
    engine.addVoxelBox({ x: 0, y: 1.95, z: -0.52, w: 4.8, h: 0.92, d: 0.35, texture: 'trim', group });
    engine.addVoxelBox({ x: -1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
    engine.addVoxelBox({ x: 1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
}

function buildPlanter({ x, z, radius = 5, tree = null, flowers = false, trunk = 19 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.52, z: 0, w: radius * 2.1, h: 1.05, d: radius * 2.1, texture: 'stone', group });
    engine.addVoxelBox({ x: 0, y: 1.24, z: 0, w: radius * 1.62, h: 0.72, d: radius * 1.62, texture: flowers ? 'flower' : 'grass', group });
    engine.addRoundPlanterCollision(x, z, radius);

    if (tree === 'palm') {
        buildPalm({ x, z, trunk, baseY: 1.6 });
    }

    if (tree === 'tree') {
        buildTree({ x, z, height: 10 });
    }
}

function buildArcadeRow({
    x, z, sections = 8, rotation = 0, paletteSwap = 'white', upperColor = 'trim', shutterColor = 'leaf', roofTexture = 'roofClay', baseTexture = 'stoneLight', depth = 8.6, spacing = 7.1, frontOffset = 4.02, facadeJitter = 0,
}) {
    const group = new THREE.Group();
    group.position.set(x, -0.9, z);
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
                engine.addVoxelBox({
                    x: offset + rail * 0.78, y: 5.92, z: frontZ + 0.25, w: 0.12, h: 0.72, d: 0.12, texture: 'woodDark', group,
                });
            }
        }

        engine.addVoxelBox({ x: offset - 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        engine.addVoxelBox({ x: offset + 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        engine.addVoxelBox({ x: offset - 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset - 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({ x: offset + 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        engine.addVoxelBox({
            x: offset, y: hasBalcony ? 5.95 : 3.15, z: frontZ - 0.05, w: hasBalcony ? 1.3 : 1.15, h: hasBalcony ? 1.78 : 1.45, d: 0.18, texture: i % 2 === 0 ? 'glass' : upperColor, group,
        });
    }
}

function buildColonialHouse({
    x, z, rotation = 0, width = 12, depth = 10, bodyHeight = 7.8, paletteSwap = 'white', roofTexture = 'roofClay', shutterColor = 'leaf', doorOffset = 0, hasBalcony = false, windows = 3,
}) {
    const group = new THREE.Group();
    group.position.set(x, -0.9, z);
    group.rotation.y = rotation;
    engine.world.add(group);

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

// --- Modelos GLB de gran escala (catedral, alcaldía) -----------------------

function buildCathedral() {
    const anchor = new THREE.Group();
    anchor.position.set(plazaLayout.cathedralX, 0, plazaLayout.cathedralZ);
    engine.world.add(anchor);

    engine.addCollisionBox(plazaLayout.cathedralX, 9, plazaLayout.cathedralZ - 1, 30, 18, 54);
    engine.addCollisionBox(plazaLayout.cathedralX, 12, plazaLayout.cathedralZ + 22, 46, 24, 11);

    gltfLoader.load(
        '/3D/catedral-zipa-voxel.glb',
        (gltf) => {
            const model = gltf.scene;
            prepareModelShadows(model, { castShadow: false, receiveShadow: true });

            const localBox = new THREE.Box3().setFromObject(model);
            const size = localBox.getSize(new THREE.Vector3());

            const targetWidth = 47;
            const targetDepth = 54;
            const targetHeight = 38;
            const scale = Math.min(
                targetWidth / Math.max(size.x, 1),
                targetDepth / Math.max(size.z, 1),
                targetHeight / Math.max(size.y, 1),
            ) * 1.5;

            model.scale.setScalar(scale);

            const scaledBox = new THREE.Box3().setFromObject(model);
            const scaledSize = scaledBox.getSize(new THREE.Vector3());
            const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

            model.position.set(-scaledCenter.x, -scaledBox.min.y, -scaledCenter.z + 1.5);
            model.rotation.y = Math.PI * 2;
            model.updateMatrix();
            model.updateMatrixWorld(true);

            anchor.add(model);

            const worldBox = new THREE.Box3().setFromObject(model);
            engine.collisions.push(worldBox.clone());
            engine.collisions.push(new THREE.Box3().setFromCenterAndSize(
                new THREE.Vector3(plazaLayout.cathedralX, worldBox.min.y + Math.max(2.5, scaledSize.y * 0.22), plazaLayout.cathedralZ + 21),
                new THREE.Vector3(Math.max(24, scaledSize.x * 0.94), Math.max(5, scaledSize.y * 0.44), 12),
            ));
            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/catedral-zipa-voxel.glb', error);
            settleSceneAsset();
        },
    );
}

function buildWhiteBuilding() {
    const anchor = new THREE.Group();
    anchor.position.set(plazaLayout.annexX, 0, plazaLayout.annexZ);
    engine.world.add(anchor);

    engine.addCollisionBox(plazaLayout.annexX, 20, plazaLayout.annexZ, 38, 40, 55);

    gltfLoader.load(
        '/3D/alcaldia1.glb',
        (gltf) => {
            const model = gltf.scene;
            prepareModelShadows(model, { castShadow: false, receiveShadow: true });

            const localBox = new THREE.Box3().setFromObject(model);
            const size = localBox.getSize(new THREE.Vector3());
            const targetWidth = 21;
            const targetDepth = 13;
            const targetHeight = 19;
            const scale = Math.min(
                targetWidth / Math.max(size.x, 1),
                targetDepth / Math.max(size.z, 1),
                targetHeight / Math.max(size.y, 1),
            ) * 2.5;

            model.scale.setScalar(scale);

            const scaledBox = new THREE.Box3().setFromObject(model);
            const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

            model.position.set(-scaledCenter.x, -scaledBox.min.y, -scaledCenter.z);
            model.rotation.y = Math.PI / 2;
            model.updateMatrix();
            model.updateMatrixWorld(true);

            anchor.add(model);

            const worldBox = new THREE.Box3().setFromObject(model);
            engine.collisions.push(worldBox.clone());
            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/alcaldia1.glb', error);
            settleSceneAsset();
        },
    );
}

function buildPerimeterArchitecture() {
    buildWhiteBuilding();
    buildImmersiveStorefronts();
}

// --- Vitrinas de emprendedores destacados ----------------------------------

function formatProductPrice(product) {
    if (product.price_type === 'consultar') {
        return 'Consultar';
    }

    if (product.price_type === 'sin_precio' || !product.price) {
        return 'Disponible';
    }

    const amount = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(Number(product.price));

    return product.price_type === 'desde' ? `Desde ${amount}` : amount;
}

function buildImmersiveStorefronts() {
    if (immersiveBusinesses.length === 0) {
        return;
    }

    const slots = [
        { x: -79, z: -36, rotation: Math.PI / 2, paletteSwap: 'ochre', shutterColor: 'white' },
        { x: -79, z: -14, rotation: Math.PI / 2, paletteSwap: 'white', shutterColor: 'leaf' },
        { x: 95, z: -36, rotation: -Math.PI / 2, paletteSwap: 'white', shutterColor: 'leaf' },
        { x: 95, z: -14, rotation: -Math.PI / 2, paletteSwap: 'coral', shutterColor: 'white' },
    ];

    immersiveBusinesses.forEach((business, index) => {
        const slot = slots[index];

        if (!slot) {
            return;
        }

        buildImmersiveStorefront(business, slot, index);
    });
}

function buildImmersiveStorefront(business, slot, index) {
    const group = new THREE.Group();
    group.position.set(slot.x, 0, slot.z);
    group.rotation.y = slot.rotation;
    engine.world.add(group);

    buildColonialHouse({
        x: slot.x, z: slot.z, rotation: slot.rotation, width: 17, depth: 10.6, bodyHeight: 7.3, paletteSwap: slot.paletteSwap, roofTexture: 'roofClay', shutterColor: slot.shutterColor, doorOffset: 0, hasBalcony: true, windows: 4,
    });

    addLabelPlane({
        x: 0, y: 6.9, z: 5.45, width: 7.8, height: 2.2, text: [business.name, business.headline || 'Vitrina local'], key: `business-sign-${business.slug}-${index}`, background: '#f1dfb6', foreground: '#412817', accent: '#9f552a', group,
    });

    const displayCount = Math.min(business.products.length, 3);
    const startX = -((displayCount - 1) * 3.7) / 2;

    business.products.slice(0, 3).forEach((product, productIndex) => {
        const localX = startX + (productIndex * 3.7);
        const localZ = 8.5;

        engine.addVoxelBox({ x: localX, y: 0.85, z: localZ, w: 2.8, h: 1.7, d: 2.2, texture: 'stoneLight', group });
        engine.addVoxelBox({ x: localX, y: 1.95, z: localZ, w: 2, h: 0.48, d: 1.48, texture: product.is_available ? 'cloth' : 'woodDark', group });

        addLabelPlane({
            x: localX, y: 3.55, z: localZ, width: 3, height: 1.28, text: [product.name, formatProductPrice(product)], key: `product-sign-${business.slug}-${productIndex}`, background: product.is_available ? '#fff5dd' : '#d9d0c3', foreground: '#40291b', accent: product.is_available ? '#cc6e2d' : '#6d5c4d', group,
        });
    });
}

// --- Mobiliario, bordes y NPCs de la plaza central -------------------------

function buildPlazaStreetFurniture() {
    [
        { x: -82, z: 14, rot: Math.PI / 2, len: 16 },
        { x: 88, z: 6, rot: Math.PI / 2, len: 16 },
    ].forEach(({ x, z, rot, len }) => buildLongBench({ x, z, rotation: rot, length: len }));

    [
        [-104, 28, 27, 5.4],
        [-50, -8, 21, 5.4],
        [58, -2, 28, 5.7],
        [12, 50, 25, 5.8],
        [40, 60, 10, 4],
    ].forEach(([x, z, trunk, radius]) => buildPlanter({ x, z, radius, tree: 'palm', trunk }));

    buildPlanter({ x: -112, z: -10, radius: 5.4, tree: 'tree' });

    buildLamp({ x: -96, z: -6, height: 14 });
    buildLamp({ x: 96, z: -8, height: 15 });
    buildLamp({ x: 30, z: 62, height: 14 });
}

function buildLongBench({ x, z, rotation = 0, length = 16 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    engine.world.add(group);

    engine.addVoxelBox({ x: 0, y: 0.9, z: 0, w: length, h: 0.9, d: 2.2, texture: 'stone', group });
    engine.addCollisionBox(x, 1.1, z, rotation === 0 ? length : 2.2, 2.2, rotation === 0 ? 2.2 : length);
}

function buildPlazaEdges() {
    for (let i = plazaLayout.edgeWest; i <= plazaLayout.edgeEast; i += 6) {
        engine.addVoxelBox({ x: i, y: 1.05, z: plazaLayout.edgeSouth, w: 1.3, h: 2.1, d: 1.3, texture: 'stone', collidable: true });
    }

    for (let i = plazaLayout.edgeNorth + 16; i <= plazaLayout.edgeSouth - 24; i += 5.8) {
        engine.addVoxelBox({ x: plazaLayout.edgeWest, y: 1.05, z: i, w: 1.25, h: 2.1, d: 1.25, texture: 'stone', collidable: true });
        engine.addVoxelBox({ x: plazaLayout.edgeEast, y: 1.05, z: i, w: 1.25, h: 2.1, d: 1.25, texture: 'stone', collidable: true });
    }

    engine.addCollisionBox(plazaLayout.centerX, 4, plazaLayout.edgeSouth + 9, 214, 8, 6);
    engine.addCollisionBox(plazaLayout.edgeWest - 9, 5, plazaLayout.centerZ, 6, 10, 150);
    engine.addCollisionBox(plazaLayout.edgeEast + 9, 5, plazaLayout.centerZ, 6, 10, 150);
    engine.addCollisionBox(plazaLayout.centerX, 6, plazaLayout.edgeNorth - 9, 214, 12, 8);
}

function buildCentralPlaza() {
    buildPlazaStreetFurniture();
}

function buildPeople() {
    [
        [-72, 28, 0x2d7acc, 0.1],
        [-48, 10, 0x5b9132, 1.2],
        [-18, 34, 0xcd632c, 2.4],
        [14, 16, 0x724eb8, 3.1],
        [38, 8, 0x1e7352, 4.1],
        [68, 20, 0xb64e8b, 5.3],
        [88, 10, 0xd79e2c, 0.5],
        [76, -14, 0x4867c0, 1.9],
        [52, -26, 0x8d4aa8, 4.7],
        [8, -18, 0x2f8c7a, 2.7],
        [-36, -18, 0xb52d2d, 3.7],
        [-74, -28, 0x264f9e, 1.5],
    ].forEach(([x, z, shirtColor, seed]) => engine.createNpc(x, z, shirtColor, seed));
}

function buildWorld() {
    buildGround();
    buildSkyProps();
    buildPerimeterArchitecture();
    buildCathedral();
    buildCentralPlaza();
    buildPlazaEdges();
    buildPeople();
}

loadPalmModel();
loadLampModel();
loadSkyDome();

engine.start([{ type: 'custom', build: () => buildWorld() }], undefined, { deferSceneReady: true });
loadDynamicStands(engine, window.zipaImmersivePlazaId);

updateCoordinatesDisplay();
baseSceneReady = true;
syncLoadingOverlay();
