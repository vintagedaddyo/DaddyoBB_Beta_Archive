<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 01:38 20.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'modcp.php');

$templatelist = "modcp_reports,modcp_reports_report,modcp_reports_multipage,modcp_reports_allreport";
$templatelist .= ",modcp_banning,modcp_banning_ban";
$templatelist .= ",modcp_nav,modcp";
$templatelist .= ",modcp_announcements_global,modcp_announcements_forum,modcp_announcements";
$templatelist .= ",codebuttons,smilieinsert,modcp_announcements_new,modcp_modqueue_empty,forumjump_bit,forumjump_special";
$templatelist .= ",modcp_modlogs,modcp_finduser_user,modcp_finduser,usercp_profile_customfield,usercp_profile_profilefields";
$templatelist .= ",modcp_editprofile,modcp_ipsearch,modcp_banuser_addusername,modcp_banuser,modcp_warninglogs_nologs";
$templatelist .= ",modcp_warninglogs,modcp_modlogs_result";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/functions_upload.php";
require_once DADDYOBB_ROOT."inc/functions_modcp.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";

$parser = new postParser;

// Set up the array of ban times.
$bantimes = fetch_ban_times();

// Load global language phrases
$lang->load("modcp");

if($daddyobb->user['uid'] == 0 || $daddyobb->usergroup['canmodcp'] != 1)
{
	error_no_permission();
}

$errors = '';
// SQL for fetching items only related to forums this user moderates
$moderated_forums = array();
if($daddyobb->usergroup['issupermod'] != 1)
{
	$query = $db->simple_select("moderators", "*", "uid='{$daddyobb->user['uid']}'");
	while($forum = $db->fetch_array($query))
	{
		$flist .= ",'{$forum['fid']}'";
		
		$children = get_child_list($forum['fid']);
		if(!empty($children))
		{
			$flist .= ",'".implode("','", $children)."'";
		}
		$moderated_forums[] = $forum['fid'];
	}
	if($flist)
	{
		$tflist = " AND t.fid IN (0{$flist})";
		$flist = " AND fid IN (0{$flist})";
	}
}
else
{
	$flist = $tflist = '';
}

// Fetch the Mod CP menu
eval("\$modcp_nav = \"".$templates->get("modcp_nav")."\";");

$plugins->run_hooks("modcp_start");

// Make navigation
add_breadcrumb($lang->nav_modcp, "modcp.php");

if($daddyobb->input['action'] == "do_reports")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	if(!is_array($daddyobb->input['reports']))
	{
		error($lang->error_noselected_reports);
	}

	$daddyobb->input['reports'] = array_map("intval", $daddyobb->input['reports']);
	$rids = implode($daddyobb->input['reports'], "','");
	$rids = "'0','{$rids}'";

	$plugins->run_hooks("modcp_do_reports");

	$db->update_query("reportedposts", array('reportstatus' => 1), "rid IN ({$rids}){$flist}");
	$cache->update_reportedposts();
	redirect("modcp.php?action=reports", $lang->redirect_reportsmarked);
}

if($daddyobb->input['action'] == "reports")
{
	add_breadcrumb($lang->mcp_nav_reported_posts, "modcp.php?action=reports");

	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}

	// Figure out if we need to display multiple pages.
	$perpage = $daddyobb->settings['threadsperpage'];
	if($daddyobb->input['page'] != "last")
	{
		$page = intval($daddyobb->input['page']);
	}

	$query = $db->simple_select("reportedposts", "COUNT(rid) AS count", "reportstatus ='0'");
	$report_count = $db->fetch_field($query, "count");

	$daddyobb->input['rid'] = intval($daddyobb->input['rid']);

	if($daddyobb->input['rid'])
	{
		$query = $db->simple_select("reportedposts", "COUNT(rid) AS count", "rid <= '".$daddyobb->input['rid']."'");
		$result = $db->fetch_field($query, "count");
		if(($result % $perpage) == 0)
		{
			$page = $result / $perpage;
		}
		else
		{
			$page = intval($result / $perpage) + 1;
		}
	}
	$postcount = intval($report_count);
	$pages = $postcount / $perpage;
	$pages = ceil($pages);



	if($daddyobb->input['page'] == "last")
	{
		$page = $pages;
	}

	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page && $page > 0)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$upper = $start+$perpage;

	$multipage = multipage($postcount, $perpage, $page, "modcp.php?action=reports");
	if($postcount > $perpage)
	{
		eval("\$reportspages = \"".$templates->get("modcp_reports_multipage")."\";");
	}

	$query = $db->simple_select("forums", "fid, name");
	while($forum = $db->fetch_array($query))
	{
		$forums[$forum['fid']] = $forum['name'];
	}

	$reports = '';
	$query = $db->query("
		SELECT r.*, u.username, up.username AS postusername, up.uid AS postuid, t.subject AS threadsubject
		FROM ".TABLE_PREFIX."reportedposts r
		LEFT JOIN ".TABLE_PREFIX."posts p ON (r.pid=p.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (p.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (r.uid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."users up ON (p.uid=up.uid)
		WHERE r.reportstatus='0'
		ORDER BY r.dateline DESC
		LIMIT {$start}, {$perpage}
	");
	while($report = $db->fetch_array($query))
	{
		$trow = alt_trow();
		if(is_moderator($report['fid']))
		{
			$trow = 'trow_shaded';
		}
		$report['postlink'] = get_post_link($report['pid'], $report['tid']);
		$report['threadlink'] = get_thread_link($report['tid']);
		$report['posterlink'] = get_profile_link($report['postuid']);
		$report['reporterlink'] = get_profile_link($report['uid']);
		$reportdate = my_date($daddyobb->settings['dateformat'], $report['dateline']);
		$reporttime = my_date($daddyobb->settings['timeformat'], $report['dateline']);
		$report['threadsubject'] = htmlspecialchars_uni($parser->parse_badwords($report['threadsubject']));
		eval("\$reports .= \"".$templates->get("modcp_reports_report")."\";");
	}
	if(!$reports)
	{
		eval("\$reports = \"".$templates->get("modcp_reports_noreports")."\";");
	}

	$plugins->run_hooks("modcp_reports");

	eval("\$reportedposts = \"".$templates->get("modcp_reports")."\";");
	output_page($reportedposts);
}

if($daddyobb->input['action'] == "allreports")
{
	add_breadcrumb($lang->mcp_nav_all_reported_posts, "modcp.php?action=allreports");

	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}

	// Figure out if we need to display multiple pages.
	$perpage = $daddyobb->settings['threadsperpage'];
	if($daddyobb->input['page'] != "last")
	{
		$page = intval($daddyobb->input['page']);
	}

	$query = $db->simple_select("reportedposts", "COUNT(rid) AS count");
	$warnings = $db->fetch_field($query, "count");

	if($daddyobb->input['rid'])
	{
		$daddyobb->input['rid'] = intval($daddyobb->input['rid']);
		$query = $db->simple_select("reportedposts", "COUNT(rid) AS count", "rid <= '".$daddyobb->input['rid']."'");
		$result = $db->fetch_field($query, "count");
		if(($result % $perpage) == 0)
		{
			$page = $result / $perpage;
		}
		else
		{
			$page = intval($result / $perpage) + 1;
		}
	}
	$postcount = intval($warnings);
	$pages = $postcount / $perpage;
	$pages = ceil($pages);

	if($daddyobb->input['page'] == "last")
	{
		$page = $pages;
	}

	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$upper = $start+$perpage;

	$multipage = multipage($postcount, $perpage, $page, "modcp.php?action=allreports");
	if($postcount > $perpage)
	{
		eval("\$allreportspages = \"".$templates->get("modcp_reports_multipage")."\";");
	}

	$query = $db->simple_select("forums", "fid, name");
  $allreportcounter = 0;
	while($forum = $db->fetch_array($query))
	{
		$forums[$forum['fid']] = $forum['name'];
	}

	$reports = '';
	$query = $db->query("
		SELECT r.*, u.username, up.username AS postusername, up.uid AS postuid, t.subject AS threadsubject
		FROM ".TABLE_PREFIX."reportedposts r
		LEFT JOIN ".TABLE_PREFIX."posts p ON (r.pid=p.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (p.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (r.uid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."users up ON (p.uid=up.uid)
		ORDER BY r.dateline DESC
		LIMIT $start, $perpage
	");
	while($report = $db->fetch_array($query))
	{
		$report['postlink'] = get_post_link($report['pid'], $report['tid']);
		$report['threadlink'] = get_thread_link($report['tid']);
		$report['posterlink'] = get_profile_link($report['postuid']);
		$report['reporterlink'] = get_profile_link($report['uid']);

		$reportdate = my_date($daddyobb->settings['dateformat'], $report['dateline']);
		$reporttime = my_date($daddyobb->settings['timeformat'], $report['dateline']);

		if($report['reportstatus'] == 0)
		{
			$trow = "trow_shaded";
		}
		else
		{
			$trow = alt_trow();
		}

		$report['postusername'] = build_profile_link($report['postusername'], $report['postuid']);

		if($report['threadsubject'])
		{
			$report['threadsubject'] = htmlspecialchars_uni($parser->parse_badwords($report['threadsubject']));
			$report['threadsubject'] = "<a href=\"".get_thread_link($report['tid'])."\" target=\"_blank\">{$report['threadsubject']}</a>";
		}
		else
		{
			$report['threadsubject'] = $lang->na;
		}
		
		$allreportcounter++;

		eval("\$allreports .= \"".$templates->get("modcp_reports_allreport")."\";");
	}

	$plugins->run_hooks("modcp_reports");

	eval("\$allreportedposts = \"".$templates->get("modcp_reports_allreports")."\";");
	output_page($allreportedposts);
}

if($daddyobb->input['action'] == "modlogs")
{
  if($daddyobb->usergroup['modcanviewmodlogs'] != 1)
  {
    error_no_permission();
  }
	add_breadcrumb($lang->mcp_nav_modlogs, "modcp.php?action=modlogs");

	$perpage = intval($daddyobb->input['perpage']);
	if(!$perpage || $perpage <= 0)
	{
		$perpage = $daddyobb->settings['threadsperpage'];
	}

	$where = '';

	// Searching for entries by a particular user
	if($daddyobb->input['uid'])
	{
		$where .= " AND l.uid='".intval($daddyobb->input['uid'])."'";
	}

	// Searching for entries in a specific forum
	if($daddyobb->input['fid'])
	{
		$where .= " AND t.fid='".intval($daddyobb->input['fid'])."'";
	}

	// Order?
	switch($daddyobb->input['sortby'])
	{
		case "username":
			$sortby = "u.username";
			break;
		case "forum":
			$sortby = "f.name";
			break;
		case "thread":
			$sortby = "t.subject";
			break;
		default:
			$sortby = "l.dateline";
	}
	$order = $daddyobb->input['order'];
	if($order != "asc")
	{
		$order = "desc";
	}

	$query = $db->query("
		SELECT COUNT(l.dateline) AS count
		FROM ".TABLE_PREFIX."moderatorlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=l.tid)
		WHERE 1=1 {$where}
	");
	$rescount = $db->fetch_field($query, "count");

	// Figure out if we need to display multiple pages.
	if($daddyobb->input['page'] != "last")
	{
		$page = intval($daddyobb->input['page']);
	}

	$postcount = intval($rescount);
	$pages = $postcount / $perpage;
	$pages = ceil($pages);

	if($daddyobb->input['page'] == "last")
	{
		$page = $pages;
	}

	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	$multipage = multipage($postcount, $perpage, $page, "modcp.php?action=modlogs&amp;perpage=$perpage&amp;uid={$daddyobb->input['uid']}&amp;fid={$daddyobb->input['fid']}&amp;sortby={$daddyobb->input['sortby']}&amp;order={$daddyobb->input['order']}");
	if($postcount > $perpage)
	{
		eval("\$resultspages = \"".$templates->get("modcp_modlogs_multipage")."\";");
	}
	$query = $db->query("
		SELECT l.*, u.username, u.usergroup, u.displaygroup, t.subject AS tsubject, f.name AS fname, p.subject AS psubject
		FROM ".TABLE_PREFIX."moderatorlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=l.tid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=l.fid)
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=l.pid)
		WHERE 1=1 {$where}
		ORDER BY {$sortby} {$order}
		LIMIT {$start}, {$perpage}
	");
	while($logitem = $db->fetch_array($query))
	{
		$information = '';
		$log_date = my_date($daddyobb->settings['dateformat'], $logitem['dateline']);
		$log_time = my_date($daddyobb->settings['timeformat'], $logitem['dateline']);
		$trow = alt_trow();
		$username = format_name($logitem['username'], $logitem['usergroup'], $logitem['displaygroup']);
		$logitem['profilelink'] = build_profile_link($username, $logitem['uid']);
		if($logitem['tsubject'])
		{
			$information = "<strong>{$lang->thread}</strong> <a href=\"".get_thread_link($logitem['tid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['tsubject'])."</a><br />";
		}
		if($logitem['fname'])
		{
			$information .= "<strong>{$lang->forum}</strong> <a href=\"".get_forum_link($logitem['fid'])."\" target=\"_blank\">{$logitem['fname']}</a><br />";
		}
		if($logitem['psubject'])
		{
			$information .= "<strong>{$lang->post}</strong> <a href=\"".get_post_link($logitem['pid'])."#pid{$logitem['pid']}\">".htmlspecialchars_uni($logitem['psubject'])."</a>";
		}

		eval("\$results .= \"".$templates->get("modcp_modlogs_result")."\";");
	}

	if(!$results)
	{
		eval("\$results = \"".$templates->get("modcp_modlogs_noresults")."\";");
	}

	// Fetch filter options
	$sortbysel[$daddyobb->input['sortby']] = "selected=\"selected\"";
	$ordersel[$daddyobb->input['order']] = "selected=\"selected\"";
	$query = $db->query("
		SELECT DISTINCT l.uid, u.username
		FROM ".TABLE_PREFIX."moderatorlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (l.uid=u.uid)
		ORDER BY u.username ASC
	");
	while($user = $db->fetch_array($query))
	{
		// Deleted Users
		if(!$user['username'])
		{
			$user['username'] = $lang->na_deleted;
		}
		
		$selected = '';
		if($daddyobb->input['uid'] == $user['uid'])
		{
			$selected = " selected=\"selected\"";
		}
		$user_options .= "<option value=\"{$user['uid']}\"{$selected}>".htmlspecialchars_uni($user['username'])."</option>\n";
	}

	$forum_select = build_forum_jump("", $daddyobb->input['fid'], 1, '', 0, '', "fid");

	eval("\$modlogs = \"".$templates->get("modcp_modlogs")."\";");
	output_page($modlogs);
}

if($daddyobb->input['action'] == "do_delete_announcement")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }
  
	verify_post_check($daddyobb->input['my_post_key']);

	$aid = intval($daddyobb->input['aid']);
	$query = $db->simple_select("announcements", "aid, subject, fid", "aid='{$aid}'");
	$announcement = $db->fetch_array($query);

	if(!$announcement['aid'])
	{
		error($lang->error_invalid_announcement);
	}
	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement['fid'] == -1) || ($announcement['fid'] != -1 && !is_moderator($announcement['fid'])))
	{
		error_no_permission();
	}

	$db->delete_query("announcements", "aid='{$aid}'");

	redirect("modcp.php?action=announcements", $lang->redirect_delete_announcement);
}

if($daddyobb->input['action'] == "delete_announcement")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }
  
	$aid = intval($daddyobb->input['aid']);
	$query = $db->simple_select("announcements", "aid, subject, fid", "aid='{$aid}'");
	$announcement = $db->fetch_array($query);

	if(!$announcement['aid'])
	{
		error($lang->error_invalid_announcement);
	}
	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement['fid'] == -1) || ($announcement['fid'] != -1 && !is_moderator($announcement['fid'])))
	{
		error_no_permission();
	}

	eval("\$announcements = \"".$templates->get("modcp_announcements_delete")."\";");
	output_page($announcements);
}

