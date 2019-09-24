<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 20:59 24.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'usercp.php');

$templatelist = "usercp,usercp_cphome_subscription, usercp_cphome_subscriptions_thread,usercp_nav,usercp_profile,error_nopermission,buddy_online,buddy_offline,usercp_changename,";
$templatelist .= ",usercp_usergroups_memberof_usergroup,usercp_usergroups_joinable_usergroup,usercp_usergroups";
$templatelist .= ",usercp_nav_messenger,usercp_nav_profile,usercp_nav_misc,usercp_usergroups_leader_usergroup,usercp_reputation";
$templatelist .= ",usercp_attachments_attachment,usercp_attachments,usercp_profile_customfield,usercp_profile_profilefields,usercp_forumsubscriptions_none,usercp_forumsubscriptions,usercp_subscriptions,usercp_options_tppselect,usercp_options_pppselect,usercp_options";

require_once "./global.php";
require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("userbase");

if($daddyobb->user['uid'] == 0 || $daddyobb->usergroup['canusercp'] == 0)
{
	error_no_permission();
}

if(!$daddyobb->user['pmfolders'])
{
	$daddyobb->user['pmfolders'] = "1**".$lang->folder_inbox."$%%$2**".$lang->folder_sent_items."$%%$3**".$lang->folder_drafts."$%%$4**".$lang->folder_trash;
	$db->update_query("users", array('pmfolders' => $daddyobb->user['pmfolders']), "uid='".$daddyobb->user['uid']."'");
}

$errors = '';

usercp_menu();

$plugins->run_hooks("usercp_start");
if($daddyobb->input['action'] == "do_editsig" && $daddyobb->request_method == "post")
{
	$parser_options = array(
		'allow_html' => $daddyobb->usergroup['sigallowhtml'],
		'filter_badwords' => 1,
		'allow_mycode' => $daddyobb->usergroup['sigallowmycode'],
		'allow_smilies' => $daddyobb->usergroup['sigallowsmilies'],
		'allow_imgcode' => $daddyobb->usergroup['sigallowimgcode'],
		"filter_badwords" => 1
	);
	$parsed_sig = $parser->parse_message($daddyobb->input['signature'], $parser_options);
	if($daddyobb->usergroup['sigallowimgcode'] == 1)
	{
		if($daddyobb->usergroup['signumimages'] != 0)
		{
      $imgsallowed = $daddyobb->usergroup['signumimages'];
    }
    else
    {
      //Infinite is actually a number for us...I mean who will post 1000 images in a sig?
      $imgsallowed = 1000;
    }
	}
	else
	{
		$imgsallowed = 0;
	}
	if((($daddyobb->usergroup['sigallowimgcode'] == 0 && $daddyobb->usergroup['sigallowsmilies'] != 1) &&
		substr_count($parsed_sig, "<img") > 0) ||
		(($daddyobb->usergroup['sigallowimgcode'] ==1 || $daddyobb->usergroup['sigallowsmilies'] == 1) &&
		substr_count($parsed_sig, "<img") > $imgsallowed)
	)
	{
		$lang->too_many_sig_images2 = $lang->sprintf($lang->too_many_sig_images2, $imgsallowed);
		$error = inline_error($lang->too_many_sig_images." ".$lang->too_many_sig_images2);
		$daddyobb->input['preview'] = 1;
	}
	else if($daddyobb->usergroup['sigmaxchars'] > 0)
	{
		if($daddyobb->settings['sigcountmycode'] == 1)
		{
			$parsed_sig = $parser->text_parse_message($daddyobb->input['signature']);
		}
		else
		{
			$parsed_sig = $daddyobb->input['signature'];
		}
		$parsed_sig = preg_replace("#\s#", "", $parsed_sig);
		$sig_length = my_strlen($parsed_sig);
		if($sig_length > $daddyobb->usergroup['sigmaxchars'])
		{
			$lang->sig_too_long = $lang->sprintf($lang->sig_too_long, $daddyobb->usergroup['sigmaxchars']);
			if($sig_length - $daddyobb->usergroup['sigmaxchars'] > 1)
			{
				$lang->sig_too_long .= $lang->sprintf($lang->sig_remove_chars_plural, $sig_length-$daddyobb->usergroup['sigmaxchars']);
			}
			else
			{
				$lang->sig_too_long .= $lang->sig_remove_chars_singular;
			}
			$error = inline_error($lang->sig_too_long);
		}
	}
	if($error || $daddyobb->input['preview'])
	{
		$daddyobb->input['action'] = "editsig";
	}
}

// Make navigation
add_breadcrumb($lang->nav_usercp, "usercp.php");

switch($daddyobb->input['action'])
{
	case "profile":
	case "do_profile":
		add_breadcrumb($lang->nav_editprofile);
		break;
	case "options":
	case "do_options":
		add_breadcrumb($lang->nav_options);
		break;
	case "email":
	case "do_email":
		add_breadcrumb($lang->nav_email);
		break;
	case "password":
	case "do_password":
		add_breadcrumb($lang->nav_password);
		break;
	case "changename":
	case "do_changename":
		add_breadcrumb($lang->nav_changename);
		break;
	case "subscriptions":
		add_breadcrumb($lang->nav_subthreads);
		break;
	case "forumsubscriptions":
		add_breadcrumb($lang->nav_forumsubscriptions);
		break;
	case "editsig":
	case "do_editsig":
		add_breadcrumb($lang->nav_editsig);
		break;
	case "avatar":
	case "do_avatar":
		add_breadcrumb($lang->nav_avatar);
		break;
	case "profilepic":
	case "do_profilepic":
		add_breadcrumb($lang->nav_profilepic);
		break;
	case "notepad":
	case "do_notepad":
		add_breadcrumb($lang->nav_notepad);
		break;
	case "editlists":
	case "do_editlists":
		add_breadcrumb($lang->nav_edit_lists);
		break;
	case "drafts":
		add_breadcrumb($lang->nav_drafts);
		break;
	case "usergroups":
		add_breadcrumb($lang->nav_usergroups);
		break;
	case "attachments":
		add_breadcrumb($lang->nav_attachments);
		break;
}

if($daddyobb->input['action'] == "do_profile" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_profile_start");

	$bday = array(
		"day" => $daddyobb->input['bday1'],
		"month" => $daddyobb->input['bday2'],
		"year" => $daddyobb->input['bday3']
	);

	// Set up user handler.
	require_once "inc/datahandlers/user.php";
	$userhandler = new UserDataHandler("update");

	$user = array(
		"uid" => $daddyobb->user['uid'],
		"website" => $daddyobb->input['website'],
		"icq" => intval($daddyobb->input['icq']),
		"aim" => $daddyobb->input['aim'],
		"yahoo" => $daddyobb->input['yahoo'],
		"msn" => $daddyobb->input['msn'],
		"birthday" => $bday,
		"birthdayprivacy" => $daddyobb->input['birthdayprivacy'],
		"profile_fields" => $daddyobb->input['profile_fields']
	);

	if($daddyobb->usergroup['cancustomtitle'] == 1)
	{
		if($daddyobb->input['usertitle'] != '')
		{
			$user['usertitle'] = $daddyobb->input['usertitle'];
		}
		elseif($daddyobb->input['reverttitle'])
		{
			$user['usertitle'] = '';
		}
	}
	$userhandler->set_data($user);

	if(!$userhandler->validate_user())
	{
		$errors = $userhandler->get_friendly_errors();
		$errors = inline_error($errors);
		$daddyobb->input['action'] = "profile";
	}
	else
	{
		$userhandler->update_user();

		$plugins->run_hooks("usercp_do_profile_end");
		$lang->redirect_profileupdated = $lang->sprintf($lang->redirect_profileupdated, $daddyobb->user['username']);
		redirect("usercp.php", $lang->redirect_profileupdated);
	}
}

if($daddyobb->input['action'] == "profile")
{
	if($errors)
	{
		$user = $daddyobb->input;
		$bday = array();
		$bday[0] = $daddyobb->input['bday1'];
		$bday[1] = $daddyobb->input['bday2'];
		$bday[2] = intval($daddyobb->input['bday3']);
	}
	else
	{
		$user = $daddyobb->user;
		$bday = explode("-", $user['birthday']);
	}

	$plugins->run_hooks("usercp_profile_start");

	$bdaysel = '';
	for($i = 1; $i <= 31; ++$i)
	{
		if($bday[0] == $i)
		{
			$bdaydaysel .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$bdaydaysel .= "<option value=\"$i\">$i</option>\n";
		}
	}
	$bdaymonthsel[$bday[1]] = "selected";

	$bdayprivacysel = '';
	if($user['birthdayprivacy'] == 'all' || !$user['birthdayprivacy'])
	{
		$bdayprivacysel .= "<option value=\"none\">{$lang->birthdayprivacynone}</option>\n";
		$bdayprivacysel .= "<option value=\"age\">{$lang->birthdayprivacyage}</option>";
		$bdayprivacysel .= "<option value=\"all\" selected=\"selected\">{$lang->birthdayprivacyall}</option>\n";
	}
	else if($user['birthdayprivacy'] == 'none')
	{
		$bdayprivacysel .= "<option value=\"none\" selected=\"selected\">{$lang->birthdayprivacynone}</option>\n";
		$bdayprivacysel .= "<option value=\"age\">{$lang->birthdayprivacyage}</option>";
		$bdayprivacysel .= "<option value=\"all\">{$lang->birthdayprivacyall}</option>\n";
	}
	else if($user['birthdayprivacy'] == 'age')
	{
		$bdayprivacysel .= "<option value=\"none\">{$lang->birthdayprivacynone}</option>\n";
		$bdayprivacysel .= "<option value=\"age\" selected=\"selected\">{$lang->birthdayprivacyage}</option>";
		$bdayprivacysel .= "<option value=\"all\">{$lang->birthdayprivacyall}</option>\n";
	}

	if($user['website'] == "" || $user['website'] == "http://")
	{
		$user['website'] = "http://";
	}
	else
	{
		$user['website'] = htmlspecialchars_uni($user['website']);
	}

	if($user['icq'] != "0")
	{
		$user['icq'] = intval($user['icq']);
	}
	if($user['icq'] == 0)
	{
		$user['icq'] = "";
	}
	if($errors)
	{
		$user['msn'] = htmlspecialchars_uni($user['msn']);
		$user['aim'] = htmlspecialchars_uni($user['aim']);
		$user['yahoo'] = htmlspecialchars_uni($user['yahoo']);
	}
	// Custom profile fields baby!
	$altbg = "trow1";
	$requiredfields = '';
	$customfields = '';
	$query = $db->simple_select("profilefields", "*", "editable=1", array('order_by' => 'disporder'));
	while($profilefield = $db->fetch_array($query))
	{
		$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
		$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
		$thing = explode("\n", $profilefield['type'], "2");
		$type = $thing[0];
		$options = $thing[1];
		$field = "fid{$profilefield['fid']}";
		$select = '';
		if($errors)
		{
			$userfield = $daddyobb->input['profile_fields'][$field];
		}
		else
		{
			$userfield = $user[$field];
		}
		if($type == "multiselect")
		{
			if($errors)
			{
				$useropts = $userfield;
			}
			else
			{
				$useropts = explode("\n", $userfield);
			}
			if(is_array($useropts))
			{
				foreach($useropts as $key => $val)
				{
					$seloptions[$val] = $val;
				}
			}
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$val = trim($val);
					$val = str_replace("\n", "\\n", $val);

					$sel = "";
					if($val == $seloptions[$val])
					{
						$sel = " selected=\"selected\"";
					}
					$select .= "<option value=\"$val\"$sel>$val</option>\n";
				}
				if(!$profilefield['length'])
				{
					$profilefield['length'] = 3;
				}
				$code = "<select name=\"profile_fields[$field][]\" size=\"{$profilefield['length']}\" multiple=\"multiple\">$select</select>";
			}
		}
		elseif($type == "select")
		{
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$val = trim($val);
					$val = str_replace("\n", "\\n", $val);
					$sel = "";
					if($val == $userfield)
					{
						$sel = " selected=\"selected\"";
					}
					$select .= "<option value=\"$val\"$sel>$val</option>";
				}
				if(!$profilefield['length'])
				{
					$profilefield['length'] = 1;
				}
				$code = "<select name=\"profile_fields[$field]\" size=\"{$profilefield['length']}\">$select</select>";
			}
		}
		elseif($type == "radio")
		{
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$checked = "";
					if($val == $userfield)
					{
						$checked = " checked=\"checked\"";
					}
					$code .= "<input type=\"radio\" class=\"radio\" name=\"profile_fields[$field]\" value=\"$val\"$checked /> <span class=\"smalltext\">$val</span><br />";
				}
			}
		}
		elseif($type == "checkbox")
		{
			if($errors)
			{
				$useropts = $userfield;
			}
			else
			{
				$useropts = explode("\n", $userfield);
			}
			if(is_array($useropts))
			{
				foreach($useropts as $key => $val)
				{
					$seloptions[$val] = $val;
				}
			}
			$expoptions = explode("\n", $options);
			if(is_array($expoptions))
			{
				foreach($expoptions as $key => $val)
				{
					$checked = "";
					if($val == $seloptions[$val])
					{
						$checked = " checked=\"checked\"";
					}
					$code .= "<input type=\"checkbox\" class=\"checkbox\" name=\"profile_fields[$field][]\" value=\"$val\"$checked /> <span class=\"smalltext\">$val</span><br />";
				}
			}
		}
		elseif($type == "textarea")
		{
			$value = htmlspecialchars_uni($userfield);
			$code = "<textarea name=\"profile_fields[$field]\" rows=\"6\" cols=\"30\" style=\"width: 95%\">$value</textarea>";
		}
		else
		{
			$value = htmlspecialchars_uni($userfield);
			$code = "<input type=\"text\" name=\"profile_fields[$field]\" class=\"textbox\" size=\"{$profilefield['length']}\" maxlength=\"{$profilefield['maxlength']}\" value=\"$value\" />";
		}
		if($profilefield['required'] == 1)
		{
			eval("\$requiredfields .= \"".$templates->get("usercp_profile_customfield")."\";");
		}
		else
		{
			eval("\$customfields .= \"".$templates->get("usercp_profile_customfield")."\";");
		}
		$altbg = alt_trow();
		$code = "";
		$select = "";
		$val = "";
		$options = "";
		$expoptions = "";
		$useropts = "";
		$seloptions = "";
	}
	if($customfields)
	{
		eval("\$customfields = \"".$templates->get("usercp_profile_profilefields")."\";");
	}

	if($daddyobb->usergroup['cancustomtitle'] == 1)
	{
		if($daddyobb->usergroup['usertitle'] == "")
		{
			$query = $db->simple_select("usertitles", "*", "posts <='".$daddyobb->user['postnum']."'", array('order_by' => 'posts', 'order_dir' => 'DESC', 'limit' => 1));
			$utitle = $db->fetch_array($query);
			$defaulttitle = $utitle['title'];
		}
		else
		{
			$defaulttitle = $daddyobb->usergroup['usertitle'];
		}
		if(empty($user['usertitle']))
		{
			$lang->current_custom_usertitle = '';
		}
		else
		{
			if($errors)
			{
				$newtitle = htmlspecialchars_uni($user['usertitle']);
				$user['usertitle'] = $daddyobb->user['usertitle'];
			}
		}
		eval("\$customtitle = \"".$templates->get("usercp_profile_customtitle")."\";");
	}
	else
	{
		$customtitle = "";
	}
	eval("\$editprofile = \"".$templates->get("usercp_profile")."\";");
	$plugins->run_hooks("usercp_profile_end");
	output_page($editprofile);
}

