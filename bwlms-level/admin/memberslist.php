<?php
	if((!current_user_can("manage_options") && !current_user_can("bwlmslevel_memberslist")))	{
		die(__("You do not have permissions to perform this action.", "wptobemem"));
	}

	global $wpdb;
	if(isset($_REQUEST['s']))
	{$s = sanitize_text_field(trim($_REQUEST['s'])); }
	else
		$s = "";

	if(isset($_REQUEST['l']))
		$l = sanitize_text_field($_REQUEST['l']);
	else
		$l = false;

?>

<div class="wrap bwlmslevel_admin">	

	<form id="posts-filter" method="get" action="">
	<h2>
		<?php _e('Membership Level Users', 'wptobemem');?>
		<a target="_blank" href="<?php echo admin_url('admin-ajax.php');?>?action=memberslist_csv&s=<?php echo $s;?>&l=<?php echo $l?>" class="add-new-h2"><?php _e('Export to CSV', 'wptobemem');?></a>

	</h2>
	<ul class="subsubsub">
		<li>
			<?php _e('Show', 'wptobemem');?>
			<select name="l" onchange="jQuery('#posts-filter').submit();">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'wptobemem');?></option>
				<?php
					$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->bwlmslevel_membership_levels ORDER BY name");
					foreach($levels as $level)
					{
				?>
					<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
				<?php
					}
				?>

				<option value="oldmembers" <?php if($l == "oldmembers") { ?>selected="selected"<?php } ?>><?php _e('Old Members', 'wptobemem');?></option>
			</select>
		</li>
	</ul>
	<p class="search-box">
		<label class="hidden" for="post-search-input"><?php _e('Search Members', 'wptobemem');?>:</label>
		<input type="hidden" name="page" value="bwlmslevel-memberslist" />
		<input id="post-search-input" type="text" value="<?php echo esc_attr($s);?>" name="s"/>
		<input class="button" type="submit" value="<?php _e('Search Members', 'wptobemem');?>"/>
	</p>
	<?php
		if(isset($_REQUEST['pn']))
			$pn = intval($_REQUEST['pn']);
		else
			$pn = 1;

		if(isset($_REQUEST['limit']))
			$limit = intval($_REQUEST['limit']);
		else
		{ // 페이지당 리스트 개수
			$limit = apply_filters('bwlmslevel_memberslist_per_page', 15);
		}

		$end = $pn * $limit;
		$start = $end - $limit;

		if($s)
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->bwlmslevel_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mu.membership_id = m.id ";

			if($l == "oldmembers")
				$sqlQuery .= " LEFT JOIN $wpdb->bwlmslevel_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

			$sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";

			if($l == "oldmembers")
				$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";

			elseif($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";

			$sqlQuery .= "GROUP BY u.ID ";

			if($l == "oldmembers")
				$sqlQuery .= "ORDER BY enddate DESC ";
			else
				$sqlQuery .= "ORDER BY u.user_registered DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}
		else
		{
			$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->bwlmslevel_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mu.membership_id = m.id";

			if($l == "oldmembers")
				$sqlQuery .= " LEFT JOIN $wpdb->bwlmslevel_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

			$sqlQuery .= " WHERE mu.membership_id > 0  ";

			if($l == "oldmembers")
				$sqlQuery .= " AND mu.status <> 'active' AND mu2.status IS NULL ";
			elseif($l)
				$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
			else
				$sqlQuery .= " AND mu.status = 'active' ";
			$sqlQuery .= "GROUP BY u.ID ";

			if($l == "oldmembers")
				$sqlQuery .= "ORDER BY enddate DESC ";
			else
				$sqlQuery .= "ORDER BY u.user_registered DESC ";

			$sqlQuery .= "LIMIT $start, $limit";
		}

		$sqlQuery = apply_filters("bwlmslevel_members_list_sql", $sqlQuery);

		$theusers = $wpdb->get_results($sqlQuery);
		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS() as found_rows");

	?>
	<table class="widefat">
		<thead>
			<tr class="thead">
				<th><?php _e('ID', 'wptobemem');?></th>
				<th><?php _e('Username', 'wptobemem');?></th>
				<th><?php _e('First&nbsp;Name', 'wptobemem');?></th>
				<th><?php _e('Last&nbsp;Name', 'wptobemem');?></th>
				<th><?php _e('Email', 'wptobemem');?></th>

				<th><?php _e('Membership', 'wptobemem');?></th>
				<th><?php _e('Points', 'wptobemem');?></th>
				<th><?php _e('Joined', 'wptobemem');?></th>

			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
				$count = 0;
				foreach($theusers as $auser)
				{
					//get meta
					$theuser = get_userdata($auser->ID);
					$user_point_total = get_user_meta( $auser->ID, 'bwlmscredit_default', true );
					?>
						<tr <?php if($count++ % 2 == 0) { ?>class="alternate"<?php } ?>>
							<td><?php echo $theuser->ID?></td>
							<td class="username column-username">
								<?php echo get_avatar($theuser->ID, 32)?>
								<strong>
									<?php
										$userlink = '<a href="user-edit.php?user_id=' . $theuser->ID . '">' . $theuser->user_login . '</a>';
										$userlink = apply_filters("bwlmslevel_members_list_user_link", $userlink, $theuser);
										echo $userlink;
									?>
								</strong>
								<br />
								<?php
									$actions = apply_filters( 'bwlmslevel_memberslist_user_row_actions', array(), $theuser );
									$action_count = count( $actions );
									$i = 0;
									if($action_count)
									{
										$out = '<div class="row-actions">';
										foreach ( $actions as $action => $link ) {
											++$i;
											( $i == $action_count ) ? $sep = '' : $sep = ' | ';
											$out .= "<span class='$action'>$link$sep</span>";
										}
										$out .= '</div>';
										echo $out;
									}
								?>
							</td>
							<td><?php echo $theuser->first_name?></td>
							<td><?php echo $theuser->last_name?></td>
							<td><a href="mailto:<?php echo esc_attr($theuser->user_email)?>"><?php echo $theuser->user_email?></a></td>
							
							<td><?php echo $auser->membership?></td>
							
							<td><div id="bwlmscredit-user-<?php echo $theuser->ID?>-balance-bwlmscredit_default"> <span><?php echo $user_point_total?></span></div><br><span class='adjust'><a href="javascript:void(0)" class="bwlmscredit-open-points-editor" data-userid="<?php echo $theuser->ID?>" data-current="<?php echo $user_point_total?>" data-type="bwlmscredit_default" data-username="<?php echo $theuser->user_login?>"><?php echo __( 'Edit', 'wptobemem' )?></a></span>
							</td>		

							<td><?php echo date(get_option("date_format"), strtotime($theuser->user_registered, current_time("timestamp")))?></td>

						</tr>
					<?php
				}

				if(!$theusers)
				{
				?>
				<tr>
					<td colspan="9"><p><?php _e("No members found.", "wptobemem");?> <?php if($l) { ?><a href="?page=bwlmslevel-memberslist&s=<?php echo esc_attr($s);?>"><?php _e("Search all levels", "wptobemem");?></a>.<?php } ?></p></td>
				</tr>
				<?php
				}
			?>
		</tbody>
	</table>
	</form>

	<?php
	echo bwlmslevel_getPaginationString($pn, $totalrows, $limit, 1, add_query_arg(array("s" => urlencode($s), "l" => $l, "limit" => $limit)));
	?>

	<div class="clear"></div>
</div>



<script type='text/javascript'>
/* <![CDATA[ */
	var bwlmsCREDITedit = {"ajaxurl":"<?php echo site_url()?>\/wp-admin\/admin-ajax.php","title":"Edit Users Balance","close":"Close","working":"Processing..."};
/* ]]> */

	/* <![CDATA[ */
	var thickboxL10n = {"next":"Next >","prev":"< Prev","image":"Image","of":"of","close":"Close","noiframes":"This feature requires inline frames. You have iframes disabled or your browser does not support them.","loadingAnimation":"http:\/\/wordpresso.co.kr\/wp-includes\/js\/thickbox\/loadingAnimation.gif"};var commonL10n = {"warnDelete":"You are about to permanently delete the selected items.\n  'Cancel' to stop, 'OK' to delete.","dismiss":"Dismiss this notice."};var heartbeatSettings = {"nonce":"e29638e275"};var authcheckL10n = {"beforeunload":"Your session has expired. You can log in again from this page or go to the login page.","interval":"180"};/* ]]> */

	document.write('<scr'+'ipt type="text/javascript" src="<?php echo site_url()?>/wp-admin/load-scripts.php?c=1&amp;load%5B%5D=thickbox,hoverIntent,common,admin-bar,svg-painter,heartbeat,wp-auth-check,jquery-ui-core,jquery-ui-widget,jquery-ui-mouse,jquery&amp;load%5B%5D=-ui-resizable,jquery-ui-draggable,jquery-ui-button,jquery-ui-position,jquery-ui-dialog,jquery-effects-core,jquery-effects-slide&amp;ver=4.2.2" ></scr'+'ipt>');
</script>
<?php wp_enqueue_script('membership_point_edit_ajax_js'); ?>