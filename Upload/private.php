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
define('THIS_SCRIPT', 'private.php');

$templatelist = "private_send,private_send_buddyselect,private_read,private_tracking,private_tracking_readmessage,private_tracking_unreadmessage";
$templatelist .= ",private_folders,private_folders_folder,private_folders_folder_unremovable,private,usercp_nav_changename,usercp_nav,private_empty_folder,private_empty,posticons";
$templatelist .= "usercp_nav_messenger,usercp_nav_changename,usercp_nav_profile,usercp_nav_misc,usercp_nav_messenger,multipage_nextpage,multipage_page_current,multipage_page,multipage_start,multipage_end,multipage";
$templatelist .= ",private_messagebit,codebuttons,smilieinsert,posticons,private_send_autocomplete,private_messagebit_denyreceipt,private_read_to, postbit_online,postbit_find,postbit_pm, postbit_email,postbit_reputation,postbit_warninglevel,postbit_author_user,postbit_reply_pm,postbit_forward_pm,postbit_delete_pm,postbit,private_tracking_nomessage";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("userbase");

if($daddyobb->settings['enablepms'] == 0)
{
	error($lang->pms_disabled);
}

if($daddyobb->user['uid'] == '/' || $daddyobb->user['uid'] == 0 || $daddyobb->usergroup['canusepms'] == 0)
{
	error_no_permission();
}

if(!$daddyobb->user['pmfolders'])
{
	$daddyobb->user['pmfolders'] = "1**$%%$2**$%%$3**$%%$4**";

	$sql_array = array(
		 "pmfolders" => $daddyobb->user['pmfolders']
	);
	$db->update_query("users", $sql_array, "uid = ".$daddyobb->user['uid']);
}

// On a random occassion, recount the users pm's just to make sure everything is in sync.
$rand = rand(0, 9);
if($rand == 5)
{
	update_pm_count();
}

$folderjump = "<select name=\"jumpto\">\n";
$folderoplist = "<input type=\"hidden\" value=\"".intval($daddyobb->input['fid'])."\" name=\"fromfid\" />\n<select name=\"fid\">\n";
$folderjump2 = "<select name=\"jumpto2\">\n";

$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
foreach($foldersexploded as $key => $folders)
{
	$folderinfo = explode("**", $folders, 2);
	if($daddyobb->input['fid'] == $folderinfo[0])
	{
		$sel = ' selected="selected"';
	}
	else
	{
		$sel = '';
	}
	$folderinfo[1] = get_pm_folder_name($folderinfo[0], $folderinfo[1]);
	$folderjump .= "<option value=\"$folderinfo[0]\"$sel>$folderinfo[1]</option>\n";
	$folderjump2 .= "<option value=\"$folderinfo[0]\"$sel>$folderinfo[1]</option>\n";
	$folderoplist .= "<option value=\"$folderinfo[0]\"$sel>$folderinfo[1]</option>\n";
	$folderlinks .= "&#149;&nbsp;<a href=\"private.php?fid=$folderinfo[0]\">$folderinfo[1]</a><br />\n";
}
$folderjump .= "</select>\n";
$folderjump2 .= "</select>\n";
$folderoplist .= "</select>\n";

usercp_menu();


// Make navigation
add_breadcrumb($lang->nav_pms, "private.php");

switch($daddyobb->input['action'])
{
	case "send":
		add_breadcrumb($lang->nav_send);
		break;
	case "tracking":
		add_breadcrumb($lang->nav_tracking);
		break;
	case "folders":
		add_breadcrumb($lang->nav_folders);
		break;
	case "empty":
		add_breadcrumb($lang->nav_empty);
		break;
	case "export":
		add_breadcrumb($lang->nav_export);
		break;
}
if($daddyobb->input['preview'])
{
	$daddyobb->input['action'] = "send";
}

// Dismissing a new/unread PM notice
if($daddyobb->input['action'] == "dismiss_notice")
{
	if($daddyobb->user['pmnotice'] != 2)
	{
		exit;
	}

	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$updated_user = array(
		"pmnotice" => 1
	);
	$db->update_query("users", $updated_user, "uid='{$daddyobb->user['uid']}'");

	if($daddyobb->input['ajax'])
	{
		echo 1;
		exit;
	}
	else
	{
		header("Location: index.php");
		exit;
	}
}

$send_errors = '';

