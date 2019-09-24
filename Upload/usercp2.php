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

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'usercp2.php');

$templatelist = 'usercp_nav_messenger,usercp_nav_changename,usercp_nav_profile,usercp_nav_misc,usercp_nav';

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";

if($daddyobb->user['uid'] == 0)
{
	error_no_permission();
}

$lang->load("userbase");

usercp_menu();

$server_http_referer = htmlentities($_SERVER['HTTP_REFERER']);

if($daddyobb->input['action'] == "do_addsubscription")
{
	if($daddyobb->input['type'] != "forum")
	{
		$thread = get_thread($daddyobb->input['tid']);
		if(!$thread['tid'])
		{
			error($lang->error_invalidthread);
		}
		$forumpermissions = forum_permissions($thread['fid']);
		if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
		{
			error_no_permission();
		}
		add_subscribed_thread($thread['tid'], $daddyobb->input['notification']);
		if($daddyobb->input['referrer'])
		{
			$url = htmlspecialchars_uni(addslashes($daddyobb->input['referrer']));
		}
		else
		{
			$url = get_thread_link($thread['tid']);
		}
		redirect($url, $lang->redirect_subscriptionadded);
	}
}

if($daddyobb->input['action'] == "addsubscription")
{
	if($daddyobb->input['type'] == "forum")
	{
		$forum = get_forum($daddyobb->input['fid']);
		if(!$forum['fid'])
		{
			error($lang->error_invalidforum);
		}
		$forumpermissions = forum_permissions($forum['fid']);
		if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
		{
			error_no_permission();
		}
		add_subscribed_forum($forum['fid']);
		if($server_http_referer)
		{
			$url = $server_http_referer;
		}
		else
		{
			$url = "index.php";
		}
		redirect($url, $lang->redirect_forumsubscriptionadded);
	}
	else
	{
		$thread  = get_thread($daddyobb->input['tid']);
		if(!$thread['tid'])
		{
			error($lang->error_invalidthread);
		}
		add_breadcrumb($lang->nav_subthreads, "usercp.php?action=subscriptions");
		add_breadcrumb($lang->nav_addsubscription);

		$forumpermissions = forum_permissions($thread['fid']);
		if($forumpermissions['canview'] == 0 || $forumpermissions['canviewthreads'] == 0)
		{
			error_no_permission();
		}
		$referrer = '';
		if($server_http_referer)
		{
			$referrer = $server_http_referer;
		}

		require_once DADDYOBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		$thread['subject'] = $parser->parse_badwords($thread['subject']);
		$thread['subject'] = htmlspecialchars_uni($thread['subject']);
		$lang->subscribe_to_thread = $lang->sprintf($lang->subscribe_to_thread, $thread['subject']);

		if($daddyobb->user['subscriptionmethod'] == 1 || $daddyobb->user['subscriptionmethod'] == 0)
		{
			$notification_none_checked = "checked=\"checked\"";
		}
		else if($daddyobb->user['subscriptionmethod'] == 2)
		{
			$notification_instant_checked = "checked=\"checked\"";
		}
		eval("\$add_subscription = \"".$templates->get("usercp_addsubscription_thread")."\";");
		output_page($add_subscription);
	}
}
elseif($daddyobb->input['action'] == "removesubscription")
{
	if($daddyobb->input['type'] == "forum")
	{
		$forum = get_forum($daddyobb->input['fid']);
		if(!$forum['fid'])
		{
			error($lang->error_invalidforum);
		}
		remove_subscribed_forum($forum['fid']);
		if($server_http_referer)
		{
			$url = $server_http_referer;
		}
		else
		{
			$url = "usercp.php?action=forumsubscriptions";
		}
		redirect($url, $lang->redirect_forumsubscriptionremoved);
	}
	else
	{
		$thread = get_thread($daddyobb->input['tid']);
		if(!$thread['tid'])
		{
			error($lang->error_invalidthread);
		}
		remove_subscribed_thread($thread['tid']);
		if($server_http_referer)
		{
			$url = $server_http_referer;
		}
		else
		{
			$url = "usercp.php?action=subscriptions";
		}
		redirect($url, $lang->redirect_subscriptionremoved);
	}
}
elseif($daddyobb->input['action'] == "removesubscriptions")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);
	
	if($daddyobb->input['type'] == "forum")
	{
		$db->delete_query("forumsubscriptions", "uid='".$daddyobb->user['uid']."'");
		if($server_http_referer)
		{
			$url = $server_http_referer;
		}
		else
		{
			$url = "usercp.php?action=forumsubscriptions";
		}
		redirect($url, $lang->redirect_forumsubscriptionsremoved);
	}
	else
	{
		$db->delete_query("threadsubscriptions", "uid='".$daddyobb->user['uid']."'");
		if($server_http_referer)
		{
			$url = $server_http_referer;
		}
		else
		{
			$url = "usercp.php?action=subscriptions";
		}
		redirect($url, $lang->redirect_subscriptionsremoved);
	}
}
else
{
	error($lang->error_invalidaction);
}
?>