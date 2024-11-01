<?php
	if( !current_user_can("manage_options"))	{
		die(__("You do not have permissions to perform this action.", "wptobemem"));
	}

	global $wpdb;

	if(isset($_REQUEST['s']))
		$s = sanitize_text_field($_REQUEST['s']);
	else
		$s = "";

	if(isset($_REQUEST['l']))
		$l = sanitize_text_field($_REQUEST['l']);
	else
		$l = false;

	if(!empty($_REQUEST['pn']))
		$pn = intval($_REQUEST['pn']);
	else
		$pn = 1;

	if(!empty($_REQUEST['limit']))
		$limit = intval($_REQUEST['limit']);
	else
		$limit = false;

	if($limit)
	{
		$end = $pn * $limit;
		$start = $end - $limit;
	}
	else
	{
		$end = NULL;
		$start = NULL;
	}

    if($s)
    {
        $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS 
			u.ID, 
			u.user_login, 
			u.user_email, 
			UNIX_TIMESTAMP(u.user_registered) as joindate, 
			mu.membership_id, 

			UNIX_TIMESTAMP(mu.startdate) as startdate, 
			UNIX_TIMESTAMP(mu.enddate) as enddate, 
			m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->bwlmslevel_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mu.membership_id = m.id ";

        if($l == "oldmembers" )
            $sqlQuery .= " LEFT JOIN $wpdb->bwlmslevel_memberships_users mu2 ON u.ID = mu2.user_id AND mu2.status = 'active' ";

        $sqlQuery .= " WHERE mu.membership_id > 0 AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";

		if($l)
            $sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
        else
            $sqlQuery .= " AND mu.status = 'active' ";

        $sqlQuery .= "GROUP BY u.ID ";


        $sqlQuery .= "ORDER BY u.user_registered DESC ";


    }
    else
    {
        $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS 
			u.ID, 
			u.user_login, 
			u.user_email, 
			UNIX_TIMESTAMP(u.user_registered) as joindate, 
			mu.membership_id, 
			UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->bwlmslevel_memberships_users mu ON u.ID = mu.user_id LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mu.membership_id = m.id";


        $sqlQuery .= " WHERE mu.membership_id > 0  ";

        if($l)
            $sqlQuery .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql($l) . "' ";
        else
            $sqlQuery .= " AND mu.status = 'active' ";
        $sqlQuery .= "GROUP BY u.ID ";

        $sqlQuery .= "ORDER BY u.user_registered DESC ";

    }

	$sqlQuery = apply_filters("bwlmslevel_members_list_sql", $sqlQuery);

	$theusers = $wpdb->get_col($sqlQuery);

	header("Content-type: text/csv");
	if($s && $l)
		header("Content-Disposition: attachment; filename=members_list_" . intval($l) . "_level_" . sanitize_file_name($s) . ".csv");
	elseif($s)
		header("Content-Disposition: attachment; filename=members_list_" . sanitize_file_name($s) . ".csv");
	elseif($l == "oldmembers")
		header("Content-Disposition: attachment; filename=members_list_expired.csv");
	else
		header("Content-Disposition: attachment; filename=members_list.csv");

	$heading = "id,username,firstname,lastname,email,address1,address2,city,state,zipcode,country,phone,membership,joined";

	$heading = apply_filters("bwlmslevel_members_list_csv_heading", $heading);
	$csvoutput = $heading;

	$default_columns = array(
		array("theuser", "ID"),
		array("theuser", "user_login"),
		array("metavalues", "first_name"),
		array("metavalues", "last_name"),
		array("theuser", "user_email"),
		array("metavalues", "bwlmslevel_baddress1"),
		array("metavalues", "bwlmslevel_baddress2"),
		array("metavalues", "bwlmslevel_bcity"),
		array("metavalues", "bwlmslevel_bstate"),
		array("metavalues", "bwlmslevel_bzipcode"),
		array("metavalues", "bwlmslevel_bcountry"),
		array("metavalues", "bwlmslevel_bphone"),
		array("theuser", "membership"),

	);

	$default_columns = apply_filters("bwlmslevel_members_list_csv_default_columns", $default_columns);

	$extra_columns = apply_filters("bwlmslevel_members_list_csv_extra_columns", array());
	if(!empty($extra_columns))
	{
		foreach($extra_columns as $heading => $callback)
		{
			$csvoutput .= "," . $heading;
		}
	}

	$csvoutput .= "\n";

	echo $csvoutput;
	$csvoutput = "";

	if($theusers)
	{
		foreach($theusers as $user_id)
		{
				$theuser = $wpdb->get_row("SELECT u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(u.user_registered) as joindate, u.user_login, u.user_nicename, u.user_url, u.user_registered, u.user_status, u.display_name, mu.membership_id, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->bwlmslevel_memberships_users mu ON u.ID = mu.user_id AND mu.status = 'active' LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mu.membership_id = m.id WHERE u.ID = '" . $user_id . "' LIMIT 1");

			$sqlQuery = "SELECT meta_key as `key`, meta_value as `value` FROM $wpdb->usermeta WHERE $wpdb->usermeta.user_id = '" . $user_id . "'";
			$metavalues = bwlmslevel_getMetavalues($sqlQuery);
			$theuser->metavalues = $metavalues;

			if(!empty($default_columns))
			{
				$count = 0;
				foreach($default_columns as $col)
				{
					$count++;
					if($count > 1)
						$csvoutput .= ",";

					if(!empty($$col[0]->$col[1]))
						$csvoutput .= bwlmslevel_enclose($$col[0]->$col[1]);
				}
			}

			$csvoutput .= "," . bwlmslevel_enclose(date("Y-m-d", $theuser->joindate)) . ",";

			if($theuser->membership_id)
			{
				if($theuser->enddate)
					$csvoutput .= bwlmslevel_enclose(apply_filters("bwlmslevel_memberslist_expires_column", date("Y-m-d", $theuser->enddate), $theuser));
				else
					$csvoutput .= bwlmslevel_enclose(apply_filters("bwlmslevel_memberslist_expires_column", "Never", $theuser));
			}
			elseif($l == "oldmembers" && $theuser->enddate)
			{
				$csvoutput .= bwlmslevel_enclose(date("Y-m-d", $theuser->enddate));
			}
			else
				$csvoutput .= "N/A";

			if(!empty($extra_columns))
			{
				foreach($extra_columns as $heading => $callback)
				{
					$csvoutput .= "," . bwlmslevel_enclose(call_user_func($callback, $theuser, $heading));
				}
			}

			$csvoutput .= "\n";
			echo $csvoutput;
			$csvoutput = "";
		}
	}

	print $csvoutput;

	function bwlmslevel_enclose($s)
	{
		return "\"" . str_replace("\"", "\\\"", $s) . "\"";
	}