if($daddyobb->input['action'] == "do_send" && $daddyobb->request_method == "post")
{
	if($daddyobb->usergroup['cansendpms'] == 0)
	{
		error_no_permission();
	}

	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_send_do_send");

	// Attempt to see if this PM is a duplicate or not
	$time_cutoff = TIME_NOW - (5 * 60 * 60);
	$query = $db->query("
		SELECT pm.pmid
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users u ON(u.uid=pm.toid)
		WHERE u.username='".$db->escape_string($daddyobb->input['to'])."' AND pm.dateline > {$time_cutoff} AND pm.fromid='{$daddyobb->user['uid']}' AND pm.subject='".$db->escape_string($daddyobb->input['subject'])."' AND pm.message='".$db->escape_string($daddyobb->input['message'])."' AND pm.folder!='3'
	");
	$duplicate_check = $db->fetch_field($query, "pmid");
	if($duplicate_check)
	{
		error($lang->error_pm_already_submitted);
	}

	require_once DADDYOBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	$pm = array(
		"subject" => $daddyobb->input['subject'],
		"message" => $daddyobb->input['message'],
		"icon" => $daddyobb->input['icon'],
		"fromid" => $daddyobb->user['uid'],
		"do" => $daddyobb->input['do'],
		"pmid" => $daddyobb->input['pmid']
	);

	// Split up any recipients we have
	$pm['to'] = explode(",", $daddyobb->input['to']);
	$pm['to'] = array_map("trim", $pm['to']);
	if(!empty($daddyobb->input['bcc']))
	{
		$pm['bcc'] = explode(",", $daddyobb->input['bcc']);
		$pm['bcc'] = array_map("trim", $pm['bcc']);
	}

	$pm['options'] = array(
		"signature" => $daddyobb->input['options']['signature'],
		"disablesmilies" => $daddyobb->input['options']['disablesmilies'],
		"savecopy" => $daddyobb->input['options']['savecopy'],
		"readreceipt" => $daddyobb->input['options']['readreceipt']
	);

	if($daddyobb->input['saveasdraft'])
	{
		$pm['saveasdraft'] = 1;
	}
	$pmhandler->set_data($pm);

	// Now let the pm handler do all the hard work.
	if(!$pmhandler->validate_pm())
	{
		$pm_errors = $pmhandler->get_friendly_errors();
		$send_errors = inline_error($pm_errors);
		$daddyobb->input['action'] = "send";
	}
	else
	{
		$pminfo = $pmhandler->insert_pm();
		$plugins->run_hooks("private_do_send_end");

		if(isset($pminfo['draftsaved']))
		{
			redirect("private.php", $lang->redirect_pmsaved);
		}
		else
		{
			redirect("private.php", $lang->redirect_pmsent);
		}
	}
}

if($daddyobb->input['action'] == "send")
{
	if($daddyobb->usergroup['cansendpms'] == 0)
	{
		error_no_permission();
	}

	$plugins->run_hooks("private_send_start");

	$smilieinserter = $codebuttons = '';
	
	if($daddyobb->settings['bbcodeinserter'] != 0 && $daddyobb->settings['pmsallowmycode'] != 0 && $daddyobb->user['showcodebuttons'] != 0)
	{
		$codebuttons = build_mycode_inserter();
		if($daddyobb->settings['pmsallowsmilies'] != 0)
		{
			$smilieinserter = build_clickable_smilies();
		}
	}

	$posticons = get_post_icons();
	$previewmessage = $daddyobb->input['message'];
	$message = htmlspecialchars_uni($daddyobb->input['message']);
	$subject = $previewsubject = htmlspecialchars_uni($daddyobb->input['subject']);

	if($daddyobb->input['preview'] || $send_errors)
	{
		$options = $daddyobb->input['options'];
		if($options['signature'] == 1)
		{
			$optionschecked['signature'] = 'checked="checked"';
		}
		if($options['disablesmilies'] == 1)
		{
			$optionschecked['disablesmilies'] = 'checked="checked"';
		}
		if($options['savecopy'] != 0)
		{
			$optionschecked['savecopy'] = 'checked="checked"';
		}
		if($options['readreceipt'] != 0)
		{
			$optionschecked['readreceipt'] = 'checked="checked"';
		}
		$to = htmlspecialchars_uni($daddyobb->input['to']);
		$bcc = htmlspecialchars_uni($daddyobb->input['bcc']);
	}

	// Preview
	if($daddyobb->input['preview'])
	{
		$options = $daddyobb->input['options'];
		$query = $db->query("
			SELECT u.username AS userusername, u.*, f.*, g.title AS grouptitle, g.usertitle AS groupusertitle, g.namestyle, g.stars AS groupstars, g.starimage AS groupstarimage, g.image AS groupimage, g.usereputationsystem
			FROM ".TABLE_PREFIX."users u
			LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
			LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid=u.usergroup)
			WHERE u.uid='".$daddyobb->user['uid']."'
		");
		$post = $db->fetch_array($query);
		$post['userusername'] = $daddyobb->user['username'];
		$post['postusername'] = $daddyobb->user['username'];
		$post['message'] = $previewmessage;
		$post['subject'] = $previewsubject;
		$post['icon'] = $daddyobb->input['icon'];
		$post['smilieoff'] = $options['disablesmilies'];
		$post['dateline'] = TIME_NOW;
		if(!$options['signature'])
		{
			$post['includesig'] = 0;
		}
		else
		{
			$post['includesig'] = 1;
		}
		$postbit = build_postbit($post, 2);
		eval("\$preview = \"".$templates->get("previewpost")."\";");
	}
	else if(!$send_errors)
	{
		// New PM, so load default settings
		if($daddyobb->user['signature'] != '')
		{
			$optionschecked['signature'] = 'checked="checked"';
		}
		if($daddyobb->usergroup['cantrackpms'] == 1)
		{
			$optionschecked['readreceipt'] = 'checked="checked"';
		}
		$optionschecked['savecopy'] = 'checked="checked"';
	}
	
	// Draft, reply, forward
	if($daddyobb->input['pmid'] && !$daddyobb->input['preview'] && !$send_errors)
	{
		$query = $db->query("
			SELECT pm.*, u.username AS quotename
			FROM ".TABLE_PREFIX."privatemessages pm
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=pm.fromid)
			WHERE pm.pmid='".intval($daddyobb->input['pmid'])."' AND pm.uid='".$daddyobb->user['uid']."'
		");
		$pm = $db->fetch_array($query);

		$message = htmlspecialchars_uni($pm['message']);
		$subject = htmlspecialchars_uni($pm['subject']);

		if($pm['folder'] == "3")
		{ // message saved in drafts
			$daddyobb->input['uid'] = $pm['toid'];

			if($pm['includesig'] == 1)
			{
				$optionschecked['signature'] = 'checked="checked"';
			}
			if($pm['smilieoff'] == 1)
			{
				$optionschecked['disablesmilies'] = 'checked="checked"';
			}
			if($pm['receipt'])
			{
				$optionschecked['readreceipt'] = 'checked="checked"';
			}

			// Get list of recipients
			$recipients = unserialize($pm['recipients']);
			$comma = '';
			$recipientids = $pm['fromid'];
			if(isset($recipients['to']) && is_array($recipients['to']))
			{
				foreach($recipients['to'] as $recipient)
				{
					$recipient_list['to'][] = $recipient;
					$recipientids .= $comma.$recipient;
					$comma = ',';
				}
			}
			
			if(isset($recipients['bcc']) && is_array($recipients['bcc']))
			{
				foreach($recipients['bcc'] as $recipient)
				{
					$recipient_list['bcc'][] = $recipient;
					$recipientids .= $comma.$recipient;
					$comma = ',';
				}	
			}
			
			$query = $db->simple_select("users", "uid, username", "uid IN ({$recipientids})");
			while($user = $db->fetch_array($query))
			{
				if(isset($recipients['bcc']) && is_array($recipients['bcc']) && in_array($user['uid'], $recipient_list['bcc']))
				{
					$bcc .= htmlspecialchars_uni($user['username']).', ';
				}
				else
				{
					$to .= htmlspecialchars_uni($user['username']).', ';
				}
			}
		}
		else
		{ // forward/reply
			$subject = preg_replace("#(FW|RE):( *)#is", '', $subject);
			$postdate = my_date($daddyobb->settings['dateformat'], $pm['dateline']);
			$posttime = my_date($daddyobb->settings['timeformat'], $pm['dateline']);
			$message = "[quote={$pm['quotename']}]\n$message\n[/quote]";
			$pm['message'] = preg_replace('#^/me (.*)$#im', "* ".$pm['quotename']." \\1", $pm['message']);

			if($daddyobb->input['do'] == 'forward')
			{
				$subject = "Fw: $subject";
			}
			elseif($daddyobb->input['do'] == 'reply')
			{
				$subject = "Re: $subject";
				$uid = $pm['fromid'];
				if($daddyobb->user['uid'] == $uid)
				{
					$to = $daddyobb->user['username'];
				}
				else
				{
					$query = $db->simple_select('users', 'username', "uid='{$uid}'");
					$to = $db->fetch_field($query, 'username');
				}
				$to = htmlspecialchars_uni($to);
			}
			else if($daddyobb->input['do'] == 'replyall')
			{
				$subject = "Re: $subject";

				// Get list of recipients
				$recipients = unserialize($pm['recipients']);
				$recipientids = $pm['fromid'];
				if(isset($recipients['to']) && is_array($recipients['to']))
				{
					foreach($recipients['to'] as $recipient)
					{
						if($recipient == $daddyobb->user['uid'])
						{
							continue;
						}
						$recipientids .= ','.$recipient;
					}
				}
				$comma = '';
				$query = $db->simple_select('users', 'uid, username', "uid IN ({$recipientids})");
				while($user = $db->fetch_array($query))
				{
					$to .= $comma.htmlspecialchars($user['username']);
					$comma = ', ';
				}
			}
		}
	}

	// New PM with recipient preset
	if($daddyobb->input['uid'] && !$daddyobb->input['preview'])
	{
		$query = $db->simple_select('users', 'username', "uid='".$db->escape_string($daddyobb->input['uid'])."'");
		$to = htmlspecialchars_uni($db->fetch_field($query, 'username')).', ';
	}

	$max_recipients = '';
	if($daddyobb->usergroup['maxpmrecipients'] > 0)
	{
		$max_recipients = $lang->sprintf($lang->max_recipients, $daddyobb->usergroup['maxpmrecipients']);
	}

	if($send_errors)
	{
		$to = htmlspecialchars_uni($daddyobb->input['to']);
		$bcc = htmlspecialchars_uni($daddyobb->input['bcc']); 
	}

	// Load the auto complete javascript if it is enabled.
	eval("\$autocompletejs = \"".$templates->get("private_send_autocomplete")."\";");

	$pmid = $daddyobb->input['pmid'];
	$do = $daddyobb->input['do'];
	if($do != "forward" && $do != "reply")
	{
		$do = '';
	}
	
	// See if it's actually worth showing the buddylist icon.
	if($daddyobb->user['buddylist'] != '')
	{
		$buddy_select = 'to';
		eval("\$buddy_select_to = \"".$templates->get("private_send_buddyselect")."\";");
		$buddy_select = 'bcc';
		eval("\$buddy_select_bcc = \"".$templates->get("private_send_buddyselect")."\";");
	}

	eval("\$send = \"".$templates->get("private_send")."\";");
	$plugins->run_hooks("private_send_end");
	output_page($send);
}


if($daddyobb->input['action'] == "read")
{
	$plugins->run_hooks("private_read");

	$pmid = intval($daddyobb->input['pmid']);

	$query = $db->query("
		SELECT pm.*, u.*, f.*, g.title AS grouptitle, g.usertitle AS groupusertitle, g.stars AS groupstars, g.starimage AS groupstarimage, g.image AS groupimage, g.namestyle
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=pm.fromid)
		LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
		LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid=u.usergroup)
		WHERE pm.pmid='".intval($daddyobb->input['pmid'])."' AND pm.uid='".$daddyobb->user['uid']."'
	");
	$pm = $db->fetch_array($query);
	if($pm['folder'] == 3)
	{
		header("Location: private.php?action=send&pmid={$pm['pmid']}");
		exit;
	}

	if(!$pm['pmid'])
	{
		error($lang->error_invalidpm);
	}

	if($pm['receipt'] == 1)
	{
		if($daddyobb->usergroup['cantrackpms'] == 1 && $daddyobb->usergroup['candenypmreceipts'] == 1 && $daddyobb->input['denyreceipt'] == 1)
		{
			$receiptadd = 0;
		}
		else
		{
			$receiptadd = 2;
		}
	}

	if($pm['status'] == 0)
	{
		$time = TIME_NOW;
		$updatearray = array(
			'status' => 1,
			'readtime' => $time
		);

		if(isset($receiptadd))
		{
			$updatearray['receipt'] = $receiptadd;
		}

		$db->update_query('privatemessages', $updatearray, "pmid='{$pmid}'");

		// Update the unread count - it has now changed.
		update_pm_count($daddyobb->user['uid'], 6);

		// Update PM notice value if this is our last unread PM
		if($daddyobb->user['unreadpms']-1 <= 0 && $daddyobb->user['pmnotice'] == 2)
		{
			$updated_user = array(
				"pmnotice" => 1
			);
			$db->update_query("users", $updated_user, "uid='{$daddyobb->user['uid']}'");
		}
	}
	// Replied PM?
	else if($pm['status'] == 3 && $pm['statustime'])
	{
		$reply_date = my_date($daddyobb->settings['dateformat'], $pm['statustime']);
		
		if($reply_date == $lang->today || $reply_date == $lang->yesterday)
		{
			$reply_date .= ', '.my_date($daddyobb->settings['timeformat'], $pm['statustime']);
			$actioned_on = $lang->sprintf($lang->you_replied, $reply_date);
		}
		else
		{
			$reply_date .= ', '.my_date($daddyobb->settings['timeformat'], $pm['statustime']);
			$actioned_on = $lang->sprintf($lang->you_replied_on, $reply_date);
		}
		
		eval("\$action_time = \"".$templates->get("private_read_action")."\";");
	}
	else if($pm['status'] == 4 && $pm['statustime'])
	{
		$forward_date = my_date($daddyobb->settings['dateformat'], $pm['statustime']);
		
		if(strpos($forward_date, $lang->today) !== false || strpos($forward_date, $lang->yesterday) !== false)
		{
			$forward_date .= ', '.my_date($daddyobb->settings['timeformat'], $pm['statustime']);
			$actioned_on = $lang->sprintf($lang->you_forwarded, $forward_date);
		}
		else
		{
			$forward_date .= ', '.my_date($daddyobb->settings['timeformat'], $pm['statustime']);
			$actioned_on = $lang->sprintf($lang->you_forwarded_on, $forward_date);
		}
		
		eval("\$action_time = \"".$templates->get("private_read_action")."\";");
	}

	$pm['userusername'] = $pm['username'];
	$pm['subject'] = htmlspecialchars_uni($parser->parse_badwords($pm['subject']));
	if($pm['fromid'] == 0)
	{
		$pm['username'] = 'DaddyoBB Engine';
	}

	// Fetch the recipients for this message
	$pm['recipients'] = @unserialize($pm['recipients']);

	if(is_array($pm['recipients']['to']))
	{
		$uid_sql = implode(',', $pm['recipients']['to']);
	}
	else
	{
		$uid_sql = $pm['toid'];
		$pm['recipients']['to'] = array($pm['toid']);
	}

	$show_bcc = 0;

	// If we have any BCC recipients and this user is an Administrator, add them on to the query
	if(count($pm['recipients']['bcc']) > 0 && $daddyobb->usergroup['cancp'] == 1)
	{
		$show_bcc = 1;
		$uid_sql .= ','.implode(',', $pm['recipients']['bcc']);
	}
	
	// Fetch recipient names from the database
	$bcc_recipients = $to_recipients = array();
	$query = $db->simple_select('users', 'uid, username', "uid IN ({$uid_sql})");
	while($recipient = $db->fetch_array($query))
	{
		// User is a BCC recipient
		if($show_bcc && in_array($recipient['uid'], $pm['recipients']['bcc']))
		{
			$bcc_recipients[] = build_profile_link($recipient['username'], $recipient['uid']);
		}
		// User is a normal recipient
		else if(in_array($recipient['uid'], $pm['recipients']['to']))
		{
			$to_recipients[] = build_profile_link($recipient['username'], $recipient['uid']);
		}
	}

	if(count($bcc_recipients) > 0)
	{
		$bcc_recipients = implode(', ', $bcc_recipients);
		eval("\$bcc = \"".$templates->get("private_read_bcc")."\";");
	}

	$replyall = false;
	if(count($to_recipients) > 1)
	{
		$replyall = true;
	}
	
	if(count($to_recipients) > 0)
	{
		$to_recipients = implode(", ", $to_recipients);
	}
	else
	{
		$to_recipients = $lang->nobody;
	}

	eval("\$pm['subject_extra'] = \"".$templates->get("private_read_to")."\";");
	
	add_breadcrumb($pm['subject']);
	$message = build_postbit($pm, 2);
	eval("\$read = \"".$templates->get("private_read")."\";");
	$plugins->run_hooks("private_read_end");
	output_page($read);
}

