<?php

function bwlmslevel_has_membership_access($post_id = NULL, $user_id = NULL, $return_membership_levels = false)
{
	global $post, $wpdb, $current_user;

	if(!$post_id && !empty($post))
		$post_id = $post->ID;
	if(!$user_id)
		$user_id = $current_user->ID;

	if(!$post_id)
		return true;

	if (!isset($post->post_type))
		return true;

	if( isset($post->ID) && !empty($post->ID) && $post_id == $post->ID)
		$mypost = $post;
	else
		$mypost = get_post($post_id);

	if($user_id == $current_user->ID)
		$myuser = $current_user;
	else
		$myuser = get_userdata($user_id);

	if(isset($mypost->post_type) && in_array( $mypost->post_type, array("attachment", "revision")))
	{
		$mypost = get_post($mypost->post_parent);
	}

    $mypost = apply_filters( 'bwlmslevel_membership_access_post', $mypost, $myuser );
	
	if(isset($mypost->post_type) && $mypost->post_type == "post")
	{
		$post_categories = wp_get_post_categories($mypost->ID);

		if(!$post_categories)
		{
			$sqlQuery = "SELECT m.id, m.name FROM $wpdb->bwlmslevel_memberships_pages mp LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $mypost->ID . "'";
		}
		else
		{
			$sqlQuery = "(SELECT m.id, m.name FROM $wpdb->bwlmslevel_memberships_categories mc LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mc.membership_id = m.id WHERE mc.category_id IN(" . implode(",", $post_categories) . ") AND m.id IS NOT NULL) UNION (SELECT m.id, m.name FROM $wpdb->bwlmslevel_memberships_pages mp LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $mypost->ID . "')";
		}
	}
	else
	{
		$sqlQuery = "SELECT m.id, m.name FROM $wpdb->bwlmslevel_memberships_pages mp LEFT JOIN $wpdb->bwlmslevel_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . $post_id . "'";
	}

	$post_membership_levels = $wpdb->get_results($sqlQuery);
	$post_membership_levels_ids = array();
	$post_membership_levels_names = array();

	if(!$post_membership_levels)
	{
		$hasaccess = true;
	}
	else
	{
		foreach($post_membership_levels as $level)
		{
			$post_membership_levels_ids[] = $level->id;
			$post_membership_levels_names[] = $level->name;
		}

		if(is_feed())
		{
			$hasaccess = false;
		}
		elseif(!empty($myuser->ID))
		{
			$myuser->membership_level = bwlmslevel_getMembershipLevelForUser($myuser->ID);
			if(!empty($myuser->membership_level->ID) && in_array($myuser->membership_level->ID, $post_membership_levels_ids))
			{
				$hasaccess = true;
			}
			else
			{
				$hasaccess = false;
			}
		}
		else
		{
			$hasaccess = false;
		}
	}


	$hasaccess = apply_filters("bwlmslevel_has_membership_access_filter", $hasaccess, $mypost, $myuser, $post_membership_levels);

	if(has_filter("bwlmslevel_has_membership_access_filter_" . $mypost->post_type))
		$hasaccess = apply_filters("bwlmslevel_has_membership_access_filter_" . $mypost->post_type, $hasaccess, $mypost, $myuser, $post_membership_levels);

	if($return_membership_levels)
		return array($hasaccess, $post_membership_levels_ids, $post_membership_levels_names);
	else
		return $hasaccess;
}

