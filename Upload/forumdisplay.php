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
define('THIS_SCRIPT', 'forumdisplay.php');

$templatelist = "forumdisplay,forumdisplay_thread,breadcrumb_bit,forumbit_depth1_cat,forumbit_depth1_forum,forumbit_depth2_cat,forumbit_depth2_forum,forumdisplay_subforums,forumdisplay_threadlist,forumdisplay_moderatedby_moderator,forumdisplay_moderatedby,forumdisplay_newthread,forumdisplay_searchforum,forumdisplay_orderarrow,forumdisplay_thread_rating,forumdisplay_announcement,forumdisplay_threadlist_rating,forumdisplay_threadlist_sortrating,forumdisplay_subforums_modcolumn,forumbit_moderators,forumbit_subforums,forumbit_depth2_forum_lastpost";
$templatelist .= ",forumbit_depth1_forum_lastpost,forumdisplay_thread_multipage_page,forumdisplay_thread_multipage,forumdisplay_thread_multipage_more";
$templatelist .= ",multipage_prevpage,multipage_nextpage,multipage_page_current,multipage_page,multipage_start,multipage_end,multipage";
$templatelist .= ",forumjump_advanced,forumjump_special,forumjump_bit";
$templatelist .= ",forumdisplay_usersbrowsing_guests,forumdisplay_usersbrowsing_user,forumdisplay_usersbrowsing,forumdisplay_inlinemoderation,forumdisplay_thread_modbit,forumdisplay_inlinemoderation_col";
$templatelist .= ",forumdisplay_announcements_announcement,forumdisplay_announcements,forumdisplay_threads_sep,forumbit_depth3_statusicon,forumbit_depth3,forumdisplay_sticky_sep,forumdisplay_thread_attachment_count,forumdisplay_threadlist_inlineedit_js,forumdisplay_rssdiscovery,forumdisplay_announcement_rating,forumdisplay_announcements_announcement_modbit,forumdisplay_rules_link,forumdisplay_thread_gotounread,forumdisplay_nothreads,forumdisplay_inlinemoderation_custom_tool,forumdisplay_inlinemoderation_custom";
require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_forumlist.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("forumdisplay");

$plugins->run_hooks("forumdisplay_start");

$fid = intval($daddyobb->input['fid']);
if($fid < 0)
{
	switch($fid)
	{
		case "-1":
			$location = "index.php";
			break;
		case "-2":
			$location = "search.php";
			break;
		case "-3":
			$location = "usercp.php";
			break;
		case "-4":
			$location = "private.php";
			break;
		case "-5":
			$location = "online.php";
			break;
	}
	if($location)
	{
		header("Location: ".$location);
		exit;
	}
}

// Get forum info
$foruminfo = get_forum($fid);
if(!$foruminfo)
{
	error($lang->error_invalidforum);
}

$archive_url = build_archive_link("forum", $fid);

$currentitem = $fid;
build_forum_breadcrumb($fid);
$parentlist = $foruminfo['parentlist'];

$forumpermissions = forum_permissions();
$fpermissions = $forumpermissions[$fid];

//Check foruminfo for posting rules
if($foruminfo['allowhtml'] == 1)
{$html_onoff = $lang->perm_on;}
else
{$html_onoff = $lang->perm_off;}
$lang->perm_html = $lang->sprintf($lang->perm_html, $html_onoff);

if($foruminfo['allowimgcode'] == 1)
{$img_onoff = $lang->perm_on;}
else
{$img_onoff = $lang->perm_off;}
$lang->perm_img = $lang->sprintf($lang->perm_img, $img_onoff);

if($foruminfo['allowmycode'] == 1)
{$mycode_onoff = $lang->perm_on;}
else
{$mycode_onoff = $lang->perm_off;}
$lang->perm_mycode = $lang->sprintf($lang->perm_mycode, $mycode_onoff);

if($foruminfo['allowsmilies'] == 1)
{$smilies_onoff = $lang->perm_on;}
else
{$smilies_onoff = $lang->perm_off;}
$lang->perm_smilies = $lang->sprintf($lang->perm_smilies, $smilies_onoff);

//Now check forumpermission
if($fpermissions['canpostthreads'] == 1)
{$may_postthreads = $lang->perm_may;}
else
{$may_postthreads = $lang->perm_maynot;}
$lang->post_threads = $lang->sprintf($lang->post_threads, $may_postthreads);

if($fpermissions['canpostreplys'] == 1)
{$may_postreplies = $lang->perm_may;}
else
{$may_postreplies = $lang->perm_maynot;}
$lang->post_replies = $lang->sprintf($lang->post_replies, $may_postreplies);

if($fpermissions['canpostpolls'] == 1)
{$may_postpolls = $lang->perm_may;}
else
{$may_postpolls = $lang->perm_maynot;}