if($daddyobb->input['action'] == "do_options" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_options_start");

	// Set up user handler.
	require_once DADDYOBB_ROOT."inc/datahandlers/user.php";
	$userhandler = new UserDataHandler("update");

	$user = array(
		"uid" => $daddyobb->user['uid'],
		"style" => intval($daddyobb->input['style']),
		"dateformat" => intval($daddyobb->input['dateformat']),
		"timeformat" => intval($daddyobb->input['timeformat']),
		"timezone" => $db->escape_string($daddyobb->input['timezoneoffset']),
		"language" => $daddyobb->input['language']
	);

	$user['options'] = array(
    "vcard" => $daddyobb->input['vcard'],
    "enablevms" => $daddyobb->input['enablevms'],
    "limitvms" => $daddyobb->input['limitvms'],
		"allownotices" => $daddyobb->input['allownotices'],
		"hideemail" => $daddyobb->input['hideemail'],
		"subscriptionmethod" => $daddyobb->input['subscriptionmethod'],
		"invisible" => $daddyobb->input['invisible'],
		"dstcorrection" => $daddyobb->input['dstcorrection'],
		"threadmode" => $daddyobb->input['threadmode'],
		"showsigs" => $daddyobb->input['showsigs'],
		"showavatars" => $daddyobb->input['showavatars'],
		"showquickreply" => $daddyobb->input['showquickreply'],
		"remember" => $daddyobb->input['remember'],
		"receivepms" => $daddyobb->input['receivepms'],
		"pmnotice" => $daddyobb->input['pmnotice'],
		"daysprune" => $daddyobb->input['daysprune'],
		"showcodebuttons" => intval($daddyobb->input['showcodebuttons']),
		"pmnotify" => $daddyobb->input['pmnotify'],
		"showredirect" => $daddyobb->input['showredirect'],
		"classicpostbit" => $daddyobb->input['classicpostbit']
	);

	if($daddyobb->settings['usertppoptions'])
	{
		$user['options']['tpp'] = intval($daddyobb->input['tpp']);
	}

	if($daddyobb->settings['userpppoptions'])
	{
		$user['options']['ppp'] = intval($daddyobb->input['ppp']);
	}

	$userhandler->set_data($user);


	if(!$userhandler->validate_user())
	{
		$errors = $userhandler->get_friendly_errors();
		$errors = inline_error($errors);
		$daddyobb->input['action'] = "options";
	}
	else
	{
		$userhandler->update_user();

		// If the cookie settings are different, re-set the cookie
		if($daddyobb->input['remember'] != $daddyobb->user['remember'])
		{
			$daddyobb->user['remember'] = $daddyobb->input['remember'];
			// Unset the old one
			my_unsetcookie("daddyobbuser");
			// Set the new one
			if($daddyobb->input['remember'] == 1)
			{
				my_setcookie("daddyobbuser", $daddyobb->user['uid']."_".$daddyobb->user['loginkey'], null, true);
			}
			else
			{
				my_setcookie("daddyobbuser", $daddyobb->user['uid']."_".$daddyobb->user['loginkey'], -1, true);
			}
		}

		$plugins->run_hooks("usercp_do_options_end");

		$lang->redirect_profileupdated = $lang->sprintf($lang->redirect_profileupdated, $daddyobb->user['username']);
		redirect("usercp.php", $lang->redirect_profileupdated);
	}
}

if($daddyobb->input['action'] == "options")
{
	$plugins->run_hooks("usercp_options_start");

	if($errors != '')
	{
		$user = $daddyobb->input;
	}
	else
	{
		$user = $daddyobb->user;
	}
	$languages = $lang->get_languages();
	$langoptions = '';
	foreach($languages as $lname => $language)
	{
		$sel = "";
		if($user['language'] == $lname)
		{
			$sel = " selected=\"selected\"";
		}
		$langoptions .= "<option value=\"$lname\"$sel>".htmlspecialchars_uni($language)."</option>\n";
	}

	// Lets work out which options the user has selected and check the boxes
	if($user['vcard'] == 1)
	{
    $vcardcheck = "checked=\"checked\"";
	}
	else
	{
    $vcardcheck = "";
	}
	if($user['enablevms'] == 1)
	{
    $vmcheck = "checked=\"checked\"";
	}
	else
	{
    $vmcheck = "";
	}
	if($user['limitvms'] == 1)
	{
    $limitvmcheck = "checked=\"checked\"";
	}
	else
	{
    $limitvmcheck = "";
	}
	if($user['allownotices'] == 1)
	{
		$allownoticescheck = "checked=\"checked\"";
	}
	else
	{
		$allownoticescheck = "";
	}

	if($user['invisible'] == 1)
	{
		$invisiblecheck = "checked=\"checked\"";
	}
	else
	{
		$invisiblecheck = "";
	}

	if($user['hideemail'] == 1)
	{
		$hideemailcheck = "checked=\"checked\"";
	}
	else
	{
		$hideemailcheck = "";
	}

	if($user['subscriptionmethod'] == 1)
	{
		$no_email_subscribe_selected = "selected=\"selected\"";
	}
	else if($user['subscriptionmethod'] == 2)
	{
		$instant_email_subscribe_selected = "selected=\"selected\"";
	}
	else
	{
		$no_subscribe_selected = "selected=\"selected\"";
	}

	if($user['showsigs'] == 1)
	{
		$showsigscheck = "checked=\"checked\"";;
	}
	else
	{
		$showsigscheck = "";
	}

	if($user['showavatars'] == 1)
	{
		$showavatarscheck = "checked=\"checked\"";
	}
	else
	{
		$showavatarscheck = "";
	}

	if($user['showquickreply'] == 1)
	{
		$showquickreplycheck = "checked=\"checked\"";
	}
	else
	{
		$showquickreplycheck = "";
	}

	if($user['remember'] == 1)
	{
		$remembercheck = "checked=\"checked\"";
	}
	else
	{
		$remembercheck = "";
	}

	if($user['receivepms'] == 1)
	{
		$receivepmscheck = "checked=\"checked\"";
	}
	else
	{
		$receivepmscheck = "";
	}

	if($user['pmnotice'] == 1 || $user['pmnotice'] == 2)
	{
		$pmnoticecheck = " checked=\"checked\"";
	}
	else
	{
		$pmnoticecheck = "";
	}

	if($user['dstcorrection'] == 2)
	{
		$dst_auto_selected = "selected=\"selected\"";
	}
	else if($user['dstcorrection'] == 1)
	{
		$dst_enabled_selected = "selected=\"selected\"";
	}
	else
	{
		$dst_disabled_selected = "selected=\"selected\"";
	}

	if($user['showcodebuttons'] == 1)
	{
		$showcodebuttonscheck = "checked=\"checked\"";
	}
	else
	{
		$showcodebuttonscheck = "";
	}

	if($user['showredirect'] != 0)
	{
		$showredirectcheck = "checked=\"checked\"";
	}
	else
	{
		$showredirectcheck = "";
	}

	if($user['pmnotify'] != 0)
	{
		$pmnotifycheck = "checked=\"checked\"";
	}
	else
	{
		$pmnotifycheck = '';
	}

	
	if($user['threadmode'] != "threaded" && $user['threadmode'] != "linear")
	{
		$user['threadmode'] = ''; // Leave blank to show default
	}

	if($user['classicpostbit'] != 0)
	{
		$classicpostbitcheck = "checked=\"checked\"";
	}
	else
	{
		$classicpostbitcheck = '';
	}

  //IF VMs are aenabled by admin, throw the template on my head
  if($daddyobb->settings['enablevmsystem'] == 1)
  {
    eval("\$vmoptions = \"".$templates->get("usercp_options_visitormessages")."\";");
  }

	$date_format_options = "<option value=\"0\">{$lang->use_default}</option>";
	foreach($date_formats as $key => $format)
	{
		if($user['dateformat'] == $key)
		{
			$date_format_options .= "<option value=\"$key\" selected=\"selected\">".my_date($format, TIME_NOW, "", 0)."</option>";
		}
		else
		{
			$date_format_options .= "<option value=\"$key\">".my_date($format, TIME_NOW, "", 0)."</option>";
		}
	}

	$time_format_options = "<option value=\"0\">{$lang->use_default}</option>";
	foreach($time_formats as $key => $format)
	{
		if($user['timeformat'] == $key)
		{
			$time_format_options .= "<option value=\"$key\" selected=\"selected\">".my_date($format, TIME_NOW, "", 0)."</option>";
		}
		else
		{
			$time_format_options .= "<option value=\"$key\">".my_date($format, TIME_NOW, "", 0)."</option>";
		}
	}

	$tzselect = build_timezone_select("timezoneoffset", $daddyobb->user['timezone'], true);

	$threadview[$user['threadmode']] = 'selected="selected"';
	$daysprunesel[$user['daysprune']] = 'selected="selected"';
	$stylelist = build_theme_select("style", $user['style']);
	if($daddyobb->settings['usertppoptions'])
	{
		$explodedtpp = explode(",", $daddyobb->settings['usertppoptions']);
		$tppoptions = '';
		if(is_array($explodedtpp))
		{
			foreach($explodedtpp as $key => $val)
			{
				$val = trim($val);
				$selected = "";
				if($user['tpp'] == $val)
				{
					$selected = "selected=\"selected\"";
				}
				$tppoptions .= "<option value=\"$val\" $selected>".$lang->sprintf($lang->tpp_option, $val)."</option>\n";
			}
		}
		eval("\$tppselect = \"".$templates->get("usercp_options_tppselect")."\";");
	}
	if($daddyobb->settings['userpppoptions'])
	{
		$explodedppp = explode(",", $daddyobb->settings['userpppoptions']);
		$pppoptions = '';
		if(is_array($explodedppp))
		{
			foreach($explodedppp as $key => $val)
			{
				$val = trim($val);
				$selected = "";
				if($user['ppp'] == $val)
				{
					$selected = "selected=\"selected\"";
				}
				$pppoptions .= "<option value=\"$val\" $selected>".$lang->sprintf($lang->ppp_option, $val)."</option>\n";
			}
		}
		eval("\$pppselect = \"".$templates->get("usercp_options_pppselect")."\";");
	}
	eval("\$editprofile = \"".$templates->get("usercp_options")."\";");
	$plugins->run_hooks("usercp_options_end");
	output_page($editprofile);
}

if($daddyobb->input['action'] == "do_email" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$errors = array();

	$plugins->run_hooks("usercp_do_email_start");
	if(validate_password_from_uid($daddyobb->user['uid'], $daddyobb->input['password']) == false)
	{
		$errors[] = $lang->error_invalidpassword;
	}
	else
	{
		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$user = array(
			"uid" => $daddyobb->user['uid'],
			"email" => $daddyobb->input['email'],
			"email2" => $daddyobb->input['email2']
		);

		$userhandler->set_data($user);

		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			if($daddyobb->user['usergroup'] != "5" && $daddyobb->usergroup['cancp'] != 1)
			{
				$activationcode = random_str();
				$now = TIME_NOW;
				$db->delete_query("awaitingactivation", "uid='".$daddyobb->user['uid']."'");
				$newactivation = array(
					"uid" => $daddyobb->user['uid'],
					"dateline" => TIME_NOW,
					"code" => $activationcode,
					"type" => "e",
					"oldgroup" => $daddyobb->user['usergroup'],
					"misc" => $db->escape_string($daddyobb->input['email'])
				);
				$db->insert_query("awaitingactivation", $newactivation);

				$username = $daddyobb->user['username'];
				$uid = $daddyobb->user['uid'];
				$lang->emailsubject_changeemail = $lang->sprintf($lang->emailsubject_changeemail, $daddyobb->settings['bbname']);
				$lang->email_changeemail = $lang->sprintf($lang->email_changeemail, $daddyobb->user['username'], $daddyobb->settings['bbname'], $daddyobb->user['email'], $daddyobb->input['email'], $daddyobb->settings['bburl'], $activationcode, $daddyobb->user['username'], $daddyobb->user['uid']);
				my_mail($daddyobb->input['email'], $lang->emailsubject_changeemail, $lang->email_changeemail);

				$plugins->run_hooks("usercp_do_email_verify");
				error($lang->redirect_changeemail_activation);
			}
			else
			{
				$userhandler->update_user();
				$plugins->run_hooks("usercp_do_email_changed");
				redirect("usercp.php", $lang->redirect_emailupdated);
			}
		}
	}
	if(count($errors) > 0)
	{
			$daddyobb->input['action'] = "email";
			$errors = inline_error($errors);
	}
}