if($daddyobb->input['action'] == "do_new_announcement")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }
  
	verify_post_check($daddyobb->input['my_post_key']);

	$announcement_fid = intval($daddyobb->input['fid']);
	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement_fid == -1) || ($announcement_fid != -1 && !is_moderator($announcement_fid)))
	{
		error_no_permission();
	}

	if(!trim($daddyobb->input['title']))
	{
		$errors[] = $lang->error_missing_title;
	}

	if(!trim($daddyobb->input['message']))
	{
		$errors[] = $lang->error_missing_message;
	}

	if(!trim($daddyobb->input['fid']))
	{
		$errors[] = $lang->error_missing_forum;
	}

	$startdate = @explode(" ", $daddyobb->input['starttime_time']);
	$startdate = @explode(":", $startdate[0]);
	$enddate = @explode(" ", $daddyobb->input['endtime_time']);
	$enddate = @explode(":", $enddate[0]);

	if(stristr($daddyobb->input['starttime_time'], "pm"))
	{
		$startdate[0] = 12+$startdate[0];
		if($startdate[0] >= 24)
		{
			$startdate[0] = "00";
		}
	}

	if(stristr($daddyobb->input['endtime_time'], "pm"))
	{
		$enddate[0] = 12+$enddate[0];
		if($enddate[0] >= 24)
		{
			$enddate[0] = "00";
		}
	}
	
	$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
	if(!in_array($daddyobb->input['starttime_month'], $months))
	{
		$daddyobb->input['starttime_month'] = 1;
	}

	$startdate = gmmktime(intval($startdate[0]), intval($startdate[1]), 0, (int)$daddyobb->input['starttime_month'], intval($daddyobb->input['starttime_day']), intval($daddyobb->input['starttime_year']));
	
	if($startdate < 0 || $startdate == false)
	{
		$errors[] = $lang->error_invalid_start_date;
	}

	if($daddyobb->input['endtime_type'] == "2")
	{
		$enddate = '0';
	}
	else
	{
		if(!in_array($daddyobb->input['endtime_month'], $months))
		{
			$daddyobb->input['endtime_month'] = 1;
		}
		$enddate = gmmktime(intval($enddate[0]), intval($enddate[1]), 0, (int)$daddyobb->input['endtime_month'], intval($daddyobb->input['endtime_day']), intval($daddyobb->input['endtime_year']));
		if($enddate < 0 || $enddate == false)
		{
			$errors[] = $lang->error_invalid_end_date;
		}
		elseif($enddate < $startdate)
		{
			$errors[] = $lang->error_end_before_start;
		}
	}

	if(!$errors)
	{
		$insert_announcement = array(
			'fid' => $announcement_fid,
			'uid' => $daddyobb->user['uid'],
			'subject' => $db->escape_string($daddyobb->input['title']),
			'message' => $db->escape_string($daddyobb->input['message']),
			'startdate' => $startdate,
			'enddate' => $enddate,
			'allowhtml' => $db->escape_string($daddyobb->input['allowhtml']),
			'allowmycode' => $db->escape_string($daddyobb->input['allowmycode']),
			'allowsmilies' => $db->escape_string($daddyobb->input['allowsmilies']),
		);

		$aid = $db->insert_query("announcements", $insert_announcement);
		redirect("modcp.php?action=announcements", $lang->redirect_add_announcement);
	}
	else
	{
		$daddyobb->input['action'] = 'new_announcement';
	}
}

if($daddyobb->input['action'] == "new_announcement")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }

	add_breadcrumb($lang->mcp_nav_announcements, "modcp.php?action=announcements");
	add_breadcrumb($lang->add_announcement, "modcp.php?action=new_announcements");

	$announcement_fid = intval($daddyobb->input['fid']);

	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement_fid == -1) || ($announcement_fid != -1 && !is_moderator($announcement_fid)))
	{
		error_no_permission();
	}

	// Deal with inline errors
	if(is_array($errors))
	{
		$errors = inline_error($errors);
		
		// Set $announcement to input stuff
		$announcement['subject'] = $daddyobb->input['title'];
		$announcement['message'] = $daddyobb->input['message'];
		$announcement['allowhtml'] = $daddyobb->input['allowhtml'];
		$announcement['allowmycode'] = $daddyobb->input['allowmycode'];
		$announcement['allowsmilies'] = $daddyobb->input['allowsmilies'];
		
		$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
		if(!in_array($daddyobb->input['starttime_month'], $months))
		{
			$daddyobb->input['starttime_month'] = 1;
		}
		
		if(!in_array($daddyobb->input['endtime_month'], $months))
		{
			$daddyobb->input['endtime_month'] = 1;
		}
		
		$startmonth = $daddyobb->input['starttime_month'];
		$startdateyear = htmlspecialchars_uni($daddyobb->input['starttime_year']);
		$startday = intval($daddyobb->input['starttime_day']);
		$starttime_time = htmlspecialchars($daddyobb->input['starttime_time']);

		$endmonth = $daddyobb->input['endtime_month'];
		$enddateyear = htmlspecialchars_uni($daddyobb->input['endtime_year']);
		$endday = intval($daddyobb->input['endtime_day']);
		$endtime_time = htmlspecialchars($daddyobb->input['endtime_time']);
	}
	else
	{
		// Note: dates are not in user's timezone
		$starttime_time = gmdate("g:i a", TIME_NOW);
		$endtime_time = gmdate("g:i a", TIME_NOW);
		$startday = $endday = gmdate("j", TIME_NOW);
		$startmonth = $endmonth = gmdate("m", TIME_NOW);
		$startdateyear = gmdate("Y", TIME_NOW);

		$enddateyear = $startdateyear+1;
	}

	// Generate form elements
	for($i = 1; $i <= 31; ++$i)
	{
		if($startday == $i)
		{
			$startdateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$startdateday .= "<option value=\"$i\">$i</option>\n";
		}

		if($endday == $i)
		{
			$enddateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$enddateday .= "<option value=\"$i\">$i</option>\n";
		}
	}

	$startmonthsel = $endmonthsel = array();
	$startmonthsel[$startmonth] = "selected=\"selected\"";
	$endmonthsel[$endmonth] = "selected=\"selected\"";

	$startdatemonth .= "<option value=\"01\" {$startmonthsel['01']}>{$lang->january}</option>\n";
	$enddatemonth .= "<option value=\"01\" {$endmonthsel['01']}>{$lang->january}</option>\n";
	$startdatemonth .= "<option value=\"02\" {$startmonthsel['02']}>{$lang->february}</option>\n";
	$enddatemonth .= "<option value=\"02\" {$endmonthsel['02']}>{$lang->february}</option>\n";
	$startdatemonth .= "<option value=\"03\" {$startmonthsel['03']}>{$lang->march}</option>\n";
	$enddatemonth .= "<option value=\"03\" {$endmonthsel['03']}>{$lang->march}</option>\n";
	$startdatemonth .= "<option value=\"04\" {$startmonthsel['04']}>{$lang->april}</option>\n";
	$enddatemonth .= "<option value=\"04\" {$endmonthsel['04']}>{$lang->april}</option>\n";
	$startdatemonth .= "<option value=\"05\" {$startmonthsel['05']}>{$lang->may}</option>\n";
	$enddatemonth .= "<option value=\"05\" {$endmonthsel['05']}>{$lang->may}</option>\n";
	$startdatemonth .= "<option value=\"06\" {$startmonthsel['06']}>{$lang->june}</option>\n";
	$enddatemonth .= "<option value=\"06\" {$endmonthsel['06']}>{$lang->june}</option>\n";
	$startdatemonth .= "<option value=\"07\" {$startmonthsel['07']}>{$lang->july}</option>\n";
	$enddatemonth .= "<option value=\"07\" {$endmonthsel['07']}>{$lang->july}</option>\n";
	$startdatemonth .= "<option value=\"08\" {$startmonthsel['08']}>{$lang->august}</option>\n";
	$enddatemonth .= "<option value=\"08\" {$endmonthsel['08']}>{$lang->august}</option>\n";
	$startdatemonth .= "<option value=\"09\" {$startmonthsel['09']}>{$lang->september}</option>\n";
	$enddatemonth .= "<option value=\"09\" {$endmonthsel['09']}>{$lang->september}</option>\n";
	$startdatemonth .= "<option value=\"10\" {$startmonthsel['10']}>{$lang->october}</option>\n";
	$enddatemonth .= "<option value=\"10\" {$endmonthsel['10']}>{$lang->october}</option>\n";
	$startdatemonth .= "<option value=\"11\" {$startmonthsel['11']}>{$lang->november}</option>\n";
	$enddatemonth .= "<option value=\"11\" {$endmonthsel['11']}>{$lang->november}</option>\n";
	$startdatemonth .= "<option value=\"12\" {$startmonthsel['12']}>{$lang->december}</option>\n";
	$enddatemonth .= "<option value=\"12\" {$endmonthsel['12']}>{$lang->december}</option>\n";

	$title = htmlspecialchars_uni($announcement['subject']);
	$message = htmlspecialchars_uni($announcement['message']);

	$html_sel = $mycode_sel = $smilies_sel = array();
	if($daddyobb->input['allowhtml'] || !isset($daddyobb->input['allowhtml']))
	{
		$html_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$html_sel['no'] = ' checked="checked"';
	}

	if($daddyobb->input['allowmycode'] || !isset($daddyobb->input['allowmycode']))
	{
		$mycode_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$mycode_sel['no'] = ' checked="checked"';
	}

	if($daddyobb->input['allowsmilies'] || !isset($daddyobb->input['allowsmilies']))
	{
		$smilies_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$smilies_sel['no'] = ' checked="checked"';
	}

	if($daddyobb->input['endtime_type'] == 2 || !isset($daddyobb->input['endtime_type']))
	{
		$end_type_sel['infinite'] = ' checked="checked"';
	}
	else
	{
		$end_type_sel['finite'] = ' checked="checked"';
	}

	// MyCode editor
	$codebuttons = build_mycode_inserter();
	$smilieinserter = build_clickable_smilies();

	eval("\$announcements = \"".$templates->get("modcp_announcements_new")."\";");
	output_page($announcements);
}

