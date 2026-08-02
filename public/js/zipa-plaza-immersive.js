import * as THREE from 'https://esm.sh/three@0.179.1';
import { GLTFLoader } from 'https://esm.sh/three@0.179.1/examples/jsm/loaders/GLTFLoader.js';

const container = document.getElementById('zipa-immersive-scene');
const lockTrigger = document.getElementById('zipa-lock-trigger');
const coordinatesDisplay = document.getElementById('zipa-player-coordinates');
const loadingOverlay = document.getElementById('zipa-loading-overlay');
const immersiveBusinesses = Array.isArray(window.zipaImmersiveBusinesses) ? window.zipaImmersiveBusinesses.slice(0, 4) : [];

if (!container) {
    throw new Error('Zipa immersive container not found.');
}

const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(54, window.innerWidth / window.innerHeight, 0.1, 1000);

const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.25));
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.BasicShadowMap;
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.setClearColor(0x87ceeb, 1);
container.appendChild(renderer.domElement);

const ambient = new THREE.HemisphereLight(0xe8f5ff, 0xb89262, 1.65);
scene.add(ambient);

const sun = new THREE.DirectionalLight(0xfff2d0, 3.1);
sun.position.set(55, 78, 36);
sun.castShadow = true;
sun.shadow.mapSize.set(1024, 1024);
sun.shadow.camera.left = -120;
sun.shadow.camera.right = 120;
sun.shadow.camera.top = 120;
sun.shadow.camera.bottom = -120;
scene.add(sun);

const fillLight = new THREE.DirectionalLight(0xc5e7ff, 0.55);
fillLight.position.set(-40, 28, -18);
scene.add(fillLight);

const world = new THREE.Group();
scene.add(world);

const collisions = [];
const animatedActors = [];
const clock = new THREE.Clock();
const gltfLoader = new GLTFLoader();
const textureLoader = new THREE.TextureLoader();
let palmModelTemplate = null;
let lampModelTemplate = null;
const pendingPalmPlacements = [];
const pendingLampPlacements = [];
let remainingSceneAssets = 5;
let baseSceneReady = false;

const movement = {
    forward: false,
    backward: false,
    left: false,
    right: false,
    jump: false,
    sprint: false,
};

const controls = {
    isLocked: false,
    isDragging: false,
    lastX: 0,
    lastY: 0,
    yaw: 5.761592653589793,
    pitch: -0.16,
    minPitch: -1.05,
    maxPitch: 0.24,
    mouseSensitivity: 0.0026,
};

const playerState = {
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
    feetY: 0,
    onGround: true,
    turnSpeed: 0.22,
    inputSmoothing: 0.18,
    jumpCooldown: 0,
    strafeFactor: 0.9,
    backpedalFactor: 0.78,
    rotationVelocity: 0,
};

const cameraState = {
    distance: 15.6,
    height: 6.15,
    shoulder: 0.9,
    smoothing: 0.16,
    sprintDistance: 17.1,
    sprintHeight: 6.45,
    target: new THREE.Vector3(),
};

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

const palette = {
    plaza: 0xd3bb8b,
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
    glass: 0x7bc0e9,
    water: 0x73d2e5,
    flower: 0xdb5775,
    cloth: 0xc98e39,
    skin: 0xdfaa77,
    shirt: 0x2869d0,
    pants: 0x33445c,
    grass: 0x90b85e,
    grassDark: 0x64853f,
    path: 0xcab07f,
    patina: 0x6f8f79,
    patinaDark: 0x3f5646,
    accent: 0xdf3527,
};

const textures = createVoxelTextures(palette);
const labelTextures = new Map();

const player = buildPlayer();
player.position.set(-1.95, 0, 29);
player.rotation.y = Math.PI;
scene.add(player);

function syncLoadingOverlay() {
    if (!loadingOverlay) {
        return;
    }

    loadingOverlay.classList.toggle('is-hidden', baseSceneReady && remainingSceneAssets <= 0);
}

function settleSceneAsset() {
    remainingSceneAssets = Math.max(0, remainingSceneAssets - 1);
    syncLoadingOverlay();
}

function updateCoordinatesDisplay() {
    if (!coordinatesDisplay) {
        return;
    }

    coordinatesDisplay.textContent = `X: ${player.position.x.toFixed(2)} · Y: ${player.position.y.toFixed(2)} · Z: ${player.position.z.toFixed(2)}`;
}

const pointerLockTarget = renderer.domElement;

function createVoxelTextures(colors) {
    const cache = {};

    const make = (name, base, noise = 18, accent = null, lines = false) => {
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
        cache[name] = texture;
    };

    make('plaza', colors.plaza, 16, colors.plazaDark, true);
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
    make('water', colors.water, 8, 0xc7f4fb);
    make('flower', colors.flower, 14, 0xf8b8ca);
    make('cloth', colors.cloth, 12, 0x7e541d);
    make('skin', colors.skin, 10, 0xf3cb9e);
    make('shirt', colors.shirt, 12, 0x133d88);
    make('pants', colors.pants, 10, 0x1e2736);
    make('grass', colors.grass, 16, colors.grassDark);
    make('path', colors.path, 12, colors.plazaDark, true);
    make('patina', colors.patina, 12, colors.patinaDark, true);

    return cache;
}

function material(texture, extra = {}) {
    return new THREE.MeshStandardMaterial({
        map: texture,
        roughness: 0.94,
        metalness: 0.03,
        ...extra,
    });
}

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

function addLabelPlane({
    x,
    y,
    z,
    width,
    height,
    rotation = 0,
    text,
    key,
    background,
    foreground,
    accent,
    group = world,
}) {
    const texture = createLabelTexture(key, text, { background, foreground, accent });
    const plane = new THREE.Mesh(
        new THREE.PlaneGeometry(width, height),
        new THREE.MeshBasicMaterial({
            map: texture,
            transparent: false,
            side: THREE.DoubleSide,
        }),
    );

    plane.position.set(x, y, z);
    plane.rotation.y = rotation;
    group.add(plane);

    return plane;
}

