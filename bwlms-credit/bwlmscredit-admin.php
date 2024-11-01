<?php

if ( ! class_exists( 'bwlmsCREDIT_Admin' ) ) {
	class bwlmsCREDIT_Admin {

		public $core;
		public $using_bp = false;

		function __construct( $settings = array() ) {
			$this->core = bwlmscredit();
		}

		public function load() {
			
			add_action( 'admin_head',                 array( $this, 'admin_header' ) );//User 메뉴에 스타일,스크립트 로드
			add_filter( 'manage_users_columns',       array( $this, 'custom_user_column' )                );
			add_action( 'manage_users_custom_column', array( $this, 'custom_user_column_content' ), 10, 3 );
			add_action( 'personal_options',   array( $this, 'show_my_balance' ) );
			add_action( 'bwlmscredit_init',        array( $this, 'edit_profile_actions' ) );
			add_filter( 'manage_users_sortable_columns', array( $this, 'sortable_points_column' ) );
			add_action( 'wp_ajax_bwlmscredit-inline-edit-users-balance', array( $this, 'inline_edit_user_balance' ) );
			add_action( 'in_admin_footer',         array( $this, 'admin_footer' )             );
		}

		public function edit_profile_actions() {

			do_action( 'bwlmscredit_edit_profile_action' );

			if ( isset( $_POST['bwlmscredit_adjust_users_balance_run'] ) && isset( $_POST['bwlmscredit_adjust_users_balance'] ) ) {
				
				//[POST/GET/REQUEST] Input validation 
				$validateed_balance = intval( $_POST['bwlmscredit_adjust_users_balance'] );
				if ( ! $validateed_balance ) {
				 $validateed_balance='';
				}
				extract( $validateed_balance );

				if ( wp_verify_nonce( $token, 'bwlmscredit-adjust-balance' ) ) {

					$ctype = sanitize_text_field( $ctype );
					$bwlmscredit = bwlmscredit( $ctype );

					if ( $bwlmscredit->can_edit_credits() && ! $bwlmscredit->can_edit_plugin() && $log == '' ) {
						wp_safe_redirect( add_query_arg( array( 'result' => 'log_error' ) ) );
						exit;
					}

					if ( $bwlmscredit->can_edit_credits() ) {

						$user_id = absint( $user_id );
						$amount = $bwlmscredit->number( $amount );
						$data = apply_filters( 'bwlmscredit_manual_change', array( 'ref_type' => 'user' ), $this );

						$bwlmscredit->add_credits(
							'manual',
							$user_id,
							$amount,
							$log,
							get_current_user_id(),
							$data,
							$ctype
						);

						wp_safe_redirect( add_query_arg( array( 'result' => 'balance_updated' ) ) );
						exit;

					}

				}

			}


			elseif ( isset( $get_page ) && 
					 (sanitize_text_field($_GET['page'])) == 'bwlmscredit-edit-balance' && 
					 isset($_GET['action'] ) && 
					 (sanitize_text_field ($_GET['action'])) == 'exclude' ) {

						
				$ctype = sanitize_text_field( $_GET['ctype'] );
				$bwlmscredit = bwlmscredit( $ctype );

				if ( $bwlmscredit->can_edit_credits() ) {

					//[POST/GET/REQUEST]Sanitization
					$user_id = sanitize_user( $_GET['user_id'] );

					if ( ! $bwlmscredit->exclude_user( $user_id ) ) {

						$options = $bwlmscredit->core;
						$excludes = explode( ',', $options['exclude']['list'] );
						if ( ! empty( $excludes ) ) {
							$_excludes = array();
							foreach ( $excludes as $_user_id ) {
								$_user_id = sanitize_key( $_user_id );
								if ( $_user_id == '' ) continue;
								$_excludes[] = absint( $_user_id );
							}
							$excludes = $_excludes;
						}

						if ( ! in_array( $user_id, $excludes ) ) {
							$excludes[] = $user_id;
							$options['exclude']['list'] = implode( ',', $excludes );

							$option_id = 'bwlmscredit_pref_core';
							if ( $ctype != 'bwlmscredit_default' )
								$option_id .= '_' . $ctype;

							bwlmscredit_update_option( $option_id, $options );

							bwlmscredit_delete_user_meta( $user_id, $ctype );
							bwlmscredit_delete_user_meta( $user_id, $ctype, '_total' );

							global $wpdb;

							$wpdb->delete(
								$bwlmscredit->log_table,
								array( 'user_id' => $user_id, 'ctype' => $ctype ),
								array( '%d', '%s' )
							);

							wp_safe_redirect( add_query_arg( array( 'user_id' => $user_id, 'result' => 'user_excluded' ), admin_url( 'user-edit.php' ) ) );
							exit;
						}
					}
				}
			}
		}


		public function inline_edit_user_balance() {
			check_ajax_referer( 'bwlmscredit-update-users-balance', 'token' );

			$current_user = get_current_user_id();
			if ( ! bwlmscredit_is_admin( $current_user ) )
				wp_send_json_error( 'ERROR_1' );

			//[POST/GET/REQUEST] sanitization
			$type = sanitize_text_field( $_POST['type'] );

			$bwlmscredit = bwlmscredit( $type );

			//$user_id = abs( $_POST['user'] );
			//[POST/GET/REQUEST] sanitization
			$user_id=sanitize_user( $_POST['user'] );

			if ( $bwlmscredit->exclude_user( $user_id ) )
				wp_send_json_error( array( 'error' => 'ERROR_2', 'message' => __( 'User is excluded', 'wptobemem' ) ) );

			//[POST/GET/REQUEST] sanitization
			$entry =sanitize_text_field( $_POST['entry'] );
			$entry = trim($entry);

			if ( $bwlmscredit->can_edit_credits() && ! $bwlmscredit->can_edit_plugin() && empty( $entry ) )
				wp_send_json_error( array( 'error' => 'ERROR_3', 'message' => __( 'Log Entry can not be empty', 'wptobemem' ) ) );

			//[POST/GET/REQUEST] validation 
			$san_amount = intval( $_POST['amount'] );
			if ( $san_amount == 0 || empty($san_amount) )
				wp_send_json_error( array( 'error' => 'ERROR_4', 'message' => __( 'Amount can not be zero', 'wptobemem' ) ) );
			else
				$amount = $bwlmscredit->number( $san_amount );

			$data = apply_filters( 'bwlmscredit_manual_change', array( 'ref_type' => 'user' ), $this );

			$result = $bwlmscredit->add_credits(
				'manual',
				$user_id,
				$amount,
				$entry,
				$current_user,
				$data,
				$type
			);

			if ( $result !== false )
				wp_send_json_success( $bwlmscredit->get_users_cred( $user_id, $type ) );
			else
				wp_send_json_error( array( 'error' => 'ERROR_5', 'message' => __( 'Failed to update this uses balance.', 'wptobemem' ) ) );
		}

		public function admin_header() {
			$screen = get_current_screen();
			if ( isset($screen->id) && ($screen->id == 'users') ) {
				wp_enqueue_script( 'bwlmscredit-inline-edit' );
				wp_enqueue_style( 'bwlmscredit-inline-edit' );
			}
		}

		public function custom_user_column( $columns ) {
			global $bwlmscredit_types;

			if ( count( $bwlmscredit_types ) == 1 )
				$columns['bwlmscredit_default'] = $this->core->plural();
			else {
				foreach ( $bwlmscredit_types as $type => $label ) {
					if ( $type == 'bwlmscredit_default' ) $label = $this->core->plural();
					$columns[ $type ] = $label;
				}
			}
			return $columns;
		}

		public function sortable_points_column( $columns ) {
			$bwlmscredit_types = bwlmscredit_get_types();

			if ( count( $bwlmscredit_types ) == 1 )
				$columns['bwlmscredit_default'] = 'bwlmscredit_default';
			else {
				foreach ( $bwlmscredit_types as $type => $label )
					$columns[ $type ] = $type;
			}

			return $columns;
		}

//		public function sort_by_points( $query ) {
//			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! function_exists( 'get_current_screen' ) ) return;
//			$screen = get_current_screen();
//			if ( $screen === NULL || $screen->id != 'users' ) return;
//
//			if ( isset( $query->query_vars['orderby'] ) ) {
//				global $wpdb;
//
//				$bwlmscredit_types = bwlmscredit_get_types();
//				$credit_id = $query->query_vars['orderby'];
//
//				$order = 'ASC';
//				if ( isset( $query->query_vars['order'] ) )
//					$order = $query->query_vars['order'];
//
//				$bwlmscredit = $this->core;
//				if ( isset( $_REQUEST['ctype'] ) && array_key_exists( $_REQUEST['ctype'], $bwlmscredit_types ) )
//					$bwlmscredit = bwlmscredit( $_REQUEST['ctype'] );
//
//				if ( $credit_id == 'balance' ) {
//
//					$amount = $bwlmscredit->zero();
//					if ( isset( $_REQUEST['amount'] ) )
//						$amount = $bwlmscredit->number( $_REQUEST['amount'] );
//
//					$query->query_from .= "
//					LEFT JOIN {$wpdb->usermeta} 
//						ON ({$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND {$wpdb->usermeta}.meta_key = '{$bwlmscredit->credit_id}')";
//
//					$query->query_where .= " AND meta_value = {$amount}";
//
//				}
//
//				elseif ( array_key_exists( $credit_id, $bwlmscredit_types ) ) {
//
//					$query->query_from .= "
//					LEFT JOIN {$wpdb->usermeta} 
//						ON ({$wpdb->users}.ID = {$wpdb->usermeta}.user_id AND {$wpdb->usermeta}.meta_key = '{$credit_id}')";
//
//					$query->query_orderby = "ORDER BY {$wpdb->usermeta}.meta_value+0 {$order} ";
//
//				}
//
//			}
//		}

		public function custom_user_column_content( $value, $column_name, $user_id ) {
			global $bwlmscredit_types;

			if ( ! array_key_exists( $column_name, $bwlmscredit_types ) ) return $value;

			$bwlmscredit = bwlmscredit( $column_name );

			if ( $bwlmscredit->exclude_user( $user_id ) === true ) return __( 'Excluded', 'wptobemem' );

			$user = get_userdata( $user_id );

			$ubalance = $bwlmscredit->get_users_cred( $user_id, $column_name );
			$balance = '<div id="bwlmscredit-user-' . $user_id . '-balance-' . $column_name . '">' . $bwlmscredit->before . ' <span>' . $bwlmscredit->format_number( $ubalance ) . '</span> ' . $bwlmscredit->after . '</div>';

			$total = bwlmscredit_query_users_total( $user_id, $column_name );
			$balance .= '<small style="display:block;">' . sprintf( '<strong>%s</strong>: %s', __( 'Total', 'wptobemem' ), $bwlmscredit->format_number( $total ) ) . '</small>';

			$balance = apply_filters( 'bwlmscredit_users_balance_column', $balance, $user_id, $column_name );

			$page = 'bwlmsCREDIT';
			if ( $column_name != 'bwlmscredit_default' )
				$page .= '_' . $column_name;

			// Row actions
			$row = array();
//			$row['history'] = '<a href="' . admin_url( 'admin.php?page=' . $page . '&user_id=' . $user_id ) . '">' . __( 'Hory', 'wptobemem' ) . '</a>';
			$row['adjust'] = '<a href="javascript:void(0)" class="bwlmscredit-open-points-editor" data-userid="' . $user_id . '" data-current="' . $ubalance . '" data-type="' . $column_name . '" data-username="' . $user->display_name . '">' . __( 'Adjust', 'wptobemem' ) . '</a>';

			$rows = apply_filters( 'bwlmscredit_user_row_actions', $row, $user_id, $bwlmscredit );
			$balance .= $this->row_actions( $rows );

			return $balance;
		}


		public function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if ( !$action_count )
				return '';

			$out = '<div class="' . ( $always_visible ? 'row-actions-visible' : 'row-actions' ) . '">';
			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';

			return $out;
		}


		public function get_users_total_accumulated( $user_id, $type ) {
			global $wpdb;

			return $wpdb->get_var( $wpdb->prepare( "
				SELECT SUM( credits ) 
				FROM {$this->core->log_table} 
				WHERE ctype = %s 
				AND user_id = %d 
				AND credits > 0;", $type, $user_id ) );
		}

		public function get_users_total_spent( $user_id, $type ) {
			global $wpdb;

			return $wpdb->get_var( $wpdb->prepare( "
				SELECT SUM( credits ) 
				FROM {$this->core->log_table} 
				WHERE ctype = %s 
				AND user_id = %d 
				AND credits < 0;", $type, $user_id ) );
		}


		public function show_my_balance( $user ) {
			$user_id = $user->ID;
			$bwlmscredit_types = bwlmscredit_get_types();

			foreach ( $bwlmscredit_types as $type => $label ) {
				$bwlmscredit = bwlmscredit( $type );
				if ( $bwlmscredit->exclude_user( $user_id ) ) continue;

				$balance = $bwlmscredit->get_users_cred( $user_id, $type );
				$balance = $bwlmscredit->format_credits( $balance ); ?>

				<tr>
					<th scope="row"><?php echo $bwlmscredit->template_tags_general( __( '%singular% balance', 'wptobemem' ) ); ?></th>
					<td><h2 style="margin:0;padding:0;"><?php echo $balance; ?></h2></td>
				</tr>
				<?php
			}
		}


		public function admin_footer() {
		//멤버리스트에서 포인트 수정하는 팝업화면

			if ( ! $this->core->can_edit_credits() ) return;

			$screen = get_current_screen();


			if ( $screen->id == 'users' || $screen->id == "memberships_page_bwlmsmem-memberslist" || $screen->id == "memberships_page_bwlmslevel-memberslist" ) { 

				if ( $this->core->can_edit_credits() && ! $this->core->can_edit_plugin() )
					$req = '(<strong>' . __( 'required', 'wptobemem' ) . '</strong>)'; 
				else
					$req = '(' . __( 'optional', 'wptobemem' ) . ')';

				ob_start(); ?>

					<div id="edit-bwlmscredit-balance" style="display: none;">
						<div class="bwlmscredit-adjustment-form">
							<p class="row inline" style="width: 20%"><label><?php _e( 'ID', 'wptobemem' ); ?>:</label><span id="bwlmscredit-userid"></span></p>
							<p class="row inline" style="width: 40%"><label><?php _e( 'User', 'wptobemem' ); ?>:</label><span id="bwlmscredit-username"></span></p>
							<p class="row inline" style="width: 40%"><label><?php _e( 'Current Balance', 'wptobemem' ); ?>:</label> <span id="bwlmscredit-current"></span></p>
							<div class="clear"></div>

							<input type="hidden" name="bwlmscredit_update_users_balance[token]" id="bwlmscredit-update-users-balance-token" value="<?php echo wp_create_nonce( 'bwlmscredit-update-users-balance' ); ?>" />
							<input type="hidden" name="bwlmscredit_update_users_balance[type]" id="bwlmscredit-update-users-balance-type" value="" />
							<p class="row"><label for="bwlmscredit-update-users-balance-amount"><?php _e( 'Amount', 'wptobemem' ); ?>:</label><input type="text" name="bwlmscredit_update_users_balance[amount]" id="bwlmscredit-update-users-balance-amount" value="" /><br /><span class="description"><?php _e( 'A positive or negative value', 'wptobemem' ); ?>.</span></p>
							<p class="row"><label for="bwlmscredit-update-users-balance-entry"><?php _e( 'Log Entry', 'wptobemem' ); ?>:</label><input type="text" name="bwlmscredit_update_users_balance[entry]" id="bwlmscredit-update-users-balance-entry" value="" /><br /><span class="description"><?php echo $req; ?></span></p>
							<p class="row"><input type="button" name="bwlmscredit-update-users-balance-submit" id="bwlmscredit-update-users-balance-submit" value="<?php _e( 'Update Balance', 'wptobemem' ); ?>" class="button button-primary button-large" /></p>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div>
				<?php
				
				$content = ob_get_contents();
				
				ob_end_clean();

				echo apply_filters( 'bwlmscredit_admin_inline_editor', $content );

			}

		}//end of footer function 

	
	}
}
?>