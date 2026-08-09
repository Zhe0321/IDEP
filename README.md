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

Press `Control + C` to stop the server.

## Share temporarily

Keep the PHP server running. Open a second terminal and run:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

Share the generated `https://...trycloudflare.com` link. The link stops working when the PHP server or tunnel is closed.