function addCollisionBox(x, y, z, w, h, d) {
    collisions.push(new THREE.Box3().setFromCenterAndSize(
        new THREE.Vector3(x, y, z),
        new THREE.Vector3(w, h, d),
    ));
}

function addVoxelBox({
    x,
    y,
    z,
    w,
    h,
    d,
    texture = 'stone',
    group = world,
    collidable = false,
    castShadow = true,
    receiveShadow = true,
    opacity = 1,
    emissive = 0x000000,
}) {
    const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(w, h, d),
        material(textures[texture], {
            transparent: opacity < 1,
            opacity,
            emissive,
        }),
    );

    mesh.position.set(x, y, z);
    mesh.castShadow = castShadow;
    mesh.receiveShadow = receiveShadow;
    group.add(mesh);

    if (collidable) {
        // Static collisions must use world-space bounds because many buildings
        // live inside rotated groups around the plaza.
        mesh.updateWorldMatrix(true, false);
        collisions.push(new THREE.Box3().setFromObject(mesh));
    }

    return mesh;
}

function addRoundPlanterCollision(x, z, radius, height = 1.6) {
    addCollisionBox(x, height / 2, z, radius * 1.85, height, radius * 1.85);
}

function prepareModelShadows(root, {
    castShadow = false,
    receiveShadow = true,
} = {}) {
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

function brightenModel(root, {
    emissive = 0x2c2c2c,
    emissiveIntensity = 0.28,
    roughness = 0.78,
} = {}) {
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
            prepareModelShadows(palmModelTemplate, {
                castShadow: false,
                receiveShadow: false,
            });

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
            prepareModelShadows(lampModelTemplate, {
                castShadow: false,
                receiveShadow: false,
            });
            brightenModel(lampModelTemplate, {
                emissive: 0x3a3328,
                emissiveIntensity: 0.34,
                roughness: 0.72,
            });

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
        '/3D/paisaje_otono_voxel_4k.png',
        (texture) => {
            texture.colorSpace = THREE.SRGBColorSpace;
            texture.mapping = THREE.EquirectangularReflectionMapping;
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.ClampToEdgeWrapping;
            texture.magFilter = THREE.LinearFilter;
            texture.minFilter = THREE.LinearMipmapLinearFilter;
            texture.generateMipmaps = true;
            texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
            texture.repeat.x = -1;
            texture.needsUpdate = true;

            const skyDome = new THREE.Mesh(
                new THREE.SphereGeometry(250, 96, 64),
                new THREE.MeshBasicMaterial({
                    map: texture,
                    side: THREE.BackSide,
                    fog: false,
                }),
            );

            skyDome.position.set(plazaLayout.centerX, 52, plazaLayout.centerZ - 8);
            skyDome.rotation.y = Math.PI * 1.08;
            skyDome.renderOrder = -10;
            scene.add(skyDome);
            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/paisaje_otono_voxel_4k.png', error);
            settleSceneAsset();
        },
    );
}

function placePalmModel({ x, z, trunk, baseY = 0 }) {
    if (!palmModelTemplate) {
        pendingPalmPlacements.push({ x, z, trunk });

        return;
    }

    const palm = palmModelTemplate.clone(true);
    const rawBox = new THREE.Box3().setFromObject(palm);
    const rawSize = rawBox.getSize(new THREE.Vector3());
    const rawCenter = rawBox.getCenter(new THREE.Vector3());
    const targetHeight = trunk + 8;
    const scale = targetHeight / Math.max(rawSize.y, 1);

    palm.scale.setScalar(scale);

    const scaledBox = new THREE.Box3().setFromObject(palm);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

    palm.position.set(
        x - scaledCenter.x,
        baseY - scaledBox.min.y,
        z - scaledCenter.z,
    );
    palm.updateMatrix();
    palm.updateMatrixWorld(true);

    world.add(palm);
    addCollisionBox(x, Math.max(3.8, trunk * 0.32), z, 2.2, Math.max(7.5, trunk * 0.64), 2.2);
}

function placeLampModel({ x, z, height = 10 }) {
    if (!lampModelTemplate) {
        pendingLampPlacements.push({ x, z, height });

        return;
    }

    const lamp = lampModelTemplate.clone(true);
    const rawBox = new THREE.Box3().setFromObject(lamp);
    const rawSize = rawBox.getSize(new THREE.Vector3());
    const rawCenter = rawBox.getCenter(new THREE.Vector3());
    const targetHeight = height + 5;
    const scale = targetHeight / Math.max(rawSize.y, 1);

    lamp.scale.setScalar(scale);

    const scaledBox = new THREE.Box3().setFromObject(lamp);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

    lamp.position.set(
        x - scaledCenter.x,
        0.08 - scaledBox.min.y,
        z - scaledCenter.z,
    );
    lamp.rotation.y = Math.PI / 2;
    lamp.updateMatrix();
    lamp.updateMatrixWorld(true);

    world.add(lamp);
    addCollisionBox(x, Math.max(3, targetHeight * 0.28), z, 1.8, Math.max(6, targetHeight * 0.56), 1.8);
}

function buildPlayer() {
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

    avatar.userData.head = addVoxelBox({
        x: 0,
        y: 3.22,
        z: 0,
        w: 1.25,
        h: 1.25,
        d: 1.12,
        texture: 'skin',
        group: avatar.userData.body,
    });

    avatar.userData.hair = addVoxelBox({
        x: 0,
        y: 3.68,
        z: -0.08,
        w: 1.28,
        h: 0.42,
        d: 1.18,
        texture: 'woodDark',
        group: avatar.userData.body,
    });

    avatar.userData.torso = addVoxelBox({
        x: 0,
        y: 2.02,
        z: 0,
        w: 1.58,
        h: 1.8,
        d: 0.98,
        texture: 'shirt',
        group: avatar.userData.body,
    });

    avatar.userData.leftLeg = addVoxelBox({
        x: -0.38,
        y: 0.72,
        z: 0,
        w: 0.48,
        h: 1.44,
        d: 0.48,
        texture: 'pants',
        group: avatar.userData.body,
    });

    avatar.userData.rightLeg = addVoxelBox({
        x: 0.38,
        y: 0.72,
        z: 0,
        w: 0.48,
        h: 1.44,
        d: 0.48,
        texture: 'pants',
        group: avatar.userData.body,
    });

    avatar.userData.leftArm = addVoxelBox({
        x: -1.02,
        y: 2.08,
        z: 0,
        w: 0.36,
        h: 1.52,
        d: 0.36,
        texture: 'skin',
        group: avatar.userData.body,
    });

    avatar.userData.rightArm = addVoxelBox({
        x: 1.02,
        y: 2.08,
        z: 0,
        w: 0.36,
        h: 1.52,
        d: 0.36,
        texture: 'skin',
        group: avatar.userData.body,
    });

    return avatar;
}

