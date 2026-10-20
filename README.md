# IDEP Bali Water Protection

PHP website for the Bali Water Protection project.

## Run locally

Open a terminal in the project folder:

```bash
cd /path/to/IDEP
./start-server.sh
```

`start-server.sh` runs `php -d upload_max_filesize=6M -d post_max_size=8M -S 127.0.0.1:8000 router.php`.
Always start the built-in server with `router.php`: without it, PHP publishes every file in the
folder, including the SQLite database and the maintenance scripts. The upload settings let
5 MB site photos through (PHP's default limit is 2 MB).

Open the website at:

```text
http://127.0.0.1:8000
```

The demonstration accounts (administrator `admin123`, manager `manager123`) are created by
`php database/migrate_user_roles.php`. Their passwords are in the credentials file supplied with
the product, not in this public README. Change them, or create named accounts and deactivate
these, before the site is shared.

Passwords are stored as one-way hashes in SQLite, so the original password text is
not exposed when the database file is shared.

The administrator sees every operational page plus User Management, where accounts
can be created, edited, activated, deactivated, or deleted. The manager
sees every operational page except User Management. Public users keep the same
read-only access and do not need an account.

Press `Control + C` to stop the server.

## Share temporarily

Keep the PHP server running. Open a second terminal and run:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

Share the generated `https://...trycloudflare.com` link. The link stops working when the PHP server or tunnel is closed.

## Current data behaviour

- Dashboard and Historical Data read sensor measurements from `database/idep_groundwater.db`.
- The SQLite database is local to each computer and is not stored in Git.
- Site Registration reads and writes sensors, wells, and the Bali location hierarchy in `database/idep_groundwater.db`.
- Newly registered wells with coordinates are included in the dashboard and map data.
- Generated reports and settings persist in the current browser.
- Public users do not need an account; administrator pages require a server session.
- User roles are stored in `user.status` as `admin` or `manager`; account access is
  enabled when `deleted_at` is empty.

## Site Registration database setup

For an existing local database, run these commands once after pulling the Site Registration changes:

```bash
php database/add_missing_columns.php
php database/add_photo_column.php
php database/add_location_types.php
php database/import_bali_wilayah.php
php database/migrate_user_roles.php
```

The migration and import commands are safe to run again. SQLite database files are
ignored by Git, so each computer keeps its own accounts and sensor history. Git does
not synchronise live sensor readings between running computers.

## Security notes

- Sensor readings are validated: `h1` and `h2` must be numbers from 0 to 100, `hasil` from 0 to
  1,000,000, and `id_device` 1–64 letters, digits, dots, dashes or underscores; anything else gets HTTP 422.
- Scripts in `database/` run from the command line only.
- `database/import_users_sensors.sql` contains plain-text demo passwords: after loading it, run
  `php database/migrate_user_roles.php` to hash them.
- On Apache, `.htaccess` blocks `/database/` and data files. On Nginx, add the deny rules from the
  technical report (Section 7.4). On PHP-FPM hosting, `.user.ini` raises the upload limit.
