---
name: revision-seguridad
description: "Use when: you need to review an application for security gaps, identify risks, and propose concrete improvements to strengthen protection."
---

# Revisar y mejorar huecos de seguridad en aplicaciones

## Propósito
Ayudar a identificar vulnerabilidades y riesgos de seguridad en aplicaciones web, híbridas o nativas, y proponer mejoras concretas, priorizadas y accionables.

## Cuándo usar esta skill
- Cuando quieres revisar una app en busca de problemas de seguridad.
- Cuando necesitas evaluar autenticación, autorización, manejo de datos, secretos o exposición de información.
- Cuando quieres preparar una auditoría de seguridad básica o una mejora de seguridad antes de lanzar.
- Cuando necesitas convertir hallazgos en acciones claras para desarrollo y despliegue.

## Flujo recomendado

### 1. Definir el alcance de la revisión
- Identificar qué partes de la aplicación se van a revisar: frontend, backend, APIs, base de datos, autenticación, almacenamiento, sesiones y despliegue.
- Determinar si el análisis es general o enfocado en un módulo concreto.
- Recoger contexto técnico, arquitectura y tecnologías utilizadas.

### 2. Revisar los puntos críticos de seguridad
- Evaluar autenticación y gestión de sesiones.
- Revisar autorización y control de acceso por roles o permisos.
- Verificar manejo seguro de contraseñas, tokens, secretos y claves.
- Revisar validación de entradas, sanitización y prevención de inyección.
- Revisar manejo de errores, logging y exposición de información sensible.
- Evaluar protección de APIs, CORS, headers de seguridad y políticas de cookies.
- Revisar dependencias, configuraciones y entornos de despliegue.

### 3. Identificar riesgos y priorizar hallazgos
- Clasificar problemas por severidad: crítico, alto, medio o bajo.
- Determinar impacto potencial sobre confidencialidad, integridad y disponibilidad.
- Priorizar los problemas que representan mayor riesgo o mayor facilidad de explotación.

### 4. Proponer mejoras concretas
- Recomendar cambios de diseño, implementación o configuración.
- Sugerir controles como validación estricta, cifrado, limitación de intentos, rotación de secretos, control de acceso, políticas de seguridad y monitoreo.
- Diseñar mitigaciones realistas y compatibles con la arquitectura actual.

### 5. Establecer un plan de acción
- Separar mejoras inmediatas, de mediano plazo y de refactorización.
- Incluir pasos de implementación, pruebas y verificación.
- Definir responsables, tiempos y criterios de éxito.

### 6. Validar la mejora
- Verificar que las correcciones realmente reducen el riesgo.
- Revisar que no se introduzcan nuevos problemas al aplicar cambios.
- Confirmar que la aplicación queda en un estado más seguro y sostenible.

## Áreas comunes a revisar
- Autenticación y autorización
- Manejo de secretos y variables de entorno
- Validación y sanitización de entradas
- Protección contra inyección, XSS, CSRF y clickjacking
- Gestión de sesiones y tokens
- Buenas prácticas de APIs y headers de seguridad
- Configuración de entornos y despliegue
- Dependencias y librerías desactualizadas

## Criterios de calidad
- La revisión identifica riesgos reales y no solo síntomas superficiales.
- Las recomendaciones son concretas, priorizadas y viables.
- Se consideran impacto, facilidad de explotación y costo de mitigación.
- La propuesta mejora la postura de seguridad sin comprometer la usabilidad.
- La mejora puede verificarse y mantenerse a lo largo del tiempo.

## Salida esperada
Al finalizar, el resultado debe incluir:
- Resumen de hallazgos de seguridad
- Clasificación de riesgos por severidad
- Recomendaciones concretas y priorizadas
- Plan de implementación con acciones claras
- Criterios para validar que la mejora fue efectiva

## Ejemplos de prompts
- Revisa esta app para encontrar huecos de seguridad en autenticación y control de acceso.
- Identifica riesgos de seguridad en una API REST y propone mejoras.
- Ayúdame a fortalecer la seguridad de una aplicación web antes de su lanzamiento.
