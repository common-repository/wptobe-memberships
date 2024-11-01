<?php

if ( ! function_exists( 'bwlmsfields_inc_login' ) ):

function bwlmsfields_inc_login( $page="page" )
{ 	
	global $bwlmsfields_regchk;

	$str = '';

	if( $page == "page" ){
	     if( $bwlmsfields_regchk!="success" ){

			$arr = get_option( 'bwlmsfields_dialogs' );

			$str = '<p>' . __( stripslashes( $arr[0] ), 'wptobemem' ) . '</p>';
			
			$str = apply_filters( 'bwlmsfields_restricted_msg', $str );

		} 	
	} 

	$default_inputs = array(
		array(
			'name'   => __( 'Username' ), 
			'type'   => 'text', 
			'tag'    => 'log',
			'class'  => 'username',
			'div'    => 'div_text'
		),
		array( 
			'name'   => __( 'Password' ), 
			'type'   => 'password', 
			'tag'    => 'pwd', 
			'class'  => 'password',
			'div'    => 'div_text'
		)
	);
	
	$default_inputs = apply_filters( 'bwlmsfields_inc_login_inputs', $default_inputs );

	$bloginfo_name =  __('Welcome to ','wptobemem') . get_bloginfo('name');
	$login_title_header = __('LOGIN','wptobemem');

	$bwlmsfields_login_header = "
		<div class='small-10 large-5 small-centered columns signup-head-container shadow-headline'>
			<div class='row signup-head'>
				<div class='small-10 small-centered columns signup-headline'>
					".$login_title_header."
				</div>
			</div>
		</div>
		
		<div class='small-10 large-5 small-centered columns signup-list shadow-lr-both'>
	";
    $defaults = array( 
		'heading'      => $bwlmsfields_login_header, 
		'action'       => 'login', 
		'button_text'  => __( 'Log In' ),
		'inputs'       => $default_inputs
	);	
	
	$args = apply_filters( 'bwlmsfields_inc_login_args', '' );

	$arr  = wp_parse_args( $args, $defaults );
	
	$str  = $str . bwlmsfields_login_form( $page, $arr );
	
	return $str;
}
endif;


if ( ! function_exists( 'bwlmsfields_inc_changepassword' ) ):
function bwlmsfields_inc_changepassword()
{
	$default_inputs = array(
		array(
			'name'   => __( 'New password' ), 
			'type'   => 'password',
			'tag'    => 'pass1',
			'class'  => 'password',
			'div'    => 'div_text'
		),
		array( 
			'name'   => __( 'Confirm new password' ), 
			'type'   => 'password', 
			'tag'    => 'pass2',
			'class'  => 'password',
			'div'    => 'div_text'
		)
	);

	$default_inputs = apply_filters( 'bwlmsfields_inc_changepassword_inputs', $default_inputs );

//	$title = __('CHANGE ACCOUNT PASSWORD','wptobemem');
//	$title = '<div class="chgpasswd-headline">' .$title. '</div>';
	$title = '';

	$defaults = array(
		'heading'      => $title,
		'action'       => 'pwdchange', 
		'button_text'  => __('Change Password', 'wptobemem'), 
		'inputs'       => $default_inputs
	);

	$args = apply_filters( 'bwlmsfields_inc_changepassword_args', '' );

	$arr  = wp_parse_args( $args, $defaults );

    $str  = bwlmsfields_login_form( 'page', $arr );
	
	return $str;
}
endif;