if($daddyobb->input['action'] == "email")
{
	// Coming back to this page after one or more errors were experienced, show fields the user previously entered (with the exception of the password)
	if($errors)
	{
		$email = htmlspecialchars_uni($daddyobb->input['email']);
		$email2 = htmlspecialchars_uni($daddyobb->input['email2']);
	}
	else
	{
		$email = $email2 = '';
	}

	$plugins->run_hooks("usercp_email_start");
	eval("\$changemail = \"".$templates->get("usercp_email")."\";");
	$plugins->run_hooks("usercp_email_end");
	output_page($changemail);
}

if($daddyobb->input['action'] == "do_password" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$errors = array();

	$plugins->run_hooks("usercp_do_password_start");
	if(validate_password_from_uid($daddyobb->user['uid'], $daddyobb->input['oldpassword']) == false)
	{
		$errors[] = $lang->error_invalidpassword;
	}
	else
	{
		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$user = array(
			"uid" => $daddyobb->user['uid'],
			"password" => $daddyobb->input['password'],
			"password2" => $daddyobb->input['password2']
		);

		$userhandler->set_data($user);

		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			$userhandler->update_user();
			my_setcookie("daddyobbuser", $daddyobb->user['uid']."_".$userhandler->data['loginkey']);
			$plugins->run_hooks("usercp_do_password_end");
			redirect("usercp.php", $lang->redirect_passwordupdated);
		}
	}
	if(count($errors) > 0)
	{
			$daddyobb->input['action'] = "password";
			$errors = inline_error($errors);
	}
}

if($daddyobb->input['action'] == "password")
{
	$plugins->run_hooks("usercp_password_start");
	eval("\$editpassword = \"".$templates->get("usercp_password")."\";");
	$plugins->run_hooks("usercp_password_end");
	output_page($editpassword);
}

if($daddyobb->input['action'] == "do_changename" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_changename_start");
	if($daddyobb->usergroup['canchangename'] != 1)
	{
		error_no_permission();
	}

	if(validate_password_from_uid($daddyobb->user['uid'], $daddyobb->input['password']) == false)
	{
		$errors[] = $lang->error_invalidpassword;
	}
	else
	{
		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$user = array(
			"uid" => $daddyobb->user['uid'],
			"username" => $daddyobb->input['username']
		);

		$userhandler->set_data($user);

		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			$userhandler->update_user();
			$plugins->run_hooks("usercp_do_changename_end");
			redirect("usercp.php", $lang->redirect_namechanged);

		}
	}
	if(count($errors) > 0)
	{
		$errors = inline_error($errors);
		$daddyobb->input['action'] = "changename";
	}
}

if($daddyobb->input['action'] == "changename")
{
	$plugins->run_hooks("usercp_changename_start");
	if($daddyobb->usergroup['canchangename'] != 1)
	{
		error_no_permission();
	}
	eval("\$changename = \"".$templates->get("usercp_changename")."\";");
	$plugins->run_hooks("usercp_changename_end");
	output_page($changename);
}

if($daddyobb->input['action'] == "do_subscriptions")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_subscriptions_start");

	if(!is_array($daddyobb->input['check']))
	{
		error($lang->no_subscriptions_selected);
	}

	// Clean input - only accept integers thanks!
	$daddyobb->input['check'] = array_map('intval', $daddyobb->input['check']);
	$tids = implode(",", $daddyobb->input['check']);

	// Deleting these subscriptions?
	if($daddyobb->input['do'] == "delete")
	{
		$db->delete_query("threadsubscriptions", "tid IN ($tids) AND uid='{$daddyobb->user['uid']}'");
	}
	// Changing subscription type
	else
	{
		if($daddyobb->input['do'] == "no_notification")
		{
			$new_notification = 0;
		}
		else if($daddyobb->input['do'] == "instant_notification")
		{
			$new_notification = 1;
		}

		// Update
		$update_array = array("notification" => $new_notification);
		$db->update_query("threadsubscriptions", $update_array, "tid IN ($tids) AND uid='{$daddyobb->user['uid']}'");
	}

	// Done, redirect
	redirect("usercp.php?action=subscriptions", $lang->redirect_subscriptions_updated);
}

