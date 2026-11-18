## tridicapas.com

Este es un sitio web construido sobre Drupal 11, es una tienda en línea para productos construidos con tecnología de impresión 3D y diseño propio.
Este es un emprendimiento familiar. Es importante mantener los costos bajos en este proyecto.


## Estado del sitio

- El sitio **todavía no es accesible en producción** (no está publicado al público). Aun así, cada push a `main` despliega automáticamente al servidor (ver "Despliegue").

## Hosting

- Producción está alojada en **Hostinger, hosting compartido (shared)**.
  - Sin acceso root ni servicios propios (no hay Redis, Solr, workers, cron de sistema a medida, etc.); todo debe funcionar con PHP + MySQL/MariaDB del plan compartido.
  - El document root del dominio es `public_html/` (por eso el proyecto usa esa carpeta como web root en lugar de `web/`).
  - El proyecto vive en `~/domains/tridicapas.com` en el servidor.

## Stack

- Drupal 11 (`drupal/recommended-project`), Drush 13, PHP 8.4, MariaDB.
- Módulos contrib principales: Commerce 3, Webform, Simple Sitemap, Twig Tweak, Admin Toolbar. Tema de administración: Gin.
- Tema público propio: `public_html/themes/custom/tridi` (independiente, sin tema base).
- Idioma por defecto: español (`es`).

## Desarrollo local
- Este prompt y la interacción con el agente en español pero mantener el codigo y comentarios en ingles, si ves comentarios en español tradúcelos.
- Entorno con DDEV: `ddev start`, `ddev drush cr`.
- URL local: DDEV asigna un puerto dinamicamente, puedes usar `ddev drush browse` para comprobar la url local.
Tambien puedes usar `ddev drush uli` para acceder como administrador.
- `public_html/sites/default/settings.local.php` contiene la configuración local y está ignorado por git.

## Configuración

- La configuración se exporta a `config/sync` (`ddev drush cex`) y se versiona.
- Después de instalar/desinstalar módulos o cambiar configuración desde la UI, exportar y commitear los cambios; si no, `drush deploy` los revertirá en producción.
- La configuración debe mantenerse siempre consistente, importar configuración  a la base de datos nunca puede fallar.

## Despliegue

- Push a `main` → GitHub Action (`.github/workflows/deploy.yml`) → SSH al servidor → `deploy.sh` (`git pull`, `composer install --no-dev`, `drush deploy`).
- El dominio en live es www.tridicapas.com

## Commits
- Mensajes en ingles.
- Seguir **Conventional Commits**: `tipo(alcance opcional): descripción`.
  - Tipos habituales: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`.
  - Ejemplos: `feat(theme): add product card template`, `fix(deps): update drupal core`, `chore(config): export block placement`.
- Un commit por cambio lógico (no mezclar tema, dependencias y configuración en el mismo commit).
