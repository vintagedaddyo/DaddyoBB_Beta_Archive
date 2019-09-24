<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 21:15 19.12.2008
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_DADDYOBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_DADDYOBB is defined.");
}

$page->add_breadcrumb_item($lang->forum_announcements, "index.php?module=forum/announcements");

if($daddyobb->input['action'] == "add" || !$daddyobb->input['action'])
{
	$sub_tabs['forum_announcements'] = array(
		'title' => $lang->forum_announcements,
		'link' => "index.php?module=forum/announcements",
		'description' => $lang->forum_announcements_desc
	);

	$sub_tabs['add_announcement'] = array(
		'title' => $lang->add_announcement,
		'link' => "index.php?module=forum/announcements&amp;action=add",
		'description' => $lang->add_announcement_desc
	);
}
else if($daddyobb->input['action'] == "edit")
{
	$sub_tabs['forum_announcements'] = array(
		'title' => $lang->forum_announcements,
		'link' => "index.php?module=forum/announcements",
		'description' => $lang->forum_announcements_desc
	);
	
	$sub_tabs['update_announcement'] = array(
		'title' => $lang->update_announcement,
		'link' => "index.php?module=forum/announcements&amp;action=add",
		'description' => $lang->update_announcement_desc
	);
}

$plugins->run_hooks("admin_forum_announcements_begin");

