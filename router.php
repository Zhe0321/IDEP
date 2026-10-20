<?php
declare(strict_types=1);

/**
 * Router for PHP's built-in development server:
 *     php -S 127.0.0.1:8000 router.php      (or ./start-server.sh)
 *
 * Without a router the built-in server publishes every file in this folder,
 * including the SQLite database and maintenance scripts. This router answers
 * 404 for those and lets everything else through unchanged.
 */
$root = __DIR__;
$path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
$real = realpath($root . $path);

$blocked =
    preg_match('#^/database(/|$)#i', $path) === 1
    || ($real !== false && str_starts_with($real, $root . DIRECTORY_SEPARATOR . 'database'))
    || preg_match('#(^|/)\.#', $path) === 1                         // .git, .env, .user.ini, .DS_Store
    || preg_match('#\.(db|sqlite3?|sql|md|log|sh)$#i', $path) === 1  // data, docs and scripts
    || preg_match('#^/uploads/.*\.(php|phtml|phar)#i', $path) === 1  // nothing uploaded may run
    || $path === '/router.php';

if ($blocked) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    return true;
}

return false; // serve the requested file or PHP script normally
