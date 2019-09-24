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
define('THIS_SCRIPT', 'report.php');

$templatelist = "report,email_reportpost,emailsubject_reportpost,report_thanks";
require_once "./global.php";

// Load global language phrases
$lang->load("report");

if($daddyobb->usergroup['canview'] == 0 || !$daddyobb->user['uid'])
{
	error_no_permission();
}

if($daddyobb->input['action'] != "do_report")
{
	$daddyobb->input['action'] = "report";
}

$post = get_post($daddyobb->input['pid']);

if(!$post['pid'])
{
	$error = $lang->error_invalidpost;
	eval("\$report_error = \"".$templates->get("report_error")."\";");
	output_page($report_error);
	exit;
}


$forum = get_forum($post['fid']);
if(!$forum)
{
	$error = $lang->error_invalidforum;
	eval("\$report_error = \"".$templates->get("report_error")."\";");
	output_page($report_error);
	exit;
}

// Password protected forums ......... yhummmmy!
check_forum_password($forum['parentlist']);

$thread = get_thread($post['tid']);

if($daddyobb->input['action'] == "report")
{
	$plugins->run_hooks("report_start");
	$pid = $daddyobb->input['pid'];
	eval("\$report = \"".$templates->get("report")."\";");
	$plugins->run_hooks("report_end");
	output_page($report);
}
elseif($daddyobb->input['action'] == "do_report" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("report_do_report_start");
	if(!trim($daddyobb->input['reason']))
	{
		eval("\$report = \"".$templates->get("report_noreason")."\";");
		output_page($report);
		exit;
	}
	
	if($daddyobb->settings['reportmethod'] == "email" || $daddyobb->settings['reportmethod'] == "pms")
	{
		$query = $db->query("
			SELECT DISTINCT u.username, u.email, u.receivepms, u.uid
			FROM ".TABLE_PREFIX."moderators m
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=m.uid)
			WHERE m.fid IN (".$forum['parentlist'].")
		");
		$nummods = $db->num_rows($query);
		if(!$nummods)
		{
			unset($query);
			switch($db->type)
			{
				case "pgsql":
				case "sqlite3":
				case "sqlite2":
					$query = $db->query("
						SELECT u.username, u.email, u.receivepms, u.uid
						FROM ".TABLE_PREFIX."users u
						LEFT JOIN ".TABLE_PREFIX."usergroups g ON (((CONCAT(','|| u.additionalgroups|| ',') LIKE CONCAT('%,'|| g.gid|| ',%')) OR u.usergroup = g.gid))
						WHERE (g.cancp=1 OR g.issupermod=1)
					");
					break;
				default:
					$query = $db->query("
						SELECT u.username, u.email, u.receivepms, u.uid
						FROM ".TABLE_PREFIX."users u
						LEFT JOIN ".TABLE_PREFIX."usergroups g ON (((CONCAT(',', u.additionalgroups, ',') LIKE CONCAT('%,', g.gid, ',%')) OR u.usergroup = g.gid))
						WHERE (g.cancp=1 OR g.issupermod=1)
					");
			}
		}
		
		while($mod = $db->fetch_array($query))
		{
			$emailsubject = $lang->sprintf($lang->emailsubject_reportpost, $daddyobb->settings['bbname']);
			$emailmessage = $lang->sprintf($lang->email_reportpost, $daddyobb->user['username'], $daddyobb->settings['bbname'], $post['subject'], $daddyobb->settings['bburl'], str_replace('&amp;', '&', get_post_link($post['pid'], $thread['tid'])), $thread['subject'], $daddyobb->input['reason']);
			
			if($daddyobb->settings['reportmethod'] == "pms" && $mod['receivepms'] != 0 && $daddyobb->settings['enablepms'] != 0)
			{
				$pm_recipients[] = $mod['uid'];
			}
			else
			{
				my_mail($mod['email'], $emailsubject, $emailmessage);
			}
		}

		if(count($pm_recipients) > 0)
		{
			$emailsubject = $lang->sprintf($lang->emailsubject_reportpost, $daddyobb->settings['bbname']);
			$emailmessage = $lang->sprintf($lang->email_reportpost, $daddyobb->user['username'], $daddyobb->settings['bbname'], $post['subject'], $daddyobb->settings['bburl'], str_replace('&amp;', '&', get_post_link($post['pid'], $thread['tid'])), $thread['subject'], $daddyobb->input['reason']);

			require_once DADDYOBB_ROOT."inc/datahandlers/pm.php";
			$pmhandler = new PMDataHandler();

			$pm = array(
				"subject" => $emailsubject,
				"message" => $emailmessage,
				"icon" => 0,
				"fromid" => 0,
				"toid" => $pm_recipients
			);

			$pmhandler->admin_override = true;
			$pmhandler->set_data($pm);

			// Now let the pm handler do all the hard work.
			if(!$pmhandler->validate_pm())
			{
				// Force it to valid to just get it out of here
				$pmhandler->is_validated = true;
				$pmhandler->errors = array();
			}
			$pminfo = $pmhandler->insert_pm();
		}
	}
	else
	{
		$reportedpost = array(
			"pid" => intval($daddyobb->input['pid']),
			"tid" => $thread['tid'],
			"fid" => $thread['fid'],
			"uid" => $daddyobb->user['uid'],
			"dateline" => TIME_NOW,
			"reportstatus" => 0,
			"reason" => $db->escape_string(htmlspecialchars_uni($daddyobb->input['reason']))
		);
		$db->insert_query("reportedposts", $reportedpost);
		$cache->update_reportedposts();
	}
	eval("\$report = \"".$templates->get("report_thanks")."\";");
	$plugins->run_hooks("report_do_report_end");
	output_page($report);
}
?>