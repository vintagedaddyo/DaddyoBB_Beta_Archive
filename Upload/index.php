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

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'index.php');

$templatelist = "index,index_whosonline,index_welcomemembertext,index_welcomeguest,index_whosonline_memberbit,forumbit_depth1_cat,forumbit_depth1_forum,forumbit_depth2_cat,forumbit_depth2_forum,forumbit_depth1_forum_lastpost,forumbit_depth2_forum_lastpost,index_modcolumn,forumbit_moderators,forumbit_subforums,index_welcomeguesttext";
$templatelist .= ",index_birthdays_birthday,index_birthdays,index_pms,index_loginform,index_logoutlink,index_stats,forumbit_depth3,forumbit_depth3_statusicon,index_boardstats";

require_once "./global.php";

require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_forumlist.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

$plugins->run_hooks("index_start");

// Load global language phrases
$lang->load("index");

$logoutlink = $loginform = '';
if($daddyobb->user['uid'] != 0)
{
	eval("\$logoutlink = \"".$templates->get("index_logoutlink")."\";");
}
else
{
	//Checks to make sure the user can login; they haven't had too many tries at logging in.
	//Function call is not fatal
	if(login_attempt_check(false) !== false)
	{
		eval("\$loginform = \"".$templates->get("index_loginform")."\";");
	}
	//Throw the guestmessage
	eval("\$guestmessage =\"".$templates->get("index_guestmessage")."\";");
}

