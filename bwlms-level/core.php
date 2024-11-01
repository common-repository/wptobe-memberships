<?php

function bwlmslevel_init()
{
	
	global $bwlmslevel_pages, $bwlmslevel_core_pages, $bwlmslevel_currencies, $bwlmslevel_currency, $bwlmslevel_currency_symbol;
	$bwlmslevel_pages = array();
	$bwlmslevel_pages["account"] = bwlmslevel_getOption("account_page_id");
	$bwlmslevel_pages["levels"] = bwlmslevel_getOption("levels_page_id");

	$bwlmslevel_core_pages = $bwlmslevel_pages;
}
add_action("init", "bwlmslevel_init");

function bwlmslevel_set_current_user()
{
	global $current_user, $wpdb;
	//get_currentuserinfo();
	$current_user = wp_get_current_user();
	$id = intval($current_user->ID);
	if($id)
	{
		$current_user->membership_level = bwlmslevel_getMembershipLevelForUser($current_user->ID);
		if(!empty($current_user->membership_level))
		{
			$current_user->membership_level->categories = bwlmslevel_getMembershipCategories($current_user->membership_level->ID);
		}
		$current_user->membership_levels = bwlmslevel_getMembershipLevelsForUser($current_user->ID);
	}

	do_action("bwlmslevel_after_set_current_user");
}
add_action('set_current_user', 'bwlmslevel_set_current_user');
add_action('init', 'bwlmslevel_set_current_user');


function bwlmslevel_manage_users_columns($columns) {
    $columns['bwlmslevel_membership_level'] = __('Membership Level', 'wptobemem');
    return $columns;
}
add_filter('manage_users_columns', 'bwlmslevel_manage_users_columns');

function bwlmslevel_manage_users_custom_column($column_data, $column_name, $user_id) {

    if($column_name == 'bwlmslevel_membership_level') {
        $levels = bwlmslevel_getMembershipLevelsForUser($user_id);
        $level_names = array();
        if(!empty($levels)) {
            foreach($levels as $key => $level)
                $level_names[] = $level->name;
            $column_data = implode(',', $level_names);
        }
        else
            $column_data = __('None', 'wptobemem');
    }
    return $column_data;
}
add_filter('manage_users_custom_column', 'bwlmslevel_manage_users_custom_column', 10, 3);

function bwlmslevel_setDBTables()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->bwlmslevel_membership_levels = $wpdb->prefix . 'bwlmslevel_membership_levels';
	$wpdb->bwlmslevel_memberships_users = $wpdb->prefix . 'bwlmslevel_memberships_users';
	$wpdb->bwlmslevel_memberships_categories = $wpdb->prefix . 'bwlmslevel_memberships_categories';
	$wpdb->bwlmslevel_memberships_pages = $wpdb->prefix . 'bwlmslevel_memberships_pages';
	$wpdb->bwlmslevel_membership_levelmeta = $wpdb->prefix . 'bwlmslevel_membership_levelmeta';
}
bwlmslevel_setDBTables();

function bwlmslevel_is_plugin_active( $plugin ) {
	return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
}

function bwlmslevel_getMatches($p, $s, $firstvalue = FALSE, $n = 1)
{
	$ok = preg_match_all($p, $s, $matches);

	if(!$ok)
		return false;
	else
	{
		if($firstvalue)
			return $matches[$n][0];
		else
			return $matches[$n];
	}
}

function bwlmslevel_getOption($s, $force = false)
{
	if(get_option("bwlmslevel_" . $s))
		return get_option("bwlmslevel_" . $s);
	else
		return "";
}

function bwlmslevel_setOption($s, $v = NULL)
{
	if($v === NULL && isset($_POST[$s]))
		$v = sanitize_text_field($_POST[$s]);

	if(is_array($v))
		$v = implode(",", $v);
	else
		$v = trim($v);

	return update_option("bwlmslevel_" . $s, $v);
}

function bwlmslevel_get_slug($post_id)
{
	global $bwlmslevel_slugs, $wpdb;

	$post_id = intval($post_id);

	if(!$bwlmslevel_slugs[$post_id])
		$bwlmslevel_slugs[$post_id] = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE ID = '" . $post_id . "' LIMIT 1");

	return $bwlmslevel_slugs[$post_id];
}

