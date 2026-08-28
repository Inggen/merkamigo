import { THREE, renderObjectByPriority, buildAvatarFigure } from './voxel-plaza-engine.js?v=6';
import { applyStandPrimaryColor } from './stand-color-utils.js?v=2';
import { applyTiling } from './texture-tiling-utils.js';
// `?v=3` fuerza la re-descarga tras agregar la velocidad de carrusel
// configurable por colocación (`rotationSeconds`) — bump este número si
// vuelves a tocar `billboard-ad-utils.js`.
import { applyBillboardAds } from './billboard-ad-utils.js?v=3';

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
 * `loadDynamicStands()` además devuelve un registro
 * `{ position, business, root, logoSprite, ownerFigure }` por cada stand
 * con negocio real (IMM-032: `position`/`business` los consume
 * `attachStandProximity()`, `stand-proximity.js`, para el indicador "Ver
 * vitrina" — que además ignora cualquier stand con `root.visible === false`;
 * IMM-034: `root`/`logoSprite`/`ownerFigure`, los tres objetos 3D ya
 * construidos, los usa `attachSearchPanel()`, `stand-search-panel.js`,
 * para ocultar/mostrar el stand COMPLETO al filtrar — booth, insignia de
 * logo y figura del dueño a la vez, sin nunca tocar su posición). La
 * insignia y la figura se agregan a `engine.world` de forma independiente
 * al `root` (ver `attachLogoBadge`/`attachOwnerFigure` más abajo), así que
 * sin esta referencia explícita quedarían visibles aunque el stand se
 * oculte. Los elementos de plaza (`loadDynamicProps`) nunca traen
 * `business`, así que ese registro siempre queda vacío para ellos.
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

        // IMM-041: lo estructural (stands, cualquier objeto con GLB real)
        // se renderiza antes que la decoración de una sola caja (tejas,
        // pasto, árboles sin GLB) — así lo que define la forma caminable
        // de la plaza aparece primero, sin esperar a la cola completa de
        // props menores. Dentro de cada grupo, lotes de concurrencia
        // acotada (no todo en paralelo de golpe, para no disparar N
        // fetches de GLB a la vez; tampoco uno por uno como antes).
        const isStructural = (object) => endpoint === 'stands' || Boolean(object.model_url);
        const prioritized = [
            ...(objects ?? []).filter(isStructural),
            ...(objects ?? []).filter((object) => ! isStructural(object)),
        ];

        const BATCH_SIZE = 6;

        for (let i = 0; i < prioritized.length; i += BATCH_SIZE) {
            const batch = prioritized.slice(i, i + BATCH_SIZE);
            const batchResults = await Promise.all(batch.map((object) => renderDynamicObject(engine, object)));

            batchResults.forEach((entry) => {
                if (entry) {
                    results.push(entry);
                }
            });
        }
    } catch {
        // Contenido adicional: un fallo aquí nunca debe romper la escena.
    }

    return results;
}

/**
 * Construye un único objeto dinámico (stand o prop) y le aplica todo lo
 * que dependa de datos por instancia (colisión, tiling, insignia de logo,
 * color del stand, figura del dueño). Extraído del bucle de
 * `loadDynamicObjects()` para poder procesar varios objetos en paralelo
 * por lote (IMM-041) sin duplicar esta lógica.
 *
 * @returns {Promise<{position: THREE.Vector3, business: object, root: THREE.Object3D|null, logoSprite: THREE.Sprite|null, ownerFigure: THREE.Object3D|null}|null>}
 */
