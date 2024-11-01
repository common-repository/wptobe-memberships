<?php

function bwlmslevel_getCaps()
{
	$bwlmslevel_caps = array(
		'bwlmslevel_membershiplevels',
		'bwlmslevel_memberslist',
	);
	
	return $bwlmslevel_caps;
}

function bwlmslevel_add_pages()
{
	global $wpdb;   

	$bwlmslevel_caps = bwlmslevel_getCaps();
	
	foreach($bwlmslevel_caps as $cap)
	{
		if(current_user_can($cap))
		{
			$top_menu_cap = $cap;
			break;
		}
	}
	
	if(empty($top_menu_cap))
		return;
	
	add_submenu_page(
						'wptobemem-menu',
						__('Memberships', 'wptobemem'),
						__('Memberships', 'wptobemem'), //Menu Title
						'bwlmslevel_membershiplevels', 
						'wptobemem-menu',
						'bwlmslevel_membershiplevels'
	);
	add_submenu_page(
						'wptobemem-menu',
						__('Members List', 'wptobemem'),
						__('Members List', 'wptobemem'), 
						'bwlmslevel_memberslist', 
						'bwlmslevel-memberslist', 
						'bwlmslevel_memberslist'
	);
}
add_action('admin_menu', 'bwlmslevel_add_pages');

function bwlmslevel_memberslist()
{
	require_once (BWLMSMEM_DIR . "/bwlms-level/admin/memberslist.php");
}

function bwlmslevel_membershiplevels()
{
	?>
	<div class="wrap">
		<h3> Wptobe-memberships </h3>
	</div>

	<div class="row vertical-tabs-container">

	  <div class="vertical-tabs small-12 medium-3 large-2 columns">
		<a href="javascript:void(0)" class="js-vertical-tab vertical-tab is-active" rel="tab1">User Fields</a>
		<a href="javascript:void(0)" class="js-vertical-tab vertical-tab" rel="tab2">Membership Levels</a>
		<a href="javascript:void(0)" class="js-vertical-tab vertical-tab" rel="tab3">Points</a>
		<a href="javascript:void(0)" class="js-vertical-tab vertical-tab" rel="tab4">Message</a>
		<a class="wptobemem-tabs-bottom-dummy vertical-tab" rel="tab5">&nbsp</a>
	  </div>

	  <div class="vertical-tab-content-container small-12 medium-9 large-10 columns">
		<a href="" class="js-vertical-tab-accordion-heading vertical-tab-accordion-heading is-active" rel="tab1">User Fields</a>
		<div id="tab1" class="js-vertical-tab-content vertical-tab-content">
			<?php bwlmsfields_admin(); ?>
		</div>

		<a href="" class="js-vertical-tab-accordion-heading vertical-tab-accordion-heading" rel="tab2">Membership Levels</a>
		<div id="tab2" class="js-vertical-tab-content vertical-tab-content">
			<?php require_once (BWLMSMEM_DIR . "/bwlms-level/admin/membershiplevels.php"); ?>
		</div>

		<a href="" class="js-vertical-tab-accordion-heading vertical-tab-accordion-heading" rel="tab3">Points</a>
		<div id="tab3" class="js-vertical-tab-content vertical-tab-content">
			<?php require_once (BWLMSMEM_DIR . "/bwlms-credit/bwlmscredit-menu.php"); ?>
		</div>

		<a href="" class="js-vertical-tab-accordion-heading vertical-tab-accordion-heading" rel="tab4">Message</a>
		<div id="tab4" class="js-vertical-tab-content vertical-tab-content">
			<?php require_once (BWLMSMEM_DIR . "/bwlms-message/bwlms-message-admin-settings.php"); ?>
		</div>
	  </div>
	</div>

	<?php
}