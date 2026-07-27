# Versionado y deprecación de la API

Resuelve "Definir política de versionado y deprecación de API" de 0.4 del TODO.

## Versionado

- La API se sirve desde `/api/v1` desde el primer commit (`routes/api.php`).
- Un cambio es **compatible** (no requiere nueva versión) si: agrega campos opcionales, agrega endpoints nuevos, o agrega valores nuevos a una enumeración ya documentada como abierta.
- Un cambio es **incompatible** (requiere `/api/v2`) si: elimina o renombra un campo, cambia el tipo de un campo, cambia el código de estado esperado de un endpoint existente, o cambia el formato estándar de respuesta/error (`App\Support\Api\ApiResponse` / `ApiError`).

## Deprecación

- Una versión no se retira mientras la futura app híbrida (Fase 6) o integraciones externas (Fase 5.4) dependan de ella.
- Antes de retirar `/api/v1` una vez exista `/api/v2`: anunciar con al menos 90 días de anticipación, devolver la cabecera `Deprecation: true` y `Sunset: <fecha>` en las respuestas de la versión saliente, y documentar la ruta de migración en `docs/api/openapi.yaml`.

## Formato de respuesta

Ver `App\Support\Api\ApiResponse` (éxito: `{"data": ..., "message"?: ...}`) y `App\Support\Api\ApiError` (error: `{"error": {"code", "message", "details"}}`). Todo endpoint nuevo debe reusar estas clases en vez de devolver arrays sueltos.
