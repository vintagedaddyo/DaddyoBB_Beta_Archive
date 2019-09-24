<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:39 19.12.2008
 */

function task_usercleanup($task)
{
	global $db, $lang, $cache;

	// Expire any old warnings
	expire_warnings();

	// Expire any post moderation or suspension limits
	$query = $db->simple_select("users", "uid, moderationtime, suspensiontime", "(moderationtime!=0 AND moderationtime<".TIME_NOW.") OR (suspensiontime!=0 AND suspensiontime<".TIME_NOW.")");
	while($user = $db->fetch_array($query))
	{
		$updated_user = array();
		if($user['moderationtime'] != 0 && $user['moderationtime'] < TIME_NOW)
		{
			$updated_user['moderateposts'] = 0;
			$updated_user['moderationtime'] = 0;
		}
		if($user['suspensiontime'] != 0 && $user['suspensiontime'] < TIME_NOW)
		{
			$updated_user['suspendposting'] = 0;
			$updated_user['suspensiontime'] = 0;
		}
		$db->update_query("users", $updated_user, "uid='{$user['uid']}'");
	}

	// Expire bans
	$query = $db->simple_select("banned", "*", "lifted!=0 AND lifted<".TIME_NOW);
	while($ban = $db->fetch_array($query))
	{
		$updated_user = array(
			"usergroup" => $ban['oldgroup'],
			"additionalgroups" => $ban['oldadditionalgroups'],
			"displaygroup" => $ban['olddisplaygroup']
		);
		$db->update_query("users", $updated_user, "uid='{$ban['uid']}'");
		$db->delete_query("banned", "uid='{$ban['uid']}'");
	}
	
	$cache->update_moderators();
	
	add_task_log($task, $lang->task_usercleanup_ran);
}
?>