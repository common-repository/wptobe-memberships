<?php

if ( ! function_exists( 'bwlmsfields_mainfunc' ) ):


function bwlmsfields_mainfunc()
{	
	global $bwlmsfields_act, $bwlmsfields_regchk;

	$bwlmsfields_act = ( isset( $_REQUEST['a'] ) ) ? sanitize_text_field( $_REQUEST['a'] ) : '';

	switch ($bwlmsfields_act) {

	case ( 'login' ):
		$bwlmsfields_regchk = bwlmsfields_login();
		break;

	case ( 'logout' ):
		bwlmsfields_logout();
		break;

	case ( 'register' ):
		require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-register.php");

		$bwlmsfields_regchk = bwlmsfields_registration( 'register' );
		break;
	
	case ( 'update' ):
		require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-register.php");
		$bwlmsfields_regchk = bwlmsfields_registration( 'update' );
		// $bwlmsfields_regchk : updaterr, editsuccess, email(If e-mail exists), $bwlmsfields_themsg(Error!)
		break;
	
	case ( 'pwdchange' ):
		$bwlmsfields_regchk = bwlmsfields_change_password();
		break;
	
	case ( 'pwdreset' ):
		$bwlmsfields_regchk = bwlmsfields_reset_password();
		break;

	} // end of switch $a (action)

	$bwlmsfields_regchk = apply_filters( 'bwlmsfields_regchk', $bwlmsfields_regchk, $bwlmsfields_act );
}
endif;


if ( ! function_exists( 'bwlmsfields_securify' ) ):

function bwlmsfields_securify( $content = null ) 
{ 
	$content = ( is_single() || is_page() ) ? $content : bwlmsfields_do_excerpt( $content );

	if ( ( ! bwlmsfields_test_shortcode( $content, 'bwlms-fields' ) ) ) {	
		global $bwlmsfields_regchk, $bwlmsfields_themsg, $bwlmsfields_act;
		
		if( !is_user_logged_in() && bwlmsfields_block() == true ) {
		
			global $post;
			$post->post_password = apply_filters( 'bwlmsfields_post_password' , wp_generate_password() );
		
			require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-dialogs.php");
			if( $bwlmsfields_regchk ) {

				$content = '';
	
				switch( $bwlmsfields_regchk ) {
	
				case "loginfailed":
					$content = bwlmsfields_inc_loginfailed();
					break;
	
				case "success":
					$content = bwlmsfields_inc_regmessage( $bwlmsfields_regchk, $bwlmsfields_themsg );
					$content = $content . bwlmsfields_inc_login();
					break;
	
				default:
					$content = bwlmsfields_inc_regmessage( $bwlmsfields_regchk, $bwlmsfields_themsg );
					$content = $content . bwlmsfields_inc_registration();
					break;
				}
	
			} else {
			
				if( BWLMSFIELDS_SHOW_EXCERPT == 1 ) {

					if( ! stristr( $content, '<span id="more' ) ) {
						$content = bwlmsfields_do_excerpt( $content );
					} else {
						$len = strpos( $content, '<span id="more' );
						$content = substr( $content, 0, $len );
					}
					
				} else {
				
					$content = '';
				
				}
	
				$content = $content . bwlmsfields_inc_login();
				
				$content = ( BWLMSFIELDS_NO_REG != 1 ) ? $content . bwlmsfields_inc_registration() : $content;
			}
	
		} elseif( is_user_logged_in() && bwlmsfields_block() == true ){

			$content = ( BWLMSFIELDS_USE_EXP == 1 && function_exists( 'bwlmsfields_do_expmessage' ) ) ? bwlmsfields_do_expmessage( $content ) : $content;
			
		}
		
	}
	
	$content = apply_filters( 'bwlmsfields_securify', $content );
	
	if( strstr( $content, '[bwlmsfields_txt]' ) ) {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_content', 'wptexturize' );
		add_filter( 'the_content', 'bwlmsfields_texturize', 99 ); 
	}

	return $content;
	
} 
endif;


if ( ! function_exists( 'bwlmsfields_do_sc_pages' ) ):

