<?php

if ( ! class_exists( 'bwlmsCREDIT_Query_Log' ) ) :
	class bwlmsCREDIT_Query_Log {

		public $args;
		public $request;
		public $prep;
		public $num_rows;
		public $max_num_pages;
		public $total_rows;
		
		public $results;
		
		public $headers;
		public $core;

		public function __construct( $args = array(), $array = false ) {
			if ( empty( $args ) ) return false;

			global $wpdb;

			$select = $where = $sortby = $limits = '';
			$prep = $wheres = array();

			if ( isset( $args['ctype'] ) )
				$type = $args['ctype'];
			else
				$type = 'bwlmscredit_default';

			$this->core = bwlmscredit( $type );
			if ( $this->core->format['decimals'] > 0 )
				$format = '%f';
			else
				$format = '%d';

			$defaults = array(
				'user_id'  => NULL,
				'ctype'    => 'bwlmscredit_default',
				'number'   => 25,
				'time'     => NULL,
				'ref'      => NULL,
				'ref_id'   => NULL,
				'amount'   => NULL,
				's'        => NULL,
				'data'     => NULL,
				'orderby'  => 'time',
				'offset'   => '',
				'order'    => 'DESC',
				'ids'      => false,
				'cache'    => '',
				'paged'    => $this->get_pagenum()
			);
			$this->args = wp_parse_args( $args, $defaults );
			
			$this->diff = array_diff_assoc( $this->args, $defaults );
			if ( isset( $this->diff['number'] ) )
				unset( $this->diff['number'] );

			$data = false;
			if ( $this->args['cache'] != '' ) {
				$cache_id = substr( $this->args['cache'], 0, 23 );
				if ( is_multisite() )
					$data = get_site_transient( 'bwlmscredit_log_query_' . $cache_id );
				else
					$data = get_transient( 'bwlmscredit_log_query_' . $cache_id );
			}
			if ( $data === false ) {
				

				$wheres[] = 'ctype = %s';
				$prep[] = $this->args['ctype'];


				if ( $this->args['user_id'] !== NULL && $this->args['user_id'] != '' ) {

					$user_id = $this->get_user_id( $this->args['user_id'] );

					if ( $user_id !== false ) {
						$wheres[] = 'user_id = %d';
						$prep[] = $user_id;
					}
				}

				if ( $this->args['ref'] !== NULL && $this->args['ref'] != '' ) {
					$refs = explode( ',', $this->args['ref'] );
					$ref_count = count( $refs );
					if ( $ref_count > 1 ) {
						$ref_count = $ref_count-1;
						$wheres[] = 'ref IN (%s' . str_repeat( ',%s', $ref_count ) . ')';
						foreach ( $refs as $ref )
							$prep[] = sanitize_text_field( $ref );
					}
					else {
						$wheres[] = 'ref = %s';
						$prep[] = sanitize_text_field( $refs[0] );
					}
				}


				if ( $this->args['ref_id'] !== NULL && $this->args['ref_id'] != '' ) {
					$ref_ids = explode( ',', $this->args['ref_id'] );
					if ( count( $ref_ids ) > 1 ) {
						$ref_id_count = count( $ref_ids )-1;
						$wheres[] = 'ref_id IN (%d' . str_repeat( ',%d', $ref_id_count ) . ')';
						foreach ( $ref_ids as $ref_id )
							$prep[] = (int) sanitize_text_field( $ref_id );
					}
					else {
						$wheres[] = 'ref_id = %d';
						$prep[] = (int) sanitize_text_field( $this->args['ref_id'] );
					}
				}

				if ( $this->args['amount'] !== NULL && $this->args['amount'] != '' ) {

					if ( is_array( $this->args['amount'] ) ) {
						// Range
						if ( isset( $this->args['amount']['start'] ) && isset( $this->args['amount']['end'] ) ) {
							$wheres[] = 'credits BETWEEN ' . $format . ' AND ' . $format;
							$prep[] = $this->core->number( sanitize_text_field( $this->args['amount']['start'] ) );
							$prep[] = $this->core->number( sanitize_text_field( $this->args['amount']['end'] ) );
						}
						// Compare
						elseif ( isset( $this->args['amount']['num'] ) && isset( $this->args['amount']['compare'] ) ) {
							$compare = urldecode( $this->args['amount']['compare'] );
							$wheres[] = 'credits ' . trim( $compare ) . ' ' . $format;
							$prep[] = $this->core->number( sanitize_text_field( $this->args['amount']['num'] ) );
						}
					}

					else {
						$amounts = explode( ',', $this->args['amount'] );
						$amount_count = count( $amounts );
						if ( $amount_count > 1 ) {
							$amount_count = $amount_count-1;
							$wheres[] = 'amount IN (' . $format . str_repeat( ',' . $format, $ref_id_count ) . ')';
							foreach ( $amount_count as $amount )
								$prep[] = $this->core->number( sanitize_text_field( $amount ) );
						}
						else {
							$wheres[] = 'credits = ' . $format;
							$prep[] = $this->core->number( sanitize_text_field( $amounts[0] ) );
						}
					}
				}

				if ( $this->args['time'] !== NULL && $this->args['time'] != '' ) {
					$now = date_i18n( 'U' );
					$today = strtotime( d( 'Y/m/d' ) . ' midnight' );
					$todays_date = date_i18n( 'd' );

					if ( $this->args['time'] == 'today' ) {
						$wheres[] = "time BETWEEN $today AND $now";
					}
					elseif ( $this->args['time'] == 'yesterday' ) {
						$yesterday = strtotime( '-1 day midnight' );
						$wheres[] = "time BETWEEN $yesterday AND $today";
					}
					elseif ( $this->args['time'] == 'thisweek' ) {
						$weekday = date_i18n( 'w' );
						if ( get_option( 'start_of_week' ) == $weekday ) {
							$wheres[] = "time BETWEEN $today AND $now";
						}
						else {
							$week_start = strtotime( '-' . ( $weekday+1 ) . ' days midnight' );
							$wheres[] = "time BETWEEN $week_start AND $now";
						}
					}
					elseif ( $this->args['time'] == 'thismonth' ) {
						$start_of_month = strtotime( date_i18n( 'Y/m/01' ) . ' midnight' );
						$wheres[] = "time BETWEEN $start_of_month AND $now";
					}
					else {
						$times = explode( ',', $this->args['time'] );
						if ( count( $times ) == 2 ) {
							$from = sanitize_key( $times[0] );
							$to = sanitize_key( $times[1] );
							$wheres[] = "time BETWEEN $from AND $to";
						}
					}
				}

				if ( $this->args['s'] !== NULL && $this->args['s'] != '' ) {
					$search_query = sanitize_text_field( $this->args['s'] );

					if ( is_int( $search_query ) )
						$search_query = (string) $search_query;

					$wheres[] = "entry LIKE %s";
					$prep[] = "%$search_query%";
				}

				if ( $this->args['data'] !== NULL && $this->args['data'] != '' ) {
					$data_query = sanitize_text_field( $this->args['data'] );

					if ( is_int( $data_query ) )
						$data_query = (string) $data_query;

					$wheres[] = "data LIKE %s";
					$prep[] = $data_query;
				}

				if ( $this->args['orderby'] != '' ) {
					$sortbys = array( 'id', 'ref', 'ref_id', 'user_id', 'credits', 'ctype', 'entry', 'data', 'time' );
					$allowed = apply_filters( 'bwlmscredit_allowed_sortby', $sortbys );
					if ( in_array( $this->args['orderby'], $allowed ) ) {
						$sortby = "ORDER BY " . $this->args['orderby'] . " " . $this->args['order'];
					}
				}

				$number = $this->args['number'];
				if ( $number < -1 )
					$number = abs( $number );
				elseif ( $number == 0 || $number == -1 )
					$number = NULL;

				if ( $number !== NULL ) {
					$page = 1;
					if ( $this->args['paged'] !== NULL ) {
						$page = absint( $this->args['paged'] );
						if ( ! $page )
							$page = 1;
					}

					if ( $this->args['offset'] == '' ) {
						$pgstrt = ($page - 1) * $number . ', ';
					}
					else {
						$offset = absint( $this->args['offset'] );
						$pgstrt = $offset . ', ';
					}

					$limits = 'LIMIT ' . $pgstrt . $number;
				}
				else {
					$limits = '';
				}

				if ( $this->args['ids'] === true )
					$select = 'id';
				else
					$select = '*';
				
				$found_rows = '';
				if ( $limits != '' )
					$found_rows = 'SQL_CALC_FOUND_ROWS';

				$select = apply_filters( 'bwlmscredit_query_log_select', $select, $this->args, $this->core );
				$sortby = apply_filters( 'bwlmscredit_query_log_sortby', $sortby, $this->args, $this->core );
				$limits = apply_filters( 'bwlmscredit_query_log_limits', $limits, $this->args, $this->core );
				$wheres = apply_filters( 'bwlmscredit_query_log_wheres', $wheres, $this->args, $this->core );

				$prep = apply_filters( 'bwlmscredit_query_log_prep', $prep, $this->args, $this->core );

				$where = 'WHERE ' . implode( ' AND ', $wheres );

				$this->request = $wpdb->prepare( "SELECT {$found_rows} {$select} FROM {$this->core->log_table} {$where} {$sortby} {$limits}", $prep );
				$this->prep = $prep;
				
				$this->results = $wpdb->get_results( $this->request, $array ? ARRAY_A : OBJECT );
				
				if ( $limits != '' )
					$this->num_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
				else
					$this->num_rows = count( $this->results );

				if ( $limits != '' )
					$this->max_num_pages = ceil( $this->num_rows / $number );

				if ( $this->args['cache'] != '' ) {
					if ( is_multisite() )
						set_site_transient( 'bwlmscredit_log_query_' . $cache_id, $this->results, DAY_IN_SECONDS * 1 );
					else
						set_transient( 'bwlmscredit_log_query_' . $cache_id, $this->results, DAY_IN_SECONDS * 1 );
				}
				
				$this->total_rows = $wpdb->get_var( "SELECT COUNT( * ) FROM {$this->core->log_table}" );
			}

			else {
				$this->request = 'transient';
				$this->results = $data;
				$this->prep = '';
				
				$this->num_rows = count( $data );
			}

			$this->headers = $this->table_headers();
		}

		public function get_user_id( $string = '' ) {

			if ( ! is_numeric( $string ) ) {

				$user = get_user_by( 'login', $string );
				if ( ! isset( $user->ID ) ) {
					$user = get_user_by( 'email', $string );
					if ( ! isset( $user->ID ) ) {
						$user = get_user_by( 'slug', $string );
						if ( ! isset( $user->ID ) )
							return false;
					}
				}
				return absint( $user->ID );

			}

			return $string;

		}

		public function have_entries() {
			if ( ! empty( $this->results ) ) return true;
			return false;
		}

		public function table_nav( $location = 'top', $is_profile = false ) {
			if ( $location == 'top' ) {

				$this->filter_options( $is_profile );
				$this->navigation( $location );

			}
			else {

				$this->navigation( $location );

			}
		}


		public function navigation( $location = 'top', $id = '' ) { ?>

<div class="tablenav-pages<?php if ( $this->max_num_pages == 1 ) echo ' one-page'; ?>">
	<?php $this->pagination( $location, $id ); ?>

</div>
<?php
		}

		public function get_pagenum() {
			global $paged;
			
			if ( $paged > 0 )
				$pagenum = absint( $paged );

			elseif ( isset( $_REQUEST['paged'] ) )
				$pagenum = absint( $_REQUEST['paged'] );

			else return 1;

			return max( 1, $pagenum );
		}

		public function pagination( $location = 'top', $id = '' ) {
			$output = '';
			$total_pages = $this->max_num_pages;
			$current = $this->get_pagenum();
			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $id );
			if ( ! is_admin() )
				$current_url = str_replace( '/page/' . $current . '/', '/', $current_url );
			$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

			// 현재페이지/총페이지 출력
			if ( $this->have_entries() ) {
				$total_number = count( $this->results );
				$output = sprintf( '<div class="large-12 medium-12  small-12 columns bwlmscredit_counter_titlepagecol">
										%d of %d Pages &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									', $current, $total_pages ) ;
			}

			$page_links = array();
			$pagination_class = apply_filters( 'bwlmscredit_log_paginate_class', '', $this );

			$disable_first = $disable_last = '';
			if ( $current == 1 )
				$disable_first = ' alink-inactive';
			if ( $current == $total_pages )
				$disable_last = ' alink-inactive';

			// 첫페이지로 가는 버튼 (사용안함)
			$page_links[] = sprintf( '<a class="%s bwlmscredit_lpbtn" title="%s" href="%s">%s</a>',
				$pagination_class . 'first-page' . $disable_first,
				esc_attr__( 'Go to the first page', 'wptobemem' ),
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				'&laquo;'
			);

			$page_links[] = sprintf( '

				<a class="npbutton back%s bwlmscredit_prevbtn" title="" href="%s">%s</a>',
				$disable_first,
				esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
				'<b>&#10229;</b>'
			);

			if ( 'bottom' == $location )
				$html_current_page = $current;
			else
				$html_current_page = sprintf( '<input class="current-page" title="%s" type="hidden" name="paged" value="%s" size="%d" />',
					esc_attr__( 'Current page', 'wptobemem' ),
					$current,
					strlen( $total_pages )
				);

			$page_links[] = sprintf( '<a class="npbutton next%s bwlmscredit_nextbtn"  title="" href="%s">%s</a>',
				$disable_last,
				esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
				'<b>&#x027F6;</b>'
			);



			// 마지막페이지로 가는 버튼 (사용안함)
			$page_links[] = sprintf( '<a class="%s bwlmscredit_lpbtn" title="%s" href="%s">%s</a>',
				$pagination_class . 'last-page' . $disable_last,
				esc_attr__( 'Go to the last page', 'wptobemem' ),
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				'&raquo;'
			);

			$page_links[] = sprintf( '</div>%s',''	);

			$output .= join( " ", $page_links );

			if ( $total_pages )
				$page_class = $total_pages < 2 ? ' one-page' : '';
			else
				$page_class = ' no-pages';

			echo $output;
		}

		public function get_refs( $req = array() ) {
			$refs = bwlmscredit_get_used_references( $this->args['ctype'] );

			foreach ( $refs as $i => $ref ) {
				if ( ! empty( $req ) && ! in_array( $ref, $req ) )
					unset( $refs[ $i ] );
			}
			$refs = array_values( $refs );

			return apply_filters( 'bwlmscredit_log_get_refs', $refs );
		}

		protected function get_users() {
			$users = wp_cache_get( 'bwlmscredit_users' );

			if ( false === $users ) {
				$users = array();
				$blog_users = get_users( array( 'orderby' => 'display_name' ) );
				foreach ( $blog_users as $user ) {
					if ( false === $this->core->exclude_user( $user->ID ) )
						$users[ $user->ID ] = $user->display_name;
				}
				wp_cache_set( 'bwlmscredit_users', $users );
			}

			return apply_filters( 'bwlmscredit_log_get_users', $users );
		}

		public function filter_options( $is_profile = false, $refs = array() ) {
			echo '<div class="alignleft actions">';
			$show = false;

//			$references = $this->get_refs( $refs );
//			if ( ! empty( $references ) ) {
//				echo '<select name="ref" id="bwlmsCREDIT-reference-filter"><option value="">' . __( 'Show all references', 'wptobemem' ) . '</option>';
//				foreach ( $references as $ref ) {
//					$label = str_replace( array( '_', '-' ), ' ', $ref );
//					echo '<option value="' . $ref . '"';
//					if ( isset( $_GET['ref'] ) && $_GET['ref'] == $ref ) echo ' selected="selected"';
//					echo '>' . ucwords( $label ) . '</option>';
//				}
//				echo '</select>';
//				$show = true;
//			}

			if ( $this->core->can_edit_credits() && ! $is_profile && $this->num_rows > 0 ) {
				$get_username = '';
				if( isset( $_GET['user'] )) { $get_username = sanitize_user( $_GET['user'] ); }
				 
				echo '<input type="text" class="form-control" name="user" id="bwlmsCREDIT-user-filter" size="32" placeholder="' . __( 'User ID, Username, Email or Nicename', 'wptobemem' ) . '" value="' . $get_username	. '" /> ';
				$show = true;
			}

			if ( $this->num_rows > 0 ) {
				echo '<select name="order" id="bwlmsCREDIT-order-filter"><option value="">' . __( 'Show in order', 'wptobemem' ) . '</option>';
				$options = array( 'ASC' => __( 'Ascending', 'wptobemem' ), 'DESC' => __( 'Descending', 'wptobemem' ) );
				foreach ( $options as $value => $label ) {
					echo '<option value="' . $value . '"';
					if ( ! isset( $_GET['order'] ) && $value == 'DESC' ) echo ' selected="selected"';
					elseif ( isset( $_GET['order'] ) && $_GET['order'] == $value ) echo ' selected="selected"';
					echo '>' . $label . '</option>';
				}
				echo '</select>';
				$show = true;
			}

			if ( has_action( 'bwlmscredit_filter_log_options' ) ) {
				do_action( 'bwlmscredit_filter_log_options', $this );
				$show = true;
			}

			if ( $show === true )
				echo '<input type="submit" class="btn btn-default button button-secondary button-large" value="' . __( 'Filter', 'wptobemem' ) . '" />';

			echo '</div>';
		}

		public function exporter( $title = '', $is_profile = false ) {
			if ( ! is_user_logged_in() ) return;

			if ( ! apply_filters( 'bwlmscredit_user_can_export', false ) && ! $this->core->can_edit_credits() ) return;

			if ( ! apply_filters( 'bwlmscredit_allow_front_export', false ) && ! is_admin() ) return;

			$exports = bwlmscredit_get_log_exports();

			if ( empty( $this->diff ) || ( ! empty( $this->diff ) && $this->max_num_pages < 2 ) )
				unset( $exports['search'] );
			
			if ( $is_profile )
				unset( $exports['all'] ); ?>

<div style="display:none;" class="clear" id="export-log-history">
	<?php	if ( ! empty( $title ) ) : ?><h3 class="group-title"><?php echo $title; ?></h3><?php endif; ?>
	<form action="<?php echo add_query_arg( array( 'bwlmscredit-export' => 'do' ) ); ?>" method="post">
		<input type="hidden" name="token" value="<?php echo wp_create_nonce( 'bwlmscredit-run-log-export' ); ?>" />
<?php
			if ( ! empty( $exports ) ) {

				foreach ( (array) $this->args as $arg_key => $arg_value )
					echo '<input type="hidden" name="' . $arg_key . '" value="' . $arg_value . '" />';

				foreach ( (array) $exports as $id => $data ) {
					// Label
					if ( $is_profile )
						$label = $data['my_label'];
					else
						$label = $data['label'];

					echo '<input type="submit" class="' . $data['class'] . '" name="action" value="' . $label . '" /> ';
				}
?>
	</form>
	<p><span class="description"><?php _e( 'Log entries are exported to a CSV file and depending on the number of entries selected, the process may take a few seconds.', 'wptobemem' ); ?></span></p>
<?php
			}
			else {
				echo '<p>' . __( 'No export options available.', 'wptobemem' ) . '</p>';
			}
?>
</div>
<script type="text/javascript">
jQuery(function($) {
	$( '.toggle-exporter' ).click(function(){
		$( '#export-log-history' ).toggle();
	});
});
</script>
<?php
		}


		public function table_headers() {
			global $bwlmscredit_types;

			return apply_filters( 'bwlmscredit_log_column_headers', array(
				'column-username' => __( 'User', 'wptobemem' ),
				'column-time'     => __( 'Date', 'wptobemem' ),
				'column-credits'    => $this->core->plural(),
				'column-entry'    => __( 'Entry', 'wptobemem' )
			), $this );
		}


		public function display() {
			echo $this->get_display();
		}


		public function get_display() {
			$output = '
<table class="table bwlmscredit-table widefat log-entries table-striped" cellspacing="0">
	<thead>
		<tr>';

			foreach ( $this->headers as $col_id => $col_title ) {
				$output .= '<th scope="col" id="' . str_replace( 'column-', '', $col_id ) . '" class="manage-column ' . $col_id . '">' . $col_title . '</th>';
			}

			$output .= '
		</tr>
	</thead>
	<tfoot>';

			foreach ( $this->headers as $col_id => $col_title ) {
				$output .= '<th scope="col" class="manage-column ' . $col_id . '">' . $col_title . '</th>';
			}

			$output .= '
	</tfoot>
	<tbody id="the-list">';

			if ( $this->have_entries() ) {
				$alt = 0;
				
				foreach ( $this->results as $log_entry ) {
					$row_class = apply_filters( 'bwlmscredit_log_row_classes', array( 'bwlmsCREDIT-log-row' ), $log_entry );

					$alt = $alt+1;
					if ( $alt % 2 == 0 )
						$row_class[] = ' alt';

					$output .= '<tr class="' . implode( ' ', $row_class ) . '">';
					$output .= $this->get_the_entry( $log_entry );
					$output .= '</tr>';
				}
			}

			else {
				$output .= '<tr><td colspan="' . count( $this->headers ) . '" class="no-entries">' . $this->get_no_entries() . '</td></tr>';
			}

			$output .= '
	</tbody>
</table>' . "\n";

			return $output;
		}


		public function the_entry( $log_entry, $wrap = 'td' ) {
			echo $this->get_the_entry( $log_entry, $wrap );
		}


		public function get_the_entry( $log_entry, $wrap = 'td' ) {
			$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$entry_data = '';

			foreach ( $this->headers as $column_id => $column_name ) {
				switch ( $column_id ) {
					case 'column-username' :

						$user = get_userdata( $log_entry->user_id );
						if ( $user === false )
							$content = '<span>' . __( 'User Missing', 'wptobemem' ) . ' (ID: ' . $log_entry->user_id . ')</span>';
						else
							$content = '<span>' . $user->display_name . '</span>';
						
						$content = apply_filters( 'bwlmscredit_log_username', $content, $log_entry->user_id, $log_entry );

					break;
					case 'column-time' :

						$content = $time = apply_filters( 'bwlmscredit_log_date', date_i18n( $date_format, $log_entry->time ), $log_entry->time, $log_entry );

					break;
					case 'column-credits' :

						$content = $credits = $this->core->format_credits( $log_entry->credits );
						$content = apply_filters( 'bwlmscredit_log_credits', $content, $log_entry->credits, $log_entry );

					break;
					case 'column-entry' :

						$content = '<div class="bwlmscredit-mobile-log" style="display:none;">' . $time . '<div>' . $credits . '</div></div>';
						$content .= $this->core->parse_template_tags( $log_entry->entry, $log_entry );
						$content = apply_filters( 'bwlmscredit_log_entry', $content, $log_entry->entry, $log_entry );

					break;
					default :
					
						$content = apply_filters( 'bwlmscredit_log_' . $column_id, '', $log_entry );
					
					break;
				}
				$entry_data .= '<' . $wrap . ' class="' . $column_id . '">' . $content . '</' . $wrap . '>';
			}
			return $entry_data;
		}

		public function mobile_support() {
			echo '<style type="text/css">' . apply_filters( 'bwlmscredit_log_mobile_support', '
					@media all and (max-width: 480px) {
						.column-time, .column-credits { display: none; }
						.bwlmscredit-mobile-log { display: block !important; }
						.bwlmscredit-mobile-log div { float: right; font-weight: bold; }
					}
					' ) . '</style>';
		}

		public function no_entries() {
			echo $this->get_no_entries();
		}

		public function get_no_entries() {
			$no_points_entry = "<div class='row'>
									<div class='small-12 medium-12 large-12  large-centered medium-centered small-centered columns bwlmscredit_row'>
										<div class='large-2 medium-2 small-2 bwlmscredit_row_pointscol'></div>
										<div class='large-6 medium-6 small-5 bwlmscredit_row_entrycol'>
										".__( 'No log entries found', 'wptobemem' )."
										</div>
										<div class='large-4 medium-4 small-5 bwlmscredit_row_datecol'></div>
									</div>
								</div>";
			return $no_points_entry;
		}

		public function search() {
			if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) 
				$serarch_string = sanitize_text_field( $_GET['s'] );
			else
				$serarch_string = ''; ?>

			<p class="search-box">
				<label class="screen-reader-text" for=""><?php _e( 'Search Log', 'wptobemem' ); ?>:</label>
				<input type="search" name="s" value="<?php echo $serarch_string; ?>" placeholder="<?php _e( 'search log entries', 'wptobemem' ); ?>" />
				<input type="submit" name="bwlmscredit-search-log" id="search-submit" class="button button-medium button-secondary" value="<?php _e( 'Search Log', 'wptobemem' ); ?>" />
			</p>
<?php
		}

		public function filter_dates( $url = '' ) {
			$date_sorting = apply_filters( 'bwlmscredit_sort_by_time', array(
				''          => __( 'All', 'wptobemem' ),
				'today'     => __( 'Today', 'wptobemem' ),
				'yesterday' => __( 'Yesterday', 'wptobemem' ),
				'thisweek'  => __( 'This Week', 'wptobemem' ),
				'thismonth' => __( 'This Month', 'wptobemem' )
			) );

			if ( ! empty( $date_sorting ) ) {
				$total = count( $date_sorting );
				$count = 0;
				echo '<ul class="subsubsub">';
				foreach ( $date_sorting as $sorting_id => $sorting_name ) {
					$count = $count+1;
					echo '<li class="' . $sorting_id . '"><a href="';

					$url_args = array();
					if ( isset( $_GET['user_id'] ) && $_GET['user_id'] != '' ) {
						$url_args['user_id'] = absint( $_GET['user_id'] );
					}
					if ( isset( $_GET['ref'] ) && $_GET['ref'] != '' ) 
						$url_args['ref'] = sanitize_text_field( $_GET['ref'] );

					if ( isset( $_GET['order'] ) && $_GET['order'] != '' )
						$url_args['order'] = sanitize_text_field( $_GET['order'] );

					if ( isset( $_GET['s'] ) && $_GET['s'] != '' )
						$url_args['s'] =  sanitize_text_field( $_GET['s'] );

					if ( $sorting_id != '' )
						$url_args['show'] = $sorting_id;

					if ( ! empty( $url_args ) )
						echo add_query_arg( $url_args, $url );
					else
						echo $url;

					echo '"';
					
					$show_flag='';
					if ( isset( $_GET['show'] ) ) $show_flag = sanitize_text_field(  $_GET['show'] );

					if ( isset( $_GET['show'] ) && $show_flag == $sorting_id ) echo ' class="current"';
					elseif ( ! isset( $_GET['show'] ) && $sorting_id != '' ) echo ' class="current"';

					echo '>' . $sorting_name . '</a>';
					if ( $count != $total ) echo ' | ';
					echo '</li>';
				}
				echo '</ul>';
			}
		}

		public function reset_query() {
			$this->args = NULL;
			$this->request = NULL;
			$this->prep = NULL;
			$this->num_rows = NULL;
			$this->max_num_pages = NULL;
			$this->total_rows = NULL;
		
			$this->results = NULL;
		
			$this->headers = NULL;
		}
	}
endif;
?>