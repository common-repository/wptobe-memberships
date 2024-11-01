<?php

function bwlmsfields_initialize()
{
	do_action( 'bwlmsfields_pre_init' );

	$bwlmsfields_settings = get_option( 'bwlmsfields_settings' );


	$bwlmsfields_settings = apply_filters( 'bwlmsfields_settings', $bwlmsfields_settings );

	/**
	 * define constants based on option settings
	 */
	( ! defined( 'BWLMSFIELDS_BLOCK_POSTS'  ) ) ? define( 'BWLMSFIELDS_BLOCK_POSTS',  $bwlmsfields_settings[1]  ) : '';
	( ! defined( 'BWLMSFIELDS_BLOCK_PAGES'  ) ) ? define( 'BWLMSFIELDS_BLOCK_PAGES',  $bwlmsfields_settings[2]  ) : '';
	( ! defined( 'BWLMSFIELDS_SHOW_EXCERPT' ) ) ? define( 'BWLMSFIELDS_SHOW_EXCERPT', $bwlmsfields_settings[3]  ) : '';
	( ! defined( 'BWLMSFIELDS_NOTIFY_ADMIN' ) ) ? define( 'BWLMSFIELDS_NOTIFY_ADMIN', $bwlmsfields_settings[4]  ) : '';
	( ! defined( 'BWLMSFIELDS_MOD_REG'      ) ) ? define( 'BWLMSFIELDS_MOD_REG',      $bwlmsfields_settings[5]  ) : '';
	( ! defined( 'BWLMSFIELDS_CAPTCHA'      ) ) ? define( 'BWLMSFIELDS_CAPTCHA',      $bwlmsfields_settings[6]  ) : '';
	( ! defined( 'BWLMSFIELDS_NO_REG'       ) ) ? define( 'BWLMSFIELDS_NO_REG',       $bwlmsfields_settings[7]  ) : '';
	( ! defined( 'BWLMSFIELDS_USE_EXP'      ) ) ? define( 'BWLMSFIELDS_USE_EXP',      $bwlmsfields_settings[9]  ) : '';
	( ! defined( 'BWLMSFIELDS_USE_TRL'      ) ) ? define( 'BWLMSFIELDS_USE_TRL',      $bwlmsfields_settings[10] ) : '';

	( ! defined( 'BWLMSFIELDS_MSURL'  ) ) ? define( 'BWLMSFIELDS_MSURL',  get_option( 'bwlmsfields_msurl', null ) ) : '';
	( ! defined( 'BWLMSFIELDS_REGURL' ) ) ? define( 'BWLMSFIELDS_REGURL', get_option( 'bwlmsfields_regurl',null ) ) : '';

	require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-core.php");


	add_action( 'init', 'bwlmsfields_mainfunc' );
	add_action( 'wp_head', 'bwlmsfields_head' );              // currentlly nothing
	add_action( 'admin_init', 'bwlmsfields_loadfunc_user' );  
//	add_action( 'admin_menu', 'bwlmsfields_add_admin_menu' );       
	add_action( 'user_register', 'bwlmsfields_to_wp_registration' );  
	add_action( 'login_enqueue_scripts', 'bwlmsfields_wplogin_style' ); // WordPress login page styling

	add_filter( 'allow_password_reset', 'bwlmsfields_chkpermission_pwreset' );  
	add_filter( 'the_content', 'bwlmsfields_securify', 1, 1 );     // securifies the_content
	add_filter( 'register_form', 'bwlmsfields_registration_form' ); // adds fields to the default wp registration
	add_filter( 'registration_errors', 'bwlmsfields_registration_validate', 10, 3 ); 


	add_shortcode( 'bwlms-fields',       'bwlmsfields_sc' );
	add_shortcode( 'bwlms_userfield',      'bwlmsfields_sc' );
	add_shortcode( 'bwlmsfields_loggedin',  'bwlmsfields_sc' );
	add_shortcode( 'bwlmsfields_logged_out', 'bwlmsfields_sc' );
	add_shortcode( 'bwlmsfields_logout',     'bwlmsfields_sc' );


	if( BWLMSFIELDS_MOD_REG == 1 ) { 
		add_filter( 'authenticate', 'bwlmsfields_check_activated', 99, 3 ); 
	}

	do_action( 'bwlmsfields_after_init' );
}


