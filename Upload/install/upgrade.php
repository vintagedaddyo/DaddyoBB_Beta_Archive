<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 14:17 20.12.2008
 */
 
error_reporting(E_ALL & ~E_NOTICE);

define('DADDYOBB_ROOT', dirname(dirname(__FILE__))."/");
define("INSTALL_ROOT", dirname(__FILE__)."/");
define("TIME_NOW", time());
define('IN_DADDYOBB', 1);
define("IN_UPGRADE", 1);

require_once DADDYOBB_ROOT."inc/class_core.php";
$daddyobb = new DaddyoBB;

require_once DADDYOBB_ROOT."inc/config.php";

$orig_config = $config;

if(!is_array($config['database']))
{
	$config['database'] = array(
		"type" => $config['dbtype'],
		"database" => $config['database'],
		"table_prefix" => $config['table_prefix'],
		"hostname" => $config['hostname'],
		"username" => $config['username'],
		"password" => $config['password'],
		"encoding" => $config['db_encoding'],
	);
}
$daddyobb->config = &$config;

// Include the files necessary for installation
require_once DADDYOBB_ROOT."inc/class_timers.php";
require_once DADDYOBB_ROOT."inc/functions.php";
require_once DADDYOBB_ROOT."inc/class_xml.php";
require_once DADDYOBB_ROOT.'inc/class_language.php';

$lang = new MyLanguage();
$lang->set_path(DADDYOBB_ROOT.'install/resources/');
$lang->load('language');

require_once DADDYOBB_ROOT."inc/db_{$config['database']['type']}.php";
switch($config['database']['type'])
{
	case "sqlite3":
		$db = new DB_SQLite3;
		break;
	case "sqlite2":
		$db = new DB_SQLite2;
		break;
	case "pgsql":
		$db = new DB_PgSQL;
		break;
	case "mysqli":
		$db = new DB_MySQLi;
		break;
	default:
		$db = new DB_MySQL;
}
	
// Connect to Database
define('TABLE_PREFIX', $config['database']['table_prefix']);
$db->connect($config['database']);
$db->set_table_prefix(TABLE_PREFIX);
$db->type = $config['database']['type'];

// Load Settings
if(file_exists(DADDYOBB_ROOT."inc/settings.php"))
{
	require_once DADDYOBB_ROOT."inc/settings.php";
}

if(!file_exists(DADDYOBB_ROOT."inc/settings.php") || !$settings)
{
	if(function_exists('rebuild_settings'))
	{
		rebuild_settings();
	}
	else
	{
		$options = array(
			"order_by" => "title",
			"order_dir" => "ASC"
		);
		
		$query = $db->simple_select("settings", "value, name", "", $options);
		while($setting = $db->fetch_array($query))
		{
			$setting['value'] = str_replace("\"", "\\\"", $setting['value']);
			$settings[$setting['name']] = $setting['value'];
		}
	}	
}

$settings['wolcutoff'] = $settings['wolcutoffmins']*60;
$settings['bbname_orig'] = $settings['bbname'];
$settings['bbname'] = strip_tags($settings['bbname']);

// Fix for people who for some specify a trailing slash on the board URL
if(substr($settings['bburl'], -1) == "/")
{
	$settings['bburl'] = my_substr($settings['bburl'], 0, -1);
}

$daddyobb->settings = &$settings;
$daddyobb->parse_cookies();

require_once DADDYOBB_ROOT."inc/class_datacache.php";
$cache = new datacache;

$daddyobb->cache = &$cache;

require_once DADDYOBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();
$daddyobb->session = &$session;

// Include the necessary contants for installation
$grouppermignore = array("gid", "type", "title", "description", "namestyle", "usertitle", "stars", "starimage", "image");
$groupzerogreater = array("pmquota", "maxreputationsday", "attachquota");
$displaygroupfields = array("title", "description", "namestyle", "usertitle", "stars", "starimage", "image");
$fpermfields = array("canview", "candlattachments", "canpostthreads", "canpostreplys", "canpostattachments", "canratethreads", "caneditposts", "candeleteposts", "candeletethreads", "caneditattachments", "canpostpolls", "canvotepolls", "cansearch");

