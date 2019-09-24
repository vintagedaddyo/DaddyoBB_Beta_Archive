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
define('THIS_SCRIPT', 'ratethread.php');

$templatelist = '';
require_once "./global.php";

// Verify incoming POST request
verify_post_check($daddyobb->input['my_post_key']);

$lang->load("ratethread");

$tid = intval($daddyobb->input['tid']);
$query = $db->simple_select("threads", "*", "tid='{$tid}'");
$thread = $db->fetch_array($query);
if(!$thread['tid'])
{
	error($lang->error_invalidthread);
}

$forumpermissions = forum_permissions($thread['fid']);
if($forumpermissions['canview'] == 0 || $forumpermissions['canratethreads'] == 0 || $daddyobb->usergroup['canratethreads'] == 0)
{
	error_no_permission();
}

// Get forum info
$fid = $thread['fid'];
$forum = get_forum($fid);
if(!$forum)
{
	error($lang->error_invalidforum);
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

if($forum['allowtratings'] == 0)
{
	error_no_permission();
}
$daddyobb->input['rating'] = intval($daddyobb->input['rating']);
if($daddyobb->input['rating'] < 1 || $daddyobb->input['rating'] > 5)
{
	error($lang->error_invalidrating);
}
$plugins->run_hooks("ratethread_start");

if($daddyobb->user['uid'] != 0)
{
	$whereclause = "uid='{$daddyobb->user['uid']}'";
}
else
{
	$whereclause = "ipaddress='".$db->escape_string($session->ipaddress)."'";
}
$query = $db->simple_select("threadratings", "*", "{$whereclause} AND tid='{$tid}'");
$ratecheck = $db->fetch_array($query);

if($ratecheck['rid'] || $daddyobb->cookies['daddyobbratethread'][$tid])
{
	error($lang->error_alreadyratedthread);
}
else
{
	$plugins->run_hooks("ratethread_process");

	$db->write_query("
		UPDATE ".TABLE_PREFIX."threads
		SET numratings=numratings+1, totalratings=totalratings+'{$daddyobb->input['rating']}'
		WHERE tid='{$tid}'
	");
	if($daddyobb->user['uid'] != 0)
	{
		$insertarray = array(
			'tid' => $tid,
			'uid' => $daddyobb->user['uid'],
			'rating' => $daddyobb->input['rating'],
			'ipaddress' => $db->escape_string($session->ipaddress)
		);
		$db->insert_query("threadratings", $insertarray);
	}
	else
	{
		$insertarray = array(
			'tid' => $tid,
			'rating' => $daddyobb->input['rating'],
			'ipaddress' => $db->escape_string($session->ipaddress)
		);
		$db->insert_query("threadratings", $insertarray);
		$time = TIME_NOW;
		my_setcookie("daddyobbratethread[{$tid}]", $daddyobb->input['rating']);
	}
}
$plugins->run_hooks("ratethread_end");

if($daddyobb->input['ajax'])
{
	echo "<success>{$lang->rating_added}</success>\n";
	$query = $db->simple_select("threads", "totalratings, numratings", "tid='$tid'", array('limit' => 1));
	$fetch = $db->fetch_array($query);
	$width = 0;
	if($fetch['numratings'] >= 0)
	{
		$averagerating = intval(round($fetch['totalratings']/$fetch['numratings'], 2));
		$width = $averagerating*20;
		$fetch['numratings'] = intval($fetch['numratings']);
		$ratingvotesav = $lang->sprintf($lang->rating_votes_average, $fetch['numratings'], $averagerating);
		echo "<average>{$ratingvotesav}</average>\n";
	}
	echo "<width>{$width}</width>";
	exit;
}

redirect(get_thread_link($thread['tid']), $lang->redirect_threadrated);
?>