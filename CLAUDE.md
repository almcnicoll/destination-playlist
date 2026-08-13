# Destination Playlist

Spotify-integrated collaborative playlist app. PHP, no framework, custom PDO-based ORM. Not CakePHP or any
other framework — there's no composer.json, no vendor directory, nothing framework-shaped anywhere in the
repo. Local dev PHP (Laragon CLI) is 8.3.30.

## Structure

- `class/` — model classes (autoloaded, no namespaces). `Model.php` is the base class all models extend.
  Also holds a few non-model helpers: `db.php` (PDO singleton), `Config.php`, `PageInfo.php`,
  `SpotifyRequest.php`, `LoggedError.php`, a vendored `PHPMailer/` and `diff_match_patch.php` (used by
  `pages/playlist_edit.php` to diff the old/new "destination" letter string when it's edited).
- `pages/` — page controllers/views, included by `index.php` (router-style front controller).
- `ajax/` — standalone endpoints hit directly via XHR (assign_letters, assign_track, get_devices,
  proxy_search, admin_get_users, etc. — Spotify device/track/search actions plus the admin CRUD endpoints).
- `inc/` — `header.php`, `config.local.php` (gitignored, real config), `secret.php` (gitignored, credentials).
- `sql/create-tables.sql` — the **base schema as of before any `db-updates.sql` migrations were introduced**
  (version 0). It does NOT reflect the current full schema — e.g. no `letters.rank` column, no `faqs` table
  (added by later migrations).
- `sql/db-updates.sql` — versioned migrations, applied in order by `update.php`.
- `update.php` — run manually (browser or CLI) to bring the DB schema up to date.

## Routing (`index.php`)

Front controller with no real router: `/foo/bar/baz/qux` → `page_parts = [foo,bar,baz,qux]` →
first two parts joined with `_` become the stub (`foo_bar`), everything past the first two is shifted into
`$params` (in order) and handed to the page. So `/admin/users` → `pages/admin_users.php`, `$params = []`;
`/playlist/manage/5` → `pages/playlist_manage.php`, `$params = ['5']`. A single URL part (e.g. `/admin`) is
treated as `/admin/index`. Unknown pages 404 before touching auth/session, on purpose — don't move that
check.

Each page has a `PageInfo` (from `Config::init()`'s `pageinfo` array, keyed by stub; unlisted pages default
to `AUTH_EARLY` + redirect-to-login-on-fail). `AUTH_EARLY` authenticates before the page body runs,
`AUTH_LATE` after, `AUTH_NEVER` skips it (used for public pages like `dp_intro`, `dp_faq`,
`account_request`, `privacy_policy`).

## Authentication & admin

- Session-based. `User::loginCheck()` checks for `$_SESSION['USER']`/`USER_ID`/`USER_AUTHMETHOD_ID` plus
  either an access token or a refresh token + refresh time; redirects to the auth method's `handler` page
  (e.g. `login-spotify.php`) if a refresh is due.
- **Admin is a single hardcoded account, not a role**: `User::isAdmin()` just checks
  `$this->identifier === 'almcnicoll'`. Used by `pages/admin_dashboard.php`, `pages/admin_users.php`, the
  three `ajax/admin_*.php` endpoints, and the nav link in `inc/header.php`. If real roles ever get added,
  all of those call sites need updating.
- `dev-login.php` is a loopback-gated (127.0.0.1/::1 only) backdoor that logs straight in as the real
  `almcnicoll` user with a dummy access token (`DEV-BACKDOOR-NO-REAL-TOKEN`) — no Spotify OAuth round trip.
  Useful for local testing, but **any code path that hits the real Spotify API will fail with 401** when
  testing this way (track search, `pushTracksToSpotify()`, etc.) — that's expected, not a bug.

## Database connection (`class/db.php`)

`db::getPDO()` is a singleton per-request, and — importantly — sets `PDO::ATTR_EMULATE_PREPARES => false`
(real server-side prepared statements). **This means you cannot reuse the same named placeholder more than
once in a single query** (e.g. `WHERE a LIKE :x OR b LIKE :x` throws "Invalid parameter number: mixed named
and positional parameters" / "invalid parameter number" at execute time) — give each occurrence its own
name and bind the same value to both. This bit `ajax/admin_get_users.php`'s search clause during
development; watch for it in any new raw SQL with a repeated condition.

PDO error mode is otherwise toggled explicitly per-use (`ERRMODE_EXCEPTION` vs `ERRMODE_SILENT`) in
different places — don't assume a global default beyond what `db.php` sets.

## Autoloading

`autoload.php` registers a spl_autoloader mapping class name → `class/ClassName.php`, walking up to 3 parent
directories to find it (so it works from `pages/`, `ajax/`, or root). No Composer, no PSR-4.

## Model base class (`class/Model.php`)

Every model declares `static string $tableName` and `static $fields` (column list, excluding `id`). Base
class provides `getAll()`, `getById()`, `find($criteria)` (array of `[field, operator, value]` triples,
operators limited to an allowlist), `save()` (insert-or-update based on whether `id` exists), `delete()`.
`created`/`modified` are auto-stamped on save. Typed properties with no default (e.g. `Letter::$user_id`,
`Playlist::$spotify_playlist_id`) must be explicitly set (even to `null`) before `save()` — PHP throws
"must not be accessed before initialization" otherwise, since `save()` reads every field in `$fields`.

Raw SQL via `db::getPDO()` is used liberally alongside the ORM wherever `find()`'s simple AND-only,
no-JOIN/no-LIMIT criteria don't fit (letter assignment, admin user search/sort/paginate, the Spotify-push
staleness check, etc.) — this is normal and expected in this codebase, not a smell.

## Migration system (`update.php` + `DbUpdate.php`)

- `dbupdates` table (id, version, created, modified) tracks the highest applied migration version.
- `sql/db-updates.sql` is one file, chunks separated by `/* UPDATE */`, each chunk starting with a
  `/* VERSION n */` comment. Versions must be strictly increasing, no duplicates/gaps checked beyond
  ordering.
- A chunk can pull in another file via `/*include filename.sql*/`, resolved relative to `sql/`.
- Each migration runs in its own transaction; on failure the whole update run stops via `pre_die()`.
- **Special case**: if version 1 fails while the DB is at version 0, `update.php` assumes the DB was never
  initialized and falls back to running `sql/create-tables.sql` directly, then tells the user to refresh to
  continue the normal migration chain. If create-tables.sql _also_ fails, both the original v1 error and the
  create-tables error are shown together (don't lose the original error — it's often the real diagnostic
  signal).
- Debug/error output goes through `pre_echo()` (info, keeps going) and `pre_die()` (fatal, always ends the
  script) — both wrap output in styled `<pre>` blocks. Keep using these rather than plain `echo`/`die` for
  consistency in this file.
- New tables created in a migration consistently follow one shape (see `faqs`, `errors`, `model` at
  versions 5/8 in `sql/db-updates.sql`): `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `created`/`modified` DATETIME DEFAULT NULL, `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci`. Follow this for any new table.
- Raw `INSERT`s written directly in a migration (as opposed to going through a model's `save()`) must set
  `created`/`modified` explicitly (typically `NOW()`) — migrations bypass `Model.php`'s auto-stamping
  entirely, since that only happens inside `save()`.

## Frontend conventions

No build step, no bundler — jQuery 3.7 and Bootstrap 5.3 are loaded from CDN in `index.php`, Bootstrap
Icons via a CSS `@import` in `css/app.css`. Every interactive feature is a plain JS object namespace,
declared defensively (`if (typeof x === 'undefined') { x = {}; }`) and wired up with an explicit `.init()`
call from the consuming page's own inline `<script>` block — see `letterGetter`/`peopleGetter`
(`js/letter_refresh.js`/`people_refresh.js`), `trackSearch` (`js/search_mgmt.js`), `letterAssigner`
(`js/letter_assign.js`), `listLocker` (`js/playlist_lock.js`), `playHandler` (`js/play_handler.js`),
`adminUsers` (`js/admin_users.js`).

Shared/reusable JS files support **page-specific customization via optional "Custom" hooks**: the shared
file checks `if ('xCustom' in obj) { obj.xCustom(...) }` before falling back to default behaviour. E.g.
`letterGetter.updateLettersCustom` is defined differently in `playlist_manage.php` vs `playlist_join.php`
even though both load the same `letter_refresh.js`. When adding a new page that reuses one of these
components, follow this pattern rather than editing the shared file's default behaviour.

Nearly all user-controlled strings get interpolated straight into HTML client-side with no escaping
(`cached_title`, `display_name`, etc.) — a known, longstanding, codebase-wide gap, not something to silently
"fix" as a drive-by in unrelated work. The one deliberate exception is `js/admin_users.js`
(`adminUsers.escapeHtml`), because that table is the highest-privilege surface in the app (rendered only in
an admin's session) — keep escaping there, and consider it before copying that file's row-building pattern
elsewhere.

**Cache-busting**: there's no build step to fingerprint assets, so every local `<script src='.../js/*.js'>`
tag _and_ `css/app.css`'s `<link>` tag are manually suffixed with a bare query string —
`.../js/foo.js?<?= $config['asset_version'] ?>` — where `asset_version` lives in `class/Config.php`, in the
"non-local, non-secret config" block near the top of `Config::init()`.
**Whenever you edit any file under `js/`, or `css/app.css`, bump `asset_version`.** A plain increment
(`'1'` → `'2'`) is enough; what matters is that the value changes so the URL changes. Skipping this is easy
to miss and hard to diagnose from the server side, since the server always serves the current file
correctly — it's purely the requesting browser's cache that goes stale, and mobile browsers in particular
can hold onto a cached script or stylesheet well past what a normal (even a forced) refresh clears. This is
exactly what caused the `#toggle-my-letters` "my letters only" checkbox to look broken on mobile at one
point: the JS was toggling the `.my-letters-only` class correctly, but a phone with an `app.css` cached from
before that CSS rule existed would show no visible effect at all — not a JS bug, not a caching-can't-explain-it
dead end, just an untouched stylesheet URL that had never had a reason to change before. If you add a _new_
`js/*.js` file and wire it into a page, give its `<script>` tag the same
`?<?= $config['asset_version'] ?>` suffix as all the others.

## The playlist-manage page (`pages/playlist_manage.php`)

The largest/most complex page — a live collaborative view that multiple people can have open at once while
building a playlist together. Notable patterns worth reusing if extending it further:

- **Polling with hash short-circuiting**: `letterGetter`/`peopleGetter` poll `ajax/get_letters.php` /
  `ajax/get_participants.php` on a timer; each response includes a `hash` of the result, and the poller
  skips re-rendering entirely if the hash matches the last one seen.
- **Row-keyed DOM patching, not full-table rebuilds**: rows are `<tr data-letter-id="…">` /
  `<tr data-user-id="…">`, and refreshes update/insert/remove individual rows by that key instead of wiping
  and rebuilding `<tbody>`. This preserves scroll position and avoids visible flicker on every poll tick.
  Full-list-driven patches (`removeStale=true`) also remove rows no longer in the list and re-sync order;
  patches driven by a single action's response (`removeStale=false`) only ever touch the row(s) named in
  that response.
- **Actions patch directly from their own response**: `assign_track.php`, `clear_track.php`,
  `unassign_letter.php`, `assign_letters.php` and `kick_participant.php` all return the row(s) they changed
  (same shape as the list endpoints), so the client patches immediately instead of waiting on the next poll
  or forcing an extra round-trip.
- **Per-row pending indicators**: clicking a per-row action swaps that row's icon for a small
  `spinner-border spinner-border-sm` and reverts it on failure, rather than locking the whole page with a
  wait cursor. The whole-list actions (Assign/Reassign letters, lock/unlock) still use the page-wide wait
  cursor, since those are the odd operations that legitimately affect the entire view.
- **Owner-only Spotify push**: collaborative playlists can't be written to via the Spotify API by
  non-owners (see the comment at the top of `assign_track.php`), so only the playlist owner's browser ever
  actually calls `Playlist::pushTracksToSpotify()`. Non-owner changes get pushed the next time the _owner's_
  client polls `get_letters.php`, gated by a `$_SESSION['last_updates_check']` checkpoint (only advances on
  a successful push, so a failed push retries the same window next poll).
  `pushTracksToSpotify()` also takes a MySQL `GET_LOCK` so overlapping pushes (e.g. an action's own push
  racing a poll's push) no-op instead of double-submitting.
- **`letters.user_id` has no foreign key** (see `sql/create-tables.sql`) — deleting a user does _not_
  cascade-null letters they hold in other people's playlists; that has to be done manually (see
  `ajax/admin_delete_user.php` and the self-delete flow in `pages/account_manage.php`). Playlists a user
  owns, and that user's own `participations` rows, _do_ cascade via real FKs.

## Letter assignment algorithm (`ajax/assign_letters.php`)

Assignment/reassignment is one set-based SQL query rather than an iterative loop: it cross-joins a static
`integers` table (1–100, from `sql/db-updates.sql` version 10) against a per-user letter count, on
`letter_count < number` (**must be strict `<`, not `<=`** — the `integers` table starts at 1, not 0, so
`<=` would make users with 0 and 1 letters tie for the very first slot instead of the 0-letter user being
served first). Each "number" tier effectively means "give one more letter to everyone still below this
level," which — taken in ascending tier order with `ORDER BY number, RAND()` — reproduces "raise the lowest
users first, then randomize among ties" as a single query, without needing to recompute counts between
assignments. This is intentional and non-obvious; don't "simplify" it back to `<=` or a naive round-robin
loop without re-deriving why it currently works.

## Local dev environment

- Laragon on Windows; local root is `http://127.0.0.1/destination-playlist` (see
  `inc/config.local.php`'s `root_path`).
- Git remotes: `origin` (GitHub, `almcnicoll/destination-playlist` — this is the one that works and should
  be pushed to) and `deploy` (`destinationplaylist.rocks` Plesk git). As of 2026-08-09, `deploy` returns a
  plain 404 _from the destination-playlist app's own router_, not from a git server — the Plesk git
  deployment endpoint at that URL isn't reachable/configured as the remote expects. Don't assume `deploy`
  works; flag it rather than silently pushing there.
- This shell environment has no cached GitHub credentials by default — `git push origin main` may hang
  waiting on a credential prompt (use `GIT_TERMINAL_PROMPT=0` with a short timeout to fail fast instead of
  hanging, and ask the user to push if it fails).
- **Production deploys via a webhook, not the `deploy` remote above.** After a successful push to
  `origin main` specifically (not other branches, not other remotes), check whether `inc/deploy-webhook.txt`
  exists. It's gitignored and not part of a fresh checkout — it holds a Plesk Git webhook URL, which is
  effectively a bearer credential, so it's never committed. If present, `POST` (not GET — GET returns a
  plain 404 from Plesk's own webhook handler, `sw-cp-server`; POST returns `204 No Content`, confirmed by
  hand on 2026-08-13) to the URL it contains right after the push succeeds, to tell Plesk to pull and deploy
  the latest `main` to production: `curl -s -X POST "$(cat inc/deploy-webhook.txt)"`. On Windows, curl's
  Schannel TLS backend may fail this specific host with `SEC_E_WRONG_PRINCIPAL` even though the server and
  URL are entirely legitimate (confirmed by hand — `-k` gets a normal `204`) — this looks like Plesk's panel
  component presenting a certificate that doesn't cleanly match this hostname via SNI, not anything wrong
  with the webhook. Falling back to `curl -sk` for just this request is fine here (diagnose first if the
  failure looks like anything other than `SEC_E_WRONG_PRINCIPAL`, rather than reaching for `-k` by default).
  If the file isn't there and we're in interactive mode then prompt the user to supply the URL and save it to
  the correct file. If the file isn't there and we're in auto/unattended mode, just skip this step — don't create one, and
  don't treat its absence as an error - but do invite the user to create the file with the webhook URL in any
  end-of-workload summary. This webhook is the thing that actually works for deployment; the
  `deploy` remote is still broken as of the note above, so don't fall back to pushing there instead.

## Gotchas

- PDO error mode is toggled explicitly per-use (`ERRMODE_EXCEPTION` vs `ERRMODE_SILENT`) in different
  places — don't assume a global default.
- `db::getPDO()` is a singleton per-request (static `$pdo`), backed by config from `Config::get()`.
- Named placeholders can't repeat within one query (see "Database connection" above) — a real, already-hit
  footgun, not a theoretical one.
- `letters.user_id` has no FK — see "The playlist-manage page" above.
- The `assign_letters.php` join needs strict `<`, not `<=` — see "Letter assignment algorithm" above.