if($daddyobb->input['action'] == "tracking")
{
	$plugins->run_hooks("private_tracking_start");
	$readmessages = '';
	$unreadmessages = '';
	
	$query = $db->query("
		SELECT pm.*, u.username as tousername
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=pm.toid)
		WHERE receipt='2' AND folder!='3'  AND status!='0' AND fromid='".$daddyobb->user['uid']."'
		ORDER BY pm.readtime DESC
	");
	while($readmessage = $db->fetch_array($query))
	{
		$readmessage['subject'] = htmlspecialchars_uni($parser->parse_badwords($readmessage['subject']));
		$readmessage['profilelink'] = build_profile_link($readmessage['tousername'], $readmessage['toid']);
		$readdate = my_date($daddyobb->settings['dateformat'], $readmessage['readtime']);
		$readtime = my_date($daddyobb->settings['timeformat'], $readmessage['readtime']);
		eval("\$readmessages .= \"".$templates->get("private_tracking_readmessage")."\";");
	}
	
	if(!$readmessages)
	{
		eval("\$readmessages = \"".$templates->get("private_tracking_nomessage")."\";");
	}
	
	$query = $db->query("
		SELECT pm.*, u.username AS tousername
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=pm.toid)
		WHERE receipt='1' AND folder!='3' AND status='0' AND fromid='".$daddyobb->user['uid']."'
		ORDER BY pm.dateline DESC
	");
	while($unreadmessage = $db->fetch_array($query))
	{
		$unreadmessage['subject'] = htmlspecialchars_uni($parser->parse_badwords($unreadmessage['subject']));
		$unreadmessage['profilelink'] = build_profile_link($unreadmessage['tousername'], $unreadmessage['toid']);		
		$senddate = my_date($daddyobb->settings['dateformat'], $unreadmessage['dateline']);
		$sendtime = my_date($daddyobb->settings['timeformat'], $unreadmessage['dateline']);
		eval("\$unreadmessages .= \"".$templates->get("private_tracking_unreadmessage")."\";");
	}
	
	if(!$unreadmessages)
	{
		$lang->no_readmessages = $lang->no_unreadmessages;
		eval("\$unreadmessages = \"".$templates->get("private_tracking_nomessage")."\";");
	}
	
	eval("\$tracking = \"".$templates->get("private_tracking")."\";");
	$plugins->run_hooks("private_tracking_end");
	output_page($tracking);
}
if($daddyobb->input['action'] == "do_tracking" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_do_tracking_start");
	
	if($daddyobb->input['stoptracking'])
	{
		if(is_array($daddyobb->input['readcheck']))
		{
			foreach($daddyobb->input['readcheck'] as $key => $val)
			{
				$sql_array = array(
					"receipt" => 0
				);
				$db->update_query("privatemessages", $sql_array, "pmid=".intval($key)." AND fromid=".$daddyobb->user['uid']);
			}
		}
		$plugins->run_hooks("private_do_tracking_end");
		redirect("private.php", $lang->redirect_pmstrackingstopped);
	}
	elseif($daddyobb->input['stoptrackingunread'])
	{
		if(is_array($daddyobb->input['unreadcheck']))
		{
			foreach($daddyobb->input['unreadcheck'] as $key => $val)
			{
				$sql_array = array(
					"receipt" => 0
				);
				$db->update_query("privatemessages", $sql_array, "pmid=".intval($key)." AND fromid=".$daddyobb->user['uid']);
			}
		}
		$plugins->run_hooks("private_do_tracking_end");
		redirect("private.php", $lang->redirect_pmstrackingstopped);
	}
	elseif($daddyobb->input['cancel'])
	{
		if(is_array($daddyobb->input['unreadcheck']))
		{
			foreach($daddyobb->input['unreadcheck'] as $pmid => $val)
			{
				$pmids[$pmid] = intval($pmid);
			}
			
			$pmids = implode(",", $pmids);
			$query = $db->simple_select("privatemessages", "uid", "pmid IN ($pmids) AND fromid='".$daddyobb->user['uid']."'");
			while($pm = $db->fetch_array($query))
			{
				$pmuids[$pm['uid']] = $pm['uid'];
			}
			
			$db->delete_query("privatemessages", "pmid IN ($pmids) AND receipt='1' AND status='0' AND fromid='".$daddyobb->user['uid']."'");
			foreach($pmuids as $uid)
			{
				// Message is canceled, update PM count for this user
				update_pm_count($uid);
			}
		}
		$plugins->run_hooks("private_do_tracking_end");
		redirect("private.php", $lang->redirect_pmstrackingcanceled);
	}
}

