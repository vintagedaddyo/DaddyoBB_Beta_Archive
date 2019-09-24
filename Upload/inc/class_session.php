<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:22 21.12.2008
  */

class session
{
	var $sid = 0;
	var $uid = 0;
	var $ipaddress = '';
	var $useragent = '';
	var $is_spider = false;
	var $logins = 1;
	var $failedlogin = 0;

	/**
	 * Initialize a session
	 */
	function init()
	{
		global $db, $daddyobb, $cache;

		// Get our visitor's IP.
		$this->ipaddress = get_ip();

		// Find out the user agent.
		$this->useragent = $_SERVER['HTTP_USER_AGENT'];
		if(my_strlen($this->useragent) > 100)
		{
			$this->useragent = my_substr($this->useragent, 0, 100);
		}
		
		// Attempt to find a session id in the cookies.
		if(isset($daddyobb->cookies['sid']))
		{
			$this->sid = $db->escape_string($daddyobb->cookies['sid']);
			// Load the session
			$query = $db->simple_select("sessions", "*", "sid='{$this->sid}' AND ip='".$db->escape_string($this->ipaddress)."'", array('limit' => 1));
			$session = $db->fetch_array($query);
			if($session['sid'])
			{
				$this->sid = $session['sid'];
				$this->uid = $session['uid'];
			}
			else
			{
				$this->sid = 0;
				$this->uid = 0;
				$this->logins = 1;
				$this->failedlogin = 0;
			}
		}

		// Still no session, fall back
		if(!$this->sid)
		{
			$this->sid = 0;
			$this->uid = 0;
			$this->logins = 1;
			$this->failedlogin = 0;
		}

		// If we have a valid session id and user id, load that users session.
		if($daddyobb->cookies['daddyobbuser'])
		{
			$logon = explode("_", $daddyobb->cookies['daddyobbuser'], 2);
			$this->load_user($logon[0], $logon[1]);
		}

		// If no user still, then we have a guest.
		if(!isset($daddyobb->user['uid']))
		{
			// Detect if this guest is a search engine spider. (bots don't get a cookied session ID so we first see if that's set)
			if(!$this->sid)
			{
				$spiders = $cache->read("spiders");
				if(is_array($spiders))
				{
					foreach($spiders as $spider)
					{
						if(my_strpos(my_strtolower($this->useragent), my_strtolower($spider['useragent'])) !== false)
						{
							$this->load_spider($spider['sid']);
						}
					}
				}
			}

			// Still nothing? JUST A GUEST!
			if(!$this->is_spider)
			{
				$this->load_guest();
			}
		}


		// As a token of our appreciation for getting this far (and they aren't a spider), give the user a cookie
		if($this->sid && ($daddyobb->cookies['sid'] != $this->sid) && $this->is_spider != true)
		{
			my_setcookie("sid", $this->sid, -1, true);
		}
	}

