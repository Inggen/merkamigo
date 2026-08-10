/**
 * Lab inmersivo del parque principal de Cajicá. A diferencia del primer
 * lab (Zipaquirá), este archivo no reimplementa personaje, cámara,
 * física ni texturas: todo eso vive en `lib/voxel-plaza-engine.js` y se
 * reutiliza tal cual. Aquí solo describimos QUÉ hay en esta plaza y
 * DÓNDE, como datos (`layout`), más la fachada de la iglesia, que es lo
 * único realmente único de este lugar.
 */
import { THREE, VoxelPlazaEngine, standardBuilders, basePalette } from './lib/voxel-plaza-engine.js';
import { loadDynamicStands } from './lib/dynamic-stand-loader.js';

const container = document.getElementById('cajica-immersive-scene');
const lockTrigger = document.getElementById('cajica-lock-trigger');

if (!container) {
    throw new Error('Cajica immersive container not found.');
}

// Paleta cálida de Cajicá: pavimento de ladrillo/terracota en vez de la
// piedra amarilla de Zipaquirá; el resto de colores base se reutiliza.
const palette = {
    ...basePalette,
    plaza: 0xb5502e,
    plazaDark: 0x8a3c22,
    path: 0xc06a3d,
};

const plaza = { centerX: 0, centerZ: -4, width: 66, depth: 58 };
const church = { x: 0, z: -40 };

const engine = new VoxelPlazaEngine({
    container,
    lockTrigger,
    palette,
    fog: { color: 0xb9d9ee, near: 70, far: 230 },
    playerStart: { x: 2, z: 18 },
    playerFacing: 0.22,
});

// --- Suelo de la plaza ---------------------------------------------------
// Dos capas, igual que en Zipaquirá: un camino ancho de piedra y encima
// el piso de ladrillo de la plaza propiamente dicha.
const groundLayout = [
    {
        type: 'voxelBox', x: plaza.centerX, y: -0.18, z: plaza.centerZ, w: plaza.width + 24, h: 0.48, d: plaza.depth + 24, texture: 'path', castShadow: false,
    },
    {
        type: 'voxelBox', x: plaza.centerX, y: 0.12, z: plaza.centerZ, w: plaza.width, h: 0.3, d: plaza.depth, texture: 'plaza', castShadow: false,
    },
    // Camino en cruz que separa los cuatro cuadrantes ajardinados.
    {
        type: 'voxelBox', x: plaza.centerX, y: 0.2, z: plaza.centerZ, w: plaza.width - 6, h: 0.12, d: 6.4, texture: 'brickAccent', castShadow: false,
    },
    {
        type: 'voxelBox', x: plaza.centerX, y: 0.2, z: plaza.centerZ, w: 6.4, h: 0.12, d: plaza.depth - 6, texture: 'brickAccent', castShadow: false,
    },
];

// --- Jardines de los cuatro cuadrantes -----------------------------------
// En vez de escribir a mano la posición de cada seto/árbol/banca (como en
// el primer lab), describimos "un jardín" una sola vez y lo repetimos en
// los cuatro cuadrantes con `engine.ring`.
function quadrantGarden(cx, cz) {
    return [
        {
            type: 'hedgeRect', x: cx, z: cz, width: 13, depth: 11, height: 1.05, flowerBand: true,
        },
        {
            type: 'tree', x: cx, z: cz, height: 9,
        },
        {
            type: 'bench', x: cx, z: cz + 8.5, rotation: 0,
        },
    ];
}

const gardenQuadrants = engine.ring({
    center: { x: plaza.centerX, z: plaza.centerZ },
    radius: 14,
    count: 4,
    startAngle: Math.PI / 4,
    build: (i, pos) => quadrantGarden(pos.x, pos.z),
});

