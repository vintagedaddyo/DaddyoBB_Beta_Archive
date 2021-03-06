<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:39 19.12.2008
 */

function task_dailycleanup($task)
{
	global $daddyobb, $db, $cache, $lang;
	
	require_once DADDYOBB_ROOT."inc/functions_user.php";

	// Clear out sessions older than 24h
	$cut = TIME_NOW-60*60*24;
	$db->delete_query("sessions", "uid='0' AND time < '{$cut}'");

	// Delete old read topics
	if($daddyobb->settings['threadreadcut'] > 0)
	{
		$cut = TIME_NOW-($daddyobb->settings['threadreadcut']*60*60*24);
		$db->delete_query("threadsread", "dateline < '{$cut}'");
		$db->delete_query("forumsread", "dateline < '{$cut}'");
	}
	
	// Check PMs moved to trash over a week ago & delete them
	$timecut = TIME_NOW-(60*60*24*7);
	$query = $db->simple_select("privatemessages", "pmid, uid, folder", "deletetime<='{$timecut}' AND folder='4'");
	while($pm = $db->fetch_array($query))
	{
		$user_update[$pm['uid']] = $uid;
		$pm_update[] = $pm['pmid'];
	}
	
	if(!empty($pm_update))
	{
		$db->delete_query("privatemessages", "pmid IN(".implode(',', $pm_update).")");
	}
	
	if(!empty($user_update))
	{
		foreach($user_update as $uid)
		{
			update_pm_count($uid);
		}
	}
	
	$cache->update_most_replied_threads();
	$cache->update_most_viewed_threads();
	$cache->update_birthdays();
	
	add_task_log($task, $lang->task_dailycleanup_ran);
}
?>