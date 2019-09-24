<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:06 19.12.2008
 */
 
$working_dir = dirname(__FILE__);
if(!$working_dir)
{
	$working_dir = '.';
}

// Load main DaddyoBB core file which begins all of the magic
require_once $working_dir."/inc/init.php";

$shutdown_queries = array();

// Read the usergroups cache as well as the moderators cache
$groupscache = $cache->read("usergroups");

// If the groups cache doesn't exist, update it and re-read it
if(!is_array($groupscache))
{
	$cache->update_usergroups();
	$groupscache = $cache->read("usergroups");
}

if(!defined('THIS_SCRIPT'))
{
	define('THIS_SCRIPT', '');
}

$current_page = my_strtolower(basename(THIS_SCRIPT));


// Send page headers - don't send no-cache headers for attachment.php
if($current_page != "attachment.php")
{
	send_page_headers();
}

// Do not use session system for defined pages
if((@isset($daddyobb->input['action']) && @isset($nosession[$daddyobb->input['action']])) || (@isset($daddyobb->input['thumbnail']) && $current_page == 'attachment.php'))
{
	define("NO_ONLINE", 1);
}

// Create session for this user
require_once DADDYOBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();
$daddyobb->session = &$session;

$daddyobb->user['ismoderator'] = is_moderator("", "", $daddyobb->user['uid']);

// Set our POST validation code here
$daddyobb->post_code = generate_post_check();

// Set and load the language
if($daddyobb->input['language'] && $lang->language_exists($daddyobb->input['language']))
{
	$daddyobb->settings['bblanguage'] = $daddyobb->input['language'];
	// If user is logged in, update their language selection with the new one
	if($daddyobb->user['uid'])
	{
		$db->update_query("users", array("language" => $db->escape_string($daddyobb->settings['bblanguage'])), "uid='{$daddyobb->user['uid']}'");
	}
	// Guest = cookie
	else
	{
		my_setcookie("daddyobblang", $daddyobb->settings['bblanguage']);
	}
}
// Cookied language!
else if($daddyobb->cookies['daddyobblang'] && $lang->language_exists($daddyobb->cookies['daddyobblang']))
{
	$daddyobb->settings['bblanguage'] = $daddyobb->cookies['daddyobblang'];
}
else if(!isset($daddyobb->settings['bblanguage']))
{
	$daddyobb->settings['bblanguage'] = "english";
}

// Load language
$lang->set_language($daddyobb->settings['bblanguage']);
$lang->load("global");
$lang->load("messages");
$lang->load("error");

// Run global_start plugin hook now that the basics are set up
$plugins->run_hooks("global_start");

if(function_exists('mb_internal_encoding') && !empty($lang->settings['charset']))
{
	@mb_internal_encoding($lang->settings['charset']);
}

// Select the board theme to use.
$loadstyle = '';
$load_from_forum = 0;
$style = array();

// This user has a custom theme set in their profile
if(isset($daddyobb->user['style']) && intval($daddyobb->user['style']) != 0)
{
	$loadstyle = "tid='".$daddyobb->user['style']."'";
}

$valid = array(
	"showthread.php", 
	"forumdisplay.php",
	"newthread.php",
	"newreply.php",
	"ratethread.php",
	"editpost.php",
	"polls.php",
	"sendthread.php",
	"printthread.php",
	"moderation.php"
);

