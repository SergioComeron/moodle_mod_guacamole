# Changelog

## [v1.7] - 2026-05-04

### Correcciones
- correct multi-line function call format in showimages.php (PHPCS)
- include all name fields in user query for fullname() in showimages.php

### Otros cambios
- release: bump to v1.7 and update build date to 2026050401
- chore: add Composer cleanup script to keep only Compute service


## [v1.6] - 2026-04-27

### Nuevas funcionalidades
- differentiate new VM vs restart in loading UX
- progress bar with live status in start.php
- async VM polling — eliminate sleep() from load.php

### Correcciones
- remove duplicate heading in showimages.php
- replace jQuery selectors with vanilla JS in view.php
- PHPCS errors — spacing in getComputersUsed, line length in settings.php
- remove jQuery CDN, fix id=box quotes, replace error(), remove duplicate require, update plugin->requires to Moodle 4.0
- correct config.php path in grade.php (missing leading slash)
- replace exit() with exceptions in GCP functions, add try/catch in cron_task_delete
- replace hardcoded 'Pruebas' with 'mod_guacamole' in GCP client application name
- remove state tabs, use Moodle langconfig date format in showimages.php
- wall-clock timeout in waitForZoneOperation, null guard for guaidconnection, clean orphaned loading VMs
- add HTTP timeout to all GCP API calls via guacamole_gcp_client() helper
- preserve 'started' state in load.php and use lowercase name in cron_task
- smart button behavior based on VM state in view.php
- preserve view.php content when opening VM in new tab
- fallback to obtenerIdInstanciaGuacamole() if crearConexion() returns empty
- use \$guaidconnection directly instead of re-querying Guacamole
- change vm_ready message to 'Validando servicio de escritorio remoto'
- async VM deletion — showimages.php no longer blocks on GCP calls
- wait guacamole_seconds_wait after RUNNING before redirecting
- disable PHP time limit in load.php for VM creation
- increase execution time limit in load.php to 180s
- handle load.php errors gracefully in start.php
- guard fechadesconexion() against non-array entries in history response
- start.php outputs minimal HTML without Moodle chrome
- embedded layout for start.php and null check in load.php
- use absolute URL for load.php in fetch() call
- replace AMD require/jQuery with native fetch() in start.php
- guard guacamole_get_token() against non-array JSON response
- add proper Moodle page setup to start.php
- add CSRF protection to load.php AJAX endpoint
- escape $url with json_encode() to prevent XSS in view.php
- update google/apiclient to v2.19.2 and bump PHP platform to 8.1
- use guacamole_get_token/api_request helpers in cron_task and handle activeConnections as map
- auto-release tag has double 'v' when release already starts with v
- get_instance() now uses its $id parameter instead of hardcoded 3
- validate userid against authenticated user in load.php
- add thirdpartylibs.xml to exclude vendored google-api client
- resolve all CI failures (postgres, phpcs, phpunit coverage)
- add test generator and fix phpcs CI invocation
- resolve all PHPCS errors across the codebase

### Documentación
- actualizar CHANGELOG para vv1.5 [skip ci]

### Otros cambios
- ci: add workflow_dispatch and remove path filter from auto-release
- test: expand coverage for getComputersUsed, orphaned loading cleanup, null guaidconnection and deleting state
- improve: fix N+1 query in getComputersUsed, add state/root DB index, make GCP machine and disk type configurable
- improve: rewrite showimages.php with state badges, counters, tabs, user info and dates
- style: replace emoji with CSS spinner in VM loading screen
- revert: restore original view.php button behavior (content replace on click)
- debug: wrap load.php logic in try-catch to return JSON errors
- refactor: extract guacamole_get_token() and guacamole_api_request() helpers
- ci: add auto-release workflow on version.php bump
- Fix CI: upgrade MySQL to 8.4 and install en_AU.UTF-8 locale
- CI: test against Moodle 4.5, 5.0 and 5.1 with PHP 8.1-8.3
- CI: add PostgreSQL and test against Moodle 4.3, 4.4 and 4.5
- Fix CI: set max_input_vars=5000 required by Moodle PHPUnit
- Fix CI: remove pre-created DB and use root for moodle-plugin-ci
- Fix CI: pass db credentials explicitly to moodle-plugin-ci install
- Fix CI: use composer create-project to install moodle-plugin-ci
- Add PHPUnit tests, PHPCS config and GitHub Actions CI workflow
- Add .claude/settings.local.json to .gitignore
- Add Dependabot config for Google API PHP client updates
- Add .gitignore to exclude CLAUDE.md
- new version 1.5
- new version google api and fix regresion icon 4.0
- new version googple api php. v2.16.0
- Google PHP api version v2.15.3
- new version 1.4 (2023093100)
- fix index.php table
- new version
- icon compatibility with moodle 4
- new version 1.2 (2023061302)
- Fix problem with disktype
- new version
- Tipo disco duro
- new version
- Cambio tipo de máquina
- Cambio disco normal por ssd
- new version
- correct get_objectid_mapping function
- add function get_other_mapping()
- Actualización API google 2.12.6
- Se elimina prueba.php
- Check that it is in the production environment to avoid machine deletions in other environments.
- borro json
- Produccion 2020021400


## [vv1.5] - 2026-04-17

### Correcciones
- add thirdpartylibs.xml to exclude vendored google-api client
- resolve all CI failures (postgres, phpcs, phpunit coverage)
- add test generator and fix phpcs CI invocation
- resolve all PHPCS errors across the codebase

### Otros cambios
- ci: add auto-release workflow on version.php bump
- Fix CI: upgrade MySQL to 8.4 and install en_AU.UTF-8 locale
- CI: test against Moodle 4.5, 5.0 and 5.1 with PHP 8.1-8.3
- CI: add PostgreSQL and test against Moodle 4.3, 4.4 and 4.5
- Fix CI: set max_input_vars=5000 required by Moodle PHPUnit
- Fix CI: remove pre-created DB and use root for moodle-plugin-ci
- Fix CI: pass db credentials explicitly to moodle-plugin-ci install
- Fix CI: use composer create-project to install moodle-plugin-ci
- Add PHPUnit tests, PHPCS config and GitHub Actions CI workflow
- Add .claude/settings.local.json to .gitignore
- Add Dependabot config for Google API PHP client updates
- Add .gitignore to exclude CLAUDE.md
- new version 1.5
- new version google api and fix regresion icon 4.0
- new version googple api php. v2.16.0
- Google PHP api version v2.15.3
- new version 1.4 (2023093100)
- fix index.php table
- new version
- icon compatibility with moodle 4
- new version 1.2 (2023061302)
- Fix problem with disktype
- new version
- Tipo disco duro
- new version
- Cambio tipo de máquina
- Cambio disco normal por ssd
- new version
- correct get_objectid_mapping function
- add function get_other_mapping()
- Actualización API google 2.12.6
- Se elimina prueba.php
- Check that it is in the production environment to avoid machine deletions in other environments.
- borro json
- Produccion 2020021400

