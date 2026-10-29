# REPAF Multi-Step Form

A WordPress plugin that renders a multi-step Real Estate Purchase Agreement Form (REPAF) via the `[repaf_form]` shortcode. The workflow is split between the buyer and a salesperson:

1. **Buyer** fills steps 1–7 in the requested personal-to-financial sequence. Steps 8–9 (property and salesperson info) are visible but locked.
2. **Salesperson** logs into the WordPress admin, opens the submission from the REPAF Submissions panel, clicks **Edit**, and is taken to the same front-end form with all buyer data pre-filled and steps 8–9 fully editable.
3. **PDF** is generated on demand from the admin panel — not automatically on buyer submission.

Buyer submissions are stored as private WordPress records for the salesperson workflow. When a salesperson/admin first saves a completed reservation, it is assigned to a Philippine-time daily batch. The plugin rebuilds one tabular CSV named `repaf-reservations-YYYY-MM-DD.csv`, with one reservation per row, so later edits refresh the row instead of creating duplicate files. Confirmations through the 6:30 PM cutoff enter that day's file; later confirmations enter the following day's batch. CSV columns use the supplied CIS buyer/property descriptions, followed by separate `co_borrower_*`, `attorney_in_fact_*`, and `spouse_*` column groups. Missing REPAF inputs remain blank. The three character references are flattened into individually named columns. Structured names preserve four fixed comma-separated positions as `Last_name, First_name, Middle_name, Suffix`, and `reservation_id` is the final export column. The completed reservation PDF remains available on demand.

---

## Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher
- `ext-curl` enabled (required by TCPDF)
- An SMTP plugin for reliable email delivery (see below)

---

## Installation

For independent updates across multiple websites, see [RELEASES.md](RELEASES.md).
Use an installable release ZIP, which bundles TCPDF and the update checker.

1. Upload the `repaf-multi-step-form` folder to `wp-content/plugins/`.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Create a page and add `[repaf_form]` to its content.
4. Go to **Settings → REPAF Form Settings** and select that page from the **REPAF Form Page** dropdown. This is required for the Edit button in the admin panel to link to the correct URL.
5. Optionally configure the color theme on the same settings page.

> **Note:** Plugin activation automatically creates the `repaf_salesperson` role and adds the `manage_repaf_submissions` capability to administrators. If you activated the plugin before this was implemented, deactivate and reactivate once to create the role.

> **Composer:** Installable release ZIPs already include TCPDF. For development from source, run `composer install --no-dev --prefer-dist` before building. The legacy bundled `vendor/tcpdf/` layout is also supported.

---

## User Roles

### repaf_salesperson

A custom WordPress role created on plugin activation. Users with this role:

- See only **REPAF Submissions** in the WP admin (all other menus are stripped).
- Are redirected to the submissions list on login instead of the dashboard.
- Can view, edit, and generate PDFs for all submissions.
- Cannot access any other WordPress admin area.

To create a salesperson account: **Users → Add New**, set the role to **REPAF Salesperson**.

---

## Salesperson Edit Flow

1. In the REPAF Submissions list, the salesperson clicks **Edit** next to a submission.
2. A time-limited signed URL opens the front-end form (`?repaf_edit={id}&_repaf_edit_nonce={nonce}`).
3. All buyer data (steps 1–7) is pre-filled and editable.
4. Steps 8 (Property Information) and 9 (Salesperson Information) are now enabled.
5. The salesperson fills in property details and salesperson fields. **Save Changes** alone keeps the record out of the CSV; selecting **Confirm this as a real reservation and add it to the daily CSV** enrolls it in the batch.
6. Once confirmed, later saves update the existing row under the same submission ID. The salesperson is redirected back to the admin submissions list, where the Daily CSV column shows Draft or the assigned batch date.

---

## SMTP Configuration (Required for Email Delivery)

WordPress's default `wp_mail()` uses PHP's `sendmail`, which is blocked or spam-filtered on virtually every managed host. Without SMTP, confirmation emails will silently fail.

**Recommended:** Install [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) and configure it with your mail provider (Gmail, SendGrid, Mailgun, etc.).

Once installed, test delivery from **WP Mail SMTP → Tools → Email Test**.

---

## Nginx Server Configuration (Required Before Production on Nginx Hosts)

The plugin writes `.htaccess` files to protect generated PDFs, CSVs, and signatures from direct access. **Apache reads these automatically. Nginx does not.**

