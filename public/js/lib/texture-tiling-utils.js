import { THREE } from './voxel-plaza-engine.js?v=6';

/**
 * Repetición de textura (U, V) elegida por instancia (Fase 4 del editor
 * espacial) — compartida entre el editor admin (`plaza-spatial-editor.
 * blade.php`) y la experiencia pública (`dynamic-stand-loader.js`) para
 * que nunca diverjan entre sí (bug real de esta sesión: el editor la
 * aplicaba pero la plaza real nunca la leía, porque cada uno tenía su
 * propia copia y solo una se actualizó).
 *
 * IMM-041: los materiales pueden compartirse entre varios objetos (voxel
 * los comparte por nombre desde siempre vía `createVoxelTextures`; GLB
 * también desde que `loadGlbTemplate()` cachea la plantilla parseada por
 * URL y clona la jerarquía con `.clone(true)`, que comparte material por
 * referencia). Clonar solo la textura y mutar `material.map` en el
 * material compartido reajustaba el tiling de TODOS los objetos que
 * usan ese material, no solo el editado (bug real reportado por el
 * usuario: cambiar el tiling de un piso/andén reajustaba otros elementos
 * — mismo problema que ya resolvió `stand-color-utils.js` con el color).
 * Por eso el material también se clona y se reasigna a la malla antes de
 * mutar su `.map`, dejando de compartirse con cualquier otro objeto.
 */
export function applyTiling(root, tiling) {
    if (! tiling) {
        return;
    }

    root.traverse((child) => {
        if (! child.isMesh) {
            return;
        }

        const isArray = Array.isArray(child.material);
        const materials = isArray ? child.material : [child.material];

        const nextMaterials = materials.map((material) => {
            if (! material?.map) {
                return material;
            }

            const texture = material.map.clone();
            texture.repeat.set(tiling.u ?? 1, tiling.v ?? 1);
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.RepeatWrapping;
            texture.needsUpdate = true;

            const instanceMaterial = material.clone();
            instanceMaterial.map = texture;
            instanceMaterial.needsUpdate = true;

            return instanceMaterial;
        });

        child.material = isArray ? nextMaterials : nextMaterials[0];
    });
}
