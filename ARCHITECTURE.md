# How it works

## What Unraid does

The Add/Edit Container form (`dynamix.docker.manager/include/CreateDocker.php`, served as `AddContainer.page` and `UpdateContainer.page`) draws each `<Config>` of the template as a row `<div id="ConfigNum<n>">` holding hidden inputs `confName[]`, `confTarget[]`, `confType[]` … `confValue[]`. Rows go into `#configLocation` (Display `always`) or `#configLocationAdvanced` (`advanced`). The form posts them in document order and `postToXML()` writes them to the template in that order, so **DOM order is template order**.

`xmlToCommand()` then turns the saved template into `docker create` arguments. Its loop acts on `Type` = Path, Port, Label, Variable or Device only; any other type falls through every branch. That is the hook for disabling.

## What we do

1. **Getting into the page.** `TemplateFieldsHook.page` is a `Menu="Buttons"` page. Unraid evaluates those inside `<head>` on every page whose `Cond` is true; ours matches `AddContainer|UpdateContainer` in the URL. A non-empty `Link` stops Unraid drawing a header icon for it. The page only emits a stylesheet, the two feature switches and the script (`include/hook.php`). No Unraid file is patched.
2. **Rows.** The script (`js/templatefields.js`) puts a `.tf-controls` span (drag grip and switch) at the start of each row. The controls are spans, not inputs: the page's `editConfigPopup()` loops over every `<input>` in a row and calls `.attr("name").replace()`, which would throw on an unnamed one. A `MutationObserver` on both lists re-decorates rows after the page adds or rewrites them.
3. **Reorder.** jQuery UI's `sortable` (bundled in `dynamix.js`) on each list with the grip as handle; arrow keys move a row with `insertBefore`/`insertAfter`.
4. **Disable.** State lives in a map keyed by row id (`ConfigNum<n>`). It survives the edit dialog, which either rewrites a row's contents or removes it and adds a new one with the same id. On submit, a capture-phase `submit` listener puts `Disabled:` in front of the `confType[]` value of every disabled row, and a `setTimeout(0)` takes it off again in case the page stays open. `required` is removed from a disabled row's value input (and remembered) so an empty field you switched off cannot block saving; the browser reads the form data before the timeout fires. No input is set `disabled`: the browser would leave it out of the post, and the entry would be lost from the template instead of kept.
5. **Loading.** The template arrives as `var Settings = {... Config: [...]}` and is drawn by a ready handler. Ours is registered in `<head>`, so it runs first: it strips `Disabled:` from `Settings.Config[i].Type` (so the page builds the right value field: port number, folder picker, dropdown) and tags the entry. It wraps `makeConfig()` to read that tag, because `makeConfig(opts)` receives the row number `opts.Number` it is about to use; counting rows would be wrong (the page increments its counter twice per row).

## Why the marker is on `Type`

`postToXML()` writes only a fixed set of attributes per `<Config>`, so nothing custom survives a save. Of those attributes only `Type` (and an empty `Target`) is consulted by `xmlToCommand()`. A `Type` prefix keeps the entry, its value and everything else, is visible in the template file, and needs no sidecar file to keep in step with the template.

`DockerClient::updateUserTemplate()` matches `<Config>` entries by `Type` and `Target` when merging an author's updated template, which would add a duplicate, enabled entry beside a disabled one. As of the Unraid source checked (master, 2026-09-19) that function returns immediately ("Don't update templates"), so it does not run. If Unraid turns it back on, this needs revisiting.

## Files

| Path | Role |
|---|---|
| `TemplateFieldsHook.page` | Buttons page evaluated in `<head>` on Add/Update Container |
| `include/hook.php` | reads the settings, emits stylesheet, config and script |
| `js/templatefields.js`, `css/templatefields.css` | the feature |
| `TemplateFields.page`, `include/settings.php` | Settings > Utilities page (two switches, `update.php` form) |
| `tests/*_test.php` | headers and `Cond` as PageBuilder reads them, hook output, and that Unraid's `docker create` loop ignores `Disabled:` types |
| `tests/harness/` | mock Add Container page built from a copy of CreateDocker.php's row code, with an in-page self-test |