On any Nginx-based host (WP Engine, Kinsta, Cloudways, Laravel Forge, etc.) add the following blocks inside your `server {}` config:

```nginx
# REPAF — block direct access to generated files
location ~* /wp-content/uploads/repaf-pdf/ {
    deny all;
    return 403;
}

location ~* /wp-content/uploads/repaf-csv/ {
    deny all;
    return 403;
}

location ~* /wp-content/uploads/repaf-signatures/ {
    deny all;
    return 403;
}
```

After adding, reload Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

On managed hosts, open a support ticket with the blocks above and ask them to add the rules to your site's server config.

Do not expose direct upload URLs as customer-facing download links. Generated PDFs use non-guessable filenames; daily CSVs use the agreed date-based filename and remain protected inside `repaf-csv`. File paths are stored as server paths, not public URLs. The signed download handler requires `manage_repaf_submissions` by default. If a site explicitly needs public signed links, set `REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS` in `wp-config.php` and keep `REPAF_DOWNLOAD_TTL` short.

### Daily CSV integration

Deploy plugin version 1.6.5 or newer, including `includes/rest-api.php`, to each website used by the downloader. The authenticated endpoint is `GET /wp-json/repaf/v1/daily-csv?date=YYYY-MM-DD`. Use a WordPress username and its Application Password; the user must have `manage_repaf_submissions` (administrators and REPAF salespeople receive this capability). The date selects the saved batch date, and an empty batch returns a CSV with headers only.

The Windows downloader is provided in `integration/Download-RepafReservations.ps1`. Copy it to your automation server and retain your existing site URLs, credential XML files, and `processed_ids.txt`. It checks yesterday and today, preserves `source_site` second-to-last and `reservation_id` last, and reports failed downloads with a nonzero exit code. WordPress error `rest_no_route` means the endpoint is missing on the website serving that URL; uploading a CSV alone does not register the endpoint.

Payment columns follow the character-reference columns in this order: `Financing Scheme`, `Reservation Date`, `Reservation Fee`, `DP Terms`, `Interest Rate`, `Down Payment`, `First Down Payment`, `Loanable Amount`, `Total Contract Price`, `VAT`, `Miscellaneous Fee`, `tcp_discount`, `dp_discount`, `others_discount`, then the final `reservation_id`. `DP Terms` is the number of months; `Interest Rate` is the indicative percentage (for example, `6.5` means 6.5%). `Down Payment` is the total down-payment amount, and `First Down Payment` is the DP Starts On due date. The three discount columns contain the independently saved Full TCP, Full DP, and Other discount amounts, not percentages. Reservation dates use the salesperson's saved day/month/year as `YYYY-MM-DD`, rather than the batch date. Missing values remain blank. The downloader carries these columns through automatically; deploy the same plugin schema to both sites before combining their CSVs.

`Financing Scheme` exports the import-system ID: `4` for Spot Cash, `16` for Deferred Cash, or, when Payment Type is With Financing, `3` for Bank, `8` for In-House, and `10` for HDMF. Financing Others or missing/unsupported selections stay blank because no corresponding ID was supplied. The website form and stored payment/financing labels retain their existing text values.

---

## LiteSpeed Cache (Staging / Production)

If LiteSpeed Cache is active with **CSS Combine External** enabled, it will bundle `repaf-form.css` into a combined file and may serve a stale version after CSS updates. To prevent this:

- Go to **LiteSpeed Cache → Page Optimization → CSS Settings** and disable **CSS Combine External and Inline**.
- Or after any CSS update, go to **LiteSpeed Cache → Toolbox** and purge the CSS/JS optimization cache (separate from the page cache purge).

---

## CSS Asset Caching

The plugin serves `assets/css/repaf-form.min.css` when that file exists, falling back to `repaf-form.css`. **Always keep both files in sync.** After editing `repaf-form.css`, copy it over the minified version:

```bash
cp assets/css/repaf-form.css assets/css/repaf-form.min.css
```

The `REPAF_VERSION` constant in `repaf-multi-step-form.php` is appended as a query string (`?ver=x.x.x`) to bust browser caches. Bump the version whenever CSS, JS, or security-sensitive generated-file behavior changes.

---

## Local Development

Add the following to `wp-config.php` to disable rate limiting during testing:

