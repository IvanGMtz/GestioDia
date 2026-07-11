# Despliegue en Hostinger (hosting compartido, apps Laravel/PHP)

Guía paso a paso para desplegar GestioDia (o cualquier proyecto Laravel similar) en un hosting compartido de Hostinger vía Git, partiendo de cero. Escrita a partir de un despliegue real, incluyendo los errores encontrados y por qué cada paso es necesario.

**Contexto del plan de hosting usado:** shared hosting sin acceso a `exec()`/`shell_exec()` desde PHP, sin proceso daemon para colas, con acceso SSH disponible, y **sin opción de cambiar el "document root" del sitio** (el panel solo ofrece un "Administrador de índice de carpetas", que es otra cosa — controla el listado de directorios, no la raíz servida). Esto último es la restricción que más condiciona todo el proceso.

---

## 0. Prerrequisitos

- Cuenta de Hostinger con hosting compartido contratado.
- Repositorio en GitHub con el proyecto Laravel.
- Base de datos MySQL de Hostinger ya creada (puede ser la misma para todos los entornos si no hay MySQL local disponible).
- Acceso SSH habilitado en el plan (hPanel → Avanzado → Acceso SSH).

---

## 1. Crear el sitio web y el dominio (temporal o real)

_[Pendiente de documentar con capturas — completar en el próximo despliegue desde cero: pantalla inicial de "Sitios web" → crear sitio → elegir dominio temporal `*.hostingersite.com` o dominio propio.]_

---

## 2. Conectar el repositorio por Git

hPanel → **Sitios web** → gestionar el dominio → **Avanzado** → **GIT**.

1. Conecta la cuenta de GitHub si no lo está ya (botón "Conectado con GitHub").
2. Elige el repositorio y la rama (`main`).
3. **Directorio raíz del despliegue: usa `public_html` a secas — NO uses una subcarpeta como `public_html/mi_app`.**

### Por qué NO usar una subcarpeta

Es la lección más cara de este despliegue. Como el plan no permite cambiar el document root, Apache siempre sirve directamente desde `public_html/`. Si el repo se clona en `public_html/mi_app/`, hacen falta **dos** `.htaccess` distintos (uno en `public_html/` reescribiendo hacia la subcarpeta, y el propio del repo) — y en la práctica esto genera reescrituras dobles y rutas rotas (`mi_app/public/public/...`), además de un archivo `.htaccess` que vive fuera del repo y no se puede versionar ni desplegar automáticamente.

**Desplegando directo en `public_html`**, el `.htaccess` de la raíz del repo (ver §6) cae exactamente en el document root real, sin capas intermedias, y viaja con cada `git push`.

4. Guarda y dale a **"Implementar"** / **"Redesplegar"**.

> Los despliegues Git de Hostinger hacen `git pull` (no un clonado limpio cada vez), así que archivos no versionados como `.env`, el symlink de `storage` o `vendor/` sobreviven a los redespliegues siguientes — solo hay que crearlos una vez.

---

## 3. Habilitar acceso remoto a MySQL

Aunque el sitio web y la base de datos estén en la misma cuenta de Hostinger, si corren en servidores físicos distintos (habitual: `srv####.hstgr.io` para MySQL vs el servidor de hosting compartido), la conexión se trata como remota y necesita estar en la whitelist.

hPanel → **Bases de datos** → **MySQL remoto** → añade:
```
%
```
(permite cualquier host; se puede restringir más adelante si Hostinger documenta rangos de IP internos).

Sin esto, cualquier comando que toque la BD desde SSH (`migrate`, etc.) falla con:
```
SQLSTATE[HY000] [1045] Access denied for user '...'@'...' (using password: YES)
```
aunque el usuario/contraseña sean correctos.

---

## 4. Conectar por SSH y preparar el proyecto

hPanel → Avanzado → Acceso SSH, copia el comando de conexión (`ssh -p PUERTO usuario@host`).

```bash
cd public_html
pwd
php -v            # confirma versión (debe coincidir con la que pide composer.json)
composer --version
```

### 4.1 Instalar dependencias de Composer

```bash
composer install --no-dev --optimize-autoloader
```

### 4.2 Crear el `.env` de producción

`.env` nunca viaja por Git (está en `.gitignore` a propósito). Créalo a mano:

```bash
cat > .env <<'EOF'
APP_NAME=GestioDia
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://TU-DOMINIO.hostingersite.com
APP_TIMEZONE=Europe/Madrid

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=srv####.hstgr.io
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
EOF
```

Ajusta `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` a los valores reales. Nota: `QUEUE_CONNECTION=sync` y `SESSION_DRIVER=file`/`CACHE_STORE=file` son obligatorios en este tipo de hosting compartido — no hay proceso daemon para colas persistentes.

### 4.3 Generar la clave de la app

```bash
php artisan key:generate --force
```

Mejor generarla directamente en el servidor que reutilizar una clave que haya pasado por chat/otros canales.

### 4.4 Symlink de `storage` (sin `php artisan storage:link`)

Hostinger bloquea `exec()`/`shell_exec()` a nivel de PHP. `php artisan storage:link` internamente depende de eso en algunos casos y falla con:
```
Call to undefined function Illuminate\Filesystem\exec()
```

Solución: crear el symlink directamente por shell (no pasa por PHP, así que la restricción no aplica):

