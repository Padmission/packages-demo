# Filament 5 Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate this Laravel 12 + Filament 4.8 demo app to Filament 5.x, including all dependent plugins (data-lens, custom-dashboards, spatie-* plugins, lara-zeus translatable) and the underlying Livewire v3 → v4 migration; then optionally wire up an in-panel AI agent via Filament Copilot.

**Architecture:** Filament 5 is a "compatibility" major — its one substantive change is requiring Livewire v4 underneath. The official `filament-v5` script rewrites the bulk of namespace and method-signature differences automatically; what remains is plugin version bumps, Livewire v4 manual fixes (wire:model child events, transition modifiers, component self-closing tags, config rekey), and a manual QA pass. The work is sequenced in phases so each phase produces a runnable, committable checkpoint.

**Tech Stack:** PHP 8.4, Laravel 12.58, Filament 4.8 → 5.x, Livewire 3.6 → 4.x, Tailwind 4.x (already compliant), padmission/data-lens 2.4 → 3.1, filament/custom-dashboards-plugin 1.1-beta → 1.4-beta, lara-zeus/spatie-translatable 1.0 → 2.0, filament/spatie-* plugins 4 → 5.

**Plugin compatibility matrix (verified from packagist 2026-05-11):**

| Package | Current | F5 target | F5 requires |
|---|---|---|---|
| `filament/filament` | 4.8.4 | `^5.0` | livewire ^4.0, php 8.2+, laravel 11.28+ |
| `filament/spatie-laravel-media-library-plugin` | 4.11.3 | `^5.0` | filament ^5.0 |
| `filament/spatie-laravel-settings-plugin` | 4.8.4 | `^5.0` | filament ^5.0 |
| `filament/spatie-laravel-tags-plugin` | 4.8.4 | `^5.0` | filament ^5.0 |
| `filament/custom-dashboards-plugin` | 1.1.0-beta1 | `^1.4.1-beta1` | filament ^4.3.1\|^5.0 |
| `lara-zeus/spatie-translatable` | 1.0.4 | `^2.0` | filament ^5.0 |
| `padmission/data-lens` | 2.4.4 | `^3.1` | filament ^5.0, php 8.3+ |

All required versions exist. No plugin is a blocker.

---

## Phase 0 — Safety Net

Capture the baseline state and create the upgrade branch. Everything in this phase is reversible and creates the artifacts we use to verify the upgrade later.

### Task 0.1: Branch and clean working tree

**Files:**
- Modify: working git state on `main`

- [ ] **Step 1: Verify clean tree on main**

```bash
git status
git branch --show-current
```