```php
define('REPAF_DISABLE_RATE_LIMIT', true);
```

Do not set `REPAF_DISABLE_RATE_LIMIT` on staging or production. Rate limiting is enabled by default and no longer depends on `WP_DEBUG`.

The test PDF endpoint (`?repaf_test_pdf=1`) is available only when `WP_DEBUG` is true, the requester is an administrator, and the URL includes a valid nonce generated by the plugin.

Generated file downloads are admin/salesperson-only by default:

```php
define('REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS', false);
define('REPAF_DOWNLOAD_TTL', 2 * HOUR_IN_SECONDS);
```

Only enable public signed downloads when there is a clear operational need.

To override rate limit settings without touching plugin files, add to `wp-config.php`:

```php
define('REPAF_RATE_LIMIT_MAX', 5);       // max submissions per window
define('REPAF_RATE_LIMIT_WINDOW', 600);  // window in seconds (10 min)
```

---

## File Structure

```
repaf-multi-step-form/
├── repaf-multi-step-form.php        Main plugin file, constants, require chain
├── composer.json                    Dependency manifest
├── uninstall.php                    Role removal and cleanup on plugin deletion
│
├── assets/
│   ├── css/
│   │   ├── repaf-form.css           Front-end form styles (source of truth)
│   │   ├── repaf-form.min.css       Served preferentially — keep in sync with .css
│   │   └── repaf-admin.css          Admin submissions panel styles
│   ├── js/
│   │   ├── repaf-form.js            Multi-step wizard logic, financial totals, edit mode
│   │   ├── repaf-form.min.js        Minified version (keep in sync)
│   │   └── repaf-admin.js           Admin panel tab switching and conditional fields
│   └── pdf/
│       ├── Group 82.jpg             PDF background — buyer info pages
│       ├── Group 85.jpg
│       ├── Group 87.jpg
│       ├── Group 89.jpg
│       ├── Group 90.jpg
│       └── Group 92.jpg             PDF background — property/computation page
│
├── includes/
│   ├── plugin-setup.php             Activation hook, CPT registration, role creation,
│   │                                asset enqueue, admin notice for missing settings
│   ├── admin-settings.php           Settings page (form page dropdown, color theme),
│   │                                repaf_get_form_page_url() helper
│   ├── admin-notices.php            Persistent notices (e.g. Nginx config warning)
│   ├── admin-submissions.php        Admin menu, asset enqueue (with theme color CSS vars),
│   │                                login/dashboard redirect for salesperson role,
│   │                                strip menus hook
│   ├── admin-submissions-list.php   Paginated submissions table with Edit + PDF buttons
│   ├── admin-submissions-edit.php   Two-tab edit form (Customer Info / Property Info)
│   ├── admin-submissions-save.php   POST handler for admin edit form,
│   │                                repaf_sanitize_property_info()
│   ├── admin-submissions-download.php  PDF stream-download interceptor
│   ├── form-render.php              [repaf_form] shortcode, edit mode detection,
│   │                                sets $GLOBALS['_repaf_edit_data'] / ['_repaf_edit_mode']
│   ├── form-handler.php             Submission processing — edit mode branch (updates meta,
│   │                                redirects to admin list) + new submission branch
│   ├── field-renderer.php           repaf_render_field(), repaf_render_radio_group()
│   │                                (pre-fills from globals in edit mode)
│   ├── field-config.php             Field definitions and required-field list
│   ├── sanitizer.php                repaf_sanitize_submission() — cleans all buyer fields
│   ├── validator.php                repaf_get_missing_required_fields()
│   ├── helpers.php                  Shared utility functions
│   ├── rate-limiter.php             Submission rate limiting
│   ├── response-helper.php          repaf_redirect_with_status()
│   ├── mailer.php                   Buyer and admin confirmation emails
│   ├── pdf-generator.php            Entry point — repaf_generate_buyer_pdf()
│   ├── pdf-generator-data.php       Data preparation for PDF
│   ├── pdf-generator-helpers.php    TCPDF drawing helpers
│   ├── pdf-generator-pages.php      Per-page rendering functions
│   ├── pdf-generator-test.php       Test PDF endpoint
│   ├── pdf-mapper.php               PDF coordinate maps for all fields
│   ├── csv-generator.php            Date-named daily reservation CSV batches
│   └── download-handler.php         Signed time-limited download URL system
│
├── templates/
│   ├── form-wrapper.php             Form shell, progress indicators (10 steps),
│   │                                hidden edit fields, step includes
│   ├── step-1-buyer.php             Buyer personal information shell
│   ├── step-2-address.php           Buyer address partial included in step 1
│   ├── step-3-spouse.php            Spouse information (shown when civil status = married)
│   ├── step-4-employment.php        Buyer work and occupation details
│   ├── step-5-representative.php    Attorney-in-fact / authorized representative
│   ├── step-6-co-owner.php          Co-owner personal, employment, and representative details
│   ├── step-7-co-owner-additional.php  Co-owner spouse information
│   ├── step-8-financial.php         Monthly income table, bank accounts, loans,
│   │                                character references (dynamic rows, pre-filled in edit)
│   ├── step-9-property.php          Property details, mode of payment, bank charges,
│   │                                in-house financing + insurance (conditional on In-House),
│   │                                computation with auto-derived fields (disabled for buyers,
│   │                                enabled + pre-filled for salesperson edit)
│   ├── step-10-salesperson.php      Salesperson info — Sales Team + Remarks (disabled for buyers)
│   └── step-7-review.php            Review and confirm (step 11 in edit mode)
│
└── vendor/
    └── tcpdf/                       Bundled TCPDF library
```