// --- Fuente, estatua y mobiliario central ---------------------------------
const centerpieces = [
    { type: 'fountain', x: plaza.centerX, z: plaza.centerZ, radius: 5.4 },
    { type: 'statue', x: plaza.centerX + 20, z: plaza.centerZ + 4, height: 6.4 },
    { type: 'lamp', x: plaza.centerX - 16, z: plaza.centerZ - 14, height: 11 },
    { type: 'lamp', x: plaza.centerX + 16, z: plaza.centerZ - 14, height: 11 },
    { type: 'lamp', x: plaza.centerX - 16, z: plaza.centerZ + 14, height: 11 },
    { type: 'lamp', x: plaza.centerX + 16, z: plaza.centerZ + 14, height: 11 },
    { type: 'marketStall', x: plaza.centerX + 8, z: plaza.centerZ + 20, rotation: -0.4 },
    { type: 'npc', x: plaza.centerX + 6.5, z: plaza.centerZ + 22, shirtColor: 0xcd632c, seed: 0.4 },
    { type: 'npc', x: plaza.centerX + 9.5, z: plaza.centerZ + 23, shirtColor: 0x5b9132, seed: 1.6 },
    { type: 'npc', x: plaza.centerX + 10.5, z: plaza.centerZ + 19, shirtColor: 0x724eb8, seed: 3.2 },
];

// --- Edificios que rodean la plaza ----------------------------------------
// `rectPerimeter` reparte casas coloniales/arcadas a lo largo del
// perímetro sin que tengamos que calcular cada x/z a mano; el lado norte
// (donde está la iglesia) se filtra devolviendo `null`.
const perimeterBuildings = engine.rectPerimeter({
    x: plaza.centerX,
    z: plaza.centerZ,
    width: plaza.width + 30,
    depth: plaza.depth + 34,
    step: 15.5,
    build: (side, index, pos, rotation) => {
        const isNorthSide = side === 0;
        const nearChurch = Math.abs(pos.x - church.x) < 26;

        if (isNorthSide && nearChurch) {
            return null;
        }

        const swaps = ['white', 'butter', 'coral', 'ochre'];
        const paletteSwap = swaps[(side + index) % swaps.length];

        return {
            type: 'arcadeRow',
            x: pos.x,
            z: pos.z,
            rotation,
            sections: 1,
            spacing: 0,
            paletteSwap,
            upperColor: 'trim',
            shutterColor: index % 2 === 0 ? 'leaf' : 'butter',
            roofTexture: 'roofClay',
            depth: 8.4,
        };
    },
});

// --- Calle perimetral con vehículos estacionados --------------------------
const streetVehicles = engine.row({
    from: { x: plaza.centerX - 34, z: plaza.centerZ + 30 },
    step: { x: 9.5, z: 0 },
    count: 6,
    build: (i, pos) => ({
        type: 'vehicle',
        x: pos.x,
        z: pos.z,
        rotation: Math.PI / 2,
        bodyColor: ['shirt', 'accent', 'trim', 'iron', 'butter', 'stone'][i % 6],
        vehicleType: i % 3 === 0 ? 'pickup' : 'car',
    }),
});

// --- Entorno lejano: montañas y nubes de sabana ---------------------------
function buildSavannaHills(engine) {
    const group = new THREE.Group();
    engine.world.add(group);

    const peaks = [
        [-70, 5, -110, 46, 15, 12],
        [0, 6, -122, 68, 18, 14],
        [70, 5, -112, 50, 16, 12],
    ];

    peaks.forEach(([x, y, z, width, height, layers]) => {
        for (let i = 0; i < layers; i += 1) {
            const factor = 1 - i / layers;
            engine.addVoxelBox({
                x,
                y: y + i * 1.1,
                z,
                w: Math.max(8, width * factor),
                h: 1.2,
                d: Math.max(8, width * 0.8 * factor),
                texture: 'mountain',
                group,
                castShadow: false,
            });
        }
    });
}

