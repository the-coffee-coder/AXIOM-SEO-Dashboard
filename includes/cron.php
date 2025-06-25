<?php
// File: includes/cron.php
if ( ! defined('ABSPATH') ) exit;

// Hook the action to our handler
add_action( SCC_GSC_CRON_HOOK, 'scc_handle_daily_gsc_fetch' );

/**
 * Fetch last 3 days GSC data for every client and save to DB, checking for duplicates.
 */
function scc_handle_daily_gsc_fetch() {
    global $wpdb;
    $clients = $wpdb->get_results("SELECT id, site_url FROM {$wpdb->prefix}scc_clients");
    // Date range: yesterday to yesterday
    $end   = date('Y-m-d', strtotime('-1 day'));
    $start = date('Y-m-d', strtotime('-3 day'));
    $range = ['startDate'=>$start,'endDate'=>$end];

    foreach ( $clients as $c ) {
        try {
            $rows = scc_fetch_gsc_data([ $c->site_url ], $range )[ $c->site_url ] ?? [];
        } catch ( Exception $e ) {
            error_log("SCC GSC cron error for client {$c->id}: ".$e->getMessage());
            continue;
        }
        // Insert each row
        foreach ( $rows as $row ) {
			$wpdb->query( $wpdb->prepare("
				INSERT INTO {$wpdb->prefix}scc_gsc_history
				  (client_id, snapshot_date, query, page_url, device, clicks, impressions, ctr, position, raw_json)
				VALUES (%d,%s,%s,%s,%s,%d,%d,%f,%f,%s)
				ON DUPLICATE KEY UPDATE
				  clicks      = VALUES(clicks),
				  impressions = VALUES(impressions),
				  ctr         = VALUES(ctr),
				  position    = VALUES(position),
				  raw_json    = VALUES(raw_json)
			",
			  $c->id,
			  $range['startDate'],             // or $yesterday in your cron
			  sanitize_text_field($row['keys'][1] ?? ''),
			  esc_url_raw($row['keys'][2] ?? ''),
			  sanitize_text_field($row['keys'][3] ?? ''),
			  intval($row['clicks']),
			  intval($row['impressions']),
			  floatval($row['ctr']),
			  floatval($row['position']),
			  wp_json_encode($row)
			) );
		}

    }
}
