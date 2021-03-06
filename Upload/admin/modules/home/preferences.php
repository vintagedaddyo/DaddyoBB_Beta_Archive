<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:16 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->preferences_and_personal_notes, "index.php?module=home/preferences");

$plugins->run_hooks("admin_home_preferences_begin");

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_home_preferences_start");
	
	if($daddyobb->request_method == "post")
	{
		$query = $db->simple_select("adminoptions", "permissions, defaultviews", "uid='{$daddyobb->user['uid']}'");
		$adminopts = $db->fetch_array($query);
		
		$sqlarray = array(
			"notes" => $db->escape_string($daddyobb->input['notes']),
			"cpstyle" => $db->escape_string($daddyobb->input['cpstyle']),
			"permissions" => $db->escape_string($adminopts['permissions']),
			"defaultviews" => $db->escape_string($adminopts['defaultviews']),
			"uid" => $daddyobb->user['uid'],
			"codepress" => intval($daddyobb->input['codepress']),
		);

		$db->replace_query("adminoptions", $sqlarray, "uid");
		
		$plugins->run_hooks("admin_home_preferences_start_commit");
	
		flash_message($lang->success_preferences_updated, 'success');
		admin_redirect("index.php?module=home/preferences");
	}
	
	$page->output_header($lang->preferences_and_personal_notes);
	
	$sub_tabs['preferences'] = array(
		'title' => $lang->preferences_and_personal_notes,
		'link' => "index.php?module=home/preferences",
		'description' => $lang->prefs_and_personal_notes_description
	);

	$page->output_nav_tabs($sub_tabs, 'preferences');	
	
	$query = $db->simple_select("adminoptions", "notes, cpstyle, codepress", "uid='".$daddyobb->user['uid']."'", array('limit' => 1));
	$admin_options = $db->fetch_array($query);
	
	$form = new Form("index.php?module=home/preferences", "post");
	$dir = @opendir(DADDYOBB_ADMIN_DIR."/styles");
	while($folder = readdir($dir))
	{
		if($file != "." && $file != ".." && @file_exists(DADDYOBB_ADMIN_DIR."/styles/$folder/main.css"))
		{
			$folders[$folder] = ucfirst($folder);
		}
	}
	closedir($dir);
	ksort($folders);
	$setting_code = $form->generate_select_box("cpstyle", $folders, $admin_options['cpstyle']);
	
	$table = new Table;
	$table->construct_header($lang->global_preferences);
	
	$table->construct_cell("<strong>{$lang->acp_theme}</strong><br /><small>{$lang->select_acp_theme}</small><br /><br />{$setting_code}");
	$table->construct_row();
	
	$table->construct_cell("<strong>{$lang->codepress}</strong><br /><small>{$lang->use_codepress_desc}</small><br /><br />".$form->generate_yes_no_radio('codepress', $admin_options['codepress']));
	$table->construct_row();
	
	$table->output($lang->preferences);
	
	$table->construct_header($lang->notes_not_shared);
	
	$table->construct_cell($form->generate_text_area("notes", $admin_options['notes'], array('style' => 'width: 99%; height: 300px;')));
	$table->construct_row();
	
	$table->output($lang->personal_notes);	
	
	$buttons[] = $form->generate_submit_button($lang->save_notes_and_prefs);
	$form->output_submit_wrapper($buttons);
	
	$form->end();
	
	$page->output_footer();
}

?>