Expected: `On branch main`, no modified tracked files. (The `.context/` untracked files are fine — they're gitignored agent notes.)

- [ ] **Step 2: Create upgrade branch from main**

```bash
git checkout -b chore/filament-5-upgrade
git branch --show-current
```

Expected: `chore/filament-5-upgrade`.

- [ ] **Step 3: Confirm the local data-lens working copy is on a 3.x ref**

```bash
git -C ../data-lens log --oneline -1
git -C ../data-lens branch --show-current
```

Expected: branch `3.x`, HEAD at `v3.1.10` or newer. If it isn't, run `git -C ../data-lens checkout 3.x && git -C ../data-lens pull --ff-only` before continuing. The demo pulls the published package, not this path, so this is informational only.

### Task 0.2: Capture baseline smoke evidence

**Files:**
- Create: `docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/` (screenshots)

- [ ] **Step 1: Boot the app and confirm it loads**

```bash
php artisan about | head -5
php artisan route:list --path=admin | head -5
php artisan route:list --path=app | head -5
```

Expected: Laravel 12.58.x, both `admin/login` and tenant routes present.

- [ ] **Step 2: Build production assets to confirm baseline build works**

```bash
npm run build
```

Expected: `built in N.NNs`, no errors. Note the output bundle hashes — we'll compare these post-upgrade.

- [ ] **Step 3: Capture baseline screenshots of every panel page**

```bash
mkdir -p docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline
php artisan serve --port=8000 &
sleep 3
agent-browser open http://localhost:8000/admin/login
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/01-admin-login.png
# Authenticate using the demo seeded admin user
agent-browser snapshot -i
# After login (use seeded credentials from DemoSeeder or .env):
agent-browser open http://localhost:8000/admin
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/02-admin-dashboard.png
agent-browser open http://localhost:8000/admin/shop/products
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/03-admin-products.png
agent-browser open http://localhost:8000/admin/shop/orders
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/04-admin-orders.png
agent-browser open http://localhost:8000/admin/blog/posts
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/05-admin-posts.png
# Tenant panel — pick any seeded tenant slug
agent-browser open http://localhost:8000/app
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline/06-tenant-dashboard.png
kill %1
```

Expected: 6 PNGs saved. These are the visual baseline for the post-upgrade comparison in Phase 7.

- [ ] **Step 4: Commit the baseline artifacts**

```bash
git add docs/superpowers/plans/2026-05-11-filament-5-upgrade-baseline
git commit -m "chore: capture pre-upgrade Filament 4 baseline screenshots"
```

### Task 0.3: Pin the Livewire v3 starting version in the lock

**Files:**
- Inspect: `composer.lock`

- [ ] **Step 1: Note the currently-installed Livewire version**

```bash
composer show livewire/livewire | grep -E "^versions"
```

Record the version (expected: `3.6.x` since Filament 4 requires Livewire ^3.0). The Phase 1 upgrade pulls Livewire 4 — knowing the baseline lets you reason about diffs.

---

## Phase 1 — Run the Automated Filament v5 Upgrader

This phase uses the official `filament-v5` Rector-style script. It rewrites namespaces, method names, and signature changes across `app/`, `database/`, `config/`, `resources/`, and `tests/`. We run it on a dedicated commit so a `git revert` reverses the whole batch if needed.

### Task 1.1: Install and run the upgrader

**Files:**
- Modify: `composer.json` (require-dev addition)
- Modify: all PHP files under `app/`, `database/`, `config/`, `tests/`, `resources/views/`
- Modify: `composer.lock`

- [ ] **Step 1: Install the upgrader as a dev dependency**

```bash
composer require filament/upgrade:"^5.0" -W --dev
```

Expected: `filament/upgrade` added to `require-dev`. Some intermediate dependencies may downgrade — that's fine, this dep is removed at the end of the phase.

- [ ] **Step 2: Run the automated upgrade script**

```bash
vendor/bin/filament-v5
```

Expected: the script prints a list of files rewritten, optionally proposes composer commands for plugin bumps, and exits 0. Read all output — note any "manual change required" lines into a scratch file for Phase 3.

- [ ] **Step 3: Commit the automated rewrites alone, before bumping Filament itself**

```bash
git status
git add -A
git commit -m "chore: apply filament-v5 automated upgrade rewrites"
```

The diff here should be confined to namespace/method renames inside `app/Filament/`, `app/Livewire/`, `app/Providers/Filament/`, possibly `tests/`, and view files. If the diff touches anything in `vendor/`, stop — the working tree was dirty. Reset and re-run.

### Task 1.2: Bump Filament and resolve all-dependencies update

**Files:**
- Modify: `composer.json` (Filament + plugin constraints)
- Modify: `composer.lock`

- [ ] **Step 1: Bump Filament and the official Spatie plugins in composer.json**

Edit `composer.json` and change these five lines under `"require"`:

```json
"filament/filament": "^5.0",
"filament/spatie-laravel-media-library-plugin": "^5.0",
"filament/spatie-laravel-settings-plugin": "^5.0",
"filament/spatie-laravel-tags-plugin": "^5.0",
"filament/custom-dashboards-plugin": "^1.4.1-beta1",
```

Leave `padmission/data-lens` and `lara-zeus/spatie-translatable` for the next task.

- [ ] **Step 2: Bump the third-party Filament plugins**

In the same file, change:

```json
"padmission/data-lens": "^3.1",
"lara-zeus/spatie-translatable": "^2.0",
```

- [ ] **Step 3: Run the unified composer update with `--with-all-dependencies`**

```bash
composer update --with-all-dependencies 2>&1 | tee /tmp/filament-5-composer.log
```

Expected: composer resolves to Filament 5.x, Livewire 4.x, data-lens 3.x, custom-dashboards 1.4.x-beta, lara-zeus 2.x, and the spatie plugins to 5.x. If composer reports a security advisory (we saw `PKSA-5bdf-2x61-v43c` blocked an earlier partial update), re-read the message — for an end-of-line v4 advisory it should disappear once v5 supersedes the affected version.

- [ ] **Step 4: If composer fails on a security advisory for a v4 package we're leaving behind, set audit to non-blocking for that one advisory only**

```bash
composer config audit.ignore.PKSA-5bdf-2x61-v43c "Removed by Filament v5 upgrade"
composer update --with-all-dependencies
```

(Only do this if Step 3 fails with that specific advisory and v5 is in the resolved set. The ignore is scoped to this repo's `composer.json` — review the diff before committing.)

- [ ] **Step 5: Remove the upgrader dev dependency**

```bash
composer remove filament/upgrade --dev
```

Expected: clean removal, no further composer.json changes besides the removed line.

- [ ] **Step 6: Verify installed versions**

```bash
composer show filament/filament livewire/livewire padmission/data-lens lara-zeus/spatie-translatable filament/custom-dashboards-plugin | grep -E "^(name|versions)"
```

Expected output (versions are minimums; latest patch is fine):
- `filament/filament` → 5.x
- `livewire/livewire` → 4.x
- `padmission/data-lens` → 3.1.x
- `lara-zeus/spatie-translatable` → 2.0.x
- `filament/custom-dashboards-plugin` → 1.4.x-beta

- [ ] **Step 7: Commit the dependency bump**

```bash
git add composer.json composer.lock
git commit -m "chore: bump filament to v5 and all dependent plugins"
```

### Task 1.3: Run `filament:upgrade` and re-publish assets

**Files:**
- Modify: `public/build/`, `public/vendor/`, `public/css/`, `public/js/` (published asset diffs)

- [ ] **Step 1: Run Filament's post-install upgrade hook**

```bash
php artisan filament:upgrade
```

Expected: assets republished, caches cleared, "Successfully upgraded!".

- [ ] **Step 2: Run the asset build for the dev panel theme**

```bash
npm run build
```

Expected: vite finishes; `public/build/manifest.json` updated. The two `theme.css` outputs (admin + app panels) should both build.

- [ ] **Step 3: Commit the rebuilt public assets**

```bash
git add public/
git commit -m "chore: rebuild filament 5 public assets"
```

---

## Phase 2 — Plugin-Specific Fixups

The automated script handles Filament core renames. Each plugin has its own version-2/3 migration notes. This phase walks through each plugin we use.

### Task 2.1: data-lens v2 → v3 migration

`padmission/data-lens` 3.x introduces a Filament-5-only API. The package has its own upgrade notes in its repo, but the key changes that affect this demo are the four custom-dashboards widgets we register in `AppPanelProvider`.

**Files:**
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Inspect: `config/data-lens.php`
- Inspect: `app/Filament/Widgets/DataSources/*.php`

- [ ] **Step 1: Diff data-lens widget signatures between 2.4.4 and 3.1.10**

```bash
diff <(git -C ../data-lens show v2.4.4:src/Widgets/CustomDashboards/DataLensReportTableWidget.php) \
     <(git -C ../data-lens show v3.1.10:src/Widgets/CustomDashboards/DataLensReportTableWidget.php) \
  | head -60
```

Expected: closure-return-type and signature-style edits only. If the public API surface (class name, public methods called from `AppPanelProvider`) is unchanged, nothing in `AppPanelProvider` needs editing.

- [ ] **Step 2: Repeat the diff for the other three widgets**

```bash
for w in DataLensChartWidget DataLensStatsWidget DataLensSummaryTableWidget; do
  echo "=== $w ==="
  diff <(git -C ../data-lens show v2.4.4:src/Widgets/CustomDashboards/$w.php 2>/dev/null) \
       <(git -C ../data-lens show v3.1.10:src/Widgets/CustomDashboards/$w.php 2>/dev/null) \
    | head -30
done
```

Expected: similar internal-only diffs. If any widget's public method (e.g. `mount`, `getColumnSpan`, `table`) changes signature, note it.

- [ ] **Step 3: Inspect the 10 `app/Filament/Widgets/DataSources/*.php` files for v3 API drift**

```bash
grep -rn "use Padmission\\\\DataLens" app/Filament/Widgets/DataSources/ | head -30
grep -rn "extends \\\\?" app/Filament/Widgets/DataSources/ | head -30
```

If any DataSource extends a class whose namespace moved in v3, fix the use statement. To find moved classes:

```bash
diff <(cd ../data-lens && git ls-tree -r --name-only v2.4.4 -- src/) \
     <(cd ../data-lens && git ls-tree -r --name-only v3.1.10 -- src/) \
  | grep -E "^[<>]" | head -40
```

Apply the rename to each affected DataSource. If no use statements moved, no edit is needed.

- [ ] **Step 4: Run the data-lens published config diff**

```bash
diff config/data-lens.php <(php artisan vendor:publish --provider="Padmission\DataLens\DataLensServiceProvider" --tag=data-lens-config --force --pretend 2>/dev/null && cat vendor/padmission/data-lens/config/data-lens.php)
```

If the upstream config grew new keys, copy them into `config/data-lens.php` preserving any local overrides (`tenant_aware`, model bindings).

- [ ] **Step 5: Commit data-lens fixups**

```bash
git add app/ config/ 2>/dev/null || true
git diff --cached --quiet || git commit -m "chore: align data-lens widget usage for v3"
```

(The conditional commit handles the case where Steps 2–4 produced no diff.)

### Task 2.2: custom-dashboards-plugin 1.1-beta → 1.4-beta migration

**Files:**
- Modify: `app/Providers/Filament/AppPanelProvider.php` (only if registration API changed)

- [ ] **Step 1: Re-verify the panel registration still parses**

```bash
php artisan filament:list-panels 2>&1 || php artisan about | grep -i filament
```

Expected: both panels (`admin`, `app`) load without TypeError. If `CustomDashboardsPlugin::make()->shareableModels([Team::class])->widgets([...])` errors, check the new plugin docs:

```bash
ls vendor/filament/custom-dashboards-plugin/docs 2>/dev/null
find vendor/filament/custom-dashboards-plugin -name "CHANGELOG*" -exec cat {} \;
```

- [ ] **Step 2: If the panel boots without errors, no edit is needed; commit nothing**

If it errors, apply the fix from the changelog and commit:

```bash
git add app/Providers/Filament/AppPanelProvider.php
git commit -m "fix: align custom-dashboards-plugin registration for v1.4"
```

### Task 2.3: lara-zeus/spatie-translatable v1 → v2 migration

**Files:**
- Inspect: `app/Filament/Resources/Blog/LinkResource.php`
- Inspect: `app/Filament/App/Resources/Blog/LinkResource.php`
- Inspect: every `LinkResource/Pages/*.php`

- [ ] **Step 1: Find every reference to the trait**

```bash
grep -rn "LaraZeus\\\\SpatieTranslatable" app/
```

Expected: trait imports in `LinkResource.php` (admin + app) and possibly in their Page classes.

- [ ] **Step 2: Check the v2 trait name and method shape**

```bash
ls vendor/lara-zeus/spatie-translatable/src/
grep -rn "trait " vendor/lara-zeus/spatie-translatable/src/ | head
```

If v2 renamed the trait or changed the static method (`getResourcePages()` override, locale switcher action), update the imports in each affected file.

- [ ] **Step 3: Verify the published spatie locales config still loads**

```bash
php -r "print_r(config('app.locale'));"
```

Expected: returns `en` (or whatever you have). If the plugin moved the locale config, follow the v2 README.

- [ ] **Step 4: Commit any translatable fixups (or skip if none)**

```bash
git diff --quiet app/ || { git add app/; git commit -m "chore: align lara-zeus translatable for v2"; }
```

### Task 2.4: filament/spatie-* plugins v4 → v5

**Files:**
- Inspect: every form/table file that uses `SpatieMediaLibraryFileUpload`, `SpatieTagsInput`, `SpatieMediaLibraryImageColumn`

- [ ] **Step 1: Locate every reference to spatie filament components**

```bash
grep -rn "SpatieMediaLibrary\|SpatieTags\|SpatieLaravelSettings" app/ | head -30
```

- [ ] **Step 2: Compare those component classes' v4 and v5 public APIs**

For each component class found in Step 1, run:

```bash
ls vendor/filament/spatie-laravel-media-library-plugin/src/
ls vendor/filament/spatie-laravel-tags-plugin/src/
ls vendor/filament/spatie-laravel-settings-plugin/src/
```

If any class moved namespace, update the `use` statement. The spatie plugin v4→v5 bumps mirror Filament's own namespace stability — most edits are zero.

- [ ] **Step 3: Run the panel-boot smoke check**

```bash
php artisan filament:cache-components
```

Expected: exits 0. A namespace error here lists the missing class.

- [ ] **Step 4: Commit any spatie plugin fixups**

```bash
git diff --quiet app/ || { git add app/; git commit -m "chore: align spatie filament plugins for v5"; }
```

---

## Phase 3 — Livewire v3 → v4 Manual Fixes

The Filament v5 script does not rewrite Livewire-v4-only behavioral changes. This phase covers the four documented v4 breaks that touch this codebase: config rekey, `wire:model` child-event change, transition modifier deprecations, and `<livewire:>` self-closing requirement.

### Task 3.1: Update `config/livewire.php` keys

**Files:**
- Modify: `config/livewire.php` (if present)

- [ ] **Step 1: Check whether the app overrides livewire config**

```bash
ls config/livewire.php 2>/dev/null && echo "present" || echo "absent"
```

If absent, skip this task entirely — Livewire reads its own defaults.

- [ ] **Step 2: If present, rekey `layout` → `component_layout`**

Open `config/livewire.php` and locate the `'layout'` key. Rename it to `'component_layout'`. Save.

- [ ] **Step 3: Diff against the upstream v4 default**

```bash
php artisan vendor:publish --tag=livewire:config --force
git diff config/livewire.php
```

Reconcile any locally-customized values into the new defaults. If you have no customizations, accept the upstream default verbatim.

- [ ] **Step 4: Commit**

```bash
git add config/livewire.php
git commit -m "chore: update livewire v4 config keys"
```

### Task 3.2: Audit `<livewire:>` tag usage for self-closing requirement

**Files:**
- Modify: any blade view using `<livewire:component-name>` without `/>` or matching close tag

- [ ] **Step 1: Find every `<livewire:` occurrence**

```bash
grep -rn "<livewire:" resources/views/ app/ vendor/livewire/livewire/stubs/ 2>/dev/null | grep -v "vendor/" | grep -v ".css" | head -40
```

- [ ] **Step 2: For each occurrence not already self-closed (`/>`) or paired (`</livewire:...>`), convert it**

Example transform:

```blade
{{-- before --}}
<livewire:notifications>

{{-- after --}}
<livewire:notifications />
```

Edit each affected blade file directly. There may be zero — `resources/views/livewire/form.blade.php` is the most likely candidate.

- [ ] **Step 3: Commit**

```bash
git diff --quiet resources/ app/ || { git add resources/ app/; git commit -m "chore: self-close livewire component tags for v4"; }
```

### Task 3.3: Audit `wire:model` for child-event reliance

Livewire v4 no longer fires `wire:model` updates from child element events by default. Filament's own form components handle this internally — the risk is only custom blade in our two Livewire components.

**Files:**
- Modify: `resources/views/livewire/form.blade.php` (if affected)
- Modify: `resources/views/livewire/notifications.blade.php` (if affected)

- [ ] **Step 1: Find every `wire:model` in the project's custom views**

```bash
grep -rn "wire:model" resources/views/ | head
```

- [ ] **Step 2: For each occurrence on an input whose update is driven by a child element's event (e.g. a wrapper div binding to an inner input), add the `.deep` modifier**

Example:

```blade
{{-- before --}}
<div wire:model="search">
    <input type="text">
</div>

{{-- after --}}
<div wire:model.deep="search">
    <input type="text">
</div>
```

Direct `wire:model` on the input itself does not need `.deep`. If the audit finds zero affected lines, skip the commit.

- [ ] **Step 3: Commit**

```bash
git diff --quiet resources/ || { git add resources/; git commit -m "fix: add wire:model.deep where v4 requires it"; }
```

### Task 3.4: Replace deprecated `wire:transition` modifiers

Livewire v4 deprecated `.opacity` and `.duration` modifiers on `wire:transition`.

**Files:**
- Modify: any blade view using `wire:transition.opacity` or `wire:transition.duration`

- [ ] **Step 1: Find every `wire:transition` modifier in the project**

```bash
grep -rEn "wire:transition\.(opacity|duration)" resources/views/ app/ | head
```

- [ ] **Step 2: For each occurrence, replace with the v4 equivalent**

Per the Livewire v4 upgrade guide, replace with the equivalent Alpine `x-transition` modifier on the same element:

```blade
{{-- before --}}
<div wire:transition.opacity.duration.500ms>

{{-- after --}}
<div wire:transition x-transition:enter="transition duration-500" x-transition:leave="transition duration-500">
```

If no matches, skip the commit.

- [ ] **Step 3: Commit**

```bash
git diff --quiet resources/ app/ || { git add resources/ app/; git commit -m "fix: replace deprecated wire:transition modifiers for v4"; }
```

### Task 3.5: Smoke-boot both panels after Livewire fixes

**Files:** none modified, evidence only

- [ ] **Step 1: Clear caches and re-discover**

```bash
php artisan optimize:clear
php artisan filament:cache-components
```

Expected: clean exit, both panels' components cached without TypeError.

- [ ] **Step 2: Boot the dev server and hit each panel root**

```bash
php artisan serve --port=8000 &
sleep 3
curl -sI http://localhost:8000/admin/login | head -1
curl -sI http://localhost:8000/app | head -1
kill %1
```

Expected: `HTTP/1.1 200 OK` for admin login, `HTTP/1.1 302 Found` (redirect to login) for tenant panel.

---

## Phase 4 — Theme and Tailwind Verification

Filament v5 requires Tailwind 4. We're already on 4.1.x, so the verification is purely that the panel themes still compile and look right.

### Task 4.1: Rebuild and visually compare both themes

**Files:**
- Inspect: `resources/css/filament/admin/theme.css`
- Inspect: `resources/css/filament/app/theme.css`

- [ ] **Step 1: Force a clean asset build**

```bash
rm -rf public/build
npm run build
```

Expected: vite emits two `theme-*.css` files and one `app-*.css` file, all under 1 MB raw.

- [ ] **Step 2: Open both panels' login pages and screenshot**

```bash
mkdir -p docs/superpowers/plans/2026-05-11-filament-5-upgrade-after
php artisan serve --port=8000 &
sleep 3
agent-browser open http://localhost:8000/admin/login
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-after/01-admin-login.png
agent-browser open http://localhost:8000/app
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-after/06-tenant-login.png
kill %1
```

- [ ] **Step 3: Diff the two screenshots against baseline**

Open each baseline/after pair side-by-side. Acceptable diffs: typography hairline shifts, button radii adjustments (Filament 5 tweaks defaults). Unacceptable diffs: missing background, broken layout, white-on-white text. If broken, the `@source` directives in `theme.css` may need updating — Filament 5 vendor paths under `vendor/filament/` are stable but `vendor/filament/custom-dashboards-plugin/resources/css/index.css` may have moved.

- [ ] **Step 4: Commit either the rebuilt assets or any source CSS fixes**

```bash
git add public/build resources/css/
git commit -m "chore: rebuild themes for filament 5 / tailwind 4"
```

---

## Phase 5 — Manual QA Pass

The app has zero Filament-aware tests, so the verification is procedural. Each step exercises one panel area and confirms it loads, renders, and saves.

### Task 5.1: Authenticated walkthrough of the admin panel

**Files:** none modified, evidence only.

- [ ] **Step 1: Boot the dev server and log into the admin panel**

```bash
php artisan serve --port=8000 &
sleep 3
agent-browser open http://localhost:8000/admin/login
agent-browser snapshot -i
# Fill in seeded admin credentials from .env or DemoSeeder; use the @e refs returned
# by the snapshot to fill email/password and click submit.
```

- [ ] **Step 2: For each of the 9 admin resources, open list → create → edit → view → delete a synthetic record**

Resources (and the routes to hit):

```
/admin/blog/authors
/admin/blog/categories
/admin/blog/links
/admin/blog/posts
/admin/shop/customers
/admin/shop/orders
/admin/shop/products
/admin/shop/products/brands       (cluster)
/admin/shop/products/categories   (cluster)
```

For each: load list, click "New", fill required fields with sane defaults, save, edit, save, delete. Capture `agent-browser console` after each action to surface JS or Livewire errors. Note any failure into a scratch file `/tmp/f5-qa-failures.md`.

- [ ] **Step 3: Exercise the dashboard widgets**

```bash
agent-browser open http://localhost:8000/admin
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-after/admin-dashboard.png
agent-browser console
```

Expected: `CustomersChart`, `LatestOrders`, `OrdersChart`, `StatsOverviewWidget` all render; console has no errors.

- [ ] **Step 4: Exercise filters, exports, imports**

For each resource that has an Exporter (`Blog/AuthorExporter`, `Shop/BrandExporter`) or Importer (`Blog/CategoryImporter`, `Shop/CategoryImporter`), click the export/import action in the toolbar and confirm it produces a job notification.

```bash
php artisan queue:work --once
```

Expected: queued job runs, file is written under `storage/app/`.

### Task 5.2: Authenticated walkthrough of the tenant panel

**Files:** none modified, evidence only.

- [ ] **Step 1: Register or pick a tenant**

```bash
agent-browser open http://localhost:8000/app
agent-browser snapshot -i
# Follow the tenant registration flow or pick a seeded tenant
```

- [ ] **Step 2: For each of the 9 tenant resources (mirrors of admin), repeat the list→create→edit→delete cycle**

Tenant-resource routes follow the pattern `/app/{tenant}/...` — list them with:

```bash
php artisan route:list --path=app | grep -E "GET.+/(blog|shop|products)" | awk '{print $2}' | sort -u
```

- [ ] **Step 3: Exercise the Custom Dashboards flow**

```bash
agent-browser open http://localhost:8000/app/{tenant}/custom-dashboards
# Create a dashboard, add each of the four DataLens widgets, configure a report
# for each, save, view. This is the exact path that hit the v4 TypeError —
# confirm it now renders the table without error.
agent-browser console
```

Expected: no `TypeError` from `Table::records()`, all four widgets render.

- [ ] **Step 4: Document any failure to a scratch file and fix incrementally**

For each issue, capture: route, action, console error, stack trace from `storage/logs/laravel.log`. Each fix is a separate commit with a `fix:` prefix referencing the affected resource.

### Task 5.3: Static analysis pass

**Files:** none modified, evidence only.

- [ ] **Step 1: Run PHPStan**

```bash
composer test:phpstan 2>&1 | tee /tmp/phpstan-after.log
```

Expected: same or fewer issues than baseline. New errors must be triaged:
- Genuine type errors introduced by F5 signature shifts → fix in code.
- Stale baselines pointing to renamed namespaces → regenerate baseline:

```bash
composer test:phpstan -- --generate-baseline
```

- [ ] **Step 2: Run Pint**

```bash
composer cs -- --test
```

If formatting violations were introduced by the upgrader, apply them:

```bash
composer cs
git diff --quiet || { git add -A; git commit -m "style: pint fixes after filament 5 upgrade"; }
```

### Task 5.4: Final upgrade commit and merge prep

**Files:** none

- [ ] **Step 1: Confirm the upgrade branch is green**

```bash
git log --oneline main..HEAD
git status
composer test:phpstan
npm run build
```

- [ ] **Step 2: Squash-merge or PR the branch**

```bash
git push -u origin chore/filament-5-upgrade
gh pr create --base main --title "chore: upgrade to Filament 5" --body "$(cat <<'EOF'
## Summary
- Upgrades Filament 4.8 → 5.x and Livewire 3 → 4
- Bumps padmission/data-lens 2.4 → 3.1, lara-zeus/spatie-translatable 1 → 2, custom-dashboards-plugin 1.1-beta → 1.4-beta, filament/spatie-* plugins 4 → 5
- Captures baseline + after screenshots under docs/superpowers/plans/

## Test plan
- [ ] Both panels boot
- [ ] Each admin resource: list/create/edit/delete works
- [ ] Each tenant resource: list/create/edit/delete works
- [ ] Custom dashboards table widget no longer throws
- [ ] PHPStan baseline holds
- [ ] npm build green
EOF
)"
```

- [ ] **Step 3: Watch CI; if green, merge**

```bash
gh run list --branch chore/filament-5-upgrade -L 1
```

---

## Phase 6 — Optional: AI Agent Integration (Filament Copilot)

Filament 5 itself ships **no** built-in AI agent. The official Filament ecosystem has multiple community AI plugins; the most mature for v5 is `eslam-reda-div/filament-copilot`, built on the Laravel AI SDK with built-in tool execution, memory, audit logging, and 8 provider backends (OpenAI, Anthropic, Gemini, Groq, xAI, DeepSeek, Mistral, Ollama).

**Decide before starting Phase 6** whether to ship this. If yes, complete Phase 5 first and merge before opening this work — it's an independent feature, not part of the upgrade.

### Task 6.1: Install and configure the copilot package

**Files:**
- Modify: `composer.json` (new dependency)
- Modify: `config/filament-copilot.php` (published)
- Create: 7 new migrations under `database/migrations/`
- Modify: `.env.example` (new env vars)
- Modify: `app/Models/User.php` (trait)

- [ ] **Step 1: Branch off main (post-merge)**

```bash
git checkout main
git pull --ff-only
git checkout -b feat/filament-copilot
```

- [ ] **Step 2: Install the package**

```bash
composer require eslam-reda-div/filament-copilot
```

Expected: pulls `laravel-ai-sdk` >= 0.2.7 as a transitive dep.

- [ ] **Step 3: Run the interactive installer**

```bash
php artisan filament-copilot:install
```

Answer the prompts:
- Provider: pick the one you have an API key for. **For the Anthropic default, choose `anthropic`** since Claude Sonnet 4 / 4.6 are the strongest at tool-use and the demo's already a Claude-friendly codebase.
- Publish config: yes.
- Publish migrations: yes.
- Run migrations: yes.

- [ ] **Step 4: Add the API key to `.env.example` and `.env`**

Edit `.env.example` and add (without the actual secret):

```env
# Filament Copilot (AI assistant)
COPILOT_PROVIDER=anthropic
COPILOT_MODEL=claude-sonnet-4-6
ANTHROPIC_API_KEY=
```

Then set the real value in `.env` (do not commit).

- [ ] **Step 5: Add the `HasCopilotChat` trait to the User model**

Edit `app/Models/User.php` and add the trait:

```php
use EslamRedaDiv\FilamentCopilot\Concerns\HasCopilotChat;

class User extends Authenticatable
{
    use HasCopilotChat;
    // ...existing traits and code unchanged
}
```

Match the exact namespace from `vendor/eslam-reda-div/filament-copilot/src/Concerns/`. If the published trait lives in a different path, adjust.

- [ ] **Step 6: Commit the install scaffold**

```bash
git add composer.json composer.lock config/filament-copilot.php database/migrations app/Models/User.php .env.example
git commit -m "feat: install filament-copilot scaffold"
```

### Task 6.2: Register the plugin on the admin panel only

We register the plugin on the admin panel first — tenant panel can be a follow-on after we know the cost profile.

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

- [ ] **Step 1: Add the plugin to the admin panel provider**

Open `app/Providers/Filament/AdminPanelProvider.php`, add the import, and register the plugin in the `->plugins([...])` chain:

```php
use EslamRedaDiv\FilamentCopilot\FilamentCopilotPlugin;

// inside panel():
->plugin(FilamentCopilotPlugin::make())
```

- [ ] **Step 2: Boot the panel and verify the keyboard shortcut works**

```bash
php artisan serve --port=8000 &
sleep 3
agent-browser open http://localhost:8000/admin
agent-browser snapshot -i
# Open the copilot with Ctrl+Shift+K (or click the registered nav item)
agent-browser press_key "Control+Shift+k"
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-after/copilot-opened.png
kill %1
```

Expected: chat panel opens. Sending a message like "list the latest 5 orders" should trigger a built-in tool call.

- [ ] **Step 3: Commit**

```bash
git add app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: register filament-copilot plugin on admin panel"
```

### Task 6.3: Expose two domain resources to the agent as a starting point

The agent only sees resources/pages/widgets that opt in via the Copilot interfaces. We start by exposing `OrderResource` and `ProductResource` since those are the highest-value support targets for a demo.

**Files:**
- Modify: `app/Filament/Resources/Shop/OrderResource.php`
- Modify: `app/Filament/Clusters/Products/Resources/ProductResource.php`

- [ ] **Step 1: Add the Copilot resource interface to `OrderResource`**

Open `app/Filament/Resources/Shop/OrderResource.php`, add the import and implement the interface:

```php
use EslamRedaDiv\FilamentCopilot\Contracts\CopilotResource;

class OrderResource extends Resource implements CopilotResource
{
    // ...existing class body

    public static function copilotTools(): array
    {
        return [
            // Use the built-in templates from the package for the standard CRUD set.
            // Refer to vendor/eslam-reda-div/filament-copilot/src/Tools/Templates/
            // for the available templates.
        ];
    }
}
```

Inspect the templates dir for exact class names, then populate the array. Match the package's actual API — the import path and method signature shown here are based on the public docs; verify against the installed source before committing.

- [ ] **Step 2: Repeat for `ProductResource`**

Apply the same interface and method to `app/Filament/Clusters/Products/Resources/ProductResource.php`.

- [ ] **Step 3: Exercise the agent end-to-end**

```bash
php artisan serve --port=8000 &
sleep 3
agent-browser open http://localhost:8000/admin
# Open copilot, ask: "Find all orders over $500 placed in the last 7 days."
# The agent should call the Order search tool and return rows.
agent-browser screenshot docs/superpowers/plans/2026-05-11-filament-5-upgrade-after/copilot-orders-query.png
kill %1
```

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Resources/Shop/OrderResource.php app/Filament/Clusters/Products/Resources/ProductResource.php
git commit -m "feat: expose Order and Product resources to filament-copilot"
```

### Task 6.4: Enable safe defaults: rate limit, audit log, token budget

The package ships per-user hourly/daily caps and token-spend caps disabled by default. For a public demo we want them on.

**Files:**
- Modify: `config/filament-copilot.php`

- [ ] **Step 1: Open the published config and set conservative caps**

Edit `config/filament-copilot.php` and set (adjust to taste):

```php
'rate_limit' => [
    'enabled' => true,
    'per_user' => [
        'messages_per_hour' => 30,
        'messages_per_day' => 200,
    ],
],

'token_budget' => [
    'enabled' => true,
    'daily_usd' => 5.00,
    'monthly_usd' => 50.00,
    'warning_threshold' => 0.8,
],

'audit_log' => [
    'log_messages' => true,
    'log_tool_calls' => true,
    'log_record_access' => true,
],
```

- [ ] **Step 2: Test rate-limit triggering**

Lower `messages_per_hour` to `2`, send 3 messages, confirm the 3rd is rejected with a friendly error. Reset to 30 after.

- [ ] **Step 3: Commit**

```bash
git add config/filament-copilot.php
git commit -m "feat: enable copilot rate limits, audit log, token budget"
```

### Task 6.5: Open a PR

- [ ] **Step 1: Push and open PR**

```bash
git push -u origin feat/filament-copilot
gh pr create --base main --title "feat: add AI assistant via Filament Copilot" --body "$(cat <<'EOF'
## Summary
- Installs eslam-reda-div/filament-copilot
- Registers the plugin on the admin panel only
- Exposes OrderResource and ProductResource to the agent
- Enables rate limits, audit logging, and a $5/day token cap

## Test plan
- [ ] Ctrl+Shift+K opens the copilot from any admin page
- [ ] "Find all orders over $500 in the last 7 days" returns rows
- [ ] Rate limit kicks in after the configured threshold
- [ ] Audit log records each interaction
EOF
)"
```

---

## Self-Review

**Spec coverage:**
- "update this app to filament 5" → Phases 0–5 cover branch, baseline, upgrader, plugin bumps, Livewire v4 fixes, theme rebuild, manual QA, and merge.
- "check filament skills, docs for using ai agent as well" → Phase 6 covers AI agent via Filament Copilot, scoped as an optional follow-on after the upgrade lands.

**Placeholder scan:**
- Each step shows the exact command or the exact code transform.
- The few "if no diff, skip the commit" branches are explicit guard clauses, not TODOs.
- Task 6.3 Step 1 instructs the executor to inspect the installed package source rather than hard-coding tool class names — this is deliberate because the package source is the authoritative API surface and may have drifted from the public docs by the time this plan is executed.

**Type/method consistency:**
- `padmission/data-lens` referred to as ^3.1 (resolving to 3.1.10) throughout.
- `lara-zeus/spatie-translatable` referred to as ^2.0 throughout.
- `filament/custom-dashboards-plugin` referred to as ^1.4.1-beta1 throughout.
- The `agent-browser` CLI used for all browser automation per the project's CLAUDE.md.
- Branch name `chore/filament-5-upgrade` used consistently in Phases 0–5; `feat/filament-copilot` used consistently in Phase 6.
