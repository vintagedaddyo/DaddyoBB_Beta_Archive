<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:15 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

function forum_meta()
{
	global $page, $lang, $plugins;
	
	$sub_menu = array();
	$sub_menu['10'] = array("id" => "management", "title" => $lang->forum_management, "link" => "index.php?module=forum/management");
	$sub_menu['20'] = array("id" => "announcements", "title" => $lang->forum_announcements, "link" => "index.php?module=forum/announcements");
	$sub_menu['30'] = array("id" => "moderation_queue", "title" => $lang->moderation_queue, "link" => "index.php?module=forum/moderation_queue");
	$sub_menu['40'] = array("id" => "attachments", "title" => $lang->attachments, "link" => "index.php?module=forum/attachments");
	
	$plugins->run_hooks_by_ref("admin_forum_menu", $sub_menu);

	$page->add_menu_item($lang->forums_and_posts, "forum", "index.php?module=forum", 20, $sub_menu);

	return true;
}

function forum_action_handler($action)
{
	global $page, $lang, $plugins;
	
	$page->active_module = "forum";
	
	$actions = array(
		'moderation_queue' => array('active' => 'moderation_queue', 'file' => 'moderation_queue.php'),
		'announcements' => array('active' => 'announcements', 'file' => 'announcements.php'),
		'attachments' => array('active' => 'attachments', 'file' => 'attachments.php'),
		'management' => array('active' => 'management', 'file' => 'management.php')
	);
	
	$plugins->run_hooks_by_ref("admin_forum_action_handler", $actions);
	
	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "management";
		return "management.php";
	}
}

function forum_admin_permissions()
{
	global $lang, $plugins;
	
	$admin_permissions = array(
		"management" => $lang->can_manage_forums,
		"announcements" => $lang->can_manage_forum_announcements,
		"moderation_queue" => $lang->can_moderate,
		"attachments" => $lang->can_manage_attachments,
	);
	
	$plugins->run_hooks_by_ref("admin_forum_permissions", $admin_permissions);
	
	return array("name" => $lang->forums_and_posts, "permissions" => $admin_permissions, "disporder" => 20);
}

?>