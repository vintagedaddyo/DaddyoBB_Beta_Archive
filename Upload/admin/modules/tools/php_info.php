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

if($daddyobb->input['action'] == 'phpinfo')
{
	$plugins->run_hooks("admin_tools_php_info_phpinfo");
	
	// Log admin action
	log_admin_action();

	phpinfo();
	exit;
}

$page->add_breadcrumb_item($lang->php_info, "index.php?module=tools/php_info");

$plugins->run_hooks("admin_tools_php_info_begin");

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_tools_php_info_start");
	
	$page->output_header($lang->php_info);
	
	echo "<iframe src=\"index.php?module=tools/php_info&amp;action=phpinfo\" width=\"100%\" height=\"500\" frameborder=\"0\">{$lang->browser_no_iframe_support}</iframe>";
	
	$page->output_footer();
}

?>