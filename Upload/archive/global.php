<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 
 */
 
// If archive mode does not work, uncomment the line below and try again
// define("ARCHIVE_QUERY_STRINGS", 1);

// Lets pretend we're a level higher
chdir('./../');

require_once dirname(dirname(__FILE__))."/inc/init.php";

require_once DADDYOBB_ROOT."inc/functions_archive.php";
require_once DADDYOBB_ROOT."inc/class_session.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

$groupscache = $cache->read("usergroups");
if(!is_array($groupscache))
{
	$cache->update_usergroups();
	$groupscache = $cache->read("usergroups");
}
$fpermissioncache = $cache->read("forumpermissions");

// Send headers before anything else.
send_page_headers();

// If the installer has not been removed and no lock exists, die.
if(is_dir(DADDYOBB_ROOT."install") && !file_exists(DADDYOBB_ROOT."install/lock"))
{
	echo "Please remove the install directory from your server, or create a file called 'lock' in the install directory. Until you do so, your board will remain unaccessable";
	exit;
}

// If the server OS is not Windows and not Apache or the PHP is running as a CGI or we have defined ARCHIVE_QUERY_STRINGS, use query strings - DIRECTORY_SEPARATOR checks if running windows
if((DIRECTORY_SEPARATOR == '\\' && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') == false) || stripos(SAPI_NAME, 'cgi') !== false || defined("ARCHIVE_QUERY_STRINGS"))
{
	$url = $_SERVER['QUERY_STRING'];
	$base_url = $daddyobb->settings['bburl']."/archive/index.php?";
	$endpart = $url;
}
// Otherwise, we're using 100% friendly URLs
else
{
	if($_SERVER['REQUEST_URI'])
    {
        $url = $_SERVER['REQUEST_URI'];
    }
    elseif($_SERVER['REDIRECT_URL'])
	{
		$url = $_SERVER['REDIRECT_URL'];
	}
	elseif($_SERVER['PATH_INFO'])
	{
		$url = $_SERVER['PATH_INFO'];
	}
	else
	{
		$url = $_SERVER['PHP_SELF'];
	}
	$base_url = $daddyobb->settings['bburl']."/archive/index.php/";
	$endpart = my_substr(strrchr($url, "/"), 1);
}

$action = "index";

// This seems to work the same as the block below except without the css bugs O_o
$archiveurl = $daddyobb->settings['bburl'].'/archive';

if($endpart != "index.php")
{
	$endpart = str_replace(".html", "", $endpart);
	$todo = explode("-", $endpart, 3);
	if($todo[0])
	{
		$action = $todo[0];
	}
	$page = $todo[2];
	$id = intval($todo[1]);

	// Get the thread, announcement or forum information.
	if($action == "announcement")
	{
		$time = TIME_NOW;
		$query = $db->query("
			SELECT a.*, u.username
			FROM ".TABLE_PREFIX."announcements a
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=a.uid)
			WHERE a.aid='{$id}' AND startdate < '{$time}'  AND (enddate > '{$time}' OR enddate = 0)
		");
		$announcement = $db->fetch_array($query);
		if(!$announcement['aid'])
		{
			$action = "404";
		}
	}
	elseif($action == "thread")
	{
		$query = $db->simple_select("threads", "*", "tid='{$id}' AND visible='1' AND closed NOT LIKE 'moved|%'");
		$thread = $db->fetch_array($query);
		if(!$thread['tid'])
		{
			$action = "404";
		}
	}
	elseif($action == "forum")
	{
		$query = $db->simple_select("forums", "*", "fid='{$id}' AND active!=0 AND password=''");
		$forum = $db->fetch_array($query);
		if(!$forum['fid'])
		{
			$action = "404";
		}
	}
	else if($action != 'index')
	{
		$action = "404";
	}
}

// Define the full DaddyoBB version location of this page.
if($action == "thread")
{
	define(DADDYOBB_LOCATION, get_thread_link($id));
}
elseif($action == "forum")
{
	define(DADDYOBB_LOCATION, get_forum_link($id));
}
elseif($action == "announcement")
{
	define(DADDYOBB_LOCATION, get_announcement_link($id));
}
else
{
	define(DADDYOBB_LOCATION, INDEX_URL);
}

// Initialise session
$session = new session;
$session->init();

if(!$daddyobb->settings['bblanguage'])
{
	$daddyobb->settings['bblanguage'] = "english";
}
$lang->set_language($daddyobb->settings['bblanguage']);

// Load global language phrases
$lang->load("global");
$lang->load("messages");
$lang->load("archive");

// Draw up the basic part of our naviagation
$navbits[0]['name'] = $daddyobb->settings['bbname_orig'];
$navbits[0]['url'] = $daddyobb->settings['bburl']."/archive/index.php";

// Check banned ip addresses
if(is_banned_ip($session->ipaddress))
{
	archive_error($lang->error_banned);
}

// If our board is closed..
if($daddyobb->settings['boardclosed'] == 1)
{
	if($daddyobb->usergroup['cancp'] != 1)
	{
		$lang->error_boardclosed .= "<blockquote>".$daddyobb->settings['boardclosed_reason']."</blockquote>";
		archive_error($lang->error_boardclosed);
	}
}

// Load Limiting - DIRECTORY_SEPARATOR checks if running windows
if(DIRECTORY_SEPARATOR != '\\')
{
	if($uptime = @exec('uptime'))
	{
		preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $uptime, $regs);
		$load = $regs[1];
		if($daddyobb->usergroup['cancp'] != 1 && $load > $daddyobb->settings['load'] && $daddyobb->settings['load'] > 0)
		{
			archive_error($lang->error_loadlimit);
		}
	}
}

if($daddyobb->usergroup['canview'] == 0)
{
	archive_error_no_permission();
}
?>