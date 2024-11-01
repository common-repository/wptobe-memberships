<?php
define( '__FILE__',          __FILE__ );
require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-functions.php");
require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-module-class.php");

function bwlmcredit_activation() {

	$install = new bwlmsCREDIT_Install();

	$install->compat();

	if ( $install->ver === false )
	{// Activate first time.
		$install->activate();
	}
	else // Re-activation
	{	
		$install->reactivate();
	}
}


if ( ! class_exists( 'bwlmsCREDIT_Core' ) ) {
	final class bwlmsCREDIT_Core {

		function __construct() {
			_deprecated_function( __CLASS__, '1.0', 'bwlmscredit_load()' );
		}
	}
}

if ( ! function_exists( 'bwlmscredit_load' ) ) :
	function bwlmscredit_load()
	{
		// Check Network blocking
		if ( bwlmscredit_is_site_blocked() ) return;

		// Load required files
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-log.php");
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-network.php"); //Multisite
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-protect.php");

//		do_action( 'bwlmscredit_ready' );
		add_action( 'plugins_loaded',   'bwlmscredit_plugin_start_up', 999 );
		add_action( 'init',             'bwlmscredit_init', 5 );
		add_action( 'admin_init',       'bwlmscredit_admin_init' );
		add_action( 'bwlmscredit_reset_key', 'bwlmscredit_reset_key' );

	}
endif;

bwlmscredit_load();



if ( ! function_exists( 'bwlmscredit_plugin_start_up' ) ) :
	function bwlmscredit_plugin_start_up()
	{
		global $bwlmscredit, $bwlmscredit_types, $bwlmscredit_modules;
		$bwlmscredit = new bwlmsCREDIT_Settings();

		$bwlmscredit_types = bwlmscredit_get_types();

		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-shortcodes.php");

		// Lets start with Multisite
		if ( is_multisite() ) {

			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once ABSPATH . '/wp-admin/plugin.php';

			if ( is_plugin_active_for_network( 'bwlms-credit/bwlms-credit.php' ) ) {
				$network = new bwlmsCREDIT_Network_Module();
				$network->load();
			}
		}

		// Load Point Settings
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-settings.php");
		foreach ( $bwlmscredit_types as $type => $title ) {
			$bwlmscredit_modules[ $type ]['hooks'] = new bwlmsCREDIT_Hooks_Module( $type );
			$bwlmscredit_modules[ $type ]['hooks']->load();
		}

		// Load log
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-log-class.php");
		foreach ( $bwlmscredit_types as $type => $title ) {
			$bwlmscredit_modules[ $type ]['log'] = new bwlmsCREDIT_Log_Module( $type );
			$bwlmscredit_modules[ $type ]['log']->load();
		}

		// Load admin
		require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-admin.php");
		$admin = new bwlmsCREDIT_Admin();
		$admin->load();

		do_action( 'bwlmscredit_pre_init' );
	}
endif;

if ( ! function_exists( 'bwlmscredit_init' ) ) :
	function bwlmscredit_init()
	{

		if ( ! wp_next_scheduled( 'bwlmscredit_reset_key' ) )
			wp_schedule_event( date_i18n( 'U' ), apply_filters( 'bwlmscredit_cron_reset_key', 'daily' ), 'bwlmscredit_reset_key' );

		add_action( 'admin_enqueue_scripts', 'bwlmscredit_enqueue_admin' );

//		add_action( 'admin_head',     'bwlmscredit_admin_head', 999 );
		add_action( 'admin_menu',     'bwlmscredit_admin_menu', 9 );
		add_action( 'wp_ajax_bwlmscredit-send-points', 'bwlmscredit_shortcode_send_points_ajax' );
		add_shortcode( 'bwlmscredit_affiliate_link', 'bwlmscredit_render_affiliate_link' );
		add_shortcode( 'bwlmscredit_affiliate_id',   'bwlmscredit_render_affiliate_id' );
		do_action( 'bwlmscredit_init' );
	}
endif;

if ( ! function_exists( 'bwlmscredit_admin_init' ) ) :
	function bwlmscredit_admin_init()
	{

		do_action( 'bwlmscredit_admin_init' );

		if ( get_transient( '_bwlmscredit_activation_redirect' ) === apply_filters( 'bwlmscredit_active_redirect', false ) )
			return;

		delete_transient( '_bwlmscredit_activation_redirect' );

	}
