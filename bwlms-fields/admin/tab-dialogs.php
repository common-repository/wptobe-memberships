<?php

function bwlmsfields_a_build_dialogs()
{ 
	$bwlmsfields_dialogs  = get_option( 'bwlmsfields_dialogs' );
	$bwlmsfields_dialog_title_arr = array(
    	__( "Restricted post (or page), displays above the login/registration form", 'wptobemem' ),
        __( "Username is taken", 'wptobemem' ),
        __( "Email is registered", 'wptobemem' ),
        __( "Registration completed", 'wptobemem' ),
        __( "User update", 'wptobemem' ),
        __( "Passwords did not match", 'wptobemem' ),
        __( "Password changes", 'wptobemem' ),
        __( "Username or email do not exist when trying to reset forgotten password", 'wptobemem' ),
        __( "Password reset", 'wptobemem' ) 
    ); ?>
	<div class="metabox-holder">
	
		<div id="post-body">
			<div id="post-body-content">
				<div class="postbox">

					<div class="inside">
						
						<form name="updatedialogform" id="updatedialogform" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>"> 
						<?php wp_nonce_field( 'bwlmsfields-update-dialogs' ); ?>
							<table class="form-table">        
							<?php for( $row = 0; $row < count( $bwlmsfields_dialog_title_arr ); $row++ ) { ?>
								<tr valign="top"> 
									<th scope="row"><?php echo $bwlmsfields_dialog_title_arr[$row]; ?></th> 
									<td><textarea name="<?php echo "dialogs_".$row; ?>" rows="3" cols="50" id="" class="large-text code"><?php echo stripslashes( $bwlmsfields_dialogs[$row] ); ?></textarea></td> 
								</tr>
							<?php } ?>
							
							<?php $bwlmsfields_tos = stripslashes( get_option( 'bwlmsfields_tos' ) ); ?>
								<tr valign="top"> 
									<th scope="row"><?php _e( 'Terms of Service (TOS)', 'wptobemem' ); ?></th> 
									<td><textarea name="dialogs_tos" rows="3" cols="50" id="" class="large-text code"><?php echo $bwlmsfields_tos; ?></textarea></td> 
								</tr>		
								<tr valign="top"> 
									<th scope="row">&nbsp;</th> 
									<td>
										<input type="hidden" name="bwlmsfields_admin_a" value="update_dialogs" />
										<input type="submit" name="save" class="button-primary" value="<?php _e( 'Update Dialogs', 'wptobemem' ); ?> &raquo;" />
									</td> 
								</tr>	
							</table> 
						</form>
					</div><!-- .inside -->
				</div><!-- #post-box -->
			</div><!-- #post-body-content -->
		</div><!-- #post-body -->
	</div> <!-- .metabox-holder -->
	<?php
}

function bwlmsfields_update_dialogs()
{
	check_admin_referer( 'bwlmsfields-update-dialogs' );
	
	$bwlmsfields_dialogs = get_option( 'bwlmsfields_dialogs' );

	for( $row = 0; $row < count( $bwlmsfields_dialogs); $row++ ) {
		$dialog = "dialogs_" . $row;
		$bwlmsfields_newdialogs[$row] = esc_html($_POST[$dialog]);
	}

	update_option( 'bwlmsfields_dialogs', $bwlmsfields_newdialogs );
	$bwlmsfields_dialogs = $bwlmsfields_newdialogs;
		
		
	return __( 'BWLMS-Fields dialogs were updated', 'wptobemem' );	
}