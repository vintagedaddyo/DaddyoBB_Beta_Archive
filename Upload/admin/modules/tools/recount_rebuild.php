<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:18 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->recount_rebuild, "index.php?module=tools/recount_rebuild");

$plugins->run_hooks("admin_tools_recount_rebuild");

function acp_rebuild_forum_counters()
{	
	global $db, $daddyobb, $lang;
	
	$query = $db->simple_select("forums", "COUNT(*) as num_forums");
	$num_forums = $db->fetch_field($query, 'num_forums');
	
	$page = intval($daddyobb->input['page']);
	$per_page = intval($daddyobb->input['forumcounters']);
	$start = ($page-1) * $per_page;
	$end = $start + $per_page;

	$query = $db->simple_select("forums", "fid", '', array('order_by' => 'fid', 'order_dir' => 'asc', 'limit_start' => $start, 'limit' => $per_page));
	while($forum = $db->fetch_array($query))
	{
		$update['parentlist'] = make_parent_list($forum['fid']);
		$db->update_query("forums", $update, "fid='{$forum['fid']}'");
		rebuild_forum_counters($forum['fid']);
	}
	
	check_proceed($num_forums, $end, ++$page, $per_page, "forumcounters", "do_rebuildforumcounters", $lang->success_rebuilt_forum_counters);
}

function acp_rebuild_thread_counters()
{	
	global $db, $daddyobb, $lang;
	
	$query = $db->simple_select("threads", "COUNT(*) as num_threads");
	$num_threads = $db->fetch_field($query, 'num_threads');
	
	$page = intval($daddyobb->input['page']);
	$per_page = intval($daddyobb->input['threadcounters']);
	$start = ($page-1) * $per_page;
	$end = $start + $per_page;

	$query = $db->simple_select("threads", "tid", '', array('order_by' => 'tid', 'order_dir' => 'asc', 'limit_start' => $start, 'limit' => $per_page));
	while($thread = $db->fetch_array($query))
	{
		rebuild_thread_counters($thread['tid']);
	}
	
	check_proceed($num_threads, $end, ++$page, $per_page, "threadcounters", "do_rebuildthreadcounters", $lang->success_rebuilt_thread_counters);
}

function acp_recount_user_posts()
{
	global $db, $daddyobb, $lang;
	
	$query = $db->simple_select("users", "COUNT(uid) as num_users");
	$num_users = $db->fetch_field($query, 'num_users');
	
	$page = intval($daddyobb->input['page']);
	$per_page = intval($daddyobb->input['userposts']);
	$start = ($page-1) * $per_page;
	$end = $start + $per_page;
	
	$query = $db->simple_select("forums", "fid", "usepostcounts = 0");
	while($forum = $db->fetch_array($query))
	{
		$fids[] = $forum['fid'];
	}
	if(is_array($fids))
    {
        $fids = implode(',', $fids);
    }
	if($fids)
	{
		$fids = " AND FID NOT IN($fids)";
	}
	else
	{
		$fids = "";
	}
	
	$query = $db->simple_select("users", "uid", '', array('order_by' => 'uid', 'order_dir' => 'asc', 'limit_start' => $start, 'limit' => $per_page));
	
	while($user = $db->fetch_array($query))
	{
		$query2 = $db->simple_select("posts", "COUNT(pid) AS post_count", "uid='{$user['uid']}' AND visible > 0{$fids}");
		$num_posts = $db->fetch_field($query2, "post_count");
		$db->update_query("users", array("postnum" => intval($num_posts)), "uid='{$user['uid']}'");
	}
	
	check_proceed($num_users, $end, ++$page, $per_page, "userposts", "do_recountuserposts", $lang->success_rebuilt_user_counters);
}

function acp_rebuild_attachment_thumbnails()
{
	global $db, $daddyobb, $lang;
	
	$query = $db->simple_select("attachments", "COUNT(aid) as num_attachments");
	$num_attachments = $db->fetch_field($query, 'num_attachments');
	
	$page = intval($daddyobb->input['page']);
	$per_page = intval($daddyobb->input['attachmentthumbs']);
	$start = ($page-1) * $per_page;
	$end = $start + $per_page;

	require_once DADDYOBB_ROOT."inc/functions_image.php";
	
	$query = $db->simple_select("attachments", "*", '', array('order_by' => 'aid', 'order_dir' => 'asc', 'limit_start' => $start, 'limit' => $per_page));
	while($attachment = $db->fetch_array($query))
	{
		$ext = my_strtolower(my_substr(strrchr($attachment['filename'], "."), 1));
		if($ext == "gif" || $ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "jpe")
		{
			$thumbname = str_replace(".attach", "_thumb.$ext", $attachment['attachname']);
			$thumbnail = generate_thumbnail(DADDYOBB_ROOT."uploads/".$attachment['attachname'], DADDYOBB_ROOT."uploads/", $thumbname, $daddyobb->settings['attachthumbh'], $daddyobb->settings['attachthumbw']);
			if($thumbnail['code'] == 4)
			{
				$thumbnail['filename'] = "SMALL";
			}
			$db->update_query("attachments", array("thumbnail" => $thumbnail['filename']), "aid='{$attachment['aid']}'");
		}
	}
	
	check_proceed($num_attachments, $end, ++$page, $per_page, "attachmentthumbs", "do_rebuildattachmentthumbs", $lang->success_rebuilt_attachment_thumbnails);
}