if($daddyobb->input['action'] == "folders")
{
	$plugins->run_hooks("private_folders_start");
	
	$folderlist = '';	
	$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
	foreach($foldersexploded as $key => $folders)
	{
		$folderinfo = explode("**", $folders, 2);
		$foldername = $folderinfo[1];
		$fid = $folderinfo[0];
		$foldername = get_pm_folder_name($fid, $foldername);
		
		if($folderinfo[0] == "1" || $folderinfo[0] == "2" || $folderinfo[0] == "3" || $folderinfo[0] == "4")
		{
			$foldername2 = get_pm_folder_name($fid);
			eval("\$folderlist .= \"".$templates->get("private_folders_folder_unremovable")."\";");
			unset($name);
		}
		else
		{
			eval("\$folderlist .= \"".$templates->get("private_folders_folder")."\";");
		}
	}
	
	$newfolders = '';
	for($i = 1; $i <= 5; ++$i)
	{
		$fid = "new$i";
		$foldername = '';
		eval("\$newfolders .= \"".$templates->get("private_folders_folder")."\";");
	}
	
	eval("\$folders = \"".$templates->get("private_folders")."\";");
	$plugins->run_hooks("private_folders_end");
	output_page($folders);
}

if($daddyobb->input['action'] == "do_folders" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_do_folders_start");
	
	$highestid = 2;
	$folders = '';
	@reset($daddyobb->input['folder']);
	foreach($daddyobb->input['folder'] as $key => $val)
	{
		if(!$donefolders[$val]) // Probably was a check for duplicate folder names, but doesn't seem to be used now 
		{
			if(my_substr($key, 0, 3) == "new") // Create a new folder
			{
				++$highestid;
				$fid = intval($highestid);
			}
			else // Editing an existing folder
			{
				if($key > $highestid)
				{
					$highestid = $key;
				}
				
				$fid = intval($key);
				// Use default language strings if empty or value is language string
				switch($fid)
				{
					case 1:
						if($val == $lang->folder_inbox || trim($val) == '')
						{
							$val = '';
						}
						break;
					case 2:
						if($val == $lang->folder_sent_items || trim($val) == '')
						{
							$val = '';
						}
						break;
					case 3:
						if($val == $lang->folder_drafts || trim($val) == '')
						{
							$val = '';
						}
						break;
					case 4:
						if($val == $lang->folder_trash || trim($val) == '')
						{
							$val = '';
						}
						break;
				}
			}
			
			if($val != '' && trim($val) == '' && !($key >= 1 && $key <= 4))
			{
				// If the name only contains whitespace and it's not a default folder, print an error
				error($lang->error_emptypmfoldername);
			}
			
			
			if($val != '' || ($key >= 1 && $key <= 4))
			{
				// If there is a name or if this is a default folder, save it 
				$foldername = $val;
				$foldername = $db->escape_string(htmlspecialchars_uni($foldername));
				
				if(my_strpos($foldername, "$%%$") === false)
				{
					if($folders != '')
					{
						$folders .= "$%%$";
					}
					$folders .= "$fid**$foldername";
				}
				else
				{
					error($lang->error_invalidpmfoldername);
				}
			}
			else
			{
				// Delete PMs from the folder
				$db->delete_query("privatemessages", "folder='$fid' AND uid='".$daddyobb->user['uid']."'");
			}
		}
	}

	$sql_array = array(
		"pmfolders" => $folders
	);	
	$db->update_query("users", $sql_array, "uid='".$daddyobb->user['uid']."'");
	
	// Update PM count
	update_pm_count();
	
	$plugins->run_hooks("private_do_folders_end");
	
	redirect("private.php", $lang->redirect_pmfoldersupdated);
}

