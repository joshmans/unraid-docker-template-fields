# Docker Template Fields for Unraid

Drag to reorder the fields of a Docker template, and switch a field off without deleting it.

It adds a **drag handle** and an **on/off switch** to every Path, Port, Variable, Label and Device row on Unraid's **Add/Edit Container** page. That includes the page you get when you install an app from Community Apps.

## Why

A Docker template is a list of fields, and Unraid gives you two ways to change that list: edit a value, or delete the field. That gets awkward quickly:

- **Testing means deleting.** To see how a container behaves without a variable, a port mapping or a path, you have to remove the field, apply, and later add it back and re-type its name, target and description. Community Apps templates often carry dozens of optional variables, so this happens a lot.
- **Order is fixed.** Fields appear in the order the template author chose. If you want the ones you touch most at the top, there is no way to move them.

With this plugin you flip a field off, apply, and flip it back on when you are done. The field keeps its value and stays in the template, but Unraid leaves it out of the container while it is off. Reordering is drag and drop, and what you leave is what gets saved.

## What it does

1. **Drag to reorder.** Grab a row by its grip (three lines) and drop it where you want it. Or focus the grip and press the up and down arrow keys. The order you leave the rows in is the order saved in the template.
2. **Switch a field off.** Click the row's switch. The row dims and is tagged *disabled*. Press **Apply** as usual and the container is created without that field. A disabled field keeps its value, so switching it back on restores it.

## Tested on

- **Unraid 7.2, 7.3 and 7.4.0-beta.2.** On 7.4.0-beta.2 a development build was used on a real container (`binhex-official-metube`): a variable was switched off and applied, the template kept it as disabled, and the recreated container had no such variable. It has also been used on 7.2 and 7.3. The published `.plg` installs cleanly and loads the same files.
- **Community Apps updates and "Update all"** with a field switched off have been tried, and the disabled field stays out of the container.
- **Automated:** the page script is run against a copy of the Add Container page's own row code, and the claim that Unraid ignores a disabled field is run against a copy of Unraid's `docker create` builder (see *Development*).

If something behaves differently on your version, please [open an issue](https://github.com/joshmans/unraid-docker-template-fields/issues) with your Unraid version.

## Install

In Unraid, go to **Plugins → Install Plugin** and paste:

```
https://raw.githubusercontent.com/joshmans/unraid-docker-template-fields/main/unraid-docker-template-fields.plg
```

Requires Unraid 7.2 or newer. There is nothing to configure: open **Docker → Add Container** (or edit a container, or install an app from Community Apps) and the controls are in the left margin of every field. **Settings → Utilities → Docker Template Fields** can switch either feature off.

## Using it

- **Reorder:** drag a row by its grip (three lines), or focus the grip and press ↑ / ↓. Rows stay in their own list: fields under *Show more settings* don't jump into the main list, because that would change what they are.
- **Disable:** click the switch. The row dims and is tagged *disabled*. Press **Apply** as usual; the container is created without that field.
- **Enable again:** click the switch and press Apply.
- Turning on a field that is *required* and empty needs a value first, the same as any required field. A disabled required field never blocks saving.

## How a disabled field is stored

The template keeps the entry, with `Disabled:` in front of its `Type`, for example `Type="Disabled:Port"`. Unraid builds the `docker create` command only from entries of type Path, Port, Label, Variable and Device, so a `Disabled:` entry is skipped. Applying, updating from Docker or Community Apps, and "Update all" all use that same builder. Nothing else in the template changes, and the page removes the prefix again when it loads the template, so its own edit dialog never sees it.

If you uninstall the plugin, disabled fields stay in the template, inert, and show up as ordinary-looking rows whose type reads `Disabled:Port` etc. Edit the type back to `Port` to enable one without the plugin.

## Limits

- Drag and drop uses jQuery UI's sortable, which is mouse-only. On a touch screen use the arrow keys, or a keyboard.
- Rows are not moved between the main list and *Show more settings*.
- The plugin works on the page in your browser only. It does not read or rewrite template files itself.

## Development

```sh
tests/run.sh                    # needs only php-cli
tests/harness/serve.sh          # mock Add Container page on http://127.0.0.1:8766
                                # open /?selftest=1 to run the in-page checks (needs internet for jQuery from cdnjs)
./build.sh 2026.09.20           # build packages/*.txz and stamp unraid-docker-template-fields.plg
```

Every rebuild that is installed on a box needs a new version (a letter suffix is fine, e.g. `2026.09.19b`): Unraid only offers an update when the `.plg` version changes, and it caches the downloaded `.txz` under a versioned filename. The package is installed with `installpkg` after the older version is removed, not `upgradepkg`, because `upgradepkg` derives the package name `unraid-docker` from `name-version.txz` and would skip the install whenever another `unraid-docker-*` package is installed. Release by committing the stamped `.plg` and uploading the exact built `.txz` to a GitHub release whose tag equals the version.

See [ARCHITECTURE.md](ARCHITECTURE.md) for how it hooks into Unraid.

## License

MIT