if($daddyobb->input['action'] == "subscriptions")
{
	$plugins->run_hooks("usercp_subscriptions_start");

	// Do Multi Pages
	$query = $db->simple_select("threadsubscriptions", "COUNT(tid) AS threads", "uid='".$daddyobb->user['uid']."'");
	$threadcount = $db->fetch_field($query, "threads");

	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}

	$perpage = $daddyobb->settings['threadsperpage'];
	$page = intval($daddyobb->input['page']);
	if($page > 0)
	{
		$start = ($page-1) *$perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$end = $start + $perpage;
	$lower = $start+1;
	$upper = $end;
	if($upper > $threadcount)
	{
		$upper = $threadcount;
	}
	$multipage = multipage($threadcount, $perpage, $page, "usercp.php?action=subscriptions");
	$fpermissions = forum_permissions();

	// Fetch subscriptions
	$query = $db->query("
		SELECT s.*, t.*, t.username AS threadusername, u.username
		FROM ".TABLE_PREFIX."threadsubscriptions s
		LEFT JOIN ".TABLE_PREFIX."threads t ON (s.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = t.uid)
		WHERE s.uid='".$daddyobb->user['uid']."'
		ORDER BY t.lastpost DESC
		LIMIT $start, $perpage
	");
	while($subscription = $db->fetch_array($query))
	{
		$forumpermissions = $fpermissions[$subscription['fid']];
		// Only keep if we're allowed to view them
		if($forumpermissions['canview'] != 0 || $forumpermissions['canviewthreads'] != 0)
		{
			$subscriptions[$subscription['tid']] = $subscription;
		}
		// Hmm, you don't have permission to view - unsubscribe!
		else if($subscription['tid'])
		{
			$del_subscriptions[] = $subscription['tid'];
		}
	}

	if(is_array($del_subscriptions))
	{
		$tids = implode(',', $del_subscriptions);
		if($tids)
		{
			$db->delete_query("threadsubscriptions", "tid IN ({$tids}) AND uid='{$daddyobb->user['uid']}'");
		}
	}

	if(is_array($subscriptions))
	{
		$tids = implode(",", array_keys($subscriptions));
		
		if($daddyobb->user['uid'] == 0)
		{
			// Build a forum cache.
			$query = $db->query("
				SELECT fid
				FROM ".TABLE_PREFIX."forums
				WHERE active != 0
				ORDER BY pid, disporder
			");
			
			$forumsread = unserialize($daddyobb->cookies['daddyobb']['forumread']);
		}
		else
		{
			// Build a forum cache.
			$query = $db->query("
				SELECT f.fid, fr.dateline AS lastread
				FROM ".TABLE_PREFIX."forums f
				LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid=f.fid AND fr.uid='{$daddyobb->user['uid']}')
				WHERE f.active != 0
				ORDER BY pid, disporder
			");
		}
		while($forum = $db->fetch_array($query))
		{
			if($daddyobb->user['uid'] == 0)
			{
				if($forumsread[$forum['fid']])
				{
					$forum['lastread'] = $forumsread[$forum['fid']];
				}
			}
			$readforums[$forum['fid']] = $forum['lastread'];
		}

		// Check participation by the current user in any of these threads - for 'dot' folder icons
		if($daddyobb->settings['dotfolders'] != 0)
		{
			$query = $db->simple_select("posts", "tid,uid", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})");
			while($post = $db->fetch_array($query))
			{
				$subscriptions[$post['tid']]['doticon'] = 1;
			}
		}

		// Read threads
		if($daddyobb->settings['threadreadcut'] > 0)
		{
			$query = $db->simple_select("threadsread", "*", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})");
			while($readthread = $db->fetch_array($query))
			{
				$subscriptions[$readthread['tid']]['lastread'] = $readthread['dateline'];
			}
		}

		// Now we can build our subscription list
		foreach($subscriptions as $thread)
		{
			$bgcolor = alt_trow();

			$folder = '';
			$prefix = '';

			// Sanitize
			$thread['subject'] = $parser->parse_badwords($thread['subject']);
			$thread['subject'] = htmlspecialchars_uni($thread['subject']);

			// Build our links
			$thread['threadlink'] = get_thread_link($thread['tid']);
			$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");

			// Fetch the thread icon if we have one
			if($thread['icon'] > 0 && $icon_cache[$thread['icon']])
			{
				$icon = $icon_cache[$thread['icon']];
				$icon = "<img src=\"{$icon['path']}\" alt=\"{$icon['name']}\" />";
			}
			else
			{
				$icon = "&nbsp;";
			}

			// Determine the folder
			$folder = '';
			$folder_label = '';

			if($thread['doticon'])
			{
				$folder = "dot_";
				$folder_label .= $lang->icon_dot;
			}

			$gotounread = '';
			$isnew = 0;
			$donenew = 0;
			$lastread = 0;

			if($daddyobb->settings['threadreadcut'] > 0 && $daddyobb->user['uid'])
			{
				$forum_read = $readforums[$thread['fid']];
			
				$read_cutoff = TIME_NOW-$daddyobb->settings['threadreadcut']*60*60*24;
				if($forum_read == 0 || $forum_read < $read_cutoff)
				{
					$forum_read = $read_cutoff;
				}
			}
			else
			{
				$forum_read = $forumsread[$thread['fid']];
			}

			if($daddyobb->settings['threadreadcut'] > 0 && $thread['lastpost'] > $forum_read)
			{
				$cutoff = TIME_NOW-$daddyobb->settings['threadreadcut']*60*60*24;
			}

			if($thread['lastpost'] > $cutoff)
			{
				if($thread['lastpost'] > $cutoff)
				{
					if($thread['lastread'])
					{
						$lastread = $thread['lastread'];
					}
					else
					{
						$lastread = 1;
					}
				}
			}

			if(!$lastread)
			{
				$readcookie = $threadread = my_get_array_cookie("threadread", $thread['tid']);
				if($readcookie > $forum_read)
				{
					$lastread = $readcookie;
				}
				else
				{
					$lastread = $forum_read;
				}
			}

			if($thread['lastpost'] > $lastread && $lastread)
			{
				$folder .= "new";
				$folder_label .= $lang->icon_new;
				$new_class = "subject_new";
				$thread['newpostlink'] = get_thread_link($thread['tid'], 0, "newpost");
				eval("\$gotounread = \"".$templates->get("forumdisplay_thread_gotounread")."\";");
				$unreadpost = 1;
			}
			else
			{
				$folder_label .= $lang->icon_no_new;
				$new_class = "";
			}

			if($thread['replies'] >= $daddyobb->settings['hottopic'] || $thread['views'] >= $daddyobb->settings['hottopicviews'])
			{
				$folder .= "hot";
				$folder_label .= $lang->icon_hot;
			}

			if($thread['closed'] == 1)
			{
				$folder .= "lock";
				$folder_label .= $lang->icon_lock;
			}

			$folder .= "folder";

			// Build last post info

			$lastpostdate = my_date($daddyobb->settings['dateformat'], $thread['lastpost']);
			$lastposttime = my_date($daddyobb->settings['timeformat'], $thread['lastpost']);
			$lastposter = $thread['lastposter'];
			$lastposteruid = $thread['lastposteruid'];

			// Don't link to guest's profiles (they have no profile).
			if($lastposteruid == 0)
			{
				$lastposterlink = $lastposter;
			}
			else
			{
				$lastposterlink = build_profile_link($lastposter, $lastposteruid);
			}

			$thread['replies'] = my_number_format($thread['replies']);
			$thread['views'] = my_number_format($thread['views']);

			// What kind of notification type do we have here?
			switch($thread['notification'])
			{
				case "1": // Instant
					$notification_type = $lang->instant_notification;
					break;
				default: // No notification
					$notification_type = $lang->no_notification;
			}

			eval("\$threads .= \"".$templates->get("usercp_subscriptions_thread")."\";");
		}
	}
	else
	{
		eval("\$threads = \"".$templates->get("usercp_subscriptions_none")."\";");
	}
	eval("\$subscriptions = \"".$templates->get("usercp_subscriptions")."\";");
	$plugins->run_hooks("usercp_subscriptions_end");
	output_page($subscriptions);
}
if($daddyobb->input['action'] == "forumsubscriptions")
{
	$plugins->run_hooks("usercp_forumsubscriptions_start");
	$query = $db->simple_select("forumpermissions", "*", "gid='".$db->escape_string($daddyobb->user['usergroup'])."'");
	while($permissions = $db->fetch_array($query))
	{
		$permissioncache[$permissions['gid']][$permissions['fid']] = $permissions;
	}
	
	if($daddyobb->user['uid'] == 0)
	{
		// Build a forum cache.
		$query = $db->query("
			SELECT fid
			FROM ".TABLE_PREFIX."forums
			WHERE active != 0
			ORDER BY pid, disporder
		");
		
		$forumsread = unserialize($daddyobb->cookies['daddyobb']['forumread']);
	}
	else
	{
		// Build a forum cache.
		$query = $db->query("
			SELECT f.fid, fr.dateline AS lastread
			FROM ".TABLE_PREFIX."forums f
			LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid=f.fid AND fr.uid='{$daddyobb->user['uid']}')
			WHERE f.active != 0
			ORDER BY pid, disporder
		");
		$forumcount = $db->num_rows($query);
	}
	while($forum = $db->fetch_array($query))
	{
		if($daddyobb->user['uid'] == 0)
		{
			if($forumsread[$forum['fid']])
			{
				$forum['lastread'] = $forumsread[$forum['fid']];
			}
		}
		$readforums[$forum['fid']] = $forum['lastread'];
	}
	
	$fpermissions = forum_permissions();
	$query = $db->query("
		SELECT fs.*, f.*, t.subject AS lastpostsubject
		FROM ".TABLE_PREFIX."forumsubscriptions fs
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = fs.fid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid = f.lastposttid)
		WHERE f.type='f' AND fs.uid='".$daddyobb->user['uid']."'
		ORDER BY f.name ASC
	");
	$forums = '';
	while($forum = $db->fetch_array($query))
	{
		$forum_url = get_forum_link($forum['fid']);
		$forumpermissions = $fpermissions[$forum['fid']];
		if($forumpermissions['canview'] != 0)
		{
			if(($forum['lastpost'] > $daddyobb->user['lastvisit'] || $readforums[$forum['fid']] > $daddyobb->user['lastvisit']) && $forum['lastpost'] != 0)
			{
				$folder = "on";
			}
			else
			{
				$folder = "off";
			}
			if($forum['lastpost'] == 0 || $forum['lastposter'] == "")
			{
				$lastpost = "<div align=\"center\">$lang->never</div>";
			}
			else
			{
				$lastpost_date = my_date($daddyobb->settings['dateformat'], $forum['lastpost']);
				$lastpost_time = my_date($daddyobb->settings['timeformat'], $forum['lastpost']);
				$lastposttid = $forum['lastposttid'];
				$lastposter = $forum['lastposter'];
				$lastpost_profilelink = build_profile_link($lastposter, $forum['lastposteruid']);
				$lastpost_subject = $forum['lastpostsubject'];
				if(my_strlen($lastpost_subject) > 25)
				{
					$lastpost_subject = my_substr($lastpost_subject, 0, 25) . "...";
				}
				$lastpost_link = get_thread_link($forum['lastposttid'], 0, "lastpost");
				eval("\$lastpost = \"".$templates->get("forumbit_depth2_forum_lastpost")."\";");
			}
		}
		$posts = my_number_format($forum['posts']);
		$threads = my_number_format($forum['threads']);
		if($daddyobb->settings['showdescriptions'] == 0)
		{
			$forum['description'] = "";
		}
		eval("\$forums .= \"".$templates->get("usercp_forumsubscriptions_forum")."\";");
	}
	if(!$forums)
	{
		eval("\$forums = \"".$templates->get("usercp_forumsubscriptions_none")."\";");
	}
	$plugins->run_hooks("usercp_forumsubscriptions_end");
	eval("\$forumsubscriptions = \"".$templates->get("usercp_forumsubscriptions")."\";");
	output_page($forumsubscriptions);
}

if($daddyobb->input['action'] == "do_editsig" && $daddyobb->request_method == "post")
{	
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_editsig_start");
	if($daddyobb->input['updateposts'] == "enable")
	{
		$update_signature = array(
			"includesig" => 1
		);
		$db->update_query("posts", $update_signature, "uid='".$daddyobb->user['uid']."'");
	}
	elseif($daddyobb->input['updateposts'] == "disable")
	{
		$update_signature = array(
			"includesig" => 0
		);
		$db->update_query("posts", $update_signature, "uid='".$daddyobb->user['uid']."'");
	}
	$new_signature = array(
		"signature" => $db->escape_string($daddyobb->input['signature'])
	);
	$plugins->run_hooks("usercp_do_editsig_process");
	$db->update_query("users", $new_signature, "uid='".$daddyobb->user['uid']."'");
	$plugins->run_hooks("usercp_do_editsig_end");
  $lang->redirect_profileupdated = $lang->sprintf($lang->redirect_profileupdated, $daddyobb->user['username']);
	redirect("usercp.php", $lang->redirect_profileupdated);

}

if($daddyobb->input['action'] == "editsig")
{

	$plugins->run_hooks("usercp_editsig_start");
	if($daddyobb->input['preview'] && !$error)
	{
		$sig = $daddyobb->input['signature'];
		$template = "usercp_editsig_preview";
	}
	elseif(!$error)
	{
		$sig = $daddyobb->user['signature'];
		$template = "usercp_editsig_current";
	}
	else if($error)
	{
		$sig = $daddyobb->input['signature'];
		$template = false;
	}

	if($sig && $template)
	{
		$sig_parser = array(
      "allow_html" => $daddyobb->usergroup['sigallowhtml'],
      "allow_mycode" => $daddyobb->usergroup['sigallowmycode'],
      "allow_smilies" => $daddyobb->usergroup['sigallowsmilies'],
      "allow_imgcode" => $daddyobb->usergroup['sigallowimgcode'],
			"me_username" => $daddyobb->user['username'],
		);

		$sigpreview = $parser->parse_message($sig, $sig_parser);
		eval("\$signature = \"".$templates->get($template)."\";");
	}
	if($daddyobb->usergroup['sigallowsmilies'] == 1)
	{
		$sigsmilies = $lang->on;
		$smilieinserter = build_clickable_smilies();
	}
	else
	{
		$sigsmilies = $lang->off;
	}
	if($daddyobb->usergroup['sigallowmycode'] == 1)
	{
		$sigmycode = $lang->on;
	}
	else
	{
		$sigmycode = $lang->off;
	}
	if($daddyobb->usergroup['sigallowhtml'] == 1)
	{
		$sightml = $lang->on;
	}
	else
	{
		$sightml = $lang->off;
	}
	if($daddyobb->usergroup['sigallowimgcode'] == 1)
	{
		$sigimgcode = $lang->on;
	}
	else
	{
		$sigimgcode = $lang->off;
	}
	$sig = htmlspecialchars_uni($sig);
	$lang->edit_sig_note2 = $lang->sprintf($lang->edit_sig_note2, $sigsmilies, $sigmycode, $sigimgcode, $sightml, $daddyobb->usergroup['sigmaxchars']);

	if($daddyobb->settings['bbcodeinserter'] != 0 || $daddyobb->user['showcodebuttons'] != 0)
	{
		$codebuttons = build_mycode_inserter("signature");
	}

	eval("\$editsig = \"".$templates->get("usercp_editsig")."\";");
	$plugins->run_hooks("usercp_editsig_end");
	output_page($editsig);
}

if($daddyobb->input['action'] == "do_avatar" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_avatar_start");
	require_once DADDYOBB_ROOT."inc/functions_upload.php";

	$avatar_error = "";

	if($daddyobb->input['remove']) // remove avatar
	{
		$updated_avatar = array(
			"avatar" => "",
			"avatardimensions" => "",
			"avatartype" => ""
		);
		$db->update_query("users", $updated_avatar, "uid='".$daddyobb->user['uid']."'");
		remove_avatars($daddyobb->user['uid']);
	}
	elseif($daddyobb->input['gallery']) // Gallery avatar
	{
		if(empty($daddyobb->input['avatar']))
		{
			$avatar_error = $lang->error_noavatar;
		}

		if(empty($avatar_error))
		{
			if($daddyobb->input['gallery'] == "default")
			{
				$avatarpath = $db->escape_string($daddyobb->settings['avatardir']."/".$daddyobb->input['avatar']);
			}
			else
			{
				$avatarpath = $db->escape_string($daddyobb->settings['avatardir']."/".$daddyobb->input['gallery']."/".$daddyobb->input['avatar']);
			}

			if(file_exists($avatarpath))
			{
				$dimensions = @getimagesize($avatarpath);

				$updated_avatar = array(
					"avatar" => $avatarpath,
					"avatardimensions" => "{$dimensions[0]}|{$dimensions[1]}",
					"avatartype" => "gallery"
				);
				$db->update_query("users", $updated_avatar, "uid='".$daddyobb->user['uid']."'");
			}
			remove_avatars($daddyobb->user['uid']);
		}
	}
	elseif($_FILES['avatarupload']['name']) // upload avatar
	{
		if($daddyobb->usergroup['canuploadavatars'] == 0)
		{
			error_no_permission();
		}
		$avatar = upload_avatar();
		if($avatar['error'])
		{
			$avatar_error = $avatar['error'];
		}
		else
		{
			if($avatar['width'] > 0 && $avatar['height'] > 0)
			{
				$avatar_dimensions = $avatar['width']."|".$avatar['height'];
			}
			$updated_avatar = array(
				"avatar" => $avatar['avatar'],
				"avatardimensions" => $avatar_dimensions,
				"avatartype" => "upload"
			);
			$db->update_query("users", $updated_avatar, "uid='".$daddyobb->user['uid']."'");
		}
	}
	else // remote avatar
	{
		$daddyobb->input['avatarurl'] = preg_replace("#script:#i", "", $daddyobb->input['avatarurl']);
		$ext = get_extension($daddyobb->input['avatarurl']);

		// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
		$file = fetch_remote_file($daddyobb->input['avatarurl']);
		if(!$file)
		{
			$avatar_error = $lang->error_invalidavatarurl;
		}
		else
		{
			$tmp_name = $daddyobb->settings['avataruploadpath']."/remote_".md5(uniqid(rand(), true));
			$fp = @fopen($tmp_name, "wb");
			if(!$fp)
			{
				$avatar_error = $lang->error_invalidavatarurl;
			}
			else
			{
				fwrite($fp, $file);
				fclose($fp);
				list($width, $height, $type) = @getimagesize($tmp_name);
				@unlink($tmp_name);
				if(!$type)
				{
					$avatar_error = $lang->error_invalidavatarurl;
				}
			}
		}

		if(empty($avatar_error))
		{
			if($width && $height && $daddyobb->settings['maxavatardims'] != "")
			{
				list($maxwidth, $maxheight) = explode("x", my_strtolower($daddyobb->settings['maxavatardims']));
				if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
				{
					$lang->error_avatartoobig = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight);
					$avatar_error = $lang->error_avatartoobig;
				}
			}
		}

		if(empty($avatar_error))
		{
			if($width > 0 && $height > 0)
			{
				$avatar_dimensions = intval($width)."|".intval($height);
			}
			$updated_avatar = array(
				"avatar" => $db->escape_string($daddyobb->input['avatarurl']),
				"avatardimensions" => $avatar_dimensions,
				"avatartype" => "remote"
			);
			$db->update_query("users", $updated_avatar, "uid='".$daddyobb->user['uid']."'");
			remove_avatars($daddyobb->user['uid']);
		}
	}

	if(empty($avatar_error))
	{
		$plugins->run_hooks("usercp_do_avatar_end");
		redirect("usercp.php", $lang->redirect_avatarupdated);
	}
	else
	{
		$daddyobb->input['action'] = "avatar";
		$avatar_error = inline_error($avatar_error);
	}
}

