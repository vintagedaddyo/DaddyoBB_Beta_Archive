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
define('THIS_SCRIPT', 'newthread.php');

$templatelist = "newthread,previewpost,error_invalidforum,redirect_newthread,loginbox,changeuserbox,newthread_postpoll,posticons,attachment,newthread_postpoll,codebuttons,smilieinsert,error_nosubject";
$templatelist .= "posticons,newthread_disablesmilies,newreply_modoptions,post_attachments_new,post_attachments,post_savedraftbutton,post_subscription_method";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";

// Load global language phrases
$lang->load("posting");

$tid = $pid = "";
if($daddyobb->input['action'] == "editdraft" || ($daddyobb->input['savedraft'] && $daddyobb->input['tid']) || ($daddyobb->input['tid'] && $daddyobb->input['pid']))
{
	$thread = get_thread($daddyobb->input['tid']);
	
	$query = $db->simple_select("posts", "*", "tid='".intval($daddyobb->input['tid'])."' AND visible='-2'", array('order_by' => 'dateline', 'limit' => 1));
	$post = $db->fetch_array($query);

	if(!$thread['tid'] || !$post['pid'] || $thread['visible'] != -2)
	{
		error($lang->invalidthread);
	}
	
	$pid = $post['pid'];
	$fid = $thread['fid'];
	$tid = $thread['tid'];
	$editdraftpid = "<input type=\"hidden\" name=\"pid\" value=\"$pid\" /><input type=\"hidden\" name=\"tid\" value=\"$tid\" />";
}
else
{
	$fid = intval($daddyobb->input['fid']);
}

// Fetch forum information.
$forum = get_forum($fid);
if(!$forum)
{
	error($lang->error_invalidforum);
}

// Draw the navigation
build_forum_breadcrumb($fid);
add_breadcrumb($lang->nav_newthread);

$forumpermissions = forum_permissions($fid);

if($forum['open'] == 0 || $forum['type'] != "f")
{
	error($lang->error_closedinvalidforum);
}

