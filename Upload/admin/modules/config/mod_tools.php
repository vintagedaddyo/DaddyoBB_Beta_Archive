<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:14 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->mod_tools, "index.php?module=config/mod_tools");

$plugins->run_hooks("admin_config_mod_tools_begin");

if($daddyobb->input['action'] == "delete_post_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_delete_post_tool");
	
	$query = $db->simple_select("modtools", "*", "tid='{$daddyobb->input['tid']}'");
	$tool = $db->fetch_array($query);
	
	// Does the post tool not exist?
	if(!$tool['tid'])
	{
		flash_message($lang->error_invalid_post_tool, 'error');
		admin_redirect("index.php?module=config/mod_tools&action=post_tools");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=config/mod_tools&action=post_tools");
	}

	if($daddyobb->request_method == 'post')
	{
		// Delete the type
		$db->delete_query('modtools', "tid='{$tool['tid']}'");
		
		$plugins->run_hooks("admin_config_mod_tools_delete_post_tool_commit");

		// Log admin action
		log_admin_action($tool['tid'], $tool['name']);

		flash_message($lang->success_post_tool_deleted, 'success');
		admin_redirect("index.php?module=config/mod_tools&action=post_tools");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config/mod_tools&amp;action=post_tools&amp;tid={$type['tid']}", $lang->confirm_post_tool_deletion);
	}
}

if($daddyobb->input['action'] == "delete_thread_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_delete_thread_tool");
	
	$query = $db->simple_select("modtools", "*", "tid='{$daddyobb->input['tid']}'");
	$tool = $db->fetch_array($query);
	
	// Does the post tool not exist?
	if(!$tool['tid'])
	{
		flash_message($lang->error_invalid_thread_tool, 'error');
		admin_redirect("index.php?module=config/mod_tools");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=config/mod_tools");
	}

	if($daddyobb->request_method == 'post')
	{
		// Delete the type
		$db->delete_query('modtools', "tid='{$tool['tid']}'");
		
		$plugins->run_hooks("admin_config_mod_tools_delete_thread_tool_commit");

		// Log admin action
		log_admin_action($tool['tid'], $tool['name']);

		flash_message($lang->success_thread_tool_deleted, 'success');
		admin_redirect("index.php?module=config/mod_tools");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config/mod_tools&amp;action=delete_thread_tool&amp;tid={$tool['tid']}", $lang->confirm_thread_tool_deletion);
	}
}


if($daddyobb->input['action'] == "post_tools")
{
	$plugins->run_hooks("admin_config_mod_tools_post_tools");
	
	$page->add_breadcrumb_item($lang->post_tools);
	$page->output_header($lang->mod_tools." - ".$lang->post_tools);
	
	$sub_tabs['thread_tools'] = array(
		'title' => $lang->thread_tools,
		'link' => "index.php?module=config/mod_tools"
	);
	$sub_tabs['add_thread_tool'] = array(
		'title'=> $lang->add_thread_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_thread_tool"
	);
	$sub_tabs['post_tools'] = array(
		'title' => $lang->post_tools,
		'link' => "index.php?module=config/mod_tools&amp;action=post_tools",
		'description' => $lang->post_tools_desc
	);
	$sub_tabs['add_post_tool'] = array(
		'title'=> $lang->add_post_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_post_tool"
	);
		
	$page->output_nav_tabs($sub_tabs, 'post_tools');
	
	$table = new Table;
	$table->construct_header($lang->title);
	$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 2));
	
	$query = $db->simple_select('modtools', 'tid, name, description, type', "type='p'", array('order_by' => 'name'));
	while($tool = $db->fetch_array($query))
	{
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=edit_post_tool&amp;tid={$tool['tid']}\"><strong>".htmlspecialchars_uni($tool['name'])."</strong></a><br /><small>".htmlspecialchars_uni($tool['description'])."</small>");
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=edit_post_tool&amp;tid={$tool['tid']}\">{$lang->edit}</a>", array('width' => 100, 'class' => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=delete_post_tool&amp;tid={$tool['tid']}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_post_tool_deletion}')\">{$lang->delete}</a>", array('width' => 100, 'class' => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_post_tools, array('colspan' => 3));
		$table->construct_row();
	}
	
	$table->output($lang->post_tools);
	
	$page->output_footer();
}

