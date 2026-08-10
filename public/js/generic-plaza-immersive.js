/**
 * Escena inmersiva genérica: a diferencia de `zipa-plaza-immersive.js` y
 * `cajica-plaza-immersive.js`, este archivo no describe ninguna geometría
 * propia a mano. Arma el mundo caminable completo a partir de los datos de
 * una `ImmersivePlaza` (límites, punto de aparición, plano de referencia,
 * elementos y stands) usando el mismo `VoxelPlazaEngine` y el mismo orden
 * de prioridad de renderizado (GLB > definición IA > forma voxel) que ya
 * usa el editor espacial del admin — así nunca divergen.
 */
import { THREE, VoxelPlazaEngine } from './lib/voxel-plaza-engine.js';
import { loadDynamicStands, loadDynamicProps } from './lib/dynamic-stand-loader.js';

const container = document.getElementById('generic-immersive-scene');
const lockTrigger = document.getElementById('generic-lock-trigger');

if (!container) {
    throw new Error('Generic immersive container not found.');
}

const bounds = window.genericPlazaBounds ?? { minX: -20, maxX: 20, minZ: -20, maxZ: 20 };
const plane = window.genericPlazaPlane ?? {
    centerX: (bounds.minX + bounds.maxX) / 2,
    centerZ: (bounds.minZ + bounds.maxZ) / 2,
    width: Math.max(1, bounds.maxX - bounds.minX),
    depth: Math.max(1, bounds.maxZ - bounds.minZ),
};
const width = Math.max(1, plane.width);
const depth = Math.max(1, plane.depth);
const centerX = plane.centerX;
const centerZ = plane.centerZ;
const groundSize = Math.max(40, width, depth) + 40;

const spawn = window.genericPlazaSpawn ?? { x: 0, z: 0 };

const engine = new VoxelPlazaEngine({
    container,
    lockTrigger,
    groundSize,
    movementBounds: bounds,
    playerStart: { x: spawn.x ?? 0, z: spawn.z ?? 0 },
    playerFacing: spawn.rotationY ?? 0,
});

// Sin layout propio: el suelo por defecto del motor ya queda listo desde el
// constructor. `deferSceneReady` evita que el panel técnico marque la
// escena como lista antes de que terminen de cargar stands/elementos.
engine.start([], undefined, { deferSceneReady: true });

// Suelo real de la plaza: el plano subido, del tamaño exacto de sus límites
// navegables, superpuesto sobre el pasto por defecto del motor — mismo
// patrón ya probado en `plaza-spatial-editor.blade.php`. No se toca la
// construcción interna del motor, esto solo se agrega encima.
if (window.genericPlazaReferenceImageUrl) {
    const texture = new THREE.TextureLoader().load(window.genericPlazaReferenceImageUrl);
    const ground = new THREE.Mesh(
        new THREE.PlaneGeometry(width, depth),
        new THREE.MeshStandardMaterial({ map: texture }),
    );
    ground.rotation.x = -Math.PI / 2;
    ground.position.set(centerX, 0.03, centerZ);
    ground.receiveShadow = true;
    engine.world.add(ground);
}

Promise.all([
    loadDynamicStands(engine, window.genericPlazaId),
    loadDynamicProps(engine, window.genericPlazaId),
]).finally(() => {
    engine.perf.markSceneReady();
});
