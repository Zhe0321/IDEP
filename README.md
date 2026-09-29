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

For local administrator testing, use:

```text
Username: admin123
Password: admin123
```

The demonstration account only works on `localhost`. On a shared/public URL,
administrator login uses active accounts from the SQLite `user` table.

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

## Site Registration database setup

For an existing local database, run these commands once after pulling the Site Registration changes:

```bash
php database/add_missing_columns.php
php database/add_photo_column.php
php database/import_bali_wilayah.php
```

The migration and import commands are safe to run again. The SQLite database file is intentionally ignored by Git, so each environment must run the setup against its own database.
