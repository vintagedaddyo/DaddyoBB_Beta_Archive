<?php
// Portal Redirect Plugin
// By DennisTT - http://www.dennistt.net
// Version 1.1.0

// This plugin (C) DennisTT 2008.  You may not redistribute this plugin without the permission from DennisTT.

if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

// The information that shows up on the plugin manager
// Note that the name of the function before _info, _activate, _deactivate must be the same as the filename before the extension.
function portalredirect_info()
{
	global $lang;
	portalredirect_load_language();
	
	return array(
		"name"			=> $lang->portalredirect,
		"description"	=> $lang->portalredirect_desc,
		"website"		=> "http://www.dennistt.net",
		"author"		=> "DennisTT",
		"authorsite"	=> "http://www.dennistt.net",
		"version"		=> "1.1.0",
		"guid"			  => "bc9eeeb7e583a0c77a942beb0f95e9bc",
		"compatibility"   => "10*",
		
		// DennisTT custom info
		"codename" => 'portalredirect',
	);
}

// Helper function to load the language variables
function portalredirect_load_language()
{
	global $lang;
	if(!defined('DENNISTT_PORTALREDIRECT_LANG_LOADED'))
	{
		$lang->load('portalredirect', false, true);
		
		if(!isset($lang->portalredirect))
		{
			$lang->portalredirect = 'Portal Redirect';
			$lang->portalredirect_desc = 'Redirects users from the index page to the portal when they arrive each day. For DaddyoBB 1.0';
			
		}
		
		define('DENNISTT_PORTALREDIRECT_LANG_LOADED', 1);
	}
}

// This function runs when the plugin is activated.
function portalredirect_activate()
{
	global $db;
	
	// Deactivate first to remove any existing settings
	portalredirect_deactivate();
	
	$info = portalredirect_info();
	$setting_group_array = array(
		'name' => str_replace(' ', '_', 'dennistt_'.strtolower($info['codename'])),
		'title' => "$info[name] (DennisTT)",
		'description' => "Settings for the $info[name] plugin",
		'disporder' => 1,
		'isdefault' => 0,
		);
	$db->insert_query('settinggroups', $setting_group_array);
	$group = $db->insert_id();
	
	$settings = array(
		'portalredirect_url' => array('URL', 'The URL to redirect users to', 'text', 'portal.php'),
		'portalredirect_timeout' => array('Timeout', 'Users will every X minutes, where X is specified in this setting.  By default this is 20 hours (1200 minutes)', 'text', '1200'),
		);

	$i = 1;
	foreach($settings as $name => $sinfo)
	{
		$insert_array = array(
			'name' => $name,
			'title' => $sinfo[0],
			'description' => $sinfo[1],
			'optionscode' => $sinfo[2],
			'value' => $sinfo[3],
			'gid' => $group,
			'disporder' => $i,
			'isdefault' => 0
			);
		$db->insert_query("settings", $insert_array);
		$i++;
	}

	rebuild_settings();
}

// This function runs when the plugin is deactivated.
function portalredirect_deactivate()
{
	global $db;
	$info = portalredirect_info();
	$result = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name = '".str_replace(' ', '_', 'dennistt_'.strtolower($info['codename']))."' LIMIT 1");
	$group = $db->fetch_array($result);
	
	if(!empty($group['gid']))
	{
		$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE gid = $group[gid] LIMIT 1");
		$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE gid = $group[gid]");
		rebuildsettings();
	}
}

$plugins->add_hook("index_start", "portalredirect_indexrun");
function portalredirect_indexrun()
{
	global $daddyobb;
	if ((!isset($_COOKIE['viewedportal']) && !isset($daddyobb->input['noredirect'])) || isset($daddyobb->input['forceredirect'])) {
		header('Location: '.$daddyobb->settings['portalredirect_url']);
		exit;
	}
}

$plugins->add_hook("portal_start", "portalredirect_portalrun");
function portalredirect_portalrun()
{
	global $daddyobb;
	
	my_setcookie('viewedportal', true, $daddyobb->settings['portalredirect_timeout']*60);
}

// End of Plugin
?>