	/**
	 * Load a user via the user credentials.
	 *
	 * @param int The user id.
	 * @param string The user's password.
	 */
	function load_user($uid, $password='')
	{
		global $daddyobb, $db, $time, $lang, $daddyobbgroups, $session, $cache;
		
		// Read the banned cache
		$bannedcache = $cache->read("banned");	
		
		// If the banned cache doesn't exist, update it and re-read it
		if(!is_array($bannedcache))
		{
			$cache->update_banned();
			$bannedcache = $cache->read("banned");
		}
		
		$uid = intval($uid);
		$query = $db->query("
			SELECT u.*, f.*
			FROM ".TABLE_PREFIX."users u 
			LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid) 
			WHERE u.uid='$uid'
			LIMIT 1
		");
		$daddyobb->user = $db->fetch_array($query);
		
		$this->logins = $daddyobb->user['loginattempts'];
		$this->failedlogin = $daddyobb->user['failedlogin'];
		
		if($bannedcache[$uid])
		{
			$banned_user = $bannedcache[$uid];
			$daddyobb->user['bandate'] = $banned_user['dateline'];
			$daddyobb->user['banlifted'] = $banned_user['lifted'];
			$daddyobb->user['banoldgroup'] = $banned_user['oldgroup'];
			$daddyobb->user['banolddisplaygroup'] = $banned_user['olddisplaygroup'];
			$daddyobb->user['banoldadditionalgroups'] = $banned_user['oldadditionalgroups'];
		}

		// Check the password if we're not using a session
		if($password != $daddyobb->user['loginkey'] || !$daddyobb->user['uid'])
		{
			unset($daddyobb->user);
			$this->uid = 0;
			return false;
		}
		$this->uid = $daddyobb->user['uid'];

		// Set the logout key for this user
		$daddyobb->user['logoutkey'] = md5($daddyobb->user['loginkey']);

		// Sort out the private message count for this user.
		if(($daddyobb->user['totalpms'] == -1 || $daddyobb->user['unreadpms'] == -1) && $daddyobb->settings['enablepms'] != 0) // Forced recount
		{
			$update = 0;
			if($daddyobb->user['totalpms'] == -1)
			{
				$update += 1;
			}
			if($daddyobb->user['unreadpms'] == -1)
			{
				$update += 2;
			}

			require_once DADDYOBB_ROOT."inc/functions_user.php";
			$pmcount = update_pm_count('', $update);
			if(is_array($pmcount))
			{
				$daddyobb->user = array_merge($daddyobb->user, $pmcount);
			}
		}
		$daddyobb->user['pms_total'] = $daddyobb->user['totalpms'];
		$daddyobb->user['pms_unread'] = $daddyobb->user['unreadpms'];

		if($daddyobb->user['lastip'] != $this->ipaddress && array_key_exists('lastip', $daddyobb->user))
		{
			$lastip_add .= ", lastip='".$db->escape_string($this->ipaddress)."', longlastip='".intval(ip2long($this->ipaddress))."'";
		}

		// If the last visit was over 900 seconds (session time out) ago then update lastvisit.
		$time = TIME_NOW;
		if($time - $daddyobb->user['lastactive'] > 900)
		{
			$db->shutdown_query("UPDATE ".TABLE_PREFIX."users SET lastvisit='{$daddyobb->user['lastactive']}', lastactive='$time' {$lastip_add} WHERE uid='{$daddyobb->user['uid']}'");
			$daddyobb->user['lastvisit'] = $daddyobb->user['lastactive'];
			require_once DADDYOBB_ROOT."inc/functions_user.php";
			update_pm_count('', 2);
		}
		else
		{
			$timespent = TIME_NOW - $daddyobb->user['lastactive'];
			$db->shutdown_query("UPDATE ".TABLE_PREFIX."users SET lastactive='$time' {$lastip_add} WHERE uid='{$daddyobb->user['uid']}'");
		}

		// Sort out the language and forum preferences.
		if($daddyobb->user['language'] && $lang->language_exists($daddyobb->user['language']))
		{
			$daddyobb->settings['bblanguage'] = $daddyobb->user['language'];
		}
		if($daddyobb->user['dateformat'] != 0 && $daddyobb->user['dateformat'] != '')
		{
			global $date_formats;
			if($date_formats[$daddyobb->user['dateformat']])
			{
				$daddyobb->settings['dateformat'] = $date_formats[$daddyobb->user['dateformat']];
			}
		}

		// Choose time format.
		if($daddyobb->user['timeformat'] != 0 && $daddyobb->user['timeformat'] != '')
		{
			global $time_formats;
			if($time_formats[$daddyobb->user['timeformat']])
			{
				$daddyobb->settings['timeformat'] = $time_formats[$daddyobb->user['timeformat']];
			}
		}

		// Find out the threads per page preference.
		if($daddyobb->user['tpp'])
		{
			$daddyobb->settings['threadsperpage'] = $daddyobb->user['tpp'];
		}

		// Find out the posts per page preference.
		if($daddyobb->user['ppp'])
		{
			$daddyobb->settings['postsperpage'] = $daddyobb->user['ppp'];
		}
		
		// Does this user prefer posts in classic mode?
		if($daddyobb->user['classicpostbit'])
		{
			$daddyobb->settings['postlayout'] = 'classic';
		}
		else
		{
			$daddyobb->settings['postlayout'] = 'horizontal';
		}

		// Check if this user is currently banned and if we have to lift it.
		if(!empty($daddyobb->user['bandate']) && (isset($daddyobb->user['banlifted']) && !empty($daddyobb->user['banlifted'])) && $daddyobb->user['banlifted'] < $time)  // hmmm...bad user... how did you get banned =/
		{
			// must have been good.. bans up :D
			$db->shutdown_query("UPDATE ".TABLE_PREFIX."users SET usergroup='".intval($daddyobb->user['banoldgroup'])."', additionalgroups='".$daddyobb->user['oldadditionalgroups']."', displaygroup='".intval($daddyobb->user['olddisplaygroup'])."' WHERE uid='".$daddyobb->user['uid']."' LIMIT 1");
			$db->shutdown_query("DELETE FROM ".TABLE_PREFIX."banned WHERE uid='".$daddyobb->user['uid']."'");
			// we better do this..otherwise they have dodgy permissions
			$daddyobb->user['usergroup'] = $daddyobb->user['banoldgroup'];
			$daddyobb->user['displaygroup'] = $daddyobb->user['banolddisplaygroup'];
			$daddyobb->user['additionalgroups'] = $daddyobb->user['banoldadditionalgroups'];
			$cache->update_banned();

			$daddyobbgroups = $daddyobb->user['usergroup'];
			if($daddyobb->user['additionalgroups'])
			{
				$daddyobbgroups .= ','.$daddyobb->user['additionalgroups'];
			}
		}
		else if(!empty($daddyobb->user['bandate']) && (empty($daddyobb->user['banlifted'])  || !empty($daddyobb->user['banlifted']) && $daddyobb->user['banlifted'] > $time))
        {
            $daddyobbgroups = $daddyobb->user['usergroup'];
        }
        else
        {
			// Gather a full permission set for this user and the groups they are in.
			$daddyobbgroups = $daddyobb->user['usergroup'];
			if($daddyobb->user['additionalgroups'])
			{
				$daddyobbgroups .= ','.$daddyobb->user['additionalgroups'];
			}
        }

		$daddyobb->usergroup = usergroup_permissions($daddyobbgroups);
		if(!$daddyobb->user['displaygroup'])
		{
			$daddyobb->user['displaygroup'] = $daddyobb->user['usergroup'];
		}

		$mydisplaygroup = usergroup_displaygroup($daddyobb->user['displaygroup']);
		if(is_array($mydisplaygroup))
		{
			$daddyobb->usergroup = array_merge($daddyobb->usergroup, $mydisplaygroup);
		}
		
		if(!$daddyobb->user['usertitle'])
		{
			$daddyobb->user['usertitle'] = $daddyobb->usergroup['usertitle'];
		}

		// Update or create the session.
		if(!defined("NO_ONLINE"))
		{
			if(!empty($this->sid))
			{
				$this->update_session($this->sid, $daddyobb->user['uid']);
			}
			else
			{
				$this->create_session($daddyobb->user['uid']);
			}
		}
		return true;
	}

