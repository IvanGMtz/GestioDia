# AGENT.md — GestioDia

Guía técnica para construir **GestioDia**: app web de gestión de tareas diarias y registro de jornada para equipos pequeños (jardinería, limpieza, mantenimiento). Producto de GarcesLab, Línea 3.

---

## 1. Contexto y principios

- **Usuarios objetivo:** empleadores de edad avanzada con poco manejo de tecnología, y sus empleados. La simplicidad de UI es un requisito funcional, no estético.
- **Modelo de acceso:** SIN login tradicional. Acceso por token de dispositivo + código de equipo. Email opcional solo para acceso multi-dispositivo (magic link, nunca contraseña).
- **Hosting:** Hostinger compartido. Restricciones conocidas: `exec()`/`shell_exec()` bloqueados, assets compilados en local (no `npm run build` en servidor), driver MySQL `nd_pdo_mysql`, cron jobs disponibles vía hPanel.
- **Gratuito con límites:** máximo 5 miembros por equipo por defecto, ampliable manualmente en BD (`teams.max_members`).

## 2. Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12 LTS, PHP 8.3 *(spec original pedía Laravel 11; en la fecha de implementación ya estaba EOL sin parches de seguridad, se optó por la LTS vigente — API prácticamente idéntica, sin impacto en el resto de este documento)* |
| BD | MySQL (Hostinger) |
| Frontend | Blade + Bootstrap 5 + Alpine.js + CSS custom |
| Assets | Vite, compilados en local y subidos (`public/build`) |
| Imágenes | Intervention Image v3 (driver GD — NO Imagick, no disponible) |
| Idioma | Código e identificadores en **inglés**. Textos de UI en **español** (usar `lang/es/` desde el día 1 aunque solo haya un idioma) |

**NO usar:** Livewire, Inertia, colas con Redis/database queue worker persistente (no hay proceso daemon en Hostinger; todo síncrono o vía cron), paquetes que requieran extensiones no estándar.

## 3. Convenciones

- PSR-12, tipado estricto (`declare(strict_types=1)` en services).
- Controladores delgados → lógica en `app/Services/` (`TeamService`, `TaskService`, `WorkSessionService`, `PhotoService`, `MagicLinkService`).
- **Regla crítica:** los services NO dependen de `request()`, sesión ni cookies. Reciben datos primitivos/DTOs y devuelven modelos/arrays. Motivo: en fase 2 se expondrán vía API REST (Sanctum) para la app móvil sin reescritura.
- Form Requests para validación. Policies para autorización (rol EMPLOYER vs EMPLOYEE).
- Migraciones con foreign keys explícitas y `onDelete` definido siempre.
- Rutas web en español para el usuario (`/equipo/crear`, `/tareas/hoy`), nombres de ruta en inglés (`team.create`, `tasks.today`).

## 4. Esquema de base de datos

### teams
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| code | string(12) unique | Código de invitación legible, ej. `JARDIN-4832`. Generado: palabra + 4 dígitos. Reintento si colisión. |
| name | string(80) | Nombre del negocio/equipo |
| max_members | tinyint unsigned, default 5 | Límite. Solo modificable en BD (MVP) |
| tasks_generated_until | date nullable | Última fecha para la que se generaron tareas recurrentes (ver §6) |
| settings | json nullable | Reservado (zona horaria default `Europe/Madrid`, etc.) |
| timestamps | | |

### members
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| team_id | FK teams, cascadeOnDelete | |
| role | enum(EMPLOYER, EMPLOYEE) | El creador del equipo es EMPLOYER. Un equipo puede tener más de un EMPLOYER |
| name | string(60) | |
| email | string unique nullable | Solo si el usuario vincula email (multi-dispositivo) |
| email_verified_at | timestamp nullable | |
| active | boolean default true | Baja lógica; nunca borrar members con historial |
| last_seen_at | timestamp nullable | |
| timestamps | | |

*(Implementado: sin columna `device_token` aquí — ver `member_devices` abajo, tal como ya anticipaba la nota de diseño de §5.4.)*

### member_devices
Identidad por dispositivo sin login. Sustituye por completo al `device_token` único que aparecía en una versión anterior de `members`.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| member_id | FK members, cascadeOnDelete | |
| device_token | uuid unique | Se guarda en cookie httpOnly `gestiodia_device`, 400 días |
| last_used_at | timestamp nullable | |
| timestamps | | |