if($daddyobb->input['action'] == "add")
{
	$plugins->run_hooks("admin_forum_announcements_add");
	
	if($daddyobb->request_method == "post")
	{
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(!trim($daddyobb->input['message']))
		{
			$errors[] = $lang->error_missing_message;
		}
		
		if(!trim($daddyobb->input['fid']))
		{
			$errors[] = $lang->error_missing_forum;
		}
		
		if(!$errors)
		{
			$startdate = @explode(" ", $daddyobb->input['starttime_time']);
			$startdate = @explode(":", $startdate[0]);
			$enddate = @explode(" ", $daddyobb->input['endtime_time']);
			$enddate = @explode(":", $enddate[0]);
		
			if(stristr($daddyobb->input['starttime_time'], "pm"))
			{
				$startdate[0] = 12+$startdate[0];
				if($startdate[0] >= 24)
				{
					$startdate[0] = "00";
				}
			}
			
			if(stristr($daddyobb->input['endtime_time'], "pm"))
			{
				$enddate[0] = 12+$enddate[0];
				if($enddate[0] >= 24)
				{
					$enddate[0] = "00";
				}
			}
			
			$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
			if(!in_array($daddyobb->input['starttime_month'], $months))
			{
				$daddyobb->input['starttime_month'] = 1;
			}
			
			$startdate = gmmktime(intval($startdate[0]), intval($startdate[1]), 0, (int)$daddyobb->input['starttime_month'], intval($daddyobb->input['starttime_day']), intval($daddyobb->input['starttime_year']));
			
			if($daddyobb->input['endtime_type'] == "2")
			{
				$enddate = '0';
			}
			else
			{
				if(!in_array($daddyobb->input['endtime_month'], $months))
				{
					$daddyobb->input['endtime_month'] = 1;
				}
				$enddate = gmmktime(intval($enddate[0]), intval($enddate[1]), 0, (int)$daddyobb->input['endtime_month'], intval($daddyobb->input['endtime_day']), intval($daddyobb->input['endtime_year']));
			}
			
			$insert_announcement = array(
				"fid" => $daddyobb->input['fid'],
				"uid" => $daddyobb->user['uid'],
				"subject" => $db->escape_string($daddyobb->input['title']),
				"message" => $db->escape_string($daddyobb->input['message']),
				"startdate" => $startdate,
				"enddate" => $enddate,
				"allowhtml" => $db->escape_string($daddyobb->input['allowhtml']),
				"allowmycode" => $db->escape_string($daddyobb->input['allowmycode']),
				"allowsmilies" => $db->escape_string($daddyobb->input['allowsmilies']),
			);
	
			$aid = $db->insert_query("announcements", $insert_announcement);
			
			$plugins->run_hooks("admin_forum_announcements_add_commit");
	
			// Log admin action
			log_admin_action($aid, $daddyobb->input['title']);
	
			flash_message($lang->success_added_announcement, 'success');
			admin_redirect("index.php?module=forum/announcements");
		}
	}
	
	$page->add_breadcrumb_item($lang->add_an_announcement);
	$page->output_header($lang->add_an_announcement);
	$page->output_nav_tabs($sub_tabs, "add_announcement");

	$form = new Form("index.php?module=forum/announcements&amp;action=add", "post");
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	
	if($daddyobb->input['endtime_type'] == "1")
	{
		$endtime_checked[1] = "checked=\"checked\"";
		$endtime_checked[2] = "";
	}
	else
	{		
		$endtime_checked[1] = "";
		$endtime_checked[2] = "checked=\"checked\"";
	}
	
	if(!$daddyobb->input['starttime_time'])
	{
		$start_time = explode("-", gmdate("g-i-a", TIME_NOW));
		$daddyobb->input['starttime_time'] = $start_time[0].":".$start_time[1]." ".$start_time[2];
	}
	
	if(!$daddyobb->input['endtime_time'])
	{
		$end_time = explode("-", gmdate("g-i-a", TIME_NOW));
		$daddyobb->input['endtime_time'] = $end_time[0].":".$end_time[1]." ".$end_time[2];
	}
	
	if($daddyobb->input['starttime_day'])
	{
		$startday = intval($daddyobb->input['starttime_day']);
	}
	else
	{
		$startday = gmdate("j", TIME_NOW);
	}
	
	if($daddyobb->input['endtime_day'])
	{
		$endday = intval($daddyobb->input['endtime_day']);
	}
	else
	{
		$endday = gmdate("j", TIME_NOW);
	}
	
	for($i = 1; $i <= 31; ++$i)
	{
		if($startday == $i)
		{
			$startdateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$startdateday .= "<option value=\"$i\">$i</option>\n";
		}
		
		if($endday == $i)
		{
			$enddateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$enddateday .= "<option value=\"$i\">$i</option>\n";
		}
	}
	
	if($daddyobb->input['starttime_month'])
	{
		$startmonth = intval($daddyobb->input['starttime_month']);
		$startmonthsel[$startmonth] = "selected=\"selected\"";
	}
	else
	{
		$startmonth = gmdate("m", TIME_NOW);
		$startmonthsel[$startmonth] = "selected=\"selected\"";
	}
	
	if($daddyobb->input['endtime_month'])
	{
		$endmonth = intval($daddyobb->input['endtime_month']);
		$endmonthsel[$endmonth] = "selected=\"selected\"";
	}
	else
	{
		$endmonth = gmdate("m", TIME_NOW);
		$endmonthsel[$endmonth] = "selected=\"selected\"";
	}
	
	$startdatemonth .= "<option value=\"01\" {$startmonthsel['01']}>{$lang->january}</option>\n";
	$enddatemonth .= "<option value=\"01\" {$endmonthsel['01']}>{$lang->january}</option>\n";
	$startdatemonth .= "<option value=\"02\" {$startmonthsel['02']}>{$lang->february}</option>\n";
	$enddatemonth .= "<option value=\"02\" {$endmonthsel['02']}>{$lang->february}</option>\n";
	$startdatemonth .= "<option value=\"03\" {$startmonthsel['03']}>{$lang->march}</option>\n";
	$enddatemonth .= "<option value=\"03\" {$endmonthsel['03']}>{$lang->march}</option>\n";
	$startdatemonth .= "<option value=\"04\" {$startmonthsel['04']}>{$lang->april}</option>\n";
	$enddatemonth .= "<option value=\"04\" {$endmonthsel['04']}>{$lang->april}</option>\n";
	$startdatemonth .= "<option value=\"05\" {$startmonthsel['05']}>{$lang->may}</option>\n";
	$enddatemonth .= "<option value=\"05\" {$endmonthsel['05']}>{$lang->may}</option>\n";
	$startdatemonth .= "<option value=\"06\" {$startmonthsel['06']}>{$lang->june}</option>\n";
	$enddatemonth .= "<option value=\"06\" {$endmonthsel['06']}>{$lang->june}</option>\n";
	$startdatemonth .= "<option value=\"07\" {$startmonthsel['07']}>{$lang->july}</option>\n";
	$enddatemonth .= "<option value=\"07\" {$endmonthsel['07']}>{$lang->july}</option>\n";
	$startdatemonth .= "<option value=\"08\" {$startmonthsel['08']}>{$lang->august}</option>\n";
	$enddatemonth .= "<option value=\"08\" {$endmonthsel['08']}>{$lang->august}</option>\n";
	$startdatemonth .= "<option value=\"09\" {$startmonthsel['09']}>{$lang->september}</option>\n";
	$enddatemonth .= "<option value=\"09\" {$endmonthsel['09']}>{$lang->september}</option>\n";
	$startdatemonth .= "<option value=\"10\" {$startmonthsel['10']}>{$lang->october}</option>\n";
	$enddatemonth .= "<option value=\"10\" {$endmonthsel['10']}>{$lang->october}</option>\n";
	$startdatemonth .= "<option value=\"11\" {$startmonthsel['11']}>{$lang->november}</option>\n";
	$enddatemonth .= "<option value=\"11\" {$endmonthsel['11']}>{$lang->november}</option>\n";
	$startdatemonth .= "<option value=\"12\" {$startmonthsel['12']}>{$lang->december}</option>\n";
	$enddatemonth .= "<option value=\"12\" {$endmonthsel['12']}>{$lang->december}</option>\n";
	
	if($daddyobb->input['starttime_year'])
	{
		$startdateyear = intval($daddyobb->input['starttime_year']);
	}
	else
	{
		$startdateyear = gmdate("Y", TIME_NOW);
	}
	
	if($daddyobb->input['endtime_year'])
	{
		$enddateyear = intval($daddyobb->input['endtime_year']);
	}
	else
	{
		$enddateyear = gmdate("Y", TIME_NOW) + 1;
	}
	
	$form_container = new FormContainer($lang->add_an_announcement);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->start_date." <em>*</em>", $lang->start_date_desc, "<select name=\"starttime_day\">\n{$startdateday}</select>\n &nbsp; \n<select name=\"starttime_month\">\n{$startdatemonth}</select>\n &nbsp; \n<input type=\"text\" name=\"starttime_year\" value=\"{$startdateyear}\" size=\"4\" maxlength=\"4\" />\n - {$lang->time} ".$form->generate_text_box('starttime_time', $daddyobb->input['starttime_time'], array('id' => 'starttime_time', 'style' => 'width: 50px;')));

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
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"endtime_type\" value=\"1\" {$endtime_checked[1]} class=\"endtimes_check\" onclick=\"checkAction('endtime');\" style=\"vertical-align: middle;\" /> <strong>{$lang->set_time}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"endtime_1\" class=\"endtimes\">
			<table cellpadding=\"4\">
				<tr>
					<td><select name=\"endtime_day\">\n{$enddateday}</select>\n &nbsp; \n<select name=\"endtime_month\">\n{$enddatemonth}</select>\n &nbsp; \n<input type=\"text\" name=\"endtime_year\" value=\"{$enddateyear}\" class=\"text_input\" size=\"4\" maxlength=\"4\" />\n - {$lang->time} ".$form->generate_text_box('endtime_time', $daddyobb->input['endtime_time'], array('id' => 'endtime_time', 'style' => 'width: 50px;'))."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"endtime_type\" value=\"2\" {$endtime_checked[2]} class=\"endtimes_check\" onclick=\"checkAction('endtime');\" style=\"vertical-align: middle;\" /> <strong>{$lang->never}</strong></label></dt>
	</dl>
	<script type=\"text/javascript\">
	checkAction('endtime');
	</script>";
	$form_container->output_row($lang->end_date." <em>*</em>", $lang->end_date_desc, $actions);

	$form_container->output_row($lang->message." <em>*</em>", "", $form->generate_text_area('message', $daddyobb->input['message'], array('id' => 'message')), 'message');
	
	$form_container->output_row($lang->forums_to_appear_in." <em>*</em>", $lang->forums_to_appear_in_desc, $form->generate_forum_select('fid', $daddyobb->input['fid'], array('size' => 5, 'main_option' => $lang->all_forums)));

	$form_container->output_row($lang->allow_html." <em>*</em>", "", $form->generate_yes_no_radio('allowhtml', $daddyobb->input['allowhtml'], array('style' => 'width: 2em;')));

	$form_container->output_row($lang->allow_mycode." <em>*</em>", "", $form->generate_yes_no_radio('allowmycode', $daddyobb->input['allowmycode'], array('style' => 'width: 2em;')));
	
	$form_container->output_row($lang->allow_smilies." <em>*</em>", "", $form->generate_yes_no_radio('allowsmilies', $daddyobb->input['allowsmilies'], array('style' => 'width: 2em;')));

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_announcement);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_forum_announcements_edit");
	
	if(!trim($daddyobb->input['aid']))
	{
		flash_message($lang->error_invalid_announcement, 'error');
		admin_redirect("index.php?module=forum/announcements");
	}
			
	if($daddyobb->request_method == "post")
	{		
		if(!trim($daddyobb->input['title']))
		{
			$errors[] = $lang->error_missing_title;
		}
		
		if(!trim($daddyobb->input['message']))
		{
			$errors[] = $lang->error_missing_message;
		}
		
		if(!trim($daddyobb->input['fid']))
		{
			$errors[] = $lang->error_missing_forum;
		}
		
		if(!$errors)
		{
			$startdate = @explode(" ", $daddyobb->input['starttime_time']);
			$startdate = @explode(":", $startdate[0]);
			$enddate = @explode(" ", $daddyobb->input['endtime_time']);
			$enddate = @explode(":", $enddate[0]);
		
			if(stristr($daddyobb->input['starttime_time'], "pm"))
			{
				$startdate[0] = 12+$startdate[0];
				if($startdate[0] >= 24)
				{
					$startdate[0] = "00";
				}
			}
			
			if(stristr($daddyobb->input['endtime_time'], "pm"))
			{
				$enddate[0] = 12+$enddate[0];
				if($enddate[0] >= 24)
				{
					$enddate[0] = "00";
				}
			}
			
			$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');			
			if(!in_array($daddyobb->input['starttime_month'], $months))
			{
				$daddyobb->input['starttime_month'] = 1;
			}
			
			$startdate = gmmktime(intval($startdate[0]), intval($startdate[1]), 0, (int)$daddyobb->input['starttime_month'], intval($daddyobb->input['starttime_day']), intval($daddyobb->input['starttime_year']));
			
			if($daddyobb->input['endtime_type'] == "2")
			{
				$enddate = '0';
			}
			else
			{
				if(!in_array($daddyobb->input['endtime_month'], $months))
				{
					$daddyobb->input['endtime_month'] = 1;
				}
				$enddate = gmmktime(intval($enddate[0]), intval($enddate[1]), 0, (int)$daddyobb->input['endtime_month'], intval($daddyobb->input['endtime_day']), intval($daddyobb->input['endtime_year']));
			}
			
			$update_announcement = array(
				"fid" => $daddyobb->input['fid'],
				"subject" => $db->escape_string($daddyobb->input['title']),
				"message" => $db->escape_string($daddyobb->input['message']),
				"startdate" => $startdate,
				"enddate" => $enddate,
				"allowhtml" => $db->escape_string($daddyobb->input['allowhtml']),
				"allowmycode" => $db->escape_string($daddyobb->input['allowmycode']),
				"allowsmilies" => $db->escape_string($daddyobb->input['allowsmilies']),
			);
	
			$aid = $db->update_query("announcements", $update_announcement, "aid='{$daddyobb->input['aid']}'");
			
			$plugins->run_hooks("admin_forum_announcements_edit_commit");
	
			// Log admin action
			log_admin_action($aid, $daddyobb->input['title']);
	
			flash_message($lang->success_updated_announcement, 'success');
			admin_redirect("index.php?module=forum/announcements");
		}
	}
	
	$page->add_breadcrumb_item($lang->update_an_announcement);
	$page->output_header($lang->update_an_announcement);
	$page->output_nav_tabs($sub_tabs, "update_announcement");

	$form = new Form("index.php?module=forum/announcements&amp;action=edit", "post");
	echo $form->generate_hidden_field("aid", $daddyobb->input['aid']);
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$query = $db->simple_select("announcements", "*", "aid='{$daddyobb->input['aid']}'");
		$announcement = $db->fetch_array($query);
		
		if(!$announcement)
		{
			flash_message($lang->error_invalid_announcement, 'error');
			admin_redirect("index.php?module=forum/announcements");
		}
		
		$start_time = explode("-", gmdate("g-i-a", $announcement['startdate']));
		$daddyobb->input['starttime_time'] = $start_time[0].":".$start_time[1]." ".$start_time[2];
		
		$startday = gmdate("j", $announcement['startdate']);
		
		$startmonth = gmdate("m", $announcement['startdate']);
		$startmonthsel[$startmonth] = "selected=\"selected\"";
		
		$startdateyear = gmdate("Y", $announcement['startdate']);
		
		$daddyobb->input['title'] = $announcement['subject'];
		$daddyobb->input['message'] = $announcement['message'];
		$daddyobb->input['allowhtml'] = $announcement['allowhtml'];
		$daddyobb->input['allowsmilies'] = $announcement['allowsmilies'];
		$daddyobb->input['allowmycode'] = $announcement['allowmycode'];
		$daddyobb->input['fid'] = $announcement['fid'];
		
		if($announcement['enddate'])
		{
			$endtime_checked[1] = "checked=\"checked\"";
			$endtime_checked[2] = "";
			
			$end_time = explode("-", gmdate("g-i-a", $announcement['enddate']));
			$daddyobb->input['endtime_time'] = $end_time[0].":".$end_time[1]." ".$end_time[2];
			
			$endday = gmdate("j", $announcement['enddate']);
			
			$endmonth = gmdate("m", $announcement['enddate']);
			$endmonthsel[$endmonth] = "selected";
			
			$enddateyear = gmdate("Y", $announcement['enddate']);
		}
		else
		{		
			$endtime_checked[1] = "";
			$endtime_checked[2] = "checked=\"checked\"";
			
			$daddyobb->input['endtime_time'] = $daddyobb->input['starttime_time'];
			$endday = $startday;
			$endmonth = $startmonth;
			$enddateyear = $startdateyear+1;
		}
	}
	
	for($i = 1; $i <= 31; ++$i)
	{
		if($startday == $i)
		{
			$startdateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$startdateday .= "<option value=\"$i\">$i</option>\n";
		}
		
		if($endday == $i)
		{
			$enddateday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$enddateday .= "<option value=\"$i\">$i</option>\n";
		}
	}
	
	$startdatemonth .= "<option value=\"01\" {$startmonthsel['01']}>{$lang->january}</option>\n";
	$enddatemonth .= "<option value=\"01\" {$endmonthsel['01']}>{$lang->january}</option>\n";
	$startdatemonth .= "<option value=\"02\" {$startmonthsel['02']}>{$lang->february}</option>\n";
	$enddatemonth .= "<option value=\"02\" {$endmonthsel['02']}>{$lang->february}</option>\n";
	$startdatemonth .= "<option value=\"03\" {$startmonthsel['03']}>{$lang->march}</option>\n";
	$enddatemonth .= "<option value=\"03\" {$endmonthsel['03']}>{$lang->march}</option>\n";
	$startdatemonth .= "<option value=\"04\" {$startmonthsel['04']}>{$lang->april}</option>\n";
	$enddatemonth .= "<option value=\"04\" {$endmonthsel['04']}>{$lang->april}</option>\n";
	$startdatemonth .= "<option value=\"05\" {$startmonthsel['05']}>{$lang->may}</option>\n";
	$enddatemonth .= "<option value=\"05\" {$endmonthsel['05']}>{$lang->may}</option>\n";
	$startdatemonth .= "<option value=\"06\" {$startmonthsel['06']}>{$lang->june}</option>\n";
	$enddatemonth .= "<option value=\"06\" {$endmonthsel['06']}>{$lang->june}</option>\n";
	$startdatemonth .= "<option value=\"07\" {$startmonthsel['07']}>{$lang->july}</option>\n";
	$enddatemonth .= "<option value=\"07\" {$endmonthsel['07']}>{$lang->july}</option>\n";
	$startdatemonth .= "<option value=\"08\" {$startmonthsel['08']}>{$lang->august}</option>\n";
	$enddatemonth .= "<option value=\"08\" {$endmonthsel['08']}>{$lang->august}</option>\n";
	$startdatemonth .= "<option value=\"09\" {$startmonthsel['09']}>{$lang->september}</option>\n";
	$enddatemonth .= "<option value=\"09\" {$endmonthsel['09']}>{$lang->september}</option>\n";
	$startdatemonth .= "<option value=\"10\" {$startmonthsel['10']}>{$lang->october}</option>\n";
	$enddatemonth .= "<option value=\"10\" {$endmonthsel['10']}>{$lang->october}</option>\n";
	$startdatemonth .= "<option value=\"11\" {$startmonthsel['11']}>{$lang->november}</option>\n";
	$enddatemonth .= "<option value=\"11\" {$endmonthsel['11']}>{$lang->november}</option>\n";
	$startdatemonth .= "<option value=\"12\" {$startmonthsel['12']}>{$lang->december}</option>\n";
	$enddatemonth .= "<option value=\"12\" {$endmonthsel['12']}>{$lang->december}</option>\n";
	
	$form_container = new FormContainer($lang->add_an_announcement);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $daddyobb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->start_date." <em>*</em>", $lang->start_date_desc, "<select name=\"starttime_day\">\n{$startdateday}</select>\n &nbsp; \n<select name=\"starttime_month\">\n{$startdatemonth}</select>\n &nbsp; \n<input type=\"text\" name=\"starttime_year\" value=\"{$startdateyear}\" size=\"4\" maxlength=\"4\" class=\"text_input\" />\n - {$lang->time} ".$form->generate_text_box('starttime_time', $daddyobb->input['starttime_time'], array('id' => 'starttime_time', 'style' => 'width: 50px;')));

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
	<dt><label style=\"display: block;\"><input type=\"radio\" name=\"endtime_type\" value=\"1\" {$endtime_checked[1]} class=\"endtimes_check\" onclick=\"checkAction('endtime');\" style=\"vertical-align: middle;\" /> <strong>{$lang->set_time}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"endtime_1\" class=\"endtimes\">
			<table cellpadding=\"4\">
				<tr>
					<td><select name=\"endtime_day\">\n{$enddateday}</select>\n &nbsp; \n<select name=\"endtime_month\">\n{$enddatemonth}</select>\n &nbsp; \n<input type=\"text\" name=\"endtime_year\" value=\"{$enddateyear}\" size=\"4\" maxlength=\"4\" />\n - {$lang->time} ".$form->generate_text_box('endtime_time', $daddyobb->input['endtime_time'], array('id' => 'endtime_time', 'style' => 'width: 50px;'))."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"endtime_type\" value=\"2\" {$endtime_checked[2]} class=\"endtimes_check\" onclick=\"checkAction('endtime');\" style=\"vertical-align: middle;\" /> <strong>{$lang->never}</strong></label></dt>
	</dl>
	<script type=\"text/javascript\">
	checkAction('endtime');
	</script>";
	$form_container->output_row($lang->end_date." <em>*</em>", $lang->end_date_desc, $actions);

	$form_container->output_row($lang->message." <em>*</em>", "", $form->generate_text_area('message', $daddyobb->input['message'], array('id' => 'message')), 'message');
	
	$form_container->output_row($lang->forums_to_appear_in." <em>*</em>", $lang->forums_to_appear_in_desc, $form->generate_forum_select('fid', $daddyobb->input['fid'], array('size' => 5, 'main_option' => $lang->all_forums)));

	$form_container->output_row($lang->allow_html." <em>*</em>", "", $form->generate_yes_no_radio('allowhtml', $daddyobb->input['allowhtml'], array('style' => 'width: 2em;')));

	$form_container->output_row($lang->allow_mycode." <em>*</em>", "", $form->generate_yes_no_radio('allowmycode', $daddyobb->input['allowmycode'], array('style' => 'width: 2em;')));
	
	$form_container->output_row($lang->allow_smilies." <em>*</em>", "", $form->generate_yes_no_radio('allowsmilies', $daddyobb->input['allowsmilies'], array('style' => 'width: 2em;')));

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_announcement);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($daddyobb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_forum_announcements_delete");
	
	$query = $db->simple_select("announcements", "*", "aid='{$daddyobb->input['aid']}'");
	$announcement = $db->fetch_array($query);
	
	// Does the announcement not exist?
	if(!$announcement['aid'])
	{
		flash_message($lang->error_invalid_announcement, 'error');
		admin_redirect("index.php?module=forum/announcements");
	}

	// User clicked no
	if($daddyobb->input['no'])
	{
		admin_redirect("index.php?module=forum/announcements");
	}

	if($daddyobb->request_method == "post")
	{
		$db->delete_query("announcements", "aid='{$announcement['aid']}'");
		
		$plugins->run_hooks("admin_forum_announcements_delete_commit");
		
		// Log admin action
		log_admin_action($announcement['aid'], $announcement['title']);

		flash_message($lang->success_announcement_deleted, 'success');
		admin_redirect("index.php?module=forum/announcements");
	}
	else
	{
		$page->output_confirm_action("index.php?module=forum/announcements&amp;action=delete&amp;aid={$announcement['aid']}", $lang->confirm_announcement_deletion);
	}
}

