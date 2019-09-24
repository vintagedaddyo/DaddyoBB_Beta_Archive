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

function task_logcleanup($task)
{
	global $daddyobb, $db, $lang;

	// Clear out old admin logs
	if($daddyobb->config['log_pruning']['admin_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['admin_logs'];
		$db->delete_query("adminlog", "dateline<'{$cut}'");
	}

	// Clear out old moderator logs
	if($daddyobb->config['log_pruning']['mod_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['mod_logs'];
		$db->delete_query("moderatorlog", "dateline<'{$cut}'");
	}

	// Clear out old task logs
	if($daddyobb->config['log_pruning']['task_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['task_logs'];
		$db->delete_query("tasklog", "dateline<'{$cut}'");
	}

	// Clear out old mail error logs
	if($daddyobb->config['log_pruning']['mail_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['mail_logs'];
		$db->delete_query("mailerrors", "dateline<'{$cut}'");
	}

	// Clear out old user mail logs
	if($daddyobb->config['log_pruning']['user_mail_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['user_mail_logs'];
		$db->delete_query("maillogs", "dateline<'{$cut}'");
	}

	// Clear out old promotion logs
	if($daddyobb->config['log_pruning']['promotion_logs'] > 0)
	{
		$cut = TIME_NOW-60*60*24*$daddyobb->config['log_pruning']['promotion_logs'];
		$db->delete_query("promotionlogs", "dateline<'{$cut}'");
	}
	
	add_task_log($task, $lang->task_logcleanup_ran);
}
?>