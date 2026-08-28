/**
 * Conserva los colores originales del modelo y recolorea solo los
 * materiales llamados exactamente `color` en un GLB o las cajas con
 * textura `color` en una definición voxel. Si no existe ese rol explícito,
 * cae a la heurística anterior de materiales originalmente rojos. Si
 * `hexColor` viene vacío/null, restaura el color original.
 *
 * IMM-041: los stands construidos por `builder_key` (voxel, no GLB) ahora
 * pueden compartir el mismo `MeshStandardMaterial` entre varios objetos
 * (`voxel-plaza-engine.js` cachea materiales por textura+opciones para no
 * crear miles de instancias redundantes). Mutar `material.color`
 * directamente en ese material compartido recolorearía TODOS los objetos
 * que lo usan, no solo este stand — mismo problema que ya resolvió
 * `texture-tiling-utils.js` clonando texturas. Por eso el material que
 * realmente se va a recolorear siempre se clona primero y se reasigna a
 * su malla; solo el GLB (materiales ya exclusivos por instancia, nunca
 * cacheados) puede mutarse tal cual sin necesidad de clonar.
 */
export function applyStandPrimaryColor(root, hexColor) {
    const entries = [];

    root.traverse((object) => {
        if (! object.isMesh) {
            return;
        }

        const isArray = Array.isArray(object.material);
        const materials = isArray ? object.material : [object.material];

        materials.forEach((material, index) => {
            if (! material?.color) {
                return;
            }

            cacheOriginalColor(material);
            entries.push({ mesh: object, index: isArray ? index : null, material });
        });
    });

    const namedColorEntries = entries.filter((e) => (
        e.mesh.userData?.voxelTexture === 'color'
        || (e.material.name ?? '').trim().toLowerCase() === 'color'
    ));
    const tintableEntries = entries.filter((e) => isOriginallyRed(e.material));

    const targetEntries = namedColorEntries.length > 0
        ? namedColorEntries
        : (tintableEntries.length > 0 ? tintableEntries : entries.slice(0, 1));

    // Todo lo que no es el objetivo se restaura a su color original tal
    // cual (nunca se clona: si nadie lo va a recolorear, no hace falta
    // dejar de compartirlo).
    const targetMaterials = new Set(targetEntries.map((e) => e.material));
    entries.forEach(({ material }) => {
        if (! targetMaterials.has(material)) {
            restoreOriginalColor(material);
        }
    });

    if (! hexColor) {
        targetEntries.forEach(({ material }) => restoreOriginalColor(material));

        return;
    }

    targetEntries.forEach(({ mesh, index, material }) => {
        const instanceMaterial = cloneForInstance(mesh, index, material);
        instanceMaterial.color.set(hexColor);
        instanceMaterial.needsUpdate = true;
    });
}

/**
 * Clona el material y lo reasigna a la malla ANTES de mutar su color —
 * así deja de compartirse con cualquier otro objeto que use el mismo
 * material cacheado. Llamadas siguientes sobre el mismo `root` encuentran
 * directamente este clon (ya es el material propio de la malla), sin
 * volver a clonar.
 */
function cloneForInstance(mesh, index, material) {
    const clone = material.clone();
    clone.userData = { ...material.userData };

    if (index === null) {
        mesh.material = clone;
    } else {
        const materials = mesh.material.slice();
        materials[index] = clone;
        mesh.material = materials;
    }

    return clone;
}

function cacheOriginalColor(material) {
    if (material.userData._standOriginalColorHex) {
        return;
    }

    material.userData._standOriginalColorHex = `#${material.color.getHexString()}`;
}

function restoreOriginalColor(material) {
    const originalHex = material.userData._standOriginalColorHex;

    if (! originalHex) {
        return;
    }

    material.color.set(originalHex);
    material.needsUpdate = true;
}

function isOriginallyRed(material) {
    const originalHex = material.userData._standOriginalColorHex;

    if (! originalHex) {
        return false;
    }

    const originalColor = material.color.clone();
    originalColor.set(originalHex);

    const hsl = { h: 0, s: 0, l: 0 };
    originalColor.getHSL(hsl);

    return (hsl.h <= 0.04 || hsl.h >= 0.96)
        && hsl.s >= 0.45
        && hsl.l >= 0.16
        && hsl.l <= 0.72;
}
