# Configuration (cfg/)

This folder contains small, optional PHP configuration snippets that are loaded by the application to tweak behavior without changing core code. Each file typically receives a $ctx (sys\Context) and may create one if not set.

General pattern used by files here:

```php
<?php
use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

$out = $ctx->config(); // often used to access/modify configuration
```

Notes
- You can guard features with environment variables via Core::env('VARNAME').

## How configuration is applied
- These cfg/*.php snippets are not included directly during bootstrap. Instead, the system builds a single cached config file.
- To generate/refresh the cached config, run one of:
  - php index.php /sys install
  - php index.php /sys config -mode=cli -write=true
- The generated files are written to the project root under:
  - .data/config-cache-cli.php (for CLI mode)
  - .data/config-cache-web.php (for web mode)
- Edit cfg/*.php as needed and re-run the command to apply changes. See the main Readme.md in the project root for more details.

## Files overview

- echoconfig.php
  Adds a handler (sys\tool\Echoblocker) before request handling to block echo/output for specific call sites.

- routesconfig.php
  Example place to add or remove routes dynamically using sys\Router.

- tokenftsconfig.php
  Configures src\tokenfts\db\Repository storage directory.

- userconfig.php
  Optionally enables user-auth-related components when USERUSEAUTH is set.


## Examples

### echoconfig.php
Add an output/echo blocker for selected call sites:

```php
<?php
use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

$out = $ctx->config();

$out->addHandlerbefore(0, \cryodrift\fw\tool\Echoblocker::class, [
  'config' => [
    'sys\\Main::path',
    'src\\mailviewer\\db\\SqliteStorage::getMessages',
    'src\\mailviewer\\Api::partview',
  ]
]);
```

Tip: Add or remove fully-qualified method references in the config list to control where echo is blocked.


### routesconfig.php
Remove an existing route and add a new one, programmatically:

```php
<?php
use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

// Remove a predefined route by name (web type)
// \cryodrift\fw\Router::remRoute($ctx, 'uploader', \cryodrift\fw\Router::TYP_WEB);

// Add your own route (maps "uploader/upload" to a handler class)
// \cryodrift\fw\Router::addConfigs($ctx, [
//   'uploader/upload' => \src\uploader\Api::class,
// ], \cryodrift\fw\Router::TYP_WEB);
```

Tip: Uncomment and adjust to your needs. Use TYP_API or other types if your project defines them.


### tokenftsconfig.php
Set a custom storage directory for token-FTS repository data:

```php
<?php
use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

$out = $ctx->config();

$cfg[\src\tokenfts\db\Repository::class] = [
  'storagedir' => G_ROOTDIR . '.data/users/',
];
```

Tip: Ensure the directory exists and is writable by the PHP process.


### userconfig.php
Enable user/auth related components conditionally via an environment variable:

```php
<?php
use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

$out = $ctx->config();

if (Core::env('USERUSEAUTH')) {
    \src\user\Auth::addConfigs($ctx, [
      'vendor',
      'quicklinks',
      'uploader',
      'src/testing',
    ]);
}
```

How to enable: set USERUSEAUTH=1 in your environment (e.g., in .env) to activate these components.


## Tips for adding new cfg files
- Copy the general pattern shown at the top: prepare $ctx and optionally $out = $ctx->config().
- Keep each file focused on one concern (e.g., routing, a feature toggle, a specific component).
- Prefer guarding optional behavior with Core::env('VARNAME') so it is easy to toggle without code edits.
- Names ending with "config.php" are conventional but not required.
