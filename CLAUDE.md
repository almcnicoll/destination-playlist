# Destination Playlist

Spotify-integrated collaborative playlist app. PHP, no framework, custom PDO-based ORM.

## Structure

- `class/` — model classes (autoloaded, no namespaces). `Model.php` is the base class all models extend.
- `pages/` — page controllers/views, included by `index.php` (router-style front controller).
- `ajax/` — standalone endpoints hit directly via XHR (assign_letters, assign_track, get_devices, proxy_search, etc. — Spotify device/track/search actions).
- `inc/` — `header.php`, `config.local.php` (gitignored, real config), `secret.php` (gitignored, credentials).
- `sql/create-tables.sql` — the **base schema as of before any `db-updates.sql` migrations were introduced** (version 0). It does NOT reflect the current full schema — e.g. no `letters.rank` column, no `faqs` table (added by later migrations).
- `sql/db-updates.sql` — versioned migrations, applied in order by `update.php`.
- `update.php` — run manually (browser or CLI) to bring the DB schema up to date.

## Autoloading

`autoload.php` registers a spl_autoloader mapping class name → `class/ClassName.php`, walking up to 3 parent directories to find it (so it works from `pages/`, `ajax/`, or root). No Composer, no PSR-4.

## Model base class (`class/Model.php`)

Every model declares `static string $tableName` and `static $fields` (column list, excluding `id`). Base class provides `getAll()`, `getById()`, `find($criteria)` (array of `[field, operator, value]` triples, operators limited to an allowlist), `save()` (insert-or-update based on whether `id` exists), `delete()`. `created`/`modified` are auto-stamped on save.

## Migration system (`update.php` + `DbUpdate.php`)

- `dbupdates` table (id, version, created, modified) tracks the highest applied migration version.
- `sql/db-updates.sql` is one file, chunks separated by `/* UPDATE */`, each chunk starting with a `/* VERSION n */` comment. Versions must be strictly increasing, no duplicates/gaps checked beyond ordering.
- A chunk can pull in another file via `/*include filename.sql*/`, resolved relative to `sql/`.
- Each migration runs in its own transaction; on failure the whole update run stops via `pre_die()`.
- **Special case**: if version 1 fails while the DB is at version 0, `update.php` assumes the DB was never initialized and falls back to running `sql/create-tables.sql` directly, then tells the user to refresh to continue the normal migration chain. If create-tables.sql *also* fails, both the original v1 error and the create-tables error are shown together (don't lose the original error — it's often the real diagnostic signal).
- Debug/error output goes through `pre_echo()` (info, keeps going) and `pre_die()` (fatal, always ends the script) — both wrap output in styled `<pre>` blocks. Keep using these rather than plain `echo`/`die` for consistency in this file.

## Gotchas

- PDO error mode is toggled explicitly per-use (`ERRMODE_EXCEPTION` vs `ERRMODE_SILENT`) in different places — don't assume a global default.
- `db::getPDO()` is a singleton per-request (static `$pdo`), backed by config from `Config::get()`.
