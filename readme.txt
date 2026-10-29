=== REPAF Multi-Step Form ===
Contributors: cbdi-webdev
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.7.1
License: Proprietary

Multi-step Real Estate Purchase Agreement Form for CBDI and KAIA websites.

== Description ==

Render the form with [repaf_form]. Configure the brand, form page, project names
and release repository under Settings > REPAF Form Settings.

Updates are distributed through a configured public GitHub repository.
See RELEASES.md in the source repository for setup and packaging instructions.
Third-party libraries retain their own licenses, included in their directories.

== Installation ==

1. Upload the installable release ZIP through Plugins > Add New > Upload Plugin.
2. Activate REPAF Multi-Step Form.
3. Configure Settings > REPAF Form Settings and add [repaf_form] to your form page.

== Changelog ==

= 1.7.1 =
* Reduce release ZIP size by excluding duplicate legacy artwork and unused TCPDF fonts.
* Preserve both complete brand profiles and the exact original image resolution and quality.
* Verify all six PDF pages, checkmarks and bold text for both CBDI and KAIA from the packaged plugin.

= 1.7.0 =
* Add GitHub release update checks and native WordPress update integration.
* Add a configurable release repository and optional wp-config.php override.
* Require packaged ZIP release assets, excluding development tags and branches.
* Add a release builder that bundles TCPDF and the update checker.
