# Checklist de despliegue, rollback y verificación posproducción

> 1.10 del TODO. Este checklist asume el estado real del proyecto en este
> pase: no hay todavía un entorno de staging/producción con hosting real
> ni backups automatizados (ver "Explícitamente diferido" en
> `docs/architecture/decisiones.md`). Es la base a seguir cuando se elija
> el proveedor de hosting, no un procedimiento ya ejecutado.

## Antes de desplegar

- [ ] `composer install --no-dev --optimize-autoloader`.
- [ ] `npm ci && npm run build`.
- [ ] Copiar `.env.example` a `.env` en el entorno destino y completar
      credenciales reales (base de datos, `APP_KEY`, correo, S3 cuando
      exista) — nunca reutilizar el `.env` de desarrollo.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`.
- [ ] `php artisan migrate --force` (revisar que todas las migraciones
      nuevas tengan `down()` reversible, ya lo exige la Fase 1 del TODO).
- [ ] Ejecutar la suite completa: `vendor/bin/pest`, `vendor/bin/pint --test`,
      `vendor/bin/phpstan analyse` — los tres deben pasar sin errores antes
      de promover el despliegue.
- [ ] `php artisan storage:link` si el disco `public` sigue en uso (ver
      nota sobre almacenamiento en `docs/architecture/decisiones.md`: debe
      migrarse a S3 antes de producción real).

## Verificación posdespliegue

- [ ] `GET /up` responde 200 (health check de Laravel ya configurado en
      `bootstrap/app.php`).
- [ ] Cargar `/`, `/plaza/cajica`, `/plaza/zipaquira`, `/buscar`,
      `/preguntas-frecuentes`, `/sitemap.xml` y confirmar 200 sin errores
      en los logs.
- [ ] Registrar un usuario de prueba, crear una vitrina completa
      (wizard de 1.2), publicarla y confirmar que aparece en su plaza.
- [ ] Confirmar que el botón de WhatsApp de una vitrina publicada redirige
      a `wa.me` con el número y mensaje correctos.
- [ ] Entrar a `/admin` con una cuenta con rol de plataforma y confirmar
      que el dashboard y los recursos de moderación cargan.
- [ ] Confirmar que una cuenta sin rol de plataforma recibe 403/redirect
      al intentar entrar a `/admin`.
- [ ] Revisar `storage/logs/laravel.log` (o el destino de logs configurado)
      en busca de errores nuevos tras el despliegue.

## Rollback

- [ ] Mantener la release anterior desplegable (o su tag/commit) lista
      para volver a apuntar el servidor web sin reconstruir desde cero.
- [ ] Si el despliegue incluyó migraciones nuevas y deben revertirse:
      `php artisan migrate:rollback --step=N` con el número exacto de
      migraciones del despliegue, nunca un rollback abierto.
- [ ] Volver a ejecutar `config:cache`/`route:cache`/`view:cache` tras
      cualquier rollback de código, para no dejar caché de la versión
      nueva sirviendo sobre código viejo.
- [ ] Confirmar `GET /up` y el flujo de publicación de vitrina (mismo
      chequeo que en "verificación posdespliegue") después del rollback.

## Pendiente antes de un despliegue real a producción

Estos puntos están fuera del alcance de este pase (requieren decisiones
de infraestructura del equipo, no solo código):

- Elegir proveedor de hosting/staging y configurar el pipeline de CI/CD
  que ejecute este checklist automáticamente.
- Backups automáticos de base de datos y archivos, con una prueba de
  restauración documentada.
- Logs centralizados y alertas (Sentry, Papertrail o equivalente).
- Almacenamiento S3-compatible real para logos, portadas y fotos de
  producto (hoy usan el disco `public` local, solo válido en desarrollo).
