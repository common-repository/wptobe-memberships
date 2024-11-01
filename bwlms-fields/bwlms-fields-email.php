<?php
if ( ! function_exists( 'bwlmsfields_inc_regemail' ) ):

function bwlmsfields_inc_regemail( $user_id, $password, $toggle, $bwlmsfields_fields = null, $field_data = null )
{
	switch( $toggle ) {
	
	case 0: 
		$arr = get_option( 'bwlmsfields_email_newreg' );
		$arr['toggle'] = 'newreg';
		break;
		
	case 1:
		$arr = get_option( 'bwlmsfields_email_newmod' );
		$arr['toggle'] = 'newmod';
		break;

	case 2:
		$arr = get_option( 'bwlmsfields_email_appmod' );
		$arr['toggle'] = 'appmod';
		break;

	case 3:
		$arr = get_option( 'bwlmsfields_email_repass' );
		$arr['toggle'] = 'repass';
		break;
		
	}

	$user = new WP_User( $user_id );
	
	$arr['user_id']       = $user_id;
	$arr['user_login']    = stripslashes( $user->user_login );
	$arr['user_email']    = stripslashes( $user->user_email );
	$arr['blogname']      = wp_specialchars_decode( get_option ( 'blogname' ), ENT_QUOTES );
	$arr['exp_type']      = ( BWLMSFIELDS_USE_EXP == 1 ) ? get_user_meta( $user_id, 'exp_type', 'true' ) : '';
	$arr['exp_date']      = ( BWLMSFIELDS_USE_EXP == 1 ) ? get_user_meta( $user_id, 'expires',  'true' ) : '';
	$arr['bwlmsfields_msurl']   = get_option( 'bwlmsfields_msurl', null );
	$arr['reg_link']      = esc_url( get_user_meta( $user_id, 'bwlmsfields_reg_url', true ) );
	$arr['do_shortcodes'] = true;
	$arr['add_footer']    = true;
	$arr['disable']       = false;
	
	global $bwlmsfields_mail_from, $bwlmsfields_mail_from_name;
	add_filter( 'wp_mail_from',      'bwlmsfields_mail_from'      );
	add_filter( 'wp_mail_from_name', 'bwlmsfields_mail_from_name' );
	$default_header = ( $bwlmsfields_mail_from && $bwlmsfields_mail_from_name ) ? 'From: ' . $bwlmsfields_mail_from_name . ' <' . $bwlmsfields_mail_from . '>' : '';
	
	$arr['headers'] = apply_filters( 'bwlmsfields_email_headers', $default_header );
	
	if( ! $bwlmsfields_fields ) {
		$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
	}
	
	$arr = apply_filters( 'bwlmsfields_email_filter', $arr, $bwlmsfields_fields, $field_data );
	extract( $arr );
	
	if( ! $disable ) {

		switch( $toggle ) {
		
		case 'newreg': 
			$arr['body'] = apply_filters( 'bwlmsfields_email_newreg', $body );
			break;
			
		case 'newmod':
			$body = apply_filters( 'bwlmsfields_email_newmod', $body );
			break;

		case 'appmod':
			$body = apply_filters( 'bwlmsfields_email_appmod', $body );
			break;

		case 'repass':
			$body = apply_filters( 'bwlmsfields_email_repass', $body );
			break;
			
		}
		
		$foot = ( $add_footer ) ? get_option ( 'bwlmsfields_email_footer' ) : '';
		
		if( $do_shortcodes ) {
			$shortcd = array( '[blogname]', '[username]', '[password]', '[reglink]', '[members-area]', '[exp-type]', '[exp-data]' );
			$replace = array( $blogname, $user_login, $password, $reg_link, $bwlmsfields_msurl, $exp_type, $exp_date );

			foreach( $bwlmsfields_fields as $field ) {
				$shortcd[] = '[' . $field[2] . ']'; 
				$replace[] = get_user_meta( $user_id, $field[2], true );
			}

			$subj = str_replace( $shortcd, $replace, $subj );
			$body = str_replace( $shortcd, $replace, $body );
			$foot = ( $add_footer ) ? str_replace( $shortcd, $replace, $foot ) : '';
		}
		
		$body = ( $add_footer ) ? $body . "\r\n" . $foot : $body;

		wp_mail( $user_email, stripslashes( $subj ), stripslashes( $body ), $headers );
	
	}
	
	return;

}
endif;


