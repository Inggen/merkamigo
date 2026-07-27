# Proceso de incidentes y recuperación

Resuelve "Definir proceso de incidentes y recuperación" de 0.6 del TODO. Es un proceso operativo; no depende de tener staging/producción real todavía, pero se activa formalmente cuando exista un entorno productivo.

## Severidades

| Nivel | Ejemplo | Respuesta |
|---|---|---|
| **P0 — Crítico** | El sitio no carga, no se puede iniciar sesión, fuga de datos personales | Atender de inmediato, sin esperar horario laboral |
| **P1 — Alto** | Una función clave falla para todos (crear vitrina, WhatsApp) | Atender el mismo día |
| **P2 — Medio** | Falla parcial o intermitente, afecta a algunos usuarios | Atender en la semana |
| **P3 — Bajo** | Cosmético, no bloquea ninguna tarea | Backlog normal |

## Pasos durante un incidente

1. **Detectar y confirmar**: reproducir el problema y clasificar la severidad.
2. **Comunicar**: quien lo detecta avisa por el canal de soporte/interno con severidad y alcance estimado.
3. **Contener**: si aplica, activar modo mantenimiento (`php artisan down`), revertir el último despliegue, o revocar el recurso comprometido (token, credencial).
4. **Corregir**: aplicar el fix o rollback; verificar en el entorno afectado.
5. **Cerrar**: confirmar que el síntoma desapareció y avisar que el incidente terminó.
6. **Postmortem** (P0/P1): qué pasó, por qué, qué se hizo, qué cambia para que no se repita. Se documenta junto a los commits relevantes.

## Datos personales

Si un incidente involucra exposición de datos personales, se sigue además la política de privacidad (`docs/legal/privacidad.md`, borrador) para decidir si corresponde notificar a los titulares afectados y/o a la autoridad competente.

## Pendiente hasta tener hosting real

- Canal de alertas automáticas (requiere logs centralizados, ver `docs/architecture/decisiones.md`).
- Runbooks específicos de caída de servidor, cola detenida o base de datos (dependen del proveedor de hosting elegido).
- Simulacro de incidente y de restauración de backup.
