import { THREE, renderObjectByPriority, buildAvatarFigure } from './voxel-plaza-engine.js';

/**
 * IMM-020b (puente mínimo de stands dinámicos): dibuja, encima de una plaza
 * fija ya construida (Zipaquirá, Cajicá, etc.) o de la escena genérica
 * (`generic-plaza-immersive.js`, que no tiene geometría propia), el
 * contenido dinámico real de la base de datos — stands ocupados y
 * elementos de plaza. La geometría fija de una escena con script propio
 * sigue siendo código (decisión de arquitectura #1 del TODO inmersivo);
 * esto solo resuelve la capa dinámica encima de ella (decisión #2).
 *
 * Ambas funciones fallan en silencio: son contenido adicional sobre una
 * escena que ya funciona sin ellas, así que un error de red o de API nunca
 * debe romper la carga de la plaza.
 *
 * El orden de prioridad de renderizado (GLB > definición IA > forma voxel)
 * vive en `renderObjectByPriority()` (`voxel-plaza-engine.js`) — mismo
 * código que usa el editor espacial del admin, para que nunca diverjan.
 *
 * `loadDynamicStands()` además devuelve un registro `{ position, business, root }`
 * por cada stand con negocio real (IMM-032: `position`/`business` los
 * consume `attachStandProximity()`, `stand-proximity.js`, para el
 * indicador "Ver vitrina"; IMM-034: `root`, el objeto 3D ya construido,
 * lo usa `attachSearchPanel()`, `stand-search-panel.js`, para ocultar/
 * mostrar stands al filtrar sin nunca tocar su posición). Los elementos
 * de plaza (`loadDynamicProps`) nunca traen `business`, así que ese
 * registro siempre queda vacío para ellos.
 */
export async function loadDynamicStands(engine, plazaId) {
    return loadDynamicObjects(engine, plazaId, 'stands');
}

export async function loadDynamicProps(engine, plazaId) {
    return loadDynamicObjects(engine, plazaId, 'props');
}

async function loadDynamicObjects(engine, plazaId, endpoint) {
    const results = [];

    if (!plazaId) {
        return results;
    }

    try {
        const params = new URLSearchParams(window.location.search);
        const previewQuery = params.get('preview') === '1' ? '?preview=1' : '';
        const response = await fetch(`/api/v1/inmersivo/plazas/${plazaId}/${endpoint}${previewQuery}`);

        if (!response.ok) {
            return results;
        }

        const { data: objects } = await response.json();

        for (const object of (objects ?? [])) {
            const { x, y = 0, z } = object.world_position ?? {};
            const rotation = object.rotation?.y ?? 0;

            if (x === undefined || z === undefined) {
                continue;
            }

            const root = await renderObjectByPriority(engine, {
                x,
                y,
                z,
                rotation,
                scale: object.scale,
                modelUrl: object.model_url,
                modelDefinition: object.model_definition,
                builderKey: object.builder_key,
            });

            if (root) {
                root.position.set(x, y, z);
                root.rotation.y = (rotation * Math.PI) / 180;

                if (object.model_url) {
                    engine.syncObjectCollision?.(root, Boolean(object.collision_enabled));
                }

                if (object.business?.logo_url) {
                    // Insignia flotante con el logo, siempre mirando a la
                    // cámara (Sprite) — funciona igual sin importar si el
                    // stand es GLB, `model_definition` o forma voxel, y se
                    // reconoce desde lejos independientemente del ángulo
                    // desde el que se camine. Un logo roto/inaccesible
                    // nunca debe tumbar la carga del resto de la plaza.
                    attachLogoBadge(engine, root, object.business.logo_url).catch(() => {});
                }

                if (object.business?.stand_color) {
                    applyPrimaryColor(root, object.business.stand_color);
                }

                if (object.business) {
                    // Persona junto al stand, con el preset hombre/mujer
                    // que el dueño del negocio eligió para sí mismo — un
                    // fallo acá (ej. `buildAvatarFigure` con datos raros)
                    // nunca debe tumbar la carga del resto de la plaza.
                    try {
                        attachOwnerFigure(engine, root, object.business.owner_avatar_preset);
                    } catch {
                        // Contenido adicional: ver comentario de cabecera.
                    }
                }
            }

            if (object.business) {
                results.push({ position: new THREE.Vector3(x, y, z), business: object.business, root });
            }
        }
    } catch {
        // Contenido adicional: un fallo aquí nunca debe romper la escena.
    }

    return results;
}

/**
 * Color de stand elegido libremente por el emprendedor (editor de vitrina,
 * `⚡vitrina.blade.php`) — se aplica a la malla más grande de `root` en
 * espacio de MUNDO (volumen real, no local), asumiendo que esa es la
 * superficie principal del stand. Funciona igual para GLB, `model_definition`
 * o forma voxel del builder: en los tres casos `root` termina siendo un
 * árbol de `Mesh` con geometría real. No hay ninguna convención de nombre
 * de malla que identifique "la pintable" — esta heurística es deliberada
 * (decisión del usuario, sesión del 2026-08-10) en vez de exigir renombrar
 * mallas en cada `.glb` ya subido.
 */