if($daddyobb->input['action'] == "do_edit_announcement")
{  
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }
	
	verify_post_check($daddyobb->input['my_post_key']);

	// Get the announcement
	$aid = intval($daddyobb->input['aid']);
	$query = $db->simple_select("announcements", "aid, subject, fid", "aid='{$aid}'");
	$announcement = $db->fetch_array($query);

	// Check that it exists
	if(!$announcement['aid'])
	{
		error($lang->error_invalid_announcement);
	}

	// Mod has permissions to edit this announcement
	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement['fid'] == -1) || ($announcement['fid'] != -1 && !is_moderator($announcement['fid'])))
	{
		error_no_permission();
	}

	// Basic error checking
	if(!trim($daddyobb->input['title']))
	{
		$errors[] = $lang->error_missing_title;
	}

	if(!trim($daddyobb->input['message']))
	{
		$errors[] = $lang->error_missing_message;
	}

	if(!trim($daddyobb->input['fid']))
	{
		$errors[] = $lang->error_missing_forum;
	}

	$startdate = @explode(" ", $daddyobb->input['starttime_time']);
	$startdate = @explode(":", $startdate[0]);
	$enddate = @explode(" ", $daddyobb->input['endtime_time']);
	$enddate = @explode(":", $enddate[0]);

	if(stristr($daddyobb->input['starttime_time'], "pm"))
	{
		$startdate[0] = 12+$startdate[0];
		if($startdate[0] >= 24)
		{
			$startdate[0] = "00";
		}
	}

	if(stristr($daddyobb->input['endtime_time'], "pm"))
	{
		$enddate[0] = 12+$enddate[0];
		if($enddate[0] >= 24)
		{
			$enddate[0] = "00";
		}
	}

	$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
	if(!in_array($daddyobb->input['starttime_month'], $months))
	{
		$daddyobb->input['starttime_month'] = 1;
	}

	$startdate = gmmktime(intval($startdate[0]), intval($startdate[1]), 0, (int)$daddyobb->input['starttime_month'], intval($daddyobb->input['starttime_day']), intval($daddyobb->input['starttime_year']));
	if($startdate < 0 || $startdate == false)
	{
		$errors[] = $lang->error_invalid_start_date;
	}

	if($daddyobb->input['endtime_type'] == "2")
	{
		$enddate = '0';
	}
	else
	{		
		if(!in_array($daddyobb->input['endtime_month'], $months))
		{
			$daddyobb->input['endtime_month'] = 1;
		}
		$enddate = gmmktime(intval($enddate[0]), intval($enddate[1]), 0, (int)$daddyobb->input['endtime_month'], intval($daddyobb->input['endtime_day']), intval($daddyobb->input['endtime_year']));
		if($enddate < 0 || $enddate == false)
		{
			$errors[] = $lang->error_invalid_end_date;
		}
		elseif($enddate < $startdate)
		{
			$errors[] = $lang->error_end_before_start;
		}
	}

	// Proceed to update if no errors
	if(!$errors)
	{
		$update_announcement = array(
			'uid' => $daddyobb->user['uid'],
			'subject' => $db->escape_string($daddyobb->input['title']),
			'message' => $db->escape_string($daddyobb->input['message']),
			'startdate' => $startdate,
			'enddate' => $enddate,
			'allowhtml' => $db->escape_string($daddyobb->input['allowhtml']),
			'allowmycode' => $db->escape_string($daddyobb->input['allowmycode']),
			'allowsmilies' => $db->escape_string($daddyobb->input['allowsmilies']),
		);

		$db->update_query("announcements", $update_announcement, "aid='{$aid}'");
		redirect("modcp.php?action=announcements", $lang->redirect_edit_announcement);
	}
	else
	{
		$daddyobb->input['action'] = 'edit_announcement';
	}
}

if($daddyobb->input['action'] == "edit_announcement")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }
  
	$announcement_fid = intval($daddyobb->input['fid']);
	$aid = intval($daddyobb->input['aid']);

	add_breadcrumb($lang->mcp_nav_announcements, "modcp.php?action=announcements");
	add_breadcrumb($lang->edit_announcement, "modcp.php?action=edit_announcements&amp;aid={$aid}");

	// Get announcement
	$query = $db->simple_select("announcements", "*", "aid='{$aid}'");
	$announcement = $db->fetch_array($query);

	if(!$announcement['fid'])
	{
		error($lang->error_invalid_announcement);
	}
	if(($daddyobb->usergroup['issupermod'] != 1 && $announcement['fid'] == -1) || ($announcement['fid'] != -1 && !is_moderator($announcement['fid'])))
	{
		error_no_permission();
	}

	// Deal with inline errors
	if(is_array($errors))
	{
		$errors = inline_error($errors);

		// Set $announcement to input stuff
		$announcement['subject'] = $daddyobb->input['title'];
		$announcement['message'] = $daddyobb->input['message'];
		$announcement['allowhtml'] = $daddyobb->input['allowhtml'];
		$announcement['allowmycode'] = $daddyobb->input['allowmycode'];
		$announcement['allowsmilies'] = $daddyobb->input['allowsmilies'];
		
		$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
		if(!in_array($daddyobb->input['starttime_month'], $months))
		{
			$daddyobb->input['starttime_month'] = 1;
		}
		
		if(!in_array($daddyobb->input['endtime_month'], $months))
		{
			$daddyobb->input['endtime_month'] = 1;
		}
		
		$startmonth = $daddyobb->input['starttime_month'];
		$startdateyear = htmlspecialchars_uni($daddyobb->input['starttime_year']);
		$startday = intval($daddyobb->input['starttime_day']);
		$starttime_time = htmlspecialchars($daddyobb->input['starttime_time']);
		$endmonth = $daddyobb->input['endtime_month'];
		$enddateyear = htmlspecialchars_uni($daddyobb->input['endtime_year']);
		$endday = intval($daddyobb->input['endtime_day']);
		$endtime_time = htmlspecialchars($daddyobb->input['endtime_time']);

		$errored = true;
	}
	else
	{
		// Note: dates are in user's timezone
		$starttime_time = my_date('g:i a', $announcement['startdate']);
		$endtime_time = my_date('g:i a', $announcement['enddate']);

		$startday = my_date('j', $announcement['startdate']);
		$endday = my_date('j', $announcement['enddate']);

		$startmonth = my_date('m', $announcement['startdate']);
		$endmonth = my_date('m', $announcement['enddate']);

		$startdateyear = my_date('Y', $announcement['startdate']);
		$enddateyear = my_date('Y', $announcement['enddate']);

		$errored = false;
	}

	// Generate form elements
	for($i = 1; $i <= 31; ++$i)
	{
		if($startday == $i)
		{
			$startdateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$startdateday .= "<option value=\"$i\">$i</option>\n";
		}

		if($endday == $i)
		{
			$enddateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$enddateday .= "<option value=\"$i\">$i</option>\n";
		}
	}

	$startmonthsel = $endmonthsel = array();
	$startmonthsel[$startmonth] = "selected=\"selected\"";
	$endmonthsel[$endmonth] = "selected=\"selected\"";

	$startdatemonth .= "<option value=\"01\" {$startmonthsel['01']}>{$lang->january}</option>\n";
	$enddatemonth .= "<option value=\"01\" {$endmonthsel['01']}>{$lang->january}</option>\n";
	$startdatemonth .= "<option value=\"02\" {$startmonthsel['02']}>{$lang->february}</option>\n";
	$enddatemonth .= "<option value=\"02\" {$endmonthsel['02']}>{$lang->february}</option>\n";
	$startdatemonth .= "<option value=\"03\" {$startmonthsel['03']}>{$lang->march}</option>\n";
	$enddatemonth .= "<option value=\"03\" {$endmonthsel['03']}>{$lang->march}</option>\n";
	$startdatemonth .= "<option value=\"04\" {$startmonthsel['04']}>{$lang->april}</option>\n";
	$enddatemonth .= "<option value=\"04\" {$endmonthsel['04']}>{$lang->april}</option>\n";
	$startdatemonth .= "<option value=\"05\" {$startmonthsel['05']}>{$lang->may}</option>\n";
	$enddatemonth .= "<option value=\"05\" {$endmonthsel['05']}>{$lang->may}</option>\n";
	$startdatemonth .= "<option value=\"06\" {$startmonthsel['06']}>{$lang->june}</option>\n";
	$enddatemonth .= "<option value=\"06\" {$endmonthsel['06']}>{$lang->june}</option>\n";
	$startdatemonth .= "<option value=\"07\" {$startmonthsel['07']}>{$lang->july}</option>\n";
	$enddatemonth .= "<option value=\"07\" {$endmonthsel['07']}>{$lang->july}</option>\n";
	$startdatemonth .= "<option value=\"08\" {$startmonthsel['08']}>{$lang->august}</option>\n";
	$enddatemonth .= "<option value=\"08\" {$endmonthsel['08']}>{$lang->august}</option>\n";
	$startdatemonth .= "<option value=\"09\" {$startmonthsel['09']}>{$lang->september}</option>\n";
	$enddatemonth .= "<option value=\"09\" {$endmonthsel['09']}>{$lang->september}</option>\n";
	$startdatemonth .= "<option value=\"10\" {$startmonthsel['10']}>{$lang->october}</option>\n";
	$enddatemonth .= "<option value=\"10\" {$endmonthsel['10']}>{$lang->october}</option>\n";
	$startdatemonth .= "<option value=\"11\" {$startmonthsel['11']}>{$lang->november}</option>\n";
	$enddatemonth .= "<option value=\"11\" {$endmonthsel['11']}>{$lang->november}</option>\n";
	$startdatemonth .= "<option value=\"12\" {$startmonthsel['12']}>{$lang->december}</option>\n";
	$enddatemonth .= "<option value=\"12\" {$endmonthsel['12']}>{$lang->december}</option>\n";

	$title = htmlspecialchars_uni($announcement['subject']);
	$message = htmlspecialchars_uni($announcement['message']);

	$html_sel = $mycode_sel = $smilies_sel = array();
	if($announcement['allowhtml'])
	{
		$html_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$html_sel['no'] = ' checked="checked"';
	}

	if($announcement['allowmycode'])
	{
		$mycode_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$mycode_sel['no'] = ' checked="checked"';
	}

	if($announcement['allowsmilies'])
	{
		$smilies_sel['yes'] = ' checked="checked"';
	}
	else
	{
		$smilies_sel['no'] = ' checked="checked"';
	}

	if(($errored && $daddyobb->input['endtime_type'] == 2) || (!$errored && intval($announcement['enddate']) == 0))
	{
		$end_type_sel['infinite'] = ' checked="checked"';
	}
	else
	{
		$end_type_sel['finite'] = ' checked="checked"';
	}

	// MyCode editor
	$codebuttons = build_mycode_inserter();
	$smilieinserter = build_clickable_smilies();

	eval("\$announcements = \"".$templates->get("modcp_announcements_edit")."\";");
	output_page($announcements);
}

