<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright � 2009 DaddyoBB Group, All Rights Reserved
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

$page->add_breadcrumb_item($lang->mycode, "index.php?module=config/mycode");

$plugins->run_hooks("admin_config_mycode_begin");

if($daddyobb->input['action'] == "toggle_status")
{
	$plugins->run_hooks("admin_config_mycode_toggle_status");
	
	$query = $db->simple_select("mycode", "*", "cid='".intval($daddyobb->input['cid'])."'");
	$mycode = $db->fetch_array($query);
	
	if(!$mycode['cid'])
	{
		flash_message($lang->error_invalid_mycode, 'error');
		admin_redirect("index.php?module=config/mycode");
	}

	if($mycode['active'] == 1)
	{
		$new_status = 0;
		$phrase = $lang->success_deactivated_mycode;
	}
	else
	{
		$new_status = 1;
		$phrase = $lang->success_activated_mycode;
	}
	$mycode_update = array(
		'active' => $new_status,
	);

	$db->update_query("mycode", $mycode_update, "cid='".intval($daddyobb->input['cid'])."'");

	$cache->update_mycode();
	
	$plugins->run_hooks("admin_config_mycode_toggle_status_commit");

	// Log admin action
	log_admin_action($mycode['cid'], $mycode['title'], $new_status);

	flash_message($phrase, 'success');
	admin_redirect('index.php?module=config/mycode');
}

if($daddyobb->input['action'] == "xmlhttp_test_mycode" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("admin_config_mycode_xmlhttp_test_mycode_start");
	
	// Send no cache headers
	header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-type: text/html");
	
	$sandbox = test_regex($daddyobb->input['regex'], $daddyobb->input['replacement'], $daddyobb->input['test_value']);
	
	$plugins->run_hooks("admin_config_mycode_xmlhttp_test_mycode_end");
	
	echo $sandbox['actual'];
	exit;
}

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_config_mycode_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!trim($daddyobb->input['regex']))
		{
			$errors[] = $lang->error_missing_regex;
		}
		
		if(!trim($daddyobb->input['replacement']))
		{
			$errors[] = $lang->error_missing_replacement;
		}
		
		if($daddyobb->input['test'])
		{
			$errors[] = $lang->changes_not_saved;
			$sandbox = test_regex($daddyobb->input['regex'], $daddyobb->input['replacement'], $daddyobb->input['test_value']);
		}

		if(!$errors)
		{
			$new_mycode = array(
				'title'	=> $db->escape_string($daddyobb->input['title']),
				'description' => $db->escape_string($daddyobb->input['description']),
				'regex' => $db->escape_string($daddyobb->input['regex']),
				'replacement' => $db->escape_string($daddyobb->input['replacement']),
				'active' => $db->escape_string($daddyobb->input['active']),
				'parseorder' => intval($daddyobb->input['parseorder'])
			);

			$cid = $db->insert_query("mycode", $new_mycode);

			$cache->update_mycode();
			
			$plugins->run_hooks("admin_config_mycode_add_commit");

			// Log admin action
			log_admin_action($cid, $daddyobb->input['title']);

			flash_message($lang->success_added_mycode, 'success');
			admin_redirect('index.php?module=config/mycode');
		}
	}
	
	$sub_tabs['mycode'] = array(
		'title'	=> $lang->mycode,
		'link' => "index.php?module=config/mycode",
		'description' => $lang->mycode_desc
	);

	$sub_tabs['add_new_mycode'] = array(
		'title'	=> $lang->add_new_mycode,
		'link' => "index.php?module=config/mycode&amp;action=add",
		'description' => $lang->add_new_mycode_desc
	);
	
	$page->extra_header .= "
	<script type=\"text/javascript\">
	var my_post_key = '".$daddyobb->post_code."';
	</script>";

	$page->add_breadcrumb_item($lang->add_new_mycode);
	$page->output_header($lang->custom_mycode." - ".$lang->add_new_mycode);
	$page->output_nav_tabs($sub_tabs, 'add_new_mycode');

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['active'] = 1;
	}

	$form = new Form("index.php?module=config/mycode&amp;action=add", "post", "add");
	$form_container = new FormContainer($lang->add_mycode);
	$form_container->output_row($lang->title." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description, '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');
	$form_container->output_row($lang->regular_expression." <em>*</em>", $lang->regular_expression_desc.'<br /><strong>'.$lang->example.'</strong> \[b\](.*?)\[/b\]', $form->generate_text_area('regex', $daddyobb->input['regex'], array('id' => 'regex')), 'regex');
	$form_container->output_row($lang->replacement." <em>*</em>", $lang->replacement_desc.'<br /><strong>'.$lang->example.'</strong> &lt;strong&gt;$1&lt;/strong&gt;', $form->generate_text_area('replacement', $daddyobb->input['replacement'], array('id' => 'replacement')), 'replacement');
	$form_container->output_row($lang->enabled." <em>*</em>", '', $form->generate_yes_no_radio('active', $daddyobb->input['active']));
	$form_container->output_row($lang->parse_order, $lang->parse_order_desc, $form->generate_text_box('parseorder', $daddyobb->input['parseorder'], array('id' => 'parseorder')), 'parseorder');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_mycode);
	$form->output_submit_wrapper($buttons);
	
	// Sandbox
	echo "<br />\n";
	$form_container = new FormContainer($lang->sandbox);
	$form_container->output_row($lang->sandbox_desc);
	$form_container->output_row($lang->test_value, $lang->test_value_desc, $form->generate_text_area('test_value', $daddyobb->input['test_value'], array('id' => 'test_value'))."<br />".$form->generate_submit_button($lang->test, array('id' => 'test', 'name' => 'test')), 'test_value');
	$form_container->output_row($lang->result_html, $lang->result_html_desc, $form->generate_text_area('result_html', $sandbox['html'], array('id' => 'result_html', 'disabled' => 1)), 'result_html');
	$form_container->output_row($lang->result_actual, $lang->result_actual_desc, "<div id=\"result_actual\">{$sandbox['actual']}</div>");
	$form_container->end();
	echo '<script type="text/javascript" src="./jscripts/mycode_sandbox.js"></script>';
	echo '<script type="text/javascript">
