<?php
/**
 * DaddyoBB 1.0 Beta
 * Copyright © 2009 DaddyoBB Group, All Rights Reserved
 *
 * Website: http://www.daddyobb.com
 * License: http://www.daddyobb.com/license
 *
 * 19:21 23.12.2008
 */

define("IN_DADDYOBB", 1);
define('THIS_SCRIPT', 'member.php');

$nosession['avatar'] = 1;
$templatelist = "member_register,error_nousername,error_nopassword,error_passwordmismatch,error_invalidemail,error_usernametaken,error_emailmismatch,error_noemail,redirect_registered";
$templatelist .= ",redirect_loggedout,login,redirect_loggedin,error_invalidusername,error_invalidpassword,member_profile_email,member_profile_offline,member_profile_reputation,member_profile_warn,member_profile_warninglevel,member_profile_customfields_field,member_profile_customfields,member_profile_adminoptions,member_profile,member_login,member_profile_online,member_profile_modoptions,member_profile_signature,member_profile_groupimage";
require_once "./global.php";

require_once DADDYOBB_ROOT."inc/functions_post.php";
require_once DADDYOBB_ROOT."inc/functions_user.php";
require_once DADDYOBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("userbase");

// Make navigation
switch($daddyobb->input['action'])
{
	case "register":
	case "do_register":
		add_breadcrumb($lang->nav_register);
		break;
	case "activate":
		add_breadcrumb($lang->nav_activate);
		break;
	case "resendactivation":
		add_breadcrumb($lang->nav_resendactivation);
		break;
	case "lostpw":
		add_breadcrumb($lang->nav_lostpw);
		break;
	case "resetpassword":
		add_breadcrumb($lang->nav_resetpassword);
		break;
	case "login":
		add_breadcrumb($lang->nav_login);
		break;
	case "emailuser":
		add_breadcrumb($lang->nav_emailuser);
		break;
}

if(($daddyobb->input['action'] == "register" || $daddyobb->input['action'] == "do_register") && $daddyobb->usergroup['cancp'] != 1)
{
	if($daddyobb->settings['disableregs'] == 1)
	{
		error($lang->registrations_disabled);
	}
	if($daddyobb->user['regdate'])
	{
		error($lang->error_alreadyregistered);
	}
	if($daddyobb->settings['betweenregstime'] && $daddyobb->settings['maxregsbetweentime'])
	{
		$time = TIME_NOW;
		$datecut = $time-(60*60*$daddyobb->settings['betweenregstime']);
		$query = $db->simple_select("users", "*", "regip='".$db->escape_string($session->ipaddress)."' AND regdate > '$datecut'");
		$regcount = $db->num_rows($query);
		if($regcount >= $daddyobb->settings['maxregsbetweentime'])
		{
			$lang->error_alreadyregisteredtime = $lang->sprintf($lang->error_alreadyregisteredtime, $regcount, $daddyobb->settings['betweenregstime']);
			error($lang->error_alreadyregisteredtime);
		}
	}
}

if($daddyobb->input['action'] == "do_register" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("member_do_register_start");

	if($daddyobb->settings['regtype'] == "randompass")
	{
		$daddyobb->input['password'] = random_str();
		$daddyobb->input['password2'] = $daddyobb->input['password'];
	}

	if($daddyobb->settings['regtype'] == "verify" || $daddyobb->settings['regtype'] == "admin" || $daddyobb->input['coppa'] == 1)
	{
		$usergroup = 5;
	}
	else
	{
		$usergroup = 2;
	}

	// Set up user handler.
	require_once DADDYOBB_ROOT."inc/datahandlers/user.php";
	$userhandler = new UserDataHandler("insert");

	// Set the data for the new user.
	$user = array(
		"username" => $daddyobb->input['username'],
		"password" => $daddyobb->input['password'],
		"password2" => $daddyobb->input['password2'],
		"email" => $daddyobb->input['email'],
		"email2" => $daddyobb->input['email2'],
		"usergroup" => $usergroup,
		"referrer" => $daddyobb->input['referrername'],
		"timezone" => $daddyobb->input['timezoneoffset'],
		"language" => $daddyobb->input['language'],
		"profile_fields" => $daddyobb->input['profile_fields'],
		"regip" => $session->ipaddress,
		"longregip" => ip2long($session->ipaddress),
		"coppa_user" => intval($daddyobb->cookies['coppauser']),
	);
	
	if(isset($daddyobb->input['regcheck1']) && isset($daddyobb->input['regcheck2']))
	{
		$user['regcheck1'] = $daddyobb->input['regcheck1'];
		$user['regcheck2'] = $daddyobb->input['regcheck2'];
	}

	// Do we have a saved COPPA DOB?
	if($daddyobb->cookies['coppadob'])
	{
		list($dob_day, $dob_month, $dob_year) = explode("-", $daddyobb->cookies['coppadob']);
		$user['birthday'] = array(
			"day" => $dob_day,
			"month" => $dob_month,
			"year" => $dob_year
		);
	}

	$user['options'] = array(
		"allownotices" => $daddyobb->input['allownotices'],
		"hideemail" => $daddyobb->input['hideemail'],
		"subscriptionmethod" => $daddyobb->input['subscriptionmethod'],
		"receivepms" => $daddyobb->input['receivepms'],
		"pmnotice" => $daddyobb->input['pmnotice'],
		"emailpmnotify" => $daddyobb->input['emailpmnotify'],
		"invisible" => $daddyobb->input['invisible'],
		"dstcorrection" => $daddyobb->input['dstcorrection']
	);

	$userhandler->set_data($user);

	$errors = "";

	if(!$userhandler->validate_user())
	{
		$errors = $userhandler->get_friendly_errors();
	}

	if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagecreatefrompng"))
	{
		$imagehash = $db->escape_string($daddyobb->input['imagehash']);
		$imagestring = $db->escape_string(my_strtolower($daddyobb->input['imagestring']));
		$query = $db->simple_select("captcha", "*", "imagehash='$imagehash' AND LOWER(imagestring)='$imagestring'");
		$imgcheck = $db->fetch_array($query);
		if(!$imgcheck['dateline'])
		{
			$errors[]  = $lang->error_regimageinvalid;
		}
		$db->delete_query("captcha", "imagehash='$imagehash'");
	}

	if(is_array($errors))
	{
		$username = htmlspecialchars_uni($daddyobb->input['username']);
		$email = htmlspecialchars_uni($daddyobb->input['email']);
		$email2 = htmlspecialchars_uni($daddyobb->input['email']);
		$referrername = htmlspecialchars_uni($daddyobb->input['referrername']);

		if($daddyobb->input['allownotices'] == 1)
		{
			$allownoticescheck = "checked=\"checked\"";
		}

		if($daddyobb->input['hideemail'] == 1)
		{
			$hideemailcheck = "checked=\"checked\"";
		}

		if($daddyobb->input['subscriptionmethod'] == 1)
		{
			$no_email_subscribe_selected = "selected=\"selected\"";
		}
		else if($daddyobb->input['subscriptionmethod'] == 2)
		{
			$instant_email_subscribe_selected = "selected=\"selected\"";
		}
		else
		{
			$no_subscribe_selected = "selected=\"selected\"";
		}

		if($daddyobb->input['receivepms'] == 1)
		{
			$receivepmscheck = "checked=\"checked\"";
		}

		if($daddyobb->input['pmnotice'] == 1)
		{
			$pmnoticecheck = " checked=\"checked\"";
		}

		if($daddyobb->input['emailpmnotify'] == 1)
		{
			$emailpmnotifycheck = "checked=\"checked\"";
		}

		if($daddyobb->input['invisible'] == 1)
		{
			$invisiblecheck = "checked=\"checked\"";
		}

		if($daddyobb->input['dstcorrection'] == 2)
		{
			$dst_auto_selected = "selected=\"selected\"";
		}
		else if($daddyobb->input['dstcorrection'] == 1)
		{
			$dst_enabled_selected = "selected=\"selected\"";
		}
		else
		{
			$dst_disabled_selected = "selected=\"selected\"";
		}

		$regerrors = inline_error($errors);
		$daddyobb->input['action'] = "register";
		$fromreg = 1;
	}
	else
	{
		$user_info = $userhandler->insert_user();

		if($daddyobb->settings['regtype'] != "randompass" && !$daddyobb->cookies['coppauser'])
		{
			// Log them in
			my_setcookie("daddyobbuser", $user_info['uid']."_".$user_info['loginkey'], null, true);
		}

		if($daddyobb->cookies['coppauser'])
		{
			$lang->redirect_registered_coppa_activate = $lang->sprintf($lang->redirect_registered_coppa_activate, $daddyobb->settings['bbname'], $user_info['username']);
			my_unsetcookie("coppauser");
			my_unsetcookie("coppadob");
			$plugins->run_hooks("member_do_register_end");
			error($lang->redirect_registered_coppa_activate);
		}
		else if($daddyobb->settings['regtype'] == "verify")
		{
			$activationcode = random_str();
			$now = TIME_NOW;
			$activationarray = array(
				"uid" => $user_info['uid'],
				"dateline" => TIME_NOW,
				"code" => $activationcode,
				"type" => "r"
			);
			$db->insert_query("awaitingactivation", $activationarray);
			$emailsubject = $lang->sprintf($lang->emailsubject_activateaccount, $daddyobb->settings['bbname']);
			$emailmessage = $lang->sprintf($lang->email_activateaccount, $user_info['username'], $daddyobb->settings['bbname'], $daddyobb->settings['bburl'], $user_info['uid'], $activationcode);
			my_mail($user_info['email'], $emailsubject, $emailmessage);
			
			$lang->redirect_registered_activation = $lang->sprintf($lang->redirect_registered_activation, $daddyobb->settings['bbname'], $user_info['username']);

			$plugins->run_hooks("member_do_register_end");

			error($lang->redirect_registered_activation);
		}
		else if($daddyobb->settings['regtype'] == "randompass")
		{
			$emailsubject = $lang->sprintf($lang->emailsubject_randompassword, $daddyobb->settings['bbname']);
			$emailmessage = $lang->sprintf($lang->email_randompassword, $user['username'], $daddyobb->settings['bbname'], $user_info['username'], $user_info['password']);
			my_mail($user_info['email'], $emailsubject, $emailmessage);

			$plugins->run_hooks("member_do_register_end");

			error($lang->redirect_registered_passwordsent);
		}
		else if($daddyobb->settings['regtype'] == "admin")
		{
			$lang->redirect_registered_admin_activate = $lang->sprintf($lang->redirect_registered_admin_activate, $daddyobb->settings['bbname'], $user_info['username']);

			$plugins->run_hooks("member_do_register_end");

			error($lang->redirect_registered_admin_activate);
		}
		else
		{
			$lang->redirect_registered = $lang->sprintf($lang->redirect_registered, $daddyobb->settings['bbname'], $user_info['username']);

			$plugins->run_hooks("member_do_register_end");

			redirect("index.php", $lang->redirect_registered);
		}
	}
}

