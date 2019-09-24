<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:05 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'announcement.php');

$templatelist = "announcement";
require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";

// Load global language phrases
$lang->load("announcements");

$aid = intval($daddyobb->input['aid']);

$plugins->run_hooks("announcements_start");

// Get announcement fid
$query = $db->simple_select("announcements", "fid", "aid='$aid'");
$announcement = $db->fetch_array($query);

if(!$announcement)
{
	error($lang->error_invalidannouncement);
}

// Get forum info
$fid = $announcement['fid'];
if($fid > 0)
{
	$forum = get_forum($fid);

	if(!$forum)
	{
		error($lang->error_invalidforum);
	}

	// Make navigation
	build_forum_breadcrumb($forum['fid']);

	// Permissions
	$forumpermissions = forum_permissions($forum['fid']);

	if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
	{
		error_no_permission();
	}
}
add_breadcrumb($lang->nav_announcements);

$archive_url = build_archive_link("announcement", $aid);

// Get announcement info
$time = TIME_NOW;

$query = $db->query("
	SELECT u.*, u.username AS userusername, a.*, f.*, g.title AS grouptitle, g.usertitle AS groupusertitle, g.stars AS groupstars, g.starimage AS groupstarimage, g.image AS groupimage, g.namestyle, g.usereputationsystem
	FROM ".TABLE_PREFIX."announcements a
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=a.uid)
	LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
	LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid=u.usergroup)
	WHERE a.startdate<='$time' AND (a.enddate>='$time' OR a.enddate='0') AND a.aid='$aid'
");
$announcementarray = $db->fetch_array($query);

$db->shutdown_query("UPDATE ".TABLE_PREFIX."announcements SET views=views+1 WHERE aid='{$aid}'");
	++$announcement['views'];

if(!$announcementarray)
{
	error($lang->error_invalidannouncement);
}

$announcementarray['dateline'] = $announcementarray['startdate'];
$announcementarray['userusername'] = $announcementarray['username'];
$announcement = build_postbit($announcementarray, 3);
$lang->forum_announcement = $lang->sprintf($lang->forum_announcement, htmlspecialchars_uni($announcementarray['subject']));

$plugins->run_hooks("announcements_end");

eval("\$forumannouncement = \"".$templates->get("announcement")."\";");
output_page($forumannouncement);
?>