function bwlmsfields_do_sc_pages( $page )
{
	global $bwlmsfields_regchk, $bwlmsfields_themsg, $bwlmsfields_act;
	require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-dialogs.php");
	$content = '';
	
	$page = ( $page == 'user-profile' ) ? 'members-area' : $page;

	if ( $page == 'members-area' || $page == 'register' ) { 
	
		if( $bwlmsfields_regchk == "loginfailed" ) {
			return bwlmsfields_inc_loginfailed();
		}
		
		if( ! is_user_logged_in() ) {

			if( $bwlmsfields_act == 'register' ) {

				switch( $bwlmsfields_regchk ) {
					case "success":
						$content = bwlmsfields_inc_regmessage( $bwlmsfields_regchk,$bwlmsfields_themsg );
						$content = $content . bwlmsfields_inc_login();
						break;

					default:
						$content = bwlmsfields_inc_registration();
						$content .=	 bwlmsfields_inc_regmessage( $bwlmsfields_regchk,$bwlmsfields_themsg );
						break;
				}

			} 
			elseif( $bwlmsfields_act == 'pwdreset' ) {

				$content = bwlmsfields_page_pwd_reset( $bwlmsfields_regchk, $content );
			} 
			else {

				//$content = ( $page == 'members-area' ) ? $content . bwlmsfields_inc_login( 'members' ) : $content;
				$content = ( $page == 'members-area' ) ? $content . bwlmsfields_inc_login( 'members' ) : $content;

				//$content = ( $page == 'register' || BWLMSFIELDS_NO_REG != 1 ) ? $content . bwlmsfields_inc_registration() : $content;
				$content = ( $page == 'register' ) ? $content .bwlmsfields_inc_registration()  : $content;
			}
		} 
		elseif( is_user_logged_in() && $page == 'members-area' ) {
		
			switch( $bwlmsfields_act ) {

			case "edit":
				$content = $content . bwlmsfields_inc_registration( 'edit');
				break;

			case "update": // $bwlmsfields_regchk : updaterr, editsuccess, email(기존에 이메일이 존재하면), $bwlmsfields_themsg(에러메시지 텍스트)

				if( $bwlmsfields_regchk == "updaterr" || $bwlmsfields_regchk == "email" ) {
					//Save Changes > Fail message
					$content = $content . bwlmsfields_inc_memberlinks();

				} 
				else {
					//Save Changes > Success message 
					$content = $content . bwlmsfields_inc_memberlinks();
				}
				break;

			case "pwdchange":
				// 유저 프로파일 페이지에서 넘어갈 때 , 액션 이후 메시지가 추가될 때 보여주는 페이지
				$content = __('','wptobemem');
				$content = '<div class="row fullwidthrow_bwlmsfields_btn">
								<div class="small-12 medium-12 large-12  large-centered medium-centered small-centered columns bwlmsfields_centered_col">
									<div class="chgpasswd-headline">' .$content. '</div>
								</div>
							</div>
							';
				$content .= bwlmsfields_page_pwd_reset( $bwlmsfields_regchk, $content );
				
				break;

			case "renew":
				$content = bwlmsfields_renew();
				break;

			default:
				$content = bwlmsfields_inc_memberlinks();
				break;					  
			}

		} elseif( is_user_logged_in() && $page == 'register' ) {
			
			$content = $content . bwlmsfields_inc_memberlinks( 'register' );
		
		}
			
	}
	
	if( $page == 'login' ) {
		$content = ( $bwlmsfields_regchk == "loginfailed" ) ? bwlmsfields_inc_loginfailed() : $content; 
		$content = ( ! is_user_logged_in() ) ? $content . bwlmsfields_inc_login( 'login' ) : bwlmsfields_inc_memberlinks( 'login' );
	}
	
	if( $page == 'password' ) {
		$content = bwlmsfields_page_pwd_reset( $bwlmsfields_regchk, $content );
	}
	
	if( $page == 'user-edit' ) {
		$content = bwlmsfields_page_user_edit( $bwlmsfields_regchk, $content );
	}
	
	return $content;
} 
endif;


