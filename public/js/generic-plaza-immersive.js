/**
 * Escena inmersiva genérica: no describe ninguna geometría propia a mano.
 * Arma el mundo caminable completo a partir de los datos de una
 * `ImmersivePlaza` (límites, punto de aparición, plano de referencia,
 * elementos y stands) usando el mismo `VoxelPlazaEngine` y el mismo orden
 * de prioridad de renderizado (GLB > definición IA > forma voxel) que ya
 * usa el editor espacial del admin — así nunca divergen.
 */
import { THREE, VoxelPlazaEngine, basePalette, avatarPresets } from './lib/voxel-plaza-engine.js';
import { loadDynamicStands, loadDynamicProps } from './lib/dynamic-stand-loader.js';
import { attachStandProximity } from './lib/stand-proximity.js';
import { createVitrinaModal } from './lib/stand-vitrina-modal.js';
import { attachSearchPanel } from './lib/stand-search-panel.js';
import { loadAvatarPreference } from './lib/avatar-preference.js';
import { loadReducedMotionPreference } from './lib/reduced-motion-preference.js';
import { createTracker, schedulePerformanceSample } from './lib/immersive-tracking.js';
import { resolveQualityTier, getQualitySettings, loadQualityOverride, saveQualityOverride, watchForAutomaticDowngrade } from './lib/immersive-quality.js';
import { createPreloader } from './lib/immersive-preloader.js';

const preloader = createPreloader();

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

const qualityTier = resolveQualityTier(window.genericPlazaQualityProfile ?? {});

const engine = new VoxelPlazaEngine({
    container,
    lockTrigger,
    // IMM-030: solo se sobreescriben las 4 claves dedicadas del avatar —
    // esta escena no tiene ninguna otra paleta propia que preservar.
    palette: { ...basePalette, ...avatarPresets[loadAvatarPreference()] },
    avatarPreset: loadAvatarPreference(),
    reducedMotion: loadReducedMotionPreference(),
    quality: getQualitySettings(qualityTier),
    qualityControl: {
        currentTier: qualityTier,
        isOverride: loadQualityOverride() !== null,
        onSelect: (tier) => {
            saveQualityOverride(tier);
            window.location.reload();
        },
    },
    groundSize,
    movementBounds: bounds,
    playerStart: { x: spawn.x ?? 0, z: spawn.z ?? 0 },
    playerFacing: spawn.rotationY ?? 0,
});

// IMM-040: si el rendimiento real cae en crítico varias muestras seguidas
// y el visitante no eligió un nivel a mano, se degrada un nivel y se
// recarga con la calidad nueva ya aplicada desde el arranque — más simple
// y confiable que parchear en vivo sombras/niebla/densidad de vegetación
// ya construidas.
watchForAutomaticDowngrade({
    currentTier: qualityTier,
    onDowngrade: (nextTier) => {
        saveQualityOverride(nextTier);
        window.location.reload();
    },
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

const track = createTracker(window.genericPlazaId);
const genericVitrinaModal = createVitrinaModal(engine, { track });

// IMM-041: la plaza genérica no tiene ninguna estructura estática propia
// (el suelo por defecto del motor es lo único que hay antes de esto) —
// los stands SON la estructura principal aquí, así que se esperan
// primero y solo después se piden los props decorativos, en vez de
// pedirlos ambos a la vez como antes.
loadDynamicStands(engine, window.genericPlazaId).then((stands) => {
    attachStandProximity(engine, stands, {
        onOpen: (business) => genericVitrinaModal.open(business),
    });
    attachSearchPanel(engine, stands, { currentMunicipalitySlug: window.genericMunicipalitySlug, vitrinaModal: genericVitrinaModal, track });

    return loadDynamicProps(engine, window.genericPlazaId);
}).finally(() => {
    engine.perf.markSceneReady();
    preloader.hide();
    track('plaza_entry');
    schedulePerformanceSample(track);
});