async function renderDynamicObject(engine, object) {
    const { x, y = 0, z } = object.world_position ?? {};
    const rotation = object.rotation?.y ?? 0;

    if (x === undefined || z === undefined) {
        return null;
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

    // Construido ANTES de disparar `attachLogoBadge`/`attachOwnerFigure`
    // para que ambos puedan guardar aquí el `Sprite`/figura que crean —
    // ninguno de los dos vive como hijo de `root` (ver sus comentarios),
    // así que sin esta referencia `stand-search-panel.js` no tendría cómo
    // ocultarlos junto con el stand al filtrar.
    const record = object.business
        ? { position: new THREE.Vector3(x, y, z), business: object.business, root, logoSprite: null, ownerFigure: null }
        : null;

    if (root) {
        root.position.set(x, y, z);
        root.rotation.y = (rotation * Math.PI) / 180;

        // Pedido del usuario: la "barrera de colisión" se ve azul con
        // bordes en el editor (para saber dónde queda), pero en la
        // experiencia inmersiva real debe ser invisible — el bloqueo del
        // paso sigue intacto porque esto solo apaga el render
        // (`this.collisions` de VoxelPlazaEngine no depende de
        // `mesh.visible`). El editor espacial de plaza NUNCA llama esta
        // función (construye su propio preview aparte), así que ahí la
        // barrera sigue viéndose.
        root.traverse((child) => {
            if (child.userData?.isCollisionBarrier) {
                child.visible = false;
            }
        });

        if (object.model_url) {
            engine.syncObjectCollision?.(root, Boolean(object.collision_enabled));
        }

        if (object.screen_material_name) {
            // Contenido adicional: ver comentario de cabecera del archivo.
            // Sin anuncios activos para esta colocación, no hace nada y la
            // pantalla se queda con la imagen estática del GLB.
            try {
                applyBillboardAds(root, object.screen_material_name, object.active_ads, object.ad_rotation_seconds);
            } catch {
                // Contenido adicional: ver comentario de cabecera del archivo.
            }
        }

        if (object.tiling) {
            // Elegido libremente por el emprendedor/admin por instancia
            // (Fase 4 del editor espacial) — un fallo acá nunca debe
            // tumbar la carga del resto de la plaza (mismo contrato que
            // el resto de esta función).
            try {
                applyTiling(root, object.tiling);
            } catch {
                // Contenido adicional: ver comentario de cabecera del archivo.
            }
        }

        if (object.business?.logo_url) {
            // Insignia flotante con el logo, siempre mirando a la cámara
            // (Sprite) — funciona igual sin importar si el stand es GLB,
            // `model_definition` o forma voxel, y se reconoce desde lejos
            // independientemente del ángulo desde el que se camine. Un
            // logo roto/inaccesible nunca debe tumbar la carga del resto
            // de la plaza. Sigue sin bloquear el resto de la función (el
            // stand queda listo sin esperar la descarga de la imagen); el
            // sprite se engancha al registro apenas resuelve, así que un
            // filtro aplicado antes de que cargue simplemente no tiene aún
            // nada que ocultar (se cubre el caso en `applyLocalFilter`).
            attachLogoBadge(engine, root, object.business.logo_url)
                .then((sprite) => {
                    if (record) {
                        record.logoSprite = sprite;
                    }
                })
                .catch(() => {});
        }

        if (object.business?.stand_color) {
            applyStandPrimaryColor(root, object.business.stand_color);
        }

        if (object.business) {
            // Persona junto al stand, con el preset hombre/mujer que el
            // dueño del negocio eligió para sí mismo — un fallo acá (ej.
            // `buildAvatarFigure` con datos raros) nunca debe tumbar la
            // carga del resto de la plaza.
            try {
                record.ownerFigure = attachOwnerFigure(engine, root, object.business.owner_avatar_preset);
            } catch {
                // Contenido adicional: ver comentario de cabecera del archivo.
            }
        }
    }

    return record;
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

    return sprite;
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
    const key = presetKey === 'mujer' ? 'mujer' : 'hombre';
    const figure = buildAvatarFigure(
        key,
        engine.avatarDefinitions?.[key] ?? null,
        engine.textures,
    );

    const box = new THREE.Box3().setFromObject(root);
    const boxCenter = new THREE.Vector3();
    box.getCenter(boxCenter);

    figure.position.set(box.max.x + 0.9, root.position.y, boxCenter.z);
    figure.rotation.y = root.rotation.y;
    engine.world.add(figure);

    return figure;
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
