<?php
require_once (BWLMSMEM_DIR . "/bwlms-level/core.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/admin/bwlms-levels-menu.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/admin/memberlist-exp.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/metaboxes.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/profile.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/content.php");
require_once (BWLMSMEM_DIR . "/bwlms-level/sc-bwlms_level.php");

global $wpdb, $all_membership_levels, $membership_levels;
$membership_levels = $wpdb->get_results( "SELECT * FROM {$wpdb->bwlmslevel_membership_levels}", OBJECT );

function bwlmslevel_create_database() {
	if(!is_admin()) return;

	$force_db_install=false;
	
	if($force_db_install==true) {
		update_option("bwlmslevel_db_version", "0.0");
	}

	$installed_db_version = get_option("bwlmslevel_db_version");

	if( $installed_db_version=='0.0' || $installed_db_version==false) {
		bwlmslevel_db_delta();
		update_option("bwlmslevel_db_version", "1.0");
	}
}

function bwlmslevel_activation() {

	$role = get_role( 'administrator' );
	$role->add_cap( 'bwlmslevel_memberships_menu' );
	$role->add_cap( 'bwlmslevel_membershiplevels' );
	$role->add_cap( 'bwlmslevel_edit_memberships' );
	$role->add_cap( 'bwlmslevel_memberslist' );
	$role->add_cap( 'bwlmslevel_memberslistcsv' );

	bwlmslevel_create_database();
	bwlmslevel_create_initial_level();

	do_action('bwlmslevel_activation');
}

function bwlmslevel_deactivation() {

	$role = get_role( 'administrator' );
	$role->remove_cap( 'bwlmslevel_memberships_menu' );
	$role->remove_cap( 'bwlmslevel_membershiplevels' );
	$role->remove_cap( 'bwlmslevel_edit_memberships' );
	$role->remove_cap( 'bwlmslevel_memberslist' );
	$role->remove_cap( 'bwlmslevel_memberslistcsv' );

	do_action('bwlmslevel_deactivation');
}

function bwlmslevel_create_initial_level() {
	global $wpdb;
	
	$force_level_reset=false;

	$initial_levels = array("Blue","Bronze","Silver","Gold","Platinum","Diamond","Vip","VVip","VVipElite","VVipStar");
	$numof_level =  count($initial_levels);

	if($force_level_reset==true) {
		$wpdb->query("DELETE * FROM  {$wpdb->bwlmslevel_membership_levels} ");
	}

	$count_level = $wpdb->get_var("SELECT id FROM $wpdb->bwlmslevel_membership_levels  LIMIT 1");
		
		$j = 0;
		if(empty($count_level))
		{
			for($i=0;$i<$numof_level;$i++)
			{
				$j = $i+1;
				$sqlQuery = "INSERT INTO {$wpdb->bwlmslevel_membership_levels}( name, level ) 
							 VALUES ( '" .	esc_sql($initial_levels[$i]) . "','" .$j."' )
				";
				$wpdb->query($sqlQuery);
			}
		}
}

function bwlmslevel_db_delta()
{
	global $wpdb;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	//$wpdb->hide_errors();
	$wpdb->bwlmslevel_membership_levels = $wpdb->prefix . 'bwlmslevel_membership_levels';
	$wpdb->bwlmslevel_memberships_users = $wpdb->prefix . 'bwlmslevel_memberships_users';
	$wpdb->bwlmslevel_memberships_categories = $wpdb->prefix . 'bwlmslevel_memberships_categories';
	$wpdb->bwlmslevel_membership_levelmeta = $wpdb->prefix . 'bwlmslevel_membership_levelmeta';


	//wp_bwlmslevel_membership_levels
	$sqlQuery = "
		CREATE TABLE " . $wpdb->bwlmslevel_membership_levels . " (
					  id int(11) unsigned NOT NULL AUTO_INCREMENT,
					  name varchar(255) NOT NULL,
					  level  int(11) NOT NULL DEFAULT '10',
					  description longtext NOT NULL,
					  confirmation longtext NOT NULL,
					  initial_payment decimal(10,2) NOT NULL DEFAULT '0.00',
					  billing_amount decimal(10,2) NOT NULL DEFAULT '0.00',
					  cycle_number int(11) NOT NULL DEFAULT '0',
					  cycle_period enum('Day','Week','Month','Year') DEFAULT 'Month',
					  billing_limit int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
					  trial_amount decimal(10,2) NOT NULL DEFAULT '0.00',
					  trial_limit int(11) NOT NULL DEFAULT '0',
					  allow_signups tinyint(4) NOT NULL DEFAULT '1',
					  expiration_number int(10) unsigned NOT NULL,
					  expiration_period enum('Day','Week','Month','Year') NOT NULL,
					  PRIMARY KEY (id),
					  KEY allow_signups (allow_signups),
					  KEY initial_payment (initial_payment),
					  KEY name (name)
					);
	";
	dbDelta($sqlQuery);

	//wp_bwlmslevel_memberships_categories
		$sqlQuery = "
			CREATE TABLE " . $wpdb->bwlmslevel_memberships_categories . " (
			  membership_id int(11) unsigned NOT NULL,
			  category_id int(11) unsigned NOT NULL,
			  modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  UNIQUE KEY membership_category (membership_id,category_id),
			  UNIQUE KEY category_membership (category_id,membership_id)
			);
		";
	dbDelta($sqlQuery);


	//wp_bwlmslevel_memberships_users
		$sqlQuery = "
			CREATE TABLE " . $wpdb->bwlmslevel_memberships_users . " (
			   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			   user_id int(11) unsigned NOT NULL,
			   membership_id int(11) unsigned NOT NULL,
			   code_id int(11) unsigned NOT NULL,
			   initial_payment decimal(10,2) NOT NULL,
			   billing_amount decimal(10,2) NOT NULL,
			   cycle_number int(11) NOT NULL,
			   cycle_period enum('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
			   billing_limit int(11) NOT NULL,
			   trial_amount decimal(10,2) NOT NULL,
			   trial_limit int(11) NOT NULL,
			   status varchar(20) NOT NULL DEFAULT 'active',
			   startdate datetime NOT NULL,
			   enddate datetime DEFAULT NULL,
			   modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			   PRIMARY KEY (id),
			   KEY membership_id (membership_id),
			   KEY modified (modified),
			   KEY code_id (code_id),
			   KEY enddate (enddate),
			   KEY user_id (user_id),
			   KEY status (status)
			);
		";
	dbDelta($sqlQuery);

	//bwlmslevel_membership_levelmeta
	$sqlQuery = "
		CREATE TABLE " . $wpdb->bwlmslevel_membership_levelmeta . " (
		  meta_id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  pmpro_membership_level_id int(10) unsigned NOT NULL,
		  meta_key varchar(255) NOT NULL,
		  meta_value longtext,
		  PRIMARY KEY (meta_id),
		  KEY (pmpro_membership_level_id),
		  KEY (meta_key)
		);
	";
	dbDelta($sqlQuery);

	//wp_bwlmslevel_memberships_pages
		$sqlQuery = "
			CREATE TABLE " . $wpdb->bwlmslevel_memberships_pages . " (
			  membership_id int(11) unsigned NOT NULL,
			  page_id int(11) unsigned NOT NULL,
			  modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  UNIQUE KEY category_membership (page_id,membership_id),
			  UNIQUE KEY membership_page (membership_id,page_id)
			);
		";
	dbDelta($sqlQuery);
}