---

## Settings

**Settings → REPAF Form Settings**

| Setting | Description |
|---|---|
| Website / PDF Brand | Select CBDI or KAIA for this WordPress installation. Auto detects `cbdi`, `citihomes`, `mycitihomes`, or `kaia` from the site identity. |
| REPAF Form Page | The WordPress page containing `[repaf_form]`. Used to build the Edit URL in the admin panel. Must be set before the Edit button works. |
| Primary Color | Main brand color used throughout the form and admin panel. |
| Primary Dark | Darker shade for hover states and headings. |
| Primary Soft | Light tint used for backgrounds and highlights. |
| Success Color | Color for success states. |
| Border Color | Color for borders and dividers. |

PDF artwork is isolated under `assets/pdf/profiles/cbdi/` and `assets/pdf/profiles/kaia/`. Update only the applicable profile directory when a form revision belongs to one website. A profile-specific `coordinates.json` in the same directory can override mapper coordinates without changing the other website. For deployment-level locking, define `REPAF_SITE_PROFILE` as `cbdi` or `kaia` in that website's `wp-config.php`.

---

## Form Steps

| Step | Template | Visible to | Notes |
|---|---|---|---|
| 1 | step-1-buyer.php + step-2-address.php | Buyer + Salesperson | Buyer personal information, address, and co-owner declaration |
| 2 | step-3-spouse.php | Buyer + Salesperson | Spouse information; shown only when married |
| 3 | step-6-co-owner.php | Buyer + Salesperson | Co-owner personal, employment, and representative information |
| 4 | step-7-co-owner-additional.php | Buyer + Salesperson | Co-owner spouse information; shown only when applicable |
| 5 | step-5-representative.php | Buyer + Salesperson | Attorney-in-fact / authorized representative |
| 6 | step-4-employment.php | Buyer + Salesperson | Buyer work and occupation only |
| 7 | step-8-financial.php | Buyer + Salesperson | Monthly income, accounts, loans, and references |
| 8 | step-9-property.php | Salesperson only (edit) | Disabled for buyers |
| 9 | step-10-salesperson.php | Salesperson only (edit) | Disabled for buyers |
| 10 | step-7-review.php | Buyer + Salesperson | Review and submit |

The wizard has 10 numbered screens. In buyer mode, salesperson-only steps 8–9 remain visible but locked; in edit mode they are fully editable.

---

## Changelog

### 1.7.0

- Add configurable GitHub release checks, WordPress update notifications and optional auto-updates.
- Bundle Plugin Update Checker 5.7 and require complete release ZIPs instead of source archives.
- Add locked TCPDF release builds, packaging verification and a dedicated-repository GitHub release workflow. See [RELEASES.md](RELEASES.md).

### 1.6.9
- Changed Full TCP, Full DP, and Others discount selectors to independent checkboxes in the wizard and admin editor.
- Save and restore multiple discount selections, support existing single-selection records, and clear all selections without retaining an old choice.
- Mark every selected discount in CBDI/KAIA PDFs while preserving the three independent CSV discount amounts and existing manual pricing inputs.

