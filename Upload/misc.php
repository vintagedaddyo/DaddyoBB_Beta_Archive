<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:06 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'misc.php');

$templatelist = "redirect_markallread,redirect_markforumread";
$templatelist .= ",misc_buddypopup,misc_buddypopup_user_online,misc_buddypopup_user_offline,misc_buddypopup_user_sendpm";
$templatelist .= ",misc_smilies,misc_smilies_smilie,misc_help_section_bit,misc_help_section,misc_help";
require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";

// Load global language phrases
$lang->load("misc");

$plugins->run_hooks("misc_start");

if($daddyobb->input['action'] == "dstswitch" && $daddyobb->request_method == "post" && $daddyobb->user['uid'] > 0)
{
	if($daddyobb->user['dstcorrection'] == 2)
	{
		if($daddyobb->user['dst'] == 1)
		{
			$update_array = array("dst" => 0);
		}
		else
		{
			$update_array = array("dst" => 1);
		}
	}
	$db->update_query("users", $update_array, "uid='{$daddyobb->user['uid']}'");
	if(!$daddyobb->input['ajax'])
	{
		redirect("index.php", $lang->dst_settings_updated);
	}
	else
	{
		echo "done";
		exit;
	}
}
if($daddyobb->input['action'] == "markread")
{
	if($daddyobb->input['fid'])
	{
		$daddyobb->input['fid'] = intval($daddyobb->input['fid']);
		$validforum = get_forum($daddyobb->input['fid']);
		if(!$validforum)
		{
			if(!$daddyobb->input['ajax'])
			{
				error($lang->error_invalidforum);
			}
			else
			{
				echo 0;
				exit;
			}
		}

		require_once DADDYOBB_ROOT."/inc/functions_indicators.php";
		mark_forum_read($daddyobb->input['fid']);

		$plugins->run_hooks("misc_markread_forum");

		if(!$daddyobb->input['ajax'])
		{
			redirect(get_forum_link($daddyobb->input['fid']), $lang->redirect_markforumread);
		}
		else
		{
			echo 1;
			exit;
		}
	}
	else
	{

		$plugins->run_hooks("misc_markread_end");
		require_once DADDYOBB_ROOT."/inc/functions_indicators.php";
		mark_all_forums_read();
		redirect("index.php", $lang->redirect_markforumsread);
	}
}
elseif($daddyobb->input['action'] == "clearpass")
{
	$plugins->run_hooks("misc_clearpass");

	if($daddyobb->input['fid'])
	{
		my_unsetcookie("forumpass[".intval($daddyobb->input['fid'])."]");
		redirect("index.php", $lang->redirect_forumpasscleared);
	}
}
elseif($daddyobb->input['action'] == "rules")
{
	if($daddyobb->input['fid'])
	{
		$plugins->run_hooks("misc_rules_start");

		$query = $db->simple_select("forums", "*", "fid='".intval($daddyobb->input['fid'])."' AND active!=0");
		$forum = $db->fetch_array($query);

		$forumpermissions = forum_permissions($forum['fid']);

		if($forum['type'] != "f" || $forum['rules'] == '')
		{
			error($lang->error_invalidforum);
		}
		if($forumpermissions['canview'] != 1)
		{
			error_no_permission();
		}
		if(!$forum['rulestitle'])
		{
			$forum['rulestitle'] = $lang->sprintf($lang->forum_rules, $forum['name']);
		}

		require_once DADDYOBB_ROOT."inc/class_parser.php";
		$parser = new postParser();
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"filter_badwords" => 1
		);

		$forum['rules'] = $parser->parse_message($forum['rules'], $parser_options);

		// Make navigation
		build_forum_breadcrumb($daddyobb->input['fid']);
		add_breadcrumb($forum['rulestitle']);

		$plugins->run_hooks("misc_rules_end");

		eval("\$rules = \"".$templates->get("misc_rules_forum")."\";");
		output_page($rules);
	}

}
elseif($daddyobb->input['action'] == "help")
{
	$lang->load("helpdocs");
	$lang->load("helpsections");
	$lang->load("customhelpdocs");
	$lang->load("customhelpsections");

	add_breadcrumb($lang->nav_helpdocs, "misc.php?action=help");

	$query = $db->query("
		SELECT h.*, s.enabled AS section
		FROM ".TABLE_PREFIX."helpdocs h
		LEFT JOIN ".TABLE_PREFIX."helpsections s ON (s.sid=h.sid)
		WHERE h.hid='".intval($daddyobb->input['hid'])."'
	");
	$helpdoc = $db->fetch_array($query);
	if($helpdoc['hid'])
	{
		if($helpdoc['section'] != 0 && $helpdoc['enabled'] != 0)
		{
			$plugins->run_hooks("misc_help_helpdoc_start");

			if($helpdoc['usetranslation'] == 1)
			{
				$langnamevar = "d".$helpdoc['hid']."_name";
				$langdescvar = "d".$helpdoc['hid']."_desc";
				$langdocvar = "d".$helpdoc['hid']."_document";
				if($lang->$langnamevar)
				{
					$helpdoc['name'] = $lang->$langnamevar;
				}
				if($lang->$langdescvar)
				{
					$helpdoc['description'] = $lang->$langdescvar;
				}
				if($lang->$langdocvar)
				{
					$helpdoc['document'] = $lang->$langdocvar;
					
					if($langdocvar == "d3_document")
					{
						$helpdoc['document'] = $lang->sprintf($helpdoc['document'], $daddyobb->user['logoutkey']);
					}
				}
			}
			add_breadcrumb($helpdoc['name']);

			$plugins->run_hooks("misc_help_helpdoc_end");

			eval("\$helppage = \"".$templates->get("misc_help_helpdoc")."\";");
			output_page($helppage);
		}
		else
		{
			error($lang->error_invalidhelpdoc);
		}
	}
	else
	{
		$plugins->run_hooks("misc_help_section_start");

		$query = $db->simple_select("helpdocs", "*", "", array('order_by' => 'sid, disporder'));
		while($helpdoc = $db->fetch_array($query))
		{
			$helpdocs[$helpdoc['sid']][$helpdoc['disporder']][$helpdoc['hid']] = $helpdoc;
		}
		unset($helpdoc);
		$sections = '';
		$query = $db->simple_select("helpsections", "*", "enabled != 0", array('order_by' => 'disporder'));
		while($section = $db->fetch_array($query))
		{
			if($section['usetranslation'] == 1)
			{
				$langnamevar = "s".$section['sid']."_name";
				$langdescvar = "s".$section['sid']."_desc";
				if($lang->$langnamevar)
				{
					$section['name'] = $lang->$langnamevar;
				}
				if($lang->$langdescvar)
				{
					$section['description'] = $lang->$langdescvar;
				}
			}
			if(is_array($helpdocs[$section['sid']]))
			{
				$helpbits = '';
				// Expand (or Collapse) forums
				if($daddyobb->input['action'] == "expand")
				{
					my_setcookie("fcollapse[{$section['sid']}]", '');
					$scollapse[$section['sid']] = '';
				}
				elseif($daddyobb->input['action'] == "collapse")
				{
					my_setcookie("fcollapse[{$section['sid']}]", "y");
					$scollapse[$section['sid']] = "y";
				}
				foreach($helpdocs[$section['sid']] as $key => $bit)
				{
					foreach($bit as $key => $helpdoc)
					{
						if($helpdoc['enabled'] != 0)
						{
							if($helpdoc['usetranslation'] == 1)
							{
								$langnamevar = "d".$helpdoc['hid'].'_name';
								$langdescvar = "d".$helpdoc['hid'].'_desc';
								if($lang->$langnamevar)
								{
									$helpdoc['name'] = $lang->$langnamevar;
								}
								if($lang->$langdescvar)
								{
									$helpdoc['description'] = $lang->$langdescvar;
								}
							}
							$altbg = alt_trow();
							eval("\$helpbits .= \"".$templates->get("misc_help_section_bit")."\";");
						}
					}
					$expdisplay = '';
					$sname = "sid_".$section['sid']."_c";
					if($collapsed[$sname] == "display: show;")
					{
						$expcolimage = "collapse_collapsed.gif";
						$expdisplay = "display: none;";
					}
					else
					{
						$expcolimage = "collapse.gif";
					}
				}
				eval("\$sections .= \"".$templates->get("misc_help_section")."\";");
			}
		}

		$plugins->run_hooks("misc_help_section_end");

		eval("\$help = \"".$templates->get("misc_help")."\";");
		output_page($help);
	}
}
elseif($daddyobb->input['action'] == "buddypopup")
{
	$plugins->run_hooks("misc_buddypopup_start");

	if($daddyobb->user['uid'] == 0)
	{
		error_no_permission();
	}
	if($daddyobb->input['removebuddy'])
	{
		$buddies = $daddyobb->user['buddylist'];
		$namesarray = explode(",",$buddies);
		if(is_array($namesarray))
		{
			foreach($namesarray as $key => $buddyid)
			{
				if($buddyid == $daddyobb->input['removebuddy'])
				{
					unset($namesarray[$key]);
				}
			}
			$buddylist = implode(',', $namesarray);
			$db->update_query("users", array('buddylist' => $buddylist), "uid='".$daddyobb->user['uid']."'");
			$daddyobb->user['buddylist'] = $buddylist;
		}
	}
	// Load Buddies
	if($daddyobb->user['buddylist'] != "")
	{
		$timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];
		$query = $db->query("
			SELECT u.*, g.canusepms
			FROM ".TABLE_PREFIX."users u
			LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid=u.usergroup)
			WHERE u.uid IN ({$daddyobb->user['buddylist']})
			ORDER BY u.lastactive
		");
		while($buddy = $db->fetch_array($query))
		{
			$buddy_name = format_name($buddy['username'], $buddy['usergroup'], $buddy['displaygroup']);
			$profile_link = build_profile_link($buddy_name, $buddy['uid'], '_blank', 'if(window.opener) { window.opener.location = this.href; return false; }');
			if($daddyobb->user['receivepms'] != 0 && $buddy['receivepms'] != 0 && $buddy['canusepms'] != 0)
			{
				eval("\$send_pm = \"".$templates->get("misc_buddypopup_user_sendpm")."\";");
			}
			else
			{
				$send_pm = '';
			}
			if($buddy['lastactive'])
			{
				$last_active = $lang->sprintf($lang->last_active, my_date($daddyobb->settings['dateformat'], $buddy['lastactive']).", ".my_date($daddyobb->settings['timeformat'], $buddy['lastactive']));
			}
			else
			{
				$last_active = $lang->sprintf($lang->last_active, $lang->never);
			}

			if($buddy['avatar'])
			{
				if($buddy['avatardimensions'])
				{
					require_once DADDYOBB_ROOT."inc/functions_image.php";
					list($width, $height) = explode("|", $buddy['avatardimensions']);
					$scaled_dimensions = scale_image($width, $height, 44, 44);
				}
				else
				{
					$scaled_dimensions = array(
						"width" => 44,
						"height" => 44
					);
				}
			}
			else
			{
				$buddy['avatar'] = $theme['imgdir'] . "/default_avatar.gif";
				$scaled_dimensions = array(
					"width" => 44,
					"height" => 44
				);
			}
			$margin_top = ceil((50-$scaled_dimensions['height'])/2);
			if($buddy['lastactive'] > $timecut && ($buddy['invisible'] == 0 || $daddyobb->user['usergroup'] == 4) && $buddy['lastvisit'] != $buddy['lastactive'])
			{
				eval("\$buddys['online'] .= \"".$templates->get("misc_buddypopup_user_online")."\";");
			}
			else
			{
				eval("\$buddys['offline'] .= \"".$templates->get("misc_buddypopup_user_offline")."\";");
			}
		}
	}

	$plugins->run_hooks("misc_buddypopup_end");

	eval("\$buddylist = \"".$templates->get("misc_buddypopup")."\";");
	output_page($buddylist);
}
elseif($daddyobb->input['action'] == "whoposted")
{
	$numposts = 0;
	$altbg = "trow1";
	$whoposted = '';
	$tid = intval($daddyobb->input['tid']);
	$thread = get_thread($tid);
	// Make sure we are looking at a real thread here.
	if(!$thread['tid'] || ($thread['visible'] == 0 && $ismod == false) || ($thread['visible'] > 1 && $ismod == true))
	{
		error($lang->error_invalidthread);
	}
	// Does the thread belong to a valid forum?
	$forum = get_forum($thread['fid']);
	if(!$forum || $forum['type'] != "f")
	{
		error($lang->error_invalidforum);
	}
	
	// Does the user have permission to view this thread?
	$forumpermissions = forum_permissions($forum['fid']);
	
	if($forumpermissions['canview'] != 1 || $forumpermissions['canviewthreads'] != 1)
	{
		error_no_permission();
	}
	
	// Check if this forum is password protected and we have a valid password
	check_forum_password($forum['fid']);
	
	if($daddyobb->input['sort'] != 'username')
	{
		$sortsql = ' ORDER BY posts DESC';
	}
	else
	{
		$sortsql = ' ORDER BY p.username ASC';
	}
	$query = $db->query("
		SELECT COUNT(p.pid) AS posts, p.username AS postusername, u.uid, u.username, u.usergroup, u.displaygroup
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		WHERE tid='".$tid."' AND p.visible='1'
		GROUP BY u.uid
		".$sortsql."
	");
	while($poster = $db->fetch_array($query))
	{
		if($poster['username'] == '')
		{
			$poster['username'] = $poster['postusername'];
		}
		$poster_name = format_name($poster['username'], $poster['usergroup'], $poster['displaygroup']);
		if($poster['uid'])
		{
			$onclick = "opener.location.href='".get_profile_link($poster['uid'])."'; return false;";
		}
		$profile_link = build_profile_link($poster_name, $poster['uid'], '_blank', $onclick);
		$numposts += $poster['posts'];
		eval("\$whoposted .= \"".$templates->get("misc_whoposted_poster")."\";");
		$altbg = alt_trow();
	}
	$numposts = my_number_format($numposts);
	eval("\$whop = \"".$templates->get("misc_whoposted")."\";");
	output_page($whop);
}
elseif($daddyobb->input['action'] == "smilies")
{
	$smilies = '';
	if($daddyobb->input['popup'])
	{ // make small popup list of smilies
		$editor = addslashes(htmlentities($daddyobb->input['editor']));
		$e = 1;
		$class = "trow1";
		$smilies = "<tr>";
		$query = $db->simple_select("smilies", "*", "", array('order_by' => 'disporder'));
		while($smilie = $db->fetch_array($query))
		{
			$smilie['insert'] = addslashes($smilie['find']);
			$smilie['find'] = htmlspecialchars_uni($smilie['find']);
			eval("\$smilies .= \"".$templates->get("misc_smilies_popup_smilie")."\";");
			if($e == 2)
			{
				$smilies .= "</tr><tr>";
				$e = 1;
				$class = alt_trow();
			}
			else
			{
				$e = 2;
			}
		}
		if($e == 2)
		{
			$smilies .= "<td colspan=\"2\" class=\"$class\">&nbsp;</td>";
		}
		eval("\$smiliespage = \"".$templates->get("misc_smilies_popup")."\";");
		output_page($smiliespage);
	}
	else
	{
		add_breadcrumb($lang->nav_smilies);
		$class = "trow1";
		$query = $db->simple_select("smilies", "*", "", array('order_by' => 'disporder'));
		while($smilie = $db->fetch_array($query))
		{
			$smilie['find'] = htmlspecialchars_uni($smilie['find']);
			eval("\$smilies .= \"".$templates->get("misc_smilies_smilie")."\";");
			$class = alt_trow();
		}
		eval("\$smiliespage = \"".$templates->get("misc_smilies")."\";");
		output_page($smiliespage);
	}
}
elseif($daddyobb->input['action'] == "imcenter")
{
	if($daddyobb->input['imtype'] != "aim" && $daddyobb->input['imtype'] != "icq" && $daddyobb->input['imtype'] != "msn" && $daddyobb->input['imtype'] != "yahoo")
	{
		error($lang->error_invalidimtype);
	}
	$uid = $daddyobb->input['uid'];
	$user = get_user($uid);

	if(!$user['username'])
	{
		error($lang->error_invaliduser);
	}
	if(!$user[$daddyobb->input['imtype']])
	{
		error($lang->error_invalidimtype);
	}

	// build im navigation bar
	$navigationbar = $navsep = '';
	if($user['aim'])
	{
		$navigationbar .= "<a href=\"misc.php?action=imcenter&amp;imtype=aim&amp;uid=$uid\">$lang->aol_im</a>";
		$navsep = ' - ';
	}
	if($user['icq'])
	{
		$navigationbar .= "$navsep<a href=\"misc.php?action=imcenter&amp;imtype=icq&amp;uid=$uid\">$lang->icq</a>";
		$navsep = ' - ';
	}
	if($user['msn'])
	{
		$navigationbar .= "$navsep<a href=\"misc.php?action=imcenter&amp;imtype=msn&amp;uid=$uid\">$lang->msn</a>";
		$navsep = ' - ';
	}
	if($user['yahoo'])
	{
		$navigationbar .= "$navsep<a href=\"misc.php?action=imcenter&amp;imtype=yahoo&amp;uid=$uid\">$lang->yahoo_im</a>";
	}
	$lang->msn_address_is = $lang->sprintf($lang->msn_address_is, $user['username']);
	$lang->send_y_message = $lang->sprintf($lang->send_y_message, $user['username']);
	$lang->view_y_profile = $lang->sprintf($lang->view_y_profile, $user['username']);
	$lang->send_icq_message = $lang->sprintf($lang->send_icq_message, $user['username']);
	
	$imtemplate = "misc_imcenter_".$daddyobb->input['imtype'];
	eval("\$imcenter = \"".$templates->get($imtemplate)."\";");
	output_page($imcenter);
}
elseif($daddyobb->input['action'] == "syndication")
{
	$plugins->run_hooks("misc_syndication_start");

	$fid = $daddyobb->input['fid'];
	$version = $daddyobb->input['version'];
	$limit = $daddyobb->input['limit'];
	$forums = $daddyobb->input['forums'];

	add_breadcrumb($lang->nav_syndication);
	$unviewable = get_unviewable_forums();
	if(is_array($forums))
	{
		$unexp = explode(",", $unviewable);
		foreach($unexp as $fid)
		{
			$unview[$fid] = 1;
		}
		$syndicate = '';
		$comma = '';
		foreach($forums as $fid)
		{
			if($fid == "all")
			{
				$all = 1;
				break;
			}
			elseif(is_numeric($fid))
			{
				if(!$unview[$fid])
				{
					$syndicate .= $comma.$fid;
					$comma = ",";
					$flist[$fid] = 1;
				}
			}
		}
		$url = $daddyobb->settings['bburl']."/syndication.php";
		if(!$all)
		{
			$url .= "?fid=$syndicate";
			$add = 1;
		}

		// If the version is not RSS2.0, set the type to Atom1.0.
		if($version != "rss2.0")
		{
			if(!$add)
			{
				$url .= "?";
			}
			else
			{
				$url .= "&";
			}
			$url .= "type=atom1.0";
			$add = 1;
		}
		if(intval($limit) > 0)
		{
			if($limit > 100)
			{
				$limit = 100;
			}
			if(!$add)
			{
				$url .= "?";
			}
			else
			{
				$url .= "&";
			}
			if(is_numeric($limit))
			{
				$url .= "limit=$limit";
			}
		}
		$limit = intval($_POST['limit']);
		eval("\$feedurl = \"".$templates->get("misc_syndication_feedurl")."\";");
	}
	unset($GLOBALS['forumcache']);
	if(!$limit || !is_numeric($limit))
	{
		$limit = 15;
	}

	// If there is no version in the input, check the default (RSS2.0).
	if($version == "atom1.0")
	{
		$atom1check = "checked=\"checked\"";
		$rss2check = '';
	}
	else
	{
		$atom1check = '';
		$rss2check = "checked=\"checked\"";
	}
	$forumselect = makesyndicateforums("", $blah);

	$plugins->run_hooks("misc_syndication_end");

	eval("\$syndication = \"".$templates->get("misc_syndication")."\";");
	output_page($syndication);
}


if($daddyobb->input['action'] == "clearcookies")
{
	$plugins->run_hooks("misc_clearcookies");
	
	if($daddyobb->input['key'] != $daddyobb->user['logoutkey'])
	{
		error($lang->error_invalidkey);
	}

	$remove_cookies = array('daddyobb', 'daddyobbuser', 'daddyobb[password]', 'daddyobb[lastvisit]', 'daddyobb[lastactive]', 'collapsed', 'daddyobb[forumread]', 'daddyobb[threadsread]', 'daddyobbadmin');

	if($daddyobb->settings['cookiedomain'])
	{
		foreach($remove_cookies as $name)
		{
			@my_setcookie($name, '', TIME_NOW-1, $daddyobb->settings['cookiepath'], $daddyobb->settings['cookiedomain']);
		}
	}
	else
	{
		foreach($remove_cookies as $name)
		{
			@my_setcookie($name, '', TIME_NOW-1, $daddyobb->settings['cookiepath']);
		}
	}
	redirect("index.php", $lang->redirect_cookiescleared);
}

function makesyndicateforums($pid="0", $selitem="", $addselect="1", $depth="", $permissions="")
{
	global $db, $forumcache, $permissioncache, $daddyobb, $selecteddone, $forumlist, $forumlistbits, $theme, $templates, $flist, $lang;

	$pid = intval($pid);
	if(!$permissions)
	{
		$permissions = $daddyobb->usergroup;
	}
	if(!is_array($forumcache))
	{
		// Get Forums
		$query = $db->simple_select("forums", "*", "linkto = '' AND active!=0", array('order_by' => 'pid, disporder'));
		while($forum = $db->fetch_array($query))
		{
			$forumcache[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
		}
	}
	if(!is_array($permissioncache))
	{
		$permissioncache = forum_permissions();
	}
	if(is_array($forumcache[$pid]))
	{
		foreach($forumcache[$pid] as $key => $main)
		{
			foreach($main as $key => $forum)
			{
				$perms = $permissioncache[$forum['fid']];
				if($perms['canview'] == 1 || $daddyobb->settings['hideprivateforums'] == 0)
				{
					if($flist[$forum['fid']])
					{
						$optionselected = "selected=\"selected\"";
						$selecteddone = "1";
					}
					else
					{
						$optionselected = '';
					}

					if($forum['password'] == '')
					{
						$forumlistbits .= "<option value=\"{$forum['fid']}\" $optionselected>$depth {$forum['name']}</option>\n";
					}
					if($forumcache[$forum['fid']])
					{
						$newdepth = $depth."&nbsp;&nbsp;&nbsp;&nbsp;";
						$forumlistbits .= makesyndicateforums($forum['fid'], $selitem, 0, $newdepth, $perms);
					}
				}
			}
		}
	}
	if($addselect)
	{
		if(!$selecteddone)
		{
			$addsel = " selected=\"selected\"";
		}
		$forumlist = "<select name=\"forums[]\" size=\"10\" multiple=\"multiple\">\n<option value=\"all\" $addsel>$lang->syndicate_all_forums</option>\n<option value=\"all\">----------------------</option>\n$forumlistbits\n</select>";
	}
	return $forumlist;
}

?>