if($daddyobb->input['action'] == "coppa_form")
{
	if(!$daddyobb->settings['faxno'])
	{
		$daddyobb->settings['faxno'] = "&nbsp;";
	}
	
	eval("\$coppa_form = \"".$templates->get("member_coppa_form")."\";");
	output_page($coppa_form);
}

if($daddyobb->input['action'] == "register")
{
	$bdaysel = '';
	if($daddyobb->settings['coppa'] == "disabled")
	{
		$bdaysel = $bday2blank = "<option value=\"\">&nbsp;</option>";
	}
	for($i = 1; $i <= 31; ++$i)
	{
		if($daddyobb->input['bday1'] == $i)
		{
			$bdaysel .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		}
		else
		{
			$bdaysel .= "<option value=\"$i\">$i</option>\n";
		}
	}

	$bdaymonthsel[$daddyobb->input['bday2']] = "selected=\"selected\"";
	$daddyobb->input['bday3'] = intval($daddyobb->input['bday3']);

	if($daddyobb->input['bday3'] == 0) $daddyobb->input['bday3'] = "";

	// Is COPPA checking enabled?
	if($daddyobb->settings['coppa'] != "disabled" && !$daddyobb->input['step'])
	{
		// Just selected DOB, we check
		if($daddyobb->input['bday1'] && $daddyobb->input['bday2'] && $daddyobb->input['bday3'])
		{
			my_unsetcookie("coppauser");
			
			$bdaytime = @mktime(0, 0, 0, $daddyobb->input['bday2'], $daddyobb->input['bday1'], $daddyobb->input['bday3']);
			
			// Store DOB in cookie so we can save it with the registration
			my_setcookie("coppadob", "{$daddyobb->input['bday1']}-{$daddyobb->input['bday2']}-{$daddyobb->input['bday3']}", -1);

			// User is <= 13, we mark as a coppa user
			if($bdaytime >= mktime(0, 0, 0, my_date('n'), my_date('d'), my_date('Y')-13))
			{
				my_setcookie("coppauser", 1, -0);
				$under_thirteen = true;
			}
			$daddyobb->request_method = "";
		}
		// Show DOB select form
		else
		{
			$plugins->run_hooks("member_register_coppa");
			
			my_unsetcookie("coppauser");
			
			eval("\$coppa = \"".$templates->get("member_register_coppa")."\";");
			output_page($coppa);
			exit;
		}
	}

	if((!isset($daddyobb->input['agree']) && !isset($daddyobb->input['regsubmit'])) || $daddyobb->request_method != "post")
	{
		// Is this user a COPPA user? We need to show the COPPA agreement too
		if($daddyobb->settings['coppa'] != "disabled" && ($daddyobb->cookies['coppauser'] == 1 || $under_thirteen))
		{
			if($daddyobb->settings['coppa'] == "deny")
			{
				error($lang->error_need_to_be_thirteen);
			}
			$lang->coppa_agreement_1 = $lang->sprintf($lang->coppa_agreement_1, $daddyobb->settings['bbname']);
			eval("\$coppa_agreement = \"".$templates->get("member_register_agreement_coppa")."\";");
		}

		$plugins->run_hooks("member_register_agreement");

		eval("\$agreement = \"".$templates->get("member_register_agreement")."\";");
		output_page($agreement);
	}
	else
	{
		$plugins->run_hooks("member_register_start");
		
		$validator_extra = '';

		if(isset($daddyobb->input['timezoneoffset']))
		{
			$timezoneoffset = $daddyobb->input['timezoneoffset'];
		}
		else
		{
			$timezoneoffset = $daddyobb->settings['timezoneoffset'];
		}
		$tzselect = build_timezone_select("timezoneoffset", $timezoneoffset, true);

		$stylelist = build_theme_select("style");

		if($daddyobb->settings['usertppoptions'])
		{
			$tppoptions = '';
			$explodedtpp = explode(",", $daddyobb->settings['usertppoptions']);
			if(is_array($explodedtpp))
			{
				foreach($explodedtpp as $val)
				{
					$val = trim($val);
					$tppoptions .= "<option value=\"$val\">".$lang->sprintf($lang->tpp_option, $val)."</option>\n";
				}
			}
			eval("\$tppselect = \"".$templates->get("usercp_options_tppselect")."\";");
		}
		if($daddyobb->settings['userpppoptions'])
		{
			$pppoptions = '';
			$explodedppp = explode(",", $daddyobb->settings['userpppoptions']);
			if(is_array($explodedppp))
			{
				foreach($explodedppp as $val)
				{
					$val = trim($val);
					$pppoptions .= "<option value=\"$val\">".$lang->sprintf($lang->ppp_option, $val)."</option>\n";
				}
			}
			eval("\$pppselect = \"".$templates->get("usercp_options_pppselect")."\";");
		}
		if($daddyobb->settings['usereferrals'] == 1 && !$daddyobb->user['uid'])
		{
			if($daddyobb->cookies['daddyobb']['referrer'])
			{
				$query = $db->simple_select("users", "uid,username", "uid='".$db->escape_string($daddyobb->cookies['daddyobb']['referrer'])."'");
				$ref = $db->fetch_array($query);
				$referrername = $ref['username'];
			}
			elseif($referrer)
			{
				$query = $db->simple_select("users", "username", "uid='".intval($referrer['uid'])."'");
				$ref = $db->fetch_array($query);
				$referrername = $ref['username'];
			}
			elseif($referrername)
			{
				$query = $db->simple_select("users", "uid", "LOWER(username)='".$db->escape_string(my_strtolower($referrername))."'");
				$ref = $db->fetch_array($query);
				if(!$ref['uid'])
				{
					$errors[] = $lang->error_badreferrer;
				}
			}
			if($quickreg)
			{
				$refbg = "trow1";
			}
			else
			{
				$refbg = "trow2";
			}
			// JS validator extra
			$validator_extra .= "\tregValidator.register('referrer', 'ajax', {url:'xmlhttp.php?action=username_exists', loading_message:'{$lang->js_validator_checking_referrer}'});\n";

			eval("\$referrer = \"".$templates->get("member_register_referrer")."\";");
		}
		else
		{
			$referrer = '';
		}
		// Custom profile fields baby!
		$altbg = "trow1";
		$query = $db->simple_select("profilefields", "*", "editable=1", array('order_by' => 'disporder'));
		while($profilefield = $db->fetch_array($query))
		{
			$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
			$thing = explode("\n", $profilefield['type'], "2");
			$type = trim($thing[0]);
			$options = $thing[1];
			$select = '';
			$field = "fid{$profilefield['fid']}";
			if($errors)
			{
				$userfield = $daddyobb->input['profile_fields'][$field];
			}
			else
			{
				$userfield = '';
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
							$sel = "selected=\"selected\"";
						}
						$select .= "<option value=\"$val\" $sel>$val</option>\n";
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 3;
					}
					$code = "<select name=\"profile_fields[$field][]\" id=\"{$field}\" size=\"{$profilefield['length']}\" multiple=\"multiple\">$select</select>";
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
							$sel = "selected=\"selected\"";
						}
						$select .= "<option value=\"$val\" $sel>$val</option>";
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 1;
					}
					$code = "<select name=\"profile_fields[$field]\" id=\"{$field}\" size=\"{$profilefield['length']}\">$select</select>";
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
							$checked = "checked=\"checked\"";
						}
						$code .= "<input type=\"radio\" class=\"radio\" name=\"profile_fields[$field]\" id=\"{$field}{$key}\" value=\"$val\" $checked /> <span class=\"smalltext\">$val</span><br />";
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
							$checked = "checked=\"checked\"";
						}
						$code .= "<input type=\"checkbox\" class=\"checkbox\" name=\"profile_fields[$field][]\" id=\"{$field}{$key}\" value=\"$val\" $checked /> <span class=\"smalltext\">$val</span><br />";
					}
				}
			}
			elseif($type == "textarea")
			{
				$value = htmlspecialchars_uni($userfield);
				$code = "<textarea name=\"profile_fields[$field]\" id=\"{$field}\" rows=\"6\" cols=\"30\" style=\"width: 95%\">$value</textarea>";
			}
			else
			{
				$value = htmlspecialchars_uni($userfield);
				$code = "<input type=\"text\" name=\"profile_fields[$field]\" id=\"{$field}\" class=\"textbox\" size=\"{$profilefield['length']}\" maxlength=\"{$profilefield['maxlength']}\" value=\"$value\" />";
			}
			if($profilefield['required'] == 1)
			{
				// JS validator extra
				if($type == "checkbox" || $type == "radio")
				{
					$id = "{$field}0";
				}
				else
				{
					$id = "fid{$profilefield['fid']}";
				}
				$validator_extra .= "\tregValidator.register('{$id}', 'notEmpty', {failure_message:'{$lang->js_validator_not_empty}'});\n";
				
				eval("\$requiredfields .= \"".$templates->get("member_register_customfield")."\";");
			}
			$code = '';
			$select = '';
			$val = '';
			$options = '';
			$expoptions = '';
			$useropts = '';
			$seloptions = '';
		}
		if($requiredfields)
		{
			eval("\$requiredfields = \"".$templates->get("member_register_requiredfields")."\";");
		}
		if(!$fromreg)
		{
			$allownoticescheck = "checked=\"checked\"";
			$hideemailcheck = '';
			$emailnotifycheck = '';
			$receivepmscheck = "checked=\"checked\"";
			$pmnoticecheck = " checked=\"checked\"";
			$emailpmnotifycheck = '';
			$invisiblecheck = '';
			if($daddyobb->settings['dstcorrection'] == 1)
			{
				$enabledstcheck = "checked=\"checked\"";
			}
			
		}
		// Spambot registration image thingy
		if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagecreatefrompng"))
		{
			$randomstr = random_str(5);
			$imagehash = md5(random_str(12));
			$regimagearray = array(
				"imagehash" => $imagehash,
				"imagestring" => $randomstr,
				"dateline" => TIME_NOW
			);
			$db->insert_query("captcha", $regimagearray);
			eval("\$regimage = \"".$templates->get("member_register_regimage")."\";");

			// JS validator extra
			$validator_extra .= "\tregValidator.register('imagestring', 'ajax', {url:'xmlhttp.php?action=validate_captcha', extra_body: 'imagehash', loading_message:'{$lang->js_validator_captcha_valid}', failure_message:'{$lang->js_validator_no_image_text}'});\n";
		}
		if($daddyobb->settings['regtype'] != "randompass")
		{
			eval("\$passboxes = \"".$templates->get("member_register_password")."\";");

			// JS validator extra
			$lang->js_validator_password_length = $lang->sprintf($lang->js_validator_password_length, $daddyobb->settings['minpasswordlength']);
			$validator_extra .= "\tregValidator.register('password', 'length', {match_field:'password2', min: {$daddyobb->settings['minpasswordlength']}, failure_message:'{$lang->js_validator_password_length}'});\n";

			// See if the board has "require complex passwords" enabled.
			if($daddyobb->settings['requirecomplexpasswords'] == 1)
			{
				$validator_extra .= "\tregValidator.register('password', 'regexp', {match_field:'password2', regexp:'[\W]+', failure_message:'{$lang->js_validator_password_complexity}'});\n";
			}
			$validator_extra .= "\tregValidator.register('password2', 'matches', {match_field:'password', status_field:'password_status', failure_message:'{$lang->js_validator_password_matches}'});\n";
		}

		// JS validator extra
		if($daddyobb->settings['maxnamelength'] > 0 && $daddyobb->settings['minnamelength'] > 0)
		{
			$lang->js_validator_username_length = $lang->sprintf($lang->js_validator_username_length, $daddyobb->settings['minnamelength'], $daddyobb->settings['maxnamelength']);
			$validator_extra .= "\tregValidator.register('username', 'length', {min: {$daddyobb->settings['minnamelength']}, max: {$daddyobb->settings['maxnamelength']}, failure_message:'{$lang->js_validator_username_length}'});\n";
		}

		$languages = $lang->get_languages();
		$langoptions = '';
		foreach($languages as $lname => $language)
		{
			$language = htmlspecialchars_uni($language);
			if($user['language'] == $lname)
			{
				$langoptions .= "<option value=\"$lname\" selected=\"selected\">$language</option>\n";
			}
			else
			{
				$langoptions .= "<option value=\"$lname\">$language</option>\n";
			}
		}

		$plugins->run_hooks("member_register_end");

		eval("\$registration = \"".$templates->get("member_register")."\";");
		output_page($registration);
	}
}