	/**
	 * Load a guest user.
	 *
	 */
	function load_guest()
	{
		global $daddyobb, $time, $db, $lang;

		// Set up some defaults
		$time = TIME_NOW;
		$daddyobb->user['usergroup'] = 1;
		$daddyobb->user['username'] = '';
		$daddyobb->user['uid'] = 0;
		$daddyobbgroups = 1;
		$daddyobb->user['displaygroup'] = 1;

		// Has this user visited before? Lastvisit need updating?
		if(isset($daddyobb->cookies['daddyobb']['lastvisit']))
		{
			if(!isset($daddyobb->cookies['daddyobb']['lastactive']))
			{
				$daddyobb->user['lastactive'] = $time;
				$daddyobb->cookies['daddyobb']['lastactive'] = $daddyobb->user['lastactive'];
			}
			else
			{
				$daddyobb->user['lastactive'] = intval($daddyobb->cookies['daddyobb']['lastactive']);
			}
			if($time - $daddyobb->cookies['daddyobb']['lastactive'] > 900)
			{
				my_setcookie("daddyobb[lastvisit]", $daddyobb->user['lastactive']);
				$daddyobb->user['lastvisit'] = $daddyobb->user['lastactive'];
			}
			else
			{
				$daddyobb->user['lastvisit'] = intval($daddyobb->cookies['daddyobb']['lastactive']);
			}
		}

		// No last visit cookie, create one.
		else
		{
			my_setcookie("daddyobb[lastvisit]", $time);
			$daddyobb->user['lastvisit'] = $time;
		}

		// Update last active cookie.
		my_setcookie("daddyobb[lastactive]", $time);

		// Gather a full permission set for this guest
		$daddyobb->usergroup = usergroup_permissions($daddyobbgroups);
		$mydisplaygroup = usergroup_displaygroup($daddyobb->user['displaygroup']);
		
		$daddyobb->usergroup = array_merge($daddyobb->usergroup, $mydisplaygroup);

		// Update the online data.
		if(!defined("NO_ONLINE"))
		{
			if(!empty($this->sid))
			{
				$this->update_session($this->sid);
			}
			else
			{
				$this->create_session();
			}
		}
	}