// Include the installation resources
require_once INSTALL_ROOT."resources/output.php";
$output = new installerOutput;
$output->script = "upgrade.php";
$output->title = "DaddyoBB Upgrade Wizard";

if(file_exists("lock"))
{
	$output->print_error($lang->locked);
}
else
{
	if($daddyobb->input['action'] == "logout" && $daddyobb->user['uid'])
	{	
		// Check session ID if we have one
		if($daddyobb->input['logoutkey'] != $daddyobb->user['logoutkey'])
		{
			$output->print_error("Your user ID could not be verified to log you out.  This may have been because a malicious Javascript was attempting to log you out automatically.  If you intended to log out, please click the Log Out button at the top menu.");
		}
	
		my_unsetcookie("daddyobbuser");
		my_unsetcookie("sid");
		if($daddyobb->user['uid'])
		{
			$time = TIME_NOW;
			$lastvisit = array(
				"lastactive" => $time-900,
				"lastvisit" => $time,
			);
			$db->update_query("users", $lastvisit, "uid='".$daddyobb->user['uid']."'");
			$db->delete_query("sessions", "sid='".$session->sid."'");
		}
		header("Location: upgrade.php");
	}
	else if($daddyobb->input['action'] == "do_login" && $daddyobb->request_method == "post")
	{	
		require_once DADDYOBB_ROOT."inc/functions_user.php";
	
		if(!username_exists($daddyobb->input['username']))
		{
			$output->print_error("The username you have entered appears to be invalid.");
		}
		$query = $db->simple_select("users", "uid,username,password,salt,loginkey", "username='".$db->escape_string($daddyobb->input['username'])."'", array('limit' => 1));
		$user = $db->fetch_array($query);
		if(!$user['uid'])
		{
			$output->print_error("The username you have entered appears to be invalid.");
		}
		else
		{
			$user = validate_password_from_uid($user['uid'], $daddyobb->input['password'], $user);
			if(!$user['uid'])
			{
				$output->print_error("The password you entered is incorrect. If you have forgotten your password, click <a href=\"../member.php?action=lostpw\">here</a>. Otherwise, go back and try again.");
			}
		}
		
		$db->delete_query("sessions", "ip='".$db->escape_string($session->ipaddress)."' AND sid != '".$session->sid."'");
		
		$newsession = array(
			"uid" => $user['uid']
		);
		
		$db->update_query("sessions", $newsession, "sid='".$session->sid."'");
	
		// Temporarily set the cookie remember option for the login cookies
		$daddyobb->user['remember'] = $user['remember'];
	
		my_setcookie("daddyobbuser", $user['uid']."_".$user['loginkey'], null, true);
		my_setcookie("sid", $session->sid, -1, true);
	
		header("Location: ./upgrade.php");
	}

	$output->steps = array($lang->upgrade);
	
	if($daddyobb->user['uid'] == 0)
	{
		$output->print_header("Please Login", "errormsg", 0, 1);
		
		$output->print_contents('<p>Please enter your username and password to begin the upgrade process. You must be a valid forum administrator to perform the upgrade.</p>
<form action="upgrade.php" method="post">
	<div class="border_wrapper">
		<table class="general" cellspacing="0">
		<thead>
			<tr>
				<th colspan="2" class="first last">Login</th>
			</tr>
		</thead>
		<tbody>
			<tr class="first">
				<td class="first">Username:</td>
				<td class="last alt_col"><input type="text" class="textbox" name="username" size="25" maxlength="'.$daddyobb->settings['maxnamelength'].'" style="width: 200px;" /></td>
			</tr>
			<tr class="alt_row last">
				<td class="first">Password:<br /><small>Please note that passwords are case sensitive.</small></td>
				<td class="last alt_col"><input type="password" class="textbox" name="password" size="25" style="width: 200px;" /></td>
			</tr>
		</tbody>
		</table>
	</div>
	<div id="next_button">
		<input type="submit" class="submit_button" name="submit" value="Login" />
		<input type="hidden" name="action" value="do_login" />
	</div>
</form>');
		$output->print_footer("");
		
		exit;
	}
	else if($daddyobb->usergroup['cancp'] != 1 && $daddyobb->usergroup['cancp'] != 'yes')
	{
		$output->print_error("You do not have permissions to run this process. You need administrator permissions to be able to run the upgrade procedure.<br /><br />If you need to logout, please click <a href=\"upgrade.php?action=logout&amp;logoutkey={$daddyobb->user['logoutkey']}\">here</a>. From there you will be able to log in again under your administrator account.");
	}

	if(!$daddyobb->input['action'] || $daddyobb->input['action'] == "intro")
	{
		$output->print_header();
		
		if($db->table_exists("upgrade_data"))
		{
			$db->drop_table("upgrade_data");
		}
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."upgrade_data (
			title varchar(30) NOT NULL,
			contents text NOT NULL,
			UNIQUE (title)
		);");

		$dh = opendir(INSTALL_ROOT."resources");
		while(($file = readdir($dh)) !== false)
		{
			if(preg_match("#upgrade([0-9]+).php$#i", $file, $match))
			{
				$upgradescripts[$match[1]] = $file;
				$key_order[] = $match[1];
			}
		}
		closedir($dh);
		natsort($key_order);
		$key_order = array_reverse($key_order);

		foreach($key_order as $k => $key)
		{
			$file = $upgradescripts[$key];
			$upgradescript = file_get_contents(INSTALL_ROOT."resources/$file");
			preg_match("#Upgrade Script:(.*)#i", $upgradescript, $verinfo);
			preg_match("#upgrade([0-9]+).php$#i", $file, $keynum);
			if(trim($verinfo[1]))
			{
				if($k == 0)
				{
					$vers .= "<option value=\"$keynum[1]\" selected=\"selected\">$verinfo[1]</option>\n";
				}
				else
				{
					$vers .= "<option value=\"$keynum[1]\">$verinfo[1]</option>\n";
				}
			}
		}
		unset($upgradescripts);
		unset($upgradescript);
		
		$output->print_contents($lang->sprintf($lang->upgrade_welcome, $daddyobb->version)."<p><select name=\"from\">$vers</select>");
		$output->print_footer("doupgrade");
	}
	elseif($daddyobb->input['action'] == "doupgrade")
	{
		require_once INSTALL_ROOT."resources/upgrade".intval($daddyobb->input['from']).".php";
		if($db->table_exists("datacache") && $upgrade_detail['requires_deactivated_plugins'] == 1 && $daddyobb->input['donewarning'] != "true")
		{
			require_once DADDYOBB_ROOT."inc/class_datacache.php";
			$cache = new datacache;
			$plugins = $cache->read('plugins', true);
			if(!empty($plugins['active']))
			{
				$output->print_header();
				$lang->plugin_warning = "<input type=\"hidden\" name=\"from\" value=\"".intval($daddyobb->input['from'])."\" />\n<input type=\"hidden\" name=\"donewarning\" value=\"true\" />\n<div class=\"error\"><strong><span style=\"color: red\">Warning:</span></strong> <p>There are still ".count($plugins['active'])." plugin(s) active. Active plugins can sometimes cause problems during an upgrade procedure or may break your forum afterward. It is <strong>strongly</strong> reccommended that you deactivate your plugins before continuing.</p></div> <br />";
				$output->print_contents($lang->sprintf($lang->plugin_warning, $daddyobb->version));
				$output->print_footer("doupgrade");
			}
			else
			{
				add_upgrade_store("startscript", $daddyobb->input['from']);
				$runfunction = next_function($daddyobb->input['from']);
			}
		}
		else
		{
			add_upgrade_store("startscript", $daddyobb->input['from']);
			$runfunction = next_function($daddyobb->input['from']);
		}
	}
	$currentscript = get_upgrade_store("currentscript");
	$system_upgrade_detail = get_upgrade_store("upgradedetail");

	if($daddyobb->input['action'] == "templates")
	{
		$runfunction = "upgradethemes";
	}
	elseif($daddyobb->input['action'] == "rebuildsettings")
	{
		$runfunction = "buildsettings";
	}
	elseif($daddyobb->input['action'] == "buildcaches")
	{
		$runfunction = "buildcaches";
	}
	elseif($daddyobb->input['action'] == "finished")
	{
		$runfunction = "upgradedone";
	}
	else // Busy running modules, come back later
	{
		$bits = explode("_", $daddyobb->input['action'], 2);
		if($bits[1]) // We're still running a module
		{
			$from = $bits[0];
			$runfunction = next_function($bits[0], $bits[1]);

		}
	}
	// Fetch current script we're in
	
	if(function_exists($runfunction))

	{
		$runfunction();
	}
}