### 1.6.8
- Required the buyer's permanent/present address lines and the specified reservation, property, financing, DP schedule, and loanable amount fields in salesperson/admin edits.
- Enforced shared required-field checks before saving or refreshing CSVs; buyer submissions still leave salesperson-only property fields disabled.
- Made the reservation date picker authoritative over hidden date components, including when cleared, and reveal required admin fields in hidden tabs/collapsed sections for browser validation.
- Kept barangay, ZIP code, employment/business years, and the company-information exemption for Others optional.

### 1.6.7
- Added financing scheme, reservation date/fee, DP terms, interest rate, down payment, first DP due date, loanable amount, TCP, VAT, and miscellaneous fee to daily CSV exports.
- Export financing scheme IDs: Bank `3`, Spot Cash `4`, In-House `8`, HDMF `10`, Deferred Cash `16`; With Financing uses the selected financing option.
- Added `tcp_discount`, `dp_discount`, and `others_discount` from the saved discount amounts immediately before the final `reservation_id` column.
- Preserved existing columns and name formatting, with `reservation_id` still last; reservation dates use validated saved date parts and interest rates support the legacy saved field.

### 1.6.6
- Added last name, first name, optional middle name, and optional suffix inputs for each of the three character references in the wizard and admin editor.
- Export character-reference names using the buyer's fixed `Last_name, First_name, Middle_name, Suffix` CSV format while retaining their full-name source for PDFs.
- Preserve legacy full names without guessing their parts. Edit and save these references using the separate name inputs to convert them; existing comma-separated names are normalized automatically.

### 1.6.5
- Added an authenticated `GET /wp-json/repaf/v1/daily-csv?date=YYYY-MM-DD` endpoint for application-password integrations.
- Rebuilds the requested batch before returning it as raw CSV and requires the `manage_repaf_submissions` capability.
- Added the integration downloader with HTTP/WordPress error diagnostics and failure exit codes so failed downloads are not reported as an empty successful run.

### 1.6.4
- Changed every structured buyer, co-borrower, attorney-in-fact, and spouse name to fixed `Last, First, Middle, Suffix` positions.
- Restored `reservation_id` and placed it as the final daily CSV column.

### 1.6.3
- Split co-borrower, attorney-in-fact, and spouse data into separate uniquely prefixed CSV column groups.
- Flattened all three character references into dedicated name, phone number, email address, and present address columns.

### 1.6.2
- Replaced the form-input CSV schema with the supplied ordered CIS import descriptions.
- Kept CIS-only fields as blank columns and formatted combined names as `Last_name, First_name Middle_name Suffix`.
- Rebuild daily batches before download so files created under an older schema are refreshed automatically.

### 1.6.1
- Updated the final Review step to display the active site's profile-specific Terms and Conditions artwork instead of the legacy hard-coded KAIA image.

### 1.6.0
- Added isolated CBDI and KAIA PDF-template profiles so both websites can use the same plugin without overwriting each other's branding.
- Added a per-website Website / PDF Brand setting with automatic detection for CBDI/CitiHomes and KAIA installations.
- Made PDF metadata, template paths, and optional coordinate JSON overrides profile-aware.

### 1.5.25
- Made every company-information field optional when a buyer, spouse, or co-owner selects Others as the employment status; the Other Employment Status description remains required.

### 1.5.24
- Imported the supplied latest REPAF plugin package as the working baseline.
- Replaced PDF page 4 with the supplied Fourth Page (3) artwork and verified its property and computation overlays.
- Preserved the newer Group 83 Terms and Conditions page from the current workspace.

### 1.5.23
- Replaced PDF page 4 with the latest supplied property/computation artwork.
- Added separate Full TCP, Full DP, and Other discount amount inputs and mapped each amount to its matching PDF row.

### 1.5.22
- Replaced active PDF pages 2 and 3 with the latest supplied artwork, converted to TCPDF-compatible RGB templates, and remapped their personal, work, business, and financial overlays.

### 1.5.21
- Made Barangay and Zip Code optional across all personal, representative, co-owner, present, permanent, and work/business address sections.

### 1.5.20
- Made No. of Years Employed and No. of Years in Business optional while preserving their saved values, CSV columns, and PDF mapping when supplied.