function bwlmsfields_loadfunc_user()
{

	do_action( 'bwlmsfields_pre_admin_init' );

	if( is_multisite() && current_user_can( 'edit_theme_options' ) ) {
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/admin.php" );
	}
	
	if( current_user_can( 'edit_users' ) ) {
		//Administrator
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/admin.php" );
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/user-profile.php" );
	} 
	else { 
		//Users
		require_once(BWLMSMEM_DIR . "/bwlms-fields/nonadmin-user-profile.php" );
		add_action( 'show_user_profile', 'bwlmsfields_user_profile'   );
		add_action( 'edit_user_profile', 'bwlmsfields_user_profile'   );
		add_action( 'profile_update',    'bwlmsfields_profile_update' );
	}
	
	do_action( 'bwlmsfields_after_admin_init' );
}

function bwlmsfields_add_admin_menu() {
	if( ! is_multisite() || ( is_multisite() && current_user_can( 'edit_theme_options' ) ) ) {
		$plugin_page = add_submenu_page(
				'wptobemem-menu',
				__('User Fields', 'wptobemem'), 
				__('User Fields', 'wptobemem'), 
				'manage_options', 
				'bwlmsfields-settings', 
				'bwlmsfields_admin'
			);
		// Drag & Drop Positioning
		add_action( 'load-'.$plugin_page, 'bwlmsfields_load_admin_js' ); 
	}
}

function bwlmsfields_activation() {
	require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-install.php" );
	if( is_multisite() ) {
		// if it is multisite, install options for each blog
		global $wpdb;
		$blogs = $wpdb->get_results("
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = '{$wpdb->siteid}'
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
		");
		$original_blog_id = get_current_blog_id();   
		foreach ( $blogs as $blog_id ) {
			switch_to_blog( $blog_id->blog_id );
			bwlmsfields_do_install();
		}   
		switch_to_blog( $original_blog_id );
	} else {
		// normal single install
		bwlmsfields_do_install();
	}
}


add_action( 'bwlmsfields_newmultisite_blog', 'bwlmsfields_new_multisite', 10, 6 );

function bwlmsfields_new_multisite( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields-install.php" );
	switch_to_blog( $blog_id );
	bwlmsfields_do_install();
	restore_current_blog();
}

add_action('wp_ajax_del_img_ajax_call', 'del_img_ajax_call');
function del_img_ajax_call() {
	global $wpdb;
	
	$current_user = wp_get_current_user();

	$data = array(
		'errcode'=>'',	
		'message'=>'',
		'sql1'=>'',
		'sql2'=>'',
		'count'=>''
	);

	if(!is_user_logged_in())
	{

		$data['errcode'] ="N";
		$data['message'] ="Please login first!";
	}
	else
	{
		@unlink($_POST['img']);
		update_user_meta( $current_user->ID , sanitize_file_name( $_POST['val'] ), '' );
		$data['errcode'] ="Y";
		$data['message'] ="Delete? ".sanitize_file_name( $_POST['val'] ).$current_user->ID;
		
	}
	
	echo json_encode($data);
	wp_die(); 
}


add_action('wp_ajax_user_del_img_admin_ajax_call', 'user_del_img_admin_ajax_call');
function user_del_img_admin_ajax_call() {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$data = array(
		'errcode'=>'',	
		'message'=>'',
		'sql1'=>'',
		'sql2'=>'',
		'count'=>''
	);

	if(!is_user_logged_in())
	{

		$data['errcode'] ="N";
		$data['message'] ="Please login first.";
	}
	else
	{
		
		update_user_meta( absint($_POST['user_id']) ,sanitize_file_name( $_POST['val'] ), '' );
		@unlink($_POST['img']);
		$data['errcode'] ="Y";
	}
	
	echo json_encode($data);
	wp_die(); 
}