if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("admin_forum_announcements_start");
	
	$page->add_breadcrumb_item($lang->forum_announcements, "index.php?module=forum/announcements");
	
	$page->output_header($lang->forum_announcements);
	
	$page->output_nav_tabs($sub_tabs, "forum_announcements");

	// Fetch announcements into their proper arrays
	$query = $db->simple_select("announcements", "aid, fid, subject, enddate");
	while($announcement = $db->fetch_array($query))
	{
		if($announcement['fid'] == -1)
		{			
			$global_announcements[$announcement['aid']] = $announcement;
			continue;
		}
		$announcements[$announcement['fid']][$announcement['aid']] = $announcement;
	}
	
	if($global_announcements)
	{
		$table = new Table;
		$table->construct_header($lang->announcement);
		$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2, "width" => 150));
		
		// Get the global announcements
		foreach($global_announcements as $aid => $announcement)
		{
			if($announcement['enddate'] < TIME_NOW && $announcement['enddate'] != 0)
			{
				$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.gif\" alt=\"(Expired)\" title=\"Expired Announcement\"  style=\"vertical-align: middle;\" /> ";
			}
			else
			{
				$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.gif\" alt=\"(Active)\" title=\"Active Announcement\"  style=\"vertical-align: middle;\" /> ";
			}
			
			$table->construct_cell($icon."<a href=\"index.php?module=forum/announcements&amp;action=edit&amp;aid={$aid}\">{$announcement['subject']}</a>");
			$table->construct_cell("<a href=\"index.php?module=forum/announcements&amp;action=edit&amp;aid={$aid}\">{$lang->edit}</a>", array("class" => "align_center", "width" => 75));
			$table->construct_cell("<a href=\"index.php?module=forum/announcements&amp;action=delete&amp;aid={$aid}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_announcement_deletion}')\">{$lang->delete}</a>", array("class" => "align_center", "width" => 75));
			$table->construct_row();
		}
		$table->output($lang->global_announcements);
	}
	
	
	$table = new Table;
	$table->construct_header($lang->announcement);
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2, "width" => 200));
	
	fetch_forum_announcements($table);
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_forums, array("colspan" => "3"));
		$table->construct_row();
	}
	
	$table->output($lang->forum_announcements);

	$page->output_footer();
}