if($daddyobb->input['action'] == "announcements")
{
  if($daddyobb->usergroup['modcanannouncements'] != 1)
  {
    error_no_permission();
  }

	add_breadcrumb($lang->mcp_nav_announcements, "modcp.php?action=announcements");

	// Fetch announcements into their proper arrays
	$query = $db->simple_select("announcements", "aid, fid, subject, enddate");
	while($announcement = $db->fetch_array($query))
	{
		if($announcement['fid'] == -1)
		{
			$global_announcements[$announcement['aid']] = $announcement;
			continue;
		}
		$announcements[$announcement['fid']][$announcement['aid']] = $announcement;
	}

	if($daddyobb->usergroup['issupermod'] == 1)
	{
		$show_global_ann = 1;
		if($global_announcements && $daddyobb->usergroup['issupermod'] == 1)
		{
			// Get the global announcements
			foreach($global_announcements as $aid => $announcement)
			{
				$trow = alt_trow();
				if($announcement['startdate'] > TIME_NOW || ($announcement['enddate'] < TIME_NOW && $announcement['enddate'] != 0))
				{
					$icon = "<img src=\"images/minioff.gif\" alt=\"({$lang->expired})\" title=\"{$lang->expired_announcement}\"  style=\"vertical-align: middle;\" /> ";
				}
				else
				{
					$icon = "<img src=\"images/minion.gif\" alt=\"({$lang->active})\" title=\"{$lang->active_announcement}\"  style=\"vertical-align: middle;\" /> ";
				}

				$subject = htmlspecialchars_uni($announcement['subject']);

				eval("\$global_ann = \"".$templates->get("modcp_announcements_announcement_global")."\";");;
			}
		}
		else
		{
				$global_ann = 0;
		}
		eval("\$announcements_global = \"".$templates->get("modcp_announcements_global")."\";");
	}
	else
	{
		// Moderator is not super, so don't show global annnouncemnets
		$show_global_ann = 0;
	}

	fetch_forum_announcements();

	if(!$announcements_forum)
	{
		eval("\$announcements_forum = \"".$templates->get("modcp_no_announcements_forum")."\";");
	}

	eval("\$announcements = \"".$templates->get("modcp_announcements")."\";");
	output_page($announcements);
}