if($fpermissions['canpostattachments'] == 1)
{$may_postattachments = $lang->perm_may;}
else
{$may_postattachments = $lang->perm_maynot;}
$lang->post_attachments = $lang->sprintf($lang->post_attachments, $may_postattachments);

if($fpermissions['caneditposts'] == 1)
{$may_edityourposts = $lang->perm_may;}
else
{$may_edityourposts = $lang->perm_maynot;}
$lang->edit_posts = $lang->sprintf($lang->edit_posts, $may_edityourposts);

if($fpermissions['canview'] != 1)
{
	error_no_permission();
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

// Get the forum moderators if the setting is enabled.
if($daddyobb->settings['modlist'] != 0)
{
	$moderatorcache = $cache->read("moderators");
}

$bgcolor = "trow1";
if($daddyobb->settings['subforumsindex'] != 0)
{
	$showdepth = 3;
}
else
{
	$showdepth = 2;
}
$child_forums = build_forumbits($fid, 2);
$forums = $child_forums['forum_list'];
if($forums)
{
	$lang->sub_forums_in = $lang->sprintf($lang->sub_forums_in, $foruminfo['name']);
	eval("\$subforums = \"".$templates->get("forumdisplay_subforums")."\";");
}

$excols = "forumdisplay";

// Password protected forums
check_forum_password($foruminfo['fid']);

if($foruminfo['linkto'])
{
	header("Location: {$foruminfo['linkto']}");
	exit;
}

// Make forum jump...
if($daddyobb->settings['enableforumjump'] != 0)
{
	$forumjump = build_forum_jump("", $fid, 1);
}

if($foruminfo['type'] == "f" && $foruminfo['open'] != 0)
{
	eval("\$newthread = \"".$templates->get("forumdisplay_newthread")."\";");
}

if($fpermissions['cansearch'] != 0 && $foruminfo['type'] == "f")
{
	eval("\$searchforum = \"".$templates->get("forumdisplay_searchforum")."\";");
}

$done_moderators = array();
$modcomma = '';
$modlist = '';
$modcount = 0;
$parentlistexploded = explode(",", $parentlist);
foreach($parentlistexploded as $mfid)
{
	if($moderatorcache[$mfid])
	{
		reset($moderatorcache[$mfid]);
		foreach($moderatorcache[$mfid] as $moderator)
		{
			if(in_array($moderator['uid'], $done_moderators))
			{
				continue;
			}
			$modcount++;
			$moderator['username'] = format_name($moderator['username'], $moderator['usergroup'], $moderator['displaygroup']);
			$moderator['profilelink'] = build_profile_link($moderator['username'], $moderator['uid']);
			eval("\$mods .= \"".$templates->get("forumdisplay_moderatedby_moderator", 1, 0)."\";");
			$modcomma = ", ";
			
			$done_moderators[] = $moderator['uid'];
		}
	}
}

if($mods)
{
  $lang->moderators_cat = $lang->sprintf($lang->moderators_cat, $modcount);
	eval("\$moderators_cat = \"".$templates->get("forumdisplay_moderators_cat")."\";");
	eval("\$moderators_head = \"".$templates->get("forumdisplay_moderators_head")."\";");
	eval("\$moderatorsrow = \"".$templates->get("forumdisplay_moderatedby")."\";");
}

// Get the users browsing this forum.
if($daddyobb->settings['browsingthisforum'] != 0)
{
	$timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];

	$comma = '';
	$guestcount = 0;
	$membercount = 0;
	$inviscount = 0;
	$onlinemembers = '';
	$query = $db->query("
		SELECT s.ip, s.uid, u.username, s.time, u.invisible, u.usergroup, u.usergroup, u.displaygroup
		FROM ".TABLE_PREFIX."sessions s
		LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)
		WHERE s.time > '$timecut' AND location1='$fid' AND nopermission != 1
		ORDER BY u.username ASC, s.time DESC
	");
	while($user = $db->fetch_array($query))
	{
		if($user['uid'] == 0)
		{
			++$guestcount;
		}
		else
		{
			if($doneusers[$user['uid']] < $user['time'] || !$doneusers[$user['uid']])
			{
				$doneusers[$user['uid']] = $user['time'];
				++$membercount;
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
				if($user['invisible'] == 1)
				{
					$invisiblemark = 1;
					++$inviscount;
				}
				else
				{
					$invisiblemark = 0;
				}
				
				if($user['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $daddyobb->user['uid'])
				{
					$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
					$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
					eval("\$onlinemembers .= \"".$templates->get("forumdisplay_usersbrowsing_user", 1, 0)."\";");
					$comma = ", ";
				}
			}
		}
	}
		
if($guestcount == 1)
	{
		$guestmsg = $lang->online_guest_singular;
	}
	else
	{
		$guestmsg = $lang->online_guest_plural;
	}
	if($membercount == 1)
	{
		$membermsg = $lang->online_member_singular;
	}
	else
	{
		$membermsg = $lang->online_member_plural;
	}
	
	$total = $guestcount + $membercount;
	$lang->online_count = $lang->sprintf($lang->online_count, $total, $membercount, $membermsg, $guestcount, $guestmsg);
	
	$invisonline = '';
	if($inviscount && $daddyobb->usergroup['canviewwolinvis'] != 1 && ($inviscount != 1 && $daddyobb->user['invisible'] != 1))
	{
		$invisonline = $lang->sprintf($lang->users_browsing_forum_invis, $inviscount);
	}

	eval("\$usersbrowsing = \"".$templates->get("forumdisplay_usersbrowsing")."\";");
}

// Do we have any forum rules to show for this forum?
$forumrules = '';
if($foruminfo['rulestype'] != 0 && $foruminfo['rules'])
{
	if(!$foruminfo['rulestitle'])
	{
		$foruminfo['rulestitle'] = $lang->sprintf($lang->forum_rules, $foruminfo['name']);
	}
	
	$rules_parser = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1
	);

	$foruminfo['rules'] = $parser->parse_message($foruminfo['rules'], $rules_parser);
	if($foruminfo['rulestype'] == 1)
	{
		eval("\$rules = \"".$templates->get("forumdisplay_rules")."\";");
	}
	else if($foruminfo['rulestype'] == 2)
	{
		eval("\$rules = \"".$templates->get("forumdisplay_rules_link")."\";");
	}
}

$bgcolor = "trow1";

// Set here to fetch only approved topics (and then below for a moderator we change this).
$visibleonly = "AND visible='1'";
$tvisibleonly = "AND t.visible='1'";

// Check if the active user is a moderator and get the inline moderation tools.
if(is_moderator($fid))
{
	eval("\$inlinemodcol = \"".$templates->get("forumdisplay_inlinemoderation_col")."\";");
	$ismod = true;
	$inlinecount = "0";
	$inlinecookie = "inlinemod_forum".$fid;
	$visibleonly = " AND (visible='1' OR visible='0')";
	$tvisibleonly = " AND (t.visible='1' OR t.visible='0')";
}
else
{
	$inlinemod = '';
	$ismod = false;
}

if(is_moderator($fid, "caneditposts") || $fpermissions['caneditposts'] == 1)
{
	$can_edit_titles = 1;
}
else
{
	$can_edit_titles = 0;
}

unset($rating);

// Pick out some sorting options.
// First, the date cut for the threads.
$datecut = 0;
if(!$daddyobb->input['datecut'])
{
	// If the user manually set a date cut, use it.
	if($daddyobb->user['daysprune'])
	{
		$datecut = $daddyobb->user['daysprune'];
	}
	else
	{
		// If the forum has a non-default date cut, use it.
		if(!empty($foruminfo['defaultdatecut']))
		{
			$datecut = $foruminfo['defaultdatecut'];
		}
	}
}
// If there was a manual date cut override, use it.
else
{
	$datecut = intval($daddyobb->input['datecut']);
}

$datecut = intval($datecut);
$datecutsel[$datecut] = "selected=\"selected\"";
if($datecut > 0 && $datecut != 9999)
{
	$checkdate = TIME_NOW - ($datecut * 86400);
	$datecutsql = "AND (lastpost >= '$checkdate' OR sticky = '1')";
	$datecutsql2 = "AND (t.lastpost >= '$checkdate' OR t.sticky = '1')";
}
else
{
	$datecutsql = '';
	$datecutsql2 = '';
}

// Pick the sort order.
if(!isset($daddyobb->input['order']) && !empty($foruminfo['defaultsortorder']))
{
	$daddyobb->input['order'] = $foruminfo['defaultsortorder'];
}

$daddyobb->input['order'] = htmlspecialchars($daddyobb->input['order']);

switch(my_strtolower($daddyobb->input['order']))
{
	case "asc":
		$sortordernow = "asc";
        $ordersel['asc'] = "selected=\"selected\"";
		$oppsort = $lang->desc;
		$oppsortnext = "desc";
		break;
	default:
        $sortordernow = "desc";
		$ordersel['desc'] = "selected=\"selected\"";
        $oppsort = $lang->asc;
		$oppsortnext = "asc";
		break;
}

// Sort by which field?
if(!isset($daddyobb->input['sortby']) && !empty($foruminfo['defaultsortby']))
{
	$daddyobb->input['sortby'] = $foruminfo['defaultsortby'];
}

$t = "t.";

$sortby = htmlspecialchars($daddyobb->input['sortby']);
switch($daddyobb->input['sortby'])
{
	case "subject":
		$sortfield = "subject";
		break;
	case "replies":
		$sortfield = "replies";
		break;
	case "views":
		$sortfield = "views";
		break;
	case "starter":
		$sortfield = "username";
		break;
	case "rating":
		$t = "";
		$sortfield = "averagerating";
		$sortfield2 = ", t.totalratings DESC";
		break;
	case "started":
		$sortfield = "dateline";
		break;
	default:
		$sortby = "lastpost";
		$sortfield = "lastpost";
		$daddyobb->input['sortby'] = "lastpost";
		break;
}

$sortsel[$daddyobb->input['sortby']] = "selected=\"selected\"";

// Are we viewing a specific page?
if(isset($daddyobb->input['page']) && is_numeric($daddyobb->input['page']))
{
	$sorturl = "forumdisplay.php?fid=$fid&amp;datecut=$datecut&amp;page=".$daddyobb->input['page'];
}
else
{
	$sorturl = "forumdisplay.php?fid=$fid&amp;datecut=$datecut";
}
eval("\$orderarrow['$sortby'] = \"".$templates->get("forumdisplay_orderarrow")."\";");

// How many posts are there?
if($datecut > 0)
{
	$query = $db->simple_select("threads", "COUNT(tid) AS threads", "fid = '$fid' $visibleonly $datecutsql");
	$threadcount = $db->fetch_field($query, "threads");
}
else
{
	$query = $db->simple_select("forums", "threads, unapprovedthreads", "fid = '{$fid}'", array('limit' => 1));
	$forum_threads = $db->fetch_array($query);
	$threadcount = $forum_threads['threads'];
	if($ismod == true)
	{
		$threadcount += $forum_threads['unapprovedthreads'];
	}
	
	// If we have 0 threads double check there aren't any "moved" threads
	if($threadcount == 0)
	{
		$query = $db->simple_select("threads", "COUNT(tid) AS threads", "fid = '$fid' $visibleonly", array('limit' => 1));
		$threadcount = $db->fetch_field($query, "threads");
	}
}

// How many pages are there?
if(!$daddyobb->settings['threadsperpage'])
{
	$daddyobb->settings['threadsperpage'] = 20;
}

$perpage = $daddyobb->settings['threadsperpage'];

if(intval($daddyobb->input['page']) > 0)
{
	$page = intval($daddyobb->input['page']);
	$start = ($page-1) * $perpage;
	$pages = $threadcount / $perpage;
	$pages = ceil($pages);
	if($page > $pages || $page <= 0)
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

$end = $start + $perpage;
$lower = $start + 1;
$upper = $end;

if($upper > $threadcount)
{
	$upper = $threadcount;
}

// Assemble page URL
if($daddyobb->input['sortby'] || $daddyobb->input['order'] || $daddyobb->input['datecut']) // Ugly URL
{	
	$page_url = str_replace("{fid}", $fid, FORUM_URL_PAGED);
	
	if($daddyobb->settings['seourls'] == "yes" || ($daddyobb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1))
	{
		$q = "?";
		$and = '';
	}
	else
	{
		$q = '';
		$and = "&";
	}
	
	if($sortby != "lastpost")
	{
		$page_url .= "{$q}{$and}sortby={$sortby}";
		$q = '';
		$and = "&";
	}
	
	if($sortordernow != "desc")
	{
		$page_url .= "{$q}{$and}order={$sortordernow}";
		$q = '';
		$and = "&";
	}
	
	if($datecut > 0 && $datecut != 9999)
	{
		$page_url .= "{$q}{$and}datecut={$datecut}";
	}
}
else
{
	$page_url = str_replace("{fid}", $fid, FORUM_URL_PAGED);
}
$multipage = multipage($threadcount, $perpage, $page, $page_url);

if($foruminfo['allowtratings'] != 0)
{
	$lang->load("ratethread");
	switch($db->type)
	{
		case "pgsql":
			$ratingadd = '';
			$query = $db->query("
				SELECT t.numratings, t.totalratings, t.tid
				FROM ".TABLE_PREFIX."threads t
				WHERE t.fid='$fid' $tvisibleonly $datecutsql2
				ORDER BY t.sticky DESC, t.$sortfield $sortordernow $sortfield2
				LIMIT $start, $perpage
			");
			while($thread = $db->fetch_array($query))
			{
				if($thread['totalratings'] == 0)
				{
					$rating = 0;
				}
				else				
				{
					$rating = $thread['totalratings'] / $thread['numratings'];
				}

				$avaragerating[$thread['tid']] = $rating;
			}
			$t = "t.";
			$sortfield = "lastpost";
			break;
		default:
			$ratingadd = "(t.totalratings/t.numratings) AS averagerating, ";
	}
	$lpbackground = "trow2";
	eval("\$ratingcol = \"".$templates->get("forumdisplay_threadlist_rating")."\";");
	eval("\$ratingsort = \"".$templates->get("forumdisplay_threadlist_sortrating")."\";");
	$colspan = "7";
	$select_voting = "\nLEFT JOIN ".TABLE_PREFIX."threadratings r ON(r.tid=t.tid AND r.uid='{$daddyobb->user['uid']}')";
	$select_rating_user = "r.uid AS rated, ";
}
else
{
	if($sortfield == "averagerating")
	{
		$t = "t.";
		$sortfield = "lastpost";
	}
	$ratingadd = '';
	$lpbackground = "trow1";
	$colspan = "6";
}

if($ismod)
{
	++$colspan;
}

// Get Announcements
$limit = '';
$announcements = '';
if($daddyobb->settings['announcementlimit'])
{
	$limit = "LIMIT 0, ".$daddyobb->settings['announcementlimit'];
}

$sql = build_parent_list($fid, "fid", "OR", $parentlist);
$time = TIME_NOW;
$query = $db->query("
	SELECT a.*, u.*
	FROM ".TABLE_PREFIX."announcements a
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=a.uid)
	WHERE a.startdate<='$time' AND (a.enddate>='$time' OR a.enddate='0') AND ($sql OR fid='-1')
	ORDER BY a.startdate DESC $limit
");
while($announcement = $db->fetch_array($query))
{
	if($announcement['startdate'] > $daddyobb->user['lastvisit'])
	{
		$new_class = ' class="subject_new"';
		$folder = "announcement_new";
	}
	else
	{
		$new_class = '';
		$folder = "announcement_old";
	}

	$announcement['announcementlink'] = get_announcement_link($announcement['aid']);
	$announcement['subject'] = $parser->parse_badwords($announcement['subject']);
	$announcement['subject'] = htmlspecialchars_uni($announcement['subject']);
	$postdate = my_date($daddyobb->settings['dateformat'], $announcement['startdate']);
	$posttime = my_date($daddyobb->settings['timeformat'], $announcement['startdate']);
	$announcement['profilelink'] = build_profile_link($announcement['username'], $announcement['uid']);
	
	//Do we have a usertitle?
	if($announcement['usertitle'])
	{
		$announcement['usertitle'] = "(".$announcement['usertitle'].")";
	}
	else
	{
		$announcement['usertitle'] = "";
	}
	
	if($foruminfo['allowtratings'] != 0)
	{
		eval("\$rating = \"".$templates->get("forumdisplay_announcement_rating")."\";");
		$lpbackground = "trow2";
	}
	else
	{
		$rating = '';
		$lpbackground = "trow1";
	}
	
	if($ismod)
	{
		eval("\$modann = \"".$templates->get("forumdisplay_announcements_announcement_modbit")."\";");
	}
	else
	{
		$modann = '';
	}
	
	$plugins->run_hooks("forumdisplay_announcement");
	eval("\$announcements  .= \"".$templates->get("forumdisplay_announcements_announcement")."\";");
	$bgcolor = alt_trow();
}

if($announcements)
{
	eval("\$announcementlist  = \"".$templates->get("forumdisplay_announcements")."\";");
	$shownormalsep = false;
}

$icon_cache = $cache->read("posticons");

// Start Getting Threads
$query = $db->query("
	SELECT t.*, {$ratingadd}{$select_rating_user}t.username AS threadusername, u.username
	FROM ".TABLE_PREFIX."threads t
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = t.uid){$select_voting}
	WHERE t.fid='$fid' $tvisibleonly $datecutsql2
	ORDER BY t.sticky DESC, {$t}{$sortfield} $sortordernow $sortfield2
	LIMIT $start, $perpage
");
while($thread = $db->fetch_array($query))
{
	if($db->type == "pgsql")
	{
		$thread['averagerating'] = $averagerating[$thread['tid']];
	}

	$threadcache[$thread['tid']] = $thread;

	// If this is a moved thread - set the tid for participation marking and thread read marking to that of the moved thread
	if(substr($thread['closed'], 0, 5) == "moved")
	{
		$tid = substr($thread['closed'], 6);
		if(!$tids[$tid])
		{
			$moved_threads[$tid] = $thread['tid'];
			$tids[$thread['tid']] = $tid;
		}
	}
	// Otherwise - set it to the plain thread ID
	else
	{
		$tids[$thread['tid']] = $thread['tid'];
		if($moved_threads[$tid])
		{
			unset($moved_threads[$tid]);
		}
	}
}

if($tids)
{
	$tids = implode(",", $tids);
}

// Check participation by the current user in any of these threads - for 'dot' folder icons
if($daddyobb->settings['dotfolders'] != 0 && $daddyobb->user['uid'] && $threadcache)
{
	$query = $db->simple_select("posts", "tid,uid", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})");
	while($post = $db->fetch_array($query))
	{
		if($moved_threads[$post['tid']])
		{
			$post['tid'] = $moved_threads[$post['tid']];
		}
		if($threadcache[$post['tid']])
		{
			$threadcache[$post['tid']]['doticon'] = 1;
		}
	}
}

// Read threads
if($daddyobb->user['uid'] && $daddyobb->settings['threadreadcut'] > 0 && $threadcache)
{
	$query = $db->simple_select("threadsread", "*", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})"); 
	while($readthread = $db->fetch_array($query))
	{
		if($moved_threads[$readthread['tid']]) 
		{ 
	 		$readthread['tid'] = $moved_threads[$readthread['tid']]; 
	 	}
		if($threadcache[$readthread['tid']])
		{
	 		$threadcache[$readthread['tid']]['lastread'] = $readthread['dateline']; 
		}
	}
}

if($daddyobb->settings['threadreadcut'] > 0 && $daddyobb->user['uid'])
{
	$query = $db->simple_select("forumsread", "dateline", "fid='{$fid}' AND uid='{$daddyobb->user['uid']}'");
	$forum_read = $db->fetch_field($query, "dateline");

	$read_cutoff = TIME_NOW-$daddyobb->settings['threadreadcut']*60*60*24;
	if($forum_read == 0 || $forum_read < $read_cutoff)
	{
		$forum_read = $read_cutoff;
	}
}
else
{
	$forum_read = my_get_array_cookie("forumread", $fid);
}

$unreadpost = 0;
$threads = '';
$load_inline_edit_js = 0;
if(is_array($threadcache))
{
	foreach($threadcache as $thread)
	{
		$plugins->run_hooks("forumdisplay_thread");

		$moved = explode("|", $thread['closed']);

		if($thread['visible'] == 0)
		{
			$bgcolor = "trow_shaded";
		}
		else
		{
			$bgcolor = alt_trow();
		}

		$folder = '';
		$prefix = '';

		$thread['author'] = $thread['uid'];
		if(!$thread['username'])
		{
			$thread['username'] = $thread['threadusername'];
			$thread['profilelink'] = $thread['threadusername'];
		}
		else
		{
			$thread['profilelink'] = build_profile_link($thread['username'], $thread['uid']);
		}

		$thread['subject'] = $parser->parse_badwords($thread['subject']);
		$thread['subject'] = htmlspecialchars_uni($thread['subject']);

		if($thread['icon'] > 0 && $icon_cache[$thread['icon']])
		{
			$icon = $icon_cache[$thread['icon']];
			$icon = "<img src=\"{$icon['path']}\" alt=\"{$icon['name']}\" />";
		}
		else
		{
			$icon = "&nbsp;";
		}

		$prefix = '';
		if($thread['poll'])
		{
			$prefix = $lang->poll_prefix;
		}
		
		if($thread['visible']==0)
		{
			$prefix = $lang->moderated_prefix;
			$moderated = 1;
		}

		if($thread['sticky'] == "1")
		{
			$sticky_img = "<img src=\"{$theme['imgdir']}/sticky.gif\" class=\"inlineimg\" title=\"{$lang->sticky_prefix} {$thread['subject']}\" alt=\"{$lang->sticky_prefix}: {$thread['subject']}\">";
			$prefix = $lang->sticky_prefix;
		}

		if($thread['sticky'] == "1" && !$donestickysep)
		{
			eval("\$threads .= \"".$templates->get("forumdisplay_sticky_sep")."\";");
			$shownormalsep = true;
			$donestickysep = true;
		}
		else if($thread['sticky'] == 0 && $shownormalsep)
		{
			eval("\$threads .= \"".$templates->get("forumdisplay_threads_sep")."\";");
			$sticky_img = "";
			$shownormalsep = false;
		}

		$rating = '';
		if($foruminfo['allowtratings'] != 0)
		{
			if($moved[0] == "moved")
			{
				$rating_img = "";
			}
			else
			{
				$thread['averagerating'] = floatval(round($thread['averagerating'], 2));
				if($thread['averagerating'] == 0)
				{
					$rating_img= "";
				}
				else
				{
					$rating_img = "";
					for($i = 0; $i < $thread['averagerating']; $i++)
					{
						$rating_img .= "<img src=\"{$theme['imgdir']}/star.gif\" class=\"inlineimg\" title=\"{$lang->rating}: {$thread['averagerating']}\" alt=\"{$lang->rating}: {$thread['averagerating']}\">";
					}
				}
				$thread['width'] = intval($thread['averagerating'])*20;
				$thread['numratings'] = intval($thread['numratings']);

				$not_rated = '';
				if(!$thread['rated'])
				{
					$not_rated = ' star_rating_notrated';
				}

				$ratingvotesav = $lang->sprintf($lang->rating_votes_average, $thread['numratings'], $thread['averagerating']);
				eval("\$rating = \"".$templates->get("forumdisplay_thread_rating")."\";");
			}
		}

		$thread['pages'] = 0;
		$thread['multipage'] = '';
		$threadpages = '';
		$morelink = '';
		$thread['posts'] = $thread['replies'] + 1;

		if(!$daddyobb->settings['postsperpage'])
		{
			$daddyobb->settings['postperpage'] = 20;
		}

		if($thread['unapprovedposts'] > 0 && $ismod)
		{
			$thread['posts'] += $thread['unapprovedposts'];
		}

		if($thread['posts'] > $daddyobb->settings['postsperpage'])
		{
			$thread['pages'] = $thread['posts'] / $daddyobb->settings['postsperpage'];
			$thread['pages'] = ceil($thread['pages']);

			if($thread['pages'] > 4)
			{
				$pagesstop = 4;
				$page_link = get_thread_link($thread['tid'], $thread['pages']);				
				eval("\$morelink = \"".$templates->get("forumdisplay_thread_multipage_more")."\";");
			}
			else
			{
				$pagesstop = $thread['pages'];
			}

			for($i = 1; $i <= $pagesstop; ++$i)
			{
				$page_link = get_thread_link($thread['tid'], $i);
				eval("\$threadpages .= \"".$templates->get("forumdisplay_thread_multipage_page")."\";");
			}

			eval("\$thread['multipage'] = \"".$templates->get("forumdisplay_thread_multipage")."\";");
		}
		else
		{
			$threadpages = '';
			$morelink = '';
			$thread['multipage'] = '';
		}

		if($ismod)
		{
			if(my_strpos($daddyobb->cookies[$inlinecookie], "|{$thread['tid']}|"))
			{
				$inlinecheck = "checked=\"checked\"";
				++$inlinecount;
			}
			else
			{
				$inlinecheck = '';
			}

			$multitid = $thread['tid'];
			eval("\$modbit = \"".$templates->get("forumdisplay_thread_modbit")."\";");
		}
		else
		{
			$modbit = '';
		}

		if($moved[0] == "moved")
		{
			$prefix = $lang->moved_prefix;
			$thread['tid'] = $moved[1];
			$thread['replies'] = "-";
			$thread['views'] = "-";
		}

		$thread['threadlink'] = get_thread_link($thread['tid']);
		$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");

		// Determine the folder
		$folder = '';
		$folder_label = '';

		if($thread['doticon'])
		{
			$folder = "dot_";
			$folder_label .= $lang->icon_dot;
		}

		$gotounread = '';
		$isnew = 0;
		$donenew = 0;

		if($daddyobb->settings['threadreadcut'] > 0 && $daddyobb->user['uid'] && $thread['lastpost'] > $forum_read)
		{
			if($thread['lastread'])
			{
				$last_read = $thread['lastread'];
			}
			else
			{
				$last_read = $read_cutoff;
			}
		}
		else
		{
			$last_read = my_get_array_cookie("threadread", $thread['tid']);
		}

		if($forum_read > $last_read)
		{
			$last_read = $forum_read;
		}

		if($thread['lastpost'] > $last_read && $moved[0] != "moved")
		{
			$folder .= "new";
			$folder_label .= $lang->icon_new;
			$new_class = "subject_new";
			$thread['newpostlink'] = get_thread_link($thread['tid'], 0, "newpost");
			eval("\$gotounread = \"".$templates->get("forumdisplay_thread_gotounread")."\";");
			$unreadpost = 1;
		}
		else
		{
			$folder_label .= $lang->icon_no_new;
			$new_class = "";
		}

		if($thread['replies'] >= $daddyobb->settings['hottopic'] || $thread['views'] >= $daddyobb->settings['hottopicviews'])
		{
			$folder .= "hot";
			$folder_label .= $lang->icon_hot;
		}

		if($thread['closed'] == 1)
		{
			$folder .= "lock";
			$folder_label .= $lang->icon_lock;
		}

		if($moved[0] == "moved")
		{
			$folder = "move";
			$gotounread = '';
		}

		$folder .= "folder";

		$inline_edit_tid = $thread['tid'];

		// If this user is the author of the thread and it is not closed or they are a moderator, they can edit
		if(($thread['uid'] == $daddyobb->user['uid'] && $thread['closed'] != 1 && $daddyobb->user['uid'] != 0 && $can_edit_titles == 1) || $ismod == true)
		{
			$inline_edit_class = "subject_editable";
		}
		else
		{
			$inline_edit_class = "";
		}
		$load_inline_edit_js = 1;

		$lastpostdate = my_date($daddyobb->settings['dateformat'], $thread['lastpost']);
		$lastposttime = my_date($daddyobb->settings['timeformat'], $thread['lastpost']);
		$lastposter = $thread['lastposter'];
		$lastposteruid = $thread['lastposteruid'];

		// Don't link to guest's profiles (they have no profile).
		if($lastposteruid == 0)
		{
			$lastposterlink = $lastposter;
		}
		else
		{
			$lastposterlink = build_profile_link($lastposter, $lastposteruid);
		}

		$thread['replies'] = my_number_format($thread['replies']);
		$thread['views'] = my_number_format($thread['views']);

		// Threads and posts requiring moderation
		if($thread['unapprovedposts'] > 0 && $ismod)
		{
			if($thread['unapprovedposts'] > 1)
			{
				$unapproved_posts_count = $lang->sprintf($lang->thread_unapproved_posts_count, $thread['unapprovedposts']);
			}
			else
			{
				$unapproved_posts_count = $lang->sprintf($lang->thread_unapproved_post_count, 1);
			}

			$unapproved_posts = " <span title=\"{$unapproved_posts_count}\">(".my_number_format($thread['unapprovedposts']).")</span>";
		}
		else
		{
			$unapproved_posts = '';
		}

		// If this thread has 1 or more attachments show the papperclip
		if($thread['attachmentcount'] > 0)
		{
			if($thread['attachmentcount'] > 1)
			{
				$attachment_count = $lang->sprintf($lang->attachment_count_multiple, $thread['attachmentcount']);
			}
			else
			{
				$attachment_count = $lang->attachment_count;
			}

			eval("\$attachment_count = \"".$templates->get("forumdisplay_thread_attachment_count")."\";");
		}
		else
		{
			$attachment_count = '';
		}

		eval("\$threads .= \"".$templates->get("forumdisplay_thread")."\";");
	}

	$customthreadtools = '';
	if($ismod)
	{
		switch($db->type)
		{
			case "pgsql":
			case "sqlite3":
			case "sqlite2":
				$query = $db->simple_select("modtools", 'tid, name', "(','||forums||',' LIKE '%,$fid,%' OR ','||forums||',' LIKE '%,-1,%' OR forums='') AND type = 't'");
				break;
			default:
				$query = $db->simple_select("modtools", 'tid, name', "(CONCAT(',',forums,',') LIKE '%,$fid,%' OR CONCAT(',',forums,',') LIKE '%,-1,%' OR forums='') AND type = 't'");
		}
		while($tool = $db->fetch_array($query))
		{
			eval("\$customthreadtools .= \"".$templates->get("forumdisplay_inlinemoderation_custom_tool")."\";");
		}
		
		if(!empty($customthreadtools))
		{
			eval("\$customthreadtools = \"".$templates->get("forumdisplay_inlinemoderation_custom")."\";");
		}
		eval("\$inlinemod = \"".$templates->get("forumdisplay_inlinemoderation")."\";");
	}
}

// If there are no unread threads in this forum and no unread child forums - mark it as read
require_once DADDYOBB_ROOT."inc/functions_indicators.php";
if(fetch_unread_count($fid) == 0 && $unread_forums == 0)
{
	mark_forum_read($fid);
}


// Subscription status
$query = $db->simple_select("forumsubscriptions", "fid", "fid='".$fid."' AND uid='{$daddyobb->user['uid']}'", array('limit' => 1));
if($db->fetch_field($query, 'fid'))
{
	$add_remove_subscription = 'remove';
	$add_remove_subscription_text = $lang->unsubscribe_forum;
}
else
{
	$add_remove_subscription = 'add';
	$add_remove_subscription_text = $lang->subscribe_forum;
}


// Is this a real forum with threads?
if($foruminfo['type'] != "c")
{
	if(!$threadcount)
	{
		eval("\$threads = \"".$templates->get("forumdisplay_nothreads")."\";");
	}

	if($foruminfo['password'] != '')
	{
		eval("\$clearstoredpass = \"".$templates->get("forumdisplay_threadlist_clearpass")."\";");
	}

	if($load_inline_edit_js == 1)
	{
		eval("\$inline_edit_js = \"".$templates->get("forumdisplay_threadlist_inlineedit_js")."\";");
	}

	$lang->rss_discovery_forum = $lang->sprintf($lang->rss_discovery_forum, htmlspecialchars_uni(strip_tags($foruminfo['name'])));
	eval("\$rssdiscovery = \"".$templates->get("forumdisplay_rssdiscovery")."\";");
	eval("\$threadslist = \"".$templates->get("forumdisplay_threadlist")."\";");
}
else
{
	$rssdiscovery = '';
	$threadslist = '';

	if(empty($forums))
	{
		error($lang->error_containsnoforums);
	}
}

$plugins->run_hooks("forumdisplay_end");

$foruminfo['name'] = strip_tags($foruminfo['name']);

eval("\$forums = \"".$templates->get("forumdisplay")."\";");
output_page($forums);
?>