### 1.5.19
- Replaced the page 2 and page 3 PDF artwork with the revised REPAF layouts.
- Remapped co-owner, spouse, attorney-in-fact, employment, business, office contact, and financial fields to the new artwork.
- Moved shared work/business information to page 2 and employee position/contract details to page 3.

### 1.5.18
- Made Date Employed optional for employee, OFW, and Other employment statuses.
- Added conditional No. of Years in Business and No. of Years Employed fields for buyer, spouse, and co-owner.
- Added office number, office email address, and office mobile number fields for each work-information section.
- Removed the Profession field and its unused PSOC form integration.
- Added TCP Discount Amount to property information and the daily CSV schema.

### 1.5.17
- Separated ordinary edits from reservation confirmation so draft corrections do not create daily CSV rows.
- Added an explicit salesperson confirmation checkbox before a reservation enters the batch.
- Show Draft or the assigned batch date in the REPAF Submissions list.
- Excluded edit IDs, nonces, and the confirmation control from stored submission metadata.

### 1.5.16
- Replaced per-submission two-column CSV files with one tabular daily reservation batch.
- Named batches `repaf-reservations-YYYY-MM-DD.csv`, using a 6:30 PM Asia/Manila cutoff.
- Rebuild the assigned batch after later edits so each submission ID appears only once.
- Added a protected daily CSV download action to the REPAF Submissions screen.
- Standardized the 270 export headers to the approved form input names and order.

### 1.5.15
- Stopped generating incomplete CSV files from initial buyer submissions.
- Generate or refresh the integration CSV only after an authorized salesperson/admin saves the reservation.
- Export the complete saved buyer, property, financial, and salesperson dataset while excluding internal file and email-queue metadata.

### 1.2.20
- Realigned buyer and co-owner role, gender, civil-status, home-ownership, and occupation checkmarks with the revised PDF circles.
- Realigned buyer-spouse and co-owner-spouse occupation checkmarks on both updated PDF pages.

### 1.2.19
- Hidden Employer's Email Address for self-employed buyers and co-owners and cleared it from self-employed submissions.

### 1.2.18
- Hidden years employed, contract status, position level, and position/department for self-employed buyers and co-owners.
- Disabled and cleared hidden employment-only values in both the browser and server-side submission sanitizer.
- Made buyer position fields conditionally required only for non-self-employed occupations.

### 1.2.17
- Restored the complete company/business address fields for buyer and co-owner employment details.
- Updated both first-page PDF backgrounds and remapped all affected buyer, co-owner, spouse, and representative fields.
- Added employment-address values to submission review, admin editing, and PDF mapping tests.

### 1.2.16
- Replaced the buyer and co-owner PDF backgrounds with the revised employment tables.
- Mapped Business/Company Type and Date of Business Established on both PDF pages.
- Added matching self-employed-only business fields for the co-owner employment form, review, validation, and admin editor.
- Realigned the existing employment values to the revised rows and cell widths.

### 1.2.15
- Formatted PSOC profession names in readable title case while preserving common abbreviations.
- Changed the employer label to Company Name for locally employed buyers and OFWs.

### 1.2.14
- Changed the self-employed buyer employer label to Business Name.
- Added self-employed-only Business/Company Type and Date of Business Established fields with client- and server-side validation.
- Removed the successful PSOC profession-count helper message while retaining loading and error feedback.

### 1.2.13
- Corrected representative birthdate digit spacing and vertical alignment on both buyer and co-owner PDF pages.

### 1.2.12
- Added adaptive PDF text fitting so long values wrap onto compact lines and remain inside their assigned cells.
- Defined cell widths across buyer, spouse, co-owner, and representative details and corrected spouse contact/employment vertical alignment.

### 1.2.11
- Changed the buyer and co-owner PSOC Profession fields from searchable text inputs to native dropdowns populated with the complete cached PSOC profession list.

### 1.2.10
- Removed Unit No. / Building No. / Phase from buyer and co-owner employment details.
- Made the buyer employment subsection heading respond to occupation: Business Information for self-employed applicants, Company Information for locally employed applicants and OFWs, and Employment / Business Information for Others or no selection.
- Replaced the buyer and co-owner PDF backgrounds with the newly aligned artwork and remapped employment, spouse, and representative fields to the revised tables.

### 1.2.9
- Added a server-side PSOC profession lookup for buyer and co-owner employment, with the API token stored only in WordPress settings and free-text fallback when PSA is unavailable.
- Removed Block / Lot, Subdivision, Street, Barangay, and Zip Code from both employment sections.
- Moved Country, Province / State, and City / Municipality directly after Profession in the form, review screen, and admin editor.

