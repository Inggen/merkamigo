-- Merkamigo: corrige en producción el tamaño de los slots que usan
-- la plantilla "stand-voxel", sin depender de IDs locales.

START TRANSACTION;

UPDATE stand_slots AS slots
INNER JOIN stand_assignments AS assignments
    ON assignments.stand_slot_id = slots.id
INNER JOIN immersive_object_templates AS templates
    ON templates.id = assignments.object_template_id
SET
    slots.max_width = 5,
    slots.max_depth = 5,
    slots.updated_at = CURRENT_TIMESTAMP
WHERE templates.slug = 'stand-voxel';

COMMIT;

-- Resultado esperado: max_width y max_depth deben aparecer en 5.
SELECT
    slots.id,
    slots.code,
    slots.max_width,
    slots.max_depth,
    templates.name AS template_name,
    assignments.status AS assignment_status
FROM stand_slots AS slots
INNER JOIN stand_assignments AS assignments
    ON assignments.stand_slot_id = slots.id
INNER JOIN immersive_object_templates AS templates
    ON templates.id = assignments.object_template_id
WHERE templates.slug = 'stand-voxel';