	/**
	 * Load a search engine spider.
	 *
	 * @param int The ID of the search engine spider
	 */
	function load_spider($spider_id)
	{
		global $daddyobb, $time, $db, $lang;

		// Fetch the spider preferences from the database
		$query = $db->simple_select("spiders", "*", "sid='{$spider_id}'", array('limit' => 1));
		$spider = $db->fetch_array($query);

		// Set up some defaults
		$time = TIME_NOW;
		$this->is_spider = true;
		if($spider['usergroup'])
		{
			$daddyobb->user['usergroup'] = $spider['usergroup'];
		}
		else
		{
			$daddyobb->user['usergroup'] = 1;
		}
		$daddyobb->user['username'] = '';
		$daddyobb->user['uid'] = 0;
		$daddyobb->user['displaygroup'] = $daddyobb->user['usergroup'];

		// Set spider language
		if($spider['language'] && $lang->language_exists($spider['language']))
		{
			$daddyobb->settings['bblanguage'] = $spider['language'];
		}

		// Set spider theme
		if($spider['theme'])
		{
			$daddyobb->user['style'] = $spider['theme'];
		}

		// Gather a full permission set for this spider.
		$daddyobb->usergroup = usergroup_permissions($daddyobb->user['usergroup']);
		$mydisplaygroup = usergroup_displaygroup($daddyobb->user['displaygroup']);
		$daddyobb->usergroup = array_merge($daddyobb->usergroup, $mydisplaygroup);

		// Update spider last minute (only do so on two minute intervals - decrease load for quick spiders)
		if($spider['lastvisit'] < TIME_NOW-120)
		{
			$updated_spider = array(
				"lastvisit" => TIME_NOW
			);
			$db->update_query("spiders", $updated_spider, "sid='{$spider_id}'", 1);
		}

		// Update the online data.
		if(!defined("NO_ONLINE"))
		{
			$this->sid = "bot=".$spider_id;
			$this->create_session();
		}

	}

	/**
	 * Update a user session.
	 *
	 * @param int The session id.
	 * @param int The user id.
	 */
	function update_session($sid, $uid='')
	{
		global $db;

		// Find out what the special locations are.
		$speciallocs = $this->get_special_locations();
		if($uid)
		{
			$onlinedata['uid'] = $uid;
		}
		else
		{
			$onlinedata['uid'] = 0;
		}
		$onlinedata['time'] = TIME_NOW;
		$onlinedata['location'] = $db->escape_string(get_current_location());
		$onlinedata['useragent'] = $db->escape_string($this->useragent);
		$onlinedata['error'] = 0;
		$onlinedata['location1'] = intval($speciallocs['1']);
		$onlinedata['location2'] = intval($speciallocs['2']);
		$onlinedata['nopermission'] = 0;
		$sid = $db->escape_string($sid);

		$db->update_query("sessions", $onlinedata, "sid='{$sid}'", 1);
	}

	/**
	 * Create a new session.
	 *
	 * @param int The user id to bind the session to.
	 */
	function create_session($uid=0)
	{
		global $db;
		$speciallocs = $this->get_special_locations();

		// If there is a proper uid, delete by uid.
		if($uid > 0)
		{
			$db->delete_query("sessions", "uid='{$uid}'", 1);
			$onlinedata['uid'] = $uid;
		}
		// Is a spider - delete all other spider references
		else if($this->is_spider == true)
		{
			$db->delete_query("sessions", "sid='{$this->sid}'", 1);
		}
		// Else delete by ip.
		else
		{
			$db->delete_query("sessions", "ip='".$db->escape_string($this->ipaddress)."'", 1);
			$onlinedata['uid'] = 0;
		}

		// If the user is a search enginge spider, ...
		if($this->is_spider == true)
		{
			$onlinedata['sid'] = $this->sid;
		}
		else
		{
			$onlinedata['sid'] = md5(uniqid(microtime()));
		}
		$onlinedata['time'] = TIME_NOW;
		$onlinedata['ip'] = $db->escape_string($this->ipaddress);
		$onlinedata['location'] = $db->escape_string(get_current_location());
		$onlinedata['useragent'] = $db->escape_string($this->useragent);
		$onlinedata['error'] = 0;
		$onlinedata['location1'] = intval($speciallocs['1']);
		$onlinedata['location2'] = intval($speciallocs['2']);
		$onlinedata['nopermission'] = 0;
		$db->replace_query("sessions", $onlinedata, "sid", false);
		$this->sid = $onlinedata['sid'];
		$this->uid = $onlinedata['uid'];
	}

	/**
	 * Find out the special locations.
	 *
	 * @return array Special locations array.
	 */
	function get_special_locations()
	{
		global $daddyobb;
		$array = array('1' => '', '2' => '');
		if(preg_match("#forumdisplay.php#", $_SERVER['PHP_SELF']) && intval($daddyobb->input['fid']) > 0)
		{
			$array[1] = intval($daddyobb->input['fid']);
			$array[2] = '';
		}
		elseif(preg_match("#showthread.php#", $_SERVER['PHP_SELF']) && intval($daddyobb->input['tid']) > 0)
		{
			global $db;
			$array[2] = intval($daddyobb->input['tid']);
			$thread = get_thread(intval($array[2]));
			$array[1] = $thread['fid'];
		}
		return $array;
	}
}
?>