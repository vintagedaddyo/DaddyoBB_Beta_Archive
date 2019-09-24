<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->version_check, "index.php?module=home/version_check");

$plugins->run_hooks("admin_home_version_check_begin");

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_home_version_check_start");
	
	$page->output_header($lang->version_check);
	
	$sub_tabs['version_check'] = array(
		'title' => $lang->version_check,
		'link' => "index.php?module=home/version_check",
		'description' => $lang->version_check_description
	);
	
	$sub_tabs['download_daddyobb'] = array(
		'title' => $lang->dl_the_latest_daddyobb,
		'link' => "http://daddyobb.com/index.php?p=daddyobb",
		'link_target' => '_blank'
	);
	
	$sub_tabs['check_plugins'] = array(
		'title' => $lang->check_plugin_versions,
		'link' => "index.php?module=config/plugins&amp;action=check",
	);
	
	$page->output_nav_tabs($sub_tabs, 'version_check');	
	
	$current_version = rawurlencode($daddyobb->version_code);

	$updated_cache = array(
		"last_check" => TIME_NOW
	);

	require DADDYOBB_ROOT."inc/class_xml.php";
	$contents = fetch_remote_file("http://daddyobb.com/project/version_check.xml");
	if(!$contents)
	{
		$page->output_inline_error($lang->error_communication);
		$page->output_footer();
		exit;
	}
	
	// We do this because there is some weird symbols that show up in the xml file for unknown reasons
	$pos = strpos($contents, "<");
	if($pos > 1)
	{
		$contents = substr($contents, $pos);
	}
	
	$pos = strpos(strrev($contents), ">");
	if($pos > 1)
	{
		$contents = substr($contents, 0, (-1) * ($pos-1));
	}

	$parser = new XMLParser($contents);
	$tree = $parser->get_tree();

	$latest_code = $tree['daddyobb']['version_code']['value'];
	$latest_version = "<strong>".$tree['daddyobb']['latest_version']['value']."</strong> (".$latest_code.")";
	if($latest_code > $daddyobb->version_code)
	{
		$latest_version = "<span style=\"color: #C00;\">".$latest_version."</span>";
		$version_warn = 1;
		$updated_cache['latest_version'] = $latest_version;
		$updated_cache['latest_version_code'] = $latest_code;
	}
	else
	{
		$latest_version = "<span style=\"color: green;\">".$latest_version."</span>";
	}
	
	$cache->update("update_check", $updated_cache);

	$table = new Table;
	$table->construct_header($lang->your_version);
	$table->construct_header($lang->latest_version);
	
	$table->construct_cell("<strong>".$daddyobb->version."</strong> (".$daddyobb->version_code.")");
	$table->construct_cell($latest_version);
	$table->construct_row();
	
	$table->output($lang->version_check);
	
	if($version_warn)
	{
		$page->output_error("<p><em>{$lang->error_out_of_date}</em> {$lang->update_forum}</p>");
	}
	else
	{
		$page->output_success("<p><em>{$lang->success_up_to_date}</em></p>");
	}
	
		$news = fetch_remote_file('http://www.daddyobb.com/project/latest_news.xml');
    if(!$news)
    {
      $page->output_inline_error($lang->error_communication);
    }
		else
		{
      $parser = new XMLParser($news);
      $parser->collapse_dups = 0;
      $tree = $parser->get_tree();
      
      foreach($tree['news_items'][0]['item'] as $item)
      {
        $newscount++;
        foreach($item['post'] as $post)
        {
          $table->construct_cell("<span style=\"font-size: 16px;\"><strong>".$post['attributes']['title']."</strong></span><br /><br />{$post['content'][0]['value']}<strong><span style=\"float: right;\">Posted on {$post['posted'][0]['value']} by {$post['author'][0]['value']}</span><br /><br /><a href=\"{$post['link'][0]['value']}\" target=\"_blank\">&raquo; {$lang->read_more}</a></strong>");
          $table->construct_row();
        }
      }
    
      $table->output($lang->latest_DADDYOBB_announcements);
    }
    
    $page->output_footer();
}

?>