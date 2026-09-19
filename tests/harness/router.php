<?php
/* php -S router: a stand-in for Unraid's Add Container page. `/` is page.php, which copies the
 * row-building code of dynamix.docker.manager's CreateDocker.php and loads the plugin's real
 * hook output; `/post` echoes what the form would send. */
$src = realpath(__DIR__ . '/../../source/usr/local/emhttp/plugins/unraid-docker-template-fields');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/') { require __DIR__ . '/page.php'; return true; }

if ($path === '/post') {
    header('Content-Type: application/json');
    echo json_encode($_POST, JSON_PRETTY_PRINT);
    return true;
}

if (strpos($path, '/plugins/unraid-docker-template-fields/') === 0) {
    $f = realpath($src . substr($path, strlen('/plugins/unraid-docker-template-fields')));
    if ($f && strpos($f, $src) === 0 && is_file($f)) {
        header('Content-Type: ' . (substr($f, -3) === 'css' ? 'text/css' : 'application/javascript'));
        readfile($f);
        return true;
    }
}
http_response_code(404);