if ( ! function_exists( 'bwlmsfields_block' ) ):
function bwlmsfields_block()
{
	global $post; 
	
	$unblock_meta = get_post_custom_values( 'unblock', $post->ID );
	$block_meta   = get_post_custom_values( 'block',   $post->ID );

	$block = false;
	
	if( is_single() ) {
		if( BWLMSFIELDS_BLOCK_POSTS == 1 && ! get_post_custom_values( 'unblock' ) ) { $block = true; }	
		if( BWLMSFIELDS_BLOCK_POSTS == 0 &&   get_post_custom_values( 'block' ) )   { $block = true; }
	}

	if( is_page() && ! is_page( 'members-area' ) && ! is_page( 'register' ) ) { 
		if( BWLMSFIELDS_BLOCK_PAGES == 1 && ! get_post_custom_values( 'unblock' ) ) { $block = true; }
		if( BWLMSFIELDS_BLOCK_PAGES == 0 &&   get_post_custom_values( 'block' ) )   { $block = true; }
	}
	
	return apply_filters( 'bwlmsfields_block', $block );
}
endif;


if ( ! function_exists( 'bwlmsfields_sc' ) ):

function bwlmsfields_sc( $attr, $content = null, $tag = 'bwlms-fields' )
{
	$defaults = array(
		'page'      => false,
		'url'       => false,
		'status'    => false,
		'msg'       => false,
		'field'     => false,
		'id'        => false
	);

	extract( shortcode_atts( $defaults, $attr, $tag ) );

	if( $page ) {
		if( $page == 'user-list' ) {
			if( function_exists( 'bwlmsfields_list_users' ) ) {
				$content = do_shortcode( bwlmsfields_list_users( $attr, $content ) );
			}
		} elseif( $page == 'tos' ) {
			return $url;
		} else {
			$content = do_shortcode( bwlmsfields_do_sc_pages( $page ) );
		}
		
		if( strstr( $content, '[bwlmsfields_txt]' ) ) {
			remove_filter( 'the_content', 'wpautop' );
			remove_filter( 'the_content', 'wptexturize' );
			add_filter( 'the_content', 'bwlmsfields_texturize', 99 ); 
		}
		return $content;
	}
	
	if( ( $status ) || $tag == 'bwlmsfields_loggedin' ) {
	
		$do_return = false;
		
		if( $tag == 'bwlmsfields_loggedin' && ( ! $attr ) && is_user_logged_in() )
			$do_return = true;
		
		if( $status == 'in' && is_user_logged_in() )
			$do_return = true;
	
		if( $status == 'out' && ! is_user_logged_in() ) 
			$do_return = true;
		
		if( $status == 'sub' && is_user_logged_in() ) {
			if( BWLMSFIELDS_USE_EXP == 1 ) {	
				if( ! bwlmsfields_chk_exp() ) { 
					$do_return = true;
				} elseif( $msg == true ) {
					$do_return = true;
					$content = bwlmsfields_sc_expmessage();
				}
			}
		}
		
		return ( $do_return ) ? do_shortcode( $content ) : '';
	}
	
	if( $tag == 'bwlmsfields_logged_out' && ( ! $attr ) && ! is_user_logged_in() ) {
		return do_shortcode( $content );
	}

	if( $field || $tag == 'bwlms_userfield' ) {
		if( $id ) {

			if( $id == 'get' ) {
				$the_user_ID = ( isset( $_GET['uid'] ) ) ? intval($_GET['uid']) : '';
			} else {
				$the_user_ID = $id;
			}
		} else {

			$the_user_ID = get_current_user_id();
		}
		$user_info = get_userdata( $the_user_ID );	

		return ( $user_info ) ? htmlspecialchars( $user_info->$field ) . do_shortcode( $content ) : do_shortcode( $content );
	}
	
	if( is_user_logged_in() && $tag == 'bwlmsfields_logout' ) {
		$link = ( $url ) ? bwlmsfields_chk_qstr( $url ) . 'a=logout' : bwlmsfields_chk_qstr( get_permalink() ) . 'a=logout';
		$text = ( $content ) ? $content : __( 'Click here to log out.', 'wptobemem' );
		return do_shortcode( "<a href=\"$link\">$text</a>" );
	}
	
}
endif;