if($daddyobb->input['action'] == "avatar")
{
	$plugins->run_hooks("usercp_avatar_start");
//Function to count the avys XD
  function CountDir($adir, $recurse)
  {
    $count = 0;
    $d = opendir($adir);
    while ($entry = readdir($d))
    {
      if (!(($entry == "..") || ($entry == ".")))
      {
        if (Is_Dir($adir . '/' . $entry))
        {
          if ($recurse)
          {
            $count += CountDir($adir . '/' . $entry, $recurse);
          }
        }
        else
        {
          $count++;
        }
      }
    }
    
    return $count;
  }
	// Get a listing of available galleries
	$gallerylist['default'] = $lang->default_gallery;
	$avatardir = @opendir($daddyobb->settings['avatardir']);
	while($dir = @readdir($avatardir))
	{
		if(is_dir($daddyobb->settings['avatardir']."/$dir") && substr($dir, 0, 1) != ".")
		{
			$gallerylist[$dir] = str_replace("_", " ", $dir);
		}
	}
	@closedir($avatardir);
	natcasesort($gallerylist);
	reset($gallerylist);
	$galleries = '';
	foreach($gallerylist as $dir => $friendlyname)
	{
		if($dir == $daddyobb->input['gallery'])
		{
			$activegallery = $friendlyname;
			$selected = "selected=\"selected\"";
		}
	//Count Files in Gallery Folder
		if($dir != "default")
		{
				$count = CountDir($daddyobb->settings['avatardir']."/$dir", false);
		}
		else
		{
			$count = CountDir($daddyobb->settings['avatardir'], false);
		}
		$galleries .= "<option value=\"$dir\" $selected>$friendlyname ($count)</option>\n";
		$selected = "";
	}
	// Check to see if we're in a gallery or not
	if($activegallery)
	{
		$gallery = str_replace("..", "", $daddyobb->input['gallery']);
		$lang->avatars_in_gallery = $lang->sprintf($lang->avatars_in_gallery, $activegallery);
		// Get a listing of avatars in this gallery
		$avatardir = $daddyobb->settings['avatardir'];
		if($gallery != "default")
		{
			$avatardir .= "/$gallery";
		}
		$opendir = opendir($avatardir);
		while($avatar = @readdir($opendir))
		{
			$avatarpath = $avatardir."/".$avatar;
			if(is_file($avatarpath) && preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $avatar))
			{
				$avatars[] = $avatar;
			}
		}
		@closedir($opendir);

		if(is_array($avatars))
		{
			natcasesort($avatars);
			reset($avatars);
			$count = 0;
			$avatarlist = "<tr>\n";
			foreach($avatars as $avatar)
			{
				$avatarpath = $avatardir."/".$avatar;
				$avatarname = preg_replace("#\.(jpg|jpeg|gif|bmp|png)$#i", "", $avatar);
				$avatarname = ucwords(str_replace("_", " ", $avatarname));
				if($daddyobb->user['avatar'] == $avatarpath)
				{
					$checked = "checked=\"checked\"";
				}
				if($count == 3)
				{
					$avatarlist .= "</tr>\n<tr>\n";
					$count = 0;
				}
				++$count;
				eval("\$avatarlist .= \"".$templates->get("usercp_avatar_gallery_avatar")."\";");
			}
			if($count != 0)
			{
				for($i = $count; $i <= 5; ++$i)
				{
					eval("\$avatarlist .= \"".$templates->get("usercp_avatar_gallery_blankblock")."\";");
				}
			}
		}
		else
		{
			eval("\$avatarlist = \"".$templates->get("usercp_avatar_gallery_noavatars")."\";");
		}
		eval("\$gallery = \"".$templates->get("usercp_avatar_gallery")."\";");
		$plugins->run_hooks("usercp_avatar_end");
		output_page($gallery);
	}
	// Show main avatar page
	else
	{
		if($daddyobb->user['avatartype'] == "upload" || stristr($daddyobb->user['avatar'], $daddyobb->settings['avataruploadpath']))
		{
			$avatarmsg = "<br /><strong>".$lang->already_uploaded_avatar."</strong>";
		}
		elseif($daddyobb->user['avatartype'] == "gallery" || stristr($daddyobb->user['avatar'], $daddyobb->settings['avatardir']))
		{
			$avatarmsg = "<br /><strong>".$lang->using_gallery_avatar."</strong>";
		}
		elseif($daddyobb->user['avatartype'] == "remote" || my_strpos(my_strtolower($daddyobb->user['avatar']), "http://") !== false)
		{
			$avatarmsg = "<br /><strong>".$lang->using_remote_avatar."</strong>";
			$avatarurl = htmlspecialchars_uni($daddyobb->user['avatar']);
		}
		$urltoavatar = htmlspecialchars_uni($daddyobb->user['avatar']);
		if($daddyobb->user['avatar'])
		{
			$avatar_dimensions = explode("|", $daddyobb->user['avatardimensions']);
			if($avatar_dimensions[0] && $avatar_dimensions[1])
			{
				$avatar_width_height = "width=\"{$avatar_dimensions[0]}\" height=\"{$avatar_dimensions[1]}\"";
			}
			eval("\$currentavatar = \"".$templates->get("usercp_avatar_current")."\";");
			$colspan = 1;
		}
		else
		{
			$colspan = 2;
		}
		if($daddyobb->usergroup['avatarmaxdimensions'] != "")
		{
			list($maxwidth, $maxheight) = explode("x", my_strtolower($daddyobb->usergroup['avatarmaxdimensions']));
			$lang->avatar_note .= "<br />".$lang->sprintf($lang->avatar_note_dimensions, $maxwidth, $maxheight);
		}
		if($daddyobb->usergroup['avatarmaxsize'])
		{
			$maxsize = get_friendly_size($daddyobb->usergroup['avatarmaxsize']*1024);
			$lang->avatar_note .= "<br />".$lang->sprintf($lang->avatar_note_size, $maxsize);
		}
		if($daddyobb->settings['avatarresizing'] == "auto")
		{
			$auto_resize = "<br /><span class=\"smalltext\">{$lang->avatar_auto_resize_note}</span>\n";
		}
		else if($daddyobb->settings['avatarresizing'] == "user")
		{
			$auto_resize = "<br /><span class=\"smalltext\"><input type=\"checkbox\" name=\"auto_resize\" value=\"1\" checked=\"checked\" id=\"auto_resize\" /> <label for=\"auto_resize\">{$lang->avatar_auto_resize_option}</label></span>";
		}
		
		$lang->avatar_notice = $lang->sprintf($lang->avatar_notice, $maxwidth, $maxheight, $maxsize);

		eval("\$avatar = \"".$templates->get("usercp_avatar")."\";");
		$plugins->run_hooks("usercp_avatar_end");
		output_page($avatar);
	}
}
if($daddyobb->input['action'] == "notepad")
{
	$plugins->run_hooks("usercp_notepad_start");
	$daddyobb->user['notepad'] = htmlspecialchars_uni($daddyobb->user['notepad']);
	eval("\$notepad = \"".$templates->get("usercp_notepad")."\";");
	$plugins->run_hooks("usercp_notepad_end");
	output_page($notepad);
}
if($daddyobb->input['action'] == "do_notepad" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_notepad_start");
	$db->update_query("users", array('notepad' => $db->escape_string($daddyobb->input['notepad'])), "uid='".$daddyobb->user['uid']."'");
	$plugins->run_hooks("usercp_do_notepad_end");
	redirect("usercp.php", $lang->redirect_notepadupdated);
}

if($daddyobb->input['action'] == "do_editlists")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_editlists_start");

	$existing_users = array();
	if($daddyobb->input['manage'] == "ignored")
	{
		if($daddyobb->user['ignorelist'])
		{
			$existing_users = explode(",", $daddyobb->user['ignorelist']);
		}
	}
	else
	{
		if($daddyobb->user['buddylist'])
		{
			$existing_users = explode(",", $daddyobb->user['buddylist']);
		}
	}
	
	$error_message = "";
	$message = "";
	
	// Adding one or more users to this list
	if($daddyobb->input['add_username'])
	{
		// Split up any usernames we have
		$found_users = 0;
		$adding_self = false;
		$users = explode(",", $daddyobb->input['add_username']);
		$users = array_map("trim", $users);
		$users = array_unique($users);
		foreach($users as $key => $username)
		{
			if(empty($username))
			{
				unset($users[$key]);
				continue;
			}

			if(my_strtoupper($daddyobb->user['username']) == my_strtoupper($username))
			{
				$adding_self = true;
				unset($users[$key]);
				continue;
			}
			$users[$key] = $db->escape_string($username);
		}

		// Fetch out new users
		if(count($users) > 0)
		{
			$query = $db->simple_select("users", "uid", "LOWER(username) IN ('".my_strtolower(implode("','", $users))."')");
			while($user = $db->fetch_array($query))
			{
				++$found_users;

				// Make sure we're not adding a duplicate
				if(in_array($user['uid'], $existing_users))
				{
					if($daddyobb->input['manage'] == "ignored")
					{
						$error_message = $lang->users_already_on_ignore_list;
					}
					else
					{
						$error_message = $lang->users_already_on_buddy_list;
					}
					array_pop($users); // To maintain a proper count when we call count($users)
					continue;
				}
				
				$existing_users[] = $user['uid'];
			}
		}

		if(($adding_self != true || ($adding_self == true && count($users) > 0)) && ($error_message == "" || count($users) > 1))
		{
			if($daddyobb->input['manage'] == "ignored")
			{
				$message = $lang->users_added_to_ignore_list;
			}
			else
			{
				$message = $lang->users_added_to_buddy_list;
			}
		}

		if($adding_self == true)
		{
			if($daddyobb->input['manage'] == "ignored")
			{
				$error_message = $lang->cant_add_self_to_ignore_list;
			}
			else
			{
				$error_message = $lang->cant_add_self_to_buddy_list;
			}
		}

		if(count($existing_users) == 0)
		{
			$message = "";
		}

		if($found_users < count($users))
		{
			if($error_message)
			{
				$error_message .= "<br />";
			}

			$error_message .= $lang->invalid_user_selected;
		}
	}

	// Removing a user from this list
	else if($daddyobb->input['delete'])
	{
		// Check if user exists on the list
		$key = array_search($daddyobb->input['delete'], $existing_users);
		if($key !== false)
		{
			unset($existing_users[$key]);
			$user = get_user($daddyobb->input['delete']);
			if($daddyobb->input['manage'] == "ignored")
			{
				$message = $lang->removed_from_ignore_list;
			}
			else
			{
				$message = $lang->removed_from_buddy_list;
			}
			$message = $lang->sprintf($message, $user['username']);
		}
	}

	// Now we have the new list, so throw it all back together
	$new_list = implode(",", $existing_users);

	// And clean it up a little to ensure there is no possibility of bad values
	$new_list = preg_replace("#,{2,}#", ",", $new_list);
	$new_list = preg_replace("#[^0-9,]#", "", $new_list);

	if(my_substr($new_list, 0, 1) == ",")
	{
		$new_list = my_substr($new_list, 1);
	}
	if(my_substr($new_list, -1) == ",")
	{
		$new_list = my_substr($new_list, 0, my_strlen($new_list)-2);
	}

	// And update
	$user = array();
	if($daddyobb->input['manage'] == "ignored")
	{
		$user['ignorelist'] = $db->escape_string($new_list);
		$daddyobb->user['ignorelist'] = $user['ignorelist'];
	}
	else
	{
		$user['buddylist'] = $db->escape_string($new_list);
		$daddyobb->user['buddylist'] = $user['buddylist'];
		//Make a complicated procedure to check if we are already on the users friendlist
		$incoming_user = get_user($daddyobb->input['uid']); //This is the user we want to add
		$incomingbuddies = explode(",", $incoming_user['buddylist']); // These are his/her buddies
		if(in_array($daddyobb->user['uid'], $incomingbuddies))
		{		
		}
		else
		{
      if($daddyobb->input['add_username'])
      {
        // Bring up the PM handler
        require_once DADDYOBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        $lang->incoming_friend_request = $lang->sprintf($lang->incoming_friend_request, $daddyobb->user['username']);
        $lang->friend_message = $lang->sprintf($lang->friend_message, $daddyobb->user['username'], $daddyobb->user['uid'], $daddyobb->post_code);

        $pm = array(
          "subject" => $lang->incoming_friend_request,
          "message" => $lang->friend_message,
          "fromid" => $daddyobb->user['uid'],
          "toid" => array($daddyobb->input['uid'])
        );

        $pm['options'] = array(
          "signature" => 1,
          "disablesmilies" => 1,
          "savecopy" => 0,
          "readreceipt" => 1
        );

        $pmhandler->set_data($pm);

        // Now let the pm handler do all the hard work.
        if(!$pmhandler->validate_pm())
        {
          $pm_errors = $pmhandler->get_friendly_errors();
          if($warn_errors)
          {
            $warn_errors = array_merge($warn_errors, $pm_errors);
          }
          else
          {
            $warn_errors = $pm_errors;
          }
        }
        else
        {
          $pminfo = $pmhandler->insert_pm();
        }
      }
		}
	}

	$db->update_query("users", $user, "uid='".$daddyobb->user['uid']."'");

	$plugins->run_hooks("usercp_do_editlists_end");

	// Ajax based request, throw new list to browser
	if($daddyobb->input['ajax'])
	{
		if($daddyobb->input['manage'] == "ignored")
		{
			$list = "ignore";
		}
		else
		{
			$list = "buddy";
		}

		if($message)
		{
			$message_js = "var success = document.createElement('div'); var element = \$('{$list}_list'); element.parentNode.insertBefore(success, element); success.innerHTML = '{$message}'; success.className = 'success_message'; window.setTimeout(function() { Element.remove(success) }, 5000);";
		}

		if($error_message)
		{
			$message_js .= " var error = document.createElement('div'); var element = \$('{$list}_list'); element.parentNode.insertBefore(error, element); 	error.innerHTML = '{$error_message}'; error.className = 'error_message'; window.setTimeout(function() { Element.remove(error) }, 5000);";
		}

		if($daddyobb->input['delete'])
		{
			header("Content-type: text/javascript");
			echo "Element.remove('{$daddyobb->input['manage']}_{$daddyobb->input['delete']}');\n";
			if($new_list == "")
			{
				echo "\$('{$daddyobb->input['manage']}_count').innerHTML = '0';\n";
				if($daddyobb->input['manage'] == "ignored")
				{
					echo "\$('ignore_list').innerHTML = '<li>{$lang->ignore_list_empty}</li>';\n";
				}
				else
				{
					echo "\$('buddy_list').innerHTML = '<li>{$lang->buddy_list_empty}</li>';\n";
				}
			}
			else
			{
				echo "\$('{$daddyobb->input['manage']}_count').innerHTML = '".count(explode(",", $new_list))."';\n";
			}
			echo $message_js;
			exit;
		}
		$daddyobb->input['action'] = "editlists";
	}
	else
	{
		if($error_message)
		{
			$message .= "<br />".$error_message;
		}
		redirect("usercp.php?action=editlists#{$daddyobb->input['manage']}", $message);
	}
}

if($daddyobb->input['action'] == "editlists")
{
	$plugins->run_hooks("usercp_editlists_start");

	$timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];

	// Fetch out buddies
	$buddy_count = 0;
	if($daddyobb->user['buddylist'])
	{
		$type = "buddy";
		$query = $db->simple_select("users", "*", "uid IN ({$daddyobb->user['buddylist']})", array("order_by" => "username"));
		while($user = $db->fetch_array($query))
		{
			$profile_link = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
			if($user['lastactive'] > $timecut && ($user['invisible'] == 0 || $daddyobb->user['usergroup'] == 4) && $user['lastvisit'] != $user['lastactive'])
			{
				$status = "online";
			}
			else
			{
				$status = "offline";
			}
			eval("\$buddy_list .= \"".$templates->get("usercp_editlists_user")."\";");
			++$buddy_count;
		}
	}

	$lang->total_buddies = $lang->sprintf($lang->total_buddies, $buddy_count);

	// Fetch out ignore list users
	$ignore_count = 0;
	if($daddyobb->user['ignorelist'])
	{
		$type = "ignored";
		$query = $db->simple_select("users", "*", "uid IN ({$daddyobb->user['ignorelist']})", array("order_by" => "username"));
		while($user = $db->fetch_array($query))
		{
			$profile_link = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
			if($user['lastactive'] > $timecut && ($user['invisible'] == 0 || $daddyobb->user['usergroup'] == 4) && $user['lastvisit'] != $user['lastactive'])
			{
				$status = "online";
			}
			else
			{
				$status = "offline";
			}
			eval("\$ignore_list .= \"".$templates->get("usercp_editlists_user")."\";");
			++$ignore_count;
		}
	}

	$lang->total_ignores = $lang->sprintf($lang->total_ignores, $ignore_count);

	// If an AJAX request from buddy management, echo out whatever the new list is.
	if($daddyobb->request_method == "post" && $daddyobb->input['ajax'] == 1)
	{
		if($daddyobb->input['manage'] == "ignored")
		{
			echo $ignore_list;
			echo "<script type=\"text/javascript\"> $('ignored_count').innerHTML = '{$ignore_count}'; {$message_js}</script>";
		}
		else
		{
			echo $buddy_list;
			echo "<script type=\"text/javascript\"> $('buddy_count').innerHTML = '{$buddy_count}'; {$message_js}</script>";
		}
		exit;
	}

	eval("\$listpage = \"".$templates->get("usercp_editlists")."\";");
	$plugins->run_hooks("usercp_editlists_end");
	output_page($listpage);
}

