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
- Site Registration, generated reports, and settings persist in the current browser until their shared database tables are finalised.
- Public users do not need an account; administrator pages require a server session.
