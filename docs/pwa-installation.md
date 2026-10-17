# Instalación y notificaciones

El sitio público y el panel muestran accesos persistentes para instalar Merkamigo y activar notificaciones. No se guardan descartes ni se ocultan por cancelar. La instalación desaparece al recibir `appinstalled` o al abrir en modo independiente; los avisos desaparecen después del registro push exitoso. En una pestaña normal el navegador puede no informar que la app ya está instalada: en ese caso se conserva el acceso deshabilitado si no hay instalador nativo.

El botón Instalar usa exclusivamente `beforeinstallprompt`: permanece visible, pero deshabilitado mientras el navegador no ofrece esa API. No se muestran modales ni tarjetas con instrucciones. Cancelar no oculta el botón; un nuevo evento lo vuelve a habilitar.

Activar notificaciones consulta la API en cada intento; un estado anterior `unavailable` o `denied` no impide reintentar. Solo se oculta después del registro exitoso del dispositivo. No se muestran ayudas de permisos ni instalación. Los errores reales de registro se mantienen separados del estado de soporte. La tarjeta duplicada se retiró de Actividad; la campana conserva un acceso compacto sin instrucciones.

Chrome en incógnito no recibe notificaciones. La emulación visual de iPhone en DevTools sigue usando Chrome y no reproduce Safari/iOS. Safari en iPhone/iPad requiere abrir la app desde la pantalla de inicio; no proporciona `beforeinstallprompt`. Estas restricciones del navegador no se pueden resolver mostrando un permiso propio ni simulando una activación.

`public/site.webmanifest` declara identidad, alcance, iconos 192/512 y capturas públicas en `public/images/pwa/`. El navegador decide qué diálogo presentar y cuándo admite instalar. Las capturas deben actualizarse cuando cambie sustancialmente la interfaz pública.

Lógica: `resources/js/app-install.js`. Vista compartida: `resources/views/components/app-install.blade.php`. Estilos: `resources/css/pages/app-install.css`. El envío y registro siguen usando `resources/js/push-notifications.js`.

Despliegue: copiar manifiesto, capturas, vistas y assets compilados completos; ejecutar `php artisan view:clear`. No requiere migraciones ni cambios de credenciales. Verificar bajo HTTPS, con sesión iniciada y con la configuración Firebase habilitada para registrar avisos. La prueba local del flujo nativo usa un evento simulado; confirmar la instalación real y la entrega push en los dispositivos de producción.