function upgradethemes()
{
	global $output, $db, $system_upgrade_detail, $lang, $daddyobb;
	
	$output->print_header($lang->upgrade_templates_reverted);

	$charset = $db->build_create_table_collation();

	if($system_upgrade_detail['revert_all_templates'] > 0)
	{
		$db->drop_table("templates");
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."templates (
		  tid int unsigned NOT NULL auto_increment,
		  title varchar(120) NOT NULL default '',
		  template text NOT NULL,
		  sid int(10) NOT NULL default '0',
		  version varchar(20) NOT NULL default '0',
		  status varchar(10) NOT NULL default '',
		  dateline int(10) NOT NULL default '0',
		  PRIMARY KEY  (tid)
		) TYPE=MyISAM{$charset};");
	}

	if($system_upgrade_detail['revert_all_themes'] > 0)
	{
		$db->drop_table("themes");
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."themes (
		 tid smallint unsigned NOT NULL auto_increment,
		 name varchar(100) NOT NULL default '',
		 pid smallint unsigned NOT NULL default '0',
		 def smallint(1) NOT NULL default '0',
		 properties text NOT NULL,
		 stylesheets text NOT NULL,
		 allowedgroups text NOT NULL,
		 PRIMARY KEY (tid)
		) TYPE=MyISAM{$charset};");

		$db->drop_table("themestylesheets");
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."themestylesheets(
			sid int unsigned NOT NULL auto_increment,
			name varchar(30) NOT NULL default '',
			tid int unsigned NOT NULL default '0',
			attachedto text NOT NULL,
			stylesheet text NOT NULL,
			cachefile varchar(100) NOT NULL default '',
			lastmodified bigint(30) NOT NULL default '0',
			PRIMARY KEY(sid)
		) TYPE=MyISAM{$charset};");

		$contents = @file_get_contents(INSTALL_ROOT.'resources/daddyobb_theme.xml');
		if(file_exists(DADDYOBB_ROOT.$daddyobb->config['admin_dir']."/inc/functions_themes.php"))
		{
			require_once DADDYOBB_ROOT.$daddyobb->config['admin_dir']."/inc/functions_themes.php";
		}
		else if(file_exists(DADDYOBB_ROOT."admin/inc/functions_themes.php"))
		{
			require_once DADDYOBB_ROOT."admin/inc/functions_themes.php";
		}
		else
		{
			$output->print_error("Please make sure your admin directory is uploaded correctly.");
		}
		import_theme_xml($contents, array("templateset" => -2, "no_templates" => 1));
		$tid = build_new_theme("Default", null, 1);

		$db->update_query("themes", array("def" => 1), "tid='{$tid}'");
		$db->update_query("users", array('style' => $tid));
		$db->update_query("forums", array('style' => 0));
		
		$db->drop_table("templatesets");
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."templatesets (
		  sid smallint unsigned NOT NULL auto_increment,
		  title varchar(120) NOT NULL default '',
		  PRIMARY KEY  (sid)
		) TYPE=MyISAM{$charset};");
		
		$db->insert_query("templatesets", array('title' => 'Default Templates'));
	}
	else
	{
		// Re-import master
		$contents = @file_get_contents(INSTALL_ROOT.'resources/daddyobb_theme.xml');
		if(file_exists(DADDYOBB_ROOT.$daddyobb->config['admin_dir']."/inc/functions_themes.php"))
		{
			require_once DADDYOBB_ROOT.$daddyobb->config['admin_dir']."/inc/functions_themes.php";
		}
		else if(file_exists(DADDYOBB_ROOT."admin/inc/functions_themes.php"))
		{
			require_once DADDYOBB_ROOT."admin/inc/functions_themes.php";
		}
		else
		{
			$output->print_error();
		}
		
		// Import master theme
		import_theme_xml($contents, array("tid" => 1, "no_templates" => 1));
	}

	$sid = -2;

	// Now deal with the master templates
	$contents = @file_get_contents(INSTALL_ROOT.'resources/daddyobb_theme.xml');
	$parser = new XMLParser($contents);
	$tree = $parser->get_tree();

	$theme = $tree['theme'];

	if(is_array($theme['templates']))
	{
		$templates = $theme['templates']['template'];
		foreach($templates as $template)
		{
			$templatename = $db->escape_string($template['attributes']['name']);
			$templateversion = intval($template['attributes']['version']);
			$templatevalue = $db->escape_string($template['value']);
			$time = TIME_NOW;
			$query = $db->simple_select("templates", "tid", "sid='-2' AND title='".$db->escape_string($templatename)."'");
			$oldtemp = $db->fetch_array($query);
			if($oldtemp['tid'])
			{
				$update_array = array(
					'template' => $templatevalue,
					'version' => $templateversion,
					'dateline' => $time
				);
				$db->update_query("templates", $update_array, "title='".$db->escape_string($templatename)."' AND sid='-2'");
			}
			else
			{
				$insert_array = array(
					'title' => $templatename,
					'template' => $templatevalue,
					'sid' => $sid,
					'version' => $templateversion,
					'dateline' => $time
				);			
				
				$db->insert_query("templates", $insert_array);
				++$newcount;
			}
		}
	}

	$output->print_contents($lang->upgrade_templates_reverted_success);
	$output->print_footer("rebuildsettings");
}

