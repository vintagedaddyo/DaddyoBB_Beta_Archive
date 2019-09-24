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
define('THIS_SCRIPT', 'newreply.php');

$templatelist = "newreply,previewpost,error_invalidforum,error_invalidthread,redirect_threadposted,loginbox,changeuserbox,posticons,newreply_threadreview,forumrules,attachments,newreply_threadreview_post";
$templatelist .= ",smilieinsert,codebuttons,post_attachments_new,post_attachments,post_savedraftbutton,newreply_modoptions,newreply_threadreview_more,newreply_disablesmilies,postbit_online,postbit_find,postbit_pm,postbit_www,postbit_email,postbit_reputation,postbit_warninglevel,postbit_author_user,postbit_edit,postbit_quickdelete,postbit_inlinecheck,postbit_posturl,postbit_quote,postbit_multiquote,postbit_report,postbit_seperator,postbit,post_subscription_method";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("posting");

// Get the pid and tid and replyto from the input.
$pid = $replyto = $daddyobb->input['pid'];
$tid = $daddyobb->input['tid'];
if(isset($daddyobb->input['replyto']))
{
	$replyto = intval($daddyobb->input['replyto']);	
}

// AJAX quick reply?
if($daddyobb->input['ajax'])
{
	unset($daddyobb->input['previewpost']);
}

// Edit a draft post.
$draft_pid = 0;
$editdraftpid = '';
if($daddyobb->input['action'] == "editdraft" && $pid)
{
	$options = array(
		"limit" => 1
	);
	$query = $db->simple_select("posts", "*", "pid='".$pid."'", $options);
	$post = $db->fetch_array($query);
	if(!$post['pid'])
	{
		error($lang->error_invalidpost);
	}
	$draft_pid = $post['pid'];
	$tid = $post['tid'];
	$editdraftpid = "<input type=\"hidden\" name=\"pid\" value=\"$draft_pid\" />";
}

// Set up $thread and $forum for later use.
$options = array(
	"limit" => 1
);
$query = $db->simple_select("threads", "*", "tid='".$tid."'");
$thread = $db->fetch_array($query);
$fid = $thread['fid'];

// Get forum info
$forum = get_forum($fid);
if(!$forum)
{
	error($lang->error_invalidforum);
}

// Make navigation
build_forum_breadcrumb($fid);
$thread['subject'] = htmlspecialchars_uni($thread['subject']);
add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
add_breadcrumb($lang->nav_newreply);

$forumpermissions = forum_permissions($fid);

// See if everything is valid up to here.
if(isset($post) && (($post['visible'] == 0 && !is_moderator($fid)) || $post['visible'] == 0))
{
	error($lang->error_invalidpost);
}
if(!$thread['subject'] || (($thread['visible'] == 0 && !is_moderator($fid)) || $thread['visible'] < 0))
{
	error($lang->error_invalidthread);
}
if($forum['open'] == 0 || $forum['type'] != "f")
{
	error($lang->error_closedinvalidforum);
}
if($forumpermissions['canview'] == 0 || $forumpermissions['canpostreplys'] == 0 || $daddyobb->user['suspendposting'] == 1)
{
	error_no_permission();
}

