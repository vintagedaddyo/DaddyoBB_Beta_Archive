<?php
/**
  * DaddyoBB 1.0 Beta
  * Copyright © 2008 DaddyoBB Group, All Rights Reserved
  *
  * Website: http://www.daddyobb.com
  * License: http://www.daddyobb.com/license
  *
  * 00:14 20.12.2008
  */

/**
 * Check if the current user has permission to perform a ModCP action on another user
 *
 * @param int The user ID to perform the action on.
 * @param int the moderators user ID
 * @return boolean True if the user has necessary permissions
 */
function modcp_can_manage_user($uid)
{
	global $daddyobb;

	$user_permissions = user_permissions($uid);

	// Current user is only a local moderator or use with Mod CP permissions, cannot manage super mods or admins
	if($daddyobb->usergroup['issupermod'] == 0 && ($user_permissions['issupermod'] == 1 || $user_permissions['cancp'] == 1))
	{
		return false;
	}
	// Current user is a super mod or is an administrator
	else if($user_permissions['cancp'] == 1 && ($daddyobb->usergroup['cancp'] != 1 || (is_super_admin($uid) && !is_super_admin($daddyobb->user['uid'])))) 
	{
		return false;
	}
	//Current user can access Mod CP but only with some permission restrictions
	else if($daddyobb->usergroup['modcanmanageprofiles'] != 1)
	{
    return false;
	}
	return true;
}

function fetch_forum_announcements($pid=0, $depth=1)
{
	global $daddyobb, $db, $lang, $announcements, $templates, $announcements_forum, $moderated_forums;
	static $forums_by_parent, $forum_cache, $parent_forums;

	if(!is_array($forum_cache))
	{
		$forum_cache = cache_forums();
	}
	if(!is_array($parent_forums) && $daddyobb->user['issupermod'] != 1)
	{
		// Get a list of parentforums to show for normal moderators
		$parent_forums = array();
		foreach($moderated_forums as $mfid)
		{
			$parent_forums = array_merge($parent_forums, explode(',', $forum_cache[$mfid]['parentlist']));
		}
	}
	if(!is_array($forums_by_parent))
	{
		foreach($forum_cache as $forum)
		{
			$forums_by_parent[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
		}
	}

	if(!is_array($forums_by_parent[$pid]))
	{
		return;
	}

	foreach($forums_by_parent[$pid] as $children)
	{
		foreach($children as $forum)
		{
			if($forum['active'] == 0 || !is_moderator($forum['fid']))
			{
				// Check if this forum is a parent of a moderated forum
				if(in_array($forum['fid'], $parent_forums))
				{
					// A child is moderated, so print out this forum's title.  RECURSE!
					$trow = alt_trow();
					eval("\$announcements_forum .= \"".$templates->get("modcp_announcements_forum_nomod")."\";");
				}
				else
				{
					// No subforum is moderated by this mod, so safely continue
					continue;
				}
			}
			else
			{
				// This forum is moderated by the user, so print out the forum's title, and its announcements
				$trow = alt_trow();
				
				$padding = 40*($depth-1);
				
				eval("\$announcements_forum .= \"".$templates->get("modcp_announcements_forum")."\";");
					
				if($announcements[$forum['fid']])
				{
					foreach($announcements[$forum['fid']] as $aid => $announcement)
					{
						$trow = alt_trow();
						
						if($announcement['enddate'] < TIME_NOW && $announcement['enddate'] != 0)
						{
							$icon = "<img src=\"images/minioff.gif\" alt=\"({$lang->expired})\" title=\"{$lang->expired_announcement}\"  style=\"vertical-align: middle;\" /> ";
						}
						else
						{
							$icon = "<img src=\"images/minion.gif\" alt=\"({$lang->active})\" title=\"{$lang->active_announcement}\"  style=\"vertical-align: middle;\" /> ";
						}
						
						$subject = htmlspecialchars_uni($announcement['subject']);
								
						eval("\$announcements_forum .= \"".$templates->get("modcp_announcements_announcement")."\";");
					}
				}
			}

			// Build the list for any sub forums of this forum
			if($forums_by_parent[$forum['fid']])
			{
				fetch_forum_announcements($forum['fid'], $depth+1);
			}
		}
	}
}

?>