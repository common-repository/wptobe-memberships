<?php

function bwlmslevel_wp_ajax_memberlist_csv()
{
	require_once(dirname(__FILE__) . "/memberslist-csv.php");	
	exit;	
}
add_action('wp_ajax_memberslist_csv', 'bwlmslevel_wp_ajax_memberlist_csv');
