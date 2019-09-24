<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:06 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'memberlist.php');

$templatelist = "memberlist,memberlist_member,memberlist_search,memberlist_user,memberlist_user_groupimage,memberlist_user_avatar";
$templatelist .= ",postbit_www,postbit_email,multipage_nextpage,multipage_page_current,multipage_page,multipage_start,multipage_end,multipage";
require_once "./global.php";

// Load global language phrases
$lang->load("memberlist");

if($daddyobb->settings['enablememberlist'] == 0)
{
	error($lang->memberlist_disabled);
}

$plugins->run_hooks("memberlist_start");

add_breadcrumb($lang->nav_memberlist);

if($daddyobb->usergroup['canviewmemberlist'] == 0)
{
	error_no_permission();
}

// Showing advanced search page?
if($daddyobb->input['action'] == "search")
{
	eval("\$search_page = \"".$templates->get("memberlist_search")."\";");
	$plugins->run_hooks("memberlist_search");
	output_page($search_page);	
}
else
{
	$search_url = '';
	
	// Incoming sort field?
	if($daddyobb->input['sort'])
	{
		$daddyobb->input['sort'] = strtolower($daddyobb->input['sort']);
	}
	else
	{
		$daddyobb->input['sort'] = $daddyobb->settings['default_memberlist_sortby'];
	}
	
	switch($daddyobb->input['sort'])
	{
		case "regdate":
			$sort_field = "u.regdate";
			break;
		case "lastvisit":
			$sort_field = "u.lastactive";
			break;
		case "reputation":
			$sort_field = "u.reputation";
			break;
		case "postnum":
			$sort_field = "u.postnum";
			break;
		default:
			$sort_field = "u.username";
			$daddyobb->input['sort'] = 'username';
			break;
	}
	$sort_selected[$daddyobb->input['sort']] = " selected=\"selected\"";
	
	// Incoming sort order?
	if($daddyobb->input['order'])
	{
		$daddyobb->input['order'] = strtolower($daddyobb->input['order']);
	}
	else
	{
		$daddyobb->input['order'] = strtolower($daddyobb->settings['default_memberlist_order']);
	}
	
	if($daddyobb->input['order'] == "asc" || (!$daddyobb->input['order'] && $daddyobb->input['sort'] == 'username'))
	{
		$sort_order = "ASC";
		$daddyobb->input['order'] = "asc";
	}
	else
	{
		$sort_order = "DESC";
		$daddyobb->input['order'] = "desc";
	}
	$order_check[$daddyobb->input['order']] = " checked=\"checked\"";
	
	// Incoming results per page?
	$daddyobb->input['perpage'] = intval($daddyobb->input['perpage']);
	if($daddyobb->input['perpage'] > 0 && $daddyobb->input['perpage'] <= 500)
	{
		$per_page = $daddyobb->input['perpage'];
	}
	else
	{
		$per_page = $daddyobb->input['perpage'] = intval($daddyobb->settings['membersperpage']);	
	}
	
	$search_query = '1=1';
	$search_url = "memberlist.php?sort={$daddyobb->input['sort']}&order={$daddyobb->input['order']}&perpage={$daddyobb->input['perpage']}";
	
	// Limiting results to a certain letter
	if($daddyobb->input['letter'] != "")
	{
		if($daddyobb->input['letter'] == '#')
		{
			$search_query .= " AND u.username NOT REGEXP('^[a-zA-Z]')";
		}
		else
		{
			$letter = chr(ord($daddyobb->input['letter']));
			$search_query .= " AND u.username LIKE '".$db->escape_string($letter)."%'";
		}
		$search_url .= "&letter={$letter}";
	}

	// Searching for a matching username
	$search_username = htmlspecialchars_uni($daddyobb->input['username']);
	if(trim($daddyobb->input['username']))
	{
		$username_like_query = $db->escape_string_like($daddyobb->input['username']);
		// Name begins with
		if($daddyobb->input['username_match'] == "begins")
		{
			$search_query .= " AND u.username LIKE '".$username_like_query."%'";
			$search_url .= "&username_match=begins";
		}
		// Just contains
		else
		{
			$search_query .= " AND u.username LIKE '%".$username_like_query."%'";
		}
		$search_url .= "&username=".urlencode($daddyobb->input['username']);
	}

	// Website contains
	$search_website = htmlspecialchars_uni($daddyobb->input['website']);
	if(trim($daddyobb->input['website']))
	{
		$search_query .= " AND u.website LIKE '%".$db->escape_string_like($daddyobb->input['website'])."%'";
		$search_url .= "&website=".urlencode($daddyobb->input['website']);
	}

	// AIM Identity
	if(trim($daddyobb->input['aim']))
	{
		$search_query .= " AND u.aim LIKE '%".$db->escape_string_like($daddyobb->input['aim'])."%'";
		$search_url .= "&aim=".urlencode($daddyobb->input['aim']);
	}

	// ICQ Number
	if(trim($daddyobb->input['icq']))
	{
		$search_query .= " AND u.icq LIKE '%".$db->escape_string_like($daddyobb->input['icq'])."%'";
		$search_url .= "&icq=".urlencode($daddyobb->input['icq']);
	}

	// MSN/Windows Live Messenger address
	if(trim($daddyobb->input['msn']))
	{
		$search_query .= " AND u.msn LIKE '%".$db->escape_string_like($daddyobb->input['msn'])."%'";
		$search_url .= "&msn=".urlencode($daddyobb->input['msn']);
	}

	// Yahoo! Messenger address
	if(trim($daddyobb->input['yahoo']))
	{
		$search_query .= " AND u.yahoo LIKE '%".$db->escape_string_like($daddyobb->input['yahoo'])."%'";
		$search_url .= "&yahoo=".urlencode($daddyobb->input['yahoo']);
	}

	$query = $db->simple_select("users u", "COUNT(*) AS users", "{$search_query}");
	$num_users = $db->fetch_field($query, "users");

	$page = intval($daddyobb->input['page']);
	if($page && $page > 0)
	{
		$start = ($page - 1) * $per_page;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$search_url = htmlspecialchars_uni($search_url);
	$multipage = multipage($num_users, $per_page, $page, $search_url);
	
	// Cache a few things
	$usergroups_cache = $cache->read('usergroups');
	$query = $db->simple_select("usertitles", "*", "", array('order_by' => 'posts', 'order_dir' => 'DESC'));
	while($usertitle = $db->fetch_array($query))
	{
		$usertitles_cache[$usertitle['posts']] = $usertitle;
	}
	$query = $db->query("
		SELECT u.*, f.*
		FROM ".TABLE_PREFIX."users u
		LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
		WHERE {$search_query}
		ORDER BY {$sort_field} {$sort_order}
		LIMIT {$start}, {$per_page}
	");
	while($user = $db->fetch_array($query))
	{
		$plugins->run_hooks("memberlist_user");

		$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);

		$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
		
		// Get the display usergroup
		if(!$user['displaygroup'])
		{
			$user['displaygroup'] = $user['usergroup'];
		}
		$usergroup = $usergroups_cache[$user['displaygroup']];
		
		// Work out the usergroup/title stuff
		if(!empty($usergroup['image']))
		{
			if(!empty($daddyobb->user['language']))
			{
				$language = $daddyobb->user['language'];
			}
			else
			{
				$language = $daddyobb->settings['bblanguage'];
			}
			$usergroup['image'] = str_replace("{lang}", $language, $usergroup['image']);
			$usergroup['image'] = str_replace("{theme}", $theme['imgdir'], $usergroup['image']);
			eval("\$usergroup['groupimage'] = \"".$templates->get("memberlist_user_groupimage")."\";");
		}

		$has_custom_title = 0;
		if(trim($user['usertitle']) != "")
		{
			$has_custom_title = 1;
		}

		if($usergroup['usertitle'] != "" && !$has_custom_title)
		{
			$user['usertitle'] = $usergroup['usertitle'];
		}
		elseif(is_array($usertitles_cache) && !$usergroup['usertitle'])
		{
			foreach($usertitles_cache as $posts => $titleinfo)
			{
				if($user['postnum'] >= $posts)
				{
					if(!$has_custom_title)
					{
						$user['usertitle'] = $titleinfo['title'];
					}
					$user['stars'] = $titleinfo['stars'];
					$user['starimage'] = $titleinfo['starimage'];
					break;
				}
			}
		}

		if($usergroup['stars'])
		{
			$user['stars'] = $usergroup['stars'];
		}

		if(!$user['starimage'])
		{
			$user['starimage'] = $usergroup['starimage'];
		}
		$user['starimage'] = str_replace("{theme}", $theme['imgdir'], $user['starimage']);

		for($i = 0; $i < $user['stars']; ++$i)
		{
			$user['userstars'] .= "<img src=\"".$user['starimage']."\" border=\"0\" alt=\"*\" />";
		}

		if($user['userstars'] && $usergroup['groupimage'])
		{
			$user['userstars'] = "<br />".$user['userstars'];
		}
	
		// Show avatar
		if($user['avatar'] != '')
		{
			$user['avatar'] = htmlspecialchars_uni($user['avatar']);
			$avatar_dimensions = explode("|", $user['avatardimensions']);
			
			if($avatar_dimensions[0] && $avatar_dimensions[1])
			{
				list($max_width, $max_height) = explode("x", my_strtolower($daddyobb->settings['memberlistmaxavatarsize']));
			 	if($avatar_dimensions[0] > $max_width || $avatar_dimensions[1] > $max_height)
				{
					require_once DADDYOBB_ROOT."inc/functions_image.php";
					$scaled_dimensions = scale_image($avatar_dimensions[0], $avatar_dimensions[1], $max_width, $max_height);
					$avatar_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
				}
				else
				{
					$avatar_width_height = "width=\"{$avatar_dimensions[0]}\" height=\"{$avatar_dimensions[1]}\"";	
				}
			}
			
			eval("\$user['avatar'] = \"".$templates->get("memberlist_user_avatar")."\";");
		}
		else
		{
			$user['avatar'] = "";
		}		
		
		$user['regdate'] = my_date($daddyobb->settings['dateformat'], $user['regdate']).", ".my_date($daddyobb->settings['timeformat'], $user['regdate']);
		$user['lastvisit'] = my_date($daddyobb->settings['dateformat'], $user['lastactive']).", ".my_date($daddyobb->settings['timeformat'], $user['lastactive']);;
		$user['postnum'] = my_number_format($user['postnum']);
		$usernum++;
		eval("\$users .= \"".$templates->get("memberlist_user")."\";");
	}
	
	if($usernum<1)
	{
		eval("\$users = \"".$templates->get("memberlist_user")."\";");
	}

	$plugins->run_hooks("memberlist_end");

	eval("\$memberlist = \"".$templates->get("memberlist")."\";");
	output_page($memberlist);
}
?>