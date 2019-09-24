<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:18 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

require_once DADDYOBB_ROOT."/inc/functions_task.php";

$page->add_breadcrumb_item($lang->task_manager, "index.php?module=tools/tasks");

$plugins->run_hooks("admin_tools_tasks_begin");

/**
 * Validates a string or array of values
 * 
 * @param mixed Comma-separated list or array of values
 * @param int Minimum value
 * @param int Maximum value
 * @param string Set "string" to return in a comma-separated list, or "array" to return in an array
 * @return mixed String or array of valid values OR false if string/array is invalid
 */
function check_time_values($value, $min, $max, $return_type)
{
	// If the values aren't in an array form, make them into an array
	if(!is_array($value))
	{
		// Empty value == *
		if($value === '')
		{
			return ($return_type == 'string') ? '*' : array('*');
		}
		$implode = 1;
		$value = explode(',', $value);
	}
	// If * is in the array, always return with * because it overrides all
	if(in_array('*', $value))
	{
		return ($return_type == 'string') ? '*' : array('*');
	}
	// Validate each value in array
	foreach($value as $time)
	{
		if($time < $min || $time > $max)
		{
			return false;
		}
	}
	// Return based on return type
	if($return_type == 'string')
	{
		$value = implode(',', $value);
	}
	return $value;
}

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_tools_tasks_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!trim($daddyobb->input['description']))
		{
			$errors[] = $lang->error_missing_description;
		}

		if(!file_exists(DADDYOBB_ROOT."inc/tasks/".$daddyobb->input['file'].".php"))
		{
			$errors[] = $lang->error_invalid_task_file;
		}

		$daddyobb->input['minute'] = check_time_values($daddyobb->input['minute'], 0, 59, 'string');
		if($daddyobb->input['minute'] === false)
		{
			$errors[] = $lang->error_invalid_minute;
		}

		$daddyobb->input['hour'] = check_time_values($daddyobb->input['hour'], 0, 59, 'string');
		if($daddyobb->input['hour'] === false)
		{
			$errors[] = $lang->error_invalid_hour;
		}

		if($daddyobb->input['day'] != "*" && $daddyobb->input['day'] != '')
		{
			$daddyobb->input['day'] = check_time_values($daddyobb->input['day'], 1, 31, 'string');
			if($daddyobb->input['day'] === false)
			{
				$errors[] = $lang->error_invalid_day;
			}
			$daddyobb->input['weekday'] = array('*');
		}
		else
		{
			$daddyobb->input['weekday'] = check_time_values($daddyobb->input['weekday'], 0, 6, 'array');
			if($daddyobb->input['weekday'] === false)
			{
				$errors[] = $lang->error_invalid_weekday;
			}
			$daddyobb->input['day'] = '*';
		}

		$daddyobb->input['month'] = check_time_values($daddyobb->input['month'], 1, 12, 'array');
		if($daddyobb->input['month'] === false)
		{
			$errors[] = $lang->error_invalid_month;
		}

		if(!$errors)
		{
			$new_task = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"description" => $db->escape_string($daddyobb->input['description']),
				"file" => $db->escape_string($daddyobb->input['file']),
				"minute" => $db->escape_string($daddyobb->input['minute']),
				"hour" => $db->escape_string($daddyobb->input['hour']),
				"day" => $db->escape_string($daddyobb->input['day']),
				"month" => $db->escape_string(implode(',', $daddyobb->input['month'])),
				"weekday" => $db->escape_string(implode(',', $daddyobb->input['weekday'])),
				"enabled" => intval($daddyobb->input['enabled']),
				"logging" => intval($daddyobb->input['logging'])
			);

			$new_task['nextrun'] = fetch_next_run($new_task);
			$tid = $db->insert_query("tasks", $new_task);
			$cache->update_tasks();
			
			$plugins->run_hooks("admin_tools_tasks_add_commit");

			// Log admin action
			log_admin_action($tid, $daddyobb->input['title']);

			flash_message($lang->success_task_created, 'success');
			admin_redirect("index.php?module=tools/tasks");
		}
	}
	$page->add_breadcrumb_item($lang->add_new_task);
	$page->output_header($lang->scheduled_tasks." - ".$lang->add_new_task);


	$sub_tabs['scheduled_tasks'] = array(
		'title' => $lang->scheduled_tasks,
		'link' => "index.php?module=tools/tasks"
	);

	$sub_tabs['add_task'] = array(
		'title' => $lang->add_new_task,
		'link' => "index.php?module=tools/tasks&amp;action=add",
		'description' => $lang->add_new_task_desc
	);

	$sub_tabs['task_logs'] = array(
		'title' => $lang->view_task_logs,
		'link' => "index.php?module=tools/tasks&amp;action=logs"
	);	

	$page->output_nav_tabs($sub_tabs, 'add_task');
	$form = new Form("index.php?module=tools/tasks&amp;action=add", "post", "add");
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['minute'] = '*';
		$daddyobb->input['hour'] = '*';
		$daddyobb->input['day'] = '*';
		$daddyobb->input['weekday'] = '*';
		$daddyobb->input['month'] = '*';
	}
	$form_container = new FormContainer($lang->add_new_task);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description." <em>*</em>", "", $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');

	$task_list = array();
	$task_files = scandir(DADDYOBB_ROOT."inc/tasks/");
	foreach($task_files as $task_file)
	{
		if(is_file(DADDYOBB_ROOT."inc/tasks/{$task_file}") && get_extension($task_file) == "php")
		{
			$file_id = preg_replace("#\.".get_extension($task_file)."$#i", "$1", $task_file);
			$task_list[$file_id] = $task_file;
		}
	}
	$form_container->output_row($lang->task_file." <em>*</em>", $lang->task_file_desc, $form->generate_select_box("file", $task_list, $daddyobb->input['file'], array('id' => 'file')), 'file');
	$form_container->output_row($lang->time_minutes, $lang->time_minutes_desc, $form->generate_text_box('minute', $daddyobb->input['minute'], array('id' => 'minute')), 'minute');
	$form_container->output_row($lang->time_hours, $lang->time_hours_desc, $form->generate_text_box('hour', $daddyobb->input['hour'], array('id' => 'hour')), 'hour');
	$form_container->output_row($lang->time_days_of_month, $lang->time_days_of_month_desc, $form->generate_text_box('day', $daddyobb->input['day'], array('id' => 'day')), 'day');

	$options = array(
		"*" => $lang->every_weekday,
		"0" => $lang->sunday,
		"1" => $lang->monday,
		"2" => $lang->tuesday,
		"3" => $lang->wednesday,
		"4" => $lang->thursday,
		"5" => $lang->friday,
		"6" => $lang->saturday
	);
	$form_container->output_row($lang->time_weekdays, $lang->time_weekdays_desc, $form->generate_select_box('weekday[]', $options, $daddyobb->input['weekday'], array('id' => 'weekday', 'multiple' => true, 'size' => 8)), 'weekday');

	$options = array(
		"*" => $lang->every_month,
		"1" => $lang->january,
		"2" => $lang->february,
		"3" => $lang->march,
		"4" => $lang->april,
		"5" => $lang->may,
		"6" => $lang->june,
		"7" => $lang->july,
		"8" => $lang->august,
		"9" => $lang->september,
		"10" => $lang->october,
		"11" => $lang->november,
		"12" => $lang->december
	);
	$form_container->output_row($lang->time_months, $lang->time_months_desc, $form->generate_select_box('month[]', $options, $daddyobb->input['month'], array('id' => 'month', 'multiple' => true, 'size' => 13)), 'month');

	$form_container->output_row($lang->enable_logging." <em>*</em>", "", $form->generate_yes_no_radio("logging", $daddyobb->input['logging'], true));
	
	$form_container->output_row($lang->enabled." <em>*</em>", "", $form->generate_yes_no_radio("enabled", $daddyobb->input['enabled'], true));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_task);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_tools_tasks_edit");
	
	$query = $db->simple_select("tasks", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$task = $db->fetch_array($query);

	// Does the task not exist?
	if(!$task['tid'])
	{
		flash_message($lang->error_invalid_task, 'error');
		admin_redirect("index.php?module=tools/tasks");
	}

	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!trim($daddyobb->input['description']))
		{
			$errors[] = $lang->error_missing_description;
		}

		if(!file_exists(DADDYOBB_ROOT."inc/tasks/".$daddyobb->input['file'].".php"))
		{
			$errors[] = $lang->error_invalid_task_file;
		}

		$daddyobb->input['minute'] = check_time_values($daddyobb->input['minute'], 0, 59, 'string');
		if($daddyobb->input['minute'] === false)
		{
			$errors[] = $lang->error_invalid_minute;
		}

		$daddyobb->input['hour'] = check_time_values($daddyobb->input['hour'], 0, 59, 'string');
		if($daddyobb->input['hour'] === false)
		{
			$errors[] = $lang->error_invalid_hour;
		}

		if($daddyobb->input['day'] != "*" && $daddyobb->input['day'] != '')
		{
			$daddyobb->input['day'] = check_time_values($daddyobb->input['day'], 1, 31, 'string');
			if($daddyobb->input['day'] === false)
			{
				$errors[] = $lang->error_invalid_day;
			}
			$daddyobb->input['weekday'] = array('*');
		}
		else
		{
			$daddyobb->input['weekday'] = check_time_values($daddyobb->input['weekday'], 0, 6, 'array');
			if($daddyobb->input['weekday'] === false)
			{
				$errors[] = $lang->error_invalid_weekday;
			}
			$daddyobb->input['day'] = '*';
		}

		$daddyobb->input['month'] = check_time_values($daddyobb->input['month'], 1, 12, 'array');
		if($daddyobb->input['month'] === false)
		{
			$errors[] = $lang->error_invalid_month;
		}
		
		if(!$errors)
		{
			$updated_task = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"description" => $db->escape_string($daddyobb->input['description']),
				"file" => $db->escape_string($daddyobb->input['file']),
				"minute" => $db->escape_string($daddyobb->input['minute']),
				"hour" => $db->escape_string($daddyobb->input['hour']),
				"day" => $db->escape_string($daddyobb->input['day']),
				"month" => $db->escape_string(implode(',', $daddyobb->input['month'])),
				"weekday" => $db->escape_string(implode(',', $daddyobb->input['weekday'])),
				"enabled" => intval($daddyobb->input['enabled']),
				"logging" => intval($daddyobb->input['logging'])
			);

			$updated_task['nextrun'] = fetch_next_run($updated_task);
			$db->update_query("tasks", $updated_task, "tid='{$task['tid']}'");
			$cache->update_tasks();
			
			$plugins->run_hooks("admin_tools_tasks_edit_commit");

			// Log admin action
			log_admin_action($task['tid'], $daddyobb->input['title']);

			flash_message($lang->success_task_updated, 'success');
			admin_redirect("index.php?module=tools/tasks");
		}
	}

	$page->add_breadcrumb_item($lang->edit_task);
	$page->output_header($lang->scheduled_tasks." - ".$lang->edit_task);
	
	$sub_tabs['edit_task'] = array(
		'title' => $lang->edit_task,
		'description' => $lang->edit_task_desc,
		'link' => "index.php?module=tools/tasks&amp;action=edit&amp;tid={$task['tid']}"
	);

	$page->output_nav_tabs($sub_tabs, 'edit_task');

	$form = new Form("index.php?module=tools/tasks&amp;action=edit", "post");

	if($errors)
	{
		$page->output_inline_error($errors);
		$task_data = $daddyobb->input;
	}
	else
	{
		$task_data = $task;
		$task_data['weekday'] = explode(',', $task['weekday']);
		$task_data['month'] = explode(',', $task['month']);
	}

	$form_container = new FormContainer($lang->edit_task);
	echo $form->generate_hidden_field("tid", $task['tid']);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $task_data['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description, "", $form->generate_text_box('description', $task_data['description'], array('id' => 'description')), 'description');

	$task_list = array();
	$task_files = scandir(DADDYOBB_ROOT."inc/tasks/");
	foreach($task_files as $task_file)
	{
		if(is_file(DADDYOBB_ROOT."inc/tasks/{$task_file}") && get_extension($task_file) == "php")
		{
			$file_id = preg_replace("#\.".get_extension($task_file)."$#i", "$1", $task_file);
			$task_list[$file_id] = $task_file;
		}
	}
	$form_container->output_row($lang->task." <em>*</em>", $lang->task_desc, $form->generate_select_box("file", $task_list, $task_data['file'], array('id' => 'file')), 'file');
	$form_container->output_row($lang->time_minutes, $lang->time_minutes_desc, $form->generate_text_box('minute', $task_data['minute'], array('id' => 'minute')), 'minute');
	$form_container->output_row($lang->time_hours, $lang->time_hours_desc, $form->generate_text_box('hour', $task_data['hour'], array('id' => 'hour')), 'hour');
	$form_container->output_row($lang->time_days_of_month, $lang->time_days_of_month_desc, $form->generate_text_box('day', $task_data['day'], array('id' => 'day')), 'day');

	$options = array(
		"*" => $lang->every_weekday,
		"0" => $lang->sunday,
		"1" => $lang->monday,
		"2" => $lang->tuesday,
		"3" => $lang->wednesday,
		"4" => $lang->thursday,
		"5" => $lang->friday,
		"6" => $lang->saturday
	);
	$form_container->output_row($lang->time_weekdays, $lang->time_weekdays_desc, $form->generate_select_box('weekday[]', $options, $task_data['weekday'], array('id' => 'weekday', 'multiple' => true)), 'weekday');

	$options = array(
		"*" => $lang->every_month,
		"1" => $lang->january,
		"2" => $lang->february,
		"3" => $lang->march,
		"4" => $lang->april,
		"5" => $lang->may,
		"6" => $lang->june,
		"7" => $lang->july,
		"8" => $lang->august,
		"9" => $lang->september,
		"10" => $lang->october,
		"11" => $lang->november,
		"12" => $lang->december
	);
	$form_container->output_row($lang->time_months, $lang->time_months_desc, $form->generate_select_box('month[]', $options, $task_data['month'], array('id' => 'month', 'multiple' => true)), 'month');

	$form_container->output_row($lang->enable_logging." <em>*</em>", "", $form->generate_yes_no_radio("logging", $task_data['logging'], true));
	
	$form_container->output_row($lang->enabled." <em>*</em>", "", $form->generate_yes_no_radio("enabled", $task_data['enabled'], true));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_task);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_tools_tasks_delete");
	
	$query = $db->simple_select("tasks", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$task = $db->fetch_array($query);

	// Does the task not exist?
	if(!$task['tid'])
	{
		flash_message($lang->error_invalid_task, 'error');
		admin_redirect("index.php?module=tools/tasks");
	}
	
	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=tools/tasks");
	}

	if($daddyobb->request_method == "post")
	{
		// Delete the task & any associated task log entries
		$db->delete_query("tasks", "tid='{$task['tid']}'");
		$db->delete_query("tasklog", "tid='{$task['tid']}'");

		// Fetch next task run
		$cache->update_tasks();
		
		$plugins->run_hooks("admin_tools_tasks_delete_commit");

		// Log admin action
		log_admin_action($task['tid'], $task['title']);

		flash_message($lang->success_task_deleted, 'success');
		admin_redirect("index.php?module=tools/tasks");
	}
	else
	{
		$page->output_confirm_action("index.php?module=tools/tasks&amp;action=delete&amp;tid={$task['tid']}", $lang->confirm_task_deletion);
	}
}

