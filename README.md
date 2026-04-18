# DataForce CMS

Legacy PHP admin panel / CMS engine, written 2004–2016 by Olexander Bunke.
Preserved here as a historical pet-project and as a reusable admin for a few
of my own sites. Not production-ready by modern standards — see the security
notice below.

## What it is

A table-driven admin panel for MySQL. You describe each entity as a PHP class
with a list of `Field` objects (type, label, relations, validation), and the
CMS auto-generates:

- catalog / list view with tree of rubrics
- add / edit form with image and file upload
- sort, search, filter, bulk delete
- multi-language fields, tags, dynamic per-domain texts
- admin users, groups and access menu
- action log

Field types (`.readme` has the full list): numeric, string, rich text, hidden,
checkbox, tree dropdown, static dropdown, file, date, color, subquery, etc.

## Requirements

- PHP ≥ 5.6 (designed for 5.6–7.x; not tested on 8.x)
- MySQL / MariaDB
- Apache with mod_rewrite (uses `.htaccess`)

## Install as a vendor package (recommended)

This package is **not published on Packagist** — it lives at
<https://github.com/bunke/dataforce-cms> and is consumed as a VCS repo.
One-time setup per host site:

```bash
composer config repositories.dataforce vcs https://github.com/bunke/dataforce-cms
composer require bunke/dataforce-cms:dev-main
vendor/bin/dataforce-install
```

Equivalent `composer.json` fragment if you prefer editing directly:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/bunke/dataforce-cms" }
    ],
    "require": {
        "bunke/dataforce-cms": "dev-main"
    }
}
```

Then `composer install` + `vendor/bin/dataforce-install`.

The installer scaffolds four files in the host site:

| file                                | purpose                                              |
|-------------------------------------|------------------------------------------------------|
| `config/dataforce.php`              | paths + table names (no secrets — `getenv()` reads creds) |
| `public/admin/index.php`            | 3-line entry point                                   |
| `public/admin/.htaccess`            | rewrite all `*.php` → `index.php`                    |
| `cms-models/AdminExample.php`       | commented skeleton of a project-specific model       |

and copies static assets (`css/`, `js/`, `img/`, `fonts/`, `plugins/`)
into `public/admin/`. The PHP core stays in `vendor/bunke/dataforce-cms/src/`
and is never edited locally.

Installer options:

```
--public=DIR              web root (default: public)
--config=DIR              config dir (default: config)
--models=DIR              project-specific models (default: cms-models)
--files=DIR               uploads dir (default: <public>/files)
--mount=URL               URL prefix (default: /admin)
--project=NAME            project name shown in UI
--force                   overwrite existing files
--skip-assets             don't copy css/js/img
--skip-example-model      don't scaffold cms-models/AdminExample.php
```

After install:

1. Edit `config/dataforce.php` — fill DB credentials.
2. On first run open `/admin/login.php?crt=1` to create base tables.
3. Default login: `admin` / `admin` — **change immediately**.

Updates: `composer update bunke/dataforce-cms` + re-run
`vendor/bin/dataforce-install --skip-assets` (or with `--force` to refresh
generated files). Local config is left untouched.

## Migrating an old DataForce-style admin to this package

If you have an old PHP site with the pre-2016 flavour of this CMS
(loose `admin/` directory, PHP-4-style classes, `mysql_*` calls), the
path to the modern composer layout is mechanical. This section is the
recipe refined on the `popov` migration — follow it top-to-bottom.

### 0. Take stock

```bash
ls admin/                    # old CMS root
ls admin/models/             # your admin_* classes
cat admin/config.php         # DB creds, $TABLE_* names
```

Figure out what's **project-specific** (every `admin_*` class except
`admin_admins*`) vs what's **CMS infrastructure** (admins, admin groups,
menu, menu assoc, log).

### 1. Backup

```bash
# DB dump
mysqldump -h HOST -u USER -p DBNAME > db-backup-$(date +%F).sql
# move the old admin out of the way, don't delete it yet
mv admin admin.legacy
```

### 2. Install the package

```bash
composer init -n --name=you/your-site
composer config repositories.dataforce vcs https://github.com/bunke/dataforce-cms
composer require bunke/dataforce-cms:dev-main

# For a non-Symfony site where admin lives at /admin/ at project root:
vendor/bin/dataforce-install --public=. --files=. --mount=admin --project=YourSite

# For a Symfony-style site with public/ as web root:
vendor/bin/dataforce-install --project=YourSite
```

### 3. Adapt old models to the new API

**File naming and class declaration**

```php
// Old (PHP 4-ish, no namespace, no base class)
<?
class admin_docs
{
    var $fld;
    ...
}

