# Docker Template Fields for Unraid

Two small conveniences for the fields of a Docker template, on the **Add/Edit Container** page (and in the Community Apps install popup):

1. **Drag to reorder.** Every Path, Port, Variable, Label and Device row gets a drag handle. The order you leave them in is the order saved in the template. The handle also answers the up and down arrow keys.
2. **Switch a field off.** Every row gets an on/off switch. A disabled field stays in the template (and keeps its value), but is left out of the container when it is created or updated. Handy for testing: flip a port or variable off, apply, flip it back on later, instead of deleting and re-typing it.

> **Status:** tested on an Unraid 7.4.0-beta.2 server (drag, switch, Apply on an existing container: the disabled variable stayed in the template and was left out of the recreated container), against a copy of the Add Container page's row code in a browser, and against a copy of Unraid's `docker create` builder. Please open an issue if anything looks off.

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

The template keeps the entry, with `Disabled:` in front of its `Type`, for example `Type="Disabled:Port"`. Unraid builds the `docker create` command only from entries of type Path, Port, Label, Variable and Device, so a `Disabled:` entry is skipped, on Apply, on Update from Docker or Community Apps, and on "Update all". Nothing else in the template changes, and the page removes the prefix again when it loads the template, so its own edit dialog never sees it.

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

Every rebuild that is installed on a box needs a new version (a letter suffix is fine, e.g. `2026.09.19b`): Slackware's `upgradepkg` skips a package whose name and version are already installed. Release by committing the stamped `.plg` and uploading the exact built `.txz` to a GitHub release whose tag equals the version.

See [ARCHITECTURE.md](ARCHITECTURE.md) for how it hooks into Unraid.

## License

MIT
