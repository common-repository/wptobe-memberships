<?php

function bwlmsfields_a_build_options( $bwlmsfields_settings )
{ 
	$admin_email = apply_filters( 'bwlmsfields_notify_addr', get_option( 'admin_email' ) );
	$chg_email   = __( sprintf( '%sChange%s or %sFilter%s this address', '<a href="' . site_url( 'wp-admin/admin.php', 'admin' ) . '">', '</a>', '<a href="http://www.wptobe.com">', '</a>' ), 'wptobemem' );

	?>
	<div class="metabox-holder">
	
		<div id="post-body">
			<div id="post-body-content">
				<div class="postbox">
					<h3><span><?php _e( 'Manage Options', 'wptobemem' ); ?></span></h3>
					<div class="inside">
						<form name="updatesettings" id="updatesettings" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
						<?php wp_nonce_field( 'bwlmsfields-update-settings' ); ?>
							<ul>
							<?php $arr = array(
								array(__('Block Posts by default','wptobemem'),'bwlmsfields_settings_block_posts',__('Note: Posts can still be individually blocked or unblocked at the article level','wptobemem')),
								array(__('Block Pages by default','wptobemem'),'bwlmsfields_settings_block_pages',__('Note: Pages can still be individually blocked or unblocked at the article level','wptobemem')),
								array(__('Show excerpts','wptobemem'),'bwlmsfields_settings_show_excerpts',__('Shows excerpted content above the login/registration on both Posts and Pages','wptobemem')),
								array(__('Notify admin','wptobemem'),'bwlmsfields_settings_notify',sprintf(__('Notify %s for each new registration? %s','wptobemem'),$admin_email,$chg_email)),
								array(__('Moderate registration','wptobemem'),'bwlmsfields_settings_moderate',__('Holds new registrations for admin approval','wptobemem')),
								array(__('Hide registration','wptobemem'),'bwlmsfields_settings_turnoff',__('Removes the registration form from blocked content','wptobemem')),
								array('','',''),
								array(__('Time-based expiration','wptobemem'),'bwlmsfields_settings_time_exp',__('Allows for access to expire','wptobemem')),
								array(__('Trial period','wptobemem'),'bwlmsfields_settings_trial',__('Allows for a trial period','wptobemem')),
								array(__('Ignore warning messages','wptobemem'),'bwlmsfields_settings_ignore_warnings',__('Ignores BWLMS-Fields warning messages in the admin panel','wptobemem'))
								);
							for( $row = 0; $row < count( $arr ); $row++ ) {
							  
							  if( $row != 7 && $row != 5 ) {  //if( $row != 7 ) {
								if(  $row < 8 || $row > 9  ) { ?>
							  <li>
								<label><?php echo $arr[$row][0]; ?></label>

								<input name="<?php echo $arr[$row][1]; ?>" type="checkbox" id="<?php echo $arr[$row][1]; ?>" value="1" <?php if( $bwlmsfields_settings[$row+1] == 1 ) { echo "checked"; }?> />&nbsp;&nbsp;
								<?php if( $arr[$row][2] ) { ?><span class="description"><?php echo $arr[$row][2]; ?></span><?php } ?>
							  </li>
							  <?php }
							  }
							} ?>
							<?php $attribution = get_option( 'bwlmsfields_attrib' ); ?>
							  <li>
								<label><?php _e( 'Attribution', 'wptobemem' ); ?></label>
								<input name="attribution" type="checkbox" id="attribution" value="1" <?php if( $attribution == 1 ) { echo "checked"; }?> />&nbsp;&nbsp;
								<span class="description"><?php _e( 'Attribution is appreciated!  Display "powered by" link on register form?', 'wptobemem' ); ?></span>
							  </li>
							<?php $auto_ex = get_option( 'bwlmsfields_autoex' ); ?>
							  <li>
							    <label><?php _e( 'Auto Excerpt:', 'wptobemem' ); ?></label>
								<input type="checkbox" name="bwlmsfields_autoex" value="1" <?php if( $auto_ex['auto_ex'] == 1 ) { echo "checked"; } ?> />&nbsp;&nbsp;&nbsp;&nbsp;<?php _e( 'Number of words in excerpt:', 'wptobemem' ); ?> <input name="bwlmsfields_autoex_len" type="text" size="5" value="<?php if( $auto_ex['auto_ex_len'] ) { echo $auto_ex['auto_ex_len']; } ?>" />&nbsp;<span class="description"><?php _e( 'Optional', 'wptobemem' ); ?>. <?php _e( 'Automatically creates an excerpt', 'wptobemem' ); ?></span>
							  </li>

							<h3><?php _e( 'Pages' ); ?></h3>
							  <?php $bwlmsfields_msurl = get_option( 'bwlmsfields_msurl' );
							  if( ! $bwlmsfields_msurl ) { $bwlmsfields_msurl = "http://"; } ?>
							  <li>
								<label><?php _e( 'User Profile Page:', 'wptobemem' ); ?></label>
								<select name="bwlmsfields_settings_mspage" id="bwlmsfields_mspage_select">
								<?php bwlmsfields_admin_page_list( $bwlmsfields_msurl ); ?>
								</select>&nbsp;<span class="description"><?php _e( 'For creating a forgot password link in the login form', 'wptobemem' ); ?></span><br />
								<div id="bwlmsfields_mspage_custom">
									<label>&nbsp;</label>
									<input class="regular-text code" type="text" name="bwlmsfields_settings_msurl" value="<?php echo $bwlmsfields_msurl; ?>" size="50" />
								</div>
							  </li>
							  <?php $bwlmsfields_regurl = get_option( 'bwlmsfields_regurl' );
							  if( ! $bwlmsfields_regurl ) { $bwlmsfields_regurl = "http://"; } ?>
							  <li>
								<label><?php _e( 'Register Page:', 'wptobemem' ); ?></label>
								<select name="bwlmsfields_settings_regpage" id="bwlmsfields_regpage_select">
									<?php bwlmsfields_admin_page_list( $bwlmsfields_regurl ); ?>
								</select>&nbsp;<span class="description"><?php _e( 'For creating a register link in the login form', 'wptobemem' ); ?></span><br />
								<div id="bwlmsfields_regpage_custom">
									<label>&nbsp;</label>	
									<input class="regular-text code" type="text" name="bwlmsfields_settings_regurl" value="<?php echo $bwlmsfields_regurl; ?>" size="50" />
								</div>
							  </li>

								<br /></br />
								<input type="hidden" name="bwlmsfields_admin_a" value="update_settings">
								<input type="submit" name="UpdateSettings"  class="button-primary" value="<?php _e( 'Update Settings', 'wptobemem' ); ?> &raquo;" /> 
							</ul>
						</form>
					</div><!-- .inside -->
				</div>
			</div><!-- #post-body-content -->
		</div><!-- #post-body -->
	</div><!-- .metabox-holder -->
	<?php
}

