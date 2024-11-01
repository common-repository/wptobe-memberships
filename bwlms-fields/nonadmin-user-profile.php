<?php
if ( ! function_exists( 'bwlmsfields_user_profile' ) ):
/**
 * 워드프레스 관리자가 아닌 일반유저: 워드프레스 Profile 메뉴에 추가되는 부분
 */
function bwlmsfields_user_profile()
{
	global $user_id; 
	 ?>
    <h3><?php echo apply_filters( 'bwlmsfields_user_profile_heading', __( 'WPTOBE-Memberships Fields', 'wptobemem' ) ); ?></h3>  
 	<table class="form-table">
		<?php
		$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
		$exclude = bwlmsfields_get_excluded_meta( 'user-profile' );
		
		foreach( $bwlmsfields_fields as $meta ) {
		
			$val = get_user_meta( $user_id, $meta[2], 'true' );
			$valtochk = '';
			
			$chk_tos = true;
			if( $meta[2] == 'tos' && $val == 'agree' ) { 
				$chk_tos = false; 
				echo bwlmsfields_create_formfield( $meta[2], 'hidden', $val );
			}
			
			$chk_pass = ( in_array( $meta[2], $exclude ) ) ? false : true;
		
			if( $meta[4] == "y" && $meta[6] == "n" && $chk_tos && $chk_pass ) { 

				$req = ( $meta[5] == 'y' ) ? ' <span class="description">' . __( '(required)' ) . '</span>' : '';
				$show_field = ' 
					<tr>
						<th><label>' . __( $meta[1], 'wptobemem' ) . $req . '</label></th>
						<td>';
					
					$val = get_user_meta( $user_id, $meta[2], 'true' );
					if( $meta[3] == 'checkbox' || $meta[3] == 'select' ) {
						$valtochk = $val; 
						$val = $meta[7];
					}
				$show_field.= bwlmsfields_create_formfield( $meta[2], $meta[3], $val, $valtochk ) . '
						</td>
					</tr>';

				echo apply_filters( 'bwlmsfields_user_profile_field', $show_field );
			} 
		} ?>
	</table><?php
}
endif;


function bwlmsfields_profile_update()
{
	global $user_id;
	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
	$exclude = bwlmsfields_get_excluded_meta( 'user-profile' );
	foreach( $bwlmsfields_fields as $meta ) {
		if( ! in_array( $meta[2], $exclude ) ) {
			if( $meta[4] == "y" && $meta[6] == "n" && $meta[3] != 'password' ) {
		
				$chk = '';
				if( $meta[5] == "n" || ( ! $meta[5] ) ) { $chk = 'ok'; }
				if( $meta[5] == "y" && $_POST[$meta[2]] != '' ) { $chk = 'ok'; }
				
				$field_val = ( isset( $_POST[$meta[2]] ) ) ? sanitize_text_field($_POST[$meta[2]]) : '';
				
				if( $chk == 'ok' ) { 
					update_user_meta( $user_id, $meta[2], $field_val ); 
				} 
			}
		}
	} 
}