if($daddyobb->input['action'] == "drafts")
{
	$plugins->run_hooks("usercp_drafts_start");
	
	// Show a listing of all of the current 'draft' posts or threads the user has.
	$draftcount = 0;
	$drafts = '';
	$query = $db->query("
		SELECT p.subject, p.pid, t.tid, t.subject AS threadsubject, t.fid, f.name AS forumname, p.dateline, t.visible AS threadvisible, p.visible AS postvisible
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid=t.fid)
		WHERE p.uid='".$daddyobb->user['uid']."' AND p.visible='-2'
		ORDER BY p.dateline DESC
	");
	while($draft = $db->fetch_array($query))
	{
		$trow = alt_trow();
		if($draft['threadvisible'] == 1) // We're looking at a draft post
		{
			$detail = $lang->thread." <a href=\"".get_thread_link($draft['tid'])."\">".htmlspecialchars_uni($draft['threadsubject'])."</a>";
			$editurl = "newreply.php?action=editdraft&amp;pid={$draft['pid']}";
			$id = $draft['pid'];
			$type = "post";
		}
		elseif($draft['threadvisible'] == -2) // We're looking at a draft thread
		{
			$detail = $lang->forum." <a href=\"".get_forum_link($draft['fid'])."\">".htmlspecialchars_uni($draft['forumname'])."</a>";
			$editurl = "newthread.php?action=editdraft&amp;tid={$draft['tid']}";
			$id = $draft['tid'];
			$type = "thread";
		}
		$draft['subject'] = htmlspecialchars_uni($draft['subject']);
		$savedate = my_date($daddyobb->settings['dateformat'], $draft['dateline']);
		$savetime = my_date($daddyobb->settings['timeformat'], $draft['dateline']);
		$draftcount++;
		eval("\$drafts .= \"".$templates->get("usercp_drafts_draft")."\";");
	}
	eval("\$draftlist = \"".$templates->get("usercp_drafts")."\";");
	$plugins->run_hooks("usercp_drafts_end");
	output_page($draftlist);

}
if($daddyobb->input['action'] == "do_drafts" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_drafts_start");
	if(!$daddyobb->input['deletedraft'])
	{
		error($lang->no_drafts_selected);
	}
	$pidin = array();
	$tidin = array();
	foreach($daddyobb->input['deletedraft'] as $id => $val)
	{
		if($val == "post")
		{
			$pidin[] = "'".intval($id)."'";
		}
		elseif($val == "thread")
		{
			$tidin[] = "'".intval($id)."'";
		}
	}
	if($tidin)
	{
		$tidin = implode(",", $tidin);
		$db->delete_query("threads", "tid IN ($tidin) AND visible='-2' AND uid='".$daddyobb->user['uid']."'");
		$tidinp = "OR tid IN ($tidin)";
	}
	if($pidin || $tidinp)
	{
		if($pidin)
		{
			$pidin = implode(",", $pidin);
			$pidinq = "pid IN ($pidin)";
		}
		else
		{
			$pidinq = "1=0";
		}
		$db->delete_query("posts", "($pidinq $tidinp) AND visible='-2' AND uid='".$daddyobb->user['uid']."'");
	}
	$plugins->run_hooks("usercp_do_drafts_end");
	redirect("usercp.php?action=drafts", $lang->selected_drafts_deleted);
}
if($daddyobb->input['action'] == "usergroups")
{
	$plugins->run_hooks("usercp_usergroups_start");
	$ingroups = ",".$daddyobb->user['usergroup'].",".$daddyobb->user['additionalgroups'].",".$daddyobb->user['displaygroup'].",";

	// Changing our display group
	if($daddyobb->input['displaygroup'])
	{
		// Verify incoming POST request
		verify_post_check($daddyobb->input['my_post_key']);

		if(my_strpos($ingroups, ",".$daddyobb->input['displaygroup'].",") === false)
		{
			error($lang->not_member_of_group);
		}
		$query = $db->simple_select("usergroups", "*", "gid='".intval($daddyobb->input['displaygroup'])."'");
		$dispgroup = $db->fetch_array($query);
		if($dispgroup['candisplaygroup'] != 1)
		{
			error($lang->cannot_set_displaygroup);
		}
		$db->update_query("users", array('displaygroup' => intval($daddyobb->input['displaygroup'])), "uid='".$daddyobb->user['uid']."'");
		$cache->update_moderators();
		$plugins->run_hooks("usercp_usergroups_change_displaygroup");
		redirect("usercp.php?action=usergroups", $lang->display_group_changed);
		exit;
	}

	// Leaving a group
	if($daddyobb->input['leavegroup'])
	{
		// Verify incoming POST request
		verify_post_check($daddyobb->input['my_post_key']);

		if(my_strpos($ingroups, ",".$daddyobb->input['leavegroup'].",") === false)
		{
			error($lang->not_member_of_group);
		}
		if($daddyobb->user['usergroup'] == $daddyobb->input['leavegroup'])
		{
			error($lang->cannot_leave_primary_group);
		}
		$query = $db->simple_select("usergroups", "*", "gid='".intval($daddyobb->input['leavegroup'])."'");
		$usergroup = $db->fetch_array($query);
		if($usergroup['type'] != 4 && $usergroup['type'] != 3)
		{
			error($lang->cannot_leave_group);
		}
		leave_usergroup($daddyobb->user['uid'], $daddyobb->input['leavegroup']);
		$plugins->run_hooks("usercp_usergroups_leave_group");
		redirect("usercp.php?action=usergroups", $lang->left_group);
		exit;
	}

	// Joining a group
	if($daddyobb->input['joingroup'])
	{
		// Verify incoming POST request
		verify_post_check($daddyobb->input['my_post_key']);

		$daddyobb->input['joingroup'] = intval($daddyobb->input['joingroup']);
		$query = $db->simple_select("usergroups", "*", "gid='".intval($daddyobb->input['joingroup'])."'");
		$usergroup = $db->fetch_array($query);

		if(($usergroup['type'] != 4 && $usergroup['type'] != 3) || !$usergroup['gid'])
		{
			error($lang->cannot_join_group);
		}

		if(my_strpos($ingroups, ",".intval($daddyobb->input['joingroup']).",") !== false)
		{
			error($lang->already_member_of_group);
		}

		$query = $db->simple_select("joinrequests", "*", "uid='".$daddyobb->user['uid']."' AND gid='".intval($daddyobb->input['joingroup'])."'");
		$joinrequest = $db->fetch_array($query);
		if($joinrequest['rid'])
		{
			error($lang->already_sent_join_request);
		}
		if($daddyobb->input['do'] == "joingroup" && $usergroup['type'] == 4)
		{
			$reason = $db->escape_string($reason);
			$now = TIME_NOW;
			$joinrequest = array(
				"uid" => $daddyobb->user['uid'],
				"gid" => intval($daddyobb->input['joingroup']),
				"reason" => $db->escape_string($daddyobb->input['reason']),
				"dateline" => TIME_NOW
			);

			$db->insert_query("joinrequests", $joinrequest);
			$plugins->run_hooks("usercp_usergroups_join_group_request");
			redirect("usercp.php?action=usergroups", $lang->group_join_requestsent);
			exit;
		}
		elseif($usergroup['type'] == 4)
		{
			$joingroup = $daddyobb->input['joingroup'];
			eval("\$joinpage = \"".$templates->get("usercp_usergroups_joingroup")."\";");
			output_page($joinpage);
			exit();
		}
		else
		{
			join_usergroup($daddyobb->user['uid'], $daddyobb->input['joingroup']);
			$plugins->run_hooks("usercp_usergroups_join_group");
			redirect("usercp.php?action=usergroups", $lang->joined_group);
		}
	}
	// Show listing of various group related things

	// List of usergroup leaders
	$query = $db->query("
		SELECT g.*, u.username, u.displaygroup, u.usergroup
		FROM ".TABLE_PREFIX."groupleaders g
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=g.uid)
		ORDER BY u.username ASC
	");
	while($leader = $db->fetch_array($query))
	{
		$groupleaders[$leader['gid']][$leader['uid']] = $leader;
	}

	// List of groups this user is a leader of
	$groupsledlist = '';


	switch($db->type)
	{
		case "pgsql":
		case "sqlite3":
		case "sqlite2":
			$query = $db->query("
				SELECT g.title, g.gid, g.type, COUNT(u.uid) AS users, COUNT(j.rid) AS joinrequests, l.canmanagerequests, l.canmanagemembers
				FROM ".TABLE_PREFIX."groupleaders l
				LEFT JOIN ".TABLE_PREFIX."usergroups g ON(g.gid=l.gid)
				LEFT JOIN ".TABLE_PREFIX."users u ON(((','|| u.additionalgroups|| ',' LIKE '%,'|| g.gid|| ',%') OR u.usergroup = g.gid))
				LEFT JOIN ".TABLE_PREFIX."joinrequests j ON(j.gid=g.gid)
				WHERE l.uid='".$daddyobb->user['uid']."'
				GROUP BY g.gid, g.title, g.type, l.canmanagerequests, l.canmanagemembers
			");
			break;
		default:
			$query = $db->query("
				SELECT g.title, g.gid, g.type, COUNT(DISTINCT u.uid) AS users, COUNT(DISTINCT j.rid) AS joinrequests, l.canmanagerequests, l.canmanagemembers
				FROM ".TABLE_PREFIX."groupleaders l
				LEFT JOIN ".TABLE_PREFIX."usergroups g ON(g.gid=l.gid)
				LEFT JOIN ".TABLE_PREFIX."users u ON(((CONCAT(',', u.additionalgroups, ',') LIKE CONCAT('%,', g.gid, ',%')) OR u.usergroup = g.gid))
				LEFT JOIN ".TABLE_PREFIX."joinrequests j ON(j.gid=g.gid)
				WHERE l.uid='".$daddyobb->user['uid']."'
				GROUP BY l.gid
			");
	}

	while($usergroup = $db->fetch_array($query))
	{
		$memberlistlink = $moderaterequestslink = '';
		$memberlistlink = " [<a href=\"managegroup.php?gid=".$usergroup['gid']."\">".$lang->view_members."</a>]";
		if($usergroup['type'] != 4)
		{
			$usergroup['joinrequests'] = '--';
		}
		if($usergroup['joinrequests'] > 0 && $usergroup['canmanagerequests'] == 1)
		{
			$moderaterequestslink = " [<a href=\"managegroup.php?action=joinrequests&amp;gid={$usergroup['gid']}\">{$lang->view_requests}</a>]";
		}
		$groupleader[$usergroup['gid']] = 1;
		$trow = alt_trow();
		eval("\$groupsledlist .= \"".$templates->get("usercp_usergroups_leader_usergroup")."\";");
	}
	if($groupsledlist)
	{
		$leadinggroups = 1;
	}

	// Fetch the list of groups the member is in
	// Do the primary group first
	$query = $db->simple_select("usergroups", "*", "gid='".$daddyobb->user['usergroup']."'");
	$usergroup = $db->fetch_array($query);
	$leavelink = "<div style=\"text-align:center;\"><span class=\"smalltext\">{$lang->usergroup_leave_primary}</span></div>";
	$trow = alt_trow();
	if($usergroup['candisplaygroup'] == 1 && $usergroup['gid'] == $daddyobb->user['displaygroup'])
	{
		$displaycode = " ({$lang->display_group})";
	}
	elseif($usergroup['candisplaygroup'] == 1)
	{
		$displaycode = " (<a href=\"usercp.php?action=usergroups&amp;displaygroup={$usergroup['gid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->set_as_display_group}</a>)";
	}
	else
	{
		$displaycode = '';
	}

	eval("\$memberoflist = \"".$templates->get("usercp_usergroups_memberof_usergroup")."\";");
	$showmemberof = false;
	if($daddyobb->user['additionalgroups'])
	{
		$query = $db->simple_select("usergroups", "*", "gid IN (".$daddyobb->user['additionalgroups'].") AND gid !='".$daddyobb->user['usergroup']."'", array('order_by' => 'title'));
		while($usergroup = $db->fetch_array($query))
		{
			$showmemberof = true;

			if($groupleader[$usergroup['gid']])
			{
				$leavelink = "<div style=\"text-align: center;\"><span class=\"smalltext\">{$lang->usergroup_leave_leader}</span></div>";
			}
			elseif($usergroup['type'] != 4 && $usergroup['type'] != 3)
			{
				$leavelink = "<div style=\"text-align: center;\"><span class=\"smalltext\">{$lang->usergroup_cannot_leave}</span></div>";
			}
			else
			{
				$leavelink = "<div style=\"text-align: center;\"><a href=\"usercp.php?action=usergroups&leavegroup=".$usergroup['gid']."&amp;my_post_key={$daddyobb->post_code}\">".$lang->usergroup_leave."</a></div>";
			}
			if($usergroup['description'])
			{
				$description = "<div><span class=\"smalltext\">".$usergroup['description']."</span></div>";
			}
			else
			{
				$description = '';
			}
			if(!$usergroup['usertitle'])
			{
				// fetch title here
			}
			$trow = alt_trow();
			if($usergroup['candisplaygroup'] == 1 && $usergroup['gid'] == $daddyobb->user['displaygroup'])
			{
				$displaycode = " ({$lang->display_group})";
			}
			elseif($usergroup['candisplaygroup'] == 1)
			{
				$displaycode = "(<a href=\"usercp.php?action=usergroups&amp;displaygroup={$usergroup['gid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->set_as_display_group}</a>)";
			}
			else
			{
				$displaycode = '';
			}
			eval("\$memberoflist .= \"".$templates->get("usercp_usergroups_memberof_usergroup")."\";");
		}
	}
	$membergroups = 1;

	// List of groups this user has applied for but has not been accepted in to
	$query = $db->simple_select("joinrequests", "*", "uid='".$daddyobb->user['uid']."'");
	while($request = $db->fetch_array($query))
	{
		$appliedjoin[$request['gid']] = $request['dateline'];
	}

	// Fetch list of groups the member can join
	$existinggroups = $daddyobb->user['usergroup'];
	if($daddyobb->user['additionalgroups'])
	{
		$existinggroups .= ",".$daddyobb->user['additionalgroups'];
	}
	$joinablegroups = '';
	$query = $db->simple_select("usergroups", "*", "(type='3' OR type='4') AND gid NOT IN ($existinggroups)", array('order_by' => 'title'));
	while($usergroup = $db->fetch_array($query))
	{
		$trow = alt_trow();
		if($usergroup['description'])
		{
			$description = "<div><span class=\"smallfont\">".$usergroup['description']."</span></div>";
		}
		else
		{
			$description = '';
		}
		if($usergroup['type'] == 4) // Moderating join requests
		{
			$conditions = $lang->usergroup_joins_moderated;
		}
		else
		{
			$conditions = $lang->usergroup_joins_anyone;
		}
		if($appliedjoin[$usergroup['gid']])
		{
			$applydate = my_date($daddyobb->settings['dateformat'], $appliedjoin[$usergroup['gid']]);
			$applytime = my_date($daddyobb->settings['timeformat'], $appliedjoin[$usergroup['gid']]);
			$joinlink = $lang->sprintf($lang->join_group_applied, $applydate, $applytime);
		}
		else
		{
			$joinlink = "<a href=\"usercp.php?action=usergroups&amp;joingroup={$usergroup['gid']}&amp;my_post_key={$daddyobb->post_code}\">{$lang->join_group}</a>";
		}
		$usergroupleaders = '';
		if($groupleaders[$usergroup['gid']])
		{
			$comma = '';
			$usergroupleaders = '';
			foreach($groupleaders[$usergroup['gid']] as $leader)
			{
				$leader['username'] = format_name($leader['username'], $leader['usergroup'], $leader['displaygroup']);
				$usergroupleaders .= $comma.build_profile_link($leader['username'], $leader['uid']);
				$comma = ", ";
			}
			$usergroupleaders = $lang->usergroup_leaders." ".$usergroupleaders;
		}
		eval("\$joinablegrouplist .= \"".$templates->get("usercp_usergroups_joinable_usergroup")."\";");
	}
	if($joinablegrouplist)
	{
		$joinablegroups = 1;
	}

	eval("\$groupmemberships = \"".$templates->get("usercp_usergroups")."\";");
	$plugins->run_hooks("usercp_usergroups_end");
	output_page($groupmemberships);
}
if($daddyobb->input['action'] == "attachments")
{
	$plugins->run_hooks("usercp_attachments_start");
	require_once DADDYOBB_ROOT."inc/functions_upload.php";

	$attachments = '';

	$query = $db->simple_select("attachments", "SUM(filesize) AS ausage, COUNT(aid) AS acount", "uid='".$daddyobb->user['uid']."'");
	$usage = $db->fetch_array($query);
	$totalusage = $usage['ausage'];
	$totalattachments = $usage['acount'];
	$friendlyusage = get_friendly_size($totalusage);
	if($daddyobb->usergroup['attachquota'])
	{
    $friendly_left = get_friendly_size(($daddyobb->usergroup['attachquota']*1024)-$totalusage);
	  $spaceused = round(($totalusage/($daddyobb->usergroup['attachquota']*1024))*100);
	  $spaceleft = 100 - $spaceused;
	  $lang->attach_space_used = $lang->sprintf($lang->attach_space_used, $totalattachments, $friendlyusage);
	  $lang->attach_free_space = $lang->sprintf($lang->attach_free_space, $friendly_left);
	  $lang->attachment_usage_note = $lang->sprintf($lang->attachment_usage_note, $friendlyusage, $totalattachments, $friendly_left);
	}
	else
	{
    $lang->attachment_usage_note = $lang->sprintf($lang->attachment_usage_note_2, $friendlyusage, $totalattachments);
	}

	// Pagination
	if(!$daddyobb->settings['threadsperpage'])
	{
		$daddyobb->settings['threadsperpage'] = 20;
	}
	$perpage = $daddyobb->settings['threadsperpage'];
	$page = intval($daddyobb->input['page']);

	if(intval($daddyobb->input['page']) > 0)
	{
		$start = ($page-1) *$perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	$end = $start + $perpage;
	$lower = $start+1;

	if($end > $totalattachments)
	{
		$upper = $totalattachments;
	}
	$multipage = multipage($totalattachments, $perpage, $page, "usercp.php?action=attachments");

	$query = $db->query("
		SELECT a.*, p.subject, p.dateline, t.tid, t.subject AS threadsubject
		FROM ".TABLE_PREFIX."attachments a
		LEFT JOIN ".TABLE_PREFIX."posts p ON (a.pid=p.pid)
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE a.uid='".$daddyobb->user['uid']."' AND a.pid!='0'
		ORDER BY p.dateline DESC LIMIT {$start}, {$perpage}
	");
	$bandwidth = $totaldownloads = 0;
	while($attachment = $db->fetch_array($query))
	{
		if($attachment['dateline'] && $attachment['tid'])
		{
			$attachment['subject'] = htmlspecialchars_uni($parser->parse_badwords($attachment['subject']));
			$attachment['postlink'] = get_post_link($attachment['pid'], $attachment['tid']);
			$attachment['threadlink'] = get_thread_link($attachment['tid']);
			$attachment['threadsubject'] = htmlspecialchars_uni($parser->parse_badwords($attachment['threadsubject']));
			$size = get_friendly_size($attachment['filesize']);
			$icon = get_attachment_icon(get_extension($attachment['filename']));
			$sizedownloads = $lang->sprintf($lang->attachment_size_downloads, $size, $attachment['downloads']);
			$attachdate = my_date($daddyobb->settings['dateformat'], $attachment['dateline']);
			$attachtime = my_date($daddyobb->settings['timeformat'], $attachment['dateline']);
			eval("\$attachments .= \"".$templates->get("usercp_attachments_attachment")."\";");
			// Add to bandwidth total
			$bandwidth += ($attachment['filesize'] * $attachment['downloads']);
			$totaldownloads += $attachment['downloads'];
		}
		else
		{
			// This little thing delets attachments without a thread/post
			remove_attachment($attachment['pid'], $attachment['posthash'], $attachment['aid']);
		}
	}
	$bandwidth = get_friendly_size($bandwidth);

	//Parse username...
	$lang->attachment_posted_by_x = $lang->sprintf($lang->attachment_posted_by_x, $daddyobb->user['username']);
	
	eval("\$manageattachments = \"".$templates->get("usercp_attachments")."\";");
	$plugins->run_hooks("usercp_attachments_end");
	output_page($manageattachments);
}

if($daddyobb->input['action'] == "do_attachments" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("usercp_do_attachments_start");
	require_once DADDYOBB_ROOT."inc/functions_upload.php";
	if(!is_array($daddyobb->input['attachments']))
	{
		error($lang->no_attachments_selected);
	}
	$aids = implode(',', array_map('intval', $daddyobb->input['attachments']));
	$query = $db->simple_select("attachments", "*", "aid IN ($aids) AND uid='".$daddyobb->user['uid']."'");
	while($attachment = $db->fetch_array($query))
	{
		remove_attachment($attachment['pid'], '', $attachment['aid']);
	}
	$plugins->run_hooks("usercp_do_attachments_end");
	redirect("usercp.php?action=attachments", $lang->attachments_deleted);
}
### Visitor Message Options ###
if($daddyobb->input['action'] == "viewvms")
{
  
  if($daddyobb->input['type'] == "moderated")
  {
    add_breadcrumb($lang->unapproved_visitor_messages);
    
    if(!$daddyobb->input['uid'])
    {
      $daddyobb->input['uid'] = $daddyobb->user['uid'];
    }
    
    if($daddyobb->input['uid'] != $daddyobb->user['uid'] && $daddyobb->usergroup['modcanmanagevms'] !=1)
    {
      $daddyobb->input['uid'] = $daddyobb->user['uid'];
    }
    
    if(user_exists($daddyobb->input['uid']) == false)
    {
      error($lang->error_nomember);
    }
    
    //Fetch user details
    $input = get_user($daddyobb->input['uid']);
  
    if($daddyobb->settings['defaultvmspp']<=$daddyobb->settings['maxvmspp'] && $daddyobb->settings['defaultvmspp'] > 0 && $daddyobb->settings['maxvmspp']>0)
    {
      $perpage = $daddyobb->settings['defaultvmspp'];
    }
    else
    {
      $perpage = 10;
    }

    if($daddyobb->input['show'] == "all" && $daddyobb->usergroup['modcanmanagevms']==1)
    {
      $query = $db->simple_select("visitormessage", "COUNT(vmid) as count", "status='0' AND touid NOT LIKE '' AND fromuid NOT LIKE ''");
    }
    else
    {
      $query = $db->simple_select("visitormessage", "COUNT(vmid) as count", "status='0' AND touid='".intval($daddyobb->input['uid'])."'");
    }
    $vmcount = $db->fetch_field($query, "count");
    $vmcount = my_number_format($vmcount);

    // Figure out if we need to display multiple pages.
    if($daddyobb->input['page'] != "last")
    {
      $page = intval($daddyobb->input['page']);
    }

    $vmcount = intval($vmcount);
    $pages = $vmcount / $perpage;
    $pages = ceil($pages);

    if($daddyobb->input['page'] == "last")
    {
      $page = $pages;
    }

    if($page > $pages || $page <= 0)
    {
      $page = 1;
    }

    if($page)
    {
      $start = ($page-1) * $perpage;
    }
    else
    {
      $start = 0;
      $page = 1;
    }

    if($daddyobb->input['show'] == "all" && $daddyobb->usergroup['modcanmanagevms']==1)
    {
      $multipage = multipage($vmcount, $perpage, $page, "usercp.php?action=viewvms&amp;type=moderated&amp;show=all&amp;perpage=$perpage&amp;uid={$daddyobb->input['uid']}");
      $query = $db->query("
      SELECT *
      FROM ".TABLE_PREFIX."visitormessage
      WHERE status = '0'
      AND touid NOT LIKE ''
      AND fromuid NOT LIKE ''
      ORDER BY dateline DESC
      LIMIT {$start}, {$perpage}
      ");
    }
    else
    {
      $multipage = multipage($vmcount, $perpage, $page, "usercp.php?action=viewvms&amp;type=moderated&amp;perpage=$perpage&amp;uid={$daddyobb->input['uid']}");
      $query = $db->query("
      SELECT *
      FROM ".TABLE_PREFIX."visitormessage
      WHERE status = '0'
      AND touid = ".intval($daddyobb->input['uid'])."
      AND fromuid NOT LIKE ''
      ORDER BY dateline DESC
      LIMIT {$start}, {$perpage}
      ");
    }
    $visitormessages = "";
    while($moderated_vms = $db->fetch_array($query))
    {    
      //Format the username of both: Sender and Recipient
      $recipient = get_user($moderated_vms['touid']);
      $sender = get_user($moderated_vms['fromuid']);
      $recipientname = format_name($recipient['username'], $recipient['usergroup'], $recipient['displaygroup']);
      $sendername = format_name($sender['username'], $sender['usergroup'], $sender['displaygroup']);
      
      //Now that we have that formatted, let us get the profilelinks
      $recipientlink = get_profile_link($recipient['uid']);
      $senderlink = get_profile_link($sender['uid']);
      
      //Get Date and Time + Parsing
      $vmessagedate = my_date($daddyobb->settings['dateformat'], $moderated_vms['dateline']);
      $vmessagetime = my_date($daddyobb->settings['timeformat'], $moderated_vms['dateline']);
      $vmessage_parser = array(
       "allow_html" => $daddyobb->settings['vmallowhtml'],
       "filter_badwords" => 1,
       "allow_mycode" => $daddyobb->settings['vmallowmycode'],
       "allow_smilies" => $daddyobb->settings['vmallowmilies'],
       "allow_imgcode" => $daddyobb->settings['vmallowimg'],
      );
      $moderated_vms['message'] = $parser->parse_message($moderated_vms['message'], $vmessage_parser);
      $moderated_vms['message'] = $parser->text_parse_message($moderated_vms['message']);
       
      eval("\$visitormessages .= \"".$templates->get("usercp_visitormessages_moderated_visitormessage")."\";");
    }
    if(!$visitormessages)
    {
      $visitormessages = "";
    }
    eval("\$unappvisitormessages = \"".$templates->get("usercp_visitormessages_moderated")."\";");
    output_page($unappvisitormessages);
  }
  
  //Reported
  if($daddyobb->input['type'] == "reported" && $daddyobb->usergroup['modcanmanagevms']==1)
  {
    add_breadcrumb($lang->reported_visitor_messages);    

    if($daddyobb->settings['defaultvmspp']<=$daddyobb->settings['maxvmspp'] && $daddyobb->settings['defaultvmspp'] > 0 && $daddyobb->settings['maxvmspp']>0)
    {
      $perpage = $daddyobb->settings['defaultvmspp'];
    }
    else
    {
      $perpage = 10;
    }

    $query = $db->simple_select("visitormessage", "COUNT(vmid) as count", "status='2'");
    $vmcount = $db->fetch_field($query, "count");
    $vmcount = my_number_format($vmcount);

    // Figure out if we need to display multiple pages.
    if($daddyobb->input['page'] != "last")
    {
      $page = intval($daddyobb->input['page']);
    }

    $vmcount = intval($vmcount);
    $pages = $vmcount / $perpage;
    $pages = ceil($pages);

    if($daddyobb->input['page'] == "last")
    {
      $page = $pages;
    }

    if($page > $pages || $page <= 0)
    {
      $page = 1;
    }

    if($page)
    {
      $start = ($page-1) * $perpage;
    }
    else
    {
      $start = 0;
      $page = 1;
    }

    $multipage = multipage($vmcount, $perpage, $page, "usercp.php?action=viewvms&amp;type=reported&amp;perpage=$perpage");
    $query = $db->query("
    SELECT *
    FROM ".TABLE_PREFIX."visitormessage
    WHERE status = '2'
    AND touid NOT LIKE ''
    AND fromuid NOT LIKE ''
    ORDER BY dateline DESC
    LIMIT {$start}, {$perpage}
    ");
    
    $visitormessages = "";
    while($moderated_vms = $db->fetch_array($query))
    {    
      //Format the username of both: Sender and Recipient
      $recipient = get_user($moderated_vms['touid']);
      $sender = get_user($moderated_vms['fromuid']);
      $recipientname = format_name($recipient['username'], $recipient['usergroup'], $recipient['displaygroup']);
      $sendername = format_name($sender['username'], $sender['usergroup'], $sender['displaygroup']);
      
      //Now that we have that formatted, let us get the profilelinks
      $recipientlink = get_profile_link($recipient['uid']);
      $senderlink = get_profile_link($sender['uid']);
      
      //Get Date and Time + Parsing
      $vmessagedate = my_date($daddyobb->settings['dateformat'], $moderated_vms['dateline']);
      $vmessagetime = my_date($daddyobb->settings['timeformat'], $moderated_vms['dateline']);
      $vmessage_parser = array(
       "allow_html" => $daddyobb->settings['vmallowhtml'],
       "filter_badwords" => 1,
       "allow_mycode" => $daddyobb->settings['vmallowmycode'],
       "allow_smilies" => $daddyobb->settings['vmallowmilies'],
       "allow_imgcode" => $daddyobb->settings['vmallowimg'],
      );
      $moderated_vms['message'] = $parser->parse_message($moderated_vms['message'], $vmessage_parser);
      $moderated_vms['message'] = $parser->text_parse_message($moderated_vms['message']);
       
      eval("\$visitormessages .= \"".$templates->get("usercp_visitormessages_moderated_visitormessage")."\";");
    }
    if(!$visitormessages)
    {
      $visitormessages = "";
    }
    eval("\$reportedvisitormessages = \"".$templates->get("usercp_visitormessages_reported")."\";");
    output_page($reportedvisitormessages);
  }
}
### END: Visitor Message Options  ###
//Get the subscriptions
if(!$daddyobb->input['action'])
{
	$plugins->run_hooks("usercp_cphome_subscriptions_start");
	
	$timeout	= TIME_NOW - 86400;
	// Fetch subscriptions
	$query = $db->query("
		SELECT s.*, t.*, t.username AS threadusername, u.username
		FROM ".TABLE_PREFIX."threadsubscriptions s
		LEFT JOIN ".TABLE_PREFIX."threads t ON (s.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = t.uid)
		WHERE s.uid='".$daddyobb->user['uid']."' AND t.lastpost > $timeout
	");
	$threadcount = $db->num_rows($query);
	$threadcount = my_number_format($threadcount);
	
	if($threadcount > 0)
	{
			eval("\$threadheader =\"".$templates->get("usercp_cphome_subscriptions_threadheader")."\";");
	}

	$perpage = 10;
	$page = intval($daddyobb->input['page']);
	if($page > 0)
	{
		$start = ($page-1) *$perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	$end = $start + $perpage;
	$lower = $start+1;
	$upper = $end;
	if($upper > $threadcount)
	{
		$upper = $threadcount;
	}
	$multipage = multipage($threadcount, $perpage, $page, "usercp.php");
	$fpermissions = forum_permissions();

		// Fetch subscriptions
		$query = $db->query("
		SELECT s.*, t.*, t.username AS threadusername, u.username
		FROM ".TABLE_PREFIX."threadsubscriptions s
		LEFT JOIN ".TABLE_PREFIX."threads t ON (s.tid=t.tid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = t.uid)
		WHERE s.uid='".$daddyobb->user['uid']."' AND t.lastpost > $timeout
		ORDER BY t.lastpost DESC
		LIMIT $start, $perpage
	");
	while($subscription = $db->fetch_array($query))
	{
		$forumpermissions = $fpermissions[$subscription['fid']];
		// Only keep if we're allowed to view them
		if($forumpermissions['canview'] != 0 || $forumpermissions['canviewthreads'] != 0)
		{
			$subscriptions[$subscription['tid']] = $subscription;
		}
		// Hmm, you don't have permission to view - unsubscribe!
		else if($subscription['tid'])
		{
			$del_subscriptions[] = $subscription['tid'];
		}
	}

	if(is_array($del_subscriptions))
	{
		$tids = implode(',', $del_subscriptions);
		if($tids)
		{
			$db->delete_query("threadsubscriptions", "tid IN ({$tids}) AND uid='{$daddyobb->user['uid']}'");
		}
	}

	if(is_array($subscriptions))
	{
		$tids = implode(",", array_keys($subscriptions));

		// Check participation by the current user in any of these threads - for 'dot' folder icons
		if($daddyobb->settings['dotfolders'] != 0)
		{
			$query = $db->simple_select("posts", "tid,uid", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})");
			while($post = $db->fetch_array($query))
			{
				$subscriptions[$post['tid']]['doticon'] = 1;
			}
		}

		// Read threads
		if($daddyobb->settings['threadreadcut'] > 0)
		{
			$query = $db->simple_select("threadsread", "*", "uid='{$daddyobb->user['uid']}' AND tid IN ({$tids})");
			while($readthread = $db->fetch_array($query))
			{
				$subscriptions[$readthread['tid']]['lastread'] = $readthread['dateline'];
			}
		}


		// Now we can build our subscription list
		foreach($subscriptions as $thread)
		{
			$bgcolor = alt_trow();

			$folder = '';
			$prefix = '';
			
			// Sanitize
			$thread['subject'] = $parser->parse_badwords($thread['subject']);
			$thread['subject'] = htmlspecialchars_uni($thread['subject']);

			// Build our links
			$thread['threadlink'] = get_thread_link($thread['tid']);
			$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");

			// Fetch the thread icon if we have one
			if($thread['icon'] > 0 && $icon_cache[$thread['icon']])
			{
				$icon = $icon_cache[$thread['icon']];
				$icon = "<img src=\"{$icon['path']}\" alt=\"{$icon['name']}\" />";
			}
			else
			{
				$icon = "";
			}

			// Determine the folder
			$folder = '';
			$folder_label = '';

			if($thread['doticon'])
			{
				$folder = "dot_";
				$folder_label .= $lang->icon_dot;
			}

			$gotounread = '';
			$isnew = 0;
			$donenew = 0;
			$lastread = 0;

			$forumread = my_get_array_cookie("forumread", $thread['fid']);
			if($daddyobb->user['lastvisit'] > $forumread)
			{
				$forumread = $daddyobb->user['lastvisit'];
			}

			if($daddyobb->settings['threadreadcut'] > 0 && $thread['lastpost'] > $forumread)
			{
				$cutoff = TIME_NOW-$daddyobb->settings['threadreadcut']*60*60*24;
			}

			if($thread['lastpost'] > $cutoff)
			{
				if($thread['lastpost'] > $cutoff)
				{
					if($thread['lastread'])
					{
							$lastread = $thread['lastread'];
					}
					else
					{
							$lastread = 1;
					}
				}
			}

			if(!$lastread)
			{
				$readcookie = $threadread = my_get_array_cookie("threadread", $thread['tid']);
				if($readcookie > $forumread)
				{
					$lastread = $readcookie;
				}
				else
				{
					$lastread = $forumread;
				}
			}

			if($thread['lastpost'] > $lastread && $lastread)
			{
				$folder .= "new";
				$folder_label .= $lang->icon_new;
				$new_class = "subject_new";
				$thread['newpostlink'] = get_thread_link($thread['tid'], 0, "newpost");
				eval("\$gotounread = \"".$templates->get("forumdisplay_thread_gotounread")."\";");
				$unreadpost = 1;
			}
			else
			{
				$folder_label .= $lang->icon_no_new;
				$new_class = "";
			}

			if($thread['replies'] >= $daddyobb->settings['hottopic'] || $thread['views'] >= $daddyobb->settings['hottopicviews'])
			{
				$folder .= "hot";
				$folder_label .= $lang->icon_hot;
			}

			if($thread['closed'] == 1)
			{
				$folder .= "lock";
				$folder_label .= $lang->icon_lock;
			}

			$folder .= "folder";

			// Build last post info

			$lastpostdate = my_date($daddyobb->settings['dateformat'], $thread['lastpost']);
			$lastposttime = my_date($daddyobb->settings['timeformat'], $thread['lastpost']);
			$lastposter = $thread['lastposter'];
			$lastposteruid = $thread['lastposteruid'];

			// Don't link to guest's profiles (they have no profile).
			if($lastposteruid == 0)
			{
				$lastposterlink = $lastposter;
			}
			else
			{
				$lastposterlink = build_profile_link($lastposter, $lastposteruid);
			}

			$thread['replies'] = my_number_format($thread['replies']);
			$thread['views'] = my_number_format($thread['views']);

			// What kind of notification type do we have here?
			switch($thread['notification'])
			{
				case "1": // Instant
					$notification_type = $lang->instant_notification;
					break;
				default: // No notification
					$notification_type = $lang->no_notification;
			}
			eval("\$threads .= \"".$templates->get("usercp_cphome_subscriptions_thread")."\";");
		}
	}
	else
	{
		$threads = 0;
	}
	eval("\$cp_subscriptions = \"".$templates->get("usercp_cphome_subscription")."\";");
	$plugins->run_hooks("usercp_cphome_subscriptions_end");
	output_page($cp_subscriptions);
}

if($daddyobb->input['action'] == "do_profilepic" && $daddyobb->request_method == "post") 
{ 
	if($daddyobb->input['remove'] == 1)
	{ 
		$update_pp = array( 
			"profilepic" => "", 
			"profilepicdimensions" => ""
		); 
		$db->update_query("users", $update_pp, "uid='".intval($daddyobb->user['uid'])."'"); 
		remove_prof_pic($daddyobb->user['uid']); 
	} 
	elseif($_FILES['ppupload']['name']) 
	{ 
		if($daddyobb->usergroup['canuploadprofilepics'] == 0) 
		{ 
			error_no_permission(); 
		} 
		$pp = upload_prof_pic(); 
		if($pp['error']) 
		{ 
			$profilepic_error = $pp['error'];
		}
		else
		{
			if($pp['width'] > 0 && $pp['height'] > 0) 
			{
				$pp_dimensions = $pp['width']."|".$pp['height']; 
			} 
			$update_pp = array( 
				"profilepic" => $pp['profilepic'], 
				"profilepicdimensions" => $pp_dimensions
			); 
			$db->update_query("users", $update_pp, "uid='".intval($daddyobb->user['uid'])."'");
		}
	} 
	else
	{ 
		$daddyobb->input['ppurl'] = preg_replace("#script:#i", "", $daddyobb->input['ppurl']); 
		$daddyobb->input['ppurl'] = htmlspecialchars($daddyobb->input['ppurl']); 
		$ext = get_extension($daddyobb->input['ppurl']); 
		list($width, $height, $type) = @getimagesize($daddyobb->input['ppurl']); 

		if(!$type) 
		{ 
			$profilepic_error = $lang->error_invalidurl;
		}

		if(empty($profilepic_error))
		{
			if($width && $height && $daddyobb->usergroup['profilepicmaxdimensions'] != "") 
			{ 
				list($maxwidth, $maxheight) = explode("x", $daddyobb->usergroup['profilepicmaxdimensions']); 
				if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight)) 
				{ 
					$lang->error_toobig = $lang->sprintf($lang->error_toobig, $maxwidth, $maxheight); 
					$profilepic_error = $lang->error_toobig;
				} 
			}
		}

		if(empty($profilepic_error))
		{
			if($width > 0 && $height > 0) 
			{ 
				$pp_dimensions = intval($width)."|".intval($height); 
			} 
			$update_pp = array( 
				"profilepic" => $db->escape_string($daddyobb->input['ppurl']), 
				"profilepicdimensions" => $pp_dimensions, 
			); 
			$db->update_query("users", $update_pp, "uid='".intval($daddyobb->user['uid'])."'");
			remove_prof_pic($daddyobb->user['uid']);
		}
	}

	if(empty($profilepic_error))
	{
    $lang->redirect_profileupdated = $lang->sprintf($lang->redirect_profileupdated, $daddyobb->user['username']);
    redirect("usercp.php", $lang->redirect_profileupdated);
	}
	else
	{
		$daddyobb->input['action'] = "profilepic";
		$profilepic_error = inline_error($profilepic_error);
	}
} 

if($daddyobb->input['action'] == "profilepic")
{
	if($daddyobb->user['profilepic'])
	{
		$pp_dimensions = explode("|", $daddyobb->user['profilepicdimensions']);
		if($pp_dimensions[0] && $pp_dimensions[1])
		{
			$profilepic_width_height = "width=\"{$pp_dimensions[0]}\" height=\"{$pp_dimensions[1]}\"";
		}
		$currentpp = 1;
	}
	if($daddyobb->usergroup['profilepicmaxdimensions'] != "")
	{
		list($maxwidth, $maxheight) = explode("x", $daddyobb->usergroup['profilepicmaxdimensions']);
	}
	if($daddyobb->usergroup['profilepicmaxsize'])
	{
		$maxsize = get_friendly_size($daddyobb->usergroup['profilepicmaxsize']*1024);
	}
	
	$lang->profilepic_notice = $lang->sprintf($lang->profilepic_notice, $maxwidth, $maxheight, $maxsize);
	
	eval("\$pp = \"".$templates->get("usercp_profilepic")."\";");
	output_page($pp);
}
?>