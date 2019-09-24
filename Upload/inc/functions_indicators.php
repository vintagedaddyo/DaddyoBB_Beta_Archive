<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2009 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 20:20 19.12.2008
  */

/**
 * Mark a particular thread as read for the current user.
 *
 * @param int The thread ID
 * @param int The forum ID of the thread
 */
function mark_thread_read($tid, $fid)
{
	global $daddyobb, $db;

	// Can only do "true" tracking for registered users
	if($daddyobb->settings['threadreadcut'] > 0 && $daddyobb->user['uid'])
	{
		// For registered users, store the information in the database.
		switch($db->type)
		{
			case "pgsql":
				$db->shutdown_query($db->build_replace_query("threadsread", array('tid' => $tid, 'uid' => $daddyobb->user['uid'], 'dateline' => TIME_NOW), "tid"));
				break;
			default:
				$db->write_query("
					REPLACE INTO ".TABLE_PREFIX."threadsread (tid, uid, dateline)
					VALUES('$tid', '{$daddyobb->user['uid']}', '".TIME_NOW."')
				");
		}

		// Fetch ALL of the child forums of this forum
		$forums = get_child_list($fid);
		$forums[] = $fid;
		$forums = implode(",", $forums);

		$unread_count = fetch_unread_count($forums);
		if($unread_count == 0)
		{
			mark_forum_read($fid);
		}
	}
	// Default back to cookie marking
	else
	{
		my_set_array_cookie("threadread", $tid, TIME_NOW);
	}
}

/**
 * Fetches the number of unread threads for the current user in a particular forum.
 *
 * @param string The forums (CSV list)
 * @return int The number of unread threads
 */
function fetch_unread_count($fid)
{
	global $db, $daddyobb;

	$cutoff = TIME_NOW-$daddyobb->settings['threadreadcut']*60*60*24;

	if($daddyobb->user['uid'] == 0)
	{
		$comma = '';
		$tids = '';
		$threadsread = unserialize($daddyobb->cookies['daddyobb']['threadread']);
		$forumsread = unserialize($daddyobb->cookies['daddyobb']['forumread']);
		if(is_array($threadsread))
		{
			foreach($threadsread as $key => $value)
			{
				$tids .= $comma.intval($key);
				$comma = ',';
			}
		}
		
		if(!empty($tids))
		{
			$count = 0;
			
			// We set a limit to 100 otherwise it'll become too processor intensive, especially if we have many threads.
			$query = $db->query("
				SELECT lastpost, tid, fid
				FROM ".TABLE_PREFIX."threads
				WHERE visible=1 AND closed NOT LIKE 'moved|%' AND fid IN ($fid) AND tid IN ($tids) AND lastpost > '{$cutoff}'
				LIMIT 100
			");
			while($thread = $db->fetch_array($query))
			{
				if($thread['lastpost'] > intval($threadsread[$thread['tid']]) && $thread['lastpost'] > intval($forumsread[$thread['fid']]))
				{
					++$count;
				}
			}
			return $count;
		}
	}
	else
	{
		switch($db->type)
		{
			case "pgsql":
				$query = $db->query("
					SELECT COUNT(t.tid) AS unread_count
					FROM ".TABLE_PREFIX."threads t
					LEFT JOIN ".TABLE_PREFIX."threadsread tr ON (tr.tid=t.tid AND tr.uid='{$daddyobb->user['uid']}')
					LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid=t.fid AND fr.uid='{$daddyobb->user['uid']}')
					WHERE t.visible=1 AND t.closed NOT LIKE 'moved|%' AND t.fid IN ($fid) AND t.lastpost > COALESCE(tr.dateline,$cutoff) AND t.lastpost > COALESCE(fr.dateline,$cutoff) AND t.lastpost>$cutoff
				");
				break;
			default:
				$query = $db->query("
					SELECT COUNT(t.tid) AS unread_count
					FROM ".TABLE_PREFIX."threads t
					LEFT JOIN ".TABLE_PREFIX."threadsread tr ON (tr.tid=t.tid AND tr.uid='{$daddyobb->user['uid']}')
					LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid=t.fid AND fr.uid='{$daddyobb->user['uid']}')
					WHERE t.visible=1 AND t.closed NOT LIKE 'moved|%' AND t.fid IN ($fid) AND t.lastpost > IFNULL(tr.dateline,$cutoff) AND t.lastpost > IFNULL(fr.dateline,$cutoff) AND t.lastpost>$cutoff
				");
		}
		return $db->fetch_field($query, "unread_count");
	}
}

/**
 * Mark a particular forum as read.
 *
 * @param int The forum ID
 */
function mark_forum_read($fid)
{
	global $daddyobb, $db;

	// Can only do "true" tracking for registered users
	if($daddyobb->settings['threadreadcut'] > 0 && $daddyobb->user['uid'])
	{
		switch($db->type)
		{
			case "pgsql":
				$db->shutdown_query($db->build_replace_query("forumsread", array('fid' => $fid, 'uid' => $daddyobb->user['uid'], 'dateline' => TIME_NOW), "fid"));
				break;
			default:
				$db->shutdown_query("
					REPLACE INTO ".TABLE_PREFIX."forumsread (fid, uid, dateline)
					VALUES('{$fid}', '{$daddyobb->user['uid']}', '".TIME_NOW."')
				");
		}
	}
	// Mark in a cookie
	else
	{
		my_set_array_cookie("forumread", $fid, TIME_NOW);
	}
}

/**
 * Marks all forums as read.
 *
 */
function mark_all_forums_read()
{
	global $daddyobb, $db, $cache;

	// Can only do "true" tracking for registered users
	if($daddyobb->user['uid'] > 0)
	{
		$db->update_query("users", array('lastvisit' => TIME_NOW), "uid='".$daddyobb->user['uid']."'");
		require_once DADDYOBB_ROOT."inc/functions_user.php";
		update_pm_count('', 2);

		if($daddyobb->settings['threadreadcut'] > 0)
		{
			// Need to loop through all forums and mark them as read
			$forums = $cache->read('forums');
			
			$update_count = ceil(count($forums)/20);
			
			if($update_count < 15)
			{
				$update_count = 15;
			}
			
			$mark_query = '';
			$done = 0;
			foreach(array_keys($forums) as $fid)
			{				
				switch($db->type)
				{
					case "pgsql":
						$mark_query[] = array('fid' => $fid, 'uid' => $daddyobb->user['uid'], 'dateline' => TIME_NOW);
						break;
					default:
						if($mark_query != '') $mark_query .= ', ';
						$mark_query .= "('{$fid}', '{$daddyobb->user['uid']}', '".TIME_NOW."')";
				}
				++$done;
				
				// Only do this in loops of $update_count, save query time
				if($done % $update_count)
				{
					switch($db->type)
					{
						case "pgsql":
							foreach($mark_query as $replace_query)
							{
								$db->shutdown_query($db->build_replace_query("forumsread", $replace_query, "fid"));
							}
							$mark_query = array();
							break;
						default:
							$db->shutdown_query("
								REPLACE INTO ".TABLE_PREFIX."forumsread (fid, uid, dateline)
								VALUES {$mark_query}
							");
							$mark_query = '';
					}
				}
			}
			
			if($mark_query != '')
			{
				switch($db->type)
				{
					case "pgsql":
						foreach($mark_query as $replace_query)
						{
							$db->shutdown_query($db->build_replace_query("forumsread", $replace_query, "fid"));
						}
						break;
					default:
						$db->shutdown_query("
							REPLACE INTO ".TABLE_PREFIX."forumsread (fid, uid, dateline)
							VALUES {$mark_query}
						");
				}
			}
		}
	}
	else
	{
		my_setcookie("daddyobb[lastvisit]", TIME_NOW);
		my_unsetcookie("daddyobb[threadread]");
		my_unsetcookie("daddyobb[forumread]");
	}
}
?>