<?php

namespace Repaf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the authenticated daily CSV endpoint used by the integration
 * downloader. WordPress Application Passwords authenticate the Basic Auth
 * credentials before the permission callback runs.
 */
function repaf_register_rest_routes()
{
    register_rest_route(
        'repaf/v1',
        '/daily-csv',
        array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => __NAMESPACE__ . '\\repaf_rest_daily_csv',
            'permission_callback' => static function () {
                return current_user_can('manage_repaf_submissions');
            },
            'args'                => array(
                'date' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => static function ($value) {
                        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                            return false;
                        }

                        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

                        return $date && $date->format('Y-m-d') === $value;
                    },
                ),
            ),
        )
    );
}
add_action('rest_api_init', __NAMESPACE__ . '\\repaf_register_rest_routes');

/**
 * Rebuild and return one protected daily batch.
 *
 * @param  \WP_REST_Request $request REST request.
 * @return \WP_REST_Response|\WP_Error
 */
function repaf_rest_daily_csv($request)
{
    $batch_date = (string) $request->get_param('date');
    $csv_path   = repaf_rebuild_daily_batch_csv($batch_date);

    if (is_wp_error($csv_path)) {
        return new \WP_Error(
            'repaf_csv_generation_failed',
            $csv_path->get_error_message(),
            array('status' => 500)
        );
    }

    if (!is_file($csv_path) || !is_readable($csv_path)) {
        return new \WP_Error(
            'repaf_csv_not_found',
            'The requested daily CSV could not be generated.',
            array('status' => 404)
        );
    }

    $contents = file_get_contents($csv_path);

    if ($contents === false) {
        return new \WP_Error(
            'repaf_csv_read_failed',
            'The requested daily CSV could not be read.',
            array('status' => 500)
        );
    }

    $response = new \WP_REST_Response($contents, 200);
    $response->header('Content-Type', 'text/csv; charset=utf-8');
    $response->header('Content-Disposition', 'attachment; filename="' . basename($csv_path) . '"');
    $response->header('Content-Length', (string) strlen($contents));
    $response->header('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    $response->header('X-Content-Type-Options', 'nosniff');

    return $response;
}

/**
 * Serve successful daily CSV responses as raw CSV instead of JSON-encoding
 * the response string through the normal REST output handler.
 *
 * @param  bool             $served  Whether the response was already served.
 * @param  \WP_HTTP_Response $result Response object.
 * @param  \WP_REST_Request $request REST request.
 * @return bool
 */
function repaf_serve_rest_csv($served, $result, $request)
{
    if (
        $served ||
        $request->get_route() !== '/repaf/v1/daily-csv' ||
        !($result instanceof \WP_REST_Response) ||
        $result->get_status() !== 200
    ) {
        return $served;
    }

    echo (string) $result->get_data();

    return true;
}
add_filter('rest_pre_serve_request', __NAMESPACE__ . '\\repaf_serve_rest_csv', 10, 3);
