# REPAF Security Hardening Guide

This guide is the implementation checklist for the critical hardening pass. Keep it close to the plugin root so future AI agents and engineers can resume work without rediscovering the same risks.

## Objective

Protect the public form and generated files from the highest-risk issues:

- Public PDF generation must never be available to anonymous visitors.
- CSV exports must not allow spreadsheet formula execution.
- Rate limiting must stay enabled by default in production.
- Generated PDFs, CSVs, and signatures must not be directly web-accessible.

## Target Files

- `includes/pdf-generator-test.php`
- `includes/plugin-setup.php`
- `includes/csv-generator.php`
- `includes/download-handler.php`
- `includes/rate-limiter.php`
- `includes/admin-notices.php`
- `README.md`

## Checklist

1. Test PDF endpoint
   - Require `WP_DEBUG === true`.
   - Require `current_user_can('manage_options')`.
   - Require a valid `repaf_test_pdf` nonce.
   - Do not localize `repafPdfTest.url` for public visitors.

2. CSV export
   - Before `fputcsv()`, prefix user-controlled cells that start with `=`, `+`, `-`, `@`, tab, or carriage return.
   - Apply the same protection inside flattened financial rows.
   - Preserve the two-column `Field,Value` export shape.

3. Rate limiting
   - Keep rate limiting enabled by default in every environment.
   - Use `REPAF_DISABLE_RATE_LIMIT === true` as the only opt-out.
   - Do not use `WP_DEBUG` to disable throttling.

4. Generated file protection
   - Generated PDF/CSV filenames must include a non-guessable per-submission token.
   - Store server paths in post meta; do not store raw public upload URLs.
   - Apache protection is handled by `.htaccess` and `index.php` files created on activation and refreshed during file generation.
   - Nginx hosts must block:
     - `/wp-content/uploads/repaf-pdf/`
     - `/wp-content/uploads/repaf-csv/`
     - `/wp-content/uploads/repaf-signatures/`
   - Downloads require `manage_repaf_submissions` by default. Public signed downloads require explicitly setting `REPAF_ALLOW_PUBLIC_SIGNED_DOWNLOADS` and should keep a short TTL.

## Validation Steps

- Visit `/?repaf_test_pdf=1` as a public visitor. It must return 403 and must not generate a PDF.
- In debug mode as an administrator, use the localized nonce URL and confirm the test PDF still works.
- Submit CSV values beginning with `=`, `+`, `-`, `@`, tab, and carriage return. Open the CSV and confirm they are text, not formulas.
- Submit repeatedly without `REPAF_DISABLE_RATE_LIMIT`; confirm the rate limit triggers.
- On production-like hosting, request direct URLs under the protected upload folders and confirm they return 403.
- Generate a PDF from the admin submissions panel and confirm the existing admin workflow still works.
- Confirm generated filenames look like `repaf-submission-{id}-{random}.pdf` or `.csv`, not predictable `repaf-submission-{id}.pdf`.

## Rollback Notes

- Reverting the PDF endpoint changes reopens a public CPU-heavy route. Do not rollback unless the route is otherwise removed.
- Reverting CSV escaping can reintroduce spreadsheet formula injection.
- Reverting rate limiting to depend on `WP_DEBUG` can disable throttling by accident in production.
- Reverting Nginx/signature docs does not change Apache behavior, but it can leave Nginx deployments exposed.
- Reverting non-guessable filenames or path-only meta reintroduces predictable raw upload URL risk.

## Do Not Skip

- Do not expose raw upload URLs as trusted public links.
- Do not disable rate limiting through `WP_DEBUG`.
- Do not let the test PDF route run without admin capability and nonce checks.
- Do not treat CSV sanitization as enough; spreadsheet formula neutralization is separate from HTML/PHP sanitization.
