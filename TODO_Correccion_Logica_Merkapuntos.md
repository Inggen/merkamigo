# TODO — Corrección completa de la lógica de Merkapuntos

## Contexto

Merkamigo cuenta con un sistema de fidelización llamado **Merkapuntos**. Los usuarios acumulan puntos por compras realizadas dentro de la plataforma y luego pueden redimirlos por productos, servicios o beneficios configurados por cada negocio.

Actualmente existe una lógica para sugerir cuántos Merkapuntos debe costar una recompensa. Esa lógica está generando valores demasiado altos y poco realistas.

Ejemplo actual detectado:

- Costo real del premio: $8.000 COP
- Regla global de acumulación: 1 Merkapunto por cada $1.000 COP comprados
- Sugerencia actual: entre 1.067 y 1.600 Merkapuntos

Con la regla global de acumulación, un premio de 1.067 puntos requiere aproximadamente $1.067.000 COP en compras antes de poder redimirlo. Para un producto económico, como un café, esto hace que la recompensa se perciba prácticamente inalcanzable y afecta negativamente la experiencia de fidelización.

La lógica actual parece estar usando el costo del premio como referencia directa para calcular los puntos requeridos. Esto debe corregirse.

---

# Objetivo

Cambiar la lógica de recomendación de recompensas para que el sistema responda principalmente a:

1. Cuánto gasta normalmente un cliente en el negocio.
2. Cuántas compras quiere el negocio que realice antes de alcanzar el premio.
3. Cuánto le cuesta realmente al negocio entregar ese premio.
4. Qué porcentaje del gasto acumulado representa el costo de la recompensa.

La meta es que las recompensas sean:

- Alcanzables para el cliente.
- Entendibles para el comerciante.
- Sostenibles para el negocio.
- Fáciles de configurar.

---

# Regla global de acumulación

Por ahora mantener la regla actual de acumulación:

```text
1 Merkapunto por cada $1.000 COP comprados
```

Ejemplo:

```text
Compra de $20.000 = 20 Merkapuntos
Compra de $50.000 = 50 Merkapuntos
Compra de $100.000 = 100 Merkapuntos
```

No modificar los puntos que ya tengan acumulados los usuarios.

---

# Nueva lógica para configurar recompensas

La cantidad recomendada de puntos para redimir un premio NO debe calcularse directamente desde el costo del premio.

Debe calcularse principalmente a partir del ticket promedio del negocio y del número de compras objetivo.

## Datos necesarios

Agregar o utilizar los siguientes datos:

### 1. Costo real del premio

Costo que asume el negocio para entregar el producto o servicio.

Ejemplo:

```text
$8.000 COP
```

No confundir con el precio público del producto.

---

### 2. Ticket promedio

Valor promedio que normalmente gasta un cliente por compra en ese negocio.

Ejemplo:

```text
$20.000 COP
```

Este dato debe poder:

- ser ingresado manualmente por el negocio;
- o calcularse automáticamente desde las compras históricas si existen suficientes datos.

---

### 3. Compras objetivo

Número aproximado de compras que el negocio desea que realice un cliente antes de alcanzar la recompensa.

Ejemplo:

```text
4 compras
6 compras
8 compras
```

---

# Fórmula principal

```text
gasto_objetivo = ticket_promedio × compras_objetivo
```

Luego:

```text
puntos_requeridos = gasto_objetivo / 1000
```

porque actualmente:

```text
1 punto = $1.000 COP comprados
```

También calcular:

```text
porcentaje_recompensa = costo_real_premio / gasto_objetivo × 100
```

Este porcentaje indica cuánto representa económicamente el premio frente al gasto que tuvo que realizar el cliente para obtenerlo.

---

# Ejemplo completo

Supongamos:

```text
Costo real del premio: $8.000
Ticket promedio: $20.000
Compras objetivo: 6
```

Entonces:

```text
gasto_objetivo = 20.000 × 6

gasto_objetivo = $120.000
```

Puntos requeridos:

```text
120.000 / 1.000 = 120 Merkapuntos
```

Costo del incentivo:

```text
8.000 / 120.000 × 100 = 6,67%
```

Resultado que debería visualizar el negocio:

```text
120 Merkapuntos

≈ 6 compras
Gasto estimado del cliente: $120.000
Costo real del premio: $8.000
Incentivo equivalente: 6,7%
```

---

# Asistente automático de recomendación

El sistema debe generar tres opciones automáticamente.

Para simplificar la experiencia del comerciante, utilizar inicialmente estas referencias:

## Opción 1 — Más atractivo

```text
4 compras objetivo
```

## Opción 2 — Equilibrado / recomendado

```text
6 compras objetivo
```

## Opción 3 — Mayor protección del margen

```text
8 compras objetivo
```

Ejemplo con ticket promedio de $20.000:

```text
Más atractivo
80 Merkapuntos
≈ $80.000 en compras
≈ 4 compras

Equilibrado
120 Merkapuntos
≈ $120.000 en compras
≈ 6 compras

Mayor protección del margen
160 Merkapuntos
≈ $160.000 en compras
≈ 8 compras
```

El negocio debe poder seleccionar cualquiera de las tres opciones o modificar manualmente los puntos.

---

# Cambio en la interfaz

Actualmente aparecen campos como:

```text
Fracción mínima (%)
Fracción máxima (%)
```

Eliminar estos campos de la interfaz visible para el comerciante.

No son intuitivos y complican innecesariamente la configuración.

La interfaz debe ser sencilla.

Mostrar:

```text
Costo real del premio
Ticket promedio
```

Y debajo:

```text
¿Cuándo quieres premiar a tu cliente?

[ 4 compras ]
[ 6 compras — Recomendado ]
[ 8 compras ]
```

