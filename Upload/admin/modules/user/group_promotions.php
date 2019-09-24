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

$page->add_breadcrumb_item($lang->user_group_promotions, "index.php?module=user/group_promotions");

$sub_tabs['usergroup_promotions'] = array(
	'title' => $lang->user_group_promotions,
	'link' => "index.php?module=user/group_promotions",
	'description' => $lang->user_group_promotions_desc
);

$sub_tabs['add_promotion'] = array(
	'title' => $lang->add_new_promotion,
	'link' => "index.php?module=user/group_promotions&amp;action=add",
	'description' => $lang->add_new_promotion_desc
);

$sub_tabs['promotion_logs'] = array(
	'title' => $lang->view_promotion_logs,
	'link' => "index.php?module=user/group_promotions&amp;action=logs",
	'description' => $lang->view_promotion_logs_desc
);

$plugins->run_hooks("admin_user_group_promotions_begin");

if($daddyobb->input['action'] == "disable")
{
	$plugins->run_hooks("admin_user_group_promotions_disable");
	
	if(!trim($daddyobb->input['pid']))
	{
		flash_message($lang->error_no_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	$query = $db->simple_select("promotions", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$promotion = $db->fetch_array($query);

	if(!$promotion['pid'])
	{
		flash_message($lang->error_invalid_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}

	$promotion = array(
		"enabled" => 0
	);
	$db->update_query("promotions", $promotion, "pid = '{$daddyobb->input['pid']}'");
	
	$plugins->run_hooks("admin_user_group_promotions_disable_commit");

	// Log admin action
	log_admin_action($promotion['pid'], $promotion['title']);

	flash_message($lang->success_promo_disabled, 'success');
	admin_redirect("index.php?module=user/group_promotions");
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_user_group_promotions_delete");
	
	if($daddyobb->input['no']) 
	{ 
		admin_redirect("index.php?module=user/group_promotions"); 
	} 
	
	if(!trim($daddyobb->input['pid']))
	{
		flash_message($lang->error_no_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	$query = $db->simple_select("promotions", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$promotion = $db->fetch_array($query);

	if(!$promotion['pid'])
	{
		flash_message($lang->error_invalid_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	if($daddyobb->request_method == "post")
	{
		$db->delete_query("promotions", "pid = '{$daddyobb->input['pid']}'");
		
		$plugins->run_hooks("admin_user_group_promotions_delete_commit");

		// Log admin action
		log_admin_action($promotion['pid'], $promotion['title']);

		flash_message($lang->success_promo_deleted, 'success');
		admin_redirect("index.php?module=user/group_promotions");
	}
	else
	{
		$page->output_confirm_action("index.php?module=user/group_promotions&amp;action=delete&amp;pid={$daddyobb->input['pid']}", $lang->confirm_promo_deletion); 
	}
}

if($daddyobb->input['action'] == "enable")
{
	$plugins->run_hooks("admin_user_group_promotions_enable");
	
	if(!trim($daddyobb->input['pid']))
	{
		flash_message($lang->error_no_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	$query = $db->simple_select("promotions", "*", "pid='".intval($daddyobb->input['pid'])."'");
	$promotion = $db->fetch_array($query);

	if(!$promotion['pid'])
	{
		flash_message($lang->error_invalid_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}

	$promotion = array(
		"enabled" => 1
	);
	
	$db->update_query("promotions", $promotion, "pid = '{$daddyobb->input['pid']}'");
	
	$plugins->run_hooks("admin_user_group_promotions_enable_commit");

	// Log admin action
	log_admin_action($promotion['pid'], $promotion['title']);

	flash_message($lang->success_promo_enabled, 'success');
	admin_redirect("index.php?module=user/group_promotions");
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_user_group_promotions_edit");
	
	if(!trim($daddyobb->input['pid']))
	{
		flash_message($lang->error_no_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	$query = $db->simple_select("promotions", "*", "pid = '{$daddyobb->input['pid']}'");
	$promotion = $db->fetch_array($query);
	
	if(!$promotion)
	{
		flash_message($lang->error_invalid_promo_id, 'error');
		admin_redirect("index.php?module=user/group_promotions");
	}
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_no_title;
		}

		if(!trim($daddyobb->input['description']))
		{
			$errors[] = $lang->error_no_desc;
		}
		
		if(!trim($daddyobb->input['requirements']))
		{
			$errors[] = $lang->error_no_requirements;
		}

		if(!trim($daddyobb->input['originalusergroup']))
		{
			$errors[] = $lang->error_no_orig_usergroup;
		}
		
		if(!trim($daddyobb->input['newusergroup']))
		{
			$errors[] = $lang->error_no_new_usergroup;
		}

		if(!trim($daddyobb->input['usergroupchangetype']))
		{
			$errors[] = $lang->error_no_usergroup_change_type;
		}

		if(!$errors)
		{
			if(in_array('*', $daddyobb->input['originalusergroup']))
			{
				$daddyobb->input['originalusergroup'] = '*';
			}
			else
			{
				$daddyobb->input['originalusergroup'] = implode(',', array_map('intval', $daddyobb->input['originalusergroup']));
			}
			
			$update_promotion = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"description" => $db->escape_string($daddyobb->input['description']),
				"posts" => intval($daddyobb->input['postcount']),
				"posttype" => $db->escape_string($daddyobb->input['posttype']),
				"registered" => intval($daddyobb->input['timeregistered']),
				"registeredtype" => $db->escape_string($daddyobb->input['timeregisteredtype']),
				"reputations" => intval($daddyobb->input['reputationcount']),
				"reputationtype" => $db->escape_string($daddyobb->input['reputationtype']),
				"requirements" => $db->escape_string(implode(",", $daddyobb->input['requirements'])),
				"originalusergroup" => $db->escape_string($daddyobb->input['originalusergroup']),
				"newusergroup" => intval($daddyobb->input['newusergroup']),
				"usergrouptype" => $db->escape_string($daddyobb->input['usergroupchangetype']),
				"enabled" => intval($daddyobb->input['enabled']),
				"logging" => intval($daddyobb->input['logging'])
			);
			
			$db->update_query("promotions", $update_promotion, "pid = '".intval($daddyobb->input['pid'])."'");
			
			$plugins->run_hooks("admin_user_group_promotions_edit_commit");

			// Log admin action
			log_admin_action($promotion['pid'], $daddyobb->input['title']);

			flash_message($lang->success_promo_updated, 'success');
			admin_redirect("index.php?module=user/group_promotions");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_promotion);
	$page->output_header($lang->user_group_promotions." - ".$lang->edit_promotion);

	$sub_tabs = array();
	$sub_tabs['edit_promotion'] = array(
		'title' => $lang->edit_promotion,
		'link' => "index.php?module=user/group_promotions&amp;action=edit",
		'description' => $lang->edit_promotion_desc
	);

	$page->output_nav_tabs($sub_tabs, 'edit_promotion');
	$form = new Form("index.php?module=user/group_promotions&amp;action=edit", "post", "edit");
	echo $form->generate_hidden_field("pid", $daddyobb->input['pid']);
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['title'] = $promotion['title'];
		$daddyobb->input['description'] = $promotion['description'];
		$daddyobb->input['requirements'] = explode(',', $promotion['requirements']);
		$daddyobb->input['reputationcount'] = $promotion['reputations'];
		$daddyobb->input['reputationtype'] = $promotion['reputationtype'];
		$daddyobb->input['postcount'] = $promotion['posts'];
		$daddyobb->input['posttype'] = $promotion['posttype'];
		$daddyobb->input['timeregistered'] = $promotion['registered'];
		$daddyobb->input['timeregisteredtype'] = $promotion['registeredtype'];
		$daddyobb->input['originalusergroup'] = explode(',', $promotion['originalusergroup']);
		$daddyobb->input['usergroupchangetype'] = $promotion['usergrouptype'];
		$daddyobb->input['newusergroup'] = $promotion['newusergroup'];
		$daddyobb->input['enabled'] = $promotion['enabled'];
		$daddyobb->input['logging'] = $promotion['logging'];
	}
	
	$form_container = new FormContainer($lang->edit_promotion);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_desc." <em>*</em>", "", $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');

	$options = array(
		"postcount" => $lang->post_count,
		"reputation" => $lang->reputation,
		"timeregistered" => $lang->time_registered
	);
	
	$form_container->output_row($lang->promo_requirements." <em>*</em>", $lang->promo_requirements_desc, $form->generate_select_box('requirements[]', $options, $daddyobb->input['requirements'], array('id' => 'requirements', 'multiple' => true, 'size' => 3)), 'requirements');
	
	$options_type = array(
		">" => $lang->greater_than,
		">=" => $lang->greater_than_or_equal_to,
		"=" => $lang->equal_to,
		"<=" => $lang->less_than_or_equal_to,
		"<" => $lang->less_than
	);
	
	$form_container->output_row($lang->reputation_count, $lang->reputation_count_desc, $form->generate_text_box('reputationcount', $daddyobb->input['reputationcount'], array('id' => 'reputationcount'))." ".$form->generate_select_box("reputationtype", $options_type, $daddyobb->input['reputationtype'], array('id' => 'reputationtype')), 'reputationcount');
	
	$form_container->output_row($lang->post_count, $lang->post_count_desc, $form->generate_text_box('postcount', $daddyobb->input['postcount'], array('id' => 'postcount'))." ".$form->generate_select_box("posttype", $options_type, $daddyobb->input['posttype'], array('id' => 'posttype')), 'postcount');
	
	$options = array(
		"hours" => $lang->hours,
		"days" => $lang->days,
		"weeks" => $lang->weeks,
		"months" => $lang->months,
		"years" => $lang->years
	);	
	
	$form_container->output_row($lang->time_registered, $lang->time_registered_desc, $form->generate_text_box('timeregistered', $daddyobb->input['timeregistered'], array('id' => 'timeregistered'))." ".$form->generate_select_box("timeregisteredtype", $options, $daddyobb->input['timeregisteredtype'], array('id' => 'timeregisteredtype')), 'timeregistered');
	
	$options = array();
	
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	$options['*'] = $lang->all_user_groups;
	while($usergroup = $db->fetch_array($query))
	{
		$options[(int)$usergroup['gid']] = $usergroup['title'];
	}

	$form_container->output_row($lang->orig_user_group." <em>*</em>", $lang->orig_user_group_desc, $form->generate_select_box('originalusergroup[]', $options, $daddyobb->input['originalusergroup'], array('id' => 'originalusergroup', 'multiple' => true, 'size' => 5)), 'originalusergroup');
	
	unset($options['*']); // Remove the all usergroups option
	$form_container->output_row($lang->new_user_group." <em>*</em>", $lang->new_user_group_desc, $form->generate_select_box('newusergroup', $options, $daddyobb->input['newusergroup'], array('id' => 'newusergroup')), 'newusergroup');

	$options = array(
		'primary' => $lang->primary_user_group,
		'secondary' => $lang->secondary_user_group
	);
	
	$form_container->output_row($lang->user_group_change_type." <em>*</em>", $lang->user_group_change_type_desc, $form->generate_select_box('usergroupchangetype', $options, $daddyobb->input['usergroupchangetype'], array('id' => 'usergroupchangetype')), 'usergroupchangetype');

	$form_container->output_row($lang->enabled." <em>*</em>", "", $form->generate_yes_no_radio("enabled", $daddyobb->input['enabled'], true));
	
	$form_container->output_row($lang->enable_logging." <em>*</em>", "", $form->generate_yes_no_radio("logging", $daddyobb->input['logging'], true));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->update_promotion);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_user_group_promotions_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_no_title;
		}

		if(!trim($daddyobb->input['description']))
		{
			$errors[] = $lang->error_no_desc;
		}
		
		if(!trim($daddyobb->input['requirements']))
		{
			$errors[] = $lang->error_no_requirements;
		}

		if(!trim($daddyobb->input['originalusergroup']))
		{
			$errors[] = $lang->error_no_orig_usergroup;
		}
		
		if(!trim($daddyobb->input['newusergroup']))
		{
			$errors[] = $lang->error_no_new_usergroup;
		}
		
		if(!trim($daddyobb->input['usergroupchangetype']))
		{
			$errors[] = $lang->error_no_usergroup_change_type;
		}
		
		if(!$errors)
		{
			if(in_array('*', $daddyobb->input['originalusergroup']))
			{
				$daddyobb->input['originalusergroup'] = '*';
			}
			else
			{
				$daddyobb->input['originalusergroup'] = implode(',', array_map('intval', $daddyobb->input['originalusergroup']));
			}
			
			$new_promotion = array(
				"title" => $db->escape_string($daddyobb->input['title']),
				"description" => $db->escape_string($daddyobb->input['description']),
				"posts" => intval($daddyobb->input['postcount']),
				"posttype" => $db->escape_string($daddyobb->input['posttype']),
				"registered" => intval($daddyobb->input['timeregistered']),
				"registeredtype" => $db->escape_string($daddyobb->input['timeregisteredtype']),
				"reputations" => intval($daddyobb->input['reputationcount']),
				"reputationtype" => $db->escape_string($daddyobb->input['reputationtype']),
				"requirements" => $db->escape_string(implode(",", $daddyobb->input['requirements'])),
				"originalusergroup" => $db->escape_string($daddyobb->input['originalusergroup']),
				"usergrouptype" => $db->escape_string($daddyobb->input['usergroupchangetype']),
				"newusergroup" => intval($daddyobb->input['newusergroup']),
				"enabled" => intval($daddyobb->input['enabled']),
				"logging" => intval($daddyobb->input['logging'])
			);
			
			$pid = $db->insert_query("promotions", $new_promotion);
			
			$plugins->run_hooks("admin_user_group_promotions_add_commit");

			// Log admin action
			log_admin_action($pid, $daddyobb->input['title']);
			
			flash_message($lang->success_promo_added, 'success');
			admin_redirect("index.php?module=user/group_promotions");
		}
	}
	$page->add_breadcrumb_item($lang->add_new_promotion);
	$page->output_header($lang->user_group_promotions." - ".$lang->add_new_promotion);
	
	$sub_tabs['usergroup_promotions'] = array(
		'title' => $lang->user_group_promotions,
		'link' => "index.php?module=user/group_promotions"
	);

	$sub_tabs['add_promotion'] = array(
		'title' => $lang->add_new_promotion,
		'link' => "index.php?module=user/group_promotions&amp;action=add",
		'description' => $lang->add_new_promotion_desc
	);

	$sub_tabs['promotion_logs'] = array(
		'title' => $lang->view_promotion_logs,
		'link' => "index.php?module=user/group_promotions&amp;action=logs"
	);

	$page->output_nav_tabs($sub_tabs, 'add_promotion');
	$form = new Form("index.php?module=user/group_promotions&amp;action=add", "post", "add");
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$daddyobb->input['reputationcount'] = '0';
		$daddyobb->input['postcount'] = '0';
		$daddyobb->input['timeregistered'] = '0';
		$daddyobb->input['timeregisteredtype'] = 'days';
		$daddyobb->input['originalusergroup'] = '*';
		$daddyobb->input['newusergroup'] = '2';
		$daddyobb->input['enabled'] = '1';
		$daddyobb->input['logging'] = '1';
	}
	$form_container = new FormContainer($lang->add_new_promotion);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->short_desc." <em>*</em>", "", $form->generate_text_box('description', $daddyobb->input['description'], array('id' => 'description')), 'description');

	$options = array(
		"postcount" => $lang->post_count,
		"reputation" => $lang->reputation,
		"timeregistered" => $lang->time_registered
	);
	
	$form_container->output_row($lang->promo_requirements." <em>*</em>", $lang->promo_requirements_desc, $form->generate_select_box('requirements[]', $options, $daddyobb->input['requirements'], array('id' => 'requirements', 'multiple' => true, 'size' => 3)), 'requirements');
	
	$options_type = array(
		">" => $lang->greater_than,
		">=" => $lang->greater_than_or_equal_to,
		"=" => $lang->equal_to,
		"<=" => $lang->less_than_or_equal_to,
		"<" => $lang->less_than
	);
	
	$form_container->output_row($lang->reputation_count, $lang->reputation_count_desc, $form->generate_text_box('reputationcount', $daddyobb->input['reputationcount'], array('id' => 'reputationcount'))." ".$form->generate_select_box("reputationtype", $options_type, $daddyobb->input['reputationtype'], array('id' => 'reputationtype')), 'reputationcount');
	
	$form_container->output_row($lang->post_count, $lang->post_count_desc, $form->generate_text_box('postcount', $daddyobb->input['postcount'], array('id' => 'postcount'))." ".$form->generate_select_box("posttype", $options_type, $daddyobb->input['posttype'], array('id' => 'posttype')), 'postcount');
	
	$options = array(
		"hours" => $lang->hours,
		"days" => $lang->days,
		"weeks" => $lang->weeks,
		"months" => $lang->months,
		"years" => $lang->years
	);	
	
	$form_container->output_row($lang->time_registered, $lang->time_registered_desc, $form->generate_text_box('timeregistered', $daddyobb->input['timeregistered'], array('id' => 'timeregistered'))." ".$form->generate_select_box("timeregisteredtype", $options, $daddyobb->input['timeregisteredtype'], array('id' => 'timeregisteredtype')), 'timeregistered');
	$options = array();
	
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	$options['*'] = $lang->all_user_groups;
	while($usergroup = $db->fetch_array($query))
	{
		$options[(int)$usergroup['gid']] = $usergroup['title'];
	}

	$form_container->output_row($lang->orig_user_group." <em>*</em>", $lang->orig_user_group_desc, $form->generate_select_box('originalusergroup[]', $options, $daddyobb->input['originalusergroup'], array('id' => 'originalusergroup', 'multiple' => true, 'size' => 5)), 'originalusergroup');

	unset($options['*']);
	$form_container->output_row($lang->new_user_group." <em>*</em>", $lang->new_user_group_desc, $form->generate_select_box('newusergroup', $options, $daddyobb->input['newusergroup'], array('id' => 'newusergroup')), 'newusergroup');
	
	$options = array(
		'primary' => $lang->primary_user_group,
		'secondary' => $lang->secondary_user_group
	);
	
	$form_container->output_row($lang->user_group_change_type." <em>*</em>", $lang->user_group_change_type_desc, $form->generate_select_box('usergroupchangetype', $options, $daddyobb->input['usergroupchangetype'], array('id' => 'usergroupchangetype')), 'usergroupchangetype');
	
	$form_container->output_row($lang->enabled." <em>*</em>", "", $form->generate_yes_no_radio("enabled", $daddyobb->input['enabled'], true));
	
	$form_container->output_row($lang->enable_logging." <em>*</em>", "", $form->generate_yes_no_radio("logging", $daddyobb->input['logging'], true));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->update_promotion);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "logs")
{
	$plugins->run_hooks("admin_user_group_promotions_logs");
	
	if($daddyobb->input['page'] && $daddyobb->input['page'] > 1)
	{
		$daddyobb->input['page'] = intval($daddyobb->input['page']);
		$start = ($daddyobb->input['page']*20)-20;
	}
	else
	{
		$daddyobb->input['page'] = 1;
		$start = 0;
	}
	
	$page->add_breadcrumb_item($lang->promotion_logs);
	$page->output_header($lang->user_group_promotions." - ".$lang->promotion_logs);
	
	$page->output_nav_tabs($sub_tabs, 'promotion_logs');

	$table = new Table;
	$table->construct_header($lang->promoted_user, array("class" => "align_center", "width" => '20%'));
	$table->construct_header($lang->user_group_change_type, array("class" => "align_center", "width" => '20%'));
	$table->construct_header($lang->orig_user_group, array("class" => "align_center", "width" => '20%'));
	$table->construct_header($lang->new_user_group, array("class" => "align_center", "width" => '20%'));
	$table->construct_header($lang->time_promoted, array("class" => "align_center", "width" => '20%'));

	$query = $db->query("
		SELECT pl.*,u.username
		FROM ".TABLE_PREFIX."promotionlogs pl
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=pl.uid)
		ORDER BY dateline DESC 
		LIMIT {$start}, 20
	");
	while($log = $db->fetch_array($query))
	{
		$log['username'] = "<a href=\"index.php?module=user/view&amp;action=edit&amp;uid={$log['uid']}\">".htmlspecialchars_uni($log['username'])."</a>";
		
		if($log['type'] == "secondary" || (!empty($log['oldusergroup']) && strstr(",", $log['oldusergroup'])))
		{
			$log['oldusergroup'] = "<i>".$lang->multiple_usergroups."</i>";
			$log['newusergroup'] = htmlspecialchars_uni($groupscache[$log['newusergroup']]['title']);
		}
		else
		{
			$log['oldusergroup'] = htmlspecialchars_uni($groupscache[$log['oldusergroup']]['title']);
			$log['newusergroup'] = htmlspecialchars_uni($groupscache[$log['newusergroup']]['title']);
		}
		
		if($log['type'] == "secondary")
		{
			$log['type'] = $lang->secondary;
		}
		else
		{
			$log['type'] = $lang->primary;
		}
		
		$log['dateline'] = date($daddyobb->settings['dateformat'], $log['dateline']).", ".date($daddyobb->settings['timeformat'], $log['dateline']);
		$table->construct_cell($log['username']);
		$table->construct_cell($log['type'], array('style' => 'text-align: center;'));
		$table->construct_cell($log['oldusergroup'], array('style' => 'text-align: center;'));
		$table->construct_cell($log['newusergroup'], array('style' => 'text-align: center;'));
		$table->construct_cell($log['dateline'], array('style' => 'text-align: center;'));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_promotion_logs, array("colspan" => "5"));
		$table->construct_row();
	}
	
	$table->output($lang->promotion_logs);
	
	$query = $db->simple_select("promotionlogs", "COUNT(plid) as promotionlogs");
	$total_rows = $db->fetch_field($query, "promotionlogs");
	
	echo "<br />".draw_admin_pagination($daddyobb->input['page'], "20", $total_rows, "index.php?module=user/group_promotions&amp;action=logs&amp;page={page}");
	
	$page->output_footer();
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_user_group_promotions_start");
	
	$page->output_header($lang->promotion_manager);
	
	$page->output_nav_tabs($sub_tabs, 'usergroup_promotions');

	$table = new Table;
	$table->construct_header($lang->promotion);
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

	$query = $db->simple_select("promotions", "*", "", array("order_by" => "title", "order_dir" => "asc"));
	while($promotion = $db->fetch_array($query))
	{
		$promotion['title'] = htmlspecialchars_uni($promotion['title']);
		$promotion['description'] = htmlspecialchars_uni($promotion['description']);
		$table->construct_cell("<div><strong><a href=\"index.php?module=user/group_promotions&amp;action=edit&amp;pid={$promotion['pid']}\">{$promotion['title']}</a></strong><br /><small>{$promotion['description']}</small></div>");

		$popup = new PopupMenu("promotion_{$promotion['pid']}", $lang->options);
		$popup->add_item($lang->edit_promotion, "index.php?module=user/group_promotions&amp;action=edit&amp;pid={$promotion['pid']}");
		if($promotion['enabled'] == 1)
		{
			$popup->add_item($lang->disable_promotion, "index.php?module=user/group_promotions&amp;action=disable&amp;pid={$promotion['pid']}");
		}
		else
		{
			$popup->add_item($lang->enable_promotion, "index.php?module=user/group_promotions&amp;action=enable&amp;pid={$promotion['pid']}");
		}
		$popup->add_item($lang->delete_promotion, "index.php?module=user/group_promotions&amp;action=delete&amp;pid={$promotion['pid']}&amp;my_post_key={$daddyobb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_promo_deletion}')");
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_promotions_set, array("colspan" => "2"));
		$table->construct_row();
	}
	
	$table->output($lang->user_group_promotions);
	
	$page->output_footer();
}

?>