function buildsettings()
{
	global $db, $output, $system_upgrade_detail, $lang;

	if(!is_writable(DADDYOBB_ROOT."inc/settings.php"))
	{
		$output->print_header("Rebuilding Settings");
		echo "<p><div class=\"error\"><span style=\"color: red; font-weight: bold;\">Error: Unable to open inc/settings.php</span><h3>Before the upgrade process can continue, you need to changes the permissions of inc/settings.php so it is writable.</h3></div></p>";
		$output->print_footer("rebuildsettings");
		exit;
	}
	$synccount = sync_settings($system_upgrade_detail['revert_all_settings']);

	$output->print_header($lang->upgrade_settings_sync);
	$output->print_contents($lang->sprintf($lang->upgrade_settings_sync_success, $synccount[1], $synccount[0]));
	$output->print_footer("buildcaches");
}

function buildcaches()
{
	global $db, $output, $cache, $lang, $daddyobb;

	$output->print_header($lang->upgrade_datacache_building);

	$contents .= $lang->upgrade_building_datacache;
	require_once DADDYOBB_ROOT."inc/class_datacache.php";
	$cache = new datacache;
	$cache->update_version();
	$cache->update_attachtypes();
	$cache->update_smilies();
	$cache->update_badwords();
	$cache->update_usergroups();
	$cache->update_forumpermissions();
	$cache->update_stats();
	$cache->update_moderators();
	$cache->update_forums();
	$cache->update_usertitles();
	$cache->update_reportedposts();
	$cache->update_mycode();
	$cache->update_posticons();
	$cache->update_update_check();
	$cache->update_tasks();
	$cache->update_spiders();
	$cache->update_bannedips();
	$cache->update_banned();
	$cache->update_birthdays();
	$cache->update_most_replied_threads();
	$cache->update_most_viewed_threads();

	$contents .= $lang->done."</p>";

	$output->print_contents("$contents<p>".$lang->upgrade_continue."</p>");
	$output->print_footer("finished");
}