// New
<?php
class admin_docs extends AdminTable
{
    public $fld;
    ...
}
```

**Field constructor signature**

```php
// Old — 8 positional arguments
new Field("rub_id", "Rubric", 9, 0, 0, 'docs_rubs', -1, 'name_1');

// New — 4 arguments, last is an options array
new Field('rub_id', 'Rubric', C_LIST, [
    'showInList'       => 0,
    'editInList'       => 0,
    'valsFromTable'    => 'docs_rubs',
    'valsFromCategory' => -1,
    'valsEchoField'    => 'name_1',
]);
```

Field-type integer constants are still accepted, but the named
`C_TEXTLINE` / `C_TEXT` / `C_NOGEN` / `C_CHECKBOX` / `C_LIST` / `C_FILE` …
constants read better.

**Class-as-constructor (PHP 4 style)**

```php
// Old — method named after class, fatal in PHP 8+
class BunTempl {
    function BunTempl($src) { ... }
}

// New
class BunTempl {
    function __construct($src) { ... }
}
```

**Deprecated `mysql_*` calls inside your hooks**

```php
// Old
mysql_query("UPDATE docs SET ...");

// New — use the CMS mysqli wrapper that's in scope
mQuery("UPDATE docs SET ...");
```

Drop the new `cms-models/AdminFoos.php` files into the host site's
`cms-models/` directory (or wherever `paths.extra_models` points in
`config/dataforce.php`). Delete `cms-models/AdminAdmins.php` — the CMS
ships its own and will refuse to load both.

### 4. Migrate the admin DB schema

Old DataForce used different column names in the CMS-core tables.
Write a single SQL migration that runs once, before the new code lands:

```sql
ALTER TABLE `admins_menu`
    CHANGE `name_1`  `name`          VARCHAR(250) NOT NULL DEFAULT '',
    CHANGE `crtdate` `creation_time` BIGINT(20)   NOT NULL DEFAULT 0;

ALTER TABLE `admins_groups`
    CHANGE `name_1`  `name`          VARCHAR(250) NOT NULL DEFAULT '',
    CHANGE `crtdate` `creation_time` BIGINT(20)   NOT NULL DEFAULT 0;

ALTER TABLE `admins`
    CHANGE `name_1` `name`           VARCHAR(250) NOT NULL DEFAULT '',
    CHANGE `under`  `group_id`       INT(11)      NOT NULL DEFAULT 0,
    CHANGE `crtdate` `creation_time` BIGINT(20)   NOT NULL DEFAULT 0,
    ADD COLUMN `email`        VARCHAR(255) NOT NULL DEFAULT '' AFTER `passwd`,
    ADD COLUMN `passwd_rec`   VARCHAR(64)  NOT NULL DEFAULT '' AFTER `email`,
    ADD COLUMN `deny_tables`  VARCHAR(255) NOT NULL DEFAULT '' AFTER `group_id`,
    ADD COLUMN `deny_scripts` VARCHAR(255) NOT NULL DEFAULT '' AFTER `deny_tables`;