if( ! function_exists( 'bwlmsfields_check_activated' ) ):

function bwlmsfields_check_activated( $user, $username, $password ) 
{
	$pass = ( ( ! is_wp_error( $user ) ) && $password ) ? wp_check_password( $password, $user->user_pass, $user->ID ) : false;
	
	if( ! $pass ) { 
		return $user; 
	}

	$active = get_user_meta( $user->ID, 'active', 1 );
	if( $active != 1 ) {
		return new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: User has not been activated.', 'wptobemem' ) );
	}

	return $user;
}
endif;


if( ! function_exists( 'bwlmsfields_login' ) ):

function bwlmsfields_login()
{
	if( $_POST['log'] && $_POST['pwd'] ) {
		
		$user_login = sanitize_user( $_POST['log'] );
		$rememberme = false;
		if (isset( $_POST['rememberme'] )) { 
			if (sanitize_text_field( $_POST['rememberme'] ) == 'forever') $rememberme = true;
		}
		
		$creds = array();
		$creds['user_login']    = $user_login;
		$creds['user_password'] = $_POST['pwd'];
		$creds['remember']      = $rememberme;
		
		$user = wp_signon( $creds, false );

		if( ! is_wp_error( $user ) ) {
			
			wp_set_auth_cookie( $user->ID, $rememberme );
			
			$redirect_to = ( isset( $_POST['redirect_to'] ) ) ? $_POST['redirect_to'] : $_SERVER['REQUEST_URI'];
			
			$redirect_to = apply_filters( 'bwlmsfields_login_redirect', $redirect_to, $user->ID );
			
			wp_redirect( $redirect_to );
			
			exit();
			
		} else {
		
			return "loginfailed";
		}
	
	} else {
		return "loginfailed";
	}	

} 
endif;


if ( ! function_exists( 'bwlmsfields_logout' ) ):

function bwlmsfields_logout()
{
	$redirect_to = apply_filters( 'bwlmsfields_logout_redirect', get_bloginfo( 'url' ) );

	wp_clear_auth_cookie();
	do_action( 'wp_logout' );
	nocache_headers();

	wp_redirect( $redirect_to );
	exit();
}
endif;


if ( ! function_exists( 'bwlmsfields_login_status' ) ):

function bwlmsfields_login_status()
{
		require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-dialogs.php");
		if (is_user_logged_in()) { echo bwlmsfields_inc_memberlinks( 'status' ); }
}
endif;

if ( ! function_exists( 'bwlmsfields_change_password' ) ):

function bwlmsfields_change_password()
{
	global $user_ID;
	if( isset( $_POST['formsubmit'] ) ) {

		$pass1 = $_POST['pass1'];
		$pass2 = $_POST['pass2'];
		
		if ( ! $pass1 && ! $pass2 ) { 
		
			return "pwdchangempty";

		} elseif ( $pass1 != $pass2 ) { 

			return "pwdchangerr";

		} else {

			wp_update_user( array ( 'ID' => $user_ID, 'user_pass' => $pass1 ) );
			
			do_action( 'bwlmsfields_pwd_change', $user_ID );
			
			return "pwdchangesuccess";

		}
	}
	return;
}
endif;


if( ! function_exists( 'bwlmsfields_reset_password' ) ):

function bwlmsfields_reset_password()
{ 
	if( isset( $_POST['formsubmit'] ) ) {

		$arr = apply_filters( 'bwlmsfields_pwdreset_args', array( 
			'user'  => ( isset( $_POST['user']  ) ) ? sanitize_user($_POST['user'])  : '', 
			'email' => ( isset( $_POST['email'] ) ) ? sanitize_email($_POST['email']) : ''
		) );

		if( ! $arr['user'] || ! $arr['email'] ) { 
			return "pwdreseterr";

		} else {

			if( username_exists( $arr['user'] ) ) {

				$user = get_user_by( 'login', $arr['user'] );
				
				if( strtolower( $user->user_email ) !== strtolower( $arr['email'] ) || ( ( BWLMSFIELDS_MOD_REG == 1 ) && ( get_user_meta( $user->ID,'active', true ) != 1 ) ) ) {
					return "pwdreseterr";
					
				} else {
					
					$new_pass = wp_generate_password();
					
					wp_update_user( array ( 'ID' => $user->ID, 'user_pass' => $new_pass ) );

					require_once( 'bwlms-fields-email.php' );
					bwlmsfields_inc_regemail( $user->ID, $new_pass, 3 );
					
					do_action( 'bwlmsfields_pwd_reset', $user->ID );
					
					return "pwdresetsuccess";
				}
			} else {

				return "pwdreseterr";
			}
		}
	}
	return;
}
endif;


