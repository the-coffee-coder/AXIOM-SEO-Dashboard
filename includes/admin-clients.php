<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Only load on our Clients page
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'scc_clients' ) {
        wp_enqueue_media();
    }
} );

add_action( 'admin_init', 'scc_handle_client_actions' );
function scc_handle_client_actions() {
	global $wpdb;

	$table = $wpdb->prefix . 'scc_clients';

	// Delete
	if ( isset($_GET['action'], $_GET['id']) && $_GET['action']==='delete' && check_admin_referer('scc_delete_client') ) {
		$wpdb->delete( $table, ['id'=> intval($_GET['id'])] );
		wp_redirect( admin_url('admin.php?page=scc_clients') );
		exit;
	}

	// Insert / Update
	if ( isset($_POST['scc_client_nonce']) && wp_verify_nonce($_POST['scc_client_nonce'], 'scc_save_client') ) {
		$data = [
			'name'            => sanitize_text_field( $_POST['name'] ),
			'site_url'        => esc_url_raw( $_POST['site_url'] ),
			'ga4_property_id' => sanitize_text_field( $_POST['ga4_property_id'] ),
			'ads_customer_id' => sanitize_text_field( $_POST['ads_customer_id'] ),
			'logo_id'         => intval( $_POST['logo_id'] ),
			'updated_at'      => current_time('mysql')
		];
		if ( ! empty( $_POST['id'] ) ) {
			$wpdb->update( $table, $data, ['id'=>intval($_POST['id'])] );
		} else {
			$data['created_at'] = current_time('mysql');
			$wpdb->insert( $table, $data );
		}
		wp_redirect( admin_url('admin.php?page=scc_clients') );
		exit;
	}
}

function scc_render_clients() {
	// If we just fetched history, show a success notice
    if ( isset($_GET['history_fetched']) && intval($_GET['history_fetched']) === 1 ) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>✅ GSC history imported successfully for client ID '. esc_html($_GET['client_id']) .'.</p>';
        echo '</div>';
    }
	global $wpdb;
	// Detect add/edit
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$editing = $id>0;
	$row = $editing ? $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}scc_clients WHERE id=%d", $id) ) : null;

	// If no action param, show table
	if ( ! isset($_GET['action']) ) {
		$table = new SCC_Clients_List();
		$table->prepare_items();
		echo '<div class="wrap"><h1>Clients <a href="'.admin_url('admin.php?page=scc_clients&action=add').'" class="page-title-action">Add New</a></h1>';
		$table->display();
		echo '</div>';
		return;
	}

	// Add/Edit form
	?>
	<div class="wrap">
	  <h1><?php echo $editing ? 'Edit' : 'Add'; ?> Client</h1>
	  <form method="post">
	    <?php wp_nonce_field('scc_save_client','scc_client_nonce'); ?>
	    <?php if($editing): ?>
	      <input type="hidden" name="id" value="<?php echo $row->id; ?>">
	    <?php endif; ?>
	    <table class="form-table">
	      <tr>
	        <th><label for="name">Name</label></th>
	        <td><input type="text" name="name" id="name" value="<?php echo esc_attr($row->name ?? ''); ?>" class="regular-text" required></td>
	      </tr>
	      <tr>
	        <th><label for="site_url">Site URL</label></th>
	        <td><input type="url" name="site_url" id="site_url" value="<?php echo esc_attr($row->site_url ?? ''); ?>" class="regular-text"></td>
	      </tr>
	      <tr>
	        <th><label for="ga4_property_id">GA4 Property ID</label></th>
	        <td><input type="text" name="ga4_property_id" id="ga4_property_id" value="<?php echo esc_attr($row->ga4_property_id ?? ''); ?>" class="regular-text"></td>
	      </tr>
	      <tr>
	        <th><label for="ads_customer_id">Ads Customer ID</label></th>
	        <td><input type="text" name="ads_customer_id" id="ads_customer_id" value="<?php echo esc_attr($row->ads_customer_id ?? ''); ?>" class="regular-text"></td>
	      </tr>
	      <tr>
            <th><label for="logo_id">Client Logo</label></th>
            <td>
              <div id="logo_preview">
                <?php if ( ! empty( $row->logo_id ) ) {
                  echo wp_get_attachment_image( $row->logo_id, 'thumbnail' );
                } ?>
              </div>
              <input type="hidden" name="logo_id" id="logo_id" value="<?php echo esc_attr( $row->logo_id ?? '' ); ?>">
              <button type="button" class="button" id="upload_logo_button">Upload Logo</button>
            </td>
          </tr>
	    </table>
	    <?php submit_button( $editing ? 'Update Client' : 'Add Client' ); ?>
	  </form>
	</div>
	<script>
	jQuery(function($){
  $('#upload_logo_button').on('click', function(e){
    e.preventDefault();

    var frame = wp.media({
      title: 'Select Client Logo',
      button: { text: 'Use this Logo' },
      library: { type: 'image' },
      multiple: false
    });

    frame.on('select', function(){
      var attachment = frame.state().get('selection').first().toJSON();
      // set the hidden field
      $('#logo_id').val(attachment.id);
      // show the thumbnail in our preview div
      $('#logo_preview').html(
        $('<img>').attr('src', attachment.sizes.thumbnail.url).css({
          marginRight: '10px',
          maxWidth: '100px',
          height: 'auto'
        })
      );
    });

    frame.open();
  });
});

	</script>
	<?php
}

add_action( 'wp_ajax_scc_fetch_history', function(){
    if ( empty($_GET['client_id']) ) wp_die('No client ID');
    $cid = intval($_GET['client_id']);
    check_admin_referer( 'scc_fetch_history_'.$cid );

    // 1 year back
    $end   = date('Y-m-d', strtotime('-30 day'));
    $start = date('Y-m-d', strtotime('-61 day'));

    // Get client URL
    global $wpdb;
    $site = $wpdb->get_var( $wpdb->prepare(
        "SELECT site_url FROM {$wpdb->prefix}scc_clients WHERE id=%d", $cid
    ) );
    if ( ! $site ) wp_die('Invalid client');

    // Fetch and insert just like cron handler
    $rows = scc_fetch_gsc_data([ $site ], ['startDate'=>$start,'endDate'=>$end ])[ $site ] ?? [];
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
		  $cid,
		  $row['keys'][0],             // or $yesterday in your cron
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
	
	$redirect = add_query_arg(
      ['page'            => 'scc_clients',
       'history_fetched' => 1,
       'client_id'       => $cid],
      admin_url('admin.php')
    );
    wp_safe_redirect( $redirect );
    exit;
});