if ( ! function_exists( 'bwlmsfields_inc_resetpassword' ) ):
function bwlmsfields_inc_resetpassword()
{ 
	$default_inputs = array(
		array(
			'name'   => __( 'Username' ), 
			'type'   => 'text',
			'tag'    => 'user', 
			'class'  => 'username',
			'div'    => 'div_text'
		),
		array( 
			'name'   => __( 'Email' ), 
			'type'   => 'text', 
			'tag'    => 'email', 
			'class'  => 'password',
			'div'    => 'div_text'
		)
	);

	$default_inputs = apply_filters( 'bwlmsfields_inc_resetpassword_inputs', $default_inputs );

	$bloginfo_name =  __('Welcome to ','wptobemem') . get_bloginfo('name');
	$resetpw_title_header = __('RESET PASSWORD','wptobemem');
	$bwlmsfields_resetpw_header = "
		<div class='small-10 large-5 small-centered columns signup-head-container shadow-headline'>
			<div class='row signup-head'>
				<div class='small-10 small-centered columns signup-headline'>
					".$resetpw_title_header."
				</div>
			</div>
		</div>
		
		<div class='small-10 large-5 small-centered columns signup-list shadow-lr-both'>
			<div class='row'>

	";

	$defaults = array(
		'heading'      => $bwlmsfields_resetpw_header,	
		'action'       => 'pwdreset', 
		'button_text'  => __( 'Reset Password' ), 
		'inputs'       => $default_inputs
	);

	$args = apply_filters( 'bwlmsfields_inc_resetpassword_args', '' );

	$arr  = wp_parse_args( $args, $defaults );
    $str  = bwlmsfields_login_form( 'page', $arr );
	
	return $str;
}
endif;


/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			User Login/Change Password 페이지 포맷						*/
/* >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> */
if ( ! function_exists( 'bwlmsfields_login_form' ) ):

