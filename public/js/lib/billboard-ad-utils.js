import * as THREE from 'https://esm.sh/three@0.179.1';

/**
 * Cada cuánto cambia el anuncio visible cuando hay más de uno activo, si la
 * colocación no eligió su propio valor (`rotationSeconds` en
 * `applyBillboardAds`, campo "Velocidad del carrusel" del Editor espacial).
 */
const DEFAULT_ROTATION_SECONDS = 8;

const CANVAS_WIDTH = 512;
const CANVAS_HEIGHT = 256;

/**
 * Convierte el material "pantalla" de un objeto de plaza (billboard) a un
 * material NO-LIT (`MeshBasicMaterial`): un material físico normal
 * (`MeshStandardMaterial`, lo que trae el GLB) sigue multiplicando su
 * textura por el color de las luces de la escena, y el sol/hemisferio de
 * la plaza son cálidos — por eso la pantalla se veía amarilla incluso con
 * el mapa de entorno agregado en `voxel-plaza-engine.js`. No-lit ignora
 * por completo el color/intensidad de las luces: se ve con sus colores
 * reales sin importar la hora del día, como una valla LED real.
 *
 * Sin anuncios activos (`adImageUrls` vacío) reusa la textura ORIGINAL del
 * GLB tal cual, solo la vuelve no-lit — así se ve bien desde el primer
 * momento, sin obligar a subir un anuncio para corregir el color. Con uno
 * o más anuncios activos (`ImmersivePlazaProp.activeAds`, ver
 * `ImmersivePlazaPropsController`), la reemplaza por un `CanvasTexture` que
 * va rotando entre ellos.
 *
 * El material se identifica por NOMBRE EXACTO (`screenMaterialName`, campo
 * "Material de la pantalla" del catálogo) — no hay forma de adivinar cuál
 * mesh es la pantalla sin que alguien lo diga, así que si el campo viene
 * vacío o no hay ninguna malla con ese nombre esta función no toca nada.
 *
 * Se reemplaza el material ANTES de tocar nada más (mismo motivo que
 * `stand-color-utils.js`/`texture-tiling-utils.js`: `voxel-plaza-engine.js`
 * cachea la escena del GLB por URL y la clona por colocación, pero clonar
 * un `Object3D` en three.js NO clona sus materiales — todas las
 * colocaciones del mismo billboard comparten hoy el mismo objeto
 * `Material` hasta que algo lo reemplaza explícitamente).
 *
 * `rotationSeconds` (segundos por imagen, `ImmersivePlazaProp.
 * ad_rotation_seconds`, ajustable desde el botón "Gestionar anuncios" del
 * Editor espacial) solo importa con 2+ anuncios activos; nulo/inválido cae
 * a `DEFAULT_ROTATION_SECONDS`.
 *
 * @returns {{stop: () => void}|null} `stop()` detiene el carrusel (llamarlo
 * si el objeto se retira de la escena antes de navegar a otra plaza).
 */
export function applyBillboardAds(root, screenMaterialName, adImageUrls, rotationSeconds) {
    const name = (screenMaterialName ?? '').trim();

    if (!name) {
        return null;
    }

    const ads = Array.isArray(adImageUrls) ? adImageUrls : [];

    const targets = [];
    root.traverse((object) => {
        if (!object.isMesh) {
            return;
        }

        const isArray = Array.isArray(object.material);
        const materials = isArray ? object.material : [object.material];

        materials.forEach((material, index) => {
            if ((material?.name ?? '').trim() === name) {
                targets.push({ mesh: object, index: isArray ? index : null });
            }
        });
    });

    if (!targets.length) {
        return null;
    }

    // Sin anuncios: cada malla se vuelve no-lit reusando su PROPIA textura
    // original (no hay una sola imagen compartida que rotar todavía).
    if (!ads.length) {
        targets.forEach(({ mesh, index }) => {
            const source = index === null ? mesh.material : mesh.material[index];
            replaceMaterial(mesh, index, buildUnlitMaterial(source, source.map));
        });

        return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = CANVAS_WIDTH;
    canvas.height = CANVAS_HEIGHT;
    const ctx = canvas.getContext('2d');

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;

    targets.forEach(({ mesh, index }) => {
        const source = index === null ? mesh.material : mesh.material[index];
        replaceMaterial(mesh, index, buildUnlitMaterial(source, texture));
    });

    const imageCache = new Map();
    let currentIndex = 0;

    const showAd = async (url) => {
        let image = imageCache.get(url);

        if (!image) {
            image = await loadImage(url);
            imageCache.set(url, image);
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawImageCover(ctx, image, canvas.width, canvas.height);
        texture.needsUpdate = true;
    };

    showAd(ads[0]).catch(() => {});

    let timer = null;

    if (ads.length > 1) {
        const seconds = Number(rotationSeconds);
        const intervalMs = (Number.isFinite(seconds) && seconds > 0 ? seconds : DEFAULT_ROTATION_SECONDS) * 1000;

        timer = setInterval(() => {
            currentIndex = (currentIndex + 1) % ads.length;
            showAd(ads[currentIndex]).catch(() => {});
        }, intervalMs);
    }

    return {
        stop: () => {
            if (timer) {
                clearInterval(timer);
            }
        },
    };
}

function buildUnlitMaterial(source, map) {
    const material = new THREE.MeshBasicMaterial({
        map,
        side: source.side,
        transparent: source.transparent,
        alphaTest: source.alphaTest,
        toneMapped: false,
    });
    material.name = source.name;

    return material;
}

function replaceMaterial(mesh, index, material) {
    if (index === null) {
        mesh.material = material;
    } else {
        const materials = mesh.material.slice();
        materials[index] = material;
        mesh.material = materials;
    }
}

/**
 * Dibuja `image` llenando todo el lienzo sin deformarla (mismo criterio que
 * `background-size: cover` en CSS) — evita bandas vacías cuando la imagen
 * subida no coincide exactamente con la proporción de la pantalla del
 * billboard.
 */
function drawImageCover(ctx, image, width, height) {
    const scale = Math.max(width / image.width, height / image.height);
    const w = image.width * scale;
    const h = image.height * scale;
    ctx.drawImage(image, (width - w) / 2, (height - h) / 2, w, h);
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}
