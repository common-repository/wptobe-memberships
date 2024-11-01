<?php
add_action( 'show_user_profile', 'bwlmsfields_admin_fields' );
add_action( 'edit_user_profile', 'bwlmsfields_admin_fields' );
add_action( 'profile_update',    'bwlmsfields_admin_update' );

function bwlmsfields_admin_fields()
{
	global $current_screen, $user_ID;
	$user_id = ( $current_screen->id == 'profile' ) ? $user_ID : intval($_REQUEST['user_id']); ?>

	<h3><?php // 일반유저 로그인후 워드프레스 어드민 User Profile 메뉴에 추가되는 부분
		echo apply_filters( 'bwlmsfields_admin_profile_heading', __( 'WPTOBE Memberships Fields', 'wptobemem' ) ); ?>
	</h3>
	
 	<table class="form-table">
		<?php
		$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
		$exclude = bwlmsfields_get_excluded_meta( 'admin-profile' );
		
		do_action( 'bwlmsfields_admin_before_profile', $user_id, $bwlmsfields_fields );
		
		foreach( $bwlmsfields_fields as $meta ) {
		
			$valtochk = ''; 

			/** determine which fields to show in the additional fields area */	
			$show = ( $meta[6] == 'n' && ! in_array( $meta[2], $exclude ) ) ? true : false;

			
			if( $show ) {
				$req = ( $meta[5] == 'y' ) ? ' <span class="description">' . __( '(required)','wptobemem' ) . '</span>' : '';
	
				$show_field = '
					<tr>
						<th><label>' .  $meta[1]  . $req . '</label></th>
						<td>';
				$val = htmlspecialchars( get_user_meta( $user_id, $meta[2], 'true' ) );
				if( $meta[3] == 'checkbox'  ) {
					$valtochk = $val; 
					$val = $meta[7];
				}
				if( $meta[3] == 'dropdown'  ) {
					$valtochk = $val; 
					
					$meta[10] = isset($meta[10]) ? $meta[10]  : null;
					$val = $meta[10];
				}
				$show_field.=  bwlmsfields_create_formfield( $meta[2], $meta[3], $val, $valtochk,'',false,'','',$user_id ) . '

						</td>
					</tr>';
				
				echo apply_filters( 'bwlmsfields_admin_profile_field', $show_field );
			}
		}

		if( BWLMSFIELDS_MOD_REG == 1 ) { 
			$user_active_flag = get_user_meta( $user_id, 'active', 'true' );
			switch( $user_active_flag ) {
			
				case '':
					$label  = __( 'Activate this user?', 'wptobemem' );
					$action = 1;
					break;
				
				case 0: 
					$label  = __( 'Reactivate this user?', 'wptobemem' );
					$action = 1;
					break;
				
				case 1:
					$label  = __( 'Deactivate this user?', 'wptobemem' );
					$action = 0;
					break;
				
			}?>

			<tr>
				<th><label><?php echo $label; ?></label></th>
				<td><input id="activate_user" type="checkbox" class="input" name="activate_user" value="<?php echo $action; ?>" /></td>
			</tr>

		<?php }  

		if( BWLMSFIELDS_USE_EXP == 1 ) {
			if( ( BWLMSFIELDS_MOD_REG == 1 &&  get_user_meta( $user_id, 'active', 'true' ) == 1 ) || ( BWLMSFIELDS_MOD_REG != 1 ) ) { 
				bwlmsfields_a_extenduser( $user_id );
			} 
		} ?>
		
		<?php 
		do_action( 'bwlmsfields_admin_after_profile', $user_id, $bwlmsfields_fields ); 

		wp_enqueue_script( 'bwlmsfields-form-enctype');

		?>

	</table><?php
}

function bwlmsfields_admin_update()
{
	$user_id = intval($_REQUEST['user_id']);
	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );

	do_action( 'bwlmsfields_admin_pre_user_update', $user_id, $bwlmsfields_fields );
	
	$fields = array();
	$chk_pass = false;
	foreach( $bwlmsfields_fields as $meta ) {
		if( $meta[6] == "n" && $meta[3] != 'password' && $meta[3] != 'checkbox' ) {
			( isset( $_POST[$meta[2]] ) ) ? $fields[$meta[2]] = sanitize_text_field($_POST[$meta[2]]) : false;
		} elseif( $meta[2] == 'password' && $meta[4] == 'y' ) {
			$chk_pass = true;
		} elseif( $meta[3] == 'checkbox' ) {
			$fields[$meta[2]] = ( isset( $_POST[$meta[2]] ) ) ? sanitize_text_field($_POST[$meta[2]]) : '';
		}
	}
	
	$fields = apply_filters( 'bwlmsfields_admin_profile_update', $fields, $user_id ); 

	
	$exclude = bwlmsfields_get_excluded_meta( 'admin-profile' );
	foreach( $fields as $key => $val ) {
		if( ! in_array( $key, $exclude ) ) {
			update_user_meta( $user_id, $key, $val );
		}
	}
	$time_str = time();
	$allowedTypes = array('image/gif', 'image/jpeg', 'image/png');
	foreach ( $_FILES as $key => $val ) {

		if($_FILES[$key]["size"] > 0 )
		{
				$extension = end(explode(".", $_FILES[$key]["name"]));
				$file_name = explode(".", $_FILES[$key]["name"]);
				$upload_dir = wp_upload_dir();

				if ( ($_FILES[$key]["type"] == "image/gif") || ($_FILES[$key]["type"] == "image/jpeg") || ($_FILES[$key]["type"] == "image/png") || ($_FILES[$key]["type"] == "image/pjpeg") )
				{
					if ($_FILES[$key]["error"] > 0)
					{
						//echo "Return Code: " . $_FILES[$key]["error"];
					}
					else
					{
						if (file_exists($upload_dir['basedir']."/" . $key.$time_str.".".$extension ))
						{
							//echo $_FILES[$key][$key] . " already exists. ";
						}
						else
						{
							move_uploaded_file($_FILES[$key]["tmp_name"], $upload_dir['basedir'] ."/" . $key.$time_str.".".$extension );
							update_user_meta( $user_id , $key, $key.$time_str.".".$extension );
						}
						
					}
				}
				else
				{
					if ($_FILES[$key]["error"] > 0)
					{
						//echo "Return Code: " . $_FILES[$key]["error"];
					}
					else
					{
						if (file_exists($upload_dir['basedir']."/" . $key.$time_str.".".$extension ))
						{
							//echo $_FILES[$key][$key] . " already exists. ";
						}
						else
						{
							move_uploaded_file($_FILES[$key]["tmp_name"], $upload_dir['basedir'] ."/" . $key.$time_str.".".$extension );
							update_user_meta( $user_id , $key, $key.$time_str.".".$extension );
						}
						
					}
				}
		}
	}

	if( BWLMSFIELDS_MOD_REG == 1 ) {

		$bwlmsfields_activate_user = ( isset( $_POST['activate_user'] ) == '' ) ? -1 : sanitize_text_field($_POST['activate_user']);
		
		if( $bwlmsfields_activate_user == 1 ) {
			bwlmsfields_a_activate_user( $user_id, $chk_pass );
		} elseif( $bwlmsfields_activate_user == 0 ) {
			bwlmsfields_a_deactivate_user( $user_id );
		}
	}

	( BWLMSFIELDS_USE_EXP == 1 ) ? bwlmsfields_a_extend_user( $user_id ) : '';
	
	do_action( 'bwlmsfields_admin_after_user_update', $user_id );
	
	return;
}