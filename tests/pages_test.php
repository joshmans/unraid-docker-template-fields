<?php
/* Reads the two .page files the way webgui's PageBuilder.php does (my_explode on "\n---\n", then
 * parse_ini_string on the header) and evaluates Cond the way page_enabled() does. */
require __DIR__ . '/lib.php';

function tf_page(string $file): array {
    [$header, $content] = explode("\n---\n", file_get_contents($file), 2);
    $page = @parse_ini_string($header);
    return $page ? $page + ['text' => $content] : [];
}
function tf_cond(array $page, string $uri): bool {
    $_SERVER['REQUEST_URI'] = $uri;
    $enabled = true;
    if (isset($page['Cond'])) eval("\$enabled={$page['Cond']};");
    return (bool)$enabled;
}

$hook = tf_page(TF_SRC . '/TemplateFieldsHook.page');
check('hook page header parses', $hook !== []);
check('hook page is a Buttons page', preg_match('/^Buttons(:\d+)?$/', $hook['Menu'] ?? '') === 1, $hook['Menu'] ?? null);
check('hook page has a Link, so Unraid draws no header icon for it', ($hook['Link'] ?? '') !== '');
check('hook page has no Type (it is not a menu)', !isset($hook['Type']));

foreach ([
    '/Docker/AddContainer?xmlTemplate=default:/tmp/x.xml' => true,
    '/Docker/AddContainer' => true,
    '/Docker/UpdateContainer?xmlTemplate=edit:/boot/config/plugins/dockerMan/templates-user/my-x.xml' => true,
    '/Apps/AddContainer?xmlTemplate=default:/tmp/x.xml' => true,
    '/Docker' => false,
    '/Docker/Logs' => false,
    '/Settings/TemplateFields' => false,
    '/Dashboard' => false,
    '/Plugins' => false,
] as $uri => $want) {
    check("Cond for $uri is " . ($want ? 'true' : 'false'), tf_cond($hook, $uri) === $want);
}

$set = tf_page(TF_SRC . '/TemplateFields.page');
check('settings page header parses', $set !== []);
check('settings page sits under Utilities', ($set['Menu'] ?? '') === 'Utilities');
check('settings page has a title', ($set['Title'] ?? '') === 'Docker Template Fields');

// every file the hook references exists
foreach (['css/templatefields.css', 'js/templatefields.js', 'default.cfg', 'include/hook.php', 'include/settings.php'] as $f) {
    check("ships $f", is_file(TF_SRC . "/$f"));
}
finish('pages_test');