function bwlmslevel_search_filter($query)
{
    global $current_user, $wpdb, $bwlmslevel_pages;
			
    if(!$query->is_admin && $query->is_search && empty($query->query['post_parent']))
    {
        if(empty($query->query_vars['post_parent']))		
			$query->set('post__not_in', $bwlmslevel_pages );

		$query->set('post__not_in', $bwlmslevel_pages ); 	
    }


	if(!$query->is_admin && 
	   !$query->is_singular && 
	   empty($query->query['post_parent']) &&
	   (
		empty($query->query_vars['post_type']) || 
		in_array($query->query_vars['post_type'], apply_filters('bwlmslevel_search_filter_post_types', array("page", "post")))
	   )	   
	)
    {		
        $levels = bwlmslevel_getMembershipLevelsForUser($current_user->ID);
        $my_pages = array();

        if($levels) {
            foreach($levels as $key => $level) {
                $sql = "SELECT page_id FROM $wpdb->bwlmslevel_memberships_pages WHERE membership_id=" . $current_user->membership_level->ID;
                $member_pages = $wpdb->get_col($sql);
                $my_pages = array_unique(array_merge($my_pages, $member_pages));
            }
        }

        if(!empty($my_pages))
			$sql = "SELECT page_id FROM $wpdb->bwlmslevel_memberships_pages WHERE page_id NOT IN(" . implode(',', $my_pages) . ")";
		else
			$sql = "SELECT page_id FROM $wpdb->bwlmslevel_memberships_pages";
        $hidden_page_ids = array_values(array_unique($wpdb->get_col($sql)));						
		
        if($hidden_page_ids)
		{
			if(empty($query->query_vars['post_parent']))		
				$query->set('post__not_in', $hidden_page_ids);
		}
				
        global $bwlmslevel_my_cats;
		$bwlmslevel_my_cats = array();

        if($levels) {
            foreach($levels as $key => $level) {
                $member_cats = bwlmslevel_getMembershipCategories($level->id);
                $bwlmslevel_my_cats = array_unique(array_merge($bwlmslevel_my_cats, $member_cats));
            }
        }
		
        if(!empty($bwlmslevel_my_cats))
			$sql = "SELECT category_id FROM $wpdb->bwlmslevel_memberships_categories WHERE category_id NOT IN(" . implode(',', $bwlmslevel_my_cats) . ")";
		else
			$sql = "SELECT category_id FROM $wpdb->bwlmslevel_memberships_categories";
					
        $hidden_cat_ids = array_values(array_unique($wpdb->get_col($sql)));
				

        if($hidden_cat_ids)
		{			
            $query->set('category__not_in', $hidden_cat_ids);

			add_action('posts_where', 'bwlmslevel_posts_where_unhide_cats');
		}
    }

    return $query;
}
$filterqueries = bwlmslevel_getOption("filterqueries");
if(!empty($filterqueries))
    add_filter( 'pre_get_posts', 'bwlmslevel_search_filter' );
  
function bwlmslevel_posts_where_unhide_cats($where)
{
	global $bwlmslevel_my_cats, $wpdb;
		
	if(!empty($where) && !empty($bwlmslevel_my_cats))
	{
		$pattern = "/$wpdb->posts.ID NOT IN \(\s*SELECT object_id\s*FROM dev_term_relationships\s*WHERE term_taxonomy_id IN \((.*)\)\s*\)/";
		$replacement = $wpdb->posts . '.ID NOT IN (
						SELECT tr1.object_id
						FROM ' . $wpdb->term_relationships . ' tr1
							LEFT JOIN ' . $wpdb->term_relationships . ' tr2 ON tr1.object_id = tr2.object_id AND tr2.term_taxonomy_id IN(' . implode($bwlmslevel_my_cats) . ') 
						WHERE tr1.term_taxonomy_id IN(${1}) AND tr2.term_taxonomy_id IS NULL ) ';	
		$where = preg_replace($pattern, $replacement, $where);
	}
			
	remove_action('posts_where', 'bwlmslevel_posts_where_unhide_cats');
		
	return $where;
}
  
