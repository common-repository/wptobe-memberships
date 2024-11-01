<?php
require_once(BWLMSMEM_DIR . "/bwlms-fields/forms.php");

if ( ! function_exists( 'bwlmsfields_inc_loginfailed' ) ):
function bwlmsfields_inc_loginfailed() 
{ 
			$heading_before = '
							<div class="row">
								<div class="large-10 medium-10 small-10 columns  large-centered medium-centered small-centered  bwlmsfields-login-fail">	
									<h1>
			';
			
			$heading =  __( 'Login Failed', 'wptobemem' ) ;
			
			$heading_after = '
								</div>
							</div>
							<div class="fullwidthrow_bwlmsfields_gaprow"></div>
			';

			$message = __( 'Invalid username or password!', 'wptobemem' );


	$defaults = array(
		'div_before'     => '<div id="bwlmsfields_msg">',
		'div_after'      => '</div>', 
		'heading_before' => $heading_before,//'<h2>',
		'heading'        => $heading,//__( 'Login Failed!', 'wptobemem' ),
		'heading_after'  => $heading_after,//'</h2>',
		'p_before'       => '</h1><p>',
		'message'        => $message,//__( 'You entered an invalid username or password.', 'wptobemem' ),
		'p_after'        => '</p>',
		'link'           => ''
	);
	
	$args = apply_filters( 'bwlmsfields_login_failed_args', '' );
	
	extract( wp_parse_args( $args, $defaults ) );
	
	$str = $div_before 
		. $heading_before . $heading . $p_before . $message .$p_after . $heading_after 
		. $p_before . $link . $p_after
		. $div_after;

	$str = apply_filters( 'bwlmsfields_login_failed', $str );

	return $str;
}
endif;


if ( ! function_exists( 'bwlmsfields_inc_regmessage' ) ):

function bwlmsfields_inc_regmessage( $toggle, $msg = '' )
{
	if ( is_user_logged_in() ) {
		// 유저 프로파일 화면
		$divbefore =  '
										<div class="bwlmsfields_msg" >
		';
		$divafter = '					</div>

		';
	} 
	else {
		// 로그인,사인업 화면
		$divbefore = '	
						<div class="fullwidthrow_bwlmsfields_gaprow"></div>
						<div class="row">

							<div class="large-10 medium-10 small-10  large-centered medium-centered small-centered columns bwlmsfields-login-fail">
								<div class="bwlmsfields_msg">
		';
		$divafter = '			</div>
							</div>

						</div>
						<div class="fullwidthrow_bwlmsfields_gaprow"></div>
		';
	}


	$defaults = array(
		'div_before' => $divbefore,
		'div_after'  => $divafter, 
		'p_before'   => '',
		'p_after'    => '',
		'toggles'    => array( 
							'user', 
							'email', 
							'success', 
							'editsuccess', 
							'pwdchangerr', 
							'pwdchangesuccess', 
							'pwdreseterr', 
							'pwdresetsuccess' 
						)
	);
	
	$args = apply_filters( 'bwlmsfields_msg_args', '' );

	$dialogs = get_option( 'bwlmsfields_dialogs' );

	for( $r = 0; $r < count( $defaults['toggles'] ); $r++ ) {
		if( $toggle == $defaults['toggles'][$r] ) {
			$msg = __( stripslashes( $dialogs[$r+1] ), 'wptobemem' );
			break;
		}
	}
	$defaults['msg'] = $msg;

	$defaults = apply_filters( 'bwlmsfields_msg_dialog_arr', $defaults, $toggle );
	
	extract( wp_parse_args( $args, $defaults ) );
	
	$str = $div_before . $p_before . stripslashes( $msg ) . $p_after . $div_after;

	return apply_filters( 'bwlmsfields_msg_dialog', $str );

}
endif;


if( ! function_exists( 'bwlmsfields_inc_memberlinks' ) ):

function bwlmsfields_inc_memberlinks( $page = 'members' ) 
{
	global $user_login; 

	
	$memberlinkpage_before = '
							<div class="row">
								
								<div class="large-10 medium-10 small-10  large-centered medium-centered small-centered columns bwlmsfields-login-fail">	
									<p>
	';
	$memberlinkpage_after = '		</p>
								</div>
								
							</div>
							<div class="fullwidthrow_bwlmsfields_gaprow"></div>
	';
	$link = bwlmsfields_chk_qstr();
	$logout = apply_filters( 'bwlmsfields_logout_link', $link . 'a=logout' );
	
	switch( $page ) {
	
		case 'members':
			$str = '';
			$str .= bwlmsfields_user_page_detail(); 

			$str .= '
							<div class="row fullwidthrow_bwlmsfields_btn">
								
								<div class="small-12 medium-12 large-12  large-centered medium-centered small-centered columns bwlmsfields-profile-shell-cols">	
									<div class="row savepwbtn-msg-row">

										<div class="small-4 medium-4 large-4 columns pwchange-btn-row">
											<div class="bwlmsfields-savebtn-wrapper">
											  
												<div class="bwlmsfields-changepwbtn-txt">
			';
			
			$str .=  __( 'Change Account Password', 'wptobemem' ) ;
			
			$str .= '
												</div>
											
											</div>
										</div>

										<div class="small-8 medium-8 large-8 columns pwchange-btn-col-wrap">
											<a href="' . $link . 'a=pwdchange" class="bwlms-pwchange-btn"> Change Password</a>
										</div>

									</div>
								</div>
								
							</div>
							<div class="fullwidthrow_bwlmsfields_gaprow"></div>
			';


			$str = apply_filters( 'bwlmsfields_member_links', $str );
			break;
			
		case 'register':	

			$str = $memberlinkpage_before;
			$str .= sprintf( __( 'You are logged in as %s', 'wptobemem' ), $user_login ) . '</p><p>
					<a href="' . get_option('home') . '">' . __( 'Return to home', 'wptobemem' ) . '</a><br>
					<a href="' . $logout . '">' . __( 'Log out', 'wptobemem' ) . '</a>
				';
			$str .= $memberlinkpage_after;
			$str = apply_filters( 'bwlmsfields_register_links', $str );
			break;	
		
		case 'login':
			$str = $memberlinkpage_before;
			$str .= sprintf( __( 'You are logged in as %s', 'wptobemem' ), $user_login ) . '</p><p>
						<a href="' . $logout . '">' . __( 'Log out', 'wptobemem' ) . '</a>
				';
			$str .= $memberlinkpage_after;

			$str = apply_filters( 'bwlmsfields_login_links', $str );
			break;	
				
		case 'status':
			$str = $memberlinkpage_before;
			$str .= sprintf( __( 'You are logged in as %s', 'wptobemem' ), $user_login ) . '</p><p>
					<a href="' . $logout . '">' . __( 'Log out', 'wptobemem' ) . '</a>
				';
			$str .= $memberlinkpage_after;
			break;
	
	}
	
	return $str;
}
endif;


