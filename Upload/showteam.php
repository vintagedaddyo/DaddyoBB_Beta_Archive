<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 19:42 24.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'showteam.php');

$templatelist = 'showteam,showteam_row,showteam_row_mod,postbit_email,postbit_pm';
$templatelist .= ',showteam_usergroup_user,showteam_usergroup,showteam_moderators_mod';
$templatelist .= ',showteam_moderators,showteam_moderators_forum';
require_once "./global.php";

// Load global language phrases
$lang->load('showteam');

add_breadcrumb($lang->nav_showteam);

$plugins->run_hooks('showteam_start');

$usergroups = array();
$moderators = array();
$users = array();

// Fetch the list of groups which are to be shown on the page
$query = $db->simple_select("usergroups", "gid, title, usertitle", "showforumteam=1", array('order_by' => 'disporder'));
while($usergroup = $db->fetch_array($query))
{
	$usergroups[$usergroup['gid']] = $usergroup;
}

if(empty($usergroups))
{
	error($lang->error_noteamstoshow);
}

// Fetch specific forum moderator details
if($usergroups[6]['gid'])
{
	$query = $db->query("
		SELECT m.*, f.name
		FROM ".TABLE_PREFIX."moderators m
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=m.uid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=m.fid)
		WHERE f.active = 1
		ORDER BY u.username
	");
	while($moderator = $db->fetch_array($query))
	{
		$moderators[$moderator['uid']][] = $moderator;
	} 
}

// Now query the users of those specific groups
$groups_in = implode(",", array_keys($usergroups));
$users_in = implode(",", array_keys($moderators));
if(!$groups_in)
{
	$groups_in = 0;
}
if(!$users_in)
{
	$users_in = 0;
}
$forum_permissions = forum_permissions();

$query = $db->simple_select("users", "uid, username, usertitle, lastactive, displaygroup, usergroup, ignorelist, hideemail, receivepms", "displaygroup IN ($groups_in) OR (displaygroup='0' AND usergroup IN ($groups_in)) OR uid IN ($users_in)", array('order_by' => 'username'));
while($user = $db->fetch_array($query))
{
	// If this user is a moderator
	if(isset($moderators[$user['uid']]))
	{
		foreach($moderators[$user['uid']] as $forum)
		{
			if($forum_permissions[$forum['fid']]['canview'] == 1)
			{
				$forum_url = get_forum_link($forum['fid']);
				eval("\$forumlist .= \"".$templates->get("showteam_moderators_forum")."\";");
			}
		}
		$user['forumlist'] = $forumlist;
		$forumlist = '';
		$usergroups[6]['user_list'][$user['uid']] = $user;
	}
	
	if($user['displaygroup'] == '6' || $user['usergroup'] == '6')
	{
		$usergroups[6]['user_list'][$user['uid']] = $user;
	}
	
	// Are they also in another group which is being shown on the list?
	if($user['displaygroup'] != 0)
	{
		$group = $user['displaygroup'];
	}
	else
	{
		$group = $user['usergroup'];
	}
	
	if($usergroups[$group] && $group != 6)
	{
		$usergroups[$group]['user_list'][$user['uid']] = $user;
	}
}

// Now we have all of our user details we can display them.
$grouplist = '';
foreach($usergroups as $usergroup)
{
	// If we have no users - don't show this group
	if(!isset($usergroup['user_list']))
	{
		continue;
	}
	$bgcolor = '';
	foreach($usergroup['user_list'] as $user)
	{
		$timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];
		$onoff = $status = "";
		if($user['lastactive'] >= $timecut)
		{
      $status = 1;
      $onoff = $lang->sprintf($lang->x_is_online, $user['username']);
    }
		else
		{
      $status = 0;
      $onoff = $lang->sprintf($lang->x_is_offline, $user['username']);
		}
		
		$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$user['profilelink'] = get_profile_link($user['uid']);
		// For the postbit templates
		$post['uid'] = $user['uid'];
		$emailcode = $pmcode = '';
		if($user['hideemail'] != 1)
		{
			eval("\$emailcode = \"".$templates->get("postbit_email")."\";");
		}
		
		if($user['receivepms'] != 0 && $daddyobb->settings['enablepms'] != 0 && my_strpos(",".$user['ignorelist'].",", ",".$daddyobb->user['uid'].",") === false)
		{
			eval("\$pmcode = \"".$templates->get("postbit_pm")."\";");
		}

		$bgcolor = alt_trow();
		
    $comma = "";
		// If the current group is a moderator group
		if($usergroup['gid'] == 6 && !empty($user['forumlist']))
		{
			$forumslist = $user['forumlist'];
			eval("\$modrows .= \"".$templates->get("showteam_moderators_mod")."\";");
			$comma = ", ";
		}
		else
		{
			eval("\$usergrouprows .= \"".$templates->get("showteam_usergroup_user")."\";");
		}			
	}
	
	if($usergroup['gid'] == 6 && !empty($user['forumlist']))
	{
		eval("\$grouplist .= \"".$templates->get("showteam_moderators")."\";");
	}
	else
	{
		eval("\$grouplist .= \"".$templates->get("showteam_usergroup")."\";");
	}
	$usergrouprows = '';
}

if(empty($grouplist))
{
	error($lang->error_noteamstoshow);
}

eval("\$showteam = \"".$templates->get("showteam")."\";");
$plugins->run_hooks("showteam_end");
output_page($showteam);
?>