function bwlmsfields_login_form( $page, $arr ) 
{
	extract( $arr );
	$defaults = array(
		'heading_before'  => '',//'<legend>',
		'heading_after'   => '',//'</legend>',
		'fieldset_before' => '',//'<fieldset>',
		'fieldset_after'  => '',//'</fieldset>',
		'main_div_before' => '<div id="bwlmsfields_login">',//최상위 컨테이너
		'main_div_after'  => '</div></div>', //$bwlmsfields_login_header 추가된 div 클로저 추가
		'txt_before'      => '[bwlmsfields_txt]',
		'txt_after'       => '[/bwlmsfields_txt]',
		'row_before'      => '<div class="small-12 small-centered columns bwlmsfields-silo-input">',// Label+inputbox
		'row_after'       => '</div>',
		'buttons_before'  => '',//<div class="small-10 small-centered columns">',
		'buttons_after'   => '',//</div>',
		'link_before'     => '<div class="small-12 small-centered columns bwlmsfields-silo-input forgotpasswd">',
		'link_after'      => '</div>',
		'form_id'         => '',
		'form_class'      => 'bwlmsfielsd_login_form',
		'button_id'       => '',
		'button_class'    => 'bwlmsfields-signup-submit',
		'strip_breaks'    => true,
		'wrap_inputs'     => true,
		'remember_check'  => true,
		'n'               => "\n",
		't'               => "\t",
	);
	
	$args = apply_filters( 'bwlmsfields_login_form_args', '', $action );
	extract( wp_parse_args( $args, $defaults ) );
	
	foreach ( $inputs as $input ) {

		if ( $action != 'login' ) {
			$row_before     = '<div class="small-12 medium-12 large-12 columns">';
		}

		if ($action == 'pwdchange') {
			$row_before     = '<div class="small-12 medium-12 large-12 columns bwlmsfields_centered_col">';
		}
		else if ($action == 'pwdreset') {
			$row_before     = '<div class="small-12 small-centered columns bwlmsfields-silo-input">';
		}

		$label = '<label for="' . $input['tag'] . '">' . $input['name'] . '</label>';
		$field = bwlmsfields_create_formfield( $input['tag'], $input['type'], '', '' , $input['class'],false,null, $input['name'] );
		$field_before = '';//( $wrap_inputs ) ? '<div class="' . $input['div'] . '">' : ''; 인풋텍스트박스인경우
		$field_after  = '';//( $wrap_inputs ) ? '</div>' : '';

		$rows[] = array( 
			'row_before'   => $row_before,
			'label'        => '',//$label,
			'field_before' => $field_before,
			'field'        => $field,
			'field_after'  => $field_after,
			'row_after'    => $row_after
		);
	}
	
	$rows = apply_filters( 'bwlmsfields_login_form_rows', $rows, $action );
	
	$form = '';
	foreach( $rows as $row_item ) {
		$row  = ( $row_item['row_before']   != '' ) ? $row_item['row_before'] . $n . $row_item['label'] . $n : $row_item['label'] . $n;
		$row .= ( $row_item['field_before'] != '' ) ? $row_item['field_before'] . $n . $t . $row_item['field'] . $n . $row_item['field_after'] . $n : $row_item['field'] . $n;
		$row .= ( $row_item['row_before']   != '' ) ? $row_item['row_after'] . $n : '';
		$form.= $row;
	}

	$redirect_to = ( isset( $_REQUEST['redirect_to'] ) ) ? esc_url( $_REQUEST['redirect_to'] ) : get_permalink();
	$hidden = bwlmsfields_create_formfield( 'redirect_to', 'hidden', $redirect_to ) . $n;
	$hidden = $hidden . bwlmsfields_create_formfield( 'a', 'hidden', $action ) . $n;
	$hidden = ( $action != 'login' ) ? $hidden . bwlmsfields_create_formfield( 'formsubmit', 'hidden', '1' ) : $hidden;

	$form = $form . apply_filters( 'bwlmsfields_login_hidden_fields', $hidden, $action );

	$bwlmsfields_before_loginbtn = '<div class="small-12 small-centered columns bwlmsfields-silo-input">';
	$bwlmsfields_after_loginbtn = '</div>';

	if ($action == 'pwdreset') {
		$bwlmsfields_before_chgpwbtn = '<div class="small-12 small-centered columns bwlmsfields-silo-input savechgbtn-msg-row-btn">
											<div class="resetpw-submit">
									';
	}
	else {
		$bwlmsfields_before_chgpwbtn = '<div class="small-12 medium-12 large-12 columns savechgbtn-msg-row-btn">
											<div class="bwlmsfields-savebtn-icon">
									';
	}
		$bwlmsfields_after_chgpwbtn = '			
											</div>
										</div>
									';	
	
	$bwlmsfields_after_form = '
											</div>

									</div>
								';

	if ( $action == 'login' ) {
		$remember_username = __('Remember Me','wptobemem');
		$remember_check = ( $remember_check ) ? $t . bwlmsfields_create_formfield('rememberme','checkbox','forever', null ,'',false,null,$remember_username ) : '';
		
		$buttons =  $bwlmsfields_before_loginbtn . $remember_check .  '<input type="submit" name="Submit" value="' . $button_text . '" class="' . $button_class . '" />' . $bwlmsfields_after_loginbtn;
	} 
	else { //[action] => pwdchange
		// 패스워드 변경
		$button_class = 'bwlmsfields-changepw-submit';
		$buttons =  $bwlmsfields_before_chgpwbtn.  '<input type="submit" name="Submit" value="' . $button_text . '" class="' . $button_class . '" />' . $bwlmsfields_after_chgpwbtn;
	}

	$form = $form . apply_filters( 'bwlmsfields_login_form_buttons', $buttons_before . $n . $buttons . $buttons_after . $n, $action );

	if ( ( BWLMSFIELDS_MSURL != null || $page == 'members' ) && $action == 'login' ) { 
		
		$link = apply_filters( 'bwlmsfields_forgot_link', bwlmsfields_chk_qstr( BWLMSFIELDS_MSURL ) . 'a=pwdreset' );	
		$str  = '<a href="' . $link . '">' . __( 'Forgot Password?', 'wptobemem' ) . '</a>';
		$form = $form . $link_before . apply_filters( 'bwlmsfields_forgot_link_str', $str ) . $link_after . $n;
	}
	
	if ( ( BWLMSFIELDS_REGURL != null ) && $action == 'login' ) { 

		$link = apply_filters( 'bwlmsfields_reg_link', BWLMSFIELDS_REGURL );
		$str  = __( 'New User?', 'wptobemem' ) . '&nbsp;<a href="' . $link . '">' . __( 'Click here to register', 'wptobemem' ) . '</a>';
		$form = $form . $link_before . apply_filters( 'bwlmsfields_reg_link_str', $str ) . $link_after . $n;
	}			
	
	$form = $heading_before . $heading . $heading_after . $n . $form;
	
	$form = $fieldset_before . $n . $form . $fieldset_after . $n;
	
	$form = '<form action="' . get_permalink() . '" method="POST" id="' . $form_id . '" class="' . $form_class . '">' . $n . $form . '</form>';
	
	$form = '<a name="login"></a>' . $n . $form;
	
	$form = $main_div_before . $n . $form . $n . $main_div_after;

	if ( $action == 'pwdchange' ) { $form .= $bwlmsfields_after_form ;}
	//if ( $action == 'pwdreset' ) { $form .= $bwlmsfields_pwreset_after_form ;}

	$form = ( $strip_breaks ) ? str_replace( array( "\n", "\r", "\t" ), array( '','','' ), $form ) : $form;
	
	$form = apply_filters( 'bwlmsfields_login_form', $form, $action );
	
	$form = apply_filters( 'bwlmsfields_login_form_before', '', $action ) . $form;
	
	return $form;
} 
endif;

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			User Profile/Registration 페이지 포맷						*/
/*			function: bwlmsfields_inc_registration					*/
/*			$toggle==new : 등록페이지									*/
/*			$toggle==edit: 프로파일 페이지								*/
/* >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> */
if ( ! function_exists( 'bwlmsfields_inc_registration' ) ):
function bwlmsfields_inc_registration( $toggle = 'new', $heading = '' )
{	
	global $bwlmsfields_regchk, $userdata; 
	$required_mark =null;
	
	$defaults = array(
		'heading_before'   => '<div class="small-10 large-5 small-centered columns signup-head-container shadow-headline">',
		'heading_after'    => '</div>',
		'fieldset_before'  => '<fieldset>',
		'fieldset_after'   => '</fieldset>',
		'main_div_before'  => '',//'<div class="row">',
		'main_div_after'   => '',//'</div>',
		'txt_before'       => '',//[bwlmsfields_txt]',
		'txt_after'        => '',//[/bwlmsfields_txt]',
		'row_before'       => '',
		'row_after'        => '',
		'buttons_before'   => '<div class="row signup-item"><div class="small-12 small-centered columns bwlmsfields-silo-input">',
		'buttons_after'    => '</div></div>',
		'form_id'          => '',
		'form_class'       => 'bwlmsfields_signup_form',
		'button_id'        => '',
		'button_class'     => 'bwlmsfields-signup-submit',
		'req_mark'         => '<font class="req">*</font>',
		'req_label'        => __( 'Required field', 'wptobemem' ),
		'req_label_before' => '<div class="req-text">',
		'req_label_after'  => '</div>',
		'show_clear_form'  => true,
		'clear_form'       => __( 'Reset Form', 'wptobemem' ),
		'submit_register'  => __( 'Sign Up', 'wptobemem' ),
		'submit_update'    => __( 'Save Changes', 'wptobemem' ),
		'strip_breaks'     => true,
		'use_nonce'        => false,
		'wrap_inputs'      => true,
		'n'                => "\n",
		't'                => "\t",
	);
	
	$args = apply_filters( 'bwlmsfields_register_form_args', '', $toggle );
	
	extract( wp_parse_args( $args, $defaults ) );

	$form = ''; $enctype = '';


	if($toggle == 'edit') {
	// 프로파일 페이지 Save Changes 버튼을 상단으로 이동
		$button_text = $submit_update;
		$buttons_before = '
						<div class="row savechgbtn-msg-row">
									<div class="small-12 medium-12 large-12 large-centered medium-centered small-centered columns savechgbtn-msg-row-btn">

										<div class="row">
											<div class="large-4 medium-4 small-4 columns bwlmsmembership-profile-title">
													Profile
											</div>

											<div class="large-4 medium-4 small-4 columns bwlmsmembership-profile-btn-col">

													<div class="bwlmsfields-savebtn-wrapper">
													  <div class="bwlmsfields-savebtn">
														<div class="bwlmsfields-savebtn-icon">
							';

		$buttons_after = '	
														</div>
													  </div>
													</div>

											</div>
											<div class="large-4 medium-4  small-4 columns savechgbtn-msg-row-msg">
											
												<div id="bwlms-message-success">

											';
					global $bwlmsfields_regchk, $bwlmsfields_themsg;
					 $buttons_after.= bwlmsfields_inc_regmessage( $bwlmsfields_regchk,$bwlmsfields_themsg );


		$buttons_after .= '						</div>
											</div>
										</div>

									</div>

								</div>
							';



		$button_class='bwlmsfields-profile-submit';
		$buttons = '<input name="submit" type="submit" value="' . $button_text . '" class="' . $button_class . '" />';
		$buttons = apply_filters( 'bwlmsfields_register_form_buttons', $buttons, $toggle );
		$form.= $buttons_before . $buttons . $buttons_after ;
	}

	/*# 01: User ID.......................................................................................................*/
	if( $toggle == 'edit' ) { 
		// 아이디
		$val   = $userdata->user_login;
		$field_before = "
						<div class='row fullwidthrow_bwlmsfields'>
							
							<div class='small-12 medium-12 large-12 large-centered medium-centered small-centered columns bwlmsfields-profile-shell-cols'>
								<div class='row bwlmsfields-profile-shell-1'>
		";
		$field_after  = "";
		$input = '';

	} 
	else {
		$val   = ( isset( $_POST['log'] ) ) ? stripslashes( $_POST['log'] ) : '';
		$input = bwlmsfields_create_formfield( 'log', 'text', $val, null , 'username',false,null,'*Username' );
		$field_before = "
						<div class='small-10 large-5 small-centered columns signup-list shadow-lr-both'>
							<div class='row signup-item'>
								<div class='small-12 small-centered columns bwlmsfields-silo-input'>
						";
		$field_after  = "</div></div>";
	}

	$rows['username'] = array( 
		'order'        => 0,
		'meta'         => 'username', 
		'type'         => 'text', 
		'value'        => $val,  
		'row_before'   => $row_before,
		'label'        => '',
		'field_before' => $field_before,
		'field'        => $input,
		'field_after'  => $field_after,
		'row_after'    => $row_after
	);

	$bwlmsfields_fields = apply_filters( 'bwlmsfields_register_fields_arr', get_option( 'bwlmsfields_fieldsopt' ), $toggle );

	foreach( $bwlmsfields_fields as $field ){
	// 필드 Row 작업.
		$val = ''; $label = ''; $input = ''; $field_before = ''; $field_after = '';
		
		$pass_arr = array( 'password', 'confirm_password', 'password_confirm' );
		$do_row = ( $toggle == 'edit' && in_array( $field[2], $pass_arr ) ) ? false : true;

		$field[9] = isset($field[9]) ? $field[9] : null;
		$field[10] = isset($field[10]) ? $field[10] : null;

		if(($toggle=='new' && $field[9] == 'y') or ($field[2] == 'user_email')) { 
			// 사인업 화면에 디스플레이 체크된 항목만 출력
			$do_row = true;
		}
		else {
			$do_row = false;
		}
		
		if( ($toggle=='edit' && $field[4] == 'y') ||  ($toggle=='edit' && $field[2]=='user_email') ) { 
		/*######## A.프로파일 페이지에 출력할 필드를 생성 (Display가 체크) ##########*/
			
			//A.01 패스워드 타입이면 'text' 타입으로 변경시켜줌
			$class = ( $field[3] == 'password' ) ? 'text' : $field[3];

			//A.02 개별필드의 레이블 추가, 레이블을 출력한다.
			$label = $field[1];
			//필수 필드 레이블에 별표 추가
			$label = ( $field[5] == 'y' ) ? $label . "*" : $label;
			
			if(   $bwlmsfields_regchk != 'updaterr'  ) { 
			// 프로파일 화면에서 업데이트 에러가 아닌 경우
				switch( $field[2] ) {
					case( 'description' ):
						$val = htmlspecialchars( get_user_meta( $userdata->ID, 'description', 'true' ) );
						break;

					case( 'user_email' ):
					case( 'confirm_email' ):
						$val = $userdata->user_email;
						break;

					case( 'user_url' ):
						$val = esc_url( $userdata->user_url );
						break;

					default:
						$val = htmlspecialchars( get_user_meta( $userdata->ID, $field[2], 'true' ) );
						break;
				}
			} 
			else { 
				if( $field[3] == 'file' ) {
					$val = htmlspecialchars( get_user_meta( $userdata->ID, $field[2], 'true' ) );
				}
				else
				{
					$val = ( isset( $_POST[ $field[2] ] ) ) ? $_POST[ $field[2] ] : '';
				}
			}

			if( $field[3] == 'checkbox' ) { 
				$valtochk = $val;
				$val = $field[7];
				$field[8] = isset($field[8]) ? $field[8] : null;
				if( $field[8] == 'y' && ( ! $_POST && $toggle != 'edit' ) ) { $val = $valtochk = $field[7]; }
			}

			if( $field[3] == 'select' ) {
				$valtochk = $val;
				$val = $field[7];
			}

			if( $field[3] == 'dropdown'  ) {
					$valtochk = $val; 
					$val=  isset( $field[10] )  ? $field[10]  : '';
			}

			if( ! isset( $valtochk ) ) { $valtochk = ''; }
			
			$input = bwlmsfields_create_formfield( $field[2], $field[3], $val, $valtochk );

			if($field[2]=='bwlmsf_pic'){
				$profilepic_left_before = '
									<div class="small-4 medium-4 large-4 columns">
										<div class="bwlmsfields-profile-image-frame">
				';
				$profilepic_left_after  = '	</div>
									</div>
				';
				
				$profilepic_right_before = '
									<div class="small-8 medium-8 large-8 columns bwlmsfields-shell-right">
											<div class="bwlmsfields-profile-image-frame">
				';
				$profilepic_right_after = ' </div>
									</div>
									
				';

				$l_input = 	$profilepic_left_before. $input .$profilepic_left_after ;
				$input = $profilepic_right_before . $profilepic_right_after ;
				$input = $l_input . $input;
//				$input .= $profilepic_right_before;
//				$input .=  $profilepic_right_after;
			}
			else { // 프로파일 사진이 아닌 나머지 모든 필드
				$profile_general_before = '

				';
				$profile_general_after = '

				';
				$input = 	$profile_general_before . $input . $profile_general_after ;
			
			}
		}

		if( ($toggle=='new' && $field[9] == 'y' && $do_row == true) ||  ($toggle=='new' && $field[2]=='user_email') ) { 
		/*### B. 사인업 페이지에 출력할 필드를 생성 (체크된 필드와, 필수인 E-mail ###*/
			$class = ( $field[3] == 'password' ) ? 'text' : $field[3];
			
			// Remove $label in Sign Up
			
			if( $field[3] == 'text' || $field[3] == 'password'  ) { 
			}


			$val = ( isset( $_POST[ $field[2] ] ) ) ? $_POST[ $field[2] ] : '';

			if( ($field[3] == 'checkbox') || ($field[3] == 'select') ) { 
				$valtochk = $val;
				$val = $field[7]; 
			}

			if( $field[2] == 'bwlms_captcha' ) { 
				//echo "캡차 테스트 forms.php 598라인"; 
				$field[3] = 'captcha';
				$valtochk = $val;
				$val = $field[7]; 
			}


			if( $field[3] == 'dropdown'  ) {
					$valtochk = $val; 
					$val=  isset( $field[10] )  ? $field[10]  : '';
			}

			if( ! isset( $valtochk ) ) { $valtochk = ''; }
			
			// Add a required mark
			if($field[5] == 'y') { $field[1] = "*".$field[1];}

			$input = bwlmsfields_create_formfield( $field[2], $field[3], $val, $valtochk , '',false,null,$field[1]);
		}

		if( ($toggle=='edit' && $field[4] == 'y') || ($toggle=='edit' && $field[2]=='user_email') ) { 
			// 프로파일 페이지 필드 출력: [레이블+인풋필드]를 감싸는 디자인
			$row_before="
							<div class='small-12 medium-12 large-12 columns'>
								<div class='row'>
									<div class='small-4 medium-4 large-4 columns bwlmsfields-shell-left'>
						";	
			$row_after="		</div><!--end of row-->
							</div>
						
						";
			$field_before="			</div><!--end of 4 -->
									<div class='small-8 medium-8 large-8 columns'>
						";	
			$field_after="			</div><!--end of 8 -->";

			$rows[$field[2]] = array(
				'order'        => $field[0],
				'meta'         => $field[2], 
				'type'         => $field[3], 
				'value'        => $val,  
				'row_before'   => $row_before,
				'label'        => $label,
				'field_before' => $field_before,
				'field'        => $input,
				'field_after'  => $field_after,
				'row_after'    => $row_after
			);
		}

		if( ($toggle=='new' && $field[9] == 'y')||($toggle=='new' && $field[2]=='user_email') ) { 
			// 사인업 페이지 인풋필드를 감싸는 디자인
			$row_before="<div class='row signup-item'>
							<div class='small-12 small-centered columns bwlmsfields-silo-input'>";	
			$row_after="	</div>
						</div>";
			$field_before="";	
			$field_after="";
			$rows[$field[2]] = array(
				'order'        => $field[0],
				'meta'         => $field[2], 
				'type'         => $field[3], 
				'value'        => $val,  
				'row_before'   => $row_before,
				'label'        => $label,
				'field_before' => $field_before,
				'field'        => $input,
				'field_after'  => $field_after,
				'row_after'    => $row_after
			);
		}
	}// End foreach ( 출력 Row ...)
	
	$rows = apply_filters( 'bwlmsfields_register_form_rows', $rows, $toggle );
//	$form = ''; $enctype = '';


	foreach( $rows as $row_item ) {
		$enctype = ( $row_item['type'] == 'file' ) ? "multipart/form-data" : $enctype;
		$row  = ( $row_item['row_before']   != '' ) ? $row_item['row_before'] . $n . $row_item['label'] . $n : $row_item['label'] . $n;
		$row .= ( $row_item['field_before'] != '' ) ? $row_item['field_before'] . $n . $t . $row_item['field'] . $n . $row_item['field_after'] . $n : $row_item['field'] . $n;
		$row .= ( $row_item['row_after']    != '' ) ? $row_item['row_after'] . $n : '';
		$form.= $row;
	}

	$var         = ( $toggle == 'edit' ) ? 'update' : 'register';
	$redirect_to = ( isset( $_REQUEST['redirect_to'] ) ) ? esc_url( $_REQUEST['redirect_to'] ) : get_permalink();
	$hidden      = '<input name="a" type="hidden" value="' . $var . '" />' . $n;
	$hidden     .= '<input name="redirect_to" type="hidden" value="' . $redirect_to . '" />' . $n;
	$hidden = apply_filters( 'bwlmsfields_register_hidden_fields', $hidden, $toggle );
	$form.= $hidden;
	
	if($toggle == 'new') {
		// Signup 페이지 사인업 버튼 
		$button_text = $submit_register;
		$buttons = '<input name="submit" type="submit" value="' . $button_text . '" class="' . $button_class . '" />' . $n;
		$buttons = apply_filters( 'bwlmsfields_register_form_buttons', $buttons, $toggle );
		$form.= $buttons_before . $n . $buttons . $buttons_after . $n;
	}
	
	if($toggle == 'new') {
		$form .= "</div>";//사인업 페이지: Close for] <div class='small-10 large-5 small-centered columns signup-list shadow-lr-both'>
	}
	if($toggle == 'edit') {
		$form .= "	
						</div>
					</div>
					
				</div>";
	}


	if($toggle=='new') {
		// [Sign Up 페이지는 헤더 디자인을 출력]
		$signup_title_header = __('SIGN UP','wptobemem');
		$bloginfo_name = __('Welcome to ','wptobemem').get_bloginfo('name');

		$bwlmsfields_signup_hd = "
					<div class='row signup-head'>
						<div class='small-10 small-centered columns signup-headline'>
							".$signup_title_header."
						</div>
					</div>";
		$heading=apply_filters( 'bwlmsfields_register_heading', $bwlmsfields_signup_hd, $toggle);

		$form = $heading_before . $heading . $heading_after . $n . $form;
			$form = ( defined( 'BWLMSFIELDS_USE_NONCE' ) || $use_nonce ) ? wp_nonce_field( 'bwlmsfields-validate-submit', 'bwlmsfields-form-submit' ) . $n . $form : $form;
			$enctype = ( $enctype == 'multipart/form-data' ) ? ' enctype="multipart/form-data"' : '';
			$form = '<form name="form" method="post"' . $enctype . ' action="' . get_permalink() . '" id="' . $form_id . '" class="' . $form_class . '">' . $n . $form. $n . '</form>';
			$form = '<a name="register"></a>' . $n . $form;

			// apply main div wrapper ( #bwlmsfield_reg): 전체 폼페이지
			$form = $main_div_before . $n . $form . $n . $main_div_after . $n;

			$form = ( $strip_breaks ) ? str_replace( array( "\n", "\r", "\t" ), array( '','','' ), $form ) : $form;

			// Form의 전체 Html을 출력시키는 부분
			// $toggle? new: 사인업페이지, edit:프로파일페이지. $row: rows 배열, $hidden: 히든 필드 Html string
			$form = apply_filters( 'bwlmsfields_register_form', $form, $toggle, $rows, $hidden );

			// Form 앞에 출력하고 싶은 html이 있을경우 추가해 준다. $toggle이 new:Sign Up, edit:Profile
			$form = apply_filters( 'bwlmsfields_register_form_before', '', $toggle ) . $form;
	}
	
	if($toggle=='edit') {	
		//[User Profile 페이지]
		$heading = '';
		// 에디트 페이지에서는 헤딩을 보여주지 않음.
			$form = ( defined( 'BWLMSFIELDS_USE_NONCE' ) || $use_nonce ) ? wp_nonce_field( 'bwlmsfields-validate-submit', 'bwlmsfields-form-submit' ) . $n . $form : $form;
			$enctype = ( $enctype == 'multipart/form-data' ) ? ' enctype="multipart/form-data"' : '';
			$form = '<form name="form" method="post"' . $enctype . ' action="' . get_permalink() . '" id="' . $form_id . '" class="' . $form_class . '">' . $n . $form. $n . '</form>';
			
			$form = $txt_before . $form . $txt_after;
			$form = $main_div_before . $n . $form . $n . $main_div_after . $n;
			$form = ( $strip_breaks ) ? str_replace( array( "\n", "\r", "\t" ), array( '','','' ), $form ) : $form;

			// Form의 전체 Html을 출력시키는 부분
			// $toggle? new: 사인업페이지, edit:프로파일페이지. $row: rows 배열, $hidden: 히든 필드 Html string
			$form = apply_filters( 'bwlmsfields_register_form', $form, $toggle, $rows, $hidden );
			
			// Form 앞에 출력하고 싶은 html이 있을경우 추가해 준다. $toggle이 new:Sign Up, edit:Profile
			$form = apply_filters( 'bwlmsfields_register_form_before', '', $toggle ) . $form;
	}

	return $form;
}
endif;