if($daddyobb->input['action'] == "activate")
{
	$plugins->run_hooks("member_activate_start");

	if($daddyobb->input['username'])
	{
		$query = $db->simple_select("users", "*", "LOWER(username)='".$db->escape_string(my_strtolower($daddyobb->input['username']))."'", array('limit' => 1));
		$user = $db->fetch_array($query);
		if(!$user['username'])
		{
			error($lang->error_invalidpworusername);
		}
		$uid = $user['uid'];
	}
	else
	{
		$query = $db->simple_select("users", "*", "uid='".intval($daddyobb->input['uid'])."'");
		$user = $db->fetch_array($query);
	}
	if($daddyobb->input['code'] && $user['uid'])
	{
		$daddyobb->settings['awaitingusergroup'] = "5";
		$query = $db->simple_select("awaitingactivation", "*", "uid='".$user['uid']."' AND (type='r' OR type='e')");
		$activation = $db->fetch_array($query);
		if(!$activation['uid'])
		{
			error($lang->error_alreadyactivated);
		}
		if($activation['code'] != $daddyobb->input['code'])
		{
			error($lang->error_badactivationcode);
		}
		$db->delete_query("awaitingactivation", "uid='".$user['uid']."' AND (type='r' OR type='e')");
		if($user['usergroup'] == 5 && $activation['type'] != "e")
		{
			$db->update_query("users", array("usergroup" => 2), "uid='".$user['uid']."'");
		}
		if($activation['type'] == "e")
		{
			$newemail = array(
				"email" => $db->escape_string($activation['misc']),
				);
			$db->update_query("users", $newemail, "uid='".$user['uid']."'");
			$plugins->run_hooks("member_activate_emailupdated");

			redirect("usercp.php", $lang->redirect_emailupdated);
		}
		else
		{
			$plugins->run_hooks("member_activate_accountactivated");

			redirect("index.php", $lang->redirect_accountactivated);
		}
	}
	else
	{
		$plugins->run_hooks("member_activate_form");

		eval("\$activate = \"".$templates->get("member_activate")."\";");
		output_page($activate);
	}
}

if($daddyobb->input['action'] == "resendactivation")
{
	$plugins->run_hooks("member_resendactivation");

	if($daddyobb->settings['regtype'] == "admin")
	{
		error($lang->error_activated_by_admin);
	}

	eval("\$activate = \"".$templates->get("member_resendactivation")."\";");
	output_page($activate);
}

