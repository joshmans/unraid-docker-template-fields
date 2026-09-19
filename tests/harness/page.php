<?php
/* Stand-in for /Docker/AddContainer. The functions and markup below are copied (trimmed, not
 * changed in behaviour) from emhttp/plugins/dynamix.docker.manager/include/CreateDocker.php:
 * confNum, makeConfig, editConfigPopup's read/save code, removeConfig, prepareConfig, the
 * templateDisplayConfig row, and the ready handler that draws Settings.Config.
 * The plugin's real hook output is emitted in <head> exactly as Unraid would do it. */
require_once __DIR__ . '/../../source/usr/local/emhttp/plugins/unraid-docker-template-fields/include/hook.php';

$config = [
    ['Name' => 'WebUI',      'Target' => '8080', 'Default' => '8080', 'Mode' => 'tcp', 'Description' => 'Web interface', 'Type' => 'Port',     'Display' => 'always',   'Required' => 'true',  'Mask' => 'false', 'Value' => '8080'],
    ['Name' => 'Debug port', 'Target' => '9999', 'Default' => '',     'Mode' => 'tcp', 'Description' => 'Only when debugging', 'Type' => 'Disabled:Port', 'Display' => 'always', 'Required' => 'true', 'Mask' => 'false', 'Value' => ''],
    ['Name' => 'Config',     'Target' => '/config', 'Default' => '/mnt/user/appdata/demo', 'Mode' => 'rw', 'Description' => 'App data', 'Type' => 'Path', 'Display' => 'always', 'Required' => 'true', 'Mask' => 'false', 'Value' => '/mnt/user/appdata/demo'],
    ['Name' => 'TZ',         'Target' => 'TZ', 'Default' => '', 'Mode' => '', 'Description' => 'Timezone', 'Type' => 'Variable', 'Display' => 'advanced', 'Required' => 'false', 'Mask' => 'false', 'Value' => 'Europe/London'],
    ['Name' => 'Mode',       'Target' => 'MODE', 'Default' => 'a|b|c', 'Mode' => '', 'Description' => 'Run mode', 'Type' => 'Disabled:Variable', 'Display' => 'advanced', 'Required' => 'false', 'Mask' => 'false', 'Value' => 'b'],
    ['Name' => 'Tag',        'Target' => 'tag', 'Default' => 'demo', 'Mode' => '', 'Description' => 'A label', 'Type' => 'Label', 'Display' => 'advanced', 'Required' => 'false', 'Mask' => 'false', 'Value' => 'demo'],
];
?><!doctype html>
<html><head><meta charset="utf-8"><title>Template Fields harness</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
<style>
  body { font: 14px/1.4 sans-serif; background: #1c1b1b; color: #ddd; margin: 16px; }
  dl { margin: 6px 0; overflow: hidden; } dt { float: left; width: 33%; text-align: right; clear: left; padding-top: 6px; }
  dd { margin-left: 36%; } input[type=text], select { width: 60%; }
  .boxed { display: block; font-size: 12px; opacity: .8; } .orange-text { color: #ff8c2f; }
  .config_always-hide, .config_advanced-hide { }
  #selftest { white-space: pre-wrap; background: #000; padding: 8px; }
  #configLocationAdvanced { border-top: 1px solid #444; margin-top: 8px; }
</style>
<?php tf_inject(['reorder' => true, 'toggle' => true]); // what TemplateFieldsHook.page emits in <head> ?>
</head><body>
<h3>Add Container (harness)</h3>
<form method="POST" action="/post" target="result" autocomplete="off" onsubmit="return prepareConfig(this)">
  <dl><dt>Name:</dt><dd><input type="text" name="contName" value="demo"></dd></dl>
  <div id="configLocation"></div>
  <dl><dt>&nbsp;</dt><dd><a onclick="$('#configLocationAdvanced').toggle()" style="cursor:pointer">Show more settings ...</a></dd></dl>
  <div id="configLocationAdvanced"></div>
  <dl><dt>&nbsp;</dt><dd><button type="submit">Apply</button> <button type="button" onclick="addConfig()">Add another Path, Port, Variable, Label or Device</button></dd></dl>
</form>

<div id="templateDisplayConfig" style="display:none">
<input type="hidden" name="confName[]" value="{0}">
<input type="hidden" name="confTarget[]" value="{1}">
<input type="hidden" name="confDefault[]" value="{2}">
<input type="hidden" name="confMode[]" value="{3}">
<input type="hidden" name="confDescription[]" value="{4}">
<input type="hidden" name="confType[]" value="{5}">
<input type="hidden" name="confDisplay[]" value="{6}">
<input type="hidden" name="confRequired[]" value="{7}">
<input type="hidden" name="confMask[]" value="{8}">
<dl><dt><span class="{11}"><i class="fa fa-fw fa-{13}"></i>&nbsp;&nbsp;{0}:</span></dt>
<dd><span class="flex flex-col gap-4">
    <span class="flex flex-row flex-wrap items-center gap-4 buttons-no-margin">
      <input type="text" class="setting_input" name="confValue[]" default="{2}" value="{9}" autocomplete="off" spellcheck="false" {11}>
      {10}
    </span>
    <span class="boxed"><span class='orange-text'>{12}: {1}</span> <span class="orange-text">{4}</span></span>
  </span></dd></dl>
</div>

<div id="dialogAddConfig" style="display:none"></div>
<h4>Posted by the form</h4>
<iframe name="result" style="width:100%;height:160px;background:#fff"></iframe>
<pre id="selftest"></pre>

<script>
/* ---- copied from CreateDocker.php ---- */
var confNum = 0;
String.prototype.format = function () { var a = arguments; return this.replace(/{(\d+)}/g, function (m, n) { return typeof a[n] !== 'undefined' ? a[n] : m; }); };
var Settings = <?= json_encode(['Name' => 'demo', 'Config' => $config], JSON_HEX_TAG) ?>;

function stripTags(s) { return s.replace(/(<([^>]+)>)/ig, ""); }
function escapeQuote(s) { return s.replace(new RegExp('"', 'g'), "&quot;"); }

function makeConfig(opts) {
  confNum += 1;
  var icons = {'Path': 'folder-o', 'Port': 'minus-square-o', 'Variable': 'file-text-o', 'Label': 'tags', 'Device': 'play-circle-o'};
  var newConfig = $("#templateDisplayConfig").html();
  newConfig = newConfig.format(stripTags(opts.Name), opts.Target, opts.Default, opts.Mode, opts.Description, opts.Type, opts.Display,
    opts.Required, opts.Mask, escapeQuote(opts.Value), opts.Buttons, opts.Required == 'true' ? 'required' : '',
    'Container ' + opts.Type, icons[opts.Type] || 'question');
  newConfig = "<div id='ConfigNum" + opts.Number + "' class='config_" + opts.Display + "'' >" + newConfig + "</div>";
  newConfig = $($.parseHTML(newConfig));
  var value = newConfig.find("input[name='confValue[]']");
  if (opts.Type == "Path") {
    value.attr("onclick", "openFileBrowser(this,$(this).val(),$(this).val(),'',true,false);");
  } else if (opts.Type == "Variable" && opts.Default.split("|").length > 1) {
    var valueOpts = opts.Default.split("|");
    var newValue = "<select name='confValue[]' class='selectVariable' default='" + valueOpts[0] + "'>";
    for (var i = 0; i < valueOpts.length; i++) newValue += "<option value='" + valueOpts[i] + "' " + (opts.Value == valueOpts[i] ? "selected" : "") + ">" + valueOpts[i] + "</option>";
    newValue += "</select>";
    value.replaceWith(newValue);
  } else if (opts.Type == "Port") {
    value.addClass("numbersOnly");
  }
  return newConfig.prop('outerHTML');
}

function buttons(num) {
  return "<span class='flex flex-row items-center gap-4'><button type='button' onclick='editConfigPopup(" + num + ")'>Edit</button>" +
         "<button type='button' onclick='removeConfig(" + num + ")'>Remove</button></span>";
}

function editConfigPopup(num) {
  var popup = $("#dialogAddConfig");
  popup.html("<dl><dt>Name:</dt><dd><input name='Name'></dd></dl><dl><dt>Type:</dt><dd><input name='Type'></dd></dl>" +
    "<dl><dt>Display:</dt><dd><select name='Display'><option>always</option><option>advanced</option></select></dd></dl>" +
    "<input type='hidden' name='Target'><input type='hidden' name='Default'><input type='hidden' name='Mode'><input type='hidden' name='Description'>" +
    "<input type='hidden' name='Required'><input type='hidden' name='Mask'><dl><dt>Value:</dt><dd><input name='Value'></dd></dl>");
  var config = $("#ConfigNum" + num);
  config.find("input").each(function () {           // <- the real code: any unnamed <input> in a row would throw here
    var name = $(this).attr("name").replace("conf", "").replace("[]", "");
    popup.find("*[name='" + name + "']").val($(this).val());
  });
  popup.dialog({ title: "Edit Configuration", modal: true, width: 420, buttons: { "Save": function () { $(this).dialog("close"); saveEdit(num, config, this); }, "Cancel": function () { $(this).dialog("close"); } } });
}

function saveEdit(num, config, Element) {
  var Opts = {};
  ["Name", "Target", "Default", "Mode", "Description", "Type", "Display", "Required", "Mask", "Value"].forEach(function (e) {
    var el = $(Element).find("*[name=" + e + "]"); Opts[e] = el.length ? el.val() : "";
  });
  Opts.Buttons = buttons(num);
  Opts.Number = num;
  var newConf = makeConfig(Opts);
  if (config.hasClass("config_" + Opts.Display)) {   // same-Display edit: the row's contents are replaced
    config.html(newConf);
    config.removeClass("config_always config_always-hide config_advanced config_advanced-hide").addClass("config_" + Opts.Display);
  } else {                                           // Display changed: the row is removed and re-added
    config.remove();
    if (Opts.Display == 'advanced' || Opts.Display == 'advanced-hide') $("#configLocationAdvanced").append(newConf);
    else $("#configLocation").append(newConf);
  }
  $('input[name="contName"]').trigger('change');
}

function removeConfig(num) {
  $('#ConfigNum' + num).fadeOut("fast", function () { $(this).remove(); });
  $('input[name="contName"]').trigger('change');
}
function prepareConfig(form) { return true; }

function addConfig() {
  var o = {Name: 'New port', Target: '1234', Default: '1234', Mode: 'tcp', Description: '', Type: 'Port', Display: 'always', Required: 'false', Mask: 'false', Value: '1234'};
  o.Number = confNum + 1; o.Buttons = buttons(o.Number);
  $("#configLocation").append(makeConfig(o));
}

/* the page's own ready handler, registered after the plugin's (which is in <head>) */
$(function () {
  for (var i = 0; i < Settings.Config.length; i++) {
    confNum += 1;
    var Opts = Settings.Config[i];
    Opts.Buttons = buttons(confNum);
    Opts.Number = confNum;
    var newConf = makeConfig(Opts);
    if (Opts.Display == 'advanced' || Opts.Display == 'advanced-hide') $("#configLocationAdvanced").append(newConf);
    else $("#configLocation").append(newConf);
  }
});
</script>
<?php if (isset($_GET['selftest'])) require __DIR__ . '/selftest.js.php'; ?>
</body></html>
