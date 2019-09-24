<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:08 19.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'sendthread.php');

$templatelist = "sendthread";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("sendthread");

// Get thread info
$tid = intval($daddyobb->input['tid']);
$thread = get_thread($tid);
$thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));

// Invalid thread
if(!$thread['tid'])
{
	error($lang->error_invalidthread);
}

// Guests cannot use this feature
if(!$daddyobb->user['uid'])
{
	error_no_permission();
}
$fid = $thread['fid'];


// Make navigation
build_forum_breadcrumb($thread['fid']);
add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
add_breadcrumb($lang->nav_sendthread);

// Get forum info
$forum = get_forum($thread['fid']);
$forumpermissions = forum_permissions($forum['fid']);

// Invalid forum?
if(!$forum['fid'] || $forum['type'] != "f")
{
	error($lang->error_invalidforum);
}

// This user can't view this forum or this thread
if($forumpermissions['canview'] != 1 || $forumpermissions['canviewthreads'] != 1)
{
	error_no_permission();
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

if($daddyobb->usergroup['cansendemail'] == 0)
{
	error_no_permission();
}

// Check group limits
if($daddyobb->usergroup['maxemails'] > 0)
{
	$query = $db->simple_select("maillogs", "COUNT(*) AS sent_count", "fromuid='{$daddyobb->user['uid']}' AND dateline >= '".(TIME_NOW - (60*60*24))."'");
	$sent_count = $db->fetch_field($query, "sent_count");
	if($sent_count > $daddyobb->usergroup['maxemails'])
	{
		$lang->error_max_emails_day = $lang->sprintf($lang->error_max_emails_day, $daddyobb->usergroup['maxemails']);
		error($lang->error_max_emails_day);
	}
}

if($daddyobb->input['action'] == "do_sendtofriend" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("sendthread_do_sendtofriend_start");
	
	if(!validate_email_format($daddyobb->input['email']))
	{
		$errors[] = $lang->error_invalidemail;
	}
	
	if(empty($daddyobb->input['subject']))
	{
		$errors[] = $lang->error_nosubject;
	}	
	
	if(empty($daddyobb->input['message']))
	{
		$errors[] = $lang->error_nomessage;
	}

	// No errors detected
	if(count($errors) == 0)
	{
		if($daddyobb->settings['mail_handler'] == 'smtp')
		{
			$from = $daddyobb->user['email'];
		}
		else
		{
			$from = "{$daddyobb->user['username']} <{$daddyobb->user['email']}>";
		}
		
		$threadlink = get_thread_link($thread['tid']);
		
		$message = $lang->sprintf($lang->email_sendtofriend, $daddyobb->user['username'], $daddyobb->settings['bbname'], $daddyobb->settings['bburl']."/".$threadlink, $daddyobb->input['message']);
		
		// Send the actual message
		my_mail($daddyobb->input['email'], $daddyobb->input['subject'], $message, $from, "", "", false, "text", "", $daddyobb->user['email']);
		
		if($daddyobb->settings['mail_logging'] > 0)
		{
			// Log the message
			$log_entry = array(
				"subject" => $db->escape_string($daddyobb->input['subject']),
				"message" => $db->escape_string($message),
				"dateline" => TIME_NOW,
				"fromuid" => $daddyobb->user['uid'],
				"fromemail" => $db->escape_string($daddyobb->user['email']),
				"touid" => 0,
				"toemail" => $db->escape_string($daddyobb->input['email']),
				"tid" => $thread['tid'],
				"ipaddress" => $db->escape_string($session->ipaddress)
			);
			$db->insert_query("maillogs", $log_entry);
		}

		$plugins->run_hooks("sendthread_do_sendtofriend_end");
		redirect(get_thread_link($thread['tid']), $lang->redirect_emailsent);
	}
	else
	{
		$daddyobb->input['action'] = '';
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("sendthread_start");

	// Do we have some errors?
	if(count($errors) >= 1)
	{
		$errors = inline_error($errors);
		$email = htmlspecialchars_uni($daddyobb->input['email']);
		$subject = htmlspecialchars_uni($daddyobb->input['subject']);
		$message = htmlspecialchars_uni($daddyobb->input['message']);
	}
	else
	{
		$errors = '';
		$email = '';
		$subject = $lang->sprintf($lang->emailsubject_sendtofriend, $daddyobb->settings['bbname']);
		$message = '';
	}

	eval("\$sendtofriend = \"".$templates->get("sendthread")."\";");
	$plugins->run_hooks("sendthread_end");
	output_page($sendtofriend);
}
?>