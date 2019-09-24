<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:07 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'printthread.php');

$templatelist = "printthread,printthread_post";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("printthread");

$plugins->run_hooks("printthread_start");

$query = $db->simple_select("threads", "*", "tid='".intval($daddyobb->input['tid'])."'  AND closed NOT LIKE 'moved|%'");
$thread = $db->fetch_array($query);
$thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));

$fid = $thread['fid'];
$tid = $thread['tid'];

// Is the currently logged in user a moderator of this forum?
if(is_moderator($fid))
{
	$ismod = true;
}
else
{
	$ismod = false;
}

// Make sure we are looking at a real thread here.
if(!$tid || ($thread['visible'] == 0 && $ismod == false) || ($thread['visible'] > 1 && $ismod == true))
{
	error($lang->error_invalidthread);
}

// Get forum info
$forum = get_forum($fid);
if(!$forum)
{
	error($lang->error_invalidforum);
}

$breadcrumb = makeprintablenav();

$parentsexp = explode(",", $forum['parentlist']);
$numparents = count($parentsexp);
$tdepth = "-";
for($i = 0; $i < $numparents; ++$i)
{
	$tdepth .= "-";
}
$forumpermissions = forum_permissions($forum['fid']);

if($forum['type'] != "f")
{
	error($lang->error_invalidforum);
}
if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
{
	error_no_permission();
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

$thread['threadlink'] = get_thread_link($tid);

$postrows = '';
$query = $db->query("
	SELECT u.*, u.username AS userusername, p.*
	FROM ".TABLE_PREFIX."posts p
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
	WHERE p.tid='$tid' AND p.visible=1
	ORDER BY p.dateline
");
while($postrow = $db->fetch_array($query))
{
	if($postrow['userusername'])
	{
		$postrow['username'] = $postrow['userusername'];
	}
	$postrow['subject'] = htmlspecialchars_uni($parser->parse_badwords($postrow['subject']));
	$postrow['date'] = my_date($daddyobb->settings['dateformat'], $postrow['dateline']);
	$postrow['time'] = my_date($daddyobb->settings['timeformat'], $postrow['dateline']);
	$postrow['profilelink'] = build_profile_link($postrow['username'], $postrow['uid']);
	$parser_options = array(
		"allow_html" => $forum['allowhtml'],
		"allow_mycode" => $forum['allowmycode'],
		"allow_smilies" => $forum['allowsmilies'],
		"allow_imgcode" => $forum['allowimgcode'],
		"me_username" => $postrow['username'],
		"shorten_urls" => 0,
		"filter_badwords" => 1
	);
	if($postrow['smilieoff'] == 1)
	{
		$parser_options['allow_smilies'] = 0;
	}

	$postrow['message'] = $parser->parse_message($postrow['message'], $parser_options);
	$plugins->run_hooks("printthread_post");
	eval("\$postrows .= \"".$templates->get("printthread_post")."\";");
}
eval("\$printable = \"".$templates->get("printthread")."\";");

$plugins->run_hooks("printthread_end");

output_page($printable);

function makeprintablenav($pid="0", $depth="--")
{
	global $db, $pforumcache, $fid, $forum, $lang;
	if(!is_array($pforumcache))
	{
		$parlist = build_parent_list($fid, "fid", "OR", $forum['parentlist']);
		$query = $db->simple_select("forums", "name, fid, pid", "$parlist", array('order_by' => 'pid, disporder'));
		while($forumnav = $db->fetch_array($query))
		{
			$pforumcache[$forumnav['pid']][$forumnav['fid']] = $forumnav;
		}
		unset($forumnav);
	}
	if(is_array($pforumcache[$pid]))
	{
		foreach($pforumcache[$pid] as $key => $forumnav)
		{
			$forums .= "+".$depth." $lang->forum {$forumnav['name']} (<i>".$daddyobb->settings['bburl']."/".get_forum_link($forumnav['fid'])."</i>)<br />\n";
			if($pforumcache[$forumnav['fid']])
			{
				$newdepth = $depth."-";
				$forums .= makeprintablenav($forumnav['fid'], $newdepth);
			}
		}
	}
	return $forums;
}

?>