<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render REPAF shortcode.
 *
 * Detects salesperson edit mode via URL params and loads existing
 * post meta so every field in the form can be pre-populated.
 */
function repaf_render_form_shortcode()
{
    // Detect edit mode
    $repaf_edit_mode    = false;
    $repaf_edit_post_id = 0;
    $repaf_edit_data    = array();

    if (
        isset($_GET['repaf_edit'], $_GET['_repaf_edit_nonce']) &&
        current_user_can('manage_repaf_submissions')
    ) {
        $candidate = absint($_GET['repaf_edit']);
        if (
            $candidate > 0 &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_GET['_repaf_edit_nonce'])),
                'repaf_front_edit_' . $candidate
            )
        ) {
            $post = get_post($candidate);
            if ($post && $post->post_type === 'repaf_submission') {
                $repaf_edit_mode    = true;
                $repaf_edit_post_id = $candidate;
                $raw_meta           = get_post_meta($candidate);
                foreach ($raw_meta as $key => $values) {
                    $repaf_edit_data[$key] = maybe_unserialize($values[0] ?? '');
                }
            }
        }
    }

    // Expose edit data to field-renderer.php (which is a function, not a template include)
    $GLOBALS['_repaf_edit_data'] = $repaf_edit_mode ? $repaf_edit_data : array();
    $GLOBALS['_repaf_edit_mode'] = $repaf_edit_mode;

    ob_start();

    echo repaf_render_status_notice();

    // $repaf_edit_mode, $repaf_edit_post_id, $repaf_edit_data flow into
    // form-wrapper.php and from there into each step template via include scope.
    include REPAF_PLUGIN_PATH . 'templates/form-wrapper.php';

    return ob_get_clean();
}
add_shortcode('repaf_form', __NAMESPACE__ . '\\repaf_render_form_shortcode');
