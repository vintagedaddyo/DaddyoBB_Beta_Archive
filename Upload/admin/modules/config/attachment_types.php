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

$page->add_breadcrumb_item($lang->attachment_types, "index.php?module=config/attachment_types");

$plugins->run_hooks("admin_config_attachment_types_begin");

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_config_attachment_types_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['mimetype']) && !trim($daddyobb->input['extension']))
		{
			$errors[] = $lang->error_missing_mime_type;
		}

		if(!trim($daddyobb->input['extension']) && !trim($daddyobb->input['mimetype']))
		{
			$errors[] = $lang->error_missing_extension;
		}

		if(!$errors)
		{
			if($daddyobb->input['mimetype'] == "images/attachtypes/")
			{
				$daddyobb->input['mimetype'] = '';
			}
			
			if($daddyobb->input['extension']{0} == '.')
			{
				$daddyobb->input['extension'] = substr($daddyobb->input['extension'], 1);
			}
			
			$maxsize = intval($daddyobb->input['maxsize']);
			
			if($maxsize == 0)
			{
				$maxsize = "";
			}

			$new_type = array(
				"mimetype" => $db->escape_string($daddyobb->input['mimetype']),
				"extension" => $db->escape_string($daddyobb->input['extension']),
				"maxsize" => $maxsize,
				"icon" => $db->escape_string($daddyobb->input['icon'])
			);

			$atid = $db->insert_query("attachtypes", $new_type);
			
			$plugins->run_hooks("admin_config_attachment_types_add_commit");

			// Log admin action
			log_admin_action($atid, $daddyobb->input['extension']);

			$cache->update_attachtypes();

			flash_message($lang->success_attachment_type_created, 'success');
			admin_redirect("index.php?module=config/attachment_types");
		}
	}

	
	$page->add_breadcrumb_item($lang->add_new_attachment_type);
	$page->output_header($lang->attachment_types." - ".$lang->add_new_attachment_type);
	
	$sub_tabs['attachment_types'] = array(
		'title' => $lang->attachment_types,
		'link' => "index.php?module=config/attachment_types"
	);
	
	$sub_tabs['add_attachment_type'] = array(
		'title' => $lang->add_new_attachment_type,
		'link' => "index.php?module=config/attachment_types&amp;action=add",
		'description' => $lang->add_attachment_type_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'add_attachment_type');

	$form = new Form("index.php?module=config/attachment_types&amp;action=add", "post", "add");
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['maxsize'] = '1024';
		$daddyobb->input['icon'] = "images/attachtypes/";
	}
	
	// PHP settings
	$upload_max_filesize = @ini_get('upload_max_filesize');
	$post_max_size = @ini_get('post_max_size');
	$limit_string = '';
	if($upload_max_filesize || $post_max_size)
	{
		$limit_string = '<br /><br />'.$lang->limit_intro;
		if($upload_max_filesize)
		{
			$limit_string .= '<br />'.$lang->sprintf($lang->limit_upload_max_filesize, $upload_max_filesize);
		}
		if($post_max_size)
		{
			$limit_string .= '<br />'.$lang->sprintf($lang->limit_post_max_size, $post_max_size);
		}
	}
	
	$form_container = new FormContainer($lang->add_new_attachment_type);
	$form_container->output_row($lang->file_extension." <em>*</em>", $lang->file_extension_desc, $form->generate_text_box('extension', $daddyobb->input['extension'], array('id' => 'extension')), 'extension');
	$form_container->output_row($lang->mime_type." <em>*</em>", $lang->mime_type_desc, $form->generate_text_box('mimetype', $daddyobb->input['mimetype'], array('id' => 'mimetype')), 'mimetype');
	$form_container->output_row($lang->maximum_file_size, $lang->maximum_file_size_desc.$limit_string, $form->generate_text_box('maxsize', $daddyobb->input['maxsize'], array('id' => 'maxsize')), 'maxsize');
	$form_container->output_row($lang->attachment_icon, $lang->attachment_icon_desc, $form->generate_text_box('icon', $daddyobb->input['icon'], array('id' => 'icon')), 'icon');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_attachment_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_config_attachment_types_edit");
	
	$query = $db->simple_select("attachtypes", "*", "atid='".intval($daddyobb->input['atid'])."'");
	$attachment_type = $db->fetch_array($query);
	
	if(!$attachment_type['atid'])
	{
		flash_message($lang->error_invalid_attachment_type, 'error');
		admin_redirect("index.php?module=config/attachment_types");
	}
		
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['mimetype']) && !trim($daddyobb->input['extension']))
		{
			$errors[] = $lang->error_missing_mime_type;
		}

		if(!trim($daddyobb->input['extension']) && !trim($daddyobb->input['mimetype']))
		{
			$errors[] = $lang->error_missing_extension;
		}

		if(!$errors)
		{
			if($daddyobb->input['mimetype'] == "images/attachtypes/")
			{
				$daddyobb->input['mimetype'] = '';
			}
			
			if($daddyobb->input['extension']{0} == '.')
			{
				$daddyobb->input['extension'] = substr($daddyobb->input['extension'], 1);
			}

			$updated_type = array(
				"mimetype" => $db->escape_string($daddyobb->input['mimetype']),
				"extension" => $db->escape_string($daddyobb->input['extension']),
				"maxsize" => intval($daddyobb->input['maxsize']),
				"icon" => $db->escape_string($daddyobb->input['icon'])
			);

			$db->update_query("attachtypes", $updated_type, "atid='{$attachment_type['atid']}'");
			
			$plugins->run_hooks("admin_config_attachment_types_edit_commit");

			// Log admin action
			log_admin_action($attachment_type['atid'], $daddyobb->input['extension']);

			$cache->update_attachtypes();

			flash_message($lang->success_attachment_type_updated, 'success');
			admin_redirect("index.php?module=config/attachment_types");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_attachment_type);
	$page->output_header($lang->attachment_types." - ".$lang->edit_attachment_type);
	
	$sub_tabs['edit_attachment_type'] = array(
		'title' => $lang->edit_attachment_type,
		'link' => "index.php?module=config/attachment_types&amp;action=edit&amp;atid={$attachment_type['atid']}",
		'description' => $lang->edit_attachment_type_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_attachment_type');

	$form = new Form("index.php?module=config/attachment_types&amp;action=edit&amp;atid={$attachment_type['atid']}", "post", "add");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input = $attachment_type;
	}
	
	// PHP settings
	$upload_max_filesize = @ini_get('upload_max_filesize');
	$post_max_size = @ini_get('post_max_size');
	$limit_string = '';
	if($upload_max_filesize || $post_max_size)
	{
		$limit_string = '<br /><br />'.$lang->limit_intro;
		if($upload_max_filesize)
		{
			$limit_string .= '<br />'.$lang->sprintf($lang->limit_upload_max_filesize, $upload_max_filesize);
		}
		if($post_max_size)
		{
			$limit_string .= '<br />'.$lang->sprintf($lang->limit_post_max_size, $post_max_size);
		}
	}
	
	$form_container = new FormContainer($lang->edit_attachment_type);
	$form_container->output_row($lang->file_extension." <em>*</em>", $lang->file_extension_desc, $form->generate_text_box('extension', $daddyobb->input['extension'], array('id' => 'extension')), 'extension');
	$form_container->output_row($lang->mime_type." <em>*</em>", $lang->mime_type_desc, $form->generate_text_box('mimetype', $daddyobb->input['mimetype'], array('id' => 'mimetype')), 'mimetype');
	$form_container->output_row($lang->maximum_file_size, $lang->maximum_file_size_desc.$limit_string, $form->generate_text_box('maxsize', $daddyobb->input['maxsize'], array('id' => 'maxsize')), 'maxsize');
	$form_container->output_row($lang->attachment_icon, $lang->attachment_icon_desc, $form->generate_text_box('icon', $daddyobb->input['icon'], array('id' => 'icon')), 'icon');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_attachment_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_config_attachment_types_delete");
	
	if($daddyobb->input['no']) 
	{ 
		admin_redirect("index.php?module=config/attachment_types"); 
	}
	
	$query = $db->simple_select("attachtypes", "*", "atid='".intval($daddyobb->input['atid'])."'");
	$attachment_type = $db->fetch_array($query);
	
	if(!$attachment_type['atid'])
	{
		flash_message($lang->error_invalid_attachment_type, 'error');
		admin_redirect("index.php?module=config/attachment_types");
	}
	
	if($daddyobb->request_method == "post")
	{
		$db->delete_query("attachtypes", "atid='{$attachment_type['atid']}'");

		$cache->update_attachtypes();
		
		$plugins->run_hooks("admin_config_attachment_types_delete_commit");

		// Log admin action
		log_admin_action($attachment_type['atid'], $attachment_type['extension']);

		flash_message($lang->success_attachment_type_deleted, 'success');
		admin_redirect("index.php?module=config/attachment_types");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config/attachment_types&amp;action=delete&amp;atid={$attachment_type['atid']}", $lang->confirm_attachment_type_deletion); 
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_config_attachment_types_start");
	
	$page->output_header($lang->attachment_types);

	$sub_tabs['attachment_types'] = array(
		'title' => $lang->attachment_types,
		'link' => "index.php?module=config/attachment_types",
		'description' => $lang->attachment_types_desc
	);
	$sub_tabs['add_attachment_type'] = array(
		'title' => $lang->add_new_attachment_type,
		'link' => "index.php?module=config/attachment_types&amp;action=add",
	);

	$page->output_nav_tabs($sub_tabs, 'attachment_types');
	
	$table = new Table;
	$table->construct_header($lang->extension, array("colspan" => 2));
	$table->construct_header($lang->mime_type);
	$table->construct_header($lang->maximum_size, array("class" => "align_center"));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2));
	
	$query = $db->simple_select("attachtypes", "*", "", array('order_by' => 'extension'));
	while($attachment_type = $db->fetch_array($query))
	{
		// Just show default icons in ACP		
		$attachment_type['icon'] = str_replace("{theme}", "images", $attachment_type['icon']);
		if(!$attachment_type['icon'] || $attachment_type['icon'] == "images/attachtypes/")
		{
			$attachment_type['icon'] = "&nbsp;";
		}
		else
		{
			$attachment_type['icon'] = "<img src=\"../{$attachment_type['icon']}\" alt=\"\" />";
		}
		
		$table->construct_cell($attachment_type['icon'], array("width" => 1));
		$table->construct_cell("<strong>.{$attachment_type['extension']}</strong>");
		$table->construct_cell($attachment_type['mimetype']);
		$table->construct_cell(get_friendly_size(($attachment_type['maxsize']*1024)), array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config/attachment_types&amp;action=edit&amp;atid={$attachment_type['atid']}\">{$lang->edit}</a>", array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config/attachment_types&amp;action=delete&amp;atid={$attachment_type['atid']}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_attachment_type_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_attachment_types, array('colspan' => 6));
		$table->construct_row();
	}
	
	$table->output($lang->attachment_types);
	
	$page->output_footer();
}

?>