El sistema calcula automáticamente los Merkapuntos correspondientes.

---

# Resultado visual esperado

Ejemplo:

```text
ASISTENTE DE RECOMPENSA

Te ayudamos a crear una recompensa atractiva para tus clientes
sin afectar innecesariamente tu margen.

Costo real del premio
$8.000

Ticket promedio
$20.000

¿Cuándo quieres premiar a tu cliente?

🔥 Más atractivo
80 Merkapuntos
≈ 4 compras
≈ $80.000 en compras
Incentivo: 10%

⭐ Recomendado
120 Merkapuntos
≈ 6 compras
≈ $120.000 en compras
Incentivo: 6,7%

🛡 Mayor protección del margen
160 Merkapuntos
≈ 8 compras
≈ $160.000 en compras
Incentivo: 5%
```

---

# Validaciones

Agregar validaciones automáticas para detectar configuraciones poco convenientes.

## Recompensa demasiado difícil de alcanzar

Si el número equivalente de compras es demasiado alto, mostrar:

```text
⚠️ Esta recompensa puede ser difícil de alcanzar para tus clientes.
```

Por ejemplo, si requiere más de 12 compras.

No bloquear el guardado; únicamente advertir.

---

## Recompensa demasiado costosa

Si el costo del premio representa un porcentaje demasiado alto frente al gasto objetivo, mostrar:

```text
⚠️ Esta recompensa puede representar un costo alto para tu negocio.
```

Como referencia inicial:

```text
> 12% = advertencia
```

Este valor debe quedar centralizado/configurable y no quemado en múltiples partes del código.

---

# Valor manual

El negocio debe continuar teniendo la posibilidad de escribir manualmente la cantidad de Merkapuntos requeridos.

Cuando se modifique manualmente, recalcular en tiempo real:

```text
Gasto aproximado requerido
Número aproximado de compras
Porcentaje equivalente del incentivo
```

Ejemplo:

```text
Puntos: 150
Ticket promedio: $20.000

Gasto requerido: $150.000
Compras aproximadas: 7,5
```

Mostrar redondeado de forma amigable:

```text
≈ 7–8 compras
```

---

# Compatibilidad con datos existentes

IMPORTANTE:

No modificar automáticamente:

- saldo actual de Merkapuntos de los usuarios;
- movimientos históricos;
- compras anteriores;
- redenciones anteriores.

La corrección debe afectar principalmente:

```text
configuración de nuevas recompensas
edición de recompensas existentes
asistente de sugerencia de puntos
```

Si existen recompensas configuradas con la lógica anterior, NO cambiar automáticamente su valor.

Únicamente mostrar la nueva recomendación cuando el comerciante las edite.

---

# Arquitectura / implementación

Evitar colocar la lógica matemática directamente dentro de la vista.

Crear un servicio o clase centralizada, por ejemplo:

```text
MerkapuntosRewardCalculator
```

Responsabilidades:

```text
calculateTargetSpend()
calculateRequiredPoints()
calculateRewardPercentage()
calculateEstimatedPurchases()
generateRecommendations()
validateRewardConfiguration()
```

La vista únicamente debe consumir los resultados del servicio.

Esto permitirá modificar posteriormente las reglas globales de Merkapuntos sin reescribir múltiples pantallas.

---

# Configuración centralizada

La equivalencia actual:

```text
1 punto = $1.000 COP comprados
```

NO debe quedar escrita directamente en diferentes controladores o vistas.

Crear una configuración centralizada, por ejemplo:

```text
MERKAPUNTOS_EARNING_UNIT=1000
```

O su equivalente dentro de la configuración existente del proyecto.

Todos los cálculos deben utilizar esa configuración.

---

# Criterios de aceptación

La tarea se considera terminada cuando:

- [ ] Se elimina la lógica actual basada directamente en el costo del premio.
- [ ] El cálculo utiliza ticket promedio + compras objetivo.
- [ ] Se mantiene la regla global actual de 1 punto por cada $1.000 COP.
- [ ] Se generan opciones de 4, 6 y 8 compras.
- [ ] Se muestra gasto estimado necesario para obtener el premio.
- [ ] Se muestra número aproximado de compras.
- [ ] Se muestra porcentaje económico del incentivo.
- [ ] Se eliminan de la interfaz “Fracción mínima” y “Fracción máxima”.
- [ ] El comerciante puede modificar manualmente los puntos.
- [ ] Los cálculos se actualizan en tiempo real.
- [ ] Se muestran advertencias para recompensas demasiado difíciles o demasiado costosas.
- [ ] No se alteran saldos ni movimientos históricos de los usuarios.
- [ ] La lógica queda centralizada en un servicio/clase reutilizable.
- [ ] La equivalencia punto/COP queda en configuración centralizada.
- [ ] Se agregan pruebas para validar los cálculos principales.

---

# Caso de prueba obligatorio

Usar como prueba mínima:

```text
Costo premio: $8.000
Ticket promedio: $20.000
```

Resultados esperados:

```text
4 compras
$80.000 de gasto
80 puntos
10% de incentivo

6 compras
$120.000 de gasto
120 puntos
6,67% de incentivo

8 compras
$160.000 de gasto
160 puntos
5% de incentivo
```

Nunca debería volver a sugerir algo como:

```text
1.067 puntos
1.600 puntos
```

para este escenario.

---

# Principio UX

Merkamigo busca hacer fácil lo complejo.

El comerciante no debería tener que entender fórmulas financieras para crear una recompensa.

La pregunta principal debe ser:

```text
¿Cuántas compras quieres que haga aproximadamente tu cliente antes de recibir este premio?
```

Merkamigo debe encargarse automáticamente del resto del cálculo.