if($daddyobb->input['action'] == "do_modqueue")
{
	require_once DADDYOBB_ROOT."inc/class_moderation.php";
	$moderation = new Moderation;

	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	if(is_array($daddyobb->input['threads']))
	{
		// Fetch threads
		$query = $db->simple_select("threads", "tid", "tid IN (".implode(",", array_map("intval", array_keys($daddyobb->input['threads'])))."){$flist}");
		while($thread = $db->fetch_array($query))
		{
			$action = $daddyobb->input['threads'][$thread['tid']];
			if($action == "approve")
			{
				$threads_to_approve[] = $thread['tid'];
			}
			else if($action == "delete")
			{
				$moderation->delete_thread($thread['tid']);
			}
		}
		if(is_array($threads_to_approve))
		{
			$moderation->approve_threads($threads_to_approve);
		}
		redirect("modcp.php?action=modqueue", $lang->redirect_threadsmoderated);
	}
	else if(is_array($daddyobb->input['posts']))
	{
		// Fetch posts
		$query = $db->simple_select("posts", "pid", "pid IN (".implode(",", array_map("intval", array_keys($daddyobb->input['posts'])))."){$flist}");
		while($post = $db->fetch_array($query))
		{
			$action = $daddyobb->input['posts'][$post['pid']];
			if($action == "approve")
			{
				$posts_to_approve[] = $post['pid'];
			}
			else if($action == "delete")
			{
				$moderation->delete_post($post['pid']);
			}
		}
		if(is_array($posts_to_approve))
		{
			$moderation->approve_posts($posts_to_approve);
		}
		redirect("modcp.php?action=modqueue&type=posts", $lang->redirect_postsmoderated);
	}
	else if(is_array($daddyobb->input['attachments']))
	{
		$query = $db->query("
			SELECT a.pid, a.aid
			FROM  ".TABLE_PREFIX."attachments a
			LEFT JOIN ".TABLE_PREFIX."posts p ON (a.pid=p.pid)
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE aid IN (".implode(",", array_map("intval", array_keys($daddyobb->input['attachments'])))."){$tflist}
		");
		while($attachment = $db->fetch_array($query))
		{
			$action = $daddyobb->input['attachments'][$attachment['aid']];
			if($action == "approve")
			{
				$db->update_query("attachments", array("visible" => 1), "aid='{$attachment['aid']}'");
			}
			else if($action == "delete")
			{
				remove_attachment($attachment['pid'], '', $attachment['aid']);
			}
		}
		redirect("modcp.php?action=modqueue&type=attachments", $lang->redirect_attachmentsmoderated);
	}
}

if($daddyobb->input['action'] == "modqueue")
{
	if($daddyobb->input['type'] == "threads" || !$daddyobb->input['type'])
	{
		$forum_cache = $cache->read("forums");

		$query = $db->simple_select("threads", "COUNT(tid) AS unapprovedthreads", "visible=0 {$flist}");
		$threadcount = $unapproved_threads = $db->fetch_field($query, "unapprovedthreads");

		// Figure out if we need to display multiple pages.
		if($daddyobb->input['page'] != "last")
		{
			$page = intval($daddyobb->input['page']);
		}

		$perpage = $daddyobb->settings['threadsperpage'];
		$pages = $unapproved_threads / $perpage;
		$pages = ceil($pages);

		if($daddyobb->input['page'] == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}

		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$multipage = multipage($pages, $perpage, $page, "modcp.php?action=modqueue&amp;type=threads");

		$query = $db->query("
			SELECT t.tid, t.dateline, t.fid, t.subject, p.message AS postmessage, u.username AS username, t.uid
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=t.firstpost)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=t.uid)
			WHERE t.visible='0' {$tflist}
			ORDER BY t.lastpost DESC
			LIMIT {$start}, {$perpage}
		");
		while($thread = $db->fetch_array($query))
		{
			$altbg = alt_trow();
			$thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));
			$thread['threadlink'] = get_thread_link($thread['tid']);
			$thread['forumlink'] = get_forum_link($thread['fid']);
			$forum_name = $forum_cache[$thread['fid']]['name'];
			$threaddate = my_date($daddyobb->settings['dateformat'], $thread['dateline']);
			$threadtime = my_date($daddyobb->settings['timeformat'], $thread['dateline']);
			$profile_link = build_profile_link($thread['username'], $thread['uid']);
			$thread['postmessage'] = nl2br(htmlspecialchars_uni($thread['postmessage']));
			$forum = "<strong>{$lang->meta_forum} <a href=\"{$thread['forumlink']}\">{$forum_name}</a></strong>";
			eval("\$threads .= \"".$templates->get("modcp_modqueue_threads_thread")."\";");
		}

		if(!$threads && $daddyobb->input['type'] == "threads")
		{
			eval("\$threads = \"".$templates->get("modcp_modqueue_threads_empty")."\";");
		}

		if($threads)
		{
			add_breadcrumb($lang->mcp_nav_modqueue_threads, "modcp.php?action=modqueue&amp;type=threads");
			eval("\$mass_controls = \"".$templates->get("modcp_modqueue_masscontrols")."\";");
			eval("\$threadqueue = \"".$templates->get("modcp_modqueue_threads")."\";");
			output_page($threadqueue);
		}
		$type = 'threads';
	}

	if($daddyobb->input['type'] == "posts" || (!$daddyobb->input['type'] && !$threadqueue))
	{
		$forum_cache = $cache->read("forums");

		$query = $db->query("
			SELECT COUNT(pid) AS unapprovedposts
			FROM  ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
		");
		$postcount = $unapproved_posts = $db->fetch_field($query, "unapprovedposts");

		// Figure out if we need to display multiple pages.
		if($daddyobb->input['page'] != "last")
		{
			$page = intval($daddyobb->input['page']);
		}

		$perpage = $daddyobb->settings['postsperpage'];
		$pages = $unapproved_posts / $perpage;
		$pages = ceil($pages);

		if($daddyobb->input['page'] == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}

		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$multipage = multipage($pages, $perpage, $page, "modcp.php?action=modqueue&amp;type=posts");

		$query = $db->query("
			SELECT p.pid, p.subject, p.message, t.subject AS threadsubject, t.tid, u.username, p.uid, t.fid, p.dateline
			FROM  ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
			WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
			ORDER BY p.dateline DESC
			LIMIT {$start}, {$perpage}
		");
		while($post = $db->fetch_array($query))
		{
			$altbg = alt_trow();
			$post['threadsubject'] = htmlspecialchars_uni($parser->parse_badwords($post['threadsubject']));
			$post['threadlink'] = get_thread_link($post['tid']);
			$post['forumlink'] = get_forum_link($post['fid']);
			$post['postlink'] = get_post_link($post['pid'], $post['tid']);
			$forum_name = $forum_cache[$post['fid']]['name'];
			$postdate = my_date($daddyobb->settings['dateformat'], $post['dateline']);
			$posttime = my_date($daddyobb->settings['timeformat'], $post['dateline']);
			$profile_link = build_profile_link($post['username'], $post['uid']);
			$thread = "<strong>{$lang->meta_thread} <a href=\"{$post['threadlink']}\">{$post['threadsubject']}</a></strong>";
			$forum = "<strong>{$lang->meta_forum} <a href=\"{$post['forumlink']}\">{$forum_name}</a></strong><br />";
			$post['message'] = nl2br(htmlspecialchars_uni($post['message']));
			eval("\$posts .= \"".$templates->get("modcp_modqueue_posts_post")."\";");
		}

		if(!$posts && $daddyobb->input['type'] == "posts")
		{
			eval("\$posts = \"".$templates->get("modcp_modqueue_posts_empty")."\";");
		}

		if($posts)
		{
			add_breadcrumb($lang->mcp_nav_modqueue_posts, "modcp.php?action=modqueue&amp;type=posts");
			eval("\$mass_controls = \"".$templates->get("modcp_modqueue_masscontrols")."\";");
			eval("\$postqueue = \"".$templates->get("modcp_modqueue_posts")."\";");
			output_page($postqueue);
		}
	}

	if($daddyobb->input['type'] == "attachments" || (!$daddyobb->input['type'] && !$postqueue && !$threadqueue))
	{
		$query = $db->query("
			SELECT COUNT(aid) AS unapprovedattachments
			FROM  ".TABLE_PREFIX."attachments a
			LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE a.visible='0' {$tflist}
		");
		$attachmentcount = $unapproved_attachments = $db->fetch_field($query, "unapprovedattachments");

		// Figure out if we need to display multiple pages.
		if($daddyobb->input['page'] != "last")
		{
			$page = intval($daddyobb->input['page']);
		}

		$perpage = $daddyobb->settings['postsperpage'];
		$pages = $unapproved_attachments / $perpage;
		$pages = ceil($pages);

		if($daddyobb->input['page'] == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}

		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$multipage = multipage($pages, $perpage, $page, "modcp.php?action=modqueue&amp;type=attachments");

		$query = $db->query("
			SELECT a.*, p.subject AS postsubject, p.dateline, p.uid, u.username, t.tid, t.subject AS threadsubject
			FROM  ".TABLE_PREFIX."attachments a
			LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
			WHERE a.visible='0'
			ORDER BY a.dateuploaded DESC
			LIMIT {$start}, {$perpage}
		");
		while($attachment = $db->fetch_array($query))
		{
			$altbg = alt_trow();

			if(!$attachment['dateuploaded'])
			{
				$attachment['dateuploaded'] = $attachment['dateline'];
			}
			
			$attachdate = my_date($daddyobb->settings['dateformat'], $attachment['dateuploaded']);
			$attachtime = my_date($daddyobb->settings['timeformat'], $attachment['dateuploaded']);

			$attachment['postsubject'] = htmlspecialchars_uni($attachment['postsubject']);
			$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);
			$attachment['threadsubject'] = htmlspecialchars_uni($attachment['threadsubject']);
			$attachment['filesize'] = get_friendly_size($attachment['filesize']);

			$link = get_post_link($attachment['pid'], $attachment['tid']) . "#pid{$attachment['pid']}";
			$thread_link = get_thread_link($attachment['tid']);
			$profile_link = build_profile_link($attachment['username'], $attachment['uid']);

			eval("\$attachments .= \"".$templates->get("modcp_modqueue_attachments_attachment")."\";");
		}

		if(!$attachments && $daddyobb->input['type'] == "attachments")
		{
			eval("\$attachments = \"".$templates->get("modcp_modqueue_attachments_empty")."\";");
		}

		if($attachments)
		{
			add_breadcrumb($lang->mcp_nav_modqueue_attachments, "modcp.php?action=modqueue&amp;type=attachments");
			eval("\$mass_controls = \"".$templates->get("modcp_modqueue_masscontrols")."\";");
			eval("\$attachmentqueue = \"".$templates->get("modcp_modqueue_attachments")."\";");
			output_page($attachmentqueue);
		}
	}

	// Still nothing? All queues are empty! :-D
	if(!$threadqueue && !$postqueue && !$attachmentqueue)
	{
		add_breadcrumb($lang->mcp_nav_modqueue, "modcp.php?action=modqueue");
		eval("\$queue = \"".$templates->get("modcp_modqueue_empty")."\";");
		output_page($queue);
	}
}

if($daddyobb->input['action'] == "do_editprofile")
{
  if($daddyobb->usergroup['modcanmanageprofiles'] != 1)
  {
    error_no_permission();
  }

	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$user = get_user($daddyobb->input['uid']);
	if(!$user['uid'])
	{
		error($lang->invalid_user);
	}

	// Check if the current user has permission to edit this user
	$user_permissions = user_permissions($user['uid']);

	// Current user is only a local moderator, cannot edit super mods or admins
	if($daddyobb->user['usergroup'] == 6 && ($user_permissions['issupermod'] == 1 || $user_permissions['cancp'] == 1))
	{
		error_no_permission();
	}
	// Current user is a super mod or is an administrator and the user we are editing is a super admin, cannot edit admins
	else if(!modcp_can_manage_user($user['uid']))
	{
		error_no_permission();
	}
	// Otherwise, free to edit

	// Set up user handler.
	require_once DADDYOBB_ROOT."inc/datahandlers/user.php";
	$userhandler = new UserDataHandler('update');

	// Set the data for the new user.
	$updated_user = array(
		"uid" => $daddyobb->input['uid'],
		"profile_fields" => $daddyobb->input['profile_fields'],
		"profile_fields_editable" => true,
		"website" => $daddyobb->input['website'],
		"icq" => $daddyobb->input['icq'],
		"aim" => $daddyobb->input['aim'],
		"yahoo" => $daddyobb->input['yahoo'],
		"msn" => $daddyobb->input['msn'],
		"signature" => $daddyobb->input['signature'],
	);

	$updated_user['birthday'] = array(
		"day" => $daddyobb->input['birthday_day'],
		"month" => $daddyobb->input['birthday_month'],
		"year" => $daddyobb->input['birthday_year']
	);

	if($daddyobb->input['usertitle'] != '')
	{
		$updated_user['usertitle'] = $daddyobb->input['usertitle'];
	}
	else if($daddyobb->input['reverttitle'])
	{
		$updated_user['usertitle'] = '';
	}

	if($daddyobb->input['remove_avatar'])
	{
		$updated_user['avatarurl'] = '';
	}

	// Set the data of the user in the datahandler.
	$userhandler->set_data($updated_user);
	$errors = '';

	// Validate the user and get any errors that might have occurred.
	if(!$userhandler->validate_user())
	{
		$errors = $userhandler->get_friendly_errors();
		$daddyobb->input['action'] = "editprofile";
	}
	else
	{
		// Are we removing an avatar from this user?
		if($daddyobb->input['remove_avatar'])
		{
			$extra_user_updates = array(
				"avatar" => "",
				"avatardimensions" => "",
				"avatartype" => ""
			);
			remove_avatars($user['uid']);
		}

		$user_info = $userhandler->update_user();
		$db->update_query("users", $extra_user_updates, "uid='{$user['uid']}'");
		redirect("modcp.php?action=finduser", $lang->redirect_user_updated);
	}
}

if($daddyobb->input['action'] == "editprofile")
{
  if($daddyobb->usergroup['modcanmanageprofiles'] != 1)
  {
    error_no_permission();
  }
  
	$user = get_user($daddyobb->input['uid']);
	if(!$user['uid'])
	{
		error($lang->invalid_user);
	}

	// Check if the current user has permission to edit this user
	$user_permissions = user_permissions($user['uid']);

	// Current user is only a local moderator, cannot edit super mods or admins
	if($daddyobb->user['usergroup'] == 6 && ($user_permissions['issupermod'] == 1 || $user_permissions['cancp'] == 1))
	{
		error_no_permission();
	}
	// Current user is a super mod or is an administrator and the user we are editing is a super admin, cannot edit admins
	else if(!modcp_can_manage_user($user['uid']))
	{
		error_no_permission();
	}
	// Otherwise, free to edit

	if($user['website'] == "" || $user['website'] == "http://")
	{
		$user['website'] = "http://";
	}

	if($user['icq'] != "0")
	{
		$user['icq'] = intval($user['icq']);
	}
	if($user['icq'] == 0)
	{
		$user['icq'] = "";
	}

	if(!$errors)
	{
		$daddyobb->input = array_merge($user, $daddyobb->input);
		list($daddyobb->input['birthday_day'], $daddyobb->input['birthday_month'], $daddyobb->input['birthday_year']) = explode("-", $user['birthday']);
	}
	else
	{
		$errors = inline_error($errors);
	}

	// Sanitize all input
	foreach(array('usertitle', 'website', 'icq', 'aim', 'yahoo', 'msn', 'signature', 'birthday_day', 'birthday_month', 'birthday_year') as $field)
	{
		$daddyobb->input[$field] = htmlspecialchars_uni($daddyobb->input[$field]);
	}

	if($daddyobb->usergroup['usertitle'] == "")
	{
		$query = $db->simple_select("usertitles", "*", "posts <='".$user['postnum']."'", array('order_by' => 'posts', 'order_dir' => 'DESC', 'limit' => 1));
		$utitle = $db->fetch_array($query);
		$defaulttitle = $utitle['title'];
	}
	else
	{
		$display_group = usergroup_displaygroup($user['displaygroup']);
		$defaulttitle = $display_group['usertitle'];
	}
	if(empty($user['usertitle']))
	{
		$lang->current_custom_usertitle = '';
	}

	$bdaysel = '';
	for($i = 1; $i <= 31; ++$i)
	{
		if($daddyobb->input['birthday_day'] == $i)
		{
			$bdaydaysel .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$bdaydaysel .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$bdaymonthsel[$daddyobb->input['birthday_month']] = "selected";


	// Fetch profile fields
	$query = $db->simple_select("userfields", "*", "ufid='{$user['uid']}'");
	$user_fields = $db->fetch_array($query);

	$requiredfields = '';
	$customfields = '';
	$alttrow = "";
	$query = $db->simple_select("profilefields", "*", "", array('order_by' => 'disporder'));
	while($profilefield = $db->fetch_array($query))
	{
		$alttrow = alt_trow();
		$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
		$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
		$thing = explode("\n", $profilefield['type'], "2");
		$type = $thing[0];
		$options = $thing[1];
		$field = "fid{$profilefield['fid']}";
		$select = '';
		if($errors)
		{
			$userfield = $daddyobb->input['profile_fields'][$field];
		}
		else
		{
			$userfield = $user_fields[$field];
		}
		if($type == "multiselect")
		{
			if($errors)
			{
				$useropts = $userfield;
			}
			else
			{
				$useropts = explode("\n", $userfield);
			}
			if(is_array($useropts))
			{
				foreach($useropts as $key => $val)
				{
					$seloptions[$val] = $val;
				}
			}
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$val = trim($val);
					$val = str_replace("\n", "\\n", $val);

					$sel = "";
					if($val == $seloptions[$val])
					{
						$sel = " selected=\"selected\"";
					}
					$select .= "<option value=\"$val\"$sel>$val</option>\n";
				}
				if(!$profilefield['length'])
				{
					$profilefield['length'] = 3;
				}
				$code = "<select name=\"profile_fields[$field][]\" size=\"{$profilefield['length']}\" multiple=\"multiple\">$select</select>";
			}
		}
		elseif($type == "select")
		{
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$val = trim($val);
					$val = str_replace("\n", "\\n", $val);
					$sel = "";
					if($val == $userfield)
					{
						$sel = " selected=\"selected\"";
					}
					$select .= "<option value=\"$val\"$sel>$val</option>";
				}
				if(!$profilefield['length'])
				{
					$profilefield['length'] = 1;
				}
				$code = "<select name=\"profile_fields[$field]\" size=\"{$profilefield['length']}\">$select</select>";
			}
		}
		elseif($type == "radio")
		{
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$checked = "";
					if($val == $userfield)
					{
						$checked = " checked=\"checked\"";
					}
					$code .= "<input type=\"radio\" class=\"radio\" name=\"profile_fields[$field]\" value=\"$val\"$checked /> <span class=\"smalltext\">$val</span><br />";
				}
			}
		}
		elseif($type == "checkbox")
		{
			if($errors)
			{
				$useropts = $userfield;
			}
			else
			{
				$useropts = explode("\n", $userfield);
			}
			if(is_array($useropts))
			{
				foreach($useropts as $key => $val)
				{
					$seloptions[$val] = $val;
				}
			}
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$checked = "";
					if($val == $seloptions[$val])
					{
						$checked = " checked=\"checked\"";
					}
					$code .= "<input type=\"checkbox\" class=\"checkbox\" name=\"profile_fields[$field][]\" value=\"$val\"$checked /> <span class=\"smalltext\">$val</span><br />";
				}
			}
		}
		elseif($type == "textarea")
		{
			$value = htmlspecialchars_uni($userfield);
			$code = "<textarea name=\"profile_fields[$field]\" rows=\"6\" cols=\"30\" style=\"width: 95%\">$value</textarea>";
		}
		else
		{
			$value = htmlspecialchars_uni($userfield);
			$code = "<input type=\"text\" name=\"profile_fields[$field]\" class=\"textbox\" size=\"{$profilefield['length']}\" maxlength=\"{$profilefield['maxlength']}\" value=\"$value\" />";
		}
		if($profilefield['required'] == 1)
		{
			if($lasttrow == "trow2")
			{
        $requiredfields .= "<tr class=\"trow1\">\n";
        $lasttrow = "trow1";
      }
      else
      {
        $requiredfields .= "<tr class=\"trow2\">\n";
        $lasttrow = "trow2";
      }
			$requiredfields .= "\t<td width=\"25%\" class=\"smalltext\">{$profilefield['name']}:</td><td width=\"75%\" class=\"smalltext\">{$code}</td>\n";
			$requiredfields .= "</tr>\n";
		}
		else
		{
			if($lasttrow == "trow2")
			{
        $customfields .= "<tr class=\"trow1\">\n";
        $lasttrow = "trow1";
      }
      else
      {
        $customfields .= "<tr class=\"trow2\">\n";
        $lasttrow = "trow2";
      }
			$customfields .= "\t<td width=\"25%\" class=\"smalltext\">{$profilefield['name']}:</td>\n\t<td width=\"75%\" class=\"smalltext\">{$code}</td>\n";
			$customfields .= "</tr>\n";
		}
		$altbg = alt_trow();
		$code = "";
		$select = "";
		$val = "";
		$options = "";
		$expoptions = "";
		$useropts = "";
		$seloptions = "";
	}

	$lang->edit_profile = $lang->sprintf($lang->edit_profile, $user['username']);
	$profile_link = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);

	$codebuttons = build_mycode_inserter("signature");
	eval("\$edituser = \"".$templates->get("modcp_editprofile")."\";");
	output_page($edituser);
}

