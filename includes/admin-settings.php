<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings and API credential fields
 */
add_action( 'admin_menu', 'scc_register_admin_menu' );
add_action( 'admin_init', 'scc_register_settings' );

function scc_register_admin_menu() {
    // Top-level SEO Dashboard
    add_menu_page(
        'SEO Dashboard',       // Page title
        'SEO Dashboard',       // Menu title
        'manage_options',      // Capability
        'scc_dashboard',       // Menu slug
        'scc_render_overview', // Callback
        'dashicons-chart-pie', // Dashicon
        50                     // Position
    );
    // Submenu: Clients
    add_submenu_page(
        'scc_dashboard',
        'Clients',
        'Clients',
        'manage_options',
        'scc_clients',
        'scc_render_clients'   // Blank stub
    );
    // Submenu: API Settings
    add_submenu_page(
        'scc_dashboard',
        'API Settings',
        'API Settings',
        'manage_options',
        'scc_settings',
        'scc_options_page_html' // Reuse your existing settings callback
    );
}

// Blank stub callbacks
function scc_render_overview() {
    echo '<div class="wrap"><h1>SEO Dashboard</h1><p>Overview dashboard will appear here.</p></div>';
}

function scc_register_settings() {
    register_setting( 'scc_settings_group', 'scc_settings', 'scc_sanitize_settings' );

    add_settings_section(
        'scc_api_section',
        'API Credentials',
        'scc_api_section_cb',
        'scc-settings'
    );

    $fields = [
        'scc_gsc_api_key'     => 'Google Search Console API Key',
        'scc_ga4_api_secret'  => 'GA4 API Secret',
        'scc_ads_dev_token'   => 'Google Ads Developer Token',
        'scc_ads_client_id'   => 'Google Ads Client ID',
        'scc_moz_api_token'   => 'Moz API Token',
    ];

    foreach ( $fields as $id => $label ) {
        add_settings_field(
            $id,
            $label,
            'scc_field_callback',
            'scc-settings',
            'scc_api_section',
            [ 'label_for' => $id ]
        );
    }
}

function scc_api_section_cb() {
    echo '<p>Enter your API credentials for each service below.</p>';
}

function scc_field_callback( $args ) {
    $options = get_option( 'scc_settings', [] );
    $id      = $args['label_for'];
    printf(
        '<input type="text" id="%1$s" name="scc_settings[%1$s]" value="%2$s" class="regular-text" />',
        esc_attr( $id ),
        isset( $options[ $id ] ) ? esc_attr( $options[ $id ] ) : ''
    );
}

function scc_sanitize_settings( $input ) {
    // Basic sanitization: trim all values
    $sanitized = [];
    foreach ( $input as $key => $value ) {
        $sanitized[ $key ] = sanitize_text_field( $value );
    }
    return $sanitized;
}

function scc_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>SEO Command Center Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'scc_settings_group' );
            do_settings_sections( 'scc-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