if( ! function_exists( 'bwlmsfields_chkpermission_pwreset' ) ):

function bwlmsfields_chkpermission_pwreset() {

	if( strpos( $_POST['user_login'], '@' ) ) {
		$user_email = sanitize_email($_POST['user_login']);
		$user = get_user_by( 'email', $user_email );
	} else {
		$username = sanitize_user( $_POST['user_login']);
		$user     = get_user_by( 'login', $username );
	}

	if( BWLMSFIELDS_MOD_REG == 1 ) { 
		if( get_user_meta( $user->ID, 'active', true ) != 1 ) {
			return false;
		}
	}
	
	return true;
}
endif;



function bwlmsfields_head() {}

function bwlmsfields_registration_form() {
	require_once(BWLMSMEM_DIR . "/bwlms-fields/wpnative-register.php" );
	bwlmsfields_do_wp_register_form();
}

function bwlmsfields_registration_validate( $errors, $sanitized_user_login, $user_email )
{
	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
	$exclude = bwlmsfields_get_excluded_meta( 'register' );

	foreach( $bwlmsfields_fields as $field ) {
		$is_error = false;
		if( $field[5] == 'y' && $field[2] != 'user_email' && ! in_array( $field[2], $exclude ) ) {
			if( ( $field[3] == 'checkbox' ) && ( ! isset( $_POST[$field[2]] ) ) ) {
				$is_error = true;
			} 
			if( ( $field[3] != 'checkbox' ) && ( ! $_POST[$field[2]] ) ) {  
				$is_error = true;
			}
			if( $is_error ) { $errors->add( 'bwlmsfields_error', sprintf( __('%s is a required field.[222222222222222222]', 'wptobemem'), $field[1] ) ); }
		}
	}

	return $errors;
}

function bwlmsfields_to_wp_registration( $user_id )
{
	$native_reg = ( isset( $_POST['wp-submit'] ) && $_POST['wp-submit'] == esc_attr( __( 'Register' ) ) ) ? true : false;
	$add_new  = ( isset( $_POST['action'] ) && $_POST['action'] == 'createuser' ) ? true : false;
	if( $native_reg || $add_new ) {
		// get the fields
		$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
		// get any excluded meta fields
		$exclude = bwlmsfields_get_excluded_meta( 'register' );
		foreach( $bwlmsfields_fields as $meta ) {
			if ( isset( $_POST[$meta[2]] ) && ! in_array( $meta[2], $exclude ) ) {
				update_user_meta( $user_id, $meta[2], sanitize_text_field( $_POST[$meta[2]] ) );
			}
		}
	}
	return;
}