function bwlmslevel_url($page = NULL, $querystring = "", $scheme = NULL)
{
	global $besecure;
	$besecure = apply_filters("besecure", $besecure);

	if(!$scheme && $besecure)
		$scheme = "https";
	elseif(!$scheme)
		$scheme = "http";

	if(!$page)
		$page = "levels";

	global $bwlmslevel_pages;

	$url = get_permalink($bwlmslevel_pages[$page]);

	if(function_exists("icl_object_id") && defined("ICL_LANGUAGE_CODE"))
	{
		$trans_id = icl_object_id($bwlmslevel_pages[$page], "page", false, ICL_LANGUAGE_CODE);
		if(!empty($trans_id))
		{
			$url = get_permalink($trans_id);
		}
	}

	if(strpos($url, "?"))
		$querystring = str_replace("?", "&", $querystring);
	$url .= $querystring;

	if(is_ssl())
		$url = str_replace("http:", "https:", $url);

	return $url;
}


function add_bwlmslevel_membership_level_meta($level_id, $meta_key, $meta_value, $unique = false) {	
	return add_metadata('bwlmslevel_membership_level', $level_id, $meta_key, $meta_value, $unique);
}

function get_bwlmslevel_membership_level_meta($level_id, $key, $single = false) {
	return get_metadata('bwlmslevel_membership_level', $level_id, $key, $single);
}

function update_bwlmslevel_membership_level_meta($level_id, $meta_key, $meta_value, $prev_value = '') {		
	return update_metadata('bwlmslevel_membership_level', $level_id, $meta_key, $meta_value, $prev_value);
}

function delete_bwlmslevel_membership_level_meta($level_id, $meta_key, $meta_value = '') {		
	return delete_metadata('bwlmslevel_membership_level', $level_id, $meta_key, $meta_value);
}

function bwlmslevel_hasMembershipLevel($levels = NULL, $user_id = NULL)
{
	global $current_user, $wpdb;

	$return = false;

	if(empty($user_id)) 
	{
		$user_id = $current_user->ID;
		$membership_levels = $current_user->membership_levels;
	}
	elseif(is_numeric($user_id)) 
	{
		$membership_levels = bwlmslevel_getMembershipLevelsForUser($user_id);
	}
	else
		return false;	

	if($levels === "0" || $levels === 0) 
	{
		$return = empty($membership_levels);
	}
	elseif(empty($levels)) 
	{
		$return = !empty($membership_levels);
	}
	else
	{
		if(!is_array($levels)) 
		{
			$levels = array($levels);
		}

		if(empty($membership_levels))
		{
			if(in_array(0, $levels, true) || in_array("0", $levels))
				$return = true;
			elseif(in_array("L", $levels) || in_array("l", $levels))
				$return = (!empty($user_id) && $user_id == $current_user->ID);
			elseif(in_array("-L", $levels) || in_array("-l", $levels))
				$return = (empty($user_id) || $user_id != $current_user->ID);
			elseif(in_array("E", $levels) || in_array("e", $levels)) {
				$sql = "SELECT id FROM $wpdb->bwlmslevel_memberships_users WHERE user_id=$user_id AND status='expired' LIMIT 1";
				$expired = $wpdb->get_var($sql);
				$return = !empty($expired);
			}
		}
		else
		{
			foreach($levels as $level)
			{
				if(strtoupper($level) == "L")
				{
					if(!empty($user_id) && $user_id == $current_user->ID)
						$return = true;
				}
				elseif(strtoupper($level) == "-L")
				{
					if(empty($user_id) || $user_id != $current_user->ID)
						$return = true;
				}
				elseif($level == "0" || strtoupper($level) == "E")
				{
					continue;	
				}
				else
				{
					$level_obj = bwlmslevel_getLevel(is_numeric($level) ? abs(intval($level)) : $level); 
					if(empty($level_obj)){continue;} 
					$found_level = false;
					foreach($membership_levels as $membership_level)
					{
						if($membership_level->id == $level_obj->id) 
						{
							$found_level = true;
						}
					}

					if(is_numeric($level) && intval($level) < 0 && !$found_level) 
					{
						$return = true;
					}
					elseif(is_numeric($level) && intval($level) > 0 && $found_level) 
					{
						$return = true;
					}
					elseif(!is_numeric($level))	
						$return = $found_level;
				}
			}
		}
	}

	$return = apply_filters("bwlmslevel_has_membership_level", $return, $user_id, $levels);
	return $return;
}

