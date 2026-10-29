<?php

/**
 * Plugin Name: REPAF Multi-Step Form
 * Description: Multi-step REPAF shortcode form for WordPress.
 * Version: 1.7.1
 * Author: CBDI / KAIA
 * Plugin URI: https://github.com/cbdi-webdev/repaf
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/cbdi-webdev/repaf
 */

if (!defined('ABSPATH')) {
    exit;
}

define('REPAF_VERSION', '1.7.1');
define('REPAF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('REPAF_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once REPAF_PLUGIN_PATH . 'includes/plugin-updates.php';

/**
 * =========================================================
 * Load TCPDF Library
 * =========================================================
 *
 * PDF generation loads TCPDF on demand from the bundled vendor folder.
 * Release builds include the dependency pinned in composer.lock, so
 * installing/updating through WordPress does not require Composer.
 */
require_once REPAF_PLUGIN_PATH . 'includes/site-profile.php';
require_once REPAF_PLUGIN_PATH . 'includes/field-renderer.php';
require_once REPAF_PLUGIN_PATH . 'includes/field-config.php';
require_once REPAF_PLUGIN_PATH . 'includes/address-api.php';
require_once REPAF_PLUGIN_PATH . 'includes/sanitizer.php';
require_once REPAF_PLUGIN_PATH . 'includes/validator.php';
require_once REPAF_PLUGIN_PATH . 'includes/csv-generator.php';
require_once REPAF_PLUGIN_PATH . 'includes/rest-api.php';
require_once REPAF_PLUGIN_PATH . 'includes/response-helper.php';
require_once REPAF_PLUGIN_PATH . 'includes/rate-limiter.php';
require_once REPAF_PLUGIN_PATH . 'includes/mailer.php';
require_once REPAF_PLUGIN_PATH . 'includes/email-queue.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-notices.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-mapper.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-mapper-updated.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-generator-data.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-generator-helpers.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-generator-pages.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-generator.php';
require_once REPAF_PLUGIN_PATH . 'includes/pdf-generator-test.php';
require_once REPAF_PLUGIN_PATH . 'includes/form-render.php';
require_once REPAF_PLUGIN_PATH . 'includes/form-handler.php';
require_once REPAF_PLUGIN_PATH . 'includes/download-handler.php';
require_once REPAF_PLUGIN_PATH . 'includes/plugin-setup.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-settings.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-submissions-list.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-submissions-edit.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-submissions-save.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-submissions-download.php';
require_once REPAF_PLUGIN_PATH . 'includes/admin-submissions.php';

/**
 * =========================================================
 * Plugin Activation
 * =========================================================
 * Creates upload subdirectories and writes .htaccess files
 * to block direct public access to generated files.
 *
 * The functions below live in the Repaf namespace (defined
 * in the include files). This main plugin file stays in
 * global scope — PHP requires the namespace declaration to
 * be the very first statement in a file, which is
 * incompatible with the plugin header comment and the
 * ABSPATH check above. We reference namespaced functions
 * using their fully-qualified name: Repaf\function_name().
 */
register_activation_hook(__FILE__, 'Repaf\repaf_on_activation');
register_deactivation_hook(__FILE__, 'Repaf\repaf_on_deactivation');

/**
 * =========================================================
 * Admin notices
 * =========================================================
 * Warn when upload directories are not protected on Nginx.
 */
add_action('admin_notices', 'Repaf\repaf_nginx_notice');
