<?php
declare(strict_types=1);

/**
 * Sends browser security headers. Include this at the very top of a page,
 * before any output.
 */
if (!headers_sent()) {
    // Stop browsers guessing file types (blocks some upload/XSS tricks).
    header('X-Content-Type-Options: nosniff');
    // Stop other sites embedding these pages in a frame (clickjacking).
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'");
    // Send only the site name (not the full URL) to other sites.
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Switch off browser features the site does not use.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    // Hide the PHP version.
    header_remove('X-Powered-By');
    // Tell browsers to always use HTTPS (only sent when the page is served over HTTPS).
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}