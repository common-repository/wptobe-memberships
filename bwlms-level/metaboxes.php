<?php

function bwlmslevel_page_meta()
{
	global $membership_levels, $post, $wpdb;
	$page_levels = $wpdb->get_col("SELECT membership_id FROM {$wpdb->bwlmslevel_memberships_pages} WHERE page_id = '{$post->ID}'");
?>
    <ul id="membershipschecklist" class="list:category categorychecklist form-no-clear">
    <input type="hidden" name="bwlmslevel_noncename" id="bwlmslevel_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) )?>" />
	<?php
		$in_member_cat = false;
		foreach($membership_levels as $level)
		{
	?>
    	<li id="membership-level-<?php echo $level->id?>">
        	<label class="selectit">
            	<input id="in-membership-level-<?php echo $level->id?>" type="checkbox" <?php if(in_array($level->id, $page_levels)) { ?>checked="checked"<?php } ?> name="page_levels[]" value="<?php echo $level->id?>" />
				<?php
					echo $level->name;
					//Check which categories are protected for this level
					$protectedcategories = $wpdb->get_col("SELECT category_id FROM $wpdb->bwlmslevel_memberships_categories WHERE membership_id = $level->id");	
					//See if this post is in any of the level's protected categories
					if(in_category($protectedcategories, $post->id))
					{
						$in_member_cat = true;
						echo ' *';
					}
				?>
            </label>
        </li>
    <?php
		}
    ?>
    </ul>
	<?php 
		if('post' == get_post_type($post) && $in_member_cat) { ?>
		<p class="bwlmslevel_meta_notice">* <?php _e("This post is already protected for this level because it is within a category that requires membership.", "wptobemem");?></p>
	<?php 
		}
		
		do_action('bwlmslevel_after_require_membership_metabox', $post);
	?>
<?php
}

function bwlmslevel_page_save($post_id)
{
	global $wpdb;

	if(empty($post_id))
		return false;
	
	if (!empty($_POST['bwlmslevel_noncename']) && !wp_verify_nonce( $_POST['bwlmslevel_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}

	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	if(!empty($_POST['post_type']) && 'page' == $_POST['post_type'] )
	{
		if ( !current_user_can( 'edit_page', $post_id ) )
			return $post_id;
	}
	else
	{
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
	}

	if(isset($_POST['bwlmslevel_noncename']))
	{
		if(!empty($_POST['page_levels'])) {
			// Checkbox data (Checked, Unchecked). We do not need sanitization.
			$mydata = $_POST['page_levels'];
		}
		else
			$mydata = NULL;
	
		$wpdb->query("DELETE FROM {$wpdb->bwlmslevel_memberships_pages} WHERE page_id = '$post_id'");

		if(is_array($mydata))
		{
			foreach($mydata as $level)
				$wpdb->query("INSERT INTO {$wpdb->bwlmslevel_memberships_pages} (membership_id, page_id) VALUES('" . intval($level) . "', '" . intval($post_id) . "')");
		}
	
		return $mydata;
	}
	else
		return $post_id;
}

function bwlmslevel_page_meta_wrapper()
{
	add_meta_box('bwlmslevel_page_meta', __('Membership Level', 'wptobemem'), 'bwlmslevel_page_meta', 'page', 'side');
}
if (is_admin())
{
	add_action('admin_menu', 'bwlmslevel_page_meta_wrapper');
	add_action('save_post', 'bwlmslevel_page_save');
}

function bwlmslevel_taxonomy_meta($term)
{
	global $membership_levels, $post, $wpdb;
	
	$protectedlevels = array();
	foreach($membership_levels as $level)
	{
		$protectedlevel = $wpdb->get_col("SELECT category_id FROM $wpdb->bwlmslevel_memberships_categories WHERE membership_id = $level->id AND category_id = $term->term_id");
		if(!empty($protectedlevel))
			$protectedlevels[] .= '<a target="_blank" href="admin.php?page=bwlmslevel-membershiplevels&edit=' . $level->id . '">' . $level->name. '</a>';
	}
	if(!empty($protectedlevels)) 
	{ 
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><?php _e( 'Membership Levels', 'wptobemem' ); ?></label></th>
			<td>
				<p><strong>
					<?php echo implode(', ',$protectedlevels); ?></strong></p>
				<p class="description"><?php _e('Only members of these levels will be able to view posts in this category.','wptobemem'); ?></p>
			</td>
		</tr>
	<?php
	}
}
add_action( 'category_edit_form_fields', 'bwlmslevel_taxonomy_meta', 10, 2 );