Un member puede tener varias filas aquí (multi-dispositivo). Cada creación de equipo, unión, magic link consumido o regeneración de acceso crea una fila nueva.

### recurring_tasks
Plantillas de tareas fijas diarias. NO son las tareas del día; son el molde.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| team_id | FK teams, cascadeOnDelete | |
| title | string(120) | |
| description | text nullable | |
| assigned_member_id | FK members nullable, nullOnDelete | null = "cualquiera del equipo" |
| requires_photo | boolean default false | Si al completar se exige foto de evidencia |
| active | boolean default true | Desactivar en vez de borrar (preserva histórico vía tasks.recurring_task_id) |
| sort_order | smallint default 0 | Orden en checklist |
| timestamps | | |

### tasks
Instancias reales: tanto las generadas desde recurring_tasks como las puntuales.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| team_id | FK teams, cascadeOnDelete | |
| recurring_task_id | FK recurring_tasks nullable, nullOnDelete | null = tarea puntual |
| task_date | date, index | |
| title | string(120) | Copiado de la plantilla al generar (snapshot; editar la plantilla no altera el histórico) |
| description | text nullable | |
| assigned_member_id | FK members nullable, nullOnDelete | |
| requires_photo | boolean default false | Snapshot de la plantilla |
| completed_at | timestamp nullable | |
| completed_by_member_id | FK members nullable | |
| completion_note | string(500) nullable | |
| photo_path | string nullable | Ruta relativa en disco `public`. Vuelve a `null` cuando se poda por retención (ver §7) |
| photo_pruned_at | timestamp nullable | Cuándo se podó la foto por retención; `null` mientras la foto sigue viva |
| timestamps | | |

**Índice único obligatorio:** `UNIQUE(recurring_task_id, task_date)` (donde recurring_task_id no es null). Es la garantía de idempotencia de la generación diaria: cron y fallback lazy pueden ejecutarse a la vez sin duplicar (usar `insertOrIgnore` / capturar violación de unicidad).

Índice compuesto: `(team_id, task_date)` — es la query principal de la app.

### work_sessions
Registro de jornada (fichaje). En España el empleador debe conservar registros 4 años → nunca hard-delete.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| team_id | FK teams, cascadeOnDelete | |
| member_id | FK members, restrictOnDelete | |
| work_date | date, index | Derivada de clocked_in_at en TZ del equipo |
| clocked_in_at | timestamp | |
| clocked_out_at | timestamp nullable | null = jornada abierta |
| auto_closed | boolean default false | Se marca cuando `clockIn()` cierra sola una sesión olvidada de un día anterior (ver §8) |
| edited_by_member_id | FK members nullable | Solo EMPLOYER puede corregir |
| edit_reason | string(255) nullable | Obligatorio si hay edición |
| original_values | json nullable | Valores previos a la edición (trazabilidad) |
| timestamps | | |

Índice compuesto: `(member_id, work_date)`. Regla de negocio: un member no puede tener dos sesiones abiertas simultáneas.

### magic_links
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| member_id | FK members, cascadeOnDelete | |
| token | string(64) unique | hash del token enviado (guardar hash, enviar plano) |
| expires_at | timestamp | 15 minutos |
| used_at | timestamp nullable | Un solo uso |

## 5. Autenticación e identidad

1. **Crear equipo:** formulario (nombre negocio + nombre persona) → crea `team` + `member` EMPLOYER → genera `device_token` → cookie `gestiodia_device` (httpOnly, secure, sameSite=lax, 400 días) → redirect al panel.
2. **Unirse:** código de equipo + nombre → valida `max_members` contra members activos → crea `member` EMPLOYEE + token + cookie.
3. **Middleware `ResolveMember`:** lee cookie → busca member activo → inyecta en request. Sin cookie válida → landing.
4. **Vincular email:** desde ajustes, member introduce email → `MagicLinkService` envía un enlace de un solo uso (Mail de Laravel vía SMTP, no Nodemailer). Al abrir el enlace en cualquier dispositivo: verifica el token, marca `email_verified_at` si era la primera vez, y crea una fila nueva en `member_devices` (ver §4) para ese dispositivo — así queda vinculado sin tocar los dispositivos ya existentes del member.
   - **Recuperación sin email** *(mismo mecanismo, reutilizado)*: la implementación del punto 5 usa este mismo `MagicLinkService` — el EMPLOYER genera el enlace desde la pantalla Equipo, pero en vez de enviarlo por correo lo comparte manualmente (WhatsApp, en persona).
