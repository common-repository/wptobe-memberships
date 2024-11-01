<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die( 'invalid uninstall' );
}
 
if ( WP_UNINSTALL_PLUGIN ) {

	if( is_multisite() ) {

		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		$original_blog_id = get_current_blog_id();

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			bwlmsfields_uninstall_options(); 
		}
		switch_to_blog( $original_blog_id );
	
	} else {
		bwlmslevel_uninstall_options();
		bwlmsfields_uninstall_options();
		bwlmscredit_uninstall_options();
		bwlmsmessage_uninstall_options();
	}
}

function bwlmsmessage_uninstall_options()
{
    global $wpdb;

	$tables = array(
		'bwlms_message_meta',
		'bwlms_messages'
	);

	foreach($tables as $table){
		$delete_table = $wpdb->prefix . $table;
		$sql = "DROP TABLE $delete_table";
		$wpdb->query($sql);
	}

	delete_option( 'bwlms_message_init_install' );
}

function bwlmslevel_uninstall_options()
{
	global $wpdb;

	$tables = array(
		'bwlmslevel_memberships_categories',
		'bwlmslevel_memberships_pages',
		'bwlmslevel_memberships_users',
		'bwlmslevel_membership_levelmeta',
		'bwlmslevel_membership_levels'
	);

	foreach($tables as $table){
		$delete_table = $wpdb->prefix . $table;
		$sql = "DROP TABLE $delete_table";
		$wpdb->query($sql);
	}

	$sqlQuery = "DELETE FROM $wpdb->options WHERE option_name LIKE 'bwlmslevel_%'";
	$wpdb->query($sqlQuery);
}

function bwlmsfields_uninstall_options()
{
	delete_option( 'bwlmsfields_settings' );
	delete_option( 'bwlmsfields_fieldsopt'   );
	delete_option( 'bwlmsfields_dialogs'  );
	delete_option( 'bwlmsfields_tos'      );
	delete_option( 'bwlmsfields_export'   );
	delete_option( 'bwlmsfields_msurl'    );
	delete_option( 'bwlmsfields_regurl'   );
	delete_option( 'bwlmsfields_cssurl'   );
	delete_option( 'bwlmsfields_autoex'   );
	delete_option( 'bwlmsfields_utfields' );
	delete_option( 'bwlmsfields_attrib'   );

	delete_option( 'bwlmsfields_email_newreg' );
	delete_option( 'bwlmsfields_email_newmod' );
	delete_option( 'bwlmsfields_email_appmod' );
	delete_option( 'bwlmsfields_email_repass' );
	delete_option( 'bwlmsfields_email_footer' );
	delete_option( 'bwlmsfields_email_notify' );
	delete_option( 'bwlmsfields_email_wpfrom' );
	delete_option( 'bwlmsfields_email_wpname' );
}

function bwlmscredit_uninstall_options() 
{
	$installed = apply_filters( 'bwlmscredit_uninstall_this', array(
		'bwlmscredit_pref_core',
		'bwlmscredit_pref_hooks',
		'bwlmscredit_pref_remote',
		'bwlmscredit_types',
	) );

	foreach ( $installed as $option_id )
		delete_option( $option_id );

	delete_option( 'bwlmscredit_setup_completed' );
	delete_option( 'bwlmscredit_version' );
	delete_option( 'bwlmscredit_version_db' );
	delete_option( 'bwlmscredit_key' );

	wp_clear_scheduled_hook( 'bwlmscredit_reset_key' );

	global $wpdb;

	// Get log table
	if ( defined( 'BWLMSCREDIT_LOG_TABLE' ) )
		$table_name = BWLMSCREDIT_LOG_TABLE;

	else {

		if ( ! is_multisite()  )
			$table_name = $wpdb->base_prefix . 'bwlmsCREDIT_log';
		else
			$table_name = $wpdb->prefix . 'bwlmsCREDIT_log';

	}

	$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" );

	if ( is_multisite() )
		delete_site_option( 'bwlmscredit_network' );
	
	$post_types = array( 'bwlmscredit_rank', 'bwlmscredit_email_notice', 'bwlmscredit_badge', 'buycred_payment' );
	if ( is_array( $post_types ) || ! empty( $post_types ) )
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "','", $post_types ) . "');" );

	$post_meta = array( 'bwlmsCREDIT_sell_content', 'bwlmscredit_rank_min', 'bwlmscredit_rank_max', 'badge_requirements', 'bwlmscredit_email_instance', 'bwlmscredit_email_settings', 'bwlmscredit_email_ctype', 'bwlmscredit_email_styling', 'ctype' );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('" . implode( "','", $post_meta ) . "');" );


	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s;", 'bwlmscredit_pref%' ) );
	
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s;", 'bwlmscredit_badge%' ) );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ( %s, %s );", 'bwlmscredit_rank%', 'bwlmscredit_rank' ) );
}