function upgradedone()
{
	global $db, $output, $daddyobb, $lang, $config;

	$output->print_header("Upgrade Complete");
	if(is_writable("./"))
	{
		$lock = @fopen("./lock", "w");
		$written = @fwrite($lock, "1");
		@fclose($lock);
		if($written)
		{
			$lock_note = $lang->sprintf($lang->upgrade_locked, $config['admin_dir']);
		}
	}
	if(!$written)
	{
		$lock_note = "<p><b><span style=\"color: red;\">".$lang->upgrade_removedir."</span></b></p>";
	}
	
	// Rebuild inc/settings.php at the end of the upgrade
	if(function_exists('rebuild_settings'))
	{
		rebuild_settings();
	}
	else
	{
		$options = array(
			"order_by" => "title",
			"order_dir" => "ASC"
		);
		
		$query = $db->simple_select("settings", "value, name", "", $options);
		while($setting = $db->fetch_array($query))
		{
			$setting['value'] = str_replace("\"", "\\\"", $setting['value']);
			$settings[$setting['name']] = $setting['value'];
		}
	}
	
	$output->print_contents($lang->sprintf($lang->upgrade_congrats, $daddyobb->version, $lock_note));
	$output->print_footer();
}

function whatsnext()
{
	global $output, $db, $system_upgrade_detail, $lang;

	if($system_upgrade_detail['revert_all_templates'] > 0)
	{
		$output->print_header($lang->upgrade_template_reversion);
		$output->print_contents($lang->upgrade_template_reversion_success);
		$output->print_footer("templates");
	}
	else
	{
		upgradethemes();
	}
}

