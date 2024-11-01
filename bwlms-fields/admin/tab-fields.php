<?php

function bwlmsfields_a_build_fields() 
{ 
	global $add_field_err_msg;
//	$add_toggle = ( isset( $_GET['edit'] ) ) ? $_GET['edit'] : false;

	$bwlmsfields_fields = get_option( 'bwlmsfields_fieldsopt' ); 
	?>
	<div class="metabox-holder">

		<div id="post-body">
			<div id="post-body-content">
			<?php 
				if( ! $add_field_err_msg ) { 
					// User Fields output
					bwlmsfields_configure_custom_fields( $bwlmsfields_fields ); 
				}
			?>
			</div>
		</div>
	</div>
	<?php
}

function bwlmsfields_a_field_reorder()
{
	$new_order = $bwlmsfields_old_fields = $bwlmsfields_new_fields = $key = $row = '';

	$new_order = sanitize_text_field($_REQUEST['orderstring']);

	$new_order = explode( "&", $new_order );	
	
	$bwlmsfields_old_fields = get_option( 'bwlmsfields_fieldsopt' );
	

	for( $row = 0; $row < count( $new_order ); $row++ )  {
		if( $row > 0 ) {
			$key = $new_order[$row];
			$key = substr( $key, 15 ); //echo $key.", ";
			
			for( $x = 0; $x < count( $bwlmsfields_old_fields ); $x++ )  {
				
				if( $bwlmsfields_old_fields[$x][0] == $key ) {
					$bwlmsfields_new_fields[$row - 1] = $bwlmsfields_old_fields[$x];
				}
			}
		}
	}

	update_option( 'bwlmsfields_fieldsopt', $bwlmsfields_new_fields ); 

	die(); 

}

