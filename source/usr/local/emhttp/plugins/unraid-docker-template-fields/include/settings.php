<?PHP
/* Settings > Utilities > Docker Template Fields. A plain update.php form: Unraid adds
 * the csrf_token to every form on the page and writes the keys to the .cfg. */
require_once __DIR__ . '/hook.php';
$s = tf_settings();
$yn = function (string $name, bool $on): string {
    return '<select name="' . $name . '" style="min-width:8em">'
         . '<option value="yes"' . ($on ? ' selected' : '') . '>Yes</option>'
         . '<option value="no"' . ($on ? '' : ' selected') . '>No</option></select>';
};
?>
<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#file" value="<?= TF_PLUGIN ?>/<?= TF_PLUGIN ?>.cfg">
<dl>
  <dt>Drag to reorder fields:</dt>
  <dd><?= $yn('REORDER', $s['reorder']) ?></dd>
</dl>
<blockquote class="inline_help">
  Puts a drag handle on every Path, Port, Variable, Label and Device row of the Add/Edit Container page. The order you leave them in is the order saved in the template.
</blockquote>
<dl>
  <dt>Enable / disable toggle on fields:</dt>
  <dd><?= $yn('TOGGLE', $s['toggle']) ?></dd>
</dl>
<blockquote class="inline_help">
  Adds an on/off switch to each row. A disabled field stays in the template, but is left out of the container when it is created. Fields already disabled stay disabled if you turn this off.
</blockquote>
<dl>
  <dt>&nbsp;</dt>
  <dd>
    <input type="submit" value="Apply">
    <input type="button" value="Done" onclick="done()">
  </dd>
</dl>
</form>
<p>
  Open <b>Docker</b> and add or edit a container (or install one from Community Apps) to use it. Turning both options off removes the plugin's script from the page.
</p>