if( ! function_exists( 'bwlmsfields_notify_admin' ) ):
function bwlmsfields_notify_admin( $user_id, $bwlmsfields_fields, $field_data = null )
{
	$wp_user_fields = array( 'user_login', 'user_nicename', 'user_url', 'user_registered', 'display_name', 'first_name', 'last_name', 'nickname', 'description' );
	$user     = get_userdata( $user_id );
	$blogname = wp_specialchars_decode( get_option ( 'blogname' ), ENT_QUOTES );
	
	$user_ip  = get_user_meta( $user_id, 'bwlmsfields_reg_ip', true );
	$reg_link = esc_url( get_user_meta( $user_id, 'bwlmsfields_reg_url', true ) );
	$act_link = get_bloginfo ( 'wpurl' ) . "/wp-admin/user-edit.php?user_id=".$user_id;

	$exp_type = ( BWLMSFIELDS_USE_EXP == 1 ) ? get_user_meta( $user_id, 'exp_type', 'true' ) : '';
	$exp_date = ( BWLMSFIELDS_USE_EXP == 1 ) ? get_user_meta( $user_id, 'expires',  'true' ) : '';	
	
	$field_str = '';
	foreach ( $bwlmsfields_fields as $meta ) {
		if( $meta[4] == 'y' ) {
			$name = $meta[1];
			if( ! in_array( $meta[2], bwlmsfields_get_excluded_meta( 'email' ) ) ) {
				if( ( $meta[2] != 'user_email' ) && ( $meta[2] != 'password' ) ) {
					if( $meta[2] == 'user_url' ) {
						$val = esc_url( $user->user_url );
					} elseif( in_array( $meta[2], $wp_user_fields ) ) {
						$val = esc_html( $user->$meta[2] );
					} else {
						$val = esc_html( get_user_meta( $user_id, $meta[2], 'true' ) );
					}
				
					$field_str.= "$name: $val \r\n";
				}
			}
		}
	}
	
	$shortcd = array( '[blogname]', '[username]', '[email]', '[reglink]', '[exp-type]', '[exp-data]', '[user-ip]', '[activate-user]', '[fields]' );
	$replace = array( $blogname, $user->user_login, $user->user_email, $reg_link, $exp_type, $exp_date, $user_ip, $act_link, $field_str );
	
	foreach( $bwlmsfields_fields as $field ) {
		$shortcd[] = '[' . $field[2] . ']'; 
		$replace[] = get_user_meta( $user_id, $field[2], true );
	}
	
	$arr  = get_option( 'bwlmsfields_email_notify' );
	$subj = str_replace( $shortcd, $replace, $arr['subj'] );
	$body = str_replace( $shortcd, $replace, $arr['body'] );
	
	$foot = get_option ( 'bwlmsfields_email_footer' );
	$foot = str_replace( $shortcd, $replace, $foot );
	
	$body.= "\r\n" . $foot;
	
	$body = apply_filters( 'bwlmsfields_email_notify', $body );
	
	$admin_email = apply_filters( 'bwlmsfields_notify_addr', get_option( 'admin_email' ) );

	global $bwlmsfields_mail_from, $bwlmsfields_mail_from_name;
	add_filter( 'wp_mail_from',      'bwlmsfields_mail_from'      );
	add_filter( 'wp_mail_from_name', 'bwlmsfields_mail_from_name' );
	$default_header = ( $bwlmsfields_mail_from && $bwlmsfields_mail_from_name ) ? 'From: ' . $bwlmsfields_mail_from_name . ' <' . $bwlmsfields_mail_from . '>' : '';
	
	$headers = apply_filters( 'bwlmsfields_email_headers', $default_header  );
	
	wp_mail( $admin_email, stripslashes( $subj ), stripslashes( $body ), $headers );

}
endif;

function bwlmsfields_mail_from( $email ) {
	global $bwlmsfields_mail_from;
	$bwlmsfields_mail_from = ( get_option( 'bwlmsfields_email_wpfrom' ) ) ? get_option( 'bwlmsfields_email_wpfrom' ) : $email;
    return $bwlmsfields_mail_from;
}

function bwlmsfields_mail_from_name( $name ) {	
	global $bwlmsfields_mail_from_name;
	$bwlmsfields_mail_from_name = ( get_option( 'bwlmsfields_email_wpname' ) ) ? stripslashes( get_option( 'bwlmsfields_email_wpname' ) ) : $name;
    return $bwlmsfields_mail_from_name;
}