if($daddyobb->input['action'] == "empty")
{
	$plugins->run_hooks("private_empty_start");
	
	$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
	$folderlist = '';
	foreach($foldersexploded as $key => $folders)
	{
		$folderinfo = explode("**", $folders, 2);
		$fid = $folderinfo[0];
		$foldername = get_pm_folder_name($fid, $folderinfo[1]);
		$query = $db->simple_select("privatemessages", "COUNT(*) AS pmsinfolder", " folder='$fid' AND uid='".$daddyobb->user['uid']."'");
		$thing = $db->fetch_array($query);
		$foldercount = my_number_format($thing['pmsinfolder']);
		eval("\$folderlist .= \"".$templates->get("private_empty_folder")."\";");
	}
	
	eval("\$folders = \"".$templates->get("private_empty")."\";");
	$plugins->run_hooks("private_empty_end");
	output_page($folders);
}

if($daddyobb->input['action'] == "do_empty" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_do_empty_start");
	
	$emptyq = '';
	if(is_array($daddyobb->input['empty']))
	{
		foreach($daddyobb->input['empty'] as $key => $val)
		{
			if($val == 1)
			{
				$key = intval($key);
				if($emptyq)
				{
					$emptyq .= " OR ";
				}
				$emptyq .= "folder='$key'";
			}
		}
		
		if($emptyq != '')
		{
			if($daddyobb->input['keepunread'] == 1)
			{
				$keepunreadq = " AND status!='0'";
			}
			$db->delete_query("privatemessages", "($emptyq) AND uid='".$daddyobb->user['uid']."' $keepunreadq");
		}
	}
	
	// Update PM count
	update_pm_count();

	$plugins->run_hooks("private_do_empty_end");
	redirect("private.php", $lang->redirect_pmfoldersemptied);
}

