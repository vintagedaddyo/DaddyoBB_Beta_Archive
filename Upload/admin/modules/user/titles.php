<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:19 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->user_titles, "index.php?module=user/titles");

if($daddyobb->input['action'] == "add" || !$daddyobb->input['action'])
{
	$sub_tabs['manage_titles'] = array(
		'title' => $lang->user_titles,
		'link' => "index.php?module=user/titles",
		'description' => $lang->user_titles_desc
	);
	$sub_tabs['add_title'] = array(
		'title' => $lang->add_new_user_title,
		'link' => "index.php?module=user/titles&amp;action=add",
		'description' => $lang->add_new_user_title_desc
	);
}

$plugins->run_hooks("admin_user_titles_begin");

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_user_titles_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!isset($daddyobb->input['posts']))
		{
			$errors[] = $lang->error_missing_posts;
		}

		if(!$errors)
		{
			$new_title = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"posts" => intval($daddyobb->input['posts']),
				"stars" => intval($daddyobb->input['stars']),
				"starimage" => $db->escape_string($daddyobb->input['starimage'])
			);
			
			$utid = $db->insert_query("usertitles", $new_title);
			
			$cache->update_usertitles();
			
			$plugins->run_hooks("admin_user_titles_add_commit");

			// Log admin action
			log_admin_action($utid, $daddyobb->input['title'], $daddyobb->input['posts']);

			flash_message($lang->success_user_title_created, 'success');
			admin_redirect("index.php?module=user/titles");
		}
	}
	else
	{
		$daddyobb->input = array(
			"stars" => "1",
			"starimage" => "star.gif",
		);
	}
	
	$page->add_breadcrumb_item($lang->add_new_user_title);
	$page->output_header($lang->user_titles." - ".$lang->add_new_user_title);
	
	$page->output_nav_tabs($sub_tabs, 'add_title');
	$form = new Form("index.php?module=user/titles&amp;action=add", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->add_new_user_title);
	$form_container->output_row($lang->title_to_assign."<em>*</em>", $lang->title_to_assign_desc, $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->minimum_posts, $lang->minimum_posts_desc, $form->generate_text_box('posts', $daddyobb->input['posts'], array('id' => 'posts')), 'posts');
	$form_container->output_row($lang->number_of_stars, $lang->number_of_stars_desc, $form->generate_text_box('stars', $daddyobb->input['stars'], array('id' => 'stars')), 'stars');
	$form_container->output_row($lang->star_image, $lang->star_image_desc, $form->generate_text_box('starimage', $daddyobb->input['starimage'], array('id' => 'starimage')), 'starimage');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_user_title);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_user_titles_edit");
	
	$query = $db->simple_select("usertitles", "*", "utid='".intval($daddyobb->input['utid'])."'");
	$usertitle = $db->fetch_array($query);

	if(!$usertitle['utid'])
	{
		flash_message($lang->error_invalid_user_title, 'error');
		admin_redirect("index.php?module=user/titles");
	}

	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!isset($daddyobb->input['posts']))
		{
			$errors[] = $lang->error_missing_posts;
		}

		if(!$errors)
		{
			$updated_title = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"posts" => intval($daddyobb->input['posts']),
				"stars" => intval($daddyobb->input['stars']),
				"starimage" => $db->escape_string($daddyobb->input['starimage'])
			);
			
			$db->update_query("usertitles", $updated_title, "utid='{$usertitle['utid']}'");
			
			$cache->update_usertitles();
			
			$plugins->run_hooks("admin_user_titles_edit_commit");

			// Log admin action
			log_admin_action($usertitle['utid'], $daddyobb->input['title'], $daddyobb->input['posts']);
			
			flash_message($lang->success_user_title_updated, 'success');
			admin_redirect("index.php?module=user/titles");
		}
	}

	$page->add_breadcrumb_item($lang->edit_user_title);
	$page->output_header($lang->user_titles." - ".$lang->edit_user_title);
	
	$sub_tabs['edit_title'] = array(
		'title' => $lang->edit_user_title,
		'link' => "index.php?module=user/titles&amp;action=edit&amp;uid=".$daddyobb->input['uid'],
		'description' => $lang->edit_user_title_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_title');
	$form = new Form("index.php?module=user/titles&amp;action=edit&amp;utid={$usertitle['utid']}", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input = $usertitle;
	}

	$form_container = new FormContainer($lang->edit_user_title);
	$form_container->output_row($lang->title_to_assign."<em>*</em>", $lang->title_to_assign_desc, $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->minimum_posts, $lang->minimum_posts_desc, $form->generate_text_box('posts', $daddyobb->input['posts'], array('id' => 'posts')), 'posts');
	$form_container->output_row($lang->number_of_stars, $lang->number_of_stars_desc, $form->generate_text_box('stars', $daddyobb->input['stars'], array('id' => 'stars')), 'stars');
	$form_container->output_row($lang->star_image, $lang->star_image_desc, $form->generate_text_box('starimage', $daddyobb->input['starimage'], array('id' => 'starimage')), 'starimage');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_user_title);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();

}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_user_titles_delete");
	
	$query = $db->simple_select("usertitles", "*", "utid='".intval($daddyobb->input['utid'])."'");
	$usertitle = $db->fetch_array($query);

	if(!$usertitle['utid'])
	{
		flash_message($lang->error_invalid_user_title, 'error');
		admin_redirect("index.php?module=user/titles");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=user/titles");
	}

	if($daddyobb->request_method == "post")
	{
		$db->delete_query("usertitles", "utid='{$usertitle['utid']}'");
		
		$plugins->run_hooks("admin_user_titles_delete_commit");

		// Log admin action
		log_admin_action($usertitle['utid'], $usertitle['title'], $usertitle['posts']);

		flash_message($lang->success_user_title_deleted, 'success');
		admin_redirect("index.php?module=user/titles");
	}
	else
	{
		$page->output_confirm_action("index.php?module=user/titles&amp;action=delete&amp;utid={$usertitle['utid']}", $lang->user_title_deletion_confirmation);
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_user_titles_start");
	
	$page->output_header($lang->manage_user_titles);

	$page->output_nav_tabs($sub_tabs, 'manage_titles');

	$table = new Table;
	$table->construct_header($lang->user_title);
	$table->construct_header($lang->minimum_posts, array('width' => '130', 'class' => 'align_center'));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2, "width" => 200));
	
	$query = $db->simple_select("usertitles", "*", "", array('order_by' => 'posts'));
	while($usertitle = $db->fetch_array($query))
	{
		$usertitle['title'] = htmlspecialchars_uni($usertitle['title']);
		$table->construct_cell("<a href=\"index.php?module=user/titles&amp;action=edit&amp;utid={$usertitle['utid']}\"><strong>{$usertitle['title']}</strong></a>");
		$table->construct_cell($usertitle['posts'], array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=user/titles&amp;action=edit&amp;utid={$usertitle['utid']}\">{$lang->edit}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=user/titles&amp;action=delete&amp;utid={$usertitle['utid']}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->user_title_deletion_confirmation}')\">{$lang->delete}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_user_titles, array('colspan' => 4));
		$table->construct_row();
		$no_results = true;
	}
	
	$table->output($lang->manage_user_titles);

	$page->output_footer();
}
?>