//<![CDATA[
Event.observe(window, "load", function() {
    new MyCodeSandbox("./index.php?module=config/mycode&action=xmlhttp_test_mycode", $("test"), $("regex"), $("replacement"), $("test_value"), $("result_html"), $("result_actual"));
});
//]]>
</script>';

	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_config_mycode_edit");
	
	$query = $db->simple_select("mycode", "*", "cid='".intval($daddyobb->input['cid'])."'");
	$mycode = $db->fetch_array($query);
	
	if(!$mycode['cid'])
	{
		flash_message($lang->error_invalid_mycode, 'error');
		admin_redirect("index.php?module=config/mycode");
	}

	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}

		if(!trim($daddyobb->input['regex']))
		{
			$errors[] = $lang->error_missing_regex;
		}
		
		if(!trim($daddyobb->input['replacement']))
		{
			$errors[] = $lang->error_missing_replacement;
		}
		
		if($daddyobb->input['test'])
		{
			$errors[] = $lang->changes_not_saved;
			$sandbox = test_regex($daddyobb->input['regex'], $daddyobb->input['replacement'], $daddyobb->input['test_value']);
		}

		if(!$errors)
		{
			$mycode = array(
				'title'	=> $db->escape_string($daddyobb->input['title']),
				'description' => $db->escape_string($daddyobb->input['description']),
				'regex' => $db->escape_string($daddyobb->input['regex']),
				'replacement' => $db->escape_string($daddyobb->input['replacement']),
				'active' => $db->escape_string($daddyobb->input['active']),
				'parseorder' => intval($daddyobb->input['parseorder'])
			);

			$db->update_query("mycode", $mycode, "cid='".intval($daddyobb->input['cid'])."'");

			$cache->update_mycode();
			
			$plugins->run_hooks("admin_config_mycode_edit_commit");

			// Log admin action
			log_admin_action($mycode['cid'], $daddyobb->input['title']);

			flash_message($lang->success_updated_mycode, 'success');
			admin_redirect('index.php?module=config/mycode');
		}
	}

	$sub_tabs['edit_mycode'] = array(
		'title'	=> $lang->edit_mycode,
		'link' => "index.php?module=config/mycode&amp;action=edit",
		'description' => $lang->edit_mycode_desc
	);
	
	$page->extra_header .= "
	<script type=\"text/javascript\">
	var my_post_key = '".$daddyobb->post_code."';
	</script>";
	
	$page->add_breadcrumb_item($lang->edit_mycode);
	$page->output_header($lang->custom_mycode." - ".$lang->edit_mycode);
	$page->output_nav_tabs($sub_tabs, 'edit_mycode');

	$form = new Form("index.php?module=config/mycode&amp;action=edit", "post", "edit");
	echo $form->generate_hidden_field('cid', $mycode['cid']);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input = $mycode;
	}

	$form_container = new FormContainer($lang->edit_mycode);
	$form_container->output_row($lang->title." <em>*</em>", '', $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_description, '', $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');
	$form_container->output_row($lang->regular_expression." <em>*</em>", $lang->regular_expression_desc.'<br /><strong>'.$lang->example.'</strong> \[b\](.*?)\[/b\]', $form->generate_text_area('regex', $daddyobb->input['regex'], array('id' => 'regex')), 'regex');
	$form_container->output_row($lang->replacement." <em>*</em>", $lang->replacement_desc.'<br /><strong>'.$lang->example.'</strong> &lt;strong&gt;$1&lt;/strong&gt;', $form->generate_text_area('replacement', $daddyobb->input['replacement'], array('id' => 'replacement')), 'replacement');
	$form_container->output_row($lang->enabled." <em>*</em>", '', $form->generate_yes_no_radio('active', $daddyobb->input['active']));
	$form_container->output_row($lang->parse_order, $lang->parse_order_desc, $form->generate_text_box('parseorder', $daddyobb->input['parseorder'], array('id' => 'parseorder')), 'parseorder');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_mycode);

	$form->output_submit_wrapper($buttons);

	// Sandbox
	echo "<br />\n";
	$form_container = new FormContainer($lang->sandbox);
	$form_container->output_row($lang->sandbox_desc);
	$form_container->output_row($lang->test_value, $lang->test_value_desc, $form->generate_text_area('test_value', $daddyobb->input['test_value'], array('id' => 'test_value'))."<br />".$form->generate_submit_button($lang->test, array('id' => 'test', 'name' => 'test')), 'test_value');
	$form_container->output_row($lang->result_html, $lang->result_html_desc, $form->generate_text_area('result_html', $sandbox['html'], array('id' => 'result_html', 'disabled' => 1)), 'result_html');
	$form_container->output_row($lang->result_actual, $lang->result_actual_desc, "<div id=\"result_actual\">{$sandbox['actual']}</div>");
	$form_container->end();
	echo '<script type="text/javascript" src="./jscripts/mycode_sandbox.js"></script>';
	echo '<script type="text/javascript">

