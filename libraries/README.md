# Bundled update checker

`plugin-update-checker/` contains the unmodified runtime files of
[Plugin Update Checker v5.7](https://github.com/YahnisElsts/plugin-update-checker/tree/v5.7),
commit `275a96a2a18d03c34c87f35cb68673c8c49ac3b1`.

Upstream author: Janis Elsts. License: MIT; see `plugin-update-checker/license.txt`.
Runtime includes its bundled Markdown and WordPress readme parsers and their licenses.
Upstream build tools, examples and repository metadata are omitted.
`vendor/Parsedown-LICENSE.txt` restores the license referenced by Parsedown's
header, from https://github.com/erusev/parsedown/blob/1.6.0/LICENSE.txt.

REPAF pins the versioned v5p7 factory to avoid depending on another plugin's copy.
Do not edit this library to customize REPAF; use `includes/plugin-updates.php`.
