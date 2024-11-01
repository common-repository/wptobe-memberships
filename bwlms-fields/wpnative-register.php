<?php
/**
 * Wptobe-Memberships 추가 필드를 워드프레스 유저 등록 화면에 보여주는 폼
 */

function bwlmsfields_do_wp_register_form()
{
	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );

	foreach ( $bwlmsfields_fields as $field ) {
	//for( $row = 0; $row < count( $bwlmsfields_fields ); $row++ ) {
	
		$req = ( $field[5] == 'y' ) ? ' <span class="req">' . __( '(required)' ) . '</span>' : '';
		
		if( $field[4] == 'y' && $field[2] != 'user_email' ) {
		
			if( $field[3] == 'checkbox' ) {
			
				$label = $field[2];

				$val = ( isset( $_POST[ $field[2] ] ) ) ? sanitize_text_field($_POST[ $field[2] ]) : '';
				$val = ( ! $_POST && $field[8] == 'y' ) ? $field[7] : $val;
			
				$row_before = '<p class="bwlmsfields-checkbox">';
				$label = '<label for="' . $field[2] . '">' . $label . $req;
				$input = bwlmsfields_create_formfield( $field[2], $field[3], $field[7], $val );
				$row_after = '</label></p>';
				
			} else {
			
				$row_before = '<p>';
				$label = '<label for="' . $field[2] . '">' . $field[1] . $req . '<br />';
				
				switch( $field[3] ) {
				
				case( 'select' ):
					$val = ( isset( $_POST[ $field[2] ] ) ) ? sanitize_text_field($_POST[ $field[2] ]) : '';
					$input = bwlmsfields_create_formfield( $field[2], $field[3], $field[7], $val );
					break;
					
				case( 'textarea' ):
					$input = '<textarea name="' . $field[2] . '" id="' . $field[2] . '" class="textarea">'; 
					$input.= ( isset( $_POST[ $field[2] ] ) ) ? esc_textarea( $_POST[ $field[2] ] ) : ''; 
					$input.= '</textarea>';		
					break;

				default:
					$input = '<input type="' . $field[3] . '" name="' . $field[2] . '" id="' . $field[2] . '" class="input" value="'; 
					$input.= ( $_POST ) ? esc_attr( $_POST[ $field[2] ] ) : ''; 
					$input.= '" size="25" />';
					break;
				}
				
				$row_after = '</label></p>';
			
			}
			
			$rows[$field[2]] = array(
				'type'         => $field[3],
				'row_before'   => $row_before,
				'label'        => $label,
				'field'        => $input,
				'row_after'    => $row_after
			);
		}
	}
	
	$rows = apply_filters( 'bwlmsfields_native_form_rows', $rows );
	
	foreach( $rows as $row_item ) {
		if( $row_item['type'] == 'checkbox' ) {
			echo $row_item['row_before'] . $row_item['field'] . $row_item['label'] . $row_item['row_after'];
		} else { 
			echo $row_item['row_before'] . $row_item['label'] . $row_item['field'] . $row_item['row_after'];
		}
	}
	
}

function bwlmsfields_do_wp_newuser_form()
{

	echo '<table class="form-table"><tbody>';
	
	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' );
	$exclude = bwlmsfields_get_excluded_meta( 'register' );

	foreach( $bwlmsfields_fields as $field ) {

		if( $field[4] == 'y' && $field[6] == 'n' && ! in_array( $field[2], $exclude ) ) {

			$req = ( $field[5] == 'y' ) ? ' <span class="description">' . __( '(required)' ) . '</span>' : '';
		
			echo '<tr>
				<th scope="row">
					<label for="' . $field[2] . '">' . $field[1] . $req . '</label>
				</th>
				<td>';
		
			switch( $field[3] ) {
			
			case( 'dropdown' ):
				$val = ( isset( $_POST[ $field[2] ] ) ) ? sanitize_text_field($_POST[ $field[2] ]) : '';
				echo bwlmsfields_create_formfield( $field[2], $field[3], $field[10], $val );
				break;

			case( 'select' ):
				$val = ( isset( $_POST[ $field[2] ] ) ) ? sanitize_text_field($_POST[ $field[2] ]) : '';
				echo bwlmsfields_create_formfield( $field[2], $field[3], $field[7], $val );
				break;
				
			case( 'textarea' ):
				echo '<textarea name="' . $field[2] . '" id="' . $field[2] . '" class="textarea">'; 
				echo ( isset( $_POST[ $field[2] ] ) ) ? esc_textarea( $_POST[ $field[2] ] ) : ''; 
				echo '</textarea>';		
				break;
				
			case( 'checkbox' ):
				echo bwlmsfields_create_formfield( $field[2], $field[3], $field[7], '' );
				break;

			default:
				echo '<input type="' . $field[3] . '" name="' . $field[2] . '" id="' . $field[2] . '" class="input" value="'; echo ( $_POST ) ? esc_attr( $_POST[ $field[2] ] ) : ''; echo '" size="25" />';
				break;
			}
				
			echo '</td>
				</tr>';

		}
	}
	echo '</tbody></table>';
	wp_enqueue_script( 'bwlmsfields-form-enctype');
	?>

	<?php
}