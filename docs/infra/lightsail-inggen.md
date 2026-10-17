# Servidor Lightsail: inggen

Preparación del 17 de octubre de 2026. Región us-east-1, Ubuntu 24.04, plan de 512 MB / 20 GB. No se amplió el plan ni se contrataron bases de datos, balanceadores, paneles o snapshots. La tarifa base de esta modalidad es USD 5/mes, prorrateada por hora, antes de impuestos/excesos; el bajo tráfico no elimina esa tarifa.

## Configuración aplicada

- Nginx, PHP 8.4.26, extensiones Laravel, MariaDB local y FFmpeg.
- Swap de 2 GB, buffer InnoDB de 64 MB, 15 conexiones y pools PHP bajo demanda con un proceso por sitio. Esta capacidad es ajustada: swap evita algunos fallos de memoria, pero no sustituye RAM ni asegura capacidad para Live o transcodificación concurrente.
- Usuarios separados: `merkamigo`, `inggen`, `sittego`.
- Raíces públicas: `/var/www/DOMINIO/current/public`.
- Hosts: `merkamigo.com`, `inggen.com`, `sittego.com` y sus variantes `www`.
- MariaDB solo escucha en `127.0.0.1:3306`. Credenciales nuevas de Merkamigo en `/root/.config/merkamigo/database.env`, modo 600; no están en este repositorio.
- `merkamigo-queue.service` y `merkamigo-schedule.timer` creados, **deshabilitados** hasta la migración. Cola con timeout 60, inferior al retry_after predeterminado de 90.
- Los hosts actuales presentan una página de preparación. No hay base de datos de producción ni APP_KEY importadas.

IP fija vinculada: **100.57.14.74**, recurso `inggen-web-ip`. El 17 de octubre el usuario autorizó explícitamente crear/vincular la IP y abrir TCP 443 para IPv4; ambos cambios quedaron aplicados y verificados en la consola. La IP inicial `18.212.123.214` dejó de ser la dirección pública vigente. Se verificó SSH en la nueva IP conservando la identidad del host anterior, los servicios y respuesta HTTP 200 de las tres páginas de preparación.

El puerto 443 está permitido en AWS, pero todavía no hay certificado emitido ni HTTPS de aplicación validado. AWS no cobra extra por una IP estática vinculada; sin vincular durante más de una hora puede cobrar.

**Bloqueo de migración:** el usuario informó que su alojamiento/cPanel está suspendido. No hay un respaldo reciente de producción disponible en el repositorio. Se solicitó elegir entre recuperar el respaldo del proveedor o usar la copia local exclusivamente para pruebas. No se ha cambiado DNS ni importado información local como producción.

## Migración pendiente

1. Entrar a cPanel y crear un respaldo consistente de base de datos, archivos de la aplicación, archivos públicos (raíz separada en el alojamiento actual), `storage/app`, `.env` y credenciales Firebase referenciadas. Guardar fuera de una carpeta pública y descargar una copia protegida. El usuario confirmó que no tiene un respaldo reciente.
2. Verificar el respaldo y preservar la **APP_KEY de producción**. No generar una nueva: se perdería acceso a información cifrada.
3. Importar en MariaDB local, copiar medios y adaptar `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://merkamigo.com`, credenciales locales de BD, sesiones/caché/cola de base de datos, SMTP y Firebase de producción. Usar credenciales de prueba o envíos desactivados durante la validación; no arrancar la cola antigua y la nueva simultáneamente sobre la misma base.
4. Código preparado sin secretos en `releases/20261017-initial`. Crear el enlace público de storage; ejecutar comprobaciones de plataforma, `package:discover`, migraciones pendientes revisadas y cachés como usuario `merkamigo`. No instalar datos de ejemplo ni correr `migrate:fresh`.
5. Validar con Host local o resolución temporal, incluidos login, productos, medios y pagos; HTTPS necesita certificado válido. El frontend compilado se sube desde local para evitar Node/builds en 512 MB.
6. Con IP fija y firewall ya autorizados/aplicados, emitir certificados Let's Encrypt y planear corte DNS. Mantener el DNS de correo existente (MX, SPF, DKIM, DMARC); no moverlo por cambiar el sitio web.
7. Hacer copia final consistente al cortar, activar cola/scheduler en AWS y verificar envíos reales controlados. Mantener rollback al alojamiento anterior.

Todavía no se puede afirmar que Merkamigo funcione en AWS: faltan respaldo, datos, secretos, TLS, DNS y pruebas de aplicación. Los proyectos inggen/sittego requieren sus propios respaldos y dependencias; PHP 8.4 se revisará contra cada composer.lock.

## Agregar otro dominio

`bootstrap-lightsail.sh` es para un Ubuntu vacío; marca la instalación para evitar sobrescribir sitios al repetirlo. Se usa junto al script siguiente en la misma carpeta.

Para un nuevo dominio, copiar `scripts/server/add-laravel-site.sh` al servidor y ejecutar como root:

```bash
bash add-laravel-site.sh ejemplo.com ejemplo
```

Crea usuario, carpeta pública, vhost y pool PHP. Rechaza sobrescribir hosts/pools existentes. Después se crea su base de datos y usuario independientes, se carga su aplicación y se configura DNS/HTTPS. No requiere otra instancia ni comprar otra licencia; sí consume recursos de la existente.

Documentación: [tarifas e IP fija](https://docs.aws.amazon.com/lightsail/latest/userguide/amazon-lightsail-frequently-asked-questions-faq-billing-and-account-management.html).
