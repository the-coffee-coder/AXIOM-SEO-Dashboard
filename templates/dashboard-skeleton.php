<?php
// Set up navigation links (single source of truth)
$client = get_query_var('client_id');
$base   = get_permalink();
$links  = [
    'gsc-analysis'  => add_query_arg('client_id', $client, $base . 'gsc-analysis'),
    'ga4-overview'  => add_query_arg('client_id', $client, $base . 'ga4-overview'),
    'ads-overview'  => add_query_arg('client_id', $client, $base . 'ads-overview'),
    'moz-overview'  => add_query_arg('client_id', $client, $base . 'moz-overview'),
];
$end_date   = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-29 days')); // includes today
// The $main_content variable should be set by the including script
?>
<!DOCTYPE html>
<html>
<head>
    <?php wp_head(); ?>
</head>
<body id="body" <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="site-container" class="site-container">
    <?php get_header(); ?>
    <div id="seo-dashboard">
        <nav id="seo-nav">
            <div id="client-selector">
                <label for="scc-client-select" style="font-weight:600; margin-right:8px;">Select Client:</label>
                <select id="scc-client-select" style="max-width:100%; width: 100%; margin-bottom:10px; padding: 5px 10px;">
                    <option value="">--- Choose a client ---</option>
                    <?php
                    global $wpdb;
                    $rows = $wpdb->get_results("SELECT id, name, site_url FROM {$wpdb->prefix}scc_clients ORDER BY name ASC");
                    foreach ($rows as $client_obj) {
                        printf(
                            '<option value="%1$s" data-site="%2$s">%3$s</option>',
                            intval($client_obj->id),
                            esc_attr($client_obj->site_url),
                            esc_html($client_obj->name)
                        );
                    }
                    ?>
                </select>
                <!-- Date Range Field -->
            </div>
			<div id="date-range-container" style="margin-bottom: 20px;">
				<label for="date-range" style="font-weight: 600; margin-right: 8px;">Date Range:</label>
				<input type="date" id="date-range-start" name="date-range-start" value="<?php echo esc_attr($start_date); ?>" style="margin-right: 4px;">
				to
				<input type="date" id="date-range-end" name="date-range-end" value="<?php echo esc_attr($end_date); ?>" style="margin-left: 4px;">
			</div>
            <ul>
                <?php foreach ($links as $slug => $url): ?>
                    <li><a href="<?php echo esc_url($url); ?>" data-slug="<?php echo esc_attr($slug); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $slug))); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <!-- Main content injected here -->
        <?php echo $main_content; ?>
    </div>
    <?php get_footer(); ?>
</div>
</body>
</html>