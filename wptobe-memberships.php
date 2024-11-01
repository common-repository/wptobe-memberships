<?php
/*
Plugin Name: WPTOBE Memberships
Plugin URI: http://www.wptobe.com/
Description: WPTOBE Memberships plugin
Version: 1.2.68
Author: WPTOBE Cooperation
Author URI: http://www.wptobe.com/wptobememberships/
License: GPLv2 or later
*/

define("BWLMSMEM_DIR", dirname(__FILE__));
define("BWLMSMEM_DIRURL", plugin_dir_url ( __FILE__ ));
define("BWLMSMEM_URL", WP_PLUGIN_URL . "/wptobe-memberships");

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			Wptobe Memberships	: Admin Settings			*/
/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
require_once (BWLMSMEM_DIR . "/admin/wptobemem-options.php");
require_once (BWLMSMEM_DIR . "/captcha/securimage.php");

add_action( 'admin_enqueue_scripts', 'load_admin_memberships_stylenscript' );
function load_admin_memberships_stylenscript($page) {

	if($page == 'profile.php' || $page == 'users.php' || $page == 'user-new.php' || $page == 'user-edit.php' )  {
		wp_register_script( 'bwlmsfields-form-enctype', plugin_dir_url(__FILE__) . 'js/bwlmsfields-enctype.js');	
		
		wp_enqueue_script('membership-user-edit',	plugin_dir_url(__FILE__) . 'js/wptobe-memberships.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide' ));
	}

	if($page == 'toplevel_page_wptobemem-menu')  {
		$foundationmin_css = plugins_url('foundation/css/foundation.min.css',__FILE__ );
		wp_enqueue_style('wptobe_memberships_foundationmin_css', plugins_url('foundation/css/foundation.min.css',__FILE__ ));
		
		//For tabs
		wp_enqueue_style('wptobe_memberships_admin_options_css', plugins_url('admin/wptobemem-admin.css',__FILE__ ));
		$jquery213_js = plugins_url('foundation/js/jquery-2.1.3.js',__FILE__ );
		wp_enqueue_script('jquery213', $jquery213_js);
		wp_enqueue_script('wptobe_memberships_admin_options_tabjs', plugins_url('admin/wptobemem-admin-tab.js',__FILE__ ));

		// User Fields
		wp_enqueue_script( 'bwlmsfields-managefield-js',  BWLMSMEM_DIRURL.'js/bwlmsfields-managefield.js' ); 
		wp_enqueue_style ( 'bwlmsfields-admin-css', BWLMSMEM_DIRURL.'css/bwlms-fields-addfield.css' );

		// Message
		wp_enqueue_style('bwlmsmsg_admin', plugins_url('css/bwlms-message-admin.css',__FILE__ ));
		// Credit
		wp_enqueue_style('wptobe_memberships_credit_admin_css', plugins_url('css/bwlms-credit-admin.css',__FILE__ ));
		// Level
		wp_enqueue_style('bwlmslevel_admin',  plugins_url('css/bwlms-level-admin.css',__FILE__ ));
	}
	
	if($page == 'memberships_page_bwlmslevel-memberslist')  {
		wp_enqueue_style('bwlms_credit_edit_css',  plugins_url('css/bwlms-credit-edit.css',__FILE__ ));
		wp_enqueue_script('memberlist-point-edit',	plugin_dir_url(__FILE__) . 'js/bwlmscredit-edit.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide' ));
	}

	if($page == 'admin_page_bwlmsCREDIT')  {
		wp_enqueue_style('wptobe_memberships_foundationmin_css', plugins_url('foundation/css/foundation.min.css',__FILE__ ));
		wp_enqueue_style('wptobe_memberships_credit_admin_css', plugins_url('css/bwlms-credit-admin.css',__FILE__ ));
	}	

	if($page == 'admin_page_bwlmsCREDIT_page_log')  {
	}
	
	if($page == 'memberships_page_bwlmsfields-settings')  {
	}
}

add_action('wp_enqueue_scripts', 'bwlms_level_frontend_enqueue_scripts');
function bwlms_level_frontend_enqueue_scripts(){

		$current_theme_wptobe = wp_get_theme( 'wptobe' );
		if ( !$current_theme_wptobe->exists() ) {//Current theme is not WPTOBE
			$jquery213_js = plugins_url('foundation/js/jquery-2.1.3.js',__FILE__ );
			wp_enqueue_script('jquery213', $jquery213_js);
		}

		$foundation_min_css = plugins_url('foundation/css/foundation.min.css',__FILE__ );
		wp_enqueue_style('foundation_core_css', $foundation_min_css);

		$foundation_min_js = plugins_url('foundation/js/foundation.min.js',__FILE__ );
		wp_enqueue_script('foundation_core_js', $foundation_min_js);
		$foundation_reveal_js = plugins_url('foundation/js/foundation.reveal.js',__FILE__ );
		wp_enqueue_script('foundation_reveal_js', $foundation_reveal_js);

		$wptobe_memberships_userpage_style = plugins_url('css/wptobemem-frontend.css',__FILE__ );
		wp_enqueue_style( 'bwlms-message-style', $wptobe_memberships_userpage_style );

		$bwlmsfield_profile_picture = plugins_url('js/bwlmsfields-profile-pic.js',__FILE__ );
		wp_enqueue_script('bwlmsfield_profile_pic', $bwlmsfield_profile_picture);
}

add_action( 'plugins_loaded', 'wptobememberships_load_textdomain' );
function wptobememberships_load_textdomain() {
		$file = apply_filters( 'wptobemem_local_file', dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		load_plugin_textdomain( 'wptobemem', false, $file );
}

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			 Wptobe Memberships	: Level						*/
/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
require_once (BWLMSMEM_DIR . "/bwlms-level/bwlms-level.php");

register_activation_hook(__FILE__, 'bwlmslevel_activation');
register_deactivation_hook(__FILE__, 'bwlmslevel_deactivation');

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			 Wptobe Memberships	: User Fields				*/
/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
require_once(BWLMSMEM_DIR . "/bwlms-fields/bwlms-fields.php");	
add_action( 'after_setup_theme', 'bwlmsfields_initialize', 10 );
register_activation_hook( __FILE__, 'bwlmsfields_activation' );

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			 Wptobe Memberships:	Messages				*/
/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
require_once(BWLMSMEM_DIR . "/bwlms-message/bwlms-message.php");	
register_activation_hook(__FILE__ , 'bwlms_message_activation');

/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
/*			 Wptobe Memberships	: Credits					*/
/* <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */
require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlms-credit.php");
register_activation_hook(__FILE__, 'bwlmcredit_activation');