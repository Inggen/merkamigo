# Streaming propio de Merkamigo

MediaMTX recibe una sola señal desde el navegador, móvil u OBS. Merkamigo
reproduce esa señal mediante HLS/WebRTC y, opcionalmente, la redistribuye a
destinos RTMP/RTMPS. Las redes sociales nunca son la fuente del reproductor.

## Desarrollo

Con Homebrew, ejecuta desde la raíz del proyecto:

```bash
mediamtx infrastructure/streaming/mediamtx.local.yml
```

Este perfil escucha únicamente en `127.0.0.1`. Para Docker/producción:

1. Publica `POST /api/streaming/auth` en una URL accesible por el contenedor.
2. Define `LIVE_STREAMING_AUTH_URL` y `LIVE_STREAMING_PUBLIC_HOST`.
3. Ejecuta `docker compose -f infrastructure/streaming/compose.yml up -d`.

Los puertos 9997 y 9998 son de administración/métricas y solo se enlazan a
`127.0.0.1`. En producción deben quedar únicamente en la red privada. HLS y WebRTC
deben exponerse mediante un proxy TLS; RTMP puede migrarse a RTMPS en el proxy.

No se despliega automáticamente porque exige elegir servidor, capacidad,
ancho de banda y presupuesto antes de crear infraestructura con costo.