function applyPrimaryColor(root, hexColor) {
    let largestMesh = null;
    let largestVolume = -1;

    root.traverse((obj) => {
        if (!obj.isMesh || !obj.geometry) {
            return;
        }

        obj.geometry.computeBoundingBox?.();
        const box = obj.geometry.boundingBox;

        if (!box) {
            return;
        }

        const size = new THREE.Vector3();
        box.getSize(size);
        const worldScale = new THREE.Vector3();
        obj.getWorldScale(worldScale);
        const volume = size.x * worldScale.x * size.y * worldScale.y * size.z * worldScale.z;

        if (volume > largestVolume) {
            largestVolume = volume;
            largestMesh = obj;
        }
    });

    const material = Array.isArray(largestMesh?.material) ? largestMesh.material[0] : largestMesh?.material;
    material?.color?.set(hexColor);
}

const logoBadgeSize = 128;

/**
 * Insignia circular con el logo del negocio, flotando encima del stand ya
 * construido (`root`) — se calcula la posición a partir de su caja
 * delimitadora real (`Box3`, siempre en espacio de mundo), así que queda
 * arriba del punto más alto sin importar si el stand es un GLB alto, uno
 * bajo, o una forma voxel. Fondo circular blanco dibujado en canvas para
 * que se lea igual con logos transparentes o de colores oscuros.
 *
 * Se agrega a `engine.world` directamente (NO como hijo de `root`) a
 * propósito: los stands suelen traer una escala aplicada
 * (`applyScaleToObject`, para ajustarse al slot que ocupan) y, al ser hija,
 * la insignia heredaba esa misma escala — multiplicando su posición local
 * y disparándola muy por encima del stand (bug real encontrado al
 * verificar: un stand escalado 3x mandaba la insignia a Y≈13.5 en vez de
 * la altura esperada). Calculando la posición en espacio de mundo y
 * agregándola aparte, la insignia queda del tamaño/altura pensados sin
 * importar la escala del stand.
 */
async function attachLogoBadge(engine, root, logoUrl) {
    const image = await loadImage(logoUrl);

    const canvas = document.createElement('canvas');
    canvas.width = logoBadgeSize;
    canvas.height = logoBadgeSize;
    const ctx = canvas.getContext('2d');
    const center = logoBadgeSize / 2;
    const radius = center - 3;

    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.arc(center, center, radius, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#d7352a';
    ctx.lineWidth = 5;
    ctx.stroke();

    ctx.save();
    ctx.beginPath();
    ctx.arc(center, center, radius - 5, 0, Math.PI * 2);
    ctx.clip();
    const padding = logoBadgeSize * 0.14;
    ctx.drawImage(image, padding, padding, logoBadgeSize - padding * 2, logoBadgeSize - padding * 2);
    ctx.restore();

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;

    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, transparent: true }));
    const badgeSize = 2.1;
    sprite.scale.set(badgeSize, badgeSize, 1);

    // Tope de altura: la cámara del personaje tiene un `maxPitch` bajo por
    // diseño (docs/architecture/personaje-inmersivo.md, no se toca), así
    // que una insignia que siga sin límite la punta de un stand escalado
    // grande puede terminar fuera del cono de visión normal. Se limita a
    // una banda cercana a la altura del propio personaje (~3.95).
    const box = new THREE.Box3().setFromObject(root);
    const boxCenter = new THREE.Vector3();
    box.getCenter(boxCenter);
    const rawHeight = Number.isFinite(box.max.y) ? box.max.y - root.position.y : 3;
    const height = Math.min(rawHeight, 3.6);

    // Centrada en X/Z usando el CENTRO real de la caja delimitadora, no
    // `root.position` — el origen/pivote de un GLB no siempre está en el
    // centro visual de su geometría (quedaba corrida hacia un lado).
    // Altura: la misma que ocupaba el CENTRO del círculo cuando tenía
    // forma de pin (con cola apuntando hacia abajo) — se quitó la cola
    // pero se conserva la posición vertical donde ya estaba el círculo.
    sprite.position.set(boxCenter.x, root.position.y + height + 4.1, boxCenter.z);
    engine.world.add(sprite);
}

/**
 * Persona parada junto al stand, con el preset hombre/mujer que el dueño
 * del negocio eligió para sí mismo en su cuenta (`/settings/avatar` →
 * `users.avatar_preset` → `ImmersivePlazaStandsController`). Mismo patrón
 * que `attachLogoBadge`: `Box3().setFromObject(root)` para posicionar en
 * espacio de MUNDO, agregada directamente a `engine.world` — NUNCA como
 * hija de `root`, por el mismo motivo ya documentado ahí (heredaría la
 * escala del stand y su posición local quedaría multiplicada).
 *
 * Parada al costado derecho del stand (fuera de su caja delimitadora, para
 * no atravesar el mostrador) y orientada con el mismo `rotation.y` del
 * stand. `buildAvatarFigure` ya cachea las texturas por preset a nivel de
 * módulo, así que llamarla una vez por stand es barato.
 */
function attachOwnerFigure(engine, root, presetKey) {
    const figure = buildAvatarFigure(presetKey);

    const box = new THREE.Box3().setFromObject(root);
    const boxCenter = new THREE.Vector3();
    box.getCenter(boxCenter);

    figure.position.set(box.max.x + 0.9, root.position.y, boxCenter.z);
    figure.rotation.y = root.rotation.y;
    engine.world.add(figure);
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