if($daddyobb->input['action'] == "edit_thread_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_edit_thread_tool");
	
	$query = $db->simple_select("modtools", "COUNT(tid) as tools", "tid = '{$daddyobb->input['tid']}' AND type='t'");
	if($db->fetch_field($query, "tools") < 1)
	{
		flash_message($lang->error_invalid_thread_tool, 'error');
		admin_redirect("index.php?module=config/mod_tools");
	}

	if($daddyobb->request_method == 'post')
	{
		if(trim($daddyobb->input['title']) == "")
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(trim($daddyobb->input['description']) == "")
		{
			$errors[] = $lang->error_missing_description;
		}
		
		if($daddyobb->input['forum_type'] == 2)
		{
			if(count($daddyobb->input['forum_1_forums']) < 1)
			{
				$errors[] = $lang->error_no_forums_selected;
			}
		}
		else
		{
			$daddyobb->input['forum_1_forums'] = '';
		}
		
		
		if($daddyobb->input['approvethread'] != '' && $daddyobb->input['approvethread'] != 'approve' && $daddyobb->input['approvethread'] != 'unapprove' && $daddyobb->input['approvethread'] != 'toggle')
		{
			$daddyobb->input['approvethread'] = '';
		}
		
		if($daddyobb->input['openthread'] != '' && $daddyobb->input['openthread'] != 'open' && $daddyobb->input['openthread'] != 'close' && $daddyobb->input['openthread'] != 'toggle')
		{
			$daddyobb->input['openthread'] = '';
		}
		
		if($daddyobb->input['move_type'] == 2)
		{
			if(!$daddyobb->input['move_1_forum'])
			{
				$errors[] = $lang->error_no_move_forum_selected;
			}
			
			if($daddyobb->input['move_2_redirect'] != 1 && $daddyobb->input['move_2_redirect'] != 0)
			{
				$daddyobb->input['move_2_redirect'] = 0;
			}
			
			if(!isset($daddyobb->input['move_3_redirecttime']))
			{
				$daddyobb->input['move_3_redirecttime'] = '';
			}
		}
		else
		{
			$daddyobb->input['move_1_forum'] = '';
			$daddyobb->input['move_2_redirect'] = 0;
			$daddyobb->input['move_3_redirecttime'] = '';
		}
		
		if($daddyobb->input['copy_type'] == 2)
		{
			if(!$daddyobb->input['copy_1_forum'])
			{
				$errors[] = $lang->error_no_copy_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['copy_1_forum'] = '';
		}
		
		if(!$errors)
		{
			$thread_options = array(
				'deletethread' => $daddyobb->input['deletethread'],
				'mergethreads' => $daddyobb->input['mergethreads'],
				'deletepoll' => $daddyobb->input['deletepoll'],
				'removeredirects' => $daddyobb->input['removeredirects'],
				'approvethread' => $daddyobb->input['approvethread'],
				'openthread' => $daddyobb->input['openthread'],
				'movethread' => intval($daddyobb->input['move_1_forum']),
				'movethreadredirect' => $daddyobb->input['move_2_redirect'],
				'movethreadredirectexpire' => intval($daddyobb->input['move_3_redirecttime']),
				'copythread' => intval($daddyobb->input['copy_1_forum']),
				'newsubject' => $daddyobb->input['newsubject'],
				'addreply' => $daddyobb->input['newreply'],
				'replysubject' => $daddyobb->input['newreplysubject']
			);
			
			$update_tool['type'] = 't';
			$update_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
			$update_tool['name'] = $db->escape_string($daddyobb->input['title']);
			$update_tool['description'] = $db->escape_string($daddyobb->input['description']);
			$update_tool['forums'] = '';
			
			if(is_array($daddyobb->input['forum_1_forums']))
			{
				foreach($daddyobb->input['forum_1_forums'] as $fid)
				{
					$checked[] = intval($fid);
				}
				$update_tool['forums'] = implode(',', $checked);
			}
		
			$db->update_query("modtools", $update_tool, "tid='{$daddyobb->input['tid']}'");
			
			$plugins->run_hooks("admin_config_mod_tools_edit_thread_tool_commit");

			// Log admin action
			log_admin_action($daddyobb->input['tid'], $daddyobb->input['title']);

			flash_message($lang->success_mod_tool_updated, 'success');
			admin_redirect("index.php?module=config/mod_tools");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_thread_tool);
	$page->output_header($lang->mod_tools." - ".$lang->edit_thread_tool);
	
	$form = new Form("index.php?module=config/mod_tools&amp;action=edit_thread_tool", 'post');
	echo $form->generate_hidden_field("tid", $daddyobb->input['tid']);
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$query = $db->simple_select("modtools", "*", "tid = '{$daddyobb->input['tid']}'");
		$modtool = $db->fetch_array($query);
		$thread_options = unserialize($modtool['threadoptions']);

		$daddyobb->input['title'] = $modtool['name'];
		$daddyobb->input['description'] = $modtool['description'];
		$daddyobb->input['forum_1_forums'] = explode(",", $modtool['forums']);

		if(!$modtool['forums'] || $modtool['forums'] == -1)
		{
			$forum_checked[1] = "checked=\"checked\"";
			$forum_checked[2] = '';
		}
		else
		{
			$forum_checked[1] = '';
			$forum_checked[2] = "checked=\"checked\"";
		}
		
		$daddyobb->input['approvethread'] = $thread_options['approvethread'];
		$daddyobb->input['openthread'] = $thread_options['openthread'];
		$daddyobb->input['move_1_forum'] = $thread_options['movethread'];
		$daddyobb->input['move_2_redirect'] = $thread_options['movethreadredirect'];
		$daddyobb->input['move_3_redirecttime'] = $thread_options['movethreadredirectexpire'];
		
		if(!$thread_options['movethread'])
		{
			$move_checked[1] = "checked=\"checked\"";
			$move_checked[2] = '';
		}
		else
		{
			$move_checked[1] = '';
			$move_checked[2] = "checked=\"checked\"";
		}
		
		if(!$thread_options['copythread'])
		{
			$copy_checked[1] = "checked=\"checked\"";
			$copy_checked[2] = '';
		}
		else
		{
			$copy_checked[1] = '';
			$copy_checked[2] = "checked=\"checked\"";
		}		
		
		$daddyobb->input['copy_1_forum'] = $thread_options['copythread'];
		$daddyobb->input['deletethread'] = $thread_options['deletethread'];
		$daddyobb->input['mergethreads'] = $thread_options['mergethreads'];
		$daddyobb->input['deletepoll'] = $thread_options['deletepoll'];
		$daddyobb->input['removeredirects'] = $thread_options['removeredirects'];
		$daddyobb->input['newsubject'] = $thread_options['newsubject'];
		$daddyobb->input['newreply'] = $thread_options['addreply'];
		$daddyobb->input['newreplysubject'] = $thread_options['replysubject'];
	}
	
	$form_container = new FormContainer($lang->general_options);
	$form_container->output_row($lang->name." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description." <em>*</em>", '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');


	$actions = "<script type=\"text/javascript\">
    function checkAction(id)
    {
        var checked = '';
        
        $$('.'+id+'s_check').each(function(e)
        {
            if(e.checked == true)
            {
                checked = e.value;
            }
        });
        $$('.'+id+'s').each(function(e)
        {
        	Element.hide(e);
        });
        if($(id+'_'+checked))
        {
            Element.show(id+'_'+checked);
        }
    }    
</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" {$forum_checked[1]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_forums}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" {$forum_checked[2]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_forums}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->forums_colon}</small></td>
					<td>".$form->generate_forum_select('forum_1_forums[]', $daddyobb->input['forum_1_forums'], array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('forum');
	</script>";
	$form_container->output_row($lang->available_in_forums." <em>*</em>", '', $actions);
	$form_container->end();
	
	$approve_unapprove = array(
		'' => $lang->no_change,
		'approve' => $lang->approve,
		'unapprove' => $lang->unapprove,
		'toggle' => $lang->toggle
	);
	
	$open_close = array(
		'' => $lang->no_change,
		'open' => $lang->open,
		'close' => $lang->close,
		'toggle' => $lang->toggle
	);
	
	$form_container = new FormContainer($lang->thread_moderation);
	$form_container->output_row($lang->approve_unapprove." <em>*</em>", '', $form->generate_select_box('approvethread', $approve_unapprove, $daddyobb->input['approvethread'], array('id' => 'approvethread')), 'approvethread');
	$form_container->output_row($lang->open_close_thread." <em>*</em>", '', $form->generate_select_box('openthread', $open_close, $daddyobb->input['openthread'], array('id' => 'openthread')), 'openthread');


	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"1\" {$move_checked[1]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_move_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"2\" {$move_checked[2]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->move_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"move_2\" class=\"moves\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_move_to}</small></td>
					<td>".$form->generate_forum_select('move_1_forum', $daddyobb->input['move_1_forum'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->leave_redirect}</small></td>
					<td>".$form->generate_yes_no_radio('move_2_redirect', $daddyobb->input['move_2_redirect'], array('style' => 'width: 2em;'))."</td>
				</tr>
				<tr>
					<td><small>{$lang->delete_redirect_after}</small></td>
					<td>".$form->generate_text_box('move_3_redirecttime', $daddyobb->input['move_3_redirecttime'], array('style' => 'width: 2em;'))." {$lang->days}</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('move');
	</script>";
	$form_container->output_row($lang->move_thread." <em>*</em>", $lang->move_thread_desc, $actions);
	
	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"1\" {$copy_checked[1]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_copy_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"2\" {$copy_checked[2]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->copy_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"copy_2\" class=\"copys\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_copy_to}</small></td>
					<td>".$form->generate_forum_select('copy_1_forum', $daddyobb->input['copy_1_forum'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('copy');
	</script>";
	$form_container->output_row($lang->copy_thread." <em>*</em>", '', $actions);
	$form_container->output_row($lang->delete_thread." <em>*</em>", '', $form->generate_yes_no_radio('deletethread', $daddyobb->input['deletethread'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->merge_thread." <em>*</em>", $lang->merge_thread_desc, $form->generate_yes_no_radio('mergethreads', $daddyobb->input['mergethreads'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->delete_poll." <em>*</em>", '', $form->generate_yes_no_radio('deletepoll', $daddyobb->input['deletepoll'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->delete_redirects." <em>*</em>", '', $form->generate_yes_no_radio('removeredirects', $daddyobb->input['removeredirects'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->new_subject." <em>*</em>", $lang->new_subject_desc, $form->generate_text_box('newsubject', $daddyobb->input['newsubject'], array('id' => 'newsubject')));
	$form_container->end();
	
	$form_container = new FormContainer($lang->add_new_reply);
	$form_container->output_row($lang->add_new_reply, $lang->add_new_reply_desc, $form->generate_text_area('newreply', $daddyobb->input['newreply'], array('id' => 'newreply')), 'newreply');
	$form_container->output_row($lang->reply_subject, $lang->reply_subject_desc, $form->generate_text_box('newreplysubject', $daddyobb->input['newreplysubject'], array('id' => 'newreplysubject')), 'newreplysubject');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_thread_tool);

	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if($daddyobb->input['action'] == "add_thread_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_add_thread_tool");
	
	if($daddyobb->request_method == 'post')
	{
		if(trim($daddyobb->input['title']) == "")
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(trim($daddyobb->input['description']) == "")
		{
			$errors[] = $lang->error_missing_description;
		}
		
		if($daddyobb->input['forum_type'] == 2)
		{
			if(count($daddyobb->input['forum_1_forums']) < 1)
			{
				$errors[] = $lang->error_no_forums_selected;
			}
		}
		else
		{
			$daddyobb->input['forum_1_forums'] = '';
		}
		
		
		if($daddyobb->input['approvethread'] != '' && $daddyobb->input['approvethread'] != 'approve' && $daddyobb->input['approvethread'] != 'unapprove' && $daddyobb->input['approvethread'] != 'toggle')
		{
			$daddyobb->input['approvethread'] = '';
		}
		
		if($daddyobb->input['openthread'] != '' && $daddyobb->input['openthread'] != 'open' && $daddyobb->input['openthread'] != 'close' && $daddyobb->input['openthread'] != 'toggle')
		{
			$daddyobb->input['openthread'] = '';
		}
		
		if($daddyobb->input['move_type'] == 2)
		{
			if(!$daddyobb->input['move_1_forum'])
			{
				$errors[] = $lang->error_no_move_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['move_1_forum'] = '';
			$daddyobb->input['move_2_redirect'] = 0;
			$daddyobb->input['move_3_redirecttime'] = '';
		}
		
		if($daddyobb->input['copy_type'] == 2)
		{
			if(!$daddyobb->input['copy_1_forum'])
			{
				$errors[] = $lang->error_no_copy_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['copy_1_forum'] = '';
		}
		
		if(!$errors)
		{
			$thread_options = array(
				'deletethread' => $daddyobb->input['deletethread'],
				'mergethreads' => $daddyobb->input['mergethreads'],
				'deletepoll' => $daddyobb->input['deletepoll'],
				'removeredirects' => $daddyobb->input['removeredirects'],
				'approvethread' => $daddyobb->input['approvethread'],
				'openthread' => $daddyobb->input['openthread'],
				'movethread' => intval($daddyobb->input['move_1_forum']),
				'movethreadredirect' => $daddyobb->input['move_2_redirect'],
				'movethreadredirectexpire' => intval($daddyobb->input['move_3_redirecttime']),
				'copythread' => intval($daddyobb->input['copy_1_forum']),
				'newsubject' => $daddyobb->input['newsubject'],
				'addreply' => $daddyobb->input['newreply'],
				'replysubject' => $daddyobb->input['newreplysubject'],
			);
			
			$new_tool['type'] = 't';
			$new_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
			$new_tool['name'] = $db->escape_string($daddyobb->input['title']);
			$new_tool['description'] = $db->escape_string($daddyobb->input['description']);
			$new_tool['forums'] = '';
			
			if($daddyobb->input['forum_type'] == 2)
			{
				if(is_array($daddyobb->input['forum_1_forums']))
				{
					foreach($daddyobb->input['forum_1_forums'] as $fid)
					{
						$checked[] = intval($fid);
					}
					$new_tool['forums'] = implode(',', $checked);
				}
			}
			else
			{
				$new_tools['forums'] = "-1";
			}
		
			$tid = $db->insert_query("modtools", $new_tool);
			
			$plugins->run_hooks("admin_config_mod_tools_add_thread_tool_commit");

			// Log admin action
			log_admin_action($tid, $daddyobb->input['title']);
			
			flash_message($lang->success_mod_tool_created, 'success');
			admin_redirect("index.php?module=config/mod_tools");
		}
	}
	
	$page->add_breadcrumb_item($lang->add_new_thread_tool);
	$page->output_header($lang->mod_tools." - ".$lang->add_new_thread_tool);
	
	$sub_tabs['thread_tools'] = array(
		'title' => $lang->thread_tools,
		'link' => "index.php?module=config/mod_tools"
	);
	$sub_tabs['add_thread_tool'] = array(
		'title'=> $lang->add_new_thread_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_thread_tool",
		'description' => $lang->add_thread_tool_desc
	);
	$sub_tabs['post_tools'] = array(
		'title' => $lang->post_tools,
		'link' => "index.php?module=config/mod_tools&amp;action=post_tools",
	);
	$sub_tabs['add_post_tool'] = array(
		'title'=> $lang->add_new_post_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_post_tool"
	);
		
	$page->output_nav_tabs($sub_tabs, 'add_thread_tool');
	
	$form = new Form("index.php?module=config/mod_tools&amp;action=add_thread_tool", 'post');
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['title'] = '';
		$daddyobb->input['description'] = '';
		$daddyobb->input['forum_1_forums'] = '';
		$forum_checked[1] = "checked=\"checked\"";
		$forum_checked[2] = '';
		$daddyobb->input['approvethread'] = '';
		$daddyobb->input['openthread'] = '';
		$daddyobb->input['move_1_forum'] = '';
		$daddyobb->input['move_2_redirect'] = '0';
		$daddyobb->input['move_3_redirecttime'] = '';
		$move_checked[1] = "checked=\"checked\"";
		$move_checked[2] = '';
		$copy_checked[1] = "checked=\"checked\"";
		$copy_checked[2] = '';
		$daddyobb->input['copy_1_forum'] = '';
		$daddyobb->input['deletethread'] = '0';
		$daddyobb->input['mergethreads'] = '0';
		$daddyobb->input['deletepoll'] = '0';
		$daddyobb->input['removeredirects'] = '0';
		$daddyobb->input['newsubject'] = '{subject}';
		$daddyobb->input['newreply'] = '';
		$daddyobb->input['newreplysubject'] = '{subject}';
	}

	$form_container = new FormContainer($lang->general_options);
	$form_container->output_row($lang->name." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description." <em>*</em>", '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');


	$actions = "<script type=\"text/javascript\">
    function checkAction(id)
    {
        var checked = '';
        
        $$('.'+id+'s_check').each(function(e)
        {
            if(e.checked == true)
            {
                checked = e.value;
            }
        });
        $$('.'+id+'s').each(function(e)
        {
        	Element.hide(e);
        });
        if($(id+'_'+checked))
        {
            Element.show(id+'_'+checked);
        }
    }    
</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" {$forum_checked[1]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_forums}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" {$forum_checked[2]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_forums}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->forums_colon}</small></td>
					<td>".$form->generate_forum_select('forum_1_forums[]', $daddyobb->input['forum_1_forums'], array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('forum');
	</script>";
	$form_container->output_row($lang->available_in_forums." <em>*</em>", '', $actions);
	$form_container->end();
	
	$approve_unapprove = array(
		'' => $lang->no_change,
		'approve' => $lang->approve,
		'unapprove' => $lang->unapprove,
		'toggle' => $lang->toggle
	);
	
	$open_close = array(
		'' => $lang->no_change,
		'open' => $lang->open,
		'close' => $lang->close,
		'toggle' => $lang->toggle
	);
	
	$form_container = new FormContainer($lang->thread_moderation);
	$form_container->output_row($lang->approve_unapprove." <em>*</em>", '', $form->generate_select_box('approvethread', $approve_unapprove, $daddyobb->input['approvethread'], array('id' => 'approvethread')), 'approvethread');
	$form_container->output_row($lang->open_close_thread." <em>*</em>", '', $form->generate_select_box('openthread', $open_close, $daddyobb->input['openthread'], array('id' => 'openthread')), 'openthread');


	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"1\" {$move_checked[1]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_move_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"2\" {$move_checked[2]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->move_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"move_2\" class=\"moves\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_move_to}</small></td>
					<td>".$form->generate_forum_select('move_1_forum', $daddyobb->input['move_1_forum'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->leave_redirect}</small></td>
					<td>".$form->generate_yes_no_radio('move_2_redirect', $daddyobb->input['move_2_redirect'], array('style' => 'width: 2em;'))."</td>
				</tr>
				<tr>
					<td><small>{$lang->delete_redirect_after}</small></td>
					<td>".$form->generate_text_box('move_3_redirecttime', $daddyobb->input['move_3_redirecttime'], array('style' => 'width: 2em;'))." {$lang->days}</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('move');
	</script>";
	$form_container->output_row($lang->move_thread." <em>*</em>", $lang->move_thread_desc, $actions);
	
	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"1\" {$copy_checked[1]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_copy_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"2\" {$copy_checked[2]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->copy_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"copy_2\" class=\"copys\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_copy_to}</small></td>
					<td>".$form->generate_forum_select('copy_1_forum', $daddyobb->input['copy_1_forum'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('copy');
	</script>";
	$form_container->output_row($lang->copy_thread." <em>*</em>", '', $actions);
	$form_container->output_row($lang->delete_thread." <em>*</em>", '', $form->generate_yes_no_radio('deletethread', $daddyobb->input['deletethread'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->merge_thread." <em>*</em>", $lang->merge_thread_desc, $form->generate_yes_no_radio('mergethreads', $daddyobb->input['mergethreads'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->delete_poll." <em>*</em>", '', $form->generate_yes_no_radio('deletepoll', $daddyobb->input['deletepoll'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->delete_redirects." <em>*</em>", '', $form->generate_yes_no_radio('removeredirects', $daddyobb->input['removeredirects'], array('style' => 'width: 2em;')));
	$form_container->output_row($lang->new_subject." <em>*</em>", $lang->new_subject_desc, $form->generate_text_box('newsubject', $daddyobb->input['newsubject'], array('id' => 'newsubject')));
	$form_container->end();
	
	$form_container = new FormContainer($lang->add_new_reply);
	$form_container->output_row($lang->add_new_reply, $lang->add_new_reply_desc, $form->generate_text_area('newreply', $daddyobb->input['newreply'], array('id' => 'newreply')), 'newreply');
	$form_container->output_row($lang->reply_subject, $lang->reply_subject_desc, $form->generate_text_box('newreplysubject', $daddyobb->input['newreplysubject'], array('id' => 'newreplysubject')), 'newreplysubject');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_thread_tool);

	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if($daddyobb->input['action'] == "edit_post_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_edit_post_tool");
	
	$query = $db->simple_select("modtools", "COUNT(tid) as tools", "tid = '{$daddyobb->input['tid']}' AND type='p'");
	if($db->fetch_field($query, "tools") < 1)
	{
		flash_message($lang->error_invalid_post_tool, 'error');
		admin_redirect("index.php?module=config/mod_tools&action=post_tools");
	}
	
	if($daddyobb->request_method == 'post')
	{
		if(trim($daddyobb->input['title']) == "")
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(trim($daddyobb->input['description']) == "")
		{
			$errors[] = $lang->error_missing_description;
		}
		
		if($daddyobb->input['forum_type'] == 2)
		{
			if(count($daddyobb->input['forum_1_forums']) < 1)
			{
				$errors[] = $lang->error_no_forums_selected;
			}
		}
		else
		{
			$daddyobb->input['forum_1_forums'] = '';
		}		
		
		if($daddyobb->input['approvethread'] != '' && $daddyobb->input['approvethread'] != 'approve' && $daddyobb->input['approvethread'] != 'unapprove' && $daddyobb->input['approvethread'] != 'toggle')
		{
			$daddyobb->input['approvethread'] = '';
		}
		
		if($daddyobb->input['openthread'] != '' && $daddyobb->input['openthread'] != 'open' && $daddyobb->input['openthread'] != 'close' && $daddyobb->input['openthread'] != 'toggle')
		{
			$daddyobb->input['openthread'] = '';
		}
		
		if($daddyobb->input['move_type'] == 2)
		{
			if(!$daddyobb->input['move_1_forum'])
			{
				$errors[] = $lang->error_no_move_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['move_1_forum'] = '';
			$daddyobb->input['move_2_redirect'] = 0;
			$daddyobb->input['move_3_redirecttime'] = '';
		}
		
		if($daddyobb->input['copy_type'] == 2)
		{
			if(!$daddyobb->input['copy_1_forum'])
			{
				$errors[] = $lang->error_no_copy_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['copy_1_forum'] = '';
		}
		
		if($daddyobb->input['approveposts'] != '' && $daddyobb->input['approveposts'] != 'approve' && $daddyobb->input['approveposts'] != 'unapprove' && $daddyobb->input['approveposts'] != 'toggle')
		{
			$daddyobb->input['approveposts'] = '';
		}
		
		if($daddyobb->input['splitposts'] < -2)
		{
			$daddyobb->input['splitposts'] = -1;
		}
		
		if($daddyobb->input['splitpostsclose'] == 1)
		{
			$daddyobb->input['splitpostsclose'] = 'close';
		}
		else
		{
			$daddyobb->input['splitpostsclose'] = '';
		}
		
		if($daddyobb->input['splitpostsstick'] == 1)
		{
			$daddyobb->input['splitpostsstick'] = 'stick';
		}
		else
		{
			$daddyobb->input['splitpostsstick'] = '';
		}
		
		if($daddyobb->input['splitpostsunapprove'] == 1)
		{
			$daddyobb->input['splitpostsunapprove'] = 'unapprove';
		}
		else
		{
			$daddyobb->input['splitpostsunapprove'] = '';
		}

		if(!$errors)
		{
			$thread_options = array(
				'deletethread' => $daddyobb->input['deletethread'],
				'approvethread' => $daddyobb->input['approvethread'],
				'openthread' => $daddyobb->input['openthread'],
				'movethread' => intval($daddyobb->input['move_1_forum']),
				'movethreadredirect' => $daddyobb->input['move_2_redirect'],
				'movethreadredirectexpire' => intval($daddyobb->input['move_3_redirecttime']),
				'copythread' => intval($daddyobb->input['copy_1_forum']),
				'newsubject' => $daddyobb->input['newsubject'],
				'addreply' => $daddyobb->input['newreply'],
				'replysubject' => $daddyobb->input['newreplysubject']
			);
			
			if(stripos($daddyobb->input['splitpostsnewsubject'], '{subject}') === false)
			{
				$daddyobb->input['splitpostsnewsubject'] = '{subject}'.$daddyobb->input['splitpostsnewsubject'];
			}
			
			$post_options = array(
				'deleteposts' => $daddyobb->input['deleteposts'],
				'mergeposts' => $daddyobb->input['mergeposts'],
				'approveposts' => $daddyobb->input['approveposts'],
				'splitposts' => intval($daddyobb->input['splitposts']),
				'splitpostsclose' => $daddyobb->input['splitpostsclose'],
				'splitpostsstick' => $daddyobb->input['splitpostsstick'],
				'splitpostsunapprove' => $daddyobb->input['splitpostsunapprove'],
				'splitpostsnewsubject' => $daddyobb->input['splitpostsnewsubject'],
				'splitpostsaddreply' => $daddyobb->input['splitpostsaddreply'],
				'splitpostsreplysubject' => $daddyobb->input['splitpostsreplysubject']
			);
			
			$update_tool['type'] = 'p';
			$update_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
			$update_tool['postoptions'] = $db->escape_string(serialize($post_options));	
			$update_tool['name'] = $db->escape_string($daddyobb->input['title']);
			$update_tool['description'] = $db->escape_string($daddyobb->input['description']);
			$update_tool['forums'] = '';
			
			if(is_array($daddyobb->input['forum_1_forums']))
			{
				foreach($daddyobb->input['forum_1_forums'] as $fid)
				{
					$checked[] = intval($fid);
				}
				$update_tool['forums'] = implode(',', $checked);
			}
		
			$db->update_query("modtools", $update_tool, "tid = '{$daddyobb->input['tid']}'");
			
			$plugins->run_hooks("admin_config_mod_tools_edit_post_tool_commit");

			// Log admin action
			log_admin_action($daddyobb->input['tid'], $daddyobb->input['title']);
			
			flash_message($lang->success_mod_tool_updated, 'success');
			admin_redirect("index.php?module=config/mod_tools&action=post_tools");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_post_tool);
	$page->output_header($lang->mod_tools." - ".$lang->edit_post_tool);
	
	$form = new Form("index.php?module=config/mod_tools&amp;action=edit_post_tool", 'post');
	echo $form->generate_hidden_field("tid", $daddyobb->input['tid']);
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$query = $db->simple_select("modtools", "*", "tid = '{$daddyobb->input['tid']}'");
		$modtool = $db->fetch_array($query);
		$thread_options = unserialize($modtool['threadoptions']);
		$post_options = unserialize($modtool['postoptions']);
		
		$daddyobb->input['title'] = $modtool['name'];
		$daddyobb->input['description'] = $modtool['description'];
		$daddyobb->input['forum_1_forums'] = explode(",", $modtool['forums']);
		
		if(!$modtool['forums'] || $modtool['forums'] == -1)
		{
			$forum_checked[1] = "checked=\"checked\"";
			$forum_checked[2] = '';
		}
		else
		{
			$forum_checked[1] = '';
			$forum_checked[2] = "checked=\"checked\"";
		}
		
		$daddyobb->input['approvethread'] = $thread_options['approvethread'];
		$daddyobb->input['openthread'] = $thread_options['openthread'];
		$daddyobb->input['move_1_forum'] = $thread_options['movethread'];
		$daddyobb->input['move_2_redirect'] = $thread_options['movethreadredirect'];
		$daddyobb->input['move_3_redirecttime'] = $thread_options['movethreadredirectexpire'];
		
		if(!$thread_options['movethread'])
		{
			$move_checked[1] = "checked=\"checked\"";
			$move_checked[2] = '';
		}
		else
		{
			$move_checked[1] = '';
			$move_checked[2] = "checked=\"checked\"";
		}
		
		if(!$thread_options['copythread'])
		{
			$copy_checked[1] = "checked=\"checked\"";
			$copy_checked[2] = '';
		}
		else
		{
			$copy_checked[1] = '';
			$copy_checked[2] = "checked=\"checked\"";
		}
		
		$daddyobb->input['copy_1_forum'] = $thread_options['copythread'];
		$daddyobb->input['deletethread'] = $thread_options['deletethread'];
		$daddyobb->input['newsubject'] = $thread_options['newsubject'];
		$daddyobb->input['newreply'] = $thread_options['addreply'];
		$daddyobb->input['newreplysubject'] = $thread_options['replysubject'];
		
		if($post_options['splitposts'] == '-1')
		{
			$do_not_split_checked = ' selected="selected"';
			$split_same_checked = '';
		}
		else if($post_options['splitposts'] == '-2')
		{
			$do_not_split_checked = '';
			$split_same_checked = ' selected="selected"';
		}
		
		$daddyobb->input['deleteposts'] = $post_options['deleteposts'];
		$daddyobb->input['mergeposts'] = $post_options['mergeposts'];
		$daddyobb->input['approveposts'] = $post_options['approveposts'];
		
		if($post_options['splitpostsclose'] == 'close')
		{
			$daddyobb->input['splitpostsclose'] = '1';
		}
		else
		{	
			$daddyobb->input['splitpostsclose'] = '0';
		}
		
		if($post_options['splitpostsstick'] == 'stick')
		{
			$daddyobb->input['splitpostsstick'] = '1';
		}
		else
		{
			$daddyobb->input['splitpostsstick'] = '0';
		}
		
		if($post_options['splitpostsunapprove'] == 'unapprove')
		{
			$daddyobb->input['splitpostsunapprove'] = '1';
		}
		else
		{
			$daddyobb->input['splitpostsunapprove'] = '0';
		}
		
		$daddyobb->input['splitposts'] = $post_options['splitposts'];
				
		$daddyobb->input['splitpostsnewsubject'] = $post_options['splitpostsnewsubject'];
		$daddyobb->input['splitpostsaddreply'] = $post_options['splitpostsaddreply'];
		$daddyobb->input['splitpostsreplysubject'] = $post_options['splitpostsreplysubject'];		
	}

	$form_container = new FormContainer($lang->general_options);
	$form_container->output_row($lang->name." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description." <em>*</em>", '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');


	$actions = "<script type=\"text/javascript\">
    function checkAction(id)
    {
        var checked = '';
        
        $$('.'+id+'s_check').each(function(e)
        {
            if(e.checked == true)
            {
                checked = e.value;
            }
        });
        $$('.'+id+'s').each(function(e)
        {
        	Element.hide(e);
        });
        if($(id+'_'+checked))
        {
            Element.show(id+'_'+checked);
        }
    }    
</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" {$forum_checked[1]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_forums}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" {$forum_checked[2]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_forums}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->forums_colon}</small></td>
					<td>".$form->generate_forum_select('forum_1_forums[]', $daddyobb->input['forum_1_forums'], array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('forum');
	</script>";
	$form_container->output_row($lang->available_in_forums." <em>*</em>", '', $actions);
	$form_container->end();

	$approve_unapprove = array(
		'' => $lang->no_change,
		'approve' => $lang->approve,
		'unapprove' => $lang->unapprove,
		'toggle' => $lang->toggle
	);

	$form_container = new FormContainer($lang->inline_post_moderation);
	$form_container->output_row($lang->delete_posts." <em>*</em>", '', $form->generate_yes_no_radio('deleteposts', $daddyobb->input['deleteposts']));
	$form_container->output_row($lang->merge_posts." <em>*</em>", $lang->merge_posts_desc, $form->generate_yes_no_radio('mergeposts', $daddyobb->input['mergeposts']));
	$form_container->output_row($lang->approve_unapprove_posts." <em>*</em>", '', $form->generate_select_box('approveposts', $approve_unapprove, $daddyobb->input['approveposts'], array('id' => 'approveposts')), 'approveposts');
	$form_container->end();
	
	$selectoptions = "<option value=\"-1\"{$do_not_split_checked}>{$lang->do_not_split}</option>\n";
	$selectoptions .= "<option value=\"-2\"{$split_same_checked} style=\"border-bottom: 1px solid #000;\">{$lang->split_to_same_forum}</option>\n";
	
	$form_container = new FormContainer($lang->split_posts);
	$form_container->output_row($lang->split_posts2." <em>*</em>", '', $form->generate_forum_select('splitposts', $daddyobb->input['splitposts']));
	$form_container->output_row($lang->close_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsclose', $daddyobb->input['splitpostsclose']));
	$form_container->output_row($lang->stick_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsstick', $daddyobb->input['splitpostsstick']));
	$form_container->output_row($lang->unapprove_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsunapprove', $daddyobb->input['splitpostsunapprove']));
	$form_container->output_row($lang->split_thread_subject, $lang->split_thread_subject_desc, $form->generate_text_box('splitpostsnewsubject', $daddyobb->input['splitpostsnewsubject'], array('id' => 'splitpostsnewsubject ')), 'newreplysubject');
	$form_container->output_row($lang->add_new_split_reply, $lang->add_new_split_reply_desc, $form->generate_text_area('splitpostsaddreply', $daddyobb->input['splitpostsaddreply'], array('id' => 'splitpostsaddreply')), 'splitpostsaddreply');
	$form_container->output_row($lang->split_reply_subject, $lang->split_reply_subject_desc, $form->generate_text_box('splitpostsreplysubject', $daddyobb->input['splitpostsreplysubject'], array('id' => 'splitpostsreplysubject')), 'splitpostsreplysubject');
	$form_container->end();
	
	$open_close = array(
		'' => $lang->no_change,
		'open' => $lang->open,
		'close' => $lang->close,
		'toggle' => $lang->toggle
	);
	
	$form_container = new FormContainer($lang->thread_moderation);
	$form_container->output_row($lang->approve_unapprove." <em>*</em>", '', $form->generate_select_box('approvethread', $approve_unapprove, $daddyobb->input['approvethread'], array('id' => 'approvethread')), 'approvethread');
	$form_container->output_row($lang->open_close_thread." <em>*</em>", '', $form->generate_select_box('openthread', $open_close, $daddyobb->input['openthread'], array('id' => 'openthread')), 'openthread');


	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"1\" {$move_checked[1]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_move_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"2\" {$move_checked[2]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->move_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"move_2\" class=\"moves\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_move_to}</small></td>
					<td>".$form->generate_forum_select('move_1_forum', $daddyobb->input['move_1_forum'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->leave_redirect}</small></td>
					<td>".$form->generate_yes_no_radio('move_2_redirect', $daddyobb->input['move_2_redirect'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->delete_redirect_after}</small></td>
					<td>".$form->generate_text_box('move_3_redirecttime', $daddyobb->input['move_3_redirecttime'], array('style' => 'width: 2em;'))." {$lang->days}</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('move');
	</script>";
	$form_container->output_row($lang->move_thread." <em>*</em>", $lang->move_thread_desc, $actions);
	
	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"1\" {$copy_checked[1]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_copy_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"2\" {$copy_checked[2]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->copy_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"copy_2\" class=\"copys\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_copy_to}</small></td>
					<td>".$form->generate_forum_select('copy_1_forum', $daddyobb->input['copy_1_forum'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('copy');
	</script>";
	$form_container->output_row($lang->copy_thread." <em>*</em>", '', $actions);
	$form_container->output_row($lang->delete_thread." <em>*</em>", '', $form->generate_yes_no_radio('deletethread', $daddyobb->input['deletethread']));
	$form_container->output_row($lang->new_subject." <em>*</em>", $lang->new_subject_desc, $form->generate_text_box('newsubject', $daddyobb->input['newsubject']));
	$form_container->end();
	
	$form_container = new FormContainer($lang->add_new_reply);
	$form_container->output_row($lang->add_new_reply, $lang->add_new_reply_desc, $form->generate_text_area('newreply', $daddyobb->input['newreply']), 'newreply');
	$form_container->output_row($lang->reply_subject, $lang->reply_subject_desc, $form->generate_text_box('newreplysubject', $daddyobb->input['newreplysubject'], array('id' => 'newreplysubject')), 'newreplysubject');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_post_tool);

	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if($daddyobb->input['action'] == "add_post_tool")
{
	$plugins->run_hooks("admin_config_mod_tools_add_post_tool");
	
	if($daddyobb->request_method == 'post')
	{
		if(trim($daddyobb->input['title']) == "")
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(trim($daddyobb->input['description']) == "")
		{
			$errors[] = $lang->error_missing_description;
		}
		
		if($daddyobb->input['forum_type'] == 2)
		{
			if(count($daddyobb->input['forum_1_forums']) < 1)
			{
				$errors[] = $lang->error_no_forums_selected;
			}
		}
		else
		{
			$daddyobb->input['forum_1_forums'] = '';
		}
		
		
		if($daddyobb->input['approvethread'] != '' && $daddyobb->input['approvethread'] != 'approve' && $daddyobb->input['approvethread'] != 'unapprove' && $daddyobb->input['approvethread'] != 'toggle')
		{
			$daddyobb->input['approvethread'] = '';
		}
		
		if($daddyobb->input['openthread'] != '' && $daddyobb->input['openthread'] != 'open' && $daddyobb->input['openthread'] != 'close' && $daddyobb->input['openthread'] != 'toggle')
		{
			$daddyobb->input['openthread'] = '';
		}
		
		if($daddyobb->input['move_type'] == 2)
		{
			if(!$daddyobb->input['move_1_forum'])
			{
				$errors[] = $lang->error_no_move_forum_selected;
			}			
		}
		else
		{
			$daddyobb->input['move_1_forum'] = '';
			$daddyobb->input['move_2_redirect'] = 0;
			$daddyobb->input['move_3_redirecttime'] = '';
		}
		
		if($daddyobb->input['copy_type'] == 2)
		{
			if(!$daddyobb->input['copy_1_forum'])
			{
				$errors[] = $lang->error_no_copy_forum_selected;
			}
		}
		else
		{
			$daddyobb->input['copy_1_forum'] = '';
		}
		
		if($daddyobb->input['approveposts'] != '' && $daddyobb->input['approveposts'] != 'approve' && $daddyobb->input['approveposts'] != 'unapprove' && $daddyobb->input['approveposts'] != 'toggle')
		{
			$daddyobb->input['approveposts'] = '';
		}
		
		if($daddyobb->input['splitposts'] < -2)
		{
			$daddyobb->input['splitposts'] = -1;
		}
		
		if($daddyobb->input['splitpostsclose'] == 1)
		{
			$daddyobb->input['splitpostsclose'] = 'close';
		}
		else
		{
			$daddyobb->input['splitpostsclose'] = '';
		}
		
		if($daddyobb->input['splitpostsstick'] == 1)
		{
			$daddyobb->input['splitpostsstick'] = 'stick';
		}
		else
		{
			$daddyobb->input['splitpostsstick'] = '';
		}
		
		if($daddyobb->input['splitpostsunapprove'] == 1)
		{
			$daddyobb->input['splitpostsunapprove'] = 'unapprove';
		}
		else
		{
			$daddyobb->input['splitpostsunapprove'] = '';
		}
		
		if(!$errors)
		{
			$thread_options = array(
				'deletethread' => $daddyobb->input['deletethread'],
				'approvethread' => $daddyobb->input['approvethread'],
				'openthread' => $daddyobb->input['openthread'],
				'movethread' => intval($daddyobb->input['move_1_forum']),
				'movethreadredirect' => $daddyobb->input['move_2_redirect'],
				'movethreadredirectexpire' => intval($daddyobb->input['move_3_redirecttime']),
				'copythread' => intval($daddyobb->input['copy_1_forum']),
				'newsubject' => $daddyobb->input['newsubject'],
				'addreply' => $daddyobb->input['newreply'],
				'replysubject' => $daddyobb->input['newreplysubject']
			);
			
			if(stripos($daddyobb->input['splitpostsnewsubject'], '{subject}') === false)
			{
				$daddyobb->input['splitpostsnewsubject'] = '{subject}'.$daddyobb->input['splitpostsnewsubject'];
			}
			
			$post_options = array(
				'deleteposts' => $daddyobb->input['deleteposts'],
				'mergeposts' => $daddyobb->input['mergeposts'],
				'approveposts' => $daddyobb->input['approveposts'],
				'splitposts' => intval($daddyobb->input['splitposts']),
				'splitpostsclose' => $daddyobb->input['splitpostsclose'],
				'splitpostsstick' => $daddyobb->input['splitpostsstick'],
				'splitpostsunapprove' => $daddyobb->input['splitpostsunapprove'],
				'splitpostsnewsubject' => $daddyobb->input['splitpostsnewsubject'],
				'splitpostsaddreply' => $daddyobb->input['splitpostsaddreply'],
				'splitpostsreplysubject' => $daddyobb->input['splitpostsreplysubject']
			);
			
			$new_tool['type'] = 'p';
			$new_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
			$new_tool['postoptions'] = $db->escape_string(serialize($post_options));	
			$new_tool['name'] = $db->escape_string($daddyobb->input['title']);
			$new_tool['description'] = $db->escape_string($daddyobb->input['description']);
			$new_tool['forums'] = '';
			
			if(is_array($daddyobb->input['forum_1_forums']))
			{
				foreach($daddyobb->input['forum_1_forums'] as $fid)
				{
					$checked[] = intval($fid);
				}
				$new_tool['forums'] = implode(',', $checked);
			}
		
			$tid = $db->insert_query("modtools", $new_tool);
			
			$plugins->run_hooks("admin_config_mod_tools_add_post_tool_commit");

			// Log admin action
			log_admin_action($tid, $daddyobb->input['title']);
			
			flash_message($lang->success_mod_tool_created, 'success');
			admin_redirect("index.php?module=config/mod_tools&action=post_tools");
		}
	}
	
	$page->add_breadcrumb_item($lang->add_new_post_tool);
	$page->output_header($lang->mod_tools." - ".$lang->add_new_post_tool);
	
	$sub_tabs['thread_tools'] = array(
		'title' => $lang->thread_tools,
		'link' => "index.php?module=config/mod_tools"
	);
	$sub_tabs['add_thread_tool'] = array(
		'title'=> $lang->add_new_thread_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_thread_tool"
	);
	$sub_tabs['post_tools'] = array(
		'title' => $lang->post_tools,
		'link' => "index.php?module=config/mod_tools&amp;action=post_tools",
	);
	$sub_tabs['add_post_tool'] = array(
		'title'=> $lang->add_new_post_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_post_tool",
		'description' => $lang->add_post_tool_desc
	);
		
	$page->output_nav_tabs($sub_tabs, 'add_post_tool');
	
	$form = new Form("index.php?module=config/mod_tools&amp;action=add_post_tool", 'post');
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['title'] = '';
		$daddyobb->input['description'] = '';
		$daddyobb->input['forum_1_forums'] = '';
		$forum_checked[1] = "checked=\"checked\"";
		$forum_checked[2] = '';
		$daddyobb->input['approvethread'] = '';
		$daddyobb->input['openthread'] = '';
		$daddyobb->input['move_1_forum'] = '';
		$daddyobb->input['move_2_redirect'] = '0';
		$daddyobb->input['move_3_redirecttime'] = '';
		$move_checked[1] = "checked=\"checked\"";
		$move_checked[2] = '';
		$copy_checked[1] = "checked=\"checked\"";
		$copy_checked[2] = '';
		$daddyobb->input['copy_1_forum'] = '';
		$daddyobb->input['deletethread'] = '0';
		$daddyobb->input['newsubject'] = '{subject}';
		$daddyobb->input['newreply'] = '';
		$daddyobb->input['newreplysubject'] = '{subject}';
		$do_not_split_checked = ' selected="selected"';
		$split_same_checked = '';
		$daddyobb->input['deleteposts'] = '0';
		$daddyobb->input['mergeposts'] = '0';
		$daddyobb->input['approveposts'] = '';
		$daddyobb->input['splitposts'] = '-1';
		$daddyobb->input['splitpostsclose'] = '0';
		$daddyobb->input['splitpostsstick'] = '0';
		$daddyobb->input['splitpostsunapprove'] = '0';
		$daddyobb->input['splitpostsnewsubject'] = '{subject}';
		$daddyobb->input['splitpostsaddreply'] = '';
		$daddyobb->input['splitpostsreplysubject'] = '{subject}';		
	}

	$form_container = new FormContainer($lang->general_options);
	$form_container->output_row($lang->name." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description." <em>*</em>", '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');


	$actions = "<script type=\"text/javascript\">
    function checkAction(id)
    {
        var checked = '';
        
        $$('.'+id+'s_check').each(function(e)
        {
            if(e.checked == true)
            {
                checked = e.value;
            }
        });
        $$('.'+id+'s').each(function(e)
        {
        	Element.hide(e);
        });
        if($(id+'_'+checked))
        {
            Element.show(id+'_'+checked);
        }
    }    
</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" {$forum_checked[1]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_forums}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" {$forum_checked[2]} class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_forums}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->forums_colon}</small></td>
					<td>".$form->generate_forum_select('forum_1_forums[]', $daddyobb->input['forum_1_forums'], array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('forum');
	</script>";
	$form_container->output_row($lang->available_in_forums." <em>*</em>", '', $actions);
	$form_container->end();

	$approve_unapprove = array(
		'' => $lang->no_change,
		'approve' => $lang->approve,
		'unapprove' => $lang->unapprove,
		'toggle' => $lang->toggle
	);

	$form_container = new FormContainer($lang->inline_post_moderation);
	$form_container->output_row($lang->delete_posts." <em>*</em>", '', $form->generate_yes_no_radio('deleteposts', $daddyobb->input['deleteposts']));
	$form_container->output_row($lang->merge_posts." <em>*</em>", $lang->merge_posts_desc, $form->generate_yes_no_radio('mergeposts', $daddyobb->input['mergeposts']));
	$form_container->output_row($lang->approve_unapprove_posts." <em>*</em>", '', $form->generate_select_box('approveposts', $approve_unapprove, $daddyobb->input['approveposts'], array('id' => 'approveposts')), 'approveposts');
	$form_container->end();
	
	$selectoptions = "<option value=\"-1\"{$do_not_split_checked}>{$lang->do_not_split}</option>\n";
	$selectoptions .= "<option value=\"-2\"{$split_same_checked} style=\"border-bottom: 1px solid #000;\">{$lang->split_to_same_forum}</option>\n";
	
	$form_container = new FormContainer($lang->split_posts);
	$form_container->output_row($lang->split_posts2." <em>*</em>", '', $form->generate_forum_select('splitposts', $daddyobb->input['splitposts']));
	$form_container->output_row($lang->close_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsclose', $daddyobb->input['splitpostsclose']));
	$form_container->output_row($lang->stick_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsstick', $daddyobb->input['splitpostsstick']));
	$form_container->output_row($lang->unapprove_split_thread." <em>*</em>", '', $form->generate_yes_no_radio('splitpostsunapprove', $daddyobb->input['splitpostsunapprove']));
	$form_container->output_row($lang->split_thread_subject, $lang->split_thread_subject_desc, $form->generate_text_box('splitpostsnewsubject', $daddyobb->input['splitpostsnewsubject'], array('id' => 'splitpostsnewsubject ')), 'newreplysubject');
	$form_container->output_row($lang->add_new_split_reply, $lang->add_new_split_reply_desc, $form->generate_text_area('splitpostsaddreply', $daddyobb->input['splitpostsaddreply'], array('id' => 'splitpostsaddreply')), 'splitpostsaddreply');
	$form_container->output_row($lang->split_reply_subject, $lang->split_reply_subject_desc, $form->generate_text_box('splitpostsreplysubject', $daddyobb->input['splitpostsreplysubject'], array('id' => 'splitpostsreplysubject')), 'splitpostsreplysubject');
	$form_container->end();
	
	$open_close = array(
		'' => $lang->no_change,
		'open' => $lang->open,
		'close' => $lang->close,
		'toggle' => $lang->toggle
	);
	
	$form_container = new FormContainer($lang->thread_moderation);
	$form_container->output_row($lang->approve_unapprove." <em>*</em>", '', $form->generate_select_box('approvethread', $approve_unapprove, $daddyobb->input['approvethread'], array('id' => 'approvethread')), 'approvethread');
	$form_container->output_row($lang->open_close_thread." <em>*</em>", '', $form->generate_select_box('openthread', $open_close, $daddyobb->input['openthread'], array('id' => 'openthread')), 'openthread');


	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"1\" {$move_checked[1]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_move_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"move_type\" value=\"2\" {$move_checked[2]} class=\"moves_check\" onclick=\"checkAction('move');\" style=\"vertical-align: middle;\" /> <strong>{$lang->move_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"move_2\" class=\"moves\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_move_to}</small></td>
					<td>".$form->generate_forum_select('move_1_forum', $daddyobb->input['move_1_forum'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->leave_redirect}</small></td>
					<td>".$form->generate_yes_no_radio('move_2_redirect', $daddyobb->input['move_2_redirect'])."</td>
				</tr>
				<tr>
					<td><small>{$lang->delete_redirect_after}</small></td>
					<td>".$form->generate_text_box('move_3_redirecttime', $daddyobb->input['move_3_redirecttime'], array('style' => 'width: 2em;'))." {$lang->days}</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('move');
	</script>";
	$form_container->output_row($lang->move_thread." <em>*</em>", $lang->move_thread_desc, $actions);
	
	$actions = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"1\" {$copy_checked[1]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->do_not_copy_thread}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"copy_type\" value=\"2\" {$copy_checked[2]} class=\"copys_check\" onclick=\"checkAction('copy');\" style=\"vertical-align: middle;\" /> <strong>{$lang->copy_thread}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"copy_2\" class=\"copys\">
			<table cellpadding=\"4\">
				<tr>
					<td><small>{$lang->forum_to_copy_to}</small></td>
					<td>".$form->generate_forum_select('copy_1_forum', $daddyobb->input['copy_1_forum'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction('copy');
	</script>";
	$form_container->output_row($lang->copy_thread." <em>*</em>", '', $actions);
	$form_container->output_row($lang->delete_thread." <em>*</em>", '', $form->generate_yes_no_radio('deletethread', $daddyobb->input['deletethread']));
	$form_container->output_row($lang->new_subject." <em>*</em>", $lang->new_subject_desc, $form->generate_text_box('newsubject', $daddyobb->input['newsubject']));
	$form_container->end();
	
	$form_container = new FormContainer($lang->add_new_reply);
	$form_container->output_row($lang->add_new_reply, $lang->add_new_reply_desc, $form->generate_text_area('newreply', $daddyobb->input['newreply'], array('id' => 'newreply')), 'newreply');
	$form_container->output_row($lang->reply_subject, $lang->reply_subject_desc, $form->generate_text_box('newreplysubject', $daddyobb->input['newreplysubject'], array('id' => 'newreplysubject')), 'newreplysubject');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_post_tool);

	$form->output_submit_wrapper($buttons);
	$form->end();
	
	$page->output_footer();
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_config_mod_tools_start");
	
	$page->output_header($lang->mod_tools." - ".$lang->thread_tools);
	
	$sub_tabs['thread_tools'] = array(
		'title' => $lang->thread_tools,
		'link' => "index.php?module=config/mod_tools",
		'description' => $lang->thread_tools_desc
	);
	$sub_tabs['add_thread_tool'] = array(
		'title'=> $lang->add_new_thread_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_thread_tool"
	);
	$sub_tabs['post_tools'] = array(
		'title' => $lang->post_tools,
		'link' => "index.php?module=config/mod_tools&amp;action=post_tools",
	);
	$sub_tabs['add_post_tool'] = array(
		'title'=> $lang->add_new_post_tool,
		'link' => "index.php?module=config/mod_tools&amp;action=add_post_tool"
	);
		
	$page->output_nav_tabs($sub_tabs, 'thread_tools');
	
	$table = new Table;
	$table->construct_header($lang->title);
	$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 2));
	
	$query = $db->simple_select('modtools', 'tid, name, description, type', "type='t'", array('order_by' => 'name'));
	while($tool = $db->fetch_array($query))
	{
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=edit_thread_tool&amp;tid={$tool['tid']}\"><strong>".htmlspecialchars_uni($tool['name'])."</strong></a><br /><small>".htmlspecialchars_uni($tool['description'])."</small>");
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=edit_thread_tool&amp;tid={$tool['tid']}\">{$lang->edit}</a>", array('width' => 100, 'class' => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config/mod_tools&amp;action=delete_thread_tool&amp;tid={$tool['tid']}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_thread_tool_deletion}')\">{$lang->delete}</a>", array('width' => 100, 'class' => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_thread_tools, array('colspan' => 3));
		$table->construct_row();
	}
	
	$table->output($lang->thread_tools);
	
	$page->output_footer();
}

?>