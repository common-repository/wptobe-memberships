<?php

function wptobemem_main_settings_menu()
{
	if (!current_user_can('manage_options'))    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

	// WPTOBE Memberships admin main menu ......
	$page = add_menu_page(
						__('Memberships', 'wptobemem'),			//	$page_title, 
						__('Memberships', 'wptobemem'),			//  $menu_title, 
						'manage_options',						//	$capability, 
						'wptobemem-menu',
						'wptobemem_adminmenu',					//	$function, 
						'dashicons-id',							//	$icon_url,
						39.1									//	$position 
	);

}
add_action('admin_menu', 'wptobemem_main_settings_menu');

function wptobemem_adminmenu() {}