if ( ! function_exists( 'bwlmsfields_page_pwd_reset' ) ):

function bwlmsfields_page_pwd_reset( $bwlmsfields_regchk, $content )
{
	$logged_in_msg_content_before = '
						<div class="row fullwidthrow_bwlmsfields_btn">
							
							<div class="large-10 medium-10 small-10  large-centered medium-centered small-centered columns">
								<div class="savechg-accountpwd-row" id="bwlms-message-success">
									
										
	';
	$logged_in_msg_content_after = '	
									
								</div>
							</div>
							
						</div>
	';
	$logged_in_form_before = '
					<div class="row fullwidthrow_bwlmsfields_btn">
						<div class="large-5 medium-7 small-10  large-centered medium-centered small-centered columns savechgbtn-field-container" >

	';
	$logged_in_form_after = '	
								
						</div>
					</div>
	';
	
	$resetpw_form_after = '</div>';
	$resetpw_msg_before = '';
	$resetpw_msg_after= '';

	$resetpw_msg_success_before = '
						<div class="small-11 small-centered medium-8 medium-centered columns">
	';
	$resetpw_msg_success_after = '		
							<div class="row">
								<div class="small-11 small-centered medium-8 medium-centered columns savechgbtn-msg-row-msg return-home">
									<a href="'. site_url().' ">	Return to home &rarr; </a>
								</div>
							</div>
						</div>
	';

	$content ='';

	if( is_user_logged_in() ) {
		$content = $logged_in_msg_content_before;
	
		switch( $bwlmsfields_regchk ) { 
				
			case "pwdchangempty":
				$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk, __( 'Password fields cannot be empty!', 'wptobemem' ) );
				$content .= $logged_in_msg_content_after;
					
				$content .= $logged_in_form_before .bwlmsfields_inc_changepassword(). $logged_in_form_after;
				break;

			case "pwdchangerr":
				$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk );
			$content .= $logged_in_msg_content_after;
				$content .=  $logged_in_form_before .bwlmsfields_inc_changepassword(). $logged_in_form_after;
				break;

			case "pwdchangesuccess":
				$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk );
				$content .= $logged_in_msg_content_after;
				$content .=  $logged_in_form_before .bwlmsfields_inc_changepassword(). $logged_in_form_after;
				break;

			default:
//				$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk, __( 'Change your password here!', 'wptobemem' ) );
//				$content .= $logged_in_msg_content_after;
				$content =  $logged_in_form_before .bwlmsfields_inc_changepassword(). $logged_in_form_after;

				break;				
		}

	
	} else {
	
		switch( $bwlmsfields_regchk ) {

		case "pwdreseterr":
			
			$content .= bwlmsfields_inc_resetpassword();  
			$content .= $resetpw_msg_before;
			$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk );
			$content .= $resetpw_msg_after;

			$bwlmsfields_regchk = '';
			break;

		case "pwdresetsuccess":// 독립 페이지 생성
			$content .= $resetpw_msg_success_before;
			$content .= bwlmsfields_inc_regmessage( $bwlmsfields_regchk );
			$content .= $resetpw_msg_success_after;

			$bwlmsfields_regchk = ''; // clear regchk
			break;

		default:
			$content = $content . bwlmsfields_inc_resetpassword() . $resetpw_form_after;
			break;
		}
		
	}
	
	return $content;

}
endif;


if ( ! function_exists( 'bwlmsfields_page_user_edit' ) ):

function bwlmsfields_page_user_edit( $bwlmsfields_regchk, $content )
{
	global $bwlmsfields_act, $bwlmsfields_themsg;

	$heading = apply_filters( 'bwlmsfields_user_edit_heading', __( 'Edit Information', 'wptobemem' ) );
	
	if($bwlmsfields_act == "update") { 
		$content.= bwlmsfields_inc_regmessage( $bwlmsfields_regchk, $bwlmsfields_themsg ); 
	}
	$content .= bwlmsfields_inc_registration( 'edit', $heading );
	
	return $content;
}
endif;