if($daddyobb->input['action'] == "enable" || $daddyobb->input['action'] == "disable")
{
	if($daddyobb->input['action'] == "enable")
	{
		$plugins->run_hooks("admin_tools_tasks_enable");
	}
	else
	{
		$plugins->run_hooks("admin_tools_tasks_enable");
	}
	
	$query = $db->simple_select("tasks", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$task = $db->fetch_array($query);

	// Does the task not exist?
	if(!$task['tid'])
	{
		flash_message($lang->error_invalid_task, 'error');
		admin_redirect("index.php?module=tools/tasks");
	}

	if($daddyobb->input['action'] == "enable")
	{
	
		if($task['file'] == "backupdb" || $task['file'] == "checktables")
		{
			// User clicked no
			if($daddyobb->input['no'])
			{
				admin_redirect("index.php?module=tools/tasks");
			}
		
			if($daddyobb->request_method == "post")
			{
				$nextrun = fetch_next_run($task);
				$db->update_query("tasks", array("nextrun" => $nextrun, "enabled" => 1), "tid='{$task['tid']}'");
				$cache->update_tasks();
				
				$plugins->run_hooks("admin_tools_tasks_enable_commit");
				
				// Log admin action
				log_admin_action($task['tid'], $task['title'], $daddyobb->input['action']);
				
				flash_message($lang->success_task_enabled, 'success');
				admin_redirect("index.php?module=tools/tasks");
			}
			else
			{
				$page->output_confirm_action("index.php?module=tools/tasks&amp;action=enable&amp;tid={$task['tid']}", $lang->confirm_task_enable);
			}
		}
		else
		{
			$nextrun = fetch_next_run($task);
			$db->update_query("tasks", array("nextrun" => $nextrun, "enabled" => 1), "tid='{$task['tid']}'");
			$cache->update_tasks();
			
			$plugins->run_hooks("admin_tools_tasks_enable_commit");
			
			// Log admin action
			log_admin_action($task['tid'], $task['title'], $daddyobb->input['action']);
			
			flash_message($lang->success_task_enabled, 'success');
			admin_redirect("index.php?module=tools/tasks");
		}
	}
	else
	{
		$db->update_query("tasks", array("enabled" => 0), "tid='{$task['tid']}'");
		$cache->update_tasks();
		
		$plugins->run_hooks("admin_tools_tasks_disable_commit");
		
		// Log admin action
		log_admin_action($task['tid'], $task['title'], $daddyobb->input['action']);
		
		flash_message($lang->success_task_disabled, 'success');
		admin_redirect("index.php?module=tools/tasks");
	}
}

if($daddyobb->input['action'] == "run")
{
	ignore_user_abort(true);
	@set_time_limit(0);
	$plugins->run_hooks("admin_tools_tasks_run");
	
	$query = $db->simple_select("tasks", "*", "tid='".intval($daddyobb->input['tid'])."'");
	$task = $db->fetch_array($query);

	// Does the task not exist?
	if(!$task['tid'])
	{
		flash_message($lang->error_invalid_task, 'error');
		admin_redirect("index.php?module=tools/tasks");
	}
	
	run_task($task['tid']);

	$plugins->run_hooks("admin_tools_tasks_run_commit");

	// Log admin action
	log_admin_action($task['tid'], $task['title']);

	flash_message($lang->success_task_run, 'success');
	admin_redirect("index.php?module=tools/tasks");
}

if($daddyobb->input['action'] == "logs")
{
	$plugins->run_hooks("admin_tools_tasks_logs");
	
	$page->output_header($lang->task_logs);

	$sub_tabs['scheduled_tasks'] = array(
		'title' => $lang->scheduled_tasks,
		'link' => "index.php?module=tools/tasks"
	);

	$sub_tabs['add_task'] = array(
		'title' => $lang->add_new_task,
		'link' => "index.php?module=tools/tasks&amp;action=add"
	);
	
	$sub_tabs['task_logs'] = array(
		'title' => $lang->view_task_logs,
		'link' => "index.php?module=tools/tasks&amp;action=logs",
		'description' => $lang->view_task_logs_desc
	);

	$page->output_nav_tabs($sub_tabs, 'task_logs');

	$table = new Table;
	$table->construct_header($lang->task);
	$table->construct_header($lang->date, array("class" => "align_center", "width" => 200));
	$table->construct_header($lang->data, array("width" => "60%"));

	$query = $db->simple_select("tasklog", "COUNT(*) AS log_count");
	$log_count = $db->fetch_field($query, "log_count");

	$per_page = 50;

	if($daddyobb->input['page'] > 0)
	{
		$current_page = intval($daddyobb->input['page']);
		$start = ($current_page-1)*$per_page;
		$pages = $log_count / $per_page;
		$pages = ceil($pages);
		if($current_page > $pages)
		{
			$start = 0;
			$current_page = 1;
		}
	}
	else
	{
		$start = 0;
		$current_page = 1;
	}

	$pagination = draw_admin_pagination($current_page, $per_page, $log_count, "index.php?module=tools/tasks&amp;action=logs&amp;page={page}");

	$query = $db->query("
		SELECT l.*, t.title
		FROM ".TABLE_PREFIX."tasklog l
		LEFT JOIN ".TABLE_PREFIX."tasks t ON (t.tid=l.tid)
		ORDER BY l.dateline DESC
		LIMIT {$start}, {$per_page}
	");
	while($log_entry = $db->fetch_array($query))
	{
		$log_entry['title'] = htmlspecialchars_uni($log_entry['title']);
		$log_entry['data'] = htmlspecialchars_uni($log_entry['data']);
		$date = my_date($daddyobb->settings['dateformat'], $log_entry['dateline']).", ".my_date($daddyobb->settings['timeformat'], $log_entry['dateline']);
		$table->construct_cell("<a href=\"index.php?module=tools/tasks&amp;action=edit&amp;tid={$log_entry['tid']}\">{$log_entry['title']}</a>");
		$table->construct_cell($date, array("class" => "align_center"));
		$table->construct_cell($log_entry['data']);
		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_task_logs, array("colspan" => "3"));
		$table->construct_row();
	}
	$table->output($lang->task_logs);
	echo $pagination;

	$page->output_footer();
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_tools_tasks_start");
	
	$page->output_header($lang->task_manager);

	$sub_tabs['scheduled_tasks'] = array(
		'title' => $lang->scheduled_tasks,
		'link' => "index.php?module=tools/tasks",
		'description' => $lang->scheduled_tasks_desc
	);

	$sub_tabs['add_task'] = array(
		'title' => $lang->add_new_task,
		'link' => "index.php?module=tools/tasks&amp;action=add"
	);

	$sub_tabs['task_logs'] = array(
		'title' => $lang->view_task_logs,
		'link' => "index.php?module=tools/tasks&amp;action=logs"
	);

	$page->output_nav_tabs($sub_tabs, 'scheduled_tasks');

	$table = new Table;
	$table->construct_header($lang->task);
	$table->construct_header($lang->next_run, array("class" => "align_center", "width" => 200));
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

	$query = $db->simple_select("tasks", "*", "", array("order_by" => "title", "order_dir" => "asc"));
	while($task = $db->fetch_array($query))
	{
		$task['title'] = htmlspecialchars_uni($task['title']);
		$task['description'] = htmlspecialchars_uni($task['description']);
		$next_run = date($daddyobb->settings['dateformat'], $task['nextrun']).", ".date($daddyobb->settings['timeformat'], $task['nextrun']);
		if($task['enabled'] == 1)
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.gif\" alt=\"({$lang->alt_enabled})\" title=\"{$lang->alt_enabled}\"  style=\"vertical-align: middle;\" /> ";
		}
		else
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.gif\" alt=\"({$lang->alt_disabled})\" title=\"{$lang->alt_disabled}\"  style=\"vertical-align: middle;\" /> ";
		}
		$table->construct_cell("<div class=\"float_right\"><a href=\"index.php?module=tools/tasks&amp;action=run&amp;tid={$task['tid']}\"><img src=\"styles/{$page->style}/images/icons/run_task.gif\" title=\"{$lang->run_task_now}\" alt=\"{$lang->run_task}\" /></a></div><div>{$icon}<strong><a href=\"index.php?module=tools/tasks&amp;action=edit&amp;tid={$task['tid']}\">{$task['title']}</a></strong><br /><small>{$task['description']}</small></div>");
		$table->construct_cell($next_run, array("class" => "align_center"));

		$popup = new PopupMenu("task_{$task['tid']}", $lang->options);
		$popup->add_item($lang->edit_task, "index.php?module=tools/tasks&amp;action=edit&amp;tid={$task['tid']}");
		if($task['enabled'] == 1)
		{
			$popup->add_item($lang->disable_task, "index.php?module=tools/tasks&amp;action=disable&amp;tid={$task['tid']}");
		}
		else
		{
			$popup->add_item($lang->enable_task, "index.php?module=tools/tasks&amp;action=enable&amp;tid={$task['tid']}");
		}
		$popup->add_item($lang->delete_task, "index.php?module=tools/tasks&amp;action=delete&amp;tid={$task['tid']}&amp;my_post_key={$daddyobb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_task_deletion}')");
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		$table->construct_row();
	}
	$table->output($lang->scheduled_tasks);

	$page->output_footer();
}
?>