if(in_array($current_page, $valid))
{
	// If we're accessing a post, fetch the forum theme for it and if we're overriding it
	if($daddyobb->input['pid'])
	{
		$query = $db->query("
			SELECT f.style, f.overridestyle, p.*
			FROM ".TABLE_PREFIX."forums f
			LEFT JOIN ".TABLE_PREFIX."posts p ON(f.fid=p.fid)
			WHERE p.pid='".intval($daddyobb->input['pid'])."'
			LIMIT 1
		");
		$style = $db->fetch_array($query);
		
		$load_from_forum = 1;
	}
	
	// We have a thread id and a forum id, we can easily fetch the theme for this forum
	else if($daddyobb->input['tid'])
	{
		$query = $db->query("
			SELECT f.style, f.overridestyle, t.*
			FROM ".TABLE_PREFIX."forums f
			LEFT JOIN ".TABLE_PREFIX."threads t ON (f.fid=t.fid)
			WHERE t.tid='".intval($daddyobb->input['tid'])."'
			LIMIT 1
		");
		$style = $db->fetch_array($query);
		$load_from_forum = 1;
	}
	
	// We have a forum id - simply load the theme from it
	else if($daddyobb->input['fid'])
	{
		cache_forums();
		$style = $forum_cache[intval($daddyobb->input['fid'])];
		$load_from_forum = 1;
	}
}
unset($valid);

// From all of the above, a theme was found
if(isset($style['style']) && $style['style'] > 0)
{
	// This theme is forced upon the user, overriding their selection
	if($style['overridestyle'] == 1 || !isset($daddyobb->user['style']))
	{
		$loadstyle = "tid='".intval($style['style'])."'";
	}
}

// After all of that no theme? Load the board default
if(empty($loadstyle))
{
	$loadstyle = "def='1'";
}

// Fetch the theme to load from the database
$query = $db->simple_select("themes", "name, tid, properties, stylesheets", $loadstyle, array('limit' => 1));
$theme = $db->fetch_array($query);

// No theme was found - we attempt to load the master or any other theme
if(!$theme['tid'])
{
	// Missing theme was from a forum, run a query to set any forums using the theme to the default
	if($load_from_forum == 1)
	{
		$db->update_query("forums", array("style" => 0), "style='{$style['style']}'");
	}
	// Missing theme was from a user, run a query to set any users using the theme to the default
	else if($load_from_user == 1)
	{
		$db->update_query("users", array("style" => 0), "style='{$style['style']}'");
	}
	// Attempt to load the master or any other theme if the master is not available
	$query = $db->simple_select("themes", "name, tid, properties, stylesheets", "", array("order_by" => "tid", "limit" => 1));
	$theme = $db->fetch_array($query);
}
$theme = @array_merge($theme, unserialize($theme['properties']));

// Fetch all necessary stylesheets
$theme['stylesheets'] = unserialize($theme['stylesheets']);
$stylesheet_scripts = array("global", basename($_SERVER['PHP_SELF']));
foreach($stylesheet_scripts as $stylesheet_script)
{
	$stylesheet_actions = array("global");
	if($daddyobb->input['action'])
	{
		$stylesheet_actions[] = $daddyobb->input['action'];
	}
	// Load stylesheets for global actions and the current action
	foreach($stylesheet_actions as $stylesheet_action)
	{
		if(!$stylesheet_action)
		{
			continue;
		}
		
		if($theme['stylesheets'][$stylesheet_script][$stylesheet_action])
		{
			// Actually add the stylesheets to the list
			foreach($theme['stylesheets'][$stylesheet_script][$stylesheet_action] as $page_stylesheet)
			{
				if($already_loaded[$page_stylesheet])
				{
					continue;
				}
				$stylesheets .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$daddyobb->settings['bburl']}/{$page_stylesheet}\" />\n";
				$already_loaded[$page_stylesheet] = 1;
			}
		}
	}
}

if(!@is_dir($theme['imgdir']))
{
	$theme['imgdir'] = "images";
} 

// If a language directory for the current language exists within the theme - we use it
if(!empty($daddyobb->user['language']) && is_dir($theme['imgdir'].'/'.$daddyobb->user['language']))
{
	$theme['imglangdir'] = $theme['imgdir'].'/'.$daddyobb->user['language'];
}
else
{
	// Check if a custom language directory exists for this theme
	if(is_dir($theme['imgdir'].'/'.$daddyobb->settings['bblanguage']))
	{
		$theme['imglangdir'] = $theme['imgdir'].'/'.$daddyobb->settings['bblanguage'];
	}
	// Otherwise, the image language directory is the same as the language directory for the theme
	else
	{
		$theme['imglangdir'] = $theme['imgdir'];
	}
}

// Theme logo - is it a relative URL to the forum root? Append bburl
if(!preg_match("#^(\.\.?(/|$)|([a-z0-9]+)://)#i", $theme['logo']) && $theme['logo']{0} != "/")
{
	$theme['logo'] = $daddyobb->settings['bburl']."/".$theme['logo'];
}

// Load Main Templates and Cached Templates
if(isset($templatelist))
{
	$templatelist .= ',';
}
$templatelist .= "css,headerinclude,header,footer,gobutton,htmldoctype,header_welcomeblock_member,header_welcomeblock_guest,header_welcomeblock_member_admin,global_pm_alert,global_unreadreports";
$templatelist .= ",nav,nav_sep,nav_bit,nav_sep_active,nav_bit_active,footer_languageselect,header_welcomeblock_member_moderator,redirect,error";
$templates->cache($db->escape_string($templatelist));

// Set the current date and time now
$datenow = my_date($daddyobb->settings['dateformat'], TIME_NOW, '', false);
$timenow = my_date($daddyobb->settings['timeformat'], TIME_NOW);

//Do we have a member? Then load the user's timezone
if($daddyobb->user['uid'])
{
				if($daddyobb->user['timezone'] > 0)
				{
						$gmt_tz = explode("+", $daddyobb->user['timezone']);
						if($gmt_tz[0] && !$gmt_tz[1])
						{
							$gmt = $gmt_tz[0];
						}
						else
						{ 
							$gmt = $gmt_tz[1];
						}
						if($daddyobb->user['dst'] == 1)
						{
              $gmt++;
              $gmt = " +".$gmt;
						}
            else
            {
              $gmt = " +".$gmt;
            }
				}
				if($daddyobb->user['timezone'] < 0)
				{
						$gmt = " ".$daddyobb->user['timezone']."";
				}
}

//If not then we have a guest or a bot so load the defaiult
else
{ 
		if($daddyobb->settings['timezoneoffset'] < 0 || $daddyobb->settings['timezoneoffset'])
		$gmt = " ".$daddyobb->settings['timezoneoffset']."";
}

$lang->welcome_current_time = $lang->sprintf($lang->welcome_current_time, $timenow, $gmt);

// Format the last visit date of this user appropriately
if(isset($daddyobb->user['lastvisit']))
{
	$lastvisit = my_date($daddyobb->settings['dateformat'], $daddyobb->user['lastvisit']);
	$lasttime = my_date($daddyobb->settings['timeformat'], $daddyobb->user['lastvisit']);
}

// Otherwise, they've never visited before
else
{
	$lastvisit = $lang->lastvisit_never;
}

// If the board is closed and we have an Administrator, show board closed warning
$bbclosedwarning = '';
if($daddyobb->settings['boardclosed'] == 1 && $daddyobb->usergroup['cancp'] == 1)
{
	eval("\$bbclosedwarning = \"".$templates->get("global_boardclosed_warning")."\";");
}

// Prepare the main templates for use
unset($admincplink);

$unreadreports = '';
// This user is a moderator, super moderator or administrator
if($daddyobb->usergroup['cancp'] == 1 || $daddyobb->user['ismoderator'] || $daddyobb->usergroup['canmodcp'])
{
	// Read the reported posts cache
	$reported = $cache->read("reportedposts");

  $notifications = $notifications+$reported['unread'];
}

// Load appropriate welcome block for the current logged in user but is not banned!
if($daddyobb->user['uid'] != 0 && $daddyobb->usergroup['isbannedgroup'] != 1)
{
  //Fetch unread and reported VMs
  if($daddyobb->settings['enablevmsystem'] == 1 && $daddyobb->usergroup['canusevms']==1 && $daddyobb->user['enablevms']==1)
  {
    $vms = 1;
    //Select unread NORMAL VMs
    $unr_vm_qry = $db->simple_select("visitormessage", "COUNT(vmid) as count", "unread='".intval(1)."' AND touid='".intval($daddyobb->user['uid'])."' AND status ='".intval(1)."'");
    $unr_vms = $db->fetch_field($unr_vm_qry, "count");
    $notifications = $notifications+$unr_vms;

    //Select unread UNAPPROVED VMs
    $unapp_vm_qry = $db->simple_select("visitormessage", "COUNT(vmid) as count", "touid='".intval($daddyobb->user['uid'])."' AND status ='".intval(0)."'");
    $unapp_vms = $db->fetch_field($unapp_vm_qry, "count");
    $notifications = $notifications+$unapp_vms;
   
    if($daddyobb->usergroup['issupermod'] == 1)
    {
      $rep_vm_qry = $db->simple_select("visitormessage", "COUNT(vmid) as count", "status='".intval(2)."'");
      $rep_vms = $db->fetch_field($rep_vm_qry, "count");
      $notifications = $notifications+$rep_vms;
    }
  }
	// User can access the admin cp and we're not hiding admin cp links, fetch it
	if($daddyobb->usergroup['cancp'] == 1 && $daddyobb->config['hide_admin_links'] != 1)
	{
		eval("\$admincplink = \"".$templates->get("header_welcomeblock_member_admin")."\";");
	}
	
	if($daddyobb->usergroup['canmodcp'] == 1)
	{
		eval("\$modcplink = \"".$templates->get("header_welcomeblock_member_moderator")."\";");
	}
	
	// Format the welcome back message
	$welcome_link = build_profile_link($daddyobb->user['username'], $daddyobb->user['uid']);
	$lang->welcome_back = $lang->sprintf($lang->welcome_back, $welcome_link, $lastvisit, $lasttime);

	// Tell the user their PM usage
	if($daddyobb->user['pms_unread'] > 0)
	{
		$pmsunread = my_number_format($daddyobb->user['pms_unread']);
		if($notifications != 0)
		{
		  $notifications = $notifications+$daddyobb->user['pms_unread'];
		}
	}
	else
	{
		$pmsunread = my_number_format($daddyobb->user['pms_unread']);
	}
	$notifications = my_number_format($notifications);
	$lang->welcome_pms_usage = $lang->sprintf($lang->welcome_pms_usage, $pmsunread, my_number_format($daddyobb->user['pms_total']));
	$lang->welcome_pms_usage_bold = $lang->sprintf($lang->welcome_pms_usage_bold, $pmsunread, my_number_format($daddyobb->user['pms_total']));
	eval("\$welcomeblock = \"".$templates->get("header_welcomeblock_member")."\";");
}
//And if not than we are maybe banned 
elseif($daddyobb->usergroup['isbannedgroup'] == 1)
{
 $welcomeblock = "";
}
// Otherwise, we have a guest
else
{
	eval("\$welcomeblock = \"".$templates->get("header_welcomeblock_guest")."\";");
}

// Got a character set?
if($lang->settings['charset'])
{
	$charset = $lang->settings['charset'];
}
// If not, revert to UTF-8
else
{
	$charset = "UTF-8";
}

$lang->ajax_loading = str_replace("'", "\\'", $lang->ajax_loading);

// Check if this user has a new private message.
if($daddyobb->user['pmnotice'] == 2 && $daddyobb->user['pms_unread'] > 0 && $daddyobb->settings['enablepms'] != 0 && $daddyobb->usergroup['canusepms'] != 0 && $daddyobb->usergroup['canview'] != 0 && ($current_page != "private.php" || $daddyobb->input['action'] != "read"))
{
	$query = $db->query("
		SELECT pm.subject, pm.pmid, fu.username AS fromusername, fu.uid AS fromuid
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users fu ON (fu.uid=pm.fromid)
		WHERE pm.folder='1' AND pm.uid='{$daddyobb->user['uid']}' AND pm.status='0'
		ORDER BY pm.dateline DESC
		LIMIT 1
	");
	$pm = $db->fetch_array($query);
	
	if($pm['fromuid'] == 0)
	{
		$pm['fromusername'] = 'DaddyoBB Engine';
	}
	
	if($daddyobb->user['pms_unread'] == 1)
	{
		$privatemessage_text = $lang->sprintf($lang->newpm_notice_one, get_profile_link($pm['fromuid']), htmlspecialchars_uni($pm['fromusername']), $pm['pmid'], htmlspecialchars_uni($pm['subject']));
	}
	else
	{
		$privatemessage_text = $lang->sprintf($lang->newpm_notice_multiple, $daddyobb->user['pms_unread'], get_profile_link($pm['fromuid']), htmlspecialchars_uni($pm['fromusername']), $pm['pmid'], htmlspecialchars_uni($pm['subject']));
	}
	eval("\$pm_notice = \"".$templates->get("global_pm_alert")."\";");
}

// Set up some of the default templates
eval("\$headerinclude = \"".$templates->get("headerinclude")."\";");
eval("\$gobutton = \"".$templates->get("gobutton")."\";");
eval("\$htmldoctype = \"".$templates->get("htmldoctype", 1, 0)."\";");
eval("\$header = \"".$templates->get("header")."\";");

$copy_year = my_date("Y", TIME_NOW);

// Check to see if we have any tasks to run
if($daddyobb->settings['taskscron'] != 1)
{
	$task_cache = $cache->read("tasks");
	if(!$task_cache['nextrun'])
	{
		$task_cache['nextrun'] = TIME_NOW;
	}
	if($task_cache['nextrun'] <= TIME_NOW)
	{
		$task_image = "<img src=\"{$daddyobb->settings['bburl']}/task.php\" border=\"0\" width=\"1\" height=\"1\" alt=\"\" />";
	}
	else
	{
		$task_image = '';
	}
}

// Are we showing the quick language selection box?
$lang_select = '';
if($daddyobb->settings['showlanguageselect'] != 0)
{
	$languages = $lang->get_languages();
	foreach($languages as $key => $language)
	{
		$language = htmlspecialchars_uni($language);
		// Current language matches
		if($lang->language == $key)
		{
			$lang_options .= "<option value=\"{$key}\" selected=\"selected\">&nbsp;&nbsp;&nbsp;{$language}</option>\n";
		}
		else
		{
			$lang_options .= "<option value=\"{$key}\">&nbsp;&nbsp;&nbsp;{$language}</option>\n";
		}
	}
	
	$lang_redirect_url = get_current_location(true, 'language');
	
	eval("\$lang_select = \"".$templates->get("footer_languageselect")."\";");
}

// DST Auto detection enabled?
if($daddyobb->user['uid'] > 0 && $daddyobb->user['dstcorrection'] == 2)
{
	$auto_dst_detection = "<script type=\"text/javascript\">if(DaddyoBB) { Event.observe(window, 'load', function() { DaddyoBB.detectDSTChange('".($daddyobb->user['timezone']+$daddyobb->user['dst'])."'); }); }</script>\n";
}

eval("\$footer = \"".$templates->get("footer")."\";");

// Add our main parts to the navigation
$navbits = array();
$navbits[0]['name'] = $daddyobb->settings['bbname_orig'];
$navbits[0]['url'] = $daddyobb->settings['bburl']."/index.php";

// Set the link to the archive.
$archive_url = $daddyobb->settings['bburl']."/archive/index.php";

// Check banned ip addresses
if(is_banned_ip($session->ipaddress, true))
{
	$db->delete_query("sessions", "ip='".$db->escape_string($session->ipaddress)."' OR uid='{$daddyobb->user['uid']}'");
	error($lang->error_banned);
}

// If the board is closed, the user is not an administrator and they're not trying to login, show the board closed message
if($daddyobb->settings['boardclosed'] == 1 && $daddyobb->usergroup['cancp'] != 1 && !($current_page == "member.php" && ($daddyobb->input['action'] == "login" || $daddyobb->input['action'] == "do_login" || $daddyobb->input['action'] == "logout")))
{
	// Show error
	$lang->error_boardclosed .= "<blockquote>{$daddyobb->settings['boardclosed_reason']}</blockquote>";
	error($lang->error_boardclosed);
	exit;
}

// Load Limiting
if($daddyobb->usergroup['cancp'] != 1 && $daddyobb->settings['load'] > 0 && ($load = get_server_load()) && $load != $lang->unknown && $load > $daddyobb->settings['load'])
{
	// User is not an administrator and the load limit is higher than the limit, show an error
	error($lang->error_loadlimit);
}

// If there is a valid referrer in the URL, cookie it
if(!$daddyobb->user['uid'] && $daddyobb->settings['usereferrals'] == 1 && (isset($daddyobb->input['referrer']) || isset($daddyobb->input['referrername'])))
{
	if(isset($daddyobb->input['referrername']))
	{
		$condition = "username='".$db->escape_string($daddyobb->input['referrername'])."'";
	}
	else
	{
		$condition = "uid='".intval($daddyobb->input['referrer'])."'";
	}
	$query = $db->simple_select("users", "uid", $condition, array('limit' => 1));
	$referrer = $db->fetch_array($query);
	if($referrer['uid'])
	{
		my_setcookie("daddyobb[referrer]", $referrer['uid']);
	}
}

// Is this user apart of a banned group?
$bannedwarning = '';
if($daddyobb->usergroup['isbannedgroup'] == 1)
{
  $error_array = array (
		"error" => '1',
		"location1" => 0,
		"location2" => 0
	);
	$db->update_query("sessions", $error_array, "sid='{$session->sid}'", 1);
	
	// Fetch details on their ban
	$query = $db->simple_select("banned", "*", "uid='{$daddyobb->user['uid']}'", array('limit' => 1));
	$ban = $db->fetch_array($query);
	if($ban['uid'])
	{
		// Format their ban lift date and reason appropriately
		if($ban['lifted'] > 0)
		{
			$banlift = my_date($daddyobb->settings['dateformat'], $ban['lifted']) . ", " . my_date($daddyobb->settings['timeformat'], $ban['lifted']);
		}
		else 
		{
			$banlift = $lang->banned_lifted_never;
		}
		$reason = htmlspecialchars_uni($ban['reason']);
	}
	if(empty($reason))
	{
		$reason = $lang->not_known;
	}
	if(empty($banlift))
	{
		$banlift = $lang->never;
	}
	// Display a nice warning to the user
	eval("\$bannedwarning = \"".$templates->get("global_bannedwarning")."\";");
	output_page($bannedwarning);
	exit;
}

if($daddyobb->usergroup['canview'] != 1)
{
	// Check pages allowable even when not allowed to view board
	$allowable_actions = array(
		"member.php" => array(
			"register",
			"do_register",
			"login",
			"do_login",
			"logout",
			"lostpw",
			"do_lostpw",
			"activate",
			"resendactivation",
			"do_resendactivation",
			"resetpassword"
		),
		"usercp2.php" => array(
			"removesubscription",
			"removesubscriptions"
		),
	);
	if(!($current_page == "member.php" && in_array($daddyobb->input['action'], $allowable_actions['member.php'])) && !($current_page == "usercp2.php" && in_array($daddyobb->input['action'], $allowable_actions['usercp2.php'])) && $current_page != "captcha.php")
	{
		error_no_permission();
	}
	unset($allowable_actions);
}

// work out which items the user has collapsed
$colcookie = $daddyobb->cookies['collapsed'];

// set up collapsable items (to automatically show them us expanded)
if($colcookie)
{
	$col = explode("|", $colcookie);
	if(!is_array($col))
	{
		$col[0] = $colcookie; // only one item
	}
	unset($collapsed);
	foreach($col as $key => $val)
	{
		$ex = $val."_e";
		$co = $val."_c";
		$collapsed[$co] = "display: show;";
		$collapsed[$ex] = "display: none;";
		$collapsedimg[$val] = "_collapsed";
	}
}

// Run hooks for end of global.php
$plugins->run_hooks("global_end");

$globaltime = $maintimer->getTime();
?>