const skyProps = [
    { type: 'cloud', x: -90, y: 50, z: -40, scale: 0.8 },
    { type: 'cloud', x: -30, y: 54, z: -60, scale: 1 },
    { type: 'cloud', x: 40, y: 52, z: -50, scale: 0.85 },
    { type: 'cloud', x: 88, y: 48, z: -30, scale: 0.75 },
];

// --- Iglesia de una sola torre (referencia fotográfica) -------------------
// Torre central, ventana en rosetón, portada en arco y remate con cruz;
// a lado y lado, casas coloniales de dos pisos siguiendo la fachada.
function buildCajicaChurch(engine, { x, z }) {
    const group = new THREE.Group();
    group.position.set(x, 0, z);
    engine.world.add(group);

    const add = (opts) => engine.addVoxelBox({ ...opts, group });

    // Nave principal.
    add({
        x: 0, y: 7.4, z: -6, w: 22, h: 14.8, d: 34, texture: 'butter', collidable: true,
    });
    add({
        x: 0, y: 14.9, z: -6, w: 24.4, h: 1, d: 36, texture: 'white',
    });
    add({
        x: 0, y: 16.1, z: -8, w: 19, h: 1, d: 28, texture: 'roofClay',
    });
    add({
        x: 0, y: 17.1, z: -8, w: 14.6, h: 0.9, d: 22, texture: 'roofClay',
    });

    // Fachada principal (mira hacia +z, hacia la plaza).
    const frontZ = 11;

    add({
        x: 0, y: 9.6, z: frontZ - 1.4, w: 16.4, h: 19.2, d: 3.2, texture: 'butter', collidable: true,
    });
    add({
        x: -8.4, y: 9.6, z: frontZ - 1.4, w: 0.9, h: 19.2, d: 3.2, texture: 'white',
    });
    add({
        x: 8.4, y: 9.6, z: frontZ - 1.4, w: 0.9, h: 19.2, d: 3.2, texture: 'white',
    });
    add({
        x: 0, y: 19.3, z: frontZ - 1.4, w: 17.4, h: 0.7, d: 3.6, texture: 'white',
    });

    // Portada en arco escalonado sobre la puerta.
    add({
        x: 0, y: 2.4, z: frontZ + 0.2, w: 3.6, h: 4.8, d: 0.5, texture: 'wood',
    });
    [
        [3.9, 5.2, 0.7],
        [3.3, 5.9, 0.5],
        [2.6, 6.5, 0.4],
    ].forEach(([w, y, d]) => add({
        x: 0, y, z: frontZ + 0.15, w, h: 0.55, d, texture: 'white',
    }));
    add({
        x: -2.6, y: 3.4, z: frontZ + 0.35, w: 0.4, h: 6.6, d: 0.4, texture: 'white',
    });
    add({
        x: 2.6, y: 3.4, z: frontZ + 0.35, w: 0.4, h: 6.6, d: 0.4, texture: 'white',
    });

    // Puertas laterales pequeñas.
    [-6, 6].forEach((sx) => {
        add({
            x: sx, y: 2, z: frontZ + 0.1, w: 1.8, h: 3.6, d: 0.4, texture: 'wood',
        });
        add({
            x: sx, y: 4.2, z: frontZ + 0.15, w: 2.2, h: 0.4, d: 0.5, texture: 'white',
        });
    });

    // Rosetón: núcleo de vidrio con rayos de cantera radiando.
    const roseY = 10.6;
    add({
        x: 0, y: roseY, z: frontZ + 0.2, w: 2.6, h: 2.6, d: 0.4, texture: 'glass',
    });
    for (let i = 0; i < 8; i += 1) {
        const angle = (Math.PI * 2 * i) / 8;
        add({
            x: Math.cos(angle) * 1.9,
            y: roseY + Math.sin(angle) * 1.9,
            z: frontZ + 0.15,
            w: 0.35,
            h: 1.5,
            d: 0.3,
            texture: 'white',
            rotationY: angle,
        });
    }
    add({
        x: 0, y: roseY, z: frontZ + 0.45, w: 3.6, h: 0.35, d: 0.3, texture: 'white',
    });
    add({
        x: 0, y: roseY + 1.9, z: frontZ + 0.2, w: 3.9, h: 0.35, d: 0.3, texture: 'white',
    });

    // Cornisas horizontales blancas marcando los niveles de la fachada.
    [4.9, 14.6].forEach((y) => add({
        x: 0, y, z: frontZ + 0.05, w: 16.8, h: 0.4, d: 0.35, texture: 'white',
    }));

    // Torre central escalonada con campanario, linterna y cruz.
    const towerX = 0;
    const towerZ = frontZ - 1.4;

    add({
        x: towerX, y: 20.2, z: towerZ, w: 7.6, h: 3.4, d: 7.2, texture: 'butter', collidable: true,
    });
    add({
        x: towerX, y: 22, z: towerZ, w: 8.6, h: 0.5, d: 8.2, texture: 'white',
    });

    // Campanario con arcos (aberturas oscuras simulan las campanas).
    add({
        x: towerX, y: 25, z: towerZ, w: 6.2, h: 3.2, d: 5.9, texture: 'white', collidable: true,
    });
    [-1.7, 1.7].forEach((bx) => {
        add({
            x: towerX + bx, y: 25, z: towerZ + 2.96, w: 1, h: 2.1, d: 0.3, texture: 'iron',
        });
        add({
            x: towerX + bx, y: 25, z: towerZ - 2.96, w: 1, h: 2.1, d: 0.3, texture: 'iron',
        });
    });
    add({
        x: towerX, y: 26.9, z: towerZ, w: 7.2, h: 0.5, d: 6.9, texture: 'white',
    });

    add({
        x: towerX, y: 28.9, z: towerZ, w: 3.6, h: 3.4, d: 3.6, texture: 'butter',
    });
    add({
        x: towerX, y: 30.9, z: towerZ, w: 4.2, h: 0.4, d: 4.2, texture: 'white',
    });
    add({
        x: towerX, y: 32.3, z: towerZ, w: 2, h: 2, d: 2, texture: 'roofClay',
    });
    add({
        x: towerX, y: 33.7, z: towerZ, w: 0.9, h: 0.9, d: 0.9, texture: 'white',
    });
    add({
        x: towerX, y: 34.6, z: towerZ, w: 0.22, h: 1.3, d: 0.22, texture: 'iron',
    });
    add({
        x: towerX, y: 35, z: towerZ, w: 0.85, h: 0.22, d: 0.22, texture: 'iron',
    });

    // Casas coloniales adosadas a cada lado de la iglesia.
    [
        { x: -19, paletteSwap: 'white', doorOffset: -2, hasBalcony: true },
        { x: 19, paletteSwap: 'white', doorOffset: 2, hasBalcony: true },
    ].forEach(({ x: hx, paletteSwap, doorOffset, hasBalcony }) => {
        // Estas casas no cuelgan del `group` de la iglesia (crean su propio
        // grupo en coordenadas de mundo), así que hay que sumar la posición
        // de la iglesia a mano en vez de usar solo el desplazamiento local.
        standardBuilders.colonialHouse(engine, {
            x: x + hx,
            z: z + frontZ - 2.5,
            width: 15,
            depth: 10.5,
            bodyHeight: 7.2,
            paletteSwap,
            shutterColor: 'leaf',
            roofTexture: 'roofClay',
            doorOffset,
            hasBalcony,
            windows: 4,
        });
    });
}

const layout = [
    ...groundLayout,
    ...gardenQuadrants,
    ...centerpieces,
    ...perimeterBuildings,
    ...streetVehicles,
    ...skyProps,
    { type: 'custom', build: () => buildSavannaHills(engine) },
    { type: 'custom', build: () => buildCajicaChurch(engine, church) },
];

engine.start(layout, standardBuilders);
loadDynamicStands(engine, window.cajicaImmersivePlazaId);