endif;


if ( ! function_exists( 'bwlmscredit_admin_menu' ) ) :
	function bwlmscredit_admin_menu()
	{
		$bwlmscredit = bwlmscredit();
		$name = bwlmscredit_label( true );

		global $bwlmscredit_types, $wp_version;

		$pages = array();
		$slug = 'bwlmsCREDIT';

		$pages = apply_filters( 'bwlmscredit_admin_pages', $pages, $bwlmscredit );

		do_action( 'bwlmscredit_add_menu', $bwlmscredit );
		
	}
endif;


if ( ! function_exists( 'bwlmscredit_enqueue_admin' ) ) :
	function bwlmscredit_enqueue_admin()
	{
		$bwlmscredit = bwlmscredit();

		wp_localize_script(
			'bwlmscredit-manage',
			'bwlmsCREDITmanage',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'token'         => wp_create_nonce( 'bwlmscredit-management-actions' ),
				'working'       => esc_attr__( 'Processing...', 'wptobemem' ),
				'confirm_log'   => esc_attr__( 'Warning! All entries in your log will be permanently removed! This can not be undone!', 'wptobemem' ),
				'confirm_clean' => esc_attr__( 'All log entries belonging to deleted users will be permanently deleted! This can not be undone!', 'wptobemem' ),
				'confirm_reset' => esc_attr__( 'Warning! All user balances will be set to zero! This can not be undone!', 'wptobemem' ),
				'done'          => esc_attr__( 'Done!', 'wptobemem' ),
				'export_close'  => esc_attr__( 'Close', 'wptobemem' ),
				'export_title'  => $bwlmscredit->template_tags_general( esc_attr__( 'Export users %plural%', 'wptobemem' ) ),
				'decimals'      => esc_attr__( 'In order to adjust the number of decimal places you want to use we must update your log. It is highly recommended that you backup your current log before continuing!', 'wptobemem' )
			)
		);

		wp_register_script( 
			'bwlmscredit-inline-edit',plugin_dir_url(__FILE__) . '../js/bwlmscredit-edit.js',		array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide' )
		); 
		wp_localize_script(
			'bwlmscredit-inline-edit',
			'bwlmsCREDITedit',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'title'   => esc_attr__( 'Edit Users Balance', 'wptobemem' ),
				'close'   => esc_attr__( 'Close', 'wptobemem' ),
				'working' => esc_attr__( 'Processing...', 'wptobemem' )
			)
		);
		
		wp_register_script(
			'bwlmscredit-edit-log',
			plugin_dir_url(__FILE__) . '../js/bwlmscredit-edit-log.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide' )
		);
		wp_localize_script(
			'bwlmscredit-edit-log',
			'bwlmsCREDITLog',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'title'   => esc_attr__( 'Edit Log Entry', 'wptobemem' ),
				'close'   => esc_attr__( 'Close', 'wptobemem' ),
				'working' => esc_attr__( 'Processing...', 'wptobemem' ),
				'messages' => array(
					'delete_row'  => esc_attr__( 'Are you sure you want to delete this log entry? This can not be undone!', 'wptobemem' ),
					'updated_row' => esc_attr__( 'Log entry updated', 'wptobemem' )
				),
				'tokens' => array(
					'delete_row' => wp_create_nonce( 'bwlmscredit-delete-log-entry' ),
					'update_row' => wp_create_nonce( 'bwlmscredit-update-log-entry' )
				)
			)
		);

		wp_register_style(
			'bwlmscredit-inline-edit',
			plugin_dir_url(__FILE__) . '../css/bwlmscredit-edit.css',
			false
		);

		do_action( 'bwlmscredit_admin_enqueue' );
	}
endif;


if ( ! function_exists( 'bwlmscredit_reset_key' ) ) :
	function bwlmscredit_reset_key()
	{
		$protect = bwlmscredit_protect();
		if ( $protect !== false )
			$protect->reset_key();
	}
endif;



