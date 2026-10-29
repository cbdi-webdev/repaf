<?php

namespace Repaf;

/**
 * REPAF Admin Submissions — List View
 */

if (!defined('ABSPATH')) {
    exit;
}

function repaf_admin_query_submissions($paged = 1)
{
    return new \WP_Query(array(
        'post_type'      => 'repaf_submission',
        'post_status'    => 'private',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
}

function repaf_admin_render_list()
{
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $query = repaf_admin_query_submissions($paged);

    $saved     = isset($_GET['saved']) ? absint($_GET['saved']) : 0;
    $csv_error = isset($_GET['csv_error']) ? absint($_GET['csv_error']) : 0;
    $batched   = isset($_GET['batched']) ? absint($_GET['batched']) : 0;

    echo '<div class="wrap repaf-admin-wrap">';
    echo '<h1 class="wp-heading-inline">REPAF Submissions</h1>';

    $today          = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila')))->format('Y-m-d');
    $daily_csv_path = repaf_daily_batch_path($today);

    if (!is_wp_error($daily_csv_path) && is_file($daily_csv_path)) {
        $daily_csv_url = repaf_signed_download_url($daily_csv_path, 'csv');
        echo '<a class="page-title-action" href="' . esc_url($daily_csv_url) . '">Download Daily CSV (' . esc_html($today) . ')</a>';
    }

    if ($csv_error === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--error"><p>Submission saved, but the daily batch CSV could not be updated. Please save the submission again.</p></div>';
    } elseif ($saved === 1 && $batched === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--success"><p>Submission saved and included in its daily reservation CSV.</p></div>';
    } elseif ($saved === 1) {
        echo '<div class="repaf-admin-notice repaf-admin-notice--success"><p>Changes saved as a draft. No daily CSV record was added.</p></div>';
    }

    echo '<div class="repaf-admin-table-wrapper">';
    echo '<table class="repaf-admin-table">';
    echo '<thead><tr>';
    echo '<th>#ID</th>';
    echo '<th>Buyer Name</th>';
    echo '<th>Email</th>';
    echo '<th>Mobile</th>';
    echo '<th>Date Submitted</th>';
    echo '<th>Property Info</th>';
    echo '<th>Daily CSV</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $id         = get_the_ID();
            $meta       = get_post_meta($id);
            $first      = isset($meta['buyer_first_name'][0]) ? $meta['buyer_first_name'][0] : '';
            $last       = isset($meta['buyer_last_name'][0])  ? $meta['buyer_last_name'][0]  : '';
            $email      = isset($meta['buyer_email'][0])      ? $meta['buyer_email'][0]      : '';
            $mobile     = isset($meta['buyer_mobile'][0])     ? $meta['buyer_mobile'][0]     : '';
            $prop       = isset($meta['prop_project'][0])     ? $meta['prop_project'][0]     : '';
            $has_prop   = !empty($prop) ? '<span class="repaf-admin-badge repaf-admin-badge--yes">Yes</span>' : '<span class="repaf-admin-badge">No</span>';
            $batch_date = isset($meta['repaf_batch_date'][0]) ? $meta['repaf_batch_date'][0] : '';
            $csv_status = ($meta['reservation_confirmed'][0] ?? '') === 'yes'
                ? '<span class="repaf-admin-badge repaf-admin-badge--yes">' . esc_html($batch_date) . '</span>'
                : '<span class="repaf-admin-badge">Draft</span>';
            $date       = get_the_date('M j, Y g:i A');

            $edit_url  = add_query_arg(array(
                'repaf_edit'        => $id,
                '_repaf_edit_nonce' => wp_create_nonce('repaf_front_edit_' . $id),
            ), repaf_get_form_page_url());

            $pdf_url   = add_query_arg(array(
                'page'     => 'repaf-submissions',
                'action'   => 'generate_pdf',
                'id'       => $id,
                '_wpnonce' => wp_create_nonce('repaf_genpdf_' . $id),
            ), admin_url('admin.php'));

            echo '<tr>';
            echo '<td data-label="#ID"><span class="repaf-td-val"><span class="repaf-admin-id">#' . esc_html($id) . '</span></span></td>';
            echo '<td data-label="Buyer Name"><span class="repaf-td-val"><strong>' . esc_html(trim($first . ' ' . $last)) . '</strong></span></td>';
            echo '<td data-label="Email"><span class="repaf-td-val">' . esc_html($email) . '</span></td>';
            echo '<td data-label="Mobile"><span class="repaf-td-val">' . esc_html($mobile) . '</span></td>';
            echo '<td data-label="Date Submitted"><span class="repaf-td-val">' . esc_html($date) . '</span></td>';
            echo '<td data-label="Property Info"><span class="repaf-td-val">' . $has_prop . '</span></td>';
            echo '<td data-label="Daily CSV"><span class="repaf-td-val">' . $csv_status . '</span></td>';
            echo '<td data-label="Actions" class="repaf-admin-actions">';
            echo '<a href="' . esc_url($edit_url) . '" class="repaf-admin-btn repaf-admin-btn--edit">Edit</a>';
            echo '<a href="' . esc_url($pdf_url) . '" class="repaf-admin-btn repaf-admin-btn--pdf">Generate PDF</a>';
            echo '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();
    } else {
        echo '<tr><td colspan="8">No submissions found.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>'; // .repaf-admin-table-wrapper

    // Pagination
    $total_pages = $query->max_num_pages;
    if ($total_pages > 1) {
        echo '<div class="repaf-admin-pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $class = ($i === $paged) ? 'button button-primary' : 'button';
            $url   = add_query_arg(array('page' => 'repaf-submissions', 'paged' => $i), admin_url('admin.php'));
            echo '<a href="' . esc_url($url) . '" class="' . $class . '">' . esc_html($i) . '</a> ';
        }
        echo '</div>';
    }

    echo '</div>';
}
