<?PHP
/* Runs from TemplateFieldsHook.page, a "Buttons" page that Unraid evaluates inside
 * <head> on every page whose URL matches its Cond (Add and Update Container).
 * It only adds a stylesheet, a script and the two feature switches; the script
 * works on the rows the Docker page builds, so nothing of Unraid is patched. */

const TF_PLUGIN = 'unraid-docker-template-fields';

/** The two feature switches as booleans; anything but "no" means on. */
function tf_settings(?string $cfg = null): array {
    $cfg = $cfg ?? '/boot/config/plugins/' . TF_PLUGIN . '/' . TF_PLUGIN . '.cfg';
    $ini = is_file($cfg) ? (@parse_ini_file($cfg) ?: []) : [];
    return [
        'reorder' => strtolower((string)($ini['REORDER'] ?? 'yes')) !== 'no',
        'toggle'  => strtolower((string)($ini['TOGGLE'] ?? 'yes')) !== 'no',
    ];
}

/** Public URL of a plugin file, versioned by Unraid's own cache-buster when there is one. */
function tf_asset(string $path): string {
    $url = '/plugins/' . TF_PLUGIN . '/' . $path;
    return function_exists('autov') ? autov($url, true) : $url;
}

function tf_inject(?array $settings = null): void {
    $s = $settings ?? tf_settings();
    // with both switched off the plugin does nothing at all
    if (!$s['reorder'] && !$s['toggle']) return;
    $json = json_encode($s, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo '<link type="text/css" rel="stylesheet" href="' . htmlspecialchars(tf_asset('css/templatefields.css')) . '">' . "\n";
    echo '<script>window.TemplateFieldsConfig=' . $json . ';</script>' . "\n";
    echo '<script src="' . htmlspecialchars(tf_asset('js/templatefields.js')) . '"></script>' . "\n";
}
