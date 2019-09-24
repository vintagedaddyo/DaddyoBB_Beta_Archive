<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:06 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'managegroup.php');

require_once "./global.php";

// Load language files
$lang->load("managegroup");

$gid = $daddyobb->input['gid'] = intval($daddyobb->input['gid']);
$usergroup = $groupscache[$daddyobb->input['gid']];
if(!$usergroup['gid'])
{
	error($lang->invalid_group);
}
$lang->nav_group_management = $lang->sprintf($lang->nav_group_management, $usergroup['title']);
add_breadcrumb($lang->nav_group_memberships, "usercp.php?action=usergroups");
add_breadcrumb($lang->nav_group_management, "managegroup.php?gid=$gid");

if($daddyobb->input['action'] == "joinrequests")
{
	add_breadcrumb($lang->nav_join_requests);
}

// Check that this user is actually a leader of this group
$query = $db->simple_select("groupleaders", "*", "uid='{$daddyobb->user['uid']}' AND gid='{$gid}'");
$groupleader = $db->fetch_array($query);
if(!$groupleader['uid'])
{
	error($lang->not_leader_of_this_group);
}

if($daddyobb->input['action'] == "do_add" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	if($groupleader['canmanagemembers'] == 0)
	{
		error_no_permission();
	}
	$query = $db->simple_select("users", "uid, additionalgroups, usergroup", "username = '".$db->escape_string($daddyobb->input['username'])."'", array("limit" => 1));
	$user = $db->fetch_array($query);
	if($user['uid'])
	{
		$additionalgroups = explode(',', $user['additionalgroups']);
		if ($user['usergroup'] != $gid && !in_array($gid, $additionalgroups))
		{
			join_usergroup($user['uid'], $gid);
			redirect("managegroup.php?gid=".$gid, $lang->user_added);
		}
		else 
		{
			error($lang->error_alreadyingroup);
		}
	}
	else
	{
		error($lang->error_invalidusername);
	}
}
elseif($daddyobb->input['action'] == "do_joinrequests" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	if($groupleader['canmanagerequests'] == 0)
	{
		error_no_permission();
	}

	$plugins->run_hooks("managegroup_do_joinrequests_start");

	if(is_array($daddyobb->input['request']))
	{
		foreach($daddyobb->input['request'] as $uid => $what)
		{
			if($what == "accept")
			{
				join_usergroup($uid, $gid);
				$uidin[] = intval($uid);
			}
			elseif($what == "decline")
			{
				$uidin[] = intval($uid);
			}
		}
	}
	if(is_array($uidin))
	{
		$uids = implode(",", $uidin);
		$db->delete_query("joinrequests", "uid IN ({$uids}) AND gid='{$gid}'");
	}

	$plugins->run_hooks("managegroup_do_joinrequests_end");

	redirect("usercp.php?action=usergroups", $lang->join_requests_moderated);
}
elseif($daddyobb->input['action'] == "joinrequests")
{
	$users = "";
	$plugins->run_hooks("managegroup_joinrequests_start");

	$query = $db->query("
		SELECT j.*, u.uid, u.username, u.postnum, u.regdate
		FROM ".TABLE_PREFIX."joinrequests j
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=j.uid)
		WHERE j.gid='".$daddyobb->input['gid']."'
		ORDER BY u.username ASC
	");
	while($user = $db->fetch_array($query))
	{
		$user['reason'] = htmlspecialchars_uni($user['reason']);
		$altbg = alt_trow();
		$regdate = my_date($daddyobb->settings['dateformat'], $user['regdate']);
		$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
		eval("\$users .= \"".$templates->get("managegroup_joinrequests_request")."\";");
	}
	if(!$users)
	{
		error($lang->no_requests);
	}
	$lang->join_requests = $lang->sprintf($lang->join_requests_title,htmlspecialchars_uni($usergroup['title']));

	$plugins->run_hooks("managegroup_joinrequests_end");

	eval("\$joinrequests = \"".$templates->get("managegroup_joinrequests")."\";");
	output_page($joinrequests);
}
elseif($daddyobb->input['action'] == "do_manageusers" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	if($groupleader['canmanagemembers'] == 0)
	{
		error_no_permission();
	}

	$plugins->run_hooks("managegroup_do_manageusers_start");

	if(is_array($daddyobb->input['removeuser']))
	{
		foreach($daddyobb->input['removeuser'] as $uid)
		{
			leave_usergroup($uid, $daddyobb->input['gid']);
		}
	}

	$plugins->run_hooks("managegroup_do_manageusers_end");

	redirect("usercp.php?action=usergroups", $lang->users_removed);
}
else
{
	$plugins->run_hooks("managegroup_start");

	$lang->members_of = $lang->sprintf($lang->members_of, $usergroup['title']);
	$lang->add_member = $lang->sprintf($lang->add_member, $usergroup['title']);
	if($usergroup['type'] == 4)
	{
		$query = $db->simple_select("joinrequests", "COUNT(*) AS req", "gid='".$daddyobb->input['gid']."'");
		$numrequests = $db->fetch_array($query);
		if($numrequests['req'])
		{
			$lang->num_requests_pending = $lang->sprintf($lang->num_requests_pending, $numrequests['req']);
			eval("\$joinrequests = \"".$templates->get("managegroup_requestnote")."\";");
		}
		$usergrouptype = $lang->group_public_moderated;
	}
	elseif($usergroup['type'] == 3)
	{
		$usergrouptype = $lang->group_public_not_moderated;
	}
	elseif($usergroup['type'] == 2)
	{
		$usergrouptype = $lang->group_private;
	}
	else
	{
		$usergrouptype = $lang->group_default;
	}
		

	switch($db->type)
	{
		case "pgsql":
		case "sqlite3":
		case "sqlite2":
			$query = $db->simple_select("users", "*", "','||additionalgroups||',' LIKE '%,".$daddyobb->input['gid'].",%' OR usergroup='".$daddyobb->input['gid']."'", array('order_by' => 'username'));
			break;
		default:
			$query = $db->simple_select("users", "*", "CONCAT(',',additionalgroups,',') LIKE '%,".$daddyobb->input['gid'].",%' OR usergroup='".$daddyobb->input['gid']."'", array('order_by' => 'username'));
	}
	
	$numusers = $db->num_rows($query);
	/*if(!$numusers && !$numrequests)
	{
		error($lang->group_no_members);
	}*/
	$perpage = $daddyobb->settings['membersperpage'];
	if($page && $page > 0)
	{
		$start = ($page-1) *$perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$multipage = multipage($numusers, $perpage, $page, "managegroup.php?gid=".$daddyobb->input['gid']);
	$users = "";
	while($user = $db->fetch_array($query))
	{
		$altbg = alt_trow();
		$regdate = my_date($daddyobb->settings['dateformat'].", ".$daddyobb->settings['timeformat'], $user['regdate']);
		$post = $user;
		$sendpm = $email = '';
		if($daddyobb->settings['enablepms'] == 1 && $post['receivepms'] != 0 && $daddyobb->usergroup['cansendpms'] == 1 && my_strpos(",".$post['ignorelist'].",", ",".$daddyobb->user['uid'].",") === false)
		{
			eval("\$sendpm = \"".$templates->get("postbit_pm")."\";");
		}
		
		if($user['hideemail'] != 1)
		{
			eval("\$email = \"".$templates->get("postbit_email")."\";");
		}
		else
		{
			$email = '';
		}
		$query1 = $db->simple_select("groupleaders", "uid", "uid='{$user['uid']}' AND gid='{$gid}'");
		$isleader = $db->fetch_array($query1);
		$user['username'] = format_name($user['username'], $user['usergroup']);
		$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
		if($isleader['uid'])
		{
			$leader = $lang->leader;
		}
		else
		{
			$leader = '';
		}

		// Checkbox for user management - only if current user is allowed
		$checkbox = '';
		if($groupleader['canmanagemembers'] == 1)
		{
			eval("\$checkbox = \"".$templates->get("managegroup_user_checkbox")."\";");
		}

		eval("\$users .= \"".$templates->get("managegroup_user")."\";");
	}

	$add_user = '';
	$remove_users = '';
	if($groupleader['canmanagemembers'] == 1)
	{
		eval("\$add_user = \"".$templates->get("managegroup_adduser")."\";");
		eval("\$remove_users = \"".$templates->get("managegroup_removeusers")."\";");
	}

	$plugins->run_hooks("managegroup_end");

	eval("\$manageusers = \"".$templates->get("managegroup")."\";");
	output_page($manageusers);
}
?>