if ( ! function_exists( 'bwlmsfields_create_formfield' ) ):
function bwlmsfields_create_formfield( $name, $type, $value, $valtochk=null, $class='textbox', $origin=false, $spfield='wpgeneralfield',$placeholder='',$user_id='' )
{
	$str="";

	if ($name=="password" || $name=="confirm_password") {
		//In case of password field
		$str = "<input name=\"$name\" type=\"password\" id=\"$name\" class=\"$class signup-input\" placeholder=\"$placeholder\" />";
		return $str;
	}

	switch( $type ) {
		case "file":

			$class = ( $class == 'textbox' ) ? "file" : $class;
			$upload_dir = wp_upload_dir();
			
			if($user_id=='') {
			// General user
				if($value)	{
				 //Found an uploaded file
					$file_blah = getimagesize($upload_dir['baseurl'] ."/".$value);

					$file_blah['mime'];
					if(substr($file_blah['mime'],0,5)=="image"  )	{
						$str = "
							<div id='d_".$name."' class='bwlmsfields-profile-image-frame-picture'>
								<img class='bwlmsfields_profilepic' src='".$upload_dir['baseurl'] ."/".$value."' ></img>
							</div>
							
							<a href='#none' onClick=\"javascript:user_del_img('".$name."','".$upload_dir['basedir']."/".$value."');\">
							<div class='bwlmsfields_file_delbtn'> </div>
							</a>
							
							<div class='bwlmsfields_file_upbtn'>
								<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class bwlmsfields-profile-image-uploadbtn\" />
								<div class=\"bwlmsfields-profile-image-upload-overlay-layer\">Upload Profile Photo</div>
							</div>
						" ;
					}
					else
					{// Not an image file
						$str = "
							<a href='#none' style='cursor:pointer;' onClick=\"javascript:user_del_img('".$name."','".$upload_dir['basedir']."/".$value."');\">
							</a>
							<div class='bwlmsfields_file_downlink'>
								<a href='".$upload_dir['baseurl'] ."/".$value."' target=_blank>".$value."</a>
							</div>
							<div class='bwlmsfields_file_upbtn'>
								<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class bwlmsfields-profile-image-uploadbtn\" />
								<div class=\"bwlmsfields-profile-image-upload-overlay-layer\">Upload Profile Photo</div>
							</div>
						";
					}
				}

				else {
				// No file 
					$str = " 
						<div class='bwlmsfields-profile-image-frame-picture'>
							<img class='bwlmsfields_profilepic' src='". plugin_dir_url( dirname( __FILE__ ) ) . 'images/nopicture.png'."' ></img>
						</div>
						<div class='bwlmsfields_file_upbtn'>
							<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class bwlmsfields-profile-image-uploadbtn\" />
							<div class=\"bwlmsfields-profile-image-upload-overlay-layer\">Upload Profile Photo</div>
						</div>
					";
				}
			}
			
			else {
			// user_id(Administrator) (Used in user-profile.php)
				if($value)	{
				//Found a uploaded file
					$file_blah = getimagesize($upload_dir['baseurl'] ."/".$value);
					//$file_blah['mime'];
					if(substr($file_blah['mime'],0,5)=="image"  )
					{
						$str = "
							<div id='d_".$name."'>
								<img src='".$upload_dir['baseurl'] ."/".$value."' width='100' height='100'>
								<a href='#none'  onClick=\"javascript:user_del_img_admin('".$user_id."','".$name."','".$upload_dir['basedir']."/".$value."');\">". __('Delete','wptobemem')."
								</a>
							</div>
								<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class\" />
						" ;
					}
					else{
					// If it is not an image file
						$str = "<a href='#none' onClick=\"javascript:user_del_img_admin('".$user_id."','".$name."','".$upload_dir['basedir']."/".$value."');\">
								". __('Delete','wptobemem') ."
								</a>
								<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class\" />
								<a href='".$upload_dir['baseurl'] ."/".$value."' target=_blank>".$value."</a>";
					}
				}
				else{
				// If there is no file
					$str = "<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" class=\"$class\" />";
				}
			}
			break;

		case "checkbox":
			if( $class == 'textbox' ) { $class = "checkbox"; }
			$str = "<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\"" . bwlmsfields_selected( $value, $valtochk, $type ) . " />";
			$str .="<label for=\"$name\"> $placeholder </label>";

			break;

		case "text":
			$value = stripslashes( esc_attr( $value ) );
			$str ="<input name=\"$name\" type=\"$type\" id=\"$name\" value=\"$value\" placeholder=\"$placeholder\" class=\"$class signup-input\" />";
			break;

		case "textarea":
			$value = stripslashes( esc_textarea( $value ) );
			if( $class == 'textbox' ) { $class = "textarea"; }
			$str = "<textarea cols=\"30\" rows=\"5\" name=\"$name\" id=\"$name\" placeholder=\"$placeholder\" class=\"$class signup-input\">$value</textarea>";
			break;

		case "password":
			//$str = "<input name=\"$name\" type=\"$type\" id=\"$name\" class=\"$class signup-input\" placeholder=\"$placeholder\" />";
			$str = "<input name=\"$name\" type=\"password\" id=\"$name\" class=\"$class signup-input\" placeholder=\"$placeholder\" />";
			break;

		case "hidden":
			$str = "<input name=\"$name\" type=\"$type\" value=\"$value\" />";
			break;

		case "option":
			$str = "<option value=\"$value\" " . bwlmsfields_selected( $value, $valtochk, 'select' ) . " >$name</option>";
			break;

		case "select": //Fields settings 
			if( $class == 'textbox' ) { $class = "dropdown"; }

			if( $origin==true) {
				// Wordpress native field 
				$str = "<select name=\"$name\" id=\"$name\" class=\"$class\">\n";
				if($spfield=='wpdescription') { 
					$str .= "<option value=\"textarea\"" . bwlmsfields_selected( 'textarea', $valtochk, 'select' ). ">" . 'textarea' . "</option>\n";
				}
				else {//WP native field 
					$str .= "<option value=\"text\"" . bwlmsfields_selected( 'text', $valtochk, 'select' ). ">" . 'text' . "</option>\n";
				}
			}
			else if($origin==false && $spfield=='password'){ //Custom password field
				$str = "<select name=\"$name\" id=\"$name\" class=\"$class\">\n";
				$str .= "<option value=\"text\"" . bwlmsfields_selected( 'text', $valtochk, 'select' ). ">" . 'password' . "</option>\n";
			}
			else { // Custom fields (Choose a type in 6
				$str = "<select name=\"$name\" id=\"$name\" class=\"$class\">\n";
				foreach( $value as $option ) {
					$str .= "<option value=\"$option\"" . bwlmsfields_selected( $option, $valtochk, 'select' ) . ">" . __( $option, 'wptobemem' ) . "</option>\n";
				}
			}

			$str .= "</select>";
			break;

		case "dropdown":
			$class = ( $class == 'textbox' ) ? "dropdown" : $class;
			$str = "<select name=\"$name\" id=\"$name\" class=\"$class\">\n";

			foreach ( $value as $option ) {
				$pieces = explode( '|', $option );
				$str .=  "<option value=\"$pieces[1]\"" . bwlmsfields_selected( $pieces[1], $valtochk, 'select' ) . ">" . __( $pieces[0], 'wptobemem' ) . "</option>\n";
			}
			$str .= "</select>";
			break;

		case "captcha":
			$placeholder = 'Input Text';

			$str = '<input type="hidden" name="do" value="contact">';
			  // show captcha HTML using Securimage::getCaptchaHtml()
			  //require_once 'securimage.php';
			  $options = array();
			  $options['input_name']             = 'bwlms_captcha'; // change name of input element for form post
			  $options['disable_flash_fallback'] = false; // allow flash fallback

			  if (!empty($_POST['ctform']['captcha_error'])) {
				// error html to show in captcha output
				$options['error_html'] = $_POST['ctform']['captcha_error'];
			  }

			  $str .=  "<div id='captcha_container_1'>\n";
			  $str .= Securimage::getCaptchaHtml($options);
			  $str .= "\n</div>\n";

			break;
	}//Switch case
	
	return $str;
}
endif;