if($daddyobb->input['action'] == "do_resendactivation" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("member_do_resendactivation_start");

	if($daddyobb->settings['regtype'] == "admin")
	{
		error($lang->error_activated_by_admin);
	}

	$query = $db->query("
		SELECT u.uid, u.username, u.usergroup, u.email, a.code
		FROM ".TABLE_PREFIX."users u
		LEFT JOIN ".TABLE_PREFIX."awaitingactivation a ON (a.uid=u.uid AND a.type='r')
		WHERE u.email='".$db->escape_string($daddyobb->input['email'])."'
	");
	$numusers = $db->num_rows($query);
	if($numusers < 1)
	{
		error($lang->error_invalidemail);
	}
	else
	{
		while($user = $db->fetch_array($query))
		{
			if($user['usergroup'] == 5)
			{
				if(!$user['code'])
				{
					$user['code'] = random_str();
					$now = TIME_NOW;
					$uid = $user['uid'];
					$awaitingarray = array(
						"uid" => $uid,
						"dateline" => TIME_NOW,
						"code" => $user['code'],
						"type" => "r"
					);
					$db->insert_query("awaitingactivation", $awaitingarray);
				}
				$username = $user['username'];
				$email = $user['email'];
				$activationcode = $user['code'];
				$emailsubject = $lang->sprintf($lang->emailsubject_activateaccount, $daddyobb->settings['bbname']);
				$emailmessage = $lang->sprintf($lang->email_activateaccount, $user['username'], $daddyobb->settings['bbname'], $daddyobb->settings['bburl'], $user['uid'], $activationcode);
				my_mail($email, $emailsubject, $emailmessage);
			}
		}
		$plugins->run_hooks("member_do_resendactivation_end");

		redirect("index.php", $lang->redirect_activationresent);
	}
}

if($daddyobb->input['action'] == "lostpw")
{
	$plugins->run_hooks("member_lostpw");

	eval("\$lostpw = \"".$templates->get("member_lostpw")."\";");
	output_page($lostpw);
}

if($daddyobb->input['action'] == "do_lostpw" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("member_do_lostpw_start");

	$email = $db->escape_string($email);
	$query = $db->simple_select("users", "*", "email='".$db->escape_string($daddyobb->input['email'])."'");
	$numusers = $db->num_rows($query);
	if($numusers < 1)
	{
		error($lang->error_invalidemail);
	}
	else
	{
		while($user = $db->fetch_array($query))
		{
			$db->delete_query("awaitingactivation", "uid='{$user['uid']}' AND type='p'");
			$user['activationcode'] = random_str();
			$now = TIME_NOW;
			$uid = $user['uid'];
			$awaitingarray = array(
				"uid" => $user['uid'],
				"dateline" => TIME_NOW,
				"code" => $user['activationcode'],
				"type" => "p"
			);
			$db->insert_query("awaitingactivation", $awaitingarray);
			$username = $user['username'];
			$email = $user['email'];
			$activationcode = $user['activationcode'];
			$emailsubject = $lang->sprintf($lang->emailsubject_lostpw, $daddyobb->settings['bbname']);
			$emailmessage = $lang->sprintf($lang->email_lostpw, $username, $daddyobb->settings['bbname'], $daddyobb->settings['bburl'], $uid, $activationcode);
			my_mail($email, $emailsubject, $emailmessage);
		}
	}
	$plugins->run_hooks("member_do_lostpw_end");

	redirect("index.php", $lang->redirect_lostpwsent);
}

if($daddyobb->input['action'] == "resetpassword")
{
	$plugins->run_hooks("member_resetpassword_start");

	if($daddyobb->input['username'])
	{
		$query = $db->simple_select("users", "*", "LOWER(username)='".$db->escape_string(my_strtolower($daddyobb->input['username']))."'");
		$user = $db->fetch_array($query);
		if(!$user['uid'])
		{
			error($lang->error_invalidpworusername);
		}
	}
	else
	{
		$query = $db->simple_select("users", "*", "uid='".intval($daddyobb->input['uid'])."'");
		$user = $db->fetch_array($query);
	}
	if($daddyobb->input['code'] && $user['uid'])
	{
		$query = $db->simple_select("awaitingactivation", "*", "uid='".$user['uid']."' AND type='p'");
		$activation = $db->fetch_array($query);
		$now = TIME_NOW;
		if($activation['code'] != $daddyobb->input['code'])
		{
			error($lang->error_badlostpwcode);
		}
		$db->delete_query("awaitingactivation", "uid='".$user['uid']."' AND type='p'");
		$username = $user['username'];

		// Generate a new password, then update it
		$password = random_str();
		$logindetails = update_password($user['uid'], md5($password), $user['salt']);

		$email = $user['email'];

		$plugins->run_hooks("member_resetpassword_process");

		$emailsubject = $lang->sprintf($lang->emailsubject_passwordreset, $daddyobb->settings['bbname']);
		$emailmessage = $lang->sprintf($lang->email_passwordreset, $username, $daddyobb->settings['bbname'], $password);
		my_mail($email, $emailsubject, $emailmessage);

		$plugins->run_hooks("member_resetpassword_reset");

		error($lang->redirect_passwordreset);
	}
	else
	{
		$plugins->run_hooks("member_resetpassword_form");

		eval("\$activate = \"".$templates->get("member_resetpassword")."\";");
		output_page($activate);
	}
}

$do_captcha = $correct = false;
$inline_errors = "";
if($daddyobb->input['action'] == "do_login" && $daddyobb->request_method == "post")
{
	$plugins->run_hooks("member_do_login_start");
	
	// Checks to make sure the user can login; they haven't had too many tries at logging in.
	// Is a fatal call if user has had too many tries
	$logins = login_attempt_check();
	$login_text = '';
	
	// Did we come from the quick login form
	if($daddyobb->input['quick_login'] == "1" && $daddyobb->input['quick_password'] && $daddyobb->input['quick_username'])
	{
		$daddyobb->input['password'] = $daddyobb->input['quick_password'];
		$daddyobb->input['username'] = $daddyobb->input['quick_username'];
	}

	if(!username_exists($daddyobb->input['username']))
	{
		my_setcookie('loginattempts', $logins + 1);
		error($lang->error_invalidpworusername.$login_text);
	}
	
	$query = $db->simple_select("users", "loginattempts", "LOWER(username)='".$db->escape_string(my_strtolower($daddyobb->input['username']))."'", array('limit' => 1));
	$loginattempts = $db->fetch_field($query, "loginattempts");
	
	$errors = array();
	
	$user = validate_password_from_username($daddyobb->input['username'], $daddyobb->input['password']);
	if(!$user['uid'])
	{
		my_setcookie('loginattempts', $logins + 1);
		$db->write_query("UPDATE ".TABLE_PREFIX."users SET loginattempts=loginattempts+1 WHERE LOWER(username) = '".$db->escape_string(my_strtolower($daddyobb->input['username']))."'");
		
		$daddyobb->input['action'] = "login";
		$daddyobb->input['request_method'] = "get";
		
		if($daddyobb->settings['failedlogintext'] == 1)
		{
			$login_text = $lang->sprintf($lang->failed_login_again, $daddyobb->settings['failedlogincount'] - $logins);
		}
		
		$errors[] = $lang->error_invalidpworusername.$login_text;
	}
	else
	{
		$correct = true;
	}
	
	if($loginattempts > 3 || intval($daddyobb->cookies['loginattempts']) > 3)
	{		
		// Show captcha image for guests if enabled
		if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagepng") && !$daddyobb->user['uid'])
		{
			// If previewing a post - check their current captcha input - if correct, hide the captcha input area
			if($daddyobb->input['imagestring'])
			{
				$imagehash = $db->escape_string($daddyobb->input['imagehash']);
				$imagestring = $db->escape_string($daddyobb->input['imagestring']);
				$query = $db->simple_select("captcha", "*", "imagehash='{$imagehash}' AND imagestring='{$imagestring}'");
				$imgcheck = $db->fetch_array($query);
				if($imgcheck['dateline'] > 0)
				{		
					$correct = true;
				}
				else
				{
					$db->delete_query("captcha", "imagehash='{$imagehash}'");
					$errors[] = $lang->error_regimageinvalid;
				}
			}
			else if($daddyobb->input['quick_login'] == 1 && $daddyobb->input['quick_password'] && $daddyobb->input['quick_username'])
			{
				$errors[] = $lang->error_regimagerequired;
			}
			else
			{
				$errors[] = $lang->error_regimagerequired;
			}
		}
		
		$do_captcha = true;
	}
	
	if(!empty($errors))
	{
		$daddyobb->input['action'] = "login";
		$daddyobb->input['request_method'] = "get";
		
		$inline_errors = inline_error($errors);
	}
	else if($correct)
	{		
		if($user['coppauser'])
		{
			error($lang->error_awaitingcoppa);
		}
		
		my_setcookie('loginattempts', 1);
		$db->delete_query("sessions", "ip='".$db->escape_string($session->ipaddress)."' AND sid != '".$session->sid."'");
		$newsession = array(
			"uid" => $user['uid'],
		);
		$db->update_query("sessions", $newsession, "sid='".$session->sid."'");
		
		$db->update_query("users", array("loginattempts" => 1), "uid='{$user['uid']}'");
	
		// Temporarily set the cookie remember option for the login cookies
		$daddyobb->user['remember'] = $user['remember'];
	
		my_setcookie("daddyobbuser", $user['uid']."_".$user['loginkey'], null, true);
		my_setcookie("sid", $session->sid, -1, true);
	
		$plugins->run_hooks("member_do_login_end");
	
		if($daddyobb->input['url'] != "" && my_strpos(basename($daddyobb->input['url']), 'member.php') === false)
		{
			if((my_strpos(basename($daddyobb->input['url']), 'newthread.php') !== false || my_strpos(basename($daddyobb->input['url']), 'newreply.php') !== false) && my_strpos($daddyobb->input['url'], '&processed=1') !== false)
			{
				$daddyobb->input['url'] = str_replace('&processed=1', '', $daddyobb->input['url']);
			}
			
			$daddyobb->input['url'] = str_replace('&amp;', '&', $daddyobb->input['url']);
			
			// Redirect to the URL if it is not member.php
			$lang->redirect_loggedin = $lang->sprintf($lang->redirect_loggedin, $user['username']);
			redirect(htmlentities($daddyobb->input['url']), $lang->redirect_loggedin);
		}
		else
		{
			$lang->redirect_loggedin = $lang->sprintf($lang->redirect_loggedin, $user['username']);
			redirect("index.php", $lang->redirect_loggedin);
		}
	}
	else
	{
		$daddyobb->input['action'] = "login";
		$daddyobb->input['request_method'] = "get";
	}
	
	$plugins->run_hooks("member_do_login_end");
}

if($daddyobb->input['action'] == "login")
{
	$plugins->run_hooks("member_login");
	
	$member_loggedin_notice = "";
	if($daddyobb->user['uid'] != 0)
	{
		$lang->already_logged_in = $lang->sprintf($lang->already_logged_in, build_profile_link($daddyobb->user['username'], $daddyobb->user['uid']));
		eval("\$member_loggedin_notice = \"".$templates->get("member_loggedin_notice")."\";");
	}

	// Checks to make sure the user can login; they haven't had too many tries at logging in.
	// Is a fatal call if user has had too many tries
	login_attempt_check();

	// Redirect to the page where the user came from, but not if that was the login page.
	if($daddyobb->input['url'] && !preg_match("/action=login/i", $daddyobb->input['url']))
	{
		$redirect_url = htmlentities($daddyobb->input['url']);
	}
	elseif($_SERVER['HTTP_REFERER'])
	{
		$redirect_url = htmlentities($_SERVER['HTTP_REFERER']);
	}
	
	$captcha = "";
	// Show captcha image for guests if enabled
	if($daddyobb->settings['captchaimage'] == 1 && function_exists("imagepng") && $do_captcha == true)
	{
		if(!$correct)
		{	
			$randomstr = random_str(5);
			$imagehash = md5(random_str(12));
			$imagearray = array(
				"imagehash" => $imagehash,
				"imagestring" => $randomstr,
				"dateline" => TIME_NOW
			);
			$db->insert_query("captcha", $imagearray);
			eval("\$captcha = \"".$templates->get("post_captcha")."\";");			
		}
	}
	
	$username = "";
	$password = "";
	if($daddyobb->input['username'] && $daddyobb->request_method == "post")
	{
		$username = htmlspecialchars_uni($daddyobb->input['username']);
	}
	
	if($daddyobb->input['password'] && $daddyobb->request_method == "post")
	{
		$password = htmlspecialchars_uni($daddyobb->input['password']);
	}

	eval("\$login = \"".$templates->get("member_login")."\";");
	output_page($login);
}

if($daddyobb->input['action'] == "logout")
{
	$plugins->run_hooks("member_logout_start");

	if(!$daddyobb->user['uid'])
	{
		redirect("index.php", $lang->redirect_alreadyloggedout);
	}

	// Check session ID if we have one
	if($daddyobb->input['sid'] && $daddyobb->input['sid'] != $session->sid)
	{
		error($lang->error_notloggedout);
	}
	// Otherwise, check logoutkey
	else if(!$daddyobb->input['sid'] && $daddyobb->input['logoutkey'] != $daddyobb->user['logoutkey'])
	{
		error($lang->error_notloggedout);
	}

	my_unsetcookie("daddyobbuser");
	my_unsetcookie("sid");
	if($daddyobb->user['uid'])
	{
		$time = TIME_NOW;
		$lastvisit = array(
			"lastactive" => $time-900,
			"lastvisit" => $time,
			);
		$db->update_query("users", $lastvisit, "uid='".$daddyobb->user['uid']."'");
		$db->delete_query("sessions", "sid='".$session->sid."'");
	}
	$plugins->run_hooks("member_logout_end");
	redirect("index.php", $lang->redirect_loggedout);
}

if($daddyobb->input['action'] == "profile")
{
	$plugins->run_hooks("member_profile_start");

	if($daddyobb->usergroup['canviewprofiles'] == 0)
	{
		error_no_permission();
	}
	if($daddyobb->input['uid'] == "lastposter")
	{
		if($daddyobb->input['tid'])
		{
			$query = $db->simple_select("posts", "uid", "tid='".intval($daddyobb->input['tid'])."'	AND visible = 1", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => '1'));
			$post = $db->fetch_array($query);
			$uid = $post['uid'];
		}
		elseif($daddyobb->input['fid'])
		{
			$flist = '';
			switch($db->type)
			{
				case "pgsql":
				case "sqlite3":
				case "sqlite2":
					$query = $db->simple_select("forums", "fid", "INSTR(','||parentlist||',',',".intval($daddyobb->input['fid']).",') > 0");
					break;
				default:
					$query = $db->simple_select("forums", "fid", "INSTR(CONCAT(',',parentlist,','),',".intval($daddyobb->input['fid']).",') > 0");
			}
			
			while($forum = $db->fetch_array($query))
			{
				if($forum['fid'] == $daddyobb->input['fid'])
				{
					$theforum = $forum;
				}
				$flist .= ",".$forum['fid'];
			}
			$query = $db->simple_select("threads", "tid", "fid IN (0$flist) AND visible = 1", array('order_by' => 'lastpost', 'order_dir' => 'DESC', 'limit' => '1'));
			$thread = $db->fetch_array($query);
			$tid = $thread['tid'];
			$query = $db->simple_select("posts", "uid", "tid='$tid' AND visible = 1", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => '1'));
			$post = $db->fetch_array($query);
			$uid = $post['uid'];
		}
	}
	else
	{
		if($daddyobb->input['uid'])
		{
			$uid = intval($daddyobb->input['uid']);
		}
		else
		{
			$uid = $daddyobb->user['uid'];
		}
	}
	
	if($daddyobb->user['uid'] != $uid)
	{
		$query = $db->simple_select("users", "*", "uid='$uid'");
		$memprofile = $db->fetch_array($query);
	}
	else
	{
		$memprofile = $daddyobb->user;
	}
	
	$lang->profile = $lang->sprintf($lang->profile, $memprofile['username']);

	if(!$memprofile['uid'])
	{
		error($lang->error_nomember);
	}

	// Get member's permissions
	$memperms = user_permissions($memprofile['uid']);

	$lang->nav_profile = $lang->sprintf($lang->nav_profile, $memprofile['username']);
	add_breadcrumb($lang->nav_profile);

	$lang->users_forum_info = $lang->sprintf($lang->users_forum_info, $memprofile['username']);
	$lang->users_contact_details = $lang->sprintf($lang->users_contact_details, $memprofile['username']);

	if($daddyobb->settings['enablepms'] != 0 && $memprofile['receivepms'] != 0 && $memperms['canusepms'] != 0 && my_strpos(",".$memprofile['ignorelist'].",", ",".$daddyobb->user['uid'].",") === false)
	{
		$lang->send_pm = $lang->sprintf($lang->send_pm, $memprofile['username']);
	}
	else
	{
		$lang->send_pm = '';
	}
	$lang->users_additional_info = $lang->sprintf($lang->users_additional_info, $memprofile['username']);
	$lang->users_signature = $lang->sprintf($lang->users_signature, $memprofile['username']);
	$lang->send_user_email = $lang->sprintf($lang->send_user_email, $memprofile['username']);

  //all new tabbing things
  $ref_query = $db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE referrer='".$daddyobb->input['uid']."'");
  $refcount = $db->num_rows($ref_query);
  
  //Now the sidebar friends...do we have ones?
  $buddies = explode(",", $memprofile['buddylist']);
  if($memprofile['buddylist'])
  {  
    //Get the real buddycount!
    $buddycount = 0;
    foreach($buddies as $budcount)
    {
      $budcountuser = get_user($budcount);
      $budcountlist = explode(",", $budcountuser['buddylist']);
      if(in_array($memprofile['uid'], $budcountlist))
      {
        $buddycount++;
      }
    }
    // Tabbed Friends
    $tab_buddylist = "";
    $timeout = TIME_NOW - $daddyobb->settings['wolcutoff']; // Set online timeout
    $timecut = TIME_NOW - $daddyobb->settings['wolcutoff'];
		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."users
			WHERE uid IN ({$memprofile['buddylist']})
			ORDER BY username ASC
		");
		while($tab_buddy = $db->fetch_array($query))
		{
      //Let this user only show if we are on they're buddylist, too!
      $bud_budlist = explode(",", $tab_buddy['buddylist']);
      if(in_array($memprofile['uid'], $bud_budlist))
      {
        $tab_buddyformattedname = format_name($tab_buddy['username'], $tab_buddy['usergroup'], $tab_buddy['displaygroup']);
        $tab_buddylink = get_profile_link($buddy['uid']);
        if($tab_buddy['lastactive'] > $timecut && ($tab_buddy['invisible'] == 0 || $daddyobb->usergroup['cancp'] == 1) && $tab_buddy['lastvisit'] != $tab_buddy['lastactive'])
        {
          $buddy_online = 1;
          $lang->x_is_online = $lang->sprintf($lang->x_is_online, $tab_buddy['username']);
        }
        else
        {
          $buddy_online = 0;
          $lang->x_is_offline = $lang->sprintf($lang->x_is_offline, $tab_buddy['username']);
        }
        eval("\$tab_buddylist .= \"".$templates->get("member_profile_tabbedbuddies_buddy")."\";");
      }
		}
		
    // Sidebar friends
    $buddylist = "";
    $done = "";
    if($daddyobb->settings['maxfriendssidebar']<=0 || !$daddyobb->settings['maxfriendssidebar'])
    {
      $daddyobb->settings['maxfriendssidebar'] = 6;
    }
    if($buddycount<=$daddyobb->settings['maxfriendssidebar'])
    {
      $i_max = $buddycount;
      foreach($buddies as $buduser)
      {
        $buddy = get_user($buduser);
        //Let this user only show if we are on they're buddylist, too!
        $bud_budlist = explode(",", $buddy['buddylist']);
        if(in_array($memprofile['uid'], $bud_budlist))
        {
          $buddyformattedname = format_name($buddy['username'], $buddy['usergroup'], $buddy['displaygroup']);
          $buddylink = get_profile_link($buddy['uid']);
          eval("\$buddylist .= \"".$templates->get("member_profile_buddylist")."\";");
        }
      }
    }
    else
    {
      $i_max = $daddyobb->settings['maxfriendssidebar'];
      for($i=1; $i<=$i_max; $i++)
      {
        $notin = explode(",", $done);
        $max = $buddycount-1;
        if($max != 0)
        {
          do
          {
            $random = rand(0,$max);
          }
          while(in_array($random, $notin) && $random != 0);
        }
        else
        {
          $random = 0;
        }
        $buddy = get_user($buddies[$random]);
        
        //Let this user only show if we are on they're buddylist, too!
        $bud_budlist = explode(",", $buddy['buddylist']);
        if(in_array($memprofile['uid'], $bud_budlist))
        {
          $buddyformattedname = format_name($buddy['username'], $buddy['usergroup'], $buddy['displaygroup']);
          $buddylink = get_profile_link($buddy['uid']);
          eval("\$buddylist .= \"".$templates->get("member_profile_buddylist")."\";");
        }        
        $done .= ",$random";
      }
    }
    $lang->showing_x_of_y_friends = $lang->sprintf($lang->showing_x_of_y_friends, $i_max, $buddycount);
  }  
  $lang->x_has_not_made_any_friends_yet = $lang->sprintf($lang->x_has_not_made_any_friends_yet, $memprofile['username']);
  $lang->befriend_x = $lang->sprintf($lang->befriend_x, $memprofile['username']);

  
  //We have unread VMs and visited our profile? We've read them all (Or not xD)
  if($memprofile['uid'] == $daddyobb->user['uid'] && $daddyobb->user['uid'] != 0)
  {
    $db->query("UPDATE ".TABLE_PREFIX."visitormessage SET unread='0' WHERE touid='".intval($daddyobb->user['uid'])."'");
  }
  
  if($daddyobb->settings['enablevmsystem'] == 1 && $memprofile['enablevms'] == 1 && $memperms['canusevms'] == 1)
  {
    $vmessage_tab_head = 1;
  }
  else
  {
    $vmessage_tab_head = 0;
  }
  
  $lang->about_x = $lang->sprintf($lang->about_x, $memprofile['username']);
  

	if($memprofile['avatar'])
	{
		$memprofile['avatar'] = htmlspecialchars_uni($memprofile['avatar']);
		$avatar_dimensions = explode("|", $memprofile['avatardimensions']);
		if($avatar_dimensions[0] && $avatar_dimensions[1])
		{
			$avatar_width_height = "width=\"{$avatar_dimensions[0]}\" height=\"{$avatar_dimensions[1]}\"";
		}
		$avatar = 1;
	}
	
	if($memprofile['profilepic'])
	{
		$memprofile['profilepicture'] = htmlspecialchars_uni($memprofile['profilepic']);
		$profilepic_dimensions = explode("|", $memprofile['profilepicdimensions']);
		if($profilepic_dimensions[0] && $profilepic_dimensions[1])
		{
			$profilepic_width_height = "width:{$profilepic_dimensions[0]}px; height: {$profilepic_dimensions[1]}px;";
		}
		$profilepic = 1;
	}

	if($memprofile['hideemail'] != 1)
	{
		$sendemail = 1;
	}

	if($memprofile['website'])
	{
		$memprofile['website'] = htmlspecialchars_uni($memprofile['website']);
		$homepage = 1;
	}
	
	if(($daddyobb->settings['enablepms'] != 0 && $memprofile['receivepms'] != 0 && $memperms['canusepms'] != 0 && my_strpos(",".$memprofile['ignorelist'].",", ",".$daddyobb->user['uid'].",") === false) || ($memprofile['enablevms'] != 0 && $memperms['canusevms'] != 0 && $daddyobb->settings['enablevmsystem'] != 0) || $memprofile['hideemail'] != 1)
	{
    $lang->send_message_to_x = $lang->sprintf($lang->send_message_to_x, $memprofile['username']);

    if($daddyobb->settings['enablepms'] != 0 && $memprofile['receivepms'] != 0 && $memperms['canusepms'] != 0 && my_strpos(",".$memprofile['ignorelist'].",", ",".$daddyobb->user['uid'].",") === false)
    {
      $lang->send_a_pm_to_x = $lang->sprintf($lang->send_a_pm_to_x, $memprofile['username']);
      $pm_list = 1;
    }
    if($memprofile['enablevms'] != 0 && $memperms['canusevms'] != 0 && $daddyobb->settings['enablevmsystem'] != 0)
    {
      $lang->post_a_vm_for_x = $lang->sprintf($lang->post_a_vm_for_x, $memprofile['username']);
      $vm_list = 1;
    }
    $send_message_fieldset = 1;
	}
	
	if($memprofile['vcard'] == 1)
	{
    $vcard_list = 1;
	}
	
	//Get the IM Fieldset
	if(!empty($memprofile['icq']) || !empty($memprofile['msn']) || !empty($memprofile['aim']) || !empty($memprofile['yahoo']))
	{
    if(!empty($memprofile['icq']))
    {
      $memprofile['icq'] = intval($memprofile['icq']);
      $lang->send_im_via_icq_to_x = $lang->sprintf($lang->send_im_via_icq_to_x, $memprofile['username']);
      $im_icq = 1;
    }
    if(!empty($memprofile['msn']))
    {
      $lang->send_im_via_msn_to_x = $lang->sprintf($lang->send_im_via_msn_to_x, $memprofile['username']);
      $im_msn = 1;
    }
    if(!empty($memprofile['aim']))
    {
      $lang->send_im_via_aim_to_x = $lang->sprintf($lang->send_im_via_aim_to_x, $memprofile['username']);
      $im_aim = 1;
    }
    if(!empty($memprofile['yahoo']))
    {
      $lang->send_im_via_yahoo_to_x = $lang->sprintf($lang->send_im_via_yahoo_to_x, $memprofile['username']);
      $im_yahoo = 1;
    }
    $lang->send_im_to_x_using = $lang->sprintf($lang->send_im_to_x_using, $memprofile['username']);
    $im_fieldset = 1;
	}

	if($memprofile['signature'])
	{
		$sig_parser = array(
      "allow_html" => $memperms['sigallowhtml'],
      "allow_mycode" => $memperms['sigallowmycode'],
      "allow_smilies" => $memperms['sigallowsmilies'],
      "allow_imgcode" => $memperms['sigallowimgcode'],
			"me_username" => $memprofile['username']
		);

		$memprofile['signature'] = $parser->parse_message($memprofile['signature'], $sig_parser);
		$signature = 1;
	}
	
  ### Visitor Messaging ###
  $member_buddies = explode(",", $memprofile['buddylist']);
  //shall we fetch VMs?
  if($memprofile['enablevms'] == 1 && $memperms['canusevms'] == 1 && $daddyobb->settings['enablevmsystem'] == 1)
  {
  
  #$memprofile['limitvms'] == 1 && (in_array($daddyobb->user['uid'], $member_buddies) || $daddyobb->usergroup['canmodcp'] == 1 || $daddyobb->usergroup['issupermod'] == 1)
      if($daddyobb->usergroup['canusevms'] == 1)
      {
        $textbox = 1;
      }
      else
      {
        $textbox = 0;
      }

      //If we aren't super moderator and if we aren't viewing our own profile then don't fetch "unapproved" messages
      if($daddyobb->usergroup['issupermod'] != 1 && $memprofile['uid'] != $daddyobb->user['uid'] || ($memprofile['uid'] == $daddyobb->user['uid'] && $daddyobb->usergroup['canmanagevms'] == 0))
      {
        $notlike = "AND status NOT LIKE '0'";
      }
      if($daddyobb->settings['defaultvmspp'] <= $daddyobb->settings['maxvmspp'])
      {
        $vmspp = $daddyobb->settings['defaultvmspp'];
      }
      else
      {
        $vmspp = $daddyobb->settings['maxvmspp'];
      }
      if($vmspp == 0)
      {
        $vmspp = 10;
      }
      if($daddyobb->input['page'] != "last")
      {
        $vpage = intval($daddyobb->input['page']);
      }
      $vmcount_qry = $db->simple_select("visitormessage", "COUNT(vmid) as count", "touid='".intval($memprofile['uid'])."' {$notlike}");
      $vcount = $db->fetch_field($vmcount_qry, "count");
      $vpages = $vcount / $vmspp;
      $vpages = ceil($vpages);
      if($daddyobb->input['page'] == "last")
      {
        $vpage = $vpages;
      }
      if($vpage > $vpages || $vpage <= 0)
      {
        $vpage = 1;
      }
      if($vpage)
      {
        $vstart = ($vpage-1) * $vmspp;
      }
      else
      {
        $vstart = 0;
        $vpage = 1;
      }
      if($vpages > 1)
      {
        $vmultipage .= multipage($vcount, $vmspp, $vpage, "member.php?action=profile&uid={$daddyobb->input['uid']}");
      }
      $start = $vstart +1;
      $total = $vcount;
      $pagetotal = $vmspp * $vpage;
      if($total <= $pagetotal)
      {
        $diff = $pagetotal - $total;
        $end = $pagetotal - $diff;
      }
      else
      {
        $end = $pagetotal;
      }
      $lang->showing_vms_x_to_y_of_z = $lang->sprintf($lang->showing_vms_x_to_y_of_z, $start, $end, $total); //Get the showing bla to bla of bla string
      
      $vmessages = "";
      $vmqry = $db->query("
      SELECT v.*, u.*
      FROM ".TABLE_PREFIX."visitormessage v
      LEFT JOIN ".TABLE_PREFIX."users u ON (v.fromuid = u.uid)
      WHERE v.touid = '".intval($memprofile['uid'])."'
      {$notlike}
      ORDER BY v.dateline DESC
      LIMIT {$vstart}, {$vmspp}
      ");
      while($vmessage = $db->fetch_array($vmqry))
      {
        $vmprofilelink = format_name($vmessage['username'], $vmessage['usergroup'], $vmessage['displaygroup']);
        $vmessagedate = my_date($daddyobb->settings['dateformat'], $vmessage['dateline']);
        $vmessagetime = my_date($daddyobb->settings['timeformat'], $vmessage['dateline']);
        $vmessage_parser = array(
        "allow_html" => $daddyobb->settings['vmallowhtml'],
        "filter_badwords" => 1,
        "allow_mycode" => $daddyobb->settings['vmallowmycode'],
        "allow_smilies" => $daddyobb->settings['vmallowmilies'],
        "allow_imgcode" => $daddyobb->settings['vmallowimg'],
        );
        $vmessage['message'] = $parser->parse_message($vmessage['message'], $vmessage_parser);
        $vmessage['message'] = $parser->text_parse_message($vmessage['message']);
        if($vmessage['avatar'])
        {
          $vmessageava = "<img src=\"".$vmessage['avatar']."\" class=\"trow2\" width=\"60\" style=\"margin-bottom: 6px;\">";
        }
        else
        {
          $vmessageava = "<img src=\"".$theme['imgdir']."/no_avatar.gif\" class=\"trow2\" height=\"60\" width=\"60\" style=\"margin-bottom: 6px;\">";
        }
        //Get the moderatig links
        if($vmessage['status'] != 2)
        {
          $reportlink = true;
          if($daddyobb->usergroup['canmanagevms'] == 1 && $daddyobb->user['uid'] == $memprofile['uid'] || $daddyobb->user['uid'] == $vmessage['fromuid'] && $daddyobb->usergroup['caneditownvms'] == 1 || $daddyobb->usergroup['modcanmanagevms'] == 1)
          {
            $editlink = true;
          }
          else
          {
            $editlink = false;
          }
        }
        else
        {
          $reportlink = false;
          $editlink = false;
        }        
        if($daddyobb->usergroup['modcanmanagevms'] == 1)
        {
          $iplink = true;
          $editlink = true;
        }
        else
        {
          $editlink = false;
        }
        eval("\$vmessages .= \"".$templates->get("member_profile_vmessage_messagebit")."\";");
      }
      if(!$vmessages || $vcount == 0)
      {
        eval("\$vmessages = \"".$templates->get("vmessages_nomessages")."\";");
      }

      $vmessage_tab = 1;
      
      if($memprofile['limitvms'] == 1 && !in_array($daddyobb->user['uid'], $member_buddies) && $daddyobb->usergroup['canmodcp'] != 1 && $daddyobb->usergroup['issupermod'] != 1)
      {
        $vmessages = "";
        $vmessage_tab = 0;
        $vmessage_tab_head = 0;
        $act_class = "first";
        $textbox = 0;
      }
    
  }

	$daysreg = (TIME_NOW - $memprofile['regdate']) / (24*3600);
	$ppd = $memprofile['postnum'] / $daysreg;
	$ppd = round($ppd, 2);
	if($ppd > $memprofile['postnum'])
	{
		$ppd = $memprofile['postnum'];
	}
	$stats = $cache->read("stats");
	$numposts = $stats['numposts'];
	if($numposts == 0)
	{
		$percent = "0";
	}
	else
	{
		$percent = $memprofile['postnum']*100/$numposts;
		$percent = round($percent, 2);
	}
	
	if($percent > 100)
	{
		$percent = 100;
	}

  if($daddyobb->settings['usereferrals'] == 1)
  {
    $query = $db->simple_select("users", "COUNT(uid) AS referrals", "referrer='{$memprofile['uid']}'");
    $referrals = $db->fetch_field($query, "referrals");
    $referral = 1;
	}
	
	$lang->find_threads = $lang->sprintf($lang->find_threads, $memprofile['username']);
	$lang->find_posts = $lang->sprintf($lang->find_posts, $memprofile['username']);

	if($memprofile['dst'] == 1)
	{
		$memprofile['timezone']++;
		if(my_substr($memprofile['timezone'], 0, 1) != "-")
		{
			$memprofile['timezone'] = "+{$memprofile['timezone']}";
		}
	}
	$memregdate = my_date($daddyobb->settings['dateformat'], $memprofile['regdate']);
	$memlocaldate = gmdate($daddyobb->settings['dateformat'], TIME_NOW + ($memprofile['timezone'] * 3600));
	$memlocaltime = gmdate($daddyobb->settings['timeformat'], TIME_NOW + ($memprofile['timezone'] * 3600));

	$localtime = $lang->sprintf($lang->local_time_format, $memlocaldate, $memlocaltime);

	if($memprofile['lastactive'])
	{
		$memlastvisitdate = my_date($daddyobb->settings['dateformat'], $memprofile['lastactive']);
		$memlastvisitsep = ', ';
		$memlastvisittime = my_date($daddyobb->settings['timeformat'], $memprofile['lastactive']);
	}
	else
	{
		$memlastvisitdate = $lang->lastvisit_never;
		$memlastvisitsep = '';
		$memlastvisittime = '';
	}

	if($memprofile['birthday'])
	{
		$membday = explode("-", $memprofile['birthday']);
		
		if($memprofile['birthdayprivacy'] != 'none')
		{
			if($membday[2])
			{
				$lang->membdayage = $lang->sprintf($lang->membdayage, get_age($memprofile['birthday']));
				
				if($membday[2] >= 1970)
				{
					$w_day = date("l", mktime(0, 0, 0, $membday[1], $membday[0], $membday[2]));
					$membday = format_bdays($daddyobb->settings['dateformat'], $membday[1], $membday[0], $membday[2], $w_day);
				}
				else
				{
					$bdayformat = fix_mktime($daddyobb->settings['dateformat'], $membday[2]);
					$membday = mktime(0, 0, 0, $membday[1], $membday[0], $membday[2]);
					$membday = date($bdayformat, $membday);
				}
				$membdayage = $lang->membdayage;
			}
			else
			{
				$membday = mktime(0, 0, 0, $membday[1], $membday[0], 0);
				$membday = date("F j", $membday);
				$membdayage = '';
			}
		}
		
		if($memprofile['birthdayprivacy'] == 'age')
		{
			$membday = $lang->birthdayhidden;
		}
		else if($memprofile['birthdayprivacy'] == 'none')
		{
			$membday = $lang->birthdayhidden;
			$membdayage = '';
		}
	}
	else
	{
		$membday = $lang->not_specified;
		$membdayage = '';
	}
	
	if(!$memprofile['displaygroup'])
	{
		$memprofile['displaygroup'] = $memprofile['usergroup'];
	}
	$displaygroup = usergroup_displaygroup($memprofile['displaygroup']);

	// Get the user title for this user
	unset($usertitle);
	unset($stars);
	if(trim($memprofile['usertitle']) != '')
	{
		// User has custom user title
		$usertitle = $memprofile['usertitle'];
	}
	elseif(trim($displaygroup['usertitle']) != '')
	{
		// User has group title
		$usertitle = $displaygroup['usertitle'];
	}
	else
	{
		// No usergroup title so get a default one
		$query = $db->simple_select("usertitles", "*", "", array('order_by' => 'posts', 'order_dir' => 'DESC'));
		while($title = $db->fetch_array($query))
		{
			if($memprofile['postnum'] >= $title['posts'])
			{
				$usertitle = $title['title'];
				$stars = $title['stars'];
				$starimage = $title['starimage'];
				break;
			}
		}
	}
	
	if($displaygroup['stars'])
	{
		// Set the number of stars if display group has constant number of stars
		$stars = $displaygroup['stars'];
	}
	elseif(!$stars)
	{
		// This is for cases where the user has a title, but the group has no defined number of stars (use number of stars as per default usergroups)
		$query = $db->simple_select("usertitles", "*", "", array('order_by' => 'posts', 'order_dir' => 'DESC'));
		while($title = $db->fetch_array($query))
		{
			if($memprofile['postnum'] >= $title['posts'])
			{
				$stars = $title['stars'];
				$starimage = $title['starimage'];
				break;
			}
		}
	}

	if(!empty($displaygroup['image']))
	{
		if(!empty($daddyobb->user['language']))
		{
			$language = $daddyobb->user['language'];
		}
		else
		{
			$language = $daddyobb->settings['bblanguage'];
		}
		$displaygroup['image'] = str_replace("{lang}", $language, $displaygroup['image']);
		$displaygroup['image'] = str_replace("{theme}", $theme['imgdir'], $displaygroup['image']);
		$groupimage = 1;
	}

	if(!$starimage)
	{
		$starimage = $displaygroup['starimage'];
	}
	$starimage = str_replace("{theme}", $theme['imgdir'], $starimage);
	$userstars = '';
	for($i = 0; $i < $stars; ++$i)
	{
		$userstars .= "<img src=\"$starimage\" border=\"0\" alt=\"*\" />";
	}
	
	// User is currently online and this user has permissions to view the user on the WOL
	$timesearch = TIME_NOW - $daddyobb->settings['wolcutoffmins']*60;
	$query = $db->simple_select("sessions", "location", "uid='$uid' AND time>'{$timesearch}'", array('order_by' => 'time', 'order_dir' => 'DESC', 'limit' => 1));
	$location = $db->fetch_field($query, 'location');
		
  $location_time = my_date($daddyobb->settings['dateformat'], $memprofile['lastactive']).", <span class=\"time\">".my_date($daddyobb->settings['timeformat'], $memprofile['lastactive'])."</span>";

	if(($memprofile['invisible'] != 1 || $daddyobb->usergroup['canviewwolinvis'] == 1 || $memprofile['uid'] == $daddyobb->user['uid']) && $location)
	{
		// Fetch their current location
		$lang->load("online");
		require_once DADDYOBB_ROOT."inc/functions_online.php";
		$activity = fetch_wol_activity($location);
		$location = build_friendly_wol_location($activity);
  
    $on_off = "online";
    $lang->online_offline = $lang->sprintf($lang->postbit_status_online, $memprofile['username']);
	}
	// User is offline
	else
	{
	  $on_off = "offline";
    $lang->online_offline = $lang->sprintf($lang->postbit_status_offline, $memprofile['username']);
	}

	// Fetch the reputation for this user
	if($memperms['usereputationsystem'] == 1 && $daddyobb->settings['enablereputation'] == 1)
	{
		$reputation = get_reputation($memprofile['reputation']);

		// If this user has permission to give reputations show the vote link
		if($daddyobb->usergroup['cangivereputations'] == 1 && $memprofile['uid'] != $daddyobb->user['uid'])
		{
			$vote_link = "[<a href=\"javascript:DaddyoBB.reputation({$memprofile['uid']});\">{$lang->reputation_vote}</a>]";
		}
    $reputation = 1;
	}
	
	if($daddyobb->settings['enablewarningsystem'] != 0 && $memperms['canreceivewarnings'] != 0 && ($daddyobb->usergroup['canwarnusers'] != 0 || ($daddyobb->user['uid'] == $memprofile['uid'] && $daddyobb->settings['canviewownwarning'] != 0)))
	{
		$warning_level = round($memprofile['warningpoints']/$daddyobb->settings['maxwarningpoints']*100);
		if($warning_level > 100)
		{
			$warning_level = 100;
		}
		$warning_level = get_colored_warning_level($warning_level);
		if($daddyobb->usergroup['canwarnusers'] != 0 && $memprofile['uid'] != $daddyobb->user['uid'])
		{
			eval("\$warn_user = \"".$templates->get("member_profile_warn")."\";");
			$warning_link = "warnings.php?uid={$memprofile['uid']}";
		}
		else
		{
			$warning_link = "usercp.php";
		}
		$warn_level = 1;
	}

	$query = $db->simple_select("userfields", "*", "ufid='$uid'");
	$userfields = $db->fetch_array($query);
	$customfields = '';
	// If this user is an Administrator or a Moderator then we wish to show all profile fields
	if($daddyobb->usergroup['cancp'] == 1 || $daddyobb->usergroup['issupermod'] == 1 || $daddyobb->usergroup['gid'] == 6)
	{
		$field_hidden = '1=1';
	}
	else
	{
		$field_hidden = "hidden=0";
	}
	$query = $db->simple_select("profilefields", "*", "{$field_hidden}", array('order_by' => 'disporder'));
	while($customfield = $db->fetch_array($query))
	{
		$thing = explode("\n", $customfield['type'], "2");
		$type = trim($thing[0]);

		$field = "fid{$customfield['fid']}";
		$useropts = explode("\n", $userfields[$field]);
		$customfieldval = $comma = '';
		if(is_array($useropts) && ($type == "multiselect" || $type == "checkbox"))
		{
			foreach($useropts as $val)
			{
				if($val != '')
				{
					$customfieldval .= "<li style=\"margin-left: 0;\">{$val}</li>";
				}
			}
			if($customfieldval != '')
			{
				$customfieldval = "<ul style=\"margin: 0; padding-left: 15px;\">{$customfieldval}</ul>";
			}
		}
		else
		{
			if($customfield['type'] == "textarea")
			{
				$customfieldval = nl2br(htmlspecialchars_uni($userfields[$field]));
			}
			else
			{
				$customfieldval = htmlspecialchars_uni($userfields[$field]);
			}
		}
		eval("\$customfields .= \"".$templates->get("member_profile_customfields_field")."\";");
	}
	$memprofile['postnum'] = my_number_format($memprofile['postnum']);
	$formattedname = format_name($memprofile['username'], $memprofile['usergroup'], $memprofile['displaygroup']);

	$plugins->run_hooks("member_profile_end");
	
	eval("\$profile = \"".$templates->get("member_profile")."\";");
	output_page($profile);
}

