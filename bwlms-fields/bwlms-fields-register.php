<?php

if( ! function_exists( 'bwlmsfields_registration' ) ):

function bwlmsfields_registration( $toggle )
{
	global $user_ID, $bwlmsfields_themsg, $userdata; 
	
	if( defined( 'BWLMSFIELDS_USE_NONCE' ) ) {
		if( empty( $_POST ) || !wp_verify_nonce( $_POST['bwlmsfields-form-submit'], 'bwlmsfields-validate-submit' ) ) {
			$bwlmsfields_themsg = __( 'There was an error processing the form.', 'wptobemem' );
			return;
		}
	}

	if( $toggle == 'register' ) { 
		$fields['username'] = ( isset( $_POST['log'] ) ) ? sanitize_user( $_POST['log'] ) : '';
	}
	
	$fields['user_email'] = ( isset( $_POST['user_email'] ) ) ? sanitize_email( $_POST['user_email'] ) : '';

	$fields['first_name'] = ( isset( $_POST['first_name'] ) ) ? sanitize_text_field( $_POST['first_name'] ) : '';
	$fields['last_name'] = ( isset( $_POST['last_name'] ) ) ? sanitize_text_field( $_POST['last_name'] ) : '';
	$fields['bwlmsf_pic'] = ( isset( $_POST['bwlmsf_pic'] ) ) ? sanitize_text_field( $_POST['bwlmsf_pic'] ) : '';
	$fields['country'] = ( isset( $_POST['country'] ) ) ? sanitize_text_field( $_POST['country'] ) : '';
	$fields['thestate'] = ( isset( $_POST['thestate'] ) ) ? sanitize_text_field( $_POST['thestate'] ) : '';
	$fields['zip'] = ( isset( $_POST['zip'] ) ) ? sanitize_text_field( $_POST['zip'] ) : '';
	$fields['phone1'] = ( isset( $_POST['phone1'] ) ) ? sanitize_text_field( $_POST['phone1'] ) : '';

	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );

	foreach( $bwlmsfields_fields as $meta ) {
		//echo "mmmmm--".$meta[5]."--mmmmmm";
		if( $meta[5] == 'y' ) {

			if( $meta[2] == 'password' ) {
				$fields['password'] = ( isset( $_POST['password'] ) ) ? $_POST['password'] : '';
			} else if ( $meta[2] == 'confirm_password') {
				$fields['confirm_password'] = ( isset( $_POST['confirm_password'] ) ) ? $_POST['confirm_password'] : '';
			} else {
				$fields[$meta[2]] = ( isset( $_POST[$meta[2]] ) ) ? sanitize_text_field( $_POST[$meta[2]] ) : '';
			}

		}
	}
	
	$fields = apply_filters( 'bwlmsfields_pre_validate_form', $fields ); 

	$bwlmsfields_fields_rev = array_reverse( $bwlmsfields_fields );

	foreach( $bwlmsfields_fields_rev as $meta ) {
		$pass_arr = array( 'password', 'confirm_password', 'password_confirm' );
		$pass_chk = ( $toggle == 'update' && in_array( $meta[2], $pass_arr ) ) ? true : false;

		if( $meta[5] == 'y' && $pass_chk == false ) {
			
			//var_dump($meta[1]);
			if( (!isset($fields[$meta[2]])) || (!$fields[$meta[2]]) ) { 
				$bwlmsfields_themsg = sprintf( __('%s*  is a required field.', 'wptobemem'), $meta[1] ); 
			}

//			if($meta[2] == 'bwlms_captcha')	  {
//				echo "Captcha Field Required...............................";
//				var_dump($meta);
//			}
		}
	}

	switch( $toggle ) {

	case "register": //Sign up
		
		if( is_multisite() ) {
			$result = wpmu_validate_user_signup($fields['username'], $fields['user_email']); 
			$errors = $result['errors'];
			if( $errors->errors ) {
				$bwlmsfields_themsg = $errors->get_error_message(); return $bwlmsfields_themsg; exit;
			}
			
		} else {
			if( !$fields['username'] ) { $bwlmsfields_themsg = __( 'Sorry, username is a required field', 'wptobemem' ); return $bwlmsfields_themsg; exit(); } 
			if( !validate_username( $fields['username'] ) ) { $bwlmsfields_themsg = __( 'The username cannot include non-alphanumeric characters.', 'wptobemem' ); return $bwlmsfields_themsg; exit(); }
			if( !is_email( $fields['user_email']) ) { $bwlmsfields_themsg = __( 'You must enter a valid email address.', 'wptobemem' ); return $bwlmsfields_themsg; exit(); }
			if( username_exists( $fields['username'] ) ) { return "user"; exit(); } 
			if( email_exists( $fields['user_email'] ) ) { return "email"; exit(); }
			
			//if(isset($fields['bwlms_captcha'])) echo "<br>입력캡차:".$fields['bwlms_captcha']."<br>";
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////
			process_si_contact_form(); // Process the form, if it was submitted

			if (isset($_POST['ctform']['error']) &&  $_POST['ctform']['error'] == true) {
				$bwlmsfields_themsg = __( 'You must enter a valid text.', 'wptobemem' );
				return $bwlmsfields_themsg; exit();
			}
			elseif (isset($_POST['ctform']['success']) && $_POST['ctform']['success'] == true){ 
				//The captcha was correct and the message has been sent!  The captcha was solved in 
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}


		if( $bwlmsfields_themsg ) { return "empty"; exit(); }
		
		if( array_key_exists( 'confirm_password', $fields ) && $fields['confirm_password'] != $fields ['password'] ) { $bwlmsfields_themsg = __( 'Passwords did not match.', 'wptobemem' ); }
		if( array_key_exists( 'confirm_email', $fields ) && $fields['confirm_email'] != $fields ['user_email'] ) { $bwlmsfields_themsg = __( 'Emails did not match.', 'wptobemem' ); }
		
		$fields['password'] = ( ! isset( $_POST['password'] ) ) ? wp_generate_password() : $_POST['password'];
		
		$fields['user_registered'] = gmdate( 'Y-m-d H:i:s' );
		$fields['user_role']       = get_option( 'default_role' );
		$fields['bwlmsfields_reg_ip']    = $_SERVER['REMOTE_ADDR'];
		$fields['bwlmsfields_reg_url']   = esc_url($_REQUEST['redirect_to']);

		$fields['user_nicename']   = ( isset( $_POST['user_nicename'] ) ) ? sanitize_title( $_POST['user_nicename'] ) : $fields['username'];
		$fields['display_name']    = ( isset( $_POST['display_name'] ) )  ? sanitize_user ( $_POST['display_name']  ) : $fields['username'];
		$fields['nickname']        = ( isset( $_POST['nickname'] ) )      ? sanitize_user ( $_POST['nickname']      ) : $fields['username'];


		$fields = apply_filters( 'bwlmsfields_register_data', $fields ); 
		
		do_action( 'bwlmsfields_pre_register_data', $fields );
		
		if( $bwlmsfields_themsg ){ return $bwlmsfields_themsg; }

		$new_user_fields = array (
			'user_pass'       => $fields['password'], 
			'user_login'      => $fields['username'],
			'user_nicename'   => $fields['user_nicename'],
			'user_email'      => $fields['user_email'],
			'display_name'    => $fields['display_name'],
			'nickname'        => $fields['nickname'],
			'user_registered' => $fields['user_registered'],
			'role'            => $fields['user_role']
		);

		$excluded_meta = bwlmsfields_get_excluded_meta( 'register' );
		$new_user_fields_meta = array( 'user_url', 'first_name', 'last_name', 'description', 'jabber', 'aim', 'yim' );
		foreach( $bwlmsfields_fields as $meta ) {
			if( in_array( $meta[2], $new_user_fields_meta ) ) {
				if( $meta[4] == 'y' && ! in_array( $meta[2], $excluded_meta ) ) {
					$new_user_fields[$meta[2]] = $fields[$meta[2]];
				}
			}
		}

		$fields['ID'] = wp_insert_user( $new_user_fields );
		
		foreach( $bwlmsfields_fields as $meta ) {
			// if the field is not excluded, update accordingly
			if( ! in_array( $meta[2], $excluded_meta ) && ! in_array( $meta[2], $new_user_fields_meta ) ) {
				if( $meta[4] == 'y' && $meta[2] != 'user_email' ) {
					update_user_meta( $fields['ID'], $meta[2], $fields[$meta[2]] );
				}
			}
		}
		
		update_user_meta( $fields['ID'], 'bwlmsfields_reg_ip', $fields['bwlmsfields_reg_ip'] );
		update_user_meta( $fields['ID'], 'bwlmsfields_reg_url', $fields['bwlmsfields_reg_url'] );

		$time_str = time();
		$allowedTypes = array('image/gif', 'image/jpeg', 'image/png');
		foreach ( $_FILES as $key => $val ) {

			if($_FILES[$key]["size"] > 0 )
			{
					$extension = end(explode(".", $_FILES[$key]["name"]));
					$file_name = explode(".", $_FILES[$key]["name"]);
					$upload_dir = wp_upload_dir();

					if ( ($_FILES[$key]["type"] == "image/gif") || ($_FILES[$key]["type"] == "image/jpeg") || ($_FILES[$key]["type"] == "image/png") || ($_FILES[$key]["type"] == "image/pjpeg") )
					{
						if ($_FILES[$key]["error"] > 0)
						{
							//echo "Return Code: " . $_FILES[$key]["error"];
						}
						else
						{
							if (file_exists($upload_dir['basedir']."/" . $key.$time_str.".".$extension ))
							{
								//echo $_FILES[$key][$key] . " already exists. ";
							}
							else
							{
								move_uploaded_file($_FILES[$key]["tmp_name"], $upload_dir['basedir'] ."/" . $key.$time_str.".".$extension );
								update_user_meta( $fields['ID'] , $key, $key.$time_str.".".$extension );
							}
							
						}
					}
					else
					{
						//Invalid file
					}
			}
		}

		if( BWLMSFIELDS_USE_EXP == 1 && BWLMSFIELDS_MOD_REG != 1 ) { bwlmsfields_set_exp( $fields['ID'] ); }
		
		do_action( 'bwlmsfields_post_register_data', $fields );
		
		require_once( 'bwlms-fields-email.php' );
		bwlmsfields_inc_regemail( $fields['ID'], $fields['password'], BWLMSFIELDS_MOD_REG, $bwlmsfields_fields, $fields );
		
		if( BWLMSFIELDS_NOTIFY_ADMIN == 1 ) { bwlmsfields_notify_admin( $fields['ID'], $bwlmsfields_fields ); }
		
		do_action( 'bwlmsfields_register_redirect' );

		return "success"; exit();
		break;

	case "update":
		
		if( $bwlmsfields_themsg ) { return "updaterr"; exit(); }

		global $current_user; 
		 //get_currentuserinfo();
		 $current_user = wp_get_current_user();
		if( $fields['user_email'] !=  $current_user->user_email ) {
			if( email_exists( $fields['user_email'] ) ) { return "email"; exit(); } 
			if( !is_email( $fields['user_email']) ) { $bwlmsfields_themsg = __( 'You must enter a valid email address.', 'wptobemem' ); return "updaterr"; exit(); }
		}
		
		if( array_key_exists( 'confirm_email', $fields ) && $fields['confirm_email'] != $fields ['user_email'] ) { $bwlmsfields_themsg = __( 'Emails did not match.', 'wptobemem' ); }
		
		$fields['ID'] = $user_ID;
		
		$fields = apply_filters( 'bwlmsfields_register_data', $fields ); 
		
		do_action( 'bwlmsfields_pre_update_data', $fields );
		
		if( $bwlmsfields_themsg ){ return $bwlmsfields_themsg; }
		
		$native_fields = array( 
			'user_nicename',
			'user_url',
			'user_email',
			'display_name',
			'nickname',
			'first_name',
			'last_name',
			'description',
			'role',
			'jabber',
			'aim',
			'yim' );
		$native_update = array( 'ID' => $user_ID );

		foreach( $bwlmsfields_fields as $meta ) {
			if( ! in_array( $meta[2], bwlmsfields_get_excluded_meta( 'update' ) ) ) {
				switch( $meta[2] ) {

				case( in_array( $meta[2], $native_fields ) ):
					$fields[$meta[2]] = ( isset( $fields[$meta[2]] ) ) ? $fields[$meta[2]] : '';
					$native_update[$meta[2]] = $fields[$meta[2]];
					break;
			
				case( 'password' ):
					break;

				default:
					//if( $meta[4] == 'y' ) {
					if( $meta[4] == 'y' && $fields[$meta[2]]) {
						update_user_meta( $user_ID, $meta[2], $fields[$meta[2]] );
					}
					break;
				}
			}
		}
		wp_update_user( $native_update );
		
		do_action( 'bwlmsfields_post_update_data', $fields );

		$time_str = time();
		$allowedTypes = array('image/gif', 'image/jpeg', 'image/png');
		foreach ( $_FILES as $key => $val ) {
			//echo "$key $val <br>";

			if($_FILES[$key]["size"] > 0 )
			{
					$extension = end(explode(".", $_FILES[$key]["name"]));
					$file_name = explode(".", $_FILES[$key]["name"]);
					$upload_dir = wp_upload_dir();

					if ( ($_FILES[$key]["type"] == "image/gif") || ($_FILES[$key]["type"] == "image/jpeg") || ($_FILES[$key]["type"] == "image/png") || ($_FILES[$key]["type"] == "image/pjpeg") )
					{
						if ($_FILES[$key]["error"] > 0)
						{
							//echo "Return Code: " . $_FILES[$key]["error"];
						}
						else
						{
							if (file_exists($upload_dir['basedir']."/" . $key.$time_str.".".$extension ))
							{
								//echo $_FILES[$key][$key] . " already exists. ";
							}
							else
							{
								move_uploaded_file($_FILES[$key]["tmp_name"], $upload_dir['basedir'] ."/" . $key.$time_str.".".$extension );
								update_user_meta( $user_ID , $key, $key.$time_str.".".$extension );
							}
						}
					}
					else
					{
						if ($_FILES[$key]["error"] > 0)
						{
							echo "Return Code: " . $_FILES[$key]["error"] . "<br>";
						}
						else
						{
							if (file_exists($upload_dir['basedir']."/" . $key.$time_str.".".$extension ))
							{
								//echo $_FILES[$key][$key] . " already exists. ";
							}
							else
							{
								move_uploaded_file($_FILES[$key]["tmp_name"], $upload_dir['basedir'] ."/" . $key.$time_str.".".$extension );
								update_user_meta( $user_ID , $key, $key.$time_str.".".$extension );
							}
							
						}
					}
			}
		}
		return "editsuccess"; exit();
		break;
	}
} 
endif;