function next_function($from, $func="dbchanges")
{
	global $oldvers, $system_upgrade_detail, $currentscript;

	load_module("upgrade".$from.".php");
	if(function_exists("upgrade".$from."_".$func))
	{
		$function = "upgrade".$from."_".$func;
	}
	else
	{
		$from = $from+1;
		if(file_exists(INSTALL_ROOT."resources/upgrade".$from.".php"))
		{
			$function = next_function($from);
		}
	}

	if(!$function)
	{
		$function = "whatsnext";
	}
	return $function;
}

function load_module($module)
{
	global $system_upgrade_detail, $currentscript, $upgrade_detail;
	
	require_once INSTALL_ROOT."resources/".$module;
	if($currentscript != $module)
	{
		foreach($upgrade_detail as $key => $val)
		{
			if(!$system_upgrade_detail[$key] || $val > $system_upgrade_detail[$key])
			{
				$system_upgrade_detail[$key] = $val;
			}
		}
		add_upgrade_store("upgradedetail", $system_upgrade_detail);
		add_upgrade_store("currentscript", $module);
	}
}

function get_upgrade_store($title)
{
	global $db;
	
	$query = $db->simple_select("upgrade_data", "*", "title='".$db->escape_string($title)."'");
	$data = $db->fetch_array($query);
	return unserialize($data['contents']);
}

function add_upgrade_store($title, $contents)
{
	global $db;
	
	$replace_array = array(
		"title" => $db->escape_string($title),
		"contents" => $db->escape_string(serialize($contents))
	);		
	$db->replace_query("upgrade_data", $replace_array, "title");
}