function check_proceed($current, $finish, $next_page, $per_page, $name, $name2, $message)
{
	global $page, $lang, $plugins;
	
	if($finish >= $current)
	{
		flash_message($message, 'success');
		admin_redirect("index.php?module=tools/recount_rebuild");
	}
	else
	{
		$page->output_header();
		
		$form = new Form("index.php?module=tools/recount_rebuild", 'post');
		
		echo $form->generate_hidden_field("page", $next_page);
		echo $form->generate_hidden_field($name, $per_page);
		echo $form->generate_hidden_field($name2, $lang->go);
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->confirm_proceed_rebuild}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->proceed, array('class' => 'button_yes'));
		echo "</p>\n";
		echo "</div>\n";
		
		$form->end();
		
		$page->output_footer();
		exit;
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_tools_recount_rebuild_start");
	
	if($daddyobb->request_method == "post")
	{
		require_once DADDYOBB_ROOT."inc/functions_rebuild.php";
		
		if(!isset($daddyobb->input['page']) || intval($daddyobb->input['page']) < 1)
		{
			$daddyobb->input['page'] = 1;
		}
		
		if(isset($daddyobb->input['do_rebuildforumcounters']))
		{
			$plugins->run_hooks("admin_tools_recount_rebuild_forum_counters");
			
			if($daddyobb->input['page'] == 1)
			{
				// Log admin action
				log_admin_action("forum");
			}
			if(!intval($daddyobb->input['forumcounters']))
			{
				$daddyobb->input['forumcounters'] = 50;
			}
			
			acp_rebuild_forum_counters();
		}
		elseif(isset($daddyobb->input['do_rebuildthreadcounters']))
		{
			$plugins->run_hooks("admin_tools_recount_rebuild_thread_counters");
			
			if($daddyobb->input['page'] == 1)
			{
				// Log admin action
				log_admin_action("thread");
			}
			if(!intval($daddyobb->input['threadcounters']))
			{
				$daddyobb->input['threadcounters'] = 500;
			}
			
			acp_rebuild_thread_counters();
		}
		elseif(isset($daddyobb->input['do_recountuserposts']))
		{
			$plugins->run_hooks("admin_tools_recount_rebuild_user_posts");
			
			if($daddyobb->input['page'] == 1)
			{
				// Log admin action
				log_admin_action("userposts");
			}
			if(!intval($daddyobb->input['userposts']))
			{
				$daddyobb->input['userposts'] = 500;
			}
			
			acp_recount_user_posts();
		}
		elseif(isset($daddyobb->input['do_rebuildattachmentthumbs']))
		{
			$plugins->run_hooks("admin_tools_recount_rebuild_forum_counters");
			
			if($daddyobb->input['page'] == 1)
			{
				// Log admin action
				log_admin_action("attachmentthumbs");
			}
			
			if(!intval($daddyobb->input['attachmentthumbs']))
			{
				$daddyobb->input['attachmentthumbs'] = 500;
			}
			
			acp_rebuild_attachment_thumbnails();
		}
		else
		{
			$cache->update_stats();
			
			$plugins->run_hooks("admin_tools_recount_rebuild_stats");
			
			// Log admin action
			log_admin_action("stats");

			flash_message($lang->success_rebuilt_forum_stats, 'success');
			admin_redirect("index.php?module=tools/recount_rebuild");
		}
	}	
	
	$page->output_header($lang->recount_rebuild);
	
	$sub_tabs['recount_rebuild'] = array(
		'title' => $lang->recount_rebuild,
		'link' => "index.php?module=tools/recount_rebuild",
		'description' => $lang->recount_rebuild_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'recount_rebuild');

	$form = new Form("index.php?module=tools/recount_rebuild", "post");
	
	$form_container = new FormContainer($lang->recount_rebuild);
	$form_container->output_row_header($lang->name);
	$form_container->output_row_header($lang->data_per_page, array('width' => 50));
	$form_container->output_row_header("&nbsp;");
	
	$form_container->output_cell("<label>{$lang->recount_stats}</label><div class=\"description\">{$lang->recount_stats_desc}</div>");
	$form_container->output_cell($lang->na);
	$form_container->output_cell($form->generate_submit_button($lang->go, array("name" => "do_recountstats")));
	$form_container->construct_row();
	
	$form_container->output_cell("<label>{$lang->rebuild_forum_counters}</label><div class=\"description\">{$lang->rebuild_forum_counters_desc}</div>");
	$form_container->output_cell($form->generate_text_box("forumcounters", 50, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button($lang->go, array("name" => "do_rebuildforumcounters")));
	$form_container->construct_row();
	
	$form_container->output_cell("<label>{$lang->rebuild_thread_counters}</label><div class=\"description\">{$lang->rebuild_thread_counters_desc}</div>");
	$form_container->output_cell($form->generate_text_box("threadcounters", 500, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button($lang->go, array("name" => "do_rebuildthreadcounters")));
	$form_container->construct_row();
	
	$form_container->output_cell("<label>{$lang->recount_user_posts}</label><div class=\"description\">{$lang->recount_user_posts_desc}</div>");
	$form_container->output_cell($form->generate_text_box("userposts", 500, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button($lang->go, array("name" => "do_recountuserposts")));
	$form_container->construct_row();
	
	$form_container->output_cell("<label>{$lang->rebuild_attachment_thumbs}</label><div class=\"description\">{$lang->rebuild_attachment_thumbs_desc}</div>");
	$form_container->output_cell($form->generate_text_box("attachmentthumbs", 20, array('style' => 'width: 150px;')));
	$form_container->output_cell($form->generate_submit_button($lang->go, array("name" => "do_rebuildattachmentthumbs")));
	$form_container->construct_row();
	
	$form_container->end();

	$form->end();
		
	$page->output_footer();
}

?>