CREATE TABLE IF NOT EXISTS `admins_menu_assoc` (
    `menu_id`  INT(11) NOT NULL,
    `group_id` INT(11) NOT NULL,
    PRIMARY KEY (`menu_id`, `group_id`),
    KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- admins_log uses table_name/rec_id/creation_time in the new schema
ALTER TABLE `admins_log`
    CHANGE `table`   `table_name`    VARCHAR(50)  NOT NULL DEFAULT '',
    CHANGE `recID`   `rec_id`        INT(11)      NOT NULL DEFAULT 0,
    CHANGE `crtdate` `creation_time` BIGINT(20)   NOT NULL DEFAULT 0;
```

Set `admins.email` for existing users or password recovery will have
nowhere to send to.

### 5. If the legacy frontend still includes files from `admin/`

Old sites often `include("admin/config.php")` / `admin/connect.php` /
`admin/lib/*.php` from root-level `index.php`. The new `admin/` is a
minimal composer entry, so those includes break.

Two options:

**a. Keep a compatibility layer under `admin/`** — copy the needed
files (`config.php`, `connect.php`, `lib/ifuncs.php`, `lib/buntempl.php`)
from `admin.legacy/` back into `admin/`. Rewrite `connect.php` to use
`mysqli_*` and re-expose the removed `mysql_*` functions as thin
wrappers so the legacy frontend still works on PHP 7/8. Rename
PHP-4-style constructors to `__construct`.

**b. Modernise the frontend** — replace `admin/config.php` includes
with a dedicated `frontend/bootstrap.php` that reads from `.env`
and opens its own PDO connection. Rewrite frontend SQL to use PDO
directly.

Option (a) is the smaller diff; (b) is cleaner but touches every
frontend file. The popov migration used (a).

### 6. Rebuild the admin menu with sensible icons

The legacy `admins_menu` rows are worth rebuilding after the schema
rename. Glyphicon names work out of the box; pick from
<https://getbootstrap.com/docs/3.3/components/#glyphicons>.

```sql
TRUNCATE `admins_menu`;
TRUNCATE `admins_menu_assoc`;

INSERT INTO `admins_menu` (`id`, `icon`, `name`, `url`, `under`, `sort`, `creation_time`) VALUES
    (1, 'glyphicon glyphicon-text-size', 'Pages',    'catalog.php?tabler=docs_rubs&tablei=docs&srci=items.php&under=-1', -1, 100, UNIX_TIMESTAMP()),
    (2, 'glyphicon glyphicon-cog',       'Settings', '#', -1, 10, UNIX_TIMESTAMP()),
    (10,'glyphicon glyphicon-tower',     'Admins',   'catalog.php?tabler=admins_groups&tablei=admins',  3, 60, UNIX_TIMESTAMP()),
    (11,'glyphicon glyphicon-flash',     'SQL',      'query.php', 3, 40, UNIX_TIMESTAMP()),
    (12,'glyphicon glyphicon-log-out',   'Exit',     'exit.php',  3, 10, UNIX_TIMESTAMP());

INSERT INTO `admins_menu_assoc` (`menu_id`, `group_id`)
SELECT `id`, 1 FROM `admins_menu`;
```

### 7. Deploy + verify

1. Upload `vendor/`, `cms-models/`, `config/`, `admin/`, `.env`,
   `composer.json`, `composer.lock` via SFTP (or `git pull + composer
   install` if the host is under git).
2. Turn on `'error_display' => true` in `config/dataforce.php` once
   for the first load — any remaining PHP-8 strictness warnings will
   surface immediately.
3. Open `/admin/login.php`, log in, click through `catalog.php` for
   each migrated model. Trigger CKEditor image upload, save a record,
   delete a record — the three paths that touched filesystem.
4. When clean, flip `error_display` back to `false`.
5. Remove `admin.legacy/` from the server.

### Gotchas seen in the field

- **White screen after migration** → `$admin_xxx` class didn't load.
  `cms-models/` wasn't uploaded, or `paths.extra_models` points at the
  wrong directory.
- **`Failed opening required vendor/autoload.php`** in
  `public/admin/index.php` → installer was run against an older
  version without the `normalizePath` fix. `composer update` + re-run
  the installer with `--force`.
- **`Cannot redeclare mb_ucfirst()`** → Symfony's polyfill collides
  with the CMS's helper. Upgrade the CMS — it's guarded with
  `function_exists()` since commit `eaac5b9`.
- **`Undefined array key` blizzards on PHP 8** → mostly harmless and
  already silenced in the controllers in `main`. If a new one shows
  up, wrap the faulting expression with `?? ''` and send a PR.

## Standalone install (legacy layout)

If you prefer the original "drop into `/admin/`" layout:

```bash
cp config.sample.php config.php
# edit config.php — set DB_HOST / DB_NAME / DB_USER / DB_PASSWORD
```

Point a vhost at the parent directory so `/admin/` resolves to this folder,
then open `/admin/login.php?crt=1` to create the base tables.

## Security notice — read before deploying

This project is a legacy codebase that was first written long before
today's PHP security practices. Several entry-point hardenings have
landed in `main` (request-variable filtering, LFI-safe routing, class
whitelist on dynamic instantiation, scoped password-recovery tokens,
CKEditor upload MIME checks, etc.), but parts of the core inevitably
reflect their era.

**Don't expose `/admin/` to the open internet without at least:**

- HTTPS (browsers will refuse the login form over plain HTTP anyway)
- An IP allow-list, HTTP Basic auth, or a VPN gate in front of it
- A strong admin password — change the default immediately after install
- Regular MySQL + uploads backups

If you're running the admin strictly for trusted operators behind one
of the above gates, you're in the intended deployment envelope.

## Code style

A `.php-cs-fixer.php` config lives at the repo root with a conservative
ruleset (whitespace, indentation, short arrays, single quotes — no
structural rewrites). To format `models/`, `inc/`, `controllers/`,
`ajax/`, `src/`:

```bash
curl -sL https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/latest/download/php-cs-fixer.phar -o php-cs-fixer.phar
php php-cs-fixer.phar fix --config=.php-cs-fixer.php
```

`lib/` (PHPExcel) and `plugins/` (CKEditor etc.) are excluded — third-party.

## License

GPL-2.0-or-later. See `LICENSE`.
Copyright © 2004 Olexander Bunke and co-authors.