if($daddyobb->input['action'] == "do_emailuser" && $daddyobb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($daddyobb->input['my_post_key']);

	$plugins->run_hooks("member_do_emailuser_start");

	// Guests or those without permission can't email other users
	if($daddyobb->usergroup['cansendemail'] == 0 || !$daddyobb->user['uid'])
	{
		error_no_permission();
	}
	
	// Check group limits
	if($daddyobb->usergroup['maxemails'] > 0)
	{
		$query = $db->simple_select("maillogs", "COUNT(*) AS sent_count", "fromuid='{$daddyobb->user['uid']}' AND dateline >= '".(TIME_NOW - (60*60*24))."'");
		$sent_count = $db->fetch_field($query, "sent_count");
		if($sent_count > $daddyobb->usergroup['maxemails'])
		{
			$lang->error_max_emails_day = $lang->sprintf($lang->error_max_emails_day, $daddyobb->usergroup['maxemails']);
			error($lang->error_max_emails_day);
		}
	}
	
	$query = $db->simple_select("users", "uid, username, email, hideemail", "uid='".intval($daddyobb->input['uid'])."'");
	$to_user = $db->fetch_array($query);
	
	if(!$to_user['username'])
	{
		error($lang->error_invalidusername);
	}
	
	if($to_user['hideemail'] != 0)
	{
		error($lang->error_hideemail);
	}
	
	if(empty($daddyobb->input['subject']))
	{
		$errors[] = $lang->error_no_email_subject;
	}
	
	if(empty($daddyobb->input['message']))
	{
		$errors[] = $lang->error_no_email_message;
	}

	if(count($errors) == 0)
	{
		if($daddyobb->settings['mail_handler'] == 'smtp')
		{
			$from = $daddyobb->user['email'];
		}
		else
		{
			$from = "{$daddyobb->user['username']} <{$daddyobb->user['email']}>";
		}
		
		$message = $lang->sprintf($lang->email_emailuser, $to_user['username'], $daddyobb->user['username'], $daddyobb->settings['bbname'], $daddyobb->settings['bburl'], $daddyobb->input['message']);
		my_mail($to_user['email'], $daddyobb->input['subject'], $message, $from, "", "", false, "text", "", $daddyobb->user['email']);
		
		if($daddyobb->settings['mail_logging'] > 0)
		{
			// Log the message
			$log_entry = array(
				"subject" => $db->escape_string($daddyobb->input['subject']),
				"message" => $db->escape_string($daddyobb->input['message']),
				"dateline" => TIME_NOW,
				"fromuid" => $daddyobb->user['uid'],
				"fromemail" => $db->escape_string($daddyobb->user['email']),
				"touid" => $to_user['uid'],
				"toemail" => $db->escape_string($to_user['email']),
				"tid" => 0,
				"ipaddress" => $db->escape_string($session->ipaddress)
			);
			$db->insert_query("maillogs", $log_entry);
		}

		$plugins->run_hooks("member_do_emailuser_end");

		redirect(get_profile_link($to_user['uid']), $lang->redirect_emailsent);
	}
	else
	{
		$daddyobb->input['action'] = "emailuser";
	}
}

