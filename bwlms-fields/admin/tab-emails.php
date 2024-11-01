<?php

function bwlmsfields_a_build_emails( $bwlmsfields_settings )
{ 
	if( $bwlmsfields_settings[5] == 0 ) {
		$bwlmsfields_email_title_arr = array(
			array( __( "New Registration", 'wptobemem' ), 'bwlmsfields_email_newreg' )
		);
	} else {
        $bwlmsfields_email_title_arr = array(
			array( __( "Registration is Moderated", 'wptobemem' ), 'bwlmsfields_email_newmod' ),
			array( __( "Registration is Moderated, User is Approved", 'wptobemem' ), 'bwlmsfields_email_appmod' )
		);
	}
	array_push( 
		$bwlmsfields_email_title_arr,
        array( __( "Password Reset", 'wptobemem' ), 'bwlmsfields_email_repass' )
	);
	if( $bwlmsfields_settings[4] == 1 ) {
		array_push(
			$bwlmsfields_email_title_arr,
			array( __( "Admin Notification", 'wptobemem' ), 'bwlmsfields_email_notify' )
		);
	}
	array_push(
		$bwlmsfields_email_title_arr,
		array( __( "Email Signature", 'wptobemem' ), 'bwlmsfields_email_footer' )
    ); ?>
	<div class="metabox-holder">

		<div id="post-body">
			<div id="post-body-content">
				<div class="postbox">	
					<div class="inside">

						<form name="updateemailform" id="updateemailform" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
						<?php wp_nonce_field( 'bwlmsfields-update-emails' ); ?>
							<table class="form-table"> 
								<tr valign="top"> 
									<th scope="row"><?php _e( 'Set a custom email address', 'wptobemem' ); ?></th> 
									<td><input type="text" name="wp_mail_from" size="40" value="<?php echo get_option( 'bwlmsfields_email_wpfrom' ); ?>" /></td> 
								</tr>
								<tr valign="top"> 
									<th scope="row"><?php _e( 'Set a custom email name', 'wptobemem' ); ?></th> 
									<td><input type="text" name="wp_mail_from_name" size="40" value="<?php echo stripslashes( get_option( 'bwlmsfields_email_wpname' ) ); ?>" /></td>
								</tr>
								<tr><td colspan="2"><hr /></td></tr>
							
							<?php for( $row = 0; $row < ( count( $bwlmsfields_email_title_arr ) - 1 ); $row++ ) { 
							
								$arr = get_option( $bwlmsfields_email_title_arr[$row][1] );
							?>
								<tr valign="top"><td colspan="2"><strong><?php echo $bwlmsfields_email_title_arr[$row][0]; ?></strong></td></tr>
								<tr valign="top"> 
									<th scope="row"><?php _e( 'Subject', 'wptobemem' ); ?></th> 
									<td><input type="text" name="<?php echo $bwlmsfields_email_title_arr[$row][1] . '_subj'; ?>" size="80" value="<?php echo stripslashes( $arr['subj'] ); ?>"></td> 
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'Body', 'wptobemem' ); ?></th>
									<td><textarea name="<?php echo $bwlmsfields_email_title_arr[$row][1] . '_body'; ?>" rows="12" cols="50" id="" class="large-text code"><?php echo stripslashes( $arr['body'] ); ?></textarea></td>
								</tr>
								<tr><td colspan="2"><hr /></td></tr>
							<?php } 
							
								$arr = get_option( $bwlmsfields_email_title_arr[$row][1] ); ?>
							
								<tr valign="top">
									<th scope="row"><strong><?php echo $bwlmsfields_email_title_arr[$row][0]; ?></strong> <span class="description"><?php _e( '(optional)', 'wptobemem' ); ?></span></th>
									<td><textarea name="<?php echo $bwlmsfields_email_title_arr[$row][1] . '_body'; ?>" rows="10" cols="50" id="" class="large-text code"><?php echo stripslashes( $arr ); ?></textarea></td>
								</tr>
								<tr><td colspan="2"><hr /></td></tr>			
								<tr valign="top"> 
									<th scope="row">&nbsp;</th> 
									<td>
										<input type="hidden" name="bwlmsfields_admin_a" value="update_emails" />
										<input type="submit" name="save" class="button-primary" value="<?php _e( 'Update Emails', 'wptobemem' ); ?> &raquo;" />
									</td> 
								</tr>	
							</table> 
						</form>
					</div><!-- .inside -->
				</div><!-- #post-box -->

			</div> <!-- #post-body-content -->
		</div><!-- #post-body -->
	</div><!-- .metabox-holder -->
	<?php
}


function bwlmsfields_update_emails()
{
	check_admin_referer( 'bwlmsfields-update-emails' );
	
	$bwlmsfields_settings = get_option( 'bwlmsfields_settings' );
			
	( $_POST['wp_mail_from'] ) ? update_option( 'bwlmsfields_email_wpfrom', sanitize_email( $_POST['wp_mail_from'] ) ) : delete_option( 'bwlmsfields_email_wpfrom' );

	( $_POST['wp_mail_from_name'] ) ? update_option( 'bwlmsfields_email_wpname', sanitize_text_field($_POST['wp_mail_from_name']) ) : delete_option( 'bwlmsfields_email_wpname' );
			
	( $bwlmsfields_settings[5] == 0 ) ? $arr = array( 'bwlmsfields_email_newreg' ) : $arr = array( 'bwlmsfields_email_newmod', 'bwlmsfields_email_appmod' );
	array_push( $arr, 'bwlmsfields_email_repass' );
	( $bwlmsfields_settings[4] == 1 ) ? array_push( $arr, 'bwlmsfields_email_notify' ) : false;
	array_push(	$arr, 'bwlmsfields_email_footer' );
			
	for( $row = 0; $row < ( count( $arr ) - 1 ); $row++ ) {
		$arr2 = array( 
			"subj" => sanitize_text_field($_POST[$arr[$row] . '_subj']),
			"body" => sanitize_text_field($_POST[$arr[$row] . '_body'])
		);
		update_option( $arr[$row], $arr2, false );
		$arr2 = '';
	}
			
	update_option( $arr[$row], sanitize_text_field($_POST[$arr[$row] . '_body']), false );
			
	return __('BWLMS-Fields emails were updated', 'wptobemem');

}