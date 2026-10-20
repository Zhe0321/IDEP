#!/bin/sh
# Starts the local development server with the security router and a 6 MB upload limit
# (PHP's default upload limit is 2 MB, below the 5 MB site-photo limit).
cd "$(dirname "$0")" || exit 1
exec php -d upload_max_filesize=6M -d post_max_size=8M -S 127.0.0.1:8000 router.php