```bash
ln -s ../storage/app/public public/storage
ls -la public/storage   # debe mostrar la flecha -> ../storage/app/public
```

### 4.5 Migraciones

```bash
php artisan migrate --force
```
(`--force` evita el prompt de confirmación que Laravel pide en `APP_ENV=production`)

### 4.6 Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

---

## 5. Subir los assets compilados (`public/build`)

`public/build` (CSS/JS de Vite) tampoco viaja por Git a propósito — se compila en local y se sube, nunca se compila en el servidor.

**En tu máquina local**, dentro del proyecto:
```bash
npm run build
rsync -avz -e "ssh -p TU_PUERTO" public/build/ TU_USUARIO@TU_HOST:~/public_html/public/build/
```

Repite este paso cada vez que cambies CSS/JS.

---

## 6. El `.htaccess` de la raíz (pieza crítica)

Como no hay forma de fijar el document root a `public/`, este archivo vive en la **raíz del repo** (por tanto viaja con Git y llega a `public_html/.htaccess` en cada despliegue) y hace dos cosas: reescribe todo hacia `public/`, y añade una capa extra de bloqueo explícito de archivos sensibles por si el rewrite alguna vez fallara.

`.htaccess` (raíz del proyecto):
```apache
<IfModule mod_authz_core.c>
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
    <FilesMatch "\.(env|env\.[a-z]+|lock|log|sqlite|sql)$">
        Require all denied
    </FilesMatch>
</IfModule>
<IfModule !mod_authz_core.c>
    <FilesMatch "^\.">
        Order allow,deny
        Deny from all
    </FilesMatch>
    <FilesMatch "\.(env|env\.[a-z]+|lock|log|sqlite|sql)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

**Por qué funciona:** cualquier petición (incluida `/`) se reescribe internamente hacia `public/$1` antes de que Apache busque el archivo. Como `app/`, `vendor/`, `.env`, `.git/` viven fuera de `public/`, nunca son alcanzables por URL — la petición reescrita simplemente no encuentra el archivo dentro de `public/` y cae al `index.php` de Laravel, que devuelve 404. El bloqueo explícito de dotfiles es una segunda barrera independiente de que el rewrite siga activo.

**Verificación tras cada despliegue** (desde cualquier máquina, no hace falta SSH):
```bash
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/.env            # debe dar 404
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/.git/config     # debe dar 403 o 404
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/composer.json   # debe dar 404
curl -s -o /dev/null -w "%{http_code}\n" https://TU-DOMINIO/vendor/autoload.php  # debe dar 404
```
Si alguno responde 200, algo está mal — revisa el `.htaccess` antes de seguir.

---

## 7. Runbook para despliegues posteriores (no el primero)

Una vez configurado todo lo anterior, los siguientes despliegues son mucho más cortos:

1. **Local**: si tocaste CSS/JS, `npm run build`. Luego `git add` / `commit` / `push`.
2. Hostinger despliega solo tras el push (auto-deploy confirmado); si no, hPanel → GIT → **Redesplegar**.
3. **Solo si cambió `composer.json`/`composer.lock`**, por SSH: `composer install --no-dev --optimize-autoloader`.
4. **Solo si tocaste CSS/JS**, sube `public/build` por `rsync` (paso 5).
5. **Solo si hay migraciones nuevas**, por SSH: `php artisan migrate --force`.
6. `.env` y el symlink de `storage` no hay que rehacerlos — sobreviven porque el despliegue es `git pull`, no un clonado limpio.

No hay forma de automatizar los pasos 3-5 en este plan (sin comandos post-despliegue en el panel de Git, y `exec()` bloqueado impide hacerlo desde dentro de la propia app).

---

## 8. Errores encontrados y su causa (referencia rápida)

| Síntoma | Causa | Solución |
|---|---|---|
| 403 Forbidden en la raíz del dominio | Document root apunta a una carpeta sin `index.php` (repo entero sin reorganizar, o subcarpeta sin `.htaccess` en el nivel correcto) | Desplegar directo en `public_html` + `.htaccess` raíz (§2, §6) |
| Página "¡Ya todo está listo!" / placeholder de Hostinger | El dominio sigue sirviendo `public_html/default.php` porque el `.htaccess` real no está en el document root verdadero | Confirmar con Administrador de Archivos en qué carpeta cae realmente `public_html`, poner el `.htaccess` ahí, borrar `default.php` |
| `Call to undefined function Illuminate\Filesystem\exec()` al correr `storage:link` | `exec()` deshabilitado en PHP (restricción de Hostinger) | Symlink manual por shell (§4.4) |
| `SQLSTATE[HY000] [1045] Access denied ... (using password: YES)` en `migrate` | El servidor de hosting no está en la whitelist de MySQL remoto (BD y web son servidores físicos distintos) | Añadir `%` en MySQL remoto (§3) |
| 500 genérico ("An internal server error has occured"), igual con `APP_DEBUG=true`, sin log en `storage/logs/laravel.log` | El fallo ocurre antes de que Laravel arranque — normalmente Apache/`.htaccess` mal ubicado, no la app | `php artisan about` por SSH para descartar que sea la app; revisar dónde cae realmente el document root |
| `~/public_html` no existe pero `public_html/algo` (ruta relativa) sí funciona | La sesión SSH no arranca en la raíz cruda de la cuenta (`$HOME`), sino en un directorio específico del dominio | Usar `pwd` y rutas relativas al conectar, no asumir `~/public_html` |
