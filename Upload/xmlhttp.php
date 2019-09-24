<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:09 19.12.2008
 */

/**
 * The deal with this file is that it handles all of the XML HTTP Requests for DaddyoBB.
 *
 * It contains a stripped down version of the DaddyoBB core which does not load things
 * such as themes, who's online data, all of the language packs and more.
 *
 * This is done to make response times when using XML HTTP Requests faster and
 * less intense on the server.
 */
 
define("IN_DADDYOBB", 1);

// We don't want visits here showing up on the Who's Online
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'xmlhttp.php');

// Load DaddyoBB core files
require_once dirname(__FILE__)."/inc/init.php";

$shutdown_queries = array();

// Load some of the stock caches we'll be using.
$groupscache = $cache->read("usergroups");

if(!is_array($groupscache))
{
	$cache->update_usergroups();
	$groupscache = $cache->read("usergroups");
}

// Send no cache headers
header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// Create the session
require_once DADDYOBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();

// Load the language we'll be using
if(!isset($daddyobb->settings['bblanguage']))
{
	$daddyobb->settings['bblanguage'] = "english";
}
if(isset($daddyobb->user['language']) && $lang->language_exists($daddyobb->user['language']))
{
	$daddyobb->settings['bblanguage'] = $daddyobb->user['language'];
}
$lang->set_language($daddyobb->settings['bblanguage']);

if(function_exists('mb_internal_encoding') && !empty($lang->settings['charset']))
{
	@mb_internal_encoding($lang->settings['charset']);
}

// Load the language pack for this file.
if(isset($daddyobb->user['style']) && intval($daddyobb->user['style']) != 0)
{
	$loadstyle = "tid='".$daddyobb->user['style']."'";
}
else
{
	$loadstyle = "def=1";
}

// Load basic theme information that we could be needing.
$query = $db->simple_select("themes", "name, tid, properties", $loadstyle);
$theme = $db->fetch_array($query);
$theme = @array_merge($theme, unserialize($theme['properties']));

// Set the appropriate image language directory for this theme.
if(!empty($daddyobb->user['language']) && is_dir($theme['imgdir'].'/'.$daddyobb->user['language']))
{
	$theme['imglangdir'] = $theme['imgdir'].'/'.$daddyobb->user['language'];
}
else
{
	if(is_dir($theme['imgdir'].'/'.$daddyobb->settings['bblanguage']))
	{
		$theme['imglangdir'] = $theme['imgdir'].'/'.$daddyobb->settings['bblanguage'];
	}
	else
	{
		$theme['imglangdir'] = $theme['imgdir'];
	}
}

if($lang->settings['charset'])
{
	$charset = $lang->settings['charset'];
}
// If not, revert to UTF-8
else
{
	$charset = "UTF-8";
}

$lang->load("global");
$lang->load("xmlhttp");

$plugins->run_hooks("xmlhttp");