function bwlmslevel_changeMembershipLevel($level, $user_id = NULL, $old_level_status = 'inactive')
{
	global $wpdb;
	global $current_user, $bwlmslevel_error;

	if(empty($user_id))
	{
		$user_id = $current_user->ID;
	}

	if(empty($user_id))
	{
		$bwlmslevel_error = __("User ID not found.", "wptobemem");
		return false;
	}

	$user_id = intval($user_id);

	if(empty($level)) 
	{
		$level = 0;
	}
	else if(is_array($level))
	{
		//custom level
	}
	else
	{
		$level_obj = bwlmslevel_getLevel($level);
		if(empty($level_obj))
		{
			$bwlmslevel_error = __("Invalid level.", "wptobemem");
			return false;
		}
		$level = $level_obj->id;
	}

	if(!is_array($level))
	{
		if(bwlmslevel_hasMembershipLevel($level, $user_id)) {
			$bwlmslevel_error = __("not changing?", "wptobemem");
			return false; 
		}
	}

	$old_levels = bwlmslevel_getMembershipLevelsForUser($user_id);

	if($old_levels)
	{
		foreach($old_levels as $old_level) {

			$sql = "UPDATE $wpdb->bwlmslevel_memberships_users SET status='$old_level_status', enddate='" . current_time('mysql') . "' WHERE id=".$old_level->subscription_id;

			if(!$wpdb->query($sql))
			{
				$bwlmslevel_error = __("Error interacting with database", "wptobemem") . ": ".(mysql_errno()?mysql_error():'unavailable');

				return false;
			}
		}
	}

	if(is_array($level))
		$level_id = $level['membership_id'];	
	else
		$level_id = $level;


	do_action("bwlmslevel_before_change_membership_level", $level_id, $user_id);

	$bwlmslevel_cancel_previous_subscriptions = true;
	if(isset($_REQUEST['cancel_membership']) && $_REQUEST['cancel_membership'] == false)
		$bwlmslevel_cancel_previous_subscriptions = false;
	$bwlmslevel_cancel_previous_subscriptions = apply_filters("bwlmslevel_cancel_previous_subscriptions", $bwlmslevel_cancel_previous_subscriptions);

	if($bwlmslevel_cancel_previous_subscriptions)
	{
		$other_order_ids = $wpdb->get_col("SELECT id FROM $wpdb->bwlmslevel_membership_orders WHERE user_id = '" . $user_id . "' AND status = 'success' ORDER BY id DESC");

		foreach($other_order_ids as $order_id)
		{
			$c_order = new MemberOrder($order_id);
			$c_order->cancel();

			if(!empty($c_order->error))
				$bwlmslevel_error = $c_order->error;
		}
	}

	if(!empty($level)) 
	{
		if(is_array($level))
		{
			if($level['startdate'] != current_time('mysql') && $level['startdate'] != "NULL" && substr($level['startdate'], 0, 1) != "'")
				$level['startdate'] = "'" . $level['startdate'] . "'";

			if($level['enddate'] != current_time('mysql') && $level['enddate'] != "NULL" && substr($level['enddate'], 0, 1) != "'")
				$level['enddate'] = "'" . $level['enddate'] . "'";

			if ($level['cycle_period'] == '') $level['cycle_period'] = 0;

			$sql = "INSERT INTO $wpdb->bwlmslevel_memberships_users (user_id, membership_id, code_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, startdate, enddate)
					VALUES('" . $level['user_id'] . "',
					'" . $level['membership_id'] . "',
					'" . intval($level['code_id']) . "',
					'" . $level['initial_payment'] . "',
					'" . $level['billing_amount'] . "',
					'" . $level['cycle_number'] . "',
					'" . $level['cycle_period'] . "',
					'" . $level['billing_limit'] . "',
					'" . $level['trial_amount'] . "',
					'" . $level['trial_limit'] . "',
					" . $level['startdate'] . ",
					" . $level['enddate'] . ")";

			if(!$wpdb->query($sql))
			{
				$bwlmslevel_error = __("Error interacting with database", "wptobemem") . ": ".(mysql_errno()?mysql_error():'unavailable');
				return false;
			}
		}
		else
		{
			$sql = "INSERT INTO $wpdb->bwlmslevel_memberships_users (user_id, membership_id, code_id, initial_payment, billing_amount, cycle_number, cycle_period, billing_limit, trial_amount, trial_limit, startdate, enddate)
			    VALUES (
			    '" . $user_id . "',
			    '" . $level . "',
			    '0',
			    '0',
			    '0',
			    '0',
			    '0',
			    '0',
			    '0',
			    '0',
			    '" . current_time('mysql') . "',
                	    '0000-00-00 00:00:00'
                	    )";

			if(!$wpdb->query($sql))
			{
				$bwlmslevel_error = __("Error interacting with database", "wptobemem") . ": ".(mysql_errno()?mysql_error():'unavailable');
				return false;
			}
		}
	}

	global $all_membership_levels;
	unset($all_membership_levels[$user_id]);

	bwlmslevel_set_current_user();

	do_action("bwlmslevel_after_change_membership_level", $level_id, $user_id);
	return true;
}

