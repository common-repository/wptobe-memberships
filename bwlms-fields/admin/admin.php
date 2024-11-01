<?php

add_action( 'bwlmsfields_admin_do_tab', 'bwlmsfields_admin_do_tab', 10, 2 );
add_action( 'wp_ajax_bwlmsfields_a_field_reorder', 'bwlmsfields_a_do_field_reorder' );
add_action( 'user_new_form', 'bwlmsfields_admin_add_new_user' );
add_filter( 'plugin_action_links', 'bwlmsfields_admin_plugin_links', 10, 2 );

function bwlmsfields_a_do_field_reorder()
{
	require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-fields.php" );
	bwlmsfields_a_field_reorder();
}

function bwlmsfields_admin_plugin_links( $links, $file )
{
	static $bwlmsfields_plugin;
	if( !$bwlmsfields_plugin ) $bwlmsfields_plugin = plugin_basename( 'bwlms-fields/bwlms-fields.php' );
	if( $file == $bwlmsfields_plugin ) {
		$settings_link = '<a href="admin.php?page=bwlmsfields-settings">' . __( 'Settings' ) . '</a>';
		$links = array_merge( array( $settings_link ), $links );
	}
	return $links;
}

function bwlmsfields_load_admin_js()
{
//	wp_enqueue_script( 'bwlmsfields-managefield-js',  BWLMSMEM_DIRURL.'js/bwlmsfields-managefield.js' ); 
//	wp_enqueue_style ( 'bwlmsfields-admin-css', BWLMSMEM_DIRURL.'css/bwlms-fields-addfield.css' );
}

function bwlmsfields_admin()
{
	$did_update = ( isset( $_POST['bwlmsfields_admin_a'] ) ) ? bwlmsfields_admin_action( sanitize_text_field($_POST['bwlmsfields_admin_a']) ) : false;

	$bwlmsfields_settings = get_option( 'bwlmsfields_settings' );

	?>
	
	<div class="wrap">
		<?php 
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-fields.php" );
		bwlmsfields_a_build_fields();
		?>
	</div>
	<?php
	
	return;
}


function bwlmsfields_admin_do_tab( $tab, $bwlmsfields_settings )
{
	switch ( $tab ) {
	
	case 'options' :
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-options.php" );
		bwlmsfields_a_build_options( $bwlmsfields_settings );
		break;
	case 'fields' :
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-fields.php" );
		bwlmsfields_a_build_fields();
		break;
	case 'dialogs' :
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-dialogs.php" );
		bwlmsfields_a_build_dialogs();
		break;
	case 'emails' :
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-emails.php" );
		bwlmsfields_a_build_emails( $bwlmsfields_settings );
		break;
	}
}



function bwlmsfields_admin_tabs( $current = 'options' ) 
{
    $tabs = array( 
		'options' => 'BWLMS-Fields ' . __( 'Options', 'wptobemem' ), 
		'fields'  => __( 'Fields', 'wptobemem' ), 
		'dialogs' => __( 'Dialogs', 'wptobemem' ), 
		'emails'  => __( 'Emails', 'wptobemem' ) 
	);
	
	$tabs = apply_filters( 'bwlmsfields_admin_tabs', $tabs );
	
    $links = array();
    foreach( $tabs as $tab => $name ) {
	
		$class = ( $tab == $current ) ? 'nav-tab nav-tab-active' : 'nav-tab';
		$links[] = '<a class="' . $class . '" href="?page=bwlmsfields-settings&amp;tab=' . $tab . '">' . $name . '</a>';
    
	}
    
	echo '<h2 class="nav-tab-wrapper">';
    foreach( $links as $link )
        echo $link;
    echo '</h2>';
}

function bwlmsfields_admin_action( $action )
{
	$did_update = ''; 
	switch( $action ) {

	case( 'update_settings' ):
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-options.php" );
		$did_update = bwlmsfields_update_options();			
		break;

	case( 'update_fields' ):
	case( 'add_field' ): 
	case( 'edit_field' ):
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-fields.php" );
		//$did_update = bwlmsfields_update_fields( $action );
		$did_update = bwlmsfields_add_edit_update_fields( $action );
		break;
	
	case( 'update_dialogs' ):
	require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-dialogs.php" );
		$did_update = bwlmsfields_update_dialogs();
		break;
	
	case( 'update_emails' ):
		require_once(BWLMSMEM_DIR . "/bwlms-fields/admin/tab-emails.php" );
		$did_update = bwlmsfields_update_emails();
		break;
	
	}
	
	return $did_update;
}


function bwlmsfields_admin_add_new_user()
{
	require_once(BWLMSMEM_DIR . "/bwlms-fields/wpnative-register.php" );
	echo bwlmsfields_do_wp_newuser_form();
	return;
}