function buildGround() {
    addVoxelBox({
        x: 0,
        y: -2,
        z: 0,
        w: 300,
        h: 4,
        d: 300,
        texture: 'grass',
        castShadow: false,
    });

    addVoxelBox({
        x: plazaLayout.centerX,
        y: -0.18,
        z: plazaLayout.centerZ,
        w: plazaLayout.baseWidth,
        h: 0.48,
        d: plazaLayout.baseDepth,
        texture: 'path',
        castShadow: false,
    });

    addVoxelBox({
        x: plazaLayout.centerX,
        y: 0.12,
        z: plazaLayout.centerZ,
        w: plazaLayout.plazaWidth,
        h: 0.3,
        d: plazaLayout.plazaDepth,
        texture: 'plaza',
        castShadow: false,
    });
}

function buildMountains() {
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
        addVoxelBox({
            x: px * scale,
            y: py * scale,
            z: pz * scale,
            w: w * scale,
            h: h * scale,
            d: d * scale,
            texture: 'white',
            castShadow: false,
            receiveShadow: false,
            group: cloud,
        });
    });

    cloud.position.set(x, y, z);
    scene.add(cloud);
}

function buildSkyProps() {
    buildCloud(-110, 56, -30, 0.74);
    buildCloud(-76, 60, -54, 0.9);
    buildCloud(-28, 57, -68, 1.02);
    buildCloud(18, 62, -24, 0.76);
    buildCloud(64, 58, -58, 0.92);
    buildCloud(106, 61, -18, 0.8);
}

function buildCityBackdrop() {
}

function buildPalm({ x, z, trunk = 18, baseY = 0 }) {
    placePalmModel({ x, z, trunk, baseY });
}

