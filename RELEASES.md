# Independent WordPress updates

Canonical source and public releases: https://github.com/cbdi-webdev/repaf

REPAF 1.7.0 bundles Plugin Update Checker 5.7. It checks a configured **public
GitHub repository** approximately every 12 hours and integrates with WordPress's
Plugins screen, version details, manual update checks and optional auto-updates.
WordPress cron and site traffic affect when scheduled checks run.

## One-time setup

1. The dedicated public repository is `https://github.com/cbdi-webdev/repaf`.
   When creating a fresh checkout, copy the **contents** of this plugin
   directory to its root, including `.github/`, `libraries/`, `tools/`, `tests/`,
   `composer.json`, and `composer.lock`. Do not copy the full website, submissions,
   credentials, `vendor/`, or `dist/`. Third-party libraries retain their licenses;
   this does not change the proprietary license of REPAF's own code.
2. Commit and push the plugin sources to `main` in that repository. The release
   workflow nested here is dormant in the website repository; it becomes active
   when `.github/workflows/release.yml` is at the new repository root.
3. Build a bootstrap ZIP locally (PHP with the zip extension is required):

   ```powershell
   # Run inside the plugin directory.
   composer install --no-dev --prefer-dist --no-interaction --no-scripts
   php tests/plugin-updates-test.php
   php tools/build-release.php --repository=https://github.com/cbdi-webdev/repaf
   ```

   The ZIP appears at `dist/repaf-multi-step-form-1.7.0.zip`. It includes TCPDF,
   the update checker, PDFs/artwork and runtime assets, with one top-level
   `repaf-multi-step-form/` directory. Do not use GitHub's automatic source ZIP.
   The builder accepts the existing `vendor/tcpdf/` layout or Composer's
   `vendor/tecnickcom/tcpdf/` layout, and verifies the TCPDF version against the lock.
4. Manually upload that ZIP to each existing site through **Plugins > Add New >
   Upload Plugin** and confirm replacement of the existing plugin. **Do not delete
   the plugin first**: its uninstall script deletes submissions and generated files.
   Take a site backup and verify the first upgrade on a staging copy.
5. Check **Settings > REPAF Form Settings > Plugin Updates** on each site.
   The repository embedded by `--repository` is used by default. Administrators
   can override it with another public GitHub root URL or clear it to disable
   checks. Save settings, then use **Plugins > REPAF > Check for updates**.

The repository can also be pinned in each site's `wp-config.php`, before the
"stop editing" comment:

```php
define('REPAF_UPDATE_REPOSITORY', 'https://github.com/cbdi-webdev/repaf');
```

The constant overrides and locks the settings field; an empty constant disables
checks. For multisite, set this constant so all sites use the same release source.
The source file `includes/update-source.php` defaults to this repository. The builder
embeds the chosen default into the ZIP without modifying that source file or the
current site's database. No GitHub requests are made without a configured source.

The release ZIP includes the existing PDF artwork and TCPDF fonts and can be
larger than a host's default upload limit. If necessary, increase the site's
upload limit or deploy the extracted plugin folder through your hosting tools.

The CBDI website migration removes this plugin from
`scripts/cpanel-code-deploy.sh` and the website workflows' allowlists, and stops
tracking its source in the website repository. The local plugin directory is its
own Git checkout. Commit and deploy those website migration changes to each
website deployment branch so later website deployments cannot overwrite plugin
updates. On the hosting server, commit the new website deployment configuration
before removing the old tracked plugin directory from its website checkout;
retain the installed plugin under the live site's `wp-content/plugins/` directory.

## Publish each subsequent update

1. Make and verify changes in the dedicated plugin repository. Keep site-specific
   branding/project names in WordPress settings; local edits inside the plugin
   directory are replaced on updates.
2. Increase `Version` and `REPAF_VERSION` in `repaf-multi-step-form.php`, plus
   `Stable tag` and the changelog in `readme.txt`. Use stable `X.Y.Z` versions.
3. Commit, then create and push a matching Git tag, for example `v1.7.1`.
4. GitHub Actions tests the updater on PHP 7.4 and 8.4, installs the locked TCPDF
   dependency, builds the ZIP with this repository's URL embedded, and creates a
   **draft** GitHub Release. The GitHub Actions workflow needs contents-write
   permission, granted in its release job.
   To start or retry an existing tag manually, open **Actions > Build REPAF
   release > Run workflow**, select `main`, and enter its existing `vX.Y.Z` tag.
   Pushes to `main` also run the updater tests without creating a release.
5. Review the draft and its ZIP. Install it on staging and verify form submission,
   editing, PDF generation and the correct site's artwork. Publish it as a stable
   release and mark it **latest**. Only then can installed sites discover it.
6. Sites show the normal WordPress update notification. Administrators click
   **Update now**, or may enable WordPress **auto-updates** for REPAF if desired.

To publish manually instead, run the builder, create a release tagged `vX.Y.Z`,
and upload `repaf-multi-step-form-X.Y.Z.zip` as a release asset **before publishing**.
Keep the tag, release ZIP version, plugin header and readme version in sync.

Only published stable GitHub Releases with a matching named ZIP are accepted.
Ordinary commits, tags without releases, draft/prerelease releases and releases
without the packaged asset are ignored. There is no fallback to source archives.
The latest release is the release channel; don't mark an older release as latest.

## Verification and troubleshooting

Run `php tests/plugin-updates-test.php` to check real updater behavior with mocked
HTTP responses and in-memory WordPress state. The test never loads a site's
`wp-config.php`, sends a network request, changes submissions or installs an update.

After publishing a release, `php tests/plugin-updates-test.php --live` makes
read-only public GitHub API requests and simulates an older installed version in
memory to verify native update detection. It never installs the update or
modifies a WordPress database. The ordinary test run stays fully offline.

After building, run
`php tests/release-package-test.php dist/repaf-multi-step-form-X.Y.Z.zip` to inspect
the actual ZIP and generate an in-memory PDF using its bundled TCPDF and artwork.

If no update appears, confirm the repository is public, the release is published
and latest, its ZIP has the exact name above, the remote plugin version is newer,
and the server can make outbound HTTPS requests to GitHub. Manual checks can hit
GitHub rate limits; wait before retrying. Changing the source clears REPAF's old
update state on the next request while preserving other plugins' update entries.

Updating replaces plugin code; stored WordPress options, submissions and generated
files in `wp-content/uploads` stay outside the ZIP. This update adds no database
migration. Keep backups for rollback; the updater only offers newer versions, so
rolling back means manually uploading a previous installable release ZIP.

Public repositories and public release ZIPs are downloadable by anyone. No access
tokens are embedded or needed. The GitHub `Update URI` prevents WordPress.org from
replacing this externally distributed plugin with a similarly named directory
plugin; the bundled checker supplies its own update information.