function bwlmslevel_toggleMembershipCategory( $level, $category, $value )
{
	global $wpdb;
	$category = intval($category);

	if ( ($level = intval($level)) <= 0 )
	{
		$safe = addslashes($level);
		if ( ($level = intval($wpdb->get_var("SELECT id FROM {$wpdb->bwlmslevel_membership_levels} WHERE name = '$safe' LIMIT 1"))) <= 0 )
		{
			return __("Membership level not found.", "wptobemem");
		}
	}

	if ( $value )
	{
		$sql = "REPLACE INTO {$wpdb->bwlmslevel_memberships_categories} (membership_id,category_id) VALUES ('$level','$category')";
		$wpdb->query($sql);
		if(mysql_errno()) return mysql_error();
	}
	else
	{
		$sql = "DELETE FROM {$wpdb->bwlmslevel_memberships_categories} WHERE membership_id = '$level' AND category_id = '$category' LIMIT 1";
		$wpdb->query($sql);
		if(mysql_errno()) return mysql_error();
	}

	return true;
}

function bwlmslevel_updateMembershipCategories($level, $categories)
{
	global $wpdb;

	if(!is_numeric($level))
	{
		$level = $wpdb->get_var("SELECT id FROM $wpdb->bwlmslevel_membership_levels WHERE name = '" . esc_sql($level) . "' LIMIT 1");
		if(empty($level))
		{
			return __("Membership level not found.", "wptobemem");
		}
	}

	$sqlQuery = "DELETE FROM $wpdb->bwlmslevel_memberships_categories WHERE membership_id = '" . esc_sql($level) . "'";
	$wpdb->query($sqlQuery);
	if(mysql_errno()) return mysql_error();

	foreach($categories as $cat)
	{
		if(is_string($r = bwlmslevel_toggleMembershipCategory( $level, $cat, true)))
		{
			return $r;
		}
	}

	return true;
}

