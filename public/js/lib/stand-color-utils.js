/**
 * Conserva los colores originales del modelo y recolorea solo los
 * materiales llamados exactamente `color` del stand. Si no existe ese
 * material, cae a la heurística anterior de materiales originalmente
 * rojos. Si `hexColor` viene vacío/null, restaura el color original del
 * GLB.
 */
export function applyStandPrimaryColor(root, hexColor) {
    const namedColorMaterials = [];
    const tintableMaterials = [];
    const fallbackMaterials = [];

    root.traverse((object) => {
        if (! object.isMesh) {
            return;
        }

        const materials = Array.isArray(object.material) ? object.material : [object.material];

        materials.forEach((material) => {
            if (! material?.color) {
                return;
            }

            cacheOriginalColor(material);
            fallbackMaterials.push(material);

            if ((material.name ?? '').trim().toLowerCase() === 'color') {
                namedColorMaterials.push(material);
            }

            if (isOriginallyRed(material)) {
                tintableMaterials.push(material);
            }
        });
    });

    const targetMaterials = namedColorMaterials.length > 0
        ? namedColorMaterials
        : (tintableMaterials.length > 0 ? tintableMaterials : fallbackMaterials.slice(0, 1));

    fallbackMaterials.forEach((material) => restoreOriginalColor(material));

    if (! hexColor) {
        return;
    }

    targetMaterials.forEach((material) => {
        material.color.set(hexColor);
        material.needsUpdate = true;
    });
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
