<?php
/* The whole feature rests on one fact: Unraid's `docker create` builder acts only on
 * Path, Port, Label, Variable and Device entries, so an entry saved as "Disabled:<Type>" is inert.
 * This runs the real loop (fixtures/xmlToCommand_config_loop.php) over a template that mixes both. */
require __DIR__ . '/lib.php';
require __DIR__ . '/fixtures/xmlToCommand_config_loop.php';

$driver = ['bridge' => 'bridge'];
$cfg = function (string $type, string $target, string $value, string $mode = '') {
    return ['Type' => $type, 'Target' => $target, 'Value' => $value, 'Default' => '', 'Mode' => $mode];
};

$xml = ['Network' => 'bridge', 'Config' => [
    $cfg('Port', '80', '8080', 'tcp'),
    $cfg('Path', '/config', '/mnt/user/appdata/x', 'rw'),
    $cfg('Variable', 'TZ', 'UTC'),
    $cfg('Label', 'tag', 'demo'),
    $cfg('Device', '/dev/dri', '/dev/dri'),
]];
$on = tf_config_loop($xml);
check('enabled entries all reach the command', count($on['Ports']) === 2 && count($on['Volumes']) === 2 && count($on['Variables']) === 2 && count($on['Labels']) === 2 && count($on['Devices']) === 2, $on);

$xml['Config'] = array_map(function ($c) { $c['Type'] = 'Disabled:' . $c['Type']; return $c; }, $xml['Config']);
$off = tf_config_loop($xml);
foreach (['Ports', 'Volumes', 'Variables', 'Labels', 'Devices'] as $k) {
    check("a disabled entry adds nothing to $k", $off[$k] === [''], $off[$k]);
}

// a mix: only the enabled ones survive, and order is preserved
$xml['Config'] = [
    $cfg('Port', '80', '8080', 'tcp'),
    $cfg('Disabled:Port', '9999', '9999', 'tcp'),
    $cfg('Variable', 'A', '1'),
    $cfg('Disabled:Variable', 'B', '2'),
    $cfg('Variable', 'C', '3'),
];
$mix = tf_config_loop($xml);
check('mixed template: one port', $mix['Ports'] === ['', "'8080:80/tcp'"], $mix['Ports']);
check('mixed template: only A and C, in order', $mix['Variables'] === ['', "'A'='1'", "'C'='3'"], $mix['Variables']);

finish('docker_command_test');
