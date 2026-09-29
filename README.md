# IDEP Bali Water Protection

PHP website for the Bali Water Protection project.

## Run locally

Open a terminal in the project folder:

```bash
cd /Users/nanzhe/Desktop/IDEP
php -S 127.0.0.1:8000
```

Open the website at:

```text
http://127.0.0.1:8000
```

The demonstration accounts are stored in the SQLite `user` table:

```text
Administrator
Username: admin123
Password: admin123

Manager
Username: manager123
Password: manager123
```

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
php database/import_bali_wilayah.php
php database/migrate_user_roles.php
```

The migration and import commands are safe to run again. The SQLite database file is intentionally ignored by Git, so each environment must run the setup against its own database.