5. **Recuperación:** si pierde el dispositivo y no vinculó email → el EMPLOYER puede regenerar la invitación del miembro (nueva entrada en member_devices al unirse de nuevo con el mismo member). Si el EMPLOYER pierde acceso sin email → sin recuperación en MVP (avisar en onboarding con banner suave: "Vincula tu correo para no perder el acceso").

## 6. Generación de tareas diarias

Doble mecanismo, ambos idempotentes gracias al índice único:

1. **Comando artisan `tasks:generate-daily {--date=}`:** para cada team con recurring_tasks activas, inserta las tasks del día con `insertOrIgnore`, actualiza `teams.tasks_generated_until`. Debe procesar en chunks (`Team::chunkById(100)`) y loggear resumen (teams procesados, tasks insertadas, duración, memoria pico) en canal `daily` propio (`storage/logs/generation.log`).
2. **Fallback lazy:** middleware/hook en las rutas de vista de tareas: si `team.tasks_generated_until < today` → `TaskService::generateForTeam($team, today())` inline (solo ese team, es barato) → actualiza la marca. Envolver en try/catch: si falla, la vista carga igualmente con lo que exista.

La lógica de generación vive en un único método `TaskService::generateForTeam(Team $team, CarbonImmutable $date): int` usado por ambos caminos. Zona horaria: `Europe/Madrid` (desde settings del team en el futuro).

## 7. Fotos de evidencia

- Input `<input type="file" accept="image/*" capture="environment">` — en móvil abre la cámara directamente.
- **Sin compresión en cliente** *(implementado distinto al plan original)*: se probó comprimir con Alpine + canvas (`createImageBitmap` + redimensionar a 1600px + JPEG 0.7) antes de subir, pero con fotos de iPhone de alta resolución (12-48 MP) la decodificación en el navegador colgaba/crasheaba Safari — el propio `createImageBitmap` tiene que cargar la imagen original completa en memoria antes de poder reducirla. Se optó por subir el archivo tal cual (máx 30 MB, validado en el Form Request) y dejar **todo** el procesamiento al servidor, que es más rápido y no tiene los límites de memoria de un navegador móvil. Solo defendible porque el objetivo son redes españolas rápidas (fibra/4G-5G); en redes muy lentas convendría retomar la compresión en cliente.
- Servidor: validar mimes reales (`jpeg,jpg,png,webp` — deliberadamente sin `heic/heif`, que GD no puede decodificar), procesar con Intervention Image (GD): redimensionar a 1600px máx + recodificar a JPEG calidad 85. GD no escribe EXIF al recodificar, así que el GPS se descarta solo (privacidad gratis). Guardar en `storage/app/public/photos/{team_id}/{Y-m}/` con nombre uuid. Envolver la llamada al service en un `try/catch` de `DecoderException`/`NotSupportedException` de Intervention Image para dar un mensaje claro si el archivo no se puede procesar, en vez de un 500.
- `php artisan storage:link` **falla en Hostinger** (confirmado, no solo "puede fallar"): `exec()` está bloqueado en PHP y el comando depende de eso. Fallback real usado (no el de streaming controller que planteaba la primera versión de este documento, no hizo falta): crear el symlink a mano por SSH una sola vez (`ln -s ../storage/app/public public/storage`) — sobrevive a redespliegues posteriores porque el deploy es `git pull`, no clonado limpio. Detalle completo en `fotos-hostinger.md` (raíz del repo).
- **Retención MVP:** las fotos se conservan 90 días; comando `photos:prune` (cron semanal) borra archivo y pone `photo_path = null` dejando `photo_pruned_at`. Comunicarlo en UI ("las fotos se guardan 3 meses"). Sin esto, el disco de Hostinger se llena de forma no acotada.
- El panel EMPLOYER muestra una miniatura clicable de la foto (56×56, enlaza a la imagen completa) junto a cada tarea completada con evidencia, vía un accessor `Task::photoUrl()`.

