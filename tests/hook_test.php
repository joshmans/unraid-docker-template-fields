<?php
require __DIR__ . '/lib.php';
require TF_SRC . '/include/hook.php';

$tmp = sys_get_temp_dir() . '/tf-test-' . bin2hex(random_bytes(4));
mkdir($tmp);
$cfg = "$tmp/templatefields.cfg";

check('missing cfg means both features on', tf_settings("$tmp/none.cfg") === ['reorder' => true, 'toggle' => true]);
file_put_contents($cfg, "REORDER=\"no\"\nTOGGLE=\"yes\"\n");
check('REORDER=no switches reorder off only', tf_settings($cfg) === ['reorder' => false, 'toggle' => true]);
file_put_contents($cfg, "REORDER=\"yes\"\nTOGGLE=\"NO\"\n");
check('values are case-insensitive', tf_settings($cfg) === ['reorder' => true, 'toggle' => false]);
file_put_contents($cfg, "REORDER=\"garbage\"\n");
check('anything but "no" counts as on', tf_settings($cfg) === ['reorder' => true, 'toggle' => true]);
file_put_contents($cfg, "this is not = valid = ini [[[\n");
check('a broken cfg falls back to on', tf_settings($cfg) === ['reorder' => true, 'toggle' => true]);

ob_start(); tf_inject(['reorder' => true, 'toggle' => true]); $html = ob_get_clean();
check('inject emits the stylesheet', strpos($html, '/plugins/unraid-docker-template-fields/css/templatefields.css') !== false);
check('inject emits the script after the config', strpos($html, 'TemplateFieldsConfig={"reorder":true,"toggle":true}') !== false && strpos($html, 'js/templatefields.js') > strpos($html, 'TemplateFieldsConfig'), $html);
ob_start(); tf_inject(['reorder' => false, 'toggle' => true]); $html = ob_get_clean();
check('one feature off is passed to the script', strpos($html, '"reorder":false,"toggle":true') !== false, $html);
ob_start(); tf_inject(['reorder' => false, 'toggle' => false]); $html = ob_get_clean();
check('both features off emits nothing at all', $html === '', $html);

// when Unraid is there, its cache-busting helper is used
function autov($file, $ret = false) { $u = "$file?v=123"; if ($ret) return $u; echo $u; }
ob_start(); tf_inject(['reorder' => true, 'toggle' => true]); $html = ob_get_clean();
check('asset URLs go through autov()', strpos($html, 'templatefields.js?v=123') !== false && strpos($html, 'templatefields.css?v=123') !== false, $html);

// the settings page posts what tf_settings() reads
$_SERVER['x'] = 1;
ob_start(); require TF_SRC . '/include/settings.php'; $page = ob_get_clean();
check('settings page writes to the plugin cfg', strpos($page, 'name="#file" value="unraid-docker-template-fields/unraid-docker-template-fields.cfg"') !== false);
check('settings page has both selects', strpos($page, 'name="REORDER"') !== false && strpos($page, 'name="TOGGLE"') !== false);

foreach (glob("$tmp/*") as $f) unlink($f);
rmdir($tmp);
finish('hook_test');