if ( ! function_exists( 'bwlmsfields_selected' ) ):

function bwlmsfields_selected( $value, $valtochk, $type=null )
{
	$issame = ( $type == 'select' ) ? ' selected' : ' checked';
	if( $value == $valtochk ){ return $issame; }
}
endif;


if ( ! function_exists( 'bwlmsfields_chk_qstr' ) ):

function bwlmsfields_chk_qstr( $url = null )
{
	$permalink = get_option( 'permalink_structure' );
	if( ! $permalink ) {
		if( ! $url ) { $url = get_option( 'home' ) . "/?" . $_SERVER['QUERY_STRING']; }
		$return_url = $url . "&amp;";
	} else {
		if( !$url ) { $url = get_permalink(); }
		$return_url = $url . "?";
	}
	return $return_url;
}
endif;

if ( ! function_exists( 'bwlmsfields_generatePassword' ) ):

function bwlmsfields_generatePassword()
{	
	return substr( md5( uniqid( microtime() ) ), 0, 7);
}
endif;


if ( ! function_exists( 'bwlmsfields_texturize' ) ):
function bwlmsfields_texturize( $content ) 
{
	$new_content = '';
	$pattern_full = '{(\[bwlmsfields_txt\].*?\[/bwlmsfields_txt\])}is';
	$pattern_contents = '{\[bwlmsfields_txt\](.*?)\[/bwlmsfields_txt\]}is';
	$pieces = preg_split( $pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE );

	foreach( $pieces as $piece ) {
		if( preg_match( $pattern_contents, $piece, $matches ) ) {
			$new_content .= $matches[1];
		} else {
			$new_content .= wptexturize( wpautop( $piece ) );
		}
	}

	return $new_content;
}
endif;