function bwlmslevel_getMembershipCategories($level_id)
{
	$level_id = intval($level_id);

	global $wpdb;
	$categories = $wpdb->get_col("SELECT c.category_id
										FROM {$wpdb->bwlmslevel_memberships_categories} AS c
										WHERE c.membership_id = '" . $level_id . "'");

	return $categories;
}



function bwlmslevel_getMetavalues($query)
{
	global $wpdb;

	$results = $wpdb->get_results($query);
	$r = new stdClass();
	foreach($results as $result)
	{
		if(!empty($r) && !empty($result->key))
			$r->{$result->key} = $result->value;
	}

	return $r;
}

function bwlmslevel_getPaginationString($page = 1, $totalitems, $limit = 15, $adjacents = 1, $targetpage = "/", $pagestring = "&pn=")
{
	if(!$adjacents) $adjacents = 1;
	if(!$limit) $limit = 15;
	if(!$page) $page = 1;
	if(!$targetpage) $targetpage = "/";

	$prev = $page - 1;
	$next = $page + 1;
	$lastpage = ceil($totalitems / $limit);	
	$lpm1 = $lastpage - 1;					

	$pagination = "";
	if($lastpage > 1)
	{
		$pagination .= "<div class=\"bwlmslevel_pagination\"";
		if(!empty($margin) || !empty($padding))
		{
			$pagination .= " style=\"";
			if($margin)
				$pagination .= "margin: $margin;";
			if($padding)
				$pagination .= "padding: $padding;";
			$pagination .= "\"";
		}
		$pagination .= ">";

		if ($page > 1)
			$pagination .= "<a href=\"$targetpage$pagestring$prev\">&laquo; prev</a>";
		else
			$pagination .= "<span class=\"disabled\">&laquo; prev</span>";

		if ($lastpage < 7 + ($adjacents * 2))	
		{
			for ($counter = 1; $counter <= $lastpage; $counter++)
			{
				if ($counter == $page)
					$pagination .= "<span class=\"current\">$counter</span>";
				else
					$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
			}
		}
		elseif($lastpage >= 7 + ($adjacents * 2))
		{
			if($page < 1 + ($adjacents * 3))
			{
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
				{
					if ($counter == $page)
						$pagination .= "<span class=\"current\">$counter</span>";
					else
						$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
				}
				$pagination .= "...";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";
			}
			elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
			{
				$pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
				$pagination .= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
				{
					if ($counter == $page)
						$pagination .= "<span class=\"current\">$counter</span>";
					else
						$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
				}
				$pagination .= "...";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . $lpm1 . "\">$lpm1</a>";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . $lastpage . "\">$lastpage</a>";
			}
			else
			{
				$pagination .= "<a href=\"" . $targetpage . $pagestring . "1\">1</a>";
				$pagination .= "<a href=\"" . $targetpage . $pagestring . "2\">2</a>";
				$pagination .= "...";
				for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++)
				{
					if ($counter == $page)
						$pagination .= "<span class=\"current\">$counter</span>";
					else
						$pagination .= "<a href=\"" . $targetpage . $pagestring . $counter . "\">$counter</a>";
				}
			}
		}

		if ($page < $counter - 1)
			$pagination .= "<a href=\"" . $targetpage . $pagestring . $next . "\">next &raquo;</a>";
		else
			$pagination .= "<span class=\"disabled\">next &raquo;</span>";
		$pagination .= "</div>\n";
	}

	return $pagination;
}

function bwlmslevel_implodeToEnglish($array, $conjunction = 'and')
{
	if (!$array || !count ($array))
		return '';

	$last = array_pop ($array);

	if (!count ($array))
		return $last;

	if($conjunction == 'and')
		$conjunction = __('and', 'wptobemem');

	return implode (', ', $array).' ' . $conjunction . ' '.$last;
}

