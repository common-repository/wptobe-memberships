<?php

function bwlmslevel_membership_level_profile_fields($user)
{
	global $current_user;

	$membership_level_capability = apply_filters("bwlmslevel_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;

	$user->membership_level = bwlmslevel_getMembershipLevelForUser($user->ID);

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->bwlmslevel_membership_levels}", OBJECT );

	if(!$levels)
		return "";
?>
<h3><?php _e("WPTOBE-Memberships Level", "wptobemem"); ?></h3>
<table class="form-table">
    <?php
		$show_membership_level = true;
		$show_membership_level = apply_filters("bwlmslevel_profile_show_membership_level", $show_membership_level, $user);
		if($show_membership_level)
		{
		?>
		<tr>
			<th><label for="membership_level"><?php _e("Level", "wptobemem"); ?></label></th>
			<td>
				<select name="membership_level">
					<option value="" <?php if(empty($user->membership_level->ID)) { ?>selected="selected"<?php } ?>>-- <?php _e("None", "wptobemem");?> --</option>
				<?php
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php selected($level->id, (isset($user->membership_level->ID) ? $user->membership_level->ID : 0 )); ?>><?php echo $level->name?></option>
				<?php
					}
				?>
				</select>

            </td>
		</tr>
		<?php
		}
		?>
		
</table>
   
<?php
	do_action("bwlmslevel_after_membership_level_profile_fields", $user);	
}


function bwlmslevel_membership_level_profile_fields_update()
{
	global $wpdb, $current_user, $user_ID;
	$current_user = wp_get_current_user();//get_currentuserinfo();
	
	if(!empty($_REQUEST['user_id'])) 
		$user_ID = intval($_REQUEST['user_id']);

	$membership_level_capability = apply_filters("bwlmslevel_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

    if(isset($_REQUEST['membership_level']))
    {
		$chg_membership_level = intval($_REQUEST['membership_level']); 

        $changed_or_cancelled = '';
        if($chg_membership_level === 0 ||$chg_membership_level === '0' ||$chg_membership_level =='')
        {
            $changed_or_cancelled = 'admin_cancelled';
        }
        else
            $changed_or_cancelled = 'admin_changed';


        if(bwlmslevel_changeMembershipLevel($chg_membership_level, $user_ID, $changed_or_cancelled))
        {
            $level_changed = true;
        }
		elseif(!empty($_REQUEST['cancel_subscription']))
		{
			$order = new MemberOrder();
			$order->getLastMemberOrder($user_ID);
						
			if(!empty($order) && !empty($order->id))
				$r = $order->cancel();			
		}
	}
}
add_action( 'show_user_profile', 'bwlmslevel_membership_level_profile_fields' );
add_action( 'edit_user_profile', 'bwlmslevel_membership_level_profile_fields' );
add_action( 'personal_options_update', 'bwlmslevel_membership_level_profile_fields_update' );
add_action( 'edit_user_profile_update', 'bwlmslevel_membership_level_profile_fields_update' );