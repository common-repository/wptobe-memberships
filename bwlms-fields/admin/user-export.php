<?php

function bwlmsfields_export_users( $args, $users = null )
{
	$today = date( "m-d-y" ); 

	$defaults = array(
		'export'        => 'all',
		'filename'      => 'bwlms-fields-user-export-' . $today . '.csv',
		'export_fields' => array()
	);

	extract( wp_parse_args( apply_filters( 'bwlmsfields_export_args', $args ), $defaults ) );

	ob_start();
	
	$users = ( $export == 'all' ) ? get_users( array( 'fields' => 'ID' ) ) : $users;

	header( "Content-Description: File Transfer" );
	header( "Content-type: application/octet-stream" );
	header( "Content-Disposition: attachment; filename=" . $filename );
	header( "Content-Type: text/csv; charset=" . get_option( 'blog_charset' ), true );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM

	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
	$hrow = "User ID,Username,";

	foreach( $bwlmsfields_fields as $meta ) {
		$hrow.= $meta[1] . ",";
	}

	$hrow.= ( BWLMSFIELDS_MOD_REG == 1 ) ? __( 'Activated?', 'wptobemem' ) . "," : '';
	$hrow.= ( BWLMSFIELDS_USE_EXP == 1 ) ? __( 'Subscription', 'wptobemem' ) . "," . __( 'Expires', 'wptobemem' ) . "," : '';

	$hrow.= __( 'Registered', 'wptobemem' ) . ",";
	$hrow.= __( 'IP', 'wptobemem' );
	$data = $hrow . "\r\n";

	reset( $bwlmsfields_fields );

	foreach( $users as $user ) {

		$user_info = get_userdata( $user );

		$data.= '"' . $user_info->ID . '","' . $user_info->user_login . '",';
		
		$wp_user_fields = array( 'user_email', 'user_nicename', 'user_url', 'display_name' );
		foreach( $bwlmsfields_fields as $meta ) {
			if( in_array( $meta[2], $wp_user_fields ) ){
				$data.= '"' . $user_info->$meta[2] . '",';	
			} else {
				$data.= '"' . get_user_meta( $user, $meta[2], true ) . '",';
			}
		}
		
		$data.= ( BWLMSFIELDS_MOD_REG == 1 ) ? '"' . ( get_user_meta( $user, 'active', 1 ) ) ? __( 'Yes' ) : __( 'No' ) . '",' : '';
		$data.= ( BWLMSFIELDS_USE_EXP == 1 ) ? '"' . get_user_meta( $user, "exp_type", true ) . '",' : '';
		$data.= ( BWLMSFIELDS_USE_EXP == 1 ) ? '"' . get_user_meta( $user, "expires", true  ) . '",' : '';
		
		$data.= '"' . $user_info->user_registered . '",';
		$data.= '"' . get_user_meta( $user, "bwlmsfields_reg_ip", true ). '"';
		$data.= "\r\n";
		
		if( $export != 'all' ){
			update_user_meta( $user, 'exported', 1 );
		}
	}

	echo $data; 

	ob_flush();

	exit();
}