$whosonline = '';
if($daddyobb->settings['showwol'] != 0 && $daddyobb->usergroup['canviewonline'] != 0)
{
	$query = $db->simple_select("users", "uid", "uid = '".intval($daddyobb->user['uid'])."'");
	$uid = $db->fetch_field($query, "uid");
	if(!$uid)
	{
		$uid = -1; //No user can have 0 as uid
	}
	
	// Get the online users.
	$timesearch = TIME_NOW - $daddyobb->settings['wolcutoff'];
	$comma = '';
	$query = $db->query("
		SELECT s.sid, s.ip, s.uid, s.time, s.location, s.location1, u.username, u.invisible, u.usergroup, u.displaygroup
		FROM ".TABLE_PREFIX."sessions s
		LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)
		WHERE s.time>'$timesearch'
		AND s.uid NOT LIKE ".intval($uid)."
		ORDER BY u.username ASC, s.time DESC
	");

	$forum_viewers = array();
	if($daddyobb->user['uid'])
	{
    $membercount = 1;
    $me['username'] = format_name($daddyobb->user['username'], $daddyobb->user['usergroup'], $daddyobb->user['displaygroup']);
		
		//Get number of actualy online Members!
		$query2 = $db->simple_select("sessions", "COUNT(uid) as count", "uid NOT LIKE 0 AND time > $timesearch");
		$onlineusers0 = $db->fetch_field($query2, "count");
		
		if($onlineusers0 <= 1)
		{
      $me_online = build_profile_link($me['username'], $daddyobb->user['uid']);
    }
    else
		{
      $me_online = build_profile_link($me['username'], $daddyobb->user['uid']).", ";
    }
  }
  else
  {
    $membercount = 0;
  }
	$onlinemembers = '';
	$guestcount = 0;
	$anoncount = 0;
	$doneusers = array();

	// Fetch spiders
	$spiders = $cache->read("spiders");

	// Loop through all users.
	while($user = $db->fetch_array($query))
	{
		// Create a key to test if this user is a search bot.
		$botkey = my_strtolower(str_replace("bot=", '', $user['sid']));

 		// Decide what type of user we are dealing with.
		if($user['uid'] > 0)
		{
			// The user is registered.
			if($doneusers[$user['uid']] < $user['time'] || !$doneusers[$user['uid']])
			{
				// If the user is logged in anonymously, update the count for that.
				if($user['invisible'] == 1)
				{
					++$anoncount;
				}
				++$membercount;
				// Mark buddies
				if($daddyobb->user['uid'] != 0)
				{
          $daddyobb_buddies = explode(",", $daddyobb->user['buddylist']);
          if(in_array($user['uid'], $daddyobb_buddies))
          {
            $buddymark = 1;
          }
          else
          {
            $buddymark = 0;
          }          
        }				
				if($user['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $daddyobb->user['uid'])
				{
					// If this usergroup can see anonymously logged-in users, mark them.
					if($user['invisible'] == 1)
					{
						$invisiblemark = 1;
					}
					else
					{
						$invisiblemark = 0;
					}

					// Properly format the username and assign the template.
					$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
					$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
					eval("\$onlinemembers .= \"".$templates->get("index_whosonline_memberbit", 1, 0)."\";");
					$comma = ", ";
				}
				// This user has been handled.
				$doneusers[$user['uid']] = $user['time'];
			}
		}
		elseif(my_strpos($user['sid'], "bot=") !== false && $spiders[$botkey])
		{
			++$botcount;
		}
		else
		{
			// The user is a guest.
			++$guestcount;
		}

		if($user['location1'])
		{
			$forum_viewers[$user['location1']]++;
		}
	}

	// Build the who's online bit on the index page.
	$onlinecount = $membercount + $guestcount;
	if($onlinecount != 1)
	{
		$onlinebit = $lang->online_online_plural;
	}
	else
	{
		$onlinebit = $lang->online_online_singular;
	}
	if($membercount != 1)
	{
		$memberbit = $lang->online_member_plural;
	}
	else
	{
		$memberbit = $lang->online_member_singular;
	}
	if($anoncount != 1)
	{
		$anonbit = $lang->online_anon_plural;
	}
	else
	{
		$anonbit = $lang->online_anon_singular;
	}
	if($guestcount != 1)
	{
		$guestbit = $lang->online_guest_plural;
	}
	else
	{
		$guestbit = $lang->online_guest_singular;
	}
	
	// Find out what the highest users online count is.
  $mostonline = $cache->read("mostonline");
  if($onlinecount > $mostonline['numusers'])
  {
    $time = TIME_NOW;
    $mostonline['numusers'] = $onlinecount;
    $mostonline['time'] = $time;
    $cache->update("mostonline", $mostonline);
  }
  $recordcount = $mostonline['numusers'];
  $recorddate = my_date($daddyobb->settings['dateformat'], $mostonline['time']);
  $recordtime = my_date($daddyobb->settings['timeformat'], $mostonline['time']);

  // Then format that language string.
  $lang->stats_mostonline = $lang->sprintf($lang->stats_mostonline, my_number_format($recordcount), $recorddate, $recordtime);
	
	$lang->online_note = $lang->sprintf($lang->online_note, my_number_format($onlinecount), $onlinebit, $daddyobb->settings['wolcutoffmins'], my_number_format($membercount), $memberbit, my_number_format($anoncount), $anonbit, my_number_format($guestcount), $guestbit);
	eval("\$whosonline = \"".$templates->get("index_whosonline")."\";");
}

// Build the birthdays for to show on the index page.
$bdays = $birthdays = '';
if($daddyobb->settings['showbirthdays'] != 0)
{
	// First, see what day this is.
	$bdaycount = 0; $bdayhidden = 0;
	$bdaytime = TIME_NOW;
	$bdaydate = my_date("j-n", $bdaytime, '', 0);
	$year = my_date("Y", $bdaytime, '', 0);
	
	$bdaycache = $cache->read("birthdays");
	
	if(!is_array($bdaycache))
	{
		$cache->update_birthdays();
		$bdaycache = $cache->read("birthdays");
	}
	
	$hiddencount = $bdaycache[$bdaydate]['hiddencount'];
	$today_bdays = $bdaycache[$bdaydate]['users'];
	
	$comma = '';
	if(!empty($today_bdays))
	{
		foreach($today_bdays as $bdayuser)
		{
			$bday = explode("-", $bdayuser['birthday']);
			if($year > $bday['2'] && $bday['2'] != '')
			{
				$age = " (".($year - $bday['2']).")";
			}
			else
			{
				$age = '';
			}
			$bdayuser['username'] = format_name($bdayuser['username'], $bdayuser['usergroup'], $bdayuser['displaygroup']);
			$bdayuser['profilelink'] = build_profile_link($bdayuser['username'], $bdayuser['uid']);
			eval("\$bdays .= \"".$templates->get("index_birthdays_birthday", 1, 0)."\";");
			++$bdaycount;
			$comma = ", ";
		}
	}
	
	if($hiddencount > 0)
	{
		if($bdaycount > 0)
		{
			$bdays .= " - ";
		}
		$bdays .= "{$hiddencount} {$lang->birthdayhidden}";
	}
	
	// If there are one or more birthdays, show them.
	if($bdaycount > 0 || $hiddencount > 0)
	{
		eval("\$birthdays = \"".$templates->get("index_birthdays")."\";");
	}
}

// Build the forum statistics to show on the index page.
if($daddyobb->settings['showindexstats'] != 0)
{
	// First, load the stats cache.
	$stats = $cache->read("stats");

	// Check who's the newest member.
	if(!$stats['lastusername'])
	{
		$newestmember = "no-one";
	}
	else
	{
		$newestmember = build_profile_link($stats['lastusername'], $stats['lastuid']);
	}
	
	//Set timeout to 30 days if not set
	if(!$daddyobb->settings['wol_active_timeout'])
	{
    $timeout = TIME_NOW - 60*60*24*30;
	}

  $query = $db->simple_select("users", "COUNT(uid) as count", "lastactive >= '".intval($timeout)."'");
  $active = $db->fetch_field($query, "count");

	// Format the stats language.
	$lang->stats_posts_threads = $lang->sprintf($lang->stats_posts_threads, my_number_format($stats['numposts']), my_number_format($stats['numthreads']), my_number_format($stats['numusers']), my_number_format($active));
	$lang->stats_newestuser = $lang->sprintf($lang->stats_newestuser, $newestmember);

	eval("\$forumstats = \"".$templates->get("index_stats")."\";");
}

// Show the board statistics table only if one or more index statistics are enabled.
if($daddyobb->settings['showwol'] != 0 || $daddyobb->settings['showindexstats'] != 0 || ($daddyobb->settings['showbirthdays'] != 0 && $bdaycount > 0))
{
	if(!is_array($stats))
	{
		// Load the stats cache.
		$stats = $cache->read("stats");
	}
	
	eval("\$boardstats = \"".$templates->get("index_boardstats")."\";");
}

if($daddyobb->user['uid'] == 0)
{
	// Build a forum cache.
	$query = $db->query("
		SELECT *
		FROM ".TABLE_PREFIX."forums
		WHERE active != 0
		ORDER BY pid, disporder
	");
	
	$forumsread = unserialize($daddyobb->cookies['daddyobb']['forumread']);
}
else
{
	// Build a forum cache.
	$query = $db->query("
		SELECT f.*, fr.dateline AS lastread
		FROM ".TABLE_PREFIX."forums f
		LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid=f.fid AND fr.uid='{$daddyobb->user['uid']}')
		WHERE f.active != 0
		ORDER BY pid, disporder
	");
}
while($forum = $db->fetch_array($query))
{
	if($daddyobb->user['uid'] == 0)
	{
		if($forumsread[$forum['fid']])
		{
			$forum['lastread'] = $forumsread[$forum['fid']];
		}
	}
	$fcache[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
}
$forumpermissions = forum_permissions();

// Get the forum moderators if the setting is enabled.
if($daddyobb->settings['modlist'] != "off")
{	
	$moderatorcache = $cache->read("moderators");
}

$excols = "index";
$permissioncache['-1'] = "1";
$bgcolor = "trow1";

// Decide if we're showing first-level subforums on the index page.
if($daddyobb->settings['subforumsindex'] != 0)
{
	$showdepth = 3;
}
else
{
	$showdepth = 2;
}
$forum_list = build_forumbits();
$forums = $forum_list['forum_list'];

$plugins->run_hooks("index_end");

eval("\$index = \"".$templates->get("index")."\";");
output_page($index);

?>