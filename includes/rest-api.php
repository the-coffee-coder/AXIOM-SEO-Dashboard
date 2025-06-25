<?php
// File: includes/rest-api.php
if ( ! defined('ABSPATH') ) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'scc/v1', '/gsc-summary', [
        'methods'             => 'GET',
        'callback'            => 'scc_get_gsc_summary_db',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ] );
} );

/**
 * Returns current and prior 30-day GSC summaries plus directional flags.
 */
function scc_get_gsc_summary_db( WP_REST_Request $req ) {
    global $wpdb;
    $cid = intval( $req->get_param('client_id') );
    if ( ! $cid ) {
        return new WP_Error( 'missing_client', 'Missing client_id', ['status'=>400] );
    }

    // Define current and prior periods
    $end_current   = date( 'Y-m-d', strtotime('-1 day') );
    $start_current = date( 'Y-m-d', strtotime('-30 days') );
    $end_prior     = date( 'Y-m-d', strtotime('-31 days') );
    $start_prior   = date( 'Y-m-d', strtotime('-60 days') );

    // Helper to sum metrics
    $get_metrics = function( $start, $end ) use ( $wpdb, $cid ) {
        $row = $wpdb->get_row( $wpdb->prepare("
            SELECT
              SUM(impressions) AS impressions,
              SUM(clicks)      AS clicks,
              AVG(ctr)         AS avg_ctr,
              AVG(position)    AS avg_pos
            FROM {$wpdb->prefix}scc_gsc_history
            WHERE client_id = %d
              AND snapshot_date BETWEEN %s AND %s
        ", $cid, $start, $end ), ARRAY_A );

        return [
            'impressions' => intval(   $row['impressions']  ?? 0 ),
            'clicks'      => intval(   $row['clicks']       ?? 0 ),
            'ctr'         => round( (float)($row['avg_ctr']  ?? 0), 2 ),
            'position'    => round( (float)($row['avg_pos']  ?? 0), 2 ),
        ];
    };

    // Fetch both periods
    $current = $get_metrics( $start_current, $end_current );
    $prior   = $get_metrics( $start_prior,   $end_prior   );

    // Compute percent changes safely
    $compute_change = function( $cur, $prev ) {
        if ( $prev === 0 ) {
            return null; // undefined if no prior data
        }
        return round( ( $cur - $prev ) / $prev * 100, 2 );
    };

    $impr_diff = $compute_change( $current['impressions'], $prior['impressions'] );
    $click_diff= $compute_change( $current['clicks'],      $prior['clicks']      );
    $ctr_diff  = $compute_change( $current['ctr'],         $prior['ctr']         );
    // For position, a drop is “good”—flip the sign so positive = improvement
    $pos_diff  = $compute_change( $prior['position'],       $current['position']  );

    return rest_ensure_response([
        'current' => [
          'startDate'   => $start_current,
          'endDate'     => $end_current,
          'impressions' => $current['impressions'],
          'clicks'      => $current['clicks'],
          'ctr'         => $current['ctr'],
          'position'    => $current['position'],
        ],
        'change'  => [
          'impressions_diff_pct' => $impr_diff,
          'impressions_is_up'    => $impr_diff >= 0,
          'clicks_diff_pct'      => $click_diff,
          'clicks_is_up'         => $click_diff >= 0,
          'ctr_diff_pct'         => $ctr_diff,
          'ctr_is_up'            => $ctr_diff >= 0,
          'position_diff_pct'    => $pos_diff,
          'position_is_up'       => $pos_diff >= 0,
        ],
    ]);
}