// Coming from quick reply? Set some defaults
if($daddyobb->input['method'] == "quickreply")
{
	if($daddyobb->user['subscriptionmethod'] == 1)
	{
		$daddyobb->input['postoptions']['subscriptionmethod'] = "none";
	}
	else if($daddyobb->user['subscriptionmethod'] == 2)
	{
		$daddyobb->input['postoptions']['subscriptionmethod'] = "instant";
	}
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

if($daddyobb->settings['bbcodeinserter'] != 0 && $forum['allowmycode'] != 0 && (!$daddyobb->user['uid'] || $daddyobb->user['showcodebuttons'] != 0))
{
	$codebuttons = build_mycode_inserter();
	if($forum['allowsmilies'] != 0)
	{
		$smilieinserter = build_clickable_smilies();
	}
}

// Display a login box or change user box?
if($daddyobb->user['uid'] != 0)
{
	eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
}
else
{
	if(!$daddyobb->input['previewpost'] && $daddyobb->input['action'] != "do_newreply")
	{
		$username = $lang->guest;
	}
	elseif($daddyobb->input['previewpost'])
	{
		$username = htmlspecialchars_uni($daddyobb->input['username']);
	}
	eval("\$loginbox = \"".$templates->get("loginbox")."\";");
}

// Check to see if the thread is closed, and if the user is a mod.
if(!is_moderator($fid, "caneditposts"))
{
	if($thread['closed'] == 1)
	{
		error($lang->redirect_threadclosed);
	}
}

// No weird actions allowed, show new reply form if no regular action.
if($daddyobb->input['action'] != "do_newreply" && $daddyobb->input['action'] != "editdraft")
{
	$daddyobb->input['action'] = "newreply";
}

// Even if we are previewing, still show the new reply form.
if($daddyobb->input['previewpost'])
{
	$daddyobb->input['action'] = "newreply";
}

if((empty($_POST) && empty($_FILES)) && $daddyobb->input['processed'] == '1')
{
	error($lang->error_cannot_upload_php_post);
}

if(!$daddyobb->input['attachmentaid'] && ($daddyobb->input['newattachment'] || ($daddyobb->input['action'] == "do_newreply" && $daddyobb->input['submit'] && $_FILES['attachment'])))
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
	if($_FILES['attachment']['size'] > 0 && $forumpermissions['canpostattachments'] != 0 && ($daddyobb->settings['maxattachments'] == 0 ||  $attachcount < $daddyobb->settings['maxattachments']))
	{
		require_once DADDYOBB_ROOT."inc/functions_upload.php";
		$attachedfile = upload_attachment($_FILES['attachment']);
	}
	
	if($attachedfile['error'])
	{
		eval("\$attacherror = \"".$templates->get("error_attacherror")."\";");
		$daddyobb->input['action'] = "newreply";
	}
	
	if(!$daddyobb->input['submit'])
	{
		$daddyobb->input['action'] = "newreply";
	}
}

// Remove an attachment.
if($daddyobb->input['attachmentaid'] && $daddyobb->input['posthash'])
{
	require_once DADDYOBB_ROOT."inc/functions_upload.php";
	remove_attachment(0, $daddyobb->input['posthash'], $daddyobb->input['attachmentaid']);
	if(!$daddyobb->input['submit'])
	{
		$daddyobb->input['action'] = "newreply";
	}
}

// Setup our posthash for managing attachments.
if(!$daddyobb->input['posthash'] && $daddyobb->input['action'] != "editdraft")
{
	mt_srand ((double) microtime() * 1000000);
	$daddyobb->input['posthash'] = md5($thread['tid'].$daddyobb->user['uid'].mt_rand());
}

$reply_errors = "";
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

