<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:17 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->cache_manager, "index.php?module=tools/cache");

$plugins->run_hooks("admin_tools_cache_begin");

if($daddyobb->input['action'] == 'view')
{
	$plugins->run_hooks("admin_tools_cache_view");
	
	if(!trim($daddyobb->input['title']))
	{
		flash_message($lang->error_no_cache_specified, 'error');
		admin_redirect("index.php?module=tools/cache");
	}
	
	$query = $db->simple_select("datacache", "*", "title = '".$db->escape_string($daddyobb->input['title'])."'");
	$cacheitem = $db->fetch_array($query);
	
	if(!$cacheitem)
	{
		flash_message($lang->error_incorrect_cache, 'error');
		admin_redirect("index.php?module=tools/cache");
	}
	
	$cachecontents = unserialize($cacheitem['cache']);
	if(empty($cachecontents))
	{
		$cachecontents = $lang->error_empty_cache;
	}
	ob_start();
	print_r($cachecontents);
	$cachecontents = htmlspecialchars_uni(ob_get_contents());
	ob_end_clean();
	
	$page->add_breadcrumb_item($lang->view);	
	$page->output_header($lang->cache_manager);


	$table = new Table;

	$table->construct_cell("<pre>\n{$cachecontents}\n</pre>");
	$table->construct_row();
	$table->output($lang->cache." {$cacheitem['title']}");
	
	$page->output_footer();
	
}

if($daddyobb->input['action'] == "rebuild")
{
	$plugins->run_hooks("admin_tools_cache_rebuild");
	
	if(method_exists($cache, "update_{$daddyobb->input['title']}"))
	{
		$func = "update_{$daddyobb->input['title']}";
		$cache->$func();
		
		$plugins->run_hooks("admin_tools_cache_rebuild_commit");

		// Log admin action
		log_admin_action($daddyobb->input['title']);

		flash_message($lang->success_cache_rebuilt, 'success');
		admin_redirect("index.php?module=tools/cache");
	}
	else
	{
		flash_message($lang->error_cannot_rebuild, 'error');
		admin_redirect("index.php?module=tools/cache");
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_tools_cache_start");
	
	$page->output_header($lang->cache_manager);
	
	$sub_tabs['cache_manager'] = array(
		'title' => $lang->cache_manager,
		'link' => "index.php?module=tools/cache",
		'description' => $lang->cache_manager_description
	);

	$page->output_nav_tabs($sub_tabs, 'cache_manager');

	$table = new Table;
	$table->construct_header($lang->name);
	$table->construct_header($lang->size, array("class" => "align_center", "width" => 100));
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

	$query = $db->simple_select("datacache");
	while($cacheitem = $db->fetch_array($query))
	{
		$table->construct_cell("<strong><a href=\"index.php?module=tools/cache&amp;action=view&amp;title=".urlencode($cacheitem['title'])."\">{$cacheitem['title']}</a></strong>");
		$table->construct_cell(get_friendly_size(strlen($cacheitem['cache'])), array("class" => "align_center"));
		
		if(method_exists($cache, "update_".$cacheitem['title']))
		{
			$table->construct_cell("<a href=\"index.php?module=tools/cache&amp;action=rebuild&amp;title=".urlencode($cacheitem['title'])."\">".$lang->rebuild_cache."</a>", array("class" => "align_center"));
		}
		else
		{
			$table->construct_cell("");
		}		
		
		$table->construct_row();
	}
	$table->output($lang->cache_manager);
	
	$page->output_footer();
}

?>