function sync_settings($redo=0)
{
	global $db;
	
	$settingcount = $groupcount = 0;
	if($redo == 2)
	{
		$db->drop_table("settinggroups");
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."settinggroups (
		  gid smallint unsigned NOT NULL auto_increment,
		  name varchar(100) NOT NULL default '',
		  title varchar(220) NOT NULL default '',
		  description text NOT NULL,
		  disporder smallint unsigned NOT NULL default '0',
		  isdefault int(1) NOT NULL default '',
		  PRIMARY KEY  (gid)
		) TYPE=MyISAM;");

		$db->drop_table("settings");

		$db->write_query("CREATE TABLE ".TABLE_PREFIX."settings (
		  sid smallint unsigned NOT NULL auto_increment,
		  name varchar(120) NOT NULL default '',
		  title varchar(120) NOT NULL default '',
		  description text NOT NULL,
		  optionscode text NOT NULL,
		  value text NOT NULL,
		  disporder smallint unsigned NOT NULL default '0',
		  gid smallint unsigned NOT NULL default '0',
		  isdefault int(1) NOT NULL default '0',
		  PRIMARY KEY (sid)
		) TYPE=MyISAM;");
	}
	else
	{
		if($db->type == "mysql" || $db->type == "mysqli")
        {
            $wheresettings = "isdefault='1' OR isdefault='yes'";
        }
        else
        {
            $wheresettings = "isdefault='1'";
        }
		
        $query = $db->simple_select("settings", "name,sid", $wheresettings);
		while($setting = $db->fetch_array($query))
		{
			$settings[$setting['name']] = $setting['sid'];
		}
		
		$query = $db->simple_select("settinggroups", "name,title,gid", $wheresettings);
		while($group = $db->fetch_array($query))
		{
			$settinggroups[$group['name']] = $group['gid'];
		}
	}
	$settings_xml = file_get_contents(INSTALL_ROOT."resources/settings.xml");
	$parser = new XMLParser($settings_xml);
	$parser->collapse_dups = 0;
	$tree = $parser->get_tree();
	$settinggroupnames = array();
	$settingnames = array();

	foreach($tree['settings'][0]['settinggroup'] as $settinggroup)
	{
		$settinggroupnames[] = $settinggroup['attributes']['name'];
		
		$groupdata = array(
			"name" => $db->escape_string($settinggroup['attributes']['name']),
			"title" => $db->escape_string($settinggroup['attributes']['title']),
			"description" => $db->escape_string($settinggroup['attributes']['description']),
			"disporder" => intval($settinggroup['attributes']['disporder']),
			"isdefault" => $settinggroup['attributes']['isdefault']
		);
		if(!$settinggroups[$settinggroup['attributes']['name']] || $redo == 2)
		{
			$gid = $db->insert_query("settinggroups", $groupdata);
			++$groupcount;
		}
		else
		{
			$gid = $settinggroups[$settinggroup['attributes']['name']];
			$db->update_query("settinggroups", $groupdata, "gid='{$gid}'");
		}
		
		if(!$gid)
		{
			continue;
		}
		
		foreach($settinggroup['setting'] as $setting)
		{
			$settingnames[] = $setting['attributes']['name'];
			
			$settingdata = array(
				"name" => $db->escape_string($setting['attributes']['name']),
				"title" => $db->escape_string($setting['title'][0]['value']),
				"description" => $db->escape_string($setting['description'][0]['value']),
				"optionscode" => $db->escape_string($setting['optionscode'][0]['value']),
				"disporder" => intval($setting['disporder'][0]['value']),
				"gid" => $gid,
				"isdefault" => 1
			);
			if(!$settings[$setting['attributes']['name']] || $redo == 2)
			{
				$settingdata['value'] = $db->escape_string($setting['settingvalue'][0]['value']);
				$db->insert_query("settings", $settingdata);
				$settingcount++;
			}
			else
			{
				$name = $db->escape_string($setting['attributes']['name']);
				$db->update_query("settings", $settingdata, "name='{$name}'");
			}
		}
	}
	
	if($redo >= 1)
	{
		require DADDYOBB_ROOT."inc/settings.php";
		foreach($settings as $key => $val)
		{
			$db->update_query("settings", array('value' => $db->escape_string($val)), "name='$key'");
		}
	}
	unset($settings);
	$query = $db->simple_select("settings", "*", "", array('order_by' => 'title'));
	while($setting = $db->fetch_array($query))
	{
		$setting['value'] = str_replace("\"", "\\\"", $setting['value']);
		$settings .= "\$settings['{$setting['name']}'] = \"".$setting['value']."\";\n";
	}
	$settings = "<?php\n/*********************************\ \n  DO NOT EDIT THIS FILE, PLEASE USE\n  THE SETTINGS EDITOR\n\*********************************/\n\n$settings\n?>";
	$file = fopen(DADDYOBB_ROOT."inc/settings.php", "w");
	fwrite($file, $settings);
	fclose($file);
	return array($groupcount, $settingcount);
}

function write_settings()
{
	global $db;
	$query = $db->simple_select("settings", "*", "", array('order_by' => 'title'));
	while($setting = $db->fetch_array($query))
	{
		$setting['value'] = $db->escape_string($setting['value']);
		$settings .= "\$settings['{$setting['name']}'] = \"{$setting['value']}\";\n";
	}
	if(!empty($settings))
	{
		$settings = "<?php\n/*********************************\ \n  DO NOT EDIT THIS FILE, PLEASE USE\n  THE SETTINGS EDITOR\n\*********************************/\n\n{$settings}\n?>";
		$file = fopen(DADDYOBB_ROOT."inc/settings.php", "w");
		fwrite($file, $settings);
		fclose($file);
	}
}
?>