function bwlmsfields_add_edit_update_fields( $action )
{
	$bwlmsfields_fields    = get_option( 'bwlmsfields_fieldsopt' );
	$bwlmsfields_ut_fields = get_option( 'bwlmsfields_utfields' );


	if( $action == 'update_fields' ) {
		check_admin_referer( 'bwlmsfields-update-fields' );

		$arr = ( isset( $_POST['ut_fields'] ) ) ? sanitize_text_field($_POST['ut_fields']) : '';
		update_option( 'bwlmsfields_utfields', $arr );
	
		$nrow = 0;
		for( $row = 0; $row < count( $bwlmsfields_fields ); $row++ ) {

		//1. delete_field | del_
			$delete_field = "del_" . $bwlmsfields_fields[$row][2];
			$delete_field = ( isset( $_POST[$delete_field] ) ) ? sanitize_text_field($_POST[$delete_field]) : false; 

			if( $delete_field != "delete" ) {

				for( $i = 0; $i < 11; $i++ ) {
					$bwlmsfields_newfields[$nrow][$i] = $bwlmsfields_fields[$row][$i];
				}
				
				$bwlmsfields_newfields[$nrow][0] = $nrow + 1;
					//2. name_field | _label
					$name_field = $bwlmsfields_fields[$row][2] . "_label"; 
					//3. type_field | _type
					$type_field = $bwlmsfields_fields[$row][2] . "_type"; 
					//4. display_field | _display
					$display_field = $bwlmsfields_fields[$row][2] . "_display"; 
					//5. require_field : _required
					$require_field = $bwlmsfields_fields[$row][2] . "_required";
					//6. checked_field : _checked
					$checked_field = $bwlmsfields_fields[$row][2] . "_checked";
					//7. profile_field : _profile (Native, Edit)
					$profile_field = $bwlmsfields_fields[$row][2] . "_profile";
					//8. content_field : _content (Users Screen)
					$content_field = $bwlmsfields_fields[$row][2] . "_content";

					$showsignup_field = $bwlmsfields_fields[$row][2] . "_showsignup";

					$bwlmsfields_newfields[$nrow][1] = ( isset( $_REQUEST[$name_field] ) ) ? sanitize_text_field($_REQUEST[$name_field]) : '';
					$bwlmsfields_newfields[$nrow][3] = ( isset( $_REQUEST[$type_field] ) ) ? sanitize_text_field($_REQUEST[$type_field]) : '';
					$bwlmsfields_newfields[$nrow][4] = ( isset( $_REQUEST[$display_field] ) ) ? sanitize_text_field($_REQUEST[$display_field]) : '';
					$bwlmsfields_newfields[$nrow][5] = ( isset( $_REQUEST[$require_field] ) ) ? sanitize_text_field($_REQUEST[$require_field]) : '';
					$bwlmsfields_newfields[$nrow][7] = ( isset( $_REQUEST[$profile_field] ) ) ? sanitize_text_field($_REQUEST[$profile_field]) : '';
					$bwlmsfields_newfields[$nrow][9] = ( isset( $_REQUEST[$showsignup_field] ) ) ? sanitize_text_field($_REQUEST[$showsignup_field]) : '';

					if($bwlmsfields_newfields[$nrow][3]=="dropdown" && !isset( $_REQUEST[$content_field] ))
					{
						 if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
							$_REQUEST[$content_field]="	Select |,
											\"Select T|select_t\",
											\"Select O|select_o\",
											\"Select B|select_b\",
											  Select E|select_e
										";
						}
						else {
							$_REQUEST[$content_field]=" Select |,
											Select T|select_t,
											Select O|select_o,
											Select B|select_b,
											Select E|select_e
										";
						}
					}

					if ( $bwlmsfields_fields[$row][2] != 'user_email' ){

						if(isset( $_REQUEST[$content_field] ) ){// Content field

							$str =  sanitize_text_field($_REQUEST[$content_field]);
							
							$str = stripslashes($str);
							$str = trim( str_replace( array("\r", "\r\n", "\n"), '', $str) );

							if($bwlmsfields_newfields[$nrow][3]=="dropdown"){

								if ( ! function_exists( 'str_getcsv' ) ) {
									$bwlmsfields_newfields[$nrow][7] = explode( ',', $str );
									$bwlmsfields_newfields[$nrow][10] = explode( ',', $str );
								} else {
									$bwlmsfields_newfields[$nrow][7] = str_getcsv( $str, ',', '"' );
									$bwlmsfields_newfields[$nrow][10] = str_getcsv( $str, ',', '"' );
								}
							}
							else{
								$bwlmsfields_newfields[$nrow][7] = $str;
								$bwlmsfields_newfields[$nrow][10] = $str;
							}
						}
					} 
					else { //In case of e-mail 
						$bwlmsfields_newfields[$nrow][4] = 'y';// Display(Profile)
						$bwlmsfields_newfields[$nrow][5] = 'y';// Required	
					}

					// display=N & Mandatory=Y error
					$chkreq = ( $bwlmsfields_newfields[$nrow][4] != 'y' && $bwlmsfields_newfields[$nrow][5] == 'y' ) ? 'err' : false;
					// Checked ?
					$bwlmsfields_newfields[$nrow][6] = $bwlmsfields_fields[$row][6];
					// Edit (Native/Edit)
					$bwlmsfields_newfields[$nrow][7] = ( isset( $bwlmsfields_fields[$row][7] ) ) ? $bwlmsfields_fields[$row][7] : '';

					// field type이 check박스 이면
					if ( $bwlmsfields_fields[$row][3] == 'checkbox' ) { 
						if ( isset( $_REQUEST[$checked_field] ) && $_REQUEST[$checked_field] == 'y' ) {
							$bwlmsfields_newfields[$nrow][8] = 'y';
						} else {
							$bwlmsfields_newfields[$nrow][8] = 'n';
						}
					}

					$nrow = $nrow + 1;
					
				}

			}

		update_option( 'bwlmsfields_fieldsopt', $bwlmsfields_newfields );
		$did_update = __( 'BWLMS-Fields fields were updated', 'wptobemem' );


	} elseif( $action == 'add_field' || 'edit_field' ) {

		check_admin_referer( 'bwlmsfields-add-fields' );
	
		global $add_field_err_msg;
	
		$add_field_err_msg = ( ! $_POST['add_name'] )   ? __( 'Field Label is required for adding a new field. Nothing was updated.', 'wptobemem' ) : false;
		$add_field_err_msg = ( ! $_POST['add_option'] ) ? __( 'Option Name is required for adding a new field. Nothing was updated.', 'wptobemem' ) : false;
		
		$chk_fields = array();
		foreach ( $bwlmsfields_fields as $field ) {
			$chk_fields[] = $field[2];
		}
		$add_field_err_msg = ( in_array( sanitize_text_field($_POST['add_option']), $chk_fields ) ) ? __( 'A field with that option name already exists', 'wptobemem' ) : false;
	
		$us_option = sanitize_text_field($_POST['add_option']);
		$us_option = preg_replace( "/ /", '_', $us_option );
		
		$arr = array();
		
		$arr[0] = ( $action == 'add_field' ) ? ( count( $bwlmsfields_fields ) ) + 2 : false;
		$arr[1] = sanitize_text_field(stripslashes( $_POST['add_name'] ));
		$arr[2] = $us_option;
		$arr[3] = sanitize_text_field($_POST['add_type']);
		$arr[4] = ( isset( $_POST['add_display'] ) )  ? sanitize_text_field($_POST['add_display'])  : 'n';
		$arr[5] = ( isset( $_POST['add_required'] ) ) ? sanitize_text_field($_POST['add_required']) : 'n';
		$arr[6] = ( $us_option == 'user_nicename' || $us_option == 'display_name' || $us_option == 'nickname' ) ? 'y' : 'n';
		
		if( $_POST['add_type'] == 'checkbox' ) { 
			$add_field_err_msg = ( ! $_POST['add_checked_value'] ) ? __( 'Checked value is required for checkboxes. Nothing was updated.', 'wptobemem' ) : false;
			$arr[7] = ( isset( $_POST['add_checked_value'] ) )   ? sanitize_text_field($_POST['add_checked_value'])   : false;
			$arr[8] = ( isset( $_POST['add_checked_default'] ) ) ? sanitize_text_field($_POST['add_checked_default']) : 'n';
		}
		
		if( $_POST['add_type'] == 'select' ) {
			$str = sanitize_text_field(stripslashes( $_POST['add_dropdown_value'] ));
			$str = trim( str_replace( array("\r", "\r\n", "\n"), '', $str ) );
			if( ! function_exists( 'str_getcsv' ) ) {
				$arr[7] = explode( ',', $str );
			} else {
				$arr[7] = str_getcsv( $str, ',', '"' );
			}
		}
		// 추가field: Signup page display check
		$arr[9] = ( isset( $_POST['add_showsignup'] ) )  ? sanitize_text_field($_POST['add_showsignup'])  : 'n';


		if( $action == 'add_field' ) {
			if( ! $add_field_err_msg ) {
				array_push( $bwlmsfields_fields, $arr );
				update_option( 'bwlmsfields_fieldsopt', $bwlmsfields_fields );
				$did_update = sanitize_text_field($_POST['add_name']) . ' ' . __( 'field was added', 'wptobemem' );
			} else {
				$did_update = $add_field_err_msg;
			}
		} 
		else {
		
			for( $row = 0; $row < count( $bwlmsfields_fields ); $row++ ) {
				if( $bwlmsfields_fields[$row][2] == $_GET['edit'] ) {
					$arr[0] = $bwlmsfields_fields[$row][0];
					$x = ( $arr[3] == 'checkbox' ) ? 8 : ( ( $arr[3] == 'select' ) ? 7 : 6 );
					for( $r = 0; $r < $x+1; $r++ ) {
						$bwlmsfields_fields[$row][$r] = $arr[$r];
					}
				}
			}

			update_option( 'bwlmsfields_fieldsopt', $bwlmsfields_fields );
			
			$did_update = sanitize_text_field($_POST['add_name']) . ' ' . __( 'field was updated', 'wptobemem' );
			
		} 
	}
	
	return $did_update;
}




