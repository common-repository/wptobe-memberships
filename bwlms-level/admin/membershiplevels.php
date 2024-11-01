<?php

	if( (!current_user_can("manage_options") && !current_user_can("bwlmslevel_membershiplevels"))){
		die(__("You do not have permissions to perform this action.", "wptobemem"));
	}	
	
	global $wpdb, $msg, $msgt, $wp_version;
	
	if(isset($_REQUEST['edit']))
		$edit = intval($_REQUEST['edit']);
	else
		$edit = false;
	
	if(isset($_REQUEST['action']))
		$action = sanitize_text_field($_REQUEST['action']);
	else
		$action = false;

	if($action == "save_membershiplevel")
	{
		$ml_categories = array();


		foreach ( $_REQUEST as $key => $value )
		{
			if ( $value == 'yes' && preg_match( '/^membershipcategory_(\d+)$/i', $key, $matches ) )
			{
				$ml_categories[] = $matches[1];
			}
		}

		for($i =1;$i<=10;$i++)
		{
			if(isset($_REQUEST['saveid_'.$i]))
				$saveid = intval($_REQUEST['saveid_'.$i]);

			$ml_name = sanitize_text_field($_REQUEST['name_'.$i]);
			
			if($saveid > 0)
			{
				$sqlQuery = " UPDATE {$wpdb->bwlmslevel_membership_levels}
							SET name = '" . esc_sql($ml_name) . "'
							WHERE id = '$saveid' LIMIT 1;";	 
				$wpdb->query($sqlQuery);
				
				bwlmslevel_updateMembershipCategories( $saveid, $ml_categories );
			}
			do_action("bwlmslevel_save_membership_level", $saveid);
		}
	}	

	elseif($action == "delete_membership_level")
	{
		global $wpdb;

		$ml_id = intval($_REQUEST['deleteid']);
	  
		if($ml_id > 0)
		{	  
			do_action("bwlmslevel_delete_membership_level", $ml_id);
			
			$sqlQuery = "DELETE FROM $wpdb->bwlmslevel_memberships_categories WHERE membership_id = '$ml_id'";
			$r1 = $wpdb->query($sqlQuery);
							
			$r2 = true;
			$user_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->bwlmslevel_memberships_users WHERE membership_id = '$ml_id' AND status = 'active'");			
			foreach($user_ids as $user_id)
			{
				if(bwlmslevel_changeMembershipLevel(0, $user_id))
				{
					//okay
				}
				else
				{
					$r2 = false;
				}	
			}					
			
			$sqlQuery = "DELETE FROM $wpdb->bwlmslevel_membership_levels WHERE id = '$ml_id' LIMIT 1";	  			
			$r3 = $wpdb->query($sqlQuery);
					
			if($r1 !== FALSE && $r2 !== FALSE && $r3 !== FALSE)
			{
				$msg = 3;
				$msgt = __("Membership level deleted successfully.", "wptobemem");
			}
			else
			{
				$msg = -3;
				$msgt = __("Error deleting membership level.", "wptobemem");	
			}
		}
		else
		{
			$msg = -3;
			$msgt = __("Error deleting membership level.", "wptobemem");
		}
	}  
	

	?>
		<div class="wptobemem_level_settings_wrapper">
			<form action="" method="post" enctype="multipart/form-data">

			<div class="row wptobemem_save_btn_row">
				<input name="save" type="submit" class="button-primary" value="<?php _e('Save Changes', 'wptobemem'); ?>" />	
			</div>


			<div class="row fullwidthrow_mem_level_admin bwlmslevel-settingtitle-row">
				<div class="small-2 large-2 columns bwlmslevel-field-title"><?php _e('LEVEL','wptobemem');?> </div>
				<div class="small-4 large-4 columns bwlmslevel-field-title"><?php _e('MEMBERSHIP LEVEL NAME','wptobemem');?> </div>
				<div class="small-6 large-6 columns"> 	</div>
			</div>

			<div class="row fullwidthrow_mem_level_admin bwlmslevel_admin_content_row">
		
				<?php	

				$sqlQuery = "SELECT * FROM $wpdb->bwlmslevel_membership_levels ";
				$sqlQuery .= "ORDER BY id ASC";
				
				$levels = $wpdb->get_results($sqlQuery, OBJECT);	
				?>

				<input name="action" type="hidden" value="save_membershiplevel" />
				<?php
					$count = 0;
					foreach($levels as $level)
					{ 
						$count++;
				?>
					<div class="row">
						<div class="small-2 large-2 columns bwlmslevel-levelname-field"> <?php echo sprintf(__("Level %s", 'wptobemem'), $count ); ?></div>
						<div class="small-4 large-4 columns bwlmslevel-levelname-field"> 

									<input name="saveid_<?php echo esc_attr($count); ?>" type="hidden" value="<?php echo esc_attr($level->id); ?>" />
									
									<input name="name_<?php echo esc_attr($count); ?>" type="text" size="50" value="<?php echo esc_attr($level->name);?>" />

						</div>
						<div class="small-6 large-6 columns bwlmslevel-levelname-field"> 
							<!--input name="save" type="submit" class="button-secondary" value="<?php _e('Change Name', 'wptobemem'); ?>" /-->
						</div>
					</div><!--row-->
				<?php } ?>

				</form>
			</div>
		</div><!--end of content row -->