if ( ! function_exists( 'bwlmsfields_do_excerpt' ) ):

function bwlmsfields_do_excerpt( $content )
{	
	$arr = get_option( 'bwlmsfields_autoex' );
	$has_more_link = ( stristr( $content, 'class="more-link"' ) ) ? true : false;

	if( $arr['auto_ex'] == true ) {
		
		if( ! $has_more_link ) {
		
			$words = explode( ' ', $content, ( $arr['auto_ex_len'] + 1 ) );
			if( count( $words ) > $arr['auto_ex_len'] ) { array_pop( $words ); }
			$content = implode( ' ', $words );
			
			$common_tags = array( 'i', 'b', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5' );
			foreach ( $common_tags as $tag ) {
				if( stristr( $content, '<' . $tag . '>' ) ) {
					$after = stristr( $content, '</' . $tag . '>' );
					$content = ( ! stristr( $after, '</' . $tag . '>' ) ) ? $content . '</' . $tag . '>' : $content;
				}
			}
		} 		
	}

	global $post, $more;

	if( ! $has_more_link && ( $arr['auto_ex'] == true ) ) {
		$more_link_text = __( '(more&hellip;)' );
		$more_link = ' <a href="'. get_permalink( $post->ID ) . '" class="more-link">' . $more_link_text . '</a>';
		$more_link = apply_filters( 'the_content_more_link' , $more_link, $more_link_text );
		$content = $content . $more_link;
	}

	$content = apply_filters( 'bwlmsfields_auto_excerpt', $content );
	return $content;
}
endif;


if ( ! function_exists( 'bwlmsfields_test_shortcode' ) ):

function bwlmsfields_test_shortcode( $content, $tag )
{
	global $shortcode_tags; 
	if( array_key_exists( $tag, $shortcode_tags ) ) {
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) )
			return false;

		foreach ( $matches as $shortcode ) {
			if ( $tag === $shortcode[2] )
				return true;
		}
	}
	return false;
}
endif;

function bwlmsfields_get_excluded_meta( $tag )
{
	return apply_filters( 'bwlmsfields_exclude_fields', array( 'password', 'confirm_password', 'confirm_email', 'password_confirm', 'email_confirm' ), $tag );
}


function bwlmsfields_wplogin_style() {
	wp_enqueue_style( 'custom_wp_admin_css', BWLMSMEM_DIRURL.'css/wpnative-login.css');
}



if ( ! function_exists( 'bwlmsfields_user_page_detail' ) ):

function bwlmsfields_user_page_detail ($page='user-profile')
{
	global $bwlmsfields_regchk, $bwlmsfields_themsg, $bwlmsfields_act;
	$content='';

	$page = ( $page == 'user-profile' ) ? 'members-area' : $page;

	
	// [Save Changes] result message
	if ($bwlmsfields_regchk=='editsuccess') {	
		if( is_user_logged_in() && $page == 'members-area' ) {
				
				// Success message 
				//$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk,$bwlmsfields_themsg );
				$content .= bwlmsfields_inc_registration( 'edit');
		}
	}
	else { 
		if( is_user_logged_in() && $page == 'members-area' ) {
				
				// Fail message
				//$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk,$bwlmsfields_themsg );
				$content .= bwlmsfields_inc_registration( 'edit');
		}
	}


	$content = apply_filters( 'bwlmsfields_securify', $content );
	return $content;

}
endif;