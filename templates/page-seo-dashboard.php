<?php
// Simple wrapper that outputs sidebar nav and widget containers
$client = get_query_var('client_id');
$base   = get_permalink();
$links  = [
  'gsc-analysis'  => add_query_arg('client_id', $client, $base . 'gsc-analysis'),
  'ga4-overview'  => add_query_arg('client_id', $client, $base . 'ga4-overview'),
  // ...
];
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
			<select id="scc-client-select" style="max-width:100%; width: 100%; margin-bottom:20px; padding: 5px 10px;">
			  <option value="">--- Choose a client ---</option>
			  <?php
			  global $wpdb;
			  $rows = $wpdb->get_results( "SELECT id, name, site_url FROM {$wpdb->prefix}scc_clients ORDER BY name ASC" );
			  foreach ( $rows as $client ) {
				  printf(
					'<option value="%1$s" data-site="%2$s">%3$s</option>',
					intval($client->id),
					esc_attr($client->site_url),
					esc_html( $client->name )
				  );
				}
			  ?>
			</select>
		</div>
		<ul>
		  <li><a href="#" data-slug="gsc-analysis">GSC Analysis</a></li>
		  <li><a href="#" data-slug="ga4-overview">GA4 Overview</a></li>
		  <li><a href="#" data-slug="ads-overview">Ads Overview</a></li>
		  <li><a href="#" data-slug="moz-overview">Moz Overview</a></li>
		</ul>
	  </nav>
	  <main id="seo-widgets">
		<div id="gsc_overview" class="widget-container"></div>
		<div id="ga4_overview" class="widget-container"></div>
		<div id="ads_overview" class="widget-container"></div>
		<div id="moz_overview" class="widget-container"></div>
	  </main>
	</div>
<?php get_footer(); ?>
</div>
</body>
</html>