if($daddyobb->input['action'] == "finduser")
{
  if($daddyobb->usergroup['modcanmanageprofiles'] != 1)
  {
    error_no_permission();
  }
  
	$perpage = intval($daddyobb->input['perpage']);
	if(!$perpage || $perpage <= 0)
	{
		$perpage = $daddyobb->settings['threadsperpage'];
	}
	$where = '';

	if($daddyobb->input['username'])
	{
		$where = " AND LOWER(username) LIKE '%".my_strtolower($db->escape_string_like($daddyobb->input['username']))."%'";
	}

	// Sort order & direction
	switch($daddyobb->input['sortby'])
	{
		case "lastvisit":
			$sortby = "lastvisit";
			break;
		case "postnum":
			$sortby = "postnum";
			break;
		case "username":
			$sortby = "username";
			break;
		default:
			$sortby = "regdate";
	}
	$order = $daddyobb->input['order'];
	if($order != "asc")
	{
		$order = "desc";
	}

	$query = $db->simple_select("users", "COUNT(uid) AS count", "1=1 {$where}");
	$usercount = $user_count = $db->fetch_field($query, "count");

	// Figure out if we need to display multiple pages.
	if($daddyobb->input['page'] != "last")
	{
		$page = intval($daddyobb->input['page']);
	}

	$pages = $user_count / $perpage;
	$pages = ceil($pages);

	if($daddyobb->input['page'] == "last")
	{
		$page = $pages;
	}

	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}
	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	$page_url = 'modcp.php?action=finduser';
	foreach(array('username', 'sortby', 'order') as $field)
	{
		if($daddyobb->input[$field])
		{
			$page_url .= "&amp;{$field}=".htmlspecialchars_uni($daddyobb->input[$field]);
			$daddyobb->input[$field] = htmlspecialchars_uni($daddyobb->input[$field]);
		}
	}

	$multipage = multipage($user_count, $perpage, $page, $page_url);

	$usergroups_cache = $cache->read("usergroups");

	// Fetch out results
	$query = $db->simple_select("users", "*", "1=1 {$where}", array("order_by" => $sortby, "order_dir" => $order, "limit" => $perpage, "limit_start" => $start));
	while($user = $db->fetch_array($query))
	{
		$alt_row = alt_trow();
		$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$user['postnum'] = my_number_format($user['postnum']);
		$regdate = my_date($daddyobb->settings['dateformat'], $user['regdate']);
		$regtime = my_date($daddyobb->settings['timeformat'], $user['regdate']);
		$lastdate = my_date($daddyobb->settings['dateformat'], $user['lastvisit']);
		$lasttime = my_date($daddyobb->settings['timeformat'], $user['lastvisit']);
		$usergroup = $usergroups_cache[$user['usergroup']]['title'];
		eval("\$users .= \"".$templates->get("modcp_finduser_user")."\";");
	}

	eval("\$finduser = \"".$templates->get("modcp_finduser")."\";");
	output_page($finduser);
}

if($daddyobb->input['action'] == "warninglogs")
{
  if($daddyobb->usergroup['canwarnusers'] != 1)
  {
    error_no_permission();
  }
  
	add_breadcrumb($lang->mcp_nav_warninglogs, "modcp.php?action=warninglogs");

	// Filter options
	$where_sql = '';
	if($daddyobb->input['filter']['username'])
	{
		$search['username'] = $db->escape_string($daddyobb->input['filter']['username']);
		$query = $db->simple_select("users", "uid", "username='{$search['username']}'");
		$daddyobb->input['filter']['uid'] = $db->fetch_field($query, "uid");
		$daddyobb->input['filter']['username'] = htmlspecialchars_uni($daddyobb->input['filter']['username']);
	}
	if($daddyobb->input['filter']['uid'])
	{
		$search['uid'] = intval($daddyobb->input['filter']['uid']);
		$where_sql .= " AND w.uid='{$search['uid']}'";
		if(!isset($daddyobb->input['search']['username']))
		{
			$user = get_user($daddyobb->input['search']['uid']);
			$daddyobb->input['search']['username'] = htmlspecialchars_uni($user['username']);
		}
	}
	if($daddyobb->input['filter']['mod_username'])
	{
		$search['mod_username'] = $db->escape_string($daddyobb->input['filter']['mod_username']);
		$query = $db->simple_select("users", "uid", "username='{$search['mod_username']}'");
		$daddyobb->input['filter']['mod_uid'] = $db->fetch_field($query, "uid");
		$daddyobb->input['filter']['mod_username'] = htmlspecialchars_uni($daddyobb->input['filter']['mod_username']);
	}
	if($daddyobb->input['filter']['mod_uid'])
	{
		$search['mod_uid'] = intval($daddyobb->input['filter']['mod_uid']);
		$where_sql .= " AND w.issuedby='{$search['mod_uid']}'";
		if(!isset($daddyobb->input['search']['mod_username']))
		{
			$mod_user = get_user($daddyobb->input['search']['uid']);
			$daddyobb->input['search']['mod_username'] = htmlspecialchars_uni($mod_user['username']);
		}
	}
	if($daddyobb->input['filter']['reason'])
	{
		$search['reason'] = $db->escape_string($daddyobb->input['filter']['reason']);
		$where_sql .= " AND (w.notes LIKE '%{$search['reason']}%' OR t.title LIKE '%{$search['reason']}%' OR w.title LIKE '%{$search['reason']}%')";
		$daddyobb->input['filter']['reason'] = htmlspecialchars_uni($daddyobb->input['filter']['reason']);
	}
	$sortbysel = array();
	switch($daddyobb->input['filter']['sortby'])
	{
		case "username":
			$sortby = "u.username";
			$sortbysel['username'] = ' selected="selected"';
			break;
		case "expires":
			$sortby = "w.expires";
			$sortbysel['expires'] = ' selected="selected"';
			break;
		case "issuedby":
			$sortby = "i.username";
			$sortbysel['issuedby'] = ' selected="selected"';
			break;
		default: // "dateline"
			$sortby = "w.dateline";
			$sortbysel['dateline'] = ' selected="selected"';
	}
	$order = $daddyobb->input['filter']['order'];
	$ordersel = array();
	if($order != "asc")
	{
		$order = "desc";
		$ordersel['desc'] = ' selected="selected"';
	}
	else
	{
		$ordersel['asc'] = ' selected="selected"';
	}

	// Pagination stuff
	$sql = "
		SELECT COUNT(wid) as count
		FROM
			".TABLE_PREFIX."warnings w
			LEFT JOIN ".TABLE_PREFIX."warningtypes t ON (w.tid=t.tid)
		WHERE 1=1
			{$where_sql}
	";
	$query = $db->query($sql);
	$total_warnings = $db->fetch_field($query, 'count');
	$page = 1;
	if(isset($daddyobb->input['page']) && intval($daddyobb->input['page']) > 0)
	{
		$page = intval($daddyobb->input['page']);
	}
	$per_page = 20;
	if(isset($daddyobb->input['filter']['per_page']) && intval($daddyobb->input['filter']['per_page']) > 0)
	{
		$per_page = intval($daddyobb->input['filter']['per_page']);
	}
	$start = ($page-1) * $per_page;
	// Build the base URL for pagination links
	$url = 'modcp.php?action=warninglogs';
	if(is_array($daddyobb->input['filter']) && count($daddyobb->input['filter']))
	{
		foreach($daddyobb->input['filter'] as $field => $value)
		{
			$value = urlencode($value);
			$url .= "&amp;filter[{$field}]={$value}";
		}
	}
	$multipage = multipage($total_warnings, $per_page, $page, $url);

	// The actual query
	$sql = "
		SELECT
			w.wid, w.title as custom_title, w.points, w.dateline, w.issuedby, w.expires, w.expired, w.daterevoked, w.revokedby,
			t.title,
			u.uid, u.username, u.usergroup, u.displaygroup,
			i.uid as mod_uid, i.username as mod_username, i.usergroup as mod_usergroup, i.displaygroup as mod_displaygroup
		FROM ".TABLE_PREFIX."warnings w
			LEFT JOIN ".TABLE_PREFIX."users u ON (w.uid=u.uid)
			LEFT JOIN ".TABLE_PREFIX."warningtypes t ON (w.tid=t.tid)
			LEFT JOIN ".TABLE_PREFIX."users i ON (i.uid=w.issuedby)
		WHERE 1=1
			{$where_sql}
		ORDER BY {$sortby} {$order}
		LIMIT {$start}, {$per_page}
	";
	$query = $db->query($sql);


	$warning_list = '';
	while($row = $db->fetch_array($query))
	{
		$trow = alt_trow();
		$username = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
		$username_link = build_profile_link($username, $row['uid']);
		$mod_username = format_name($row['mod_username'], $row['mod_usergroup'], $row['mod_displaygroup']);
		$mod_username_link = build_profile_link($mod_username, $row['mod_uid']);
		$issued_date = my_date($daddyobb->settings['dateformat'], $row['dateline']).' '.my_date($daddyobb->settings['timeformat'], $row['dateline']);
		$revoked_text = '';
		if($row['daterevoked'] > 0)
		{
			$revoked_date = my_date($daddyobb->settings['dateformat'], $row['daterevoked']).' '.my_date($daddyobb->settings['timeformat'], $row['daterevoked']);
			eval("\$revoked_text = \"".$templates->get("modcp_warninglogs_warning_revoked")."\";");
		}
		if($row['expires'] > 0)
		{
			$expire_date = my_date($daddyobb->settings['dateformat'], $row['expires']).' '.my_date($daddyobb->settings['timeformat'], $row['expires']);
		}
		else
		{
			$expire_date = $lang->never;
		}
		$title = $row['title'];
		if(empty($row['title']))
		{
			$title = $row['custom_title'];
		}
		$title = htmlspecialchars_uni($title);
		if($row['points'] >= 0)
		{
			$points = '+'.$row['points'];
		}

		eval("\$warning_list .= \"".$templates->get("modcp_warninglogs_warning")."\";");
	}

	if(!$warning_list)
	{
		eval("\$warning_list = \"".$templates->get("modcp_warninglogs_nologs")."\";");
	}

	eval("\$warninglogs = \"".$templates->get("modcp_warninglogs")."\";");
	output_page($warninglogs);
}

