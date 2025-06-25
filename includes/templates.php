<?php
// File: includes/templates.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Map each tool slug to its corresponding template file.
 */
$tools = [
    'seo-dashboard' => 'page-seo-dashboard.php',
    'gsc-analysis'  => 'page-gsc-analysis.php',
    'ads-overview'  => 'page-ads-overview.php',
    // add more as 'slug' => 'your-template.php'
];

/**
 * Returns true if the current request matches one of our tool slugs.
 */
function scc_is_tool_page() {
    global $tools;
    foreach ( array_keys( $tools ) as $slug ) {
        $var = 'scc_' . str_replace( '-', '_', $slug );
        if ( '1' === get_query_var( $var ) ) {
            return true;
        }
    }
    return false;
}

/**
 * 1) Register rewrite tags & rules for each tool.
 */
add_action( 'init', function() {
    global $tools;
    foreach ( $tools as $slug => $template ) {
        $var = 'scc_' . str_replace( '-', '_', $slug );
        // %scc_gsc_analysis% query var
        add_rewrite_tag( "%{$var}%", '1' );
        // example: '^gsc-analysis/?' → index.php?scc_gsc_analysis=1
        add_rewrite_rule( "^{$slug}/?$", "index.php?{$var}=1", 'top' );
    }
} );

/**
 * 2) Whitelist our custom query vars (scc_* and client_id).
 */
add_filter( 'query_vars', function( $vars ) {
    global $tools;
    // add scc_<slug> for each tool
    foreach ( array_keys( $tools ) as $slug ) {
        $vars[] = 'scc_' . str_replace( '-', '_', $slug );
    }
    // also allow client_id for context
    $vars[] = 'client_id';
    return $vars;
} );

/**
 * 3) Swap in our plugin template when a tool query var is present.
 */
add_filter( 'template_include', function( $template ) {
    global $tools;
    foreach ( $tools as $slug => $file ) {
        $var = 'scc_' . str_replace( '-', '_', $slug );
        if ( '1' === get_query_var( $var ) ) {
            $path = SCC_PLUGIN_DIR . "templates/{$file}";
            if ( file_exists( $path ) ) {
                return $path;
            }
        }
    }
    return $template;
}, 99 );