function bwlmscredit_main_settings_menu()
{

//	add_submenu_page( // 필요시 다시 사용
//						'wptobemem-menu',
//						__('Point Setting', 'wptobemem'),	//title
//						__('Point Setting', 'wptobemem'),	//menu title
//						'bwlmsCREDIT',					//capability
//						'bwlmsCREDIT',					//menu slug
//						'bwlmsCREDIT'					//callable function
//	);
	
	add_submenu_page(
						'wptobemem-menu',
						__('PointS', 'wptobemem'),	//title
						__('Point Log', 'wptobemem'),	//menu title
						'manage_options',				//capability
						'bwlmsCREDIT_page_log',			//menu slug
						'bwlmsCREDIT'					//callable function
	);

}
add_action('admin_menu', 'bwlmscredit_main_settings_menu');


if ( ! class_exists( 'bwlmsCREDIT_Install' ) ) :
	class bwlmsCREDIT_Install {
	
		public $core;
		public $ver;

		function __construct() {
			$this->core = bwlmscredit();
			// Get main sites settings
			$this->ver = get_option( 'bwlmscredit_version', false );
		}


		public function compat() {
			global $wpdb;

			$message = array();
			$wp_version = $GLOBALS['wp_version'];

			if ( version_compare( $wp_version, '3.8', '<' ) && ! defined( 'BWLMSCREDIT_FOR_OLDER_WP' ) )
				$message[] = __( 'WPTOBE Memberships requires WordPress 3.8 or higher. Version detected:', 'wptobemem' ) . ' ' . $wp_version;

			$php_version = phpversion();
			if ( version_compare( $php_version, '5.2.4', '<' ) )
				$message[] = __( 'WPTOBE Memberships requires PHP 5.2.4 or higher. Version detected: ', 'wptobemem' ) . ' ' . $php_version;

			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) )
				$message[] = __( 'WPTOBE Memberships requires SQL 5.0 or higher. Version detected: ', 'wptobemem' ) . ' ' . $sql_version;

			$extensions = get_loaded_extensions();
			if ( ! in_array( 'mcrypt', $extensions ) && ! defined( 'BWLMSCREDIT_DISABLE_PROTECTION' ) )
				$message[] = __( 'The mcrypt PHP library must be enabled in order to use this plugin! Please check your PHP configuration or contact your host and ask them to enable it for you!', 'wptobemem' );

			if ( ! empty( $message ) ) {
				$error_message = implode( "\n", $message );
				die( __( 'Sorry but your WordPress installation does not reach the minimum requirements for running WPTOBE Memberships. The following errors were given:', 'wptobemem' ) . "\n" . $error_message );
			}
		}

		public function activate() {

			add_option( 'bwlmscredit_pref_core', $this->core->defaults() );
			add_option( 'bwlmscredit_pref_hooks', array(
				'installed'  => array(),
				'active'     => array(),
				'hook_prefs' => array()
			) );

			add_option( 'bwlmscredit_version', $this->ver);

			$key = wp_generate_password( 12, true, true );
			add_option( 'bwlmscredit_key', $key );

			do_action( 'bwlmscredit_activation' );

			if ( isset( $_GET['activate-multi'] ) )
				return;

			$settings['credit_id'] = 'bwlmscredit_default';

			if (isset($_POST['bwlmsCREDIT-format-dec'])) {
				$settings['format']['decimals'] = (int) sanitize_text_field( $_POST['bwlmsCREDIT-format-dec'] );
			}else {
				$settings['format']['decimals'] = 0;
			}

			if ( empty( $settings['format']['decimals'] ) ) $settings['format']['decimals'] = 0;

			//$settings['format']['separators']['decimal'] = $_POST['bwlmsCREDIT-sep-dec'];
			//$settings['format']['separators']['thousand'] = $_POST['bwlmsCREDIT-sep-tho'];

			if ( $settings['format']['decimals'] > 0 )
				$settings['format']['type'] = 'decimal';
			else
				$settings['format']['type'] = 'bigint';

			if ( ! function_exists( 'bwlmscredit_install_log' ) )
				require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-functions.php" );

			//bwlmscredit_install_log( $decimals = 0, $table = NULL )
			//bwlmscredit_install_log( $settings['format']['decimals'], $this->log_table );
			bwlmscredit_install_log( $settings['format']['decimals']);

			update_option( 'bwlmscredit_setup_completed', date_i18n( 'U' ) );
		}


		public function reactivate() {

			do_action( 'bwlmscredit_reactivation', $this->ver );

			if ( isset( $_GET['activate-multi'] ) )
				return;

			set_transient( '_bwlmscredit_activation_redirect', true, 60 );
		}

	}
endif;