if($daddyobb->input['action'] == "ipsearch")
{
	add_breadcrumb($lang->mcp_nav_ipsearch, "modcp.php?action=ipsearch");

	if($daddyobb->input['ipaddress'])
	{
		if(!is_array($groupscache))
		{
			$groupscache = $cache->read("usergroups");
		}

		$ipaddressvalue = htmlspecialchars_uni($daddyobb->input['ipaddress']);

		// Searching post IP addresses
		if($daddyobb->input['search_posts'])
		{
			// IPv6 IP
			if(strpos($daddyobb->input['ipaddress'], ":") !== false)
			{
				$post_ip_sql = "ipaddress LIKE '".$db->escape_string(str_replace("*", "%", $daddyobb->input['ipaddress']))."'";
			}
			else
			{
				$ip_range = fetch_longipv4_range($daddyobb->input['ipaddress']);
				if(!is_array($ip_range))
				{
					$post_ip_sql = "longipaddress='{$ip_range}'";
				}
				else
				{
					$post_ip_sql = "longipaddress > '{$ip_range[0]}' AND longipaddress < '{$ip_range[1]}'";
				}
			}
			$query = $db->query("
				SELECT COUNT(pid) AS count
				FROM ".TABLE_PREFIX."posts
				WHERE {$post_ip_sql}
			");
			$post_results = $db->fetch_field($query, "count");
		}

		// Searching user IP addresses
		if($daddyobb->input['search_users'])
		{
			// IPv6 IP
			if(strpos($daddyobb->input['ipaddress'], ":") !== false)
			{
				$user_ip_sql = "regip LIKE '".$db->escape_string(str_replace("*", "%", $daddyobb->input['ipaddress']))."' OR lastip LIKE '".$db->escape_string(str_replace("*", "%", $daddyobb->input['ipaddress']))."'";
			}
			else
			{
				$ip_range = fetch_longipv4_range($daddyobb->input['ipaddress']);
				if(!is_array($ip_range))
				{
					$user_ip_sql = "longregip='{$ip_range}' OR longlastip='{$ip_range}'";
				}
				else
				{
					$user_ip_sql = "(longregip > '{$ip_range[0]}' AND longregip < '{$ip_range[1]}') OR (longlastip > '{$ip_range[0]}' AND longlastip < '{$ip_range[1]}')";
				}
			}
			$query = $db->query("
				SELECT COUNT(uid) AS count
				FROM ".TABLE_PREFIX."users
				WHERE {$user_ip_sql}
			");
			$user_results = $db->fetch_field($query, "count");
		}

		$total_results = $post_results+$user_results;

		// Now we have the result counts, paginate
		$perpage = intval($daddyobb->input['perpage']);
		if(!$perpage || $perpage <= 0)
		{
			$perpage = $daddyobb->settings['threadsperpage'];
		}

		// Figure out if we need to display multiple pages.
		if($daddyobb->input['page'] != "last")
		{
			$page = intval($daddyobb->input['page']);
		}

		$pages = $total_results / $perpage;
		$pages = ceil($pages);

		if($daddyobb->input['page'] == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}

		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}

		$page_url = "modcp.php?action=ipsearch&amp;perpage={$perpage}&amp;ipaddress={$daddyobb->input['ipaddress']}";
		foreach(array('ipaddress', 'search_users', 'search_posts') as $input)
		{
			if(!$daddyobb->input[$input]) continue;
			$page_url .= "&amp;{$input}=".htmlspecialchars_uni($daddyobb->input[$input]);
		}
		$multipage = multipage($total_results, $perpage, $page, $page_url);

		$post_limit = $perpage;
		if($daddyobb->input['search_users'] && $start <= $user_results)
		{
			$query = $db->query("
				SELECT username, uid, regip, lastip
				FROM ".TABLE_PREFIX."users
				WHERE {$user_ip_sql}
				ORDER BY regdate DESC
				LIMIT {$start}, {$perpage}
			");
			while($ipaddress = $db->fetch_array($query))
			{
				$result = false;
				$profile_link = build_profile_link($ipaddress['username'], $ipaddress['uid']);
				$trow = alt_trow();
				$regexp_ip = str_replace("\*", "(.*)", preg_quote($daddyobb->input['ipaddress'], "#"));
				// Reg IP matches
				if(preg_match("#{$regexp_ip}#i", $ipaddress['regip']))
				{
					$ip = $ipaddress['regip'];
					$subject = "<strong>{$lang->ipresult_regip}</strong> {$profile_link}";
					eval("\$results .= \"".$templates->get("modcp_ipsearch_result")."\";");
					$result = true;
				}
				// Last known IP matches
				if(preg_match("#{$regexp_ip}#i", $ipaddress['lastip']))
				{
					$ip = $ipaddress['lastip'];
					$subject = "<strong>{$lang->ipresult_lastip}</strong> {$profile_link}";
					eval("\$results .= \"".$templates->get("modcp_ipsearch_result")."\";");
					$result = true;
				}

				if($result)
				{
					--$post_limit;
				}
			}
		}
		$post_start = 0;
		if($total_results > $user_results && $post_limit)
		{
			$post_start = $start-$user_results;
			if($post_start < 0) $post_start = 0;
		}
		if($daddyobb->input['search_posts'] && (!$daddyobb->input['search_users'] || ($daddyobb->input['search_users'] && $post_limit > 0)))
		{
			$query = $db->query("
				SELECT p.username AS postusername, p.uid, u.username, p.subject, p.pid, p.tid, p.ipaddress, t.subject AS threadsubject
				FROM ".TABLE_PREFIX."posts p
				LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
				LEFT JOIN ".TABLE_PREFIX."users u ON(p.uid=u.uid)
				WHERE {$post_ip_sql}
				ORDER BY p.dateline DESC
				LIMIT {$post_start}, {$post_limit}
			");
			while($ipaddress = $db->fetch_array($query))
			{
				$ip = $ipaddress['ipaddress'];
				if(!$ipaddress['username']) $ipaddress['username'] = $ipaddress['postusername']; // Guest username support
				$trow = alt_trow();
				if(!$ipaddress['subject'])
				{
					$ipaddress['subject'] = "RE: {$ipaddress['threadsubject']}";
				}
				$subject = "<strong>{$lang->ipresult_post}</strong> <a href=\"".get_post_link($ipaddress['pid'], $ipaddress['tid'])."\">".htmlspecialchars_uni($ipaddress['subject'])."</a> {$lang->by} ".build_profile_link($ipaddress['username'], $ipaddress['uid']);
				eval("\$results .= \"".$templates->get("modcp_ipsearch_result")."\";");
			}
		}

		if(!$results)
		{
			eval("\$results = \"".$templates->get("modcp_ipsearch_noresults")."\";");
		}

		if($ipaddressvalue)
		{
			$lang->ipsearch_results = $lang->sprintf($lang->ipsearch_results, $ipaddressvalue);
		}
		else
		{
			$lang->ipsearch_results = $lang->ipsearch;
		}

		eval("\$ipsearch_results = \"".$templates->get("modcp_ipsearch_results")."\";");
	}

	// Fetch filter options
	if(!$daddyobb->input['ipaddress'])
	{
		$daddyobb->input['search_posts'] = 1;
		$daddyobb->input['search_users'] = 1;
	}
	if($daddyobb->input['search_posts'])
	{
		$postsearchselect = "checked=\"checked\"";
	}
	if($daddyobb->input['search_users'])
	{
		$usersearchselect = "checked=\"checked\"";
	}

	eval("\$ipsearch = \"".$templates->get("modcp_ipsearch")."\";");
	output_page($ipsearch);
}

if($daddyobb->input['action'] == "banning")
{
  if($daddyobb->usergroup['modcanbanusers'] != 1)
  {
    error_no_permission();
  }
  
	add_breadcrumb($lang->mcp_nav_banning, "modcp.php?action=banning");

	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}

	// Figure out if we need to display multiple pages.
	$perpage = $daddyobb->settings['threadsperpage'];
	if($daddyobb->input['page'] != "last")
	{
		$page = intval($daddyobb->input['page']);
	}

	$query = $db->simple_select("banned", "COUNT(uid) AS count");
	$banned_count = $db->fetch_field($query, "count");

	$postcount = intval($banned_count);
	$pages = $postcount / $perpage;
	$pages = ceil($pages);

	if($daddyobb->input['page'] == "last")
	{
		$page = $pages;
	}

	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$upper = $start+$perpage;

	$multipage = multipage($postcount, $perpage, $page, "modcp.php?action=banning");
	if($postcount > $perpage)
	{
		eval("\$allbannedpages = \"".$templates->get("modcp_banning_multipage")."\";");
	}

	$query = $db->query("
		SELECT b.*, a.username AS adminuser, u.username
		FROM ".TABLE_PREFIX."banned b
		LEFT JOIN ".TABLE_PREFIX."users u ON (b.uid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."users a ON (b.admin=a.uid)
		ORDER BY lifted ASC
		LIMIT {$start}, {$perpage}
	");

	// Get the banned users
	$banncounter = 0;
	while($banned = $db->fetch_array($query))
	{
		$profile_link = build_profile_link($banned['username'], $banned['uid']);

		// Only show the edit & lift links if current user created ban, or is super mod/admin
		$edit_link = '';
		if($daddyobb->user['uid'] == $banned['admin'] || !$banned['adminuser'] || $daddyobb->usergroup['issupermod'] == 1 || $daddyobb->usergroup['cancp'] == 1)
		{
			$edit_link = "<br /><span class=\"smalltext\"><a href=\"modcp.php?action=banuser&amp;uid={$banned['uid']}\">{$lang->edit_ban}</a> | <a href=\"modcp.php?action=liftban&amp;uid={$banned['uid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->lift_ban}</a></span>";
		}

		$admin_profile = build_profile_link($banned['adminuser'], $banned['admin']);

		$trow = alt_trow();

		if($banned['reason'])
		{
			$banned['reason'] = htmlspecialchars_uni($parser->parse_badwords($banned['reason']));
		}
		else
		{
			$banned['reason'] = $lang->na;
		}

		if($banned['lifted'] == 'perm' || $banned['lifted'] == '' || $banned['bantime'] == 'perm' || $banned['bantime'] == '---')
		{
			$banlength = $lang->permanent;
			$timeremaining = $lang->na;
		}
		else
		{
			$banlength = $bantimes[$banned['bantime']];
			$remaining = $banned['lifted']-TIME_NOW;

			$timeremaining = nice_time($remaining, array('short' => 1, 'seconds' => false))."";

			if($remaining < 3600)
			{
				$timeremaining = "<span style=\"color: red;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 86400)
			{
				$timeremaining = "<span style=\"color: maroon;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 604800)
			{
				$timeremaining = "<span style=\"color: green;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else
			{
				$timeremaining = "({$timeremaining} {$lang->ban_remaining})";
			}
		}
		$banncounter++;
		eval("\$bannedusers .= \"".$templates->get("modcp_banning_ban")."\";");
	}
	
	$plugins->run_hooks("modcp_banning");

	eval("\$bannedpage = \"".$templates->get("modcp_banning")."\";");
	output_page($bannedpage);
}

if($daddyobb->input['action'] == "liftban")
{
  if($daddyobb->usergroup['modcanbanusers'] != 1)
  {
    error_no_permission();
  }
  
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$query = $db->simple_select("banned", "*", "uid='".intval($daddyobb->input['uid'])."'");
	$ban = $db->fetch_array($query);

	if(!$ban['uid'])
	{
		error($lang->error_invalidban);
	}

	// Permission to edit this ban?
	if($daddyobb->user['uid'] != $ban['admin'] && $daddyobb->usergroup['issupermod'] != 1 && $daddyobb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}

	$updated_group = array(
		'usergroup' => $ban['oldgroup'],
		'additionalgroups' => $ban['oldadditionalgroups'],
		'displaygroup' => $ban['olddisplaygroup']
	);
	$db->update_query("users", $updated_group, "uid='{$ban['uid']}'");
	$db->delete_query("banned", "uid='{$ban['uid']}'");

	$cache->update_banned();
	$cache->update_moderators();

	redirect("modcp.php?action=banning", $lang->redirect_banlifted);
}

if($daddyobb->input['action'] == "do_banuser" && $daddyobb->request_method == "post")
{
  if($daddyobb->usergroup['modcanbanusers'] != 1)
  {
    error_no_permission();
  }

	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	// Editing an existing ban
	if($daddyobb->input['uid'])
	{
		// Get the users info from their uid
		$query = $db->query("
			SELECT b.*, u.uid, u.usergroup, u.additionalgroups, u.displaygroup
			FROM ".TABLE_PREFIX."banned b
			LEFT JOIN ".TABLE_PREFIX."users u ON (b.uid=u.uid)
			WHERE b.uid='{$daddyobb->input['uid']}'
		");
		$user = $db->fetch_array($query);
		if(!$user['uid'])
		{
			error($lang->error_invalidban);
		}

		// Permission to edit this ban?
		if($daddyobb->user['uid'] != $user['admin'] && $daddyobb->usergroup['issupermod'] != 1 && $daddyobb->usergroup['cancp'] != 1)
		{
			error_no_permission();
		}
	}
	// Creating a new ban
	else
	{
		// Get the users info from their Username
		$query = $db->simple_select("users", "uid, usergroup, additionalgroups, displaygroup", "username = '".$db->escape_string($daddyobb->input['username'])."'", array('limit' => 1));
		$user = $db->fetch_array($query);
		if(!$user['uid'])
		{
			$errors[] = $lang->invalid_username;
		}
	}

	if($user['uid'] == $daddyobb->user['uid'])
	{
		$errors[] = $lang->error_cannotbanself;
	}

	// Have permissions to ban this user?
	if(!modcp_can_manage_user($user['uid']))
	{
		$errors[] = $lang->error_cannotbanuser;
	}

	// Check for an incoming reason
	if(!$daddyobb->input['banreason'])
	{
		$errors[] = $lang->error_nobanreason;
	}

	// Check banned group
	if(!$db->fetch_field($db->simple_select("usergroups", "gid", "isbannedgroup=1 AND gid='".intval($daddyobb->input['usergroup'])."'"), "gid"))
	{
		$errors[] = $lang->error_nobangroup;
	}

	// If this is a new ban, we check the user isn't already part of a banned group
	if(!$daddyobb->input['uid'] && $user['uid'])
	{
		$query = $db->simple_select("banned", "uid", "uid='{$user['uid']}'");
		if($db->fetch_field($query, "uid"))
		{
			$errors[] = $lang->error_useralreadybanned;
		}
	}

	// Still no errors? Ban the user
	if(!$errors)
	{
		// Ban the user
		if($daddyobb->input['liftafter'] == '---')
		{
			$lifted = 0;
		}
		else
		{
			$lifted = ban_date2timestamp($daddyobb->input['liftafter'], $user['dateline']);
		}

		if($daddyobb->input['uid'])
		{
			$update_array = array(
				'gid' => intval($daddyobb->input['usergroup']),
				'admin' => intval($daddyobb->user['uid']),
				'dateline' => TIME_NOW,
				'bantime' => $db->escape_string($daddyobb->input['liftafter']),
				'lifted' => $db->escape_string($lifted),
				'reason' => $db->escape_string($daddyobb->input['banreason'])
			);

			$db->update_query('banned', $update_array, "uid='{$user['uid']}'");
		}
		else
		{
			$insert_array = array(
				'uid' => $user['uid'],
				'gid' => intval($daddyobb->input['usergroup']),
				'oldgroup' => $user['usergroup'],
				'oldadditionalgroups' => $user['additionalgroups'],
				'olddisplaygroup' => $user['displaygroup'],
				'admin' => intval($daddyobb->user['uid']),
				'dateline' => TIME_NOW,
				'bantime' => $db->escape_string($daddyobb->input['liftafter']),
				'lifted' => $db->escape_string($lifted),
				'reason' => $db->escape_string($daddyobb->input['banreason'])
			);

			$db->insert_query('banned', $insert_array);
		}

		// Move the user to the banned group
		$update_array = array(
			'usergroup' => intval($daddyobb->input['usergroup']),
			'displaygroup' => 0,
			'additionalgroups' => '',
		);
		$db->update_query('users', $update_array, "uid = {$user['uid']}");

		$cache->update_banned();

		if($daddyobb->input['uid'])
		{
			redirect("modcp.php?action=banning", $lang->redirect_banuser_updated);
		}
		else
		{
			redirect("modcp.php?action=banning", $lang->redirect_banuser);
		}
	}
	// Otherwise has errors, throw back to ban page
	else
	{
		$daddyobb->input['action'] = "banuser";
	}
}

if($daddyobb->input['action'] == "banuser")
{  
  if($daddyobb->usergroup['modcanbanusers'] != 1)
  {
    error_no_permission();
  }
	
	add_breadcrumb($lang->mcp_nav_banning, "modcp.php?action=banning");

	if($daddyobb->input['uid'])
	{
		add_breadcrumb($lang->mcp_nav_ban_user);
	}
	else
	{
		add_breadcrumb($lang->mcp_nav_editing_ban);
	}

	// If incoming user ID, we are editing a ban
	if($daddyobb->input['uid'])
	{
		$query = $db->query("
			SELECT b.*, u.username
			FROM ".TABLE_PREFIX."banned b
			LEFT JOIN ".TABLE_PREFIX."users u ON (b.uid=u.uid)
			WHERE b.uid='{$daddyobb->input['uid']}'
		");
		$banned = $db->fetch_array($query);
		if($banned['username'])
		{
			$username = htmlspecialchars_uni($banned['username']);
			$banreason = htmlspecialchars_uni($banned['reason']);
			$uid = $daddyobb->input['uid'];
			$lang->ban_user = $lang->edit_ban; // Swap over lang variables
			$addusername = 0;
		}
	}
	
	// New ban!
	if(!$banuser_username)
	{
		if($daddyobb->input['uid'])
		{
			$user = get_user($daddyobb->input['uid']);
			$username = $user['username'];
		}
		else
		{
			$username = htmlspecialchars_uni($daddyobb->input['username']);
		}
		$addusername = 1;
	}

	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
		$banned = array(
			"bantime" => $daddyobb->input['liftafter'],
			"reason" => $daddyobb->input['reason'],
			"gid" => $daddyobb->input['gid']
		);
		$banreason = htmlspecialchars_uni($daddyobb->input['banreason']);
	}

	// Generate the banned times dropdown
	foreach($bantimes as $time => $title)
	{
		$liftlist .= "<option value=\"{$time}\"";
		if($banned['bantime'] == $time)
		{
			$liftlist .= " selected=\"selected\"";
		}
		$thatime = my_date("D, jS M Y @ g:ia", ban_date2timestamp($time, $banned['dateline']));
		if($time == '---')
		{
			$liftlist .= ">{$title}</option>\n";
		}
		else
		{
			$liftlist .= ">{$title} ({$thatime})</option>\n";
		}
	}
	
	$bangroups = '';
	$query = $db->simple_select("usergroups", "gid, title", "isbannedgroup=1");
	while($item = $db->fetch_array($query))
	{
		$selected = "";
		if($banned['gid'] == $item['gid'])
		{
			$selected = " selected=\"selected\"";
		}
		$bangroups .= "<option value=\"{$item['gid']}\"{$selected}>".htmlspecialchars_uni($item['title'])."</option>\n";
	}
	
	$lift_link = "<div class=\"float_right\"><a href=\"modcp.php?action=liftban&amp;uid={$user['uid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->lift_ban}</a></div>";

	eval("\$banuser = \"".$templates->get("modcp_banuser")."\";");
	output_page($banuser);
}

if($daddyobb->input['action'] == "do_modnotes")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);
	
	// Update Moderator Notes cache
	$update_cache = array(
		"modmessage" => $daddyobb->input['modnotes']
	);

	$cache->update("modnotes", $update_cache);
	redirect("modcp.php", $lang->redirect_modnotes);
}

if(!$daddyobb->input['action'])
{
	$query = $db->query("
		SELECT COUNT(aid) AS unapprovedattachments
		FROM  ".TABLE_PREFIX."attachments a
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE a.visible='0' {$tflist}
	");
	$unapproved_attachments = $db->fetch_field($query, "unapprovedattachments");

	if($unapproved_attachments > 0)
	{
		$query = $db->query("
			SELECT t.tid, p.pid, p.uid, t.username, a.filename, a.dateuploaded
			FROM  ".TABLE_PREFIX."attachments a
			LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=a.pid)
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE a.visible='0' {$tflist}
			ORDER BY a.dateuploaded DESC
			LIMIT 1
		");
		$attachment = $db->fetch_array($query);
		$attachment['date'] = my_date($daddyobb->settings['dateformat'], $attachment['dateuploaded']);
		$attachment['time'] = my_date($daddyobb->settings['timeformat'], $attachment['dateuploaded']);
		$attachment['profilelink'] = build_profile_link($attachment['username'], $attachment['uid']);
		$attachment['link'] = get_post_link($attachment['pid'], $attachment['tid']);
		$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);

		eval("\$latest_attachment = \"".$templates->get("modcp_lastattachment")."\";");
	}
	else
	{
		$latest_attachment = "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->query("
		SELECT COUNT(pid) AS unapprovedposts
		FROM  ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
	");
	$unapproved_posts = $db->fetch_field($query, "unapprovedposts");

	if($unapproved_posts > 0)
	{
		$query = $db->query("
			SELECT p.pid, p.tid, p.subject, p.uid, p.username, p.dateline
			FROM  ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
			WHERE p.visible='0' {$tflist} AND t.firstpost != p.pid
			ORDER BY p.dateline DESC
			LIMIT 1
		");
		$post = $db->fetch_array($query);
		$post['date'] = my_date($daddyobb->settings['dateformat'], $post['dateline']);
		$post['time'] = my_date($daddyobb->settings['timeformat'], $post['dateline']);
		$post['profilelink'] = build_profile_link($post['username'], $post['uid']);
		$post['link'] = get_post_link($post['pid'], $post['tid']);
		$post['subject'] = $post['fullsubject'] = $parser->parse_badwords($post['subject']);
		if(my_strlen($post['subject']) > 25)
		{
			$post['subject'] = my_substr($post['subject'], 0, 25)."...";
		}
		$post['subject'] = htmlspecialchars_uni($post['subject']);
		$post['fullsubject'] = htmlspecialchars_uni($post['fullsubject']);

		eval("\$latest_post = \"".$templates->get("modcp_lastpost")."\";");
	}
	else
	{
		$latest_post =  "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->simple_select("threads", "COUNT(tid) AS unapprovedthreads", "visible=0 {$flist}");
	$unapproved_threads = $db->fetch_field($query, "unapprovedthreads");

	if($unapproved_threads > 0)
	{
		$query = $db->simple_select("threads", "tid, subject, uid, username, dateline", "visible=0 {$flist}", array('order_by' =>  'dateline', 'order_dir' => 'DESC', 'limit' => 1));
		$thread = $db->fetch_array($query);
		$thread['date'] = my_date($daddyobb->settings['dateformat'], $thread['dateline']);
		$thread['time'] = my_date($daddyobb->settings['timeformat'], $thread['dateline']);
		$thread['profilelink'] = build_profile_link($thread['username'], $thread['uid']);
		$thread['link'] = get_thread_link($thread['tid']);
		$thread['subject'] = $thread['fullsubject'] = $parser->parse_badwords($thread['subject']);
		if(my_strlen($thread['subject']) > 25)
		{
			$post['subject'] = my_substr($thread['subject'], 0, 25)."...";
		}
		$thread['subject'] = htmlspecialchars_uni($thread['subject']);
		$thread['fullsubject'] = htmlspecialchars_uni($thread['fullsubject']);

		eval("\$latest_thread = \"".$templates->get("modcp_lastthread")."\";");
	}
	else
	{
		$latest_thread = "<span style=\"text-align: center;\">{$lang->lastpost_never}</span>";
	}

	$query = $db->query("
		SELECT l.*, u.username, u.usergroup, u.displaygroup, t.subject AS tsubject, f.name AS fname, p.subject AS psubject
		FROM ".TABLE_PREFIX."moderatorlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=l.tid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=l.fid)
		LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=l.pid)
		ORDER BY l.dateline DESC
		LIMIT 5
	");
	while($logitem = $db->fetch_array($query))
	{
		$information = '';
		$log_date = my_date($daddyobb->settings['dateformat'], $logitem['dateline']);
		$log_time = my_date($daddyobb->settings['timeformat'], $logitem['dateline']);
		$trow = alt_trow();
		$username = format_name($logitem['username'], $logitem['usergroup'], $logitem['displaygroup']);
		$logitem['profilelink'] = build_profile_link($username, $logitem['uid']);
		if($logitem['tsubject'])
		{
			$information = "<strong>{$lang->thread}</strong> <a href=\"".get_thread_link($logitem['tid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['tsubject'])."</a><br />";
		}
		if($logitem['fname'])
		{
			$information .= "<strong>{$lang->forum}</strong> <a href=\"".get_forum_link($logitem['fid'])."\" target=\"_blank\">".htmlspecialchars_uni($logitem['fname'])."</a><br />";
		}
		if($logitem['psubject'])
		{
			$information .= "<strong>{$lang->post}</strong> <a href=\"".get_post_link($logitem['pid'])."#pid{$logitem['pid']}\">".htmlspecialchars_uni($logitem['psubject'])."</a>";
		}

		eval("\$modlogresults .= \"".$templates->get("modcp_modlogs_result")."\";");
	}

	if(!$modlogresults)
	{
		eval("\$modlogresults = \"".$templates->get("modcp_modlogs_noresults")."\";");
	}

	$query = $db->query("
		SELECT b.*, a.username AS adminuser, u.username, (b.lifted-".TIME_NOW.") AS remaining
		FROM ".TABLE_PREFIX."banned b
		LEFT JOIN ".TABLE_PREFIX."users u ON (b.uid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."users a ON (b.admin=a.uid)
		WHERE b.bantime != '---' AND b.bantime != 'perm'
		ORDER BY remaining ASC
		LIMIT 5
	");

	// Get the banned users
	while($banned = $db->fetch_array($query))
	{
		$profile_link = build_profile_link($banned['username'], $banned['uid']);

		// Only show the edit & lift links if current user created ban, or is super mod/admin
		$edit_link = '';
		if($daddyobb->user['uid'] == $banned['admin'] || !$banned['adminuser'] || $daddyobb->usergroup['issupermod'] == 1 || $daddyobb->usergroup['cancp'] == 1)
		{
			$edit_link = "<br /><span class=\"smalltext\"><a href=\"modcp.php?action=banuser&amp;uid={$banned['uid']}\">{$lang->edit_ban}</a> | <a href=\"modcp.php?action=liftban&amp;uid={$banned['uid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->lift_ban}</a></span>";
		}

		$admin_profile = build_profile_link($banned['adminuser'], $banned['admin']);

		$trow = alt_trow();

		if($banned['reason'])
		{
			$banned['reason'] = htmlspecialchars_uni($parser->parse_badwords($banned['reason']));
		}
		else
		{
			$banned['reason'] = $lang->na;
		}

		if($banned['lifted'] == 'perm' || $banned['lifted'] == '' || $banned['bantime'] == 'perm' || $banned['bantime'] == '---')
		{
			$banlength = $lang->permanent;
			$timeremaining = $lang->na;
		}
		else
		{
			$banlength = $bantimes[$banned['bantime']];
			$remaining = $banned['remaining'];

			$timeremaining = nice_time($remaining, array('short' => 1, 'seconds' => false))."";

			if($remaining < 3600)
			{
				$timeremaining = "<span style=\"color: red;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 86400)
			{
				$timeremaining = "<span style=\"color: maroon;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else if($remaining < 604800)
			{
				$timeremaining = "<span style=\"color: green;\">({$timeremaining} {$lang->ban_remaining})</span>";
			}
			else
			{
				$timeremaining = "({$timeremaining} {$lang->ban_remaining})";
			}
		}

		eval("\$bannedusers .= \"".$templates->get("modcp_banning_ban")."\";");
	}

	if(!$bannedusers)
	{
		eval("\$bannedusers = \"".$templates->get("modcp_banning_nobanned")."\";");
	}

	$modnotes = $cache->read("modnotes");
	$modnotes = htmlspecialchars_uni($modnotes['modmessage']);

	eval("\$modcp = \"".$templates->get("modcp")."\";");
	output_page($modcp);
}

?>