## 8. Registro de jornada (MVP)

- Botón grande único en la home del empleado: **"Empezar jornada"** ↔ **"Terminar jornada"** (toggle según haya sesión abierta). Confirmación con la hora en grande.
- Protección contra doble clic y contra sesión abierta olvidada: si al fichar entrada existe una sesión abierta de un día anterior, se cierra automáticamente a las 23:59 de su work_date con flag `auto_closed` (columna boolean) y se avisa al EMPLOYER en su panel.
- Panel EMPLOYER: vista semanal por empleado (día, entrada, salida, total horas), indicador de sesiones auto-cerradas o editadas.
- Edición por EMPLOYER con `edit_reason` obligatorio y snapshot en `original_values`.
- **Export CSV** de un rango de fechas (obligación legal de registro horario en España; es el gancho comercial). Sin PDF en MVP.

## 9. Identidad visual y pantallas del MVP

### Branding (definido — no improvisar)

**Paleta oficial** (definir como CSS custom properties Y como variables SCSS de Bootstrap):

| Token | Hex | Uso |
|---|---|---|
| `--gd-green-primary` | `#2E7D32` | Color de marca. Botones primarios (texto blanco), enlaces, navbar oscura, theme_color PWA. Único verde permitido para elementos con texto |
| `--gd-green-accent` | `#66BB6A` | Éxito y acento. SOLO para iconos de check, badges de estado, ilustraciones y elementos grandes SIN texto. **PROHIBIDO como fondo de botón con texto o como color de texto sobre blanco** (contraste 2.3:1, no pasa WCAG AA — usuarios mayores) |
| `--gd-text-primary` | `#212121` | Texto principal |
| `--gd-text-secondary` | `#616161` | Texto secundario. Nunca por debajo de 16px |
| `--gd-bg` | `#F7F7F5` | Fondo de app. Cards en blanco puro `#FFFFFF` sobre este fondo |

