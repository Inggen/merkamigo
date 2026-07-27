# Estrategia de ramas, versionado y releases

Resuelve el punto "Definir estrategia de ramas, versionado y releases" de 0.3 del TODO.

## Ramas

- `main`: siempre desplegable. Ninguna persona hace push directo; todo cambio entra por pull request.
- `feature/{slug}`: una funcionalidad o tarea del backlog (p. ej. `feature/pidelo-en-merkamigo`).
- `fix/{slug}`: corrección de un bug puntual.
- `chore/{slug}`: mantenimiento (dependencias, configuración, documentación) sin cambio de comportamiento.

Ramas de vida corta: se integran a `main` y se eliminan. No se mantienen ramas de fase de larga duración (`fase-1`, `fase-2`...) para evitar divergencia; el propio `TODO_MERKAMIGO.md` es el backlog vivo.

## Pull requests

- Un PR referencia la sección del TODO que resuelve (p. ej. "0.5", "1.2").
- Antes de pedir revisión: `composer test` (pint + phpstan + pest) y `npm run build` deben pasar localmente (el hook de pre-commit ya corre pint, ver más abajo).
- Squash merge a `main` para mantener el historial legible.

## Versionado

Merkamigo (la aplicación) no se versiona con tags de release todavía: el piloto se despliega de forma continua desde `main`. Cuando exista staging/producción real, se adoptará versionado por fecha (`YYYY.MM.DD`) o SemVer si la cadencia de releases lo justifica — decisión pendiente hasta tener el proveedor de hosting.

La **API sí está versionada desde ahora** (`/api/v1`); ver `docs/architecture/versionado-api.md`.

## Changelog

Mientras no haya releases formales, los cambios relevantes se documentan en los mensajes de commit y en las secciones "Explícitamente diferido" / decisiones de `docs/architecture/decisiones.md`. Se adoptará un `CHANGELOG.md` formal cuando arranque la Fase 1 con el primer piloto real.
