import { renderObjectByPriority } from './voxel-plaza-engine.js';

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
 */
export async function loadDynamicStands(engine, plazaId) {
    await loadDynamicObjects(engine, plazaId, 'stands');
}

export async function loadDynamicProps(engine, plazaId) {
    await loadDynamicObjects(engine, plazaId, 'props');
}

async function loadDynamicObjects(engine, plazaId, endpoint) {
    if (!plazaId) {
        return;
    }

    try {
        const params = new URLSearchParams(window.location.search);
        const previewQuery = params.get('preview') === '1' ? '?preview=1' : '';
        const response = await fetch(`/api/v1/inmersivo/plazas/${plazaId}/${endpoint}${previewQuery}`);

        if (!response.ok) {
            return;
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
            }
        }
    } catch {
        // Contenido adicional: un fallo aquí nunca debe romper la escena.
    }
}
