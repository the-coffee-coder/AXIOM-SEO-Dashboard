<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SCC_Clients_List extends WP_List_Table {
	public function __construct() {
		parent::__construct([
			'singular' => 'scc_client',
			'plural'   => 'scc_clients',
			'ajax'     => false,
		]);
	}

	public function get_columns() {
		return [
			'cb'     => '<input type="checkbox" />',
			'logo'   => 'Logo',
			'name'   => 'Name',
			'site_url' => 'Site URL',
			'ga4'    => 'GA4',
			'ads'    => 'Ads',
		];
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk_ids[]" value="%s" />',
			$item->id
		);
	}

	protected function column_logo( $item ) {
		if ( $item->logo_id ) {
			return wp_get_attachment_image( $item->logo_id, [50,50] );
		}
		return '';
	}

	protected function column_name( $item ) {
		$edit_url   = admin_url( 'admin.php?page=scc_clients&action=edit&id=' . $item->id );
		$delete_url = wp_nonce_url(
			add_query_arg([
				'action' => 'delete',
				'id'     => $item->id,
			]),
			'scc_delete_client'
		);

		// Build row actions
		$actions = [
			'edit'   => sprintf('<a href="%s">Edit</a>', esc_url( $edit_url )),
			'delete' => sprintf('<a href="%s">Delete</a>', esc_url( $delete_url )),
		];

		// Fetch History action
		$fetch_url = wp_nonce_url(
			add_query_arg([
				'action'    => 'scc_fetch_history',
				'client_id' => $item->id,
			], admin_url('admin-ajax.php')),
			'scc_fetch_history_' . $item->id
		);
		$actions['fetch'] = sprintf(
			'<a href="%s" class="scc-fetch-history" style="color:red;font-weight:bold;">Fetch History</a>',
			esc_url( $fetch_url )
		);

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong>%3$s',
			esc_url( $edit_url ),
			esc_html( $item->name ),
			$this->row_actions( $actions )
		);
	}

	protected function column_site_url( $item ) {
		return esc_url( $item->site_url );
	}

	protected function column_ga4( $item ) {
		if ( ! empty( $item->ga4_property_id ) ) {
			return '<span style="color:green;">&#10004;</span>'; // green check
		}
		return '<span style="color:red;">&#10006;</span>';   // red X
	}

	protected function column_ads( $item ) {
		if ( ! empty( $item->ads_customer_id ) ) {
			return '<span style="color:green;">&#10004;</span>'; // green check
		}
		return '<span style="color:red;">&#10006;</span>';   // red X
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'site_url':
				return $this->column_site_url( $item );
			case 'ga4':
				return $this->column_ga4( $item );
			case 'ads':
				return $this->column_ads( $item );
			default:
				return '';
		}
	}

	protected function get_bulk_actions() {
		return [
			'delete' => 'Delete'
		];
	}

	public function prepare_items() {
		global $wpdb;
		$table = $wpdb->prefix . 'scc_clients';

		$per_page    = 20;
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$current_page = $this->get_pagenum();

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
		]);

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns()
		];

		$offset = ( $current_page - 1 ) * $per_page;
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY name ASC LIMIT %d OFFSET %d",
				$per_page, $offset
			)
		);
	}
}