function bwlmsfields_update_options()
{
	check_admin_referer( 'bwlmsfields-update-settings' );

	$post_arr = array(
		'0',
		'bwlmsfields_settings_block_posts',
		'bwlmsfields_settings_block_pages',
		'bwlmsfields_settings_show_excerpts',
		'bwlmsfields_settings_notify',
		'bwlmsfields_settings_moderate',
		'bwlmsfields_settings_turnoff',
		'bwlmsfields_settings_legacy',
		'bwlmsfields_settings_time_exp',
		'bwlmsfields_settings_trial',
		'bwlmsfields_settings_ignore_warnings'
	);
				
	$bwlmsfields_newsettings = array();
	for( $row = 0; $row < count( $post_arr ); $row++ ) {
		if( $post_arr == '0' ) {
			$bwlmsfields_newsettings[$row] = '0';
		} else {
			if( isset( $_POST[$post_arr[$row]] ) != 1 ) {
				$bwlmsfields_newsettings[$row] = 0;
			} else {
				$bwlmsfields_newsettings[$row] = sanitize_text_field($_POST[$post_arr[$row]]);
			}
		}
		
		if( $row == 5 ) {
			if( isset( $_POST[$post_arr[$row]] ) == 1) {
				global $current_user;
				//get_currentuserinfo();
				$current_user = wp_get_current_user();
				$user_ID = $current_user->ID;
				update_user_meta( $user_ID, 'active', 1 );
			}
		}			
	}
	
	$bwlmsfields_attribution = ( isset( $_POST['attribution'] ) ) ? 1 : 0;
	update_option( 'bwlmsfields_attrib', $bwlmsfields_attribution );

	$bwlmsfields_settings_msurl  = ( $_POST['bwlmsfields_settings_mspage'] == 'use_custom' ) ? sanitize_text_field($_POST['bwlmsfields_settings_msurl']) : '';
	$bwlmsfields_settings_mspage = ( $_POST['bwlmsfields_settings_mspage'] == 'use_custom' ) ? '' : sanitize_text_field($_POST['bwlmsfields_settings_mspage']);
	if( $bwlmsfields_settings_mspage ) { update_option( 'bwlmsfields_msurl', $bwlmsfields_settings_mspage ); }
	if( $bwlmsfields_settings_msurl != 'http://' && $bwlmsfields_settings_msurl != 'use_custom' && ! $bwlmsfields_settings_mspage ) {
		update_option( 'bwlmsfields_msurl', trim( $bwlmsfields_settings_msurl ) );
	}

	$bwlmsfields_settings_regurl  = ( $_POST['bwlmsfields_settings_regpage'] == 'use_custom' ) ? sanitize_text_field($_POST['bwlmsfields_settings_regurl']) : '';
	$bwlmsfields_settings_regpage = ( $_POST['bwlmsfields_settings_regpage'] == 'use_custom' ) ? '' : sanitize_text_field($_POST['bwlmsfields_settings_regpage']);
	if( $bwlmsfields_settings_regpage ) { update_option( 'bwlmsfields_regurl', $bwlmsfields_settings_regpage ); }
	if( $bwlmsfields_settings_regurl != 'http://' && $bwlmsfields_settings_regurl != 'use_custom' && ! $bwlmsfields_settings_regpage ) {
		update_option( 'bwlmsfields_regurl', trim( $bwlmsfields_settings_regurl ) );
	}
	
	
	$bwlmsfields_settings_cssurl = sanitize_text_field($_POST['bwlmsfields_settings_cssurl']);
	if( $bwlmsfields_settings_cssurl != 'http://' ) {
		update_option( 'bwlmsfields_cssurl', trim( $bwlmsfields_settings_cssurl ) );
	}
	
	$bwlmsfields_settings_style = ( isset( $_POST['bwlmsfields_settings_style'] ) ) ? sanitize_text_field($_POST['bwlmsfields_settings_style']) : false;
	
	$bwlmsfields_autoex = array (
		'auto_ex'     => isset( $_POST['bwlmsfields_autoex'] ) ? sanitize_text_field($_POST['bwlmsfields_autoex']) : 0,
		'auto_ex_len' => isset( $_POST['bwlmsfields_autoex_len'] ) ? sanitize_text_field($_POST['bwlmsfields_autoex_len']) : ''
	);
	update_option( 'bwlmsfields_autoex', $bwlmsfields_autoex, false );
	
	update_option( 'bwlmsfields_settings', $bwlmsfields_newsettings );
	$bwlmsfields_settings = $bwlmsfields_newsettings;
	
	
	return __( 'User fields settings were updated', 'wptobemem' );
}

function bwlmsfields_admin_page_list( $val, $show_custom_url = true )
{
	echo '<option value="">'; echo esc_attr( __( 'Select a page' ) ); echo '</option>';
	$pages = get_pages(); 
	$selected = false;
	foreach ( $pages as $page ) {
		$selected = ( get_page_link( $page->ID ) == $val ) ? true : $selected;
		$option = '<option value="' . get_page_link( $page->ID ) . '"' . bwlmsfields_selected( get_page_link( $page->ID ), $val, 'select' ) . '>';
		$option .= $page->post_title;
		$option .= '</option>';
		echo $option;
	}
	if( $show_custom_url ) {
		$selected = ( ! $selected ) ? ' selected' : '';
		echo '<option value="use_custom"' . $selected . '>' . __( 'USE CUSTOM URL BELOW', 'wptobemem' ) . '</option>'; }
}