### 1.2.8
- Added a mandatory Privacy Policy and Data Privacy Policy consent modal before customers can access the form.
- Added keyboard focus trapping, mobile styling, and buyer-mode-only gating while leaving salesperson edit mode unchanged.
- Added server-side consent validation plus a stored UTC consent timestamp and notice version for each new submission.

### 1.2.7
- Added Profession text inputs plus Position / Level and Employment Contract Status dropdowns for buyer and co-owner employment.
- Updated the buyer and co-owner PDF backgrounds and remapped both pages to the newly aligned layout.
- Added the new employment fields to review, validation, admin editing, and PDF generation.

### 1.2.6
- Added a fixed, searchable Nature of Business / Industry list for buyer and co-owner employment fields.
- Added matching server-side allowlist validation and dropdowns in the admin submission editor.
- No external industry API or network dependency is required.

### 1.2.5
- Moved co-owner employment details into the co-owner / co-borrower / financier step.
- Restored the co-owner authorized representative / attorney-in-fact fields and their conditional validation.
- Kept the work and occupation step limited to buyer fields.

### 1.2.4
- Reordered the buyer form and updated its headings to match the approved sequence.
- Combined buyer address details with personal information.
- Remapped conditional navigation, review edit links, and salesperson-only steps to the new numbering.

### 1.2.2
- **Generated file hardening**: PDF/CSV filenames now include a per-submission random token instead of predictable IDs.
- **No raw upload URL meta**: Generated files are stored as server paths (`repaf_pdf_path`, `repaf_csv_path`) and stale URL meta is removed when regenerated.
- **Legacy metadata cleanup**: Existing `repaf_pdf_url` and `repaf_csv_url` meta keys are removed by a one-time security upgrade task.
- **Download hardening**: Signed downloads now require `manage_repaf_submissions` by default; public signed downloads require an explicit opt-in constant. Default TTL reduced to 2 hours.
- **Direct access defense**: Protected upload directories now get `Options -Indexes` and `index.php` guards, refreshed during CSV/PDF generation.
- **Debug route hardening**: The test PDF endpoint streams files directly instead of redirecting to an upload URL.
- **Production cleanup**: Removed the development-only PDF coordinate generator script.
- **TLS check**: Upload-protection probe now uses SSL verification.
- **Asset versioning**: `REPAF_VERSION` bumped to `1.2.2`.

### 1.2.1
- **Fix loanable formula**: Loanable Amount now correctly derived as TCP − DP Amount (was TCP − Balance on DP). % Balance / Loanable follows from that.
- **Remove number input spinners**: CSS hides up/down arrows on all `type="number"` inputs in the form and admin panel (Chrome, Safari, Firefox).
- **Step 9 field removals**: Removed Price per sqm, House Model, and the general Starts On / Ends On date pair from Property Details — these fields are not present in the physical reservation form.
- **In-House Financing section**: Moved to the very bottom of step 9 and made always visible (was previously conditional on `prop_financing = In-House`).
- **Asset versioning**: `REPAF_VERSION` bumped to `1.2.1`.

### 1.2.0
- **Performance**: TCPDF library lazy-loaded — only required inside `repaf_generate_buyer_pdf()`, not on every WordPress page load.
- **Performance**: Form submission no longer blocks on SMTP — `fastcgi_finish_request()` (or output-buffer flush fallback) flushes the redirect to the browser before emails are sent, so the user sees the success screen immediately.
- **Money inputs**: All monetary fields on steps 8 and 9 converted from `type="number"` to `type="text" inputmode="decimal"` with comma-formatting (e.g. `1,000,000.00`). Avoids browser silently returning `""` for comma-formatted values.
- **Financial step auto-calc**: `recalcFinancialTotals()` now uses `parseMoney()` / `formatMoney()` helpers so income totals compute correctly with comma-formatted values. `attachMoneyFormatter()` wired to all static money inputs.
- **Property step auto-computation** (step 9): Added `recalcPropertyFields()` — a single computation chain that derives every dependent field from its source inputs:
  - Misc. Fee (₱) = Total Selling Price × Misc. Fee %
  - VAT is opt-in through a VATable checkbox; the user enters the VAT percentage and the VAT amount is computed automatically
  - Total Contract Price is entered manually and drives all downstream payment computations
  - DP Amount = TCP × DP %
  - Balance on DP = DP Amount − Reservation Fee
  - Monthly DP = Balance on DP ÷ No. of Months
  - DP Ends On = DP Starts On + No. of Months (date arithmetic)
  - Loanable Amount = TCP − DP Amount (non-editable)
  - Monthly Amortization uses the Excel PMT formula from interest rate, loan terms, and loanable amount
  - % Balance / Loanable = (Loanable ÷ TCP) × 100 (non-editable)
