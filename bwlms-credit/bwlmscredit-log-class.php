<?php

if ( ! class_exists( 'bwlmsCREDIT_Log_Module' ) ) :
	class bwlmsCREDIT_Log_Module extends bwlmsCREDIT_Module {

		public $user;
		public $screen;

		function __construct( $type = 'bwlmscredit_default' ) {

			parent::__construct( 'bwlmsCREDIT_Log_Module', array(
				'module_name' => 'log',
				'labels'      => array(
					'menu'        => __( 'Log', 'wptobemem' ),
					'page_title'  => __( 'Log', 'wptobemem' )
				),
				'screen_id'   => 'bwlmsCREDIT_page_log',
				'cap'         => 'manage_options'
			), $type );

		}


		public function module_init() {

			$this->current_user_id = get_current_user_id();
			$this->download_export_log();

			add_action( 'bwlmscredit_add_menu',   array( $this, 'my_history_menu' ) );

			add_action( 'before_delete_post', array( $this, 'post_deletions' ) );
			add_action( 'delete_comment',     array( $this, 'comment_deletions' ) );

			if ( isset( $this->core->delete_user ) && ! $this->core->delete_user )
				add_action( 'delete_user', array( $this, 'user_deletions' ) );

		}

		public function module_admin_init() {

			add_action( 'wp_ajax_bwlmscredit-delete-log-entry', array( $this, 'action_delete_log_entry' ) );
			add_action( 'wp_ajax_bwlmscredit-update-log-entry', array( $this, 'action_update_log_entry' ) );

		}

		public function download_export_log() {

			
			if ( ! isset( $_REQUEST['bwlmscredit-export'] ) ) { 
				return; 
			}
			else if ( sanitize_text_field($_REQUEST['bwlmscredit-export']) != 'do'){
				if($export_flag != 'do') return;
			}

			if ( ! is_user_logged_in() ) return;

			if ( ! apply_filters( 'bwlmscredit_user_can_export', false ) && ! $this->core->can_edit_credits() ) return;

			if ( apply_filters( 'bwlmscredit_allow_front_export', false ) === true ) {
				if ( ! isset( $_REQUEST['token'] ) || ! wp_verify_nonce( $_REQUEST['token'], 'bwlmscredit-run-log-export' ) ) return;
			}

			else {
				check_admin_referer( 'bwlmscredit-run-log-export', 'token' );
			}

			$type = '';
			$data = array();

			foreach ( (array) $_POST as $key => $value ) {
				if ( $key == 'action' ) continue;
				//[POST/GET/REQUEST] sanitization
				$_value = sanitize_text_field( $value );
				if ( $_value != '' )
					$data[ $key ] = $_value;
			}

			$exports = bwlmscredit_get_log_exports();
			if ( empty( $exports ) ) return;

			foreach ( $exports as $id => $info ) {
				if ( $info['label'] == $_POST['action'] ) {
					$type = $id;
					break;
				}
			}

			switch ( $type ) {

				case 'all'    :
					$old_data = $data;
					unset( $data );
					$data = array();
					$data['ctype']  = $old_data['ctype'];
					$data['number'] = -1;

				break;

				case 'search' :

					$data['number'] = -1;

				break;

				case 'displayed' :
				default :

					$data = apply_filters( 'bwlmscredit_export_log_args', $data );

				break;

			}

			if ( has_action( 'bwlmscredit_export_' . $type ) )
				do_action( 'bwlmscredit_export_' . $type, $data );

			else {

				$log = new bwlmsCREDIT_Query_Log( $data, true );

				if ( $log->have_entries() ) {

					$export = array();
					foreach ( $log->results as $entry ) {

						unset( $entry['id'] );

						$entry['entry'] = str_replace( ',', '', $entry['entry'] );
						$entry['data'] = str_replace( ',', '.', $entry['data'] );

						$export[] = $entry;
					}

					$log->reset_query();

					require_once (BWLMSMEM_DIR . "/lib/parsecsv.lib.php");
					$csv = new parseCSV();

					$date = date_i18n( 'Y-m-d' );
					$csv->output( true, 'bwlmscredit-log-' . $date . '.csv', $export, array( 'ref', 'ref_id', 'user_id', 'credits', 'ctype', 'time', 'entry', 'data' ) );
					die;
				}

				$log->reset_query();
			}
		}

		public function action_delete_log_entry() {

			check_ajax_referer( 'bwlmscredit-delete-log-entry', 'token' );

			if ( ! is_user_logged_in() || ! $this->core->can_edit_plugin() )
				wp_send_json_error(  __( 'Access denied for this action', 'wptobemem' ) );

			global $wpdb;
			//[POST/GET/REQUEST] sanitization
			$log_row = absint( $_POST['row'] );
			$wpdb->delete( $this->core->log_table, array( 'id' => $log_row ), array( '%d' ) );

			wp_send_json_success( __( 'Row Deleted', 'wptobemem' ) );

		}

		public function action_update_log_entry() {

			check_ajax_referer( 'bwlmscredit-update-log-entry', 'token' );

			if ( ! is_user_logged_in() || ! $this->core->can_edit_plugin() )
				wp_send_json_error(  __( 'Access denied for this action', 'wptobemem' ) );

			//[POST/GET/REQUEST] sanitization
			$new_entry = trim( $_POST['new_entry'] );
			$new_entry = esc_attr( $new_entry );

			global $wpdb;

			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->core->log_table} WHERE id = %d;", absint( $_POST['row'] ) ) );

			if ( empty( $row ) || $row === NULL )
				wp_send_json_error( __( 'Log entry not found', 'wptobemem' ) );

			$wpdb->update(
				$this->core->log_table,
				array( 'entry' => $new_entry ),
				array( 'id' => $row->id ),
				array( '%s' ),
				array( '%d' )
			);

			wp_send_json_success( array(
				'label'         => __( 'Entry Updated' ),
				'row_id'        => $row->id,
				'new_entry_raw' => $new_entry,
				'new_entry'     => $this->core->parse_template_tags( $new_entry, $row )
			) );

		}

		public function my_history_menu() {

			if ( $this->core->exclude_user() ) return;

			$page = add_users_page(
				$this->core->plural() . ' ' . __( 'History', 'wptobemem' ),
				$this->core->plural() . ' ' . __( 'History', 'wptobemem' ),
				'read',
				$this->bwlmscredit_type . '_history',
				array( $this, 'my_history_page' )
			);

			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_header' ) );
			add_action( 'load-' . $page,               array( $this, 'screen_options' ) );
		}

		public function settings_header() {
			wp_enqueue_script( 'bwlmscredit-edit-log' );
			wp_enqueue_style( 'bwlmscredit-inline-edit' );
		}

		public function screen_options() {

			$this->set_entries_per_page();
			
			$get_page = sanitize_text_field( $_GET['page'] );
			$settings_key = 'bwlmscredit_epp_' . $get_page;
			
			if ( ! $this->is_main_type )
				$settings_key .= '_' . $this->bwlmscredit_type;

			$args = array(
				'label'   => __( 'Entries', 'wptobemem' ),
				'default' => 10,
				'option'  => $settings_key
			);
			add_screen_option( 'per_page', $args );
		}

		public function page_title( $title = 'Log' ) {

			if ( $this->core->can_edit_plugin() )
				$link = '<a href="javascript:void(0)" class="toggle-exporter add-new-h2" data-toggle="export-log-history">' . __( 'Export', 'wptobemem' ) . '</a>';

			else
				$link = '';

			if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
				$search_keyword = sanitize_text_field( $_GET['s'] );
				$search_for = ' <span class="subtitle">' . __( 'Search results for', 'wptobemem' ) . ' "' . $search_keyword . '"</span>';
			}
			else
				$search_for = '';

			echo $title . ' ' . $link . $search_for;
		}

		public function admin_page() {

			global $wpdb;

			if ( ! $this->core->can_edit_credits() )
				wp_die( __( 'Access Denied', 'wptobemem' ) );
			
			$getpage_name = sanitize_text_field($_GET['page']);
			$settings_key = 'bwlmscredit_epp_' . $getpage_name;
			if ( ! $this->is_main_type )
				$settings_key .= '_' . $this->bwlmscredit_type;

			$per_page = bwlmscredit_get_user_meta( $this->current_user_id, $settings_key, '', true );
			if ( $per_page == '' ) $per_page = 10;

			$args = array( 'number' => absint( $per_page ) );

			if ( isset( $_GET['type'] ) && $_GET['type'] != '' )
				$args['ctype'] = sanitize_key( $_GET['type'] );
			else
				$args['ctype'] = $this->bwlmscredit_type;

			if ( isset( $_GET['user'] ) && $_GET['user'] != '' )
				$args['user_id'] = sanitize_text_field( $_GET['user'] );

			if ( isset( $_GET['s'] ) && $_GET['s'] != '' )
				$args['s'] = sanitize_text_field( $_GET['s'] );

			if ( isset( $_GET['ref'] ) && $_GET['ref'] != '' )
				$args['ref'] = sanitize_text_field( $_GET['ref'] );

			if ( isset( $_GET['show'] ) && $_GET['show'] != '' )
				$args['time'] = absint( $_GET['show'] );

			if ( isset( $_GET['order'] ) && $_GET['order'] != '' )
				$args['order'] = sanitize_text_field( $_GET['order'] );

			if ( isset( $_GET['start'] ) && isset( $_GET['end'] ) )
				$args['amount'] = array( 'start' => sanitize_text_field( $_GET['start'] ), 'end' => sanitize_text_field( $_GET['end'] ) );

			elseif ( isset( $_GET['num'] ) && isset( $_GET['compare'] ) )
				$args['amount'] = array( 'num' => sanitize_text_field( $_GET['num'] ), 'compare' => urldecode( $_GET['compare'] ) );

			elseif ( isset( $_GET['amount'] ) )
				$args['amount'] = sanitize_text_field( $_GET['amount'] );

			if ( isset( $_GET['data'] ) && $_GET['data'] != '' )
				$args['data'] = sanitize_text_field( $_GET['data'] );

			if ( isset( $_GET['paged'] ) && $_GET['paged'] != '' )
				$args['paged'] = absint( $_GET['paged'] );

			$log = new bwlmsCREDIT_Query_Log( $args );

			$log->headers['column-actions'] = __( 'Actions', 'wptobemem' );

?>
<div class="wrap" id="bwlmsCREDIT-wrap">
	<h2><?php $this->page_title( sprintf( __( '%s Log', 'wptobemem' ), $this->core->plural() ) ); ?></h2>
<?php

			$extensions = get_loaded_extensions();
			if ( ! in_array( 'mcrypt', $extensions ) && ! defined( 'BWLMSCREDIT_DISABLE_PROTECTION' ) )
				echo '<div id="message" class="error below-h2"><p>' . __( 'Warning. The required Mcrypt PHP Library is not installed on this server! Certain hooks and shortcodes will not work correctly!', 'wptobemem' ) . '</p></div>';

			// Filter by dates
			$log->filter_dates( admin_url( 'admin.php?page=' . $this->screen_id ) );

?>
<?php do_action( 'bwlmscredit_top_log_page', $this ); ?>
<div class="clear"></div>

	<?php $log->exporter( __( 'Export', 'wptobemem' ) ); ?>
	<div >
		<?php
			$point_wheres ="";

			if ( isset( $_GET['show'] ) && $_GET['show'] != '' ) {
				$get_showflag = sanitize_text_field( $_GET['show']);
				$now = date_i18n( 'U' );
				$today = strtotime( date( 'Y/m/d' ) . ' midnight' );
				$todays_date = date_i18n( 'd' );

				if (  $get_showflag == 'today' ) {
					$point_wheres = "time BETWEEN $today AND $now";
				}
				elseif (  $get_showflag == 'yesterday' ) {
					$yesterday = strtotime( '-1 day midnight' );
					$point_wheres = "time BETWEEN $yesterday AND $today";
				}
				elseif (  $get_showflag == 'thisweek' ) {
					$weekday = date_i18n( 'w' );
					if ( get_option( 'start_of_week' ) == $weekday ) {
						$point_wheres = "time BETWEEN $today AND $now";
					}
					else {
						$week_start = strtotime( '-' . ( $weekday+1 ) . ' days midnight' );
						$point_wheres = "time BETWEEN $week_start AND $now";
					}
				}
				
				elseif (  $get_showflag == 'thismonth' ) {
					$start_of_month = strtotime( date_i18n( 'Y/m/01' ) . ' midnight' );
					$point_wheres = "time BETWEEN $start_of_month AND $now";
				}
				else {
					$times = explode( ',',  $get_showflag );
					if ( count( $times ) == 2 ) {
						$from = sanitize_key( $times[0] );
						$to = sanitize_key( $times[1] );
						$point_wheres = "time BETWEEN $from AND $to";
					}
				}
				
			}

			if($point_wheres)
			{
				$point_wheres = " and ". $point_wheres;
			}

			$bwlmscreatetype ="bwlmscredit_default";
			$bwlmscredit = bwlmscredit( $bwlmscreatetype );

			$point_total = $wpdb->get_var( "SELECT SUM( meta_value ) FROM {$wpdb->usermeta} WHERE meta_key = '{$bwlmscreatetype}';" );
			$point_gained = $wpdb->get_var( "SELECT SUM(credits) FROM {$bwlmscredit->log_table} WHERE credits > 0 AND ctype = '{$bwlmscreatetype}' {$point_wheres};" );
			$point_lost = $wpdb->get_var( "SELECT SUM(credits) FROM {$bwlmscredit->log_table} WHERE credits < 0 AND ctype = '{$bwlmscreatetype}' {$point_wheres};" );

		?>
		<?php _e( 'Points', 'wptobemem' ); ?> : <?php echo $bwlmscredit->format_credits( $point_total ); ?> <?php _e( 'Awarded', 'wptobemem' ); ?> : <?php echo $bwlmscredit->format_credits( $point_gained ); ?> <?php _e( 'Deducted', 'wptobemem' ); ?> : <?php echo $bwlmscredit->format_credits( $point_lost ); ?> 
	</div>

	<form method="get" action="" name="bwlmscredit-thelog-form" novalidate>
<?php

			if ( isset( $_GET['type'] ) && $_GET['type'] != '' )
				echo '<input type="hidden" name="type" value="' . esc_attr( $_GET['type'] ) . '" />';

			if ( isset( $_GET['user'] ) && $_GET['user'] != '' )
				echo '<input type="hidden" name="user" value="' . esc_attr( $_GET['user'] ) . '" />';

			if ( isset( $_GET['s'] ) && $_GET['s'] != '' )
				echo '<input type="hidden" name="s" value="' . esc_attr( $_GET['s'] ) . '" />';

			if ( isset( $_GET['ref'] ) && $_GET['ref'] != '' )
				echo '<input type="hidden" name="ref" value="' . esc_attr( $_GET['ref'] ) . '" />';

			if ( isset( $_GET['show'] ) && $_GET['show'] != '' )
				echo '<input type="hidden" name="show" value="' . esc_attr( $_GET['show'] ) . '" />';

			if ( isset( $_GET['order'] ) && $_GET['order'] != '' )
				echo '<input type="hidden" name="order" value="' . esc_attr( $_GET['order'] ) . '" />';

			if ( isset( $_GET['data'] ) && $_GET['data'] != '' )
				echo '<input type="hidden" name="data" value="' . esc_attr( $_GET['data'] ) . '" />';

			if ( isset( $_GET['paged'] ) && $_GET['paged'] != '' )
				echo '<input type="hidden" name="paged" value="' . esc_attr( $_GET['paged'] ) . '" />';
			
			$log->search();

?>
		<input type="hidden" name="page" value="<?php echo $this->screen_id; ?>" />

		<?php do_action( 'bwlmscredit_above_log_table', $this ); ?>

		<div class="tablenav top">

			<?php $log->table_nav( 'top', false ); ?>

		</div>
		<table class="table wp-list-table widefat bwlmscredit-table log-entries" cellspacing="0">
			<thead>
				<tr>
<?php

			foreach ( $log->headers as $col_id => $col_title )
				echo '<th scope="col" id="' . str_replace( 'column-', '', $col_id ) . '" class="manage-column ' . $col_id . '">' . $col_title . '</th>';

?>
				</tr>
			</thead>
			<tfoot>
				<tr>
<?php

			foreach ( $log->headers as $col_id => $col_title )
				echo '<th scope="col" class="manage-column ' . $col_id . '">' . $col_title . '</th>';

?>
				</tr>
			</tfoot>
			<tbody id="the-list">
<?php

			if ( $log->have_entries() ) {

				$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$entry_data  = '';
				$alt         = 0;

				foreach ( $log->results as $log_entry ) {

					$alt = $alt+1;
					if ( $alt % 2 == 0 )
						$class = ' alt';

					else
						$class = '';

					echo '<tr class="bwlmsCREDIT-log-row' . $class . '" id="bwlmscredit-log-entry-' . $log_entry->id . '">';

					foreach ( $log->headers as $column_id => $column_name ) {

						echo '<td class="' . $column_id . '">';

						switch ( $column_id ) {

							// Username Column
							case 'column-username' :

								$user = get_userdata( $log_entry->user_id );
								if ( $user === false )
									$content = '<span>' . __( 'User Missing', 'wptobemem' ) . ' (ID: ' . $log_entry->user_id . ')</span>';
								else 
									$content = '<span>' . $user->display_name . '</span>';

								if ( $user !== false && $this->core->can_edit_credits() )
									$content .= ' <em><small>(ID: ' . $log_entry->user_id . ')</small></em>';

								echo apply_filters( 'bwlmscredit_log_username', $content, $log_entry->user_id, $log_entry );

							break;

							// Date & Time Column
							case 'column-time' :
								echo apply_filters( 'bwlmscredit_log_date', date_i18n( $date_format, $log_entry->time ), $log_entry->time );
							break;

							case 'column-credits' :
								$content = $this->core->format_credits( $log_entry->credits );
								echo apply_filters( 'bwlmscredit_log_credits', $content, $log_entry->credits, $log_entry );
							break;

							case 'column-entry' :
								$content = '<div style="display:none;" class="raw">' . htmlentities( $log_entry->entry ) . '</div>';
								$content .= '<div class="entry">' . $this->core->parse_template_tags( $log_entry->entry, $log_entry ) . '</div>';
								echo apply_filters( 'bwlmscredit_log_entry', $content, $log_entry->entry, $log_entry );
							break;

							case 'column-actions' :
								$content = '<a href="javascript:void(0)" class="bwlmscredit-open-log-entry-editor" data-id="' . $log_entry->id . '">' . __( 'Edit', 'wptobemem' ) . '</a> &bull; <span class="delete"><a href="javascript:void(0);" class="bwlmscredit-delete-row" data-id="' . $log_entry->id . '">' . __( 'Delete', 'wptobemem' ) . '</a></span>';
								echo apply_filters( 'bwlmscredit_log_actions', $content, $log_entry );
							break;

							default :
								echo apply_filters( 'bwlmscredit_log_' . $column_id, '', $log_entry );
							break;
						}
						echo '</td>';
					}
					echo '</tr>';
				}
			}

			else {
				echo '<tr><td colspan="' . count( $log->headers ) . '" class="no-entries">' . $log->get_no_entries() . '</td></tr>';
			}

?>
			</tbody>
		</table>
		<div class="tablenav bottom">

			<?php $log->table_nav( 'bottom', false ); ?>

		</div>

		<?php do_action( 'bwlmscredit_bellow_log_table', $this ); ?>

	</form>

	<?php do_action( 'bwlmscredit_bottom_log_page', $this ); ?>

	<div id="edit-bwlmscredit-log-entry" style="display: none;">
		<div class="bwlmscredit-adjustment-form">
			<p class="row inline" style="width: 40%;"><label><?php _e( 'User', 'wptobemem' ); ?>:</label><span id="bwlmscredit-username"></span></p>
			<p class="row inline" style="width: 40%;"><label><?php _e( 'Time', 'wptobemem' ); ?>:</label> <span id="bwlmscredit-time"></span></p>
			<p class="row inline" style="width: 20%;"><label><?php echo $this->core->plural(); ?>:</label> <span id="bwlmscredit-credits"></span></p>
			<div class="clear"></div>
			<p class="row">
				<label for="bwlmscredit-update-users-balance-amount"><?php _e( 'Current Log Entry', 'wptobemem' ); ?>:</label>
				<input type="text" name="bwlmscredit-raw-entry" id="bwlmscredit-raw-entry" value="" disabled="disabled" /><br />
				<span class="description"><?php _e( 'The current saved log entry', 'wptobemem' ); ?>.</span>
			</p>
			<p class="row">
				<label for="bwlmscredit-update-users-balance-entry"><?php _e( 'Adjust Log Entry', 'wptobemem' ); ?>:</label>
				<input type="text" name="bwlmscredit-new-entry" id="bwlmscredit-new-entry" value="" /><br />
				<span class="description"><?php _e( 'The new log entry', 'wptobemem' ); ?>.</span>
			</p>
			<p class="row">
				<input type="button" id="bwlmscredit-update-log-entry"  class="button button-primary button-large" value="<?php _e( 'Update Log Entry', 'wptobemem' ); ?>" />
				<input type="hidden" id="bwlmscredit-log-row-id" value="" />
			</p>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
</div>
<?php
			$log->reset_query();
		}

		public function my_history_page() {

			if ( ! is_user_logged_in() )
				wp_die( __( 'Access Denied', 'wptobemem' ) );

			$getpage_name = sanitize_text_field($_GET['page']);
			$settings_key = 'bwlmscredit_epp_' . $getpage_name;

			if ( ! $this->is_main_type )
				$settings_key .= '_' . $this->bwlmscredit_type;

			$per_page = bwlmscredit_get_user_meta( $this->current_user_id, $settings_key, '', true );
			if ( $per_page == '' ) $per_page = 10;

			$args = array(
				'user_id' => $this->current_user_id,
				'number'  => $per_page
			);

			if ( isset( $_GET['type'] ) && ! empty( $_GET['type'] ) )
				$args['ctype'] = sanitize_key( $_GET['type'] );
			else
				$args['ctype'] = $this->bwlmscredit_type;

			if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) )
				$args['s'] = sanitize_text_field( $_GET['s'] );

			if ( isset( $_GET['ref'] ) && ! empty( $_GET['ref'] ) )
				$args['ref'] = sanitize_text_field( $_GET['ref'] );

			if ( isset( $_GET['show'] ) && ! empty( $_GET['show'] ) )
				$args['time'] = absint( $_GET['show'] );

			if ( isset( $_GET['order'] ) && ! empty( $_GET['order'] ) )
				$args['order'] = sanitize_text_field( $_GET['order'] );

			if ( isset( $_GET['start'] ) && isset( $_GET['end'] ) )
				$args['amount'] = array( 'start' => sanitize_text_field( $_GET['start'] ), 'end' => sanitize_text_field( $_GET['end'] ) );

			elseif ( isset( $_GET['num'] ) && isset( $_GET['compare'] ) )
				$args['amount'] = array( 'num' => sanitize_text_field( $_GET['num'] ), 'compare' => $_GET['compare'] );

			elseif ( isset( $_GET['amount'] ) )
				$args['amount'] = sanitize_text_field( $_GET['amount'] );

			if ( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) )
				$args['paged'] = absint( $_GET['paged'] );

			$log = new bwlmsCREDIT_Query_Log( $args );
			unset( $log->headers['column-username'] );

