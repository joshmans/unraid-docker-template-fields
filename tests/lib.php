<?php
/* Tiny test kit: check() and finish(), used by every *_test.php. */

$GLOBALS['tf_fail'] = 0;
$GLOBALS['tf_pass'] = 0;

function check(string $label, bool $ok, $detail = null): void {
    if ($ok) { $GLOBALS['tf_pass']++; return; }
    $GLOBALS['tf_fail']++;
    echo "FAIL: $label" . ($detail !== null ? "\n      " . (is_string($detail) ? $detail : json_encode($detail)) : '') . "\n";
}

function finish(string $name): void {
    echo "$name: {$GLOBALS['tf_pass']} passed, {$GLOBALS['tf_fail']} failed\n";
    exit($GLOBALS['tf_fail'] ? 1 : 0);
}

const TF_SRC = __DIR__ . '/../source/usr/local/emhttp/plugins/unraid-docker-template-fields';
