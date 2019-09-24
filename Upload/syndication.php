<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:08 19.12.2008
 */
 
define("IN_DADDYOBB", 1);
define("IGNORE_CLEAN_VARS", "fid");
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'syndication.php');

require_once "./global.php";

// Load global language phrases
$lang->load("syndication");

// Load syndication class.
require_once DADDYOBB_ROOT."inc/class_feedgeneration.php";
$feedgenerator = new FeedGenerator();

// Load the post parser
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Find out the thread limit.
$thread_limit = intval($daddyobb->input['limit']);
if($thread_limit > 50)
{
	$thread_limit = 50;
}
else if(!$thread_limit)
{
	$thread_limit = 20;
}

// Syndicate a specific forum or all viewable?
if(isset($daddyobb->input['fid']))
{
	$forumlist = $daddyobb->input['fid'];
	$forumlist = explode(',', $forumlist);
}
else
{
	$forumlist = "";
}

// Get the forums the user is not allowed to see.
$unviewableforums = get_unviewable_forums(true);
$inactiveforums = get_inactive_forums();

$unviewable = '';

// If there are any, add SQL to exclude them.
if($unviewableforums)
{
	$unviewable .= " AND f.fid NOT IN($unviewableforums)";
}

if($inactiveforums)
{
	$unviewable .= " AND f.fid NOT IN($inactiveforums)";
}

// If there are no forums to syndicate, syndicate all viewable.
if(!empty($forumlist))
{
	$forum_ids = "'-1'";
	foreach($forumlist as $fid)
	{
		$forum_ids .= ",'".intval($fid)."'";
	}
	$forumlist = "AND f.fid IN ($forum_ids) $unviewable";
}
else
{
	$forumlist = $unviewable;
	$all_forums = 1;
}

// Find out which title to add to the feed.
$title = $daddyobb->settings['bbname'];
$query = $db->simple_select("forums f", "f.name, f.fid", "1=1 ".$forumlist);
$comma = " - ";
while($forum = $db->fetch_array($query))
{
	$title .= $comma.$forum['name'];
	$forumcache[$forum['fid']] = $forum;
	$comma = ", ";
}

// If syndicating all forums then cut the title back to "All Forums"
if($all_forums)
{
	$title = $daddyobb->settings['bbname']." - ".$lang->all_forums;
}

// Get the threads to syndicate.
$query = $db->query("
	SELECT t.tid, t.dateline, p.edittime, t.subject, f.allowhtml, f.allowmycode, f.allowsmilies, f.allowimgcode,
	f.name, p.message, u.username, p.smilieoff, f.fid
	FROM ".TABLE_PREFIX."threads t
	LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=t.fid)
	LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=t.firstpost)
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
	WHERE t.visible=1 AND t.closed NOT LIKE 'moved|%' ".$forumlist."
	ORDER BY t.dateline DESC
	LIMIT 0, ".$thread_limit
);

// Set the feed type.
$feedgenerator->set_feed_format($daddyobb->input['type']);

// Set the channel header.
$channel = array(
	"title" => $title,
	"link" => $daddyobb->settings['bburl']."/",
	"date" => TIME_NOW,
	"description" => $daddyobb->settings['bbname']." - ".$daddyobb->settings['bburl']
);
$feedgenerator->set_channel($channel);

// Loop through all the threads.
while($thread = $db->fetch_array($query))
{
	$thread['link'] = $channel['link'].get_thread_link($thread['tid']);
	if($forumcache[$thread['fid']])
	{
		if($thread['smilieoff'])
		{
			$thread['allowsmilies'] = 0;
		}
		
		// Set up the parser options.
		$parser_options = array(
			"allow_html" => $thread['allowhtml'],
			"allow_mycode" => $thread['allowmycode'],
			"allow_smilies" => $thread['allowsmilies'],
			"allow_imgcode" => $thread['allowimgcode'],
			"filter_badwords" => 1
		);
		
		$thread['message'] = $parser->parse_message($thread['message'], $parser_options);
		
		$item = array(
			'updated' => $thread['edittime'],
			'author' => $thread['username'],
			'title' => $thread['subject'],
			'name' => $thread['forumname'],
			'description' => $thread['message'],
			'date' => $thread['dateline'],
			'link' => $thread['link']
		);
		
		$feedgenerator->add_item($item);
	}
}

// Then output the feed XML.
$feedgenerator->output_feed();
?>