<?php
global $wpdb;

define('BWLMS_MESSAGES_TABLE',$wpdb->prefix.'bwlms_messages');
define('BWLMS_MESSAGE_META_TABLE',$wpdb->prefix.'bwlms_message_meta');

require_once(BWLMSMEM_DIR ."/bwlms-message/bwlms-message-ui.php");


function bwlms_message_activation()
{
	global $wpdb;

	$charset_collate = '';
	if( $wpdb->has_cap('collation'))
	{
		if(!empty($wpdb->charset))
		  $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if(!empty($wpdb->collate))
		  $charset_collate .= " COLLATE $wpdb->collate";
	}

	if($wpdb->get_var("SHOW TABLES LIKE '".BWLMS_MESSAGES_TABLE."'") != BWLMS_MESSAGES_TABLE) {

		$sqlMsgs = 	"CREATE TABLE ".BWLMS_MESSAGES_TABLE." (
			id int(11) NOT NULL auto_increment,
			parent_id int(11) NOT NULL default '0',
			from_user int(11) NOT NULL default '0',
			to_user int(11) NOT NULL default '0',
			last_sender int(11) NOT NULL default '0',
			send_date datetime NOT NULL default '0000-00-00 00:00:00',
			last_date datetime NOT NULL default '0000-00-00 00:00:00',
			message_title varchar(256) NOT NULL,
			message_contents longtext NOT NULL,
			status int(11) NOT NULL default '0',
			to_del int(11) NOT NULL default '0',
			from_del int(11) NOT NULL default '0',
			PRIMARY KEY (id))
			{$charset_collate};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sqlMsgs);
	}
  
	if( $wpdb->get_var("SHOW TABLES LIKE '".BWLMS_MESSAGE_META_TABLE."'") != BWLMS_MESSAGE_META_TABLE) {

		$sql_meta = 	"CREATE TABLE ".BWLMS_MESSAGE_META_TABLE." (
			meta_id int(11) NOT NULL auto_increment,
			message_id int(11) NOT NULL default '0',
			field_name varchar(128) NOT NULL,
			field_value longtext NOT NULL,
			PRIMARY KEY (meta_id),
			KEY (field_name))
			{$charset_collate};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql_meta);

	}

	if(!get_option( "bwlms_message_init_install" )) {
		//첫번째 인스톨이면
		update_option( 'bwlms_message_init_install', 'installed' );
	}

}

add_action('after_setup_theme', 'bwlms_message_include_require_files'); 
function bwlms_message_include_require_files() {

	if ( is_admin() ) {
			$bwlms_message_files = array(
							'admin' => 'bwlms-message-admin-class.php'
							);
										
	} 
	else {
			$bwlms_message_files = array(
							'main' => 'bwlms-message-class.php',
							'menu' => 'bwlms-message-menu-class.php',
							'frontend-admin' => 'bwlms-message-admin-frontend-class.php',
							'email' => 'bwlms-message-email-class.php'
							);
	}

	$bwlms_message_files['functions'] = 'bwlms-message-data-filter.php';
	$bwlms_message_files['attachment'] = 'bwlms-message-attachment-class.php';

	$bwlms_message_files = apply_filters('bwlms_message_include_files', $bwlms_message_files );

	foreach ( $bwlms_message_files as $bwlms_message_file ) {
		require_once ( $bwlms_message_file );
	}
}

add_action('wp_enqueue_scripts', 'bwlms_message_enqueue_scripts');
function bwlms_message_enqueue_scripts(){

//	wp_enqueue_script( 'bwlms-message-replynoti-script',  plugins_url('js/bwlmsmsg-reply-noti.js',dirname(__FILE__) ) );

	wp_register_script( 'bwlms-message-attachment-script', plugins_url('js/bwlmsmsg-file-attach.js',dirname(__FILE__) ) );
	wp_localize_script( 'bwlms-message-attachment-script', 'bwlms_message_attachment_script', 
			array( 
				'remove' => esc_js(__('x', 'wptobemem')),
				'maximum' => esc_js( bwlms_message_get_option('attachment_no', 4) ),
				'max_text' => esc_js(__('Maximum file allowed', 'wptobemem'))
			) 
		);
}

//add_action('admin_menu', 'bwlms_message_create_menu');
function bwlms_message_create_menu(){

		$bwlms_message_admin_class = new WptobeMsgadminClass;
		$bwlms_message_admin_class->addAdminPage();
}