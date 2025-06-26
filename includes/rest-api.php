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


add_action('wp_ajax_gsc_analysis_data', 'gsc_analysis_data_ajax');
add_action('wp_ajax_nopriv_gsc_analysis_data', 'gsc_analysis_data_ajax');

function gsc_analysis_data_ajax() {
    global $wpdb;

    $client_id = intval($_POST['client_id']);
    $device = sanitize_text_field($_POST['device']);
    $date_start = sanitize_text_field($_POST['date_start']);
    $date_end = sanitize_text_field($_POST['date_end']);
	
	$search = isset($_POST['search']) ? $_POST['search'] : '';
	$exclude = isset($_POST['exclude']) ? $_POST['exclude'] : '';

	$search_terms = array_filter(array_map('trim', explode(',', strtolower($search))));
	$exclude_terms = array_filter(array_map('trim', explode(',', strtolower($exclude))));

	$device_sql = '';
	if ($device == 'mobile' || $device == 'desktop') {
		$device_sql = $wpdb->prepare("AND device = %s", $device);
	}
	$where = $wpdb->prepare("client_id = %d AND snapshot_date BETWEEN %s AND %s", $client_id, $date_start, $date_end);
	$where .= $device_sql;

	$sql = "SELECT query, SUM(clicks) as clicks, SUM(impressions) as impressions, AVG(ctr) as ctr, AVG(position) as position
			FROM {$wpdb->prefix}scc_gsc_history
			WHERE $where
			GROUP BY query";
	$rows = $wpdb->get_results($sql);

	// Filter for search and exclude
	$filtered = [];
	foreach ($rows as $row) {
		$kw = strtolower($row->query);

		// Search: include if any term is found, or if search is empty
		$include = empty($search_terms) || array_reduce($search_terms, function($carry, $term) use ($kw) {
			return $carry || ($term && strpos($kw, $term) !== false);
		}, false);

		// Exclude: exclude if any term is found
		$is_excluded = !empty($exclude_terms) && array_reduce($exclude_terms, function($carry, $term) use ($kw) {
			return $carry || ($term && strpos($kw, $term) !== false);
		}, false);

		if ($include && !$is_excluded) {
			$filtered[] = $row;
		}
	}

    // Stats for all
    $total_clicks = $total_impr = $ctr_sum = $pos_sum = 0;
    foreach ($rows as $r) {
        $total_clicks += $r->clicks;
        $total_impr += $r->impressions;
        $ctr_sum += $r->ctr;
        $pos_sum += $r->position;
    }
    $all_stats = [
        'unique' => count($rows),
        'clicks' => $total_clicks,
        'impressions' => $total_impr,
        'ctr' => count($rows) ? $ctr_sum / count($rows) : 0,
        'position' => count($rows) ? $pos_sum / count($rows) : 0,
    ];

    // Stats for filtered/highlighted
    $f_clicks = $f_impr = $f_ctr_sum = $f_pos_sum = 0;
    foreach ($filtered as $r) {
        $f_clicks += $r->clicks;
        $f_impr += $r->impressions;
        $f_ctr_sum += $r->ctr;
        $f_pos_sum += $r->position;
    }
    $filtered_stats = [
        'unique' => count($filtered),
        'clicks' => $f_clicks,
        'impressions' => $f_impr,
        'ctr' => count($filtered) ? $f_ctr_sum / count($filtered) : 0,
        'position' => count($filtered) ? $f_pos_sum / count($filtered) : 0,
    ];

    // Buckets for all
    $buckets = [0,0,0,0,0,0]; // 1-3, 4-7, 8-10, 11-20, 21-50, 51+
    foreach ($rows as $r) {
        if ($r->position <= 3) $buckets[0]++;
        else if ($r->position <= 7) $buckets[1]++;
        else if ($r->position <= 10) $buckets[2]++;
        else if ($r->position <= 20) $buckets[3]++;
        else if ($r->position <= 50) $buckets[4]++;
        else $buckets[5]++;
    }
    // Buckets for highlighted
    $buckets_high = [0,0,0,0,0,0];
    foreach ($filtered as $r) {
        if ($r->position <= 3) $buckets_high[0]++;
        else if ($r->position <= 7) $buckets_high[1]++;
        else if ($r->position <= 10) $buckets_high[2]++;
        else if ($r->position <= 20) $buckets_high[3]++;
        else if ($r->position <= 50) $buckets_high[4]++;
        else $buckets_high[5]++;
    }

    wp_send_json([
        'data' => $rows,
        'filtered' => $filtered,
        'all_stats' => $all_stats,
        'filtered_stats' => $filtered_stats,
        'buckets' => $buckets,
        'buckets_high' => $buckets_high,
    ]);
}