function bwlmslevel_getMembershipLevelForUser($user_id = NULL, $force = false)
{
	if(empty($user_id))
	{
		global $current_user;
		$user_id = $current_user->ID;
	}

	if(empty($user_id))
	{
		return false;
	}

	$user_id = intval($user_id);

	global $all_membership_levels;

	if(isset($all_membership_levels[$user_id]) && !$force)
	{
		return $all_membership_levels[$user_id];
	}
	else
	{
		global $wpdb;
		$all_membership_levels[$user_id] = $wpdb->get_row("SELECT
															l.id AS ID,
															l.id as id,
															mu.id as subscription_id,
															l.name AS name,
															l.level as l_level,
															l.description,
															l.expiration_number,
															l.expiration_period,
															mu.initial_payment,
															mu.billing_amount,
															mu.cycle_number,
															mu.cycle_period,
															mu.billing_limit,
															mu.trial_amount,
															mu.trial_limit,
															mu.code_id as code_id,
															UNIX_TIMESTAMP(startdate) as startdate,
															UNIX_TIMESTAMP(enddate) as enddate
														FROM {$wpdb->bwlmslevel_membership_levels} AS l
														JOIN {$wpdb->bwlmslevel_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = $user_id AND mu.status = 'active'
														LIMIT 1");

		$all_membership_levels[$user_id] = apply_filters('bwlmslevel_get_membership_level_for_user', $all_membership_levels[$user_id]);

		return $all_membership_levels[$user_id];
	}
}

function bwlmslevel_getMembershipLevelsForUser($user_id = NULL, $include_inactive = false)
{
	if(empty($user_id))
	{
		global $current_user;
		$user_id = $current_user->ID;
	}

	if(empty($user_id))
	{
		return false;
	}

	$user_id = intval($user_id);

	global $wpdb;

	$levels = $wpdb->get_results("SELECT
								l.id AS ID,
								l.id as id,
								mu.id as subscription_id,
								l.name,
								l.level as l_level,
								l.description,
								l.expiration_number,
								l.expiration_period,
								mu.initial_payment,
								mu.billing_amount,
								mu.cycle_number,
								mu.cycle_period,
								mu.billing_limit,
								mu.trial_amount,
								mu.trial_limit,
								mu.code_id as code_id,
								UNIX_TIMESTAMP(startdate) as startdate,
								UNIX_TIMESTAMP(enddate) as enddate
							FROM {$wpdb->bwlmslevel_membership_levels} AS l
							JOIN {$wpdb->bwlmslevel_memberships_users} AS mu ON (l.id = mu.membership_id)
							WHERE mu.user_id = $user_id".($include_inactive?"":" AND mu.status = 'active'"));

	$levels = apply_filters('bwlmslevel_get_membership_levels_for_user', $levels);

	return $levels;
}

function bwlmslevel_getLevel($level)
{
	global $bwlmslevel_levels;

	if(is_object($level) && !empty($level->id))
		$level = $level->id;

	if(is_numeric($level))
	{
		$level_id = intval($level);
		if(isset($bwlmslevel_levels[$level_id]))
		{
			return $bwlmslevel_levels[$level_id];
		}
		else
		{
			global $wpdb;
			$bwlmslevel_levels[$level_id] = $wpdb->get_row("SELECT * FROM $wpdb->bwlmslevel_membership_levels WHERE id = '" . $level_id . "' LIMIT 1");
			return $bwlmslevel_levels[$level_id];
		}
	}
	else
	{
		global $wpdb;
		$level_obj = $wpdb->get_row("SELECT * FROM $wpdb->bwlmslevel_membership_levels WHERE name = '" . esc_sql($level) . "' LIMIT 1");

		if(!empty($level_obj))
			$level_id = $level_obj->id;
		else
			return false;

		$bwlmslevel_levels[$level_id] = $level_obj;
		return $bwlmslevel_levels[$level_id];
	}
}

function bwlmslevel_getAllLevels($include_hidden = false, $force = false)
{
	global $bwlmslevel_levels, $wpdb;

	if(!empty($bwlmslevel_levels) && !$force)
		return $bwlmslevel_levels;

	$sqlQuery = "SELECT * FROM $wpdb->bwlmslevel_membership_levels ";
	if(!$include_hidden)
		$sqlQuery .= " WHERE allow_signups = 1 ORDER BY id";

	$raw_levels = $wpdb->get_results($sqlQuery);

	$bwlmslevel_levels = array();
	foreach($raw_levels as $raw_level)
	{
		$bwlmslevel_levels[$raw_level->id] = $raw_level;
	}

	return $bwlmslevel_levels;
}

if (!function_exists('unique_multidim_array')) { 
	//sc-bwlms_level.php
	function unique_multidim_array($array, $key) { 
		$temp_array = array(); 
		$i = 0; 
		$key_array = array(); 
		
		foreach($array as $val) { 
			if (!in_array($val[$key], $key_array)) { 
				$key_array[$i] = $val[$key]; 
				$temp_array[$i] = $val; 
			} 
			$i++; 
		} 
		return $temp_array; 
	} 	
}