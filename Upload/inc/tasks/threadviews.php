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

function task_threadviews($task)
{
	global $daddyobb, $db, $lang;
	
	$threadviews = array();

	if($daddyobb->settings['delayedthreadviews'] != 1)
	{
		return;
	}

	// Update thread views
	$query = $db->query("
		SELECT tid, COUNT(tid) AS views
		FROM ".TABLE_PREFIX."threadviews
		GROUP BY tid
	");
	while($threadview = $db->fetch_array($query))
	{
		$db->write_query("UPDATE ".TABLE_PREFIX."threads SET views=views+{$threadview['views']} WHERE tid='{$threadview['tid']}' LIMIT 1");
	}
	
	$db->write_query("TRUNCATE TABLE ".TABLE_PREFIX."threadviews");
	
	add_task_log($task, $lang->task_threadviews_ran);
}
?>