if($daddyobb->input['action'] == 'vcard')
{	 
 	if($daddyobb->input['uid'])
	{
		$uid = intval($daddyobb->input['uid']);
	}
	else
	{
		$uid = $daddyobb->user['uid'];
  }
 
	$user = get_user($uid);
	
	if($user['vcard'] == 1)
	{
    $content = "BEGIN:VCARD\r\n";
    $content .= "VERSION:2.1\r\n";
    $content .= "N:;".$user['username']."\r\n";
    $content .= "FN:".$user['username']."\r\n";
    $content .= "EMAIL;PREF;INTERNET:".$user['email']."\r\n";
    if(!empty($user['birthday']) && $user['birthdayprivacy'] == "all")
    {
      $birthday = explode('-', $user['birthday']);
      //Fix the nulls
      if($birthday[1] < 10)
      {
        $birthday[1] = "0".$birthday[1];
      }
      if($birthday[0] < 10)
      {
        $birthday[0] = "0".$birthday[0];
      }
      $content .= "BDAY:".$birthday[2]."-".$birthday[1]."-".$birthday[0]."\r\n";
    }
    if(!empty($user['website']))
    {
      $content .= "URL:".$user['website']."\r\n";
    }
    $content .= "REV:".date('Y-m-d')."T".date('H:i:s')."Z\r\n";
    $content .= "END:VCARD\r\n";

    $filename = $user['username'].".vcf";

    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Length: ". strlen($content));
    header("Connection: close");
    header("Content-Type: text/x-vCard; name=$filename");
    echo $content;
    exit;
  }
}

