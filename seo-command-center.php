<?php
/**
 * Plugin Name: SEO Command Center
 * Description: Transportable SEO dashboard with multiple tool pages.
 * Version: 0.2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;


// Define plugin constants
define( 'SCC_VERSION',     '0.2.0' );
define( 'SCC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SCC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define('SCC_GSC_CRON_HOOK', 'scc_daily_gsc_fetch');

// Include modules
require_once SCC_PLUGIN_DIR . 'includes/admin-settings.php';
require_once SCC_PLUGIN_DIR . 'includes/rest-api.php';
require_once SCC_PLUGIN_DIR . 'includes/templates.php';
require_once SCC_PLUGIN_DIR . 'includes/db-tables.php';
require_once SCC_PLUGIN_DIR . 'includes/class-scc-clients-list.php';
require_once SCC_PLUGIN_DIR . 'includes/admin-clients.php';
require_once SCC_PLUGIN_DIR . 'includes/gsc-api.php';
require_once SCC_PLUGIN_DIR . 'includes/cron.php';

// 1) Make sure your init hook that calls add_rewrite_rule() runs on every request
add_action( 'init', function() use ( $tools ) {
    foreach ( array_keys( $tools ) as $slug ) {
        $var = 'scc_' . str_replace( '-', '_', $slug );
        add_rewrite_tag( "%{$var}%", '1' );
        add_rewrite_rule( "^{$slug}/?$", "index.php?{$var}=1", 'top' );
    }
});

// 2) On activation: register your rules again, then flush
register_activation_hook( __FILE__, function() use ( $tools ) {
    // Re-register the same rules so dbDelta sees them
    foreach ( array_keys( $tools ) as $slug ) {
        $var = 'scc_' . str_replace( '-', '_', $slug );
        add_rewrite_tag( "%{$var}%", '1' );
        add_rewrite_rule( "^{$slug}/?$", "index.php?{$var}=1", 'top' );
    }
    // Flush WP’s rewrite rules to persist them
    flush_rewrite_rules();
});

// On activation, create all tables
register_activation_hook( __FILE__, 'scc_activate' );
function scc_activate() {
    scc_create_clients_table();         // Creates wp_scc_clients :contentReference[oaicite:3]{index=3}
    scc_create_gsc_history_table();     // Creates wp_scc_gsc_history :contentReference[oaicite:4]{index=4}
    scc_create_ga4_history_table();     // Creates wp_scc_ga4_history
    scc_create_ads_history_table();     // Creates wp_scc_ads_history
    scc_create_moz_history_table();     // Creates wp_scc_moz_history
    flush_rewrite_rules();              // Ensures any rewrite rules are refreshed :contentReference[oaicite:5]{index=5}
}

// On deactivation, flush rewrite rules
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Schedule our daily fetch when the plugin is activated
register_activation_hook( __FILE__, 'scc_schedule_daily_fetch' );
// Clear it on deactivation
register_deactivation_hook( __FILE__, 'scc_clear_daily_fetch' );

/**
 * Schedules the daily GSC fetch.
 */
function scc_schedule_daily_fetch() {
  if ( ! wp_next_scheduled( 'scc_daily_gsc_fetch' ) ) {
    wp_schedule_event( strtotime( '00:10 tomorrow' ), 'daily', 'scc_daily_gsc_fetch' );
  }
}

/**
 * Clears the scheduled hook.
 */
function scc_clear_daily_fetch() {
  wp_clear_scheduled_hook( 'scc_daily_gsc_fetch' );
}

// Enqueue master CSS/JS for all dashboard/tool pages
add_action( 'wp_enqueue_scripts', function() {
    if ( function_exists('scc_is_tool_page') && scc_is_tool_page() ) {
        // Single CSS/JS bundle for all tool pages
        wp_enqueue_style(  'scc-dashboard', SCC_PLUGIN_URL . 'public/css/dashboard.css', [], SCC_VERSION );
        wp_enqueue_script( 'scc-dashboard', SCC_PLUGIN_URL . 'public/js/dashboard.js', ['jquery'], SCC_VERSION, true );
		
		$default_site = esc_url_raw( 'https://jagoehomes.com/' );
        wp_localize_script('scc-dashboard', 'SCC_API', [
            'root'  => esc_url_raw( rest_url('scc/v1/') ),
            'nonce' => wp_create_nonce('wp_rest'),
			'client_id' => get_query_var('client_id'),
        ] );
    }
}, 20 );

function enqueue_gsc_analysis_js() {
    if (is_page_template('page-gsc-analysis.php')) {
        wp_enqueue_script('dashboard-gsc-analysis', get_template_directory_uri().'/public/js/dashboard-gsc-analysis.js', ['jquery'], null, true);
        wp_localize_script('dashboard-gsc-analysis', 'ajaxurl', admin_url('admin-ajax.php'));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_gsc_analysis_js');