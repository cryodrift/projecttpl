### htmlload — Tiny PHP framework with batteries included

A lean, pluggable runtime that feels like a micro‑framework but ships with practical modules you actually use: a composable core (`sys`), a handy power‑user CLI (`src/shell`), and a password + 2FA user module (`src/user`).

- Zero build. Start with `php index.php`.
- One entry. `index.php` boots the core, resolves handlers, and routes CLI/web requests.
- Composable. Bring your own modules under `src/**`, or reuse the `sys` utilities.
- CLI‑first UX. Every handler can expose discoverable `@cli` commands.
- Web or CLI. Same config model, same handlers.


#### Why this project
You asked for a pragmatic toolkit, not a ceremony. This repo gives you a minimal but capable runtime:
- Core: request/response, routing, DI-ish object construction, logging, and PSR‑4‑style autoload mapping.
- Shell: everyday developer utilities (sort, dedupe, replace in streams/files, token tree grouping, screenshots via Chrome).
- User: small auth kit with password storage and optional Google Authenticator 2FA.


---

### Quickstart

- PHP 8+
- Run directly (no build step):

```bash
php index.php
```

CLI examples use the same entry point. Use the path to target a module, e.g. `/shell` or `/user`.

```bash
# List or run a CLI command (examples below)
php index.php /shell ...
php index.php /user ...
```

Tip: Use `-echo` to print output in some examples if your shell buffers output.


### Installation and bootstrap

This repo is directly runnable. For library use, you can consume the `sys` core as a Composer module inside another project (see `sys/README_CORE.md`). In this repository:

- Entry: `index.php`
  - Registers the `cryodrift\fw` core from `sys/`
  - Defines include roots for `src/` and `sys/`
  - Loads config via `Main::readConfig()` and runs via `Main::run()`

Key bootstrap lines (from `index.php`):

```php
require_once __DIR__ . '/sys/Main.php';
Main::$rootdir = dirname(Phar::running(false)) ? dirname(Phar::running(false)) . '/' : __DIR__ . '/';
Main::autoload('cryodrift\\fw', __DIR__.'/sys');
Main::autoloader();

$config = Main::readConfig();
return Main::run($config);
```

What the core does (see `sys/Main.php`):
- `Main::readConfig()` chooses CLI or Web config file and builds a `Config`
- `Main::run($config)` instantiates `Core`, executes handlers, collects `Response`, and prints/logs
- `Main::autoload()`/`Main::autoloader()` provide simple namespace→folder mapping

For deeper `sys` API documentation, read `sys/README_CORE.md`.


### Configuration

- Config lives under `.data/` by default (see `Config::$datadir`).
- On CLI, `Main::readConfig('cli')` resolves a CLI‑specific config; for Web, a web config.
- Handlers can declare their own config blocks (e.g., `src/user/Auth` expects `protect`, `cookiepassword`, etc.).

Use environment and local config files to wire modules the way you want. The core’s config loader will include from these search paths (see `index.php`):

- Project root
- `src/`
- `sys/`


### Routing model

- The framework resolves handlers by route segments for web requests, and by CLI segments for console runs.
- Handlers implement `cryodrift\fw\interface\Handler` and may use traits like `trait\CliHandler` to expose `@cli` methods.
- CLI arguments are mapped by name and docblocks. Types like `ParamFile` or `ParamHidden` add UX niceties (stdin/files and hidden prompts).


---

### Module: Shell (`src/shell`)
Developer convenience tools accessible as CLI commands.

All commands accept `-file` as either stdin or a file path where applicable.

- `undupe` — Remove duplicate lines
  - Usage: `php index.php /shell undupe -file="input.txt"`
  - Piped: `type input.txt | php index.php /shell undupe -file`

- `sort` — Sort lines
  - Usage: `php index.php /shell sort -file="input.txt"`

- `group` — Group lines by tokens into a tree view
  - Usage: `php index.php /shell group -file="commands.txt"`

- `replace` — Search/replace in piped data or a file
  - Usage: `php index.php /shell replace -search="from" -replace="to" -file="input.txt"`
  - Piped: `type input.txt | php index.php /shell replace "from" "to" -file`

- `screenshot` — Take a web screenshot via Chrome (uses your Chrome profile to avoid prompts)
  - Flags: `-url`, `-local` (bool), `-small` (bool), `-open` (bool)
  - Usage: `php index.php /shell screenshot -url="https://example.com" -small=1`

Implementation heads‑up: see `src/shell/Cli.php` for details and the `@cli` docblocks.


### Module: User (`src/user`)
Small user CLI + web utilities with optional TOTP 2FA (Google Authenticator compatible).

Core pieces:
- `src/user/Cli.php` — register, login, change password, test flows
- `src/user/Auth.php` — lightweight gatekeeper for protected routes using cookie‑bound sessions
- `src/user/db/Repository.php` — simple persistence and credential/secret storage

CLI commands:
- `register` — create user, set 2FA secret if password meets policy
  - `php index.php /user register -user="alice" -password`  (password will be prompted if left blank)
- `login` — verify password (+2FA if enabled)
  - `php index.php /user login -user="alice" -password -code=123456`
- `changepw` — change password
  - `php index.php /user changepw -user="alice" -password -newpassword`
- `getsecret` — print user’s 2FA secret (after auth)
- `getcode` — generate a current TOTP for a given secret
- `test` — end‑to‑end self‑check across register→login
- `keygen` — generate a random 2FA secret

Configuration hints:
- `passwordlen` (minimum length), `use2fa` (bool), and a `secretkey` used in the web flow.
- `Auth` middleware expects `protect` array and a `cookiepassword` to decrypt session ids from cookies.

Web flow:
- `Auth::handle()` inspects the current path segments and enforces protection for configured routes.
- In CLI mode, you can bypass via `-sessionuser`, but for Web the cookie/session must be valid.


---

### Developer UX

- Discoverability via docblocks: methods annotated with `@cli` become commands when the handler uses `CliHandler`.
- Strong typed params: framework resolves parameters (e.g., `ParamFile`, `ParamHidden`) and injects context.
- Logging by default: see `sys/Core` helpers (`Core::log`, `Core::echo`, timestamps via `Core::time`).


### Run modes

- CLI (project entry): `php index.php /module command -flag=value`
- PHAR: package the app as a `.phar` and run it directly
  - Example: `php cryodrift.phar /shell undupe -file="input.txt"`
  - Notes: `index.php` auto-detects PHAR via `Phar::running()` and `Main::pharmount()`; configure mounts in `Config::$pharmounts` if you need external file access
- Composer bin: run via the installed executable
  - Unix/macOS: `vendor/bin/cryodrift.php /shell undupe -file="input.txt"` (or `php vendor/bin/cryodrift.php ...`)
  - Windows: `php vendor\\bin\\cryodrift.php /shell undupe -file="input.txt"`
- Web: serve `public/` or point your server to `index.php`, then hit routes that map to your handlers.

Docker samples are included for convenience (`docker-compose-*.yml`), but are optional.


### Troubleshooting

- Missing class? Check `index.php` include roots and namespaces. `cryodrift\fw` maps to `sys/`, project code maps to `src/`.
- No output? Some handlers set response as "raw" and log to `.data/` — check `out.log` or `core.log` under `.data/`.
- Phar mode: `Main::pharmount()` supports running as a phar; ensure mounts are configured in `Config::$pharmounts`.


### Contributing

- Keep changes minimal and focused; mirror surrounding code style.
- Prefer small private helpers for one‑off reuse.
- Reuse utilities from the `sys` namespace where possible.


### License

See the repository for licensing terms.
