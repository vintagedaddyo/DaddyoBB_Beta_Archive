<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:07 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'online.php');

$templatelist = "online,online_row,online_row_ip,online_today,online_today_row,online_iplookup,mostonline";
require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_online.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;
// Load global language phrases
$lang->load("online");

if($daddyobb->usergroup['canviewonline'] == 0)
{
	error_no_permission();
}

// Make navigation
add_breadcrumb($lang->nav_online, "online.php");

if($daddyobb->input['action'] == "today")
{
	add_breadcrumb($lang->nav_onlinetoday);

	$plugins->run_hooks("online_today_start");

	$todaycount = 0;
	$stime = TIME_NOW-(60*60*24);
	$todayrows = '';
	$query = $db->query("
		SELECT u.*
		FROM ".TABLE_PREFIX."users u
		LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid=u.usergroup)
		WHERE u.lastactive > $stime
		ORDER BY u.lastactive DESC
	");
	while($online = $db->fetch_array($query))
	{
		if($online['uid'] > 0)
    {
      $daddyobb_buddies = explode(",", $daddyobb->user['buddylist']);
      if(in_array($online['uid'], $daddyobb_buddies))
      {
        $buddymark = 1;
      }
      else
      {
        $buddymark = 0;
      }          
    }
		if($online['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $online['uid'] == $daddyobb->user['uid'])
		{
			if($online['invisible'] == 1)
			{
				$invisiblemark = "*";
			}
			else
			{
				$invisiblemark = "";
			}
			$username = $online['username'];
			$username = format_name($username, $online['usergroup'], $online['displaygroup']);
			$online['profilelink'] = build_profile_link($username, $online['uid']);
			$onlinetime = my_date($daddyobb->settings['timeformat'], $online['lastactive']);
			eval("\$todayrows .= \"".$templates->get("online_today_row")."\";");
		}
		++$todaycount;
	}
	if($todaycount == 1)
	{
		$onlinetoday = $lang->member_online_today;
	}
	else
	{
		$onlinetoday = $lang->sprintf($lang->members_were_online_today, $todaycount);
	}

	$plugins->run_hooks("online_today_end");

	eval("\$today = \"".$templates->get("online_today")."\";");
	output_page($today);
}
else
{
	$plugins->run_hooks("online_start");

	// Custom sorting options
	if($daddyobb->input['sortby'] == "username")
	{
		$sql = "u.username ASC, s.time DESC";
		$refresh_string = "?sortby=username";
	}
	elseif($daddyobb->input['sortby'] == "location")
	{
		$sql = "s.location, s.time DESC";
		$refresh_string = "?sortby=location";
	}
	// Otherwise sort by last refresh
	else
	{
		switch($db->type)
		{
			case "sqlite3":
			case "sqlite2":
			case "pgsql":		
				$sql = "s.time DESC";
				break;
			default:
				$sql = "IF( s.uid >0, 1, 0 ) DESC, s.time DESC";
				break;
		}
		$refresh_string = '';
	}
	
	$timesearch = TIME_NOW - $daddyobb->settings['wolcutoffmins']*60;

	// Exactly how many users are currently online?
	switch($db->type)
	{
		case "sqlite3":
		case "sqlite2":	
			$query = $db->simple_select("sessions", "COUNT(count_sid)", "(SELECT DISTINCT sid as count_sid FROM ".TABLE_PREFIX."sessions WHERE time > {$timesearch})");
			break;
		case "pgsql":
		default:
			$query = $db->simple_select("sessions", "COUNT(DISTINCT sid) as online", "time > {$timesearch}");
			break;
	}
	$online_count = $db->fetch_field($query, "online");
	
	// How many pages are there?
	$perpage = $daddyobb->settings['threadsperpage'];

	if(intval($daddyobb->input['page']) > 0)
	{
		$page = intval($daddyobb->input['page']);
		$start = ($page-1) * $perpage;
		$pages = ceil($online_count / $perpage);
		if($page > $pages)
		{
			$start = 0;
			$page = 1;
		}
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	// Assemble page URL
	$multipage = multipage($online_count, $perpage, $page, "online.php".$refresh_string);
	
	// Query for active sessions
	$query = $db->query("
		SELECT DISTINCT s.sid, s.ip, s.uid, s.time, s.location, u.username, s.nopermission, s.error, u.invisible, u.usergroup, u.displaygroup
		FROM ".TABLE_PREFIX."sessions s
		LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)
		WHERE s.time>'$timesearch'
		ORDER BY $sql
		LIMIT {$start}, {$perpage}
	");

	// Fetch spiders
	$spiders = $cache->read("spiders");

	while($user = $db->fetch_array($query))
	{
		$plugins->run_hooks("online_user");

		// Fetch the WOL activity
		$user['activity'] = fetch_wol_activity($user['location']);
		$user['activity']['nopermission'] = $user['nopermission']; 
		$user['activity']['error'] = $user['error']; 
		
		$botkey = my_strtolower(str_replace("bot=", '', $user['sid']));

		// Have a registered user
		if($user['uid'] > 0)
		{
			if($users[$user['uid']]['time'] < $user['time'] || !$users[$user['uid']])
			{
				$users[$user['uid']] = $user;
			}
		}
		// Otherwise this session is a bot
		else if(my_strpos($user['sid'], "bot=") !== false && $spiders[$botkey])
		{
			$user['bot'] = $spiders[$botkey]['name'];
			$user['usergroup'] = $spiders[$botkey]['usergroup'];
			$guests[] = $user;
		}
		// Or a guest
		else
		{
			$guests[] = $user;
		}
	}

	// Now we build the actual online rows - we do this separately because we need to query all of the specific activity and location information
	$online_rows = '';
	if(is_array($users))
	{
		reset($users);
		foreach($users as $user)
		{
			$online_rows .= build_wol_row($user);
		}
	}
	if(is_array($guests))
	{
		reset($guests);
		foreach($guests as $user)
		{
			$online_rows .= build_wol_row($user);
		}
	}

	// Set automatic refreshing if enabled
	if($daddyobb->settings['refreshwol'] > 0)
	{
		$refresh_time = $daddyobb->settings['refreshwol'] * 60;
		$refresh = "<meta http-equiv=\"refresh\" content=\"{$refresh_time};URL=online.php{$refresh_string}\" />";
	}
	
	$plugins->run_hooks("online_end");
	
	$whosonline = '';
  // Get the online users.
	$timesearch = TIME_NOW - $daddyobb->settings['wolcutoff'];
	$comma = '';
	$query = $db->query("
		SELECT s.sid, s.ip, s.uid, s.time, s.location, s.location1, u.username, u.invisible, u.usergroup, u.displaygroup
		FROM ".TABLE_PREFIX."sessions s
		LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)
		WHERE s.time>'$timesearch'
		ORDER BY u.username ASC, s.time DESC
	");

	$forum_viewers = array();
  $membercount = 0;
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
  $lang->most_users_online = $lang->sprintf($lang->most_users_online, my_number_format($recordcount), $recorddate, $recordtime);
	
	$lang->online_count = $lang->sprintf($lang->online_count, my_number_format($onlinecount), $onlinebit, $daddyobb->settings['wolcutoffmins'], my_number_format($membercount), $memberbit, my_number_format($anoncount), $anonbit, my_number_format($guestcount), $guestbit);


	eval("\$online = \"".$templates->get("online")."\";");
	output_page($online);
}
?>