function buildTree({ x, z, height = 10 }) {
    for (let i = 0; i < height; i += 1) {
        addVoxelBox({
            x,
            y: i + 1,
            z,
            w: 1.1,
            h: 1,
            d: 1.1,
            texture: 'wood',
            collidable: i < 5,
        });
    }

    for (let lx = -3; lx <= 3; lx += 1) {
        for (let lz = -3; lz <= 3; lz += 1) {
            if (Math.abs(lx) + Math.abs(lz) > 4) {
                continue;
            }

            addVoxelBox({
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

function buildLamp({ x, z, height = 10, hanging = true }) {
    placeLampModel({ x, z, height });
}

function buildBench({ x, z, rotation = 0 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    world.add(group);

    addVoxelBox({ x: 0, y: 1.1, z: 0, w: 4.8, h: 0.48, d: 1.25, texture: 'trim', group });
    addVoxelBox({ x: 0, y: 1.95, z: -0.52, w: 4.8, h: 0.92, d: 0.35, texture: 'trim', group });
    addVoxelBox({ x: -1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
    addVoxelBox({ x: 1.9, y: 0.5, z: 0, w: 0.35, h: 1, d: 0.35, texture: 'wood', group });
}

function buildPlanter({ x, z, radius = 5, tree = null, flowers = false, trunk = 19 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    world.add(group);

    addVoxelBox({ x: 0, y: 0.52, z: 0, w: radius * 2.1, h: 1.05, d: radius * 2.1, texture: 'stone', group });
    addVoxelBox({ x: 0, y: 1.24, z: 0, w: radius * 1.62, h: 0.72, d: radius * 1.62, texture: flowers ? 'flower' : 'grass', group });
    addRoundPlanterCollision(x, z, radius);

    if (tree === 'palm') {
        buildPalm({ x, z, trunk, baseY: 1.6 });
    }

    if (tree === 'tree') {
        buildTree({ x, z, height: 10 });
    }
}

function buildArcadeRow({
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
    group.position.set(x, -0.9, z);
    group.rotation.y = rotation;
    world.add(group);

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

        addVoxelBox({ x: offset, y: bodyHeight / 2 + 0.9, z: 0, w: width, h: bodyHeight, d: depth, texture: paletteSwap, group, collidable: true });
        addVoxelBox({ x: offset, y: corniceY, z: upperInset * 0.35, w: width - 0.4, h: 0.36, d: depth - 0.48, texture: upperColor, group });
        addVoxelBox({ x: offset, y: roofBaseY, z: 0, w: width + 0.95, h: 0.95, d: depth + 0.85, texture: roofTexture, group });
        addVoxelBox({ x: offset, y: roofBaseY + 0.92, z: 0, w: width + 1.7, h: 0.7, d: depth + 1.5, texture: roofTexture, group });

        addVoxelBox({ x: offset - 2.05, y: 2.25, z: frontZ - 0.55, w: 0.62, h: 3.9, d: 0.62, texture: upperColor, group });
        addVoxelBox({ x: offset + 2.05, y: 2.25, z: frontZ - 0.55, w: 0.62, h: 3.9, d: 0.62, texture: upperColor, group });
        addVoxelBox({ x: offset, y: 4.15, z: frontZ - 0.55, w: 4.8, h: 0.62, d: 0.4, texture: upperColor, group });

        addVoxelBox({ x: offset, y: hasLargeDoor ? 1.5 : 1.18, z: frontZ, w: hasLargeDoor ? 2.45 : 1.9, h: hasLargeDoor ? 3.02 : 2.35, d: 0.24, texture: 'wood', group });
        addVoxelBox({ x: offset - 2.04, y: 1.55, z: frontZ, w: 1.02, h: 2.45, d: 0.22, texture: 'glass', group });
        addVoxelBox({ x: offset + 2.04, y: 1.55, z: frontZ, w: 1.02, h: 2.45, d: 0.22, texture: 'glass', group });
        addVoxelBox({ x: offset - 2.67, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset - 1.41, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset + 1.41, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset + 2.67, y: 1.55, z: frontZ + 0.06, w: shutterWidth, h: 2.45, d: 0.14, texture: shutterColor, group });

        if (hasBalcony) {
            const balconyWidth = width - 1.55;

            addVoxelBox({ x: offset, y: 5.15, z: frontZ + 0.08, w: balconyWidth, h: 0.26, d: 0.62, texture: upperColor, group });
            addVoxelBox({ x: offset, y: 5.65, z: frontZ + 0.28, w: balconyWidth, h: 0.2, d: 0.18, texture: 'woodDark', group });

            for (let rail = -2; rail <= 2; rail += 1) {
                addVoxelBox({
                    x: offset + rail * 0.78,
                    y: 5.92,
                    z: frontZ + 0.25,
                    w: 0.12,
                    h: 0.72,
                    d: 0.12,
                    texture: 'woodDark',
                    group,
                });
            }
        }

        addVoxelBox({ x: offset - 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        addVoxelBox({ x: offset + 2.04, y: windowY, z: frontZ + 0.02, w: 0.96, h: windowHeight, d: 0.18, texture: 'glass', group });
        addVoxelBox({ x: offset - 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset - 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset + 1.46, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset + 2.62, y: windowY, z: frontZ + 0.08, w: shutterWidth, h: windowHeight, d: 0.14, texture: shutterColor, group });
        addVoxelBox({ x: offset, y: hasBalcony ? 5.95 : 3.15, z: frontZ - 0.05, w: hasBalcony ? 1.3 : 1.15, h: hasBalcony ? 1.78 : 1.45, d: 0.18, texture: i % 2 === 0 ? 'glass' : upperColor, group });
    }
}

function buildColonialHouse({
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
    group.position.set(x, -0.9, z);
    group.rotation.y = rotation;
    world.add(group);

    addVoxelBox({ x: 0, y: bodyHeight / 2 + 0.9, z: 0, w: width, h: bodyHeight, d: depth, texture: paletteSwap, group, collidable: true });
    addVoxelBox({ x: 0, y: bodyHeight + 1.18, z: 0, w: width + 0.9, h: 1, d: depth + 1.1, texture: roofTexture, group });
    addVoxelBox({ x: 0, y: bodyHeight + 2, z: 0, w: width + 1.7, h: 0.7, d: depth + 1.8, texture: roofTexture, group });
    addVoxelBox({ x: 0, y: bodyHeight + 0.35, z: 0.12, w: width - 0.3, h: 0.34, d: depth - 0.5, texture: 'trim', group });

    const spacing = windows > 1 ? (width - 3.6) / (windows - 1) : 0;
    const startX = -((windows - 1) * spacing) / 2;

    for (let i = 0; i < windows; i += 1) {
        const wx = startX + i * spacing;
        const isDoorBay = Math.abs(wx - doorOffset) < 0.8;

        if (isDoorBay) {
            addVoxelBox({ x: wx, y: 1.55, z: 5.04, w: 2.35, h: 3.1, d: 0.22, texture: 'wood', group });
            continue;
        }

        addVoxelBox({ x: wx, y: 1.65, z: 5.02, w: 1.3, h: 2.5, d: 0.2, texture: 'glass', group });
        addVoxelBox({ x: wx - 0.78, y: 1.65, z: 5.08, w: 0.24, h: 2.5, d: 0.12, texture: shutterColor, group });
        addVoxelBox({ x: wx + 0.78, y: 1.65, z: 5.08, w: 0.24, h: 2.5, d: 0.12, texture: shutterColor, group });

        if (hasBalcony && i === Math.floor(windows / 2)) {
            addVoxelBox({ x: wx, y: 5.25, z: 5.18, w: 2.8, h: 0.24, d: 0.55, texture: 'trim', group });
            addVoxelBox({ x: wx, y: 5.72, z: 5.3, w: 2.8, h: 0.2, d: 0.18, texture: 'woodDark', group });
            addVoxelBox({ x: wx, y: 6.05, z: 5.12, w: 1.22, h: 1.82, d: 0.18, texture: 'glass', group });

            [-0.9, -0.45, 0, 0.45, 0.9].forEach((rail) => {
                addVoxelBox({ x: wx + rail, y: 5.98, z: 5.28, w: 0.1, h: 0.66, d: 0.1, texture: 'woodDark', group });
            });
        } else {
            addVoxelBox({ x: wx, y: 3.25, z: 4.98, w: 1.18, h: 1.56, d: 0.18, texture: 'glass', group });
        }
    }
}

function buildChurchSideComplex() {
}

function buildFlagCornerCluster() {
    buildColonialHouse({
        x: 84,
        z: -34,
        rotation: -Math.PI / 2,
        width: 14.8,
        depth: 10.4,
        bodyHeight: 7.4,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.4,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 84,
        z: -18,
        rotation: -Math.PI / 2,
        width: 13.2,
        depth: 9.8,
        bodyHeight: 6.8,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: 73,
        z: -51.5,
        width: 15.8,
        depth: 11.2,
        bodyHeight: 7.2,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 3.2,
        windows: 4,
    });
}

function buildFrontChurchEdge() {
    buildColonialHouse({
        x: -92,
        z: 84,
        rotation: Math.PI,
        width: 14.8,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.4,
        windows: 4,
    });

    buildColonialHouse({
        x: -72,
        z: 86,
        rotation: Math.PI,
        width: 17.2,
        depth: 11.2,
        bodyHeight: 7.6,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 3.1,
        hasBalcony: true,
        windows: 5,
    });

    buildColonialHouse({
        x: -50,
        z: 86,
        rotation: Math.PI,
        width: 15.4,
        depth: 10.8,
        bodyHeight: 7.3,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.5,
        windows: 4,
    });

    buildColonialHouse({
        x: -30,
        z: 86.5,
        rotation: Math.PI,
        width: 13.8,
        depth: 10.4,
        bodyHeight: 7,
        paletteSwap: 'stoneLight',
        shutterColor: 'leafDark',
        roofTexture: 'roof',
        doorOffset: 0,
        hasBalcony: true,
        windows: 3,
    });

    buildColonialHouse({
        x: -10,
        z: 86,
        rotation: Math.PI,
        width: 14.6,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 2.6,
        windows: 4,
    });

    buildColonialHouse({
        x: 12,
        z: 86,
        rotation: Math.PI,
        width: 16.2,
        depth: 11,
        bodyHeight: 7.4,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -3,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 34,
        z: 86,
        rotation: Math.PI,
        width: 14.4,
        depth: 10.6,
        bodyHeight: 7.1,
        paletteSwap: 'stoneLight',
        shutterColor: 'leafDark',
        roofTexture: 'roofClay',
        doorOffset: 2.2,
        windows: 4,
    });

    buildColonialHouse({
        x: 56,
        z: 86,
        rotation: Math.PI,
        width: 15.2,
        depth: 10.8,
        bodyHeight: 7.3,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.4,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 92,
        z: 86,
        rotation: Math.PI,
        width: 16,
        depth: 11.2,
        bodyHeight: 7.4,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -3,
        hasBalcony: true,
        windows: 4,
    });
}

function buildNorthernColonialEdge() {
    buildColonialHouse({
        x: -88,
        z: -98,
        width: 17.2,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: -3.1,
        windows: 4,
    });

    buildColonialHouse({
        x: -66,
        z: -98.5,
        width: 15.4,
        depth: 10.6,
        bodyHeight: 7.1,
        paletteSwap: 'coral',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 2.6,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: -45,
        z: -98.5,
        width: 12.8,
        depth: 10.2,
        bodyHeight: 6.8,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: -25,
        z: -98,
        width: 14.2,
        depth: 10.4,
        bodyHeight: 6.9,
        paletteSwap: 'butter',
        shutterColor: 'woodDark',
        roofTexture: 'roofClay',
        doorOffset: -2.2,
        windows: 4,
    });

    buildColonialHouse({
        x: -4,
        z: -98.5,
        width: 15.8,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 2.8,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 18,
        z: -98.5,
        width: 13.6,
        depth: 10.2,
        bodyHeight: 6.8,
        paletteSwap: 'coral',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: 38,
        z: -98.5,
        width: 14.8,
        depth: 10.4,
        bodyHeight: 7,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.4,
        windows: 4,
    });

    buildColonialHouse({
        x: 60,
        z: -98,
        width: 16.2,
        depth: 10.8,
        bodyHeight: 7.3,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 3,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 84,
        z: -98,
        width: 16.8,
        depth: 10.8,
        bodyHeight: 7.1,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -3.1,
        windows: 4,
    });
}

function buildNorthGrassBarrier() {
    buildColonialHouse({
        x: -92,
        z: -78,
        width: 14.8,
        depth: 10.4,
        bodyHeight: 7,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.6,
        windows: 4,
    });

    buildColonialHouse({
        x: -70,
        z: -78.5,
        width: 15.6,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 2.8,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: -48,
        z: -78.5,
        width: 13.4,
        depth: 10.2,
        bodyHeight: 6.8,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: -28,
        z: -78,
        width: 14.4,
        depth: 10.4,
        bodyHeight: 6.9,
        paletteSwap: 'coral',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: -2.2,
        windows: 4,
    });

    buildColonialHouse({
        x: -6,
        z: -78.5,
        width: 15.2,
        depth: 10.8,
        bodyHeight: 7.1,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 2.5,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 16,
        z: -78.5,
        width: 13.8,
        depth: 10.2,
        bodyHeight: 6.8,
        paletteSwap: 'butter',
        shutterColor: 'woodDark',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: 38,
        z: -78.5,
        width: 14.6,
        depth: 10.4,
        bodyHeight: 7,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.3,
        windows: 4,
    });

    buildColonialHouse({
        x: 60,
        z: -78,
        width: 15.8,
        depth: 10.8,
        bodyHeight: 7.2,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 3,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 84,
        z: -78,
        width: 16.2,
        depth: 10.8,
        bodyHeight: 7.1,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -3,
        windows: 4,
    });
}

function buildSouthGrassBarrier() {
    buildColonialHouse({
        x: -92,
        z: 80.5,
        rotation: Math.PI,
        width: 15.2,
        depth: 10.6,
        bodyHeight: 7.4,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.6,
        windows: 4,
    });

    buildColonialHouse({
        x: -71,
        z: 80.8,
        rotation: Math.PI,
        width: 16.4,
        depth: 10.8,
        bodyHeight: 7.6,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 2.8,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: -50,
        z: 81,
        rotation: Math.PI,
        width: 13.6,
        depth: 10.2,
        bodyHeight: 7.1,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: -29,
        z: 80.6,
        rotation: Math.PI,
        width: 14.8,
        depth: 10.4,
        bodyHeight: 7.2,
        paletteSwap: 'coral',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: -2.2,
        windows: 4,
    });

    buildColonialHouse({
        x: -8,
        z: 81,
        rotation: Math.PI,
        width: 15.6,
        depth: 10.8,
        bodyHeight: 7.5,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: 2.5,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 14,
        z: 81,
        rotation: Math.PI,
        width: 14,
        depth: 10.2,
        bodyHeight: 7,
        paletteSwap: 'butter',
        shutterColor: 'woodDark',
        roofTexture: 'roofClay',
        doorOffset: 0,
        windows: 3,
    });

    buildColonialHouse({
        x: 37,
        z: 81,
        rotation: Math.PI,
        width: 15,
        depth: 10.4,
        bodyHeight: 7.3,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -2.3,
        windows: 4,
    });

    buildColonialHouse({
        x: 60,
        z: 80.6,
        rotation: Math.PI,
        width: 16,
        depth: 10.8,
        bodyHeight: 7.6,
        paletteSwap: 'ochre',
        shutterColor: 'white',
        roofTexture: 'roofClay',
        doorOffset: 3,
        hasBalcony: true,
        windows: 4,
    });

    buildColonialHouse({
        x: 84,
        z: 80.5,
        rotation: Math.PI,
        width: 16.6,
        depth: 10.8,
        bodyHeight: 7.4,
        paletteSwap: 'white',
        shutterColor: 'leaf',
        roofTexture: 'roofClay',
        doorOffset: -3,
        windows: 4,
    });
}

function buildCathedral() {
    const anchor = new THREE.Group();
    anchor.position.set(plazaLayout.cathedralX, 0, plazaLayout.cathedralZ);
    world.add(anchor);

    addCollisionBox(plazaLayout.cathedralX, 9, plazaLayout.cathedralZ - 1, 30, 18, 54);
    addCollisionBox(plazaLayout.cathedralX, 12, plazaLayout.cathedralZ + 22, 46, 24, 11);

    gltfLoader.load(
        '/3D/catedral-zipa-voxel.glb',
        (gltf) => {
            const model = gltf.scene;
            prepareModelShadows(model, {
                castShadow: false,
                receiveShadow: true,
            });

            const localBox = new THREE.Box3().setFromObject(model);
            const size = localBox.getSize(new THREE.Vector3());
            const center = localBox.getCenter(new THREE.Vector3());

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

            model.position.set(
                -scaledCenter.x,
                -scaledBox.min.y,
                -scaledCenter.z + 1.5,
            );
            model.rotation.y = Math.PI * 2;
            model.updateMatrix();
            model.updateMatrixWorld(true);

            anchor.add(model);

            const worldBox = new THREE.Box3().setFromObject(model);
            collisions.push(worldBox.clone());
            collisions.push(new THREE.Box3().setFromCenterAndSize(
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

loadPalmModel();
loadLampModel();
loadSkyDome();

function buildWhiteBuilding() {
    const anchor = new THREE.Group();
    anchor.position.set(plazaLayout.annexX, 0, plazaLayout.annexZ);
    world.add(anchor);

    addCollisionBox(plazaLayout.annexX, 20, plazaLayout.annexZ, 38, 40, 55);

    gltfLoader.load(
        '/3D/alcaldia1.glb',
        (gltf) => {
            const model = gltf.scene;
            prepareModelShadows(model, {
                castShadow: false,
                receiveShadow: true,
            });

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

            model.position.set(
                -scaledCenter.x,
                -scaledBox.min.y,
                -scaledCenter.z,
            );
            model.rotation.y = Math.PI / 2;
            model.updateMatrix();
            model.updateMatrixWorld(true);

            anchor.add(model);

            const worldBox = new THREE.Box3().setFromObject(model);
            collisions.push(worldBox.clone());
            settleSceneAsset();
        },
        undefined,
        (error) => {
            console.error('No se pudo cargar public/3D/alcaldia1.glb', error);
            settleSceneAsset();
        },
    );
}

function buildCornerDomeBuilding({ x, z, rotation = 0, domeColor = 'flower' }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    world.add(group);

    addVoxelBox({ x: 0, y: 7.4, z: 0, w: 12.8, h: 14.8, d: 10.8, texture: 'white', group, collidable: true });
    addVoxelBox({ x: 0, y: 14.8, z: 0, w: 13.8, h: 1, d: 11.8, texture: 'trim', group });
    addVoxelBox({ x: 0, y: 18.4, z: 0, w: 4.6, h: 4.6, d: 4.6, texture: domeColor, group });
    addVoxelBox({ x: 0, y: 21.6, z: 0, w: 2.4, h: 2.2, d: 2.4, texture: 'glass', group });
    addVoxelBox({ x: 0, y: 23.6, z: 0, w: 0.45, h: 1.4, d: 0.45, texture: 'white', group });
}

function buildPerimeterArchitecture() {
    buildWhiteBuilding();
    buildImmersiveStorefronts();
}

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
    world.add(group);

    buildColonialHouse({
        x: slot.x,
        z: slot.z,
        rotation: slot.rotation,
        width: 17,
        depth: 10.6,
        bodyHeight: 7.3,
        paletteSwap: slot.paletteSwap,
        roofTexture: 'roofClay',
        shutterColor: slot.shutterColor,
        doorOffset: 0,
        hasBalcony: true,
        windows: 4,
    });

    addLabelPlane({
        x: 0,
        y: 6.9,
        z: 5.45,
        width: 7.8,
        height: 2.2,
        text: [business.name, business.headline || 'Vitrina local'],
        key: `business-sign-${business.slug}-${index}`,
        background: '#f1dfb6',
        foreground: '#412817',
        accent: '#9f552a',
        group,
    });

    const displayCount = Math.min(business.products.length, 3);
    const startX = -((displayCount - 1) * 3.7) / 2;

    business.products.slice(0, 3).forEach((product, productIndex) => {
        const localX = startX + (productIndex * 3.7);
        const localZ = 8.5;

        addVoxelBox({
            x: localX,
            y: 0.85,
            z: localZ,
            w: 2.8,
            h: 1.7,
            d: 2.2,
            texture: 'stoneLight',
            group,
        });

        addVoxelBox({
            x: localX,
            y: 1.95,
            z: localZ,
            w: 2,
            h: 0.48,
            d: 1.48,
            texture: product.is_available ? 'cloth' : 'woodDark',
            group,
        });

        addLabelPlane({
            x: localX,
            y: 3.55,
            z: localZ,
            width: 3,
            height: 1.28,
            text: [product.name, formatProductPrice(product)],
            key: `product-sign-${business.slug}-${productIndex}`,
            background: product.is_available ? '#fff5dd' : '#d9d0c3',
            foreground: '#40291b',
            accent: product.is_available ? '#cc6e2d' : '#6d5c4d',
            group,
        });
    });
}

function buildPlazaSteps() {
    // Las escalinatas ahora vienen resueltas por el modelo de la catedral.
}

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

    buildLamp({ x: -96, z: -6, height: 14, hanging: true });
    buildLamp({ x: 96, z: -8, height: 15, hanging: true });
    buildLamp({ x: 30, z: 62, height: 14, hanging: true });
}

function buildLongBench({ x, z, rotation = 0, length = 16 }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    group.rotation.y = rotation;
    world.add(group);

    addVoxelBox({ x: 0, y: 0.9, z: 0, w: length, h: 0.9, d: 2.2, texture: 'stone', group });
    addCollisionBox(x, 1.1, z, rotation === 0 ? length : 2.2, 2.2, rotation === 0 ? 2.2 : length);
}

function buildPlazaEdges() {
    for (let i = plazaLayout.edgeWest; i <= plazaLayout.edgeEast; i += 6) {
        addVoxelBox({ x: i, y: 1.05, z: plazaLayout.edgeSouth, w: 1.3, h: 2.1, d: 1.3, texture: 'stone', collidable: true });
    }

    for (let i = plazaLayout.edgeNorth + 16; i <= plazaLayout.edgeSouth - 24; i += 5.8) {
        addVoxelBox({ x: plazaLayout.edgeWest, y: 1.05, z: i, w: 1.25, h: 2.1, d: 1.25, texture: 'stone', collidable: true });
        addVoxelBox({ x: plazaLayout.edgeEast, y: 1.05, z: i, w: 1.25, h: 2.1, d: 1.25, texture: 'stone', collidable: true });
    }

    addCollisionBox(plazaLayout.centerX, 4, plazaLayout.edgeSouth + 9, 214, 8, 6);
    addCollisionBox(plazaLayout.edgeWest - 9, 5, plazaLayout.centerZ, 6, 10, 150);
    addCollisionBox(plazaLayout.edgeEast + 9, 5, plazaLayout.centerZ, 6, 10, 150);
    addCollisionBox(plazaLayout.centerX, 6, plazaLayout.edgeNorth - 9, 214, 12, 8);
}

function buildCentralPlaza() {
    buildPlazaSteps();
    buildPlazaStreetFurniture();
}

function createColorTexture(hex) {
    const key = `shirt-${hex.toString(16)}`;

    if (textures[key]) {
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
    textures[key] = texture;

    return key;
}

function createNpc(x, z, shirtColor, seed = 0) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    world.add(group);

    const body = new THREE.Group();
    group.add(body);

    const shirtTexture = createColorTexture(shirtColor);

    addVoxelBox({ x: 0, y: 1.7, z: 0, w: 1.02, h: 2.18, d: 0.8, texture: shirtTexture, group: body });
    addVoxelBox({ x: 0, y: 3.14, z: 0, w: 0.94, h: 0.98, d: 0.72, texture: 'skin', group: body });
    addVoxelBox({ x: -0.25, y: 0.5, z: 0, w: 0.3, h: 1, d: 0.3, texture: 'wood', group: body });
    addVoxelBox({ x: 0.25, y: 0.5, z: 0, w: 0.3, h: 1, d: 0.3, texture: 'wood', group: body });

    animatedActors.push({
        group,
        body,
        phase: seed,
        drift: 0.16 + (seed % 3) * 0.05,
    });
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
    ].forEach(([x, z, shirtColor, seed]) => createNpc(x, z, shirtColor, seed));
}

function buildWorld() {
    buildGround();
    buildMountains();
    buildSkyProps();
    buildCityBackdrop();
    buildPerimeterArchitecture();
    buildCathedral();
    buildCentralPlaza();
    buildPlazaEdges();
    buildPeople();
}

function getPlayerBounds(position) {
    const centerY = position.y + playerState.height / 2;
    return new THREE.Box3().setFromCenterAndSize(
        new THREE.Vector3(position.x, centerY, position.z),
        new THREE.Vector3(playerState.radius * 2, playerState.height, playerState.radius * 2),
    );
}

function collides(position) {
    const bounds = getPlayerBounds(position);
    return collisions.some((box) => box.intersectsBox(bounds));
}

function updateActors(time) {
    animatedActors.forEach((actor) => {
        actor.body.position.y = Math.sin(time * actor.drift + actor.phase) * 0.06;
        actor.group.rotation.y = Math.sin(time * 0.25 + actor.phase) * 0.2;
    });
}

function updatePlayerAnimation(delta, speedRatio) {
    const cycle = performance.now() * 0.012;
    const sprintBoost = movement.sprint ? 1.28 : 1;
    const swing = speedRatio > 0.03 ? Math.sin(cycle * sprintBoost) * 0.62 * speedRatio : 0;
    const bounce = speedRatio > 0.03 ? Math.abs(Math.sin(cycle * sprintBoost)) * 0.11 * speedRatio : 0;

    player.userData.body.position.y = bounce;
    player.userData.leftArm.rotation.x = swing;
    player.userData.rightArm.rotation.x = -swing;
    player.userData.leftLeg.rotation.x = -swing;
    player.userData.rightLeg.rotation.x = swing;

    if (!playerState.onGround) {
        player.userData.leftArm.rotation.x = -0.34;
        player.userData.rightArm.rotation.x = -0.34;
        player.userData.leftLeg.rotation.x = 0.18;
        player.userData.rightLeg.rotation.x = -0.18;
    }

    player.userData.body.rotation.y = THREE.MathUtils.damp(
        player.userData.body.rotation.y,
        speedRatio > 0.03 ? Math.sin(performance.now() * 0.004) * 0.08 * speedRatio : 0,
        8,
        delta,
    );
    player.userData.shadow.scale.setScalar(1 - Math.min(0.25, player.position.y * 0.04));
}

function updatePlayer(delta) {
    const input = new THREE.Vector3();
    const forward = new THREE.Vector3(Math.sin(controls.yaw), 0, Math.cos(controls.yaw));
    const right = new THREE.Vector3(Math.cos(controls.yaw), 0, -Math.sin(controls.yaw));

    if (movement.forward) input.z -= 1;
    if (movement.backward) input.z += 1;
    if (movement.left) input.x -= 1;
    if (movement.right) input.x += 1;

    const hasInput = input.lengthSq() > 0;
    const desiredVelocity = new THREE.Vector3();

    if (hasInput) {
        input.normalize();
        const speedMultiplier = movement.sprint && !movement.backward ? playerState.sprintSpeed : playerState.moveSpeed;
        const directionalSpeed =
            (input.z > 0 ? playerState.backpedalFactor : 1)
            * (input.x !== 0 && input.z === 0 ? playerState.strafeFactor : 1);

        desiredVelocity
            .copy(right)
            .multiplyScalar(input.x)
            .addScaledVector(forward, input.z)
            .normalize()
            .multiplyScalar(speedMultiplier * directionalSpeed);

        const acceleration = playerState.onGround ? playerState.acceleration : playerState.airAcceleration;
        playerState.velocity.x = THREE.MathUtils.damp(playerState.velocity.x, desiredVelocity.x, acceleration, delta);
        playerState.velocity.z = THREE.MathUtils.damp(playerState.velocity.z, desiredVelocity.z, acceleration, delta);

        const facingAngle = Math.atan2(desiredVelocity.x, desiredVelocity.z);
        player.rotation.y = THREE.MathUtils.damp(player.rotation.y, facingAngle, 10, delta * (playerState.turnSpeed * 10));
    } else {
        playerState.velocity.x = THREE.MathUtils.damp(playerState.velocity.x, 0, playerState.drag, delta);
        playerState.velocity.z = THREE.MathUtils.damp(playerState.velocity.z, 0, playerState.drag, delta);
    }

    playerState.jumpCooldown = Math.max(0, playerState.jumpCooldown - delta);

    if (movement.jump && playerState.onGround && playerState.jumpCooldown === 0) {
        playerState.velocity.y = playerState.jumpVelocity;
        playerState.onGround = false;
        playerState.jumpCooldown = 0.22;
    }

    playerState.velocity.y -= playerState.gravity * delta;

    const next = player.position.clone();
    next.x += playerState.velocity.x * delta;
    if (!collides(next)) {
        player.position.x = next.x;
    } else {
        playerState.velocity.x *= 0.18;
    }

    next.copy(player.position);
    next.z += playerState.velocity.z * delta;
    if (!collides(next)) {
        player.position.z = next.z;
    } else {
        playerState.velocity.z *= 0.18;
    }

    player.position.y += playerState.velocity.y * delta;

    if (player.position.y <= playerState.feetY) {
        player.position.y = playerState.feetY;
        playerState.velocity.y = 0;
        playerState.onGround = true;
    } else {
        playerState.onGround = false;
    }

    const horizontalSpeed = new THREE.Vector2(playerState.velocity.x, playerState.velocity.z).length() / playerState.moveSpeed;
    updatePlayerAnimation(delta, Math.min(horizontalSpeed, 1));
}

function updateCamera() {
    const yawVector = new THREE.Vector3(Math.sin(controls.yaw), 0, Math.cos(controls.yaw));
    const desiredDistance = movement.sprint ? cameraState.sprintDistance : cameraState.distance;
    const desiredHeight = movement.sprint ? cameraState.sprintHeight : cameraState.height;
    const desiredPosition = player.position.clone().add(
        new THREE.Vector3(
            yawVector.x * desiredDistance + Math.cos(controls.yaw) * cameraState.shoulder,
            desiredHeight + Math.sin(-controls.pitch) * 2.1,
            yawVector.z * desiredDistance - Math.sin(controls.yaw) * cameraState.shoulder,
        ),
    );

    camera.position.lerp(desiredPosition, cameraState.smoothing);

    cameraState.target.lerp(player.position.clone().add(new THREE.Vector3(0, 3.35, 0)), 0.22);
    camera.lookAt(cameraState.target);
}

function animate() {
    requestAnimationFrame(animate);

    const delta = Math.min(clock.getDelta(), 0.05);
    const time = clock.elapsedTime;

    updatePlayer(delta);
    updateActors(time);
    updateCamera();
    updateCoordinatesDisplay();
    renderer.render(scene, camera);
}

function lockPointer() {
    pointerLockTarget.requestPointerLock?.();
}

function onPointerLockChange() {
    controls.isLocked = document.pointerLockElement === pointerLockTarget;
    container.classList.toggle('is-locked', controls.isLocked);
}

function onMouseMove(event) {
    if (!controls.isLocked && !controls.isDragging) {
        return;
    }

    const deltaX = controls.isLocked ? event.movementX : event.clientX - controls.lastX;
    const deltaY = controls.isLocked ? event.movementY : event.clientY - controls.lastY;

    controls.lastX = event.clientX;
    controls.lastY = event.clientY;
    controls.yaw -= deltaX * controls.mouseSensitivity;
    controls.pitch -= deltaY * controls.mouseSensitivity * 0.88;
    controls.pitch = THREE.MathUtils.clamp(controls.pitch, controls.minPitch, controls.maxPitch);
}

function onMouseDown(event) {
    controls.isDragging = true;
    controls.lastX = event.clientX;
    controls.lastY = event.clientY;
}

function onMouseUp() {
    controls.isDragging = false;
}

function setMovement(code, pressed) {
    if (code === 'ArrowUp' || code === 'KeyW') movement.forward = pressed;
    if (code === 'ArrowDown' || code === 'KeyS') movement.backward = pressed;
    if (code === 'ArrowLeft' || code === 'KeyA') movement.left = pressed;
    if (code === 'ArrowRight' || code === 'KeyD') movement.right = pressed;
    if (code === 'Space') movement.jump = pressed;
    if (code === 'ShiftLeft' || code === 'ShiftRight') movement.sprint = pressed;
}

window.addEventListener('keydown', (event) => {
    setMovement(event.code, true);
});

window.addEventListener('keyup', (event) => {
    setMovement(event.code, false);
});

window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

pointerLockTarget.addEventListener('click', lockPointer);
pointerLockTarget.addEventListener('mousedown', onMouseDown);
window.addEventListener('mouseup', onMouseUp);
window.addEventListener('mousemove', onMouseMove);
document.addEventListener('pointerlockchange', onPointerLockChange);
lockTrigger?.addEventListener('click', lockPointer);

buildWorld();
updateCamera();
updateCoordinatesDisplay();
baseSceneReady = true;
syncLoadingOverlay();
animate();