if($daddyobb->input['action'] == "do_stuff" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_do_stuff");
	
	if($daddyobb->input['hop'])
	{
		header("Location: private.php?fid=".intval($daddyobb->input['jumpto']));
	}
	elseif($daddyobb->input['moveto'])
	{
		if(is_array($daddyobb->input['check']))
		{
			foreach($daddyobb->input['check'] as $key => $val)
			{
				$sql_array = array(
					"folder" => intval($daddyobb->input['fid'])
				);
				$db->update_query("privatemessages", $sql_array, "pmid='".intval($key)."' AND uid='".$daddyobb->user['uid']."'");
			}
		}
		// Update PM count
		update_pm_count();

		if(!empty($daddyobb->input['fromfid']))
		{
			redirect("private.php?fid=".intval($daddyobb->input['fromfid']), $lang->redirect_pmsmoved);
		}
		else
		{
			redirect("private.php", $lang->redirect_pmsmoved);
		}
	}
	else if($daddyobb->input['delete'])
	{
		if(is_array($daddyobb->input['check']))
		{
			$pmssql = '';
			foreach($daddyobb->input['check'] as $key => $val)
			{
				if($pmssql)
				{
					$pmssql .= ",";
				}
				$pmssql .= "'".intval($key)."'";
			}
			
			$query = $db->simple_select("privatemessages", "pmid, folder", "pmid IN ($pmssql) AND uid='".$daddyobb->user['uid']."' AND folder='4'", array('order_by' => 'pmid'));
			while($delpm = $db->fetch_array($query))
			{
				$deletepms[$delpm['pmid']] = 1;
			}
			
			reset($daddyobb->input['check']);
			foreach($daddyobb->input['check'] as $key => $val)
			{
				$key = intval($key);
				if($deletepms[$key])
				{
					$db->delete_query("privatemessages", "pmid='$key' AND uid='".$daddyobb->user['uid']."'");
				}
				else
				{
					$sql_array = array(
						"folder" => 4,
						"deletetime" => TIME_NOW
					);
					$db->update_query("privatemessages", $sql_array, "pmid='".$key."' AND uid='".$daddyobb->user['uid']."'");
				}
			}
		}
		// Update PM count
		update_pm_count();

		redirect("private.php", $lang->redirect_pmsdeleted);
	}
}

if($daddyobb->input['action'] == "delete")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_delete_start");

	$sql_array = array(
		"folder" => 4,
		"deletetime" => TIME_NOW
	);
	$db->update_query("privatemessages", $sql_array, "pmid='".intval($daddyobb->input['pmid'])."' AND uid='".$daddyobb->user['uid']."'");

	// Update PM count
	update_pm_count();

	$plugins->run_hooks("private_delete_end");
	redirect("private.php", $lang->redirect_pmsdeleted);
}

if($daddyobb->input['action'] == "export")
{
	$plugins->run_hooks("private_export_start");
	
	$folderlist = "<select name=\"exportfolders[]\" multiple=\"multiple\">\n";
	$folderlist .= "<option value=\"all\" selected=\"selected\">$lang->all_folders</option>";
	$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
	foreach($foldersexploded as $key => $folders)
	{
		$folderinfo = explode("**", $folders, 2);
		$folderinfo[1] = get_pm_folder_name($folderinfo[0], $folderinfo[1]);
		$folderlist .= "<option value=\"$folderinfo[0]\">$folderinfo[1]</option>\n";
	}
	$folderlist .= "</select>\n";
	eval("\$archive = \"".$templates->get("private_archive")."\";");
	
	$plugins->run_hooks("private_export_end");
	
	output_page($archive);
}