if($daddyobb->input['action'] == "emailuser")
{
	$plugins->run_hooks("member_emailuser_start");

	// Guests or those without permission can't email other users
	if($daddyobb->usergroup['cansendemail'] == 0 || !$daddyobb->user['uid'])
	{
		error_no_permission();
	}
	
	// Check group limits
	if($daddyobb->usergroup['maxemails'] > 0)
	{
		$query = $db->simple_select("maillogs", "COUNT(*) AS sent_count", "fromuid='{$daddyobb->user['uid']}' AND dateline >= '".(TIME_NOW - (60*60*24))."'");
		$sent_count = $db->fetch_field($query, "sent_count");
		if($sent_count > $daddyobb->usergroup['maxemails'])
		{
			$lang->error_max_emails_day = $lang->sprintf($lang->error_max_emails_day, $daddyobb->usergroup['maxemails']);
			error($lang->error_max_emails_day);
		}
	}	
	
	$query = $db->simple_select("users", "uid, username, email, hideemail", "uid='".intval($daddyobb->input['uid'])."'");
	$to_user = $db->fetch_array($query);
	
	$lang->email_user = $lang->sprintf($lang->email_user, $to_user['username']);
	
	if(!$to_user['uid'])
	{
		error($lang->error_invaliduser);
	}
	
	if($to_user['hideemail'] != 0)
	{
		error($lang->error_hideemail);
	}
	
	if(count($errors) > 0)
	{
		$errors = inline_error($errors);
		$subject = htmlspecialchars_uni($daddyobb->input['subject']);
		$message = htmlspecialchars_uni($daddyobb->input['message']);
	}
	else
	{
		$errors = '';
		$subject = '';
		$message = '';
	}
	
	eval("\$emailuser = \"".$templates->get("member_emailuser")."\";");
	$plugins->run_hooks("member_emailuser_end");
	output_page($emailuser);
}
?>