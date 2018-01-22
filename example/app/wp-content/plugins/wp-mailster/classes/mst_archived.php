<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mst_archived extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		add_action('load-edit.php',         array(&$this, 'process_bulk_action'));
		parent::__construct( array(
			'singular' => __( 'Archived Email', 'wpmst-mailster' ), //singular name of the servered records
			'plural'   => __( 'Archived Emails', 'wpmst-mailster' ), //plural name of the servered records
			'ajax'     => true //does this table support ajax?
        ) );

	}
	
	/**
	 * Retrieve archived emails data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 * @param string $state
	 *
	 * @return mixed
	 */
	public static function mst_get_archived( $per_page = 20, $page_number = 1) {
		global $wpdb;
        $sql = self::getMailArchiveQuery();

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby($_REQUEST['orderby']);
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( sanitize_text_field($_REQUEST['order']) ) : ' ASC';
		}else{
            $sql .= ' ORDER BY receive_timestamp DESC';
        }

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

    /**
     * Get all mails, take into account search variable
     * @return string
     */
    protected static function getMailArchiveQuery(){
        global $wpdb;

        $where = array();
        $where[] = 'l.id = l.id'; // need to add at least one always-true-conditions to be sure nothing breaks if non of the following goe into where()

        if( isset($_REQUEST['selectedlid']) ) {
            $where[] = "l.id = ". intval($_REQUEST['selectedlid']);
        }

        $state = 'processed';
        if( isset( $_REQUEST['state'] )) {
            $state = sanitize_text_field( $_REQUEST['state'] );
        }

        if( $state == "bounced" ) {
            $where[] = 'm.bounced_mail = \'1\'';
        } else if( $state == "blocked" ){
            $where[] = 'm.blocked_mail = \'1\'';
        }elseif( $state == "processed" ){
            $where[] = 'm.bounced_mail = \'0\'';
            $where[] = 'm.blocked_mail = \'0\'';
        }


        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $where[] = '(from_name LIKE \'%'.$searchTerm.'%\''
                        . ' OR from_email LIKE \'%'.$searchTerm.'%\''
                        . ' OR subject LIKE \'%'.$searchTerm.'%\''
                        . ' OR body LIKE \'%'.$searchTerm.'%\''
                        . ' OR html LIKE \'%'.$searchTerm.'%\')';
        }

        $whereStr = ' WHERE '.implode(' AND ', $where);

        $sql= 'SELECT m.*, l.name'
            . ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
            . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_lists AS l'
            . ' ON (m.list_id = l.id)'
            . $whereStr
        ;

        return $sql;
    }

	/**
	 * Delete a server record.
	 *
	 * @param int $id server ID
	 */
	public static function mst_delete_archived( $id ) {
		global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('mst_delete_archived, id: '.print_r($id, true));
		$wpdb->delete(
			"{$wpdb->prefix}mailster_mails",
            array( 'id' => $id ),
            array( '%d' )
		);
	}
	
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getMailArchiveQuery();
        $sql = "SELECT COUNT(*) FROM (".$sql.") MT";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no servers data is available */
	public function no_items() {
		_e( 'No Archived emails.', 'wpmst-mailster' );
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

	/**
	 * Method for subject column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_subject( $item ) {

		$delete_nonce = wp_create_nonce( 'mst_delete_archived' );
		$edit_nonce = wp_create_nonce( 'mst_resend_archived' );

        $subject = $item["subject"];
        $subject = (is_null($subject) || strlen($subject) == 0) ? '<em>'.__( '(No Subject)', "wpmst-mailster" ).'</em>' : $subject;
		$title = sprintf( '<a href="?page=mst_archived&subpage=details&sid=%s&_wpnonce=%s"><strong>' . $subject . '</strong></a>', absint( $item['id'] ), $edit_nonce );

		$actions = array(
			'resend' => sprintf(
				'<a href="?page=mst_archived&subpage=resend&eid=%s&_wpnonce=%s">%s</a>',
				absint( $item['id'] ),
				$edit_nonce,
				__("Resend", "wpmst-mailster")
			),
			'delete' => sprintf(
				'<a href="?page=mst_archived&action=delete&sid=%s&_wpnonce=%s">%s</a>',
				absint( $item['id'] ),
				$delete_nonce,
				__("Delete", "wpmst-mailster")
			)
        );
		return $title . $this->row_actions( $actions );
	}

	/**
	 * Method for fwd_errors column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_fwd_errors( $item ) {
		if($item['fwd_errors'] > 1 ) {
			return '<span class="dashicons dashicons-no"></span>';
		} else {
			return '';
		}
	}

	/**
	 * Method for fwd_errors column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_list_id( $item ) {
		return $item['name'];
	}

	/**
	 * Method for fwd_completed column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_fwd_completed( $item ) {
		if($item['fwd_completed'] == 1 ) {
			return '<span class="dashicons dashicons-yes"></span>';
		} else {
			return '';
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'from_name'    => __( 'From Name', 'wpmst-mailster' ),
			'from_email'    => __( 'From Email', 'wpmst-mailster' ),
			'subject'    => __( 'Subject', 'wpmst-mailster' ),
			'receive_timestamp'    => __( 'Date', 'wpmst-mailster' ),
			'fwd_completed_timestamp'    => __( 'Sent date', 'wpmst-mailster' ),
			'fwd_errors' => __('Errors', 'wpmst-mailster'),
			'fwd_completed' => __('Sent', 'wpmst-mailster'),
			'list_id' => __('In Mailing List', 'wpmst-mailster'),
			'id' => __('ID', 'wpmst-mailster'),
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
			'subject' => array( 'subject', true ),
			'receive_timestamp' => array( 'receive_timestamp', true ),
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
			'bulk_resend' => __('Resend', 'wpmst-mailster')
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

		$this->items = self::mst_get_archived( $per_page, $current_page );
	}


	public function process_bulk_action() {
        $log = MstFactory::getLogger();
		//Detect when a bulk action is being triggered...
        $action = $this->current_action();
        $log->debug('mst_archived -> process_bulk_action: '.$action);
		switch ($action) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_archived' ) ) {
					die( 'Go get a life script kiddies' );
				} else {
					self::mst_delete_mail( absint( $_GET['sid'] ) );
				}
				break;

			case 'resend':
				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( sanitize_text_field($_REQUEST['_wpnonce']) );

				if ( ! wp_verify_nonce( $nonce, 'mst_resend_archived' ) ) {
					die( 'Go get a life script kiddies' );
				} else {
					$ids = $_GET['id'];
					$edit_nonce = wp_create_nonce( 'mst_resend_archived' );
					$url = "?page=mst_resend&_wpnonce=". $edit_nonce;
					foreach($ids as $id) {
						$url .= "&eid[]=" . intval($id);
					}
				}
				break;
			case 'bulk-resend':
				$ids = $_REQUEST['bulk-action'];
				$edit_nonce = wp_create_nonce( 'mst_resend_archived' );
				$i=0;
				$args = array("page" => "mst_resend", "_wpnonce" => $edit_nonce);
				foreach($ids as $id) {
					$args["eid[" . $i . "]"] = intval($id);
					$i++;
				}
				break;
			case 'bulk-delete':
				$selected_ids = esc_sql( $_REQUEST['bulk-action'] );
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_delete_mail( intval($id) );
					}
				}
				break;
			default:
				# code...
				break;
		}
	}

	/**
	 * Delete a mail record.
	 *
	 * @param int $id mail ID
	 */
	public static function mst_delete_mail( $id ) {
		global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('mst_delete_mail: '.$id);
		$wpdb->delete(
			"{$wpdb->prefix}mailster_mails",
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Add the dropdown menu for "state"
	 */
	function extra_tablenav( $which ) {
		$move_on_url = '&state=';
		if ( $which == "top" ){
			?>
			<div class="alignleft actions bulkactions">
				<?php
				$states = array(
					"processed" => __("Processed Emails", 'wpmst-mailster'),
					"blocked" => __("Blocked/Filtered Emails", 'wpmst-mailster'),
					"bounced" => __("Bounced Emails", 'wpmst-mailster')
				);
				?>
				<select name="state" class="ewc-filter-cat">
					<?php
					foreach( $states as $stateid => $state ){
						$selected = '';
						if(isset($_GET['state'])) {
							if ( $_GET['state'] == $stateid ) {
								$selected = ' selected = "selected"';
							}
						}
						?>
						<option value="<?php echo $move_on_url . $stateid; ?>" <?php echo $selected; ?>><?php echo $state; ?></option>
						<?php
					}
					?>
				</select>
			</div>

			<?php
		}
		if ( $which == "bottom" ){
			//The code that goes after the table is there
		}
	}

}