if($daddyobb->input['action'] == "do_newreply" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("newreply_do_newreply_start");

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
	if(!$daddyobb->input['savedraft'])
	{
		$query = $db->simple_select("posts p", "p.pid", "{$user_check} AND p.tid='{$thread['tid']}' AND p.subject='".$db->escape_string($daddyobb->input['subject'])."' AND p.message='".$db->escape_string($daddyobb->input['message'])."' AND p.posthash='".$db->escape_string($daddyobb->input['posthash'])."'");
		$duplicate_check = $db->fetch_field($query, "pid");
		if($duplicate_check)
		{
			error($lang->error_post_already_submitted);
		}
	}
	
	// Set up posthandler.
	require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");

	// Set the post data that came from the input to the $post array.
	$post = array(
		"tid" => $daddyobb->input['tid'],
		"replyto" => $daddyobb->input['replyto'],
		"fid" => $thread['fid'],
		"subject" => $daddyobb->input['subject'],
		"icon" => $daddyobb->input['icon'],
		"uid" => $uid,
		"username" => $username,
		"message" => $daddyobb->input['message'],
		"ipaddress" => get_ip(),
		"posthash" => $daddyobb->input['posthash']
	);

	if($daddyobb->input['pid'])
	{
		$post['pid'] = $daddyobb->input['pid'];
	}

	// Are we saving a draft post?
	if($daddyobb->input['savedraft'] && $daddyobb->user['uid'])
	{
		$post['savedraft'] = 1;
	}
	else
	{
		$post['savedraft'] = 0;
	}

	// Set up the post options from the input.
	$post['options'] = array(
		"signature" => $daddyobb->input['postoptions']['signature'],
		"subscriptionmethod" => $daddyobb->input['postoptions']['subscriptionmethod'],
		"disablesmilies" => $daddyobb->input['postoptions']['disablesmilies']
	);

	// Apply moderation options if we have them
	$post['modoptions'] = $daddyobb->input['modoptions'];

	$posthandler->set_data($post);

	// Now let the post handler do all the hard work.
	$valid_post = $posthandler->validate_post();

	$post_errors = array();
	// Fetch friendly error messages if this is an invalid post
	if(!$valid_post)
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
		
		// if we're using AJAX, and we have a captcha, regenerate a new one
		if($daddyobb->input['ajax'])
		{
			$randomstr = random_str(5);
			$imagehash = md5(random_str(12));
			$imagearray = array(
				"imagehash" => $imagehash,
				"imagestring" => $randomstr,
				"dateline" => TIME_NOW
			);
			$db->insert_query("captcha", $imagearray);
			header("Content-type: text/html; charset={$lang->settings['charset']}");
			echo "<captcha>$imagehash";
			if($hide_captcha)
			{
				echo "|$randomstr";
			}
			echo "</captcha>";
		}
	}

	// One or more errors returned, fetch error list and throw to newreply page
	if(count($post_errors) > 0)
	{
		$reply_errors = inline_error($post_errors);
		$daddyobb->input['action'] = "newreply";
	}
	else
	{
		$postinfo = $posthandler->insert_post();
		$pid = $postinfo['pid'];
		$visible = $postinfo['visible'];

		// Deciding the fate
		if($visible == -2)
		{
			// Draft post
			$lang->redirect_newreply = $lang->draft_saved;
			$url = "usercp.php?action=drafts";
		}
		elseif($visible == 1)
		{
			// Visible post
			$lang->redirect_newreply .= $lang->redirect_newreply_post;
			$url = get_post_link($pid, $tid)."#pid{$pid}";
		}
		else
		{
			// Moderated post
			$lang->redirect_newreply .= '<br />'.$lang->redirect_newreply_moderation;
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
			// Only quoted a few - attempt to remove them from the cookie
			else
			{
				$quoted_ids = explode("|", $daddyobb->input['quoted_ids']);
				$multiquote = explode("|", $daddyobb->cookies['multiquote']);
				if(is_array($multiquote) && is_array($quoted_ids))
				{
					foreach($multiquote as $key => $quoteid)
					{
						// If this ID was quoted, remove it from the multiquote list
						if(in_array($quoteid, $quoted_ids))
						{
							unset($multiquote[$key]);
						}
					}
					// Still have an array - set the new cookie
					if(is_array($multiquote))
					{
						$new_multiquote = implode(",", $multiquote);
						my_setcookie("multiquote", $new_multiquote);
					}
					// Otherwise, unset it
					else
					{
						my_unsetcookie("multiquote");
					}
				}
			}
		}

		// Mark thread as read
		require_once DADDYOBB_ROOT."inc/functions_indicators.php";
		mark_thread_read($tid, $fid);

		$plugins->run_hooks("newreply_do_newreply_end");
		
		// This was a post made via the ajax quick reply - we need to do some special things here
		if($daddyobb->input['ajax'])
		{
			// Visible post
			if($visible == 1)
			{
				// Set post counter
				$postcounter = $thread['replies'] + 1;

				// Was there a new post since we hit the quick reply button?
				if($daddyobb->input['lastpid'])
				{
					$query = $db->simple_select("posts", "pid", "tid = '{$tid}' AND pid != '{$pid}'", array("order_by" => "pid", "order_dir" => "desc"));
					$new_post = $db->fetch_array($query);
					if($new_post['pid'] != $daddyobb->input['lastpid'])
					{
						redirect(get_thread_link($tid, 0, "lastpost"));
					}
				}

				// Lets see if this post is on the same page as the one we're viewing or not
				// if it isn't, redirect us
				if($perpage > 0 && (($postcounter) % $perpage) == 0)
				{
					$post_page = ($postcounter) / $daddyobb->settings['postsperpage'];
				}
				else
				{
					$post_page = intval(($postcounter) / $daddyobb->settings['postsperpage']) + 1;
				}

				if($daddyobb->input['from_page'] && $post_page > $daddyobb->input['from_page'])
				{
					redirect(get_thread_link($tid, 0, "lastpost"));
					exit;
				}

				// Return the post HTML and display it inline
				$query = $db->query("
					SELECT u.*, u.username AS userusername, p.*, f.*, eu.username AS editusername
					FROM ".TABLE_PREFIX."posts p
					LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
					LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
					LEFT JOIN ".TABLE_PREFIX."users eu ON (eu.uid=p.edituid)
					WHERE p.pid='{$pid}'
				");
				$post = $db->fetch_array($query);

				// Now lets fetch all of the attachments for this post
				$query = $db->simple_select("attachments", "*", "pid='{$pid}'");
				while($attachment = $db->fetch_array($query))
				{
					$attachcache[$attachment['pid']][$attachment['aid']] = $attachment;
				}

				// Is the currently logged in user a moderator of this forum?
				if(is_moderator($fid))
				{
					$ismod = true;
				}
				else
				{
					$ismod = false;
				}

				// Establish altbg - may seem like this is backwards, but build_postbit reverses it
				if(($postcounter - $daddyobb->settings['postsperpage']) % 2 != 0)
				{
					$altbg = "trow1";
				}
				else
				{
					$altbg = "trow2";
				}

				require_once DADDYOBB_ROOT."inc/functions_post.php";
				$pid = $post['pid'];
				$post = build_postbit($post);
				echo $post;

				// Build a new posthash incase the user wishes to quick reply again
				mt_srand((double) microtime() * 1000000);
			    $new_posthash = md5($daddyobb->user['uid'].mt_rand());
				echo "<script type=\"text/javascript\">\n"; 
				echo "var hash = document.getElementById('posthash'); if(hash) { hash.value = '{$new_posthash}'; }\n";
				echo "if(typeof(inlineModeration) != 'undefined') { Event.observe($('inlinemod_{$pid}'), 'click', inlineModeration.checkItem); }\n";
				echo "</script>\n"; 
				exit;				
			}
			// Post is in the moderation queue
			else
			{
				redirect(get_thread_link($tid, 0, "lastpost"), $lang->redirect_newreply_moderation);
				exit;
			}
		}
		else
		{
			$lang->redirect_newreply .= $lang->sprintf($lang->redirect_return_forum, get_forum_link($fid)); 
			redirect($url, $lang->redirect_newreply); 
			exit;
		}
	}
}