if($forumpermissions['canview'] == 0 || $forumpermissions['canpostthreads'] == 0 || $daddyobb->user['suspendposting'] == 1)
{
	error_no_permission();
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

// If MyCode is on for this forum and the MyCode editor is enabled in the Admin CP, draw the code buttons and smilie inserter.
if($daddyobb->settings['bbcodeinserter'] != 0 && $forum['allowmycode'] != 0 && (!$daddyobb->user['uid'] || $daddyobb->user['showcodebuttons'] != 0))
{
	$codebuttons = build_mycode_inserter();
	if($forum['allowsmilies'] != 0)
	{
		$smilieinserter = build_clickable_smilies();
	}
}

// Does this forum allow post icons? If so, fetch the post icons.
if($forum['allowpicons'] != 0)
{
	$posticons = get_post_icons();
}

// If we have a currently logged in user then fetch the change user box.
if($daddyobb->user['uid'] != 0)
{
	eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
}

// Otherwise we have a guest, determine the "username" and get the login box.
else
{
	if(!$daddyobb->input['previewpost'] && $daddyobb->input['action'] != "do_newthread")
	{
		$username = $lang->guest;
	}
	else
	{
		$username = htmlspecialchars($daddyobb->input['username']);
	}
	eval("\$loginbox = \"".$templates->get("loginbox")."\";");
}

// If we're not performing a new thread insert and not editing a draft then we're posting a new thread.
if($daddyobb->input['action'] != "do_newthread" && $daddyobb->input['action'] != "editdraft")
{
	$daddyobb->input['action'] = "newthread";
}

// Previewing a post, overwrite the action to the new thread action.
if($daddyobb->input['previewpost'])
{
	$daddyobb->input['action'] = "newthread";
}

if((empty($_POST) && empty($_FILES)) && $daddyobb->input['processed'] == '1')
{
	error($lang->error_cannot_upload_php_post);
}

// Handle attachments if we've got any.
if(!$daddyobb->input['attachmentaid'] && ($daddyobb->input['newattachment'] || ($daddyobb->input['action'] == "do_newthread" && $daddyobb->input['submit'] && $_FILES['attachment'])))
{
	if($daddyobb->input['action'] == "editdraft" || ($daddyobb->input['tid'] && $daddyobb->input['pid']))
	{
		$attachwhere = "pid='{$pid}'";
	}
	else
	{
		$attachwhere = "posthash='".$db->escape_string($daddyobb->input['posthash'])."'";
	}
	$query = $db->simple_select("attachments", "COUNT(aid) as numattachs", $attachwhere);
	$attachcount = $db->fetch_field($query, "numattachs");
	
	// If there's an attachment, check it and upload it
	if($_FILES['attachment']['size'] > 0 && $forumpermissions['canpostattachments'] != 0 && ($daddyobb->settings['maxattachments'] == 0 || $attachcount < $daddyobb->settings['maxattachments']))
	{
		require_once DADDYOBB_ROOT."inc/functions_upload.php";
		$attachedfile = upload_attachment($_FILES['attachment']);
	}
	
	// Error with attachments - should use new inline errors?
	if($attachedfile['error'])
	{
		eval("\$attacherror = \"".$templates->get("error_attacherror")."\";");
		$daddyobb->input['action'] = "newthread";
	}
	
	// If we were dealing with an attachment but didn't click 'Post Thread', force the new thread page again.
	if(!$daddyobb->input['submit'])
	{
		$daddyobb->input['action'] = "newthread";
	}
}

// Are we removing an attachment from the thread?
if($daddyobb->input['attachmentaid'] && $daddyobb->input['posthash'])
{
	require_once DADDYOBB_ROOT."inc/functions_upload.php";
	remove_attachment(0, $daddyobb->input['posthash'], $daddyobb->input['attachmentaid']);
	if(!$daddyobb->input['submit'])
	{
		$daddyobb->input['action'] = "newthread";
	}
}

$thread_errors = "";
$hide_captcha = false;

// Check the maximum posts per day for this user
if($daddyobb->settings['maxposts'] > 0 && $daddyobb->usergroup['cancp'] != 1)
{
	$daycut = TIME_NOW-60*60*24;
	$query = $db->simple_select("posts", "COUNT(*) AS posts_today", "uid='{$daddyobb->user['uid']}' AND visible='1' AND dateline>{$daycut}");
	$post_count = $db->fetch_field($query, "posts_today");
	if($post_count >= $daddyobb->settings['maxposts'])
	{
		$lang->error_maxposts = $lang->sprintf($lang->error_maxposts, $daddyobb->settings['maxposts']);
		error($lang->error_maxposts);
	}
}

// Performing the posting of a new thread.
if($daddyobb->input['action'] == "do_newthread" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("newthread_do_newthread_start");

	// If this isn't a logged in user, then we need to do some special validation.
	if($daddyobb->user['uid'] == 0)
	{
		$username = htmlspecialchars_uni($daddyobb->input['username']);
	
		// Check if username exists.
		if(username_exists($daddyobb->input['username']))
		{
			// If it does and no password is given throw back "username is taken"
			if(!$daddyobb->input['password'])
			{
				error($lang->error_usernametaken);
			}
			
			// Checks to make sure the user can login; they haven't had too many tries at logging in.
			// Is a fatal call if user has had too many tries
			$logins = login_attempt_check();		

			// If the user specified a password but it is wrong, throw back invalid password.
			$daddyobb->user = validate_password_from_username($daddyobb->input['username'], $daddyobb->input['password']);
			if(!$daddyobb->user['uid'])
			{
				my_setcookie('loginattempts', $logins + 1);
				$db->write_query("UPDATE ".TABLE_PREFIX."users SET loginattempts=loginattempts+1 WHERE username = '".$db->escape_string($daddyobb->input['username'])."'");
				if($daddyobb->settings['failedlogintext'] == 1)
				{
					$login_text = $lang->sprintf($lang->failed_login_again, $daddyobb->settings['failedlogincount'] - $logins);
				}				
				error($lang->error_invalidpassword.$login_text);
			}
			// Otherwise they've logged in successfully.

			$daddyobb->input['username'] = $username = $daddyobb->user['username'];
			my_setcookie("daddyobbuser", $daddyobb->user['uid']."_".$daddyobb->user['loginkey'], null, true);
			my_setcookie('loginattempts', 1);
			
			// Update the session to contain their user ID
			$updated_session = array(
				"uid" => $daddyobb->user['uid'],
			);
			$db->update_query("sessions", $updated_session, "sid='{$session->sid}'");
			
			$db->update_query("users", array("loginattempts" => 1), "uid='{$daddyobb->user['uid']}'");
			
			// Set uid and username
			$uid = $daddyobb->user['uid'];
			$username = $daddyobb->user['username'];
			
			// Check if this user is allowed to post here
			$daddyobb->usergroup = &$groupscache[$daddyobb->user['usergroup']];
			$forumpermissions = forum_permissions($fid);
			if($forumpermissions['canview'] == 0 || $forumpermissions['canpostreplys'] == 0 || $daddyobb->user['suspendposting'] == 1)
			{
				error_no_permission();
			}
		}
		// This username does not exist.
		else
		{
			// If they didn't specify a username then give them "Guest"
			if(!$daddyobb->input['username'])
			{
				$username = $lang->guest;
			}
			// Otherwise use the name they specified.
			else
			{
				$username = htmlspecialchars($daddyobb->input['username']);
			}
			$uid = 0;
		}
	}
	// This user is logged in.
	else
	{
		$username = $daddyobb->user['username'];
		$uid = $daddyobb->user['uid'];
	}
	
	// Attempt to see if this post is a duplicate or not
	if($uid > 0)
	{
		$user_check = "p.uid='{$uid}'";
	}
	else
	{
		$user_check = "p.ipaddress='".$db->escape_string($session->ipaddress)."'";
	}
	if(!$daddyobb->input['savedraft'] && !$pid)
	{
		$query = $db->simple_select("posts p", "p.pid", "$user_check AND p.fid='{$forum['fid']}' AND p.subject='".$db->escape_string($daddyobb->input['subject'])."' AND p.message='".$db->escape_string($daddyobb->input['message'])."' AND p.posthash='".$db->escape_string($daddyobb->input['posthash'])."'");
		$duplicate_check = $db->fetch_field($query, "pid");
		if($duplicate_check)
		{
			error($lang->error_post_already_submitted);
		}
	}
	
	// Set up posthandler.
	require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";

	// Set the thread data that came from the input to the $thread array.
	$new_thread = array(
		"fid" => $forum['fid'],
		"subject" => $daddyobb->input['subject'],
		"icon" => $daddyobb->input['icon'],
		"uid" => $uid,
		"username" => $username,
		"message" => $daddyobb->input['message'],
		"ipaddress" => get_ip(),
		"posthash" => $daddyobb->input['posthash']
	);
	
	if($pid != '')
	{
		$new_thread['pid'] = $pid;
	}

	// Are we saving a draft thread?
	if($daddyobb->input['savedraft'] && $daddyobb->user['uid'])
	{
		$new_thread['savedraft'] = 1;
	}
	else
	{
		$new_thread['savedraft'] = 0;
	}
	
	// Is this thread already a draft and we're updating it?
	if(isset($thread['tid']) && $thread['visible'] == -2)
	{
		$new_thread['tid'] = $thread['tid'];
	}

	// Set up the thread options from the input.
	$new_thread['options'] = array(
		"signature" => $daddyobb->input['postoptions']['signature'],
		"subscriptionmethod" => $daddyobb->input['postoptions']['subscriptionmethod'],
		"disablesmilies" => $daddyobb->input['postoptions']['disablesmilies']
	);
	
	// Apply moderation options if we have them
	$new_thread['modoptions'] = $daddyobb->input['modoptions'];

	$posthandler->set_data($new_thread);
	
	// Now let the post handler do all the hard work.
	$valid_thread = $posthandler->validate_thread();
	
	$post_errors = array();
	// Fetch friendly error messages if this is an invalid thread
	if(!$valid_thread)
	{
		$post_errors = $posthandler->get_friendly_errors();
	}
	
	// Check captcha image
	if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagepng") && !$daddyobb->user['uid'])
	{
		$imagehash = $db->escape_string($daddyobb->input['imagehash']);
		$imagestring = $db->escape_string($daddyobb->input['imagestring']);
		$query = $db->simple_select("captcha", "*", "imagehash='$imagehash'"); 
		$imgcheck = $db->fetch_array($query);
		if(my_strtolower($imgcheck['imagestring']) != my_strtolower($imagestring) || !$imgcheck['imagehash'])
		{
			$post_errors[] = $lang->invalid_captcha;
		}
		else
		{
			$db->delete_query("captcha", "imagehash='$imagehash'");
			$hide_captcha = true;
		}
	}

	
	// One or more errors returned, fetch error list and throw to newthread page
	if(count($post_errors) > 0)
	{
		$thread_errors = inline_error($post_errors);
		$daddyobb->input['action'] = "newthread";		
	}
	// No errors were found, it is safe to insert the thread.
	else
	{
		$thread_info = $posthandler->insert_thread();
		$tid = $thread_info['tid'];
		$visible = $thread_info['visible'];

		// Mark thread as read
		require_once DADDYOBB_ROOT."inc/functions_indicators.php";
		mark_thread_read($tid, $fid);
		
		// We were updating a draft thread, send them back to the draft listing.
		if($new_thread['savedraft'] == 1)
		{
			$lang->redirect_newthread = $lang->draft_saved;
			$url = "usercp.php?action=drafts";
		}
		
		// A poll was being posted with this thread, throw them to poll posting page.
		else if($daddyobb->input['postpoll'] && $forumpermissions['canpostpolls'])
		{
			$url = "polls.php?action=newpoll&tid=$tid&polloptions=".intval($daddyobb->input['numpolloptions']);
			$lang->redirect_newthread .= $lang->redirect_newthread_poll;
		}
		
		// This thread is stuck in the moderation queue, send them back to the forum.
		else if(!$visible)
		{
			// Moderated thread
			$lang->redirect_newthread .= $lang->redirect_newthread_moderation;
			$url = get_forum_link($fid);
		}

		// This is just a normal thread - send them to it.
		else
		{
			// Visible thread
			$lang->redirect_newthread .= $lang->redirect_newthread_thread;
			$url = get_thread_link($tid);
		}
		
		// Mark any quoted posts so they're no longer selected - attempts to maintain those which weren't selected
		if($daddyobb->input['quoted_ids'] && $daddyobb->cookies['multiquote'] && $daddyobb->settings['multiquote'] != 0)
		{
			// We quoted all posts - remove the entire cookie
			if($daddyobb->input['quoted_ids'] == "all")
			{
				my_unsetcookie("multiquote");
			}
		}

		$plugins->run_hooks("newthread_do_newthread_end");
		
		// Hop to it! Send them to the next page.
		if(!$daddyobb->input['postpoll'])
		{
			$lang->redirect_newthread .= $lang->sprintf($lang->redirect_return_forum, get_forum_link($fid));
		}
		redirect($url, $lang->redirect_newthread);
	}
}

if($daddyobb->input['action'] == "newthread" || $daddyobb->input['action'] == "editdraft")
{

	$plugins->run_hooks("newthread_start");
	
	$quote_ids = '';
	// If this isn't a preview and we're not editing a draft, then handle quoted posts
	if(!$daddyobb->input['previewpost'] && !$thread_errors && $daddyobb->input['action'] != "editdraft")
	{
		$message = '';
		$quoted_posts = array();
		// Handle multiquote
		if($daddyobb->cookies['multiquote'] && $daddyobb->settings['multiquote'] != 0)
		{
			$multiquoted = explode("|", $daddyobb->cookies['multiquote']);
			foreach($multiquoted as $post)
			{
				$quoted_posts[$post] = intval($post);
			}
		}

		// Quoting more than one post - fetch them
		if(count($quoted_posts) > 0)
		{
			$external_quotes = 0;
			$quoted_posts = implode(",", $quoted_posts);
			$unviewable_forums = get_unviewable_forums();
			if($unviewable_forums)
			{
				$unviewable_forums = "AND t.fid NOT IN ({$unviewable_forums})";
			}
			
			if(is_moderator($fid))
			{
				$visible_where = "AND p.visible != 2";
			}
			else
			{
				$visible_where = "AND p.visible > 0";
			}
			
			if(intval($daddyobb->input['load_all_quotes']) == 1)
			{
				$query = $db->query("
					SELECT p.subject, p.message, p.pid, p.tid, p.username, p.dateline, u.username AS userusername
					FROM ".TABLE_PREFIX."posts p
					LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
					LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
					WHERE p.pid IN ($quoted_posts) {$unviewable_forums} {$visible_where}
				");
				while($quoted_post = $db->fetch_array($query))
				{
					if($quoted_post['userusername'])
					{
						$quoted_post['username'] = $quoted_post['userusername'];
					}
					$quoted_post['message'] = preg_replace('#(^|\r|\n)/me ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} \\2", $quoted_post['message']);
					$quoted_post['message'] = preg_replace('#(^|\r|\n)/slap ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} {$lang->slaps} \\2 {$lang->with_trout}", $quoted_post['message']);
					$quoted_post['message'] = preg_replace("#\[attachment=([0-9]+?)\]#i", '', $quoted_post['message']);
					$message .= "[quote='{$quoted_post['username']}' pid='{$quoted_post['pid']}' dateline='{$quoted_post['dateline']}']\n{$quoted_post['message']}\n[/quote]\n\n";
				}

				$quoted_ids = "all";
			}
			else
			{
				$query = $db->query("
					SELECT COUNT(*) AS quotes
					FROM ".TABLE_PREFIX."posts p
					LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
					WHERE p.pid IN ($quoted_posts) {$unviewable_forums} {$visible_where}
				");
				$external_quotes = $db->fetch_field($query, 'quotes');

				if($external_quotes > 0)
				{
					if($external_quotes == 1)
					{
						$multiquote_text = $lang->multiquote_external_one;
						$multiquote_deselect = $lang->multiquote_external_one_deselect;
						$multiquote_quote = $lang->multiquote_external_one_quote;
					}
					else
					{
						$multiquote_text = $lang->sprintf($lang->multiquote_external, $external_quotes);
						$multiquote_deselect = $lang->multiquote_external_deselect;
						$multiquote_quote = $lang->multiquote_external_quote;
					}
					eval("\$multiquote_external = \"".$templates->get("newthread_multiquote_external")."\";");
				}
			}
		}
	}

	if($daddyobb->input['quoted_ids'])
	{
		$quoted_ids = htmlspecialchars_uni($daddyobb->input['quoted_ids']);
	}

	// Check the various post options if we're
	// a -> previewing a post
	// b -> removing an attachment
	// c -> adding a new attachment
	// d -> have errors from posting
	
	if($daddyobb->input['previewpost'] || $daddyobb->input['attachmentaid'] || $daddyobb->input['newattachment'] || $thread_errors)
	{
		$postoptions = $daddyobb->input['postoptions'];
		if($postoptions['signature'] == 1)
		{
			$postoptionschecked['signature'] = " checked=\"checked\"";
		}
		if($postoptions['subscriptionmethod'] == "none")
		{
			$postoptions_subscriptionmethod_none = "checked=\"checked\"";
		}
		else if($postoptions['subscriptionmethod'] == "instant")
		{
			$postoptions_subscriptionmethod_instant = "checked=\"checked\"";
		}
		else
		{
			$postoptions_subscriptionmethod_dont = "checked=\"checked\"";
		}
		if($postoptions['disablesmilies'] == 1)
		{
			$postoptionschecked['disablesmilies'] = " checked=\"checked\"";
		}
		if($daddyobb->input['postpoll'] == 1)
		{
			$postpollchecked = "checked=\"checked\"";
		}
		$numpolloptions = intval($daddyobb->input['numpolloptions']);
	}
	
	// Editing a draft thread
	else if($daddyobb->input['action'] == "editdraft" && $daddyobb->user['uid'])
	{
		$message = htmlspecialchars_uni($post['message']);
		$subject = htmlspecialchars_uni($post['subject']);
		if($post['includesig'] != 0)
		{
			$postoptionschecked['signature'] = " checked=\"checked\"";
		}
		if($post['smilieoff'] == 1)
		{
			$postoptionschecked['disablesmilies'] = " checked=\"checked\"";
		}
		$icon = $post['icon'];
		$posticons = get_post_icons();
	}
	
	// Otherwise, this is our initial visit to this page.
	else
	{
		if($daddyobb->user['signature'] != '')
		{
			$postoptionschecked['signature'] = " checked=\"checked\"";
		}
		if($daddyobb->user['subscriptionmethod'] ==  1)
		{
			$postoptions_subscriptionmethod_none = "checked=\"checked\"";
		}
		else if($daddyobb->user['subscriptionmethod'] == 2)
		{
			$postoptions_subscriptionmethod_instant = "checked=\"checked\"";
		}
		else
		{
			$postoptions_subscriptionmethod_dont = "checked=\"checked\"";
		}
		$numpolloptions = "2";
	}

	
	// If we're preving a post then generate the preview.
	if($daddyobb->input['previewpost'])
	{
		// Set up posthandler.
		require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
		$posthandler = new PostDataHandler("insert");
		$posthandler->action = "thread";
	
		// Set the thread data that came from the input to the $thread array.
		$new_thread = array(
			"fid" => $forum['fid'],
			"subject" => $daddyobb->input['subject'],
			"icon" => $daddyobb->input['icon'],
			"uid" => $uid,
			"username" => $username,
			"message" => $daddyobb->input['message'],
			"ipaddress" => get_ip(),
			"posthash" => $daddyobb->input['posthash']
		);
		
		if($pid != '')
		{
			$new_thread['pid'] = $pid;
		}
		
		$posthandler->set_data($new_thread);

		// Now let the post handler do all the hard work.
		$valid_thread = $posthandler->verify_message();
		$valid_subject = $posthandler->verify_subject();
	
		$post_errors = array();
		// Fetch friendly error messages if this is an invalid post
		if(!$valid_thread || !$valid_subject)
		{
			$post_errors = $posthandler->get_friendly_errors();
		}
		
		// One or more errors returned, fetch error list and throw to newreply page
		if(count($post_errors) > 0)
		{
			$thread_errors = inline_error($post_errors);
		}
		else
		{		
			if(!$daddyobb->input['username'])
			{
				$daddyobb->input['username'] = $lang->guest;
			}
			if($daddyobb->input['username'] && !$daddyobb->user['uid'])
			{
				$daddyobb->user = validate_password_from_username($daddyobb->input['username'], $daddyobb->input['password']);
			}
			$query = $db->query("
				SELECT u.*, f.*
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
				WHERE u.uid='".$daddyobb->user['uid']."'
			");
			$post = $db->fetch_array($query);
			if(!$daddyobb->user['uid'] || !$post['username'])
			{
				$post['username'] = htmlspecialchars_uni($daddyobb->input['username']);
			}
			else
			{
				$post['userusername'] = $daddyobb->user['username'];
				$post['username'] = $daddyobb->user['username'];
			}
			$previewmessage = $daddyobb->input['message'];
			$post['message'] = $previewmessage;
			$post['subject'] = $daddyobb->input['subject'];
			$post['icon'] = $daddyobb->input['icon'];
			$post['smilieoff'] = $postoptions['disablesmilies'];
			$post['dateline'] = TIME_NOW;
			$post['includesig'] = $daddyobb->input['postoptions']['signature'];
			if($post['includesig'] != 1)
			{
				$post['includesig'] = 0;
			}
	
	
			// Fetch attachments assigned to this post
			if($daddyobb->input['pid'])
			{
				$attachwhere = "pid='".intval($daddyobb->input['pid'])."'";
			}
			else
			{
				$attachwhere = "posthash='".$db->escape_string($daddyobb->input['posthash'])."'";
			}
	
			$query = $db->simple_select("attachments", "*", $attachwhere);
			while($attachment = $db->fetch_array($query)) 
			{
				$attachcache[0][$attachment['aid']] = $attachment;
			}
	
			$postbit = build_postbit($post, 1);
			eval("\$preview = \"".$templates->get("previewpost")."\";");
		}
		$message = htmlspecialchars_uni($daddyobb->input['message']);
		$subject = htmlspecialchars_uni($daddyobb->input['subject']);
	}
	
	// Removing an attachment or adding a new one, or showting thread errors.
	else if($daddyobb->input['attachmentaid'] || $daddyobb->input['newattachment'] || $thread_errors) 
	{
		$message = htmlspecialchars_uni($daddyobb->input['message']);
		$subject = htmlspecialchars_uni($daddyobb->input['subject']);
	}

	// Setup a unique posthash for attachment management
	if(!$daddyobb->input['posthash'] && $daddyobb->input['action'] != "editdraft")
	{
	    mt_srand((double) microtime() * 1000000);
	    $posthash = md5($daddyobb->user['uid'].mt_rand());
	}
	else
	{
		$posthash = htmlspecialchars($daddyobb->input['posthash']);
	}

	// Can we disable smilies or are they disabled already?
	if($forum['allowsmilies'] != 0)
	{
		eval("\$disablesmilies = \"".$templates->get("newthread_disablesmilies")."\";");
	}
	else
	{
		$disablesmilies = "<input type=\"hidden\" name=\"postoptions[disablesmilies]\" value=\"no\" />";
	}

	// Show the moderator options
	if(is_moderator($fid))
	{
		$modoptions = $daddyobb->input['modoptions'];
		if($modoptions['closethread'] == 1)
		{
			$closecheck = "checked=\"checked\"";
		}
		else
		{
			$closecheck = '';
		}
		if($modoptions['stickthread'] == 1)
		{
			$stickycheck = "checked=\"checked\"";
		}
		else
		{
			$stickycheck = '';
		}
		unset($modoptions);
		eval("\$modoptions = \"".$templates->get("newreply_modoptions")."\";");
		$bgcolor = "trow1";
		$bgcolor2 = "trow2";
	}
	else
	{
		$bgcolor = "trow2";
		$bgcolor2 = "trow1";
	}

	// Fetch subscription select box
	eval("\$subscriptionmethod = \"".$templates->get("post_subscription_method")."\";");

	if($forumpermissions['canpostattachments'] != 0)
	{ // Get a listing of the current attachments, if there are any
		$attachcount = 0;
		if($daddyobb->input['action'] == "editdraft" || ($daddyobb->input['tid'] && $daddyobb->input['pid']))
		{
			$attachwhere = "pid='$pid'";
		}
		else
		{
			$attachwhere = "posthash='".$db->escape_string($posthash)."'";
		}
		$query = $db->simple_select("attachments", "*", $attachwhere);
		$attachments = '';
		while($attachment = $db->fetch_array($query))
		{
			$attachment['size'] = get_friendly_size($attachment['filesize']);
			$attachment['icon'] = get_attachment_icon(get_extension($attachment['filename']));
			if($daddyobb->settings['bbcodeinserter'] != 0 && $forum['allowmycode'] != 0 && (!$daddyobb->user['uid'] || $daddyobb->user['showcodebuttons'] != 0))
			{
				eval("\$postinsert = \"".$templates->get("post_attachments_attachment_postinsert")."\";");
			}
			$attach_mod_options = '';
			if($attachment['visible'] != 1)
			{
				eval("\$attachments .= \"".$templates->get("post_attachments_attachment_unapproved")."\";");
			}
			else
			{
				eval("\$attachments .= \"".$templates->get("post_attachments_attachment")."\";");
			}
			$attachcount++;
		}
		$query = $db->simple_select("attachments", "SUM(filesize) AS ausage", "uid='".$daddyobb->user['uid']."'");
		$usage = $db->fetch_array($query);
		if($usage['ausage'] > ($daddyobb->usergroup['attachquota']*1024) && $daddyobb->usergroup['attachquota'] != 0)
		{
			$noshowattach = 1;
		}
		if($daddyobb->usergroup['attachquota'] == 0)
		{
			$friendlyquota = $lang->unlimited;
		}
		else
		{
			$friendlyquota = get_friendly_size($daddyobb->usergroup['attachquota']*1024);
		}
		$friendlyusage = get_friendly_size($usage['ausage']);
		$lang->attach_quota = $lang->sprintf($lang->attach_quota, $friendlyusage, $friendlyquota);
		if($daddyobb->settings['maxattachments'] == 0 || ($daddyobb->settings['maxattachments'] != 0 && $attachcount < $daddyobb->settings['maxattachments']) && !$noshowattach)
		{
			eval("\$newattach = \"".$templates->get("post_attachments_new")."\";");
		}
		eval("\$attachbox = \"".$templates->get("post_attachments")."\";");

		$bgcolor = alt_trow();
	}

	if($daddyobb->user['uid'])
	{
		eval("\$savedraftbutton = \"".$templates->get("post_savedraftbutton", 1, 0)."\";");
	}
	
	// Show captcha image for guests if enabled
	if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagepng") && !$daddyobb->user['uid'])
	{
		$correct = false;
		// If previewing a post - check their current captcha input - if correct, hide the captcha input area
		if($daddyobb->input['previewpost'] || $hide_captcha == true)
		{
			$imagehash = $db->escape_string($daddyobb->input['imagehash']);
			$imagestring = $db->escape_string($daddyobb->input['imagestring']);
			$query = $db->simple_select("captcha", "*", "imagehash='$imagehash' AND imagestring='$imagestring'");
			$imgcheck = $db->fetch_array($query);
			if($imgcheck['dateline'] > 0)
			{
				eval("\$captcha = \"".$templates->get("post_captcha_hidden")."\";");			
				$correct = true;
			}
			else
			{
				$db->delete_query("captcha", "imagehash='$imagehash'");
			}
		}
		if(!$correct)
		{	
			$randomstr = random_str(5);
			$imagehash = md5(random_str(12));
			$imagearray = array(
				"imagehash" => $imagehash,
				"imagestring" => $randomstr,
				"dateline" => TIME_NOW
			);
			$db->insert_query("captcha", $imagearray);
			eval("\$captcha = \"".$templates->get("post_captcha")."\";");			
		}
	}
	
	if($forumpermissions['canpostpolls'] != 0)
	{
		$lang->max_options = $lang->sprintf($lang->max_options, $daddyobb->settings['maxpolloptions']);
		eval("\$pollbox = \"".$templates->get("newthread_postpoll")."\";");
	}

	$plugins->run_hooks("newthread_end");
	
	$forum['name'] = strip_tags($forum['name']);
	$lang->newthread_in = $lang->sprintf($lang->newthread_in, $forum['name']);
	
	eval("\$newthread = \"".$templates->get("newthread")."\";");
	output_page($newthread);

}
?>