Event.observe(window, "load", function() {
//<![CDATA[
    new MyCodeSandbox("./index.php?module=config/mycode&action=xmlhttp_test_mycode", $("test"), $("regex"), $("replacement"), $("test_value"), $("result_html"), $("result_actual"));
});
//]]>
</script>';

	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_config_mycode_delete");
	
	$query = $db->simple_select("mycode", "*", "cid='".intval($daddyobb->input['cid'])."'");
	$mycode = $db->fetch_array($query);
	
	if(!$mycode['cid'])
	{
		flash_message($lang->error_invalid_mycode, 'error');
		admin_redirect("index.php?module=config/mycode");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=config/mycode");
	}

	if($daddyobb->request_method == "post")
	{
		$db->delete_query("mycode", "cid='{$mycode['cid']}'");

		$cache->update_mycode();
		
		$plugins->run_hooks("admin_config_mycode_delete_commit");

		// Log admin action
		log_admin_action($mycode['cid'], $mycode['title']);

		flash_message($lang->success_deleted_mycode, 'success');
		admin_redirect("index.php?module=config/mycode");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config/mycode&amp;action=delete&amp;cid={$mycode['cid']}", $lang->confirm_mycode_deletion);
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_config_mycode_start");
	
	$page->output_header($lang->custom_mycode);

	$sub_tabs['mycode'] = array(
		'title'	=> $lang->mycode,
		'link' => "index.php?module=config/mycode",
		'description' => $lang->mycode_desc
	);

	$sub_tabs['add_new_mycode'] = array(
		'title'	=> $lang->add_new_mycode,
		'link' => "index.php?module=config/mycode&amp;action=add"
	);

	$page->output_nav_tabs($sub_tabs, 'mycode');

	$table = new Table;
	$table->construct_header($lang->title);
	$table->construct_header($lang->controls, array('class' => 'align_center', 'width' => 150));

	$query = $db->simple_select("mycode", "*", "", array('order_by' => 'parseorder'));
	while($mycode = $db->fetch_array($query))
	{
		if($mycode['active'] == 1)
		{
			$phrase = $lang->deactivate_mycode;
			$indicator = '';
		}
		else
		{
			$phrase = $lang->activate_mycode;
			$indicator = "<div class=\"float_right\"><small>{$lang->deactivated}</small></div>";
		}
		
		if($mycode['description'])
		{
			$mycode['description'] = "<small>{$mycode['description']}</small>";
		}
		
		$table->construct_cell("{$indicator}<strong><a href=\"index.php?module=config/mycode&amp;action=edit&amp;cid={$mycode['cid']}\">{$mycode['title']}</a></strong><br />{$mycode['description']}");

		$popup = new PopupMenu("mycode_{$mycode['cid']}", $lang->options);
		$popup->add_item($lang->edit_mycode, "index.php?module=config/mycode&amp;action=edit&amp;cid={$mycode['cid']}");
		$popup->add_item($phrase, "index.php?module=config/mycode&amp;action=toggle_status&amp;cid={$mycode['cid']}");
		$popup->add_item($lang->delete_mycode, "index.php?module=config/mycode&amp;action=delete&amp;cid={$mycode['cid']}&amp;my_post_key={$daddyobb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_mycode_deletion}')");
		$table->construct_cell($popup->fetch(), array('class' => 'align_center'));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_mycode, array('colspan' => 2));
		$table->construct_row();
	}

	$table->output($lang->custom_mycode);

	$page->output_footer();
}

function test_regex($regex, $replacement, $test)
{
	$array = array();
	$array['actual'] = @preg_replace("#".$regex."#si", $replacement, $test);
	$array['html'] = htmlspecialchars($array['actual']);
	return $array;
}
?>