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
define('THIS_SCRIPT', 'attachment.php');

require_once "./global.php";

// Find the AID we're looking for
if($daddyobb->input['thumbnail'])
{
	$aid = intval($daddyobb->input['thumbnail']);
}
else
{
	$aid = intval($daddyobb->input['aid']);
}

$plugins->run_hooks("attachment_start");

$pid = intval($daddyobb->input['pid']);

// Select attachment data from database
if($aid)
{
	$query = $db->simple_select("attachments", "*", "aid='{$aid}'");
}
else
{
	$query = $db->simple_select("attachments", "*", "pid='{$pid}'");
}
$attachment = $db->fetch_array($query);
$pid = $attachment['pid'];

$post = get_post($pid);
$thread = get_thread($post['tid']);

if(!$thread['tid'] && !$daddyobb->input['thumbnail'])
{
	error($lang->error_invalidthread);
}
$fid = $thread['fid'];

// Get forum info
$forum = get_forum($fid);

// Permissions
$forumpermissions = forum_permissions($fid);

if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0 || ($forumpermissions['candlattachments'] == 0 && !$daddyobb->input['thumbnail']))
{
	error_no_permission();
}

// Error if attachment is invalid or not visible
if(!$attachment['aid'] || !$attachment['attachname'] || (!is_moderator($fid) && $attachment['visible'] != 1))
{
	error($lang->error_invalidattachment);
}

if(!$daddyobb->input['thumbnail']) // Only increment the download count if this is not a thumbnail
{
	$attachupdate = array(
		"downloads" => $attachment['downloads']+1,
	);
	$db->update_query("attachments", $attachupdate, "aid='{$attachment['aid']}'");
}

// basename isn't UTF-8 safe. This is a workaround.
$attachment['filename'] = ltrim(basename(' '.$attachment['filename']));

$plugins->run_hooks("attachment_end");

if($daddyobb->input['thumbnail'])
{
	$ext = get_extension($attachment['thumbnail']);
	switch($ext)
	{
		case "gif":
			$type = "image/gif";
			break;
		case "bmp":
			$type = "image/bmp";
			break;
		case "png":
			$type = "image/png";
			break;
		case "jpg":
		case "jpeg":
		case "jpe":
			$type = "image/jpeg";
			break;
		default:
			$type = "image/unknown";
			break;
	}
	
	header("Content-disposition: filename=\"{$attachment['filename']}\"");
	header("Content-type: ".$type);
	$thumb = $daddyobb->settings['uploadspath']."/".$attachment['thumbnail'];
	header("Content-length: ".@filesize($thumb));
	echo file_get_contents($thumb);
}
else
{
	$ext = get_extension($attachment['filename']);
	
	if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "msie") !== false)
	{
		header("Content-disposition: attachment; filename=\"{$attachment['filename']}\"");
	}
	else
	{
		header("Content-disposition: inline; filename=\"{$attachment['filename']}\"");
	}
	
	header("Content-type: {$attachment['filetype']}");
	header("Content-length: {$attachment['filesize']}");
	header("Content-range: bytes=0-".($attachment['filesize']-1)."/".$attachment['filesize']); 
	echo file_get_contents($daddyobb->settings['uploadspath']."/".$attachment['attachname']);
}
?>