function bwlmslevel_membership_content_filter($content, $skipcheck = false)
{	
	global $post, $current_user;

	if(!$skipcheck)
	{
		$hasaccess = bwlmslevel_has_membership_access(NULL, NULL, true);
		if(is_array($hasaccess))
		{
			$post_membership_levels_ids = $hasaccess[1];
			$post_membership_levels_names = $hasaccess[2];
			$hasaccess = $hasaccess[0];
		}
	}

	if($hasaccess)
	{
		return $content;
	}
	else
	{
		if(bwlmslevel_getOption("showexcerpts"))
		{			
			global $post;
			if($post->post_excerpt)
			{								
				$content = wpautop($post->post_excerpt);
			}
			elseif(strpos($content, "<span id=\"more-" . $post->ID . "\"></span>") !== false)
			{				
				$pos = strpos($content, "<span id=\"more-" . $post->ID . "\"></span>");
				$content = wpautop(substr($content, 0, $pos));
			}
			elseif(strpos($content, 'class="more-link">') !== false)
			{
				$content = preg_replace("/\<a.*class\=\"more\-link\".*\>.*\<\/a\>/", "", $content);
			}
			else
			{
				$content = strip_shortcodes( $content );
				$content = str_replace(']]>', ']]&gt;', $content);
				$content = strip_tags($content);
				$excerpt_length = apply_filters('excerpt_length', 55);
				$words = preg_split("/[\n\r\t ]+/", $content, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
				if ( count($words) > $excerpt_length ) {
					array_pop($words);
					$content = implode(' ', $words);
					$content = $content . "... ";
				} else {
					$content = implode(' ', $words) . "... ";
				}

				$content = wpautop($content);
			}
		}
		else
		{
			$content = "";
		}

		if(empty($post_membership_levels_ids))
			$post_membership_levels_ids = array();

		if(empty($post_membership_levels_names))
			$post_membership_levels_names = array();

        if(!apply_filters("bwlmslevel_membership_content_filter_disallowed_levels", false, $post_membership_levels_ids, $post_membership_levels_names))
        {
            foreach($post_membership_levels_ids as $key=>$id)
            {
                $level_obj = bwlmslevel_getLevel($id);
                if(!$level_obj->allow_signups)
                {
                    unset($post_membership_levels_ids[$key]);
                    unset($post_membership_levels_names[$key]);
                }
            }
        }

		$bwlmslevel_content_message_pre = '<div class="bwlmslevel_content_message">';
		$bwlmslevel_content_message_post = '</div>';

		$sr_search = array("!!levels!!", "!!referrer!!");
		$sr_replace = array(bwlmslevel_implodeToEnglish($post_membership_levels_names), urlencode(site_url($_SERVER['REQUEST_URI'])));

		if(is_feed())
		{
			$newcontent = apply_filters("bwlmslevel_rss_text_filter", stripslashes(bwlmslevel_getOption("rsstext")));
			$content .= $bwlmslevel_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $bwlmslevel_content_message_post;
		}
		elseif($current_user->ID)
		{
			$newcontent = apply_filters("bwlmslevel_non_member_text_filter", stripslashes(bwlmslevel_getOption("nonmembertext")));
			$content .= $bwlmslevel_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $bwlmslevel_content_message_post;
		}
		else
		{
			$newcontent = apply_filters("bwlmslevel_not_logged_in_text_filter", stripslashes(bwlmslevel_getOption("notloggedintext")));
			$content .= $bwlmslevel_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $bwlmslevel_content_message_post;
		}
	}

	return $content;
}
add_filter('the_content', 'bwlmslevel_membership_content_filter', 5);
add_filter('the_content_rss', 'bwlmslevel_membership_content_filter', 5);
add_filter('comment_text_rss', 'bwlmslevel_membership_content_filter', 5);


function bwlmslevel_membership_excerpt_filter($content, $skipcheck = false)
{		
	remove_filter('the_content', 'bwlmslevel_membership_content_filter', 5);	
	$content = bwlmslevel_membership_content_filter($content, $skipcheck);
	add_filter('the_content', 'bwlmslevel_membership_content_filter', 5);
	
	return $content;
}
function bwlmslevel_membership_get_excerpt_filter_start($content, $skipcheck = false)
{	
	remove_filter('the_content', 'bwlmslevel_membership_content_filter', 5);		
	return $content;
}
function bwlmslevel_membership_get_excerpt_filter_end($content, $skipcheck = false)
{	
	add_filter('the_content', 'bwlmslevel_membership_content_filter', 5);		
	return $content;
}
add_filter('the_excerpt', 'bwlmslevel_membership_excerpt_filter', 15);
add_filter('get_the_excerpt', 'bwlmslevel_membership_get_excerpt_filter_start', 1);
add_filter('get_the_excerpt', 'bwlmslevel_membership_get_excerpt_filter_end', 100);

function bwlmslevel_comments_filter($comments, $post_id = NULL)
{
	global $post, $wpdb, $current_user;
	if(!$post_id && isset($post->ID))
		$post_id = $post->ID;

	if(!$comments)
		return $comments;	

	global $post, $current_user;

	$hasaccess = bwlmslevel_has_membership_access(NULL, NULL, true);
	if(is_array($hasaccess))
	{
		$post_membership_levels_ids = $hasaccess[1];
		$post_membership_levels_names = $hasaccess[2];
		$hasaccess = $hasaccess[0];
	}

	if($hasaccess)
	{
		return $comments;
	}
	else
	{
		if(!$post_membership_levels_ids)
			$post_membership_levels_ids = array();

		if(!$post_membership_levels_names)
			$post_membership_levels_names = array();

		if(is_feed())
		{
			if(is_array($comments))
				return array();
			else
				return false;
		}
		elseif($current_user->ID)
		{
			if(is_array($comments))
				return array();
			else
				return false;
		}
		else
		{
			if(is_array($comments))
				return array();
			else
				return false;
		}
	}

	return $comments;
}
add_filter("comments_array", "bwlmslevel_comments_filter");
add_filter("comments_open", "bwlmslevel_comments_filter");

function bwlmslevel_hide_pages_redirect()
{
	global $post;

	if(!is_admin() && !empty($post->ID))
	{
		if($post->post_type == "attachment")
		{
			if(!bwlmslevel_has_membership_access($post->ID))
			{
				wp_redirect(bwlmslevel_url("levels"));
				exit;
			}
		}
	}
}
add_action('wp', 'bwlmslevel_hide_pages_redirect');


function bwlmslevel_post_classes( $classes, $class, $post_id ) {	
	
	$post = get_post($post_id);
	
	if(empty($post))
		return $classes;
	
	$post_levels = array();
	$post_levels = bwlmslevel_has_membership_access($post->ID,NULL,true);
	
	if(!empty($post_levels))
	{
		if(!empty($post_levels[1]))
		{
			$classes[] = 'bwlmslevel-level-required';
			foreach($post_levels[1] as $post_level)
				$classes[] = 'bwlmslevel-level-' . $post_level[0];
		}
		if(!empty($post_levels[0]) && $post_levels[0] == true)
			$classes[] = 'bwlmslevel-has-access';
	}
	return $classes;
}
add_filter( 'post_class', 'bwlmslevel_post_classes', 10, 3 );

function bwlmslevel_body_classes( $classes ) {	
	
	$post = get_queried_object();
	
	if(empty($post) || !is_singular())
		return $classes;
	
	$post_levels = array();
	$post_levels = bwlmslevel_has_membership_access($post->ID,NULL,true);
	
	if(!empty($post_levels))
	{
		if(!empty($post_levels[1]))
		{
			$classes[] = 'bwlmslevel-body-level-required';
			foreach($post_levels[1] as $post_level)
				$classes[] = 'bwlmslevel-body-level-' . $post_level[0];
		}
		if(!empty($post_levels[0]) && $post_levels[0] == true)
			$classes[] = 'bwlmslevel-body-has-access';
	}
	return $classes;
}
add_filter( 'body_class', 'bwlmslevel_body_classes' );