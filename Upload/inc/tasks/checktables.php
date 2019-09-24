<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright ©2009 DaddyoBB Group, All Rights Reserved - Modifications are copyright © 2008  DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:38 19.12.2008
  */

function task_checktables($task)
{
	global $db, $daddyobb, $lang;
	
	@set_time_limit(0);
	
	$ok = array(
		"The storage engine for the table doesn't support check",
		"Table is already up to date",
		"OK"
	);
	
	$comma = "";
	$tables_list = "";
	$repaired = "";

	$tables = $db->list_tables($daddyobb->config['database']['database'], $daddyobb->config['database']['table_prefix']);
	foreach($tables as $key => $table)
	{
		$tables_list .= "{$comma}{$table} ";		
		$comma = ",";
	}
	
	if($tables_list)
	{
		$query = $db->query("CHECK TABLE {$tables_list}CHANGED;");
		while($table = $db->fetch_array($query))
		{
			if(!in_array($table['Msg_text'], $ok))
			{
				if($table['Table'] != $daddyobb->config['database']['database'].".".TABLE_PREFIX."settings" && $setting_done != true)
				{
					$boardclosed = $daddyobb->settings['boardclosed'];
					$boardclosed_reason = $daddyobb->settings['boardclosed_reason'];
					
					$db->update_query("settings", array('value' => 1), "name='boardclosed'", 1);
					$db->update_query("settings", array('value' => $lang->error_database_repair), "name='boardclosed_reason'", 1);
					rebuild_settings();
					
					$setting_done = true;
				}
				
				$db->query("REPAIR TABLE {$table['Table']}");
				$repaired[] = $table['Table'];
			}
		}
		
		if($table['Table'] != $daddyobb->config['database']['table_prefix'].".".TABLE_PREFIX."settings" && $setting_done == true)
		{
			$db->update_query("settings", array('value' => $boardclosed), "name='boardclosed'", 1);
			$db->update_query("settings", array('value' => $boardclosed_reason), "name='boardclosed_reason'", 1);
					
			rebuild_settings();
		}
		
	}
	
	if(!empty($repaired))
	{
		add_task_log($task, $lang->sprintf($lang->task_checktables_ran_found, implode(', ', $repaired)));
	}
	else
	{
		add_task_log($task, $lang->task_checktables_ran);
	}
}
?>