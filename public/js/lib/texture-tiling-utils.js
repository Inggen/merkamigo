import { THREE } from './voxel-plaza-engine.js';

/**
 * Repetición de textura (U, V) elegida por instancia (Fase 4 del editor
 * espacial) — compartida entre el editor admin (`plaza-spatial-editor.
 * blade.php`) y la experiencia pública (`dynamic-stand-loader.js`) para
 * que nunca diverjan entre sí (bug real de esta sesión: el editor la
 * aplicaba pero la plaza real nunca la leía, porque cada uno tenía su
 * propia copia y solo una se actualizó).
 *
 * Dos rutas de material muy distintas:
 * - GLB (`isSharedTexture=false`): cada carga de `GLTFLoader` ya crea
 *   materiales/texturas propios de esa instancia (sin caché entre
 *   cargas) — se puede mutar `texture.repeat` directo, sin clonar.
 * - Voxel (`model_definition`/`builderKey`, `isSharedTexture=true`): las
 *   texturas SÍ están compartidas por nombre entre todos los objetos de
 *   la escena (`createVoxelTextures`, un solo diccionario) — mutar
 *   `.repeat` directo afectaría a cualquier otro objeto que use la misma
 *   clave de textura. Se clona antes.
 */
export function applyTiling(root, tiling, isSharedTexture) {
    if (! tiling) {
        return;
    }

    root.traverse((child) => {
        if (! child.isMesh) {
            return;
        }

        const materials = Array.isArray(child.material) ? child.material : [child.material];

        materials.forEach((material) => {
            if (! material?.map) {
                return;
            }

            const texture = isSharedTexture ? material.map.clone() : material.map;
            texture.repeat.set(tiling.u ?? 1, tiling.v ?? 1);
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.RepeatWrapping;
            texture.needsUpdate = true;

            if (isSharedTexture) {
                material.map = texture;
            }
        });
    });
}
