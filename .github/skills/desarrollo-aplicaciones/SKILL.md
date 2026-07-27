---
name: desarrollo-aplicaciones
description: "Use when: you are planning, architecting, or implementing web, hybrid, or native applications and need a structured end-to-end workflow."
---

# Desarrollo de aplicaciones web, híbridas y nativas

## Propósito
Ayudar a definir, diseñar, construir y validar aplicaciones para web, híbridas o nativas con un enfoque práctico, escalable y orientado a resultados.

## Cuando usar esta skill
- El usuario necesita planificar una aplicación nueva.
- Requiere comparar opciones entre web, híbrida o nativa.
- Necesita una arquitectura, propuesta de stack o plan de implementación.
- Quiere construir una MVP y validar calidad, rendimiento y despliegue.

## Flujo recomendado

### 1. Entender el problema y el contexto
- Recolectar objetivos, usuarios, alcance, restricciones y plazos.
- Identificar si el producto es principalmente web, híbrido o nativo.
- Definir métricas de éxito y criterios de aceptación.

### 2. Definir la propuesta de solución
- Elegir el tipo de aplicación según el caso:
  - Web: si el objetivo principal es acceso desde navegador.
  - Híbrida: si se requiere una app móvil con código compartido y tiempos de desarrollo menores.
  - Nativa: si se necesita máximo rendimiento, integración profunda con hardware o experiencia específica de plataforma.
- Seleccionar stack inicial, arquitectura y modelo de datos.
- Estimar riesgos, dependencias y complejidad.

### 3. Diseñar la experiencia y la implementación
- Priorizar funcionalidades críticas para una MVP.
- Definir flujos principales, pantallas, APIs, autenticación, almacenamiento y notificaciones.
- Considerar accesibilidad, seguridad, internacionalización y observabilidad desde el inicio.

### 4. Implementar iterativamente
- Construir en ciclos cortos: base, funcionalidad principal, integración, pruebas y refinamiento.
- Mantener un dominio claro y evitar sobreingeniería.
- Separar responsabilidades entre frontend, backend, servicios y despliegue.

### 5. Validar antes de lanzar
- Probar funcionalidad, rendimiento, compatibilidad, errores, seguridad y UX.
- Revisar que el producto cumpla con requisitos y que sea fácil de mantener.

### 6. Desplegar y evolucionar
- Preparar entorno, CI/CD, monitoreo y rollbacks.
- Recoger feedback y planificar mejoras incrementales.

## Decisiones comunes
- Si el producto es principalmente browser-based y no requiere distribución móvil, prioriza web.
- Si necesita una app móvil con desarrollo relativamente rápido y lógica compartida, prioriza híbrida.
- Si requiere alto rendimiento, integración con sensores, hardware o experiencia nativa específica, prioriza nativa.

## Criterios de calidad
- La solución responde al problema real del usuario.
- El alcance está bien delimitado y la MVP es alcanzable.
- Se eligió la opción de plataforma más adecuada.
- La arquitectura es comprensible y mantenible.
- Se contemplan seguridad, accesibilidad, pruebas y rendimiento.
- El plan incluye riesgos, dependencias y próximos pasos.

## Salida esperada
Al finalizar, el resultado debe incluir:
- Resumen del problema y objetivos
- Recomendación de plataforma
- Propuesta de arquitectura y stack
- Plan de implementación por fases
- Criterios de validación y riesgos
- Siguientes pasos concretos

## Ejemplos de prompts
- Diseña una app web para gestión de tareas con usuarios y pagos.
- Compara una opción híbrida versus nativa para una app de delivery.
- Ayúdame a construir una MVP de una app móvil con autenticación y notificaciones.