Mapeo Bootstrap: `$primary: #2E7D32`, `$success: #66BB6A` (con la restricción anterior; `$btn-success` debe sobreescribirse a fondo #2E7D32 o no usarse), `$body-bg: #F7F7F5`, `$body-color: #212121`.

**Tipografía:** **Poppins** (Google Fonts). Pesos: 400 (texto), 500 (labels/botones), 700 (títulos y cifras grandes como el contador de jornada). **Self-hostear** los woff2 en `public/fonts/` con `font-display: swap` y preload del peso 400 — no depender del CDN de Google (rendimiento en móviles de gama baja y RGPD). Números de jornada/horas en 700 y tamaño grande (patrón "6h 30m" del mockup de marca).

**Logo y assets** — el desarrollador recibirá los archivos en `public/brand/`; el código debe referenciarlos con estos nombres exactos:

- `logo-horizontal.svg` — símbolo + wordmark bicolor ("Gestio" #2E7D32 / "Dia" #66BB6A) para fondo claro. Header de la app y landing
- `logo-horizontal-dark.svg` — variante para fondo verde oscuro ("Gestio" blanco / "Dia" #66BB6A). Footer y secciones oscuras
- `icon.svg` — símbolo solo (sol + hoja/check), origen de todos los tamaños
- `icon-512.png`, `icon-192.png` — PWA manifest
- `icon-128.png`, `icon-48.png`, `favicon.ico`, `apple-touch-icon.png` (180×180)

Reglas de uso: el símbolo nunca se recolorea ni se estira; área de respeto mínima = altura del check; sobre fotos siempre con la variante dark sobre overlay verde. Claim oficial para landing y meta descriptions: **"Simple. Útil. Hecho. — Todo lo que necesitas para gestionar el día con tu equipo."**

### Pantallas

Navegación: barra inferior fija con 3 items máximo, icono + texto siempre (referencia del mockup de marca: Hoy · Equipo · Más).

**Empleado:** Hoy (checklist + botón fichaje) · Mi semana (historial propio) · Ajustes. *(3 items, cumple el límite tal cual.)*
**Empleador:** Hoy (estado del equipo: tareas hechas/pendientes, quién ha fichado) · Tareas (CRUD recurrentes y puntuales) · Equipo (miembros, código de invitación, jornadas semanales, export) · Ajustes.

*(Resolución implementada: EMPLOYER necesita 4 secciones, incompatible con el límite de 3 de la barra inferior. Se resolvió sacando Ajustes de la barra: para EMPLOYER queda como icono fijo en la cabecera de la app, y la barra inferior se queda en 3 items — Hoy · Tareas · Equipo. Para EMPLOYEE, Ajustes sí vive en la barra inferior, ya que ahí solo hay 3 items en total.)*

Reglas UI (obligatorias):
- Font-size base 18px; botones de acción principal min-height 56px, ancho completo en móvil.
- Máximo una acción primaria por pantalla.
- Confirmaciones explícitas y grandes ("✓ Tarea completada — 10:32"), nunca solo un toast.
- Nada de gestos ocultos (swipe para borrar, long-press): todo con botones visibles.
- Estados vacíos con texto guía ("Aún no hay tareas hoy. Pulsa el botón verde para crear una").
- Personalizar variables Bootstrap con la paleta y tipografía de la sección de branding (`$font-size-base: 1.125rem`, `$font-family-sans-serif: 'Poppins'...`, `$btn-padding-y-lg`, `$border-radius: .75rem` — radios generosos como en el branding, contraste AA mínimo en toda combinación texto/fondo).

## 10. PWA (incluido en MVP)

- `manifest.json`: `name: "GestioDia"`, `short_name: "GestioDia"`, `theme_color: "#2E7D32"`, `background_color: "#F7F7F5"`, iconos `public/brand/icon-192.png` y `icon-512.png` (añadir también variante maskable con padding del 20% para Android), `display: standalone`.
- Service worker mínimo: cache de shell estático (CSS/JS/fuentes) con estrategia stale-while-revalidate; las vistas y datos SIEMPRE network-first (no cachear HTML de tareas: riesgo de estado desactualizado). Sin sync offline en MVP.
- Banner propio de "Añadir a pantalla de inicio" con instrucciones por navegador (los usuarios objetivo no conocen el menú del navegador).

## 11. Seguridad y límites

- Rate limiting: crear equipo (3/hora/IP), unirse (10/hora/IP), magic links (3/hora/email).
- Códigos de equipo: no enumerables (palabra aleatoria de diccionario propio + 4 dígitos ≈ espacio suficiente + rate limit).
- Validar `max_members` con lock (`lockForUpdate`) al unirse para evitar carrera.
- Todas las queries de datos filtradas por `team_id` del member autenticado (Global Scope `BelongsToTeam` o pasar siempre el team desde el middleware — nunca confiar en IDs del request).
- Cookies: httpOnly + secure + sameSite. HTTPS forzado.

## 12. Estructura de proyecto

Repo único `gestiodia` (monolito). `app/Services`, `app/Http/Controllers`, `app/Http/Middleware/ResolveMember.php`, `app/Console/Commands/GenerateDailyTasks.php` y `PrunePhotos.php`, `public/brand/` (assets de logo según §9 — mientras no llegan los archivos reales, hay placeholders con los nombres exactos y TODO), `public/fonts/` (Poppins woff2 self-hosted). Seeders de demo (1 team, 3 members, 5 recurring, 2 semanas de histórico) para desarrollo y para las pruebas de carga descritas en el documento de operación.

*(Implementado distinto al plan original en un punto: no hay `layouts/employer.blade.php` ni `layouts/employee.blade.php` separados — un único `layouts/app.blade.php` incluye condicionalmente `partials/bottom-nav.blade.php` y `partials/top-bar.blade.php` según el rol del member resuelto en el contenedor. Evita duplicar el layout base para una diferencia que es solo de navegación.)*

Documentación viva del proyecto (fuera de este archivo de spec): `docs/despliegue-hostinger.md` (guía de despliegue paso a paso, con tabla de errores encontrados) y `fotos-hostinger.md` (raíz del repo, guía portable del pipeline de fotos para reutilizar en otros proyectos Hostinger).

## 13. Fuera de alcance (NO implementar)

Pagos, panel admin de plataforma, notificaciones push, multiidioma (la estructura `lang/` queda lista, pero solo `es`), chat, geolocalización en fichaje, API pública (la capa de services queda preparada, pero no exponer rutas API en MVP).