function fetch_forum_announcements(&$table, $pid=0, $depth=1)
{
	global $daddyobb, $db, $lang, $announcements, $page;
	static $forums_by_parent;

	if(!is_array($forums_by_parent))
	{
		$forum_cache = cache_forums();

		foreach($forum_cache as $forum)
		{
			$forums_by_parent[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
		}
	}

	if(!is_array($forums_by_parent[$pid]))
	{
		return;
	}

	foreach($forums_by_parent[$pid] as $children)
	{
		foreach($children as $forum)
		{
			$forum['name'] = htmlspecialchars_uni($forum['name']);
			if($forum['active'] == 0)
			{
				$forum['name'] = "<em>".$forum['name']."</em>";
			}
			
			if($forum['type'] == "c")
			{
				$forum['name'] = "<strong>".$forum['name']."</strong>";
			}
				
			$table->construct_cell("<div style=\"padding-left: ".(40*($depth-1))."px;\">{$forum['name']}</div>");
			$table->construct_cell("<a href=\"index.php?module=forum/announcements&amp;action=add&amp;fid={$forum['fid']}\">{$lang->add_announcement}</a>", array("class" => "align_center", "colspan" => 2));
			$table->construct_row();
				
			if($announcements[$forum['fid']])
			{
				foreach($announcements[$forum['fid']] as $aid => $announcement)
				{
					if($announcement['enddate'] < TIME_NOW && $announcement['enddate'] != 0)
					{
						$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.gif\" alt=\"(Expired)\" title=\"Expired Announcement\"  style=\"vertical-align: middle;\" /> ";
					}
					else
					{
						$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.gif\" alt=\"(Active)\" title=\"Active Announcement\"  style=\"vertical-align: middle;\" /> ";
					}
							
					$table->construct_cell("<div style=\"padding-left: ".(40*$depth)."px;\">{$icon}<a href=\"index.php?module=forum/announcements&amp;action=edit&amp;aid={$aid}\">{$announcement['subject']}</a></div>");
					$table->construct_cell("<a href=\"index.php?module=forum/announcements&amp;action=edit&amp;aid={$aid}\">{$lang->edit}</a>", array("class" => "align_center"));
					$table->construct_cell("<a href=\"index.php?module=forum/announcements&amp;action=delete&amp;aid={$aid}&amp;my_post_key={$daddyobb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_announcement_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
					$table->construct_row();
				}
			}

			// Build the list for any sub forums of this forum
			if($forums_by_parent[$forum['fid']])
			{
				fetch_forum_announcements($table, $forum['fid'], $depth+1);
			}
		}
	}
}

?>