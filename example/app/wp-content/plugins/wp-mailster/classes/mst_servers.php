<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mst_servers extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Server', 'wpmst-mailster' ), //singular name of the servered records
			'plural'   => __( 'Servers', 'wpmst-mailster' ), //plural name of the servered records
			'ajax'     => true //does this table support ajax?
        ) );

	}

	/**
	 * Retrieve mailing servers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function mst_get_servers( $per_page = 20, $page_number = 1 ) {
		global $wpdb;
		$sql = self::getServerQuery();

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . sanitize_text_field( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

    /**
     * Get all servers, take into account search variable
     * @return string
     */
    protected static function getServerQuery(){
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}mailster_servers";
        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $sql .= ' WHERE name LIKE \'%'.$searchTerm.'%\' OR server_host LIKE  \'%'.$searchTerm.'%\'';
        }
        return $sql;
    }
	/**
	 * Delete a server record.
	 *
	 * @param int $id server ID
	 */
	public static function mst_delete_server( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}mailster_servers",
			array( 'id' => $id ),
			array( '%d' )
		);
	}
	/**
	 * Activate a server record.
	 *
	 * @param int $id server ID
	 */
	public static function mst_activate_server( $id ) {
		global $wpdb;

		$wpdb->update( 
			"{$wpdb->prefix}mailster_servers", 
			array( 'published' => 1 ), 
			array( 'id' => $id ) 
		);
	}
	/**
	 * Deactivate a server record.
	 *
	 * @param int $id server ID
	 */
	public static function mst_deactivate_server( $id ) {
		global $wpdb;

		$result = $wpdb->update( 
			"{$wpdb->prefix}mailster_servers", 
			array( 'published' => 0 ), 
			array( 'id' => $id ) 
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getServerQuery();
		$sql = "SELECT COUNT(*) FROM (".$sql.") ST";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no servers data is available */
	public function no_items() {
		_e( 'No Servers avaliable.', 'wpmst-mailster' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return $item[ $column_name ];
				//return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['id']
		);
	}

	function column_active( $item ) {
		if($item['published']) {
			$deactivate_nonce = wp_create_nonce( 'mst_deactivate_server' );
			return sprintf( 
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons dashicons-yes"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'deactivate',
				absint( $item['id'] ), 
				$deactivate_nonce ,
				__("Activated - click to Deactivate", "wpmst-mailster")
			);
		} else {
			$activate_nonce = wp_create_nonce( 'mst_activate_server' );
			return sprintf( 
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons  dashicons-no-alt"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'activate',
				absint( $item['id'] ), 
				$activate_nonce ,
				__("Deactivated - click to Activate", "wpmst-mailster")
			);
		}
	}

	function column_server_type( $item ) {
		if ( $item['server_type'] == MstConsts::SERVER_TYPE_MAIL_INBOX ) {
			return __("Inbox", "wpmst-mailster");
		} else {
			return __("SMTP", "wpmst-mailster");			
		}
	}

	function column_lists( $item ) {
		$server = new MailsterModelServer($item['id']);
		return count( $server->getLists() );
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'mst_delete_server' );
		$edit_nonce = wp_create_nonce( 'mst_edit_server' );

		$title = sprintf( '<a href="?page=%s&amp;subpage=edit&sid=%s&_wpnonce=%s"><strong>' . $item["name"] . '</strong></a>', sanitize_text_field( $_REQUEST['page'] ), absint( $item['id'] ), $edit_nonce );

		$actions = array(
			'edit' => sprintf( 
				'<a href="?page=%s&amp;subpage=%s&sid=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'edit',
				absint( $item['id'] ),
				$edit_nonce,
				__("Edit", "wpmst-mailster") 
			),
			'delete' => sprintf( 
				'<a href="?page=%s&action=%s&server=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] ), 
				$delete_nonce ,
				__("Delete", "wpmst-mailster")
			)
        );
		return $title . $this->row_actions( $actions );
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Name', 'wpmst-mailster' ),
			'active' => __( 'Status', 'wpmst-mailster' ),
			'server_type' => __( 'Server Type', 'wpmst-mailster' ),
			'lists' => __( 'Server used in #lists', 'wpmst-mailster')
        );

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __('Delete', 'wpmst-mailster'),
			'bulk-activate' => __('Activate', 'wpmst-mailster'),
			'bulk-deactivate' => __('Deactivate', 'wpmst-mailster')
        );

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'edit_post_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

		$this->items = self::mst_get_servers( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		switch ($this->current_action()) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_server' ) ) {
					die( 'Go get a life script kiddies' );
				} else {
					self::mst_delete_server( absint( $_GET['server'] ) );
				}
				break;

			case 'activate':
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mst_activate_server' ) ) {
					die( 'Go get a life script kiddies' );
				} else {
					self::mst_activate_server( absint( $_GET['id'] ) );
					$query = array();

					if ( ! empty( $deleted ) )
						$query['message'] = 'activated';
				}
				break;

			case 'deactivate':
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mst_deactivate_server' ) ) {
					die( 'Go get a life script kiddies' );
				} else {
					self::mst_deactivate_server( absint( $_GET['id'] ) );
				}
				break;
			case 'bulk-delete':
				$selected_ids = $_REQUEST['bulk-action'];

				// loop over the array of record IDs and delete them
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_delete_server( intval($id) );

					}
				}
				break;
			case 'bulk-activate':
				$selected_ids = $_REQUEST['bulk-action'];

				// loop over the array of record IDs and delete them
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_activate_server( intval($id) );
					}
				}
				break;
			case 'bulk-deactivate':
				$selected_ids = $_REQUEST['bulk-action'];

				// loop over the array of record IDs and delete them
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_deactivate_server( intval($id) );
					}
				}
				break;
			default:
				# code...
				break;
		}
	}


}