- **Misc. Fee as percentage**: Replaced the free-entry Misc. Fee field with a Misc. Fee % input; the peso value is auto-computed and non-editable.
- **Remove Add field**: Removed `prop_add_amount` (Add ₱) from the Computation section.
- **Remove Insurance Amount field**: Removed the computed Insurance Amount field from both the front-end template and admin edit view (MRI and Fire Insurance inputs remain).
- **In-House Financing section** (step 9): New conditional fieldset shown only when `prop_financing = In-House`. Fields: Monthly Amortization Amount, Loan Terms (years), Interest Rate / Indicative (%), Starts On.
- **Insurance conditional**: Insurance fieldset (MRI, Fire Insurance) now shown only when `prop_financing = In-House`.
- **Step 9 conditional visibility**: Added `updatePropertyConditionals()` to `repaf-form.js` — reads all `[data-show-when]` attributes in step 9 and toggles `.is-visible`. Also fixes the previously non-functional "Others" conditional inputs for Place of Reservation, Product Type, and Mode of Payment.
- **Approval section removed** (step 10): Removed the Approval fieldset (Prepared By, Verified By, Checked By, Approved By) from the Salesperson Information step and the admin edit view, per supervisor guidance (signatures to be collected in person).
- **Admin panel auto-compute**: `repaf-admin.js` mirrors all step 9 computation logic — Misc. Fee, VAT, TCP, DP, Balance on DP, Loanable Amount, % Loanable, and DP Ends On all recompute live in the admin edit form.
- **Asset versioning**: `REPAF_VERSION` bumped to `1.2.0`.

### 1.1.0
- **Admin panel**: New REPAF Submissions page accessible to `repaf_salesperson` role and administrators. Shows paginated submissions list with Edit and Generate PDF actions. Mobile card layout with theme color CSS variables.
- **Salesperson role**: Custom `repaf_salesperson` WordPress role. Gets stripped admin menu, dashboard redirect, and login redirect to submissions list.
- **Front-end edit mode**: Salesperson clicks Edit → opens `[repaf_form]` page with all buyer data pre-filled and steps 9–10 enabled. Signed nonce URL (`?repaf_edit=&_repaf_edit_nonce=`). Saves via existing form POST handler (edit branch).
- **Property Information step** (step 9): All `prop_*` fields — property details, mode of payment, bank charges, insurance, computation. Disabled for buyers, enabled in edit mode.
- **Salesperson Information step** (step 10): Sign-off fields (`sp_agent_name`, `sp_manager_name`, etc.). Disabled for buyers, enabled in edit mode.
- **Edit mode pre-fill**: `$GLOBALS['_repaf_edit_data']` / `['_repaf_edit_mode']` pattern passes saved meta to templates and `repaf_render_field()` / `repaf_render_radio_group()` so all fields restore correctly.
- **Financial step pre-fill**: Static income fields and dynamic bank/loan/ref rows pre-populated from saved meta via PHP loop.
- **Bug fix**: `has_representative` / `has_co_owner` radio buttons not restoring in edit mode.
- **Bug fix**: `has_co_owner` always saved as `yes` in salesperson edit mode due to `array_merge` override order.
- **Bug fix**: `<fieldset disabled>` on steps 9–10 prevented buyers from clicking the Next button. Removed; individual inputs carry the `disabled` attribute directly.
- **Mobile responsive**: Financial step (income table card layout, dynamic rows stack) at ≤640px. General form spacing tightened at ≤700px. Fixed `flex-basis:200px` becoming min-height in column flex mode.
- **Settings**: Form page dropdown + color theme settings. `repaf_get_form_page_url()` helper.
- **Asset versioning**: `REPAF_VERSION` bumped to `1.1.0` for cache busting.

### 1.0.0
- Initial release
