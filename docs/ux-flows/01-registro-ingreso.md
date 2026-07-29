# Registro e ingreso con selección de intención

## Entrada

- `/` (landing) muestra dos CTA igual de destacados: "Soy Cliente" (`route('clientes.home')`) y "Soy Emprendedor" (`route('emprendedores.bienvenida')`).
- Ninguna de las dos rutas exige cuenta: un visitante puede explorar la Plaza o leer la propuesta de valor de Emprendedores sin registrarse.

## Selección de intención sin cuenta

- La intención elegida se guarda como "experiencia" mediante `POST /experience` (`ExperienceController::update`, `App\Domain\Identity\Actions\SwitchExperience`).
- Sin sesión, `SwitchExperience` solo decide a qué inicio redirigir (`clientes.home` o `emprendedores.bienvenida`); no crea usuario.
- Con sesión, la misma acción actualiza la experiencia activa del usuario y redirige a `clientes.home` o `emprendedores.home`.

## Registro

- El registro (`route('register')`, acción `App\Domain\Identity\Actions\RegisterUser`) pide nombre, correo/teléfono y contraseña.
- Guarda consentimiento legal: `terms_version` y `terms_accepted_at` en `users` (migración `2026_07_27_230001_add_terms_consent_to_users_table.php`), usando la versión vigente de `config('legal.terms_version')`.
- Tras registrarse, el usuario llega a la experiencia que originó el registro (Cliente o Emprendedor) sin perder el punto del recorrido — por ejemplo, "Crear mi vitrina" en la bienvenida de Emprendedores lleva a `register` y de ahí directo al wizard de 1.2.

## Cambio de experiencia con cuenta existente

- Un usuario autenticado puede tener ambos perfiles (Cliente y Emprendedor) sin duplicar cuenta; el selector de experiencia (visible en la navegación) dispara el mismo `POST /experience`.
- La última experiencia usada se recuerda (columna en `users`, ver `SwitchExperience`) y se usa como destino por defecto en visitas siguientes.

## Pendiente

- Verificación y recuperación (`pages/auth/verify-email.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`) ya usan el layout y los componentes `flux` de marca, pero solo cubren correo — no hay verificación por teléfono/WhatsApp todavía.
- Registro solo pide correo; el TODO deja abierta la decisión de admitir también teléfono como identificador (0.5).
