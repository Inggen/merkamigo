/**
 * Escena inmersiva genérica: no describe ninguna geometría propia a mano.
 * Arma el mundo caminable completo a partir de los datos de una
 * `ImmersivePlaza` (límites, punto de aparición, plano de referencia,
 * elementos y stands) usando el mismo `VoxelPlazaEngine` y el mismo orden
 * de prioridad de renderizado (GLB > definición IA > forma voxel) que ya
 * usa el editor espacial del admin — así nunca divergen.
 */
// `voxel-plaza-engine.js` lo importan también `dynamic-stand-loader.js`,
// `stand-proximity.js`, `texture-tiling-utils.js` y varias vistas del
// editor espacial del admin — todos deben resolver la MISMA URL de módulo
// o el navegador lo carga dos veces como instancias separadas (clases
// distintas, caché de texturas duplicada). Por eso el `?v=` de acá SIEMPRE
// tiene que coincidir exactamente con el de cada uno de esos archivos —
// bump los 8 sitios juntos, nunca solo este (bug real: un fix de colisión
// nunca le llegó al usuario porque el navegador siguió sirviendo la copia
// vieja cacheada de este archivo sin `?v=`).
import { THREE, VoxelPlazaEngine, basePalette, avatarPresets } from './lib/voxel-plaza-engine.js?v=6';
// `?v=5` fuerza la re-descarga tras pasar `ad_rotation_seconds` a
// `applyBillboardAds` — bump este número si vuelves a tocar
// `dynamic-stand-loader.js`.
import { loadDynamicStands, loadDynamicProps } from './lib/dynamic-stand-loader.js?v=8';
// `?v=2` idem — `attachStandProximity` ahora ignora stands ocultos.
import { attachStandProximity } from './lib/stand-proximity.js?v=2';
// `?v=2` fuerza a refrescar la copia en caché del navegador tras el fix
// del recorte de texto en móvil — bump este número si vuelves a tocar
// `stand-vitrina-modal.js` y necesitas que los navegadores lo re-descarguen.
import { createVitrinaModal } from './lib/stand-vitrina-modal.js?v=2';
// `?v=4` fuerza la re-descarga tras mover el botón de búsqueda al header
// compartido — bump este número si vuelves a tocar `stand-search-panel.js`.
import { attachSearchPanel } from './lib/stand-search-panel.js?v=4';
// `?v=2` fuerza la re-descarga tras agregar la sección de sensibilidad
// de cámara al menú.
import { attachDisplaySettingsPanel } from './lib/display-settings-panel.js?v=2';
import { loadAvatarPreference } from './lib/avatar-preference.js';
import { loadReducedMotionPreference } from './lib/reduced-motion-preference.js';
import { createTracker, schedulePerformanceSample } from './lib/immersive-tracking.js';
import { resolveQualityTier, getQualitySettings, loadQualityOverride, saveQualityOverride, watchForAutomaticDowngrade } from './lib/immersive-quality.js';
import { createPreloader } from './lib/immersive-preloader.js';

const preloader = createPreloader();

const container = document.getElementById('generic-immersive-scene');
const lockTrigger = document.getElementById('generic-lock-trigger');
const headerActions = document.getElementById('generic-header-actions');

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
const avatarPreset = loadAvatarPreference();

// Compartido entre el motor (panel técnico oculto, `?perf=1`/tecla "P") y
// el botón de engranaje del header (`display-settings-panel.js`) — mismo
// estado, misma acción al elegir un nivel, dos puertas de entrada.
const qualityControl = {
    currentTier: qualityTier,
    isOverride: loadQualityOverride() !== null,
    onSelect: (tier) => {
        saveQualityOverride(tier);
        window.location.reload();
    },
};

const engine = new VoxelPlazaEngine({
    container,
    lockTrigger,
    // IMM-030: solo se sobreescriben las 4 claves dedicadas del avatar —
    // esta escena no tiene ninguna otra paleta propia que preservar.
    palette: { ...basePalette, ...avatarPresets[avatarPreset] },
    avatarPreset,
    avatarDefinition: window.genericAvatarDefinitions?.[avatarPreset] ?? null,
    avatarDefinitions: window.genericAvatarDefinitions ?? {},
    reducedMotion: loadReducedMotionPreference(),
    quality: getQualitySettings(qualityTier),
    qualityControl,
    groundSize,
    movementBounds: bounds,
    playerStart: { x: spawn.x ?? 0, z: spawn.z ?? 0 },
    playerFacing: spawn.rotationY ?? 0,
    // Pedido del usuario: niebla configurable por plaza desde el editor
    // espacial — `window.genericPlazaFog` siempre trae las 4 claves
    // (`ImmersivePlaza::fogSettings()` normaliza contra el mismo default
    // que trae el motor si nunca se configuró).
    fog: window.genericPlazaFog,
});

if (headerActions) {
    attachDisplaySettingsPanel(qualityControl, { container: headerActions, engine });
}

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

// Pedido del usuario: imagen de cielo panorámica (spheremap/equirectangular)
// configurable por plaza desde "Editar Plaza" — mismo tipo de imagen que
// usaban las plazas hardcodeadas de Zipaquirá/Cajicá (una esfera invertida
// con `EquirectangularReflectionMapping` posicionada a mano), pero aquí se
// aplica directo como `scene.background`: Three.js ya sabe renderizar un
// fondo equirectangular correctamente desde cualquier ángulo de cámara, sin
// geometría ni posición que ajustar por plaza. Sin imagen configurada, la
// escena se ve exactamente como hasta ahora (el color de cielo plano de
// `VoxelPlazaEngine`).
if (window.genericPlazaSkyImageUrl) {
    new THREE.TextureLoader().load(window.genericPlazaSkyImageUrl, (texture) => {
        texture.mapping = THREE.EquirectangularReflectionMapping;
        texture.colorSpace = THREE.SRGBColorSpace;
        engine.scene.background = texture;
        // Pedido del usuario: poder girar el fondo hasta 360° desde el
        // editor espacial (ej. para alinear el horizonte de la imagen con
        // la plaza) — mismo `sky_rotation` que previsualiza ese editor.
        engine.scene.backgroundRotation = new THREE.Euler(0, THREE.MathUtils.degToRad(window.genericPlazaSkyRotation ?? 0), 0);
    });
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
    attachSearchPanel(engine, stands, { currentMunicipalitySlug: window.genericMunicipalitySlug, vitrinaModal: genericVitrinaModal, track, container: headerActions });

    return loadDynamicProps(engine, window.genericPlazaId);
}).finally(() => {
    engine.perf.markSceneReady();
    preloader.hide();
    track('plaza_entry');
    schedulePerformanceSample(track);
});