if($daddyobb->input['action'] == "do_export" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("private_do_export_start");
	
	$lang->private_messages_for = $lang->sprintf($lang->private_messages_for, $daddyobb->user['username']);
	$exdate = my_date($daddyobb->settings['dateformat'], TIME_NOW, 0, 0);
	$extime = my_date($daddyobb->settings['timeformat'], TIME_NOW, 0, 0);
	$lang->exported_date = $lang->sprintf($lang->exported_date, $exdate, $extime);
	$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
	foreach($foldersexploded as $key => $folders)
	{
		$folderinfo = explode("**", $folders, 2);
		$folderinfo[1] = get_pm_folder_name($folderinfo[0], $folderinfo[1]);
		$foldersexploded[$key] = implode("**", $folderinfo);
	}
	
	if($daddyobb->input['pmid'])
	{
		$wsql = "pmid='".intval($daddyobb->input['pmid'])."' AND uid='".$daddyobb->user['uid']."'";
	}
	else
	{
		if($daddyobb->input['daycut'] && ($daddyobb->input['dayway'] != "disregard"))
		{
			$datecut = TIME_NOW-($daddyobb->input['daycut'] * 86400);
			$wsql = "pm.dateline";
			if($daddyobb->input['dayway'] == "older")
			{
				$wsql .= "<=";
			}
			else
			{
				$wsql .= ">=";
			}
			$wsql .= "'$datecut'";
		}
		else
		{
			$wsql = "1=1";
		}
		
		if(is_array($daddyobb->input['exportfolders']))
		{
			$folderlst = '';
			reset($daddyobb->input['exportfolders']);
			foreach($daddyobb->input['exportfolders'] as $key => $val)
			{
				$val = $db->escape_string($val);
				if($val == "all")
				{
					$folderlst = '';
					break;
				}
				else
				{
					if(!$folderlst)
					{
						$folderlst = " AND pm.folder IN ('$val'";
					}
					else
					{
						$folderlst .= ",'$val'";
					}
				}
			}
			if($folderlst)
			{
				$folderlst .= ")";
			}
			$wsql .= "$folderlst";
		}
		else
		{
			error($lang->error_pmnoarchivefolders);
		}
		
		if($daddyobb->input['exportunread'] != 1)
		{
			$wsql .= " AND pm.status!='0'";
		}
	}
	$query = $db->query("
		SELECT pm.*, fu.username AS fromusername, tu.username AS tousername
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users fu ON (fu.uid=pm.fromid)
		LEFT JOIN ".TABLE_PREFIX."users tu ON (tu.uid=pm.toid)
		WHERE $wsql AND pm.uid='".$daddyobb->user['uid']."'
		ORDER BY pm.folder ASC, pm.dateline DESC
	");
	$numpms = $db->num_rows($query);
	if(!$numpms)
	{
		error($lang->error_nopmsarchive);
	}
	
	$pmsdownload = '';
	while($message = $db->fetch_array($query))
	{
		if($message['folder'] == 2 || $message['folder'] == 3)
		{ // Sent Items or Drafts Folder Check
			if($message['toid'])
			{
				$tofromuid = $message['toid'];
				if($daddyobb->input['exporttype'] == "txt")
				{
					$tofromusername = $message['tousername'];
				}
				else
				{
					$tofromusername = build_profile_link($message['tousername'], $tofromuid);
				}
			}
			else
			{
				$tofromusername = $lang->not_sent;
			}
			$tofrom = $lang->to;
		}
		else
		{
			$tofromuid = $message['fromid'];
			if($daddyobb->input['exporttype'] == "txt")
			{
				$tofromusername = $message['fromusername'];
			}
			else
			{
				$tofromusername = build_profile_link($message['fromusername'], $tofromuid);
			}
			
			if($tofromuid == 0)
			{
				$tofromusername = "DaddyoBB Engine";
			}
			$tofrom = $lang->from;
		}
		
		if($tofromuid == 0)
		{
			$message['fromusername'] = "DaddyoBB Engine";
		}
		
		if(!$message['toid'] && $message['folder'] == 3)
		{
			$message['tousername'] = $lang->not_sent;
		}

		$message['subject'] = $parser->parse_badwords($message['subject']);
		if($message['folder'] != "3")
		{
			$senddate = my_date($daddyobb->settings['dateformat'], $message['dateline']);
			$sendtime = my_date($daddyobb->settings['timeformat'], $message['dateline']);
			$senddate .= " $lang->at $sendtime";
		}
		else
		{
			$senddate = $lang->not_sent;
		}
		
		if($daddyobb->input['exporttype'] == "html")
		{
			$parser_options = array(
				"allow_html" => $daddyobb->settings['pmsallowhtml'],
				"allow_mycode" => $daddyobb->settings['pmsallowmycode'],
				"allow_smilies" => 0,
				"allow_imgcode" => $daddyobb->settings['pmsallowimgcode'],
				"me_username" => $daddyobb->user['username'],
				"filter_badwords" => 1
			);

			$message['message'] = $parser->parse_message($message['message'], $parser_options);
			$message['subject'] = htmlspecialchars_uni($message['subject']);
		}
		
		if($daddyobb->input['exporttype'] == "txt" || $daddyobb->input['exporttype'] == "csv")
		{
			$message['message'] = str_replace("\r\n", "\n", $message['message']);
			$message['message'] = str_replace("\n", "\r\n", $message['message']);
		}
		
		if($daddyobb->input['exporttype'] == "csv")
		{
			$message['message'] = addslashes($message['message']);
			$message['subject'] = addslashes($message['subject']);
			$message['tousername'] = addslashes($message['tousername']);
			$message['fromusername'] = addslashes($message['fromusername']);
		}
		
		
		if(!$donefolder[$message['folder']])
		{
			reset($foldersexploded);
			foreach($foldersexploded as $key => $val)
			{
				$folderinfo = explode("**", $val, 2);
				if($folderinfo[0] == $message['folder'])
				{
					$foldername = $folderinfo[1];
					if($daddyobb->input['exporttype'] != "csv")
					{
						if($daddyobb->input['exporttype'] != "html")
						{
							$daddyobb->input['exporttype'] == "txt";
						}
						eval("\$pmsdownload .= \"".$templates->get("private_archive_".$daddyobb->input['exporttype']."_folderhead", 1, 0)."\";");
					}
					else
					{
						$foldername = addslashes($folderinfo[1]);
					}
					$donefolder[$message['folder']] = 1;
				}
			}
		}
		
		eval("\$pmsdownload .= \"".$templates->get("private_archive_".$daddyobb->input['exporttype']."_message", 1, 0)."\";");
		$ids .= ",'{$message['pmid']}'";
	}
	
	$query = $db->simple_select("themestylesheets", "stylesheet", "sid=1", array('limit' => 1));
	$css = $db->fetch_field($query, "stylesheet");

	eval("\$archived = \"".$templates->get("private_archive_".$daddyobb->input['exporttype'], 1, 0)."\";");
	if($daddyobb->input['deletepms'] == 1)
	{ // delete the archived pms
		$db->delete_query("privatemessages", "pmid IN (''$ids)");
		// Update PM count
		update_pm_count();
	}
	
	if($daddyobb->input['exporttype'] == "html")
	{
		$filename = "pm-archive.html";
		$contenttype = "text/html";
	}
	elseif($daddyobb->input['exporttype'] == "csv")
	{
		$filename = "pm-archive.csv";
		$contenttype = "application/octet-stream";
	}
	else
	{
		$filename = "pm-archive.txt";
		$contenttype = "text/plain";
	}
	
	$archived = str_replace("\\\'","'",$archived);
	header("Content-disposition: filename=$filename");
	header("Content-type: ".$contenttype);
	
	$plugins->run_hooks("private_do_export_end");
	
	if($daddyobb->input['exporttype'] == "html")
	{
		output_page($archived);
	}
	else
	{
		echo $archived;
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("private_start");
	
	if(!$daddyobb->input['fid'])
	{
		$daddyobb->input['fid'] = 1;
	}

	$foldersexploded = explode("$%%$", $daddyobb->user['pmfolders']);
	foreach($foldersexploded as $key => $folders)
	{
		$folderinfo = explode("**", $folders, 2);
		if($folderinfo[0] == $daddyobb->input['fid'])
		{
			$folder = $folderinfo[0];
			$foldername = get_pm_folder_name($folder, $folderinfo[1]);
		}
	}
	$lang->private_messages_in_folder = $lang->sprintf($lang->private_messages_in_folder, $foldername);
	if($folder == 2 || $folder == 3)
	{ // Sent Items Folder
		$sender = $lang->sentto;
	}
	else
	{
		$sender = $lang->sender;
	}

	// Do Multi Pages
	$query = $db->simple_select("privatemessages", "COUNT(*) AS total", "uid='".$daddyobb->user['uid']."' AND folder='$folder'");
	$pmscount = $db->fetch_array($query);

	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}

	$perpage = $daddyobb->settings['threadsperpage'];
	$page = intval($daddyobb->input['page']);
	
	if(intval($daddyobb->input['page']) > 0)
	{
		$start = ($page-1) *$perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	
	$end = $start + $perpage;
	$lower = $start+1;
	$upper = $end;
	
	if($upper > $threadcount)
	{
		$upper = $threadcount;
	}
	$multipage = multipage($pmscount['total'], $perpage, $page, "private.php?fid=$folder");
	$messagelist = '';
	
	$icon_cache = $cache->read("posticons");
	
	// Cache users in multiple recipients for sent & drafts folder
	if($folder == 2 || $folder == 3)
	{		
		// Get all recipients into an array
		$cached_users = $get_users = array();
		$users_query = $db->simple_select("privatemessages", "recipients", "folder='$folder' AND uid='{$daddyobb->user['uid']}'", array('limit_start' => $start, 'limit' => $perpage));
		while($row = $db->fetch_array($users_query))
		{
			$recipients = unserialize($row['recipients']);
			if(is_array($recipients['to']) && count($recipients['to']))
			{
				$get_users = array_merge($get_users, $recipients['to']);
			}
			
			if(is_array($recipients['bcc']) && count($recipients['bcc']))
			{
				$get_users = array_merge($get_users, $recipients['bcc']);
			}
		}
		
		$get_users = implode(',', array_unique($get_users));
		
		// Grab info
		if($get_users)
		{
			$users_query = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid IN ({$get_users})");
			while($user = $db->fetch_array($users_query))
			{
				$cached_users[$user['uid']] = $user;
			}
		}
	}
	
	$query = $db->query("
		SELECT pm.*, fu.username AS fromusername, tu.username as tousername
		FROM ".TABLE_PREFIX."privatemessages pm
		LEFT JOIN ".TABLE_PREFIX."users fu ON (fu.uid=pm.fromid)
		LEFT JOIN ".TABLE_PREFIX."users tu ON (tu.uid=pm.toid)
		WHERE pm.folder='$folder' AND pm.uid='".$daddyobb->user['uid']."'
		ORDER BY pm.dateline DESC
		LIMIT $start, $perpage
	");
		
	if($db->num_rows($query) > 0)
	{
		while($message = $db->fetch_array($query))
		{
			$msgalt = $msgsuffix = $msgprefix = '';
			// Determine Folder Icon
			if($message['status'] == 0)
			{
				$msgfolder = 'new_pm.gif';
				$msgalt = $lang->new_pm;
				$msgprefix = "<strong>";
				$msgsuffix = "</strong>";
			}
			elseif($message['status'] == 1)
			{
				$msgfolder = 'old_pm.gif';
				$msgalt = $lang->old_pm;
			}
			elseif($message['status'] == 3)
			{
				$msgfolder = 're_pm.gif';
				$msgalt = $lang->reply_pm;
			}
			elseif($message['status'] == 4)
			{
				$msgfolder = 'fw_pm.gif';
				$msgalt = $lang->fwd_pm;
			}
			
			if($folder == 2 || $folder == 3)
			{ // Sent Items or Drafts Folder Check
				$recipients = unserialize($message['recipients']);
				$to_users = $bcc_users = '';
				if(count($recipients['to']) > 1 || (count($recipients['to']) == 1 && count($recipients['bcc']) > 0))
				{
					foreach($recipients['to'] as $uid)
					{
						$profilelink = get_profile_link($uid);
						$user = $cached_users[$uid];
						$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
						eval("\$to_users .= \"".$templates->get("private_multiple_recipients_user")."\";"); 
					}
					if(is_array($recipients['bcc']) && count($recipients['bcc']))
					{
						eval("\$bcc_users = \"".$templates->get("private_multiple_recipients_bcc")."\";");
						foreach($recipients['bcc'] as $uid)
						{
							$profilelink = get_profile_link($uid);
							$user = $cached_users[$uid];
							$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
							eval("\$bcc_users .= \"".$templates->get("private_multiple_recipients_user")."\";"); 
						}
					}
					
					eval("\$tofromusername = \"".$templates->get("private_multiple_recipients")."\";");
				}
				else if($message['toid'])
				{
					$tofromusername = $message['tousername'];
					$tofromuid = $message['toid'];
				}
				else
				{
					$tofromusername = $lang->not_sent;
				}
			}
			else
			{
				$tofromusername = $message['fromusername'];
				$tofromuid = $message['fromid'];
				if($tofromuid == 0)
				{
					$tofromusername = 'DaddyoBB Engine';
				}
			}
			
			$tofromusername = build_profile_link($tofromusername, $tofromuid);
			
			if($daddyobb->usergroup['cantrackpms'] == 1 && $daddyobb->usergroup['candenypmreceipts'] == 1 && $message['receipt'] == '1' && $message['folder'] != '3' && $message['folder'] != 2)
			{
				eval("\$denyreceipt = \"".$templates->get("private_messagebit_denyreceipt")."\";");
			}
			else
			{
				$denyreceipt = '';
			}
			
			if($message['icon'] > 0 && $icon_cache[$message['icon']])
			{
				$icon = $icon_cache[$message['icon']];
				$icon = "<img src=\"{$icon['path']}\" alt=\"{$icon['name']}\" valign=\"middle\" align=\"center\" />&nbsp;";
			}
			else
			{
				$icon = '';
			}
			
			if(!trim($message['subject']))
			{
				$message['subject'] = $lang->pm_no_subject;
			}

			$message['subject'] = htmlspecialchars_uni($parser->parse_badwords($message['subject']));
			if($message['folder'] != "3")
			{
				$sendpmdate = my_date($daddyobb->settings['dateformat'], $message['dateline']);
				$sendpmtime = my_date($daddyobb->settings['timeformat'], $message['dateline']);
			}
			else
			{
				$senddate = $lang->not_sent;
			}
			eval("\$messagelist .= \"".$templates->get("private_messagebit")."\";");
		}
	}
	else
	{
		eval("\$messagelist .= \"".$templates->get("private_nomessages")."\";");
	}

	if($daddyobb->usergroup['pmquota'] != '0' && $daddyobb->usergroup['cancp'] != '0')
	{
		$query = $db->simple_select("privatemessages", "COUNT(*) AS total", "uid='".$daddyobb->user['uid']."'");
		$pmscount = $db->fetch_array($query);
		if($pmscount['total'] == 0)
		{
			$spaceused = 0;
		}
		else
		{
			$spaceused = $pmscount['total'] / $daddyobb->usergroup['pmquota'] * 100;
		}
		$spaceused2 = 100 - $spaceused;
		
		$pmslast = $daddyobb->usergroup['pmquota'] - $pmscount['total'];
		
		//Format the language strings
		$lang->space_used = $lang->sprintf($lang->space_used, $pmscount['total'], $spaceused);
		$lang->free_space = $lang->sprintf($lang->free_space, $pmslast, $spaceused2);
		
	}
	
	if($daddyobb->usergroup['pmquota'] != "0" && $pmscount['total'] >= $daddyobb->usergroup['pmquota'] && $daddyobb->usergroup['cancp'] != 1)
	{
		eval("\$limitwarning = \"".$templates->get("private_limitwarning")."\";");
	}
	
	eval("\$folder = \"".$templates->get("private")."\";");
	$plugins->run_hooks("private_end");
	output_page($folder);
}
?>