function bwlmsfields_fields_edit_link( $field_id ) {
	return '<a href="' . get_admin_url() . 'admin.php?page=bwlmsfields-settings&amp;tab=fields&amp;edit=' . $field_id . '">' . __( 'Edit' ) . '</a>';
}


function bwlmsfields_a_field_edit( $mode, $bwlmsfields_fields = null, $field = null )
{	
	if( $mode == 'edit' ) {
	
		for( $row = 0; $row < count( $bwlmsfields_fields ); $row++ ) {
			if( $bwlmsfields_fields[$row][2] == $field ) {
				$field_arr = $bwlmsfields_fields[$row];
			}
		}	
	}
	
	$form_action = ( $mode == 'edit' ) ? 'editfieldform' : 'addfieldform';
	
?>

	<div class="postbox">
		<h3 class="title"><?php ( $mode == 'edit' ) ? _e( 'Edit Field', 'wptobemem' ) : _e( 'Add a Field', 'wptobemem' ); ?></h3>
		<div class="inside">
			<form name="<?php echo $form_action; ?>" id="<?php echo $form_action; ?>" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
				<?php wp_nonce_field( 'bwlmsfields-add-fields' ); ?>
				<ul>
					<li>
						<label><?php _e( 'Field Label', 'wptobemem' ); ?></label>
						<input type="text" name="add_name" value="<?php echo ( $mode == 'edit' ) ? $field_arr[1] : false; ?>" />
						<?php _e( 'The name of the field as it will be displayed to the user.', 'wptobemem' ); ?>
					</li>
					<li>
						<label><?php _e( 'Option Name', 'wptobemem' ); ?></label>
						<?php if( $mode == 'edit' ) { 
							echo $field_arr[2]; ?>
							<input type="hidden" name="add_option" value="<?php echo $field_arr[2]; ?>" /> 
						<?php } else { ?>	
							<input type="text" name="add_option" value="" />
							<?php _e( 'The database meta value for the field.', 'wptobemem' ); ?>
						<?php } ?>
					</li>
					<li>
						<label><?php _e( 'Type', 'wptobemem' ); ?></label>
						<?php if( $mode == 'edit' ) {
							echo $field_arr[3]; ?>
							<input type="hidden" name="add_type" value="<?php echo $field_arr[3]; ?>" /> 							
						<?php } else { ?>						
							<select name="add_type" id="bwlmsfields_field_type_select">
								<option value="text"><?php     _e( 'text',     'wptobemem' ); ?></option>
								<option value="textarea"><?php _e( 'textarea', 'wptobemem' ); ?></option>
								<option value="checkbox"><?php _e( 'checkbox', 'wptobemem' ); ?></option>
								<option value="select"><?php   _e( 'dropdown', 'wptobemem' ); ?></option>
								<option value="password"><?php _e( 'password', 'wptobemem' ); ?></option>
							</select>
						<?php } ?>
					</li>
					<li>
						<label><?php _e( 'Display[profile]?', 'wptobemem' ); ?></label>
						<input type="checkbox" name="add_display" value="y" <?php echo ( $mode == 'edit' ) ? bwlmsfields_selected( 'y', $field_arr[4] ) : false; ?> />
					</li>
					<li>
						<label><?php _e( 'Required?', 'wptobemem' ); ?></label>
						<input type="checkbox" name="add_required" value="y" <?php echo ( $mode == 'edit' ) ? bwlmsfields_selected( 'y', $field_arr[5] ) : false; ?> />
					</li>
					<li>
						<label><?php _e( 'Display[Signup]?', 'wptobemem' ); ?></label>
						<input type="checkbox" name="add_display" value="y" <?php echo ( $mode == 'edit' ) ? bwlmsfields_selected( 'y', $field_arr[9] ) : false; ?> />
					</li>


				<?php if( $mode == 'add' || ( $mode == 'edit' && $field_arr[3] == 'checkbox' ) ) { ?>
				<?php echo ( $mode == 'add' ) ? '<div id="bwlmsfields_checkbox_info">' : ''; ?>
					<li>
						<strong><?php _e( 'Additional information for checkbox fields', 'wptobemem' ); ?></strong>
					</li>
					<li>
						<label><?php _e( 'Checked by default?', 'wptobemem' ); ?></label>
						<input type="checkbox" name="add_checked_default" value="y" <?php echo ( $mode == 'edit' && $field_arr[3] == 'checkbox' ) ? bwlmsfields_selected( 'y', $field_arr[8] ) : false; ?> />
					</li>
					<li>
						<label><?php _e( 'Stored value if checked:', 'wptobemem' ); ?></label>
						<input type="text" name="add_checked_value" value="<?php echo ( $mode == 'edit' && $field_arr[3] == 'checkbox' ) ? $field_arr[7] : false; ?>" class="small-text" />
					</li>
				<?php echo ( $mode == 'add' ) ? '</div>' : ''; ?>
				<?php } ?>
				<?php if( $mode == 'add' || ( $mode == 'edit' && $field_arr[3] == 'select' ) ) { ?>
				<?php echo ( $mode == 'add' ) ? '<div id="bwlmsfields_dropdown_info">' : ''; ?>
					<li>
						<strong><?php _e( 'Additional information for dropdown fields', 'wptobemem' ); ?></strong>
					</li>
					<li>
						<label><?php _e( 'For dropdown, array of values:', 'wptobemem' ); ?></label>
							<textarea name="add_dropdown_value" rows="5" cols="40"><?php
								
								if( $mode == 'edit' ) {
								for( $row = 0; $row < count( $field_arr[7] ); $row++ ) {
								
								if( strstr( $field_arr[7][$row], ',' ) ) {
								echo '"' . $field_arr[7][$row]; echo ( $row == count( $field_arr[7] )- 1  ) ? '"' : "\",\n";
								} else {
								echo $field_arr[7][$row]; echo ( $row == count( $field_arr[7] )- 1  ) ? "" : ",\n";
								} }
														} else { 
								if (version_compare(PHP_VERSION, '5.3.0') >= 0) { ?>
											Select |,
											\"Select T|select_t\",
											\"Select O|select_o\",
											\"Select B|select_b\",
											  Select E|select_e
								<?php } else { ?>
											Select |,
											Select T|select_t,
											Select O|select_o,
											Select B|select_b,
											Select E|select_e
								<?php } } ?>


							</textarea>
					</li>
					<li>
						<label>&nbsp;</label>
						<span class="description"><?php _e( 'Options should be Option Name|option_value,', 'wptobemem' ); ?>
					</li>

				<?php echo ( $mode == 'add' ) ? '</div>' : ''; ?>
				<?php } ?>
				
				</ul><br />
				<?php if( $mode == 'edit' ) { ?><input type="hidden" name="field_arr" value="<?php echo $field_arr[2]; ?>" /><?php } ?>
				<input type="hidden" name="bwlmsfields_admin_a" value="<?php echo ( $mode == 'edit' ) ? 'edit_field' : 'add_field'; ?>" />
				<input type="submit" name="save"  class="button-primary" value="<?php echo ( $mode == 'edit' ) ? __( 'Edit Field', 'wptobemem' ) : __( 'Add Field', 'wptobemem' ); ?> &raquo;" /> 
			</form>
		</div>
	</div>

<?php

}