// Show the newreply form.
if($daddyobb->input['action'] == "newreply" || $daddyobb->input['action'] == "editdraft")
{
	$plugins->run_hooks("newreply_start");

	$quote_ids = '';
	// If this isn't a preview and we're not editing a draft, then handle quoted posts
	if(!$daddyobb->input['previewpost'] && !$reply_errors && $daddyobb->input['action'] != "editdraft")
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
		// Handle incoming 'quote' button
		if($daddyobb->input['pid'])
		{
			$quoted_posts[$daddyobb->input['pid']] = $daddyobb->input['pid'];
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
			$query = $db->query("
				SELECT p.subject, p.message, p.pid, p.tid, p.username, p.dateline, u.username AS userusername
				FROM ".TABLE_PREFIX."posts p
				LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
				WHERE p.pid IN ($quoted_posts) {$unviewable_forums} {$visible_where}
			");
			$load_all = intval($daddyobb->input['load_all_quotes']);
			while($quoted_post = $db->fetch_array($query))
			{
				// Only show messages for the current thread
				if($quoted_post['tid'] == $tid || $load_all == 1)
				{
					// If this post was the post for which a quote button was clicked, set the subject
					if($pid == $quoted_post['pid'])
					{
						$subject = preg_replace('#RE:\s?#i', '', $quoted_post['subject']);
						$subject = "RE: ".$subject;
					}
					if($quoted_post['userusername'])
					{
						$quoted_post['username'] = $quoted_post['userusername'];
					}
					$quoted_post['message'] = preg_replace('#(^|\r|\n)/me ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} \\2", $quoted_post['message']);
					$quoted_post['message'] = preg_replace('#(^|\r|\n)/slap ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} {$lang->slaps} \\2 {$lang->with_trout}", $quoted_post['message']);
					$quoted_post['message'] = preg_replace("#\[attachment=([0-9]+?)\]#i", '', $quoted_post['message']);
					$quoted_post['message'] = $parser->parse_badwords($quoted_post['message']);
					$message .= "[quote='{$quoted_post['username']}' pid='{$quoted_post['pid']}' dateline='{$quoted_post['dateline']}']\n{$quoted_post['message']}\n[/quote]\n\n";
					$quoted_ids[] = $quoted_post['pid'];
				}
				// Count the rest
				else
				{
					++$external_quotes;
				}
			}
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
				eval("\$multiquote_external = \"".$templates->get("newreply_multiquote_external")."\";");
			}
			if(count($quoted_ids) > 0)
			{
				$quoted_ids = implode("|", $quoted_ids);
			}
		}
	}

	if($daddyobb->input['quoted_ids'])
	{
		$quoted_ids = htmlspecialchars_uni($daddyobb->input['quoted_ids']);
	}

	if($daddyobb->input['previewpost'])
	{
		$previewmessage = $daddyobb->input['message'];
	}
	if(!$message)
	{
		$message = $daddyobb->input['message'];
	}
	$message = htmlspecialchars_uni($message);

	// Set up the post options.
	if($daddyobb->input['previewpost'] || $maximageserror || $reply_errors != '')
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
		$subject = $daddyobb->input['subject'];
	}
	elseif($daddyobb->input['action'] == "editdraft" && $daddyobb->user['uid'])
	{
		$message = htmlspecialchars_uni($post['message']);
		$subject = $post['subject'];
		if($post['includesig'] != 0)
		{
			$postoptionschecked['signature'] = " checked=\"checked\"";
		}
		if($post['smilieoff'] == 1)
		{
			$postoptionschecked['disablesmilies'] = " checked=\"checked\"";
		}
		$daddyobb->input['icon'] = $post['icon'];
	}
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
	}

	if($forum['allowpicons'] != 0)
	{
		$posticons = get_post_icons();
	}

	// Preview a post that was written.
	if($daddyobb->input['previewpost'])
	{
		// Set up posthandler.
		require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
		$posthandler = new PostDataHandler("insert");
	
		// Set the post data that came from the input to the $post array.
		$post = array(
			"tid" => $daddyobb->input['tid'],
			"replyto" => $daddyobb->input['replyto'],
			"fid" => $thread['fid'],
			"subject" => $daddyobb->input['subject'],
			"icon" => $daddyobb->input['icon'],
			"uid" => $uid,
			"username" => $username,
			"message" => $daddyobb->input['message'],
			"ipaddress" => get_ip(),
			"posthash" => $daddyobb->input['posthash']
		);
	
		if($daddyobb->input['pid'])
		{
			$post['pid'] = $daddyobb->input['pid'];
		}
		
		$posthandler->set_data($post);

		// Now let the post handler do all the hard work.
		$valid_post = $posthandler->verify_message();
		$valid_subject = $posthandler->verify_subject();
	
		$post_errors = array();
		// Fetch friendly error messages if this is an invalid post
		if(!$valid_post || !$valid_subject)
		{
			$post_errors = $posthandler->get_friendly_errors();
		}
		
		// One or more errors returned, fetch error list and throw to newreply page
		if(count($post_errors) > 0)
		{
			$reply_errors = inline_error($post_errors);
		}
		else
		{		
			$quote_ids = htmlspecialchars_uni($daddyobb->input['quote_ids']);
			if(!$daddyobb->input['username'])
			{
				$daddyobb->input['username'] = $lang->guest;
			}
			if($daddyobb->input['username'] && !$daddyobb->user['uid'])
			{
				$daddyobb->user = validate_password_from_username($daddyobb->input['username'], $daddyobb->input['password']);
			}
			$daddyobb->input['icon'] = intval($daddyobb->input['icon']);
			$query = $db->query("
				SELECT u.*, f.*
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
				WHERE u.uid='".$daddyobb->user['uid']."'
			");
			$post = $db->fetch_array($query);
			if(!$daddyobb->user['uid'] || !$post['username'])
			{
				$post['username'] = $daddyobb->input['username'];
			}
			else
			{
				$post['userusername'] = $daddyobb->user['username'];
				$post['username'] = $daddyobb->user['username'];
			}
			$post['message'] = $previewmessage;
			$post['subject'] = $subject;
			$post['icon'] = $daddyobb->input['icon'];
			$post['smilieoff'] = $postoptions['disablesmilies'];
			$post['dateline'] = TIME_NOW;
			$post['includesig'] = $daddyobb->input['postoptions']['signature'];
			if($post['includesig'] != 1)
			{
				$post['includesig'] = 0;
			}
	
			// Fetch attachments assigned to this post.
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
	}
	$subject = htmlspecialchars_uni($subject);

	if(!$pid && !$daddyobb->input['previewpost'])
	{
		$subject = "RE: " . $thread['subject'];
	}
	// Setup a unique posthash for attachment management
	if(!$daddyobb->input['posthash'] && $daddyobb->input['action'] != "editdraft")
	{
	    mt_srand ((double) microtime() * 1000000);
	    $posthash = md5($daddyobb->user['uid'].mt_rand());
	}
	else
	{
		$posthash = $daddyobb->input['posthash'];
	}

	// Get a listing of the current attachments.
	if($forumpermissions['canpostattachments'] != 0)
	{
		$attachcount = 0;
		if($daddyobb->input['action'] == "editdraft" && $daddyobb->input['pid'])
		{
			$attachwhere = "pid='$pid'";
		}
		else
		{
			$attachwhere = "posthash='".$db->escape_string($posthash)."'";
		}
		$attachments = '';
		$query = $db->simple_select("attachments", "*", $attachwhere);
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
	}

	// If the user is logged in, provide a save draft button.
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

	if($daddyobb->settings['threadreview'] != 0)
	{
		if(!$daddyobb->settings['postsperpage'])
		{
			$daddyobb->settings['postperpage'] = 20;
		}
		
		if(is_moderator($fid))
		{
			$visibility = "(visible='1' OR visible='0')";
		}
		else
		{
			$visibility = "visible='1'";
		}
		$query = $db->simple_select("posts", "COUNT(pid) AS post_count", "tid='{$tid}' AND {$visibility}");
		$numposts = $db->fetch_field($query, "post_count");

		if($numposts > $daddyobb->settings['postsperpage'])
		{
			$numposts = $daddyobb->settings['postsperpage'];
			$lang->thread_review_more = $lang->sprintf($lang->thread_review_more, $daddyobb->settings['postsperpage'], get_thread_link($tid));
			eval("\$reviewmore = \"".$templates->get("newreply_threadreview_more")."\";");
		}

		$query = $db->simple_select("posts", "pid", "tid='{$tid}' AND {$visibility}", array("order_by" => "dateline", "order_dir" => "desc", "limit" => $daddyobb->settings['postsperpage']));
		while($post = $db->fetch_array($query))
		{
			$pidin[] = $post['pid'];
		}

		$pidin = implode(",", $pidin);

		// Fetch attachments
		$query = $db->simple_select("attachments", "*", "pid IN ($pidin)");
		while($attachment = $db->fetch_array($query))
		{
			$attachcache[$attachment['pid']][$attachment['aid']] = $attachment;
		}
		$query = $db->query("
			SELECT p.*, u.username AS userusername
			FROM ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."users u ON (p.uid=u.uid)
			WHERE pid IN ($pidin)
			ORDER BY dateline DESC
		");
		$postsdone = 0;
		$altbg = "trow1";
		$reviewbits = '';
		while($post = $db->fetch_array($query))
		{
			if($post['userusername'])
			{
				$post['username'] = $post['userusername'];
			}
			$reviewpostdate = my_date($daddyobb->settings['dateformat'], $post['dateline']);
			$reviewposttime = my_date($daddyobb->settings['timeformat'], $post['dateline']);
			$parser_options = array(
				"allow_html" => $forum['allowhtml'],
				"allow_mycode" => $forum['allowmycode'],
				"allow_smilies" => $forum['allowsmilies'],
				"allow_imgcode" => $forum['allowimgcode'],
				"me_username" => $post['username'],
				"filter_badwords" => 1
			);
			if($post['smilieoff'] == 1)
			{
				$parser_options['allow_smilies'] = 0;
			}

			if($post['visible'] != 1)
			{
				$altbg = "trow_shaded";
			}

			$post['message'] = $parser->parse_message($post['message'], $parser_options);
			get_post_attachments($post['pid'], $post);
			$reviewmessage = $post['message'];
			eval("\$reviewbits .= \"".$templates->get("newreply_threadreview_post")."\";");
			if($altbg == "trow1")
			{
				$altbg = "trow2";
			}
			else
			{
				$altbg = "trow1";
			}
		}
		eval("\$threadreview = \"".$templates->get("newreply_threadreview")."\";");
	}
	// Can we disable smilies or are they disabled already?
	if($forum['allowsmilies'] != 0)
	{
		eval("\$disablesmilies = \"".$templates->get("newreply_disablesmilies")."\";");
	}
	else
	{
		$disablesmilies = "<input type=\"hidden\" name=\"postoptions[disablesmilies]\" value=\"no\" />";
	}
	// Show the moderator options.
	if(is_moderator($fid))
	{
		if($daddyobb->input['processed'])
		{
			$closed = intval($daddyobb->input['modoptions']['closethread']);
			$stuck = intval($daddyobb->input['modoptions']['stickthread']);
		}
		else
		{
			$closed = $thread['closed'];
			$stuck = $thread['sticky'];
		}
		
		if($closed)
		{
			$closecheck = ' checked="checked"';
		}
		else
		{
			$closecheck = '';
		}

		if($stuck)
		{
			$stickycheck = ' checked="checked"';
		}
		else
		{
			$stickycheck = '';
		}

		eval("\$modoptions = \"".$templates->get("newreply_modoptions")."\";");
		$bgcolor = "trow1";
	}
	else
	{
		$bgcolor = "trow2";
	}
	
	// Fetch subscription select box
	eval("\$subscriptionmethod = \"".$templates->get("post_subscription_method")."\";");
	
	$lang->post_reply_to = $lang->sprintf($lang->post_reply_to, $thread['subject']);
	$lang->reply_to = $lang->sprintf($lang->reply_to, $thread['subject']);

	$plugins->run_hooks("newreply_end");
	
	$forum['name'] = strip_tags($forum['name']);

	eval("\$newreply = \"".$templates->get("newreply")."\";");
	output_page($newreply);
}
?>