?>
<div class="wrap" id="bwlmsCREDIT-wrap">
	<h2><?php $this->page_title( sprintf( __( 'My %s History', 'wptobemem' ),  $this->core->plural() ) ); ?></h2>

	<?php
			$get_page = sanitize_text_field( $_GET['page'] );
			$log->filter_dates( admin_url( 'users.php?page=' . $get_page ) ); 
	?>

	<?php do_action( 'bwlmscredit_top_my_log_page', $this ); ?>

	<div class="clear"></div>

	<?php $log->exporter( __( 'Export', 'wptobemem' ), true ); ?>

	<form method="get" action="" name="bwlmscredit-mylog-form" novalidate>
<?php

			if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) )
				echo '<input type="hidden" name="s" value="' . $_GET['s'] . '" />';

			if ( isset( $_GET['ref'] ) && ! empty( $_GET['ref'] ) )
				echo '<input type="hidden" name="ref" value="' . $_GET['ref'] . '" />';

			if ( isset( $_GET['show'] ) && ! empty( $_GET['show'] ) )
				echo '<input type="hidden" name="show" value="' . $_GET['show'] . '" />';

			if ( isset( $_GET['order'] ) && ! empty( $_GET['order'] ) )
				echo '<input type="hidden" name="order" value="' . $_GET['order'] . '" />';

			if ( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) )
				echo '<input type="hidden" name="paged" value="' . $_GET['paged'] . '" />';

			$log->search();

