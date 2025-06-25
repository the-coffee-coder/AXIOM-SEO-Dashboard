<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

/**
 * Create wp_scc_clients table.
 */
function scc_create_clients_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'scc_clients';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255) NOT NULL,
      site_url VARCHAR(255) NOT NULL,
      ga4_property_id VARCHAR(50),
      ads_customer_id VARCHAR(50),
	  logo_id INT DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    ) $charset;";     // Each field on its own line, two spaces before PRIMARY KEY
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );   // Safely creates or updates the table structure
}

/**
 * Create wp_scc_gsc_history table.
 */
function scc_create_gsc_history_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'scc_gsc_history';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
      id BIGINT NOT NULL AUTO_INCREMENT,
      client_id INT NOT NULL,
      snapshot_date DATE NOT NULL,
      query VARCHAR(255) DEFAULT '' NOT NULL,
      page_url TEXT,
      device VARCHAR(50) DEFAULT '' NOT NULL,
      clicks INT,
      impressions INT,
      ctr DECIMAL(5,2),
      position DECIMAL(6,3),
      raw_json JSON,
      PRIMARY KEY  (id),
      KEY client_date (client_id, snapshot_date),
      UNIQUE KEY unique_gsc (client_id, snapshot_date, query(191), page_url(191), device)
    ) $charset;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}


// Repeat for GA4, Ads, Moz:

function scc_create_ga4_history_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'scc_ga4_history';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
      id BIGINT NOT NULL AUTO_INCREMENT,
      client_id INT NOT NULL,
      snapshot_date DATE NOT NULL,
      sessions INT,
      users INT,
      new_users INT,
      engagement_rate DECIMAL(5,2),
      pageviews INT,
      events INT,
      raw_json JSON,
      PRIMARY KEY  (id),
      KEY client_date (client_id, snapshot_date)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function scc_create_ads_history_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'scc_ads_history';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
      id BIGINT NOT NULL AUTO_INCREMENT,
      client_id INT NOT NULL,
      snapshot_date DATE NOT NULL,
      campaign_id VARCHAR(50),
      campaign_name VARCHAR(255),
      ad_group_id VARCHAR(50),
      ad_group_name VARCHAR(255),
      impressions BIGINT,
      clicks BIGINT,
      cost_micros BIGINT,
      conversions INT,
      raw_json JSON,
      PRIMARY KEY  (id),
      KEY client_date (client_id, snapshot_date)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function scc_create_moz_history_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'scc_moz_history';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
      id BIGINT NOT NULL AUTO_INCREMENT,
      client_id INT NOT NULL,
      snapshot_date DATE NOT NULL,
      domain_authority DECIMAL(5,2),
      page_authority DECIMAL(5,2),
      external_links INT,
      linking_domains INT,
      raw_json JSON,
      PRIMARY KEY  (id),
      KEY client_date (client_id, snapshot_date)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