function bwlmsfields_field_label_edit( $mode, $bwlmsfields_fields = null, $field = null )
{	
	if( $mode != 'edit' ) return;

	for( $row = 0; $row < count( $bwlmsfields_fields ); $row++ ) {
		if( $bwlmsfields_fields[$row][2] == $field ) {
			$field_arr = $bwlmsfields_fields[$row];
		}
	}	

	$form_action = 'editfieldform';
?>
				<input type="hidden" name="field_arr" value="<?php echo $field_arr[2]; ?>" />
				<input type="hidden" name="bwlmsfields_admin_a" value="edit_field" />
<?php
}

function bwlmsfields_configure_custom_fields( $bwlmsfields_fields )
{
	?>

	<div class="postbox">
		<div class="inside">

			<form name="updatefieldform" id="updatefieldform" method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
				<?php wp_nonce_field( 'bwlmsfields-update-fields' ); ?>
				
				<input type="hidden" name="bwlmsfields_admin_a" value="update_fields" />
				<input type="submit" name="save"  class="button-primary" value="<?php _e( 'Save Changes', 'wptobemem' ); ?>" /> 

				<table class="widefat" id="wptobefields">
					<thead><tr class="head">
						<th width="25%" scope="col"><?php _e( 'FIELD LABEL', 'wptobemem' ); ?></th>
						<th width="15%" scope="col"><?php _e( 'TYPE',  'wptobemem' ); ?></th>
						<th width="10%" scope="col"><?php _e( 'PROFILE',    'wptobemem' ); ?></th>
						<th width="10%" scope="col"><?php _e( 'REQUIRED',   'wptobemem' ); ?></th>
						<th width="10%" scope="col"><?php _e( 'SIGN UP',  'wptobemem' ); ?></th>
						<th width="30%" scope="col"><?php _e( 'OPTION',  'wptobemem' ); ?></th>
					</tr></thead>
				<?php
				$bwlmsfields_ut_fields = get_option( 'bwlmsfields_utfields' ); // order, label, optionname, input type, display, required, native

				$class = '';
				for( $row = 0; $row < count($bwlmsfields_fields); $row++ ) {

					$class = ( $class == 'alternate' ) ? '' : 'alternate'; ?>

					<tr class="<?php echo $class; ?>" valign="top" id="<?php echo $bwlmsfields_fields[$row][0];?>">
						
						<td width="25%">
							<?php // Column1: Field Label 
								echo bwlmsfields_create_formfield( 
									$bwlmsfields_fields[$row][2]."_label" , // $name
									'text',									// $type
									$bwlmsfields_fields[$row][1]			// $value 
									); 
							?>
						</td>

						<td width="15%">
							<?php // Column2: field data type - Read Only Select box
								$bwlmsfields_dropdown_list  = array("text","textarea","checkbox","dropdown","password","file");
								if($bwlmsfields_fields[$row][6] == 'y') { // Wordpress native field
									if($bwlmsfields_fields[$row][2] =="description" ) {
										//echo $bwlmsfields_fields[$row][3];
										echo bwlmsfields_create_formfield( 
											$bwlmsfields_fields[$row][2]."_type" ,	// $name
											'select',								// $type
											$bwlmsfields_dropdown_list,				// $value
											$bwlmsfields_fields[$row][3],			// $valtochk : dropdown selected field
											'dropdown',								// $class
											true,									// $origin	: Wordpress native field
											'wpdescription'							// $spfield : Wordpress description field
											); 
									}
									else {//All text
										echo bwlmsfields_create_formfield( 
											$bwlmsfields_fields[$row][2]."_type" ,	// $name
											'select',								// $type
											$bwlmsfields_dropdown_list,				// $value
											'text',									// $valtochk : dropdown selected field
											'dropdown',								// $class
											true,									// $origin	: Wordpress native field
											'wpgeneralfield'						// $spfield : Wordpress description field
											); 
									}
								}
								else if($bwlmsfields_fields[$row][2] =="password" || $bwlmsfields_fields[$row][2] =="confirm_password" ){ // Password field
										echo bwlmsfields_create_formfield( 
											$bwlmsfields_fields[$row][2]."_type" ,	// $name
											'select',								// $type
											$bwlmsfields_dropdown_list,				// $value
											$bwlmsfields_fields[$row][3],			// $valtochk : dropdown selected field
											'dropdown',								// $class
											false,									// $origin	: Wordpress native field
											'password'						// $spfield : Wordpress description field
											); 
								}
								else{ // Normal field 
										echo bwlmsfields_create_formfield( 
											$bwlmsfields_fields[$row][2]."_type" ,	// $name
											'select',								// $type
											$bwlmsfields_dropdown_list,				// $value
											$bwlmsfields_fields[$row][3],			// $valtochk : dropdown  selected field
											'dropdown',								// $class
											false,									// $origin	: Wordpress native field
											'generalfield'						// $spfield : Wordpress description field
											); 
								}

							?>
						</td>

						<td width="10%">
							<?php // Column3: Display  check
							if ( $bwlmsfields_fields[$row][2] == 'user_email' ){
								?><input type="checkbox" checked disabled><?php
							}
							else if ( $bwlmsfields_fields[$row][2] == 'password' || $bwlmsfields_fields[$row][2] == 'confirm_password' ){
									?><input type="checkbox" disabled><?php
							}
							else {

								echo bwlmsfields_create_formfield( $bwlmsfields_fields[$row][2] . "_display", 'checkbox', 'y', $bwlmsfields_fields[$row][4] ); 
							}
							?>
						</td>

						<td width="10%">
							<?php // Column4:Set required field
							if ( $bwlmsfields_fields[$row][2] == 'user_email' ){
								?><input type="checkbox" checked disabled><?php
							}
							else {
								echo bwlmsfields_create_formfield( $bwlmsfields_fields[$row][2] . "_required",'checkbox', 'y', $bwlmsfields_fields[$row][5] ); 
							}
							?>
						</td>

						<td width="10%">
							<?php // Column3: Signup page Display  check

								$bwlmsfields_fields[$row][9] = isset($bwlmsfields_fields[$row][9]) ? $bwlmsfields_fields[$row][9] : null;
								
								if ( $bwlmsfields_fields[$row][2] == 'user_email' ){
									?><input type="checkbox" checked disabled><?php
								}
								else {
									echo bwlmsfields_create_formfield( $bwlmsfields_fields[$row][2] . "_showsignup", 'checkbox', 'y', $bwlmsfields_fields[$row][9] ); 
								}
								
								if($bwlmsfields_fields[$row][9] == 'y') {
								}
							?>
						</td>

						<td width="30%">
							<?php // Column5: Field Setting
								if(	$bwlmsfields_fields[$row][3]=='dropdown') {// Dropdown
									
									if(isset($bwlmsfields_fields[$row][10]) && $bwlmsfields_fields[$row][3] !='n')
									{
										$txtareaval ="";

										for ( $k = 0; $k < count( $bwlmsfields_fields[$row][10] ); $k++ ) {
											// If the row contains commas (i.e. 1,000-10,000), wrap in double quotes.
											if ( strstr( $bwlmsfields_fields[$row][10][$k], ',' ) ) {
													$txtareaval .= '"' . $bwlmsfields_fields[$row][10][$k]; 
													$txtareaval .= ( $k == count( $bwlmsfields_fields[$row][10] )- 1  ) ? '"' : "\",\n";
											} else {
													$txtareaval .=  $bwlmsfields_fields[$row][10][$k]; 
													$txtareaval .=  ( $k == count( $bwlmsfields_fields[$row][10] )- 1  ) ? "" : ",\n";
											} 
										}
									}

									$txtareaval = str_replace("	", "", $txtareaval);

									echo bwlmsfields_create_formfield( 
										$bwlmsfields_fields[$row][2]."_content" ,	// $name
										'textarea',								// $type
										$txtareaval,							// $value
										'',										// $valtochk : dropdown  selected field
										'textarea',								// $class
										false,									// $origin	: Wordpress native field
										'generalfield'							// $spfield : Wordpress description field
										); 
								}
							?>

						</td>
						<?php /*
						<td width="10%">
							<?php // Column6: WordPress/Wptobe custom 
								if($bwlmsfields_fields[$row][6] == 'y') {
									?><font color="#aeaeae"> <?php _e('WordPress','wptobemem'); ?> </font><?php
								}
								else {
									?><font color="#555"> <?php _e('Custom','wptobemem'); ?> </font><?php
								}
							?>
						</td>
						*/ ?>

					</tr><?php
				} ?>

				</table><br />


			</form>
		</div><!-- .inside -->
	</div>	
	<?php
}