?>
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />

		<?php do_action( 'bwlmscredit_above_my_log_table', $this ); ?>

		<div class="tablenav top">

			<?php $log->table_nav( 'top', true ); ?>

		</div>

		<?php $log->display(); ?>

		<div class="tablenav bottom">

			<?php $log->table_nav( 'bottom', true ); ?>

		</div>

		<?php do_action( 'bwlmscredit_bellow_my_log_table', $this ); ?>

	</form>

	<?php do_action( 'bwlmscredit_bottom_my_log_page', $this ); ?>

</div>
<?php
			$log->reset_query();
		}

		public function post_deletions( $post_id ) {
			global $post_type, $wpdb;
			$sql = "SELECT * FROM {$this->core->log_table} WHERE ref_id = %d;";
			$records = $wpdb->get_results( $wpdb->prepare( $sql, $post_id ) );

			if ( $wpdb->num_rows > 0 ) {

				foreach ( $records as $row ) {

					$check = @unserialize( $row->data );
					if ( $check !== false && $row->data !== 'b:0;' ) {

						$data = unserialize( $row->data );

						if ( ( isset( $data['ref_type'] ) && $data['ref_type'] == 'post' ) || ( isset( $data['post_type'] ) && $post_type == $data['post_type'] ) ) {

							if ( trim( $row->entry ) === '' ) continue;

							$new_data = array( 'ref_type' => 'post' );

							$post = get_post( $post_id );
							$new_data['ID'] = $post->ID;
							$new_data['post_title'] = $post->post_title;
							$new_data['post_type'] = $post->post_type;

							$wpdb->update(
								$this->core->log_table,
								array( 'data' => serialize( $new_data ) ),
								array( 'id'   => $row->id ),
								array( '%s' ),
								array( '%d' )
							);
						}
					}
				}
			}
		}

		public function user_deletions( $user_id ) {

			global $wpdb;

			$sql = "SELECT * FROM {$this->core->log_table} WHERE user_id = %d;";
			$records = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ) );

			if ( $wpdb->num_rows > 0 ) {

				foreach ( $records as $row ) {

					$new_data = array( 'ref_type' => 'user' );

					$user = get_userdata( $user_id );
					$new_data['ID'] = $user->ID;
					$new_data['user_login'] = $user->user_login;
					$new_data['display_name'] = $user->display_name;

					$wpdb->update(
						$this->core->log_table,
						array( 'data' => serialize( $new_data ) ),
						array( 'id'   => $row->id ),
						array( '%s' ),
						array( '%d' )
					);
				}
			}
		}

		public function comment_deletions( $comment_id ) {
			global $wpdb;

			$sql = "SELECT * FROM {$this->core->log_table} WHERE ref_id = %d;";
			$records = $wpdb->get_results( $wpdb->prepare( $sql, $comment_id ) );

			if ( $wpdb->num_rows > 0 ) {

				foreach ( $records as $row ) {

					$check = @unserialize( $row->data );
					if ( $check !== false && $row->data !== 'b:0;' ) {

						$data = unserialize( $row->data );

						if ( isset( $data['ref_type'] ) && $data['ref_type'] == 'comment' ) {

							if ( trim( $row->entry ) === '' ) continue;

							$new_data = array( 'ref_type' => 'comment' );

							$comment = get_comment( $comment_id );
							$new_data['comment_ID'] = $comment->comment_ID;
							$new_data['comment_post_ID'] = $comment->comment_post_ID;

							$wpdb->update(
								$this->core->log_table,
								array( 'data' => serialize( $new_data ) ),
								array( 'id'   => $row->id ),
								array( '%s' ),
								array( '%d' )
							);
						}
					}
				}
			}
		}
	}
endif;

?>