# Docker Template Fields

## 2026.11.24

- **Rows can now be moved between "Show more settings" and the main list.** Drag a row's grip from one list into the other, or press the up or down arrow key at the end of a list to carry the row across (the other list has to be open). The row's Display is changed to match (Always or Advanced, keeping any "Hide Buttons" setting), so it is saved that way and stays put the next time you open the template. Before, a row could only move within the list it started in, so important rows that Unraid had placed under "Show more settings" were stuck there (reported by kintrupf).

## 2026.09.25

- Fixed installing this plugin removing another plugin's package. Slackware's `upgradepkg` reduced this package's name to `unraid-docker`, the same name it reduces `unraid-docker-folders-modern` to, so at every boot this plugin's install replaced (and so removed) Docker Folders Modern. The package is now installed with `installpkg` after removing only this plugin's own older version. The fix was already in the source, but 2026.09.20 was released without it, so installed copies never received it: this version is what delivers it.

## 2026.09.20

- The plugin now names the same update URL that its Community Apps listing uses (raw.githubusercontent.com), so Community Apps recognises an installed copy and shows it as installed. No other change.

## 2026.09.19

First release.

- Drag to reorder the Path, Port, Variable, Label and Device rows on the Add/Edit Container page, including when installing an app from Community Apps. The order you leave them in is the order saved in the template. Rows can also be moved with the up and down arrow keys while the handle is focused.
- An on/off switch on every row. A disabled field stays in the template, but is left out of the container when it is created or updated, so you can test without deleting anything.
- Settings > Utilities > Docker Template Fields can switch either feature off. With both off the plugin adds nothing to the page.