// Fetch a list of usernames beginning with a certain string (used for auto completion)
if($daddyobb->input['action'] == "get_users")
{
	// If the string is less than 3 characters, quit.
	if(my_strlen($daddyobb->input['query']) < 3)
	{
		exit;
	}
	
	// Send our headers.
	header("Content-type: text/plain; charset={$charset}");

	// Sanitize the input.
	$daddyobb->input['query'] = str_replace(array("%", "_"), array("\\%", "\\_"), $daddyobb->input['query']);
	
	// Query for any matching users.
	$query_options = array(
		"order_by" => "username",
		"order_dir" => "asc",
		"limit_start" => 0,
		"limit" => 15
	);
	
	$query = $db->simple_select("users", "uid, username", "username LIKE '".$db->escape_string($daddyobb->input['query'])."%'", $query_options);
	while($user = $db->fetch_array($query))
	{
		$user['username'] = htmlspecialchars_uni($user['username']);
		// Send the result to the browser for this user.
		echo "<div>\n";
		echo "<span class=\"username\">{$user['username']}</span>\n";
		echo "</div>\n";
	}
}
// This action provides editing of thread/post subjects from within their respective list pages.
else if($daddyobb->input['action'] == "edit_subject" && $daddyobb->request_method == "post")
{
	// Verify POST request
	if(!verify_post_check($daddyobb->input['my_post_key'], true))
	{
		xmlhttp_error($lang->invalid_post_code);
	}
	
	// Editing a post subject.
	if($daddyobb->input['pid'])
	{
		// Fetch the post from the database.
		$post = get_post($daddyobb->input['pid']);
		
		// No result, die.
		if(!$post['pid'])
		{
			xmlhttp_error($lang->post_doesnt_exist);
		}
		
		// Fetch the thread associated with this post.
		$thread = get_thread($post['tid']);
	}
	
	// We're editing a thread subject.
	else if($daddyobb->input['tid'])
	{
		// Fetch the thread.
		$thread = get_thread($daddyobb->input['tid']);
		
		// Fetch some of the information from the first post of this thread.
		$query_options = array(
			"order_by" => "dateline",
			"order_dir" => "asc",
		);
		$query = $db->simple_select("posts", "pid,uid,dateline", "tid='".$thread['tid']."'", $query_options);
		$post = $db->fetch_array($query);
	}
	// Fetch the specific forum this thread/post is in.
	$forum = get_forum($thread['fid']);

	// Missing thread, invalid forum? Error.
	if(!$thread['tid'] || !$forum['fid'] || $forum['type'] != "f")
	{
		xmlhttp_error($lang->thread_doesnt_exist);
	}
	
	// Fetch forum permissions.
	$forumpermissions = forum_permissions($forum['fid']);
	
	// If this user is not a moderator with "caneditposts" permissions.
	if(!is_moderator($forum['fid'], "caneditposts"))
	{
		// Thread is closed - no editing allowed.
		if($thread['closed'] == 1)
		{
			xmlhttp_error($lang->thread_closed_edit_subjects);
		}
		// Forum is not open, user doesn't have permission to edit, or author doesn't match this user - don't allow editing.
		else if($forum['open'] == 0 || $forumpermissions['caneditposts'] == 0 || $daddyobb->user['uid'] != $post['uid'] || $daddyobb->user['uid'] == 0)
		{
			xmlhttp_error($lang->no_permission_edit_subject);
		}
		// If we're past the edit time limit - don't allow editing.
		else if($daddyobb->settings['edittimelimit'] != 0 && $post['dateline'] < (TIME_NOW-($daddyobb->settings['edittimelimit']*60)))
		{
			$lang->edit_time_limit = $lang->sprintf($lang->edit_time_limit, $daddyobb->settings['edittimelimit']);
			xmlhttp_error($lang->edit_time_limit);
		}
		$ismod = false;
	}
	else
	{
		$ismod = true;
	}
	$subject = $daddyobb->input['value'];
	if(my_strtolower($charset) != "utf-8")
	{
		if(function_exists("iconv"))
		{
			$subject = iconv($charset, "UTF-8//IGNORE", $subject);
		}
		else if(function_exists("mb_convert_encoding"))
		{
			$subject = @mb_convert_encoding($subject, $charset, "UTF-8");
		}
		else if(my_strtolower($charset) == "iso-8859-1")
		{
			$subject = utf8_decode($subject);
		}
	}	
	
	// Set up posthandler.
	require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("update");
	$posthandler->action = "post";

	// Set the post data that came from the input to the $post array.
	$updatepost = array(
		"pid" => $post['pid'],
		"tid" => $thread['tid'],
		"subject" => $subject,
		"edit_uid" => $daddyobb->user['uid']
	);
	$posthandler->set_data($updatepost);

	// Now let the post handler do all the hard work.
	if(!$posthandler->validate_post())
	{
		$post_errors = $posthandler->get_friendly_errors();
		$errors = implode("\n\n", $post_errors);
		xmlhttp_error($errors);
	}
	// No errors were found, we can call the update method.
	else
	{
		$posthandler->update_post();
		if($ismod == true)
		{
			$modlogdata = array(
				"tid" => $thread['tid'],
				"pid" => $post['pid'],
				"fid" => $forum['fid']
			);
			log_moderator_action($modlogdata, $lang->edited_post);
		}
	}
	
	require_once DADDYOBB_ROOT."inc/class_parser.php";
	$parser = new postParser;

	// Send our headers.
	header("Content-type: text/plain; charset={$charset}");
	
	$daddyobb->input['value'] = $parser->parse_badwords($daddyobb->input['value']);
	
	// Spit the subject back to the browser.
	echo substr($daddyobb->input['value'], 0, 120); // 120 is the varchar length for the subject column
	
	// Close the connection.
	exit;
}
else if($daddyobb->input['action'] == "edit_post")
{	
	// Fetch the post from the database.
	$post = get_post($daddyobb->input['pid']);
		
	// No result, die.
	if(!$post['pid'])
	{
		xmlhttp_error($lang->post_doesnt_exist);
	}
	
	// Fetch the thread associated with this post.
	$thread = get_thread($post['tid']);

	// Fetch the specific forum this thread/post is in.
	$forum = get_forum($thread['fid']);

	// Missing thread, invalid forum? Error.
	if(!$thread['tid'] || !$forum['fid'] || $forum['type'] != "f")
	{
		xmlhttp_error($lang->thread_doesnt_exist);
	}
	
	// Fetch forum permissions.
	$forumpermissions = forum_permissions($forum['fid']);
	
	// If this user is not a moderator with "caneditposts" permissions.
	if(!is_moderator($forum['fid'], "caneditposts"))
	{
		// Thread is closed - no editing allowed.
		if($thread['closed'] == 1)
		{
			xmlhttp_error($lang->thread_closed_edit_message);
		}
		// Forum is not open, user doesn't have permission to edit, or author doesn't match this user - don't allow editing.
		else if($forum['open'] == 0 || $forumpermissions['caneditposts'] == 0 || $daddyobb->user['uid'] != $post['uid'] || $daddyobb->user['uid'] == 0 || $daddyobb->user['suspendposting'] == 1)
		{
			xmlhttp_error($lang->no_permission_edit_post);
		}
		// If we're past the edit time limit - don't allow editing.
		else if($daddyobb->settings['edittimelimit'] != 0 && $post['dateline'] < (TIME_NOW-($daddyobb->settings['edittimelimit']*60)))
		{
			$lang->edit_time_limit = $lang->sprintf($lang->edit_time_limit, $daddyobb->settings['edittimelimit']);
			xmlhttp_error($lang->edit_time_limit);
		}
	}
	if($daddyobb->input['do'] == "get_post")
	{
		// Send our headers.
		header("Content-type: text/xml; charset={$charset}");
		
		$post['message'] = htmlspecialchars_uni($post['message']);
		$post['editreason'] = htmlspecialchars_uni($post['editreason']);
		
		// Send the contents of the post.
		eval("\$inline_editor = \"".$templates->get("xmlhttp_inline_post_editor")."\";");
		echo "<?xml version=\"1.0\" encoding=\"{$charset}\"?".">";
		echo "<form>".$inline_editor."</form>";
		exit;
	}
	else if($daddyobb->input['do'] == "update_post")
	{
		// Verify POST request
		if(!verify_post_check($daddyobb->input['my_post_key'], true))
		{
			xmlhttp_error($lang->invalid_post_code);
		}

		$message = strval($_POST['value']);
		if(my_strtolower($charset) != "utf-8")
		{
			if(function_exists("iconv"))
			{
				$message = iconv($charset, "UTF-8//IGNORE", $message);
			}
			else if(function_exists("mb_convert_encoding"))
			{
				$message = @mb_convert_encoding($message, $charset, "UTF-8");
			}
			else if(my_strtolower($charset) == "iso-8859-1")
			{
				$message = utf8_decode($message);
			}
		}
		
		// Set up posthandler.
		require_once DADDYOBB_ROOT."inc/datahandlers/post.php";
		$posthandler = new PostDataHandler("update");
		$posthandler->action = "post";

		// Set the post data that came from the input to the $post array.
		$updatepost = array(
			"pid" => $daddyobb->input['pid'],
			"message" => $message,
			"edit_uid" => $daddyobb->user['uid']
		);
		$posthandler->set_data($updatepost);

		// Now let the post handler do all the hard work.
		if(!$posthandler->validate_post())
		{
			$post_errors = $posthandler->get_friendly_errors();
			$errors = implode("\n\n", $post_errors);
			xmlhttp_error($errors);
		}
		// No errors were found, we can call the update method.
		else
		{
			$postinfo = $posthandler->update_post();
			$visible = $postinfo['visible'];
			if($visible == 0 && !is_moderator())
			{
				echo "<p>\n";
				echo $lang->post_moderation;
				echo "</p>\n";
				exit;
			}
		}

	//Stripe Edit Reason
	$input_reason = htmlspecialchars_uni($daddyobb->input['editreason']);
	$query = $db->simple_select("posts", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$check = $db->fetch_array($query);
	if($check['pid'] == $daddyobb->input['pid'])
	{
	  $reason = array(
		  "editreason" => $db->escape_string($input_reason),
		);
		$db->update_query("posts", $reason, "pid='".intval($daddyobb->input['pid'])."'");
	}

		require_once DADDYOBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		
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
	
		$post['message'] = $parser->parse_message($message, $parser_options);
		
		// Now lets fetch all of the attachments for these posts.
		$query = $db->simple_select("attachments", "*", "pid='{$post['pid']}'");
		while($attachment = $db->fetch_array($query))
		{
			$attachcache[$attachment['pid']][$attachment['aid']] = $attachment;
		}
		
		require_once DADDYOBB_ROOT."inc/functions_post.php";
		
		get_post_attachments($post['pid'], $post);

		// Figure out if we need to show an "edited by" message
		// Only show if at least one of "showeditedby" or "showeditedbyadmin" is enabled
		if($daddyobb->settings['showeditedby'] != 0 && $daddyobb->settings['showeditedbyadmin'] != 0)
		{
			$post['editdate'] = my_date($daddyobb->settings['dateformat'], TIME_NOW);
			$post['edittime'] = my_date($daddyobb->settings['timeformat'], TIME_NOW);
			$post['editedprofilelink'] = build_profile_link($daddyobb->user['username'], $daddyobb->user['uid']);
			if($daddyobb->input['editreason'])
			{
        $post['editnote'] = $lang->sprintf($lang->postbit_edited_reason, $post['editdate'], $post['edittime'], $post['editedprofilelink'], $daddyobb->input['editreason']);
      }
      else
      {
      	$post['editnote'] = $lang->sprintf($lang->postbit_edited, $post['editdate'], $post['edittime'], $post['editedprofilelink']);
      }
			eval("\$editedmsg = \"".$templates->get("postbit_editedby")."\";");
		}
		
		// Send our headers.
		header("Content-type: text/plain; charset={$charset}");
		echo "<p>\n";
		echo $post['message'];
		echo "</p>\n";
		if($editedmsg)
		{
			echo str_replace(array("\r", "\n"), "", "<editedmsg>{$editedmsg}</editedmsg>");
		}
	}
}
// Fetch the list of multiquoted posts which are not in a specific thread
else if($daddyobb->input['action'] == "get_multiquoted")
{
	// If the cookie does not exist, exit
	if(!array_key_exists("multiquote", $daddyobb->cookies))
	{
		exit;
	}
	// Divide up the cookie using our delimeter
	$multiquoted = explode("|", $daddyobb->cookies['multiquote']);
	
	// No values - exit
	if(!is_array($multiquoted))
	{
		exit;
	}
	
	// Loop through each post ID and sanitize it before querying
	foreach($multiquoted as $post)
	{
		$quoted_posts[$post] = intval($post);
	}

	// Join the post IDs back together
	$quoted_posts = implode(",", $quoted_posts);
	
	// Fetch unviewable forums
	$unviewable_forums = get_unviewable_forums();
	if($unviewable_forums)
	{
		$unviewable_forums = "AND t.fid NOT IN ({$unviewable_forums})";
	}
	$message = '';
	
	// Are we loading all quoted posts or only those not in the current thread?
	if(!$daddyobb->input['load_all'])
	{
		$from_tid = "p.tid != '".intval($daddyobb->input['tid'])."' AND ";
	}
	else
	{
		$from_tid = '';
	}

	require_once DADDYOBB_ROOT."inc/class_parser.php";
	$parser = new postParser;

	// Query for any posts in the list which are not within the specified thread
	$query = $db->query("
		SELECT p.subject, p.message, p.pid, p.tid, p.username, p.dateline, t.fid, p.visible, u.username AS userusername
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		WHERE {$from_tid}p.pid IN ($quoted_posts) {$unviewable_forums}
	");
	while($quoted_post = $db->fetch_array($query))
	{
		if(!is_moderator($quoted_post['fid']) && $quoted_post['visible'] == 0)
		{
			continue;
		}
		
		// Swap username over if we have a registered user
		if($quoted_post['userusername'])
		{
			$quoted_post['username'] = $quoted_post['userusername'];
		}
		// Clean up the message
		$quoted_post['message'] = preg_replace('#(^|\r|\n)/me ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} \\2", $quoted_post['message']);
		$quoted_post['message'] = preg_replace('#(^|\r|\n)/slap ([^\r\n<]*)#i', "\\1* {$quoted_post['username']} {$lang->slaps} \\2 {$lang->with_trout}", $quoted_post['message']);
		$quoted_post['message'] = preg_replace("#\[attachment=([0-9]+?)\]#i", '', $quoted_post['message']);
		$quoted_post['message'] = $parser->parse_badwords($quoted_post['message']);

		// Tack on to list of messages
		$message .= "[quote='{$quoted_post['username']}' pid='{$quoted_post['pid']}' dateline='{$quoted_post['dateline']}']\n{$quoted_post['message']}\n[/quote]\n\n";
	}
	
	// Send our headers.
	header("Content-type: text/plain; charset={$charset}");
	echo $message;
	exit;	
}
else if($daddyobb->input['action'] == "refresh_captcha")
{
	$imagehash = $db->escape_string($daddyobb->input['imagehash']);
	$query = $db->simple_select("captcha", "dateline", "imagehash='$imagehash'");
	if($db->num_rows($query) == 0)
	{
		xmlhttp_error($lang->captcha_not_exists);
	}
	$db->delete_query("captcha", "imagehash='$imagehash'");
	$randomstr = random_str(5);
	$imagehash = md5(random_str(12));
	$regimagearray = array(
		"imagehash" => $imagehash,
		"imagestring" => $randomstr,
		"dateline" => TIME_NOW
	);
	$db->insert_query("captcha", $regimagearray);
	header("Content-type: text/plain; charset={$charset}");
	echo $imagehash;
}
else if($daddyobb->input['action'] == "validate_captcha")
{
	header("Content-type: text/xml; charset={$charset}");
	$imagehash = $db->escape_string($daddyobb->input['imagehash']);
	$query = $db->simple_select("captcha", "imagestring", "imagehash='$imagehash'");
	if($db->num_rows($query) == 0)
	{
		echo "<fail>{$lang->captcha_valid_not_exists}</fail>";
		exit;
	}
	$imagestring = $db->fetch_field($query, 'imagestring');

	if(my_strtolower($imagestring) == my_strtolower($daddyobb->input['value']))
	{
		echo "<success>{$lang->captcha_matches}</success>";
		exit;
	}
	else
	{
		echo "<fail>{$lang->captcha_does_not_match}</fail>";
		exit;
	}
}
else if($daddyobb->input['action'] == "username_availability")
{
	require_once DADDYOBB_ROOT."inc/functions_user.php";
	$username = $daddyobb->input['value'];

	// Fix bad characters
	$username = trim($username);
	$username = str_replace(array(unicode_chr(160), unicode_chr(173), unicode_chr(0xCA), dec_to_utf8(8238), dec_to_utf8(8237)), array(" ", "-", "", "", ""), $username);

	// Remove multiple spaces from the username
	$username = preg_replace("#\s{2,}#", " ", $username);
	
	if(empty($username))
	{
		echo "<fail>{$lang->banned_characters_username}</fail>";
		exit;
	}

	header("Content-type: text/xml; charset={$charset}");

	// Check if the username belongs to the list of banned usernames.
	$banned_username = is_banned_username($username, true);
	if($banned_username)
	{
		echo "<fail>{$lang->banned_username}</fail>";
		exit;
	}

	// Check for certain characters in username (<, >, &, and slashes)
	if(strpos($username, "<") !== false || strpos($username, ">") !== false || strpos($username, "&") !== false || my_strpos($username, "\\") !== false || strpos($username, ";") !== false)
	{
		echo "<fail>{$lang->banned_characters_username}</fail>";
		exit;
	}

	// Check if the username is actually already in use
	$query = $db->simple_select("users", "uid", "LOWER(username)='".$db->escape_string(my_strtolower($username))."'");
	$user = $db->fetch_array($query);

	if($user['uid'])
	{
		$lang->username_taken = $lang->sprintf($lang->username_taken, $username);
		echo "<fail>{$lang->username_taken}</fail>";
		exit;		
	}
	else
	{
		$lang->username_available = $lang->sprintf($lang->username_available, $username);
		echo "<success>{$lang->username_available}</success>";
		exit;
	}
}
else if($daddyobb->input['action'] == "username_exists")
{
	require_once DADDYOBB_ROOT."inc/functions_user.php";
	$username = $daddyobb->input['value'];

	header("Content-type: text/xml; charset={$charset}");

	if(!trim($username))
	{
		echo "<success></success>";
		exit;
	}

	// Check if the username actually exists
	$query = $db->simple_select("users", "uid", "LOWER(username)='".$db->escape_string(my_strtolower($username))."'");
	$user = $db->fetch_array($query);

	if($user['uid'])
	{
		$lang->valid_username = $lang->sprintf($lang->valid_username, $username);
		echo "<success>{$lang->valid_username}</success>";
		exit;
	}
	else
	{
		$lang->invalid_username = $lang->sprintf($lang->invalid_username, $username);
		echo "<fail>{$lang->invalid_username}</fail>";
		exit;
	}
}
else if($daddyobb->input['action'] == "get_buddyselect")
{
	// Send our headers.
	header("Content-type: text/plain; charset={$charset}");

	if($daddyobb->user['buddylist'] != "")
	{
		$query_options = array(
			"order_by" => "username",
			"order_dir" => "asc"
		);
		$timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];		
		$query = $db->simple_select("users", "uid, username, usergroup, displaygroup, lastactive, lastvisit, invisible", "uid IN ({$daddyobb->user['buddylist']})", $query_options);
		$online = array();
		$offline = array();
		while($buddy = $db->fetch_array($query))
		{
			$buddy_name = format_name($buddy['username'], $buddy['usergroup'], $buddy['displaygroup']);
			$profile_link = build_profile_link($buddy_name, $buddy['uid'], '_blank');
			if($buddy['lastactive'] > $timecut && ($buddy['invisible'] == 0 || $daddyobb->user['usergroup'] == 4) && $buddy['lastvisit'] != $buddy['lastactive'])
			{
				eval("\$online[] = \"".$templates->get("xmlhttp_buddyselect_online")."\";");
			}
			else
			{
				eval("\$offline[] = \"".$templates->get("xmlhttp_buddyselect_offline")."\";");
			}
		}
		$online = implode("", $online);
		$offline = implode("", $offline);
		eval("\$buddy_select = \"".$templates->get("xmlhttp_buddyselect")."\";");
		echo $buddy_select;
	}
	else
	{
		xmlhttp_error($lang->buddylist_error);
	}
}

/**
 * Spits an XML Http based error message back to the browser
 *
 * @param string The message to send back.
 */
function xmlhttp_error($message)
{
	global $charset;
	
	// Send our headers.
	header("Content-type: text/xml; charset={$charset}");
	
	// Send the error message.
	echo "<error>".$message."</error>";
	
	// Exit
	exit;
}

?>