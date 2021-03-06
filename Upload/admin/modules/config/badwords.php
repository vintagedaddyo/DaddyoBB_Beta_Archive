<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:13 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->bad_words, "index.php?module=config/badwords");

$plugins->run_hooks("admin_config_badwords_begin");

if($daddyobb->input['action'] == "add" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("admin_config_badwords_add");
	
	if(!trim($daddyobb->input['badword']))
	{
		$errors[] = $lang->error_missing_bad_word;
	}

	if(!$errors)
	{
		$new_badword = array(
			"badword" => $db->escape_string($daddyobb->input['badword']),
			"replacement" => $db->escape_string($daddyobb->input['replacement'])
		);

		$bid = $db->insert_query("badwords", $new_badword);
		
		$plugins->run_hooks("admin_config_badwords_add_commit");

		// Log admin action
		log_admin_action($bid, $daddyobb->input['badword']);

		$cache->update_badwords();
		flash_message($lang->success_added_bad_word, 'success');
		admin_redirect("index.php?module=config/badwords");
	}
	else
	{
		$daddyobb->input['action'] = '';
	}
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_config_badwords_delete");
	
	$query = $db->simple_select("badwords", "*", "bid='".intval($daddyobb->input['bid'])."'");
	$badword = $db->fetch_array($query);
	
	// Does the bad word not exist?
	if(!$badword['bid'])
	{
		flash_message($lang->error_invalid_bid, 'error');
		admin_redirect("index.php?module=config/badwords");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=config/badwords");
	}

	if($daddyobb->request_method == "post")
	{
		// Delete the bad word
		$db->delete_query("badwords", "bid='{$badword['bid']}'");
		
		$plugins->run_hooks("admin_config_badwords_delete_commit");

		// Log admin action
		log_admin_action($badword['bid'], $badword['badword']);

		$cache->update_badwords();

		flash_message($lang->success_deleted_bad_word, 'success');
		admin_redirect("index.php?module=config/badwords");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config/badwords&action=delete&bid={$badword['bid']}", $lang->confirm_bad_word_deletion);
	}
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_config_badwords_edit");
	
	$query = $db->simple_select("badwords", "*", "bid='".intval($daddyobb->input['bid'])."'");
	$badword = $db->fetch_array($query);
	
	// Does the bad word not exist?
	if(!$badword['bid'])
	{
		flash_message($lang->error_invalid_bid, 'error');
		admin_redirect("index.php?module=config/badwords");
	}

	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['badword']))
		{
			$errors[] = $lang->error_missing_bad_word;
		}

		if(!$errors)
		{
			$updated_badword = array(
				"badword" => $db->escape_string($daddyobb->input['badword']),
				"replacement" => $db->escape_string($daddyobb->input['replacement'])
			);

			$db->update_query("badwords", $updated_badword, "bid='{$badword['bid']}'");
			
			$plugins->run_hooks("admin_config_badwords_edit_commit");

			// Log admin action
			log_admin_action($badword['bid'], $daddyobb->input['badword']);

			$cache->update_badwords();

			flash_message($lang->success_updated_bad_word, 'success');
			admin_redirect("index.php?module=config/badwords");
		}
	}

	$page->add_breadcrumb_item($lang->edit_bad_word);
	$page->output_header($lang->bad_words." - ".$lang->edit_bad_word);
	
	$form = new Form("index.php?module=config/badwords&amp;action=edit&amp;bid={$badword['bid']}", "post");

	if($errors)
	{
		$page->output_inline_error($errors);
		$badword_data = $daddyobb->input;
	}
	else
	{
		$badword_data = $badword;
	}

	$form_container = new FormContainer($lang->edit_bad_word);
	$form_container->output_row($lang->bad_word." <em>*</em>", $lang->bad_word_desc, $form->generate_text_box('badword', $badword_data['badword'], array('id' => 'badword')), 'badword');
	$form_container->output_row($lang->replacement, $lang->replacement_desc, $form->generate_text_box('replacement', $badword_data['replacement'], array('id' => 'replacement')), 'replacement');
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->save_bad_word);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_config_badwords_start");
	
	$page->output_header($lang->bad_words);

	$sub_tabs['badwords'] = array(
		'title' => $lang->bad_word_filters,
		'description' => $lang->bad_word_filters_desc,
		'link' => "index.php?module=config/badwords"
	);

	$page->output_nav_tabs($sub_tabs, "badwords");

	$table = new Table;
	$table->construct_header($lang->bad_word);
	$table->construct_header($lang->replacement, array("width" => "50%"));
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150, "colspan" => 2));

	$query = $db->simple_select("badwords", "*", "", array("order_by" => "badword", "order_dir" => "asc"));
	while($badword = $db->fetch_array($query))
	{
		$badword['badword'] = htmlspecialchars_uni($badword['badword']);
		$badword['replacement'] = htmlspecialchars_uni($badword['replacement']);
		if(!$badword['replacement'])
		{
			$badword['replacement'] = '*****';
		}
		$table->construct_cell($badword['badword']);
		$table->construct_cell($badword['replacement']);
		$table->construct_cell("<a href=\"index.php?module=config/badwords&amp;action=edit&amp;bid={$badword['bid']}\">{$lang->edit}</a>", array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config/badwords&amp;action=delete&amp;bid={$badword['bid']}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_bad_word_deletion}');\">{$lang->delete}</a>", array("class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_bad_words, array("colspan" => 4));
		$table->construct_row();
	}
	
	$table->output($lang->bad_word_filters);

	$form = new Form("index.php?module=config/badwords&amp;action=add", "post", "add");
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	$form_container = new FormContainer($lang->add_bad_word);
	$form_container->output_row($lang->bad_word." <em>*</em>", $lang->bad_word_desc, $form->generate_text_box('badword', $daddyobb->input['badword'], array('id' => 'badword')), 'badword');
	$form_container->output_row($lang->replacement, $lang->replacement_desc, $form->generate_text_box('replacement', $daddyobb->input['replacement'], array('id' => 'replacement')), 'replacement');
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->save_bad_word);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

?>